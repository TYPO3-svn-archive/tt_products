<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2005 Franz Holzinger <kontakt@fholzinger.com>
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
 * bill and delivery functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_billdelivery_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_order_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_paymentshipping_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_view_div.php');


class tx_ttproducts_billdelivery_div {

	/**
	 * Bill,Delivery Tracking
	 */
	function getInformation(&$pibase,$type, $orderRow, $templateCode, $tracking)
	{
			/*

					Bill or delivery information display, which needs tracking code to be shown

   					This is extension information to tracking at another page
					See Tracking for further information
			*/
		global $TSFE;

			// initialize order data.
		$orderData = unserialize($orderRow['orderData']);

		$basket = $orderData[''];

		$markerArray = array();
		$subpartArray = array();
		$wrappedSubpartArray = array();

		$this->itemArray = $orderData['itemArray'];
		$this->calculatedArray = $orderData['calculatedArray'];

		if ($type == 'bill')
		{
			$subpartMarker='###BILL_TEMPLATE###';
		}
		else
		{
			$subpartMarker='###DELIVERY_TEMPLATE###';
		}

			// Getting subparts from the template code.
		$t=array();
			// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
		$t['orderFrameWork'] = $this->cObj->getSubpart($templateCode,tx_ttproducts_view_div::spMarker($subpartMarker));

		$t['categoryTitle'] = $this->cObj->getSubpart($t['orderFrameWork'],'###ITEM_CATEGORY###');
		$t['itemFrameWork'] = $this->cObj->getSubpart($t['orderFrameWork'],'###ITEM_LIST###');
		$t['item'] = $this->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');

		$categoryQty = array();
//		$categoryPrice = array();
		$category = array();
//
//		reset($basket);
//		$countTotal = 0;
//
		// Calculate quantities for all categories
		// loop over all items in the basket sorted by page and itemnumber
		//foreach ($this->itemArray as $pid=>$pidItem) {
			//foreach ($pidItem as $itemnumber=>$actItem) {

		// loop over all items in the basket indexed by page and itemnumber
		foreach ($this->itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					$currentCategory=$actItem['rec']['category'];
					$category[$currentCategory] = 1;
	//			$countTotal += $actBasket['count'];
					$categoryQty[$currentCategory] += intval($actItem['count']);
	//			$categoryPrice[$currentCategory] += doubleval($actBasket['priceTax']) * intval($actBasket['count']);
				}
			}
		}
//			// Initialize traversing the items in the calculated basket
//
//		tx_ttproducts_paymentshipping_div::GetPaymentShippingData(
//			$countTotal,
//			$priceShippingTax);

		reset($this->itemArray);
		reset($category);
		$itemsOut='';
		$out='';

		foreach ($category as $currentCategory=>$value)
		{
			$categoryChanged = 1;
			// loop over all items in the basket indexed by page and itemnumber
			foreach ($this->itemArray as $pid=>$pidItem) {
				foreach ($pidItem as $itemnumber=>$actItemArray) {
					foreach ($actItemArray as $k1=>$actItem) {

							// Print Category Title
						if ($actItem['rec']['category']==$currentCategory)
						{

							if ($categoryChanged == 1)
							{
								$markerArray=array();
								$tmpCategory = $this->category->getCategory($currentCategory);
								$catTitle= ($tmpCategory ? $tmpCategory: '');
								$this->cObj->setCurrentVal($catTitle);
								$markerArray['###CATEGORY_TITLE###'] = $this->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
								$markerArray['###CATEGORY_QTY###'] = $categoryQty[$currentCategory];

								$markerArray['###PRICE_GOODS_TAX###']= tx_ttproducts_view_div::priceFormat($this->calculatedArray['categoryPriceTax']['goodstotal'][$currentCategory]);
								$markerArray['###PRICE_GOODS_NO_TAX###']= tx_ttproducts_view_div::priceFormat($this->calculatedArray['categoryPriceNoTax']['goodstotal'][$currentCategory]);

								$out2 = $this->cObj->substituteMarkerArray($t['categoryTitle'], $markerArray);
								$out.= $out2;
							}

								// Print Item Title
							$wrappedSubpartArray=array();
							$markerArray = tx_ttproducts_view_div::getItemMarkerArray ($pibase,$actItem,$this->basketExt, $catTitle,1,'image');

							$markerArray['###FIELD_QTY###'] = $actItem['count'];

							$itemsOut = $this->cObj->substituteMarkerArrayCached($t['item'],$markerArray,array(),$wrappedSubpartArray);
							if ($itemsOut)
							{
								$out2 =$this->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
								$out .= $out2;
							}
							$itemsOut='';			// Clear the item-code var

						$categoryChanged = 0;
						}
					}
				}
			}
		}


		$subpartArray['###ITEM_CATEGORY_AND_ITEMS###'] = $out;

			// Final things
			// Personal and delivery info:

