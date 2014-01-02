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
 * functions for the content
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


require_once(PATH_BE_table.'lib/class.tx_table_db.php');
require_once(PATH_BE_table.'lib/class.tx_table_db_access.php');


class tx_ttproducts_content extends tx_ttproducts_table_base {
	var $dataArray=array(); // array of read in contents
	var $dataPageArray=array(); // array of read in contents with page id as index
	var $table;		 // object of the type tx_table_db

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init (&$cObj, $functablename)	{
		parent::init($cObj, $functablename);

		$this->getTableObj()->setDefaultFieldArray(array('uid'=>'uid', 'pid'=>'pid', 't3ver_oid'=>'t3ver_oid', 't3ver_id' => 't3ver_id', 't3ver_label' => 't3ver_label', 'tstamp'=>'tstamp', 'sorting'=> 'sorting',
		'deleted' => 'deleted', 'hidden'=>'hidden', 'starttime' => 'starttime', 'endtime' => 'endtime', 'fe_group' => 'fe_group'));
		$this->getTableObj()->setTCAFieldArray('tt_content');
	} // init

// 	function get ($uid=0,$pid=0,$bStore=true,$where_clause='',$limit='',$fields='',$bCount=FALSE) {
// 		global $TYPO3_DB;
// 		$rc = $this->dataArray[$uid];
// 		if (!$rc) {
// 			$sql = t3lib_div::makeInstance('tx_table_db_access');
// 			$sql->prepareFields($this->getTableObj(), 'select', '*');
// 			$sql->prepareWhereFields($this->getTableObj(), 'uid', '=', intval($uid));
// 			$this->getTableObj()->enableFields();
// 			// Fetching the category
// 			$res = $sql->exec_SELECTquery();
// 			$row = $TYPO3_DB->sql_fetch_assoc($res);
// 			$TYPO3_DB->sql_free_result($res);
// 			$rc = $this->dataArray[$row['uid']] = $row;
// 		}
// 		return $rc;
// 	}

	public function getFromPid ($pid) {
		global $TYPO3_DB, $TSFE;
		$rcArray = $this->dataPageArray[$pid];
		if (!is_array($rcArray)) {
			$sql = t3lib_div::makeInstance('tx_table_db_access');
			$sql->prepareFields($this->getTableObj(), 'select', '*');
			$sql->prepareFields($this->getTableObj(), 'orderBy', 'sorting');
			$sql->prepareWhereFields ($this->getTableObj(), 'pid', '=', intval($pid));
			$sql->prepareWhereFields ($this->getTableObj(), 'sys_language_uid', '=', intval($TSFE->config['config']['sys_language_uid']));

			$enableFields = $this->getTableObj()->enableFields();
			$sql->where_clause .= $enableFields;

			// Fetching the category
			$res = $sql->exec_SELECTquery();
			while ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
				$this->dataPageArray[$pid][$row['uid']] = $row;
			}
			$TYPO3_DB->sql_free_result($res);
			$tmp = $this->dataPageArray[$pid];
			$rcArray = (is_array($tmp) ? $tmp : array());
		}
		return $rcArray;
	}

	// returns the Path of all categories above, separated by '/'
	public function getCategoryPath ($uid) {
		$rc = '';

		return $rc;
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_content.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_content.php']);
}


?>
