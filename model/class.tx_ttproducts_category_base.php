<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2009 Franz Holzinger <franz@ttproducts.de>
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
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


global $TYPO3_CONF_VARS;

class tx_ttproducts_category_base {
	var $table;		 // object of the type tx_table_db
	var $pibase; 	 // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $dataArray;  // array of read in categories
	var $titleArray; // associative array of read in categories with title as index
	var $image;
	var $conftablename;	// table name of the configuration
	var $marker = 'CATEGORY';
	var $piVar = ''; // must be overridden
	var $mm_table = ''; // only set if a mm table is used
	var $parentField; // reference field name for parent


	/**
	 * initialization with table object and language table
	 */
	function init (&$pibase, &$cnf, &$tt_content, $conftablename)	{
		global $TYPO3_DB,$TSFE;

		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;
		$this->conftablename = $conftablename;

		if (is_object($tt_content))	{
			include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_image.php');
				// image
			$this->image = t3lib_div::makeInstance('tx_ttproducts_image');
			$this->image->init($this->pibase, $cnf, $tt_content, $this->table, $this->marker);
		}
	} // init


	function &getTableObj ()	{
		return $this->table;
	}


	function get ($uid=0,$pid=0) {
		$rc = array();
		return $rc;
	}


	function getFromTitle ($title)	{
		$rc = array();
		return $rc;
	}


	function getParent ($uid=0) {
		$rc = array();
		return $rc;
	}


	function getRootCat ()	{
		$rc = 0;
		return $rc;
	}


	function getRowCategory ($row) {
		$rc = '';
		return $rc;
	}


	function getRowPid ($row) {
		$rc = '';
		return $rc;
	}


	function getParamDefault ()	{
		$rc = '';
		return $rc;
	}


	function getChildUidArray ($uid)	{
		$rcArray = array();
		return $rcArray;
	}


