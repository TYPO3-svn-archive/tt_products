<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2008 Franz Holzinger <franz@ttproducts.de>
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
 * Part of the tt_products (Shop System) extension.
 *
 * payment shipping and basket extra functions
 *
 * $Id$
 *
 * @author  Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author  René Fritz <r.fritz@colorcube.de>
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @author  Klaus Zierer <zierer@pz-systeme.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

global $TYPO3_CONF_VARS;

require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_feuser.php');


class tx_ttproducts_paymentshipping {

	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $basket;
	var $basketView;
	var $fe_users; // element of class tx_table_db
	var $marker; // marker functions
	var $price;	// price functions

	function init(&$pibase, &$cnf, &$basket, &$fe_users)	{
		global $TSFE;

		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;
		$this->basket = &$basket;
		$this->fe_users = &$fe_users;

		$this->conf['TAXincluded'] = ($this->conf['TAXincluded'] ? $this->conf['TAXincluded'] : $this->pibase->conf['TAXincluded']);
		$this->conf['TAXpercentage'] = ($this->conf['TAXpercentage'] ? $this->conf['TAXpercentage'] : $this->pibase->conf['TAXpercentage']);

		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init(
			$pibase,
			$cnf,
			$basket
		);
			// price
		$this->price = t3lib_div::makeInstance('tx_ttproducts_price');
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
			$shipArray = t3lib_div::trimExplode('-', $basketRec['tt_products']['shipping']);
			$k = intval ($shipArray[0]);
			if (!$this->checkExtraAvailable('shipping',$k)) {
				$temp = $this->cleanConfArr($this->conf['shipping.'],1);
				$shipArray[0]=$k=intval(key($temp));
			}
			$this->basket->basketExtra['shipping'] = $shipArray;
			$this->basket->basketExtra['shipping.'] = $this->conf['shipping.'][$k.'.'];
			$excludePayment = trim($this->basket->basketExtra['shipping.']['excludePayment']);
		}

		// overwrite payment from shipping
		if (is_array($this->basket->basketExtra['shipping.']) &&
			is_array($this->basket->basketExtra['shipping.']['replacePayment.']))	{
			if (!$this->conf['payment.'])	{
				$this->conf['payment.'] = array();
			}
			$this->conf['payment.'] = array_merge($this->conf['payment.'], $this->basket->basketExtra['shipping.']['replacePayment.']);
		}

