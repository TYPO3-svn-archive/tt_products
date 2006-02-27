<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2006 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * Part of the tt_products (Shopping System) extension.
 *
 * functions for the product
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

class tx_ttproducts_article {
	var $dataArray; // array of read in categories
	var $table;		 // object of the type tx_table_db
	var $fields = array();
	var $pibase; // reference to object of pibase

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, $LLkey, $tablename)  {
		global $TYPO3_DB,$TSFE,$TCA;
		
		$this->pibase = $pibase;
		$tablename = ($tablename ? $tablename : 'tt_products_articles');
		$this->table = t3lib_div::makeInstance('tx_table_db');
		$this->table->setTCAFieldArray($tablename,'articles');

	} // init



	function get ($uid) {
		global $TYPO3_DB;
		$rc = $this->dataArray[$uid];
		if (!$rc) {
//			$sql = t3lib_div::makeInstance('tx_table_db_access');
//			$sql->prepareFields($this->table, 'select', '*');
//			$sql->prepareFields($this->table, 'where', 'uid = '.$uid);
//			$sql->prepareWhereFields ($this->table, 'uid', '=', $uid);
			$this->table->enableFields($this->table->name);		
			// Fetching the products
			// $res = $sql->exec_SELECTquery();
			$res = $this->table->exec_SELECTquery('*','uid = '.$uid);
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$rc = $this->dataArray[$row['uid']] = $row;
		}
		return $rc;
	}



}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_article.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_article.php']);
}


?>
