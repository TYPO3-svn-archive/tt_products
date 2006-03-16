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
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_variant.php');



class tx_ttproducts_product {
	var $dataArray; // array of read in categories
	var $table;		 // object of the type tx_table_db
	var $fields = array();
	var $pibase; // reference to object of pibase
	var $variant; // object for the product variant attributes

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, $LLkey, $tablename, &$tableconf, $bUseArticles)  {
		global $TYPO3_DB,$TSFE,$TCA;
		
		$this->pibase = $pibase;
		$tablename = ($tablename ? $tablename : 'tt_products');
		$this->table = t3lib_div::makeInstance('tx_table_db');
		$this->table->setTCAFieldArray($tablename);
		
		$requiredListFields = ($tableconf['requiredListFields'] ? $tableconf['requiredListFields'] : 'uid,pid,category,price,price2,tax');
		$requiredListArray = t3lib_div::trimExplode(',', $requiredListFields);
		$this->table->setRequiredFieldArray($requiredListArray);
		
		if ($TSFE->config['config']['sys_language_uid'] && ($tablename == 'tt_products')) {
			$this->table->setLanguage ($LLkey);
			$this->table->setTCAFieldArray('tt_products_language');
		}
		
		$this->variant = t3lib_div::makeInstance('tx_ttproducts_variant');
		$this->variant->init($tableconf['variant.'], $bUseArticles);
		
		$this->fields['itemnumber'] = ($tableconf['itemnumber'] ? $tableconf['itemnumber'] : 'itemnumber');
	} // init



	function get ($uid) {
		global $TYPO3_DB;
		$rc = $this->dataArray[$uid];
		if (!$rc) {
//			$sql = t3lib_div::makeInstance('tx_table_db_access');
//			$sql->prepareFields($this->table, 'select', '*');
//			$sql->prepareFields($this->table, 'where', 'uid = '.$uid);
//			$sql->prepareWhereFields ($this->table, 'uid', '=', $uid);
			$where = '1=1 '.$this->table->enableFields($this->table->name);		
			// Fetching the products
			// $res = $sql->exec_SELECTquery();
			$res = $this->table->exec_SELECTquery('*',$where.'AND uid = '.$uid);
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


	/**
	 * Reduces the instock value of the orderRecord with the sold items and returns the result
	 * 
	 */
	function reduceInStock(&$itemArray, $useArticles)	{
		global $TYPO3_DB, $TCA;
		$rc = '';

		if ($this->table->name == 'tt_products' || is_array(($TCA[$this->table->name]['columns']['inStock'])) )	{		
			// Reduce inStock
	
			// loop over all items in the basket indexed by page and itemnumber
			foreach ($itemArray as $pid=>$pidItem) {
				foreach ($pidItem as $itemnumber=>$actItemArray) {
					#error_Log ("tt_products  $this->conf['useArticles'] = ".$this->conf['useArticles']);
					if ($useArticles) {
						foreach ($actItemArray as $k1=>$actItem) {
							$query='uid_product=\''.intval($actItem['rec']['uid']).'\' AND color=\''.$actItem['rec']['color'].'\' AND size=\''.$actItem['rec']['size'].'\' AND description=\''.$actItem['rec']['description'].'\' AND gradings=\''.$actItem['rec']['gradings'].'\'';
	
							$res = $TYPO3_DB->exec_SELECTquery('inStock', 'tt_products_articles', $query);
								//  TODO: Saving the order record support color, size, description and gradings here
						}
					} else {
						foreach ($actItemArray as $k1=>$actItem) {
							$query='uid=\''.intval($actItem['rec']['uid']).'\'';
	
							$res = $TYPO3_DB->exec_SELECTquery('inStock', $this->table->name, $query);
	
							if ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
								if ($row['inStock'] > 0) {
									$newInStock = intval($row['inStock'])-intval($actItem['count']);
									if ($newInStock < 0) {
										$newInStock = 0;
									}
	
									$fieldsArray =array();
											// Setting tstamp, deleted and tracking code
									$fieldsArray['inStock']=$newInStock;
	
									$res = $TYPO3_DB->exec_UPDATEquery($this->table->name, 'uid='.intval($actItem['rec']['uid']), $fieldsArray);
								}
							}
						}
					}
				}
			}
		}
		return $rc;
	}



	/**
	 * Returns true if the product is a single product
	 * 
	 */
	function isSingle(&$row)  {
		$additional = t3lib_div::xml2array($row['additional']);
		$isSingle = $this->pibase->pi_getFFvalue($additional, 'isSingle');
		return $isSingle; 
	}



	/**
	 * Returns true if the product has additional attribute settings
	 * 
	 */
	function hasAdditional(&$row) {
		$rc = false;
		if ($this->isSingle($row)) {
			$rc = true;
		}
		return $rc; 
	}


	/**
	 * Generates a search where clause.
	 */
	function searchWhere(&$searchFieldList, $sw)	{
		$where=$this->pibase->cObj->searchWhere($sw, $searchFieldList, $this->table->name);
		return $where;
	} // searchWhere


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_product.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_product.php']);
}


?>
