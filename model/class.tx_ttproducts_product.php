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
 * functions for the product
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

require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_article_base.php');
require_once(PATH_BE_div2007.'class.tx_div2007_ff.php');



class tx_ttproducts_product extends tx_ttproducts_article_base {
	var $relatedArray = array(); // array of related products
	var $marker = 'PRODUCT';
	var $type = 'product';
	var $articleArray = array();

	/**
	 * Getting all tt_products_cat categories into internal array
	 *
	 * @param	[type]		$$cObj: ...
	 * @param	[type]		$functablename: ...
	 * @return	[type]		...
	 */
	function init (&$cObj, $functablename='tt_products')  {
		global $TYPO3_DB,$TSFE,$TCA;

		parent::init($cObj, $functablename);
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

		$tableConfig = array();
		$tableConfig['orderBy'] = $cnf->conf['orderBy'];

		if (!$tableConfig['orderBy'])	{
			 $tableConfig['orderBy'] = $this->tableconf['orderBy'];
		}

		$tableObj = &$this->getTableObj();
		$tableObj->setConfig($tableConfig);
		$tableObj->addDefaultFieldArray(array('sorting' => 'sorting'));

		$requiredFields = 'uid,pid,category,price,price2,directcost,tax';
		$instockField = $cnf->getTableDesc($functablename,'inStock');
		if ($instockField && !$this->conf['alwaysInStock'])	{
			$requiredFields .= ','.$instockField;
		}
		$tableconf = $cnf->getTableConf($functablename,$theCode);

		if ($tableconf['requiredFields'])	{
			$tmp = $tableconf['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}
		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$this->getTableObj()->setRequiredFieldArray($requiredListArray);
		if (is_array($tableconf['language.']) &&
			$tableconf['language.']['type'] == 'field' &&
			is_array($tableconf['language.']['field.'])
			)	{
			$addRequiredFields = array();
			$addRequiredFields = $tableconf['language.']['field.'];
			$this->getTableObj()->addRequiredFieldArray($addRequiredFields);
		}
		$tablename = $cnf->getTableName($functablename);
		$tableObj->setTCAFieldArray($tablename, 'products');


		if ($cnf->bUseLanguageTable($tableconf))	{
			$tableObj->setLanguage ($this->config['LLkey']);
			$tableObj->setLangName($tableconf['language.']['table']);
			$tableObj->setTCAFieldArray($tableObj->langname, 'productslang', FALSE);
		}

		if ($tableconf['language.'] && $tableconf['language.']['type'] == 'csv')	{
			$tableObj->initLanguageFile($tableconf['language.']['file']);
		}
		if ($tableconf['language.'] && is_array($tableconf['language.']['marker.']))	{
			$tableObj->initMarkerFile($tableconf['language.']['marker.']['file']);
		}

		$this->relatedArray['accessories'] = array();
		$this->relatedArray['articles'] = array();
		$this->relatedArray['products'] = array();
	} // init


	function &getArticleRows ($uid, $where='')	{

		$rowArray = $this->articleArray[$uid];

		if (!$rowArray && $uid) {
			$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
			$tt_products_articles = $tablesObj->get('tt_products_articles');
			$tmpWhere = 'uid_product=\''.intval($uid).'\''. ($where ? ' AND '.$where : '');
			$rowArray = $tt_products_articles->getWhereArray($tmpWhere);
			if (!$where)	{
				$this->articleArray[$uid] = $rowArray;
			}
		}
		return $rowArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$row: ...
	 * @param	[type]		$theCode: ...
	 * @return	[type]		...
	 */
	function &getArticleRow ($row, $theCode) {
		global $TYPO3_DB;

		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$firstRow = $this->variant->getFirstVariantRow($row);
		$fieldArray = $this->variant->getFieldArray();
		$whereArray = array();
		foreach ($fieldArray as $k => $field)	{
			$whereArray[] = $field.'=\''.$firstRow[$field].'\'';
		}

		if (count($whereArray))	{
			$where = implode (' AND ',$whereArray);
		} else {
			$where = '';
		}
		$articleRows = $this->getArticleRows(intval($row['uid']), $where);
		if (is_array($articleRows) && count($articleRows))	{
			$articleRow = $this->variant->fetchArticle($row, $articleRows);
			$articleConf = $cnf->getTableConf('tt_products_articles', $theCode);
			if (
				$theCode &&
				isset($articleConf['fieldIndex.']) && is_array($articleConf['fieldIndex.']) &&
				isset($articleConf['fieldIndex.']['image.']) && is_array($articleConf['fieldIndex.']['image.'])
			)	{
				$prodImageArray = t3lib_div::trimExplode(',',$row['image']);
				$artImageArray = t3lib_div::trimExplode(',',$articleRow['image']);
				$tmpDestArray = $prodImageArray;
				foreach ($articleConf['fieldIndex.']['image.'] as $kImage => $vImage)	{
					$tmpDestArray[$vImage-1] = $artImageArray[$kImage-1];
				}
				$articleRow['image'] = implode (',', $tmpDestArray);
			}
		}
		return $articleRow;
	}

	/* types:
		'accessories' ... accessory products
		'articles' ... related articles
		'products' ... related products
		returns the uids of the related products or articles
	*/

	function getRelated ($uid, $type) {
		global $TYPO3_DB;
		$rcArray = array();
		$allowedTypeArray = array('accessories', 'articles', 'products');

		if (in_array($type, $allowedTypeArray) && is_array($this->relatedArray[$type]))	{
			if ($type == 'articles')	{
				$relatedArticles = $this->getArticleRows($uid);
				if (count($relatedArticles))	{
					$rowArray = array();
					foreach ($relatedArticles as $k => $articleRow)	{
						$rcArray[] = $articleRow['uid'];
					}
				}
			} else {
				$rowArray = $this->relatedArray[$type][$uid];
				if (!is_array($rowArray) && $uid) {
					$mmTable = array(
						'accessories' => array('table' =>  'tt_products_accessory_products_products_mm'),
						'products' => array('table' =>  'tt_products_related_products_products_mm')
					);
					$rowArray = $TYPO3_DB->exec_SELECTgetRows('*', $mmTable[$type]['table'], 'uid_local = '.intval($uid));
					$this->relatedArray[$type][$uid] = $rowArray;
				}
				foreach ($rowArray as $k => $row)	{
					$rcArray [] = $row['uid_foreign'];
				}
			}
		}
		return $rcArray;
	}


	// returns the Path of all categories above, separated by '/'
	function getPath ($uid) {
		$rc = '';

		return $rc;
	}


	/**
	 * Reduces the instock value of the orderRecord with the sold items and returns the result
	 *
	 * @param	[type]		$$itemArray: ...
	 * @param	[type]		$useArticles: ...
	 * @return	[type]		...
	 */
	function &reduceInStockItems (&$itemArray, $useArticles)	{
		global $TYPO3_DB, $TCA;
		$instockTableArray = array();
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

		$instockField = $cnf->getTableDesc($this->getTableObj()->name, 'inStock');
		$instockField = ($instockField ? $instockField : 'inStock');
		if ($this->getTableObj()->name == 'tt_products' || is_array(($TCA[$this->getTableObj()->name]['columns']['inStock'])) )	{
			// Reduce inStock
			if ($useArticles == 1) {
				// loop over all items in the basket indexed by a sorting text
				foreach ($itemArray as $sort=>$actItemArray) {
					foreach ($actItemArray as $k1=>$actItem) {
						$row = $this->getArticleRow ($actItem['rec'], $theCode);
						if ($row)	{
							$tt_products_articles = &$tablesObj->get('tt_products_articles');
							$tt_products_articles->reduceInStock($row['uid'], $actItem['count']);
							$instockTableArray['tt_products_articles'][$row['uid'].','.$row['itemnumber'].','.$row['title']] = intval($row[$instockField] - $actItem['count']);
						}
					}
				}
			}
			// loop over all items in the basket indexed by a sorting text
			foreach ($itemArray as $sort=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					$row = $actItem['rec'];
					if (!$this->hasAdditional($row,'alwaysInStock')) {
						$this->reduceInStock($row['uid'], $actItem['count']);
						$instockTableArray['tt_products'][$row['uid'].','.$row['itemnumber'].','.$row['title']] = intval($row[$instockField] - $actItem['count']);
					}
				}
			}
		}
		return $instockTableArray;
	}


	/**
	 * Returns true if the item has the $check value checked
	 *
	 * @param	[type]		$$row: ...
	 * @param	[type]		$check: ...
	 * @return	[type]		...
	 */
	function hasAdditional (&$row, $check)  {
		$hasAdditional = false;
		$additional = t3lib_div::xml2array($row['additional']);
		$hasAdditional = tx_div2007_ff::get($additional, $check);

		return $hasAdditional;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$cat: ...
	 * @param	[type]		$pid_list: ...
	 * @return	[type]		...
	 */
	function addWhereCat ($cat, $pid_list)	{
		$bOpenBracket = FALSE;
		$where = '';

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addWhereCat')) {
					$whereNew = $hookObj->addWhereCat($this, $cat, $where, $operator, $pid_list);
					$operator = ($operator ? $operator : 'OR');
					$where .= ($whereNew ? ' '.$operator.' '.$whereNew : '');
				}
			}
		} else if($cat || $cat=='0') {
			$cat = implode(',',t3lib_div::intExplode(',', $cat));
			$where = ' AND ( category IN ('.$cat.') )';
		}

		return $where;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$cat: ...
	 * @param	[type]		$selectConf: ...
	 * @return	[type]		...
	 */
	function addselectConfCat ($cat, &$selectConf)	{

		$tableNameArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addselectConfCat')) {
					$tableNameArray[] = $hookObj->addselectConfCat($this, $cat, $selectConf);
				}
			}
		}

		return implode(',', $tableNameArray);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$cat: ...
	 * @return	[type]		...
	 */
	function getPageUidsCat ($cat)	{
		$uidArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getPageUidsCat')) {
					$hookObj->getPageUidsCat($this, $cat, $uidArray);
				}
			}
		}

		$uidArray = array_unique($uidArray);
		return (implode(',',$uidArray));
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$row: ...
	 * @param	[type]		$field: ...
	 * @return	[type]		...
	 */
	function getProductField (&$row, $field)	{
		return $row[$field];
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_product.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_product.php']);
}


?>
