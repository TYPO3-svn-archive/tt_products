<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * basket functions for a basket object
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Renè Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Els Verberne <verberne@bendoo.nl>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

/*
require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_model_activity.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_creditpoints_div.php');
require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_marker.php');
require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_subpartmarker.php');
require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_url_view.php');*/


class tx_ttproducts_basket_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $price; // price object
	var $templateCode='';		// In init(), set to the content of the templateFile. Used by default in getView()
	var $subpartmarkerObj; // subpart marker functions
	var $urlObj; // url functions
	var $urlArray; // overridden url destinations
	var $funcTablename;
	var $error_code;
	var $bUseArticles;


	function getMarkerArray ()	{
		$basket = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$markerArray = array();
		$priceViewObj = t3lib_div::getUserObj('&tx_ttproducts_field_price_view');

			// This is the total for the goods in the basket.
		$markerArray['###PRICE_GOODSTOTAL_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['goodstotal']);
		$markerArray['###PRICE_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceNoTax']['goodstotal']);
		$markerArray['###PRICE_GOODSTOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['goodstotal']-$basket->calculatedArray['priceNoTax']['goodstotal']);

		$markerArray['###PRICE2_GOODSTOTAL_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['price2Tax']['goodstotal']);
		$markerArray['###PRICE2_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['price2NoTax']['goodstotal']);
		$markerArray['###PRICE2_GOODSTOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['price2Tax']['goodstotal']-$basket->calculatedArray['price2NoTax']['goodstotal']);

		$markerArray['###PRICE_DISCOUNT_GOODSTOTAL_TAX###']    = $priceViewObj->priceFormat($basket->calculatedArray['noDiscountPriceTax']['goodstotal']-$basket->calculatedArray['priceTax']['goodstotal']);
		$markerArray['###PRICE_DISCOUNT_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['noDiscountPriceNoTax']['goodstotal']-$basket->calculatedArray['priceNoTax']['goodstotal']);
		$markerArray['###PRICE2_DISCOUNT_GOODSTOTAL_TAX###']    = $priceViewObj->priceFormat($basket->calculatedArray['noDiscountPriceTax']['goodstotal']-$basket->calculatedArray['price2Tax']['goodstotal']);
		$markerArray['###PRICE2_DISCOUNT_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['noDiscountPriceNoTax']['goodstotal']-$basket->calculatedArray['price2NoTax']['goodstotal']);

		$taxRateArray = t3lib_div::trimExplode(',', $this->conf['TAXrates']);
		if (isset($taxRateArray) && is_array($taxRateArray))	{
			foreach ($taxRateArray as $k => $taxrate)	{
				$taxstr = strval(number_format(floatval($taxrate),2));
				$label = chr(ord('A')+$k);
				$markerArray['###PRICE_TAXRATE_NAME'.($k+1).'###'] = $label;
				$markerArray['###PRICE_TAXRATE_TAX'.($k+1).'###'] = $taxrate;
				$label = $priceViewObj->priceFormat($basket->calculatedArray['priceNoTax']['sametaxtotal'][$taxstr]);
				$markerArray['###PRICE_TAXRATE_GOODSTOTAL'.($k+1).'###'] = $label;
				$label = $priceViewObj->priceFormat($basket->calculatedArray['priceNoTax']['sametaxtotal'][$taxstr] * ($taxrate/100));
				$markerArray['###PRICE_TAXRATE_ONLY_TAX' . ($k + 1) . '###'] = $label;
			}
		}

		// This is for the Basketoverview
		$markerArray['###NUMBER_GOODSTOTAL###'] = $basket->calculatedArray['count'];
		$fileresource = $this->pibase->cObj->fileResource($this->conf['basketPic']);
		$markerArray['###IMAGE_BASKET###'] = $fileresource;

		return $markerArray;
	}


	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param	string		$fieldname is the field in the table you want to create a JavaScript for
	 * @param	[type]		$urlArray: ...
	 * @param	[type]		$bUseArticles: ...
	 * @param	[type]		$templateCode: ...
	 * @param	[type]		$error_code: ...
	 * @return	void
	 */
	function init (
		$pibase,
		$urlArray=array(),
		$bUseArticles,
		&$templateCode,
		&$error_code
	)	{
		$this->pibase = $pibase;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->templateCode = &$templateCode;
		$this->error_code = &$error_code;
		$this->bUseArtcles = $bUseArticles;

		$this->subpartmarkerObj = t3lib_div::makeInstance('tx_ttproducts_subpartmarker');
		$this->subpartmarkerObj->init($pibase->cObj);
		$this->urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view'); // a copy of it
		$this->urlObj->setUrlArray($urlArray);
	} // init

	/**
	 * This generates the shopping basket layout and also calculates the totals. Very important function.
	 *
	 * @param	[type]		$$templateCode: ...
	 * @param	[type]		$theCode: ...
	 * @param	[type]		$infoObj: ...
	 * @param	[type]		$bSelectSalutation: ...
	 * @param	[type]		$bSelectVariants: ...
	 * @param	[type]		$bHtml: ...
	 * @param	[type]		$subpartMarker: ...
	 * @param	[type]		$mainMarkerArray: ...
	 * @return	[type]		...
	 */
	function getView (&$templateCode, $theCode, $infoObj, $bSelectSalutation, $bSelectVariants, $bHtml=true, $subpartMarker='###BASKET_TEMPLATE###', $mainMarkerArray=array())	{
			/*
				Very central function in the library.
				By default it extracts the subpart, ###BASKET_TEMPLATE###, from the $templateCode (if given, else the default $this->templateCode)
				and substitutes a lot of fields and subparts.
				Any pre-preparred fields can be set in $mainMarkerArray, which is substituted in the subpart before the item-and-categories part is substituted.
			*/
		global $TSFE, $TCA;
		global $TYPO3_DB;

		$out = '';
		$basket = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$creditpointsObj = t3lib_div::getUserObj('&tx_ttproducts_field_creditpoints');
		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');

		$funcTablename = $basket->getFuncTablename();
		$itemTableView = $tablesObj->get($funcTablename, true);
		$itemTable = $itemTableView->getModelObj();
		$tableConf = $itemTable->getTableConf($theCode);
		$itemTable->initCodeConf($theCode,$tableConf);

		if ($this->bUseArtcles == 1) {
			$articleViewObj = $tablesObj->get('tt_products_articles', TRUE);
			$articleTable = $articleViewObj->getModelObj();
		}

			// basket
		$paymentshippingObj = t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
		$priceViewObj = t3lib_div::getUserObj('&tx_ttproducts_field_price_view');

		if (!$templateCode)	{
			$templateCode = &$this->templateCode;
		}

			// Getting subparts from the template code.
		$t = array();
		$feuserSubpartArray = array();
		$feuserWrappedSubpartArray = array();
		$feUsersViewObj = $tablesObj->get('fe_users', true);
		$feUsersViewObj->getWrappedSubpartArray($feuserSubpartArray, $feuserWrappedSubpartArray, $funcTablename);
		$tempContent = $this->pibase->cObj->getSubpart($templateCode, $this->subpartmarkerObj->spMarker($subpartMarker));
		$markerArray = array();

		if (isset($mainMarkerArray) && is_array($mainMarkerArray))	{
			$markerArray = array_merge($markerArray, $mainMarkerArray);
		}
			// add Global Marker Array
		$globalMarkerArray = &$markerObj->getGlobalMarkerArray();
		$markerArray = array_merge($markerArray, $globalMarkerArray);
		$t['basketFrameWork'] = $this->pibase->cObj->substituteMarkerArrayCached($tempContent, $markerArray, $feuserSubpartArray, $feuserWrappedSubpartArray);
		$subpartEmptyArray = array('###EMAIL_PLAINTEXT_TEMPLATE_SHOP###', '###BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE###');

		if (!$t['basketFrameWork'] && !in_array($subpartMarker, $subpartEmptyArray)) {
			$mainObj = t3lib_div::getUserObj('&tx_ttproducts_main');
			$this->error_code[0] = 'no_subtemplate';
			$this->error_code[1] = $subpartMarker;
			$this->error_code[2] = $mainObj->templateFile;
			return '';
		}

		if ($t['basketFrameWork'])	{
			if (!$bHtml)	{
				$t['basketFrameWork'] = html_entity_decode($t['basketFrameWork'],ENT_QUOTES,$TSFE->renderCharset);
			}

				// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
			if (trim($this->pibase->cObj->getSubpart($t['basketFrameWork'],'###BILLING_ADDRESS_LOGIN###')))	{
				//if ($GLOBALS['TSFE']->loginUser)	{
				if ($TSFE->loginUser && $this->conf['lockLoginUserInfo']) {
					$t['basketFrameWork'] = $this->pibase->cObj->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS###', '');
				} else {
					$t['basketFrameWork'] = $this->pibase->cObj->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS_LOGIN###', '');
				}
			}

			$t['categoryFrameWork'] = $this->pibase->cObj->getSubpart($t['basketFrameWork'],'###ITEM_CATEGORY###');
			$t['itemFrameWork'] = $this->pibase->cObj->getSubpart($t['basketFrameWork'],'###ITEM_LIST###');
			$t['item'] = $this->pibase->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');

			$currentP='';
			$out='';
			$itemsOut='';
			$viewTagArray = array();
			$markerFieldArray = array('BULKILY_WARNING' => 'bulkily',
				'PRODUCT_SPECIAL_PREP' => 'special_preparation',
				'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
				'PRODUCT_LINK_DATASHEET' => 'datasheet');
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
			$count = 0;
			$basketItemView = '';
			$basket->checkMinPrice = false;

			if ($this->bUseArtcles == 1) {
				$markerFieldArray = array();
				$articleViewTagArray = array();
				$articleParentArray = array();
				$articleFieldsArray = $markerObj->getMarkerFields(
					$t['item'],
					$itemTable->getTableObj()->tableFieldArray,
					$itemTable->getTableObj()->requiredFieldArray,
					$markerFieldArray,
					$articleTable->marker,
					$articleViewTagArray,
					$articleParentArray
				);

				$prodUidField = $cnf->getTableDesc($articleTable->getTableObj()->name, 'uid_product');
				$fieldsArray = array_merge($fieldsArray, $articleFieldsArray);
				$uidKey = array_search($prodUidField, $fieldsArray);
				if ($uidKey != '')	{
					unset($fieldsArray[$uidKey]);
				}
			}

			$damViewTagArray = array();
			// DAM support
			if (t3lib_extMgm::isLoaded('dam') || $this->pibase->piVars['dam']) {
				$damParentArray = array();
				$damObj = $tablesObj->get('tx_dam');
				$fieldsArray = $markerObj->getMarkerFields(
					$itemFrameWork,
					$damObj->getTableObj()->tableFieldArray,
					$damObj->getTableObj()->requiredFieldArray,
					$markerFieldArray,
					$damObj->marker,
					$damViewTagArray,
					$damParentArray
				);
				$damCatObj = $tablesObj->get('tx_dam_cat');
				$damCatMarker = $damCatObj->marker;
				$damCatObj->marker = 'DAM_CAT';

				$viewDamCatTagArray = array();
				$catParentArray = array();
				$catfieldsArray = $markerObj->getMarkerFields(
					$itemFrameWork,
					$damCatObj->getTableObj()->tableFieldArray,
					$damCatObj->getTableObj()->requiredFieldArray,
					$tmp = array(),
					$damCatObj->marker,
					$viewDamCatTagArray,
					$catParentArray
				);
			}
			$hiddenFields = '';

			// loop over all items in the basket indexed by sorting text
			foreach ($basket->itemArray as $sort=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {

					$row = $actItem['rec'];
					if (!$row)	{	// avoid bug with missing row
						continue;
					}
					$pid = intval($row['pid']);
					if (!$basket->getPidListObj()->getPageArray($pid))	{
						// product belongs to another basket
						continue;
					}
					$count++;
					$row = $actItem['rec'];
					$itemTable->tableObj->substituteMarkerArray($row);
					$actItem['rec'] = $row;	// fix bug with PHP 5.2.1
					$bIsNoMinPrice = $itemTable->hasAdditional($row,'noMinPrice');
					if (!$bIsNoMinPrice)	{
						$basket->checkMinPrice = true;
					}

					$pidcategory = ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] == 1 ? $pid : '');
					$currentPnew = $pidcategory.'_'.$actItem['rec']['category'];
						// Print Category Title
					if ($currentPnew!=$currentP)	{
						if ($itemsOut)	{
							$out .= $this->pibase->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
						}
						$itemsOut='';		// Clear the item-code var
						$currentP = $currentPnew;
						if ($this->conf['displayBasketCatHeader'])	{
							$markerArray=array();
							$pageCatTitle = '';
							if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] == 1) {
								$page = $tablesObj->get('pages');
								$pageTmp = $page->get($pid);
								$pageCatTitle = $pageTmp['title'].'/';
							}
							$catTmp = '';
							if ($actItem['rec']['category']) {
								$catTmp = $tablesObj->get('tt_products_cat')->get($actItem['rec']['category']);
								$catTmp = $catTmp['title'];
							}
							$catTitle = $pageCatTitle.$catTmp;
							$this->pibase->cObj->setCurrentVal($catTitle);
							$markerArray['###CATEGORY_TITLE###']=$this->pibase->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
							$out .= $this->pibase->cObj->substituteMarkerArray($t['categoryFrameWork'], $markerArray);
						}
					}
						// Fill marker arrays
					$wrappedSubpartArray = array();
					$subpartArray = array();
					$markerArray = array();

					if (!is_object($basketItemView))	{
						include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_basketitem_view.php');
						$basketItemView = t3lib_div::getUserObj('tx_ttproducts_basketitem_view');
						$basketItemView->init($this->pibase, $basket->basketExt);
					}

					if ($this->bUseArtcles == 1) {
						// get the article uid with these colors, sizes and gradings
						$articleRow = $itemTable->getArticleRow($row, $theCode);

							// use the product if no article row has been found
						$prodVariantRow = $row;
						if ($articleRow)	{
							$itemTable->mergeAttributeFields($prodVariantRow, $articleRow, FALSE);
						}
						$prodVariantItem = $actItem;

							// use the fields of the article instead of the product

						$prodVariantItem['rec'] = $prodVariantRow;
						$articleViewObj->getItemMarkerArray(
							$prodVariantItem,
							$markerArray,
							$catTitle,
							1,
							'basketImage',
							$articleViewTagArray,
							$tmp=array(),
							$theCode,
							$count,
							'',
							'',
							$bHtml,
							$TSFE->renderCharset
						);
						$articleViewObj->getItemMarkerSubpartArrays(
							$t['item'],
							$prodVariantRow,
							$markerArray,
							$subpartArray,
							$wrappedSubparArray,
							$articleViewTagArray,
							$theCode
						);
					}

					$basketItemView->getItemMarkerArray (
						$funcTablename,
						$actItem,
						$markerArray,
						$viewTagArray,
						$hiddenFields,
						$theCode,
						$count,
						false,
						$TSFE->renderCharset
					);

					$catRow = $row['category'] ? $tablesObj->get('tt_products_cat')->get($row['category']) : array();
					$catTitle = $catRow['title'];
					$tmp = array();
					$itemTableView->getItemMarkerArray (
						$actItem,
						$markerArray,
						$catTitle,
						1,
						'basketImage',
						$viewTagArray,
						$tmp,
						$theCode,
						$count,
						'',
						'',
						$bHtml,
						$TSFE->renderCharset
					);
// 					$row['price'] = $actItem['priceTax'];

					$priceViewObj->getItemMarkerArray(
						$funcTablename,
						'price',
						$row,
						'',
						$markerArray,
						$viewTagArray,
						$theCode,
						'',
						$bSkip
					);
					$itemTableView->getItemMarkerSubpartArrays (
						$tempContent,
						$row,
						$markerArray,
						$subpartArray,
						$wrappedSubparArray,
						$viewTagArray,
						$theCode
					);

					$this->pibase->cObj->setCurrentVal($catTitle);
					$markerArray['###CATEGORY_TITLE###'] = $this->pibase->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
					$markerArray['###PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat($actItem['totalTax']);
					$markerArray['###PRICE_TOTAL_NO_TAX###'] = $priceViewObj->priceFormat($actItem['totalNoTax']);
					$markerArray['###PRICE_TOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($actItem['totalTax']-$actItem['totalNoTax']);

					if ($row['category'] == $this->conf['creditsCategory']) {
						// creditpoint system start
						$pricecredits_total_totunits_no_tax = $actItem['totalNoTax']*$row['unit_factor'];
						$pricecredits_total_totunits_tax = $actItem['totalTax']*$row['unit_factor'];
					} else if ($row['price'] > 0 && $row['price2'] > 0 && $row['unit_factor'] > 0) {
						$pricecredits_total_totunits_no_tax = 0;
						$pricecredits_total_totunits_tax = 0;
						$unitdiscount = ($row['price'] - $row['price2']) * $row['unit_factor'] * $actItem['count'];
						$sum_pricediscount_total_totunits += $unitdiscount;
					}
					$markerArray['###PRICE_TOTAL_TOTUNITS_NO_TAX###'] = $priceViewObj->priceFormat($pricecredits_total_totunits_no_tax);
					$markerArray['###PRICE_TOTAL_TOTUNITS_TAX###'] = $priceViewObj->priceFormat($pricecredits_total_totunits_tax);

					$sum_pricecredits_total_totunits_no_tax += $pricecredits_total_totunits_no_tax;
					$sum_price_total_totunits_no_tax += $pricecredits_total_totunits_no_tax;
					$sum_pricecreditpoints_total_totunits += $pricecredits_total_totunits_no_tax;

					// creditpoint system end
					$page = $tablesObj->get('pages');
					$pid = $page->getPID($this->conf['PIDitemDisplay'], $this->conf['PIDitemDisplay.'], $row, $TSFE->rootLine[1]);
					$addQueryString=array();
					$addQueryString[$itemTable->type] = intval($row['uid']);
					$extArray = $row['ext'];

					if (is_array($extArray) && is_array($extArray[$basket->getFuncTablename()]))	{
						$addQueryString['variants'] = htmlspecialchars($extArray[$basket->getFuncTablename()][0]['vars']);
					}
					$isImageProduct = $itemTable->hasAdditional($row,'isImage');
					$damMarkerArray = array();
					$damCategoryMarkerArray = array();

					if (($isImageProduct || $funcTablename == 'tt_products') && is_array($extArray) && is_array($extArray['tx_dam']))	{
						reset ($extArray['tx_dam']);
						$damext = current($extArray['tx_dam']);
						$damUid = $damext['uid'];
						$damRow = $tablesObj->get('tx_dam')->get($damUid);
						$damItem = array();
						$damItem['rec'] = $damRow;
						$damCategoryArray = $tablesObj->get('tx_dam_cat')->getCategoryArray ($damUid);
						if (count($damCategoryArray))	{
							reset ($damCategoryArray);
							$damCat = current($damCategoryArray);
						}
						$tablesObj->get('tx_dam_cat',TRUE)->getMarkerArray (
							$damCategoryMarkerArray,
							$damCat,
							$damRow['pid'],
							$this->config['limitImage'],
							'basketImage',
							$viewDamCatTagArray,
							array(),
							$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'],
							'SINGLE',
							1,
							''
						);
						$tablesObj->get('tx_dam',TRUE)->getItemMarkerArray (
							$damItem,
							$damMarkerArray,
							$damCatRow['title'],
							1,
							'basketImage',
							$damViewTagArray,
							$tmp,
							$theCode,
							$count,
							'',
							'',
							$bHtml
						);
					}
					$markerArray = array_merge($markerArray, $damMarkerArray, $damCategoryMarkerArray);
					$tempUrl = htmlspecialchars($this->pibase->pi_getPageLink($pid,'',$this->urlObj->getLinkParams('', $addQueryString, TRUE, TRUE, ''),array('useCacheHash' => true)));
					$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'. $tempUrl .'"'.$css_current.'>','</a>');
					if (is_object($itemTableView->variant))	{
						$itemTableView->variant->removeEmptyMarkerSubpartArray($markerArray,$subpartArray, $row, $this->conf, $itemTable->hasAdditional($row,'isSingle'), !$itemTable->hasAdditional($row,'noGiftService'));
					}

					// Substitute
					$tempContent = $this->pibase->cObj->substituteMarkerArrayCached($t['item'],$markerArray,$subpartArray,$wrappedSubpartArray);

					$feUsersViewObj->getModelObj()->setCondition($row, $funcTablename);
					$feUsersViewObj->getWrappedSubpartArray($subpartArray, $wrappedSubpartArray,$funcTablename);
					$tempContent = $this->pibase->cObj->substituteMarkerArrayCached($tempContent,$markerArray,$subpartArray,$wrappedSubpartArray);

					$itemsOut .= $tempContent;
				}
				if ($itemsOut)	{
					$tempContent=$this->pibase->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
					$out .= $tempContent;
					$itemsOut='';	// Clear the item-code var
				}
			}

			if (isset ($damCatMarker))	{
				$damCatObj->marker = $damCatMarker; // restore original value
			}
			$subpartArray = array();
			$wrappedSubpartArray = array();

				// Initializing the markerArray for the rest of the template
			$markerArray=$mainMarkerArray;

			$basketMarkerArray = $this->getMarkerArray();
			$markerArray = array_merge($markerArray,$basketMarkerArray);

			$pid = ( $this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);

// 			$tmpLinkParam = $this->urlObj->getLinkParams(
// 				'',
// 				array(),
// 				TRUE,
// 				TRUE,
// 				''
// 			);
// 			$wrappedSubpartArray['###LINK_BASKET###'] = array(
// 				'<a href="' . htmlspecialchars(
// 					$this->pibase->pi_getPageLink(
// 						$pid,
// 						'',
// 						$tmpLinkParam
// 					)
// 				) . '">',
// 				'</a>'
// 			);

// ANFANG Korrektur
			$conf = array('useCacheHash' => FALSE);
			$url = tx_div2007_alpha5::getTypoLink_URL_fh003(
				$this->pibase->cObj,
				$pid,
				$this->urlObj->getLinkParams(
					'',
					array(),
					TRUE,
					TRUE,
					''
				),
				$target = '',
				$conf
			);
			$htmlUrl = htmlspecialchars(
				$url,
				ENT_NOQUOTES,
				$GLOBALS['TSFE']->renderCharset
			);

			$wrappedSubpartArray['###LINK_BASKET###'] = array('<a href="'. $htmlUrl .'">','</a>');
// ENDE Korrektur

			$activityArray = tx_ttproducts_model_activity::getActivityArray();
			if (is_array($activityArray))	{
				$activity = '';
				if ($activityArray['products_payment'])	{
					$activity = 'payment';
				} else if ($activityArray['products_info']) {
					$activity = 'info';
				}
				if ($activity)	{
					$bUseXHTML = $TSFE->config['config']['xhtmlDoctype'] != '';
					$hiddenFields .= '<input type="hidden" name="' . TT_PRODUCTS_EXT . '[activity]" value="' . $activity . '" ' . ($bUseXHTML ? '/' : '') . '>';
				}
			}

			//$markerArray['###PRICE_SHIPPING_PERCENT###'] = $perc;
			$markerArray['###PRICE_SHIPPING_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['shipping']);
			$markerArray['###PRICE_SHIPPING_NO_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceNoTax']['shipping']);
			$markerArray['###PRICE_SHIPPING_ONLY_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['shipping']-$basket->calculatedArray['priceNoTax']['shipping']);

			$basketUrl = htmlspecialchars(tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->urlObj->getLinkParams('', array(), TRUE, TRUE, ''),'',''));

			$markerArray['###SHIPPING_SELECTOR###'] = $paymentshippingObj->generateRadioSelect($theCode, 'shipping', $basket->calculatedArray, $basketUrl);
			$imageCode = $this->pibase->cObj->IMAGE($basket->basketExtra['shipping.']['image.']);
			if ($theCode == 'EMAIL') {
				tx_div2007_alpha5::fixImageCodeAbsRefPrefix($imageCode);
			}
			$markerArray['###SHIPPING_IMAGE###'] = $imageCode;

			$shippingTitle = $basket->basketExtra['shipping.']['title'];
			$markerArray['###SHIPPING_TITLE###'] = htmlspecialchars($shippingTitle);
			$markerArray['###SHIPPING_WEIGHT###'] = doubleval($basket->calculatedArray['weight']);
			$markerArray['###DELIVERYCOSTS###'] = $priceViewObj->priceFormat($paymentshippingObj->getDeliveryCosts()); $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['shipping'] + $basket->calculatedArray['priceTax']['payment']);

			//$markerArray['###PRICE_PAYMENT_PERCENT###'] = $perc;
			$markerArray['###PRICE_PAYMENT_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['payment']);
			$markerArray['###PRICE_PAYMENT_NO_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceNoTax']['payment']);
			$markerArray['###PRICE_PAYMENT_ONLY_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['payment']-$basket->calculatedArray['priceNoTax']['payment'] );

			$markerArray['###PAYMENT_SELECTOR###'] = $paymentshippingObj->generateRadioSelect($theCode, 'payment', $basket->calculatedArray, $basketUrl);
			$imageCode = $this->pibase->cObj->IMAGE($basket->basketExtra['payment.']['image.']);
			if ($theCode == 'EMAIL') {
				tx_div2007_alpha5::fixImageCodeAbsRefPrefix($imageCode);
			}
			$markerArray['###PAYMENT_IMAGE###'] = $imageCode;
			$markerArray['###PAYMENT_TITLE###'] = $basket->basketExtra['payment.']['title'];
			$markerArray['###PAYMENT_NUMBER###'] = htmlspecialchars(t3lib_div::_GP('payment_number'));
			$markerArray['###PAYMENT_NAME###'] = htmlspecialchars(t3lib_div::_GP('payment_name'));
			$markerArray['###PAYMENT_CITY###'] = htmlspecialchars(t3lib_div::_GP('payment_city'));
			// for receipt from DIBS script
			$markerArray['###TRANSACT_CODE###'] = htmlspecialchars(t3lib_div::_GP('transact'));

				// Fill the Currency Symbol or not
			$markerArray['###CUR_SYM###'] = ' '.$this->conf['currencySymbol'];

			$markerArray['###PRICE_TAX_DISCOUNT###'] = $markerArray['###PRICE_DISCOUNT_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['price0Tax']['goodstotal']-$basket->calculatedArray['priceTax']['goodstotal']);
			$markerArray['###PRICE_VAT###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['goodstotal']-$basket->calculatedArray['priceNoTax']['goodstotal']);
			$markerArray['###PRICE_TOTUNITS_DISCOUNT###'] = $priceViewObj->priceFormat($sum_pricediscount_total_totunits);

				// order
			$orderObj = $tablesObj->get('sys_products_orders');

				// Order:	NOTE: Data exist only if the order->getBlankUid() has been called. Therefore this field in the template should be used only when an order has been established
			$markerArray['###ORDER_UID###'] = $orderObj->getNumber($basket->order['orderUid']);
			$markerArray['###ORDER_DATE###'] = $this->pibase->cObj->stdWrap($basket->order['orderDate'],$this->conf['orderDate_stdWrap.']);
			$markerArray['###ORDER_TRACKING_NO###'] = $basket->order['orderTrackingNo'];
				// URL
			$markerArray =  $this->urlObj->addURLMarkers(0, $markerArray);
			$taxFromShipping = $paymentshippingObj->getReplaceTaxPercentage();
			$taxInclExcl = (isset($taxFromShipping) && is_double($taxFromShipping) && $taxFromShipping == 0 ? 'tax_zero' : 'tax_included');
			$markerArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? tx_div2007_alpha5::getLL_fh002($langObj, $taxInclExcl) : '');

			if ($subpartMarker != '###BASKET_OVERVIEW_TEMPLATE###') {

	// Added Franz: GIFT CERTIFICATE
				$markerArray['###GIFT_CERTIFICATE_UNIQUE_NUMBER_NAME###']='recs[tt_products][giftcode]'; // deprecated
				$markerArray['###FORM_NAME###']='BasketForm';
				$markerArray['###FORM_NAME_GIFT_CERTIFICATE###']='BasketGiftForm';
				$markerArray['###INSERT_GIFTCODE###'] = 'recs[tt_products][giftcode]';
				$markerArray['###VALUE_GIFTCODE###'] = htmlspecialchars($basket->recs['tt_products']['giftcode']);
				$cpArray = $TSFE->fe_user->getKey('ses','cp');
				$creditpointsGifts = $cpArray['gift']['amount'];
				$markerArray['###CREDITPOINTS_GIFTS###'] = $creditpointsGifts;

				if ($basket->recs['tt_products']['giftcode'] == '') {
					$subpartArray['###SUB_GIFTCODE_DISCOUNT###'] = '';
					$subpartArray['###SUB_GIFTCODE_DISCOUNTWRONG###'] = '';
					if ($creditpointsGifts == '') {
						$subpartArray['###SUB_GIFTCODE_DISCOUNT_TRUE###'] = '';
					}
				} else {
					$uniqueId = t3lib_div::trimExplode('-', $basket->recs['tt_products']['giftcode'], true);
					$query='uid=\'' . intval($uniqueId[0]) . '\' AND crdate=\'' . intval($uniqueId[1]) . '\'';
					$giftRes = $TYPO3_DB->exec_SELECTquery('*', 'tt_products_gifts', $query);
					$row = $TYPO3_DB->sql_fetch_assoc($giftRes);
					$TYPO3_DB->sql_free_result($giftRes);
					$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);
					$creditpointsDiscount = $creditpointsGifts * $pricefactor;
					$markerArray['###GIFT_DISCOUNT###'] = $creditpointsDiscount;
					$markerArray['###VALUE_GIFTCODE_USED###'] = htmlspecialchars($basket->recs['tt_products']['giftcode']);

					if ($row && $creditpointsGifts && $pricefactor > 0) {
						$subpartArray['###SUB_GIFTCODE_DISCOUNTWRONG###']= '';
						if ($creditpointsGifts == '') {
							$subpartArray['###SUB_GIFTCODE_DISCOUNT_TRUE###'] = '';
						}
					} else {
						$markerArray['###VALUE_GIFTCODE_USED###'] = '**********';
						if (t3lib_div::_GP('creditpoints_gifts') == '') {
							$subpartArray['###SUB_GIFTCODE_DISCOUNT_TRUE###'] = '';
						}
					}
				}
			}

			$amountCreditpoints = $TSFE->fe_user->user['tt_products_creditpoints']+$creditpointsGifts;
			$markerArray['###AMOUNT_CREDITPOINTS###'] = htmlspecialchars($amountCreditpoints);

// #### start

			$pricefactor = doubleval($this->conf['creditpoints.']['priceprod']);
 			$autoCreditpointsTotal = $creditpointsObj->getBasketTotal();
 			$markerArray['###AUTOCREDITPOINTS_TOTAL###'] = number_format($autoCreditpointsTotal, '0');
 			$markerArray['###AUTOCREDITPOINTS_PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat($autoCreditpointsTotal * $pricefactor);
			$markerArray['###CREDITPOINTS_AVAILABLE###'] = number_format($TSFE->fe_user->user['tt_products_creditpoints'],'0');
 			$markerArray['###USERCREDITPOINTS_PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat(($autoCreditpointsTotal < $amountCreditpoints ? $autoCreditpointsTotal : $amountCreditpoints) * $pricefactor);

			// maximum1 amount of creditpoint to change is amount on account minus amount already spended in the credit-shop

			$creditpoints = $autoCreditpointsTotal + $sum_pricecreditpoints_total_totunits * tx_ttproducts_creditpoints_div::getCreditPoints($sum_pricecreditpoints_total_totunits, $this->conf['creditpoints.']);

// #### ENDE

			// maximum1 amount of creditpoint to change is amount on account minus amount already spended in the credit-shop
			$max1_creditpoints = $TSFE->fe_user->user['tt_products_creditpoints'] + $creditpointsGifts;
			// maximum2 amount of creditpoint to change is amount bought multiplied with creditpointfactor
	//		$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);
			$max2_creditpoints = 0;
			if ($pricefactor > 0) {
				$max2_creditpoints = intval (($basket->calculatedArray['priceTax']['total'] - $basket->calculatedArray['priceTax']['vouchertotal']) / $pricefactor );
			}
			// real maximum amount of creditpoint to change is minimum of both maximums
			$markerArray['###AMOUNT_CREDITPOINTS_MAX###'] = number_format( min ($max1_creditpoints,$max2_creditpoints),0);

			// if quantity is 0 than
			if ($amountCreditpoints == '0') {
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
				$wrappedSubpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
				$subpartArray['###SUB_CREDITPOINTS_AMOUNT###'] = '';
			} else {
				$wrappedSubpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
				$wrappedSubpartArray['###SUB_CREDITPOINTS_AMOUNT_EMPTY###'] = '';
				$wrappedSubpartArray['###SUB_CREDITPOINTS_AMOUNT###'] = '';
			}
			$markerArray['###CHANGE_AMOUNT_CREDITPOINTS###'] = 'recs[tt_products][creditpoints]';

			if ($basket->recs['tt_products']['creditpoints'] == '') {
				$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = 0;
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
				$markerArray['###CREDIT_DISCOUNT###'] = '0.00';
			} else {
				// quantity chosen can not be larger than the maximum amount, above calculated
				if ($basket->recs['tt_products']['creditpoints'] > min ($max1_creditpoints,$max2_creditpoints))	{
					$basket->recs['tt_products']['creditpoints'] = min ($max1_creditpoints,$max2_creditpoints);
				}

				$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = htmlspecialchars($basket->recs['tt_products']['creditpoints']);
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
				$markerArray['###CREDIT_DISCOUNT###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['creditpoints']);
			}
			$markerArray['###CREDITPOINTS_SPENDED###'] = $sum_pricecredits_total_totunits_no_tax;

			if ($sum_pricecredits_total_totunits_no_tax <= $amountCreditpoints) {
				$subpartArray['###SUB_CREDITPOINTS_SPENDED_EMPTY###'] = '';
				$markerArray['###CREDITPOINTS_SPENDED###'] = $sum_pricecredits_total_totunits_no_tax;
				// new saldo: creditpoints
				$markerArray['###AMOUNT_CREDITPOINTS###'] = $amountCreditpoints - $markerArray['###CREDITPOINTS_SPENDED###'];
			} else {
				if (!$markerArray['###FE_USER_UID###']) {
					$subpartArray['###SUB_CREDITPOINTS_SPENDED_EMPTY###'] = '';
				} else {
					$markerArray['###CREDITPOINTS_SPENDED_ERROR###'] = 'Wijzig de artikelen in de kurkenshop: onvoldoende kurken op uw saldo ('.$amountCreditpoints.').'; // TODO
					$markerArray['###CREDITPOINTS_SPENDED###'] = '&nbsp;';
				}
			}

			// check the basket limits
			$basketConf = $cnf->getBasketConf('minPrice');
			$minPriceSuccess = true;
			if ($basket->checkMinPrice && $basketConf['type'] == 'price')	{
				$value = $basket->calculatedArray['priceTax'][$basketConf['collect']];
				if (isset($value) && isset($basketConf['collect']) && $value < doubleval($basketConf['value']))	{
					$subpartArray['###MESSAGE_MINPRICE###'] = '';
					$tmpSubpart = $this->pibase->cObj->getSubpart($t['basketFrameWork'],'###MESSAGE_MINPRICE_ERROR###');
					$subpartArray['###MESSAGE_MINPRICE_ERROR###'] = $this->pibase->cObj->substituteMarkerArray($tmpSubpart,$markerArray);
					$minPriceSuccess = false;
				}
			}
			if ($minPriceSuccess)	{
				$subpartArray['###MESSAGE_MINPRICE_ERROR###'] = '';
				$tmpSubpart = $this->pibase->cObj->getSubpart($t['basketFrameWork'],'###MESSAGE_MINPRICE###');
				$subpartArray['###MESSAGE_MINPRICE###'] = $this->pibase->cObj->substituteMarkerArray($tmpSubpart,$markerArray);
			}

			$voucherView = $tablesObj->get('voucher', true);
			$voucherView->getsubpartMarkerArray($subpartArray, $wrappedSubpartArray);
			$voucherView->getMarkerArray($markerArray);

			$markerArray['###CREDITPOINTS_SAVED###'] = number_format($creditpoints,'0');
			$agb_url = array();
			$pidagb = intval($this->conf['PIDagb']);
			$addQueryString = array();
			$singleExcludeList = $this->urlObj->getSingleExcludeList('');

			if ($TSFE->type)	{
				$addQueryString['type'] = $TSFE->type;
			}
			$wrappedSubpartArray['###LINK_AGB###'] = array(
				'<a href="'. $this->pibase->pi_getPageLink($pidagb,'',$this->urlObj->getLinkParams($singleExcludeList, $addQueryString, TRUE, TRUE, '')) .'" target="'.$this->conf['AGBtarget'].'">',
				'</a>'
			);

			$pidRevocation = intval($this->conf['PIDrevocation']);
			$wrappedSubpartArray['###LINK_REVOCATION###'] = array(
				'<a href="' . htmlspecialchars(
					$this->pibase->pi_getPageLink(
						$pidRevocation,
						'',
						$this->urlObj->getLinkParams(
							$singleExcludeList,
							$addQueryString,
							TRUE,
							TRUE,
							''
						)
					)
				) . '" target="' . $this->conf['AGBtarget'] . '">',
				'</a>'
			);

				// Final substitution:
			if (!$TSFE->loginUser)	{		// Remove section for FE_USERs only, if there are no fe_user
				$subpartArray['###FE_USER_SECTION###'] = '';
			}
			if (is_object($infoObj))	{
				$infoObj->getItemMarkerArray ($markerArray, $bSelectSalutation);
			}

			$markerArray['###PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['total']);
			$markerArray['###PRICE_TOTAL_NO_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceNoTax']['total']);
			$markerArray['###PRICE_TOTAL_0_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['price0Tax']['total']);
			$markerArray['###PRICE_TOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['total']-$basket->calculatedArray['priceNoTax']['total']);

			$markerArray['###PRICE_TOTAL_TAX_WITHOUT_PAYMENT###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['total']-$basket->calculatedArray['priceTax']['payment']);

			$markerArray['###PRICE_TOTAL_NO_TAX_WITHOUT_PAYMENT###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceNoTax']['total']-$basket->calculatedArray['priceNoTax']['payment']);

			$markerArray['###PRICE_VOUCHERTOTAL_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceTax']['vouchertotal']);
			$markerArray['###PRICE_VOUCHERTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['priceNoTax']['vouchertotal']);
			$markerArray['###HIDDENFIELDS###'] = $hiddenFields;

				// Call all getItemMarkerArrays hooks at the end of this method
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getBasketView'])) {
				foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getBasketView'] as $classRef) {
					$hookObj= t3lib_div::getUserObj($classRef);
					if (method_exists($hookObj, 'getItemMarkerArrays')) {
						$hookObj->getItemMarkerArrays($this, $templateCode, $theCode, $markerArray,$subpartArray,$wrappedSubpartArray, $theCode, $mainMarkerArray, $count);
					}
				}
			}

			$frameWork = $this->pibase->cObj->substituteSubpart($t['basketFrameWork'], '###ITEM_CATEGORY_AND_ITEMS###', $out);
			$paymentshippingObj->getSubpartArrays($markerArray, $subpartArray, $wrappedSubpartArray, $frameWork);
			$feUsersViewObj->getWrappedSubpartArray($subpartArray, $wrappedSubpartArray, $funcTablename, $bUseBackPid);
				// substitute the main subpart with the rendered content.

			$out =
				$this->pibase->cObj->substituteMarkerArrayCached(
					$frameWork,
					$markerArray,
					$subpartArray,
					$wrappedSubpartArray
				);

		}

		return $out;
	} // getView
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_basket_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_basket_view.php']);
}

?>
