<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_account extends tx_ttproducts_table_base {
	public $pibase; // reference to object of pibase
	public $conf;
	public $acArray;	// credit card data
	public $bIsAllowed = array(); // allowed uids of bank ACCOUNTS
	public $fieldArray = array('owner_name', 'ac_number', 'bic');
	public $tablename = 'sys_products_accounts';
	public $asterisk = '********';
	public $useAsterisk = FALSE;


	function init ($pibase, $functablename) {
		$basketObj = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$formerBasket = $basketObj->recs;
		$bIsAllowed = $basketObj->basketExtra['payment.']['accounts'];
		if (isset($basketObj->basketExtra['payment.']['useAsterisk']))	{
			$this->useAsterisk = $basketObj->basketExtra['payment.']['useAsterisk'];
		}

		parent::init($pibase, 'sys_products_accounts');
		$this->acArray = array();
		$this->acArray = $formerBasket['account'];
		if (isset($bIsAllowed))	{
			$this->bIsAllowed = $bIsAllowed;
		}

		$bNumberRecentlyModified = TRUE;

		if (!$this->acArray['ac_number'])	{
			$bNumberRecentlyModified = FALSE;
		}

		if ($bNumberRecentlyModified)	{
			global $TSFE;

			$acArray = $TSFE->fe_user->getKey('ses','ac');
			if (!$acArray)	{
				$acArray = array();
			}
			$acArray['ac_uid'] = $this->create($acArray['ac_uid'], $this->acArray);
			$TSFE->fe_user->setKey('ses','ac',$acArray);
			if ($this->useAsterisk)	{
				$this->acArray['ac_number'] = $this->asterisk;
			}
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

		if ($acArray['owner_name'] != '' && $acArray[$accountField] && $TSFE->sys_page->getPage_noCheck($pid)) {
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
				$TYPO3_DB->exec_UPDATEquery($this->tablename, 'uid=' . $uid,$newFields);
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

		$acArray = $TSFE->fe_user->getKey('ses', 'ac');
		return $acArray['ac_uid'];
	}


	function get ($uid, $bFieldArrayAll = FALSE) {
		global $TYPO3_DB;
		$rcArray = array();
		if ($bFieldArrayAll)	{
			foreach ($this->fieldArray as $k => $field)	{
				$rcArray [$field] = '';
			}
		}

		if ($uid) {
			$where = 'uid = ' . intval($uid);
			// Fetching the products
			$fields = '*';
			if ($bFieldArrayAll)	{
				$fields = implode(',', $this->fieldArray);
			}
			$res = $TYPO3_DB->exec_SELECTquery($fields, $this->tablename, $where);
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$TYPO3_DB->sql_free_result($res);
			if ($row)	{
				$rcArray = $row;
			}
		}
		return $rcArray;
	}


	/**
	 * Returns the label of the record, Usage in the following format:
	 *
	 * @param	array		$row: current record
	 * @return	string		Label of the record
	 */
	public function getLabel ($row) {
		return $row['owner_name'] . ':' . $row['ac_number'] . ':' . $row['bic'];
	}


	/**
	 * Checks if required fields for bank accounts are filled in
	 */
	function checkRequired ()	{
		$rc = '';
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		if (t3lib_extMgm::isLoaded('static_info_tables_banks_de')) {
			$bankObj = $tablesObj->get('static_banks_de');
		}

		foreach ($this->fieldArray as $k => $field)	{
			if (!$this->acArray[$field])	{
				$rc = $field;

				break;
			}
			if ($field == 'bic' && is_object($bankObj) /* && t3lib_extMgm::isLoaded('static_info_tables_banks_de')*/)	{
				$where_clause = 'sort_code=' . intval(implode('', t3lib_div::trimExplode(' ', $this->acArray[$field]))) . ' AND level=1';

				$bankRow = $bankObj->get('', 0, FALSE, $where_clause);

				if (!$bankRow)	{
					$rc = $field;
					break;
				}
			}
		}
		return $rc;
	} // checkRequired
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_account.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_account.php']);
}


?>