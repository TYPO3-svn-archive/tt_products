<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Part of the tt_products (Shopping System) extension.
 *
 * product list view functions
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Renè Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_marker.php');

class tx_ttproducts_list_view {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $basket;
	var $page;
	var $tt_content; // element of class tx_table_db
	var $tt_products; // element of class tx_table_db
	var $tt_products_articles; // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db
	var $fe_users; // element of class tx_table_db
	var $pid; // pid where to go
	var $marker; // marker functions
	var $LLkey; // language key
	var $useArticles;
	var $searchFieldList='';

	function init(&$pibase, &$cnf, &$basket, &$basketView,
			&$page, &$tt_content, &$tt_products,
			&$tt_products_articles, &$tt_products_cat, &$fe_users,
			$pid, $LLkey, $useArticles) {
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;
		$this->basket = &$basket;
		$this->basketView = &$basketView;
		$this->page = &$page;
		$this->tt_content = &$tt_content;
		$this->tt_products = &$tt_products;
		$this->tt_products_articles = &$tt_products_articles;
		$this->tt_products_cat = &$tt_products_cat;
		$this->fe_users = &$fe_users;
		$this->pid = $pid;
		$this->LLkey = $LLkey;
		$this->useArticles = $useArticles;	
		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($pibase, $cnf, $basket);

			//extend standard search fields with user setup
		$this->searchFieldList = trim($this->conf['stdSearchFieldExt']) ? implode(',', array_unique(t3lib_div::trimExplode(',',$this->searchFieldList.','.trim($this->conf['stdSearchFieldExt']),1))) : 'title,note,'.$this->tt_products->fields['itemnumber'];
	}


	function finishHTMLRow($iColCount, $tableRowOpen)  {
		$itemsOut = '';
		if ($tableRowOpen)	{
			$iColCount++;
			while ($iColCount <= $this->conf['displayBasketColumns']) {
				$itemsOut.= '<td></td>';
				$iColCount++;
			}
			$itemsOut.= ($tableRowOpen ? '</tr>' : '');
		}
		return $itemsOut;
	} // comp


	function &advanceCategory(&$categoryAndItemsFrameWork, &$itemListOut, &$categoryOut)	{
		$subpartArray = array();
		$subpartArray['###ITEM_CATEGORY###'] = $categoryOut;
		$subpartArray['###ITEM_LIST###'] = $itemListOut;
		$rc = $this->pibase->cObj->substituteMarkerArrayCached($categoryAndItemsFrameWork,array(),$subpartArray);
		$categoryOut = '';
		$itemListOut = '';			// Clear the item-code var
		return $rc;
	}


