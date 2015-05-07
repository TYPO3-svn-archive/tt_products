<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2009 Franz Holzinger <franz@ttproducts.de>
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
 * @maintainer	Franz Holzinger <franz@ttproduct.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


abstract class tx_ttproducts_category_base extends tx_ttproducts_table_base {
	var $dataArray;  // array of read in categories
	var $titleArray; // associative array of read in categories with title as index
	public $marker = 'CATEGORY';
	var $markerObj;
	protected $mm_table = ''; // only set if a mm table is used
	var $parentField; // reference field name for parent


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

	public function setMMTablename ($mm_table)	{
		$this->mm_table = $mm_table;
	}

	public function getMMTablename ()	{
		return $this->mm_table;
	}

	function hasSpecialConf ($cat, $theCode, $type)	{
		$rc = FALSE;
		$conf = $this->getTableConf ($theCode);

		if (is_array($conf['special.']) && isset($conf['special.'][$type]))	{
			$specialArray = t3lib_div::trimExplode (',', $conf['special.'][$type]);
			if (in_array($cat, $specialArray))	{
				$rc = TRUE;
			}
		}

		return $rc;
	}

	function getRowPid ($row) {
		$rc = '';
		return $rc;
	}

	function getParamDefault ($theCode, $piVars)	{
		$rc = '';
		return $rc;
	}

	function getChildUidArray ($uid)	{
		$rcArray = array();
		return $rcArray;
	}

	function getCategoryArray ($uid, $orderBy='')	{
		$catArray = array();
		if($this->getMMTablename()) {
			$hookVar = '';
			$functablename = $this->getFuncTablename();
			if ($functablename == 'tt_products_cat')	{
				$hookVar = 'prodCategory';
			} else if($functablename == 'tx_dam_cat')	{
				$hookVar = 'DAMCategory';
			}
				// Call all addWhere hooks for categories at the end of this method
			if ($hookVar && isset ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar]) && is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar])) {
				foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar] as $classRef) {
					$hookObj= &t3lib_div::getUserObj($classRef);
					if (method_exists($hookObj, 'init')) {
						$hookObj->init($this->parentField);
					}
					if (method_exists($hookObj, 'getCategories')) {
						$retArray = $hookObj->getCategories($this, $uid, $this->mm_table, $orderBy);
						if (isset($retArray) && is_array($retArray))	{
							foreach ($retArray as $k => $row)	{
								$catArray[] = $row['cat'];
							}
						}
					}
				}
			}
		}
		return $catArray;
	}

	public function getDepth ($theCode)	{
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$functablename = $this->getFuncTablename();
		$conf = $this->getTableConf ($theCode);
		$tableconf = $cnf->getTableConf($functablename,$theCode);
		$rc = $tableconf['hierarchytiers'];
		if (!isset($rc)) {
			$rc = 1;
		}
		return $rc;
	}

	function getLineArray ($start, $endArray)	{
		$catArray = array();
		$hookVar = '';
		$functablename = $this->getFuncTablename ();
		if ($functablename == 'tt_products_cat')	{
			$hookVar = 'prodCategory';
		} else if($functablename == 'tx_dam_cat')	{
			$hookVar = 'DAMCategory';
		}

		$tmpArray = array();
			// Call all addWhere hooks for categories at the end of this method
		if ($hookVar && is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this->parentField);
				}
				if (method_exists($hookObj, 'getLineCategories')) {
					$catArray = $hookObj->getLineCategories($this, $start, $endArray, $this->getTableObj()->enableFields());
				}
			}
		}
		return $catArray;
	}

	function getHookVar ()	{
		$funcTablename = $this->getFuncTablename ();
		if ($funcTablename == 'tt_products_cat')	{
			$rc = 'prodCategory';
		} else if ($funcTablename == 'tx_dam_cat') {
			$rc = 'DAMCategory';
		}
		return $rc;
	}

	function getChildCategoryArray ($cat)	{

		$catArray = array();
		$hookVar = $this->getHookVar ();

		$tmpArray = array();
			// Call all addWhere hooks for categories at the end of this method
		if ($hookVar && is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this->parentField);
				}
				if (method_exists($hookObj, 'getChildCategories')) {
					$tmpArray = $hookObj->getChildCategories($this, $cat);
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

//
//	function &getRootpathArray (&$relationArray, $rootCat,$currentCat) {
//		$rootpathArray = array();
//		return $rootpathArray;
//	}

	function &getRootArray ($rootCat, &$categoryArray)	{
		$rootArray = array();
		$rootCatArray = t3lib_div::trimExplode(',', $rootCat);
		foreach ($categoryArray as $uid => $row)	{
			if (
				(
					class_exists('t3lib_utility_Math') ?
					t3lib_utility_Math::canBeInterpretedAsInteger($uid) :
					t3lib_div::testInt($uid)
				) &&
				(
					!$row['parent_category'] ||
					in_array($uid, $rootCatArray)
				)
			) {
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
			} while ($row && !in_array($lastUid,$rootCatArray) && isset($uid) && $count < 199);
		}
		return $rootpathArray;
	}

	function &getRelationArray ($excludeCats='',$rootUids='',$allowedCats='')	{
		$relationArray = array();
		return $relationArray;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_category_base.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_category_base.php']);
}

?>
