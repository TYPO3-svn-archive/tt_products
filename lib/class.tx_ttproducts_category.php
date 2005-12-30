<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2005 Franz Holzinger <kontakt@fholzinger.com>
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
 * functions for the category
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once(PATH_BE_table.'lib/class.tx_table_db.php');
require_once(PATH_BE_table.'lib/class.tx_table_db_access.php');
require_once(PATH_BE_ttproducts.'lib/class.tx_ttproducts_email.php');

class tx_ttproducts_category {
	var $dataArray;	// array of read in categories
	var $table;			// object of the type tx_table_db
	var $tt_products_email;						// object of the type tx_table_db


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init($LLkey)	{
		global $TYPO3_DB,$TSFE;
		
		$this->table = t3lib_div::makeInstance('tx_table_db');
		$this->table->setTCAFieldArray('tt_products_cat');
 		if ($TSFE->config['config']['sys_language_uid']) {
			$this->table->setLanguage ($LLkey);
			$this->table->setTCAFieldArray('tt_products_cat_language');
 		}
	} // init



	function get ($uid) {
		global $TYPO3_DB;
		$rc = $this->dataArray[$uid];
		if (!$rc) {
			$sql = t3lib_div::makeInstance('tx_table_db_access');
			$sql->prepareFields($this->table, 'select', '*');
			$sql->prepareFields($this->table, 'where', 'uid = '.$uid);
			$sql->prepareWhereFields ($this->table, 'uid', '=', $uid);
			$this->table->enableFields('tt_products_cat');		 
			// Fetching the category
		 	$res = $sql->exec_SELECTquery();
		 	$row = $TYPO3_DB->sql_fetch_assoc($res);
		 	$rc = $this->dataArray[$row['uid']] = $row;
		}
		return $rc;
	}


	// returns the Path of all categories above, separated by '/'
	function getPath ($uid) {
		$rc = '';
		
		return $rc;
	}


	// returns the delivery email addresses from the basket`s item array with the category number as index
	function getEmail (&$itemArray) {
		$emailArray = array();
		$this->tt_products_email = t3lib_div::makeInstance('tx_ttproducts_email');
		$this->tt_products_email->init();
		// loop over all items in the basket indexed by page and itemnumber
		foreach ($itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					$category = $this->get($actItem['rec']['category']);
					$tmp = $this->tt_products_email->getEmail($category['email_uid']);
					if ($tmp) {
						$emailArray[$actItem['rec']['category']] = $tmp['name'].' <'.$tmp['email'].'>';
					} 
				}
			}
		}
		return $emailArray;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_category.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_category.php']);
}


?>
