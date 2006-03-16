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
 * payment shipping and basket extra functions
 *
 * $Id$
 *
 * @author  Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author  René Fritz <r.fritz@colorcube.de>
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @author  Klaus Zierer <zierer@pz-systeme.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_div.php');


class tx_ttproducts_paymentshipping {

	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $basket;
	var $basketView;


	function init(&$pibase, &$conf, &$config, &$basket, &$basketView)	{
		global $TSFE;

		$this->pibase = &$pibase;
		$this->conf = &$conf;
		$this->config = &$config;
		$this->basket = &$basket;
		$this->basketView = &$basketView;
	}


	/**
	 * Setting shipping, payment methods
	 */
	function setBasketExtras(&$basketRec) {
		global $TSFE;

			// shipping
		if ($this->conf['shipping.']) {
			ksort($this->conf['shipping.']);
			reset($this->conf['shipping.']);
			$k=intval($basketRec['tt_products']['shipping']);
			if (!$this->checkExtraAvailable('shipping',$k)) {
				$temp = $this->cleanConfArr($this->conf['shipping.'],1);
				$k=intval(key($temp));
			}
			$this->basket->basketExtra['shipping'] = $k;
			$this->basket->basketExtra['shipping.'] = $this->conf['shipping.'][$k.'.'];
			$excludePayment = trim($this->basket->basketExtra['shipping.']['excludePayment']);
		}

			// payment
		if ($this->conf['payment.']) {
			if ($excludePayment)	{
				$exclArr = t3lib_div::intExplode(',',$excludePayment);
				while(list(,$theVal)=each($exclArr))	{
					unset($this->conf['payment.'][$theVal]);
					unset($this->conf['payment.'][$theVal.'.']);
				}
			}
	
			$confArr = $this->cleanConfArr($this->conf['payment.']);
			while(list($key,$val)=each($confArr)) {
				if ($val['show'] || !isset($val['show']))
					if (($val['visibleForGroupID'] != '') &&
						(!tx_ttproducts_div::isUserInGroup($TSFE->fe_user->user, $val['visibleForGroupID'])))
					{
						unset($this->conf['payment.'][$key.'.']);
					}
			}
	
			ksort($this->conf['payment.']);
			reset($this->conf['payment.']);
			$k=intval($basketRec['tt_products']['payment']);
			if (!$this->checkExtraAvailable('payment',$k))  {
				$temp = $this->cleanConfArr($this->conf['payment.'],1);
				$k=intval(key($temp));
			}
			$this->basket->basketExtra['payment'] = $k;
			$this->basket->basketExtra['payment.'] = $this->conf['payment.'][$k.'.'];
		}

	} // setBasketExtras



	/**
	 * Check if payment/shipping option is available
	 */
	function checkExtraAvailable($name,$key)	{
		$result = false;

		if (is_array($this->conf[$name.'.'][$key.'.']) && (!isset($this->conf[$name.'.'][$key.'.']['show']) || $this->conf[$name.'.'][$key.'.']['show']))	{
			$result = true;
		}

		return $result;
	} // checkExtraAvailable



	/**
	 * Generates a radio or selector box for payment shipping
	 */
	function generateRadioSelect($key, &$calculatedArray)
{
			/*
			 The conf-array for the payment/shipping configuration has numeric keys for the elements
			 But there are also these properties:
				.radio	  [boolean]	Enables radiobuttons instead of the default, selector-boxes
				.wrap		[string]	<select>|</select> - wrap for the selectorboxes.  Only if .radio is false. See default value below
				.template	[string]	Template string for the display of radiobuttons.  Only if .radio is true. See default below
			 */

		$type=$this->conf[$key.'.']['radio'];
		$active = $this->basket->basketExtra[$key];
		$confArr = $this->cleanConfArr($this->conf[$key.'.']);
		$out='';

		$template = $this->conf[$key.'.']['template'] ? ereg_replace('\' *\. *\$key *\. *\'',$key, $this->conf[$key.'.']['template']) : '<nobr>###IMAGE### <input type="radio" name="recs[tt_products]['.$key.']" onClick="submit()" value="###VALUE###"###CHECKED###> ###TITLE###</nobr><BR>';

		$wrap = $this->conf[$key.'.']['wrap'] ? $this->conf[$key.'.']['wrap'] :'<select name="recs[tt_products]['.$key.']" onChange="submit()">|</select>';

		while(list($key,$val)=each($confArr))	{
			if (($val['show'] || !isset($val['show'])) &&
				(doubleval($val['showLimit']) >= doubleval($this->basket->calculatedArray['count']) || !isset($val['showLimit']) ||
				 intval($val['showLimit']) == 0)) {
				if ($type)  {	// radio
					$markerArray=array();
					$markerArray['###VALUE###']=intval($key);
					$markerArray['###CHECKED###']=(intval($key)==$active?' checked':'');
					$markerArray['###TITLE###']=$val['title'];
					$markerArray['###IMAGE###']=$this->pibase->cObj->IMAGE($val['image.']);
					$out.=$this->pibase->cObj->substituteMarkerArrayCached($template, $markerArray);
				} else {
					$out.='<option value="'.intval($key).'"'.(intval($key)==$active?' selected':'').'>'.htmlspecialchars($val['title']).'</option>';
				}
			}
		}
		if (!$type) {
			$out=$this->pibase->cObj->wrap($out,$wrap);
		}
		return $out;
	} // generateRadioSelect