	// returns the products list view
	function &printView(
		&$templateCode,
		$theCode,
		$allowedItems,
		&$error_code,
		$templateArea = '###ITEM_LIST_TEMPLATE###',
		$pageAsCategory
	) {
		global $TSFE, $TCA, $TYPO3_DB;
		
		$content = '';
		$out = '';
		$more = 0;		// If set during this loop, the next-item is drawn
		$where = '';
		$formName = 'ShopListForm';
		$itemTable = &$this->tt_products;

		if (!$pageAsCategory || $pageAsCategory == 1)	{
			$viewCatTable = &$this->tt_products_cat;
		} else {
			$viewCatTable = &$this->page;
		}
 
		// if parameter 'newitemdays' is specified, only new items from the last X days are displayed
		$newitemdays = $this->pibase->piVars['newitemdays'];
		$newitemdays = ($newitemdays ? $newitemdays : t3lib_div::_GP('newitemdays'));  
		if ($newitemdays) {
			$temptime = time() - 86400*intval(trim($newitemdays));
			// $where = ' AND tstamp >= '.$temptime;
			$where = ' AND (starttime >= '.$temptime . ' OR tstamp >= '. $temptime . ')'; 
		}			
		
		$cat = $this->tt_products_cat->getParamDefault();
		$where .= $itemTable->addWhereCat($cat, $this->page->pid_list);
		$formName = $this->conf['form.'][$theCode.'.']['name'];

		if ($allowedItems)	{
			$where .= ' AND uid IN ('.$allowedItems.')';
		}

		switch ($theCode) {
			case 'SEARCH':
					// Get search subpart
				$t['search'] = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker('###ITEM_SEARCH###'));
					// Substitute a few markers
				$out = $t['search'];
				$swords = t3lib_div::_GP('swords');
				$pid = ( $this->conf['PIDsearch'] ? $this->conf['PIDsearch'] : $this->pibase->pid);
				$markerArray = $this->marker->addURLMarkers($pid,array());
				$markerArray['###FORM_NAME###'] = $formName;
				$out = $this->pibase->cObj->substituteMarkerArrayCached($out,$markerArray);

				$htmlSwords = htmlspecialchars($swords);
				$out = $this->pibase->cObj->substituteMarker($out, '###SWORDS###', $htmlSwords);
				if ($formName)	{
						// Add to content
					$content .= $out;
				}
				$out = '';
				// $entitySwords = htmlentities($swords); if the data has been entered e.g. with '&uuml;' instead of '&uuml;' 
				if ($htmlSwords)	{
					$where .= $this->tt_products->searchWhere($this->searchFieldList, trim($htmlSwords));
				}
			break;
			case 'LISTGIFTS':
				$formName = 'GiftForm';
				$where .= ' AND '.($this->conf['whereGift'] ? $this->conf['whereGift'] : '1=0');
				$templateArea = '###ITEM_LIST_GIFTS_TEMPLATE###';
			break;
			case 'LISTOFFERS':
				$formName = 'ListOffersForm';
				$where .= ' AND offer';
			break;
			case 'LISTHIGHLIGHTS':
				$formName = 'ListHighlightsForm';
				$where .= ' AND highlight';
			break;
			case 'LISTNEWITEMS':
				$formName = 'ListNewItemsForm';
				$temptime = time() - 86400*intval(trim($this->conf['newItemDays']));
				$where .= ' AND tstamp >= '.$temptime;
			break;
			case 'LISTARTICLES':
				$formName = 'ListArticlesForm';
				if ($this->conf['useArticles'])	{
					$itemTable = &$this->tt_products_articles;
				}
			break;
			case 'MEMO':
				$formName = 'ListMemoForm';
			break;
			default:
				// nothing here
			break;
		}

		$begin_at = $this->pibase->piVars['begin_at'];
		$begin_at = ($begin_at ? $begin_at : t3lib_div::_GP('begin_at'));
		$begin_at=t3lib_div::intInRange($begin_at,0,100000);
		if ($where || ($theCode != 'SEARCH' && !t3lib_div::_GP('swords')))	{
			$t['listFrameWork'] = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker($templateArea));
			if (!$t['listFrameWork']) {
				$error_code[0] = 'no_subtemplate';
				$error_code[1] = $templateArea;
				$error_code[2] = $this->conf['templateFile'];
				return $content;
			}
		
				// different language is used?
			if ($this->LLkey)	{
				
			}
			
			$markerArray = $this->marker->addURLMarkers($this->pid,array());
			$wrappedSubpartArray = array();
			$this->marker->getWrappedSubpartArray($pid,$wrappedSubpartArray);
			$subPartArray = array();
			$this->fe_users->getWrappedSubpartArray($subPartArray, $wrappedSubpartArray);
			$t['listFrameWork'] = $this->pibase->cObj->substituteMarkerArrayCached($t['listFrameWork'],$markerArray,$subPartArray,$wrappedSubpartArray);
			$t['categoryAndItemsFrameWork'] = $this->pibase->cObj->getSubpart($t['listFrameWork'],'###ITEM_CATEGORY_AND_ITEMS###');
			$t['categoryFrameWork'] = $this->pibase->cObj->getSubpart($t['categoryAndItemsFrameWork'],'###ITEM_CATEGORY###');
			$t['itemFrameWork'] = $this->pibase->cObj->getSubpart($t['categoryAndItemsFrameWork'],'###ITEM_LIST###');
			$t['item'] = $this->pibase->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');
			$dum = strstr($t['item'], 'ITEM_SINGLE_POST_HTML');
			$bItemPostHtml = (strstr($t['item'], 'ITEM_SINGLE_POST_HTML') != false);

