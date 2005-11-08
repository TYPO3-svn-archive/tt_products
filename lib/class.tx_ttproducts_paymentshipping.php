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
 * payment shipping and basket extra functions
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_price.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricecalc_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_view_div.php');


class tx_ttproducts_paymentshipping {

	var $pibase; // reference to object of pibase
	var $conf;


	function init(&$pibase, &$conf, &$price)	{
		global $TSFE;

 		$this->pibase = &$pibase;
 		$this->conf = &$conf;
 		$this->price = &$price;
	}


	/**
	 * Setting shipping, payment methods
	 */
	function setBasketExtras(&$basket, &$basketRec)	{
		global $TSFE;

			// shipping
		if ($this->conf['shipping.']) {
			ksort($this->conf['shipping.']);
			reset($this->conf['shipping.']);
			$k=intval($basketRec['tt_products']['shipping']);
			if (!$this->checkExtraAvailable('shipping',$k))	{
				$temp = $this->cleanConfArr($this->conf['shipping.'],1);
				$k=intval(key($temp));
			}
			$basket->basketExtra['shipping'] = $k;
			$basket->basketExtra['shipping.'] = $this->conf['shipping.'][$k.'.'];
			$excludePayment = trim($basket->basketExtra['shipping.']['excludePayment']);
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
			if (!$this->checkExtraAvailable('payment',$k))	{
				$temp = $this->cleanConfArr($this->conf['payment.'],1);
				$k=intval(key($temp));
			}
			$basket->basketExtra['payment'] = $k;
			$basket->basketExtra['payment.'] = $this->conf['payment.'][$k.'.'];
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
	function generateRadioSelect(&$basket, $key)	{
			/*
			 The conf-array for the payment/shipping configuration has numeric keys for the elements
			 But there are also these properties:

			 	.radio 		[boolean]	Enables radiobuttons instead of the default, selector-boxes
			 	.wrap 		[string]	<select>|</select> - wrap for the selectorboxes.  Only if .radio is false. See default value below
			 	.template	[string]	Template string for the display of radiobuttons.  Only if .radio is true. See default below

			 */

		$type=$this->conf[$key.'.']['radio'];
		$active = $basket->basketExtra[$key];
		$confArr = $this->cleanConfArr($this->conf[$key.'.']);
		$out='';

		$template = $this->conf[$key.'.']['template'] ? ereg_replace('\' *\. *\$key *\. *\'',$key, $this->conf[$key.'.']['template']) : '<nobr>###IMAGE### <input type="radio" name="recs[tt_products]['.$key.']" onClick="submit()" value="###VALUE###"###CHECKED###> ###TITLE###</nobr><BR>';

		$wrap = $this->conf[$key."."]["wrap"] ? $this->conf[$key."."]["wrap"] :'<select name="recs[tt_products]['.$key.']" onChange="submit()">|</select>';

		while(list($key,$val)=each($confArr))	{
			if (($val['show'] || !isset($val['show'])) &&
				(doubleval($val['showLimit']) >= doubleval($basket->calculatedArray['count']) || !isset($val['showLimit']) ||
				 intval($val['showLimit']) == 0)) {
				if ($type)	{	// radio
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
		if (!$type)	{
			$out=$this->pibase->cObj->wrap($out,$wrap);
		}
		return $out;
	} // generateRadioSelect



	function cleanConfArr($confArr,$checkShow=0)	{
		$outArr=array();
		if (is_array($confArr))	{
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



	function GetPaymentShippingData(
			&$basket,
			$countTotal,
/* Added Els: necessary to calculate shipping price which depends on total no-tax price */
			&$priceTotalNoTax,
			&$priceShippingTax,
			&$priceShippingNoTax,
			&$pricePaymentTax,
			&$pricePaymentNoTax
			) {
		global $TSFE;

			// shipping
		// $priceShipping = $priceShippingTax = $priceShippingNoTax = 0;

		$confArr = $basket->basketExtra['shipping.']['priceTax.'];
		$tax = doubleVal($this->conf['shipping.']['TAXpercentage']);

		if ($confArr) {
	        $minPrice=0;
	        if ($basket->basketExtra['shipping.']['priceTax.']['WherePIDMinPrice.']) {
	                // compare PIDList with values set in priceTaxWherePIDMinPrice in the SETUP
	                // if they match, get the min. price
	                // if more than one entry for priceTaxWherePIDMinPrice exists, the highest is value will be taken into account
	            foreach ($basket->basketExtra['shipping.']['priceTax.']['WherePIDMinPrice.'] as $minPricePID=>$minPriceValue) {
	                if (is_array($basket->itemArray[$minPricePID]) && $minPrice<doubleval($minPriceValue)) {
	                    $minPrice=$minPriceValue;
	                }
	            }
	        }

			krsort($confArr);
			reset($confArr);

			if ($confArr['type'] == 'count') {
				while (list ($k1, $price1) = each ($confArr)) {
					if ($countTotal >= intval($k1)) {
						$priceShipping = $price1;
						break;
					}
				}
			} else if ($confArr['type'] == 'weight') {
				while (list ($k1, $price1) = each ($confArr)) {
					if ($basket->calculatedArray['weight'] * 1000 >= intval($k1)) {
						$priceShipping = $price1;
						break;
					}
				}
			/* Added Els: shipping price (verzendkosten) depends on price of goodstotal */
			} else if ($confArr['type'] == 'price') {
				while (list ($k1, $price1) = each ($confArr)) {
					if ($priceTotalNoTax >= intval($k1)) {
						$priceShipping = $price1;
						break;
					}
				}
			}
			// compare the price to the min. price
			if ($minPrice > $priceShipping) {
				$priceShipping = $minPrice;
			}

			$priceShippingTax += $this->price->getPrice($priceShipping,1,$tax);
			$priceShippingNoTax += $this->price->getPrice($priceShipping,0,$tax);
		} else {
			$priceShippingTax += doubleVal($basket->basketExtra['shipping.']['priceTax']);
			$priceShippingNoTax += doubleVal($basket->basketExtra['shipping.']['priceNoTax']);
			if ($this->conf['shipping.']['TAXpercentage']) {
				$priceShippingNoTax = $priceShippingTax/(1+$tax/100);
			}			
		}

		$perc = doubleVal($basket->basketExtra['shipping.']['percentOfGoodstotal']);
		if ($perc)	{
			$priceShipping = doubleVal(($basket->calculatedArray['priceTax']['goodstotal']/100)*$perc);
			$dum = $this->price->getPrice($priceShipping,1,$tax);
			$priceShippingTax = $priceShippingTax + $this->price->getPrice($priceShipping,1,$tax);
			$priceShippingNoTax = $priceShippingNoTax + $this->price->getPrice($priceShipping,0,$tax);
		}

		$weigthFactor = doubleVal($basket->basketExtra['shipping.']['priceFactWeight']);
		if($weigthFactor > 0) {
			$priceShipping = $basket->calculatedArray['weight'] * $weigthFactor;
			$priceShippingTax += $this->price->getPrice($priceShipping,1,$tax);
			$priceShippingNoTax += $this->price->getPrice($priceShipping,0,$tax);
		}

		if ($basket->basketExtra['shipping.']['calculationScript'])	{
			$calcScript = $TSFE->tmpl->getFileName($basket->basketExtra['shipping.']['calculationScript']);
			if ($calcScript)	{
				tx_ttproducts_pricecalc_div::includeCalcScript(
					$calcScript,
					$this->pibase,
					$basket->basketExtra['shipping.']['calculationScript.'],
					$basket
				);
			}
		}

			// Payment
		$pricePayment = $pricePaymentTax = $pricePaymentNoTax = 0;
		// TAXpercentage replaces priceNoTax
		$tax = doubleVal($this->conf['payment.']['TAXpercentage']);

		$pricePaymentTax = $basket->getValue($basket->basketExtra['payment.']['priceTax'],
		                  		$basket->basketExtra['payment.']['priceTax.'],
		                  		$basket->calculatedArray['count']);
		if ($tax) {
			$pricePaymentNoTax = $this->price->getPrice($pricePaymentTax,0,$tax);

		} else {
			$pricePaymentNoTax = $basket->getValue($basket->basketExtra['payment.']['priceNoTax'],
		                  		$basket->basketExtra['payment.']['priceNoTax.'],
		                  		$basket->calculatedArray['count']);
		}

		$perc = doubleVal($basket->basketExtra['payment.']['percentOfTotalShipping']);
		if ($perc)	{

			$payment = ($basket->calculatedArray['priceTax']['goodstotal'] + $basket->calculatedArray['priceTax']['shipping'] ) * doubleVal($perc);

			$pricePaymentTax = $this->price->getPrice($payment,1,$tax);
			$pricePaymentNoTax = $this->price->getPrice($payment,0,$tax);
		}

		$perc = doubleVal($basket->basketExtra['payment.']['percentOfGoodstotal']);
		if ($perc)	{
			$pricePaymentTax += ($basket->calculatedArray['priceTax']['goodstotal']/100)*$perc;
			$pricePaymentNoTax += ($basket->calculatedArray['priceNoTax']['goodstotal']/100)*$perc;
		}

		if ($basket->basketExtra['payment.']['calculationScript'])	{
			$calcScript = $TSFE->tmpl->getFileName($basket->basketExtra['payment.']['calculationScript']);
			if ($calcScript)	{
				tx_ttproducts_pricecalc_div::includeCalcScript($calcScript,$this->pibase,$basket->basketExtra['payment.']['calculationScript.'],$basket);
			}
		}

	} // GetPaymentShippingData


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']);
}


?>