	function getCategoryArray ($uid)	{
		global $TYPO3_CONF_VARS;

		$catArray = array();
		if($this->mm_table) {
			$hookVar = '';
			if ($this->piVar == 'cat')	{
				$hookVar = 'prodCategory';
			} else if($this->piVar == 'damcat')	{
				$hookVar = 'DAMCategory';
			}
			$tmpArray = array();
				// Call all addWhere hooks for categories at the end of this method
			if ($hookVar && is_array ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar])) {
				foreach($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar] as $classRef) {
					$hookObj= &t3lib_div::getUserObj($classRef);
					if (method_exists($hookObj, 'init')) {
						$hookObj->init($this->parentField);
					}
					if (method_exists($hookObj, 'getCategories')) {
						$tmpArray = $hookObj->getCategories($uid, $this->mm_table);
					}
				}
			}
			foreach ($tmpArray as $k => $row)	{
				$catArray[] = $row['cat'];
			}
		}
		return $catArray;
	}


	function getLineArray ($start, $endArray)	{
		global $TYPO3_CONF_VARS;

		$catArray = array();
		$hookVar = '';
		if ($this->piVar == 'cat')	{
			$hookVar = 'prodCategory';
		} else if($this->piVar == 'damcat')	{
			$hookVar = 'DAMCategory';
		}
		$tmpArray = array();
			// Call all addWhere hooks for categories at the end of this method
		if ($hookVar && is_array ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this->parentField);
				}
				if (method_exists($hookObj, 'getLineCategories')) {
					$catArray = $hookObj->getLineCategories($start, $endArray, $this->table->name, $this->table->enableFields());
				}
			}
		}
		return $catArray;
	}


	function getChildCategoryArray ($cat)	{
		global $TYPO3_CONF_VARS;

		$catArray = array();
		$hookVar = '';
		if ($this->piVar == 'cat')	{
			$hookVar = 'prodCategory';
		} else if($this->piVar == 'damcat')	{
			$hookVar = 'DAMCategory';
		}
		$tmpArray = array();
			// Call all addWhere hooks for categories at the end of this method
		if ($hookVar && is_array ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar])) {
			foreach  ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this->parentField);
				}
				if (method_exists($hookObj, 'getChildCategories')) {
					$tmpArray = $hookObj->getChildCategories($this, $cat, $this->table->name);
				}
			}
		}
		if (is_array($tmpArray))	{
			foreach ($tmpArray as $k => $row)	{
				$catArray[] = $row['cat'];
			}
		}
		return $catArray;
	}


	function &getRootArray ($rootCat, &$categoryArray)	{
		$rootArray = array();
		$rootCatArray = t3lib_div::trimExplode(',', $rootCat);

		foreach ($categoryArray as $uid => $row)	{
			if (!$row['parent_category']	||
				in_array($uid, $rootCatArray))	{
				$rootArray[] = $uid;
			}
		}
		return $rootArray;
	}


	function &getRootpathArray (&$relationArray,$rootCat,$currentCat) {
		$rootpathArray = array();
		$rootCatArray = t3lib_div::trimExplode(',', $rootCat);
		$uid = $currentCat;
		if (isset($uid))	{
			$count = 0;
			do	{
				$count++;
				$row = $relationArray[$uid];
				if ($row)	{
					$rootpathArray[] = $row;
					$lastUid = $uid;
					$uid = $row['parent_category'];
				}
			} while ($row && !in_array($lastUid,$rootCatArray) && isset($uid) && $count < 99);
		}
		return $rootpathArray;
	}


	function &getRelationArray ($excludeCat=0,$currentCat=0,$rootUids='') {
		$relationArray = array();
		return $relationArray;
	}


	function setMarkerArrayCatTitle (&$markerArray, $catTitle, $prefix)	{
		global $TSFE;

		$this->pibase->cObj->setCurrentVal($catTitle);
		$title = $this->pibase->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
		$markerArray['###'.$prefix.$this->marker.'_TITLE###'] = htmlentities($title,ENT_QUOTES,$TSFE->renderCharset);
	}


	function getMarkerArrayCatTitle (&$markerArray,$prefix='')	{
		return ($markerArray['###'.$prefix.$this->marker.'_TITLE###']);
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
	}


	function getParentMarkerArray (&$parentArray, &$row, &$markerArray, &$page, $category, $pid, $imageNum=0, $imageRenderObj='image', &$viewCatTagArray, $forminfoArray=array(), $pageAsCategory=0, $code, $id, $prefix)	{
		if (is_array($parentArray)) {
			$currentRow = $row;
			$count = 0;
			$currentCategory = $this->getRowCategory($row);
			$parentCategory = '';

			foreach ($parentArray as $key => $parent)	{
				do	{
					$parentRow = $this->getParent($currentCategory);
					$parentCategory = $parentRow['uid'];
					$parentPid = $this->getRowPid($parentRow);
					$count++;
					if ($count < $parent) {
						$currentCategory = $parentCategory;
					}
				} while ($count < $parent && count($currentRow));
				$currentCategory = $parentCategory;

				if (count($currentRow))	{
					$this->getMarkerArray (
						$markerArray,
						$this->page,
						$parentCategory,
						$parentPid,
						$this->config['limitImage'],
						'listcatImage',
						$viewCatTagArray,
						array(),
						$pageAsCategory,
						'SINGLE',
						1,
						'PARENT'.$parent.'_'
					);
				}
			}
		}
	}


	function getItemMarkerArray (&$row, &$markerArray, $code, $prefix='')	{
		global $TSFE;

		$cssConf = $this->cnf->getCSSConf($this->conftablename, $code);

		$marker = $prefix.$this->marker;
		$markerArray['###'.$marker.'_ID###'] = $row['uid'];
		$markerArray['###'.$marker.'_UID###'] = $row['uid'];
		$markerArray['###'.$marker.'_SUBTITLE###'] = htmlentities($row['subtitle'],ENT_QUOTES,$TSFE->renderCharset);
		if (isset($row['note']))	{
			if ($code == 'EMAIL' && !$this->conf['orderEmail_htmlmail'])	{ // no formatting for emails
				$markerArray['###'.$marker.'_NOTE###'] = $row['note'];
			} else {
				$markerArray['###'.$marker.'_NOTE###'] = ($this->conf['nl2brNote'] ? nl2br($row['note']) : $row['note']);
			}

			// cuts note in list view
			if (strlen($markerArray['###'.$marker.'_NOTE###']) > $this->conf['max_note_length']) {
				$markerArray['###'.$prefix.$marker.'_NOTE###'] = substr(strip_tags($markerArray['###'.$marker.'_NOTE###']), 0, $this->conf['max_note_length']) . '...';
			}
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_category_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_category_base.php']);
}


?>
