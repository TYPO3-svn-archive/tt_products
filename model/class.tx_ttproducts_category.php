<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2011 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once(PATH_BE_table.'lib/class.tx_table_db.php');
require_once(PATH_BE_ttproducts.'model/class.tx_ttproducts_category_base.php');


class tx_ttproducts_category extends tx_ttproducts_category_base {
	var $tt_products_email;	// object of the type tx_table_db
	var $tableconf;


	/**
	 * initialization with table object and language table
	 *
	 * @param	object		$$cObj: ...
	 * @param	string		$functablename: ...
	 * @return	void
	 */
	function init ($cObj, $functablename)	{
		global $TYPO3_DB;

		$tablename = ($tablename ? $tablename : $functablename);

		parent::init($pibase, $functablename);

		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->tableconf = $cnf->getTableConf($functablename);
		$tableObj = $this->getTableObj();
		$tableObj->addDefaultFieldArray(array('sorting' => 'sorting'));
		$tablename = $this->getTablename();
		$tableObj->setTCAFieldArray($tablename);

		if (t3lib_extMgm::isLoaded('mbi_products_categories')) {
			if ($functablename == 'tt_products_cat')	{
				$this->mm_table = 'tx_mbiproductscategories_mm';
			} else if($functablename == 'tx_dam_cat')	{
				$this->mm_table = 'tx_dam_mm_cat';
			}
		}
		if ($functablename == 'tt_products_cat')	{
			$parentField = 'parent_category';
		} else if ($functablename == 'tx_dam_cat')	{
			$parentField = 'parent_id';
		}

		if (t3lib_extMgm::isLoaded('mbi_products_categories')) {
			$this->parentField = $parentField;
		}

		if ($cnf->bUseLanguageTable($this->tableconf) && ($functablename == 'tt_products_cat'))	{
			$this->getTableObj()->setLanguage ($this->config['LLkey']);
			$langTable = 'tt_products_cat_language'; // TODO: DAM alternative language
			$tableObj->setLangName($langTable);
			$tableObj->setTCAFieldArray($this->getTableObj()->langname);
		}

		if ($this->tableconf['language.'] && $this->tableconf['language.']['type'] == 'csv')	{
			$tableObj->initLanguageFile($this->tableconf['language.']['file']);
		}

		if ($this->tableconf['language.'] && is_array($this->tableconf['language.']['marker.']))	{
			$tableObj->initMarkerFile($this->tableconf['language.']['marker.']['file']);
		}
	} // init

	/**
	 * get the root categories
	 *
	 * @return	string	comma separated list of root categories
	 */
	function getRootCat ()	{
		$functablename = $this->getFuncTablename ();
		if ($functablename == 'tt_products_cat')	{
			$rc = $this->conf['rootCategoryID'];
		} else if ($functablename == 'tx_dam_cat')	{
			$rc = $this->conf['rootDAMCategoryID'];
		}
		if ($rc == '') {
			$rc = 0;
		}

		return $rc;
	}

	public function getAllChildCats ($pid, $orderBy, $category = 0) {
		$rowArray = array();
		if ($this->parentField != '') {
			$where = $this->parentField . '=' . intval($category);
			$rowArray = $this->get('', $pid, FALSE, $where, '', 'uid', FALSE, '', $orderBy);
		}

		$resultArray = array();
		$result = '';
		if (isset($rowArray) && is_array($rowArray)) {
			foreach($rowArray as $row) {
				$resultArray[] = $row['uid'];
			}
			$result = implode (',', $resultArray);
		}

		return $result;
	}

	/**
	 * get the rootline of the categories
	 *
	 * @param	array		$rootArray: ...
	 * @param	integer		$uid: ...
	 * @param	integer		$pid: ...
	 * @return	array		the category uids of the rootline
	 */
	function getRootline ($rootArray, $uid, $pid)	{
		global $TYPO3_DB;

		$bRootfound = false;
		$rc = array();
		if ($uid)	{
			$tableObj = $this->getTableObj();
			$rc = $rowArray = $this->get($uid.' ', $pid, false);
			$orderBy = $this->tableconf['orderBy'];
			$uidArray = t3lib_div::trimExplode (',', $uid);

			foreach ($uidArray as $actUid)	{
				if (!in_array($actUid,$rootArray))	{
					$iCount = 0;
					$row = $rowArray[$actUid];
					while (is_array($row) && ($parent = $row[$this->parentField]) && ($iCount < 100))	{
						$where = 'uid ='.$parent;
						$where .= ($pid ? ' AND pid IN ('.$pid.')' : '');
						$where .= $tableObj->enableFields();
						$res = $tableObj->exec_SELECTquery('*',$where,'',$TYPO3_DB->stripOrderBy($orderBy));
						$row = $TYPO3_DB->sql_fetch_assoc($res);
						$TYPO3_DB->sql_free_result($res);
						if ($row)	{
							$rc[$parent] = $row;
						}
						if (in_array($parent,$rootArray))	{
							$bRootfound = true;
							break;
						}
						$iCount++;
					}
					if (!$parent && in_array($parent, $rootArray)) {
						$bRootfound = TRUE;
						break;
					}
				} else {
					$bRootfound = true;
				}

				if ($bRootfound) {
					break;
				}
			}
		}

		if (!$bRootfound)	{
			$rc = array();
		}
		return $rc;
	}

