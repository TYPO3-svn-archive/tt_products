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

global $TYPO3_CONF_VARS;

require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_article_base.php');



class tx_ttproducts_product extends tx_ttproducts_article_base {
	var $dataArray = array(); // array of read in products
	var $relatedArray = array(); // array of related products
	var $table;		   // object of the type tx_table_db
	var $tt_products_articles; // element of class tx_table_db to get the article
	var $bIsProduct=true;	// if this is the base for a product
	var $marker = 'PRODUCT';
	var $type = 'product';
	var $tableconf;
	var $articleArray = array();


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init (&$pibase, &$cnf, &$tt_content, &$paymentshipping, $LLkey, $tablename, $useArticles)  {
		global $TYPO3_DB,$TSFE,$TCA;

		parent::init($pibase, $cnf, 'tt_products', $tt_content, $paymentshipping);

		$this->table = t3lib_div::makeInstance('tx_table_db');

		$tableConfig = array();
		$tableConfig['orderBy'] = $this->cnf->conf['orderBy'];
		if (!$tableConfig['orderBy'])	{
			 $tableConfig['orderBy'] = $this->tableconf['orderBy'];
		}

		$this->table->setConfig($tableConfig);
		$this->table->addDefaultFieldArray(array('sorting' => 'sorting'));

		$requiredFields = 'uid,pid,category,price,price2,directcost,tax,inStock';
		if ($this->tableconf['requiredFields'])	{
			$tmp = $this->tableconf['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}
		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$this->table->setRequiredFieldArray($requiredListArray);
		if (is_array($this->tableconf['language.']) &&
			$this->tableconf['language.']['type'] == 'field' &&
			is_array($this->tableconf['language.']['field.'])
		)	{
			$addRequiredFields = array();
			$addRequiredFields = $this->tableconf['language.']['field.'];
			$this->table->addRequiredFieldArray ($addRequiredFields);
		}

		$tablename = ($tablename ? $tablename : 'tt_products');
		$this->table->setTCAFieldArray($tablename, 'products');

		if ($cnf->bUseLanguageTable($this->tableconf))	{
			$this->table->setLanguage ($LLkey);
			$this->table->setLangName($this->tableconf['language.']['table']);
			$this->table->setTCAFieldArray($this->table->langname, 'productslang', FALSE);
		}

		if ($this->tableconf['language.'] && $this->tableconf['language.']['type'] == 'csv')	{
			$this->table->initLanguageFile($this->tableconf['language.']['file']);
		}

		$this->variant = t3lib_div::makeInstance('tx_ttproducts_variant');
		$this->variant->init($this->pibase, $cnf, $this, $useArticles);
	} // init


	function setArticleTable (&$tt_products_articles)	{
		$this->tt_products_articles = &$tt_products_articles;
	}


	function &getArticleRows ($uid)	{
		$rowArray = $this->articleArray[$uid];
		if (!$rowArray && $uid) {
			$rowArray = $this->tt_products_articles->getWhereArray('uid_product=\''.intval($uid).'\'');
			$this->articleArray[$uid] = $rowArray;
		}
		return $rowArray;
	}


	function &getArticleRow ($row) {
		global $TYPO3_DB;

		$articleRows = $this->getArticleRows(intval($row['uid']));
		$articleRow = $this->variant->fetchArticle($row, $articleRows);
		return $articleRow;
	}


	function get ($uid,$where_clause='') {
		global $TYPO3_DB;

		$rc = $this->dataArray[$uid];
		if (!$rc && $uid) {
			$where = '1=1 '.$this->table->enableFields().' AND uid = '.intval($uid);
			if ($where_clause)	{
				$where .= ' '.$where_clause;
			}
			// Fetching the products
			$res = $this->table->exec_SELECTquery('*', $where);
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$rc = $this->dataArray[$uid] = $row;
		}
		return $rc;
	}


	function getRelated ($uid) {
		global $TYPO3_DB;
		$rowArray = $this->relatedArray[$uid];
		$rcArray = array();
		if (!is_array($rowArray) && $uid) {
			$rowArray = $TYPO3_DB->exec_SELECTgetRows('*', 'tt_products_related_products_products_mm', 'uid_local = '.intval($uid));
			$this->relatedArray[$uid] = $rowArray;
		}
		foreach ($rowArray as $k => $row)	{
			$rcArray [] = $row['uid_foreign'];
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
	 */
	function &reduceInStockItems (&$itemArray, $useArticles)	{
		global $TYPO3_DB, $TCA;
		$instockTableArray = array();

		if ($this->table->name == 'tt_products' || is_array(($TCA[$this->table->name]['columns']['inStock'])) )	{
			// Reduce inStock
			if ($useArticles == 1) {
				// loop over all items in the basket indexed by a sorting text
				foreach ($itemArray as $sort=>$actItemArray) {
					foreach ($actItemArray as $k1=>$actItem) {
						$row = $this->getArticleRow ($actItem['rec']);
						$this->tt_products_articles->reduceInStock($row['uid'], $actItem['count']);
						$instockTableArray['tt_products_articles'][$row['uid'].','.$row['itemnumber'].','.$row['title']] = intval($row['inStock'] - $actItem['count']);
					}
				}
			} else {
				// loop over all items in the basket indexed by a sorting text
				foreach ($itemArray as $sort=>$actItemArray) {
					foreach ($actItemArray as $k1=>$actItem) {
						$row = $actItem['rec'];
						if (!$this->hasAdditional($row,'alwaysInStock')) {
							$this->reduceInStock($row['uid'], $actItem['count']);
							$instockTableArray['tt_products'][$row['uid'].','.$row['itemnumber'].','.$row['title']] = intval($row['inStock'] - $actItem['count']);
						}
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
	function hasAdditional (&$row, $check)  {
		$hasAdditional = false;
		$additional = t3lib_div::xml2array($row['additional']);
		$hasAdditional = $this->pibase->pi_getFFvalue($additional, $check);
		return $hasAdditional;
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	function getItemMarkerArray (&$item, &$markerArray, $catTitle, &$basketExt, $imageNum=0, $imageRenderObj='image', &$tagArray, $forminfoArray=array(), $code='', $id='1')	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		$row = &$item['rec'];
		parent::getItemMarkerArray($item, $markerArray, $catTitle, $basketExt, $imageNum, $imageRenderObj, $tagArray, $forminfoArray, $code, $id);

			// Subst. fields
		$markerArray['###'.$this->marker.'_UNIT###'] = $row['unit'];
		$markerArray['###'.$this->marker.'_UNIT_FACTOR###'] = $row['unit_factor'];
		$iconImgCode = $this->pibase->cObj->IMAGE($this->conf['datasheetIcon.']);
		$markerArray['###ICON_DATASHEET###'] = $iconImgCode;
		$markerArray['###'.$this->marker.'_WWW###'] = $row['www'];
//		$markerArray['###CATEGORY_TITLE###'] = $catTitle;

//		$markerArray["###FIELD_NAME###"]="recs[tt_products][".$row["uid"]."]";

		$markerArray['###FIELD_ID###'] = TT_PRODUCTS_EXTkey.'_'.strtolower($code).'_id_'.$id;
		$markerArray['###BULKILY_WARNING###'] = $row['bulkily'] ? $this->conf['bulkilyWarning'] : '';

		if ($this->conf['itemMarkerArrayFunc'])	{
			$markerArray = $this->pibase->userProcess('itemMarkerArrayFunc',$markerArray);
		}

		if ($code=='SINGLE')	{

			if ($row['note_uid']) {
					// pages
				$page = tx_ttproducts_page::createPageTable(
					$this->pibase,
					$this->cnf,
					$this->tt_content,
					$this->pibase->LLkey,
					$this->conf['table.']['pages'],
					$this->conf['conf.']['pages.'],
					$this->pibase->page,
					$tmp='',
					99
				);
				$notePageArray = $page->getNotes ($row['uid']);
				$contentConf = $this->cnf->getTableConf('tt_content', $code);

				foreach ($notePageArray as $k => $pid)	{
					$pageRow = $page->get($pid);
					$pageMarkerKey = 'PRODUCT_NOTE_UID_'.($k + 1);
					$contentArray = $this->tt_content->getFromPid($pid);
					$countArray = array();
					foreach ($contentArray as $k2 => $contentEl)	{
						$cType = $contentEl['CType'];
						$countArray[$cType] = intval($countArray[$cType]) + 1;
						$markerKey = $pageMarkerKey.'_'.$countArray[$cType].'_'.strtoupper($cType);
						foreach ($tagArray as $index => $v)	{
							if (strstr($index, $pageMarkerKey) !== FALSE)	{
								$fieldPos = strrpos($index, '_');
								$fieldName = substr($index, $fieldPos+1);
								$markerArray['###'.$index.'###'] = $pageRow[$fieldName];
							}
							if (strstr($index, $markerKey) === FALSE)	{
								continue;
							}
							$fieldPos = strrpos($index, '_');
							$fieldName = substr($index, $fieldPos+1);
							$markerArray['###'.$index.'###'] = $contentEl[$fieldName];
							if (isset ($contentConf['displayFields.']) && is_array ($contentConf['displayFields.']) && $contentConf['displayFields.'][$fieldName] == 'RTEcssText')	{
									// Extension CSS styled content
								if (t3lib_extMgm::isLoaded('css_styled_content')) {
									$markerArray['###'.$index.'###'] =
										$this->pibase->pi_RTEcssText($contentEl[$fieldName]);
								} else if (is_array($this->conf['parseFunc.']))	{
									$markerArray['###'.$index.'###'] =
										$this->pibase->cObj->parseFunc($contentEl[$fieldName],$this->conf['parseFunc.']);
								}
							}
						}
					}
				}
			}
			foreach ($tagArray as $key => $val)	{
				if (strstr($key,'PRODUCT_NOTE_UID')!==FALSE)	{
					if (!isset($markerArray['###'.$key.'###']))	{
						$markerArray['###'.$key.'###'] = '';
					}
				}
			}
		}

		if ($row['special_preparation'])	{
			$markerArray['###'.$this->marker.'_SPECIAL_PREP###'] = $this->pibase->cObj->substituteMarkerArray($this->conf['specialPreparation'], $markerArray);
		} else	{
			$markerArray['###'.$this->marker.'_SPECIAL_PREP###'] = '';
		}
	} // getItemMarkerArray


	function addWhereCat ($cat, $pid_list)	{
		global $TYPO3_CONF_VARS;
		$bOpenBracket = FALSE;

		$where = '';

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'])) {
			foreach  ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'] as $classRef) {
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


	function addselectConfCat ($cat, &$selectConf)	{
		global $TYPO3_CONF_VARS;
		$tableNameArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'])) {
			foreach  ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addselectConfCat')) {
					$tableNameArray[] = $hookObj->addselectConfCat($this, $cat, $selectConf);
				}
			}
		}
		return implode(',', $tableNameArray);
	}


	function getPageUidsCat ($cat)	{
		global $TYPO3_CONF_VARS;

		$uidArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'])) {
			foreach  ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getPageUidsCat')) {
					$hookObj->getPageUidsCat($this, $cat, $uidArray);
				}
			}
		}
		$uidArray = array_unique($uidArray);
		return (implode(',',$uidArray));
	}


	function getProductField (&$row, $field)	{
		return $row[$field];
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_product.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_product.php']);
}


?>