	function cleanConfArr($confArr,$checkShow=0)	{
		$outArr=array();
		if (is_array($confArr)) {
			reset($confArr);
			while(list($key,$val)=each($confArr))	{
				if (!t3lib_div::testInt($key) && intval($key) && is_array($val) && (!$checkShow || $val['show'] || !isset($val['show'])))	{
					$outArr[intval($key)]=$val;
				}
			}
		}
		ksort($outArr);
		reset($outArr);
		return $outArr;
	} // cleanConfArr


	function getConfiguredPrice(&$price, &$tax, &$confArr, &$countTotal, &$priceTotalTax, &$priceTax, &$priceNoTax) {
		$minPrice=0;
		$priceNew=0;
		if ($confArr['WherePIDMinPrice.']) {
				// compare PIDList with values set in priceTaxWherePIDMinPrice in the SETUP
				// if they match, get the min. price
				// if more than one entry for priceTaxWherePIDMinPrice exists, the highest is value will be taken into account
			foreach ($confArr['WherePIDMinPrice.'] as $minPricePID=>$minPriceValue) {
				if (is_array($this->basket->itemArray[$minPricePID]) && $minPrice<doubleval($minPriceValue)) {
					$minPrice=$minPriceValue;
				}
			}
		}

		krsort($confArr);
		reset($confArr);

		if ($confArr['type'] == 'count') {
			while (list ($k1, $price1) = each ($confArr)) {
				if ($countTotal >= $k1) {
					$priceNew = $price1;
					break;
				}
			}
		} else if ($confArr['type'] == 'weight') {
			while (list ($k1, $price1) = each ($confArr)) {
				if ($this->basket->calculatedArray['weight'] * 1000 >= $k1) {
					$priceNew = $price1;
					break;
				}
			}
		/* Added Els: shipping price (verzendkosten) depends on price of goodstotal */
		} else if ($confArr['type'] == 'price') {
			while (list ($k1, $price1) = each ($confArr)) {
				if ($priceTotalTax >= $k1) {
					$priceNew = $price1;
					break;
				}
			}
		}

		// compare the price to the min. price
		if ($minPrice > $priceNew) {
			$priceNew = $minPrice;
		}

 
		// constant noCostsAmount by Christoph Zipper <info@chriszip.de>
		// the total products price as from the payment/shipping is free
		$noCostsAmount = (double) $confArr['noCostsAmount'];
		if ($noCostsAmount && ($priceTotalTax >= $noCostsAmount)) {
			$priceNew = 0;
		}

		$priceTax += $price->getPrice($priceNew,1,$tax,1);
		$priceNoTax += $price->getPrice($priceNew,0,$tax,1);
	}


