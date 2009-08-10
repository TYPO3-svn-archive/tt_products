<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skårhøj <kasperYYYY@typo3.com>
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
 * product single view functions
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Renè Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


global $TYPO3_CONF_VARS;


require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_marker.php');
require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_javascript_marker.php');

require_once (PATH_BE_ttproducts.'view/field/class.tx_ttproducts_field_datafield_view.php');


class tx_ttproducts_single_view {
	var $pibase; // reference to object of pibase
	var $cObj;
	var $cnf;
	var $conf;
	var $config;
	var $basket;
	var $uid=0; 	// product id
	var $type='product'; 	// 'product', 'article' or 'dam'
	var $variants; 	// different attributes
	var $page; // element of class tx_table_db
	var $tt_content; // element of class tx_table_db
	var $tt_products; // element of class tx_table_db
	var $tt_products_articles;  // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db
	var $fe_users; // element of class tx_table_db
	var $tx_dam; // element of class tx_table_db
	var $tx_dam_cat; // element of class tx_table_db

	var $marker; // marker functions
	var $javaScriptMarker; // JavaScript marker functions
	var $pid; // PID where to go
	var $LLkey; // language key
	var $useArticles;
	var $uidArray=array();

	function init (
		&$pibase,
		&$cnf,
		&$basket,
		&$page,
		&$tt_content,
		&$tt_products,
		&$tt_products_articles,
		&$tt_products_cat,
		&$tx_dam,
		&$tx_dam_cat,
		&$fe_users,
		$uidArray,
		$extVars,
		$pid,
		$LLkey,
		$useArticles
	) {
		$this->pibase = &$pibase;
		$this->cObj = &$this->pibase->cObj;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;
		$this->basket = &$basket;
		$this->page = &$page;
		$this->tt_content = &$tt_content;
		if (count($uidArray))	{
			$this->uidArray = $uidArray;
			reset($uidArray);
			$this->uid = current($uidArray);
			$this->type = key($uidArray);
		}
		$this->variants = $extVars;
		$this->tt_products = &$tt_products;
		$this->tt_products_articles = &$tt_products_articles;
		$this->tt_products_cat = &$tt_products_cat;
		$this->tx_dam = $tx_dam;
		$this->tx_dam_cat = $tx_dam_cat;
		$this->fe_users = &$fe_users;
		$this->pid = $pid;
		$this->LLkey = $LLkey;
		$this->useArticles = $useArticles;
		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($pibase, $cnf, $basket);
		$this->javaScriptMarker = t3lib_div::makeInstance('tx_ttproducts_javascript_marker');
		$this->javaScriptMarker->init($pibase, $cnf, $this->pibase->javascript);
	}

