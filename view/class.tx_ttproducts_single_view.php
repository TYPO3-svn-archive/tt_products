<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * product single view functions
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_single_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $uid; 	// product id
	var $type='product'; 	// 'product', 'article' or 'dam'
	var $variants; 	// different attributes

	var $urlObj; // url functions
	var $javascript; // JavaScript functions
	var $javaScriptMarker; // JavaScript marker functions
	var $pid; // PID where to go
	var $useArticles;
	var $uidArray=array();
	var $pidListObj;
	var $cOjb;


	function init (&$pibase, $uidArray, $extVars,
			$pid, $useArticles, $pid_list, $recursive) {
		$this->pibase = $pibase;
		$this->cObj = $pibase->cObj;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		if (count($uidArray))	{
			$this->uidArray = $uidArray;
			reset($uidArray);
			if (isset($uidArray['product']))	{
				$this->type = 'product';
				$this->uid = $uidArray['product'];
			} else if (isset($uidArray['article']))	{
				$this->uid = $uidArray['article'];
				$this->type = 'article';
			} else if (isset($uidArray['dam']) && t3lib_extMgm::isLoaded('dam'))	{
				$this->type = 'dam';
				$this->uid = $uidArray['dam'];
			}
		}

		$this->variants = $extVars;
		$this->pid = $pid;
		$this->useArticles = $useArticles;
		$this->urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view');
		$this->pidListObj = t3lib_div::getUserObj('tx_ttproducts_pid_list');
		$this->pidListObj->init($this->cObj);
		$this->pidListObj->applyRecursive($recursive, $pid_list, TRUE);
		$this->pidListObj->setPageArray();
		$this->javaScriptMarker = t3lib_div::makeInstance('tx_ttproducts_javascript_marker');
		$this->javaScriptMarker->init($pibase);
	}


	// returns the single view
	function &printView (&$templateCode, &$error_code, $pageAsCategory, $templateSuffix = '') {
		global $TSFE, $TCA, $TYPO3_DB;

		$basketObj = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$subpartmarkerObj = t3lib_div::getUserObj('&tx_ttproducts_subpartmarker');
		$theCode = 'SINGLE';

		$itemTableArray = array();
		$itemTableArray['product'] = $tablesObj->get('tt_products');
		$itemTableArray['article'] = $tablesObj->get('tt_products_articles');
		$itemTableViewArray = array();
		$itemTableViewArray['product'] = $tablesObj->get('tt_products', true);
		$itemTableViewArray['article'] = $tablesObj->get('tt_products_articles', true);
		if (t3lib_extMgm::isLoaded('dam'))	{
			$itemTableArray['dam'] = $tablesObj->get('tx_dam');
			$itemTableViewArray['dam'] = $tablesObj->get('tx_dam', true);
		}
		$rowArray = array('product' => array(), 'article' => array(), 'dam' => array());
		$itemTableConf = $rowArray;
		$itemTableLangFields = $rowArray;
		$content = '';
		$javaScriptObj = t3lib_div::getUserObj('&tx_ttproducts_javascript');

		if ($this->config['displayCurrentRecord'] && $this->type == 'product' && !$this->useArticles)	{
			$rowArray[$this->type] = $this->cObj->data;
		} else {
			$where = 'pid IN (' . $this->pidListObj->getPidlist() . ')';
			$rowArray[$this->type] = $itemTableArray[$this->type]->get($this->uid, 0, TRUE, $where);
			$itemTableConf[$this->type] = $cnf->getTableConf($itemTableArray[$this->type]->getFuncTablename(), 'SINGLE');
			$itemTableLangFields[$this->type] = $cnf->getTranslationFields($itemTableConf[$this->type]);

			if ($this->type == 'product' || $this->type == 'dam')	{
				if ($this->variants) {
					$itemTableArray[$this->type]->variant->modifyRowFromVariant($rowArray[$this->type], $this->variants);
				}
			} else if ($this->type == 'article') {
				$where = 'pid IN (' . $this->pidListObj->getPidlist() . ')';
				$rowArray['product'] = $itemTableArray['product']->get(intval($rowArray[$this->type]['uid_product']), 0, TRUE, $where);
				$itemTableConf['product'] = $cnf->getTableConf($itemTableArray['product']->getFuncTablename(), 'SINGLE');
				$itemTableLangFields['product'] = $cnf->getTranslationFields($itemTableConf['product']);
				$itemImageFields['product'] = $cnf->getImageFields($itemTableConf['product']);
				$itemTableArray['article']->mergeAttributeFields($rowArray['product'], $rowArray['article']);
			}
		}
		$origRow = $rowArray[$this->type];

		foreach ($itemTableLangFields as $type => $fieldArray)	{
			if (is_array($fieldArray))	{
				foreach ($fieldArray as $field => $langfield)	{
					$rowArray[$type][$field] = $rowArray[$type][$langfield];
				}
			}
		}
		$row = $rowArray[$this->type];
		$tablename = $itemTableArray[$this->type]->getTableObj()->getName();

		if ($row['uid']) {
			// $this->uid = intval ($row['uid']); // store the uid for later usage here

			$markerArray = array();
			$subpartArray = array();
			$wrappedSubpartArray = array();
			$pageObj = $tablesObj->get('pages');

				// Get the subpart code
			$subPartMarker ='';
			$giftNumberArray = tx_ttproducts_gifts_div::getGiftNumbers ($rowArray['product']['uid'], $this->variants);

			if ($this->config['displayCurrentRecord'])	{
				$subPartMarker = 'ITEM_SINGLE_DISPLAY_RECORDINSERT';
			} else if (count($giftNumberArray)) {
				$subPartMarker = 'ITEM_SINGLE_DISPLAY_GIFT';
			} else if (!$this->conf['alwaysInStock'] && $row['inStock'] <= 0 && $this->conf['showNotinStock'] && is_array($TCA[$itemTableArray[$this->type]->getTableObj()->name]['columns']['inStock']) ) {
				$subPartMarker = 'ITEM_SINGLE_DISPLAY_NOT_IN_STOCK';
			} else {
				if ($this->type == 'product')	{
					$subPartMarker = 'ITEM_SINGLE_DISPLAY';
				} else if ($this->type == 'article'){
					$subPartMarker = 'ARTICLE_SINGLE_DISPLAY';
				} else if ($this->type == 'dam'){
					$subPartMarker = 'DAM_SINGLE_DISPLAY';
				}
			}

			// Add the template suffix
			$subPartMarker = $subPartMarker.$templateSuffix;
			$itemFrameWork = $this->cObj->getSubpart($templateCode,$subpartmarkerObj->spMarker('###'.$subPartMarker.'###'));
			$tablesObj->get('fe_users', true)->getWrappedSubpartArray($subpartArray, $wrappedSubpartArray, $itemTableArray ['product']->getFuncTablename());
			if (!$itemFrameWork) {
				$mainObj = t3lib_div::getUserObj('&tx_ttproducts_main');
				$error_code[0] = 'no_subtemplate';
				$error_code[1] = '###' . $subPartMarker . '###';
				$error_code[2] = $mainObj->templateFile;
				return '';
			}

			$itemFrameWork = $this->cObj->substituteMarkerArrayCached($itemFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);

			$markerFieldArray = array(
				'BULKILY_WARNING' => 'bulkily',
				'PRODUCT_SPECIAL_PREP' => 'special_preparation',
				'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
				'PRODUCT_LINK_DATASHEET' => 'datasheet');
			$viewTagArray = array();
			$parentArray = array();
			$fieldsArray = $markerObj->getMarkerFields(
				$itemFrameWork,
				$itemTableArray[$this->type]->getTableObj()->tableFieldArray,
				$itemTableArray[$this->type]->getTableObj()->requiredFieldArray,
				$markerFieldArray,
				$itemTableArray[$this->type]->marker,
				$viewTagArray,
				$parentArray
			);

			if ($this->type == 'product' && $this->useArticles == 1) {
				$markerFieldArray = array();
				$articleViewTagArray = array();
				$articleParentArray = array();
				$articleFieldsArray = $markerObj->getMarkerFields(
					$itemFrameWork,
					$itemTableArray[$this->type]->getTableObj()->tableFieldArray,
					$itemTableArray[$this->type]->getTableObj()->requiredFieldArray,
					$markerFieldArray,
					$itemTableViewArray ['article']->marker,
					$articleViewTagArray,
					$articleParentArray
				);

				$prodUidField = $cnf->getTableDesc($itemTableArray['article']->getTableObj()->name, 'uid_product');
				$fieldsArray = array_merge($fieldsArray, $articleFieldsArray);
				$uidKey = array_search($prodUidField, $fieldsArray);
				if ($uidKey != '')	{
					unset($fieldsArray[$uidKey]);
				}
			}

			if (count($giftNumberArray)) {
				$personDataFrameWork = $this->cObj->getSubpart($itemFrameWork, '###PERSON_DATA###');
				// the itemFramework is a smaller part here
				$itemFrameWork = $this->cObj->getSubpart($itemFrameWork, '###PRODUCT_DATA###');
			}

			// set the title of the single view
			switch ($this->conf['substitutePagetitle']) {
				case 1:
					$titleStr = $row['title'];
					break;
				case 2:
					$titleStr = $row['subtitle'] ? $row['subtitle'] : $row['title'];
					break;
				case 12:
					$titleStr = $row['title'] . ' / ' . $row['subtitle'];
					break;
				case 21:
					$titleStr = $row['subtitle'] . ' / ' . $row['title'];
					break;
			}
			if (isset($titleStr)) {
				$GLOBALS['TSFE']->page['title'] = $titleStr;
				// set pagetitle for indexed search to the tt_products title
				$GLOBALS['TSFE']->indexedDocTitle = $titleStr;
			}

				// Fill marker arrays
			$backPID = $this->pibase->piVars['backPID'];
			$backPID = ($backPID ? $backPID : t3lib_div::_GP('backPID'));
			$basketPID = $this->conf['PIDbasket'];
			$bNeedSingleParams = FALSE;

			if ($this->conf['clickIntoList'])	{
				$pid = $pageObj->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $row);
			} else if ($this->conf['clickIntoBasket'] && ($basketPID || $backPID))	{
				$pid = ($basketPID ? $basketPID : $backPID);
			} else {
				$pid = $TSFE->id;
				$bNeedSingleParams = TRUE;
			}

			// get categories
			if (!$pageAsCategory || $pageAsCategory == 1)	{
				if ($this->type == 'product' || $this->type == 'article')	{
					$viewCatTable = $tablesObj->get('tt_products_cat');
					$viewCatViewTable = $tablesObj->get('tt_products_cat', true);
				} else if ($this->type == 'dam') {
					$viewCatTable = $tablesObj->get('tx_dam_cat');
					$viewCatViewTable = $tablesObj->get('tx_dam_cat', true);
				}
			} else {
				$viewCatTable = $pageObj;
				$viewCatViewTable = $tablesObj->get('pages', true);
			}

			if ($this->type == 'product')	{
				$viewTextTable = $tablesObj->get('tt_products_texts');
				$viewTextViewTable = $tablesObj->get('tt_products_texts',TRUE);
				$textTagArray = $viewTextViewTable->getTagMarkerArray($viewTagArray, $itemTableArray['product']->marker);
				$itemArray = $viewTextTable->getChildUidArray($this->uid, $textTagArray, 'tt_products');
				$viewTextViewTable->getItemMarkerArray($itemArray, $markerArray, $itemTableArray['product']->marker, $textTagArray);
			}
			$item = $basketObj->getItem($row, 'firstVariant');
			$forminfoArray = array('###FORM_NAME###' => 'item_'.$this->uid);

			if ($this->type == 'product' && t3lib_extMgm::isLoaded('taxajax'))	{
				$variantFieldArray = $itemTableArray[$this->type]->variant->getFieldArray();
				$param = array($itemTableArray[$this->type]->getFuncTablename() => $variantFieldArray);
				$javaScriptObj->set('fetchdata', $param);
			}
			$viewCatTagArray = array();
			$catParentArray = array();

			$catfieldsArray = $markerObj->getMarkerFields(
				$itemFrameWork,
				$viewCatTable->getTableObj()->tableFieldArray,
				$viewCatTable->getTableObj()->requiredFieldArray,
				$tmp = array(),
				$viewCatTable->marker,
				$viewCatTagArray,
				$catParentArray
			);
			$cat = $row['category'];
			$itemTableConf['category'] = $cnf->getTableConf($viewCatTable->getFuncTablename(), 'SINGLE');
			$catArray = $viewCatTable->getCategoryArray($row['uid'],$itemTableConf['category']['orderBy']);

			if (count($catArray))	{
				reset($catArray);
				$cat = current($catArray);
			}

			$categoryMarkerArray = array();
			$viewCatViewTable->getMarkerArray (
				$categoryMarkerArray,
				$cat,
				$row['pid'],
				$this->config['limitImage'],
				'listcatImage',
				$viewCatTagArray,
				array(),
				$pageAsCategory,
				'SINGLE',
				1,
				''
			);
			$bUseBackPid = TRUE;
			if ($cat) {
				$currentCat = $this->pibase->piVars[$viewCatViewTable->getPivar()];
				if ($currentCat)	{
					// $bUseBackPid = false;
					$cat = $currentCat;
				}
			}

			if ($pid === $TSFE->id && isset($this->conf['PIDlistDisplay'])) {
				$linkPid = $pageObj->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $row);
			} else {
				$linkPid = $pid;
			}

			if ($bUseBackPid && $backPID)	{
				$linkPid = $backPID;
			}

			if ($viewTagArray['LINK_ITEM'])	{
				$addQueryString = array();

				if (
					(
						(
							$linkPid == $TSFE->id && !$bUseBackPid
						)
					) &&
					$this->conf['NoSingleViewOnList']
				) {
					// if the page remains the same then the product parameter will still be needed
					$excludeList = '';
				} else {
					$excludeList = $itemTableViewArray[$this->type]->getPivar();
				}
				$sword = $this->pibase->piVars['sword'];
				if ($sword) 	{
					$addQueryString['sword'] = $sword;
				}

				$queryString = $this->urlObj->getLinkParams($excludeList, $addQueryString, TRUE, FALSE, $viewCatViewTable->getPivar());

				$linkUrl = tx_div2007_alpha5::getPageLink_fh003($this->cObj, $linkPid, '', $queryString, array('useCacheHash' => TRUE));
				$linkUrl = htmlspecialchars($linkUrl);
				$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="' . $linkUrl . '">','</a>');
			}

			if ($viewCatTagArray['LINK_CATEGORY'])	{
				$catRow = $viewCatTable->get($cat);
				$catListPid = $pageObj->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $catRow);
				$viewCatViewTable->getSubpartArrays($this->urlObj, $catRow, $subpartArray, $wrappedSubpartArray, $viewCatTagArray, $catListPid, 'LINK_CATEGORY');
			}

			$catTitle = $viewCatViewTable->getMarkerArrayCatTitle($categoryMarkerArray);
			$viewParentCatTagArray = array();
			$viewCatViewTable->getParentMarkerArray (
				$parentArray,
				$row,
				$catParentArray,
				$row['category'],
				$row['pid'],
				$this->config['limitImage'],
				'listcatImage',
				$viewParentCatTagArray,
				array(),
				$pageAsCategory,
				'SINGLE',
				1,
				''
			);

			if ($viewCatTagArray['LINK_PARENT1_CATEGORY'])	{
				$catRow = $viewCatTable->getParent($cat);
				$catListPid = $pageObj->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $catRow);
				$viewCatTable->getSubpartArrays($this->urlObj, $catRow, $subpartArray, $wrappedSubpartArray, $viewCatTagArray, $catListPid, 'LINK_PARENT1_CATEGORY');
			}

			if ($this->type == 'product' || $this->type == 'article')	{
// 				include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_basketitem_view.php');
				$basketItemView = t3lib_div::getUserObj('tx_ttproducts_basketitem_view');
				$basketItemView->init($this->pibase, $basketObj->basketExt);
				$basketItemView->getItemMarkerArray (
					$itemTableArray[$this->type]->getFuncTablename(),
					$item,
					$markerArray,
					$viewTagArray,
					$tmpHidden,
					'SINGLE',
					1,
					true
				);
				$itemTableViewArray[$this->type]->getItemMarkerSubpartArrays (
					$itemFrameWork,
					$row,
					$markerArray,
					$subpartArray,
					$wrappedSubpartArray,
					$viewTagArray,
					'SINGLE'
				);
			}
			$itemTableViewArray[$this->type]->getItemMarkerArray (
				$item,
				$markerArray,
				$catTitle,
				$this->config['limitImageSingle'],
				'image',
				$viewTagArray,
				$forminfoArray,
				'SINGLE',
				1,
				'',
				'',
				true,
				$TSFE->renderCharset
			);

			if ($this->type == 'product' && $this->useArticles == 1) {
				// get the article uid with these colors, sizes and gradings
				$articleRow = $itemTableArray['product']->getArticleRow($row, 'SINGLE');

					// use the product if no article row has been found
				$prodVariantRow = $row;
				if ($articleRow)	{
					$itemTableArray['product']->mergeAttributeFields($prodVariantRow, $articleRow, FALSE);
				}
				$prodVariantItem = $item;
					// use the fields of the article instead of the product
				//
				$prodVariantItem['rec'] = $prodVariantRow;
				$itemTableViewArray['article']->getItemMarkerArray (
					$prodVariantItem,
					$markerArray,
					$catTitle,
					$this->config['limitImage'],
					'image',
					$articleViewTagArray,
					array(),
					'SINGLE',
					1,
					'',
					'',
					true,
					$TSFE->renderCharset
				);
				$itemTableViewArray['article']->getItemSubpartArrays (
					$itemFrameWork,
					$row,
					$subPartArray,
					$wrappedSubpartArray,
					$articleViewTagArray,
					$theCode,
					$iCount
				);
			} else if ($this->type == 'article')	{
				$prodRow = $itemTableArray['product']->get($row['uid_product']);
				// $variant = $itemTableArray['product']->variant->getFirstVariantRow();
				$item = $basketObj->getItem($prodRow, 'firstVariant');
				$itemTableViewArray['product']->getItemMarkerArray (
					$item,
					$markerArray,
					$catTitle,
					$this->config['limitImage'],
					'listImage',
					$viewTagArray,
					array(),
					'SINGLE',
					1,
					'',
					'',
					TRUE,
					$TSFE->renderCharset
				);
			}

			$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'];
			$pidMemo = ( $this->conf['PIDmemo'] ? $this->conf['PIDmemo'] : $TSFE->id);
			$markerArray['###FORM_MEMO###'] = htmlspecialchars(tx_div2007_alpha5::getPageLink_fh003($this->cObj,$pidMemo,'',$this->urlObj->getLinkParams('', array(), TRUE)));

			//$markerArray['###FORM_URL###']=$this->formUrl.'&tt_products='.$this->uid ;
			$addQueryString = array();
			if ($bNeedSingleParams)	{
				// if the page remains the same then the product parameter will still be needed if there is no list view
				$addQueryString[$this->type] = $this->uid;
			}
			if ($bUseBackPid && $backPID)	{
				$addQueryString['backPID'] = $backPID;
			}
			$markerArray = $this->urlObj->addURLMarkers($pid, $markerArray, $addQueryString, '', $bUseBackPid); // Applied it here also...
			$queryPrevPrefix = '';
			$queryNextPrefix = '';
			$prevOrderby = '';
			$nextOrderby = '';
			$bDefaultOrder = TRUE;

			if ($this->conf['orderByItemNumberSg']) {
				$itemnumberField = $itemTableArray[$this->type]->fields['itemnumber'];
				$queryPrevPrefix = $itemnumberField.' < ' . $TYPO3_DB->fullQuoteStr($origRow[$itemnumberField], $tablename);
				$queryNextPrefix = $itemnumberField.' > ' . $TYPO3_DB->fullQuoteStr($origRow[$itemnumberField], $tablename);
				$prevOrderby = $itemnumberField.' DESC';
				$nextOrderby = $itemnumberField.' ASC';
				$bDefaultOrder = FALSE;
			} else {

				if(is_array($itemTableConf[$this->type]) && isset($itemTableConf[$this->type]['orderBy']))	{
					$orderByFieldArray = t3lib_div::trimExplode(',',$itemTableConf[$this->type]['orderBy']);
					$count = count($orderByFieldArray);

					if ($count)	{
						$bDefaultOrder = FALSE;
						$queryPrevPrefixArray = array();
						$queryNextPrefixArray = array();
						$prevOrderbyArray = array();
						$nextOrderbyArray = array();
						$limitArray = array();

						foreach($orderByFieldArray as $i => $orderByFieldLine)	{
							$bIsDesc = (stripos($orderByFieldLine,'DESC') !== FALSE);
							$bIsLast = ($i == $count - 1);
							$orderByField = str_ireplace('ASC', '', $orderByFieldLine);
							$orderByField = trim(str_ireplace('DESC', '', $orderByField));
							$comparatorPrev = ($bIsDesc ? '>' : '<');
							$comparatorNext = ($bIsDesc ? '<' : '>');
							$comparand = $TYPO3_DB->fullQuoteStr($origRow[$orderByField], $tablename);

							$newPrevPrevix = $orderByField . ' '. $comparatorPrev . ' ' . $comparand;
							$newNextPrevix = $orderByField . ' ' . $comparatorNext . ' ' . $comparand;

							$ascOperatorPrev = ($bIsDesc ? 'ASC' : 'DESC');
							$ascOperatorNext = ($bIsDesc ? 'DESC' : 'ASC');
							$prevOrderbyArray[] = $orderByField . ' ' . $ascOperatorPrev;
							$nextOrderbyArray[] = $orderByField . ' ' . $ascOperatorNext;

							if ($bIsLast)	{
								$lastPrevPrevix = implode(' AND ',$limitArray) . (count($limitArray) > 0 ? ' AND ' : '') . $newPrevPrevix;
								$lastNextPrevix = implode(' AND ',$limitArray) . (count($limitArray) > 0 ? ' AND ' : '') .  $newNextPrevix;
							} else {
								$limitArray[] = $orderByField . '=' . $comparand;
								$queryPrevPrefixArray[] = $newPrevPrevix;
								$queryNextPrefixArray[] = $newNextPrevix;
							}
						}
						$queryNextPrefix = '(' . implode(' AND ', $queryNextPrefixArray) . (count($queryNextPrefixArray) > 0 ? ' OR ' : '') . $lastNextPrevix . ')';
						$queryPrevPrefix = '(' . implode(' AND ', $queryPrevPrefixArray) . (count($queryNextPrefixArray) > 0 ? ' OR ' : '') . $lastPrevPrevix . ')';
						$prevOrderby =  implode(',', $prevOrderbyArray);
						$nextOrderby =  implode(',', $nextOrderbyArray);
					}
				}
			}
			if ($bDefaultOrder)	{
				$queryPrevPrefix = 'uid < ' . intval($this->uid);
				$queryNextPrefix = 'uid > ' . intval($this->uid);

				$prevOrderby = 'uid DESC';
				$nextOrderby = 'uid ASC';
			}
			$prevOrderby = $itemTableArray[$this->type]->getTableObj()->transformOrderby($prevOrderby);
			$nextOrderby = $itemTableArray[$this->type]->getTableObj()->transformOrderby($nextOrderby);
			$whereFilter = '';
			if (is_array($itemTableConf[$this->type]['filter.']) && is_array($itemTableConf[$this->type]['filter.']['regexp.']))	{
				if (is_array($itemTableConf[$this->type]['filter.']['regexp.']['field.']))	{
					foreach ($itemTableConf[$this->type]['filter.']['field.'] as $field => $value)	{
						$whereFilter .= ' AND ' . $field . ' REGEXP ' . $TYPO3_DB->fullQuoteStr($value, $tablename);
					}
				}
			}
			$wherePid = ' AND pid IN (' . $this->pidListObj->getPidlist() . ')';

			$queryprev = '';
			$wherestock = ($this->conf['showNotinStock'] || !is_array($TCA[$itemTableArray[$this->type]->getTableObj()->name]['columns']['inStock']) ? '' : ' AND (inStock <>0) ') . $whereFilter;
			$queryprev = $queryPrevPrefix . $wherePid . $wherestock . $itemTableArray[$this->type]->getTableObj()->enableFields();

			// $resprev = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $queryprev,'', $prevOrderby);
			$resprev = $itemTableArray[$this->type]->getTableObj()->exec_SELECTquery('*', $queryprev, '', $TYPO3_DB->stripOrderBy($prevOrderby));

			if ($rowprev = $TYPO3_DB->sql_fetch_assoc($resprev) )	{
				$addQueryString=array();
				$addQueryString[$this->type] = $rowprev['uid'];

				if ($bUseBackPid) 	{
					$addQueryString['backPID'] = $backPID;
				} else if ($cat)	{
					$addQueryString[$viewCatViewTable->getPivar()] = $cat;
				}
				$wrappedSubpartArray['###LINK_PREV_SINGLE###'] = array('<a href="'. htmlspecialchars(tx_div2007_alpha5::getPageLink_fh003($this->cObj, $TSFE->id, '', $this->urlObj->getLinkParams('', $addQueryString, true, $bUseBackPid, $viewCatViewTable->getPivar()), array('useCacheHash' => true))) . '">','</a>');
			} else	{
				$subpartArray['###LINK_PREV_SINGLE###']='';
			}

			$TYPO3_DB->sql_free_result($resprev);
			$querynext = $queryNextPrefix./*' AND pid IN ('.$this->pidListObj->getPidlist().')'*/ $wherePid . $wherestock . $itemTableArray[$this->type]->getTableObj()->enableFields();

			$resnext = $itemTableArray[$this->type]->getTableObj()->exec_SELECTquery('*', $querynext, '', $TYPO3_DB->stripOrderBy($nextOrderby));

			if ($rownext = $TYPO3_DB->sql_fetch_assoc($resnext) )	{
				$addQueryString=array();
				$addQueryString[$this->type] = $rownext['uid'];
				if ($bUseBackPid) 	{
					$addQueryString['backPID'] = $backPID;
				} else if ($cat)	{
					$addQueryString[$viewCatViewTable->getPivar()] = $cat;
				}

				$wrappedSubpartArray['###LINK_NEXT_SINGLE###'] = array('<a href="' . htmlspecialchars(tx_div2007_alpha5::getPageLink_fh003($this->cObj,$TSFE->id,'',$this->urlObj->getLinkParams('', $addQueryString,true,$bUseBackPid,$viewCatViewTable->getPivar()),array('useCacheHash' => true))) . '">','</a>');
			} else {
				$subpartArray['###LINK_NEXT_SINGLE###'] = '';
			}
			$TYPO3_DB->sql_free_result($resnext);

			if ($this->type == 'product')	{
				$itemTableViewArray[$this->type]->variant->removeEmptyMarkerSubpartArray(
					$markerArray,
					$subpartArray,
					$row,
					$this->conf,
					$itemTableArray[$this->type]->hasAdditional($row, 'isSingle'),
					!$itemTableArray[$this->type]->hasAdditional($row, 'noGiftService')
				);
			}
			$damext = array('tx_dam' =>
				array(
					array('uid' => $this->uid)
				)
			);
			$extArray = array('ext' => $damext);

			$addTableListArray = array(
				'product' => array(
					'articles' => array(
						'marker' => 'PRODUCT_RELATED_ARTICLES',
						'template' => 'ITEM_LIST_RELATED_ARTICLES_TEMPLATE',
						'require' => $this->useArticles,
						'code' => 'LISTARTICLES',
						'additionalPages' => $this->conf['pidsRelatedArticles'],
						'mergeRow' => array(),
						'functablename' => 'tt_products_articles'
					),
					'accessories' => array(
						'marker' => 'PRODUCT_ACCESSORY_UID',
						'template' => 'ITEM_LIST_ACCESSORY_TEMPLATE',
						'require' => TRUE,
						'code' => 'SINGLE',
						'additionalPages' => $this->conf['pidsRelatedAccessories'],
						'mergeRow' => array(),
						'functablename' => 'tt_products'
					),
					'products' => array(
						'marker' => 'PRODUCT_RELATED_UID',
						'template' => 'ITEM_LIST_RELATED_TEMPLATE',
						'require' => TRUE,
						'code' => 'LISTRELATED',
						'additionalPages' => $this->conf['pidsRelatedProducts'],
						'mergeRow' => array(),
						'functablename' => 'tt_products'
					)
				)
			);

			if (t3lib_extMgm::isLoaded('dam'))	{
				$addTableListArray['dam'] = array(
					'products' => array(
						'marker' => 'DAM_PRODUCTS',
						'template' => 'DAM_ITEM_LIST_TEMPLATE',
						'require' => TRUE,
						'code' => 'SINGLE',
						'additionalPages' => FALSE,
						'mergeRow' => $extArray,
						'functablename' => 'tt_products'
					)
				);
			}

			$addListArray = $addTableListArray[$this->type];
			if (is_array($addListArray))	{
				$listView = '';
				foreach ($addListArray as $type => $funcArray)	{

					if (isset($viewTagArray[$funcArray['marker']]) && $funcArray['require'])	{
						$relatedIds = $itemTableArray[$this->type]->getRelated($this->uid, $type);

						if (count($relatedIds))	{
							// List all products:
// 							include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_list_view.php');
							if (!is_object($listView))	{

								$listView = t3lib_div::makeInstance('tx_ttproducts_list_view');
								$listView->init (
									$this->pibase,
									$this->pid,
									$this->useArticles,
									$this->uidArray,
									$tmp = $this->pidListObj->getPidlist(),
									0
								);
							}

							$listPids = ($funcArray['additionalPages'] != '' ? $funcArray['additionalPages'] : $this->pidListObj->getPidlist());

							if ($listPids)	{
								$this->pidListObj->applyRecursive($this->config['recursive'], $listPids);
							}
							$tmpContent = $listView->printView (
								$templateCode,
								$funcArray['code'],
								$funcArray['functablename'],
								implode(',', $relatedIds),
								$listPids,
								$error_code,
								$funcArray['template'] . $this->config['templateSuffix'],
								$pageAsCategory,
								array(),
								1
							);
							$markerArray['###'.$funcArray['marker'].'###'] = $tmpContent;
						} else {
							$markerArray['###'.$funcArray['marker'].'###'] = '';
						}
					} else {
						$markerArray['###'.$funcArray['marker'].'###'] = '';
					}
				}
			}
			$jsMarkerArray = array();
			$this->javaScriptMarker->getMarkerArray($jsMarkerArray, $markerArray);
			$markerArray = array_merge($categoryMarkerArray, $jsMarkerArray, $markerArray);
			$markerArray['###HIDDENFIELDS###'] = $hiddenText; // TODO
			$markerArray = $markerObj->reduceMarkerArray($itemFrameWork, $markerArray);

				// Substitute
			$content = $this->cObj->substituteMarkerArrayCached($itemFrameWork, $markerArray, $subpartArray, $wrappedSubpartArray);

			if ($personDataFrameWork) {
				$subpartArray = array();
				$wrappedSubpartArray=array();
				foreach ($giftNumberArray as $k => $giftnumber) {
					$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($markerArray, $giftnumber);
					$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'].'_'.$giftnumber;
					$markerArray['###FORM_ONSUBMIT###']='return checkParams (document.'.$markerArray['###FORM_NAME###'].')';
					$addQueryString = array();
					$addQueryString[$this->type] = intval($row['uid']);
					$addQueryString['variants'] = htmlspecialchars($this->variants);
					$markerArray = $this->urlObj->addURLMarkers($backPID,$markerArray, $addQueryString); // Applied it here also...
					$markerArray['###FIELD_NAME###'] = 'ttp_gift[item]['.$row['uid'].']['.$this->variants.']'; // here again, because this is here in ITEM_LIST view
					$markerArray['###FIELD_QTY###'] = $basketObj->basketExt['gift'][$giftnumber]['item'][$row['uid']][$this->variants];
					$content .= $this->cObj->substituteMarkerArrayCached($personDataFrameWork, $markerArray, $subpartArray, $wrappedSubpartArray);
				}
				$javaScriptObj->set('email');  // other JavaScript checks can come here
			}
		} else { // 		if ($row['uid']) {
			$error_code[0] = 'wrong_parameter';
			$error_code[1] = ($this->type ? $this->type : 'product');
			$error_code[2] = intval($this->uidArray['product']);
			$error_code[3] = $this->pidListObj->getPidlist();
		}
		return $content;
	} // print
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_single_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_single_view.php']);
}


?>