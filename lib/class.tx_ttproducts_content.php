<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Franz Holzinger <kontakt@fholzinger.com>
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
 * Part of the tt_products (Shopping System) extension.
 *
 * functions for the content
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once(PATH_BE_table.'lib/class.tx_table_db.php');
require_once(PATH_BE_table.'lib/class.tx_table_db_access.php');


class tx_ttproducts_content {
	var $dataArray; // array of read in contents
	var $dataPageArray; // array of read in contents with page id as index
	var $table;		 // object of the type tx_table_db

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init() {
		global $TYPO3_DB;
		
		$this->table = t3lib_div::makeInstance('tx_table_db');
		$this->table->setDefaultFieldArray(array('uid'=>'uid', 'pid'=>'pid', 't3ver_oid'=>'t3ver_oid', 't3ver_id' => 't3ver_id', 't3ver_label' => 't3ver_label', 'tstamp'=>'tstamp', 'sorting'=> 'sorting',
			'deleted' => 'deleted', 'hidden'=>'hidden', 'starttime' => 'starttime', 'endtime' => 'endtime', 'fe_group' => 'fe_group'));
		$this->table->setTCAFieldArray('tt_content');
	} // init


	function get ($uid) {
		global $TYPO3_DB;
		$rc = $this->dataArray[$uid];
		if (!$rc) {
			$sql = t3lib_div::makeInstance('tx_table_db_access');
			$sql->prepareFields($this->table, 'select', '*');
			$sql->prepareWhereFields ($this->table, 'uid', '=', intval($uid));
			$this->table->enableFields();		
			// Fetching the category
			$res = $sql->exec_SELECTquery();
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$rc = $this->dataArray[$row['uid']] = $row;
		}
		return $rc;
	}


	function getFromPid ($pid) {
		global $TYPO3_DB;
		$rc = $this->dataPageArray[$pid];
		if (!$rc) {
			$sql = t3lib_div::makeInstance('tx_table_db_access');
			$sql->prepareFields($this->table, 'select', '*');
			$sql->prepareWhereFields ($this->table, 'pid', '=', intval($pid));
			$this->table->enableFields();		
			// Fetching the category
			$res = $sql->exec_SELECTquery();
			$row = '';
			while ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
				$this->dataPageArray[$pid][$row['uid']] = $row;
			}
			$rc = $this->dataPageArray[$pid];
		}
		return $rc;
	}


	// returns the Path of all categories above, separated by '/'
	function getCategoryPath ($uid) {
		$rc = '';
		
		return $rc;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_content.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_content.php']);
}


?>