/* Added Els: 'feusers_uid,'*/
		$infoFields = explode(',','feusers_uid,name,address,telephone,fax,email,company,city,zip,state,country');		// Fields...
		while(list(,$fName)=each($infoFields))
		{
			$markerArray['###PERSON_'.strtoupper($fName).'###'] = $orderData['personInfo'][$fName];
			$markerArray['###DELIVERY_'.strtoupper($fName).'###'] = $orderData['deliveryInfo'][$fName]; // $this->deliveryInfo[$fName];
		}

		$markerArray['###PERSON_ADDRESS_DISPLAY###'] = nl2br($markerArray['###PERSON_ADDRESS###']);
		$markerArray['###DELIVERY_ADDRESS_DISPLAY###'] = nl2br($markerArray['###DELIVERY_ADDRESS###']);

		$temp = explode(' ', $orderRow['payment']);
		$markerArray['###PAYMENT_TITLE###'] = $temp[1];
		$markerArray['###PRICE_SHIPPING_TAX###'] = tx_ttproducts_view_div::priceFormat($this->calculatedArray['priceTax']['shipping']);
		$markerArray['###PRICE_SHIPPING_NO_TAX###'] = tx_ttproducts_view_div::priceFormat($this->calculatedArray['priceNoTax']['shipping']);
		$markerArray['###PRICE_PAYMENT_TAX###'] = tx_ttproducts_view_div::priceFormat($this->calculatedArray['priceTax']['payment']);
		$markerArray['###PRICE_PAYMENT_NO_TAX###'] = tx_ttproducts_view_div::priceFormat($this->calculatedArray['priceNoTax']['payment']);
		$markerArray['###PRICE_TOTAL_TAX###'] = tx_ttproducts_view_div::priceFormat($this->calculatedArray['priceTax']['total']);
		$markerArray['###PRICE_TOTAL_NO_TAX###'] = tx_ttproducts_view_div::priceFormat($this->calculatedArray['priceNoTax']['total']);

		$markerArray['###ORDER_UID###'] = tx_ttproducts_order_div::getOrderNumber($orderRow['uid']);
		$markerArray['###ORDER_DATE###'] = $this->cObj->stdWrap($orderRow['crdate'],$this->conf['orderDate_stdWrap.']);

		$content= $this->cObj->substituteMarkerArrayCached($t['orderFrameWork'], $markerArray, $subpartArray);
		$reldateiname = $this->conf['outputFolder'] . '/' . $type . '/' . $tracking . '.htm';

		$dateiname = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') .'/'. $reldateiname;
		$datei = fopen($dateiname, 'w');
		fwrite ($datei, $content);
		fclose ($datei);

		$message = $this->pi_getLL('open '.$type);
		$content = '<a href="' . $reldateiname . '" >'.$message.'</a>';

		return $content;
	}



}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_billdelivery_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_billdelivery_div.php']);
}


?>