	/**
	 * get related uids
	 *
	 * @param	string		$rootUids: comma separated list of root uids
	 * @param	integer		$currentCat: currently selected category
	 * @param	integer		$pid: page id
	 * @return	array		related uid array
	 */
	function &getRelated ($rootUids,$currentCat,$pid=0, $orderBy='') {
		global $TYPO3_DB;

		$relatedArray = array();
		$uidArray = $rootArray = t3lib_div::trimExplode(',', $rootUids);
		$tableObj = $this->getTableObj();
		$rootLine = $this->getRootline($uidArray, $currentCat, $pid);

		foreach ($rootLine as $k => $row)	{
			if (!in_array($k, $uidArray))	{
				$uidArray[] = $k;
			}
		}

		foreach ($uidArray as $uid)	{

			if (
				class_exists('t3lib_utility_Math') ? t3lib_utility_Math::canBeInterpretedAsInteger($uid) :
				t3lib_div::testInt($uid)
			) {
				$row = $this->get($uid, $pid, in_array($uid, $rootArray),'','','',FALSE,'',$orderBy);
				$relatedArray[$uid] = $row;

				if (isset($rootLine[$uid]))	{
					if ($this->parentField)	{
						$where = $this->parentField.'='.intval($uid);
					} else {
						$where = '1=1';
					}
					$where .= ($pid ? ' AND pid IN ('.$pid.')' : '');
					$where .= $tableObj->enableFields();
					$res = $tableObj->exec_SELECTquery('*',$where,'',$TYPO3_DB->stripOrderBy($orderBy));

					while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{

						if (is_array($tableObj->langArray) && $tableObj->langArray[$row['title']])	{
							$row['title'] = $tableObj->langArray[$row['title']];
						}
						$rc = $this->dataArray[$row['uid']] = $row;
						$relatedArray[$row['uid']] = $row;
					}
					$TYPO3_DB->sql_free_result($res);
				}
			}
		}
		foreach ($rootLine as $k => $row)	{
			$relatedArray[$k] = $row;
		}
		return $relatedArray;
	}

	/**
	 * This returns the first row where the title equals to a search string
	 *
	 * @param	string		$title: search string for the title field
	 * @return	array		row of category record
	 */
	function getRowFromTitle ($title)	{
		$rc = $this->titleArray[$title];
		if (is_array($rc))	{
			$tableObj = $this->getTableObj();

			$where = '1=1 '.$tableObj->enableFields();
			$where .= ' AND title='.$TYPO3_DB->fullQuoteStr($title,$tableObj->name);
			$res = $tableObj->exec_SELECTquery('*',$where);
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$TYPO3_DB->sql_free_result($res);
			$rc = $this->titleArray[$title] = $row;
		}
		return $rc;
	}

	/**
	 * gets the parent uid
	 *
	 * @param	integer		$uid
	 * @return	integer		parent
	 */
	function getParent ($uid=0) {
		$result = FALSE;

		$row = $this->get($uid);
		if (isset($row) && is_array($row) && isset($row['parent_category'])) {
			$result = $this->get($row['parent_category']);
		}
		return $result;
	}

