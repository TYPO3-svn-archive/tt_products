<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2007 Kasper Skårhøj <kasperYYYY@typo3.com>
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
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_marker.php');
require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_javascript_marker.php');


class tx_ttproducts_single_view {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $basket;
	var $uid; 	// product id
	var $type; 	// 'product' or 'article'
	var $variants; 	// different attributes
	var $page; // element of class tx_table_db
	var $tt_content; // element of class tx_table_db
	var $tt_products; // element of class tx_table_db
	var $tt_products_articles;  // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db
	var $fe_users; // element of class tx_table_db

	var $marker; // marker functions
	var $javaScriptMarker; // JavaScript marker functions
	var $pid; // PID where to go
	var $LLkey; // language key
	var $useArticles;

 	function init(&$pibase, &$cnf, &$basket, &$page,
 			&$tt_content, &$tt_products, &$tt_products_articles,
 			&$tt_products_cat, &$fe_users, $uidArray, $extVars,
 			$pid, $LLkey, $useArticles) {
 		$this->pibase = &$pibase;
 		$this->cnf = &$cnf;
 		$this->conf = &$this->cnf->conf;
 		$this->config = &$this->cnf->config;
		$this->basket = &$basket;
 		$this->page = &$page;
 		$this->tt_content = &$tt_content;
 		$this->uid = current($uidArray);
 		$this->type = key($uidArray);
 		$this->variants = $extVars;
 		$this->tt_products = &$tt_products;
 		$this->tt_products_articles = &$tt_products_articles;
 		$this->tt_products_cat = &$tt_products_cat;
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
	function &printView(&$templateCode, &$error_code, $pageAsCategory, $templateSuffix = '') {
		global $TSFE, $TCA, $TYPO3_DB;
		global $TYPO3_CONF_VARS;

		$itemTableArray = array('product' => &$this->tt_products, 'article' => &$this->tt_products_articles);
		$rowArray = array('product' => array(), 'article' => array());
		$itemTableConf = $rowArray;
		$itemTableLangFields = $rowArray; 
		$content = '';

		if ($this->config['displayCurrentRecord'])	{
			$rowArray[$this->type] = $this->pibase->cObj->data;  
		} else {
			$where = ' AND pid IN ('.$this->page->pid_list.')';
			$rowArray[$this->type] = $itemTableArray[$this->type]->get($this->uid, $where);
			$itemTableConf[$this->type] = $this->cnf->getTableConf($itemTableArray[$this->type]->table->name, 'SINGLE');
			$itemTableLangFields[$this->type] = $this->cnf->getTranslationFields($itemTableConf[$this->type]);

			// TODO: $itemImageFields[$this->type] = $this->cnf->getImageFields($itemTableConf[$this->type]);

			if ($this->type == 'product')	{
				if ($this->variants) {
					$itemTableArray[$this->type]->variant->modifyRowFromVariant ($rowArray[$this->type], $this->variants);
				}
			} else {
				$where = ' AND pid IN ('.$this->page->pid_list.')';
				$rowArray['product'] = $itemTableArray['product']->get(intval($rowArray[$this->type]['uid_product']), $where);
				$itemTableConf['product'] = $this->cnf->getTableConf($itemTableArray['product']->table->name, 'SINGLE');
				$itemTableLangFields['product'] = $this->cnf->getTranslationFields($itemTableConf['product']);

				$itemImageFields['product'] = $this->cnf->getImageFields($itemTableConf['product']);
				$itemTableArray['article']->mergeProductRow($rowArray['article'], $rowArray['product']);
			}

		}

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
			} else if ($row['inStock'] <= 0 && $this->conf['showNotinStock'] && is_array($TCA[$itemTableArray[$this->type]->table->name]['columns']['inStock']) ) {
				$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY_NOT_IN_STOCK###';
			} else {
				if ($this->type == 'product')	{
					$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY###';
				} else {
					$itemFrameTemplate = '###ARTICLE_SINGLE_DISPLAY###';
				}
			}
			// Add the template suffix
			$itemFrameTemplate = substr($itemFrameTemplate, 0, -3).$templateSuffix.'###';
			$itemFrameWork = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker($itemFrameTemplate));

			$this->fe_users->getWrappedSubpartArray($subpartArray, $wrappedSubpartArray);
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

				// Fill marker arrays
			$backPID = $this->pibase->piVars['backPID'];
			$backPID = ($backPID ? $backPID : t3lib_div::_GP('backPID'));
			if ($this->conf['clickIntoBasket'] && $backPID)	{
				$pid = $backPID;
			} else {
				$pid = $TSFE->id;
			}

			$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'. $this->pibase->pi_getPageLink($pid,'',$this->marker->getLinkParams('',array(),true), array('useCacheHash' => true)) .'">','</a>');

