<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2016 Franz Holzinger <franz@ttproducts.de>
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
// require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_pid_list.php');
// require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_url_view.php');
// require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_memo_view.php');
// require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_relatedlist_view.php');



class tx_ttproducts_list_view {
	public $cObj;
	public $pibaseClass;
	public $conf;
	public $config;
	public $pid; // pid where to go
	public $urlObj; // url functions
	public $useArticles;
	public $javaScriptMarker;
	public $searchFieldList='';
	public $uidArray;
	public $pidListObj;


	public function init (
		$pibaseClass,
		$pid,
		$useArticles,
		$uidArray,
		$pid_list,
		$recursive
	) {
		$this->pibaseClass = $pibaseClass;
		$pibaseObj = t3lib_div::getUserObj('&'.$pibaseClass);
		$this->cObj = $pibaseObj->cObj;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->getConf();
		$this->config = &$cnf->getConfig();
		$this->pid = $pid;
		$this->useArticles = $useArticles;
		$this->uidArray = $uidArray;
		$this->urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view');

		$this->javaScriptMarker = t3lib_div::makeInstance('tx_ttproducts_javascript_marker');
		$this->javaScriptMarker->init($pibaseObj);
		$this->pidListObj = t3lib_div::getUserObj('tx_ttproducts_pid_list');
		$this->pidListObj->init($this->cObj);
		$this->pidListObj->applyRecursive($recursive, $pid_list, TRUE);
		$this->pidListObj->setPageArray();
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');

			//extend standard search fields with user setup
		$this->searchFieldList = trim($this->conf['stdSearchFieldExt']) ? implode(',', array_unique(t3lib_div::trimExplode(',', $this->searchFieldList . ',' . trim($this->conf['stdSearchFieldExt']), 1))) : 'title,note,' . $tablesObj->get('tt_products')->fieldArray['itemnumber'];
	}


	public function finishHTMLRow (
		&$cssConf,
		&$iColCount,
		$tableRowOpen,
		$displayColumns
	)  {
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
	} // finishHTMLRow


	public function &advanceCategory (
		&$categoryAndItemsFrameWork,
		&$itemListOut,
		&$categoryOut,
		$itemListSubpart,
		$oldFormCount,
		&$formCount
	)	{
		$pibaseObj = t3lib_div::getUserObj('&' . $this->pibaseClass);
		$subpartArray = array();
		$subpartArray['###ITEM_CATEGORY###'] = $categoryOut;
		$subpartArray[$itemListSubpart] = $itemListOut;
		$rc = $pibaseObj->cObj->substituteMarkerArrayCached($categoryAndItemsFrameWork, array(), $subpartArray);
		if ($formCount == $oldFormCount) {
			$formCount++; // next form must have another name
		}
		$categoryOut = '';
		$itemListOut = '';	// Clear the item-code var
		return $rc;
	}


	public function &advanceProduct (
		&$productAndItemsFrameWork,
		&$productFrameWork,
		&$itemListOut,
		&$productMarkerArray,
		&$categoryMarkerArray
	)	{

		$markerArray = array_merge($productMarkerArray, $categoryMarkerArray);
		$productOut = $this->cObj->substituteMarkerArray($productFrameWork, $markerArray);
		$subpartArray = array();
		$subpartArray['###ITEM_PRODUCT###'] = $productOut;
		$subpartArray['###ITEM_LIST###'] = $itemListOut;
		$rc = $this->cObj->substituteMarkerArrayCached($productAndItemsFrameWork, array(), $subpartArray);
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
			$pibaseObj = t3lib_div::getUserObj('&' . $this->pibaseClass);
			$sword = $pibaseObj->piVars['sword'];
		}