	function GetPaymentShippingData(
			$countTotal,
/* Added Els: necessary to calculate shipping price which depends
on total no-tax price */
			&$priceTotalTax,
			&$priceShippingTax,
			&$priceShippingNoTax,
			&$pricePaymentTax,
			&$pricePaymentNoTax
			) {
		global $TSFE;

			// price
		$price = t3lib_div::makeInstance('tx_ttproducts_price');

		// Shipping
		$confArr = $this->basket->basketExtra['shipping.']['priceTax.'];
		$tax = doubleVal($this->conf['shipping.']['TAXpercentage']);
		$price->init($this, $this->conf['shipping.'], $this->config);

		if ($confArr) {
			$this->getConfiguredPrice($price, $tax, $confArr, $countTotal, $priceTotalTax, $priceShippingTax, $priceShippingNoTax); 
		} else {
			$priceShippingTax += doubleVal($this->basket->basketExtra['shipping.']['priceTax']);
			$priceShippingNoTax += doubleVal($this->basket->basketExtra['shipping.']['priceNoTax']);

			if ($tax) {
				$priceShippingNoTax = $priceShippingTax/(1+$tax/100);
			} else if (!$priceShippingNoTax) {
				$priceShippingNoTax = $priceShippingTax; 
			}
		}

		$perc = doubleVal($this->basket->basketExtra['shipping.']['percentOfGoodstotal']);
		if ($perc)  {
			$priceShipping = doubleVal(($this->basket->calculatedArray['priceTax']['goodstotal']/100)*$perc);
			$dum = $price->getPrice($priceShipping,1,$tax);
			$priceShippingTax = $priceShippingTax + $price->getPrice($priceShipping,1,$tax);
			$priceShippingNoTax = $priceShippingNoTax + $price->getPrice($priceShipping,0,$tax);
		}

		$weigthFactor = doubleVal($this->basket->basketExtra['shipping.']['priceFactWeight']);
		if($weigthFactor > 0) {
			$priceShipping = $this->basket->calculatedArray['weight'] * $weigthFactor;
			$priceShippingTax += $price->getPrice($priceShipping,1,$tax);
			$priceShippingNoTax += $price->getPrice($priceShipping,0,$tax);
		}

		if ($this->basket->basketExtra['shipping.']['calculationScript']) {
			$calcScript = $TSFE->tmpl->getFileName($this->basket->basketExtra['shipping.']['calculationScript']);
			if ($calcScript)	{
				$this->basket->pricecalc->includeCalcScript(
					$calcScript,
					$this->basket->basketExtra['shipping.']['calculationScript.'],
					$this->basketView
				);
			}
		}

			// Payment
		$pricePayment = $pricePaymentTax = $pricePaymentNoTax = 0;
		$confArr = $this->basket->basketExtra['payment.']['priceTax.'];
		$tax = doubleVal($this->conf['payment.']['TAXpercentage']);
		$price->init($this, $this->conf['payment.'], $this->config);

		if ($confArr) {
			$this->getConfiguredPrice($price, $tax, $confArr, $countTotal, $priceTotalTax, $pricePaymentTax, $pricePaymentNoTax);
		} else {
			$pricePaymentTax += doubleVal($this->basket->basketExtra['payment.']['priceTax']);
			$pricePaymentNoTax += doubleVal($this->basket->basketExtra['payment.']['priceNoTax']);	
			if ($tax) {
				$pricePaymentNoTax = $pricePaymentTax/(1+$tax/100);
			} else if (!$pricePaymentNoTax) {
				$pricePaymentNoTax = $pricePaymentTax; 
			}
		}

		$perc = doubleVal($this->basket->basketExtra['payment.']['percentOfTotalShipping']);
		if ($perc)  {

			$payment = ($this->basket->calculatedArray['priceTax']['goodstotal'] + $this->basket->calculatedArray['priceTax']['shipping'] ) * doubleVal($perc);
			$pricePaymentTax += $price->getPrice($payment,1,$tax);
			$pricePaymentNoTax += $price->getPrice($payment,0,$tax);
		}

		$perc = doubleVal($this->basket->basketExtra['payment.']['percentOfGoodstotal']);
		if ($perc)  {
			$pricePaymentTax += ($this->basket->calculatedArray['priceTax']['goodstotal']/100)*$perc;
			$pricePaymentNoTax += ($this->basket->calculatedArray['priceNoTax']['goodstotal']/100)*$perc;
		}

		if ($this->basket->basketExtra['payment.']['calculationScript'])  {
			$calcScript = $TSFE->tmpl->getFileName($this->basket->basketExtra['payment.']['calculationScript']);
			if ($calcScript)	{
				$this->basket->pricecalc->includeCalcScript($calcScript,$this->basket->basketExtra['payment.']['calculationScript.'], $this->basketView);
			}
		}

	} // GetPaymentShippingData


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']);
}


?>
