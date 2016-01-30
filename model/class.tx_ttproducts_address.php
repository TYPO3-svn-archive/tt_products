<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Franz Holzinger <franz@ttproducts.de>
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
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_address extends tx_ttproducts_category_base {
	var $dataArray = array(); // array of read in categories
	var $fields = array();
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $piVar = 'a';
	var $marker = 'ADDRESS';

	public $tableObj;	// object of the type tx_table_db

	/**
	 * Getting all address values into internal array
	 *
	 * @param	[type]		$$pibase: ...
	 * @param	[type]		$functablename: ...
	 * @return	[type]		...
	 */
	function init ($pibase, $functablename)	{
		global $TYPO3_DB,$TSFE,$TCA;

		parent::init($pibase, $functablename);
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

		$tableconf = $cnf->getTableConf('address');
		$tabledesc = $cnf->getTableDesc('address');

		$tableObj = $this->getTableObj();
		$tablename = $this->getTablename();

		$tableObj->setConfig($tableconf);
		$defaultFieldArray = array('uid'=>'uid', 'pid'=>'pid');
		$checkDefaultFieldArray = array('tstamp'=>'tstamp', 'hidden'=>'hidden', 'deleted' => 'deleted');
		foreach ($checkDefaultFieldArray as $theField)	{
			if (isset($TCA[$tablename]['ctrl'][$theField]) && (is_array($TCA[$tablename]['columns'][$theField]) || in_array($theField,$TCA[$tablename]['ctrl'])))	{
				$defaultFieldArray[$theField] = $theField;
			}
		}
		$this->fields['name'] = ($tabledesc['name'] && is_array($TCA[$tabledesc['name']]['ctrl']) ? $tabledesc['name'] : ($TCA[$tablename]['ctrl']['label'] ? $TCA[$tablename]['ctrl']['label'] : 'name'));

		$tableObj->setDefaultFieldArray($defaultFieldArray);
		$tableObj->setNewFieldArray();
		$requiredListFields = 'uid,pid,'.$this->fields['name'];
		$requiredListArray = t3lib_div::trimExplode(',', $requiredListFields);
		$tableObj->setRequiredFieldArray($requiredListArray);
		$tableObj->setTCAFieldArray($tablename);
	} // init


	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getRootCat ()	{
		$rc = $this->conf['rootAddressID'];
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$excludeCats: ...
	 * @param	[type]		$rootUids: ...
	 * @param	[type]		$allowedCats: ...
	 * @return	[type]		...
	 */
	function &getRelationArray ($excludeCats = '', $rootUids = '', $allowedCats = '') {
		$relationArray = array();
		$rootArray = t3lib_div::trimExplode(',', $rootUids);

		if (is_array($this->dataArray))	{
			foreach ($this->dataArray as $k => $row)	{
				$uid = $row['uid'];
				$title = $row[$this->fields['name']];
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