<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Part of the tt_products (Shopping System) extension.
 *
 * product list view functions
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

// require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_javascript_marker.php');
// require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_subpartmarker.php');
// require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_url_view.php');


class tx_ttproducts_list_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $tablesObj;
	var $pid; // pid where to go
	var $urlObj; // url functions
	var $bUseArtcles;
	var $javaScriptMarker;
	var $searchFieldList='';
	var $uidArray;
	var $pidListObj;


	function init (
		$pibase, $pid, $bUseArticles, $uidArray, $pid_list, $recursive
	) {
		$this->pibase = $pibase;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->pid = $pid;
		$this->bUseArticles = $bUseArticles;
		$this->uidArray = $uidArray;
		$this->urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view');

		$this->javaScriptMarker = t3lib_div::makeInstance('tx_ttproducts_javascript_marker');
		$this->javaScriptMarker->init($pibase);
		$this->pidListObj = t3lib_div::getUserObj('tx_ttproducts_pid_list');
		$this->pidListObj->init($pibase->cObj);
		$this->pidListObj->applyRecursive($recursive, $pid_list, TRUE);
		$this->pidListObj->setPageArray();
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');

			//extend standard search fields with user setup
		$this->searchFieldList = trim($this->conf['stdSearchFieldExt']) ? implode(',', array_unique(t3lib_div::trimExplode(',',$this->searchFieldList.','.trim($this->conf['stdSearchFieldExt']),1))) : 'title,note,'.$tablesObj->get('tt_products')->fields['itemnumber'];
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$cssConf: ...
	 * @param	[type]		$iColCount: ...
	 * @param	[type]		$tableRowOpen: ...
	 * @param	[type]		$displayColumns: ...
	 * @return	[type]		...
	 */
	function finishHTMLRow (&$cssConf, &$iColCount, $tableRowOpen, $displayColumns)  {
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
		$iColCount = 0;
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
		$itemListOut = '';	// Clear the item-code var
		return $rc;
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$productAndItemsFrameWork: ...
	 * @param	[type]		$productFrameWork: ...
	 * @param	[type]		$itemListOut: ...
	 * @param	[type]		$productMarkerArray: ...
	 * @param	[type]		$categoryMarkerArray: ...
	 * @return	[type]		...
	 */
	function &advanceProduct (&$productAndItemsFrameWork, &$productFrameWork, &$itemListOut, &$productMarkerArray, &$categoryMarkerArray)	{
		$markerArray = array_merge($productMarkerArray, $categoryMarkerArray);
		$productOut = $this->pibase->cObj->substituteMarkerArray($productFrameWork,$markerArray);
		$subpartArray = array();
		$subpartArray['###ITEM_PRODUCT###'] = $productOut;
		$subpartArray['###ITEM_LIST###'] = $itemListOut;
		$rc = $this->pibase->cObj->substituteMarkerArrayCached($productAndItemsFrameWork,array(),$subpartArray);
		$categoryOut = '';
		$itemListOut = '';	// Clear the item-code var

		return $rc;
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$queryString: ...
	 * @return	[type]		...
	 */
	function getSearchParams (&$queryString) {
		$sword = t3lib_div::_GP('sword');

		if (!isset($sword))	{
			$sword = t3lib_div::_GP('swords');
		}

		if ($sword)	{
			$sword = rawurlencode($sword);
		}

		if (!isset($sword))	{
			$sword = $this->pibase->piVars['sword'];
		}

		if ($sword) {
			$queryString['sword'] = $sword;
		}
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$catObj: ...
	 * @param	[type]		$catArray: ...
	 * @param	[type]		$rootCatArray: ...
	 * @param	[type]		$rootLineArray: ...
	 * @param	[type]		$cat: ...
	 * @param	[type]		$currentCat: ...
	 * @param	[type]		$displayCat: ...
	 * @return	[type]		...
	 */
	function getCategories (&$catObj, $catArray, $rootCatArray, &$rootLineArray, $cat, &$currentCat, &$displayCat)	{
		if (in_array($cat, $catArray))	{
			$currentCat = $cat;
		} else {
			$currentCat = current($catArray);
		}

		foreach ($catArray as $displayCat)	{
			$totalRootLineArray = $catObj->getLineArray($currentCat, array(0));

			if (($displayCat != $currentCat) && !in_array($displayCat, $totalRootLineArray))	{
				break;
			}
		}
		$rootLineArray = $catObj->getLineArray($currentCat, $rootCatArray);
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$displayConf: ...
	 * @param	[type]		$type: ...
	 * @param	[type]		$depth: ...
	 * @param	[type]		$bLast: ...
	 * @return	[type]		...
	 */
	function getDisplayInfo ($displayConf, $type, $depth, $bLast)	{
		$rc = '';
		if (is_array($displayConf[$type]))	{
			foreach ($displayConf[$type] as $k => $val)	{
				if (
					tx_div2007_core::testInt($k) &&
					$depth >= $k
				) {
					$rc = $val;
				} else {
					break;
				}
			}

			if (isset($displayConf[$type]['last']) && $bLast)	{
				$rc = $displayConf[$type]['last'];
			}
		}
		return $rc;
	}


	// returns the products list view
	function &printView (
		$templateCode,
		$theCode,
		$functablename,
		$allowedItems,
		$additionalPages,
		&$error_code,
		$templateArea = 'ITEM_LIST_TEMPLATE',
		$pageAsCategory,
		$mergeRow = array(),
		$calllevel = 0
	) {
		global $TSFE, $TCA, $TYPO3_DB;

		if (count($error_code))	{
			return '';
		}
		$bUseCache = TRUE;

		$basketObj = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$subpartmarkerObj = t3lib_div::getUserObj('&tx_ttproducts_subpartmarker');

		$content = '';
		$out = '';
		$contentEmpty = '';
		$t = array();
		$childCatArray = array();
		$rootCatArray = array();
		$jsMarkerArray = array();
		$childCatWrap = '';
		$imageWrap = '';
		$linkCat = '';
		$depth = 1;	// TODO

		if ($this->conf['displayBasketColumns'] == '{$plugin.tt_products.displayBasketColumns}')	{
			$this->conf['displayBasketColumns'] = '1';
		}
		$displayColumns = $this->conf['displayBasketColumns'];
		$sword = '';
		$htmlSwords = '';

		if ($calllevel == 0)	{
			$sword = t3lib_div::_GP('sword');
			$sword = (isset($sword) ? $sword : t3lib_div::_GP('swords'));

			if (!isset($sword))	{
				$postVars = t3lib_div::_POST($this->pibase->prefixId);
				$sword = $postVars['sword'];

				if (!isset($sword))	{
					$getVars = t3lib_div::_GET($this->pibase->prefixId);
					$sword = $getVars['sword'];
				}
			}
			$sword = rawurldecode($sword);
			$htmlSwords = htmlspecialchars($sword);
		}

		$more = 0;	// If set during this loop, the next-item is drawn
		$where = '';
		$formName = 'ShopListForm';
		$itemTableView = $tablesObj->get($functablename, TRUE);
		$itemTable = $itemTableView->getModelObj();
		$tablename = $itemTable->getTablename();
		$tableConfArray = array();
		$tableConfArray[$functablename] = &$itemTable->getTableConf($theCode);
		$itemTable->initCodeConf($theCode,$tableConfArray[$functablename]);
		$prodAlias = $itemTable->getTableObj()->getAlias();

		$variantFieldArray = $itemTable->variant->getFieldArray();

		if ($itemTable->type == 'product' && $this->bUseArticles == 1) {
			$articleViewObj = $tablesObj->get('tt_products_articles', TRUE);
			$articleTable = $articleViewObj->getModelObj();
			$param = array($itemTable->getFuncTablename() => $variantFieldArray);
			$javaScriptObj = t3lib_div::getUserObj('&tx_ttproducts_javascript');
			if ($theCode != 'LISTRELATED' && t3lib_extMgm::isLoaded('taxajax')) {
				$javaScriptObj->set('fetchdata', $param);
			}
		}

		$cssConf = $cnf->getCSSConf($itemTable->getFuncTablename(), $theCode);

		if ($theCode == 'LISTDAM')	{
			$categoryfunctablename = 'tx_dam_cat';
		} else {
			if (!$pageAsCategory || $pageAsCategory == 1)	{
				$categoryfunctablename = 'tt_products_cat';
			} else {
				$categoryfunctablename = 'pages';
			}
		}
		$categoryTableView = $tablesObj->get($categoryfunctablename, TRUE);

		$categoryTable = $categoryTableView->getModelObj();
		$catconf = $cnf->getTableConf($categoryTable->getFuncTablename(), $theCode);
		$whereArray = $this->pibase->piVars['tt_products'];
		if (is_array($whereArray))	{
			foreach ($whereArray as $field => $value)	{
				$where .= ' AND '.$field.'='.$TYPO3_DB->fullQuoteStr($value, $itemTable->getTableObj()->name);
			}
		}
		$productsConf = $cnf->getTableConf($functablename,$theCode);

		if (!$productsConf['orderBy'] && $allowedItems != '') {
			$productsConf['orderBy'] = 'FIELD(' . $prodAlias . '.uid, ' . $allowedItems . ')';
		}
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

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] != '2')	{
			$cat = $categoryTable->getParamDefault($theCode, $this->pibase->piVars[$categoryTableView->piVar]);
		}
		$pageViewObj = $tablesObj->get('pages',1);
		$pid = $pageViewObj->getModelObj()->getParamDefault($theCode,  $this->pibase->piVars[$pageViewObj->piVar]);

		if ($itemTable->type == 'product')	{
			$address = $this->pibase->piVars['a'];
		}
		$hookVar = 'allowedItems';
		if ($hookVar && is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar] as $classRef) {
				$hookObj= t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this);
				}
				if (method_exists($hookObj, 'getAllowedItems')) {
					$tmpArray = $hookObj->getAllowedItems($allowedItems, $itemTable, $theCode, $additionalPages, $pageAsCategory);
				}
			}
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
			$addressViewObj = $tablesObj->get('address',1);
			$addressObj = $addressViewObj->getModelObj();
			$addressRow = $addressObj->get($address);
			$addressText = $addressRow[$addressObj->fields['name']];
			$whereAddress .= ' OR ' . $itemTable->fields['address'] . '=' . $TYPO3_DB->fullQuoteStr($addressText, $addressObj->getTableObj()->name) . ')';
			$where .= $whereAddress;
		}

		if ($whereAddress == '') {	// do not mix address with category filter
			if ($allowedItems == '')	{
				$whereCat = $itemTable->addWhereCat($categoryTable, $theCode, $cat, $this->pidListObj->getPidlist(),TRUE);
			}

			if ($whereCat == '' && $allowedItems == '')	{
				$neededParams = $itemTable->getNeededUrlParams($functablename, $theCode);
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
			$formNameSetup = $this->conf['form.'][$theCode.'.']['data.']['name'];
		}
		$formName = ($formNameSetup ? $formNameSetup : $formName);

		if ($allowedItems || $allowedItems == '0')	{
			$allowedItemArray = array();
			$tempArray = t3lib_div::trimExplode(',',$allowedItems);
			$allowedItemArray = $TYPO3_DB->cleanIntArray($tempArray);
			$where .= ' AND uid IN ('.implode(',',$allowedItemArray).')';
		}

		if ($htmlSwords && (in_array($theCode, array('LIST', 'SEARCH'))))	{
			$where .= $tablesObj->get('tt_products')->searchWhere($this->searchFieldList, trim($htmlSwords));
		}

		switch ($theCode) {
			case 'SEARCH':
				$formName = 'ShopSearchForm';
					// Get search subpart
				$t['search'] = $this->pibase->cObj->getSubpart($templateCode,$subpartmarkerObj->spMarker('###ITEM_SEARCH###'));
					// Substitute a few markers
				$out = $t['search'];
				$tmpPid = ($this->conf['PIDsearch'] ? $this->conf['PIDsearch'] : $TSFE->id);
				$addQueryString=array();
				$this->getSearchParams($addQueryString);
				$excludeList = 'sword';
				$markerArray = $this->urlObj->addURLMarkers($tmpPid,array(),$addQueryString,$excludeList,$bUseBackPid);

				$markerArray['###FORM_NAME###'] = $formName;
				$markerArray['###SWORD###'] = $htmlSwords;
				$markerArray['###SWORD_NAME###'] = $this->pibase->prefixId . '[sword]';
				$markerArray['###SWORDS###'] = $htmlSwords; // for backwards compatibility
				$out = $this->pibase->cObj->substituteMarkerArrayCached($out,$markerArray);

				if ($formName)	{
						// Add to content
					$content .= $out;
				}
				$out = '';
				// $entitySwords = htmlentities($sword); if the data has been entered e.g. with '&uuml;' instead of '&uuml;'
				$bUseCache = FALSE;
			break;
			case 'LISTDAM':
				$formName = 'ListDAMForm';
				$templateArea = 'ITEM_LISTDAM_TEMPLATE';
				$currentCat = $categoryTable->getParamDefault($theCode,  $this->pibase->piVars[$categoryTableView->piVar]);
				$rootCat = $this->conf['rootDAMCategoryID'];
				$relatedArray = $categoryTable->getRelated($rootCat, $currentCat, $this->pidListObj->getPidlist());	// read only related categories;
				$excludeCat = 0;
				$categoryArray = $categoryTable->getRelationArray($excludeCat, $rootCat,implode(',', array_keys($relatedArray)));
				$rootCatArray = $categoryTable->getRootArray($rootCat, $categoryArray);

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

// neu Anfang
		$limit = isset($tableConfArray[$functablename]['limit']) ? $tableConfArray[$functablename]['limit'] : $this->config['limit'];
		$limit = intval($limit);
// neu Ende

		if ($calllevel == 0)	{
			$begin_at = $this->pibase->piVars['begin_at'];
			$begin_at = ($begin_at ? $begin_at : t3lib_div::_GP('begin_at'));
		}
		$begin_at = tx_div2007_core::intInRange($begin_at, 0, 100000);

		if ($theCode == 'SINGLE')	{
			$begin_at = ''; // no page browser in single view for related products
		}

		if ($theCode != 'SEARCH' || ($this->conf['listViewOnSearch'] == '1' && $theCode == 'SEARCH' && $sword))	{
			$t['listFrameWork'] = $this->pibase->cObj->getSubpart($templateCode,$subpartmarkerObj->spMarker('###'.$templateArea.'###'));

			// $templateArea = 'ITEM_LIST_TEMPLATE'
			if (!$t['listFrameWork']) {
				$mainObj = t3lib_div::getUserObj('&tx_ttproducts_main');
				$error_code[0] = 'no_subtemplate';
				$error_code[1] = '###'.$templateArea.'###';
				$error_code[2] = $mainObj->templateFile;
				return $content;
			}

			$addQueryString = $this->uidArray;
			$excludeList = ($theCode == 'SEARCH' ? 'sword' : '');
			$this->getSearchParams($addQueryString);
			$markerArray = array();
			$markerArray = $this->urlObj->addURLMarkers($TSFE->id,$markerArray,$addQueryString,$excludeList,TRUE);

			$wrappedSubpartArray = array();
			$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
			$subpartArray = array();
			$tablesObj->get('fe_users',TRUE)->getWrappedSubpartArray($subpartArray, $wrappedSubpartArray, $itemTable->getFuncTablename());
			$t['listFrameWork'] = $this->pibase->cObj->substituteMarkerArrayCached(
				$t['listFrameWork'],
				$markerArray,
				$subpartArray,
				$wrappedSubpartArray
			);

			$t['categoryAndItemsFrameWork'] = $this->pibase->cObj->getSubpart($t['listFrameWork'],'###ITEM_CATEGORY_AND_ITEMS###');
			$t['categoryFrameWork'] = $this->pibase->cObj->getSubpart(
				$t['categoryAndItemsFrameWork'],
				'###ITEM_CATEGORY###'
			);
			if ($itemTable->type == 'article')	{
				$t['productAndItemsFrameWork'] = $this->pibase->cObj->getSubpart($t['listFrameWork'],'###ITEM_PRODUCT_AND_ITEMS###');
				$t['productFrameWork'] = $this->pibase->cObj->getSubpart($t['productAndItemsFrameWork'],'###ITEM_PRODUCT###');
			}
			$t['itemFrameWork'] = $this->pibase->cObj->getSubpart($t['categoryAndItemsFrameWork'],'###ITEM_LIST###');
			$t['item'] = $this->pibase->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');

			$dum = strstr($t['item'], 'ITEM_SINGLE_POST_HTML');
			$bItemPostHtml = (strstr($t['item'], 'ITEM_SINGLE_POST_HTML') != FALSE);

				// Get products count
			$selectConf = array();
			$allowedPages = ($pid ? $pid : $this->pidListObj->getPidlist());
			if ($additionalPages)	{
				$allowedPages .= ','.$additionalPages;
			}
			$selectConf['pidInList'] = $allowedPages;
			$wherestock = ($this->conf['showNotinStock'] || !is_array(($TCA[$tablename]['columns']['inStock'])) ? '' : ' AND (inStock > 0) ');
			$whereNew = $wherestock.$where;
			$whereNew = $itemTable->getTableObj()->transformWhere($whereNew);

			$selectConf['where'] = '1=1 '.$whereNew;
			$selectConf['from'] = $itemTable->getTableObj()->getAdditionalTables();

				// performing query to count all products (we need to know it for browsing):
			$selectConf['selectFields'] = 'count(*)';

			$queryParts = $itemTable->getTableObj()->getQueryConf($this->pibase->cObj, $tablename, $selectConf, TRUE);
			$res = $itemTable->getTableObj()->exec_SELECT_queryArray($queryParts);
			$row = $TYPO3_DB->sql_fetch_row($res);
			$TYPO3_DB->sql_free_result($res);
			$productsCount = $row[0];

				// range check to current productsCount

			$begin_at_start = (($begin_at >= $productsCount) ? ($productsCount >= $limit ? $productsCount - $limit : $productsCount) : $begin_at);
			$begin_at = tx_div2007_core::intInRange($begin_at_start, 0);

			$displayConf = array();
				// Get products count
			$displayConf['columns'] = '';
			if ($productsConf['displayColumns.'])	{
				$displayConf['columns'] = $productsConf['displayColumns.'];
				if (is_array($displayConf['columns']))	{
					$displayColumns = $displayConf['columns']['1'];
					ksort($displayConf['columns'],SORT_STRING);
				}
			}
			$displayConf['header'] = '';
			if ($productsConf['displayHeader.'])	{
				$displayConf['header'] = $productsConf['displayHeader.'];
				if (is_array($displayConf['header']))	{
					ksort($displayConf['header'],SORT_STRING);
				}
			}

			$selectConf['orderBy'] = $productsConf['orderBy'];
				// performing query for display:
			if (!$selectConf['orderBy'])	{
				$selectConf['orderBy'] = $this->conf['orderBy'];
			}
			$tmpArray = t3lib_div::trimExplode(',', $selectConf['orderBy']);
			$orderByProduct = $tmpArray[0];

			$orderByCat = $catconf['orderBy'];

				// sorting by category not yet possible for articles
			if ($itemTable->type == 'article')	{ // ($itemTable === $this->tt_products_articles)
				$orderByCat = '';	// articles do not have a direct category
				$orderByArray = t3lib_div::trimExplode(',', $selectConf['orderBy']);
				$orderByArray = array_diff($orderByArray, array('category'));
				$selectConf['orderBy'] = implode (',', $orderByArray);
			}
			if ($itemTable->fields['itemnumber'])	{
				$selectConf['orderBy'] = str_replace ('itemnumber', $itemTable->fields['itemnumber'], $selectConf['orderBy']);
			}
			$selectConf['orderBy'] = $itemTable->getTableObj()->transformOrderby($selectConf['orderBy']);
			$productMarkerFieldArray = array(
				'BULKILY_WARNING' => 'bulkily',
				'PRODUCT_SPECIAL_PREP' => 'special_preparation',
				'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
				'PRODUCT_LINK_DATASHEET' => 'datasheet'
			);

			$markerFieldArray = array();
			if ($itemTable->type == 'product')	{
				$markerFieldArray = $productMarkerFieldArray;
			}

			$viewTagArray = array();
			$parentArray = array();

			$fieldsArray = $markerObj->getMarkerFields(
				$t['item'],
				$itemTable->getTableObj()->tableFieldArray,
				$itemTable->getTableObj()->requiredFieldArray,
				$markerFieldArray,
				$itemTable->marker,
				$viewTagArray,
				$parentArray
			);

			if ($itemTable->type == 'product' && $this->bUseArticles == 1) {
				$markerFieldArray = array();
				$articleViewTagArray = array();
				$articleParentArray = array();
				$articleFieldsArray = $markerObj->getMarkerFields(
					$t['item'],
					$itemTable->getTableObj()->tableFieldArray,
					$itemTable->getTableObj()->requiredFieldArray,
					$markerFieldArray,
					$articleViewObj->marker,
					$articleViewTagArray,
					$articleParentArray
				);

				$prodUidField = $cnf->getTableDesc($articleTable->getTableObj()->name, 'uid_product');
				$fieldsArray = array_merge($fieldsArray, $articleFieldsArray);
				$uidKey = array_search($prodUidField, $fieldsArray);
				if ($uidKey != '')	{
					unset($fieldsArray[$uidKey]);
				}
			} else if ($itemTable->type == 'article') {
				$viewProductsTagArray = array();
				$productsParentArray = array();
				$tmpFramework = ($t['productAndItemsFrameWork'] ? $t['productAndItemsFrameWork'] : $t['categoryAndItemsFrameWork']);
				$productsFieldsArray = $markerObj->getMarkerFields(
					$tmpFramework,
					$tablesObj->get('tt_products')->getTableObj()->tableFieldArray,
					$tablesObj->get('tt_products')->getTableObj()->requiredFieldArray,
					$productMarkerFieldArray,
					$tablesObj->get('tt_products')->marker,
					$viewProductsTagArray,
					$productsParentArray
				);
			} else {
				$bCheckUnusedArticleMarkers = TRUE;
			}

			$itemTableConf = $cnf->getTableConf($itemTable->getFuncTablename(), $theCode);
			$itemTableLangFields = $cnf->getTranslationFields($itemTableConf);
			$fieldsArray = array_merge($fieldsArray, $itemTableLangFields);

			$itemImageFields = $cnf->getImageFields($itemTableConf);
			$fieldsArray = array_merge($fieldsArray, $itemImageFields);

			$columnFields = $cnf->getColumnFields($itemTableConf);
			if (isset($columnFields) && is_array($columnFields) && count($columnFields)) {
				foreach ($columnFields as $field => $value) {
					$key = array_search($field, $fieldsArray);
					if ($key !== FALSE) {
						unset($fieldsArray[$key]);
						$fieldsArray[] = str_replace($field, $prodAlias . '.' . $field, $value) . ' ' . $field;
					}
				}
			}

			$viewCatTagArray = array();
			$catParentArray = array();
			$catFramework = '';
			$catfieldsArray = $markerObj->getMarkerFields(
				$t['categoryAndItemsFrameWork'], // categoryAndItemsFrameWork  categoryFrameWork
				$categoryTable->getTableObj()->tableFieldArray,
				$categoryTable->getTableObj()->requiredFieldArray,
				$tmp = array(),
				$categoryTable->marker,
				$viewCatTagArray,
				$catParentArray
			);
			$catTitle = '';
			$catTableConf = $cnf->getTableConf($categoryTable->getTableObj()->getName(), $theCode);

			if ($orderByCat && ($pageAsCategory < 2))	{
				// $catFields = ($orderByCat == 'uid' ? $orderByCat : 'uid,'.$orderByCat);
				$catOrderBy = $categoryTable->getTableObj()->transformOrderby($orderByCat);
				$orderByCatFieldArray = t3lib_div::trimExplode(',',$catOrderBy);

				$selectConf['orderBy'] = $catOrderBy . ($selectConf['orderBy'] ? ($catOrderBy != '' ? ',' : '') . $selectConf['orderBy'] : '');
				$prodAlias = $itemTable->getTableObj()->getAlias();
				$catAlias = $categoryTable->getTableObj()->getAlias();
				// SELECT *
				// FROM tt_products
				// LEFT OUTER JOIN tt_products_cat ON tt_products.category = tt_products_cat.uid
				$selectConf['leftjoin'] = $categoryTable->getTableObj()->name.' '.$catAlias.' ON '.$catAlias.'.uid='.$prodAlias.'.category';
				$catTables = $categoryTable->getTableObj()->getAdditionalTables();

				if ($selectConf['from'] != '')	{
					$tmpDelim = ',';
				}
				$selectConf['from'] = ($catTables!='' ? $catTables.$tmpDelim.$selectConf['from'] : $selectConf['from']);

// 				foreach ($orderByCatFieldArray as $k => $catField)	{
// 					$catSelect .= ','.$catField.' '.str_replace ('.','_', $catField);
// 				}
			}
			$selectFields = implode(',', $fieldsArray);
			$selectConf['selectFields'] = 'DISTINCT '. $itemTable->getTableObj()->transformSelect($selectFields).$catSelect;
			$join = '';
			$tmpTables = $itemTable->getTableObj()->transformTable('', FALSE, $join);
			// $selectConf['where'] = $join.$itemTable->getTableObj()->transformWhere($selectConf['where']);
			$selectConf['where'] = $join.' '.$selectConf['where'];
			if (is_array($itemTableConf['filter.']) && is_array($itemTableConf['filter.']['regexp.']))	{
				if (is_array($itemTableConf['filter.']['regexp.']['field.']))	{
					foreach ($itemTableConf['filter.']['regexp.']['field.'] as $field => $value)	{
						$selectConf['where'] .= ' AND '.$field.' REGEXP \''.$value.'\'';
					}
				}
			}
			$selectConf['max'] = ($limit + 1);
			$selectConf['begin'] = $begin_at;
			// $selectConf['from'] = ($selectConf['from'] ? $selectConf['from'].', ':'').$itemTable->getTableObj()->getAdditionalTables();

			if ($selectConf['orderBy'])	{
				$selectConf['orderBy'] = $TYPO3_DB->stripOrderBy($selectConf['orderBy']);
			}

			if (isset($productsConf['groupBy'])) {
				$selectConf['groupBy'] = $productsConf['groupBy'];

				$selectConf['groupBy'] = $itemTable->getTableObj()->transformOrderby($selectConf['groupBy']);

				if ($selectConf['groupBy']) {
					$selectConf['groupBy'] = $TYPO3_DB->stripGroupBy($selectConf['groupBy']);
				}
			}

			$tablename = $itemTable->getTableObj()->name;
			$queryParts = $itemTable->getTableObj()->getQueryConf(
				$this->pibase->cObj,
				$tablename,
				$selectConf,
				TRUE
			);

			if ($queryParts === FALSE)	{
				return 'ERROR in tt_products';
			}
			$res = $itemTable->getTableObj()->exec_SELECT_queryArray($queryParts);
			$itemArray=array();
			$iCount = 0;
			$uidArray = array();

			while ($iCount < $limit && ($row = $TYPO3_DB->sql_fetch_assoc($res)))	{
				$iCount++;

				if (count($itemTableLangFields))	{
					foreach ($itemTableLangFields as $field => $langfield)	{
						$row[$field] = $row[$langfield];
					}
				}
				$itemTable->getTableObj()->substituteMarkerArray($row,$variantFieldArray);
				$itemArray[] = $row;
				$uidArray[] = $row['uid'];
			}

			if ($iCount == $limit && ($row = $TYPO3_DB->sql_fetch_assoc($res)))	{
				$more = 1;
			}

			$TYPO3_DB->sql_free_result($res);
			if ($theCode == 'LISTGIFTS') {
				$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($markerArray, $this->giftnumber);
				if (!isset($javaScriptObj)) {
					$javaScriptObj = t3lib_div::getUserObj('&tx_ttproducts_javascript');
				}
				$javaScriptObj->set('email');
			}

			$markerArray['###FORM_NAME###'] = $formName; // needed if form starts e.g. between ###ITEM_LIST_TEMPLATE### and ###ITEM_CATEGORY_AND_ITEMS###
			$markerFramework = 'listFrameWork';
			$t[$markerFramework] = $this->pibase->cObj->substituteMarkerArrayCached($t[$markerFramework],$markerArray,array(),array());
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
			$basketObj->getGraduatedPrices($uidArray);

			if (count($itemArray))	{	// $itemArray must have numbered indexes to work, because the next item must be determined
				$itemRowWrapArray = t3lib_div::trimExplode('|', $cssConf['itemRowWrap']);
				$categoryMarkerArray = array();

				foreach ($itemArray as $k2 => $row) {
					$iColCount++;
					$iCount++;
					if ($categoryTable->getFuncTablename() == 'tt_products_cat')	{
						$currentCat = $row['category'];
					}
					$catArray = $categoryTable->getCategoryArray($row['uid'], 'sorting');
					if (count($catArray))	{
						reset($catArray);
						$this->getCategories($categoryTable, $catArray, $rootCatArray, $rootLineArray, $cat, $currentCat, $displayCat);
						$depth = 0;

						$bFound = FALSE;
						foreach ($rootLineArray as $catVal)	{
							$depth++;
							if (in_array($catVal, $rootCatArray))	{
								$bFound = TRUE;
								break;
							}
						}
						if (!$bFound)	{
							$depth = 0;
						}

						$catLineArray = $categoryTable->getLineArray($displayCat, array(0 => $currentCat));
						$catLineArray = array_reverse($catLineArray);
						reset($catLineArray);

						$confDisplayColumns = $this->getDisplayInfo($displayConf, 'columns', $depth, !count($childCatArray));
						$displayColumns = (
							tx_div2007_core::testInt($confDisplayColumns) ?
								$confDisplayColumns : $displayColumns
						);

						if (count($childCatArray))	{
							$linkCat = next($catLineArray);
							if ($linkCat)	{
								$addQueryString = array($categoryTableView->getPivar() => $linkCat);
								$tempUrl = htmlspecialchars($this->pibase->pi_linkTP_keepPIvars_url($addQueryString,1,1,$TSFE->id));
								$childCatWrap = '<a href="'. htmlspecialchars($tempUrl) .'"'.$css.'> | </a>';
								$imageWrap = FALSE;
							}
						}
					} else {
						$displayCat = $currentCat;
					}
					$displayCatHeader = $this->getDisplayInfo($displayConf, 'header', $depth, !count($childCatArray));

					if ($displayCatHeader == 'current')	{
						$displayCat = $currentCat;
					}

						// print category title
					if	(
							($pageAsCategory < 2) && ($displayCat != $currentArray['category']) ||
							($pageAsCategory == 2) && ($row['pid'] != $currentArray['category']) ||
							$displayCatHeader == 'always'
						)	{
						$catItemsListOut = &$itemListOut;
						if ($itemTable->type == 'article' && $productListOut && $t['productAndItemsFrameWork'])	{
							$catItemsListOut = &$productListOut;
						}

						if ($catItemsListOut)	{
							$out .= $this->advanceCategory($t['categoryAndItemsFrameWork'], $catItemsListOut, $categoryOut, $itemListSubpart, $oldFormCount, $formCount);
						}
						$currentArray['category'] = (($pageAsCategory < 2 || $itemTable->type == 'dam') ? $displayCat : $row['pid']);
						$bCategoryHasChanged = TRUE;
						if ($displayCatHeader != 'always')	{
							$iColCount = 1;
						}
						$categoryMarkerArray = array();
						if ($where || $this->conf['displayListCatHeader'])	{

							$categoryTableView->getMarkerArray (
								$categoryMarkerArray,
								$displayCat,
								$row['pid'],
								$this->config['limitImage'],
								'listcatImage',
								$viewCatTagArray,
								$tmp = array(),
								$pageAsCategory,
								$theCode,
								$iCount,
								'',
								''
							);
							$catTitle = $categoryTableView->getMarkerArrayCatTitle($categoryMarkerArray);
							$categoryTableView->getParentMarkerArray (
								$catParentArray,
								$row,
								$categoryMarkerArray,
								$displayCat,
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
					$subpartArray = array();

						// relevant only for article list
					if ($itemTable->type == 'article')	{
						if ($row['uid_product'] && $row['uid_product'] != $currentArray['product'])	{
							$productMarkerArray = array();
							// fetch new product if articles are listed
							$prodRow = $tablesObj->get('tt_products')->get($row['uid_product']);
							// $variant = $itemTable->variant->getFirstVariantRow();
							$item = $basketObj->getItem($prodRow, 'firstVariant');
							$tablesObj->get('tt_products', TRUE)->getItemMarkerArray (
								$item,
								$productMarkerArray,
								$catTitle,
								$this->config['limitImage'],
								'listImage',
								$viewProductsTagArray,
								array(),
								$theCode,
								$iCount,
								'',
								$imageWrap,
								TRUE,
								$TSFE->renderCharset
							);

							$tablesObj->get('tt_products',TRUE)->getItemSubpartArrays (
								$t['item'],
								$row,
								$subpartArray,
								$wrappedSubpartArray,
								$viewProductsTagArray,
								$theCode,
								$iCount
							);

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
					$pagesObj = $tablesObj->get('pages');
					$pid = $pagesObj->getPID($this->conf['PIDitemDisplay'], $this->conf['PIDitemDisplay.'], $row);
					$addQueryString[$itemTableView->getPivar()] = intval($row['uid']);
					$piVarCat = $this->pibase->piVars[$categoryTableView->getPivar()];
					$bUseBackPid = ($pid != $TSFE->id);
					if ($piVarCat)	{
						if ($this->conf['PIDlistDisplay'])	{
							$bUseBackPid = FALSE;
						}
						$cat = $piVarCat;
					}
					if ($cat)	{
						$addQueryString[$categoryTableView->getPivar()] = $cat;
					}
					$queryString = $this->urlObj->getLinkParams('begin_at', $addQueryString, FALSE, $bUseBackPid, $itemTableView->getPivar(), $categoryTableView->getPivar());
					$pageLink = htmlspecialchars($this->pibase->pi_linkTP_keepPIvars_url($queryString,1,0,$pid));

					if ($childCatWrap)	{
						$wrappedSubpartArray['###LINK_ITEM###'] = t3lib_div::trimExplode('|',$childCatWrap);
					} else {
						$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'. $pageLink .'"'.$css_current.'>','</a>');
					}

					if (count($mergeRow))	{
						$row = array_merge($row, $mergeRow);
					}

					$markerArray = array();
					$item = $basketObj->getItem($row, 'firstVariant');
					$image = ($childCatWrap ? 'listImageHasChilds': 'listImage');

					if (is_array($categoryArray) && !isset($categoryArray[$currentCat])  && is_array($this->conf['listImageRoot.']))	{
						$image = 'listImageRoot';
					}

					if ($itemTable->type == 'product' && $this->bUseArticles == 1) {
						// get the article uid with these colors, sizes and gradings
						$articleRow = $itemTable->getArticleRow($row, $theCode);
							// use the product if no article row has been found
						$prodVariantRow = $row;
						if ($articleRow)	{
							$itemTable->mergeAttributeFields($prodVariantRow, $articleRow, FALSE);
						}
						$prodVariantItem = $item;

							// use the fields of the article instead of the product
						//
						$prodVariantItem['rec'] = $prodVariantRow;
						$articleViewObj->getItemMarkerArray (
							$prodVariantItem,
							$markerArray,
							$catTitle,
							$this->config['limitImage'],
							$image,
							$articleViewTagArray,
							array(),
							$theCode,
							$iCount,
							'',
							$imageWrap,
							TRUE,
							$TSFE->renderCharset
						);

						$articleViewObj->getItemSubpartArrays (
							$t['item'],
							$row,
							$subpartArray,
							$wrappedSubpartArray,
							$articleViewTagArray,
							$theCode,
							$iCount
						);
					}

					include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_basketitem_view.php');
					$basketItemView = t3lib_div::getUserObj('tx_ttproducts_basketitem_view');
					$basketItemView->init($this->pibase, $basketObj->basketExt);
					$basketItemView->getItemMarkerArray (
						$functablename,
						$item,
						$markerArray,
						$viewTagArray,
						$tmpHidden,
						$theCode,
						$iCount,
						TRUE,
						$TSFE->renderCharset
					);
					$itemTableView->getItemMarkerArray (
						$item,
						$markerArray,
						$catTitle,
						$this->config['limitImage'],
						$image,
						$viewTagArray,
						array(),
						$theCode,
						$iCount,
						'',
						$imageWrap,
						TRUE,
						$TSFE->renderCharset
					);
					$itemTableView->getItemSubpartArrays (
						$t['item'],
						$row,
						$subpartArray,
						$wrappedSubpartArray,
						$viewTagArray,
						$theCode,
						$iCount
					);

					if ($itemTable->type == 'article')	{
						$productMarkerArray = array_merge ($productMarkerArray, $markerArray);
						$markerArray = array_merge ($productMarkerArray, $markerArray);
					}
					if ($linkCat)	{
						$linkCategoryMarkerArray = array();
						$categoryTableView->getMarkerArray (
							$linkCategoryMarkerArray,
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
						$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($markerArray, $basketObj->giftnumber);
					}

					// $markerArray['###FORM_URL###']=$this->formUrl; // Applied later as well.
					$addQueryString = array();
					$addQueryString = $this->uidArray;
					$this->getSearchParams($addQueryString);
					$this->urlObj->addURLMarkers($TSFE->id,$markerArray,$addQueryString,'',$bUseBackPid);
					$oldFormCount = $formCount;

					$markerArray['###FORM_NAME###'] = $formName.$formCount;

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
							$tablesObj->get('tx_dam')->setFormMarkerArray($damUid, $markerArray);
						}
					}
					$markerArray['###FORM_ONSUBMIT###'] = 'return checkParams (document.'.$markerArray['###FORM_NAME###'].');';
					$rowEven = $cssConf['row.']['even'];
					$rowEven = ($rowEven ? $rowEven : $this->conf['CSSRowEven']); // backwards compatible
					$rowUneven = $cssConf['row.']['uneven'];
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
					$markerArray['###FORM_MEMO###'] = htmlspecialchars($this->pibase->pi_getPageLink($pid,'',$this->urlObj->getLinkParams('', array(), TRUE, TRUE, $itemTableView->getPivar()))); //$this->getLinkUrl($this->conf['PIDmemo']);

					// cuts note in list view
					if (strlen($markerArray['###'.$itemTableView->marker.'_NOTE###']) > $this->conf['max_note_length']) {
						$markerArray['###'.$itemTableView->marker.'_NOTE###'] = substr(strip_tags($markerArray['###'.$itemTableView->marker.'_NOTE###']), 0, $this->conf['max_note_length']) . '...';
					}
					if (strlen($markerArray['###'.$itemTableView->marker.'_NOTE2###']) > $this->conf['max_note_length']) {
						$markerArray['###'.$itemTableView->marker.'_NOTE2###'] = substr(strip_tags($markerArray['###'.$itemTableView->marker.'_NOTE2###']), 0, $this->conf['max_note_length']) . '...';
					}
					if (is_object($itemTableView->variant))	{
						$itemTableView->variant->removeEmptyMarkerSubpartArray($markerArray,$subpartArray, $row, $this->conf, $itemTable->hasAdditional($row,'isSingle'), !$itemTable->hasAdditional($row,'noGiftService'));
					}


					if ($t['item'])	{
						$tempContent = $this->pibase->cObj->substituteMarkerArrayCached(
							$t['item'],
							$markerArray,
							$subpartArray,
							$wrappedSubpartArray
						);
					}
					$itemsOut .= $tempContent;

	//				} // foreach ($productList as $k2 => $row)

					// max. number of columns reached?

					if (!$displayColumns || $iColCount == $displayColumns || $displayCatHeader == 'always') {

						if ($t['itemFrameWork'])	{

							// complete the last table row
							if (!$displayColumns || $iColCount == $displayColumns)	{
								$itemsOut .= $this->finishHTMLRow($cssConf, $iColCount, $tableRowOpen, $displayColumns);
							}
							// $itemListOut .= $this->pibase->cObj->substituteSubpart($t['itemFrameWork'],'###ITEM_SINGLE###',$itemsOut,0);
							$markerArray = array_merge ($productMarkerArray, $categoryMarkerArray, $markerArray);
							$subpartArray = array();
							$subpartArray['###ITEM_SINGLE###'] = $itemsOut;
							$itemListOut .= $this->pibase->cObj->substituteMarkerArrayCached($t['itemFrameWork'], $markerArray, $subpartArray, $wrappedSubpartArray);
							$itemsOut = '';
						}
//						$iColCount = 0; // restart in the first column
					}
					$nextRow = $itemArray[$iCount];
					$nextCat = $nextRow['category'];
					$catArray = $categoryTable->getCategoryArray($nextRow['uid']);
					if (count($catArray))	{
						reset ($catArray);
						$this->getCategories ($categoryTable, $catArray, $rootCatArray, $rootLineArray, $cat, $nextCurrentCat, $nextCat);
					}

					$nextArray['category'] = (($pageAsCategory < 2) ? $nextCat : $nextRow['pid']);
					if ($itemTable->type == 'article')	{
						$nextArray['product'] = $nextRow['uid_product'];
					} else {
						$nextArray['product'] = $nextRow['uid'];
					}

					// multiple columns display and ITEM_SINGLE_POST_HTML is in the item's template?
					if (
						$displayCatHeader != 'always' && $displayCatHeader != 'current' &&
							($nextArray['category'] != $currentArray['category'] && $itemsOut ||
							$nextArray['product']  != $currentArray['product']  && $itemTable->type != 'product' && $t['productAndItemsFrameWork']) ||
						$nextRow == ''
					) {
						if ($bItemPostHtml && (
							$nextArray['category']  !=  $currentArray['category'] && $itemsOut  || // && $t['categoryFrameWork'] != ''
							$nextArray['product']   !=  $currentArray['product']  && $itemTable->type == 'article' && $t['productAndItemsFrameWork']) )	{
							// complete the last table row
							$itemsOut .= $this->finishHTMLRow($cssConf, $iColCount, $tableRowOpen, $displayColumns);
						}

						if (($nextArray['category'] != $currentArray['category'] || $nextRow == '') && $itemsOut && $t['itemFrameWork'])	{

							$subpartArray = array();
							$subpartArray['###ITEM_SINGLE###'] = $itemsOut;
							$itemListNewOut = $this->pibase->cObj->substituteMarkerArrayCached($t['itemFrameWork'], $markerArray, $subpartArray, $wrappedSubpartArray);
							$itemListOut .= $itemListNewOut;
							$itemsOut = '';
						}
					}
				}	// foreach ($itemArray as $k1 => $productList) {
			} else {
				if (isset($catTableConf['subpart.']))	{
					$displayCat = $cat;
					$categoryTableView->getMarkerArray (
						$categoryMarkerArray,
						$displayCat,
						$TSFE->id,
						$this->config['limitImage'],
						'listcatImage',
						$viewCatTagArray,
						$tmp = array(),
						$pageAsCategory,
						$theCode,
						$iCount,
						'',
						''
					);

					foreach($catTableConf['subpart.'] as $subpart => $subpartConfig)	{

						if (
							is_array($subpartConfig) &&
							$subpartConfig['show'] == 'default'
						)	{

							if (
								$subpart == 'ITEM_CATEGORY.' &&
								$t['categoryFrameWork']
							)	{
								$catTitle = $categoryTableView->getMarkerArrayCatTitle($categoryMarkerArray);
								$categoryOut = $this->pibase->cObj->substituteMarkerArray($t['categoryFrameWork'], $categoryMarkerArray);
							}

							if (
								$subpart == 'ITEM_LIST.' &&
								$t['itemFrameWork']
							)	{
								$markerArray = $categoryMarkerArray;
								$subpartArray = array();
								$markerArray['###ITEM_SINGLE_PRE_HTML###'] = '';
								$markerArray['###ITEM_SINGLE_POST_HTML###'] = '';
								$subpartArray['###ITEM_SINGLE###'] = '';
								$itemListOut = $this->pibase->cObj->substituteMarkerArrayCached($t['itemFrameWork'], $categoryMarkerArray, $subpartArray, $wrappedSubpartArray);
							}
						}
					}
				} else {
					$out = '';  // TODO: keine Produkte gefunden
				}
			}

			if ($itemListOut || $categoryOut || $productListOut)	{
				$catItemsListOut = &$itemListOut;
				if ($itemTable->type == 'article' && $productListOut && $t['productAndItemsFrameWork'])	{
					$productListOut .= $this->advanceProduct($t['productAndItemsFrameWork'], $t['productFrameWork'], $itemListOut, $productMarkerArray, $categoryMarkerArray);
					$catItemsListOut = &$productListOut;
				}
				$out .= $this->advanceCategory($t['categoryAndItemsFrameWork'], $catItemsListOut, $categoryOut, $itemListSubpart, $oldFormCount, $formCount);
			}
		}	// if ($theCode != 'SEARCH' || ($theCode == 'SEARCH' && $sword))	{

		if (count($itemArray)) {
			// next / prev:
			// $url = $this->getLinkUrl('','begin_at');
				// Reset:
			$subpartArray=array();
			$wrappedSubpartArray=array();
			$markerArray=array();
			$splitMark = md5(microtime());
			$addQueryString=array();
			$memoControls = tx_ttproducts_control_memo::getControlVars();
			foreach ($memoControls as $memoControl) {
				$addQueryString[$memoControl] = '';
			}

			if ($cat)	{
				$addQueryString['cat'] = $cat;
			}

			if ($sword) 	{
				$addQueryString['sword'] = $sword;
			}

			if ($more)	{
				$next = ($begin_at + $limit > $productsCount) ? $productsCount - $limit : $begin_at + $limit;
				$addQueryString['begin_at'] = $next;
				$this->getSearchParams($addQueryString);
				$tempUrl = $this->pibase->pi_linkTP_keepPIvars($splitMark, $addQueryString, $bUseCache, 0);
				$wrappedSubpartArray['###LINK_NEXT###'] = explode ($splitMark, $tempUrl);
			} else {
				$subpartArray['###LINK_NEXT###']='';
			}
			$bUseCache = $bUseCache && (count($basketObj->itemArray) == 0);

			if ($begin_at)	{
				$prev = ($begin_at-$limit < 0) ? 0 : $begin_at - $limit;
				$addQueryString['begin_at'] = $prev;
				$this->getSearchParams($addQueryString);
				$tempUrl = $this->pibase->pi_linkTP_keepPIvars($splitMark, $addQueryString, $bUseCache, 0);
				$wrappedSubpartArray['###LINK_PREV###'] = explode ($splitMark, $tempUrl); // array('<a href="'.$url.'&begin_at='.$prev.'">','</a>');
			} else {
				$subpartArray['###LINK_PREV###'] = '';
			}
			$markerArray['###BROWSE_LINKS###']='';
			if ($productsCount > $limit )	{ // there is more than one page, so let's browse
				$wrappedSubpartArray['###LINK_BROWSE###'] = array('',''); // <- this could be done better I think, or not?
				for ($i = 0 ; $i < ($productsCount / $limit); $i++)	 {
					if (($begin_at >= $i * $limit) && ($begin_at < $i * $limit + $limit))	{
						$markerArray['###BROWSE_LINKS###'].= ' <b>' . (string) ($i + 1) . '</b> ';
						//	you may use this if you want to link to the current page also
						//
					} else {
						$addQueryString['begin_at'] = (string)($i * $limit);
						$tempUrl = $this->pibase->pi_linkTP_keepPIvars((string)($i+1).' ',$addQueryString,$bUseCache,0);
						$markerArray['###BROWSE_LINKS###'] .= $tempUrl; // ' <a href="'.$url.'&begin_at='.(string)($i * $limit).'">'.(string)($i+1).'</a> ';
					}
				}
			} else {
				$subpartArray['###LINK_BROWSE###']='';
			}

			$subpartArray['###ITEM_CATEGORY_AND_ITEMS###'] = $out;
			// $markerArray['###FORM_URL###'] = $this->formUrl;	  // Applied it here also...
			$addQueryString = array();
			$addQueryString = $this->uidArray;
			$excludeList = ($theCode == 'SEARCH' ? 'sword' : '');
			$this->getSearchParams($addQueryString);
			$markerArray = $this->urlObj->addURLMarkers($TSFE->id,$markerArray,$addQueryString,$excludeList,$bUseBackPid); //Applied it here also...
			$markerArray['###AMOUNT_CREDITPOINTS###'] = number_format($TSFE->fe_user->user['tt_products_creditpoints'],0);
			$markerArray['###ITEMS_SELECT_COUNT###'] = $productsCount;
 			$this->javaScriptMarker->getMarkerArray($jsMarkerArray, $markerArray);
 			$markerArray = array_merge ($jsMarkerArray, $markerArray);
			$markerArray['###HIDDENFIELDS###'] = $hiddenText; // TODO
			$out = $this->pibase->cObj->substituteMarkerArrayCached($t['listFrameWork'],$markerArray,$subpartArray,$wrappedSubpartArray);

			$content .= $out;
		} elseif ($this->conf['listViewOnSearch'] == '1' && $sword && $allowedItems!='0' && $theCode=='SEARCH')	{
			$content .= $this->pibase->cObj->getSubpart($templateCode, $subpartmarkerObj->spMarker('###ITEM_SEARCH_EMPTY###'));
		} else if ($out) {
			$content .= $out;
		} else if ($whereCat != '' || $allowedItems != '0' || !$bListStartEmpty) {
			$subpartArray = array();
			$subpartArray['###ITEM_CATEGORY_AND_ITEMS###'] = '';
			$subpartArray['###LINK_PREV###'] = '';
			$subpartArray['###LINK_NEXT###'] = '';

			$markerArray['###BROWSE_LINKS###'] = '';

			$out = $this->pibase->cObj->substituteMarkerArrayCached($t['listFrameWork'], $markerArray, $subpartArray);
			$content .= $out;
			$contentEmpty = $this->pibase->cObj->getSubpart($templateCode, $subpartmarkerObj->spMarker('###ITEM_LIST_EMPTY###'));
		} else {
			// nothing is shown
		} // if (count ($itemArray))

		$content .= $contentEmpty;

		if ($bCheckUnusedArticleMarkers)	{
			$markerFieldArray = array();
			$articleViewTagArray = array();
			$articleParentArray = array();
			$articleViewObj = $tablesObj->get('tt_products_articles', TRUE);
			$searchString = '###'.$articleViewObj->marker.'_';
			if (strpos($t['item'], $searchString) > 0)	{
				$error_code[0] = 'article_markers_unsubstituted';
				$error_code[1] = '###'.$articleViewObj->marker.'_...###';
				$error_code[2] = $this->bUseArticles;
			}
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_list_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_list_view.php']);
}

?>
