<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Franz Holzinger <franz@ttproducts.de>
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

require_once(PATH_BE_table.'lib/class.tx_table_db.php');
require_once(PATH_BE_ttproducts.'lib/class.tx_ttproducts_email.php');
require_once(PATH_BE_ttproducts.'model/class.tx_ttproducts_category_base.php');

class tx_ttproducts_category extends tx_ttproducts_category_base {
	var $tt_products_email;				// object of the type tx_table_db
	var $tableconf;
	var $image;
	var $cnf;

	/**
	 * initialization with table object and language table
	 */
	function init (&$pibase, &$cnf, &$tt_content, $LLkey, $tablename, $conftablename, $parentField, $pivar = 'cat')	{
		global $TYPO3_DB;

		$this->cnf = &$cnf;
		$tablename = ($tablename ? $tablename : $conftablename);
		$this->tableconf = $this->cnf->getTableConf($conftablename);
		$this->table = t3lib_div::makeInstance('tx_table_db');
		$this->table->addDefaultFieldArray(array('sorting' => 'sorting'));
		$this->table->setTCAFieldArray($tablename);
		$this->parentField = $parentField;

		if ($cnf->bUseLanguageTable($this->tableconf) && ($conftablename = 'tt_products_cat'))	{
			$this->table->setLanguage ($LLkey);
			$langTable = 'tt_products_cat_language'; // TODO: DAM alternative language
			$this->table->setLangName($langTable);
			$this->table->setTCAFieldArray($this->table->langname);
		}

		$this->piVar = $pivar;

		if ($this->tableconf['language.'] && $this->tableconf['language.']['type'] == 'csv')	{
			$this->table->initLanguageFile($this->tableconf['language.']['file']);
		}

		if ($this->tableconf['language.'] && is_array($this->tableconf['language.']['marker.']))	{
			$this->table->initMarkerFile($this->tableconf['language.']['marker.']['file']);
		}

		if (t3lib_extMgm::isLoaded('mbi_products_categories')) {

			if ($this->piVar == 'cat')	{
				$this->mm_table = 'tx_mbiproductscategories_mm';
			} else if($this->piVar == 'damcat')	{
				$this->mm_table = 'tx_dam_mm_cat';
			}
		}
		parent::init($pibase, $cnf, $tt_content, $conftablename);
	} // init


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function get ($uid=0,$pid=0,$bStore=true) {
		global $TYPO3_DB;

		if (is_array($this->dataArray[$uid]))	{
			if (($pid && $this->dataArray[$uid]['pid'] == $pid) || ($pid == 0))	{
				$rc = $this->dataArray[$uid];
			} else {
				$rc = array();
			}
		}

		if (!$rc) {
			// Fetching the category
			$where = '1=1 '.$this->table->enableFields();
			$where .= ($uid ? ' AND uid='.intval($uid) : '');
			$where .= ($pid ? ' AND pid IN ('.$pid.')' : '');
			$orderBy = $this->tableconf['orderBy'];
			$res = $this->table->exec_SELECTquery('*',$where,'',$TYPO3_DB->stripOrderBy($orderBy));

			if ($uid)	{
				$row = $TYPO3_DB->sql_fetch_assoc($res);
				if (is_array($this->table->langArray) && $this->table->langArray[$row['title']])	{
					$row['title'] = $this->table->langArray[$row['title']];
				}
				$rc =  $row;
				if ($bStore)	{
					$this->dataArray[$uid] = $rc;
				}
			} else {
				while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
					if (is_array($this->table->langArray) && $this->table->langArray[$row['title']])	{
						$row['title'] = $this->table->langArray[$row['title']];
					}
					$rc = $row;
					if ($bStore)	{
						$this->dataArray[$row['uid']] = $rc;
					} else {
						break;
					}
				}
			}
		}
		if (!$rc) {
			$rc = array();
			$this->dataArray = array();
		}

