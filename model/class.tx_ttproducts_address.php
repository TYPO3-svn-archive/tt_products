<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Franz Holzinger <contact@fholzinger.com>
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
 * functions for the frontend users
 *
 * $Id$
 *
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

/*
require_once(PATH_BE_table.'lib/class.tx_table_db.php');
require_once(PATH_BE_ttproducts.'model/class.tx_ttproducts_category_base.php');*/


class tx_ttproducts_address extends tx_ttproducts_category_base {
	var $dataArray = array(); // array of read in categories
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $piVar = 'a';
	var $marker = 'ADDRESS';

	public $tableObj;	// object of the type tx_table_db

	/**
	 * Getting all address values into internal array
	 */
	function init(&$pibase, $functablename)	{
		global $TYPO3_DB,$TSFE,$TCA;

		parent::init($pibase, $functablename);
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

		$tableconf = $cnf->getTableConf('address');
		$tabledesc = $cnf->getTableDesc('address');

		$tableObj = $this->getTableObj();
		$tablename = $this->getTablename();

		$tableObj->setConfig($tableconf);
		$defaultFieldArray = $this->getDefaultFieldArray();
		$tableObj->setDefaultFieldArray($defaultFieldArray);
		$tableObj->setNewFieldArray();
		$requiredFields = 'uid,pid,title';
		$tableconf = $cnf->getTableConf($functablename);
		if ($tableconf['requiredFields'])	{
			$tmp = $tableconf['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}

		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$tableObj->setRequiredFieldArray($requiredListArray);
		$tableObj->setTCAFieldArray($tablename);

		if (isset($tabledesc) && is_array($tabledesc))	{
			$this->fieldArray = array_merge($this->fieldArray, $tabledesc);
		}
	} // init


	function getRootCat()	{
		$rc = $this->conf['rootAddressID'];
		return $rc;
	}


	function &getRelationArray ($excludeCats = '', $rootUids = '', $allowedCats = '') {
		$relationArray = array();
		$rootArray = t3lib_div::trimExplode(',', $rootUids);

		if (is_array($this->dataArray))	{
			foreach ($this->dataArray as $k => $row)	{
				$uid = $row['uid'];
				$title = $row[$this->getField('name')];
				$relationArray [$uid]['title'] = $title;
				$relationArray [$uid]['pid'] = $row['pid'];
				$relationArray [$uid]['parent_category'] = '';
			}
		}

		return $relationArray;
	}

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_address.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_address.php']);
}


?>