				// Get products count
			$selectConf = Array();
			$selectConf['pidInList'] = $this->page->pid_list;
			$wherestock = ($this->conf['showNotinStock'] || !is_array(($TCA[$itemTable->table->name]['columns']['inStock'])) ? '' : ' AND (inStock <> 0) ');
			$whereNew = $wherestock.$where;
			$whereNew = $itemTable->table->transformWhere($whereNew);
			$selectConf['where'] = '1=1 '.$whereNew;
			$selectConf['from'] = $itemTable->table->getAdditionalTables();
	
				// performing query to count all products (we need to know it for browsing):
			$selectConf['selectFields'] = 'count(*)';
			$tablename = $itemTable->table->name;
			// $queryParts = $this->pibase->cObj->getQuery($tablename, $selectConf, TRUE);
			$queryParts = $itemTable->table->getQueryConf($this->pibase->cObj, $tablename, $selectConf, TRUE);
			$res = $itemTable->table->exec_SELECT_queryArray($queryParts);
			$row = $TYPO3_DB->sql_fetch_row($res);
			$productsCount = $row[0];
	
				// range check to current productsCount
			$begin_at = t3lib_div::intInRange(($begin_at >= $productsCount)?($productsCount > $this->config['limit'] ? $productsCount-$this->config['limit'] : $productsCount):$begin_at,0);
			
				// Get products count			
			$selectConf['orderBy'] = $this->conf['orderBy'];
			$productsConf = $this->cnf->getTableConf($itemTable->table->name,$theCode);

				// performing query for display:	
			if (!$selectConf['orderBy'])	{
				 $selectConf['orderBy'] = $productsConf['orderBy'];				
			}
			$tmpArray = t3lib_div::trimExplode(',', $selectConf['orderBy']);
			$orderByProduct = $tmpArray[0];
			$orderByCat = $viewCatTable->$this->catconf['ALL.']['orderBy'];

				// sorting by category not yet possible for articles
			if ($itemTable->type == 'article')	{ // ($itemTable === $this->tt_products_articles)
				$orderByCat = '';	// articles do not have a direct category
				$orderByArray = split (',', $selectConf['orderBy']);
				$orderByArray = array_diff($orderByArray, array('category'));
				$selectConf['orderBy'] = implode (',', $orderByArray);
			} 
			if ($itemTable->fields['itemnumber'])	{
				$selectConf['orderBy'] = str_replace ('itemnumber', $itemTable->fields['itemnumber'], $selectConf['orderBy']);
			}
			$selectConf['orderBy'] = $itemTable->table->transformOrderby($selectConf['orderBy']);
			$markerFieldArray = array('BULKILY_WARNING' => 'bulkily',
				'PRODUCT_SPECIAL_PREP' => 'special_preparation',
				'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
				'LINK_DATASHEET' => 'datasheet');
			$viewTagArray = array();
			$fieldsArray = $this->marker->getMarkerFields(
				$t['item'],
				$itemTable->table->name,
				$itemTable->table->tableFieldArray,
				$itemTable->table->requiredFieldArray,
				$markerFieldArray,
				$itemTable->table->marker,
				$viewTagArray
			);
			$itemTableConf = $this->cnf->getTableConf($itemTable->table->name, $theCode);
			$itemTableLangFields = $this->cnf->getTranslationFields($itemTableConf);
			$fieldsArray = array_merge($fieldsArray, $itemTableLangFields);
			$viewCatTagArray = array();
			$catfieldsArray = $this->marker->getMarkerFields(
				$t['categoryFrameWork'],
				$viewCatTable->table->name,
				$viewCatTable->table->tableFieldArray,
				$viewCatTable->table->requiredFieldArray,
				$tmp = array(),
				$viewCatTable->table->marker,
				$viewCatTagArray
			);
			$catTitle = '';
			if ($orderByCat && ($pageAsCategory < 2))	{
				// $catFields = ($orderByCat == 'uid' ? $orderByCat : 'uid,'.$orderByCat);
				$selectConf['orderBy'] = $viewCatTable->table->transformOrderby($orderByCat).
					($selectConf['orderBy'] ? ','. $selectConf['orderBy'] : '');

				$prodAlias = $itemTable->table->getAliasName();
				$catAlias = $viewCatTable->table->getAliasName();

				// SELECT *
				// FROM tt_products
				// LEFT OUTER JOIN tt_products_cat ON tt_products.category = tt_products_cat.uid
				$selectConf['leftjoin'] = $viewCatTable->table->name.' '.$catAlias.' ON '.$catAlias.'.uid='.$prodAlias.'.category';
				$catTables = $viewCatTable->table->getAdditionalTables();
				$selectConf['from'] = ($catTables ? $catTables.', '.$selectConf['from']:$selectConf['from']);
			}

