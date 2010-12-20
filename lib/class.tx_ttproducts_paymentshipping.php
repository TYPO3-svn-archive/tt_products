<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2007 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * @author  Renè Fritz <r.fritz@colorcube.de>
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @author  Klaus Zierer <zierer@pz-systeme.de>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_paymentshipping {
	var $cObj;
	var $conf;
	var $config;
	var $basket;
	var $basketView;
	var $priceObj;	// price functions
	var $typeArray = array('shipping','payment');

	function init(&$cObj)	{
		global $TSFE;

		$this->cObj = &$cObj;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->basket = &t3lib_div::getUserObj('&tx_ttproducts_basket');
// 		$this->config['TAXincluded'] = ($this->conf['TAXincluded'] ? $this->conf['TAXincluded'] : $this->pibase->conf['TAXincluded']);
// 		$this->config['TAXpercentage'] = ($this->conf['TAXpercentage'] ? $this->conf['TAXpercentage'] : $this->pibase->conf['TAXpercentage']);

		$this->priceObj = &t3lib_div::getUserObj('tx_ttproducts_field_price');	// new independant price object
	}


	/**
	 * Setting shipping, payment methods
	 *
	 * @param	[type]		$$basketRec: ...
	 * @return	[type]		...
	 */
	function setBasketExtras(&$basketRec) {
		global $TSFE;

		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

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

			foreach ($this->basket->basketExtra['shipping.']['replacePayment.'] as $k1 => $replaceArray)	{
				foreach ($replaceArray as $k2 => $value2)	{
					if (is_array($value2))	{
						$this->conf['payment.'][$k1][$k2] = array_merge($this->conf['payment.'][$k1][$k2], $value2);
					} else {
						$this->conf['payment.'][$k1][$k2] = $value2;
					}
				}
			}
		}

			// payment
		if ($this->conf['payment.']) {
			if ($excludePayment)	{
				$exclArr = t3lib_div::intExplode(',',$excludePayment);
				foreach($exclArr as $theVal)	{
					unset($this->conf['payment.'][$theVal]);
					unset($this->conf['payment.'][$theVal.'.']);
				}
			}

			$confArr = $this->cleanConfArr($this->conf['payment.']);
			foreach($confArr as $key => $val) {
				if ($val['show'] || !isset($val['show']))	{
					if ($val['type'] == 'fe_users')	{
						if (is_array($TSFE->fe_user->user))	{
							$paymentField = $tablesObj->get('fe_users')->getFieldName('payment');
							$paymentMethod = $TSFE->fe_user->user[$paymentField];
							$this->conf['payment.'][$key.'.']['title'] = $paymentMethod;
						} else {
							unset($this->conf['payment.'][$key.'.']);
						}
					}
					if (($val['visibleForGroupID'] != '') &&
						(!$tablesObj->get('fe_users')->isUserInGroup($TSFE->fe_user->user, $val['visibleForGroupID'])))	{
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
			$this->basket->basketExtra['payment'] = array($k);
			$this->basket->basketExtra['payment.'] = $this->conf['payment.'][$k.'.'];
		}
	} // setBasketExtras


	/**
	 * Check if payment/shipping option is available
	 *
	 * @param	[type]		$name: ...
	 * @param	[type]		$key: ...
	 * @return	[type]		...
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
	 * Fills in the subpartArray with data depending on payment and shipping
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	[type]		$markerArray: ...
	 * @param	[type]		$framework: ...
	 * @return	[type]		...
	 * @access private
	 */
	function getSubpartArray (&$subpartArray, &$markerArray, &$framework)	{

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
			$tmpSubpartArray['shipping'] = $this->cObj->getSubpart($framework,'###MESSAGE_SHIPPING_'.$shipKey.'###');
			$msgShipping .= $this->cObj->substituteMarkerArrayCached($tmpSubpartArray['shipping'],$markerArray);
		}
		$subpartArray['###MESSAGE_SHIPPING###'] = $msgShipping;

//		$tmpKey = $this->basket->basketExtra['shipping'];
//		if (is_array($tmpKey))	{
//			$tmpKey = current($tmpKey);
//		}
//		$tmpSubpartArray = array();
//		$tmpSubpartArray['shipping'] = $this->cObj->getSubpart($framework,'###MESSAGE_SHIPPING_'.$tmpKey.'###');
//		$subpartArray['###MESSAGE_SHIPPING###'] = $this->cObj->substituteMarkerArrayCached($tmpSubpartArray['shipping'], $markerArray);
//

		$payKeyArray = $this->basket->basketExtra['payment'];
		if (!is_array($payKeyArray))	{
			$payKeyArray = array($shipKeyArray);
		}
		reset ($payKeyArray);
		$payKey = current($payKeyArray);
		$tmpSubpartArray['payment'] = $this->cObj->getSubpart($framework,'###MESSAGE_PAYMENT_'.$payKey.'###');
		$subpartArray['###MESSAGE_PAYMENT###'] = $this->cObj->substituteMarkerArrayCached($tmpSubpartArray['payment'],$markerArray);

		foreach ($this->typeArray as $k => $type)	{
			$marker = strtoupper($type);
			foreach ($this->conf[$type.'.'] as $k2 => $confRow)	{
				if (!t3lib_div::testInt(substr($k2,0,-1)))	{
					continue;
				}
				$key = substr($k2,0,-1);
				if (is_numeric($key))	{
					$tmpMarker = '###MESSAGE_'.$marker.'_'.$key.'###';
					if ($key == $this->basket->basketExtra[$type][0])	{
						$tmpSubpart = $this->cObj->getSubpart($framework,$tmpMarker);
						$subpartArray[$tmpMarker] = $this->cObj->substituteMarkerArrayCached($tmpSubpart,$markerArray);
						//
					} else {
						$subpartArray[$tmpMarker] = '';
					}
				}
			}
		}
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	array		marker array
	 * @param	[type]		$value: ...
	 * @param	[type]		$activeArray: ...
	 * @return	array
	 * @access private
	 */
	function getItemMarkerArray (&$item, &$markerArray, $value, $activeArray)	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		$row = &$item;

		$markerArray['###VALUE###'] = $value;
		$markerArray['###CHECKED###'] = ($value==implode('-',$activeArray) ? ' checked':'');
		$markerArray['###TITLE###'] = $row['title'];
		$markerArray['###IMAGE###'] = $this->cObj->IMAGE($row['image.']);
	}


	/**
	 * Generates a radio or selector box for payment shipping
	 *
	 * @param	[type]		$pskey: ...
	 * @param	[type]		$calculatedArray: ...
	 * @param	[type]		$basketUrl: ...
	 * @return	[type]		...
	 */
	function generateRadioSelect($pskey, &$calculatedArray, $basketUrl)	{
			/*
			 The conf-array for the payment/shipping configuration has numeric keys for the elements
			 But there are also these properties:
				.radio	  [boolean]	Enables radiobuttons instead of the default, selector-boxes
				.wrap		[string]	<select>|</select> - wrap for the selectorboxes.  Only if .radio is false. See default value below
				.template	[string]	Template string for the display of radiobuttons.  Only if .radio is true. See default below
			 */
		global $TSFE;

		if (is_array($this->conf[$pskey.'.']))	{
			$type = $this->conf[$pskey.'.']['radio'];
		}

		if (!t3lib_div::testInt($type))	{
			$type = 0;
		}

		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$active = $this->basket->basketExtra[$pskey];
		$activeArray = is_array($active) ? $active : array($active);
		$confArr = $this->cleanConfArr($this->conf[$pskey.'.']);
		$out='';
		// tx_div2007_alpha::getPageLink_fh001(&$pibase, $id

		$submitCode = 'this.form.action=\''.$basketUrl.'\';this.form.submit();';
		$template = $this->conf[$pskey.'.']['template'] ? ereg_replace('\' *\. *\$pskey *\. *\'',$pskey, $this->conf[$pskey.'.']['template']) : '###IMAGE### <input type="radio" name="recs[tt_products]['.$pskey.']" onClick="'.$submitCode.'" value="###VALUE###"###CHECKED###> ###TITLE###<br>';
		$wrap = $this->conf[$pskey.'.']['wrap'] ? $this->conf[$pskey.'.']['wrap'] :'<select id="'.$pskey.'-select" name="recs[tt_products]['.$pskey.']" onChange="'.$submitCode.'">|</select>';
		$t = array();
		$actTitle = $this->basket->basketExtra[$pskey.'.']['title'];
		if (is_array($confArr))	{
			foreach($confArr as $key => $item)	{
				if (($item['show'] || !isset($item['show'])) &&
					(doubleval($item['showLimit']) >= doubleval($this->basket->calculatedArray['count']) || !isset($item['showLimit']) ||
					intval($item['showLimit']) == 0)) {
					$addItems = array();
					$itemTable = '';
					$t['title'] = $item['title'];
					if ($item['where.'] && strstr($t['title'], '###'))	{
						$tableName = key($item['where.']);
						if (($tableName == 'static_countries') && t3lib_extMgm::isLoaded('static_info_tables')) {
							$viewTagArray = array();
							$itemTable = $tablesObj->get($tableName);
							if (is_object($itemTable))	{
								$markerFieldArray = array();
								$parentArray = array();
								$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
								$fieldsArray = $markerObj->getMarkerFields(
									$item['title'],
									$itemTable->getTableObj()->tableFieldArray,
									$itemTable->getTableObj()->requiredFieldArray,
									$markerFieldArray,
									$itemTable->marker,
									$viewTagArray,
									$parentArray
								);
								$addItems = $itemTable->get ('', $item['where.'][$tableName], implode(',',$fieldsArray));
							}
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
								$item['title'] = $this->cObj->substituteMarkerArrayCached($t['title'], $markerArray);
								$value = $key . '-'.$row['uid'];
								if ($value == implode('-',$activeArray))	{
									$actTitle = $item['title'];
								}
							} else {
								$value = $key;
							}
							$markerArray = array();
							$this->getItemMarkerArray ($item, $markerArray, $value, $activeArray);
							$out .= $this->cObj->substituteMarkerArrayCached($template, $markerArray).chr(10);
						}
					} else {
						foreach ($addItems as $k1 => $row)	{
							if (is_array($row))	{
								$markerArray = array();
								$itemTable->getItemMarkerArray ($row, $markerArray, $fieldsArray);
								$item['title'] = $this->cObj->substituteMarkerArrayCached($t['title'], $markerArray);
								$item['title'] = htmlentities($item['title'],ENT_QUOTES,$TSFE->renderCharset);
								$value = $key . '-'.$row['uid'];
								if ($value == implode('-',$activeArray))	{
									$actTitle = $item['title'];
								}
							} else {
								$value = $key;
							}
							$out .= '<option value="'.$value.'"'.($value == implode('-',$activeArray) ? ' selected':'').'>'.$item['title'].'</option>'.chr(10);
						}
					}
				}
			}
		}
		if (strstr($actTitle, '###'))	{
			$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
			$markerArray = array();
			$viewTagArray = array();
			$parentArray = array();
			$fieldsArray = $markerObj->getMarkerFields(
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
			$actTitle = $this->cObj->substituteMarkerArrayCached($actTitle, $markerArray);
		}

		$this->basket->basketExtra[$pskey.'.']['title'] = $actTitle;
		if (!$type) {
			$out = $this->cObj->wrap($out,$wrap);
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
					// if more than one entry for priceTaxWherePIDMinPrice exists, the highest is value will be taken into account
				foreach ($confArr['WherePIDMinPrice.'] as $minPricePID=>$minPriceValue) {
					if (is_array($this->basket->itemArray[$minPricePID]) && $minPrice<doubleval($minPriceValue)) {
						$minPrice=$minPriceValue;
					}
				}
			}
			krsort($confArr);
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
			if (is_array($confArr['calc.']) && isset($confArr['calc.']['use']))	{
				$useArray = t3lib_div::trimExplode(',', $confArr['calc.']['use']);
				$shippingcalc = array();
				foreach ($this->conf['shippingcalc.'] as $k => $v)	{
					$kInt = trim($k, '.'); // substr($k, 0, strlen($k) - 1);
					if (in_array($kInt, $useArray))	{
						$shippingcalc[$k] = $v;
					}
				}
				include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_discountprice.php');
				$discountPrice = t3lib_div::makeInstance('tx_ttproducts_discountprice');
				$priceReduction = 0;
				$discountPrice->getCalculatedData($this->basket->itemArray, $shippingcalc, 'shipping', $priceReduction, $priceTotalTax);
			}

			// compare the price to the min. price
			if ($minPrice > $priceNew) {
				$priceNew = $minPrice;
			}
			// the total products price as from the payment/shipping is free
			$noCostsAmount = (double) $confArr['noCostsAmount'];
			if ($noCostsAmount && ($priceTotalTax >= $noCostsAmount)) {
				$priceNew = 0;
				$priceTax = $priceNoTax = 0;
			}
			$taxIncluded = $this->priceObj->getTaxIncluded();
			$priceTax += $this->priceObj->getPrice($priceNew,1,$tax,$taxIncluded,true);
			$priceNoTax += $this->priceObj->getPrice($priceNew,0,$tax,$taxIncluded,true);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$priceShippingTax: ...
	 * @param	[type]		$priceShippingNoTax: ...
	 * @param	[type]		$taxpercentage: ...
	 * @param	[type]		$taxIncluded: ...
	 * @return	[type]		...
	 */
	function addItemShippingPrices(&$priceShippingTax, &$priceShippingNoTax, $taxpercentage, $taxIncluded)	{
		foreach ($this->basket->itemArray as $sort=>$actItemArray) {
			// $actItemArray = all items array
			foreach ($actItemArray as $k2=>$actItem) {
				$row = &$actItem['rec'];
				if ($row['shipping'])	{
					$priceShippingTax += $this->priceObj->getPrice($row['shipping'],true,$taxpercentage,$taxIncluded,true);
					$priceShippingNoTax += $this->priceObj->getPrice($row['shipping'],false,$taxpercentage,$taxIncluded,true);
				}
				if ($row['bulkily'])	{
					$value = floatval($this->basket->basketExtra['shipping.']['bulkilyAddition']) * $actItem['count'];
					$tax = floatval($this->basket->basketExtra['shipping.']['bulkilyFeeTax']);
					$priceShippingTax += $this->priceObj->getPrice($value,true,$tax,$taxIncluded,true);
					$priceShippingNoTax += $this->priceObj->getPrice($value,false,$tax,$taxIncluded,true);
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$pskey: ...
	 * @param	[type]		$countTotal: ...
	 * @param	[type]		$priceTotalTax: ...
	 * @param	[type]		$priceTax: ...
	 * @param	[type]		$priceNoTax: ...
	 * @param	[type]		$taxpercentage: ...
	 * @return	[type]		...
	 */
	function getPrices ($pskey, $countTotal, $priceTotalTax, &$priceTax, &$priceNoTax, &$taxpercentage)	{

		$taxIncluded = $this->conf['TAXincluded'];
		if (is_array($this->basket->basketExtra[$pskey.'.']) && isset($this->basket->basketExtra[$pskey.'.']['TAXincluded']))	{
			$taxIncluded = $this->basket->basketExtra[$pskey.'.']['TAXincluded'];
		}
		$confArr = $this->basket->basketExtra[$pskey.'.']['price.'];
		$confArr = ($confArr ? $confArr : $this->basket->basketExtra[$pskey.'.']['priceTax.']);
		$taxpercentage = doubleVal($this->conf[$pskey.'.']['TAXpercentage']);
		$taxFrom = $this->getReplaceTaxPercentage();
		$this->priceObj->init($this->cObj, $this->conf[$pskey.'.'], 0);
		if ($confArr) {
			$this->getConfiguredPrice($taxpercentage, $confArr, $countTotal, $priceTotalTax, $priceTax, $priceNoTax);
		} else {
			$priceAdd = doubleVal($this->basket->basketExtra[$pskey.'.']['price']);

			if ($priceAdd)	{
				$priceTaxAdd = $this->priceObj->getPrice($priceAdd,true,$taxpercentage,$taxIncluded,true);
			} else {
				$priceTaxAdd = doubleVal($this->basket->basketExtra[$pskey.'.']['priceTax']);
			}
			$priceTax += $priceTaxAdd;
			$priceNoTaxAdd = doubleVal($this->basket->basketExtra[$pskey.'.']['priceNoTax']);
			if (isset($taxFrom) && is_double($taxFrom))	{
				$taxpercentage = $taxFrom;
			}

			if (!$priceNoTaxAdd) {
				$priceNoTaxAdd = $this->priceObj->getPrice($priceTaxAdd,false,$taxpercentage,true,true);
			}
			$priceNoTax += $priceNoTaxAdd;
		}
		if ($pskey == 'shipping')	{
			$this->addItemShippingPrices($priceTax, $priceNoTax, $taxpercentage, $taxIncluded);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$pskey: ...
	 * @param	[type]		$taxpercentage: ...
	 * @param	[type]		$priceShippingTax: ...
	 * @param	[type]		$priceShippingNoTax: ...
	 * @return	[type]		...
	 */
	function getSpecialPrices ($pskey, $taxpercentage, &$priceShippingTax, &$priceShippingNoTax)	{
		global $TSFE;

		$perc = doubleVal($this->basket->basketExtra[$pskey.'.']['percentOfGoodstotal']);
		if ($perc)  {
			$priceShipping = doubleVal(($this->basket->calculatedArray['priceTax']['goodstotal']/100)*$perc);
			$dum = $this->priceObj->getPrice($priceShipping,1,$taxpercentage);
			$taxIncluded = $this->priceObj->getTaxIncluded();
			$priceShippingTax = $priceShippingTax + $this->priceObj->getPrice($priceShipping,true,$taxpercentage,$taxIncluded,true);
			$priceShippingNoTax = $priceShippingNoTax + $this->priceObj->getPrice($priceShipping,false,$taxpercentage,$taxIncluded,true);
		}

		$calculationScript = $this->basket->basketExtra[$pskey.'.']['calculationScript'];
		if ($calculationScript) {
			$calcScript = $TSFE->tmpl->getFileName($calculationScript);
			if ($calcScript)	{
				$confScript = &$this->basket->basketExtra[$pskey.'.']['calculationScript.'];
				include($calcScript);
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$countTotal: ...
	 * @param	[type]		$priceTotalTax: ...
	 * @param	[type]		$priceShippingTax: ...
	 * @param	[type]		$priceShippingNoTax: ...
	 * @param	[type]		$pricePaymentTax: ...
	 * @param	[type]		$pricePaymentNoTax: ...
	 * @return	[type]		...
	 */
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
		$weigthFactor = doubleVal($this->basket->basketExtra['shipping.']['priceFactWeight']);
		if($weigthFactor > 0) {
			$priceShipping = $this->basket->calculatedArray['weight'] * $weigthFactor;
			$priceShippingTax += $this->priceObj->getPrice($priceShipping,true,$taxpercentage,$taxIncluded,true);
			$priceShippingNoTax += $this->priceObj->getPrice($priceShipping,false,$taxpercentage,$taxIncluded,true);
		}
		$countFactor = doubleVal($this->basket->basketExtra['shipping.']['priceFactCount']);
		if($countFactor > 0) {
			$priceShipping = $countTotal * $countFactor;
			$priceShippingTax += $this->priceObj->getPrice($priceShipping,true,$taxpercentage,$taxIncluded,true);
			$priceShippingNoTax += $this->priceObj->getPrice($priceShipping,false,$taxpercentage,$taxIncluded,true);
		}

		$this->getSpecialPrices ('shipping', $taxpercentage, $priceShippingTax, $priceShippingNoTax);

		$this->getPrices ('shipping', $countTotal, $priceTotalTax, $priceShippingTax, $priceShippingNoTax, $taxpercentage);
		$taxIncluded = $this->priceObj->getTaxIncluded();

			// Payment
		$pricePayment = $pricePaymentTax = $pricePaymentNoTax = 0;
		$taxpercentage = '';
		$perc = doubleVal($this->basket->basketExtra['payment.']['percentOfTotalShipping']);
		if ($perc)  {
			$payment = ($this->basket->calculatedArray['priceTax']['goodstotal'] + $this->basket->calculatedArray['priceTax']['shipping'] ) * doubleVal($perc);
			$pricePaymentTax += $this->priceObj->getPrice($payment,true,$taxpercentage,$taxIncluded,true);
			$pricePaymentNoTax += $this->priceObj->getPrice($payment,false,$taxpercentage,$taxIncluded,true);
		}
		$this->getSpecialPrices ('payment', $taxpercentage, $priceShippingTax, $priceShippingNoTax);
		$this->getPrices ('payment', $countTotal, $priceTotalTax, $pricePaymentTax, $pricePaymentNoTax, $taxpercentage);
	} // getPaymentShippingData


	/**
	 * Include handle script
	 *
	 * @param	[type]		$handleScript: ...
	 * @param	[type]		$confScript: ...
	 * @param	[type]		$activity: ...
	 * @param	[type]		$bFinalize: ...
	 * @param	[type]		$pibase: ...
	 * @param	[type]		$infoViewObj: ...
	 * @return	[type]		...
	 */
	function includeHandleScript($handleScript, &$confScript, $activity, &$bFinalize, &$pibase, &$infoViewObj)	{
		$content = '';
		include($handleScript);
		return $content;
	} // includeHandleScript


	/**
	 * get the replaceTAXpercentage from the shipping if available
	 *
	 * @return	[type]		...
	 */
	function getReplaceTaxPercentage()	{
		if (is_array($this->basket->basketExtra['shipping.']) && isset($this->basket->basketExtra['shipping.']['replaceTAXpercentage']))	{
			$rc = doubleval($this->basket->basketExtra['shipping.']['replaceTAXpercentage']);
		}
		return $rc;
	}


	/**
	 * get the delivery costs
	 *
	 * @return	[type]		...
	 */
	function getDeliveryCosts()	{
		$rc = $this->basket->calculatedArray['priceTax']['shipping'] + $this->basket->calculatedArray['priceTax']['payment'];
		return $rc;
	}


	/**
	 * get the where condition for a shipping entry
	 * E.g.:  30.where.static_countries = cn_short_local = 'Deutschland'
	 *
	 * @param	[type]		$tablename: ...
	 * @return	[type]		...
	 */
	function getWhere($tablename)	{
		if (is_array($this->basket->basketExtra['shipping.']) && isset($this->basket->basketExtra['shipping.']['where.']))	{
			switch ($tablename) {
				case 'static_countries':
					if (t3lib_extMgm::isLoaded('static_info_tables')) {
						$eInfo = tx_div2007_alpha::getExtensionInfo_fh001('static_info_tables');
						$sitVersion = $eInfo['version'];
					}
					if (version_compare($sitVersion, '2.0.1', '>='))	{
						$rc = $this->basket->basketExtra['shipping.']['where.'][$tablename];
					}
				break;
			}
		}
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getAddRequiredInfoFields()	{
		$rc = '';
		$tmp = $this->basket->basketExtra['payment.']['addRequiredInfoFields'];
		if ($tmp != '')	{
			$rc = trim($tmp);
		}
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function useCreditcard ()	{
		$rc = false;
		$payConf = &$this->basket->basketExtra['payment.'];
		if (is_array($payConf) && $payConf['creditcards'] != '')	{
			$rc = true;
		}
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function useAccount ()	{
		$rc = false;
		$payConf = &$this->basket->basketExtra['payment.'];
		if (is_array($payConf) && $payConf['accounts'] != '')	{
			$rc = true;
		}
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
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


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']);
}


?>
