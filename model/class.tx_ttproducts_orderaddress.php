<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
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
 * functions for the order addresses
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_table.'lib/class.tx_table_db.php');


class tx_ttproducts_orderaddress extends tx_ttproducts_table_base {
	var $dataArray; // array of read in frontend users
	var $table;		 // object of the type tx_table_db
	var $fields = array();
	var $tableconf;
	var $piVar = 'fe';
	var $marker = 'FEUSER';
	var $image;

	private $bCondition = FALSE;
	private $bConditionRecord = FALSE;

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init($pibase, $functablename)  {
		global $TYPO3_DB,$TSFE,$TCA;

		parent::init($pibase, $functablename);
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

		$this->tableconf = $cnf->getTableConf($functablename);
		$tablename = $this->getTablename ();

			// image
		$this->image = t3lib_div::makeInstance('tx_ttproducts_field_image_view');
		$this->image->init($this->pibase->cObj, $this->pibase);

		$this->getTableObj()->setTCAFieldArray($tablename);
		$this->fields['payment'] = ($this->tableconf['payment'] ? $this->tableconf['payment'] : '');
		$requiredFields = 'uid,pid,email'.($this->fields['payment'] ? ','.$this->fields['payment'] : '');
		if (is_array($this->tableconf['ALL.']))	{
			$tmp = $this->tableconf['ALL.']['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}
		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$this->getTableObj()->setRequiredFieldArray($requiredListArray);
	} // init


	function getSelectInfoFields() {
		$result = array('salutation', 'tt_products_business_partner', 'tt_products_organisation_form');

		return $result;
	}


	function getTCATableFromField ($field) {
		$result = 'fe_users';
		if ($field == 'salutation') {
			$result = 'sys_products_orders';
		}
		return $result;
	}


	public function get ($uid) {
		global $TYPO3_DB;

		$rc = $this->dataArray[$uid];
		if (!$rc && $uid) {
			$where = '1=1 '.$this->getTableObj()->enableFields();
			$res = $this->getTableObj()->exec_SELECTquery('*',$where.' AND uid = '.intval($uid));
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$TYPO3_DB->sql_free_result($res);
			$rc = $this->dataArray[$row['uid']] = $row;
		}
		return $rc;
	}


	public function getFieldName ($field)	{
		$rc = $field;
		if (is_array($this->fields) && $this->fields[$field])	{
			$rc = $this->fields[$field];
		}

		return $rc;
	}


	public function isUserInGroup($feuser, $group)	{
		$groups = explode(',', $feuser['usergroup']);
		foreach ($groups as $singlegroup)
			if ($singlegroup == $group)
				return true;
		return false;
	} // isUserInGroup


	public function setCondition($row, $funcTablename)	{
		global $TSFE;

		$bCondition = FALSE;

		if (isset($this->conf['conf.'][$funcTablename.'.']['ALL.']['fe_users.']['date_of_birth.']['period.']['y']))	{
			$year = $this->conf['conf.'][$funcTablename.'.']['ALL.']['fe_users.']['date_of_birth.']['period.']['y'];
			$infoObj = t3lib_div::getUserObj('&tx_ttproducts_info_view');

			if ($infoObj->infoArray['billing']['date_of_birth'])	{
				$timeTemp = $infoObj->infoArray['billing']['date_of_birth'];
				$bAge = TRUE;
			} else if ($TSFE->fe_user->user)	{
				$timeTemp = date('d-m-Y', ($TSFE->fe_user->user['date_of_birth']));
				$bAge = TRUE;
			} else {
				$bAge = FALSE;
			}

			if ($bAge)	{
				$feDateArray = t3lib_div::trimExplode('-', $timeTemp);
				$date = getdate();
				$offset = 0;
				if ($date['mon'] < $feDateArray[1])	{
					$offset = 1;
				}
				if ($date['year'] - $feDateArray[2] - $offset >= $year)	{
					$bCondition = TRUE;
				}
			}
		} else {
			$bCondition = TRUE;
		}

		$whereConf = $this->conf['conf.'][$funcTablename.'.']['ALL.']['fe_users.']['where'];
		$whereArray = t3lib_div::trimExplode('IN', $whereConf);
		$pos1 = strpos ($whereArray[1], '(');
		$pos2 = strpos ($whereArray[1], ')');
		$inString = substr ($whereArray[1], $pos1+1, $pos2-$pos1-1);

		$valueArray = t3lib_div::trimExplode(',', $inString);
		foreach ($valueArray as $value)	{
			if ($row[$whereArray[0]] == $value)	{
				$this->bConditionRecord = TRUE;
			}
		}

		if ($bCondition)	{
			$this->bCondition = TRUE;
		}
	}

	public function getCondition()	{
		return $this->bCondition;
	}

	public function getConditionRecord()	{
		return $this->bConditionRecord;
	}

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_orderaddress.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_orderaddress.php']);
}


?>