			// payment
		if ($this->conf['payment.']) {
			if ($excludePayment)	{
				$exclArr = t3lib_div::intExplode(',',$excludePayment);
				reset($exclArr);
				while(list(,$theVal)=each($exclArr))	{
					unset($this->conf['payment.'][$theVal]);
					unset($this->conf['payment.'][$theVal.'.']);
				}
			}

			$confArr = $this->cleanConfArr($this->conf['payment.']);
			while(list($key,$val) = each($confArr)) {
				if ($val['show'] || !isset($val['show']))	{
					if ($val['type'] == 'fe_users')	{
						if (is_array($TSFE->fe_user->user))	{
							$paymentField = $this->fe_users->getFieldName('payment');
							$paymentMethod = $TSFE->fe_user->user[$paymentField];
							$this->conf['payment.'][$key.'.']['title'] = $paymentMethod;
						} else {
							unset($this->conf['payment.'][$key.'.']);
						}
					}
					if (($val['visibleForGroupID'] != '') &&
						(!$this->fe_users->isUserInGroup($TSFE->fe_user->user, $val['visibleForGroupID'])))	{
						unset($this->conf['payment.'][$key.'.']);
					}
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
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	array		marker array
	 * @return	array
	 * @access private
	 */
	function getItemMarkerArray (&$item, &$markerArray, $value, $activeArray)	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		$row = &$item;

		$markerArray['###VALUE###'] = $value;
		$markerArray['###CHECKED###'] = ($value==implode('-',$activeArray) ? ' checked':'');
		$markerArray['###TITLE###'] = $row['title'];
		$markerArray['###IMAGE###'] = $this->pibase->cObj->IMAGE($row['image.']);
	}


	/**
	 * Generates a radio or selector box for payment shipping
	 */
	function generateRadioSelect($pskey, &$calculatedArray)	{
			/*
			 The conf-array for the payment/shipping configuration has numeric keys for the elements
			 But there are also these properties:
				.radio	  [boolean]	Enables radiobuttons instead of the default, selector-boxes
				.wrap		[string]	<select>|</select> - wrap for the selectorboxes.  Only if .radio is false. See default value below
				.template	[string]	Template string for the display of radiobuttons.  Only if .radio is true. See default below
			 */
		global $TYPO3_CONF_VARS, $TSFE;

		if ($this->conf['PIDbasket'])	{
			$basketPid = $this->conf['PIDbasket'];
		} else {
			$basketPid = $TSFE->id;
		}

		$urlMarkerArray = array();
		$urlMarkerArray = $this->marker->addURLMarkers($basketPid, $urlMarkerArray);
		$basketUrl = $urlMarkerArray['###FORM_URL###'];	// the former parameters must be preserved

		$type = $this->conf[$pskey.'.']['radio'];
		$active = $this->basket->basketExtra[$pskey];
		$activeArray = is_array($active) ? $active : array($active);
		$confArr = $this->cleanConfArr($this->conf[$pskey.'.']);
		$out='';
		$submitCode = 'this.form.action=\''.$basketUrl.'\';this.form.submit();';
		$template = $this->conf[$pskey.'.']['template'] ? ereg_replace('\' *\. *\$pskey *\. *\'',$pskey, $this->conf[$pskey.'.']['template']) : '###IMAGE### <input type="radio" name="recs[tt_products]['.$pskey.']" onClick="'.$submitCode.'" value="###VALUE###"###CHECKED###> ###TITLE###<br>';
		$wrap = $this->conf[$pskey.'.']['wrap'] ? $this->conf[$pskey.'.']['wrap'] :'<select id="'.$pskey.'-select" name="recs[tt_products]['.$pskey.']" onChange="'.$submitCode.'">|</select>';
		$t = array();
		$actTitle = $this->basket->basketExtra[$pskey.'.']['title'];
		while(list($key,$item) = each($confArr))	{
			if (($item['show'] || !isset($item['show'])) &&
				(doubleval($item['showLimit']) >= doubleval($this->basket->calculatedArray['count']) || !isset($item['showLimit']) ||
				intval($item['showLimit']) == 0)) {
				$addItems = array();
				$itemTable = '';
				$t['title'] = $item['title'];
				if ($item['where.'] && strstr($t['title'], '###'))	{
					$tableName = key($item['where.']);
					if ($this->pibase->tableArray[$tableName])	{
						global $TYPO3_CONF_VARS;

						include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_'.$this->pibase->tableArray[$tableName].'.php');
						$viewTagArray = array();
						$itemTable = &t3lib_div::getUserObj('tx_ttproducts_'.$this->pibase->tableArray[$tableName]);
						$itemTable->init($this->pibase, $this->cnf, $this->pibase->LLkey, $tableName);
						$markerFieldArray = array();
						$parentArray = array();
						$fieldsArray = $this->marker->getMarkerFields(
							$item['title'],
							$itemTable->table->tableFieldArray,
							$itemTable->table->requiredFieldArray,
							$markerFieldArray,
							$itemTable->marker,
							$viewTagArray,
							$parentArray
						);
						$addItems = $itemTable->get ('', $item['where.'][$tableName], implode(',',$fieldsArray));
					}
				}

				if (!count($addItems))	{
					$addItems = array('0' => '');
				}
				if ($type)  {	// radio
					foreach ($addItems as $k1 => $row)	{
						if (is_array($row))	{
							$markerArray = array();
							$itemTable->getItemMarkerArray ($row, $markerArray, $fieldsArray);
							$item['title'] = $this->pibase->cObj->substituteMarkerArrayCached($t['title'], $markerArray);
							$value = $key . '-'.$row['uid'];
							if ($value == implode('-',$activeArray))	{
								$actTitle = $item['title'];
							}
						} else {
							$value = $key;
						}
						$markerArray = array();
						$this->getItemMarkerArray ($item, $markerArray, $value, $activeArray);
						$out .= $this->pibase->cObj->substituteMarkerArrayCached($template, $markerArray).chr(10);
					}
				} else {
					foreach ($addItems as $k1 => $row)	{
						if (is_array($row))	{
							$markerArray = array();
							$itemTable->getItemMarkerArray ($row, $markerArray, $fieldsArray);
							$item['title'] = $this->pibase->cObj->substituteMarkerArrayCached($t['title'], $markerArray);
							$value = $key . '-'.$row['uid'];
							if ($value == implode('-',$activeArray))	{
								$actTitle = $item['title'];
							}
						} else {
							$value = $key;
						}
						$out .= '<option value="'.$value.'"'.($value == implode('-',$activeArray) ? ' selected':'').'>'.htmlspecialchars($item['title']).'</option>'.chr(10);
					}
				}
			}
		}

		if (strstr($actTitle, '###'))	{
			$markerArray = array();
			$viewTagArray = array();
			$parentArray = array();
			$fieldsArray = $this->marker->getMarkerFields(
				$actTitle,
				$tmp = array(),
				$tmp = array(),
				$tmp = array(),
				$itemTable->marker,
				$viewTagArray,
				$parentArray
			);
			$markerArray = array();
			foreach ($viewTagArray as $tag => $v)	{
				$markerArray['###'.$tag.'###'] = '?';
			}
			$actTitle = $this->pibase->cObj->substituteMarkerArrayCached($actTitle, $markerArray);
		}

		$this->basket->basketExtra[$pskey.'.']['title'] = $actTitle;
		if (!$type) {
			$out = $this->pibase->cObj->wrap($out,$wrap);
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



	function getConfiguredPrice(&$tax, &$confArr, &$countTotal, &$priceTotalTax, &$priceTax, &$priceNoTax) {
		if (is_array($confArr))	{
			$minPrice=0;
			$priceNew=0;

			if ($confArr['WherePIDMinPrice.']) {
					// compare PIDList with values set in priceTaxWherePIDMinPrice in the SETUP
					// if they match, get the min. price
					// if more than one entry for priceTaxWherePIDMinPrice exists, the highest value will be taken into account
				foreach ($confArr['WherePIDMinPrice.'] as $minPricePID=>$minPriceValue) {
					if (is_array($this->basket->itemArray[$minPricePID]) && $minPrice<doubleval($minPriceValue)) {
						$minPrice=$minPriceValue;
					}
				}
			}

			krsort($confArr);
			reset($confArr);
			if ($confArr['type'] == 'count') {
				foreach ($confArr as $k1 => $price1)	{
					if (t3lib_div::testInt($k1) && $countTotal >= $k1) {
						$priceNew = $price1;
						break;
					}
				}
			} else if ($confArr['type'] == 'weight') {
				foreach ($confArr as $k1 => $price1)	{
					if (t3lib_div::testInt($k1) && $this->basket->calculatedArray['weight'] * 1000 >= $k1) {
						$priceNew = $price1;
						break;
					}
				}
			} else if ($confArr['type'] == 'price') {
				foreach ($confArr as $k1 => $price1)	{
					if (t3lib_div::testInt($k1) && $priceTotalTax >= $k1) {
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

			$taxIncluded = $this->price->getTaxIncluded();
			$priceTax += $this->price->getPrice($priceNew,1,$tax,$taxIncluded,true);
			$priceNoTax += $this->price->getPrice($priceNew,0,$tax,$taxIncluded,true);
		}
	}



	function getPrices ($pskey, $countTotal, $priceTotalTax, &$priceShippingTax, &$priceShippingNoTax, &$taxpercentage)	{

		$taxIncluded = $this->conf['TAXincluded'];
		if (is_array($this->basket->basketExtra[$pskey.'.']) && isset($this->basket->basketExtra[$pskey.'.']['TAXincluded']))	{
			$taxIncluded = $this->basket->basketExtra[$pskey.'.']['TAXincluded'];
		}

		$confArr = $this->basket->basketExtra[$pskey.'.']['price.'];
		$confArr = ($confArr ? $confArr : $this->basket->basketExtra[$pskey.'.']['priceTax.']);
		$taxpercentage = doubleVal($this->conf[$pskey.'.']['TAXpercentage']);
		$taxFromShipping = $this->getReplaceTAXpercentage();
		$this->price->init($this->pibase, $this->conf[$pskey.'.'], $this->cnf, $this->basket, $this);
		if ($confArr) {
			$this->getConfiguredPrice($taxpercentage, $confArr, $countTotal, $priceTotalTax, $priceShippingTax, $priceShippingNoTax);
		} else {
			$priceShippingAdd = doubleVal($this->basket->basketExtra[$pskey.'.']['price']);
			if ($priceShippingAdd)	{
				$priceShippingTaxAdd = $this->price->getPrice($priceShippingAdd,true,$taxpercentage,$taxIncluded,true);
			} else {
				$priceShippingTaxAdd = doubleVal($this->basket->basketExtra[$pskey.'.']['priceTax']);
			}
			$priceShippingTax += $priceShippingTaxAdd;
			$priceShippingNoTaxAdd = doubleVal($this->basket->basketExtra[$pskey.'.']['priceNoTax']);
			if (isset($taxFromShipping) && is_double($taxFromShipping))	{
				$taxpercentage = $taxFromShipping;
			}
			if (!$priceShippingNoTaxAdd) {
				$priceShippingNoTaxAdd = $this->price->getPrice($priceShippingTaxAdd,false,$taxpercentage,true,true);
			}
			$priceShippingNoTax += $priceShippingNoTaxAdd;
		}
	}

	function getSpecialPrices ($pskey, $taxpercentage, &$priceShippingTax, &$priceShippingNoTax)	{
		global $TSFE;

		$perc = doubleVal($this->basket->basketExtra[$pskey.'.']['percentOfGoodstotal']);
		if ($perc)  {
			$priceShipping = doubleVal(($this->basket->calculatedArray['priceTax']['goodstotal']/100)*$perc);
			$dum = $this->price->getPrice($priceShipping,1,$taxpercentage);
			$taxIncluded = $this->price->getTaxIncluded();
			$priceShippingTax = $priceShippingTax + $this->price->getPrice($priceShipping,true,$taxpercentage,$taxIncluded,true);
			$priceShippingNoTax = $priceShippingNoTax + $this->price->getPrice($priceShipping,false,$taxpercentage,$taxIncluded,true);
		}

		if ($this->basket->basketExtra[$pskey.'.']['calculationScript']) {
			$calcScript = $TSFE->tmpl->getFileName($this->basket->basketExtra[$pskey.'.']['calculationScript']);
			if ($calcScript)	{
				$this->basket->pricecalc->includeCalcScript(
					$calcScript,
					$this->basket->basketExtra[$pskey.'.']['calculationScript.']
				);
			}
		}
	}

	function getPaymentShippingData(
			$countTotal,
			&$priceTotalTax,
			&$priceShippingTax,
			&$priceShippingNoTax,
			&$pricePaymentTax,
			&$pricePaymentNoTax
			) {
		global $TSFE;
		$taxpercentage = '';

		// Shipping
		$this->getPrices ('shipping', $countTotal, $priceTotalTax, $priceShippingTax, $priceShippingNoTax, $taxpercentage);

		$weigthFactor = doubleVal($this->basket->basketExtra['shipping.']['priceFactWeight']);
		if($weigthFactor > 0) {
			$priceShipping = $this->basket->calculatedArray['weight'] * $weigthFactor;
			$taxIncluded = $this->price->getTaxIncluded();
			$priceShippingTax += $this->price->getPrice($priceShipping,true,$taxpercentage,$taxIncluded,true);
			$priceShippingNoTax += $this->price->getPrice($priceShipping,false,$taxpercentage,$taxIncluded,true);
		}
		$this->getSpecialPrices ('shipping', $taxpercentage, $priceShippingTax, $priceShippingNoTax);

			// Payment
		$pricePayment = $pricePaymentTax = $pricePaymentNoTax = 0;
		$taxpercentage = '';

		$this->getPrices ('payment', $countTotal, $priceTotalTax, $pricePaymentTax, $pricePaymentNoTax, $taxpercentage);

		$perc = doubleVal($this->basket->basketExtra['payment.']['percentOfTotalShipping']);
		if ($perc)  {
			$payment = ($this->basket->calculatedArray['priceTax']['goodstotal'] + $this->basket->calculatedArray['priceTax']['shipping'] ) * doubleVal($perc);
			$taxIncluded = $this->price->getTaxIncluded();
			$pricePaymentTax += $this->price->getPrice($payment,true,$taxpercentage,$taxIncluded,true);
			$pricePaymentNoTax += $this->price->getPrice($payment,false,$taxpercentage,$taxIncluded,true);
		}
		$this->getSpecialPrices ('payment', $taxpercentage, $priceShippingTax, $priceShippingNoTax);
	} // getPaymentShippingData

	/**
	 * Include handle script
	 */
	function includeHandleScript($handleScript, &$confScript, $order, $activity, &$bFinalize)	{
		$content = '';
		include($handleScript);
		return $content;
	} // includeHandleScript

	/**
	 * get the replaceTAXpercentage from the shipping if available
	 */
	function getReplaceTAXpercentage()	{
		if (is_array($this->basket->basketExtra['shipping.']) && isset($this->basket->basketExtra['shipping.']['replaceTAXpercentage']))	{
			$rc = doubleval($this->basket->basketExtra['shipping.']['replaceTAXpercentage']);
		}
		return $rc;
	}

	/**
	 * get the where condition for a shipping entry
	 * E.g.:  30.where.static_countries = cn_short_local = 'Deutschland'
	 */
	function getWhere($tablename)	{
		if (is_array($this->basket->basketExtra['shipping.']) && isset($this->basket->basketExtra['shipping.']['where.']))	{
			$rc = $this->basket->basketExtra['shipping.']['where.'][$tablename];
		}
		return $rc;
	}


	function useGatewayRequest ()	{
		$rc = false;
		if (t3lib_extMgm::isLoaded ('paymentlib') && version_compare(phpversion(), '5.0.0', '>='))	{
			$payConf = &$this->basket->basketExtra['payment.'];
			if (is_array($payConf) && $payConf['handleLib'] == 'paymentlib' && is_array($payConf['handleLib.']) && $payConf['handleLib.']['gatewaymode'] == 'request')	{
				$rc = true;
			}
		}
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']);
}


?>