			if( $datasheetFile == '' )  {
				$wrappedSubpartArray['###LINK_DATASHEET###']= array('<!--','-->');
			}  else  {
				$wrappedSubpartArray['###LINK_DATASHEET###']= array('<a href="uploads/tx_ttproducts/datasheet/'.$datasheetFile.'">','</a>');
			}

			$variant = $itemTableArray[$this->type]->variant->getVariantFromRow ($row);
			$item = $this->basket->getItem($row, $variant);
			$forminfoArray = array ('###FORM_NAME###' => 'item_'.$this->uid);
			$markerArray = array();

			// get categories
			if (!$pageAsCategory || $pageAsCategory == 1)	{
				$viewCatTable = &$this->tt_products_cat;
			} else {
				$viewCatTable = &$this->page;
			}

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

			$viewParentCatTagArray = array();
			$catTitle = $viewCatTable->getMarkerArrayCatTitle($categoryMarkerArray);
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
			$basketItemView->init($this->pibase, $this->tt_products_cat, $this->basket->basketExt);

			$basketMarkerArray = array();
			$subpartArray = array();
			$basketItemView->getItemMarkerArray ($itemTableArray[$this->type], $item, $basketMarkerArray, $viewTagArray, 'SINGLE', 1);
			$basketItemView->getItemSubpartArrays (
				$itemTableArray[$this->type],
				$item,
				$subpartArray,
				$wrappedSubpartArray,
				$viewTagArray,
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
				1
			);

			if ($this->type == 'article')	{ // ($itemTable === $this->tt_products_articles)
				$prodRow = $itemTableArray['product']->get($row['uid_product']);
				$variant = $itemTableArray['product']->variant->getVariantFromRow($prodRow);
				$item = $this->basket->getItem($prodRow, $variant);
				$itemTableArray['product']->getItemMarkerArray (
					$item,
					$markerArray,
					$catTitle,
					$this->basket->basketExt,
					$this->config['limitImageSingle'],
					'listImage',
					$viewTagArray,
					array(),
					'SINGLE',
					1,
					true
				);
			} else {
				$itemTableArray['product']->variant->getItemMarkerArray (
					$item,
					$markerArray,
					$this->basket->basketExt,
					$viewTagArray,
					'SINGLE',
					1
				);
			}
			$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'];

			//$markerArray['###FORM_URL###']=$this->formUrl.'&tt_products='.$this->uid ;
			$addQueryString = array();
			if ($pid == $TSFE->pid)	{
				$addQueryString[$this->pibase->prefixId.'['.$this->type.']'] = $this->uid;
			}

			// $markerArray = $this->marker->addURLMarkers($this->pid,$markerArray, array('tt_products' => $this->uid)); // Applied it here also...
			$markerArray = $this->marker->addURLMarkers($pid, $markerArray, $addQueryString); // Applied it here also...
			// $url = $this->pibase->pi_getPageLink($TSFE->id,'',$this->marker->getLinkParams()) ; // $this->getLinkUrl('','tt_products');
			$queryPrevPrefix = '';
			$queryNextPrefix = '';
			$prevOrderby = '';
			$nextOrderby = ''; 

			if ($this->conf['orderByItemNumberSg']) {
				$itemnumberField = $itemTableArray[$this->type]->fields['itemnumber'];
				$queryPrevPrefix = $itemnumberField.' < '.$TYPO3_DB->fullQuoteStr($row[$itemnumberField],$tablename);
				$queryNextPrefix = $itemnumberField.' > '.$TYPO3_DB->fullQuoteStr($row[$itemnumberField],$tablename);
				$prevOrderby= $itemnumberField.' DESC';
				$nextOrderby= $itemnumberField.' ASC';

			} else {
				$queryPrevPrefix = 'uid < '.intval($this->uid);
				$queryNextPrefix = 'uid > '.intval($this->uid);
				$prevOrderby = 'uid DESC';
				$nextOrderby = 'uid ASC';
			}

			$prevOrderby = $itemTableArray[$this->type]->table->transformOrderby($prevOrderby);
			$nextOrderby = $itemTableArray[$this->type]->table->transformOrderby($nextOrderby);

			$queryprev = '';
			$wherestock = ($this->conf['showNotinStock'] || !is_array($TCA[$itemTableArray[$this->type]->table->name]['columns']['inStock']) ? '' : ' AND (inStock <>0) ');
			$queryprev = $queryPrevPrefix .' AND pid IN ('.$this->page->pid_list.')'. $wherestock . $itemTableArray[$this->type]->table->enableFields();
			// $resprev = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $queryprev,'', $prevOrderby);
			$resprev = $itemTableArray[$this->type]->table->exec_SELECTquery('*', $queryprev, '', $TYPO3_DB->stripOrderBy($prevOrderby));

