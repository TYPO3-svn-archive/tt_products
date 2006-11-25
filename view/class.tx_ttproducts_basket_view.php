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
 * Part of the tt_products (Shop System) extension.
 *
 * basket functions for a basket object
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Els Verberne <verberne@bendoo.nl>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_creditpoints_div.php');



class tx_ttproducts_basket_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	
	var $page;
	var $tt_content; // element of class tx_table_db
	var $tt_products; // element of class tx_table_db
	var $tt_products_articles; // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db
	var $fe_users; // element of class tx_table_db
	var $price; // price object

	var $paymentshipping; 			// object of the type tx_ttproducts_paymentshipping

	var $basket; 	// the basket object
	var $templateCode='';				// In init(), set to the content of the templateFile. Used by default in getView()

	var $marker; // marker functions
	var $viewTable;

	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
 	 */

	function init(&$basket, &$templateCode )	{
 		$this->pibase = &$basket->pibase;
 		$this->cnf = &$basket->cnf;
 		$this->conf = &$this->cnf->conf;
 		$this->config = &$this->cnf->config;
 		$this->basket = &$basket;
 		$this->page = &$basket->page;
 		$this->tt_content = &$basket->tt_content;
 		$this->tt_products = &$basket->tt_products;
 		$this->tt_products_articles = &$basket->tt_products_articles;
 		$this->tt_products_cat = &$basket->tt_products_cat;
 		$this->fe_users = &$basket->fe_users;
 		$this->viewTable = &$basket->viewTable;
 		$this->price = &$basket->price;
		$this->paymentshipping = &$basket->paymentshipping;
 		$this->templateCode = &$templateCode;
 
		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($this->pibase, $this->cnf, $this->basket);
	} // init




	/**
	 * This generates the shopping basket layout and also calculates the totals. Very important function.
	 */
	function getView(&$templateCode, $code, &$address, $bSelectSalutation, $bSelectVariants, $subpartMarker='###BASKET_TEMPLATE###', $mainMarkerArray=array())	{
			/*
				Very central function in the library.
				By default it extracts the subpart, ###BASKET_TEMPLATE###, from the $templateCode (if given, else the default $this->templateCode)
				and substitutes a lot of fields and subparts.
				Any pre-preparred fields can be set in $mainMarkerArray, which is substituted in the subpart before the item-and-categories part is substituted.
			*/

		global $TSFE, $TCA;
		global $TYPO3_DB, $TYPO3_CONF_VARS;

		if (!$templateCode)	{
			$templateCode = &$this->templateCode;		
		}

			// Getting subparts from the template code.
		$t=array();
		$t['basketFrameWork'] = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker($subpartMarker));

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
			'LINK_DATASHEET' => 'datasheet');
		$parentArray = array();
		$fieldsArray = $this->marker->getMarkerFields(
			$t['item'],
			$this->viewTable->table->tableFieldArray,
			$this->viewTable->table->requiredFieldArray,
			$markerFieldArray,
			$this->viewTable->marker,
			$viewTagArray,
			$parentArray
		);
		
		$count = 0;
		$basketItemView = '';

		// loop over all items in the basket indexed by sorting text
		foreach ($this->basket->itemArray as $sort=>$actItemArray) {
			foreach ($actItemArray as $k1=>$actItem) {
				$row = &$actItem['rec'];
				$pid = intval($row['pid']);
				if (!isset($this->page->pageArray[$pid]))	{
					// product belongs to another basket	
					continue;
				}
				$count++;
				$pidcategory = ($this->pibase->pageAsCategory == 1 ? $pid : '');
				$currentPnew = $pidcategory.'_'.$actItem['rec']['category'];
					// Print Category Title
				if ($currentPnew!=$currentP)	{
					if ($itemsOut)	{
						$out .= $this->pibase->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
					}
					$itemsOut = '';			// Clear the item-code var
					$currentP = $currentPnew;
					if ($this->conf['displayBasketCatHeader'])	{
						$markerArray=array();
						$pageCatTitle = '';
						if ($this->pibase->pageAsCategory == 1) {
							$pageTmp = $this->page->get($pid);
							$pageCatTitle = $pageTmp['title'].'/';
						}
						$catTmp = '';
						if ($actItem['rec']['category']) {
							$catTmp = $this->tt_products_cat->get($actItem['rec']['category']);
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
					$basketItemView = &t3lib_div::getUserObj('tx_ttproducts_basketitem_view');
					$basketItemView->init($this->tt_products_cat, $this->basket->basketExt);
				}

				$basketItemView->getItemMarkerArray ($this->viewTable, $actItem, $markerArray, $this->basket->basketExt, $code, $count);
				$catRow = $actItem['rec']['category'] ? $this->tt_products_cat->get($actItem['rec']['category']) : array();
				// $catTitle= $actItem['rec']['category'] ? $this->tt_products_cat->get($actItem['rec']['category']) : '';
				$catTitle=$catRow['title'];
				$tmp = array();
				$this->viewTable->getItemMarkerArray ($actItem, $markerArray, $catTitle, $this->basket->basketExt, 1,'basketImage', $viewTagArray, $tmp, $code, $count);
				$markerArray['###PRODUCT_COLOR###'] = $actItem['rec']['color'];
				$markerArray['###PRODUCT_SIZE###'] = $actItem['rec']['size'];
				$markerArray['###PRODUCT_DESCRIPTION###'] = $actItem['rec']['description'];
				$markerArray['###PRODUCT_GRADINGS###'] = $actItem['rec']['gradings'];
				//$markerArray['###PRODUCT_ADDITIONAL###'] = $actItem['rec']['additional'];

				$this->pibase->cObj->setCurrentVal($catTitle);
				$markerArray['###CATEGORY_TITLE###'] = $this->pibase->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
				$markerArray['###PRICE_TOTAL_TAX###'] = $this->price->priceFormat($actItem['totalTax']);
				$markerArray['###PRICE_TOTAL_NO_TAX###'] = $this->price->priceFormat($actItem['totalNoTax']);
				$markerArray['###PRICE_TOTAL_ONLY_TAX###'] = $this->price->priceFormat($actItem['totalTax']-$actItem['totalNoTax']);

/* Added els4: calculating of price_discount necessary in winkelwagen.tmpl (articles in kurkenshop are excluded, because these articled will be payed with creditpoints) */
				if ( ($actItem['rec']['price'] != '0.00') && doubleval($actItem['rec']['price2']) && ($actItem['rec']['category'] != $this->conf['creditsCategory']) ) {
					$pricediscount_total_tot_units = "";

/* Added els7: different calculation of pricediscount_total_tot_units */
//						$oldprice_total_tot_units = ($actItem['totalNoTax']/$actItem['rec']['price2'])*$actItem['rec']['price'];
//						$pricediscount_total_tot_units = ($oldprice_total_tot_units - $actItem['totalNoTax']) * $actItem['rec']['unit_factor'];
					$pricediscount_total_tot_units = ($actItem['rec']['price'] - $actItem['rec']['price2']) * $actItem['rec']['unit_factor'] * $actItem['count'];
					$sum_pricediscount_total_totunits += $pricediscount_total_tot_units;
				}

/* Added els4: TOTUNITS_: both prices mulitplied by unit_factor and third line is calculating the sum, necessary in winkelwagen.tmpl. All articles in kurkenshop are payed with creditpoints*/
				$markerArray['###PRICE_TOTAL_TOTUNITS_TAX###'] = $this->price->priceFormat($actItem['totalTax']*$actItem['rec']['unit_factor']);
				if ($actItem['rec']['category'] == $this->conf['creditsCategory']) {
/* Added els7: different calculation of PRICECREDITS_TOTAL_TOTUNITS_NO_TAX */
//						$markerArray['###PRICECREDITS_TOTAL_TOTUNITS_NO_TAX###']=$this->price->priceFormat($actItem['totalNoTax']*$actItem['rec']['unit_factor']);
					$markerArray['###PRICECREDITS_TOTAL_TOTUNITS_NO_TAX###'] = $this->price->priceFormat($actItem['rec']['price2']*$actItem['rec']['unit_factor']) * $actItem['count'];
				} else {
/* Added els7: different calculation of PRICECREDITS_TOTAL_TOTUNITS_NO_TAX */
//						$markerArray['###PRICE_TOTAL_TOTUNITS_NO_TAX###']=$actItem['totalNoTax']*$actItem['rec']['unit_factor'];
					$markerArray['###PRICE_TOTAL_TOTUNITS_NO_TAX###'] = $actItem['rec']['price2']*$actItem['rec']['unit_factor'] * $actItem['count'];
				}

				$sum_pricecredits_total_totunits_no_tax += $markerArray['###PRICECREDITS_TOTAL_TOTUNITS_NO_TAX###'];
				$sum_price_total_totunits_no_tax += $markerArray['###PRICE_TOTAL_TOTUNITS_NO_TAX###'];

/* Added els4: calculating of price_creditpoints necessary in winkelwagen.tmpl, only if article contains special_prep then one can gains creditpoints */
				if ($actItem['rec']['special_preparation'] != '0.00') {
					$sum_pricecreditpoints_total_totunits += $markerArray['###PRICE_TOTAL_TOTUNITS_NO_TAX###'];
				}

				$pid = $this->page->getPID($this->conf['PIDitemDisplay'], $this->conf['PIDitemDisplay.'], $actItem['rec'], $TSFE->rootLine[1]);
				$splitMark = md5(microtime());
				$addQueryString=array();
				$addQueryString[$this->pibase->prefixId.'['.$this->viewTable->type.']'] = intval($actItem['rec']['uid']);
				$addQueryString[$this->pibase->prefixId.'[variants]'] = htmlspecialchars($actItem['rec']['extVars']);
				// $addQueryString['ttp_extvars'] = htmlspecialchars($actItem['rec']['extVars']);
				$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'. $this->pibase->pi_getPageLink($pid,'',$this->marker->getLinkParams('', $addQueryString, true)).'"'.$css_current.'>','</a>'); 

				// Substitute
				$tempContent = $this->pibase->cObj->substituteMarkerArrayCached($t['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
				$this->viewTable->variant->getVariantSubpartArray ($subpartArray, $actItem['rec'], $tempContent, 
					$bSelectVariants, $this->conf );
				$this->basket->fe_users->getWrappedSubpartArray($subpartArray, $wrappedSubpartArray);
				$tempContent = $this->pibase->cObj->substituteMarkerArrayCached($tempContent,$markerArray,$subpartArray,$wrappedSubpartArray);
				$itemsOut .= $tempContent;
			}
			if ($itemsOut)	{
				$tempContent=$this->pibase->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
				$out .= $tempContent;
				$itemsOut='';			// Clear the item-code var
			}
		}

		$subpartArray = array();
		$wrappedSubpartArray = array();

			// Initializing the markerArray for the rest of the template
		$markerArray=$mainMarkerArray;

			// This is the total for the goods in the basket.
		$markerArray['###PRICE_GOODSTOTAL_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['goodstotal']);
		$markerArray['###PRICE_GOODSTOTAL_NO_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceNoTax']['goodstotal']);
		$markerArray['###PRICE_GOODSTOTAL_ONLY_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['goodstotal']-$this->basket->calculatedArray['priceNoTax']['goodstotal']);

		$markerArray['###PRICE2_GOODSTOTAL_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['price2Tax']['goodstotal']);
		$markerArray['###PRICE2_GOODSTOTAL_NO_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['price2NoTax']['goodstotal']);
		$markerArray['###PRICE2_GOODSTOTAL_ONLY_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['price2Tax']['goodstotal']-$this->basket->calculatedArray['price2NoTax']['goodstotal']);

		// This is for the Basketoverview
		$markerArray['###NUMBER_GOODSTOTAL###'] = $this->basket->calculatedArray['count'];
		$markerArray['###IMAGE_BASKET###'] = '<img src="'.$this->conf['basketPic'].'">';

		$splitMark = md5(microtime());
		$pid = ( $this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);
		$tempUrl = $this->pibase->pi_linkToPage($splitMark,$pid,'',$this->marker->getLinkParams());
		$wrappedSubpartArray['###LINK_BASKET###'] = explode ($splitMark, $tempUrl);
		//$wrappedSubpartArray['###LINK_BASKET###']= array('<a href="'.$this->getLinkUrl($this->conf['PIDbasket']).'">','</a>');

		//$markerArray['###PRICE_SHIPPING_PERCENT###'] = $perc;
		$markerArray['###PRICE_SHIPPING_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['shipping']);
		$markerArray['###PRICE_SHIPPING_NO_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceNoTax']['shipping']);
		$markerArray['###PRICE_SHIPPING_ONLY_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['shipping']-$this->basket->calculatedArray['priceNoTax']['shipping']);

		$markerArray['###SHIPPING_SELECTOR###'] = $this->paymentshipping->generateRadioSelect('shipping', $this->basket->calculatedArray);
		$markerArray['###SHIPPING_IMAGE###'] = $this->pibase->cObj->IMAGE($this->basket->basketExtra['shipping.']['image.']);
		
//		$shippingMarkerArray = array();
//		$itemTable->getItemMarkerArray ($this->basket->basketExtra['shipping.'], $markerArray, $fieldsArray);
//		$this->paymentshipping->getItemMarkerArray ($this->basket->basketExtra['shipping.'], $shippingMarkerArray, '', array());
//		$shippingTitle = $this->pibase->cObj->substituteMarkerArrayCached($this->basket->basketExtra['shipping.']['title'], $shippingMarkerArray);

		$shippingTitle = $this->basket->basketExtra['shipping.']['title'];
		$markerArray['###SHIPPING_TITLE###'] = $shippingTitle;
		$markerArray['###SHIPPING_WEIGHT###'] = doubleval($this->basket->calculatedArray['weight']);
		$markerArray['###DELIVERYCOSTS###']=$this->price->priceFormat($this->basket->calculatedArray['priceTax']['shipping'] + $this->basket->calculatedArray['priceTax']['payment']);

		//$markerArray['###PRICE_PAYMENT_PERCENT###'] = $perc;
		$markerArray['###PRICE_PAYMENT_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['payment']);
		$markerArray['###PRICE_PAYMENT_NO_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceNoTax']['payment']);
		$markerArray['###PRICE_PAYMENT_ONLY_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['payment']-$this->basket->calculatedArray['priceNoTax']['payment'] );
		
/* Added els4: payment layout (used in basket_payment_template, winkelwagen.tmpl) */
		$markerArray['###PAYMENT_SELECTOR###'] = $this->paymentshipping->generateRadioSelect('payment', $this->basket->calculatedArray);
		$markerArray['###PAYMENT_IMAGE###'] = $this->pibase->cObj->IMAGE($this->basket->basketExtra['payment.']['image.']);
		$markerArray['###PAYMENT_TITLE###'] = $this->basket->basketExtra['payment.']['title'];

			// Call all getItemMarkerArrays hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['getBasketView'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['getBasketView'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getBasketView')) {
					$hookObj->getItemMarkerArrays ($this, $templateCode, $code, $markerArray,$subpartArray,$wrappedSubpartArray, $code, $mainMarkerArray, $count);
				}
			}
		}

///* Added els4: output for payment (used in basket_payment_template, winkelwagen.tmpl)*/
//		$markerArray['###PAYMENT_NUMBER###'] = t3lib_div::_GP('payment_number');
//		$markerArray['###PAYMENT_NAME###'] = t3lib_div::_GP('payment_name');
//		$markerArray['###PAYMENT_CITY###'] = t3lib_div::_GP('payment_city');

		// for receipt from DIBS script
		$markerArray['###TRANSACT_CODE###'] = t3lib_div::_GP('transact');

			// Fill the Currency Symbol or not
		if ($this->conf['showcurSymbol']) {
			$markerArray['###CUR_SYM###'] = ' '.$this->conf['currencySymbol'];
		} else {
			$markerArray['###CUR_SYM###'] = '';
		}

/* Added Els: below 3 lines after creditpoint calculation! */
		// This is the total for everything
//		$this->getCalculatedSums();
//		$markerArray['###PRICE_TOTAL_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['total']);
//		$markerArray['###PRICE_TOTAL_NO_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceNoTax']['total']);
/* Added Els: marker PRICE_DISCOUNT += uw voordeel (PRICE_GOODSTOTAL_NO_TAX-oldPriceNoTax) and PRICE_VAT = BTW (= ['priceTax']['goodstotal'] - ['priceNoTax']['goodstotal']*/
		$markerArray['###PRICE_DISCOUNT###'] = $this->price->priceFormat($this->basket->calculatedArray['oldPriceNoTax']-$this->basket->calculatedArray['priceNoTax']['goodstotal']);
		$markerArray['###PRICE_VAT###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['goodstotal']-$this->basket->calculatedArray['priceNoTax']['goodstotal']);

/* Added els4: discount based on total units (without articles in kurkenshop), necessary in winkelwagen.tmpl */
		$markerArray['###PRICE_TOTUNITS_DISCOUNT###'] = $this->price->priceFormat($sum_pricediscount_total_totunits);

		include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_order.php');
			// order
		$order = t3lib_div::makeInstance('tx_ttproducts_order');
		$order->init(
			$this->pibase,
			$this->cnf,
			$this->tt_products,
			$this->tt_products_articles,
			$this->tt_products_cat,
			$this->basket,
			$this->conf['useArticles']
		);
			// Order:	NOTE: Data exist only if the order->getBlankUid() has been called. Therefore this field in the template should be used only when an order has been established
		$markerArray['###ORDER_UID###'] = $order->getNumber($this->basket->recs['tt_products']['orderUid']);
		$markerArray['###ORDER_DATE###'] = $this->pibase->cObj->stdWrap($this->basket->recs['tt_products']['orderDate'],$this->conf['orderDate_stdWrap.']);
		$markerArray['###ORDER_TRACKING_NO###'] = $this->basket->recs['tt_products']['orderTrackingNo'];

			// URL
		$markerArray =  $this->marker->addURLMarkers(0, $markerArray);


/* Added els6: reorganized this part. First calculating amount in euros, then calculate voucher discount, then calcualte the creditpoints */

/* Added Els: below 3 lines moved from above */
			// This is the total for everything
		$this->basket->getCalculatedSums();
		$markerArray['###PRICE_TOTAL_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['total']);
		$markerArray['###PRICE_TOTAL_NO_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceNoTax']['total']);
		$markerArray['###PRICE_TOTAL_ONLY_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['total']-$this->basket->calculatedArray['priceNoTax']['total']);

		$taxFromShipping = $this->paymentshipping->getReplaceTAXpercentage();
		$taxInclExcl = (isset($taxFromShipping) && is_double($taxFromShipping) && $taxFromShipping == 0 ? 'tax_zero' : 'tax_included');
		$markerArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? $this->pibase->pi_getLL($taxInclExcl) : '');

/* Added els4: PRICE_GOODSTOTAL_TOTUNITS_NO_TAX: sum total price (winkelwagen.tmpl) */
		$markerArray['###PRICE_GOODSTOTAL_TOTUNITS_NO_TAX###'] = $this->price->priceFormat($sum_price_total_totunits_no_tax);

/* Added els8: shipping project meerwijn depends on subtotal amount, below shipping.NoTaxPrice, shipping price is shipping.fixprice, above no shiiping price */
		if ($markerArray['###PRICE_GOODSTOTAL_TOTUNITS_NO_TAX###'] <= $this->conf['shipping.']['NoTaxPrice']) {
			$markerArray['###PRICE_SHIPPING_NO_TAX###'] = $this->conf['shipping.']['fixprice'];
		}
//		} else { Franz: This is wrong here!
//			$markerArray['###PRICE_SHIPPING_NO_TAX###'] = '0.00';
//		}
		

/* Added Els: voucher marker inclusive conditions */
/* Added Els5: small changes in voucher marker inclusive conditions */
		if ($TSFE->fe_user->user['tt_products_vouchercode'] == '') {
			$subpartArray['###SUB_VOUCHERCODE###'] = '';
			$markerArray['###INSERT_VOUCHERCODE###'] = 'recs[tt_products][vouchercode]';
			$markerArray['###VALUE_VOUCHERCODE###'] = $this->basket->recs['tt_products']['vouchercode'];
			if ($this->basket->recs['tt_products']['vouchercode'] == '') {
				$subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = '';
				$subpartArray['###SUB_VOUCHERCODE_DISCOUNTOWNID###'] = '';
				$subpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = '';
			} else {
				$res = $TYPO3_DB->exec_SELECTquery('uid', 'fe_users', 'username="'.$this->basket->recs['tt_products']['vouchercode'].'"');
				if ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
					$uid_voucher = $row['uid'];
				}
				if ($uid_voucher != '') {
					// first check if not inserted own vouchercode
					if ($TSFE->fe_user->user['uid'] == $uid_voucher) {
						$subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = '';
						$subpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = '';
					} else {
						$subpartArray['###SUB_VOUCHERCODE_DISCOUNTOWNID###'] = '';
						$subpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = '';
						//$this->basket->calculatedArray['priceTax']['voucher'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['shipping']);
						$this->basket->calculatedArray['priceTax']['voucher'] = $this->conf['voucherPrice'];
						$markerArray['###VOUCHER_DISCOUNT###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['voucher']);
						$subpartArray['###SUB_VOUCHERCODE_NODISCOUNT###'] = '';
					}
				} else {
					$subpartArray['###SUB_VOUCHERCODE_DISCOUNTOWNID###'] = '';
					$subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = '';
				}
			}
		} else {
			$subpartArray['###SUB_VOUCHERCODE_EMPTY###'] = '';
/* Added Els8: put voucher_discount 0 for plain text email */
			$markerArray['###VOUCHER_DISCOUNT###'] = '0.00';
		}

/* Added Els: creditpoint inclusive conditions*/
/* Added Els6: more conditions on creditpoints calculations inclusive redeeming of gift certificates */

/* Added els6: do not execute the redeeming of the gift certificate if template = OVERVIEW */
		if ($subpartMarker != '###BASKET_OVERVIEW_TEMPLATE###') {

// Added Franz: GIFT CERTIFICATE
			$markerArray['###GIFT_CERTIFICATE_UNIQUE_NUMBER_NAME###']='recs[tt_products][gift_certificate_unique_number]';
			$markerArray['###FORM_NAME###']='BasketForm';
			$markerArray['###FORM_NAME_GIFT_CERTIFICATE###']='BasketGiftForm';

/* Added els5: markerarrays for gift certificates */
/* Added Els6: routine for redeeming the gift certificate (other way then proposed by Franz */
			$markerArray['###INSERT_GIFTCODE###'] = 'recs[tt_products][giftcode]';
			$markerArray['###VALUE_GIFTCODE###'] = $this->basket->recs['tt_products']['giftcode'];
			if ($this->basket->recs['tt_products']['giftcode'] == '') {
				$subpartArray['###SUB_GIFTCODE_DISCOUNT###'] = '';
				$subpartArray['###SUB_GIFTCODE_DISCOUNTWRONG###'] = '';
				if (t3lib_div::_GP('creditpoints_gifts') == '') {
					$markerArray['###CREDITPOINTS_GIFTS###'] = '';
					$subpartArray['###SUB_GIFTCODE_DISCOUNT_TRUE###'] = '';
				} else {
					$markerArray['###CREDITPOINTS_GIFTS###'] = t3lib_div::_GP('creditpoints_gifts');
				}
			} else {
				$uniqueId = t3lib_div::trimExplode ('-', $this->basket->recs['tt_products']['giftcode'], true);

				$query='uid=\''.intval($uniqueId[0]).'\' AND crdate=\''.$uniqueId[1].'\''.' AND NOT deleted' ;
				$giftRes = $TYPO3_DB->exec_SELECTquery('*', 'tt_products_gifts', $query);

				$row = $TYPO3_DB->sql_fetch_assoc($giftRes);
				$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);

				if ($row && $pricefactor > 0) {
					$money = $row['amount'];
					$uid = $row['uid'];
					$fieldsArray = array();
					$fieldsArray['deleted']=1;
						// Delete the gift record
					$TYPO3_DB->exec_UPDATEquery('tt_products_gifts', 'uid='.intval($uid), $fieldsArray);
					$creditpoints_gift = $money / $pricefactor;
					tx_ttproducts_creditpoints_div::addCreditPoints($TSFE->fe_user->user['username'], $creditpoints_gift);
					$markerArray['###GIFT_DISCOUNT###'] = $creditpoints_gift;
					$markerArray['###VALUE_GIFTCODE_USED###'] = $this->basket->recs['tt_products']['giftcode'];
					$markerArray['###VALUE_GIFTCODE###'] = '';
					$subpartArray['###SUB_GIFTCODE_DISCOUNTWRONG###']= '';
					if (t3lib_div::_GP('creditpoints_gifts') == '') {
						$markerArray['###CREDITPOINTS_GIFTS###'] = '';
						$subpartArray['###SUB_GIFTCODE_DISCOUNT_TRUE###'] = '';
					} else {
						$markerArray['###CREDITPOINTS_GIFTS###'] = t3lib_div::_GP('creditpoints_gifts');
					}
					$creditpoints_gift_tot = $markerArray['###CREDITPOINTS_GIFTS###'] + $creditpoints_gift;
					$markerArray['###CREDITPOINTS_GIFTS###'] = $creditpoints_gift_tot;
				} else {
					$subpartArray['###SUB_GIFTCODE_DISCOUNT###'] = '';
					if (t3lib_div::_GP('creditpoints_gifts') == '') {
						$markerArray['###CREDITPOINTS_GIFTS###'] = '';
						$subpartArray['###SUB_GIFTCODE_DISCOUNT_TRUE###'] = '';
					} else {
						$markerArray['###CREDITPOINTS_GIFTS###'] = t3lib_div::_GP('creditpoints_gifts');
					}
				}
			}
		}

		$markerArray['###AMOUNT_CREDITPOINTS###'] = $TSFE->fe_user->user['tt_products_creditpoints']+$creditpoints_gift;

		// maximum1 amount of creditpoint to change is amount on account minus amount already spended in the credit-shop
		$max1_creditpoints = $TSFE->fe_user->user['tt_products_creditpoints']+$creditpoints_gift - $sum_pricecredits_total_totunits_no_tax;
		// maximum2 amount of creditpoint to change is amount bought multiplied with creditpointfactor
		$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);
		if ($pricefactor > 0) {
			$max2_creditpoints = explode (".",($markerArray['###PRICE_GOODSTOTAL_TOTUNITS_NO_TAX###'] + $markerArray['###PRICE_SHIPPING_NO_TAX###'] - $markerArray['###VOUCHER_DISCOUNT###'])/$pricefactor );
		}
		// real maximum amount of creditpoint to change is minimum of both maximums
		$markerArray['###AMOUNT_CREDITPOINTS_MAX###'] = number_format( min ($max1_creditpoints,$max2_creditpoints[0]),0);

		// if quantity is 0 than
		if ($markerArray['###AMOUNT_CREDITPOINTS###'] == '0') {
			$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
			$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
			$subpartArray['###SUB_CREDITPOINTS_AMOUNT###'] = '';
		} else {
			$subpartArray['###SUB_CREDITPOINTS_AMOUNT_EMPTY###'] = '';
		}
		$markerArray['###CHANGE_AMOUNT_CREDITPOINTS###'] = 'recs[tt_products][creditpoints]';
		if ($this->basket->recs['tt_products']['creditpoints'] == '') {
			$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = 0;
			$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
/* Added Els8: put credit_discount 0 for plain text email */
			$markerArray['###CREDIT_DISCOUNT###'] = '0.00';
		} else {
			// quantity chosen can not be larger than the maximum amount, above calculated
			if ($this->basket->recs['tt_products']['creditpoints'] > number_format( min ($max1_creditpoints,$max2_creditpoints[0]),0)) $this->basket->recs['tt_products']['creditpoints'] = number_format( min ($max1_creditpoints,$max2_creditpoints[0]),0);
			$this->basket->calculatedArray['priceTax']['creditpoints'] = $this->price->priceFormat($this->basket->recs['tt_products']['creditpoints']*$pricefactor);
			$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = $this->basket->recs['tt_products']['creditpoints'];
			$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
			$markerArray['###CREDIT_DISCOUNT###'] = $this->basket->calculatedArray['priceTax']['creditpoints'];
		}

/* Added els5: CREDITPOINTS_SPENDED: creditpoint needed, check if user has this amount of creditpoints on his account (winkelwagen.tmpl), only if user has logged in */
		$markerArray['###CREDITPOINTS_SPENDED###'] = $sum_pricecredits_total_totunits_no_tax;
		if ($sum_pricecredits_total_totunits_no_tax <= $markerArray['###AMOUNT_CREDITPOINTS###']) {
			$subpartArray['###SUB_CREDITPOINTS_SPENDED_EMPTY###'] = '';
			$markerArray['###CREDITPOINTS_SPENDED###'] = $sum_pricecredits_total_totunits_no_tax;
			// new saldo: creditpoints
			$markerArray['###AMOUNT_CREDITPOINTS###'] = $markerArray['###AMOUNT_CREDITPOINTS###'] - $markerArray['###CREDITPOINTS_SPENDED###'];
		} else {
			if (!$markerArray['###FE_USER_UID###']) {
				$subpartArray['###SUB_CREDITPOINTS_SPENDED_EMPTY###'] = '';
			} else {
				$markerArray['###CREDITPOINTS_SPENDED_ERROR###'] = 'Wijzig de artikelen in de kurkenshop: onvoldoende kurken op uw saldo ('.$markerArray['###AMOUNT_CREDITPOINTS###'].').'; // TODO
				$markerArray['###CREDITPOINTS_SPENDED###'] = '&nbsp;';
			}
		}

		$creditpoints = tx_ttproducts_creditpoints_div::getCreditPoints($sum_pricecreditpoints_total_totunits);
		$markerArray['###CREDITPOINTS_SAVED###'] = number_format($creditpoints * $sum_pricecreditpoints_total_totunits,'0');
/* Added Els4: total price = subtotal - bezorgkosten + voucher + gift + giftcertificate (winkelwagen.tmpl) */
/* Added Els7: error in calcualtion */
		$markerArray['###PRICE_TOTAL_MEERWIJN###'] = $this->price->priceFormat($markerArray['###PRICE_GOODSTOTAL_TOTUNITS_NO_TAX###'] + $markerArray['###PRICE_SHIPPING_NO_TAX###'] - $markerArray['###VOUCHER_DISCOUNT###'] - $markerArray['###CREDIT_DISCOUNT###']);
		
		$agb_url=array();
		$pidagb = intval($this->conf['PIDagb']);
		// $addQueryString['id'] = $pidagb;
		if ($TSFE->type)	{
			$addQueryString['type'] = $TSFE->type;
		}
		$wrappedSubpartArray['###LINK_AGB###'] = array(
			'<a href="'. $this->pibase->pi_getPageLink($pidagb,'',$this->marker->getLinkParams('', $addQueryString, true)) .'" target="'.$this->conf['AGBtarget'].'">',
			'</a>'
		);
		
		// $wrappedSubpartArray['###LINK_AGB###']=array('<a href="'.$TSFE->absRefPrefix.'index.php?'.implode($agb_url,'&').'" target="'.$this->conf['agbtarget'].'">','</a>');

			// Final substitution:
		if (!$TSFE->loginUser)	{		// Remove section for FE_USERs only, if there are no fe_user
			$subpartArray['###FE_USER_SECTION###']='';
		}
		$bFrameWork = $t['basketFrameWork'];

		if (is_object($address))	{
			$address->getItemMarkerArray ($markerArray, $bSelectSalutation);
		}
		
		$shipKeyArray = $this->basket->basketExtra['shipping'];
		if (!is_array($shipKeyArray))	{
			$shipKeyArray = array($shipKeyArray);
		}
		$shipKey = '';
		$msgShipping = '';
		foreach ($shipKeyArray as $k => $value)	{
			if ($shipKey)	{
				$shipKey .= '_';
			}
			$shipKey .= $value;
			$tmpSubpart = $this->pibase->cObj->getSubpart($bFrameWork,'###MESSAGE_SHIPPING_'.$shipKey.'###');
			$msgShipping .= $this->pibase->cObj->substituteMarkerArrayCached($tmpSubpart,$markerArray);
		}
		$subpartArray['###MESSAGE_SHIPPING###'] = $msgShipping;
		
		$tmpSubpart = $this->pibase->cObj->getSubpart($bFrameWork,'###MESSAGE_PAYMENT_'.$this->basket->basketExtra['payment'].'###');
		$subpartArray['###MESSAGE_PAYMENT###'] = $this->pibase->cObj->substituteMarkerArrayCached($tmpSubpart,$markerArray);
		$this->basket->fe_users->getWrappedSubpartArray($subpartArray, $wrappedSubpartArray);

		$bFrameWork=$this->pibase->cObj->substituteMarkerArrayCached($t['basketFrameWork'],$markerArray,$subpartArray,$wrappedSubpartArray);

			// substitute the main subpart with the rendered content.
		$out=$this->pibase->cObj->substituteSubpart($bFrameWork, '###ITEM_CATEGORY_AND_ITEMS###', $out);
		return $out;
	} // getView


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_basket_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_basket_view.php']);
}


?>
