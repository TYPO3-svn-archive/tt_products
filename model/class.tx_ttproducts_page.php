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
 * Part of the tt_products (Shop System) extension.
 *
 * functions for the page
 *
 * $Id: class.tx_ttproducts_page.php 3460 2006-07-14 12:00:13Z franzholz $
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once(PATH_BE_table.'lib/class.tx_table_db.php');
require_once(PATH_BE_table.'lib/class.tx_table_db_access.php');
require_once(PATH_BE_ttproducts.'model/class.tx_ttproducts_category_base.php');


class tx_ttproducts_page extends tx_ttproducts_category_base {
	var $pid_list;		// list of page ids
	var $pageArray;		// pid_list as array
	var $cnf;
	var $piVar = 'pid';

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, &$cnf, &$tt_content, $LLkey, $tablename, &$pageconf)	{
		global $TYPO3_DB;
		
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$tablename = ($tablename ? $tablename : 'pages');
		$this->tableconf = $this->cnf->getTableConf($tablename);
		$this->table = t3lib_div::makeInstance('tx_table_db');
		$this->table->setDefaultFieldArray(array('uid'=>'uid', 'pid'=>'pid', 't3ver_oid'=>'t3ver_oid', 't3ver_id' => 't3ver_id', 't3ver_label' => 't3ver_label', 'tstamp'=>'tstamp', 'hidden'=>'hidden', 'sorting'=> 'sorting',
 			'deleted' => 'deleted', 'hidden'=>'hidden', 'starttime' => 'starttime', 'endtime' => 'endtime', 'fe_group' => 'fe_group'));		
		$this->table->setTCAFieldArray($tablename, 'pages');
		
		$requiredFields = 'uid,pid,title,shortcut';
		if ($this->tableconf['requiredFields'])	{
			$tmp = $this->tableconf['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}	
		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$this->table->setRequiredFieldArray($requiredListArray);

		if (is_array($this->tableconf['generatePath.']) &&
			$this->tableconf['generatePath.']['type'] == 'tablefields' &&
			is_array($this->tableconf['generatePath.']['field.'])
			)	{
			$addRequiredFields = array();
			foreach ($this->tableconf['generatePath.']['field.'] as $field => $value)	{
				$addRequiredFields[] = $field;
			}
			$this->table->addRequiredFieldArray ($addRequiredFields);
		}		
		
		parent::init($pibase, $cnf, $tt_content);
	} // init


	function get ($uid,$pid=0) {
		global $TYPO3_DB;
		$bMultple = (strstr($uid, ',') ? true : false);
		
		$rc = $this->dataArray[$uid];
		if (!$rc && !$bMultple) {
			$sql = t3lib_div::makeInstance('tx_table_db_access');
			$sql->prepareFields($this->table, 'select', implode(',', $this->table->requiredFieldArray));
			$sql->prepareWhereFields ($this->table, 'uid', '=', intval($uid));
			$this->table->enableFields();		 
			// Fetching the category
		 	$res = $sql->exec_SELECTquery();
		 	$row = $TYPO3_DB->sql_fetch_assoc($res);
		 	$rc = $this->dataArray[$row['uid']] = $row;
		}
		return $rc;
	}


	function getParent ($uid=0) {
		$rc = array();
		$row = $this->get ($uid);
		if ($row['pid'])	{
			$rc = $this->get ($row['pid']);
		}
		return $rc;
	}

  function getRowCategory ($row) {
    $rc = $row['pid'];
    return $rc;
  }

  function getRowPid($row) {
    $rc = $row['uid'];
    return $rc;
  }
  
	function getParamDefault ()	{
		$pid = $this->pibase->piVars[$this->piVar];
		$pid = ($pid ? $pid : $this->conf['defaultPageID']);
		if ($pid)	{
			$pid = implode(',',t3lib_div::intExplode(',', $pid));
		}
		return $pid;	
	}

	function &getRootpathArray ($rootCat,$currentCat) {
		$rootpathArray = array();
		$uid = $currentCat;
		$count = 0;
		do	{
			$count++;
			$row = $this->get ($uid);
			$rootpathArray[] = $row;
			$lastUid = $uid;
			$uid = $row['pid'];
		} while ($lastUid != $rootCat && $count < 100);
		return $rootpathArray;
	}

	function &getRelationArray ($excludeCat=0,$currentCat=0) {
		$relationArray = array();
		if ($currentCat)	{
			$pid_list = $currentCat;
			$this->applyRecursive(1,$pid_list);
		} else {
			$pid_list = $this->pid_list;
		}
		
		$pageArray = t3lib_div::trimExplode (',', $pid_list);
		$excludeArray = t3lib_div::trimExplode (',', $excludeCat);
		foreach ($excludeArray as $k => $cat)	{
			$excludeKey = array_search($cat, $pageArray);
			unset($pageArray[$excludeKey]);	
		}
		foreach ($pageArray as $k => $uid)	{
			$row = $this->get ($uid);
			if ($row['shortcut'] == $excludeCat)	{	// do not show shortcuts to the excluded page
				$excludeKey = array_search($row['uid'], $pageArray);
				unset($pageArray[$excludeKey]);
				continue;
			}
			$relationArray [$uid]['title'] = $row['title'];
			$relationArray [$uid]['pid'] = $row['uid'];
			$pid = $row['pid'];
			$parentKey = array_search($pid, $pageArray);
			if ($parentKey === FALSE || $parentKey == 0 && $pageArray[0] != $pid)	{
				$pid = 0;
			}
			$relationArray [$uid]['parent_category'] = $pid;
			$parentId = $pid;
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


	/**
	 * Getting the page table
	 * On the basket page there will be a separate page table
	 */
	function &createPageTable(&$pibase, &$cnf, &$tt_content, $LLkey, $tablename,  &$pageconf, &$pageObject, &$pid_list, $recursive)	{
		if (!is_object($pageObject)) {
			$pageObject = t3lib_div::makeInstance('tx_ttproducts_page');
			$pageObject->init(
				$pibase,
				$cnf,
				$tt_content,
				$LLkey,
				$tablename,
				$pageconf
			);
		}
		$pageObject->setPidlist($pid_list);				// The list of pid's we're operation on. All tt_products records must be in the pidlist in order to be selected.
		$tmp = '';
		$pageObject->applyRecursive($recursive, $tmp);
		return $pageObject;
	}


//	/**
//	 * Generates an array, ->pageArray of the pagerecords from ->pid_list
//	 */
//	function generatePageArray()	{
//			// Get pages (for category titles)
//		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid,pid', 'pages', 'uid IN ('.$this->pid_list.')');
//		$this->pageArray = array();
//		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
//			$this->pageArray[$row['uid']] = $row;
//		}
//	} // generatePageArray
//


	/**
	 * Returning the pid out from the row using the where clause
	 */
	function getPID($conf, $confExt, $row) {
		$rc = 0;
		if ($confExt) {
			foreach ($confExt as $k1 => $param) {
				$type  = $param['type'];
				$where = $param['where'];
				$isValid = false;
				if ($where) {
					$wherelist = explode ('AND', $where);
					$isValid = true;
					foreach ($wherelist as $k2 => $condition) {
						$args = explode ('=', $condition);
						if ($row[$args[0]] != $args[1]) {
							$isValid = false;
						}
					}
				} else {
					$isValid = true;
				}

				if ($isValid == true) {
					switch ($type) {
						case 'sql':
							$rc = $param['pid'];
							break;
						case 'pid':
							$pageTmp = $this->get($row['pid']);
							$rc = intval ($pageTmp['pid']);
							break;
					}
					break;  //ready with the foreach loop
				}
			}
		} else {
			if ($conf) {
				$rc = $conf;
			} else {
				global $TSFE;
				
				$rc = intval ($TSFE->id);
//				$pageTmp = $this->get($row['pid']);
//				$rc = intval ($pageTmp['pid']);
			}
		}
		return $rc;
	} // getPID


	/**
	 * Sets the pid_list internal var
	 */
	function setPidlist($pid_list)	{
		$this->pid_list = $pid_list;
	}


	/**
	 * Sets the pid_list internal var
	 */
	function setPageArray()	{
		$this->pageArray = t3lib_div::trimExplode (',', $this->pid_list);
		$this->pageArray = array_flip($this->pageArray);
	}


	/**
	 * Extends the internal pid_list by the levels given by $recursive
	 */
	function applyRecursive($recursive, &$pids)	{
		global $TSFE;
		
		if (!$pids)	{
			$pid_list = &$this->pid_list;
		} else {
			$pid_list = &$pids;
		}
		if (!$pid_list) {
			$pid_list = $TSFE->id;
		}
		if ($recursive)	{		// get pid-list if recursivity is enabled
			$recursive = intval($recursive);
			$pid_list_arr = explode(',',$pid_list);
			$pid_list = '';
			while(list(,$val) = each($pid_list_arr))	{
				$pid_list .= $val.','.$this->pibase->cObj->getTreeList($val,$recursive);
			}
			$pid_list = ereg_replace(',$','',$pid_list);
			$pid_list_arr = explode(',',$pid_list);
			$pid_list_arr = array_unique ($pid_list_arr);
			$pid_list = implode(',', $pid_list_arr);
		}
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
	function getMarkerArray (&$markerArray, &$page, $category, $pid, $imageNum=0, $imageRenderObj='image', &$viewCatTagArray, $forminfoArray=array(), $pageAsCategory=0, $code, $id, $prefix='')	{
		$row = $this->get($pid);

			// Get image	
		$this->image->getItemMarkerArray ($row, $markerArray, $pid, $imageNum, $imageRenderObj, $viewCatTagArray, $code, $id, $prefix);
		
		$pageCatTitle = $row['title'];
		$this->setMarkerArrayCatTitle ($markerArray, $pageCatTitle, $prefix);
		$markerArray['###'.$prefix.$this->marker.'_SUBTITLE###'] = $row['subtitle'];

		parent::getItemMarkerArray($row, $markerArray, $code, $prefix);
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_page.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_page.php']);
}


?>