			$selectFields = implode(',', $fieldsArray);
			$selectConf['selectFields'] = $itemTable->table->transformSelect($selectFields);
			$join = '';
			$tmpTables = $itemTable->table->transformTable('',false,$join);
			// $selectConf['where'] = $join.$itemTable->table->transformWhere($selectConf['where']);
			$selectConf['where'] = $join.' '.$selectConf['where'];
			$selectConf['max'] = ($this->config['limit']+1);
			$selectConf['begin'] = $begin_at;
			// $selectConf['from'] = ($selectConf['from'] ? $selectConf['from'].', ':'').$itemTable->table->getAdditionalTables();
			
			if ($selectConf['orderBy'])	{
				$selectConf['orderBy'] = $TYPO3_DB->stripOrderBy($selectConf['orderBy']);
			}
			
			$tablename = $itemTable->table->name;
			$queryParts = $itemTable->table->getQueryConf($this->pibase->cObj,$tablename, $selectConf, TRUE);
			$res = $TYPO3_DB->exec_SELECT_queryArray($queryParts);
			$itemArray=array();
			$iCount = 0;
			while ($iCount < $this->config['limit'] && ($row = $TYPO3_DB->sql_fetch_assoc($res)))		{
				$iCount++;
				if (count($itemTableLangFields))	{
					foreach ($itemTableLangFields as $field => $langfield)	{
						$row[$field] = $row[$langfield];
					}
				}
				
				$itemArray[]=$row;
			}
			if ($iCount == $this->config['limit'] && ($row = $TYPO3_DB->sql_fetch_assoc($res)))	{
				$more = 1;
			}
	
			if ($theCode == 'LISTGIFTS') {
				$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($this->basket, $markerArray, $this->giftnumber);
			}
			$markerArray['###FORM_NAME###'] = $formName;
			$markerArray['###FORM_ONSUBMIT###']='return checkParams (document.'.$markerArray['###FORM_NAME###'].');';
			$markerFramework = 'listFrameWork'; 
			$t[$markerFramework] = $this->pibase->cObj->substituteMarkerArrayCached($t[$markerFramework],$markerArray,array(),array());
			$this->pibase->javascript->set('email');
			$t['itemFrameWork'] = $this->pibase->cObj->substituteMarkerArrayCached($t['itemFrameWork'],$markerArray,array(),array());
			$currentP = '';
			$out = '';
			$categoryAndItemsOut = '';
			$iCount = 0;
			$iColCount = 0;	

			$itemsOut = '';
			$itemListOut = '';
			$categoryOut = '';
			$tableRowOpen = 0;

