<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
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
 * bill and delivery functions
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_billdelivery {


	var $pibase;
	var $conf;		  // original configuration
	var $config;		// updated configuration
	var $basket;		// basket object
	var $tt_content;		// content object
	var $tt_products;		// object of the type tx_ttproducts
	var $tt_products_cat;		// object of the type tx_ttproducts_category
	var $order;
	var $price;		 // object for price functions
	var $type;		  // if bill or delivery
	var $marker; 	// marker functions

	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string	  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */

	function init(&$pibase, &$conf, &$config, &$basket, &$tt_products, &$tt_products_cat, &$tt_content, &$order, &$price, $type) {
	global $TSFE;

		$this->pibase = &$pibase;
		$this->conf = &$conf;
		$this->config = &$config;
		$this->basket = &$basket;
		$this->tt_products = &$tt_products;
		$this->tt_products_cat = &$tt_products_cat;
		$this->tt_content = &$tt_content;
		$this->order = &$order;
		$this->price = &$price;
		$this->type = $type;	
	
		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($pibase, $conf, $config, $basket);
	}


	/**
	 * Bill,Delivery Generation from tracking code
	 */
	function getInformation($orderRow, $templateCode, $tracking)
	{
		/*
		Bill or delivery information display, which needs tracking code to be shown
		This is extension information to tracking at another page
		See Tracking for further information
		*/
	global $TSFE;

		// initialize order data.
	$orderData = unserialize($orderRow['orderData']);

	$markerArray = array();
	$subpartArray = array();
	$wrappedSubpartArray = array();

	$tmp = $orderData['itemArray'];
	$itemArray = ($tmp ? $tmp : array());
	$tmp = $orderData['calculatedArray'];
	$calculatedArray = ($tmp ? $tmp : array());

	if ($this->type == 'bill') {
		$subpartMarker='###BILL_TEMPLATE###';
	} else {
		$subpartMarker='###DELIVERY_TEMPLATE###';
	}

		// Getting subparts from the template code.
	$t=array();
		// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
	$t['orderFrameWork'] = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker($subpartMarker));

	$t['categoryFrameWork'] = $this->pibase->cObj->getSubpart($t['orderFrameWork'],'###ITEM_CATEGORY###');
	$t['itemFrameWork'] = $this->pibase->cObj->getSubpart($t['orderFrameWork'],'###ITEM_LIST###');
	$t['item'] = $this->pibase->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');

	$categoryQty = array();
//	  $categoryPrice = array();
	$categoryArray = array();

//	  $countTotal = 0;
//
	// Calculate quantities for all categories

	// loop over all items in the ordered items indexed by page and itemnumber
	foreach ($itemArray as $pid=>$pidItem) {
		foreach ($pidItem as $itemnumber=>$actItemArray) {
			foreach ($actItemArray as $k1=>$actItem) {
				$currentCategory=$actItem['rec']['category'];
				$categoryArray[$currentCategory] = 1;
		//	  $countTotal += $actBasket['count'];
				$categoryQty[$currentCategory] += $actItem['count'];
		//	  $categoryPrice[$currentCategory] += doubleval($actBasket['priceTax']) * intval($actBasket['count']);
			}
		}
	}

	$itemsOut='';
	$out='';

	$itemTable = &$this->tt_products;
	$viewTagArray = array();
	$markerFieldArray = array('BULKILY_WARNING' => 'bulkily',
		'PRODUCT_SPECIAL_PREP' => 'special_preparation',
		'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
		'LINK_DATASHEET' => 'datasheet');	
	$fieldsArray = $this->marker->getMarkerFields(
		$t['item'],
		$itemTable->table->name,
		$itemTable->table->tableFieldArray,
		$itemTable->table->requiredFieldArray,
		$markerFieldArray,
		$itemTable->table->marker,
		$viewTagArray
	);

	foreach ($categoryArray as $currentCategory=>$value)	{
		$categoryChanged = 1;
		// loop over all orderd items indexed by page and itemnumber
		foreach ($itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
	
					// Print Category Title
				if ($actItem['rec']['category']==$currentCategory)
				{
					if ($categoryChanged == 1)	{
						$markerArray=array();
						$tmpCategory = $this->tt_products_cat->get($currentCategory);
						$catTitle= ($tmpCategory ? $tmpCategory['title']: '');
						$this->pibase->cObj->setCurrentVal($catTitle);
						$markerArray['###CATEGORY_TITLE###'] = $this->pibase->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
						$markerArray['###CATEGORY_QTY###'] = $categoryQty[$currentCategory];
						$categoryPriceTax = $calculatedArray['categoryPriceTax']['goodstotal'][$currentCategory];
						$markerArray['###PRICE_GOODS_TAX###'] = $this->price->priceFormat($categoryPriceTax);
						$categoryPriceNoTax = $calculatedArray['categoryPriceNoTax']['goodstotal'][$currentCategory];
						$markerArray['###PRICE_GOODS_NO_TAX###'] = $this->price->priceFormat($categoryPriceNoTax);
						$markerArray['###PRICE_GOODS_ONLY_TAX###'] = $this->price->priceFormat($categoryPriceTax - $categoryPriceNoTax);
						
						$out2 = $this->pibase->cObj->substituteMarkerArray($t['categoryFrameWork'], $markerArray);
						$out.= $out2;
					}
	
					// Print Item Title
					$wrappedSubpartArray=array();
					$markerArray = array();
					$this->tt_products->getItemMarkerArray ($actItem, $markerArray, $catTitle, $this->basket->basketExt,1,'image', $viewTagArray, array(), strtoupper($this->type));
					$markerArray['###FIELD_QTY###'] = $actItem['count'];
					$markerArray['###PRICE_TOTAL_TAX###']=$this->price->priceFormat($actItem['totalTax']);
					$markerArray['###PRICE_TOTAL_NO_TAX###']=$this->price->priceFormat($actItem['totalNoTax']);
					$markerArray['###PRICE_TOTAL_ONLY_TAX###']=$this->price->priceFormat($actItem['totalTax']-$actItem['totalNoTax']);
					$itemsOut = $this->pibase->cObj->substituteMarkerArrayCached($t['item'],$markerArray,array(),$wrappedSubpartArray);
					if ($itemsOut) {
						$out2 =$this->pibase->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
						$out .= $out2;
					}
					$itemsOut='';		// Clear the item-code var
					$categoryChanged = 0;
					}
				}
			}
		}
	}

	$subpartArray['###ITEM_CATEGORY_AND_ITEMS###'] = $out;

		// Final things
		// Personal and delivery info:

	$orderData['personInfo']['salutation'] = $this->pibase->pi_getLL('salutation'.$orderData['personInfo']['salutation']);

	$orderData['deliveryInfo']['salutation'] = $this->pibase->pi_getLL('salutation'.$orderData['deliveryInfo']['salutation']);

	/* Added Els: 'feusers_uid,'*/
	$infoFields = explode(',','feusers_uid,name,first_name,last_name,salutation,address,telephone,fax,email,company,city,zip,state,country');
	  // Fields...
	while(list(,$fName)=each($infoFields))
	{
		$markerArray['###PERSON_'.strtoupper($fName).'###'] = $orderData['personInfo'][$fName];
		$markerArray['###DELIVERY_'.strtoupper($fName).'###'] = $orderData['deliveryInfo'][$fName];
	}

	$markerArray['###PERSON_ADDRESS_DISPLAY###'] = nl2br($markerArray['###PERSON_ADDRESS###']);
	$markerArray['###DELIVERY_ADDRESS_DISPLAY###'] = nl2br($markerArray['###DELIVERY_ADDRESS###']);

	$temp = explode(' ', $orderRow['payment']);
	$markerArray['###PAYMENT_TITLE###'] = $temp[1];
	$markerArray['###PRICE_SHIPPING_TAX###'] = $this->price->priceFormat($calculatedArray['priceTax']['shipping']);
	$markerArray['###PRICE_SHIPPING_NO_TAX###'] = $this->price->priceFormat($calculatedArray['priceNoTax']['shipping']);
	$markerArray['###PRICE_PAYMENT_TAX###'] = $this->price->priceFormat($calculatedArray['priceTax']['payment']);
	$markerArray['###PRICE_PAYMENT_NO_TAX###'] = $this->price->priceFormat($calculatedArray['priceNoTax']['payment']);
	$markerArray['###PRICE_TOTAL_TAX###'] = $this->price->priceFormat($calculatedArray['priceTax']['total']);
	$markerArray['###PRICE_TOTAL_NO_TAX###'] = $this->price->priceFormat($calculatedArray['priceNoTax']['total']);

	$markerArray['###ORDER_UID###'] = $this->order->getNumber($orderRow['uid']);
	$markerArray['###ORDER_DATE###'] = $this->pibase->cObj->stdWrap($orderRow['crdate'],$this->conf['orderDate_stdWrap.']);

	$content= $this->pibase->cObj->substituteMarkerArrayCached($t['orderFrameWork'], $markerArray, $subpartArray);
	$reldateiname = $this->conf['outputFolder'] . '/' . $this->type . '/' . $tracking . '.htm';
	// $dateiname = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') .'/'. $reldateiname;
	$dateiname = t3lib_div::getFileAbsFileName($reldateiname);
	$datei = fopen($dateiname, 'wb');
	fwrite ($datei, $content);
	fclose ($datei);

	$message = $this->pibase->pi_getLL('open_'.$this->type);
	$content = '<a href="' . $reldateiname . '" >'.$message.'</a>';

	return $content;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_billdelivery.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_billdelivery.php']);
}


?>
