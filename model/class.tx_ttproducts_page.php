<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_page extends tx_ttproducts_category_base {
	var $noteArray = array(); 	// array of pages with notes
	var $piVar = 'pid';
	var $pageAsCategory;		// > 0 if pages are used as categories


	/**
	 * Getting all tt_products_cat categories into internal array
	 *
	 * @param	[type]		$$pibase: ...
	 * @param	[type]		$tablename: ...
	 * @return	[type]		...
	 */
	function init($pibase, $tablename)	{
		global $TYPO3_DB;

		parent::init($pibase, $tablename);

		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$tablename = ($tablename ? $tablename : 'pages');
		$this->tableconf = $cnf->getTableConf('pages');
		$this->pageAsCategory = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'];

		$requiredFields = 'uid,pid,title,subtitle,media,shortcut';
		if ($this->tableconf['requiredFields'])	{
			$tmp = $this->tableconf['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}
		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$this->getTableObj()->setRequiredFieldArray($requiredListArray);
		if (is_array($this->tableconf['language.']) &&
			$this->tableconf['language.']['type'] == 'field' &&
			is_array($this->tableconf['language.']['field.'])
			)	{
			$addRequiredFields = array();
			$addRequiredFields = $this->tableconf['language.']['field.'];
			$this->getTableObj()->addRequiredFieldArray($addRequiredFields);
		}

		if (is_array($this->tableconf['generatePath.']) &&
			$this->tableconf['generatePath.']['type'] == 'tablefields' &&
			is_array($this->tableconf['generatePath.']['field.'])
			)	{
			$addRequiredFields = array();
			foreach ($this->tableconf['generatePath.']['field.'] as $field => $value)	{
				$addRequiredFields[] = $field;
			}
			$this->getTableObj()->addRequiredFieldArray($addRequiredFields);
		}

		$this->getTableObj()->setTCAFieldArray($tablename, 'pages');

		if ($cnf->bUseLanguageTable($this->tableconf))	{
			$this->getTableObj()->setLanguage($this->config['LLkey']);
			$this->getTableObj()->setLangName($this->tableconf['language.']['table']);
			$this->getTableObj()->setForeignUidArray($this->getTableObj()->langname, 'pid');
			$this->getTableObj()->setTCAFieldArray($this->getTableObj()->langname);
		}

		if ($this->tableconf['language.'] && $this->tableconf['language.']['type'] == 'csv')	{
			$this->getTableObj()->initLanguageFile($this->tableconf['language.']['file']);
		}
	} // init

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$uid: ...
	 * @param	[type]		$pid: ...
	 * @param	[type]		$bStore: ...
	 * @param	[type]		$where_clause: ...
	 * @param	[type]		$limit: ...
	 * @param	[type]		$fields: ...
	 * @param	[type]		$bCount: ...
	 * @return	[type]		...
	 */
	function get ($uid = 0, $pid = 0, $bStore = true, $where_clause = '', $limit = '', $fields = '', $bCount = FALSE) {
		global $TYPO3_DB;

		$bMultple = (strstr($uid, ',') ? true : false);

		$rc = $this->dataArray[$uid];
		if (!$rc && !$bMultple && isset($uid)) {
			$where = '1=1 '.$this->getTableObj()->enableFields().' AND uid = '.intval($uid);

			// Fetching the pages
			$res = $this->getTableObj()->exec_SELECTquery('*', $where);
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$TYPO3_DB->sql_free_result($res);
			$rc = $this->dataArray[$uid] = $row;
		}
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getRootCat ()	{
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$rc = $cnf->config['rootPageID'];
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function getNotes ($uid) {
		global $TYPO3_DB;
		$rowArray = $this->noteArray[$uid];
		$rcArray = array();
		if (!is_array($rowArray) && $uid) {
			$rowArray = $TYPO3_DB->exec_SELECTgetRows('*', 'tt_products_products_note_pages_mm', 'uid_local = '.intval($uid),'','sorting');
			$this->noteArray[$uid] = $rowArray;
		}
		foreach ($rowArray as $k => $row)	{
			$rcArray[] = $row['uid_foreign'];
		}
		return $rcArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function getParent ($uid=0) {
		$rc = array();
		$row = $this->get ($uid);
		if ($row['pid'])	{
			$rc = $this->get ($row['pid']);
		}
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function getRowCategory ($row) {
		$rc = $row['pid'];
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function getRowPid ($row) {
		$rc = $row['uid'];
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$theCode: ...
	 * @param	[type]		$piVars: ...
	 * @return	[type]		...
	 */
	function getParamDefault ($theCode, $piVars)	{
		$pid = $piVars[$this->piVar];
		$pid = ($pid ? $pid : $this->conf['defaultPageID']);
		if ($pid)	{
			$pid = implode(',',t3lib_div::intExplode(',', $pid));
		}
		return $pid;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$excludeCats: ...
	 * @param	[type]		$rootUids: ...
	 * @param	[type]		$allowedCats: ...
	 * @return	[type]		...
	 */
	function &getRelationArray ($excludeCats='',$rootUids='',$allowedCats='') {

		$relationArray = array();
		$pageArray = t3lib_div::trimExplode (',', $pid_list);
		$excludeArray = t3lib_div::trimExplode (',', $excludeCats);
		foreach ($excludeArray as $k => $cat)	{
			$excludeKey = array_search($cat, $pageArray);
			unset($pageArray[$excludeKey]);
		}
		$tablename = $this->getTableObj()->name;
		if ($this->config['LLkey'] && is_array($this->tableconf['language.']) && $this->tableconf['language.']['type'] == 'table')	{
			$tablename = $this->tableconf['language.']['table'];
		}

		foreach ($pageArray as $k => $uid)	{
			$row = $this->get ($uid);
			if ($row)	{
				if (in_array($row['shortcut'],$excludeArray))	{	// do not show shortcuts to the excluded page
					$excludeKey = array_search($row['uid'], $pageArray);
					unset($pageArray[$excludeKey]);
					continue;
				}
				$relationArray [$uid]['title'] = $row['title'];
				if ($tablename == $this->getTableObj()->name)	{ // default language and using language overlay table
					$relationArray [$uid]['pid'] = $row['uid'];
				} else {
					$relationArray [$uid]['pid'] = $row['pid'];
				}
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
		}
		return $relationArray;
	}

	/**
	 * Returning the pid out from the row using the where clause
	 *
	 * @param	[type]		$conf: ...
	 * @param	[type]		$confExt: ...
	 * @param	[type]		$row: ...
	 * @param	[type]		$rootRow: ...
	 * @return	[type]		...
	 */
	function getPID ($conf, $confExt, $row, $rootRow=array()) {
		$rc = 0;
		if ($confExt) {
			foreach ($confExt as $k1 => $param) {
				$type  = $param['type'];
				$where = $param['where'];
				$isValid = false;
				if ($where) {
					$wherelist = t3lib_div::trimExplode ('AND', $where);
					$isValid = true;
					foreach ($wherelist as $k2 => $condition) {
						$args = t3lib_div::trimExplode ('=', $condition);
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
							$rc = intval($row['pid']);
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

				$rc = ($rootRow['uid'] ? $rootRow['uid'] : $TSFE->id);
				$rc = intval($rc);
			}
		}
		return $rc;
	} // getPID
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_page.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_page.php']);
}

?>