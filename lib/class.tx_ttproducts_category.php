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
	var $categoryArray;	// array of read in categories
	var $tt_products_cat;			// object of the type tx_table_db
	var $tt_products_email;						// object of the type tx_table_db

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init()	{
		global $TYPO3_DB;
		
		$this->tt_products_cat = t3lib_div::makeInstance('tx_table_db');
		$this->tt_products_cat->setTCAFieldArray('tt_products_cat');
	} // init



	function categorycomp($row1, $row2)  {
		return strcmp($this->categories[$row1['category']], $this->categories[$row2['category']]);
	} // categorycomp


	function getCategory ($uid) {
		global $TYPO3_DB;
		#debug ($uid, 'getCategory $uid', __LINE__, __FILE__);
		$rc = $this->categoryArray[$uid];
		#debug ($rc, '$rc', __LINE__, __FILE__);
		if (!$rc) {
			$sql = t3lib_div::makeInstance('tx_table_db_access');
			$sql->prepareFields($this->tt_products_cat, 'select', '*');
			$sql->prepareFields($this->tt_products_cat, 'where', 'uid = '.$uid);
			$sql->prepareWhereFields ($this->tt_products_cat, 'uid', '=', $uid);
			$this->tt_products_cat->enableFields('tt_products_cat');		 
			// Fetching the category
		 	$res = $sql->exec_SELECTquery();
		 	$row = $TYPO3_DB->sql_fetch_assoc($res);
			#debug ($row, '$row', __LINE__, __FILE__);
		 	$rc = $this->categoryArray[$row['uid']] = $row;
		}
		return $rc;
	}


	// returns the delivery email addresses from the basket`s item array with the category number as index
	function getCategoryEmail (&$itemArray) {
		$emailArray = array();
		$this->tt_products_email = t3lib_div::makeInstance('tx_ttproducts_email');
		$this->tt_products_email->init();
		// loop over all items in the basket indexed by page and itemnumber
		foreach ($itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					#debug ($actItem, '$actItem', __LINE__, __FILE__);
					$category = $this->getCategory ($actItem['rec']['category']);
					#debug ($category, '$category', __LINE__, __FILE__);
					$emailArray[$actItem['rec']['category']] = $this->tt_products_email->getEmail($category['email_uid']);
					
				}
			}
		}
		#debug ($emailArray, '$emailArray', __LINE__, __FILE__);
		return $emailArray;
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_category.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_category.php']);
}


?>
