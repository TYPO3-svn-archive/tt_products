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


class tx_ttproducts_basket_view {
	public $pibase; // reference to object of pibase
	public $pibaseClass;
	public $cObj;
	public $conf;
	public $config;
	public $price; // price object
	public $templateCode='';		// In init(), set to the content of the templateFile. Used by default in getView()
	public $subpartmarkerObj; // subpart marker functions
	public $urlObj; // url functions
	public $urlArray; // overridden url destinations
	public $funcTablename;
	public $error_code;
	public $useArticles;




	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basketObj is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param	string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */
	public function init (
		$pibaseClass,
		$urlArray=array(),
		$useArticles,
		&$templateCode,
		&$error_code
	)	{
		$this->pibaseClass = $pibaseClass;
		$this->pibase = t3lib_div::getUserObj('&' . $pibaseClass);
		$this->cObj = $this->pibase->cObj;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->templateCode = &$templateCode;
		$this->error_code = &$error_code;
		$this->useArticles = $useArticles;

		$this->subpartmarkerObj = t3lib_div::makeInstance('tx_ttproducts_subpartmarker');
		$this->subpartmarkerObj->init($pibase->cObj);
		$this->urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view'); // a copy of it
		$this->urlObj->setUrlArray($urlArray);
	} // init


