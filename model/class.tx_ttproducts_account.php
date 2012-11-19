<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the tt_products (Shop System) extension.
 *
 * account functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_bank_de.php');

global $TYPO3_CONF_VARS;

class tx_ttproducts_account {
	var $pibase; // reference to object of pibase
	var $conf;
	var $acArray;	// credit card data
	var $bIsAllowed = array(); // allowed uids of bank ACCOUNTS
	var $fieldArray = array('owner_name', 'ac_number', 'bic');
	var $tablename = 'sys_products_accounts';
	var	$asterisk = '********';


	function init (&$pibase, &$cnf, $formerBasket, $bIsAllowed='') {

		$this->pibase = &$pibase;
		$this->conf = &$cnf->conf;
		$this->acArray = array();
		$this->acArray = $formerBasket['account'];
		if (isset($bIsAllowed))	{
			$this->bIsAllowed = $bIsAllowed;
		}

		$bNumberRecentlyModified = true;

		if (!$this->acArray['ac_number'])	{
			$bNumberRecentlyModified = false;
		}

		if ($bNumberRecentlyModified)	{
			global $TSFE;

			$acArray = $TSFE->fe_user->getKey('ses','ac');
			if (!$acArray)	{
				$acArray = array();
			}
			$acArray['ac_uid'] = $this->create ($acArray['ac_uid'], $this->acArray);
			$TSFE->fe_user->setKey('ses','ac',$acArray);
			$this->acArray['ac_number'] = $this->asterisk;
		}
	}


	// **************************
	// ORDER related functions
	// **************************
	/**
	 * Create a new credit card record
	 *
	 * This creates a new credit card record on the page with pid PID_sys_products_orders. That page must exist!
	 * Should be called only internally by eg. $order->getBlankUid, that first checks if a blank record is already created.
	 */
	function create ($uid, $acArray)	{
		global $TSFE, $TYPO3_DB;

		$newId = 0;
		$pid = intval($this->conf['PID_sys_products_orders']);
		if (!$pid)	$pid = intval($TSFE->id);

		if ($acArray['ac_number'] && $TSFE->sys_page->getPage_noCheck ($pid))	{
			$time = time();
			$newFields = array (
				'pid' => intval($pid),
				'tstamp' => $time,
				'crdate' => $time,
				'owner_name' => $acArray['owner_name'],
				'bic' => $acArray['bic']
			);
			if ( strcmp ($acArray['ac_number'], $this->asterisk) != 0)	{
				$newFields['ac_number'] = $acArray['ac_number'];
			}

			if ($uid)	{
				$TYPO3_DB->exec_UPDATEquery($this->tablename,'uid='.$uid,$newFields);
				$newId = $uid;
			} else {
				$TYPO3_DB->exec_INSERTquery($this->tablename, $newFields);
				$newId = $TYPO3_DB->sql_insert_id();
			}
		}
		return $newId;
	} // create


	function getUid ()	{
		global $TSFE;

		$acArray = $TSFE->fe_user->getKey('ses','ac');
		return $acArray['ac_uid'];
	}


	function get ($uid, $bFieldArrayAll=false) {
		global $TYPO3_DB;
		$rcArray = array();
		if ($bFieldArrayAll)	{
			foreach ($this->fieldArray as $k => $field)	{
				$rcArray [$field] = '';
			}
		}

		if ($uid) {
			$where = 'uid = '.intval($uid);
			// Fetching the products
			$fields = '*';
			if ($bFieldArrayAll)	{
				$fields = implode(',',$this->fieldArray);
			}
			$res = $TYPO3_DB->exec_SELECTquery($fields, $this->tablename, $where);
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			if ($row)	{
				$rcArray = $row;
			}
		}
		return $rcArray;
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	function getItemMarkerArray (&$markerArray)	{
		global $TCA;
		global $TYPO3_CONF_VARS;

		include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_form_div.php');

		if ($this->bIsAllowed)	{
			$acNumber = $this->acArray['ac_number'];
			$acOwnerName = $this->acArray['owner_name'];
			$acBic = $this->acArray['bic'];
		} else {
			$acNumber = '';
			$acOwnerName = '';
			$acBic = '';
		}

		$markerArray['###PERSON_ACCOUNTS_OWNER_NAME###'] = $acOwnerName;
		$markerArray['###PERSON_ACCOUNTS_AC_NUMBER###'] = $acNumber;
		$markerArray['###PERSON_ACCOUNTS_BIC###'] = $acBic;
	} // getMarkerArray


	/**
	 * Checks if required fields for bank accounts are filled in
	 */
	function checkRequired ()	{
		$rc = '';
		$bankObj = t3lib_div::getUserObj('&tx_ttproducts_bank_de');
		$bankObj->init();

		foreach ($this->fieldArray as $k => $field)	{
			if (!$this->acArray[$field])	{
				$rc = $field;
				break;
			}
			if ($field == 'bic' && is_object($bankObj) /* && t3lib_extMgm::isLoaded('static_info_tables_banks_de')*/)	{
				$where_clause = 'sort_code=' . intval(implode('',t3lib_div::trimExplode(' ',$this->acArray[$field]))) . ' AND level=1';
				$bankRow = $bankObj->get('',0,FALSE,$where_clause);

				if (!$bankRow)	{
					$rc = $field;
					break;
				}
			}
		}
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_account.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_account.php']);
}


?>
