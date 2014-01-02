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
	public $relatedArray = array(); // array of related products
	public $marker = 'PRODUCT';
	public $type = 'product';
	public $piVar='product';
	public $articleArray = array();
	protected $tableAlias = 'product';

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init (&$cObj, $functablename='tt_products')  {
		global $TYPO3_DB,$TSFE,$TCA;

		parent::init($cObj, $functablename);
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$tableConfig = array();
		$tableConfig['orderBy'] = $cnf->conf['orderBy'];

		if (!$tableConfig['orderBy'])	{
			 $tableConfig['orderBy'] = $this->getOrderBy();
		}

		$tableObj = &$this->getTableObj();
		$tableObj->setConfig($tableConfig);
		$tableObj->addDefaultFieldArray(array('sorting' => 'sorting'));


// 		$requiredFields = 'uid,pid,category,price,price2,directcost,tax';
// 		$tableconf = $cnf->getTableConf($functablename);
// 		if ($tableconf['requiredFields'])	{
// 			$tmp = $tableconf['requiredFields'];
// 			$requiredFields = ($tmp ? $tmp : $requiredFields);
// 		}

		$this->relatedArray['accessories'] = array();
		$this->relatedArray['articles'] = array();
		$this->relatedArray['products'] = array();
	} // init


	public function fixTableConf (&$tableConf)	{
		if (t3lib_extMgm::isLoaded('static_info_tables_taxes') && !t3lib_extMgm::isLoaded('patchem'))	{
			$tableConf['requiredFields'] = str_replace(',tax,', ',tax_id,', $tableConf['requiredFields']);
		}
	}


	public function &getArticleRows ($uid, $whereArticle='')	{
		$rowArray = $this->articleArray[$uid];

		if (!$rowArray && $uid || $whereArticle!='') {
			$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
			$articleObj = &$tablesObj->get('tt_products_articles');
			$rowArray = $articleObj->getWhereArray($uid, $whereArticle);

			if (!$whereArticle)	{
				$this->articleArray[$uid] = $rowArray;
			}
		}
		return $rowArray;
	}


	public function fillVariantsFromArticles (&$row)	{

		$articleRowArray = $this->getArticleRows($row['uid']);

		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$articleObj = &$tablesObj->get('tt_products_articles');

		if (count($articleRowArray))	{
			// $articleObj->sortArticleRowsByUidArray($row['uid'],$articleRowArray);
			$variantRow = $this->variant->getVariantValuesByArticle($articleRowArray,$row);
			$row = array_merge($row, $variantRow);
		}

/*
		$fieldArray = $this->variant->getFieldArray();
		foreach ($fieldArray as $field)	{
			if (isset($row[$field]))	{
				$valueArray = array();
				foreach ($articleRowArray as $articleRow)	{
					$articleValueArray = t3lib_div::trimExplode(';',$articleRow[$field]);
					if ($articleValueArray[0])	{
						$valueArray = array_merge ($valueArray, $articleValueArray);
					}
				}
				$valueArray = array_unique($valueArray);
				$row[$field] = implode(';', $valueArray);
			}
		}*/
	}


	public function &getArticleRowsFromVariant ($row, $theCode, $variant) {

		$articleRowArray = $this->getArticleRows(intval($row['uid']));
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$articleObj = &$tablesObj->get('tt_products_articles');
	//	$articleRowArray = $articleObj->sortArticleRowsByUidArray($row['uid'],$articleRowArray);
		$rc = $this->variant->filterArticleRowsByVariant($articleRowArray, $variant, TRUE);
		return $rc;
	}


	function getMatchingArticleRows ($productRow, $articleRows) {

		$fieldArray = array();
		foreach ($this->variant->conf as $k => $field)	{
			if ($productRow[$field] && $field != $this->variant->additionalField)	{
				$fieldArray[$field] = t3lib_div::trimExplode(';', $productRow[$field]);
			}
		}
		$articleRow = array();
		if (count($fieldArray))	{

			$bFitArticleRowArray = array();
			foreach ($articleRows as $k => $row)	{
				$bFits = TRUE;
				foreach ($fieldArray as $field => $valueArray)	{
					$rowFieldArray = t3lib_div::trimExplode(';',$row[$field]);
					$intersectArray = array_intersect($valueArray, $rowFieldArray);
					if ($row[$field] && !count($intersectArray) && $field != 'additional')	{
						$bFits = FALSE;
						break;
					}
				}
				if ($bFits)	{
					$bFitArticleRowArray[] = $row;
				}
			}
			$articleCount = count($bFitArticleRowArray);
			$articleRow = $bFitArticleRowArray[0];

			if ($articleCount > 1)	{
				// many articles fit here. So lets generated a merged article.
				$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
				$articleObj = &$tablesObj->get('tt_products_articles');
				for ($i=1; $i<=$articleCount; ++$i)	{

					$articleObj->mergeAttributeFields($articleRow, $bFitArticleRowArray[$i], FALSE, TRUE, TRUE);
				}
				if (isset($articleRow['ext']))	{
					unset($articleRow['ext']);
				}
			}
		}

		return $articleRow;
	}


	public function &getArticleRow ($row, $theCode, $bUsePreset=TRUE) {
		global $TYPO3_DB;

		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$articleNo = FALSE;
		if ($bUsePreset)	{
			$presetVarianArray = tx_ttproducts_control_product::getPresetVariantArray($row['uid']);
			if (!count($presetVarianArray))	{
				$articleNo = tx_ttproducts_control_product::getActiveArticleNo();
			}
		} else {
			$presetVarianArray = array();
		}

		if ($articleNo === FALSE)	{
			$currentRow = $this->variant->getVariantRow($row,$presetVarianArray);
		} else {
			$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
			$articleObj = &$tablesObj->get('tt_products_articles');

			$articleRow = $articleObj->get($articleNo);
			$variantRow = $this->variant->getVariantValuesByArticle(array($articleRow),$row);
			$currentRow = array_merge($row, $variantRow);

			// $articleObj->mergeAttributeFields($currentRow, $articleRow, FALSE, TRUE);
		}

		$fieldArray = $this->variant->getSelectableFieldArray();
		$whereArray = array();
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$articleObj = &$tablesObj->get('tt_products_articles');

		foreach ($fieldArray as $k => $field)	{
			$whereClause = $field.'=\''.$currentRow[$field].'\'';

			$value = trim($currentRow[$field]);
			// $value = $TYPO3_DB->fullQuoteStr($value, $articleObj->getTablename());
			$regexpValue = $TYPO3_DB->quoteStr(quotemeta($value), $articleObj->getTablename());
			if ($value!='')	{
				$whereClause = '('.$whereClause;
				$whereClause .= ' OR '.$field.' LIKE \'%;'.$value.';%\''.
				' OR '.$field.' REGEXP \'^('.$regexpValue.')[[:blank:]]*[[.semicolon.]]\''.
				' OR '.$field.' REGEXP \'[[.semicolon.]][[:blank:]]*('.$regexpValue.')[[:blank:]]*$\')';
				$whereArray[] = $whereClause;
			} else if ($this->conf['useArticles'] == 1) {
				$whereArray[] = $whereClause;
			}
		}

		if (count($whereArray))	{
			$where = '(' . implode (($this->conf['useArticles'] == '3' ? ' OR ' : ' AND '), $whereArray) . ')';
		} else {
			$where = '';
		}

		$articleRows = $this->getArticleRows(intval($row['uid']), $where);

		if (is_array($articleRows) && count($articleRows))	{
			$articleRow = $this->getMatchingArticleRows($row, $articleRows);
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


	public function getRowFromExt ($funcTablename, $row, $useArticles)	{
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$priceRow = $row;

		if (
			in_array($useArticles, array(1,3)) &&
			$funcTablename == 'tt_products' &&
			isset($row['ext']['tt_products_articles']) &&
			is_array($row['ext']['tt_products_articles'])
		) {
			$articleObj = &$tablesObj->get('tt_products_articles');
			reset($row['ext']['tt_products_articles']);

			$articleInfo = current($row['ext']['tt_products_articles']);
			foreach ($row['ext']['tt_products_articles'] as $extRow)	{

				$articleUid = $extRow['uid'];

				if (isset($articleUid))	{
					$articleRow = $articleObj->get($articleUid);
					$articleObj->mergeAttributeFields($priceRow, $articleRow, FALSE,TRUE);
				}

				if ($articleRow)	{
					$priceRow['weight'] = ($articleRow['weight'] ? $articleRow['weight'] : $row['weight']);
					$priceRow['inStock'] = $articleRow['inStock'];
				}
			}
		}
		return $priceRow;
	}


	public function getArticleRowFromExt ($row)	{
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

		$rc = FALSE;
		$extArray = $row['ext'];

		if (isset($extArray) && is_array($extArray) && is_array($extArray['tt_products_articles']) && is_array($extArray['tt_products_articles']['0']))	{
			$articleUid = $extArray['tt_products_articles']['0']['uid'];
			$articleTable = &$tablesObj->get('tt_products_articles', FALSE);
			$rc = $articleTable->get($articleUid);
		}
		return $rc;
	}


	/* types:
		'accessories' ... accessory products
		'articles' ... related articles
		'products' ... related products
		returns the uids of the related products or articles
	*/
	public function getRelated ($uid, $type) {
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
				if (t3lib_div::testInt($uid))	{
					$rowArray = $this->relatedArray[$type][$uid];
				}
				if (!is_array($rowArray) && $uid) {
					$mmTable = array(
						'accessories' => array('table' =>  'tt_products_accessory_products_products_mm'),
						'products' => array('table' =>  'tt_products_related_products_products_mm')
					);
					if (t3lib_div::testInt($uid))	{
						$where = 'uid_local = '.intval($uid);
					} else if (is_array($uid))	{
						$where = 'uid_local IN ('.implode(',',$uid).')';
					}
					$rowArray = $TYPO3_DB->exec_SELECTgetRows('*', $mmTable[$type]['table'], $where);
					if (t3lib_div::testInt($uid))	{
						$this->relatedArray[$type][$uid] = $rowArray;
					}
				}
				if (isset($rowArray) && is_array($rowArray))	{
					foreach ($rowArray as $k => $row)	{
						$rcArray[] = $row['uid_foreign'];
					}
				}
			}
		}
		return $rcArray;
	}


	// returns the Path of all categories above, separated by '/'
	public function getPath ($uid) {
		$rc = '';

		return $rc;
	}


	/**
	 * Reduces the instock value of the orderRecord with the sold items and returns the result
	 *
	 */
	public function &reduceInStockItems (&$itemArray, $useArticles)	{
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
						$row = $this->getArticleRow($actItem['rec'], $theCode);
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
	 */
	public function hasAdditional (&$row, $check)  {
		$hasAdditional = false;
		$additional = t3lib_div::xml2array($row['additional']);
		$hasAdditional = tx_div2007_ff::get($additional, $check);

		return $hasAdditional;
	}


	public function addWhereCat (&$catObject, $theCode, $cat, $pid_list, $bLeadingOperator=TRUE)	{
		$bOpenBracket = FALSE;
		$where = '';

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'] as $classRef) {
				$hookObj=&t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addWhereCat')) {
					$whereNew = $hookObj->addWhereCat($this, $catObject, $cat, $where, $operator, $pid_list, $catObject->getDepth($theCode));
					if ($bLeadingOperator)	{
						$operator = ($operator ? $operator : 'OR');
						$where .= ($whereNew ? ' '.$operator.' '.$whereNew : '');
					} else {
						$where .= $whereNew;
					}
				}
			}
		} else if($cat || $cat == '0') {
			$cat = implode(',',t3lib_div::intExplode(',', $cat));
			$where = 'category IN ('.$cat.')';
			if ($bLeadingOperator)	{
				$where = ' AND ( ' . $where . ')';
			}
		}
		return $where;
	}


	public function addConfCat ($catObject, &$selectConf, $aliasArray)	{
		$tableNameArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addConfCatProduct')) {
					$newTablenames = $hookObj->addConfCatProduct($this, $catObject, $selectConf, $aliasArray);
					if ($newTablenames != '')	{
						$tableNameArray[] = $newTablenames;
					}
				}
			}
		}
		return implode(',', $tableNameArray);
	}


	public function addselectConfCat ($catObject, $cat, &$selectConf)	{
		$tableNameArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addselectConfCat')) {
					$newTablenames = $hookObj->addselectConfCat($this, $catObject, $cat, $selectConf,$catObject->getDepth());
					if ($newTablenames != '')	{
						$tableNameArray[] = $newTablenames;
					}
				}
			}
		}
		return implode(',', $tableNameArray);
	}


	public function getPageUidsCat ($cat)	{
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


	public function getProductField (&$row, $field)	{
		return $row[$field];
	}


	public function getRequiredFields ($theCode='')	{
		$tableConf = $this->getTableConf($theCode);
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

		if ($tableConf['requiredFields']!='')	{
			$requiredFields = $tableConf['requiredFields'];
		} else {
			$requiredFields = 'uid,pid,category,price,price2,directcost,tax';
		}
		$instockField = $cnf->getTableDesc($functablename,'inStock');
		if ($instockField && !$this->conf['alwaysInStock'])	{
			$requiredFields = $requiredFields.','.$instockField;
		}

		$rc = $requiredFields;
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_product.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_product.php']);
}


?>