	public function getMarkerArray ()	{
		$basketObj = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$priceViewObj = t3lib_div::getUserObj('&tx_ttproducts_field_price_view');

		$markerArray = array();


			// This is the total for the goods in the basket.
		$markerArray['###PRICE_GOODSTOTAL_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['priceTax']['goodstotal']);
		$markerArray['###PRICE_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['priceNoTax']['goodstotal']);
		$markerArray['###PRICE_GOODSTOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['priceTax']['goodstotal']-$basketObj->calculatedArray['priceNoTax']['goodstotal']);

		$markerArray['###PRICE2_GOODSTOTAL_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['price2Tax']['goodstotal']);
		$markerArray['###PRICE2_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['price2NoTax']['goodstotal']);
		$markerArray['###PRICE2_GOODSTOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['price2Tax']['goodstotal']-$basketObj->calculatedArray['price2NoTax']['goodstotal']);

		$markerArray['###PRICE_DISCOUNT_GOODSTOTAL_TAX###']    = $priceViewObj->priceFormat($basket->calculatedArray['noDiscountPriceTax']['goodstotal'] - $basket->calculatedArray['priceTax']['goodstotal']);
		$markerArray['###PRICE_DISCOUNT_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['noDiscountPriceNoTax']['goodstotal'] - $basket->calculatedArray['priceNoTax']['goodstotal']);
		$markerArray['###PRICE2_DISCOUNT_GOODSTOTAL_TAX###']    = $priceViewObj->priceFormat($basket->calculatedArray['noDiscountPrice2Tax']['goodstotal'] - $basket->calculatedArray['price2Tax']['goodstotal']);
		$markerArray['###PRICE2_DISCOUNT_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($basket->calculatedArray['noDiscountPrice2NoTax']['goodstotal'] - $basket->calculatedArray['price2NoTax']['goodstotal']);

		$taxRateArray = t3lib_div::trimExplode(',', $this->conf['TAXrates']);

		if (isset($taxRateArray) && is_array($taxRateArray))	{
			foreach ($taxRateArray as $k => $taxrate)	{
				$taxstr = strval(number_format(floatval($taxrate), 2));
				$label = chr(ord('A') + $k);
				$markerArray['###PRICE_TAXRATE_NAME' . ($k + 1) . '###'] = $label;
				$markerArray['###PRICE_TAXRATE_TAX' . ($k + 1) . '###'] = $taxrate;
				$label = $priceViewObj->priceFormat($basketObj->calculatedArray['priceNoTax']['sametaxtotal'][$taxstr]);
				$markerArray['###PRICE_TAXRATE_TOTAL' . ($k + 1) . '###'] = $label;
				$label = $priceViewObj->priceFormat($basketObj->calculatedArray['priceNoTax']['goodssametaxtotal'][$taxstr]);
				$markerArray['###PRICE_TAXRATE_GOODSTOTAL' . ($k + 1) . '###'] = $label;
				$label = $priceViewObj->priceFormat($basketObj->calculatedArray['priceNoTax']['sametaxtotal'][$taxstr] * ($taxrate / 100));
				$markerArray['###PRICE_TAXRATE_ONLY_TAX' . ($k + 1) . '###'] = $label;
				$label = $priceViewObj->priceFormat($basketObj->calculatedArray['priceNoTax']['goodssametaxtotal'][$taxstr] * ($taxrate / 100));
				$markerArray['###PRICE_TAXRATE_GOODSTOTAL_ONLY_TAX' . ($k + 1) . '###'] = $label;
			}
		}

		// This is for the Basketoverview
		$markerArray['###NUMBER_GOODSTOTAL###'] = $basketObj->calculatedArray['count'];
		$fileresource = $this->cObj->fileResource($this->conf['basketPic']);
		$markerArray['###IMAGE_BASKET###'] = $fileresource;

		return $markerArray;
	}


	/**
	 * This generates the shopping basket layout and also calculates the totals. Very important function.
	 */
	public function getView (
		&$templateCode,
		$theCode,
		$infoObj,
		$bSelectSalutation,
		$bSelectVariants,
		$bHtml=TRUE,
		$subpartMarker='BASKET_TEMPLATE',
		$mainMarkerArray=array(),
		$templateFilename=''
	)	{
			/*
				Very central function in the library.
				By default it extracts the subpart, ###BASKET_TEMPLATE###, from the $templateCode (if given, else the default $this->templateCode)
				and substitutes a lot of fields and subparts.
				Any pre-preparred fields can be set in $mainMarkerArray, which is substituted in the subpart before the item-and-categories part is substituted.
			*/
		global $TSFE, $TCA;
		global $TYPO3_DB;


		$out = '';
		$basketObj = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$billdeliveryObj = t3lib_div::getUserObj('&tx_ttproducts_billdelivery');
// #### START
		$creditpointsObj = t3lib_div::getUserObj('&tx_ttproducts_field_creditpoints');
// #### ENDE
		$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');

		$funcTablename = $basketObj->getFuncTablename();
		$itemTableView = $tablesObj->get($funcTablename, TRUE);
		$itemTable = $itemTableView->getModelObj();
		$tableConf = $itemTable->getTableConf ($theCode);
		$itemTable->initCodeConf($theCode, $tableConf);
		$minQuantityArray = array();

		if ($this->useArticles == 1 || $this->useArticles == 3) {
			$articleViewObj = $tablesObj->get('tt_products_articles', TRUE);
			$articleTable = $articleViewObj->getModelObj();
		}
			// basket
		$paymentshippingObj = t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
		$priceViewObj = t3lib_div::getUserObj('&tx_ttproducts_field_price_view');

		if ($templateCode == '')	{
			$templateCode = &$this->templateCode;
		}
			// Getting subparts from the template code.
		$t=array();
		$feuserSubpartArray = array();
		$feuserWrappedSubpartArray = array();
		$feUsersViewObj = $tablesObj->get('fe_users', TRUE);
		$feUsersViewObj->getWrappedSubpartArray($feuserSubpartArray, $feuserWrappedSubpartArray, $funcTablename);
		$tempContent = $this->cObj->getSubpart($templateCode,$this->subpartmarkerObj->spMarker('###' . $subpartMarker . $this->config['templateSuffix'] . '###'));

		if (!$tempContent)	{
			$tempContent = $this->cObj->getSubpart($templateCode, $this->subpartmarkerObj->spMarker('###' . $subpartMarker . '###'));
		}
		$markerArray = array();
		if (isset($mainMarkerArray) && is_array($mainMarkerArray))	{
			$markerArray = array_merge($markerArray, $mainMarkerArray);
		}
			// add Global Marker Array
		$globalMarkerArray = &$markerObj->getGlobalMarkerArray();
		$markerArray = array_merge($markerArray, $globalMarkerArray);

		$t['basketFrameWork'] =  $this->cObj->substituteMarkerArrayCached($tempContent, $markerArray, $feuserSubpartArray, $feuserWrappedSubpartArray);

		$subpartEmptyArray = array('EMAIL_PLAINTEXT_TEMPLATE_SHOP', 'BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE');
		if (!$t['basketFrameWork'] && !in_array($subpartMarker, $subpartEmptyArray)) {
			$templateObj = t3lib_div::getUserObj('&tx_ttproducts_template');
			$this->error_code[0] = 'no_subtemplate';
			$this->error_code[1] = '###' . $subpartMarker . $templateObj->getTemplateSuffix() . '###';
			$this->error_code[2] = ($templateFilename ? $templateFilename : $templateObj->getTemplateFile());
			return '';
		}

		if ($t['basketFrameWork'])	{
			$wrongPounds = preg_match_all('/([^#]+(#{2}|#{4,5}|#{7,8})([^#])+?)/', $t['basketFrameWork'], $matches);
			if ($wrongPounds) {
				$templateObj = t3lib_div::getUserObj('&tx_ttproducts_template');
				$this->error_code[0] = 'template_invalid_marker_border';
				$this->error_code[1] = '###' . $subpartMarker . $templateObj->getTemplateSuffix() . '###';
				$this->error_code[2] = htmlspecialchars(implode('|', $matches['0']));

				return '';
			}

			if (!$bHtml)	{
				$t['basketFrameWork'] = html_entity_decode($t['basketFrameWork'],ENT_QUOTES,$TSFE->renderCharset);
			}

				// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
			if (trim($this->cObj->getSubpart($t['basketFrameWork'], '###BILLING_ADDRESS_LOGIN###')))	{
				//if ($GLOBALS['TSFE']->loginUser)	{
				if ($TSFE->loginUser && $this->conf['lockLoginUserInfo']) {
					$t['basketFrameWork'] = $this->cObj->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS###', '');
				} else {
					$t['basketFrameWork'] = $this->cObj->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS_LOGIN###', '');
				}
			}
			$t['categoryFrameWork'] = $this->cObj->getSubpart($t['basketFrameWork'], '###ITEM_CATEGORY###');
			$t['itemFrameWork'] = $this->cObj->getSubpart($t['basketFrameWork'], '###ITEM_LIST###');
			$t['item'] = $this->cObj->getSubpart($t['itemFrameWork'], '###ITEM_SINGLE###');

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
			$basketObj->checkMinPrice = FALSE;

			if ($this->useArticles == 1 || $this->useArticles == 3) {
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
			foreach ($basketObj->itemArray as $sort=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					$row = $actItem['rec'];
					$extArray = $row['ext'];
					if (!$row)	{	// avoid bug with missing row
						continue;
					}
					$pid = intval($row['pid']);
					if (!$basketObj->getPidListObj()->getPageArray($pid))	{
						// product belongs to another basket
						continue;
					}
					$quantity = $basketObj->getItemObj()->getQuantity($actItem);
					$minQuantity = $basketObj->getItemObj()->getMinQuantity($actItem);
					if ($minQuantity != '0.00' && $quantity < $minQuantity) {
						$minQuantityArray[] = array('rec' => $row, 'minQuantity' => $minQuantity, 'quantity' => $quantity);
					}
					$count++;
					$itemTable->tableObj->substituteMarkerArray($row);

					$actItem['rec'] = $row;	// fix bug with PHP 5.2.1
					$bIsNoMinPrice = $itemTable->hasAdditional($row,'noMinPrice');
					if (!$bIsNoMinPrice)	{
						$basketObj->checkMinPrice = TRUE;
					}

					$pidcategory = ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] == 1 ? $pid : '');
					$currentPnew = $pidcategory . '_' . $actItem['rec']['category'];
						// Print Category Title
					if ($currentPnew != $currentP)	{
						if ($itemsOut)	{
							$out .= $this->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
						}
						$itemsOut='';		// Clear the item-code var
						$currentP = $currentPnew;
						if ($this->conf['displayBasketCatHeader'])	{
							$markerArray=$globalMarkerArray;
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
							$this->cObj->setCurrentVal($catTitle);
							$markerArray['###CATEGORY_TITLE###']=$this->cObj->cObjGetSingle($this->conf['categoryHeader'], $this->conf['categoryHeader.'], 'categoryHeader');
							$out .= $this->cObj->substituteMarkerArray($t['categoryFrameWork'], $markerArray);
						}
					}
						// Fill marker arrays
					$wrappedSubpartArray = array();
					$subpartArray = array();
					$markerArray = $globalMarkerArray;

					if (!is_object($basketItemView))	{
						include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_basketitem_view.php');
						$basketItemView = t3lib_div::getUserObj('tx_ttproducts_basketitem_view');
						$basketItemView->init($this->pibaseClass,$basketObj->basketExt,$basketObj->getItemObj());
					}

					// $extRow = array('extTable' => $row['extTable'], 'extUid' => $row['extUid']);
					$basketItemView->getItemMarkerArray (
						$funcTablename,
						$actItem,
						$markerArray,
						$viewTagArray,
						$hiddenFields,
						$theCode,
						$count,
						FALSE,
						$TSFE->renderCharset
					);

					$catRow = $row['category'] ? $tablesObj->get('tt_products_cat')->get($row['category']) : array();
					// $catTitle= $actItem['rec']['category'] ? $this->tt_products_cat->get($actItem['rec']['category']) : '';
					$catTitle = $catRow['title'];
					$tmp = array();
					$itemTableView->getModelMarkerArray (
						$row,
						'',
						$markerArray,
						$catTitle,
						$this->config['limitImage'],
						'basketImage',
						$viewTagArray,
						$tmp,
						$theCode,
						$count,
						'',
						'',
						'',
						$bHtml,
						$TSFE->renderCharset
					);

						// use the product if no article row has been found
					$prodVariantRow = $row;

					if ($this->useArticles == 1 || $this->useArticles == 3) {
						$extArray = $row['ext'];
						$articleRows = array();

						// get the article uid with these colors, sizes and gradings
						if (is_array($extArray) && is_array($extArray[$articleTable->getFuncTablename()]))	{
							$articleExtArray = $extArray[$articleTable->getFuncTablename()];
							foreach($articleExtArray as $k => $articleData) {
								$articleRows[$k] = $articleTable->get($articleData['uid']);
							}
						} else {
							$articleRows['0'] = $itemTable->getArticleRow($row, $theCode);
						}

						if (is_array($articleRows) && count($articleRows)) {
							$bKeepNotEmpty = FALSE;
							if ($this->useArticles == 3)	{
								$itemTable->fillVariantsFromArticles($prodVariantRow);
								$itemTable->variant->modifyRowFromVariant($prodVariantRow);
								$bKeepNotEmpty = TRUE;
							}
							foreach ($articleRows as $articleRow) {
								$itemTable->mergeAttributeFields(
									$prodVariantRow,
									$articleRow,
									$bKeepNotEmpty,
									TRUE
								);
							}
						} else {
							$variant = $itemTable->variant->getVariantFromRow($row);
							$itemTable->variant->modifyRowFromVariant($prodVariantRow, $variant);
						}
							// use the fields of the article instead of the product
						//
						$prodVariantItem['rec'] = $prodVariantRow;
						$articleViewObj->getModelMarkerArray (
							$prodVariantRow,
							'',
							$markerArray,
							$catTitle,
							$this->config['limitImage'],
							'basketImage',
							$articleViewTagArray,
							$tmp=array(),
							$theCode,
							$count,
							'',
							'',
							'',
							$bHtml,
							$TSFE->renderCharset
						);

						$articleViewObj->getItemMarkerSubpartArrays (
							$t['item'],
							$articleViewObj->getModelObj()->getFuncTablename(),
							$prodVariantRow,
							$markerArray,
							$subpartArray,
							$wrappedSubpartArray,
							$articleViewTagArray,
							$theCode
						);
					}
					$itemTableView->getItemMarkerSubpartArrays (
						$tempContent,
						$itemTableView->getModelObj()->getFuncTablename(),
						$prodVariantRow,
						$markerArray,
						$subpartArray,
						$wrappedSubpartArray,
						$viewTagArray,
						$theCode,
						$count
					);

					$this->cObj->setCurrentVal($catTitle);
					$markerArray['###CATEGORY_TITLE###'] = $this->cObj->cObjGetSingle($this->conf['categoryHeader'], $this->conf['categoryHeader.'], 'categoryHeader');

					tx_ttproducts_field_price_view::getItemMarkerArray($actItem, $markerArray);

					$markerArray['###PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat($actItem['totalTax']);
					$markerArray['###PRICE_TOTAL_NO_TAX###'] = $priceViewObj->priceFormat($actItem['totalNoTax']);
					$markerArray['###PRICE_TOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($actItem['totalTax'] - $actItem['totalNoTax']);

					if ($row['category'] == $this->conf['creditsCategory']) {
						// creditpoint system start
						$pricecredits_total_totunits_no_tax = $actItem['totalNoTax']*$row['unit_factor'];
						$pricecredits_total_totunits_tax = $actItem['totalTax']*$row['unit_factor'];
					} else if ( doubleval($row['price']) && doubleval($row['price2'])) {
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
					$addQueryString = array();
					$addQueryString[$itemTable->type] = intval($row['uid']);

					if (is_array($extArray) && is_array($extArray[$basketObj->getFuncTablename()]))	{
						$addQueryString['variants'] = htmlspecialchars($extArray[$basketObj->getFuncTablename()][0]['vars']);
					}
					$isImageProduct = $itemTable->hasAdditional($row,'isImage');
					$damMarkerArray = array();
					$damCategoryMarkerArray = array();

					if (($isImageProduct || $funcTablename == 'tt_products') && is_array($extArray) && is_array($extArray['tx_dam']))	{
						reset($extArray['tx_dam']);
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
							'',
							$damCat,
							$damRow['pid'],
							$this->config['limitImage'],
							'basketImage',
							$viewDamCatTagArray,
							array(),
							$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'],
							'SINGLE',
							1,
							'',
							''
						);

						$tablesObj->get('tx_dam',TRUE)->getModelMarkerArray (
							$damRow,
							'',
							$damMarkerArray,
							$damCatRow['title'],
							$this->config['limitImage'],
							'basketImage',
							$damViewTagArray,
							$tmp,
							$theCode,
							$count,
							'',
							'',
							'',
							$bHtml
						);
					}
					$markerArray = array_merge($markerArray, $damMarkerArray, $damCategoryMarkerArray);
					$tempUrl = htmlspecialchars($this->pibase->pi_getPageLink($pid, '', $this->urlObj->getLinkParams('', $addQueryString, TRUE, TRUE, ''),array('useCacheHash' => TRUE)));
					$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="' . $tempUrl . '"' . $css_current . '>', '</a>');
					if (is_object($itemTableView->variant))	{
						$itemTableView->variant->removeEmptyMarkerSubpartArray($markerArray,$subpartArray, $wrappedSubpartArray, $prodVariantRow, $this->conf, $itemTable->hasAdditional($row,'isSingle'), !$itemTable->hasAdditional($row,'noGiftService'));
					}

					// Substitute
					$feUsersViewObj->getModelObj()->setCondition($row, $funcTablename);
					$feUsersViewObj->getWrappedSubpartArray($subpartArray, $wrappedSubpartArray, $funcTablename);

					$tempContent = $this->cObj->substituteMarkerArrayCached(
						$t['item'],
						array(),
						$subpartArray,
						$wrappedSubpartArray
					);

					$tempContent = $this->cObj->substituteMarkerArray(
						$tempContent,
						$markerArray
					);

					$itemsOut .= $tempContent;
				}

				if ($itemsOut)	{
					$tempContent = $this->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
					$out .= $tempContent;
					$itemsOut='';	// Clear the item-code var
				}
			}
			if (isset ($damCatMarker))	{
				$damCatObj->marker = $damCatMarker; // restore original value
			}
			$subpartArray = array();
			$wrappedSubpartArray = array();

			$basketMarkerArray = $this->getMarkerArray();

				// Initializing the markerArray for the rest of the template
			$markerArray = array_merge($mainMarkerArray, $globalMarkerArray);
			$markerArray = array_merge($markerArray,$basketMarkerArray);

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
					$hiddenFields .= '<input type="hidden" name="' . TT_PRODUCTS_EXT . '[activity]['. $activity . ']" value="1" ' . ($bUseXHTML ? '/' : '') . '>';
				}
			}
			$markerArray['###HIDDENFIELDS###'] = $hiddenFields;
			$pid = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);

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
//

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


			$markerArray['###PRICE_SHIPPING_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['priceTax']['shipping']);
			$markerArray['###PRICE_SHIPPING_NO_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['priceNoTax']['shipping']);
			$markerArray['###PRICE_SHIPPING_ONLY_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['priceTax']['shipping']-$basketObj->calculatedArray['priceNoTax']['shipping']);

			$basketUrl = htmlspecialchars(tx_div2007_alpha5::getTypoLink_URL_fh003($this->cObj,$pid,$this->urlObj->getLinkParams('',array(),TRUE,TRUE,''),'',''));
			$markerArray['###SHIPPING_SELECTOR###'] = $paymentshippingObj->generateRadioSelect($theCode, 'shipping', $basketObj->calculatedArray, $basketUrl);
			$imageCode = $this->cObj->IMAGE($basketObj->basketExtra['shipping.']['image.']);
			if ($theCode == 'EMAIL') {
				tx_div2007_alpha5::fixImageCodeAbsRefPrefix($imageCode);
			}
			$markerArray['###SHIPPING_IMAGE###'] = $imageCode;

	//		$shippingMarkerArray = array();
	//		$itemTable->getItemMarkerArray ($this->basket->basketExtra['shipping.'], $markerArray, $fieldsArray);
	//		$this->paymentshipping->getItemMarkerArray ($this->basket->basketExtra['shipping.'], $shippingMarkerArray, '', array());
	//		$shippingTitle = $this->cObj->substituteMarkerArrayCached($this->basket->basketExtra['shipping.']['title'], $shippingMarkerArray);

			$shippingTitle = $basketObj->basketExtra['shipping.']['title'];
			$markerArray['###SHIPPING_TITLE###'] = htmlspecialchars($shippingTitle);
			$markerArray['###SHIPPING_WEIGHT###'] = doubleval($basketObj->calculatedArray['weight']);
			$markerArray['###DELIVERYCOSTS###'] = $priceViewObj->priceFormat($paymentshippingObj->getDeliveryCosts()); $priceViewObj->priceFormat($basketObj->calculatedArray['priceTax']['shipping'] + $basketObj->calculatedArray['priceTax']['payment']);

			//$markerArray['###PRICE_PAYMENT_PERCENT###'] = $perc;
			$markerArray['###PRICE_PAYMENT_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['priceTax']['payment']);
			$markerArray['###PRICE_PAYMENT_NO_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['priceNoTax']['payment']);
			$markerArray['###PRICE_PAYMENT_ONLY_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['priceTax']['payment']-$basketObj->calculatedArray['priceNoTax']['payment'] );
	/* Added els4: payment layout (used in basket_payment_template, winkelwagen.tmpl) */
			$markerArray['###PAYMENT_SELECTOR###'] = $paymentshippingObj->generateRadioSelect($theCode, 'payment', $basketObj->calculatedArray, $basketUrl);

			$imageCode = $this->cObj->IMAGE($basketObj->basketExtra['payment.']['image.']);
			if ($theCode == 'EMAIL') {
				tx_div2007_alpha5::fixImageCodeAbsRefPrefix($imageCode);
			}
			$markerArray['###PAYMENT_IMAGE###'] = $imageCode;
			$markerArray['###PAYMENT_TITLE###'] = $basketObj->basketExtra['payment.']['title'];
	/* Added els4: output for payment (used in basket_payment_template, winkelwagen.tmpl)*/
			$markerArray['###PAYMENT_NUMBER###'] = htmlspecialchars(t3lib_div::_GP('payment_number'));
			$markerArray['###PAYMENT_NAME###'] = htmlspecialchars(t3lib_div::_GP('payment_name'));
			$markerArray['###PAYMENT_CITY###'] = htmlspecialchars(t3lib_div::_GP('payment_city'));
			// for receipt from DIBS script
			$markerArray['###TRANSACT_CODE###'] = htmlspecialchars(t3lib_div::_GP('transact'));

			$markerArray['###CUR_SYM###'] = ' '.$this->conf['currencySymbol'];
			$markerArray['###PRICE_TAX_DISCOUNT###'] = $markerArray['###PRICE_DISCOUNT_TAX###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['price0Tax']['goodstotal']-$basketObj->calculatedArray['priceTax']['goodstotal']);
			$markerArray['###PRICE_VAT###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['priceTax']['goodstotal']-$basketObj->calculatedArray['priceNoTax']['goodstotal']);
	/* Added els4: discount based on total units (without articles in kurkenshop), necessary in winkelwagen.tmpl */
			$markerArray['###PRICE_TOTUNITS_DISCOUNT###'] = $priceViewObj->priceFormat($sum_pricediscount_total_totunits);

			$orderViewObj = $tablesObj->get('sys_products_orders', TRUE);
			$orderViewObj->getBasketRecsMarkerArray($markerArray);
			$billdeliveryObj->getMarkerArray($markerArray, $basketObj->order['orderTrackingNo'], 'bill');
			$billdeliveryObj->getMarkerArray($markerArray, $basketObj->order['orderTrackingNo'], 'delivery');

				// URL
			$markerArray =  $this->urlObj->addURLMarkers(0, $markerArray);

	/* Added Els: below 3 lines moved from above */
				// This is the total for everything
		//	$basketObj->getCalculatedSums();

			$taxFromShipping = $paymentshippingObj->getReplaceTaxPercentage();
			$taxInclExcl = (isset($taxFromShipping) && is_double($taxFromShipping) && $taxFromShipping == 0 ? 'tax_zero' : 'tax_included');
			$markerArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? tx_div2007_alpha5::getLL_fh002($langObj, $taxInclExcl) : '');

	/* Added els6: do not execute the redeeming of the gift certificate if template = OVERVIEW */
			if ($subpartMarker != 'BASKET_OVERVIEW_TEMPLATE') {

	// Added Franz: GIFT CERTIFICATE
				$markerArray['###GIFT_CERTIFICATE_UNIQUE_NUMBER_NAME###']='recs[tt_products][giftcode]'; // deprecated
				$markerArray['###FORM_NAME###']='BasketForm';
				$markerArray['###FORM_NAME_GIFT_CERTIFICATE###']='BasketGiftForm';

	/* Added els5: markerarrays for gift certificates */
	/* Added Els6: routine for redeeming the gift certificate (other way then proposed by Franz */
				$markerArray['###INSERT_GIFTCODE###'] = 'recs[tt_products][giftcode]';
				$markerArray['###VALUE_GIFTCODE###'] = htmlspecialchars($basketObj->recs['tt_products']['giftcode']);
				$cpArray = $TSFE->fe_user->getKey('ses','cp');
				$creditpointsGifts = $cpArray['gift']['amount'];
				$markerArray['###CREDITPOINTS_GIFTS###'] = $creditpointsGifts;

				if ($basketObj->recs['tt_products']['giftcode'] == '') {
					$subpartArray['###SUB_GIFTCODE_DISCOUNT###'] = '';
					$subpartArray['###SUB_GIFTCODE_DISCOUNTWRONG###'] = '';
					if ($creditpointsGifts == '') {
						$subpartArray['###SUB_GIFTCODE_DISCOUNT_TRUE###'] = '';
					}
				} else {
					$uniqueId = t3lib_div::trimExplode ('-', $basketObj->recs['tt_products']['giftcode'], TRUE);
					$query='uid=\'' . intval($uniqueId[0]) . '\' AND crdate=\'' . intval($uniqueId[1]) . '\'';
					$giftRes = $TYPO3_DB->exec_SELECTquery('*', 'tt_products_gifts', $query);
					$row = $TYPO3_DB->sql_fetch_assoc($giftRes);
					$TYPO3_DB->sql_free_result($giftRes);
					$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);
					$creditpointsDiscount = $creditpointsGifts * $pricefactor;
					$markerArray['###GIFT_DISCOUNT###'] = $creditpointsDiscount;
					$markerArray['###VALUE_GIFTCODE_USED###'] = htmlspecialchars($basketObj->recs['tt_products']['giftcode']);

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

// #### START
			$pricefactor = doubleval($this->conf['creditpoints.']['priceprod']);
 			$autoCreditpointsTotal = $creditpointsObj->getBasketTotal();
 			$markerArray['###AUTOCREDITPOINTS_TOTAL###'] = number_format($autoCreditpointsTotal,'0');
 			$markerArray['###AUTOCREDITPOINTS_PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat($autoCreditpointsTotal * $pricefactor);
			$markerArray['###CREDITPOINTS_AVAILABLE###'] = number_format($TSFE->fe_user->user['tt_products_creditpoints'],'0');
 			$markerArray['###USERCREDITPOINTS_PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat(($autoCreditpointsTotal < $amountCreditpoints ? $autoCreditpointsTotal : $amountCreditpoints) * $pricefactor);

			// maximum1 amount of creditpoint to change is amount on account minus amount already spended in the credit-shop

			$creditpoints = $autoCreditpointsTotal + $sum_pricecreditpoints_total_totunits * tx_ttproducts_creditpoints_div::getCreditPoints($sum_pricecreditpoints_total_totunits, $this->conf['creditpoints.']);

// #### ENDE

			// maximum1 amount of creditpoint to change is amount on account minus amount already spended in the credit-shop
			$max1_creditpoints = $TSFE->fe_user->user['tt_products_creditpoints'] + $creditpointsGifts;
			// maximum2 amount of creditpoint to change is amount bought multiplied with creditpointfactor
			$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);

			if ($pricefactor > 0) {
				$max2_creditpoints = intval (($basketObj->calculatedArray['priceTax']['total'] - $basketObj->calculatedArray['priceTax']['vouchertotal']) / $pricefactor );
			}
			// real maximum amount of creditpoint to change is minimum of both maximums
			$markerArray['###AMOUNT_CREDITPOINTS_MAX###'] = number_format( min ($max1_creditpoints, $max2_creditpoints), 0);

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
			if ($basketObj->recs['tt_products']['creditpoints'] == '') {
				$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = 0;
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
	/* Added Els8: put credit_discount 0 for plain text email */
				$markerArray['###CREDIT_DISCOUNT###'] = '0.00';
			} else {
				// quantity chosen can not be larger than the maximum amount, above calculated
				if ($basketObj->recs['tt_products']['creditpoints'] > min ($max1_creditpoints, $max2_creditpoints))	{
					$basketObj->recs['tt_products']['creditpoints'] = min ($max1_creditpoints, $max2_creditpoints);
				}
				// $basketObj->calculatedArray['priceTax']['creditpoints'] = $basketObj->recs['tt_products']['creditpoints']*$pricefactor;
				$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = htmlspecialchars($basketObj->recs['tt_products']['creditpoints']);
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
				$markerArray['###CREDIT_DISCOUNT###'] = $priceViewObj->priceFormat($basketObj->calculatedArray['priceTax']['creditpoints']);
			}

	/* Added els5: CREDITPOINTS_SPENDED: creditpoint needed, check if user has this amount of creditpoints on his account (winkelwagen.tmpl), only if user has logged in */
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
			$minPriceSuccess = TRUE;
			if ($basketObj->checkMinPrice && $basketConf['type'] == 'price')	{
				$value = $basketObj->calculatedArray['priceTax'][$basketConf['collect']];
				if (isset($value) && isset($basketConf['collect']) && $value < doubleval($basketConf['value']))	{
					$subpartArray['###MESSAGE_MINPRICE###'] = '';
					$tmpSubpart = $this->cObj->getSubpart($t['basketFrameWork'],'###MESSAGE_MINPRICE_ERROR###');
					$subpartArray['###MESSAGE_MINPRICE_ERROR###'] = $this->cObj->substituteMarkerArray($tmpSubpart, $markerArray);
					$minPriceSuccess = FALSE;
				}
			}
			if ($minPriceSuccess)	{
				$subpartArray['###MESSAGE_MINPRICE_ERROR###'] = '';
				$tmpSubpart = $this->cObj->getSubpart($t['basketFrameWork'],'###MESSAGE_MINPRICE###');
				$subpartArray['###MESSAGE_MINPRICE###'] = $this->cObj->substituteMarkerArray($tmpSubpart, $markerArray);
			}

			if (count($minQuantityArray))	{
				$subpartArray['###MESSAGE_MINQUANTITY###'] = '';
				$tmpSubpart = $this->cObj->getSubpart($t['basketFrameWork'],'###MESSAGE_MINQUANTITY_ERROR###');
					//	$minQuantityArray[] = array('rec' => $row, 'minQuantity' => $minQuantity, 'quantity' => $quantity);
				$errorObj = t3lib_div::getUserObj('&tx_ttproducts_model_error');
				$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');
				$error_code = array();
				$error_code[0] = 'error_minquantity';
				$error_code[1] = '';

				foreach ($minQuantityArray as $minQuantityRow)	{
					$error_code[1] .= $minQuantityRow['rec']['title'] . ':' . $minQuantityRow['quantity'] . '&lt;' . $minQuantityRow['minQuantity'];
				}
				$errorOut = $errorObj->getMessage($error_code, $langObj);
				$markerArray['###ERROR_MINQUANTITY###'] = $errorOut;
				$subpartArray['###MESSAGE_MINQUANTITY_ERROR###'] = $this->cObj->substituteMarkerArray($tmpSubpart, $markerArray);
			} else {
				$subpartArray['###MESSAGE_MINQUANTITY_ERROR###'] = '';
				$tmpSubpart = $this->cObj->getSubpart($t['basketFrameWork'],'###MESSAGE_MINQUANTITY###');
				$subpartArray['###MESSAGE_MINQUANTITY###'] = $this->cObj->substituteMarkerArray($tmpSubpart, $markerArray);
			}
			if (count($minQuantityArray) || !$minPriceSuccess)	{
				$subpartArray['###MESSAGE_NO_ERROR###'] = '';
			} else {
				$subpartArray['###MESSAGE_ERROR###'] = '';
			}
			$voucherView = $tablesObj->get('voucher', TRUE);
			$voucherView->getsubpartMarkerArray($subpartArray, $wrappedSubpartArray);
			$voucherView->getMarkerArray($markerArray);
			$markerArray['###CREDITPOINTS_SAVED###'] = number_format($creditpoints, '0');
			$agb_url = array();
			$pidagb = intval($this->conf['PIDagb']);
			$addQueryString = array();

			// $addQueryString['id'] = $pidagb;
			if ($TSFE->type)	{
				$addQueryString['type'] = $TSFE->type;
			}
			$singleExcludeList = $this->urlObj->getSingleExcludeList('');

			$wrappedSubpartArray['###LINK_AGB###'] = array(
				'<a href="'. htmlspecialchars($this->pibase->pi_getPageLink($pidagb, '', $this->urlObj->getLinkParams($singleExcludeList, $addQueryString, TRUE, TRUE, ''))) .'" target="' . $this->conf['AGBtarget'] . '">',
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
							$bUseBackPid,
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
				$infoObj->getRowMarkerArray($markerArray, $bHtml, $bSelectSalutation);
			}

			$fieldsTempArray = $markerObj->getMarkerFields(
				$t['basketFrameWork'],
				$itemTable->getTableObj()->tableFieldArray,
				$itemTable->getTableObj()->requiredFieldArray,
				$markerFieldArray,
				$itemTable->marker,
				$viewTagArray,
				$parentArray
			);

			$priceCalcMarkerArray = array(
				'PRICE_TOTAL_TAX' => $basketObj->calculatedArray['priceTax']['total'],
				'PRICE_TOTAL_NO_TAX' => $basketObj->calculatedArray['priceNoTax']['total'],
				'PRICE_TOTAL_0_TAX' => $basketObj->calculatedArray['price0Tax']['total'],
				'PRICE_TOTAL_ONLY_TAX' => $basketObj->calculatedArray['priceTax']['total'] - $basketObj->calculatedArray['priceNoTax']['total'],
				'PRICE_VOUCHERTOTAL_TAX' => $basketObj->calculatedArray['priceTax']['vouchertotal'],
				'PRICE_VOUCHERTOTAL_NO_TAX' => $basketObj->calculatedArray['priceNoTax']['vouchertotal'],
				'PRICE_TOTAL_TAX_WITHOUT_PAYMENT' => $basketObj->calculatedArray['priceTax']['total'] - $basketObj->calculatedArray['priceTax']['payment'],
				'PRICE_TOTAL_NO_TAX_WITHOUT_PAYMENT' => $basketObj->calculatedArray['priceNoTax']['total'] - $basketObj->calculatedArray['priceNoTax']['payment'],
				'PRICE_TOTAL_TAX_CENT' => intval(round(100 * $basketObj->calculatedArray['priceTax']['total'])),
				'PRICE_VOUCHERTOTAL_TAX_CENT' => intval(round(100 * $basketObj->calculatedArray['priceTax']['vouchertotal']))
			);

			foreach ($priceCalcMarkerArray as $markerKey => $value)	{
				$markerArray['###'.$markerKey.'###'] = (is_int($value) ? $value : $priceViewObj->priceFormat($value));
			}

			$variantFieldArray = array();
			$variantMarkerArray = array();
			$staticTaxViewObj = $tablesObj->get('static_taxes', TRUE);
			$staticTaxObj = $staticTaxViewObj->getModelObj();

			if ($staticTaxObj->isInstalled())	{

				$allTaxesArray = $staticTaxObj->getAllTaxesArray();
				$bUseTaxArray = FALSE;
				$viewTaxTagArray = array();
				$parentArray = array();
				$markerFieldArray = array();

				$fieldsArray = $markerObj->getMarkerFields(
					$t['basketFrameWork'],
					$staticTaxObj->getTableObj()->tableFieldArray,
					$staticTaxObj->getTableObj()->requiredFieldArray,
					$markerFieldArray,
					$staticTaxObj->marker,
					$viewTaxTagArray,
					$parentArray
				);

				if (isset($allTaxesArray) && is_array($allTaxesArray))	{
					if (count($allTaxesArray))	{
						$bUseTaxArray = TRUE;
						foreach ($allTaxesArray as $taxId => $taxArray)	{
							foreach ($taxArray as $k => $taxRow)	{
								$theTax = $taxRow['tx_rate'] * 0.01;
								$staticTaxViewObj->getRowMarkerArray (
									$taxRow,
									'STATICTAX_'.($taxId).'_'.($k+1),
									$markerArray,
									$variantFieldArray,
									$variantMarkerArray,
									$viewTagArray,
									$theCode,
									$bHtml,
									$charset,
									0,
									'',
									$id,
									$prefix, // if FALSE, then no table marker will be added
									$suffix,
									''
								);
								$calculatedArray = array();
								$calculatedArray['priceNoTax'] = $basketObj->calculatedArray['priceNoTax']['total'];
								$calculatedArray['priceTax'] = $calculatedArray['priceNoTax'] * (1 + $theTax);
								$calculatedArray['onlyTax'] = $calculatedArray['priceTax'] - $calculatedArray['priceNoTax'];
								$priceCalcMarkerArray2 = array(
									'PRICE_TOTAL_ONLY_TAX' => $calculatedArray['onlyTax']
								);

								foreach ($priceCalcMarkerArray2 as $markerKey => $value)	{
									$markerArray['###STATICTAX_' . ($taxId) . '_' . ($k + 1) . '_' . $markerKey . '###'] = $priceViewObj->priceFormat($value);
								}
							}
						}
					}
				}
				if (!$bUseTaxArray)	{
					$staticTaxViewObj->getItemSubpartArrays(
						$templateCode,
						$staticTaxObj->getFuncTablename(),
						$tmp=array(),
						$subpartArray,
						$wrappedSubpartArray,
						$viewTaxTagArray,
						$theCode,
						''
					);
				}
				foreach ($viewTagArray as $theTag => $v1)	{
					if (!isset($markerArray['###'.$theTag.'###']))	{
						foreach ($priceCalcMarkerArray as $markerKey => $value)	{
							if (strpos($theTag,$markerKey) !== FALSE)	{
								$markerArray['###'.$theTag.'###'] = '';
							}
						}
						if (strpos($theTag,'STATICTAX_') === 0)	{
							$markerArray['###'.$theTag.'###'] = '';
						}
					}
				}
			}

				// Call all getItemMarkerArrays hooks at the end of this method
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getBasketView'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getBasketView'] as $classRef) {
					$hookObj= t3lib_div::getUserObj($classRef);
					if (method_exists($hookObj, 'getMarkerArrays')) {
						$hookObj->getMarkerArrays ($this, $templateCode, $theCode, $markerArray, $subpartArray, $wrappedSubpartArray, $mainMarkerArray, $count);
					}
				}
			}

			$pidListObj = $basketObj->getPidListObj();
			$relatedListView = t3lib_div::getUserObj('&tx_ttproducts_relatedlist_view');
			$relatedListView->init($this->cObj, $pidListObj->getPidlist(), $pidListObj->getRecursive());
			$relatedMarkerArray = $relatedListView->getListMarkerArray(
				$theCode,
				$this->pibaseClass,
				$templateCode,
				$markerArray,
				$viewTagArray,
				$funcTablename,
				$basketObj->getUidArray(),
				array(),
				$this->useArticles,
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'],
				$TSFE->id,
				$this->error_code
			);
			if ($relatedMarkerArray && is_array($relatedMarkerArray)) {
				$markerArray = array_merge($markerArray, $relatedMarkerArray);
			}
			$frameWork = $this->cObj->substituteSubpart($t['basketFrameWork'], '###ITEM_CATEGORY_AND_ITEMS###', $out);
			$paymentshippingObj->getSubpartArrays($markerArray, $subpartArray, $wrappedSubpartArray, $frameWork);
			$feUsersViewObj->getWrappedSubpartArray($subpartArray, $wrappedSubpartArray, $funcTablename, $bUseBackPid);
				// substitute the main subpart with the rendered content.

			$frameWork =
				$this->cObj->substituteMarkerArray(
					$frameWork,
					$markerArray
				); // workaround for TYPO3 bug

				// substitute the main subpart with the rendered content.
			$out =
				$this->cObj->substituteMarkerArrayCached(
					$frameWork,
					array(),
					$subpartArray,
					$wrappedSubpartArray
				);

		} // if ($t['basketFrameWork'])

		return $out;
	} // getView
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_basket_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_basket_view.php']);
}

?>