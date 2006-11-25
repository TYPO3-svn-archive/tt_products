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
 * Part of the tt_products (Shop System) extension.
 *
 * functions for the category
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
require_once(PATH_BE_ttproducts.'lib/class.tx_ttproducts_email.php');
require_once(PATH_BE_ttproducts.'model/class.tx_ttproducts_category_base.php');

class tx_ttproducts_category extends tx_ttproducts_category_base {
	var $tt_products_email;				// object of the type tx_table_db
	var $tableconf;
	var $image;
	var $cnf;
	var $piVar = 'cat';

	/**
	 * initialization with table object and language table
	 */
	function init(&$pibase, &$cnf, &$tt_content, $LLkey, $tablename)	{
		global $TYPO3_DB;
		
		$this->cnf = &$cnf;
		$tablename = ($tablename ? $tablename : 'tt_products_cat');
		$this->tableconf = $this->cnf->getTableConf('tt_products_cat');	
		$this->table = t3lib_div::makeInstance('tx_table_db');
		$this->table->addDefaultFieldArray(array('sorting' => 'sorting'));
		$this->table->setTCAFieldArray($tablename);

//		if ($TSFE->config['config']['sys_language_uid'] && 
//				(!$this->catconf['language.'] ||
//				!$this->catconf['language.']['type'])) {
		if ($cnf->bUseLanguageTable($this->tableconf))	{
			$this->table->setLanguage ($LLkey);
			$this->table->setLangName('tt_products_cat_language');
			$this->table->setTCAFieldArray($this->table->langname);
		}
		
		if ($this->tableconf['language.'] && $this->tableconf['language.']['type'] == 'csv')	{
			$this->table->initLanguageFile($this->tableconf['language.']['file']);
		}

		parent::init($pibase, $cnf, $tt_content);
	} // init



	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function get ($uid=0,$pid=0) {
		global $TYPO3_DB;
		
		if (is_array($this->dataArray[$uid]))	{
			if (($pid && $this->dataArray[$uid]['pid'] == $pid) || ($pid == 0))	{
				$rc = $this->dataArray[$uid];
			} else {
				$rc = array();
			}
		}
		
		if (!$rc) {
			// $sql = t3lib_div::makeInstance('tx_table_db_access');
			// $sql->prepareFields($this->table, 'select', '*');
			// $sql->prepareFields($this->table, 'where', 'uid = '.$uid);
			// $sql->prepareWhereFields ($this->table, 'uid', '=', $uid);

			// Fetching the category
			// $res = $sql->exec_SELECTquery();
			$where = '1=1 '.$this->table->enableFields();;
			$where .= ($uid ? ' AND uid='.intval($uid) : '');
			$where .= ($pid ? ' AND pid IN ('.$pid.')' : '');
			$orderBy = '';
			$orderBy = $this->tableconf['orderBy'];
			
			$res = $this->table->exec_SELECTquery('*',$where,'',$TYPO3_DB->stripOrderBy($orderBy));
			if ($uid)	{
				$row = $TYPO3_DB->sql_fetch_assoc($res);
				if (is_array($this->table->langArray) && $this->table->langArray[$row['title']])	{
					$row['title'] = $this->table->langArray[$row['title']];
				}
				$rc = $this->dataArray[$uid] = $row;
			} else {
				while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
					if (is_array($this->table->langArray) && $this->table->langArray[$row['title']])	{
						$row['title'] = $this->table->langArray[$row['title']];
					}
					$rc = $this->dataArray[$row['uid']] = $row;
				}
			}
		}
		if (!$rc) {
			$rc = array();
			$this->dataArray = array();
		}
		return $rc;
	}


	function getParent ($uid=0) {
		$rc = array();
		return $rc;
	}


	function getRowCategory ($row) {
		$rc = $row['category'];
		return $rc;
	}


	function getRowPid($row) {
		$rc = $row['pid'];
		return $rc;
	}


	function getParamDefault ()	{
		$cat = $this->pibase->piVars[$this->piVar];
		$cat = ($cat ? $cat : $this->conf['defaultCategoryID']);
		$cat = implode(',',t3lib_div::intExplode(',', $cat));
		return $cat;	
	}


	/**
	 * Getting all sub categories from internal array
	 * This must be overwritten by other classes who support multiple categories
	 * getPrepareCategories must have been called before
	 * 
	 */
	function &getSubcategories(&$row)	{
		return array();
	}


	function &getRelationArray ($excludeCat=0,$currentCat=0) {

		$relationArray = array();
		
		foreach ($this->dataArray as $k => $row)	{
			$uid = $row['uid'];
			$relationArray [$uid]['title'] = $row['title'];
			$relationArray [$uid]['pid'] = $row['pid'];
			$relationArray [$uid]['parent_category'] = $row['parent_category'];
			$parentId = $row['parent_category'];
			if ($parentId)	{
				$count = 0;
				if (!is_array($relationArray[$parentId]['child_category']))	{
					$relationArray[$parentId]['child_category'] = array();
				}
				$relationArray[$parentId]['child_category'][] = (int) $uid;
			}
		}
		
		return $relationArray;
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
		// loop over all items in the basket indexed by page and a sorting text
		foreach ($itemArray as $sort=>$actItemArray) {
			foreach ($actItemArray as $k1=>$actItem) {
				$category = $this->get($actItem['rec']['category']);
				$tmp = $this->tt_products_email->getEmail($category['email_uid']);
				if ($tmp) {
					$emailArray[$actItem['rec']['category']] = $tmp['name'].' <'.$tmp['email'].'>';
				} 
			}
		}
		return $emailArray;
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array		Returns a markerArray ready for substitution with information
	 * 			 			for the tt_producst record, $row
	 * @access private
	 */
	function getMarkerArray (&$markerArray, &$page, $category, $pid, $imageNum=0, $imageRenderObj='image', &$viewCatTagArray, $forminfoArray=array(), $pageAsCategory=0, $code, $id, $prefix)	{
		$row = ($category ? $this->get($category) : array ('title' => '', 'pid' => $pid));
			// Get image	
		$this->image->getItemMarkerArray ($row, $markerArray, $row['pid'], $imageNum, $imageRenderObj, $viewCatTagArray, $code, $id, $prefix);
		$pageCatTitle = '';
		if ($pageAsCategory == 1) {
			$pageTmp = $page->get($pid);
			$pageCatTitle = $pageTmp['title'].'/';
		}

		$catTitle = $pageCatTitle.($row['title']);
		$this->setMarkerArrayCatTitle ($markerArray, $catTitle, $prefix);
		parent::getItemMarkerArray ($row, $markerArray, $code, $prefix);
	}
	
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_category.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_category.php']);
}


?>
