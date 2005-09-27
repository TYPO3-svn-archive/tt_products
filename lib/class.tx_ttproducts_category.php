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


class tx_ttproducts_category {
	var $categoryArray;	// array of read in categories
	var $tt_products_cat;			// object of the type tx_table_db

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function initCategories()	{
		global $TYPO3_DB;
		
		$this->tt_products_cat = t3lib_div::makeInstance('tx_table_db');
		$this->tt_products_cat->setTCAFieldArray('tt_products_cat');
		
//		$sql = t3lib_div::makeInstance('tx_table_db_access');
//		
//		$sql->prepareFields($this->tt_products_cat, 'select', '*');
//		$this->tt_products_cat->enableFields('tt_products_cat');
//		
//			// Fetching categories:
//	 	$res = $sql->exec_SELECTquery();
//		$this->categories = array();
//		while($row = $TYPO3_DB->sql_fetch_assoc($res))	{
//			debug ($row, '$row', __LINE__, __FILE__);
//			$this->categories[$row['uid']] = $row['title'];
//		}
//		
//		debug ($this->categories, '$this->categories', __LINE__, __FILE__);
	} // initCategories



	function categorycomp($row1, $row2)  {
		return strcmp($this->categories[$row1['category']], $this->categories[$row2['category']]);
	} // categorycomp


	function getCategory ($uid) {
		$rc = $this->categoryArray[$uid];;
		if (!$rc) {
			$sql = t3lib_div::makeInstance('tx_table_db_access');
			$sql->prepareFields($this->tt_products_cat, 'select', '*');
			$sql->prepareFields($this->tt_products_cat, 'where', 'uid = '.$uid);
			$this->tt_products_cat->enableFields('tt_products_cat');		 
			// Fetching the category
		 	$res = $sql->exec_SELECTquery();
		 	$rc = $this->categoryArray[$row['uid']] = $row;
		}
		return $rc;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_category.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_category.php']);
}


?>