		return $rc;
	}


	function getRootCat ()	{
		$rc = $this->cnf->config['rootCategoryID'];
		return $rc;
	}


	function getRootline ($rootArray, $uid, $pid)	{
		global $TYPO3_DB;

		$bRootfound = false;
		$rc = array();
		if ($uid)	{
			$rc [$uid] = $row = $this->get($uid, $pid, false);
			$orderBy = $this->tableconf['orderBy'];
			if (!in_array($row['uid'],$rootArray))	{
				while ($parent = $row[$this->parentField])	{
					$where = 'uid ='.$parent;
					$where .= ($pid ? ' AND pid IN ('.$pid.')' : '');
					$where .= $this->table->enableFields();
					$res = $this->table->exec_SELECTquery('*',$where,'',$TYPO3_DB->stripOrderBy($orderBy));
					$row = $TYPO3_DB->sql_fetch_assoc($res);
					if ($row)	{
						$rc [$row['uid']] = $row;
					}
					if (in_array($row['uid'],$rootArray))	{
						$bRootfound = true;
						break;
					}
				}
			} else {
				$bRootfound = true;
			}
		}
		if (!$bRootfound)	{
			$rc = array();
		}
		return $rc;
	}


	function &getRelated ($rootUids,$currentCat,$pid=0) {
		global $TYPO3_DB;

		$relatedArray = array();
		$uidArray = $rootArray = t3lib_div::trimExplode(',', $rootUids);
		$orderBy = $this->tableconf['orderBy'];
		$rootLine = $this->getRootline($uidArray, $currentCat, $pid);
		foreach ($rootLine as $k => $row)	{
			if (!in_array($k, $uidArray))	{
				$uidArray[] = $k;
			}
		}
		foreach ($uidArray as $k => $uid)	{
			$row = $this->get ($uid, $pid, in_array($uid, $rootArray));
			$relatedArray[$uid] = $row;
			if (isset($rootLine[$uid]) && $this->table->bFieldExists($this->parentField))	{
				$where = $this->parentField.'='.$uid;
				$where .= ($pid ? ' AND pid IN ('.$pid.')' : '');
				$where .= $this->table->enableFields();
				$res = $this->table->exec_SELECTquery('*',$where,'',$TYPO3_DB->stripOrderBy($orderBy));
				while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
					if (is_array($this->table->langArray) && $this->table->langArray[$row['title']])	{
						$row['title'] = $this->table->langArray[$row['title']];
					}
					$rc = $this->dataArray[$row['uid']] = $row;
					$relatedArray[$row['uid']] = $row;
				}
			}
		}
		foreach ($rootLine as $k => $row)	{
			$relatedArray[$k] = $row;
		}
		return $relatedArray;
	}


	function getRowFromTitle ($title)	{
		$rc = $this->titleArray[$title];
		if (is_array($rc))	{
			$where = '1=1 '.$this->table->enableFields();
			$where .= ' AND title='.$TYPO3_DB->fullQuoteStr($title,$this->table->name);
			$res = $this->table->exec_SELECTquery('*',$where);
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$rc = $this->titleArray[$title] = $row;
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


	function getRowPid ($row) {
		$rc = $row['pid'];
		return $rc;
	}


	function getParamDefault ()	{
		$cat = $this->pibase->piVars[$this->piVar];
		if (!$cat)	{
			if ($this->table->name == 'tt_products_cat')	{
				$cat = $this->conf['defaultCategoryID'];
			}
			if ($this->table->name == 'tx_dam_cat')	{
				$cat = $this->conf['defaultDAMCategoryID'];
			}
		}

		if ($cat)	{
			$cat = implode(',',t3lib_div::intExplode(',', $cat));
		}
		return $cat;
	}


	function getChildUidArray ($uid)	{
		$rcArray = array();
		return $rcArray;
	}


	/**
	 * Getting all sub categories from internal array
	 * This must be overwritten by other classes who support multiple categories
	 * getPrepareCategories must have been called before
	 *
	 */
	function &getSubcategories (&$row)	{
		return array();
	}


	function addRootRow ($rootrow, $firstIndex)	{
		if (is_array($this->dataArray))	{
			$this->dataArray[] = $rootrow;
			end ($this->dataArray);
			$rootParentId = key($this->dataArray);
			$this->dataArray[$rootParentId]['uid'] = $firstIndex; // no real uid
			foreach ($this->dataArray as $k => $row)	{
				$uid = $row['uid'];
				$parentId = $row[$this->parentField];
				if (!$parentId && $uid > 0)	{
					$this->dataArray[$k][$this->parentField] = $rootParentId;
//					$this->dataArray[$rootParentId]['child_category'][] = (int) $uid;
				}
			}
		}
	}


	function &getRelationArray ($excludeCat=0,$currentCat=0,$rootUids='',$pid=0) {
		$relationArray = array();
		$rootArray = t3lib_div::trimExplode(',', $rootUids);

		if (is_array($this->dataArray))	{
			foreach ($this->dataArray as $k => $row)	{
				$uid = $row['uid'];
				$relationArray[$uid]['title'] = $row['title'];
				$relationArray[$uid]['pid'] = $row['pid'];
				$relationArray[$uid]['parent_category'] = $row[$this->parentField];
				$parentId = $row[$this->parentField];

				if ($parentId && isset($this->dataArray[$parentId]) && !in_array($uid, $rootArray))	{
					if (!is_array($relationArray[$parentId]['child_category']))	{
						$relationArray[$parentId]['child_category'] = array();
					}
					$relationArray[$parentId]['child_category'][] = (int) $uid;
				}
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
	function getMarkerArray (&$markerArray, &$page, $category, $pid, $imageNum=0, $imageRenderObj='image', &$viewCatTagArray, $forminfoArray=array(), $pageAsCategory=0, $code, $id, $prefix, $linkWrap='')	{
		$row = ($category ? $this->get($category) : array ('title' => '', 'pid' => $pid));

			// Get image
		$this->image->getItemMarkerArray($row, $markerArray, $row['pid'], $imageNum, $imageRenderObj, $viewCatTagArray, $code, $id, $prefix, $linkWrap);
		$pageCatTitle = '';
		if ($pageAsCategory == 1) {
			$pageTmp = $page->get($pid);
			$pageCatTitle = $pageTmp['title'];
		}

		$catTitle = $pageCatTitle;
		if (($row['title']))	{
			$catTitle .= ($this->tableconf['separator'].$row['title']);
		}
		$this->setMarkerArrayCatTitle($markerArray, $catTitle, $prefix);
		parent::getItemMarkerArray($row, $markerArray, $code, $prefix);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_category.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_category.php']);
}


?>