	/**
	 * gets the category of the row.
	 *
	 * @param	array		$row: category record
	 * @return	integer		category
	 */
	function getRowCategory ($row) {
		$rc = $row['category'];
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function getRowPid ($row) {
		$rc = $row['pid'];
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$theCode: ...
	 * @param	[type]		$cat: ...
	 * @return	[type]		...
	 */
	function getParamDefault ($theCode, $cat)	{

		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

		if (!$cat)	{
			if ($this->getFuncTablename() == 'tt_products_cat')	{
				$cat = $cnf->conf['defaultCategoryID'];
			}
			if ($this->getFuncTablename() == 'tx_dam_cat')	{
				$cat = $cnf->conf['defaultDAMCategoryID'];
			}
		}

		if ($cat)	{
			$tableConf = $this->getTableConf($theCode);
			$catArray = t3lib_div::intExplode(',', $cat);

			if (
				is_array($tableConf['special.']) &&
				(
					(
						class_exists('t3lib_utility_Math') ? t3lib_utility_Math::canBeInterpretedAsInteger($tableConf['special.']['all']) :
						t3lib_div::testInt($tableConf['special.']['all'])
					) &&
					in_array($tableConf['special.']['all'], $catArray) ||
					$tableConf['special.']['all'] == 'all'
				)
			) 	{
				$cat = '';	// no filter shall be used
			} else if (
				is_array($tableConf['special.']) &&
				(
					class_exists('t3lib_utility_Math') ? t3lib_utility_Math::canBeInterpretedAsInteger($tableConf['special.']['no']) :
					t3lib_div::testInt($tableConf['special.']['no'])
				) &&
				in_array($tableConf['special.']['no'], $catArray)
			) {
				$cat = '0';	// no products shall be shown
			} else {
				$cat = implode(',',$catArray);
			}
		}
		return $cat;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function getChildUidArray ($uid)	{
		$rcArray = array();
		return $rcArray;
	}

	/**
	 * Getting all sub categories from internal array
	 * This must be overwritten by other classes who support multiple categories
	 * getPrepareCategories must have been called before
	 *
	 * @param	[type]		$$row: ...
	 * @return	[type]		...
	 */
	function &getSubcategories (&$row)	{
		return array();
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$rootrow: ...
	 * @param	[type]		$firstIndex: ...
	 * @return	[type]		...
	 */
	function addRootRow ($rootrow, $firstIndex)	{
		if (is_array($this->dataArray))	{
			$this->dataArray[] = $rootrow;
			end($this->dataArray);
			$rootParentId = key($this->dataArray);
			$this->dataArray[$rootParentId]['uid'] = $firstIndex; // no real uid
			foreach ($this->dataArray as $k => $row)	{
				$uid = $row['uid'];
				$parentId = $row[$this->parentField];
				if (!$parentId && $uid > 0)	{
					$this->dataArray[$k][$this->parentField] = $rootParentId;
				}
			}
		}
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
		$rootArray = t3lib_div::trimExplode(',', $rootUids);
		$catArray = t3lib_div::trimExplode(',', $allowedCats);
		$excludeArray = t3lib_div::trimExplode (',', $excludeCats);

		foreach ($excludeArray as $cat)	{
			$excludeKey = array_search($cat, $catArray);
			if ($excludeKey !== FALSE)	{
				unset($catArray[$excludeKey]);
			}
		}
		if (is_array($this->dataArray))	{
			foreach ($this->dataArray as $row)	{	// separate loop to keep the sorting order
				$relationArray[$row['uid']] = array();
			}

			foreach ($this->dataArray as $row)	{

				$uid = $row['uid'];

				if (
					(!$uid) ||
					($allowedCats && !in_array($uid, $catArray)) ||
					($excludeCats && in_array($uid, $excludeArray))
				)	{
					continue;
				}
				foreach ($row as $field => $value)	{
					$relationArray[$uid][$field] = $value;
				}
				$parent = $row[$this->parentField];

				if(
					(!$parent) ||
					($allowedCats && !in_array($parent, $catArray)) ||
					($excludeCats && in_array($parent, $excludeArray))
				)	{
					$parent = 0;
				}
				$relationArray[$uid]['parent_category'] = $parent;

				$parentId = $row[$this->parentField];
				if ($parentId && isset($this->dataArray[$parentId]) && !in_array($uid, $rootArray) && !in_array($parentId,$excludeArray))	{
					if (!isset($relationArray[$parentId]['child_category']))	{
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
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$emailArray = array();
		$emailObj = $tablesObj->get('tt_products_emails');

		// loop over all items in the basket indexed by page and a sorting text
		foreach ($itemArray as $sort=>$actItemArray) {
			foreach ($actItemArray as $k1=>$actItem) {
				$category = $this->get($actItem['rec']['category']);
				$tmp = $emailObj->getEmail($category['email_uid']);
				if ($tmp) {
					$emailArray[$actItem['rec']['category']] = array($tmp['email'] => $tmp['name']);
				}
			}
		}
		return $emailArray;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_category.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_category.php']);
}


?>