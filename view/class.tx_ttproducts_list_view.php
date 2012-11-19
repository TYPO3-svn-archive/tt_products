<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * @author	René Fritz <r.fritz@colorcube.de>
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
	var $tx_dam; // element of class tx_table_db
	var $tx_dam_cat; // element of class tx_table_db
	var $fe_users; // element of class tx_table_db
	var $pid; // pid where to go
	var $marker; // marker functions
	var $LLkey; // language key
	var $useArticles;
	var $searchFieldList='';
	var $uidArray;


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
		$pid,
		$LLkey,
		$useArticles,
		$uidArray
	) {
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;
		$this->basket = &$basket;
		$this->page = &$page;
		$this->tt_content = &$tt_content;
		$this->tt_products = &$tt_products;
		$this->tt_products_articles = &$tt_products_articles;
		$this->tt_products_cat = &$tt_products_cat;
		$this->tx_dam = $tx_dam;
		$this->tx_dam_cat = $tx_dam_cat;
		$this->fe_users = &$fe_users;
		$this->pid = $pid;
		$this->LLkey = $LLkey;
		$this->useArticles = $useArticles;
		$this->uidArray = $uidArray;
		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($pibase, $cnf, $basket);
		$this->javaScriptMarker = t3lib_div::makeInstance('tx_ttproducts_javascript_marker');
		$this->javaScriptMarker->init($pibase, $cnf, $this->pibase->javascript);

			//extend standard search fields with user setup
		$this->searchFieldList = trim($this->conf['stdSearchFieldExt']) ? implode(',', array_unique(t3lib_div::trimExplode(',',$this->searchFieldList.','.trim($this->conf['stdSearchFieldExt']),1))) : 'title,note,'.$this->tt_products->fields['itemnumber'];
	}


	function finishHTMLRow (&$cssConf, $iColCount, $tableRowOpen, $displayColumns)  {
		$itemsOut = '';
		if ($tableRowOpen)	{
			$iColCount++;
			$itemSingleWrapArray = t3lib_div::trimExplode('|', $cssConf['itemSingleWrap']);
			$bIsTable = (strpos($itemSingleWrapArray[0], 'td') != FALSE);
			if ($bIsTable)	{
				// fill up with empty fields
				while ($iColCount <= $displayColumns) {
					$itemsOut .= $itemSingleWrapArray[0].$itemSingleWrapArray[1];
					$iColCount++;
				}
			}
			$itemRowWrapArray = t3lib_div::trimExplode('|', $cssConf['itemRowWrap']);
			$itemsOut.= ($tableRowOpen ? $itemRowWrapArray[1] : '');
		}
		return $itemsOut;
	} // comp


	function &advanceCategory (&$categoryAndItemsFrameWork, &$itemListOut, &$categoryOut, $itemListSubpart, $oldFormCount, &$formCount)	{
		$subpartArray = array();
		$subpartArray['###ITEM_CATEGORY###'] = $categoryOut;
		$subpartArray[$itemListSubpart] = $itemListOut;
		$rc = $this->pibase->cObj->substituteMarkerArrayCached($categoryAndItemsFrameWork,array(),$subpartArray);
		if ($formCount == $oldFormCount) {
			$formCount++; // next form must have another name
		}
		$categoryOut = '';
		$itemListOut = '';			// Clear the item-code var
		return $rc;
	}


	function &advanceProduct (&$productAndItemsFrameWork, &$productFrameWork, &$itemListOut, &$productMarkerArray, &$categoryMarkerArray)	{
		$markerArray = array_merge($productMarkerArray, $categoryMarkerArray);
		$productOut = $this->pibase->cObj->substituteMarkerArray($productFrameWork,$markerArray);
		$subpartArray = array();
		$subpartArray['###ITEM_PRODUCT###'] = $productOut;
		$subpartArray['###ITEM_LIST###'] = $itemListOut;
		$rc = $this->pibase->cObj->substituteMarkerArrayCached($productAndItemsFrameWork,array(),$subpartArray);
		$categoryOut = '';
		$itemListOut = '';			// Clear the item-code var

		return $rc;
	}


	// returns the products list view
	function &printView (
		&$templateCode,
		$theCode,
		$allowedItems,
		$additionalPages,
		&$error_code,
		$templateArea = 'ITEM_LIST_TEMPLATE',
		$pageAsCategory,
		$mergeRow = array(),
		$calllevel = 0
	) {
		global $TSFE, $TCA, $TYPO3_DB;
		global $TYPO3_CONF_VARS;

		if (count($error_code))	{
			return '';
		}

		$bUseCache = TRUE;
		$content = '';
		$out = '';
		$childCatArray = array();
		$rootArray = array();
		$jsMarkerArray = array();
		$childCatWrap = '';
		$imageWrap = '';
		$linkCat = '';
		$depth = 1;	// TODO
		$displayColumns = $this->conf['displayBasketColumns'];
		$sword = '';
		$htmlSwords = '';

		$viewControlConf = $this->cnf->getViewControlConf($theCode);

		if (count($viewControlConf))	{
			if (isset($viewControlConf['param.']) && is_array($viewControlConf['param.']))	{
				$viewParamConf = $viewControlConf['param.'];
			}
		}
		$bUseBackPid = (isset($viewParamConf) && $viewParamConf['use'] == 'backPID' ? TRUE : FALSE);

		if ($calllevel == 0)	{
			$sword = t3lib_div::_GP('sword');
			$sword = (isset($sword) ? $sword : t3lib_div::_GP('swords'));

			if (!isset($sword))	{
				$sword = $this->pibase->piVars['sword'];
			}
			$sword = rawurldecode($sword);
			$htmlSwords = htmlspecialchars($sword);
		}

		$more = 0;		// If set during this loop, the next-item is drawn
		$where = '';
		$whereCat = '';
		$formName = 'ShopListForm';
		$itemTable = &$this->tt_products;
		if ($theCode == 'LISTARTICLES' && $this->useArticles)	{
			$itemTable = &$this->tt_products_articles;
		} else if ($theCode == 'LISTDAM')	{
			$itemTable = &$this->tx_dam;
		}

		$cssConf = $this->cnf->getCSSConf($itemTable->conftablename, $theCode);
		if (!$pageAsCategory || $pageAsCategory == 1)	{
			if ($theCode == 'LISTDAM')	{
				$categoryTable = &$this->tx_dam_cat;
			} else {
				$categoryTable = &$this->tt_products_cat;
			}
		} else {
			$categoryTable = &$this->page;
		}

		$catconf = $this->cnf->getTableConf($categoryTable->conftablename, $theCode);
		$whereArray = $this->pibase->piVars['tt_products'];
		if (is_array($whereArray))	{
			foreach ($whereArray as $field => $value)	{
				$where .= ' AND '.$field.'='.$TYPO3_DB->fullQuoteStr($value, $itemTable->table->name);
			}
		}

		$productsConf = $this->cnf->getTableConf($itemTable->conftablename,$theCode);
		// if parameter 'newitemdays' is specified, only new items from the last X days are displayed
		$newitemdays = $this->pibase->piVars['newitemdays'];
		$newitemdays = ($newitemdays ? $newitemdays : t3lib_div::_GP('newitemdays'));
		if (($newitemdays || $theCode == 'LISTNEWITEMS') && is_array($productsConf) && is_array($productsConf['controlFields.'])) {
			if (!$newitemdays)	{
				$newitemdays = $this->conf['newItemDays'];
			}
			$temptime = time() - 86400*intval(trim($newitemdays));
			$timeFieldArray = t3lib_div::trimExplode (',', $productsConf['controlFields.']['newItemDays']);
			$whereTimeFieldArray = array();
			foreach ($timeFieldArray as $k => $value)	{
				$whereTimeFieldArray[] = $value.' >= '.$temptime;
			}
			if (count ($whereTimeFieldArray))	{
				$where .= ' AND ('. implode(' OR ', $whereTimeFieldArray). ')';
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'] != '2') {
			$cat = $categoryTable->getParamDefault($theCode, $this->pibase->piVars[$categoryTable->piVar]);
		}
		$pid = $this->page->getParamDefault($theCode, $this->pibase->piVars[$this->page->piVar]);

		if ($itemTable->type == 'product')	{
			$address = $this->pibase->piVars['address'];
		}

		$addrTablename = $this->conf['table.']['address'];

		if (
				(
					$addrTablename == 'tx_party_addresses' && t3lib_extMgm::isLoaded(PARTY_EXTkey) ||
					$addrTablename == 'tx_partner_main' && t3lib_extMgm::isLoaded(PARTNER_EXTkey) ||
					$addrTablename == 'tt_address' && t3lib_extMgm::isLoaded(TT_ADDRESS_EXTkey)
				)
				&& $address && $itemTable->fields['address']
		)	{
			$whereAddress = ' AND ('.$itemTable->fields['address'].'='.intval($address);
			include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_address.php');
			$addressObj = t3lib_div::makeInstance('tx_ttproducts_address');
			$addressObj->init(
				$this,
				$this->cnf,
				$this->conf['table.']['address']
			);
			$addressRow = $addressObj->get($address);
			$addressText = $addressRow[$addressObj->fields['name']];
			$whereAddress .= ' OR '.$itemTable->fields['address'].'='.$TYPO3_DB->fullQuoteStr($addressText,$address->table->name).')';
			$where .= $whereAddress;
		}

		if ($whereAddress == '') {	// do not mix address with category filter
			if (isset($productsConf['filter.']) && is_array($productsConf['filter.']) &&
				isset($productsConf['filter.']['param.']) && is_array($productsConf['filter.']['param.']) &&
				$productsConf['filter.']['param.']['cat'] == 'gp')	{
				$bForceCatParams = TRUE;
			}

			if ($allowedItems == '' || $bForceCatParams)	{
				$whereCat = $itemTable->addWhereCat($cat, $this->page->pid_list);
			}

			if ($whereCat == '' && ($allowedItems == '' || $bForceCatParams))	{
				$neededParams = $itemTable->getNeededUrlParams($theCode);
				$needArray = t3lib_div::trimExplode(',', $neededParams);
				$bListStartEmpty = FALSE;
				foreach ($needArray as $k => $param)	{
					if ($param && !isset($this->pibase->piVars[$param]))	{
						$bListStartEmpty = TRUE;
						break;
					}
				}
				if ($bListStartEmpty)	{
					$allowedItems = '0';	// not possible uid
				}
			}
			$where .= $whereCat;
		}

		if (is_array($this->conf['form.'][$theCode.'.']) && is_array($this->conf['form.'][$theCode.'.']['data.']))	{
			$tmp = $this->conf['form.'][$theCode.'.']['data.']['name'];
		}
		$formName = ($tmp ? $tmp : $formName);

		if ($allowedItems || $allowedItems == '0')	{
			$allowedItemArray = array();
			$tempArray = t3lib_div::trimExplode(',',$allowedItems);

			$dblangfile = 'locallang_db.xml';
			$allowedItemArray = $TYPO3_DB->fullQuoteArray($tempArray,$itemTable->table->name);

			$where .= ' AND uid IN ('.implode(',',$allowedItemArray).')';
		}

		if ($htmlSwords)	{
			$where .= $this->tt_products->searchWhere($this->searchFieldList, trim($htmlSwords));
		}

		switch ($theCode) {
			case 'SEARCH':
				$formName = 'ShopSearchForm';
					// Get search subpart
				$t['search'] = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker('###ITEM_SEARCH###'));
					// Substitute a few markers
				$out = $t['search'];
				$tmpPid = ($this->conf['PIDsearch'] ? $this->conf['PIDsearch'] : $TSFE->id);
				$addQueryString=array();
				$this->marker->getSearchParams($addQueryString);

				$markerArray = $this->marker->addURLMarkers($tmpPid,array(),$addQueryString,'sword');
				$markerArray['###FORM_NAME###'] = $formName;
				$markerArray['###SWORD###'] = $htmlSwords;
				$markerArray['###SWORD_NAME###'] = 'sword';
				$markerArray['###SWORDS###'] = $htmlSwords; // for backwards compatibility
				$out = $this->pibase->cObj->substituteMarkerArrayCached($out, $markerArray);

				if ($formName)	{
						// Add to content
					$content .= $out;
				}
				$out = '';
				$bUseCache = FALSE;
			break;
			case 'LISTDAM':
				$formName = 'ListDAMForm';
				$templateArea = 'ITEM_LISTDAM_TEMPLATE';
				$currentCat = $categoryTable->getParamDefault();
				$rootCat = $this->conf['rootDAMCategoryID'];
				$relatedArray = $categoryTable->getRelated($rootCat, $currentCat, $this->page->pid_list);	// read only related categories;
				$excludeCat = 0;
				$categoryArray = $categoryTable->getRelationArray($excludeCat,$currentCat,$rootCat);
				$rootArray = $categoryTable->getRootArray($rootCat, $categoryArray);

				if ($this->conf['clickItemsIntoSubmenu'])	{
					$childCatArray = $categoryTable->getChildCategoryArray($currentCat);
					if (count($childCatArray))	{
						$templateArea = 'HAS_CHILDS_'.$templateArea;
					}
				}
			break;
			case 'LISTGIFTS':
				$formName = 'GiftForm';
				$where .= ' AND '.($this->conf['whereGift'] ? $this->conf['whereGift'] : '1=0');
				$templateArea = 'ITEM_LIST_GIFTS_TEMPLATE';
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
/*				$temptime = time() - 86400*intval(trim($this->conf['newItemDays']));
				$where .= ' AND crdate >= '.$temptime;*/
			break;
			case 'LISTARTICLES':
				$formName = 'ListArticlesForm';
			break;
			case 'MEMO':
				$formName = 'ListMemoForm';
				$bUseCache = FALSE;
			break;
			default:
				// nothing here
			break;
		}

		if ($calllevel == 0)	{
			$begin_at = $this->pibase->piVars['begin_at'];
			$begin_at = ($begin_at ? $begin_at : t3lib_div::_GP('begin_at'));
		}

		$begin_at = (
			class_exists('t3lib_utility_Math') ?
			t3lib_utility_Math::forceIntegerInRange($begin_at, 0, 100000) :
			t3lib_div::intInRange($begin_at, 0, 100000)
		);


		if ($theCode == 'SINGLE')	{
			$begin_at = ''; // no page browser in single view for related products
		}

		if ($theCode != 'SEARCH' || ($this->conf['listViewOnSearch'] == '1' && $theCode == 'SEARCH' && $sword))	{
			$t['listFrameWork'] = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker('###'.$templateArea.'###'));
			if (!$t['listFrameWork']) {
				$error_code[0] = 'no_subtemplate';
				$error_code[1] = '###'.$templateArea.'###';
				$error_code[2] = $this->conf['templateFile'];
				return $content;
			}
			$addQueryString = $this->uidArray;
			$excludeList = ($theCode == 'SEARCH' ? 'sword' : '');
			$this->marker->getSearchParams($addQueryString);
			$markerArray = $this->marker->addURLMarkers($TSFE->id,$markerArray,$addQueryString,$excludeList);

			// $markerArray = array();  // $this->marker->addURLMarkers($TSFE->id,array());
			$wrappedSubpartArray = array();
			$this->marker->getWrappedSubpartArray($wrappedSubpartArray);
			$subPartArray = array();
			$this->fe_users->getWrappedSubpartArray($subPartArray, $wrappedSubpartArray, $itemTable->conftablename);
			$t['listFrameWork'] = $this->pibase->cObj->substituteMarkerArrayCached($t['listFrameWork'],$markerArray,$subPartArray,$wrappedSubpartArray);
			$t['categoryAndItemsFrameWork'] = $this->pibase->cObj->getSubpart($t['listFrameWork'],'###ITEM_CATEGORY_AND_ITEMS###');
			$t['categoryFrameWork'] = $this->pibase->cObj->getSubpart($t['categoryAndItemsFrameWork'],'###ITEM_CATEGORY###');

			if ($itemTable->type == 'article')	{
				$t['productAndItemsFrameWork'] = $this->pibase->cObj->getSubpart($t['listFrameWork'],'###ITEM_PRODUCT_AND_ITEMS###');
				$t['productFrameWork'] = $this->pibase->cObj->getSubpart($t['productAndItemsFrameWork'],'###ITEM_PRODUCT###');
			}
			$t['itemFrameWork'] = $this->pibase->cObj->getSubpart($t['categoryAndItemsFrameWork'],'###ITEM_LIST###');
			$t['item'] = $this->pibase->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');

			$dum = strstr($t['item'], 'ITEM_SINGLE_POST_HTML');
			$bItemPostHtml = (strstr($t['item'], 'ITEM_SINGLE_POST_HTML') != FALSE);

				// Get products count
			$selectConf = Array();
			$allowedPages = ($pid ? $pid : $this->page->pid_list);
			if ($additionalPages)	{
				$allowedPages .= ','.$additionalPages;
			}
			$selectConf['pidInList'] = $allowedPages;
			$wherestock = ($this->conf['showNotinStock'] || !is_array(($TCA[$itemTable->table->name]['columns']['inStock'])) ? '' : ' AND (inStock > 0) ');

			$whereNew = $wherestock.$where;
			$whereNew = $itemTable->table->transformWhere($whereNew);
			$selectConf['where'] = '1=1 '.$whereNew;
			$selectConf['from'] = $itemTable->table->getAdditionalTables();

				// performing query to count all products (we need to know it for browsing):
			$selectConf['selectFields'] = 'count(*)';
			$tablename = $itemTable->table->name;
			$queryParts = $itemTable->table->getQueryConf($this->pibase->cObj, $tablename, $selectConf, TRUE);

			$res = $itemTable->table->exec_SELECT_queryArray($queryParts);
			$row = $TYPO3_DB->sql_fetch_row($res);
			$productsCount = $row[0];

				// range check to current productsCount
			$begin_at_start = (($begin_at >= $productsCount) ? ($productsCount >= $this->config['limit'] ? $productsCount - $this->config['limit'] : $productsCount) : $begin_at);
			$begin_at = (
				class_exists('t3lib_utility_Math') ?
				t3lib_utility_Math::forceIntegerInRange($begin_at_start, 0) :
				t3lib_div::intInRange($begin_at_start, 0)
			);


			$displayColumnsConf = '';
			if ($productsConf['displayColumns.'])	{
				$displayColumnsConf = $productsConf['displayColumns.'];
				if (is_array($displayColumnsConf))	{
					$displayColumns = $displayColumnsConf['1'];
					ksort($displayColumnsConf,SORT_STRING);
				}
			}

			$selectConf['orderBy'] = $productsConf['orderBy'];

				// performing query for display:
			if (!$selectConf['orderBy'])	{
				$selectConf['orderBy'] = $this->conf['orderBy'];
			}
			$tmpArray = t3lib_div::trimExplode(',', $selectConf['orderBy']);
			$orderByProduct = $tmpArray[0];
			if ($whereCat == '')	{
				$orderByCat = $catconf['orderBy'];
			}

				// sorting by category not yet possible for articles
			if ($itemTable->type == 'article')	{ // ($itemTable === $this->tt_products_articles)
				$orderByCat = '';	// articles do not have a direct category
				$orderByArray = t3lib_div::trimExplode (',', $selectConf['orderBy']);
				$orderByArray = array_diff($orderByArray, array('category'));
				$selectConf['orderBy'] = implode (',', $orderByArray);
			}
			if ($itemTable->fields['itemnumber'])	{
				$selectConf['orderBy'] = str_replace ('itemnumber', $itemTable->fields['itemnumber'], $selectConf['orderBy']);
			}
			$selectConf['orderBy'] = $itemTable->table->transformOrderby($selectConf['orderBy']);
			$productMarkerFieldArray = array('BULKILY_WARNING' => 'bulkily',
				'PRODUCT_SPECIAL_PREP' => 'special_preparation',
				'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
				'LINK_DATASHEET' => 'datasheet');

			$markerFieldArray = array();
			if ($itemTable->type == 'product')	{
				$markerFieldArray = $productMarkerFieldArray;
			}
			$viewTagArray = array();
			$parentArray = array();

			$fieldsArray = $this->marker->getMarkerFields(
				$t['item'],
				$itemTable->table->tableFieldArray,
				$itemTable->table->requiredFieldArray,
				$markerFieldArray,
				$itemTable->marker,
				$viewTagArray,
				$parentArray
			);

			if ($itemTable->type == 'article')	{
				$viewProductsTagArray = array();
				$productsParentArray = array();
				$tmpFramework = ($t['productAndItemsFrameWork'] ? $t['productAndItemsFrameWork'] : $t['categoryAndItemsFrameWork']);
				$productsFieldsArray = $this->marker->getMarkerFields(
					$tmpFramework,
					$this->tt_products->table->tableFieldArray,
					$this->tt_products->table->requiredFieldArray,
					$productMarkerFieldArray,
					$this->tt_products->marker,
					$viewProductsTagArray,
					$productsParentArray
				);
			}
			$itemTableConf = $this->cnf->getTableConf($itemTable->table->name, $theCode);
			$itemTableLangFields = $this->cnf->getTranslationFields($itemTableConf);
			$fieldsArray = array_merge($fieldsArray, $itemTableLangFields);
			$itemImageFields = $this->cnf->getImageFields($itemTableConf);
			$fieldsArray = array_merge($fieldsArray, $itemImageFields);
			$viewCatTagArray = array();
			$catParentArray = array();
			$catFramework = '';

			$catfieldsArray = $this->marker->getMarkerFields(
				$t['categoryAndItemsFrameWork'], // categoryAndItemsFrameWork  categoryFrameWork
				$categoryTable->table->tableFieldArray,
				$categoryTable->table->requiredFieldArray,
				$tmp = array(),
				$categoryTable->marker,
				$viewCatTagArray,
				$catParentArray
			);
			$catTitle = '';

			$catTableConf = $this->cnf->getTableConf($categoryTable->table->name, $theCode);
			if ($orderByCat && ($pageAsCategory < 2))	{ // && $catTableConf['language.']['type'] == 'table'
				// $catFields = ($orderByCat == 'uid' ? $orderByCat : 'uid,'.$orderByCat);
				$selectConf['orderBy'] = $categoryTable->table->transformOrderby($orderByCat).
					($selectConf['orderBy'] ? ','. $selectConf['orderBy'] : '');

				$prodAlias = $itemTable->table->getAlias();
				$catAlias = $categoryTable->table->getAlias();

				// SELECT *
				// FROM tt_products
				// LEFT OUTER JOIN tt_products_cat ON tt_products.category = tt_products_cat.uid
				$selectConf['leftjoin'] = $categoryTable->table->name.' '.$catAlias.' ON '.$catAlias.'.uid='.$prodAlias.'.category';
				$catTables = $categoryTable->table->getAdditionalTables();
				$selectConf['from'] = ($catTables ? $catTables.', '.$selectConf['from']:$selectConf['from']);
			}

			$selectFields = implode(',', $fieldsArray);
			$selectConf['selectFields'] = 'DISTINCT '.$itemTable->table->transformSelect($selectFields);
			$join = '';
			$tmpTables = $itemTable->table->transformTable('', FALSE, $join);
			// $selectConf['where'] = $join.$itemTable->table->transformWhere($selectConf['where']);
			$selectConf['where'] = $join.' '.$selectConf['where'];
			if (is_array($itemTableConf['filter.']) && $itemTableConf['filter.']['type'] == 'regexp')	{
				if (is_array($itemTableConf['filter.']['field.']))	{
					foreach ($itemTableConf['filter.']['field.'] as $field => $value)	{
						$selectConf['where'] .= ' AND '.$field.' REGEXP \''.$value.'\'';
					}
				}
			}
			$selectConf['max'] = ($this->config['limit']+1);
			$selectConf['begin'] = $begin_at;
			// $selectConf['from'] = ($selectConf['from'] ? $selectConf['from'].', ':'').$itemTable->table->getAdditionalTables();

			if ($selectConf['orderBy'])	{
				$selectConf['orderBy'] = $TYPO3_DB->stripOrderBy($selectConf['orderBy']);
			}
			$tablename = $itemTable->table->name;
			$queryParts = $itemTable->table->getQueryConf($this->pibase->cObj,$tablename, $selectConf, TRUE);

			$res = $itemTable->table->exec_SELECT_queryArray($queryParts);
			$itemArray=array();
			$iCount = 0;

			while ($iCount < $this->config['limit'] && ($row = $TYPO3_DB->sql_fetch_assoc($res)))		{

				$iCount++;
				if (count($itemTableLangFields))	{
					foreach ($itemTableLangFields as $field => $langfield)	{
						$row[$field] = $row[$langfield];
					}
				}
				$variantFieldArray = $itemTable->variant->getFieldArray();
				$itemTable->table->substituteMarkerArray($row,$variantFieldArray);
				$itemArray[] = $row;
			}

			if ($iCount == $this->config['limit'] && ($row = $TYPO3_DB->sql_fetch_assoc($res)))	{
				$more = 1;
			}

			if ($theCode == 'LISTGIFTS') {
				$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($this->basket, $markerArray, $this->giftnumber);
			}
			$addQueryString = array();
			$addQueryString = $this->uidArray;
			$this->marker->getSearchParams($addQueryString);

			$this->marker->addURLMarkers($TSFE->id,$markerArray,$addQueryString,'',FALSE);
			$markerArray['###FORM_NAME###'] = $formName; // needed if form starts e.g. between ###ITEM_LIST_TEMPLATE### and ###ITEM_CATEGORY_AND_ITEMS###

			$markerFramework = 'listFrameWork';
			$t[$markerFramework] = $this->pibase->cObj->substituteMarkerArrayCached($t[$markerFramework],$markerArray,array(),array());
			$this->pibase->javascript->set('email');
			$t['itemFrameWork'] = $this->pibase->cObj->substituteMarkerArrayCached($t['itemFrameWork'],$markerArray,array(),array());
			$currentArray = array();
			$currentArray['category'] = '';
			$currentArray['product'] = '';
			$nextArray = array();
			$nextArray['category'] = '';
			$nextArray['product'] = '';
			$productMarkerArray = array();
			$categoryMarkerArray = array();
			$out = '';
			$categoryAndItemsOut = '';
			$iCount = 0;
			$iColCount = 0;
			$productListOut = '';
			$itemsOut = '';
			$itemListOut = '';
			$categoryOut = '';
			$tableRowOpen = 0;
			$itemListSubpart = ($itemTable->type == 'article' && $t['productAndItemsFrameWork'] ? '###ITEM_PRODUCT_AND_ITEMS###' : '###ITEM_LIST###');
			$prodRow = array();
			$oldFormCount = 0;
			$formCount = 1;
			$bFormPerItem = FALSE;
			$itemLower = strtolower($t['item']);

			if (strstr($itemLower, '<form') !== FALSE)	{
				$bFormPerItem = TRUE;
			}
			$bUseDAM = FALSE;

			if (strstr($itemLower, '###dam_field_name###') !== FALSE)	{
				$bUseDAM = TRUE;
			}

			if (count($itemArray))	{
				$categoryMarkerArray = array();
				$itemRowWrapArray = t3lib_div::trimExplode('|', $cssConf['itemRowWrap']);

				foreach ($itemArray as $k2 => $row) {

					$iColCount++;
					$iCount++;

					$currentCat = $row['category'];
					$catArray = $categoryTable->getCategoryArray($row['uid']);
					if (count($catArray))	{
						$currentCat = current($catArray);

						if (in_array($cat, $catArray))	{
							$currentCat = $cat;
						}
						foreach($catArray as $k => $nextCat)	{

							if ($nextCat != $currentCat)	{
								break;
							}
						}
						$depth = 0;
						$rootLineArray = $categoryTable->getLineArray($currentCat, $rootArray);
						$bFound = FALSE;
						foreach ($rootLineArray as $k => $catVal)	{
							$depth++;

							if (in_array($catVal, $rootArray))	{
								$bFound = TRUE;
								break;
							}
						}
						if (!$bFound)	{
							$depth = 0;
						}

						$catLineArray = $categoryTable->getLineArray($nextCat, array(0 => $currentCat));
						$catLineArray = array_reverse($catLineArray);

						if (is_array($displayColumnsConf))	{
							foreach ($displayColumnsConf as $k => $val)	{
								if (
									(
										class_exists('t3lib_utility_Math') ? t3lib_utility_Math::canBeInterpretedAsInteger($k) :
										t3lib_div::testInt($k)
									) &&
									$depth >= $k
								)	{
									$displayColumns = $val;
								} else {
									break;
								}
							}

							if (isset($displayColumnsConf['last']) && !count($childCatArray))	{
								$displayColumns = $displayColumnsConf['last'];
							}
						}
						reset($catLineArray);

						if (count($childCatArray))	{
							$linkCat = next($catLineArray);

							if ($linkCat)	{
								$addQueryString = array($categoryTable->piVar => $linkCat);
								$tempUrl = htmlspecialchars($this->pibase->pi_linkTP_keepPIvars_url($addQueryString,1,1,$TSFE->id));
								$childCatWrap = '<a href="'. $tempUrl .'"'.$css.'> | </a>';
								$imageWrap = FALSE;
							}
						}
					}

						// print category title
					if	(
							($pageAsCategory < 2) && ( $currentCat != $currentArray['category']) ||
							($pageAsCategory == 2) && ($row['pid'] != $currentArray['category'])
						)	{

						$catItemsListOut = &$itemListOut;
						if ($itemTable->type == 'article' && $productListOut && $t['productAndItemsFrameWork'])	{
							$catItemsListOut = &$productListOut;
						}
						if ($catItemsListOut)	{
							$out .= $this->advanceCategory($t['categoryAndItemsFrameWork'], $catItemsListOut, $categoryOut, $itemListSubpart, $oldFormCount, $formCount);
						}
						$currentArray['category'] = (($pageAsCategory < 2 || $itemTable->type == 'dam') ? $currentCat : $row['pid']);
						$bCategoryHasChanged = TRUE;
						$iColCount = 1;
						$categoryMarkerArray = array();
						if ($where || $this->conf['displayListCatHeader'])	{
							$categoryTable->getMarkerArray (
								$categoryMarkerArray,
								$this->page,
								$currentCat,
								$row['pid'],
								$this->config['limitImage'],
								'listcatImage',
								$viewCatTagArray,
								array(),
								$pageAsCategory,
								$theCode,
								$iCount,
								''
							);

							$catTitle = $categoryTable->getMarkerArrayCatTitle($categoryMarkerArray);
							$categoryTable->getParentMarkerArray (
								$catParentArray,
								$row,
								$categoryMarkerArray,
								$this->page,
								$currentCat,
								$row['pid'],
								$this->config['limitImage'],
								'listcatImage',
								$viewCatTagArray,
								array(),
								$pageAsCategory,
								$theCode,
								1,
								''
							);

							if ($t['categoryFrameWork'])	{
								$categoryOut = $this->pibase->cObj->substituteMarkerArray($t['categoryFrameWork'], $categoryMarkerArray);
							}
						}
					} else {
						$bCategoryHasChanged = FALSE;
					}

						// relevant only for article list
					if ($itemTable->type == 'article')	{
						if ($row['uid_product'] != $currentArray['product'])	{
							$productMarkerArray = array();
							// fetch new product if articles are listed
							$prodRow = $this->tt_products->get($row['uid_product']);
							$variant = $itemTable->variant->getVariantFromRow($prodRow);
							$item = $this->basket->getItem($prodRow, $variant);
							$this->tt_products->getItemMarkerArray ($item, $productMarkerArray, $catTitle, $this->basket->basketExt, $this->config['limitImage'],'listImage', $viewProductsTagArray, array(), $theCode, $iCount, '', $imageWrap, TRUE, TRUE);
							if ($itemListOut && $t['productAndItemsFrameWork'])	{
								$productListOut .= $this->advanceProduct($t['productAndItemsFrameWork'], $t['productFrameWork'], $itemListOut, $productMarkerArray, $categoryMarkerArray);
							}
						}
						$itemTable->mergeAttributeFields($row, $prodRow);
						$currentArray['product'] = $row['uid_product'];
					} else {
						$currentArray['product'] = $row['uid'];
					}
					$tmp = $cssConf['default'];
					$css_current = ($tmp ? $tmp : $this->conf['CSSListDefault']);	// only for backwards compatibility

					if ($row['uid'] == $this->pibase->tt_product_single[$itemTable->type]) {
						$tmp = $cssConf['current'];
						$css_current = ($tmp ? $tmp : $this->conf['CSSListCurrent']);
					}
					$css_current = ($css_current ? '" id="'.$css_current.'"' : '');

						// Print Item Title
					$wrappedSubpartArray=array();
					$addQueryString=array();
					$pid = $this->page->getPID($this->conf['PIDitemDisplay'], $this->conf['PIDitemDisplay.'], $row);

					$addQueryString[$itemTable->piVar] = intval($row['uid']);
					$piVarCat = $this->pibase->piVars[$categoryTable->piVar];
					$bUseBackPid = $bUseBackPid && ($pid != $TSFE->id);

					if ($piVarCat)	{
						if ($this->conf['PIDlistDisplay'])	{
							$bUseBackPid = FALSE;
						}
						$cat = $piVarCat;
					}
					if ($cat)	{
						$addQueryString[$categoryTable->piVar] = $cat;
					}
					$this->marker->getSearchParams($addQueryString);
					$queryString = $this->marker->getLinkParams('begin_at', $addQueryString, FALSE, $bUseBackPid, $itemTable->piVar, $categoryTable->piVar);
					$pageLink = htmlspecialchars($this->pibase->pi_linkTP_keepPIvars_url($queryString,1,0,$pid));

					if ($childCatWrap)	{
						$wrappedSubpartArray['###LINK_ITEM###'] = t3lib_div::trimExplode('|',$childCatWrap);
					} else {
						$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'. $pageLink .'"'.$css_current.'>','</a>');
					}

					if (isset($row['datasheet']))	{
						$datasheetView = t3lib_div::getUserObj('&tx_ttproducts_field_datafield_view');
						$datasheetView->getItemSubpartArrays($productsConf, $row, $wrappedSubpartArray, $markerArray, $this->pibase->cObj);
					}
					if (count($mergeRow))	{
						$row = array_merge($row, $mergeRow);
					}

					$variant = $itemTable->variant->getVariantFromRow($row);
					$item = $this->basket->getItem($row, $variant);
					$markerArray = array();
					include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_basketitem_view.php');
					$basketItemView = t3lib_div::getUserObj('tx_ttproducts_basketitem_view');
					$basketItemView->init($this->pibase, $this->tt_products_cat, $this->basket->basketExt, $this->tx_dam, $this->tx_dam_cat);

					$basketItemView->getItemMarkerArray($itemTable, $item, $markerArray, $theCode, $iCount);

					$image = ($childCatWrap ? 'listImageHasChilds': 'listImage');
					if (is_array($categoryArray) && !isset($categoryArray[$currentCat]) && is_array($this->conf['listImageRoot.']))	{
						$image = 'listImageRoot';
					}
					$bSelect = ($itemTable->type == 'product' ? TRUE : FALSE);
					$itemTable->getItemMarkerArray($item, $markerArray, $catTitle, $this->basket->basketExt, $this->config['limitImage'], $image, $viewTagArray, array(), $theCode, $iCount, '', $imageWrap, $bSelect, TRUE);

					if ($itemTable->type == 'article')	{
						$productMarkerArray = array_merge ($productMarkerArray, $markerArray);
						$markerArray = array_merge ($productMarkerArray, $markerArray);
					}

					if ($linkCat)	{
						$linkCategoryMarkerArray = array();
						$categoryTable->getMarkerArray(
							$linkCategoryMarkerArray,
							$this->page,
							$linkCat,
							$row['pid'],
							$this->config['limitImage'],
							'listcatImage',
							$viewCatTagArray,
							array(),
							$pageAsCategory,
							$theCode,
							$iCount,
							''
						);
						$productMarkerArray = array_merge ($productMarkerArray, $linkCategoryMarkerArray);
					}
					$markerArray = array_merge ($productMarkerArray, $categoryMarkerArray, $markerArray);

					$jsMarkerArray = array_merge ($jsMarkerArray, $productMarkerArray);
					if ($theCode == 'LISTGIFTS') {
						$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($this->basket, $markerArray, $this->basket->giftnumber);
					}
					$subpartArray = array();

					$urlMarkerArray = $this->marker->addURLMarkers($TSFE->id,$markerArray,$addQueryString,$itemTable->type);
					$markerArray = array_merge($markerArray, $urlMarkerArray);
					$oldFormCount = $formCount;
					$markerArray['###FORM_NAME###'] = $formName . ($bFormPerItem ? $formCount : '');

					if ($bFormPerItem)	{
						$formCount++;
					}
					$markerArray['###ITEM_NAME###'] = 'item_'.$iCount;
					if (!$displayColumns)	{
						$markerArray['###FORM_NAME###'] = $markerArray['###ITEM_NAME###'];
					}
					if ($bUseDAM)	{
						$damUid = $this->uidArray['dam'];
						if ($damUid)	{
							$this->tx_dam->setFormMarkerArray($damUid, $markerArray);
						}
					}
					$markerArray['###FORM_ONSUBMIT###']='return checkParams(document.'.$markerArray['###FORM_NAME###'].');';
					$rowEven = $this->conf['CSS.'][$itemTable->table->name.'.']['row.']['even'];
					$rowEven = ($rowEven ? $rowEven : $this->conf['CSSRowEven']); // backwards compatible
					$rowUneven = $this->conf['CSS.'][$itemTable->table->name.'.']['row.']['uneven'];
					$rowUneven = ($rowUneven ? $rowUneven : $this->conf['CSSRowUneven']); // backwards compatible
					// alternating css-class eg. for different background-colors
					$evenUneven = (($iCount & 1) == 0 ? $rowEven : $rowUneven);

					$temp='';
					if ($iColCount == 1) {
						if ($evenUneven) {
							$temp = str_replace('###UNEVEN###', $evenUneven, $itemRowWrapArray[0]);
						} else {
							$temp = $itemRowWrapArray[0];
						}
						$tableRowOpen = 1;
					}

					$itemSingleWrapArray = t3lib_div::trimExplode('|', $cssConf['itemSingleWrap']);
					if ($itemSingleWrapArray[0]) {
						$temp .= str_replace('###UNEVEN###', $evenUneven, $itemSingleWrapArray[0]);
					}

					$markerArray['###ITEM_SINGLE_PRE_HTML###'] = $temp;

					$temp = $itemSingleWrapArray[1];

					if (!$displayColumns || $iColCount == $displayColumns) {
						$temp .= $itemRowWrapArray[1];
						$tableRowOpen = 0;
					}
					$markerArray['###ITEM_SINGLE_POST_HTML###'] = $temp;
					$pid = ( $this->conf['PIDmemo'] ? $this->conf['PIDmemo'] : $TSFE->id);
					$markerArray['###FORM_MEMO###'] = htmlspecialchars($this->pibase->pi_getPageLink($pid,'',$this->marker->getLinkParams('', array(), TRUE, TRUE, $itemTable->piVar))); //$this->getLinkUrl($this->conf['PIDmemo']);

					// cuts note in list view
					if (strlen($markerArray['###'.$itemTable->marker.'_NOTE###']) > $this->conf['max_note_length']) {
						$markerArray['###'.$itemTable->marker.'_NOTE###'] = substr(strip_tags($markerArray['###'.$itemTable->marker.'_NOTE###']), 0, $this->conf['max_note_length']) . '...';
					}
					if (strlen($markerArray['###'.$itemTable->marker.'_NOTE2###']) > $this->conf['max_note_length']) {
						$markerArray['###'.$itemTable->marker.'_NOTE2###'] = substr(strip_tags($markerArray['###'.$itemTable->marker.'_NOTE2###']), 0, $this->conf['max_note_length']) . '...';
					}
					if (is_object($itemTable->variant))	{
						$itemTable->variant->removeEmptyMarkerSubpartArray($markerArray,$subpartArray, $row, $this->conf);
					}

					if ($t['item'])	{
						$tempContent = $this->pibase->cObj->substituteMarkerArrayCached($t['item'], $markerArray, $subpartArray, $wrappedSubpartArray);

					}
					$itemsOut .= $tempContent;
	//				} // foreach ($productList as $k2 => $row)

					// max. number of columns reached?
					if (!$displayColumns || $iColCount == $displayColumns) {

						if ($t['itemFrameWork'])	{
							// complete the last table row
							$itemsOut .= $this->finishHTMLRow($cssConf, $iColCount, $tableRowOpen, $displayColumns);
							// $itemListOut .= $this->pibase->cObj->substituteSubpart($t['itemFrameWork'],'###ITEM_SINGLE###',$itemsOut,0);
							$subpartArray = array();
							$subpartArray['###ITEM_SINGLE###'] = $itemsOut;
							$itemListOut .= $this->pibase->cObj->substituteMarkerArrayCached($t['itemFrameWork'], $markerArray, $subpartArray, $wrappedSubpartArray);
							$itemsOut = '';
						}
						$iColCount = 0; // restart in the first column
					}
					$nextRow = $itemArray[$iCount];
					$nextCat = $nextRow['category'];
					$catArray = $categoryTable->getCategoryArray($nextRow['uid']);
					if (count($catArray))	{
						$nextCat = current($catArray);
						if (in_array($cat, $catArray))	{
							$nextCat = $cat;
						}
					}

					$nextArray['category'] = (($pageAsCategory < 2) ? $nextCat : $nextRow['pid']);
					if ($itemTable->type == 'article')	{
						$nextArray['product'] = $nextRow['uid_product'];
					} else {
						$nextArray['product'] = $nextRow['uid'];
					}

					// multiple columns display and ITEM_SINGLE_POST_HTML is in the item's template?
					if (
						$nextArray['category'] != $currentArray['category'] && $itemsOut ||
						$nextArray['product']  != $currentArray['product']  && $itemTable->type == 'article' && $t['productAndItemsFrameWork']
					) {

						if ($bItemPostHtml && (
							$nextArray['category']  !=  $currentArray['category'] && $itemsOut  || // && $t['categoryFrameWork'] != ''
							$nextArray['product']   !=  $currentArray['product']  && $itemTable->type == 'article' && $t['productAndItemsFrameWork']) )	{
							// complete the last table row
							$itemsOut .= $this->finishHTMLRow($cssConf, $iColCount, $tableRowOpen, $displayColumns);
						}

						if (($nextArray['category'] != $currentArray['category'] || !isset($nextArray['category'])) && $itemsOut && $t['itemFrameWork'])	{
							// $itemListOut .= $this->pibase->cObj->substituteSubpart($t['itemFrameWork'],'###ITEM_SINGLE###',$itemsOut,0);
							$markerArray = array_merge($productMarkerArray, $categoryMarkerArray, $markerArray);
							$subpartArray = array();
							$subpartArray['###ITEM_SINGLE###'] = $itemsOut;
							$itemListNewOut = $this->pibase->cObj->substituteMarkerArrayCached($t['itemFrameWork'], $markerArray, $subpartArray, $wrappedSubpartArray);
							$itemListOut .= $itemListNewOut;
							$itemsOut = '';
						}
					}
				}	// foreach ($itemArray as $k1 => $productList) {
			} else {
				$out = '';  // TODO: keine Produkte gefunden
			}

			if ($itemListOut || $categoryOut || $productListOut)	{
				$catItemsListOut = &$itemListOut;
				if ($itemTable->type == 'article' && $productListOut && $t['productAndItemsFrameWork'])	{
					$productListOut .= $this->advanceProduct($t['productAndItemsFrameWork'], $t['productFrameWork'], $itemListOut, $productMarkerArray, $categoryMarkerArray);
					$catItemsListOut = &$productListOut;
				}
				$out .= $this->advanceCategory($t['categoryAndItemsFrameWork'], $catItemsListOut, $categoryOut, $itemListSubpart, $oldFormCount, $formCount);
			}
		}	// if ($where ...

		if ($out)	{
				// Reset:
			$subpartArray=array();
			$wrappedSubpartArray=array();
			$markerArray=array();
			$splitMark=md5(microtime());

			$addQueryString=array();
			$addQueryString['addmemo'] = '';
			$addQueryString['delmemo'] = '';

			if ($cat)	{
				$addQueryString['cat'] = $cat;
			}
			if ($sword) 	{
				$addQueryString['sword'] = $sword;
			}
			$this->marker->getSearchParams($addQueryString);

// 			$backPID = $this->pibase->piVars['backPID'];
// 			$pid = ($backPID ? $backPID : $TSFE->id);

// 			$linkUrl = $this->pibase->pi_getPageLink($pid,'',$this->marker->getLinkParams('',$addQueryString,TRUE,TRUE,''));
//
// 			$linkUrl = htmlspecialchars($linkUrl);
// 			$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'. $linkUrl .'">','</a>',array('useCacheHash' => TRUE));

			if ($more)	{
				$next = ($begin_at+$this->config['limit'] > $productsCount) ? $productsCount-$this->config['limit'] : $begin_at+$this->config['limit'];
				$addQueryString['begin_at'] = $next;
				$this->marker->getSearchParams($addQueryString);
				$tempUrl = $this->pibase->pi_linkTP_keepPIvars($splitMark,$addQueryString,$bUseCache,0);
				$wrappedSubpartArray['###LINK_NEXT###'] = explode($splitMark, $tempUrl);
			} else {
				$subpartArray['###LINK_NEXT###']='';
			}
			if ($begin_at)	{
				$prev = ($begin_at-$this->config['limit'] < 0) ? 0 : $begin_at-$this->config['limit'];
				$addQueryString['begin_at'] = $prev;
				$this->marker->getSearchParams($addQueryString);
				$tempUrl = $this->pibase->pi_linkTP_keepPIvars($splitMark,$addQueryString,$bUseCache,0);
				$wrappedSubpartArray['###LINK_PREV###']=explode ($splitMark, $tempUrl);
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
						$addQueryString['begin_at'] = (string)($i * $this->config['limit']);
						$tempUrl = $this->pibase->pi_linkTP_keepPIvars((string)($i+1).' ',$addQueryString,$bUseCache,0);
						$markerArray['###BROWSE_LINKS###'] .= $tempUrl;
					}
				}
			} else {
				$subpartArray['###LINK_BROWSE###']='';
			}
			$subpartArray['###ITEM_CATEGORY_AND_ITEMS###'] = $out;
			// $markerArray['###FORM_URL###']=$this->formUrl;	  // Applied it here also...

			$urlMarkerArray = $this->marker->addURLMarkers($TSFE->id,$markerArray,$addQueryString,$itemTable->type);
			$markerArray = array_merge ($markerArray, $urlMarkerArray);
			$markerArray['###AMOUNT_CREDITPOINTS###'] = number_format($TSFE->fe_user->user['tt_products_creditpoints'],0);
			$markerArray['###ITEMS_SELECT_COUNT###'] = $productsCount;
 			$this->javaScriptMarker->getMarkerArray($jsMarkerArray, $markerArray);
 			$markerArray = array_merge ($jsMarkerArray, $markerArray);
			$markerArray['###HIDDEN_FIELDS###'] = $hiddenText; // TODO

			$out = $this->pibase->cObj->substituteMarkerArrayCached($t['listFrameWork'],$markerArray,$subpartArray,$wrappedSubpartArray);

			$content .= $out;
		} elseif ($sword && $allowedItems!='0' && $theCode=='SEARCH' && $this->conf['listViewOnSearch'] == '1')	{
			$content .= $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker('###ITEM_SEARCH_EMPTY###'));
		} // if ($out)

		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_list_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_list_view.php']);
}

?>