			if ($rowprev = $TYPO3_DB->sql_fetch_assoc($resprev) )	{
				$addQueryString=array();
				$addQueryString[$this->pibase->prefixId.'['.$this->type.']'] = $rowprev['uid'];
				$addQueryString[$this->pibase->prefixId.'[backPID]'] = $backPID;
				// $wrappedSubpartArray['###LINK_PREV_SINGLE###']=array('<a href="'.$url.'&tt_products='.$rowprev['uid'].'">','</a>');
				$wrappedSubpartArray['###LINK_PREV_SINGLE###']= array('<a href="'. $this->pibase->pi_getPageLink($TSFE->id,'',$this->marker->getLinkParams('', $addQueryString,true),array('useCacheHash' => true)) .'">','</a>');
			} else	{
				$subpartArray['###LINK_PREV_SINGLE###']='';
			}

			$querynext = $queryNextPrefix.' AND pid IN ('.$this->page->pid_list.')'. $wherestock . $itemTableArray[$this->type]->table->enableFields();
			// $resnext = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $querynext, $nextOrderby);
			$resnext = $itemTableArray[$this->type]->table->exec_SELECTquery('*', $querynext, '', $TYPO3_DB->stripOrderBy($nextOrderby));

			if ($rownext = $TYPO3_DB->sql_fetch_assoc($resnext) )	{
				$addQueryString=array();
				$addQueryString[$this->pibase->prefixId.'['.$this->type.']'] = $rownext['uid'];
				$addQueryString[$this->pibase->prefixId.'[backPID]'] = $backPID;				
				// $wrappedSubpartArray['###LINK_NEXT_SINGLE###']=array('<a href="'.$url.'&tt_products='.$rownext['uid'].'">','</a>');
				$wrappedSubpartArray['###LINK_NEXT_SINGLE###'] = array('<a href="'. $this->pibase->pi_getPageLink($TSFE->id,'',$this->marker->getLinkParams('', $addQueryString,true),array('useCacheHash' => true)) .'">','</a>');
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
						$this->fe_users,
						$this->pid,
						$this->LLkey,
						$this->useArticles
					);
					$listPids = $this->conf['pidsRelatedProducts'];
					$this->page->applyRecursive($this->config['recursive'], $listPids);
		
					$templateArea = '###ITEM_LIST_RELATED_TEMPLATE###';
					$tmpContent = $listView->printView(
						$templateCode,
						'SINGLE',
						implode(',', $relatedIds),
						$listPids,
						$error_code,
						$templateArea,
						$this->pibase->pageAsCategory
					);
					$markerArray['###PRODUCT_RELATED_UID###'] = $tmpContent;
				} else {
					$markerArray['###PRODUCT_RELATED_UID###'] = '';
				}
			}

			$this->javaScriptMarker->getMarkerArray($markerArray);		
			$markerArray = array_merge ($categoryMarkerArray, $basketMarkerArray, $markerArray);

				// Substitute	
			$content = $this->pibase->cObj->substituteMarkerArrayCached($itemFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);
			if ($personDataFrameWork) {
				$subpartArray = array();
				$wrappedSubpartArray=array();
				foreach ($giftNumberArray as $k => $giftnumber) {
					$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($this->basket, $markerArray, $giftnumber);
					$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'].'_'.$giftnumber;
					$markerArray['###FORM_ONSUBMIT###']='return checkParams (document.'.$markerArray['###FORM_NAME###'].')';
					//$markerArray['###FORM_URL###'] = $this->pibase->pi_getPageLink(t3lib_div::_GP('backPID'),'',$this->marker->getLinkParams('', array('tt_products' => $row['uid'], 'ttp_extvars' => htmlspecialchars($this->variants))));
					$addQueryString=array();
					$addQueryString[$this->pibase->prefixId.'['.$this->type.']']= intval($row['uid']);
					$addQueryString[$this->pibase->prefixId.'[variants]']= htmlspecialchars($this->variants);
					$markerArray = $this->marker->addURLMarkers($backPID,$markerArray, $addQueryString); // Applied it here also...
					$markerArray['###FIELD_NAME###']='ttp_gift[item]['.$row['uid'].']['.$this->variants.']'; // here again, because this is here in ITEM_LIST view
					$markerArray['###FIELD_QTY###'] = $this->basket->basketExt['gift'][$giftnumber]['item'][$row['uid']][$this->variants];
					$content.=$this->pibase->cObj->substituteMarkerArrayCached($personDataFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);
				}
				$this->pibase->javascript->set('email');  // other JavaScript checks can come here
			}

		} else {
			$error_code[0] = 'wrong_parameter';
			$error_code[1] = intval($this->uid);
		}
		return $content;
	} // print
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_single_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_single_view.php']);
}


?>