			if (count ($itemArray))	{
				foreach ($itemArray as $k2 => $row) {
					$iColCount++;
					$iCount++;
	
						// Print Category Title
					if	(
							($pageAsCategory < 2) && ($row['category'] != $currentP) ||
							($pageAsCategory == 2) && ($row['pid'] != $currentP)
						)	{

						if ($itemListOut)	{
							$out .= $this->advanceCategory($t['categoryAndItemsFrameWork'], $itemListOut, $categoryOut);
							$itemListOut = '';
						}
						$currentP = (($pageAsCategory < 2) ? $row['category'] : $row['pid']);
						$bCategoryHasChanged = true;
						$iColCount = 1;
						$markerArray = array();
						if ($where || $this->conf['displayListCatHeader'])	{
							$viewCatTable->getMarkerArray (
								$markerArray, 
								$this->page,
								$row['category'], 
								$row['pid'], 
								$this->config['limitImage'], 
								'listcatImage', 
								$viewCatTagArray, 
								array(), 
								$pageAsCategory,
								$theCode,
								$iCount
							);
							$catTitle = $viewCatTable->getMarkerArrayCatTitle($markerArray);
							if ($t['categoryFrameWork'])	{
								$categoryOut = $this->pibase->cObj->substituteMarkerArray($t['categoryFrameWork'], $markerArray);
							}
						}
					} else {
						$bCategoryHasChanged = false;
					}
					$tmp = $this->conf['CSS.'][$itemTable->table->name.'.']['list.']['default'];
					$css_current = ($tmp ? $tmp : $this->conf['CSSListDefault']);	// only for backwards compatibility
	
					if ($row['uid'] == $this->tt_product_single) {
						$tmp = $this->conf['CSS.'][$itemTable->table->name.'.']['list.']['current'];
						$css_current = ($tmp ? $tmp : $this->conf['CSSListCurrent']);
					}
					$css_current = ($css_current ? '" id="'.$css_current.'"' : '');
	
						// Print Item Title
					$wrappedSubpartArray=array();
					$addQueryString=array();
					$typoVersion = t3lib_div::int_from_ver($GLOBALS['TYPO_VERSION']);
					$pid = $this->page->getPID($this->conf['PIDitemDisplay'], $this->conf['PIDitemDisplay.'], $row);
					if ($typoVersion < 3008000)	{
						$pageLink = 'index.php?id='.$pid.'&'.$this->pibase->prefixId.'['.strtolower($itemTable->marker).']='.intval($row['uid']).'&'.$this->pibase->prefixId.'[backPID]='.$TSFE->id;						
					} else {
						$addQueryString[$itemTable->type] = intval($row['uid']);
						$addQueryString['cat'] = $cat;
						$queryString = $this->marker->getLinkParams('', $addQueryString);	
						// $pageLink = $this->pibase->pi_getPageLink($pid,'',$queryString);
						// 1-$TSFE->no_cache
						$pageLink = $this->pibase->pi_linkTP_keepPIvars_url($queryString,1,0,$pid);
					}
					$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'. $pageLink .'"'.$css_current.'>','</a>');
					$datasheetFile = $row['datasheet'] ;
					if( $datasheetFile == '' )  {
						$wrappedSubpartArray['###LINK_DATASHEET###'] = array('<!--','-->');
					}  else  {
						$wrappedSubpartArray['###LINK_DATASHEET###'] = array('<a href="uploads/tx_ttproducts/datasheet/'.$datasheetFile.'">','</a>');
					}
	
					$variant = $itemTable->variant->getVariantFromRow($row);
					$item = $this->basket->getItem($row, $variant);
					$markerArray = array();
					$this->basketView->getItemMarkerArray ($item, $markerArray, $this->basket->basketExt, $theCode, $iCount);
					$itemTable->getItemMarkerArray ($item, $markerArray, $catTitle, $this->basket->basketExt, $this->config['limitImage'],'listImage', $viewTagArray, $theCode, $iCount);
					if ($itemTable->type == 'article')	{ // ($itemTable === $this->tt_products_articles)
						$prodRow = $this->tt_products->get($row['uid_product']);
						$variant = $itemTable->variant->getVariantFromRow($prodRow);
						$item = $this->basket->getItem($prodRow, $variant);
						$this->tt_products->getItemMarkerArray ($item, $markerArray, $catTitle, $this->basket->basketExt, $this->config['limitImage'],'listImage', $viewTagArray, array(), $theCode, $iCount);
					} else {
						$this->tt_products->variant->getItemMarkerArray ($item, $markerArray, $this->basket->basketExt, $viewTagArray, $theCode, $iCount);
					}
					
					if ($theCode == 'LISTGIFTS') {
						$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($this->basket, $markerArray, $this->basket->giftnumber);
					}
					$subpartArray = array();
	
					// $markerArray['###FORM_URL###']=$this->formUrl; // Applied later as well.
					$markerArray = $this->marker->addURLMarkers($this->pid,$markerArray);
					$markerArray['###FORM_NAME###'] = $formName;
					$markerArray['###ITEM_NAME###'] = 'item_'.$iCount;
					if (!$this->conf['displayBasketColumns'])	{
						$markerArray['###FORM_NAME###'] = $markerArray['###ITEM_NAME###']; 						
					}
	
					$rowEven = $this->conf['CSS.'][$itemTable->table->name.'.']['row.']['even'];
					$rowEven = ($rowEven ? $rowEven : $this->conf['CSSRowEven']); // backwards compatible
					$rowUneven = $this->conf['CSS.'][$itemTable->table->name.'.']['row.']['uneven'];
					$rowUneven = ($rowUneven ? $rowUneven : $this->conf['CSSRowUneven']); // backwards compatible
					// alternating css-class eg. for different background-colors
					$evenUneven = (($iCount & 1) == 0 ? $rowEven : $rowUneven);
	
					$temp='';
					if ($iColCount == 1) {
						if ($evenUneven) {
							$temp = '<tr class="'.$evenUneven.'">';
						} else {
							$temp = '<tr>';
						}
						$tableRowOpen = 1;
					}
					$markerArray['###ITEM_SINGLE_PRE_HTML###'] = $temp;
					$temp='';
					if (!$this->conf['displayBasketColumns'] || $iColCount == $this->conf['displayBasketColumns']) {
						$temp = '</tr>';
						$tableRowOpen = 0;
					}
					$markerArray['###ITEM_SINGLE_POST_HTML###'] = $temp;
					$pid = ( $this->conf['PIDmemo'] ? $this->conf['PIDmemo'] : $TSFE->id);
					$markerArray['###FORM_MEMO###'] = $this->pibase->pi_getPageLink($pid,'',$this->marker->getLinkParams('', array(), true)); //$this->getLinkUrl($this->conf['PIDmemo']);
	
					// cuts note in list view
					if (strlen($markerArray['###PRODUCT_NOTE###']) > $this->conf['max_note_length']) {
						$markerArray['###PRODUCT_NOTE###'] = substr(strip_tags($markerArray['###PRODUCT_NOTE###']), 0, $this->conf['max_note_length']) . '...';
					}
					if (is_object($itemTable->variant))	{
						$itemTable->variant->removeEmptySubpartArray($subpartArray, $row, $this->conf);
					}
					if ($t['item'])	{
						$tempContent = $this->pibase->cObj->substituteMarkerArrayCached($t['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
					}
					$itemsOut .= $tempContent;
	
	//				} // foreach ($productList as $k2 => $row)

					// max. number of columns reached?
					if (!$this->conf['displayBasketColumns'] || $iColCount == $this->conf['displayBasketColumns']) {
						if ($t['itemFrameWork'])	{
							// complete the last table row
							$itemsOut .= $this->finishHTMLRow($iColCount, $tableRowOpen);
							$itemListOut .= $this->pibase->cObj->substituteSubpart($t['itemFrameWork'],'###ITEM_SINGLE###',$itemsOut,0);;
							$itemsOut = '';
						}
						$iColCount = 0; // restart in the first column
					}
					$nextRow = $itemArray[$iCount];
					$nextP = (($pageAsCategory < 2) ? $nextRow['category'] : $nextRow['pid']);
					// multiple columns display and ITEM_SINGLE_POST_HTML is in the item's template?
					if (
							$nextP != $currentP &&
							$itemsOut &&
							$bItemPostHtml
						) {
						// complete the last table row
						$itemsOut .= $this->finishHTMLRow($iColCount, $tableRowOpen);
						if ($t['itemFrameWork'])	{
							$itemListOut .= $this->pibase->cObj->substituteSubpart($t['itemFrameWork'],'###ITEM_SINGLE###',$itemsOut,0);;
							$itemsOut = '';
						}
					}
	
				}	// foreach ($itemArray as $k1 => $productList) {
			} else {
				$content = '';  // TODO: keine Produkte gefunden
			}
//			if ($t['itemFrameWork'])
//				$out=$this->pibase->cObj->substituteSubpart($t['itemFrameWork'],'###ITEM_SINGLE###',$out,0);
			
			if ($itemListOut || $categoryOut)	{
				$out .= $this->advanceCategory($t['categoryAndItemsFrameWork'], $itemListOut, $categoryOut);
			}
		}	// if ($where ...
		if ($out)	{
			// next / prev:
			// $url = $this->getLinkUrl('','begin_at');
				// Reset:
			$subpartArray=array();
			$wrappedSubpartArray=array();
			$markerArray=array();
			$splitMark = md5(microtime());

			$addQueryString=array();
			if ($cat)
				$addQueryString['cat'] = $cat;

			if ($more)	{
				$next = ($begin_at+$this->config['limit'] > $productsCount) ? $productsCount-$this->config['limit'] : $begin_at+$this->config['limit'];
				// $addQueryString=array();
				// $addQueryString[$this->pibase->prefixId.'[begin_at]']= $next;
				// $tempUrl = $this->pibase->pi_linkToPage($splitMark,$TSFE->id,'',$this->marker->getLinkParams('', $addQueryString));
				$addQueryString['begin_at'] = $next;
				$tempUrl = $this->pibase->pi_linkTP_keepPIvars($splitMark,$addQueryString,1,0);
				$wrappedSubpartArray['###LINK_NEXT###'] =  explode ($splitMark, $tempUrl);  // array('<a href="'.$url.'&begin_at='.$next.'">','</a>');
			} else {
				$subpartArray['###LINK_NEXT###']='';
			}
			$bUseCache = count($this->basket->itemArray)>0;
			if ($begin_at)	{
				$prev = ($begin_at-$this->config['limit'] < 0) ? 0 : $begin_at-$this->config['limit'];
				// $addQueryString=array();
				// $addQueryString[$this->pibase->prefixId.'[begin_at]']= $prev;
				// $tempUrl = $this->pibase->pi_linkToPage($splitMark,$TSFE->id,'',$this->marker->getLinkParams('', $addQueryString));
				$addQueryString['begin_at'] = $prev;
				$tempUrl = $this->pibase->pi_linkTP_keepPIvars($splitMark,$addQueryString,$bUseCache,0);
				$wrappedSubpartArray['###LINK_PREV###']=explode ($splitMark, $tempUrl); // array('<a href="'.$url.'&begin_at='.$prev.'">','</a>');
			} else {
				$subpartArray['###LINK_PREV###']='';
			}
			$markerArray['###BROWSE_LINKS###']='';
			if ($productsCount > $this->config['limit'] )	{ // there is more than one page, so let's browse
				$wrappedSubpartArray['###LINK_BROWSE###']=array('',''); // <- this could be done better I think, or not?
				for ($i = 0 ; $i < ($productsCount/$this->config['limit']); $i++)	 {
					if (($begin_at >= $i*$this->config['limit']) && ($begin_at < $i*$this->config['limit']+$this->config['limit']))	{
						$markerArray['###BROWSE_LINKS###'].= ' <b>'.(string)($i+1).'</b> ';
						//	you may use this if you want to link to the current page also
						//
					} else {
						// $addQueryString=array();
						// $addQueryString[$this->pibase->prefixId.'[begin_at]']= (string)($i * $this->config['limit']);
						// $tempUrl = $this->pibase->pi_linkToPage((string)($i+1).' ',$TSFE->id,'',$this->marker->getLinkParams('', $addQueryString));
						$addQueryString['begin_at'] = (string)($i * $this->config['limit']);
						$tempUrl = $this->pibase->pi_linkTP_keepPIvars((string)($i+1).' ',$addQueryString,$bUseCache,0);
						$markerArray['###BROWSE_LINKS###'].= $tempUrl; // ' <a href="'.$url.'&begin_at='.(string)($i * $this->config['limit']).'">'.(string)($i+1).'</a> ';
					}
				}
			} else {
				$subpartArray['###LINK_BROWSE###']='';
			}

			$subpartArray['###ITEM_CATEGORY_AND_ITEMS###']=$out;
			// $markerArray['###FORM_URL###']=$this->formUrl;	  // Applied it here also...
			$markerArray = $this->marker->addURLMarkers($this->pid,$markerArray); //Applied it here also...
			$markerArray['###AMOUNT_CREDITPOINTS###'] = number_format($TSFE->fe_user->user['tt_products_creditpoints'],0);
			$markerArray['###ITEMS_SELECT_COUNT###']=$productsCount;
			$out = $this->pibase->cObj->substituteMarkerArrayCached($t['listFrameWork'],$markerArray,$subpartArray,$wrappedSubpartArray);
			$content .= $out;
		} elseif ($where)	{
			$content .= $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker('###ITEM_SEARCH_EMPTY###'));
		} // if ($out)	

		return $content;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_list_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_list_view.php']);
}

?>