	// returns the single view
	function &printView (&$templateCode, &$error_code, $pageAsCategory, $templateSuffix = '') {
		global $TSFE, $TCA, $TYPO3_DB;
		global $TYPO3_CONF_VARS;

		$itemTableArray = array('product' => &$this->tt_products, 'article' => &$this->tt_products_articles, 'dam' => &$this->tx_dam);
		$rowArray = array('product' => array(), 'article' => array(), 'dam' => array());
		$itemTableConf = $rowArray;
		$itemTableLangFields = $rowArray;
		$content = '';

		if ($this->config['displayCurrentRecord'] && $this->type == 'product' && !$this->useArticles)	{
			$rowArray[$this->type] = $this->pibase->cObj->data;
		} else {
			$itemTableArray[$this->type]->table->enableFields();

			$where = ' AND pid IN ('.$this->page->pid_list.')';
			$rowArray[$this->type] = $itemTableArray[$this->type]->get($this->uid, $where);
			$itemTableConf[$this->type] = $this->cnf->getTableConf($itemTableArray[$this->type]->table->name, 'SINGLE');
			$itemTableLangFields[$this->type] = $this->cnf->getTranslationFields($itemTableConf[$this->type]);
			// TODO: $itemImageFields[$this->type] = $this->cnf->getImageFields($itemTableConf[$this->type]);

			if ($this->type == 'product' || $this->type == 'dam')	{
				if ($this->variants) {
					$itemTableArray[$this->type]->variant->modifyRowFromVariant($rowArray[$this->type], $this->variants);
				}
			} else if ($this->type == 'article') {
				$where = ' AND pid IN ('.$this->page->pid_list.')';
				$rowArray['product'] = $itemTableArray['product']->get(intval($rowArray[$this->type]['uid_product']), $where);
				$itemTableConf['product'] = $this->cnf->getTableConf($itemTableArray['product']->table->name, 'SINGLE');
				$itemTableLangFields['product'] = $this->cnf->getTranslationFields($itemTableConf['product']);
				$itemImageFields['product'] = $this->cnf->getImageFields($itemTableConf['product']);
				// $itemTableArray['article']->mergeProductRow($rowArray['article'], $rowArray['product']);
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
		$tablename = $itemTableArray[$this->type]->table->name;

		if ($row) {
		 	// $this->uid = intval ($row['uid']); // store the uid for later usage here

			$markerArray = array();
			$subpartArray = array();
			$wrappedSubpartArray = array();

				// Get the subpart code
			$itemFrameTemplate ='';
			$giftNumberArray = tx_ttproducts_gifts_div::getGiftNumbers ($this->basket, $rowArray['product']['uid'], $this->variants);
			if ($this->config['displayCurrentRecord'])	{
				$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY_RECORDINSERT###';
			} else if (count($giftNumberArray)) {
				$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY_GIFT###';
			} else if (!$this->conf['alwaysInStock'] && $row['inStock'] <= 0 && $this->conf['showNotinStock'] && is_array($TCA[$itemTableArray[$this->type]->table->name]['columns']['inStock']) ) {
				$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY_NOT_IN_STOCK###';
			} else {
				if ($this->type == 'product')	{
					$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY###';
				} else if ($this->type == 'article'){
					$itemFrameTemplate = '###ARTICLE_SINGLE_DISPLAY###';
				} else if ($this->type == 'dam'){
					$itemFrameTemplate = '###DAM_SINGLE_DISPLAY###';
				}
			}

			// Add the template suffix
			$itemFrameTemplate = substr($itemFrameTemplate, 0, -3).$templateSuffix.'###';
			$itemFrameWork = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker($itemFrameTemplate));
			$this->fe_users->getWrappedSubpartArray($subpartArray, $wrappedSubpartArray, $tablename);

			if (!$itemFrameWork) {
				$error_code[0] = 'no_subtemplate';
				$error_code[1] = $itemFrameTemplate;
				$error_code[2] = $this->pibase->templateFile;
				return '';
			}

			$itemFrameWork = $this->pibase->cObj->substituteMarkerArrayCached($itemFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);

			$markerFieldArray = array(
				'BULKILY_WARNING' => 'bulkily',
				'PRODUCT_SPECIAL_PREP' => 'special_preparation',
				'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
				'LINK_DATASHEET' => 'datasheet');
			$viewTagArray = array();
			$parentArray = array();
			$fieldsArray = $this->marker->getMarkerFields(
				$itemFrameWork,
				$itemTableArray[$this->type]->table->tableFieldArray,
				$itemTableArray[$this->type]->table->requiredFieldArray,
				$markerFieldArray,
				$itemTableArray[$this->type]->marker,
				$viewTagArray,
				$parentArray
			);

			if (count($giftNumberArray)) {
				$personDataFrameWork = $this->pibase->cObj->getSubpart($itemFrameWork,'###PERSON_DATA###');
				// the itemFramework is a smaller part here
				$itemFrameWork = $this->pibase->cObj->getSubpart($itemFrameWork,'###PRODUCT_DATA###');
			}

			// set the title of the single view
			switch ($this->conf['substitutePagetitle']) {
				case 1:
					$TSFE->page['title'] = $row['title'];
					break;
				case 2:
					$TSFE->page['title'] = $row['subtitle'] ? $row['subtitle'] : $row['title'];
					break;
				case 12:
					$TSFE->page['title'] = $row['title'] . ' / ' . $row['subtitle'];
					break;
				case 21:
					$TSFE->page['title'] = $row['subtitle'] . ' / ' . $row['title'];
					break;
			}
			$datasheetFile = $row['datasheet'];
			$wrappedSubpartArray=array();
			$backPID = $this->pibase->piVars['backPID'];
			$backPID = ($backPID ? $backPID : t3lib_div::_GP('backPID'));
			$basketPID = $this->conf['PIDbasket'];
			$bNeedSingleParams = FALSE;

			if ($this->conf['clickIntoBasket'] && $basketPID)	{
				$pid = $basketPID;
			} else if ($this->conf['clickIntoList'] || !$backPID)	{
				$pid = $this->page->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $row);
			} else {
				$pid = $TSFE->id;
				if ($this->conf['NoSingleViewOnList'])	{
					$bNeedSingleParams = TRUE;
				}
			}

			// get categories
			if (!$pageAsCategory || $pageAsCategory == 1)	{
				if ($this->type == 'product' || $this->type == 'article')	{
					$viewCatTable = &$this->tt_products_cat;
				} else if ($this->type == 'dam') {
					$viewCatTable = &$this->tx_dam_cat;
				}
			} else {
				$viewCatTable = &$this->page;
			}
			$variant = $itemTableArray[$this->type]->variant->getVariantFromRow($row);
			$item = $this->basket->getItem($row, $variant);

			$forminfoArray = array('###FORM_NAME###' => 'item_'.$this->uid);
			$viewCatTagArray = array();
			$catParentArray = array();
			$catfieldsArray = $this->marker->getMarkerFields(
				$itemFrameWork,
				$viewCatTable->table->tableFieldArray,
				$viewCatTable->table->requiredFieldArray,
				$tmp = array(),
				$viewCatTable->marker,
				$viewCatTagArray,
				$catParentArray
			);

			$cat = $row['category'];
			$catArray = $viewCatTable->getCategoryArray($row['uid']);
			if (count($catArray))	{
				$cat = current($catArray);
			}

			$categoryMarkerArray = array();
			$viewCatTable->getMarkerArray (
				$categoryMarkerArray,
				$this->page,
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
				$currentCat = $this->pibase->piVars[$viewCatTable->piVar];
				if ($currentCat)	{
					// $bUseBackPid = false;
					$cat = $currentCat;
				}
			}

			$addQueryString = array();
			if ($bNeedSingleParams)	{
				// if the page remains the same then the product parameter will still be needed if there is no list view
				$addQueryString[$this->type] = $row['uid'];
			}
			$this->marker->getSearchParams($addQueryString);
			$this->marker->addQueryStringParam($addQueryString, 'sword', FALSE);

			$linkPid = $pid;
			if ($bUseBackPid && $backPID)	{
				$linkPid = $backPID;
			}
			$linkUrl = tx_div2007_alpha::getPageLink_fh002($this->cObj,$linkPid,'',$this->marker->getLinkParams('',$addQueryString,TRUE,FALSE,$viewCatTable->piVar),array('useCacheHash' => TRUE));
			$linkUrl = htmlspecialchars($linkUrl);

			// link back to the list view or basket view
			$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="' . $linkUrl . '">','</a>');

			$catTitle = $viewCatTable->getMarkerArrayCatTitle($categoryMarkerArray);
			$viewParentCatTagArray = array();
			$viewCatTable->getParentMarkerArray (
				$parentArray,
				$row,
				$catParentArray,
				$this->page,
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

			include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_basketitem_view.php');
			$basketItemView = &t3lib_div::getUserObj('tx_ttproducts_basketitem_view');
			$basketItemView->init(
				$this->pibase,
				$this->tt_products_cat,
				$this->basket->basketExt,
				$this->tx_dam,
				$this->tx_dam_cat
			);

			$basketMarkerArray = array();
			$basketItemView->getItemMarkerArray (
				$itemTableArray[$this->type],
				$item,
				$basketMarkerArray,
				'SINGLE',
				1
			);
			$markerArray = array();
			$itemTableArray[$this->type]->getItemMarkerArray (
				$item,
				$markerArray,
				$catTitle,
				$this->basket->basketExt,
				$this->config['limitImageSingle'],
				'image',
				$viewTagArray,
				$forminfoArray,
				'SINGLE',
				1,
				'',
				'',
				true,
				true
			);

			if ($this->type == 'article')	{
				$prodRow = $itemTableArray['product']->get($row['uid_product']);
				$variant = $itemTableArray['product']->variant->getVariantFromRow($prodRow);
				$item = $this->basket->getItem($prodRow, $variant);
				$itemTableArray['product']->getItemMarkerArray (
					$item,
					$markerArray,
					$catTitle,
					$this->basket->basketExt,
					$this->config['limitImage'],
					'listImage',
					$viewTagArray,
					array(),
					'SINGLE',
					1,
					'',
					'',
					true,
					true
				);
			}
			$subpartArray = array();
			$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'];
			$pidMemo = ( $this->conf['PIDmemo'] ? $this->conf['PIDmemo'] : $TSFE->id);
			$markerArray['###FORM_MEMO###'] = htmlspecialchars(tx_div2007_alpha::getPageLink_fh002($this->cObj, $pidMemo,'',$this->marker->getLinkParams('', array(), true)));

			$addQueryString = array();
			if ($pid == $TSFE->id)	{
				// if the page remains the same then the product parameter will still be needed if there is no list view
				$addQueryString[$this->type] = $row['uid'];
			}

			if ($bUseBackPid && $backPID)	{
				$addQueryString['backPID'] = $backPID;
			}
			$this->marker->addQueryStringParam($addQueryString, 'sword', FALSE);
			$markerArray = $this->marker->addURLMarkers($pid, $markerArray, $addQueryString,'',$bUseBackPid); // Applied it here also...
			$queryPrevPrefix = '';
			$queryNextPrefix = '';
			$prevOrderby = '';
			$nextOrderby = '';

			if ($this->conf['orderByItemNumberSg']) {
				$itemnumberField = $itemTableArray[$this->type]->fields['itemnumber'];
				$queryPrevPrefix = $itemnumberField.' < '.$TYPO3_DB->fullQuoteStr($origRow[$itemnumberField],$tablename);
				$queryNextPrefix = $itemnumberField.' > '.$TYPO3_DB->fullQuoteStr($origRow[$itemnumberField],$tablename);
				$prevOrderby= $itemnumberField.' DESC';
				$nextOrderby= $itemnumberField.' ASC';
			} else {
				if(is_array($itemTableConf[$this->type]) && isset($itemTableConf[$this->type]['orderBy']) && strpos( $itemTableConf[$this->type]['orderBy'],',') === FALSE)	{
					$orderByField = $itemTableConf[$this->type]['orderBy'];
					$queryPrevPrefix = $orderByField.' < '.$TYPO3_DB->fullQuoteStr($origRow[$orderByField],$tablename);
					$queryNextPrefix = $orderByField.' > '.$TYPO3_DB->fullQuoteStr($origRow[$orderByField],$tablename);
					$prevOrderby = $orderByField.' DESC';
					$nextOrderby = $orderByField.' ASC';
				} else {
					$queryPrevPrefix = 'uid < '.intval($this->uid);
					$queryNextPrefix = 'uid > '.intval($this->uid);
					$prevOrderby = 'uid DESC';
					$nextOrderby = 'uid ASC';
				}
			}

			$prevOrderby = $itemTableArray[$this->type]->table->transformOrderby($prevOrderby);
			$nextOrderby = $itemTableArray[$this->type]->table->transformOrderby($nextOrderby);
			$whereFilter = '';
			if (is_array($itemTableConf[$this->type]['filter.']) && $itemTableConf[$this->type]['filter.']['type'] == 'regexp')	{
				if (is_array($itemTableConf[$this->type]['filter.']['field.']))	{
					foreach ($itemTableConf[$this->type]['filter.']['field.'] as $field => $value)	{
						$whereFilter .= ' AND '.$field.' REGEXP \''.$value.'\'';
					}
				}
			}

			$queryprev = '';
			$wherestock = ($this->conf['showNotinStock'] || !is_array($TCA[$itemTableArray[$this->type]->table->name]['columns']['inStock']) ? '' : ' AND (inStock <>0) ') . $whereFilter;
			$queryprev = $queryPrevPrefix . ' AND pid IN ('.$this->page->pid_list.')' . $wherestock . $itemTableArray[$this->type]->table->enableFields();

			$resprev = $itemTableArray[$this->type]->table->exec_SELECTquery('*', $queryprev, '', $TYPO3_DB->stripOrderBy($prevOrderby));

			if ($rowprev = $TYPO3_DB->sql_fetch_assoc($resprev) )	{
				$addQueryString=array();
				$addQueryString[$this->type] = $rowprev['uid'];
				if ($bUseBackPid) 	{
					$addQueryString['backPID'] = $backPID;
				} else if ($cat)	{
					$addQueryString[$viewCatTable->piVar] = $linkCat;
				}
				$this->marker->addQueryStringParam($addQueryString, 'sword', FALSE);
				$wrappedSubpartArray['###LINK_PREV_SINGLE###'] = array('<a href="'. tx_div2007_alpha::getPageLink_fh002($this->cObj, $TSFE->id,'',$this->marker->getLinkParams('',$addQueryString,TRUE,$bUseBackPid,$viewCatTable->piVar),array('useCacheHash' => true)) .'">','</a>');

			} else	{
				$subpartArray['###LINK_PREV_SINGLE###']='';
			}

			$querynext = $queryNextPrefix.' AND pid IN ('.$this->page->pid_list.')'. $wherestock . $itemTableArray[$this->type]->table->enableFields();
			$resnext = $itemTableArray[$this->type]->table->exec_SELECTquery('*', $querynext, '', $TYPO3_DB->stripOrderBy($nextOrderby));

			if ($rownext = $TYPO3_DB->sql_fetch_assoc($resnext) )	{
				$addQueryString=array();
				$addQueryString[$this->type] = $rownext['uid'];
				if ($bUseBackPid) 	{
					$addQueryString['backPID'] = $backPID;
				} else if ($cat)	{
					$addQueryString[$viewCatTable->piVar] = $linkCat;
				}
				$this->marker->addQueryStringParam($addQueryString, 'sword', FALSE);
				$wrappedSubpartArray['###LINK_NEXT_SINGLE###'] = array('<a href="' . tx_div2007_alpha::getPageLink_fh002($this->cObj, $TSFE->id,'',$this->marker->getLinkParams('', $addQueryString,TRUE,$bUseBackPid,$viewCatTable->piVar),array('useCacheHash' => true)) . '">','</a>');
			} else {
				$subpartArray['###LINK_NEXT_SINGLE###'] = '';
			}

			if ($this->type == 'product')	{
				$itemTableArray[$this->type]->variant->removeEmptyMarkerSubpartArray($markerArray, $subpartArray, $row, $this->conf);
			}

			if ($this->type == 'product' && $key = array_search('related_uid', $fieldsArray))	{

				include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_list_view.php');
				$relatedIds = $itemTableArray[$this->type]->getRelated($this->uid);
				if (count($relatedIds))	{
					// List all products:
					$listView = t3lib_div::makeInstance('tx_ttproducts_list_view');
					$listView->init (
						$this->pibase,
						$this->cnf,
						$this->basket,
						$this->page,
						$this->tt_content,
						$this->tt_products,
						$this->tt_products_articles,
						$this->tt_products_cat,
						$this->tx_dam,
						$this->tx_dam_cat,
						$this->fe_users,
						$this->pid,
						$this->LLkey,
						$this->useArticles,
						$this->uidArray
					);

					$listPids = ($this->conf['pidsRelatedProducts'] != '' ? $this->conf['pidsRelatedProducts'] : $this->page->pid_list);

					$this->page->applyRecursive($this->config['recursive'], $listPids);
					$templateArea = 'ITEM_LIST_RELATED_TEMPLATE';
					$tmpContent = $listView->printView (
						$templateCode,
						'LISTRELATED',
						implode(',', $relatedIds),
						$listPids,
						$error_code,
						$templateArea,
						$this->pibase->pageAsCategory,
						array(),
						1
					);
					$markerArray['###PRODUCT_RELATED_UID###'] = $tmpContent;
				} else {
					$markerArray['###PRODUCT_RELATED_UID###'] = '';
				}
			}

			if ($this->type == 'dam')	{
				include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_list_view.php');
				$additional = $this->tt_products->getFlexQuery ('isImage',1);
				$rowArray = $this->tt_products->getWhere ('additional REGEXP \''.$additional.'\'');
				if (count($rowArray))	{
					// List all products:
					$listView = t3lib_div::makeInstance('tx_ttproducts_list_view');
					$listView->init (
						$this->pibase,
						$this->cnf,
						$this->basket,
						$this->page,
						$this->tt_content,
						$this->tt_products,
						$this->tt_products_articles,
						$this->tt_products_cat,
						$this->tx_dam,
						$this->tx_dam_cat,
						$this->fe_users,
						$this->pid,
						$this->LLkey,
						$this->useArticles,
						$this->uidArray
					);

					$templateArea = 'DAM_ITEM_LIST_TEMPLATE';
					$damext = array('tx_dam' =>
						array(
							array('uid' => $this->uid)
						)
					);
					$extArray = array('ext' => $damext);
					$tmpContent = $listView->printView(
						$templateCode,
						'SINGLE',
						implode(',', array_keys($rowArray)),
						false,
						$error_code,
						$templateArea,
						$this->pibase->pageAsCategory,
						$extArray
					);
					$markerArray['###DAM_PRODUCTS###'] = $tmpContent;
				} else {
					$markerArray['###DAM_PRODUCTS###'] = '';
				}
			}
			$jsMarkerArray = array();
			$this->javaScriptMarker->getMarkerArray($jsMarkerArray, $markerArray);
			$markerArray = array_merge ($categoryMarkerArray, $basketMarkerArray, $jsMarkerArray, $markerArray);

			if (isset($row['datasheet']))	{
				$datasheetView = &t3lib_div::getUserObj('&tx_ttproducts_field_datafield_view');
				$datasheetView->getItemSubpartArrays($itemTableConf[$this->type], $row, $wrappedSubpartArray, $markerArray, $this->pibase->cObj);
			}

				// Substitute
			$content = $this->pibase->cObj->substituteMarkerArrayCached($itemFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);

			if ($personDataFrameWork) {
				$subpartArray = array();
				$wrappedSubpartArray=array();
				foreach ($giftNumberArray as $k => $giftnumber) {
					$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($this->basket, $markerArray, $giftnumber);
					$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'].'_'.$giftnumber;
					$markerArray['###FORM_ONSUBMIT###']='return checkParams (document.'.$markerArray['###FORM_NAME###'].')';
					$addQueryString = array();
					$addQueryString[$this->pibase->prefixId.'['.$this->type.']'] = intval($row['uid']);
					$addQueryString[$this->pibase->prefixId.'[variants]'] = htmlspecialchars($this->variants);
					$this->marker->addQueryStringParam($addQueryString, 'sword', FALSE);
					$markerArray = $this->marker->addURLMarkers($backPID,$markerArray, $addQueryString); // Applied it here also...
					$markerArray['###FIELD_NAME###'] = 'ttp_gift[item]['.$row['uid'].']['.$this->variants.']'; // here again, because this is here in ITEM_LIST view
					$markerArray['###FIELD_QTY###'] = $this->basket->basketExt['gift'][$giftnumber]['item'][$row['uid']][$this->variants];
					$content .= $this->pibase->cObj->substituteMarkerArrayCached($personDataFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);
				}
				$this->pibase->javascript->set('email');  // other JavaScript checks can come here
			}
		} else {
			$error_code[0] = 'wrong_parameter';
			$error_code[1] = intval($this->uid);
			$error_code[2] = $this->page->pid_list;
		}
		return $content;
	} // print
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_single_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_single_view.php']);
}


?>