		if ($sword) {
			$queryString['sword'] = $sword;
		}
	}


	protected function getCategories (
		&$catObj,
		$catArray,
		$rootCatArray,
		&$rootLineArray,
		$cat,
		&$currentCat,
		&$displayCat
	)	{

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


	protected function getDisplayInfo (
		$displayConf,
		$type,
		$depth,
		$bLast
	)	{
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
	public function &printView (
		&$templateCode,
		$theCode,
		$functablename,
		$allowedItems,
		$additionalPages,
		&$error_code,
		$templateArea='ITEM_LIST_TEMPLATE',
		$pageAsCategory,
		$mergeRow=array(),
		$calllevel=0,
		$callFunctableArray=array()
	) {
		global $TSFE, $TCA, $TYPO3_DB;

		if (count($error_code))	{
			return '';
		}
		$bUseCache = TRUE;

		$pibaseObj = t3lib_div::getUserObj('&'.$this->pibaseClass);
		$basketObj = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$subpartmarkerObj = t3lib_div::getUserObj('&tx_ttproducts_subpartmarker');
		$itemTableArray = array();
		$itemTableViewArray = array();
		$globalMarkerArray = $markerObj->getGlobalMarkerArray();

		if (strpos($theCode,'MEMO') === FALSE)	{
			$memoViewObj = t3lib_div::makeInstance('tx_ttproducts_memo_view');
			$memoViewObj->init(
				$this->pibaseClass,
				$theCode,
				$this->config['pid_list'],
				$this->conf,
				$this->conf['useArticles']
			);
		}
		$pid = ($this->conf['PIDmemo'] ? $this->conf['PIDmemo'] : $TSFE->id);

		$sqlTableArray = array();
		$tableAliasArray = array();
		$sqlTableIndex = 0;
		$headerFieldArray = array();
		$headerTableArray = array();
		$headerTableObjArray = array();
		$content = '';
		$out = '';
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
		$limit = isset($tableConfArray[$functablename]['limit']) ? $tableConfArray[$functablename]['limit'] : $this->config['limit'];

		$limit = intval($limit);
		if ($calllevel == 0)	{
			$sword = t3lib_div::_GP('sword');
			$sword = (isset($sword) ? $sword : t3lib_div::_GP('swords'));

			if (!isset($sword))	{
				$postVars = t3lib_div::_POST($pibaseObj->prefixId);
				$sword = $postVars['sword'];

				if (!isset($sword))	{
					$getVars = t3lib_div::_GET($pibaseObj->prefixId);
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
		$keyFieldArray = $itemTable->getKeyFieldArray($theCode);
		$tableConfArray = array();
		$tableConfArray[$functablename] = &$itemTable->getTableConf($theCode);

	//(	$tableConf = &$itemTable->getTableConf($theCode);
		$itemTable->initCodeConf($theCode,$tableConfArray[$functablename]);
		$prodAlias = $itemTable->getTableObj()->getAlias();
		$tableAliasArray[$tablename] = $itemTable->getAlias();
		$itemTableArray[$itemTable->getType()] = &$itemTable;
		$itemTableViewArray[$itemTable->getType()] = &$itemTableView;

		$selectableVariantFieldArray = $itemTable->variant->getSelectableFieldArray();

		$excludeList = '';

		if (strpos($theCode,'MEMO')===FALSE)	{	// if you link to MEMO from somewhere else, you must not set some parameters for it coming from this list view
			$excludeList = $pibaseObj->prefixId.'[begin_at]';
		}
		$globalMarkerArray['###FORM_MEMO###'] = htmlspecialchars($pibaseObj->pi_getPageLink($pid, '', $this->urlObj->getLinkParams($excludeList, array(), TRUE, TRUE, $itemTableView->getPivar())));

		if ($itemTable->type == 'product' && in_array($this->useArticles, array(1, 2, 3))) {
			$articleViewObj = $tablesObj->get('tt_products_articles', TRUE);
			$articleTable = $articleViewObj->getModelObj();
			$itemTableArray['article'] = $articleTable;
			$itemTableViewArray['article'] = $articleViewObj;
			$param = array($itemTable->getFuncTablename() => $selectableVariantFieldArray);
			$javaScriptObj = t3lib_div::getUserObj('&tx_ttproducts_javascript');
			if ($theCode != 'LISTRELATED' && t3lib_extMgm::isLoaded('taxajax')) {
				$javaScriptObj->set('fetchdata', $param);
			}
		} else if ($itemTable->type == 'article' || $itemTable->getType() == 'dam' && $this->conf['productDAMCategoryID'] != '')	{
			$itemTableViewArray['product'] = $tablesObj->get('tt_products', TRUE);
			$itemTableArray['product'] = $itemTableViewArray['product']->getModelObj();
		}

		$cssConf = $cnf->getCSSConf($itemTable->getFuncTablename(), $theCode);

		if ($itemTable->getType() == 'dam')	{
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
		$tableConfArray[$categoryfunctablename] = &$categoryTable->getTableConf($theCode);

		$catTableConf = &$categoryTable->getTableConf($theCode);
		$categoryTable->initCodeConf($theCode,$catTableConf);
		$whereArray = $pibaseObj->piVars['tt_products'];

		if (is_array($whereArray))	{
			foreach ($whereArray as $field => $value)	{
				$where .= ' AND ' . $field . '=' . $TYPO3_DB->fullQuoteStr($value, $itemTable->getTableObj()->name);
			}
		}

		$flexformArray = $this->cObj->data['pi_flexform'];

		if ($itemTable->type == 'product')	{
			$product_where = tx_div2007_ff::get($flexformArray, 'product_where');
			if ($product_where)	{
				$product_where = $itemTable->getTableObj()->transformWhere($product_where);
				$where .= ' AND ' . $product_where;
			}
		} else if ($itemTable->type == 'dam')	{
			$dam_where = tx_div2007_ff::get($flexformArray, 'dam_where');
			$dam_group_by = tx_div2007_ff::get($flexformArray, 'dam_group_by');
			$dam_join_tables = tx_div2007_ff::get($flexformArray, 'dam_join_tables');
			$damJoinTableArray = t3lib_div::trimExplode(',', $dam_join_tables);

			if ($dam_where)	{
				$dam_where = $itemTable->getTableObj()->transformWhere($dam_where);
				$where .= ' AND '.$dam_where;
			}
		}
		$tableConfArray[$functablename] = $cnf->getTableConf($functablename,$theCode); // $productsConf

		if (!$tableConfArray[$functablename]['orderBy'] && $allowedItems != '') {
			$tableConfArray[$functablename]['orderBy'] = 'FIELD(' . $prodAlias . '.uid, ' . $allowedItems . ')';
		}

		// if parameter 'newitemdays' is specified, only new items from the last X days are displayed
		$newitemdays = $pibaseObj->piVars['newitemdays'];
		$newitemdays = ($newitemdays ? $newitemdays : t3lib_div::_GP('newitemdays'));
		if (($newitemdays || $theCode == 'LISTNEWITEMS') && is_array($tableConfArray[$functablename]) && is_array($tableConfArray[$functablename]['controlFields.'])) {
			if (!$newitemdays)	{
				$newitemdays = $this->conf['newItemDays'];
			}
			$temptime = time() - 86400 * intval(trim($newitemdays));
			$timeFieldArray = t3lib_div::trimExplode (',', $tableConfArray[$functablename]['controlFields.']['newItemDays']);
			$whereTimeFieldArray = array();
			foreach ($timeFieldArray as $k => $value)	{
				$whereTimeFieldArray[] = $tableAliasArray[$tablename] . '.' . $value . ' >= ' . $temptime;
			}
			if (count ($whereTimeFieldArray))	{
				$where .= ' AND (' . implode(' OR ', $whereTimeFieldArray). ')';
			}
		}

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] != '2')	{
			$cat = $categoryTable->getParamDefault($theCode, $pibaseObj->piVars[$categoryTableView->piVar]);
		}
		$searchboxWhere = '';
		$searchVars = $pibaseObj->piVars[tx_ttproducts_model_control::getSearchboxVar()];
		$bUseSearchboxArray = array();

		if (isset($searchVars['local']) || isset($searchVars['uid']))	{
			tx_ttproducts_model_control::getSearchInfo($this->cObj, $searchVars, $functablename, $tablename, $searchboxWhere, $bUseSearchboxArray, $sqlTableIndex, $latest);
		}
		$pageViewObj = $tablesObj->get('pages',1);
		$pid = $pageViewObj->getModelObj()->getParamDefault($theCode,  $pibaseObj->piVars[$pageViewObj->piVar]);

/*		if ($itemTable->type == 'product')	{*/
		$addressUid = $pibaseObj->piVars['a'];
// 		}
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
				&& $addressUid && $itemTable->fieldArray['address']
		)	{
			$addressViewObj = $tablesObj->get('address',TRUE);
			$addressObj = $addressViewObj->getModelObj();

			if (intval($addressUid))	{
				$whereAddress = ' AND (' . $itemTable->fieldArray['address'] . '=' . intval($addressUid) . ')';
			} else if ($addressObj->fieldArray['name'])	{
				$addressRow = $addressObj->get('0', 0, FALSE, $addressObj->fieldArray['name'] . '=' . $TYPO3_DB->fullQuoteStr($addressUid, $addressObj->getTablename(), '', 'uid,' . $addressObj->fieldArray['name']));

				$addressText = $addressRow[$addressObj->fieldArray['name']];
				$whereAddress = ' AND (' . $itemTable->fieldArray['address'] . '=' . $TYPO3_DB->fullQuoteStr($addressText, $addressObj->getTablename()) . ')';
			}
			$where .= $whereAddress;
		}

		if ($whereAddress == '') {	// do not mix address with category filter
			if (isset($tableConfArray[$functablename]['filter.']) && is_array($tableConfArray[$functablename]['filter.']) &&
				isset($tableConfArray[$functablename]['filter.']['param.']) && is_array($tableConfArray[$functablename]['filter.']['param.']) &&
				$tableConfArray[$functablename]['filter.']['param.']['cat'] == 'gp')	{
				$bForceCatParams = TRUE;
			}

			if ($allowedItems == '' || $bForceCatParams)	{
				$whereCat = $itemTable->addWhereCat($categoryTable, $theCode, $cat, $this->pidListObj->getPidlist(),TRUE);
			}

			if ($whereCat == '' && ($allowedItems == '' || $bForceCatParams))	{
				$neededParams = $itemTable->getNeededUrlParams($functablename, $theCode);
				$needArray = t3lib_div::trimExplode(',', $neededParams);
				$bListStartEmpty = FALSE;
				foreach ($needArray as $k => $param)	{
					if ($param && !isset($pibaseObj->piVars[$param]))	{
						$bListStartEmpty = TRUE;
						break;
					}
				}
				if ($bListStartEmpty)	{
					$allowedItems = '0';	// not possible uid
				}
			}

			if ($searchboxWhere != '')	{
				if ($bUseSearchboxArray[$categoryfunctablename])	{
					$whereCat .= ' AND '.$searchboxWhere;
				} else {
					$whereProduct = ' AND '.$searchboxWhere;
				}
			}
			$where .= $whereCat . $whereProduct;
		}

		if (is_array($this->conf['form.'][$theCode . '.']) && is_array($this->conf['form.'][$theCode . '.']['data.']))	{
			$formNameSetup = $this->conf['form.'][$theCode . '.']['data.']['name'];
		}
		$formName = ($formNameSetup ? $formNameSetup : $formName);

		if ($allowedItems || $allowedItems == '0')	{
			$allowedItemArray = array();
			$tempArray = t3lib_div::trimExplode(',',$allowedItems);
			$allowedItemArray = $TYPO3_DB->cleanIntArray($tempArray);
			$where .= ' AND uid IN ('.implode(',',$allowedItemArray).')';
		}

		if ($htmlSwords && (in_array($theCode, array('LIST','SEARCH'))))	{
			$where .= $tablesObj->get('tt_products')->searchWhere($this->searchFieldList, trim($htmlSwords), $theCode);
		}

		if (isset($pibaseObj->piVars['search']) && is_array($pibaseObj->piVars['search']))	{
			$searchWhere = '';
			foreach ($pibaseObj->piVars['search'] as $field => $value)	{
				if (isset($TCA[$tablename]['columns'][$field]))	{
					$searchWhere .= ' AND ' . $field . '=' . $TYPO3_DB->fullQuoteStr($value, $tablename);
				}
			}
			$where .= $searchWhere;
		}


		$currentCat = $categoryTable->getParamDefault($theCode,  $pibaseObj->piVars[$categoryTableView->piVar]);
		$rootCat = $this->conf['rootCategoryID'];

		switch ($theCode) {
			case 'SEARCH':
				$formName = 'ShopSearchForm';
					// Get search subpart
				$t['search'] = $this->cObj->getSubpart($templateCode,$subpartmarkerObj->spMarker('###ITEM_SEARCH###'.$this->config['templateSuffix']));

					// Substitute a few markers
				$out = $t['search'];
				$tmpPid = ($this->conf['PIDsearch'] ? $this->conf['PIDsearch'] : $TSFE->id);
				$addQueryString=array();
				$this->getSearchParams($addQueryString);
				$markerArray = $this->urlObj->addURLMarkers($tmpPid, array(), $addQueryString, 'sword');
					// add Global Marker Array
				$markerArray = array_merge($markerArray, $globalMarkerArray);
				$markerArray['###FORM_NAME###'] = $formName;
				$markerArray['###SWORD###'] = $htmlSwords;
				$markerArray['###SWORD_NAME###'] = 'sword';
				$markerArray['###SWORDS###'] = $htmlSwords; // for backwards compatibility

				$out = $this->cObj->substituteMarkerArrayCached($out, $markerArray);
				if ($formName)	{
						// Add to content
					$content .= $out;
				}
				$out = '';
				$bUseCache = FALSE;
			break;
			case 'LISTDAM':
			case 'MEMODAM':
				if ($theCode == 'LISTDAM')	{
					$formName = 'ListDAMForm';
					$templateArea = 'ITEM_LISTDAM_TEMPLATE'.$this->config['templateSuffix'];
				} else if ($theCode == 'MEMODAM')	{
					$formName = 'ListMemoDAMForm';
					$bUseCache = FALSE;
				}
				$rootCat = $this->conf['rootDAMCategoryID'];
			break;
			case 'LISTGIFTS':
				$formName = 'GiftForm';
				$where .= ' AND '.($this->conf['whereGift'] ? $this->conf['whereGift'] : '1=0');
				$templateArea = 'ITEM_LIST_GIFTS_TEMPLATE'.$this->config['templateSuffix'];
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

		$relatedArray = $categoryTable->getRelated($rootCat, $currentCat, $this->pidListObj->getPidlist());	// read only related categories;
		$excludeCat = 0;
		$categoryArray = $categoryTable->getRelationArray($excludeCat, $rootCat, implode(',', array_keys($relatedArray)));
		$rootCatArray = $categoryTable->getRootArray($rootCat, $categoryArray);

		if ($this->conf['clickItemsIntoSubmenu'])	{
			$childCatArray = $categoryTable->getChildCategoryArray($currentCat);
			if (count($childCatArray))	{
				$templateArea = 'HAS_CHILDS_'.$templateArea;
			}
		}

		if ($calllevel == 0)	{
			$begin_at = $pibaseObj->piVars['begin_at'];
			$begin_at = ($begin_at ? $begin_at : t3lib_div::_GP('begin_at'));
		}

		$begin_at = tx_div2007_core::intInRange($begin_at, 0, 100000);

		if ($theCode == 'SINGLE')	{
			$begin_at = ''; // no page browser in single view for related products
		}

		if ($theCode != 'SEARCH' || ($this->conf['listViewOnSearch'] == '1' && $theCode == 'SEARCH' && $sword))	{
			$t['listFrameWork'] = $this->cObj->getSubpart($templateCode, $subpartmarkerObj->spMarker('###' . $templateArea . '###'));
			// $templateArea = 'ITEM_LIST_TEMPLATE'
			if (!$t['listFrameWork']) {
				$templateObj = t3lib_div::getUserObj('&tx_ttproducts_template');
				$error_code[0] = 'no_subtemplate';
				$error_code[1] = '###' . $templateArea . '###';
				$error_code[2] = $templateObj->getTemplateFile();
				return $content;
			}

			$wrongPounds = preg_match_all('/([^#]+(#{2}|#{4,5}|#{7,8})([^#])+?)/', $t['listFrameWork'], $matches);

			if ($wrongPounds) {
				$error_code[0] = 'template_invalid_marker_border';
				$error_code[1] = '###' . $templateArea . '###';
				$error_code[2] =  htmlspecialchars(implode('|', $matches['0']));

				return '';
			}

			$addQueryString = $this->uidArray;
			$excludeList = ($theCode == 'SEARCH' ? 'sword' : '');
			$this->getSearchParams($addQueryString);
			$markerArray = array();
			$markerArray['###HIDDENFIELDS###'] = '';
			$markerArray = $this->urlObj->addURLMarkers($TSFE->id, $markerArray, $addQueryString, $excludeList);
			$wrappedSubpartArray = array();
			$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
			$subpartArray = array();
			$tablesObj->get('fe_users',TRUE)->getWrappedSubpartArray($subpartArray, $wrappedSubpartArray, $itemTable->getFuncTablename());
			$viewConfArray = array();
			$functableArray = array($functablename, $categoryfunctablename);
			tx_ttproducts_model_control::getTableConfArrays($functableArray,$theCode,$tableConfArray,$viewConfArray,$sqlTableArray);

			if (count($viewConfArray))	{
				$allMarkers = $markerObj->getAllMarkers($t['listFrameWork']);
// 				require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_control_view.php');
				$controlViewObj = t3lib_div::getUserObj('&tx_ttproducts_control_view');
				$controlViewObj->getMarkerArray($markerArray, $allMarkers, $tableConfArray);
			}

				// add Global Marker Array
			$markerArray = array_merge($markerArray, $globalMarkerArray);

			$t['listFrameWork'] = $this->cObj->substituteMarkerArrayCached(
				$t['listFrameWork'],
				$markerArray,
				$subpartArray,
				$wrappedSubpartArray
			);
			$t['categoryAndItemsFrameWork'] = $this->cObj->getSubpart($t['listFrameWork'], '###ITEM_CATEGORY_AND_ITEMS###');
			$t['categoryFrameWork'] = $this->cObj->getSubpart(
				$t['categoryAndItemsFrameWork'],
				'###ITEM_CATEGORY###'
			);
			if ($itemTable->type == 'article')	{
				$t['productAndItemsFrameWork'] = $this->cObj->getSubpart($t['listFrameWork'], '###ITEM_PRODUCT_AND_ITEMS###');
				$t['productFrameWork'] = $this->cObj->getSubpart($t['productAndItemsFrameWork'], '###ITEM_PRODUCT###');
			}
			$t['itemFrameWork'] = $this->cObj->getSubpart($t['categoryAndItemsFrameWork'], '###ITEM_LIST###');
			$t['item'] = $this->cObj->getSubpart($t['itemFrameWork'], '###ITEM_SINGLE###');

			if (isset($damJoinTableArray) && is_array($damJoinTableArray) && in_array('address', $damJoinTableArray))	{

				$t['itemheader'] = array();
				$t['itemheader']['address'] = $this->cObj->getSubpart($t['itemFrameWork'], '###ITEM_ADDRESS###');
				if ($t['itemheader']['address'] != '')	{
					$headerField = $itemTable->getField('address');
					$headerFieldIndex = 0;
					$headerFieldArray[$headerFieldIndex] = $headerField;
					$headerTableArray[$headerFieldIndex] = 'address';
					$headerTableObjArray['address'] = $tablesObj->get('address', TRUE);
					$markerFieldArray = array();
					$headerViewTagArray[$headerFieldIndex] = array();
					$headerParentArray[$headerFieldIndex] = array();

					$headerTableFieldsArray[$headerFieldIndex] = $markerObj->getMarkerFields(
						$t['itemheader']['address'],
						$tablesObj->get('address')->getTableObj()->tableFieldArray,
						$tablesObj->get('address')->getTableObj()->requiredFieldArray,
						$markerFieldArray,
						$tablesObj->get('tt_products')->marker,
						$headerViewTagArray[$headerFieldIndex],
						$headerParentArray[$headerFieldIndex]
					);
					// $foreignTableInfo = $tablesObj->getForeignTableInfo ($tablename,$itemTable->getField('address'));
				}
			}

			$dum = strstr($t['item'], 'ITEM_SINGLE_POST_HTML');
			$bItemPostHtml = (strstr($t['item'], 'ITEM_SINGLE_POST_HTML') != FALSE);

				// Get products count
			$selectConf = array();
			$allowedPages = ($pid ? $pid : $this->pidListObj->getPidlist());
			if ($additionalPages)	{
				$allowedPages .= ','.$additionalPages;
			}
			$selectConf['pidInList'] = $allowedPages;
//			$wherestock = ($this->conf['showNotinStock'] || !is_array(($TCA[$tablename]['columns']['inStock'])) ? '' : ' AND (inStock > 0) ');
			$wherestock = (($this->conf['showNotinStock'] || !is_array($TCA[$tablename]['columns']['inStock'])) ? '' : ' AND (inStock > 0) ');
			$whereNew = $wherestock . $where;
			$whereNew = $itemTable->getTableObj()->transformWhere($whereNew);

			$selectConf['where'] = '1=1 ' . $whereNew;
			$selectConf['from'] = $itemTable->getTableObj()->getAdditionalTables();

			if (isset($damJoinTableArray) && is_array($damJoinTableArray) && in_array('address', $damJoinTableArray))	{
				$addressTable = $tablesObj->get('address', FALSE);
				$addressAlias = $addressTable->getAlias();
				$addressTablename = $addressTable->getTablename();
				$bTableAlreadyPresent = FALSE;

				foreach ($sqlTableArray['from'] as $fromTables)	{
					if (strpos($fromTables,$addressTablename) !== FALSE)	{
						$bTableAlreadyPresent = TRUE;
					}
				}
				if (!$bTableAlreadyPresent)	{
					$enableFieldArray = $addressTable->getTableObj()->getEnableFieldArray();
					$foreignTableInfo = $tablesObj->getForeignTableInfo($tablename, $itemTable->fieldArray['address']);
					$foreignTableInfo['table_field'] = $itemTable->fieldArray['address'];
					$newSqlTableArray = array();
					$aliasPostfix=($sqlTableIndex);
					$tablesObj->prepareSQL($foreignTableInfo, $tableAliasArray, $aliasPostfix, $newSqlTableArray);
					$sqlTableArray['from'][$sqlTableIndex] = $foreignTableInfo['foreign_table'];
					if ($foreignTableInfo['where'] != '')	{
						$sqlTableArray['where'][$sqlTableIndex] = $foreignTableInfo['where'];
					}
					if (isset($newSqlTableArray) && is_array($newSqlTableArray))	{
						foreach ($sqlTableArray as $k => $tmpArray)	{
							if (isset($newSqlTableArray[$k]))	{
								$sqlTableArray[$k][$sqlTableIndex] = $newSqlTableArray[$k];
							}
						}
					}
					$sqlTableIndex++;
				}
// 		$sqlTableArray['from'] = array();
// 		$sqlTableArray['join'] = array();
// 		$sqlTableArray['local'] = array();
// 		$sqlTableArray['where'] = array();
			}

			if (isset($sqlTableArray) && is_array($sqlTableArray) && isset($sqlTableArray['from']) && is_array($sqlTableArray['from']))	{
				foreach ($sqlTableArray['from'] as $k => $sqlFrom)	{
					if ($sqlFrom != '')	{
						$delimiter =',';
						if ($sqlTableArray['local'][$k] == $tablename)	{
							$delimiter = '';
						}
						$selectConf['from'] .= $delimiter . $sqlFrom;
					}
				}
				if ($sqlTableArray['where'] != '')	{
					$tmpWhere = implode(' AND ' , $sqlTableArray['where']);
					if ($tmpWhere != '')	{
						$selectConf['where'] = '(' . $selectConf['where'] . ') AND ' . $tmpWhere;
					}
				}
			}
			$displayConf = array();
				// Get products count
			$displayConf['columns'] = '';
			if ($tableConfArray[$functablename]['displayColumns.'])	{
				$displayConf['columns'] = $tableConfArray[$functablename]['displayColumns.'];
				if (is_array($displayConf['columns']))	{
					$displayColumns = $displayConf['columns']['1'];
					ksort($displayConf['columns'],SORT_STRING);
				}
			}
			$displayConf['header'] = '';
			if ($tableConfArray[$functablename]['displayHeader.'])	{
				$displayConf['header'] = $tableConfArray[$functablename]['displayHeader.'];
				if (is_array($displayConf['header']))	{
					ksort($displayConf['header'], SORT_STRING);
				}
			}
			$selectConf['orderBy'] = $tableConfArray[$functablename]['orderBy'];

				// performing query for display:
			if (!$selectConf['orderBy'])	{
				$selectConf['orderBy'] = $this->conf['orderBy'];
			}
			$tmpArray = t3lib_div::trimExplode(',', $selectConf['orderBy']);
			$orderByArray[$functablename] = $tmpArray[0]; // $orderByProduct

		//	if ($whereCat == '')	{
			$orderByCat = $tableConfArray[$categoryfunctablename]['orderBy'];
		//	}
				// sorting by category not yet possible for articles
			if ($itemTable->type == 'article')	{ // ($itemTable === $this->tt_products_articles)
				$orderByCat = '';	// articles do not have a direct category
				$tmpArray = t3lib_div::trimExplode(',', $selectConf['orderBy']);
				$tmpArray = array_diff($tmpArray, array('category'));
				$selectConf['orderBy'] = implode (',', $tmpArray);
			}
			if ($itemTable->fieldArray['itemnumber'])	{
				$selectConf['orderBy'] = str_replace ('itemnumber', $itemTable->fieldArray['itemnumber'], $selectConf['orderBy']);
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

			if ($itemTable->type == 'product' && in_array($this->useArticles, array(1, 2, 3))) {
				$markerFieldArray = array();
				$articleViewTagArray = array();
				$articleParentArray = array();
				$articleFieldsArray = $markerObj->getMarkerFields(
					$t['item'],
					$itemTable->getTableObj()->tableFieldArray,
					$itemTable->getTableObj()->requiredFieldArray,
					$productMarkerFieldArray,
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
			} else if ($itemTable->type == 'article' || $itemTable->type == 'dam') {
				$viewProductsTagArray = array();
				$productsParentArray = array();
				$tmpFramework = ($t['productAndItemsFrameWork'] ? $t['productAndItemsFrameWork'] : $t['categoryAndItemsFrameWork']);
				$productsFieldsArray = $markerObj->getMarkerFields(
					$tmpFramework,
					$tablesObj->get('tt_products')->getTableObj()->tableFieldArray,
					$tablesObj->get('tt_products')->getTableObj()->requiredFieldArray,
					$markerFieldArray,
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
			$viewCatTagArray = array();
			$catParentArray = array();

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
			$mergeTagArray = array_merge($viewTagArray,$viewCatTagArray);
			$catTitle = '';

			if ($whereCat != '' || $itemTable->type == 'product' && $tablename != 'tt_products' || $itemTable->type == 'dam')	{
				$aliasArray = array();
				$aliasArray['mm1'] = 'mm_cat1';
				$aliasArray['mm2'] = 'mm_cat2';
				$itemTable->addConfCat($categoryTable, $selectConf, $aliasArray);
			}

			if ($orderByCat && ($pageAsCategory < 2 || $itemTable->getType() == 'dam'))	{

			// if ($orderByCat && ($pageAsCategory < 2) || $itemTable->type == 'dam')	{ //  && $tableConfArray[$categoryfunctablename]['language.']['type'] == 'table'
				// $catFields = ($orderByCat == 'uid' ? $orderByCat : 'uid,'.$orderByCat);
				$catOrderBy = $categoryTable->getTableObj()->transformOrderby($orderByCat);

				$orderByCatFieldArray = t3lib_div::trimExplode(',',$catOrderBy);
				$selectConf['orderBy'] = $catOrderBy . ($selectConf['orderBy'] ? ($catOrderBy != '' ? ',' : '') . $selectConf['orderBy'] : '');

				$catAlias = $categoryTable->getTableObj()->getAlias();

				if ($itemTable->type == 'dam')	{
					// SELECT *
					// FROM tx_dam LEFT OUTER JOIN  tx_dam_mm_cat ON tx_dam.category = tx_dam_mm_cat.uid_local

/*					$catFrom = $categoryTable->getTableObj()->name.' '.$catAlias;
					$selectConf['from'] = $catFrom.($selectConf['from']!='' ? ',' : '').$selectConf['from'];*/
					if ($selectConf['leftjoin']=='')	{
						$selectConf['leftjoin'] = 'tx_dam_mm_cat mm_cat1 ON ' . $prodAlias . '.uid=mm_cat1.uid_local';
					}
				} else {
					// SELECT *
					// FROM tt_products
					// LEFT OUTER JOIN tt_products_cat ON tt_products.category = tt_products_cat.uid
					$selectConf['leftjoin'] = $categoryTable->getTableObj()->name . ' ' . $catAlias . ' ON ' . $catAlias . '.uid=' . $prodAlias . '.category';
				}
				$catTables = $categoryTable->getTableObj()->getAdditionalTables(array($categoryTable->getTableObj()->getLangName()));

				if ($selectConf['from'] != '')	{
					$tmpDelim = ',';
				}
				if ($catTables!='')	{
					$selectConf['from'] = $catTables . $tmpDelim . $selectConf['from'];
				}

				if ($categoryTable->bUseLanguageTable($tableConfArray[$categoryfunctablename]))	{

					$joinTables = $selectConf['leftjoin'];
					$categoryTable->getTableObj()->transformLanguage($joinTables, $selectConf['where'], TRUE);
					$selectConf['leftjoin'] = $joinTables;
				}
			}
			$selectFields = implode(',', $fieldsArray);
			$selectConf['selectFields'] = 'DISTINCT ' . $itemTable->getTableObj()->transformSelect($selectFields) . $catSelect;

			if (isset($damJoinTableArray) && is_array($damJoinTableArray) && in_array('address',$damJoinTableArray) && $addressAlias!='')	{

				$addressConf = $addressTable->getTableConf($theCode);
				if (isset($addressConf['requiredFields']))	{
					$addressFieldArray = t3lib_div::trimExplode(',',$addressConf['requiredFields']);
					foreach ($addressFieldArray as $field)	{
						$selectConf['selectFields'] .= ',' . $addressAlias . $aliasPostfix . '.' . $field . ' address_' . $field;
					}
				}
			}
			$join = '';
			$tmpTables = $itemTable->getTableObj()->transformTable('', FALSE, $join);
			// $selectConf['where'] = $join.$itemTable->getTableObj()->transformWhere($selectConf['where']);
			$selectConf['where'] = $join . ' '.$selectConf['where'];
			if (is_array($itemTableConf['filter.']) && is_array($itemTableConf['filter.']['regexp.']))	{
				if (is_array($itemTableConf['filter.']['regexp.']['field.']))	{
					foreach ($itemTableConf['filter.']['regexp.']['field.'] as $field => $value)	{
						$selectConf['where'] .= ' AND ' . $field . ' REGEXP ' . $TYPO3_DB->fullQuoteStr(quotemeta($value), $tablename);
					}
				}
			}
			$selectConf['groupBy'] = $dam_group_by;

				// performing query to count all products (we need to know it for browsing):
			$selectCountConf = $selectConf;
			$selectCountConf['selectFields'] = 'count(distinct ' . $itemTable->getAlias() . '.uid)'; // .$catSelect;

			$queryParts = $itemTable->getTableObj()->getQueryConf($this->cObj, $tablename, $selectCountConf, TRUE);

			if ($selectCountConf['groupBy'] != '')	{
				$queryParts['SELECT'] = 'count(DISTINCT '.$selectCountConf['groupBy'].')';
				unset($queryParts['GROUPBY']);
			}

			$res = $itemTable->getTableObj()->exec_SELECT_queryArray($queryParts);
			$row = $TYPO3_DB->sql_fetch_row($res);
			$TYPO3_DB->sql_free_result($res);
			$productsCount = $row[0];

				// range check to current productsCount
			$begin_at_start = (($begin_at >= $productsCount) ? ($productsCount >= $limit ? $productsCount - $limit : $productsCount) : $begin_at);
			$begin_at = tx_div2007_core::intInRange($begin_at_start, 0);

			if ($latest > 0)	{
				$start = $productsCount - $latest;
				if ($start <= 0)	{
					$start = 1;
				}
				$selectConf['begin'] = $start;
				$limit = $latest;
				$productsCount = $latest;
			}
			$selectConf['max'] = ($limit + 1);
			if ($begin_at > 0)	{
				$selectConf['begin'] = $begin_at;
			}

			if ($selectConf['orderBy'])	{
				$selectConf['orderBy'] = $TYPO3_DB->stripOrderBy($selectConf['orderBy']);
			}

			if (isset($tableConfArray[$functablename]['groupBy'])) {
				$selectConf['groupBy'] = $tableConfArray[$functablename]['groupBy'];

				$selectConf['groupBy'] = $itemTable->getTableObj()->transformOrderby($selectConf['groupBy']);

				if ($selectConf['groupBy']) {
					$selectConf['groupBy'] = $TYPO3_DB->stripGroupBy($selectConf['groupBy']);
				}
			}

		//	$tablename = $itemTable->getTableObj()->name;
			$queryParts = $itemTable->getTableObj()->getQueryConf(
				$this->cObj,
				$tablename,
				$selectConf,
				TRUE
			);

			if ($selectConf['groupBy'] != '')	{
				$queryParts['SELECT'] .= ',count('.$selectConf['groupBy'].') sql_groupby_count';
			}

			if ($queryParts === FALSE)	{
				return 'ERROR in tt_products';
			}

			$res = $itemTable->getTableObj()->exec_SELECT_queryArray($queryParts);
			$itemArray=array();
			$iCount = 0;
			$uidArray = array();
			while($iCount < $limit && ($row = $TYPO3_DB->sql_fetch_assoc($res)))	{
				$iCount++;
				if (count($itemTableLangFields))	{
					foreach($itemTableLangFields as $field => $langfield)	{
						$row[$field] = $row[$langfield];
					}
				}

				if ($itemTable->variant->getUseArticles()==3)	{
					$itemTable->fillVariantsFromArticles($row);
				}

				$itemTable->getTableObj()->substituteMarkerArray($row,$selectableVariantFieldArray);
				$itemTable->getTableObj()->transformRow($row, TT_PRODUCTS_EXT);
				$itemArray[] = $row;
				$uidArray[] = $row['uid'];
			}

			if ($iCount == $limit && ($row = $TYPO3_DB->sql_fetch_assoc($res)))	{
				$more = 1;
			}

			$TYPO3_DB->sql_free_result($res);
			if ($theCode == 'LISTGIFTS') {
				$markerArray = tx_ttproducts_gifts_div::addGiftMarkers($markerArray, $this->giftnumber);
				if (!isset($javaScriptObj)) {
					$javaScriptObj = t3lib_div::getUserObj('&tx_ttproducts_javascript');
				}
				$javaScriptObj->set('email');
			}
			$markerArray['###FORM_NAME###'] = $formName; // needed if form starts e.g. between ###ITEM_LIST_TEMPLATE### and ###ITEM_CATEGORY_AND_ITEMS###
			$markerFramework = 'listFrameWork';
			$t[$markerFramework] = $this->cObj->substituteMarkerArrayCached($t[$markerFramework], $markerArray, array(), array());
			$t['itemFrameWork'] = $this->cObj->substituteMarkerArrayCached($t['itemFrameWork'], $markerArray, array(), array());

			$currentArray = array();
			$currentArray['category'] = '-1';
			$currentArray['product'] = '-1';
			$nextArray = array();
			$nextArray['category'] = '';
			$nextArray['product'] = '';
			$productMarkerArray = array();
			$out = '';
			$categoryAndItemsOut = '';
			$iCount = 0;
			$iColCount = 0;
			$productListOut = '';
			$itemsOut = '';
			$headerItemsOutArray = array();
			$currentHeaderRow = array();
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
			$basketObj->getGraduatedPrices($uidArray);

			if (count($itemArray))	{	// $itemArray must have numbered indexes to work, because the next item must be determined

				if ($itemTable->getType() == 'dam')	{ //
					$relatedListView = t3lib_div::getUserObj('&tx_ttproducts_relatedlist_view');
					$relatedListView->init($this->cObj, $this->pidListObj->getPidlist(), $this->pidListObj->getRecursive());
					$productDAMMarkerArray = $relatedListView->getListMarkerArray(
						$theCode,
						$this->pibaseClass,
						$templateCode,
						array(),
						$mergeTagArray,
						$functablename,
						'',
						$this->uidArray,
						$this->useArticles,
						$pageAsCategory,
						$this->pid,
						$error_code
					);
				}
				$categoryMarkerArray = array();
				$itemRowWrapArray = t3lib_div::trimExplode('|', $cssConf['itemRowWrap']);

				foreach ($itemArray as $k2 => $row) {

					$bHeaderFieldChanged = FALSE;

					if (count($headerTableArray))	{
						if (count($currentHeaderRow))	{
							foreach($headerTableArray as $headertable)	{
								$headerTableLen = strlen($headertable);

								foreach($row as $field => $v)	{
									if (strpos($field,$headertable)===0)	{
										$headerKey = substr($field,$headerfieldLen+1,strlen($field)-$headerTableLen-1);
										if ($currentHeaderRow[$headertable][$headerKey] != $v)	{
											$bHeaderFieldChanged = TRUE;
											break;
										}
									}
								}
							}
						}

						if ($bHeaderFieldChanged || !count($currentHeaderRow))	{
							$bHeaderFieldChanged = TRUE;
							$headerMarkerArray = array();
							foreach($headerTableArray as $headertable)	{

								$headerTableLen = strlen($headertable);
								foreach($row as $field => $v)	{

									if (strpos($field,$headertable)===0)	{
										$headerKey = substr($field,$headerTableLen+1,strlen($field)-$headerTableLen-1);
										$currentHeaderRow[$headertable][$headerKey] = $v;
									} // getMarker ()
								}
							}

							foreach($currentHeaderRow as $headertable => $headerRow)	{

								$headerMarkerArray = array();
								$tablesObj->get($headertable,TRUE)->getRowMarkerArray(
									$headerRow,
									'',
									$headerMarkerArray,
									$tmp=array(),
									$tmp=array(),
									$headerViewTagArray[$headerFieldIndex],
									$theCode,
									TRUE,
									'',
									0,
									'image',
									'',	// id part to be added
									'', // if FALSE, then no table marker will be added
									'',	// this could be a number to discern between repeated rows
									''
								);
								$headerItemsOutArray[$headertable] = $this->cObj->substituteMarkerArrayCached(
									$t['itemheader']['address'],
									$headerMarkerArray,
									array(),
									array()
								);
							}
						}
					}
					$iColCount++;
					$iCount++;

					if ($categoryTable->getFuncTablename() == 'tt_products_cat')	{
						$currentCat = $row['category'];
					}
					$catArray = $categoryTable->getCategoryArray($row['uid'],'sorting');

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
						$confDisplayColumns = $this->getDisplayInfo($displayConf, 'columns', $depth,  !count($childCatArray));
						$displayColumns =
							(
								tx_div2007_core::testInt($confDisplayColumns) ?
									$confDisplayColumns : $displayColumns
							);
						if (count($childCatArray))	{
							$linkCat = next($catLineArray);

							if ($linkCat)	{
								$addQueryString = array($categoryTableView->getPivar() => $linkCat);
								$tempUrl = $pibaseObj->pi_linkTP_keepPIvars_url($addQueryString, 1, 1, $TSFE->id);
								$childCatWrap = '<a href="' . htmlspecialchars($tempUrl) . '"' . $css . '> | </a>';
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
							$this->conf['displayListCatHeader'] &&
							(
								($pageAsCategory < 2) && ($displayCat != $currentArray['category']) ||
								($pageAsCategory == 2) && ($row['pid'] != $currentArray['category']) ||
								$displayCatHeader == 'always'
							)
						)	{
						$catItemsListOut = &$itemListOut;
						if ($itemTable->type == 'article' && $productListOut && $t['productAndItemsFrameWork'])	{
							$catItemsListOut = &$productListOut;
						}

						if ($catItemsListOut && $this->conf['displayListCatHeader'])	{
							$out .= $this->advanceCategory($t['categoryAndItemsFrameWork'], $catItemsListOut, $categoryOut, $itemListSubpart, $oldFormCount, $formCount);
						}
						$currentArray['category'] = (($pageAsCategory < 2 || $itemTable->type == 'dam') ? $displayCat : $row['pid']);
						$bCategoryHasChanged = TRUE;
						$categoryMarkerArray = array();

						if ($where!='' || $this->conf['displayListCatHeader'])	{

							$categoryTableView->getMarkerArray(
								$categoryMarkerArray,
								'',
								$displayCat,
								$row['pid'],
								$this->config['limitImage'],
								'listcatImage',
								$viewCatTagArray,
								$tmp = array(),
								$pageAsCategory,
								$theCode,
								'',
								'',
								''
							);
							$catTitle = $categoryTableView->getMarkerArrayCatTitle($categoryMarkerArray);
							$categoryTableView->getParentMarkerArray(
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

							if ($t['categoryFrameWork'] && $this->conf['displayListCatHeader'])	{
								$categoryOut = $this->cObj->substituteMarkerArray($t['categoryFrameWork'], $categoryMarkerArray);
								if ($displayCatHeader != 'always')	{
									$iColCount = 1;
								}
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

							$itemTableViewArray['product']->getModelMarkerArray (
								$prodRow,
								$itemTableViewArray['product']->getMarker(),
								$productMarkerArray,
								$catTitle,
								$this->config['limitImage'],
								'listImage',
								$viewProductsTagArray,
								array(),
								$theCode,
								$iCount,
								'',
								'',
								$imageWrap,
								TRUE,
								$TSFE->renderCharset
							);

							$tablesObj->get('tt_products',TRUE)->getItemSubpartArrays (
								$t['item'],
								'tt_products',
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
						$prodRow = $row;
					}
					$temp = $cssConf['default'];
					$css_current = ($temp ? $temp : $this->conf['CSSListDefault']);	// only for backwards compatibility

					if ($row['uid'] == $this->uidArray[$itemTable->type]) {
						$temp = $cssConf['current'];
						$css_current = ($temp ? $temp : $this->conf['CSSListCurrent']);
					}

					$css_current = ($css_current ? '" id="'.$css_current.'"' : '');

						// Print Item Title
					$wrappedSubpartArray=array();
					$addQueryString=array();
					$pagesObj = $tablesObj->get('pages');
					$pid = $pagesObj->getPID($this->conf['PIDitemDisplay'], $this->conf['PIDitemDisplay.'], $row);
					$addQueryString[$itemTableView->getPivar()] = intval($row['uid']);
					$piVarCat = $pibaseObj->piVars[$categoryTableView->getPivar()];
					$bUseBackPid = ($pid != $TSFE->id);
					if ($piVarCat)	{
						if ($this->conf['PIDlistDisplay'])	{
							$bUseBackPid = FALSE;
						}
						$cat = $piVarCat;
					}
					if ($cat)	{
						$addQueryString[$categoryTableView->getPivar()] = $cat;
					} // 'tx_ttproducts_pi_search'
					$queryString = $this->urlObj->getLinkParams('begin_at', $addQueryString, FALSE, $bUseBackPid, $itemTableView->getPivar(), $categoryTableView->getPivar());
					$pageLink = htmlspecialchars($pibaseObj->pi_linkTP_keepPIvars_url($queryString,1,0,$pid));
					if ($childCatWrap)	{
						$wrappedSubpartArray['###LINK_ITEM###'] = t3lib_div::trimExplode('|',$childCatWrap);
					} else {
						$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="' . $pageLink . '"' . $css_current . '>','</a>');
					}

					if (count($mergeRow))	{
						$row = array_merge($row, $mergeRow);
					}
					$markerArray = array();
					$item = $basketObj->getItem($row, 'firstVariant');
					$image = ($childCatWrap ? 'listImageHasChilds': 'listImage');

					if (is_array($categoryArray) && !isset($categoryArray[$currentCat]) && is_array($this->conf['listImageRoot.']))	{
						$image = 'listImageRoot';
					}
					$markerArray['###SQL_GROUPBY_COUNT###'] = $row['sql_groupby_count'];
					if ($itemTable->type == 'product' && in_array($this->useArticles, array(1,2,3))) {
						// get the article uid with these colors, sizes and gradings
						$articleRow = $itemTable->getArticleRow($row, $theCode);

							// use the product if no article row has been found
						$prodVariantRow = $row;
						if ($articleRow)	{
							$itemTable->mergeAttributeFields($prodVariantRow, $articleRow, FALSE);
						}

						// use the fields of the article instead of the product
						//
						$prodVariantItem['rec'] = $prodVariantRow;
						$itemTableView->getModelMarkerArray (
							$prodVariantRow,
							$itemTableViewArray['article']->getMarker(),
							$markerArray,
							$catTitle,
							$this->config['limitImage'],
							$image,
							$articleViewTagArray,
							array(),
							$theCode,
							'from-tt-products-articles',
							'',
							'',
							$imageWrap,
							TRUE,
							$TSFE->renderCharset
						);
						$articleViewObj->getItemSubpartArrays (
							$t['item'],
							'tt_products_articles',
							$row,
							$subpartArray,
							$wrappedSubpartArray,
							$articleViewTagArray,
							$theCode,
							$iCount
						);
					}

					if ($itemTable->getType() == 'product' || $itemTable->getType() == 'article')	{
						$currPriceMarkerArray = array();
						$articleTablename = (is_object($itemTableArray['article']) ? $itemTableArray['article']->getTablename() : '');
						$itemTableViewArray[$itemTable->type]->getCurrentPriceMarkerArray(
							$currPriceMarkerArray,
							$itemTableArray['product']->getTablename(),
							$prodRow,
							$articleTablename,
							$prodVariantRow,
							'',
							$theCode
						);

						$markerArray = array_merge($markerArray, $currPriceMarkerArray);

						include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_basketitem_view.php');
						$basketItemView = t3lib_div::getUserObj('tx_ttproducts_basketitem_view');
						$basketItemView->init($this->pibaseClass,$basketObj->basketExt, $basketObj->getItemObj());
						$basketItemView->getItemMarkerArray(
							$functablename,
							$item,
							$markerArray,
							$viewTagArray,
							$tmpHidden,
							$theCode,
							$iCount,
							TRUE,
							$TSFE->renderCharset,
							$callFunctableArray
						);
					}
					$itemTableView->getModelMarkerArray(
						$row,
						$itemTableViewArray[$itemTable->type]->getMarker(),
						$markerArray,
						$catTitle,
						$this->config['limitImage'],
						$image,
						$viewTagArray,
						array(),
						$theCode,
						'',
						'',
						'',
						$imageWrap,
						TRUE,
						$TSFE->renderCharset
					);

					if ($itemTable->getType() == 'product')	{

						$itemTableView->getItemMarkerSubpartArrays(
							$t['item'],
							'tt_products',
							$row,
							$markerArray,
							$subpartArray,
							$wrappedSubpartArray,
							$viewTagArray,
							$theCode,
							$iCount
						);
 					} else {
						$itemTableView->getItemSubpartArrays(
							$t['item'],
							'tt_products',
							$row,
							$subpartArray,
							$wrappedSubpartArray,
							$viewTagArray,
							$theCode,
							$iCount
						);

					}

					if ($itemTable->getType() == 'article')	{
						$productMarkerArray = array_merge ($productMarkerArray, $markerArray);
						$markerArray = array_merge ($productMarkerArray, $markerArray);
					} else if ($itemTable->getType() == 'dam' && $productDAMMarkerArray && is_array($productDAMMarkerArray))	{

						$tmpMarkerArray = array();
						$tmpMarkerArray['###DAM_UID###'] = $row['uid'];

						foreach ($productDAMMarkerArray as $marker => $v)	{
							$markerArray[$marker] = $this->cObj->substituteMarkerArray(
								$v,
								$tmpMarkerArray
							);
						}
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
							'',
							''
						);
						$productMarkerArray = array_merge($productMarkerArray, $linkCategoryMarkerArray);
					}
					$markerArray = array_merge ($productMarkerArray, $categoryMarkerArray, $markerArray);

					if (isset($memoViewObj) && is_object($memoViewObj))	{
						$memoViewObj->getFieldMarkerArray (
							$row,
							'MEMODAM',
							$markerArray,
							$mergeTagArray,
							$bUseCheckBox
						);
					}
					$jsMarkerArray = array_merge ($jsMarkerArray, $productMarkerArray);
					if ($theCode == 'LISTGIFTS') {
						$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($markerArray, $basketObj->giftnumber);
					}

					// $markerArray['###FORM_URL###']=$this->formUrl; // Applied later as well.
					$addQueryString = array();
					$addQueryString = $this->uidArray;
					$this->getSearchParams($addQueryString);
					$markerArray = $this->urlObj->addURLMarkers($TSFE->id, $markerArray, $addQueryString, '', FALSE);
					$oldFormCount = $formCount;
					$markerArray['###FORM_NAME###'] = $formName . ($bFormPerItem ? $formCount : '');

					if ($bFormPerItem)	{
						$formCount++;
					}

					$markerArray['###ITEM_NAME###'] = 'item_' . $iCount;
					if (!$displayColumns)	{
						$markerArray['###FORM_NAME###'] = $markerArray['###ITEM_NAME###'];
					}
					if ($bUseDAM)	{
						$damUid = $this->uidArray['dam'];
						if ($damUid)	{
							$tablesObj->get('tx_dam')->setFormMarkerArray($damUid, $markerArray);
						}
					}
					$markerArray['###FORM_ONSUBMIT###'] = 'return checkParams (document.' . $markerArray['###FORM_NAME###'] . ');';
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

					// cuts note in list view

					if (strlen($markerArray['###' . $itemTableView->marker . '_NOTE###']) > $this->conf['max_note_length']) {
						$markerArray['###' . $itemTableView->marker . '_NOTE###'] = substr(strip_tags($markerArray['###' .$itemTableView->marker . '_NOTE###']), 0, $this->conf['max_note_length']) . '...';
					}
					if (strlen($markerArray['###' . $itemTableView->marker . '_NOTE2###']) > $this->conf['max_note_length']) {
						$markerArray['###' . $itemTableView->marker . '_NOTE2###'] = substr(strip_tags($markerArray['###'.$itemTableView->marker . '_NOTE2###']), 0, $this->conf['max_note_length']) . '...';
					}
					if (is_object($itemTableView->variant))	{

						$itemTableView->variant->removeEmptyMarkerSubpartArray(
							$markerArray,
							$subpartArray,
							$wrappedSubpartArray,
							$row,
							$this->conf,
							$itemTable->hasAdditional($row,'isSingle'),
							!$itemTable->hasAdditional($row,'noGiftService')
						);
					}
					$tempContent = '';
					if ($t['item']!='')	{
						$tempContent .= $this->cObj->substituteMarkerArrayCached(
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
							// $itemListOut .= $this->cObj->substituteSubpart($t['itemFrameWork'],'###ITEM_SINGLE###',$itemsOut,0);

							$markerArray = array_merge($productMarkerArray, $categoryMarkerArray, $markerArray);
							$subpartArray = array();

							if ($bHeaderFieldChanged)	{
								foreach ($headerItemsOutArray as $headerTable => $headerItemsOut)	{
									$marker = $headerTableObjArray['address']->getMarker();
									$subpartArray['###ITEM_'.$marker.'###'] = $this->cObj->substituteMarkerArrayCached($headerItemsOut, $markerArray);
								}
							}
							$subpartArray['###ITEM_SINGLE###'] = $itemsOut;
							$itemListOut .= $this->cObj->substituteMarkerArrayCached($t['itemFrameWork'], $markerArray, $subpartArray, $wrappedSubpartArray);
							$itemsOut = '';
						}
						$iColCount = 0; // restart in the first column
					}

					$nextCat = 0;
					$nextRow = array();
					$catArray = array();
					if (isset($itemArray[$iCount])) {
						$nextRow = $itemArray[$iCount];
						$nextCat = $nextRow['category'];
						$catArray = $categoryTable->getCategoryArray($nextRow['uid']);
					}

					if (count($catArray))	{
						reset($catArray);
						$this->getCategories($categoryTable, $catArray, $rootCatArray, $rootLineArray, $cat, $nextCurrentCat, $nextCat);
					}

					$nextArray['category'] = (($pageAsCategory < 2) ? $nextCat : $nextRow['pid']);
					if ($itemTable->type == 'article')	{
						$nextArray['product'] = $nextRow['uid_product'];
					} else {
						$nextArray['product'] = $nextRow['uid'];
					}

					// multiple columns display and ITEM_SINGLE_POST_HTML is in the item's template?
					if (
						$displayCatHeader != 'always' && $displayCatHeader != 'current' && (
							$nextArray['category'] != $currentArray['category'] && $itemsOut && $t['categoryFrameWork'] ||
							$nextArray['product']  != $currentArray['product']  && $itemTable->type != 'product' && $t['productAndItemsFrameWork']
						) ||
						$nextRow == ''
					) {
						if ($bItemPostHtml && (
							$nextArray['category']  !=  $currentArray['category'] && $itemsOut && $t['categoryFrameWork'] || // && $t['categoryFrameWork'] != ''
							$nextArray['product']   !=  $currentArray['product']  && $itemTable->type == 'article' && $t['productAndItemsFrameWork']) )	{
							// complete the last table row
							$itemsOut .= $this->finishHTMLRow($cssConf, $iColCount, $tableRowOpen, $displayColumns);
						}

						if (($nextArray['category'] != $currentArray['category'] && $t['categoryFrameWork'] || $nextRow == '') && $itemsOut && $t['itemFrameWork'])	{

							// $itemListOut .= $this->cObj->substituteSubpart($t['itemFrameWork'],'###ITEM_SINGLE###',$itemsOut,0);
/*							$markerArray = array_merge($productMarkerArray, $categoryMarkerArray, $markerArray);*/
							$subpartArray = array();
							$subpartArray['###ITEM_SINGLE###'] = $itemsOut;

							$itemListNewOut = $this->cObj->substituteMarkerArrayCached(
								$t['itemFrameWork'],
								$markerArray,
								$subpartArray,
								$wrappedSubpartArray
							);
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
					// keine Produkte gefunden
				}
			}

			if ($itemListOut || $categoryOut || $productListOut)	{
				$catItemsListOut = &$itemListOut;
				if ($itemTable->type == 'article' && $productListOut && $t['productAndItemsFrameWork'])	{
					$productListOut .= $this->advanceProduct($t['productAndItemsFrameWork'], $t['productFrameWork'], $itemListOut, $productMarkerArray, $categoryMarkerArray);
					$catItemsListOut = &$productListOut;
				}
				if ($this->conf['displayListCatHeader'])	{
					$out .= $this->advanceCategory($t['categoryAndItemsFrameWork'], $catItemsListOut, $categoryOut, $itemListSubpart, $oldFormCount, $formCount);
				} else {
					$out .= $itemListOut;
				}
			}
		}	// if ($theCode != 'SEARCH' || ($theCode == 'SEARCH' && $sword))	{
		$contentEmpty = '';

		if (count($itemArray))	{

			// next / prev:
			// $url = $this->getLinkUrl('','begin_at');
				// Reset:
			$subpartArray=array();
			$wrappedSubpartArray=array();
			$markerArray=$globalMarkerArray;
			$splitMark = md5(microtime());
			$addQueryString=array();
			$addQueryString['addmemo'] = '';
			$addQueryString['delmemo'] = '';
			if ($cat)	{
				$addQueryString['cat'] = $cat;
			}
// 			$backPID = $pibaseObj->piVars['backPID'];
// 			$pid = ($backPID ? $backPID : $TSFE->id);
// 			$linkUrl = $pibaseObj->pi_getPageLink($pid,'',$this->urlObj->getLinkParams('',array(),TRUE, TRUE, $itemTableView->getPivar()));
// 			$linkUrl = htmlspecialchars($linkUrl);
// 			$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'. $linkUrl . '">', '</a>', array('useCacheHash' => TRUE));

			if ($sword) 	{
				$addQueryString['sword'] = $sword;
			}

			if ($more)	{
				$next = ($begin_at + $limit > $productsCount) ? $productsCount - $limit : $begin_at + $limit;
				$addQueryString['begin_at'] = $next;
				$this->getSearchParams($addQueryString);

				$tempUrl = $pibaseObj->pi_linkTP_keepPIvars($splitMark,$addQueryString,$bUseCache,0);
				$wrappedSubpartArray['###LINK_NEXT###'] = explode($splitMark, $tempUrl);
			} else {
				$subpartArray['###LINK_NEXT###']='';
			}
			$bUseCache = $bUseCache && (count($basketObj->itemArray)==0);

			if ($begin_at)	{
				$prev = ($begin_at - $limit < 0) ? 0 : $begin_at - $limit;
				$addQueryString['begin_at'] = $prev;
				$this->getSearchParams($addQueryString);

				$tempUrl = $pibaseObj->pi_linkTP_keepPIvars($splitMark, $addQueryString, $bUseCache, 0);
				$wrappedSubpartArray['###LINK_PREV###'] = explode($splitMark, $tempUrl); // array('<a href="'.$url.'&begin_at='.$prev.'">','</a>');
			} else {
				$subpartArray['###LINK_PREV###']='';
			}
			$markerArray['###BROWSE_LINKS###']='';
			if ($productsCount > $limit)	{ // there is more than one page, so let's browse

				$t['browseFrameWork'] = $this->cObj->getSubpart($t['listFrameWork'], $subpartmarkerObj->spMarker('###LINK_BROWSE###'));
				if ($t['browseFrameWork'] != '')	{
					$wrappedSubpartArray['###LINK_BROWSE###'] = array('', '');

					for ($i = 0 ; $i < ($productsCount / $limit); $i++)	 {
						if (($begin_at >= $i * $limit) && ($begin_at < $i * $limit + $limit))	{
							$markerArray['###BROWSE_LINKS###'] .= ' <b>' . (string) ($i + 1) . '</b> ';
							//	you may use this if you want to link to the current page also
							//
						} else {
							$addQueryString['begin_at'] = (string)($i * $limit);
							$tempUrl = $pibaseObj->pi_linkTP_keepPIvars((string) ($i + 1).' ',$addQueryString, $bUseCache, 0);
							$markerArray['###BROWSE_LINKS###'] .= $tempUrl;
						}
					}
				}
				// ###CURRENT_PAGE### of ###TOTAL_PAGES###
				$markerArray['###CURRENT_PAGE###'] = intval($begin_at / $limit + 1);
				$markerArray['###TOTAL_PAGES###'] = ceil($productsCount / $limit);
			} else {
				$subpartArray['###LINK_BROWSE###'] = '';
				$markerArray['###CURRENT_PAGE###'] = '1';
				$markerArray['###TOTAL_PAGES###'] = '1';
			}
			$subpartArray['###ITEM_CATEGORY_AND_ITEMS###'] = $out;

			// $markerArray['###FORM_URL###']=$this->formUrl;	  // Applied it here also...
			$addQueryString = array();
			$addQueryString = $this->uidArray;
			$excludeList = ($theCode == 'SEARCH' ? 'sword' : '');
			$this->getSearchParams($addQueryString);

			$markerArray = $this->urlObj->addURLMarkers($TSFE->id, $markerArray, $addQueryString, $excludeList, FALSE);
			$markerArray['###AMOUNT_CREDITPOINTS###'] = number_format($TSFE->fe_user->user['tt_products_creditpoints'], 0);
			$markerArray['###ITEMS_SELECT_COUNT###'] = $productsCount;
			$this->javaScriptMarker->getMarkerArray($jsMarkerArray, $markerArray);
			$markerArray = array_merge($jsMarkerArray, $markerArray);
			if ($calllevel == 0)	{
				$hiddenCount = 0;
				if ($itemTable->getType() == 'dam')	{
					$hiddenText .= '<input type="hidden" name="' . $pibaseObj->prefixId . '[type][' . $hiddenCount . ']" value="product" />';
					$hiddenCount++;
				}
				$hiddenText .= '<input type="hidden" name="' . $pibaseObj->prefixId . '[type][' . $hiddenCount . ']" value="' . $itemTable->getType() . '" />';
			}
			$markerArray['###HIDDENFIELDS###'] = $hiddenText; // TODO

			if (isset($memoViewObj) && is_object($memoViewObj))	{
				$memoViewObj->getHiddenFields($uidArray, $markerArray, $bUseCheckBox);
			}

			if ($itemTable->getType() == 'dam' && is_object($relatedListView))	{

				$quantityMarkerArray = array();
				$relatedListView->getQuantityMarkerArray (
					$theCode,
					$functablename,
					$itemTableView->getMarker(),
					$itemArray,
					$this->useArticles,
 					$quantityMarkerArray,
					$mergeTagArray
				);
				$subpartArray['###ITEM_CATEGORY_AND_ITEMS###'] = $this->cObj->substituteMarkerArrayCached($subpartArray['###ITEM_CATEGORY_AND_ITEMS###'], $quantityMarkerArray);
			}

			$out = $this->cObj->substituteMarkerArrayCached($t['listFrameWork'], $markerArray, $subpartArray, $wrappedSubpartArray);

			$content .= $out;
		} else if ($theCode == 'SEARCH')	{
			if ($this->conf['listViewOnSearch'] == '1' && $sword && $allowedItems != '0')	{
				$contentEmpty = $subpartmarkerObj->getSubpart($templateCode, $subpartmarkerObj->spMarker('###ITEM_SEARCH_EMPTY###'), $error_code);
			} else {
				// nothing is shown
			}
		} else if ($out)	{
			$content .= $out;
		} else if ($whereCat != '' || $allowedItems != '0' || !$bListStartEmpty)	{
			$contentEmpty = $subpartmarkerObj->getSubpart($templateCode, $subpartmarkerObj->spMarker('###ITEM_LIST_EMPTY###'), $error_code);
		} else {
			// nothing is shown
		} // if (count ($itemArray))

		if ($bCheckUnusedArticleMarkers)	{
			$markerFieldArray = array();
			$articleViewTagArray = array();
			$articleParentArray = array();
			$articleViewObj = $tablesObj->get('tt_products_articles', TRUE);

			$searchString = '###' . $articleViewObj->marker . '_';
			if (strpos($t['item'], $searchString) > 0)	{
				$error_code[0] = 'article_markers_unsubstituted';
				$error_code[1] = '###' . $articleViewObj->marker . '_...###';
				$error_code[2] = $this->useArticles;
			}
		}
		if ($contentEmpty != '')	{
			$contentEmpty = $this->cObj->substituteMarkerArray($contentEmpty,$globalMarkerArray);
		}
		$content .= $contentEmpty;

		return $content;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_list_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_list_view.php']);
}

?>