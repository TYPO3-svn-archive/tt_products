<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Franz Holzinger <franz@ttproducts.de>
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
 * @author  René Fritz <r.fritz@colorcube.de>
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @author  Klaus Zierer <zierer@pz-systeme.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_paymentshipping {
	var $cObj;
	var $conf;
	var $config;
	var $basket;
	var $basketView;
	var $priceObj;	// price functions
	var $typeArray = array('shipping', 'payment');


	function init (&$cObj)	{
		global $TSFE;

		$this->cObj = &$cObj;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->basket = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$this->priceObj = t3lib_div::getUserObj('tx_ttproducts_field_price');	// new independant price object
	}


	public function getTypeArray () {
		return $this->typeArray;
	}


	function getScriptPrices ($pskey='shipping', &$calculatedArray, &$itemArray)	{
		$hookVar = 'scriptPrices';
		if ($hookVar && isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar]) &&
			is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar]) &&
			isset ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar][$pskey]) &&
			is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar][$pskey])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar][$pskey] as $classRef) {
				$hookObj= t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this);
				}
				if (method_exists($hookObj, 'getScriptPrices')) {
					$tmpArray = $hookObj->getScriptPrices($calculatedArray, $itemArray, $this->basket->basketExtra[$pskey], $this->basket->basketExtra[$pskey.'.']);
				}
			}
		}
	}


	/**
	 * Setting shipping, payment methods
	 */
	function setBasketExtras (&$basketRec) {
		global $TSFE;

		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');

			// shipping
		if ($this->conf['shipping.']) {
			ksort($this->conf['shipping.']);
			reset($this->conf['shipping.']);
			$shipArray = t3lib_div::trimExplode('-', $basketRec['tt_products']['shipping']);
			$k = intval ($shipArray[0]);
			if (!$this->checkExtraAvailable('shipping',$k)) {
				$temp = $this->cleanConfArr($this->conf['shipping.'],1);
				$shipArray[0] = $k = intval(key($temp));
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
	 */
	function checkExtraAvailable ($name,$key)	{
		$result = FALSE;

		if (is_array($this->conf[$name.'.'][$key.'.']) && (!isset($this->conf[$name.'.'][$key.'.']['show']) || $this->conf[$name.'.'][$key.'.']['show']))	{
			$result = TRUE;
		}

		return $result;
	} // checkExtraAvailable


	/**
	 * Template marker substitution
	 * Fills in the subpartArray with data depending on payment and shipping
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @access private
	 */
	function getSubpartArrays ($markerArray, &$subpartArray, &$wrappedSubpartArray, $framework)	{

		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');

		if (strpos($handleLib, 'transactor') !== FALSE && t3lib_extMgm::isLoaded($handleLib)) {

			$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');
				// Payment Transactor
			tx_transactor_api::init($langObj, '', $conf);

			tx_transactor_api::getItemMarkerSubpartArrays(
				$this->basket->basketExtra['payment.']['handleLib.'],
				$subpartArray,
				$wrappedSubpartArray
			);
		} else {	// markers for the missing payment transactor extension
			$wrappedSubpartArray['###MESSAGE_PAYMENT_TRANSACTOR_NO###'] = '';
			$subpartArray['###MESSAGE_PAYMENT_TRANSACTOR_YES###'] = '';
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

			$subFrameWork = $this->cObj->getSubpart($framework, '###MESSAGE_SHIPPING###');
			if ($subFrameWork != '') {
				$tmpSubpartArray['shipping'] = $this->cObj->getSubpart($subFrameWork, '###MESSAGE_SHIPPING_' . $shipKey . '###');
				$subpartArray['###MESSAGE_SHIPPING###'] .= $this->cObj->substituteMarkerArrayCached($tmpSubpartArray['shipping'], $markerArray);
			}
			$subpartArray['###MESSAGE_SHIPPING_NE_' . $shipKey . '###'] = '';
		}

		$tagArray = &$markerObj->getAllMarkers($framework);

		foreach($this->typeArray as $k => $type)	{
			$marker = strtoupper($type);
			$tmpMarkerPrefix = 'MESSAGE_' . $marker;

			if (isset($this->conf[$type . '.']) && is_array($this->conf[$type . '.']))	{
				foreach($this->conf[$type . '.'] as $k2 => $confRow)	{

					if (
						!tx_div2007_core::testInt(substr($k2, 0, -1))
					) {
						continue;
					}
					$key = substr($k2, 0, -1);
					if (is_numeric($key))	{
						$tmpMarker = '###' . $tmpMarkerPrefix . '_' . $key . '###';
						if ($key == $this->basket->basketExtra[$type][0])	{
							$tmpSubpart = $this->cObj->getSubpart($framework, $tmpMarker);
							$subpartArray['###' . $tmpMarkerPrefix . '###'] = $this->cObj->substituteMarkerArrayCached($tmpSubpart, $markerArray);
							$wrappedSubpartArray[$tmpMarker] = '';
						} else {
							$subpartArray[$tmpMarker] = '';
						}
					}
				}
			}
			foreach($tagArray as $k3 => $v3)	{
				if (strpos($k3, $tmpMarkerPrefix) === 0 && !isset($subpartArray['###' . $k3 . '###']))	{

					if (strpos($k3, '_NE_') !== FALSE)	{
						$wrappedSubpartArray['###' . $k3 . '###'] = '';
						$tmpSubpartArray[$type] = $this->cObj->getSubpart($framework, '###' . $k3 . '###');
						$subpartArray['###'.$tmpMarkerPrefix.'###'] .= $this->cObj->substituteMarkerArrayCached($tmpSubpartArray[$type], $markerArray);
					} else if (!isset($wrappedSubpartArray['###' . $k3 . '###'])) {
						$subpartArray['###' . $k3 . '###'] = '';
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
	 * @return	array
	 * @access private
	 */
	public function getModelMarkerArray ($theCode, $title, $value, $imageCode, $activeArray, &$markerArray) {

			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		$markerArray['###VALUE###'] = $value;
		$markerArray['###CHECKED###'] = ($value == implode('-', $activeArray) ? ' checked="checked"' : '');
		$markerArray['###TITLE###'] = $title;
		$markerArray['###IMAGE###'] = $imageCode;
	}


	/**
	 * Generates a radio or selector box for payment shipping
	 */
	function generateRadioSelect ($theCode, $pskey, &$calculatedArray, $basketUrl)	{
			/*
			 The conf-array for the payment/shipping configuration has numeric keys for the elements
			 But there are also these properties:
				.radio	  [boolean]	Enables radiobuttons instead of the default, selector-boxes
				.wrap		[string]	<select>|</select> - wrap for the selectorboxes.  Only if .radio is FALSE. See default value below
				.template	[string]	Template string for the display of radiobuttons.  Only if .radio is TRUE. See default below
			 */
		global $TSFE;

		if (is_array($this->conf[$pskey.'.']))	{
			$type = $this->conf[$pskey.'.']['radio'];
		}

		if (
			!tx_div2007_core::testInt($type)
		) {
			$type = 0;
		}
		$bUseXHTML = $TSFE->config['config']['xhtmlDoctype'] != '';
		$selectedText = ($bUseXHTML ? 'selected="selected"' : 'selected');

		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$active = $this->basket->basketExtra[$pskey];
		$activeArray = is_array($active) ? $active : array($active);
		$confArr = $this->cleanConfArr($this->conf[$pskey.'.']);
		$out='';
		$submitCode = 'this.form.action=\''.$basketUrl.'\';this.form.submit();';
	//	$template = $this->conf[$pskey.'.']['template'] ? ereg_replace('\' *\. *\$pskey *\. *\'',$pskey, $this->conf[$pskey.'.']['template']) : '###IMAGE### <input type="radio" name="recs[tt_products]['.$pskey.']" onClick="'.$submitCode.'" value="###VALUE###"###CHECKED###> ###TITLE###<br>';

		$template = (
			$this->conf[$pskey.'.']['template'] ?
				preg_replace('/[[:space:]]*\\.[[:space:]]*' . $pskey . '[[:space:]]*\\.[[:space:]]*/', $pskey, $this->conf[$pskey . '.']['template']) :
				'###IMAGE### <input type="radio" name="recs[tt_products][' . $pskey . ']" onClick="' . $submitCode . '" value="###VALUE###"###CHECKED###> ###TITLE###<br>'
			);

		$wrap = $this->conf[$pskey.'.']['wrap'] ? $this->conf[$pskey.'.']['wrap'] :'<select id="'.$pskey.'-select" name="recs[tt_products]['.$pskey.']" onChange="'.$submitCode.'">|</select>';
		$t = array();

		$actTitle = $this->basket->basketExtra[$pskey.'.']['title'];

		if (is_array($confArr))	{
			foreach($confArr as $key => $item)	{

				if (
					($item['show'] || !isset($item['show'])) &&
					(!isset($item['showLimit']) || doubleval($item['showLimit']) >= doubleval($this->basket->calculatedArray['count']) ||
					intval($item['showLimit']) == 0)
				) {
					$addItems = array();
					$itemTable = '';
					$t['title'] = $item['title'];
					if ($item['where.'] && strpos($t['title'], '###') !== FALSE)	{
						$tableName = key($item['where.']);
						if (($tableName == 'static_countries') && t3lib_extMgm::isLoaded('static_info_tables')) {
							$viewTagArray = array();
							$itemTableView = $tablesObj->get($tableName,TRUE);
							$itemTable = $itemTableView->getModelObj();
							if (is_object($itemTable))	{
								$markerFieldArray = array();
								$parentArray = array();
								$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
								$fieldsArray = $markerObj->getMarkerFields(
									$item['title'],
									$itemTable->getTableObj()->tableFieldArray,
									$itemTable->getTableObj()->requiredFieldArray,
									$markerFieldArray,
									$itemTable->marker,
									$viewTagArray,
									$parentArray
								);
								$addItems = $itemTable->get('',0,FALSE, $item['where.'][$tableName],'','','', implode(',',$fieldsArray));

								if (isset($addItems) && is_array($addItems))	{
									foreach ($addItems as $k1 => $row)	{
										foreach ($row as $field => $v)	{
											$addItems[$k1][$field] = $TSFE->csConv($v, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['static_info_tables']['charset']);
										}
									}
								}
							}
						}
					}

					if (!count($addItems))	{
						$addItems = array('0' => '');
					}

					if (isset($addItems) && is_array($addItems))	{
						if ($type)  {	// radio

							foreach($addItems as $k1 => $row)	{
								$image = '';
								if (is_array($row))	{
									$markerArray = array();
									$itemTableView->getRowMarkerArray($row, $markerArray, $fieldsArray);
									$title = $this->cObj->substituteMarkerArrayCached($t['title'], $markerArray);
									$title = htmlentities($title,ENT_QUOTES,$TSFE->renderCharset);
									$value = $key . '-' . $row['uid'];
									if ($value == implode('-',$activeArray))	{
										$actTitle = $item['title'];
									}
									if (isset($row['image.'])) {
										$image = $row['image.'];
									}
								} else {
									$value = $key;
									$title = $item['title'];
									if (isset($row['image.'])) {
										$image = $item['image.'];
									}
								}
								$markerArray = array();
								$imageCode = '';
								if ($image != '') {
									$imageCode = $this->cObj->IMAGE($image);
									if ($theCode == 'EMAIL') {
										tx_div2007_alpha5::fixImageCodeAbsRefPrefix($imageCode);
									}
								}

								$this->getModelMarkerArray(
									$theCode,
									$title,
									$value,
									$imageCode,
									$activeArray,
									$markerArray
								);

								$out .= $this->cObj->substituteMarkerArrayCached($template, $markerArray) . chr(10);
							}
						} else {
							foreach ($addItems as $k1 => $row)	{
								if (is_array($row))	{
									$markerArray = array();
									$itemTableView->getRowMarkerArray ($row, $markerArray, $fieldsArray);
									$title = $this->cObj->substituteMarkerArrayCached($t['title'], $markerArray);
									$title = htmlentities($title,ENT_QUOTES,$TSFE->renderCharset);
									$value = $key . '-' . $row['uid'];
									if ($value == implode('-', $activeArray))	{
										$actTitle = $item['title'];
									}
								} else {
									$value = $key;
									$title = $item['title'];
								}
								$out .= '<option value="' . $value . '"' . ($value == implode('-',$activeArray) ? ' ' . $selectedText : '') . '>' . $title . '</option>' . chr(10);
							}
						}
					}
				}
			}
		}

		if (strstr($actTitle, '###'))	{
			$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
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
				$markerArray['###' . $tag . '###'] = '?';
			}
			$actTitle = $this->cObj->substituteMarkerArrayCached($actTitle, $markerArray);
		}
		$this->basket->basketExtra[$pskey . '.']['title'] = $actTitle;
		if (!$type) {
			$out = $this->cObj->wrap($out, $wrap);
		}
		return $out;
	} // generateRadioSelect


	public function cleanConfArr ($confArray, $checkShow = 0)	{
		$outArr=array();

		if (is_array($confArray)) {
			foreach($confArray as $key => &$val)	{

				if (
					intval($key) &&
					is_array($val) &&
					!tx_div2007_core::testInt($key) &&
					(!$checkShow || !isset($val['show']) || $val['show'])
				) {
					$i = intval($key);
 					$outArr[$i] = $val;
				}
			}
		}
		ksort($outArr);
		reset($outArr);

		return $outArr;
	} // cleanConfArr


	function getConfiguredPrice (&$row, &$confArr, &$countTotal, &$priceTotalTax, &$priceTax, &$priceNoTax,  &$funcParams='') {

		if (is_array($confArr))	{
			$minPrice=0;
			$priceNew=0;
			if ($confArr['WherePIDMinPrice.']) {
					// compare PIDList with values set in priceTaxWherePIDMinPrice in the SETUP
					// if they match, get the min. price
					// if more than one entry for priceTaxWherePIDMinPrice exists, the highest is value will be taken into account
				foreach ($confArr['WherePIDMinPrice.'] as $minPricePID => $minPriceValue) {
					foreach ($this->basket->itemArray as $sort => $actItemArray) {
						foreach ($actItemArray as $k1 => $actItem) {
							$tmpRow = &$actItem['rec'];
							$pid = intval($tmpRow['pid']);
							if ($pid == $minPricePID) {
								$minPrice = $minPriceValue;
							}
						}
					}
				}
			}
			krsort($confArr);
			if ($confArr['type'] == 'count') {
				foreach ($confArr as $k1 => $price1)	{
					if (
						tx_div2007_core::testInt($k1) &&
						$countTotal >= $k1
					) {
						$priceNew = $price1;
						break;
					}
				}
			} else if ($confArr['type'] == 'weight') {
				foreach ($confArr as $k1 => $price1)	{
					if (
						tx_div2007_core::testInt($k1) &&
						$this->basket->calculatedArray['weight'] * 1000 >= $k1
					) {
						$priceNew = $price1;
						break;
					}
				}
			} else if ($confArr['type'] == 'price') {
				foreach ($confArr as $k1 => $price1)	{
					if (
						tx_div2007_core::testInt($k1) &&
						$priceTotalTax >= $k1
					) {
						$priceNew = $price1;
						break;
					}
				}
			} else if ($confArr['type'] == 'objectMethod' && isset($confArr['class'])) {
				$obj= t3lib_div::getUserObj($confArr['class']);
				if (method_exists($obj, 'getConfiguredPrice')){
					$funcParams = $confArr['method.'];
					$priceNew = $obj->getConfiguredPrice($row, $confArr, $countTotal, $priceTotalTax, $priceTax, $priceNoTax, $funcParams);
				} else {
					$priceNew = '0';
				}
			}

			if (is_array($confArr['calc.']) && isset($confArr['calc.']['use']) && isset($this->conf['shippingcalc.']) && is_array($this->conf['shippingcalc.']))	{
				$useArray = t3lib_div::trimExplode(',', $confArr['calc.']['use']);
				$shippingcalc = array();

				foreach ($this->conf['shippingcalc.'] as $k => $v)	{
					$kInt = trim($k, '.'); // substr($k, 0, strlen($k) - 1);
					if (in_array($kInt, $useArray))	{
						$shippingcalc[$k] = $v;
					}
				}
				include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_discountprice.php');
				$discountPriceObj = t3lib_div::makeInstance('tx_ttproducts_discountprice');
				$priceReduction = array();
				$discountPriceObj->getCalculatedData(
					$this->basket->itemArray,
					$shippingcalc,
					'shipping',
					$priceReduction,
					$priceTotalTax,
					FALSE
				);
			}

			if(is_array($funcParams)){
				$hookObj= t3lib_div::getUserObj($funcParams['class']);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this);
				}
				if (method_exists($hookObj, 'getConfiguredPrice')) {
					$tmpArray = $hookObj->getConfiguredPrice($tax, $confArr, $countTotal, $priceTotalTax, $priceTax, $priceNoTax,$funcParams);
				};
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
			$priceTax += $this->priceObj->getPrice($priceNew, 1, $row, $taxIncluded, TRUE);
			$priceNoTax += $this->priceObj->getPrice($priceNew, 0, $row, $taxIncluded, TRUE);
		}
	}


	function addItemShippingPrices (&$priceShippingTax, &$priceShippingNoTax, $row, $taxIncluded)	{

		foreach ($this->basket->itemArray as $sort=>$actItemArray) {

			// $actItemArray = all items array
			foreach ($actItemArray as $k2 => $actItem) {
				$row = &$actItem['rec'];

				if ($row['bulkily'])	{
					$value = floatval($this->basket->basketExtra['shipping.']['bulkilyAddition']) * $actItem['count'];
					$row['tax'] = floatval($this->basket->basketExtra['shipping.']['bulkilyFeeTax']);
					$priceShippingTax += $this->priceObj->getPrice($value, TRUE, $row, $taxIncluded, TRUE);

					$priceShippingNoTax += $this->priceObj->getPrice($value, FALSE, $row, $taxIncluded, TRUE);
				}
			}
		}
	}


	function getPrices ($pskey, $row, $countTotal, $priceTotalTax, &$priceTax, &$priceNoTax)	{

		$taxIncluded = $this->conf['TAXincluded'];
		if (is_array($this->basket->basketExtra[$pskey . '.']) && isset($this->basket->basketExtra[$pskey . '.']['TAXincluded']))	{
			$taxIncluded = $this->basket->basketExtra[$pskey . '.']['TAXincluded'];
		}
		$confArr = $this->basket->basketExtra[$pskey . '.']['price.'];
		$confArr = ($confArr ? $confArr : $this->basket->basketExtra[$pskey . '.']['priceTax.']);
		$this->priceObj->init($this->cObj, $this->conf[$pskey . '.'], 0);
		if ($confArr) {
			$this->getConfiguredPrice($row, $confArr, $countTotal, $priceTotalTax, $priceTax, $priceNoTax, $tmp = '');
		} else {
			$priceAdd = doubleVal($this->basket->basketExtra[$pskey . '.']['price']);

			if ($priceAdd)	{
				$priceTaxAdd = $this->priceObj->getPrice($priceAdd, TRUE, $row, $taxIncluded, TRUE);
			} else {
				$priceTaxAdd = doubleVal($this->basket->basketExtra[$pskey . '.']['priceTax']);
			}
			$priceTax += $priceTaxAdd;

			$priceNoTaxAdd = doubleVal($this->basket->basketExtra[$pskey . '.']['priceNoTax']);

			if (!$priceNoTaxAdd) {
				$priceNoTaxAdd = $this->priceObj->getPrice($priceTaxAdd, FALSE, $row, TRUE, TRUE);
			}
			$priceNoTax += $priceNoTaxAdd;
		}

		if ($pskey == 'shipping')	{
			$this->addItemShippingPrices($priceTax, $priceNoTax, $row, $taxIncluded);
		}
	}


	function getSpecialPrices ($pskey, $row, &$priceShippingTax, &$priceShippingNoTax)	{
		global $TSFE;

		$perc = doubleVal($this->basket->basketExtra[$pskey.'.']['percentOfGoodstotal']);
		if ($perc)  {
			$priceShipping = doubleVal(($this->basket->calculatedArray['priceTax']['goodstotal'] / 100) * $perc);
			$dum = $this->priceObj->getPrice($priceShipping, TRUE, $row);
			$taxIncluded = $this->priceObj->getTaxIncluded();
			$priceShippingTax = $priceShippingTax + $this->priceObj->getPrice($priceShipping, TRUE, $row, $taxIncluded, TRUE);
			$priceShippingNoTax = $priceShippingNoTax + $this->priceObj->getPrice($priceShipping, FALSE, $row, $taxIncluded, TRUE);
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


	function getPaymentShippingData (
			$countTotal,
			$priceTotalTax,
			$shippingRow,
			$paymentRow,
			&$priceShippingTax,
			&$priceShippingNoTax,
			&$pricePaymentTax,
			&$pricePaymentNoTax
		) {
		global $TSFE;

		$row = $shippingRow;

		// Shipping
		$weigthFactor = doubleVal($this->basket->basketExtra['shipping.']['priceFactWeight']);
		if($weigthFactor > 0) {
			$priceShipping = $this->basket->calculatedArray['weight'] * $weigthFactor;
			$priceShippingTax += $this->priceObj->getPrice($priceShipping, TRUE, $row, $taxIncluded, TRUE);
			$priceShippingNoTax += $this->priceObj->getPrice($priceShipping, FALSE, $row, $taxIncluded, TRUE);
		}
		$countFactor = doubleVal($this->basket->basketExtra['shipping.']['priceFactCount']);
		if($countFactor > 0) {
			$priceShipping = $countTotal * $countFactor;
			$priceShippingTax += $this->priceObj->getPrice($priceShipping, TRUE, $row, $taxIncluded, TRUE);
			$priceShippingNoTax += $this->priceObj->getPrice($priceShipping, FALSE, $row, $taxIncluded, TRUE);
		}
		$this->getSpecialPrices('shipping', $row, $priceShippingTax, $priceShippingNoTax);

		$this->getPrices('shipping', $row, $countTotal, $priceTotalTax, $priceShippingTax, $priceShippingNoTax);

		$taxIncluded = $this->priceObj->getTaxIncluded();

			// Payment
		$pricePayment = $pricePaymentTax = $pricePaymentNoTax = 0;
		$taxpercentage = '';
		$row = $paymentRow;
		$perc = doubleVal($this->basket->basketExtra['payment.']['percentOfTotalShipping']);

		if ($perc)  {
			$payment = ($this->basket->calculatedArray['priceTax']['goodstotal'] + $this->basket->calculatedArray['priceTax']['shipping'] ) * doubleVal($perc);
			$pricePaymentTax += $this->priceObj->getPrice($payment, TRUE, $row, $taxIncluded, TRUE);
			$pricePaymentNoTax += $this->priceObj->getPrice($payment, FALSE, $row, $taxIncluded, TRUE);
		}
		$this->getSpecialPrices('payment', $row, $pricePaymentTax, $pricePaymentNoTax);
		$this->getPrices('payment', $row, $countTotal, $priceTotalTax, $pricePaymentTax, $pricePaymentNoTax);
	} // getPaymentShippingData


	/**
	 * Include handle script
	 */
	function includeHandleScript ($handleScript, &$confScript, $activity, &$bFinalize, $pibase, $infoViewObj)	{
		$content = '';
		$infoViewObj = t3lib_div::getUserObj('&tx_ttproducts_info_view');

		include($handleScript);
		return $content;
	} // includeHandleScript


	/**
	 * get the TAXpercentage from the shipping if available
	 */
	function getTaxPercentage ($pskey = 'shipping')	{

		if (is_array($this->basket->basketExtra[$pskey . '.']) && isset($this->basket->basketExtra[$pskey . '.']['TAXpercentage']))	{
			$rc = doubleval($this->basket->basketExtra[$pskey . '.']['TAXpercentage']);
		} else {
			$rc = $this->conf[$pskey . '.']['TAXpercentage'];
		}
		$rc = doubleval($rc);
		return $rc;
	}


	/**
	 * get the replaceTAXpercentage from the shipping if available
	 */
	function getReplaceTaxPercentage ($pskey = 'shipping')	{
		if (is_array($this->basket->basketExtra[$pskey . '.']) && isset($this->basket->basketExtra[$pskey . '.']['replaceTAXpercentage']))	{
			$rc = doubleval($this->basket->basketExtra[$pskey . '.']['replaceTAXpercentage']);
		}
		return $rc;
	}


	/**
	 * get the delivery costs
	 */
	function getDeliveryCosts ()	{
		$rc = $this->basket->calculatedArray['priceTax']['shipping'] + $this->basket->calculatedArray['priceTax']['payment'];
		return $rc;
	}


	/**
	 * get the where condition for a shipping entry
	 * E.g.:  30.where.static_countries = cn_short_local = 'Deutschland'
	 */
	function getWhere ($tablename)	{
		if (is_array($this->basket->basketExtra['shipping.']) && isset($this->basket->basketExtra['shipping.']['where.']))	{
			switch ($tablename) {
				case 'static_countries':
					if (t3lib_extMgm::isLoaded('static_info_tables')) {
						$eInfo = tx_div2007_alpha5::getExtensionInfo_fh003('static_info_tables');
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

	public function getAddRequiredInfoFields ($type) {
		$resultArray = array();
		$pskeyArray = $this->getTypeArray();

		foreach ($pskeyArray as $pskey) {
			if (
				isset($this->basket->basketExtra[$pskey . '.']) &&
				is_array($this->basket->basketExtra[$pskey . '.'])
			) {
				$tmp = '';

				if (
					isset($this->basket->basketExtra[$pskey . '.']['addRequiredInfoFields.']) &&
					isset($this->basket->basketExtra[$pskey . '.']['addRequiredInfoFields.'][$type])
				) {
					$tmp = $this->basket->basketExtra[$pskey . '.']['addRequiredInfoFields.'][$type];
				} else {
					$tmp = $this->basket->basketExtra[$pskey . '.']['addRequiredInfoFields'];
				}

				if ($tmp != '') {
					$resultArray[] = trim($tmp);
				}
			}
		}
		$result = implode(',', $resultArray);
		return $result;
	}

	function get ($pskey, $setup)	{
		$rc = '';
		$tmp = $this->basket->basketExtra[$pskey . '.'][$setup];
		if ($tmp != '')	{
			$rc = trim($tmp);
		}
		return $rc;
	}


	function useCreditcard ()	{
		$rc = FALSE;
		$payConf = &$this->basket->basketExtra['payment.'];
		if (is_array($payConf) && $payConf['creditcards'] != '')	{
			$rc = TRUE;
		}
		return $rc;
	}


	function useAccount ()	{
		$rc = FALSE;
		$payConf = &$this->basket->basketExtra['payment.'];
		if (is_array($payConf) && $payConf['accounts'] != '')	{
			$rc = TRUE;
		}
		return $rc;
	}


	public function getHandleLib ($request)	{ // getGatewayRequestExt

		$rc = FALSE;
		$basketObj = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$payConf = $basketObj->basketExtra['payment.'];

		if (is_array($payConf))	{
			$handleLib = $payConf['handleLib'];
		}

		if (
			(strpos($handleLib,'transactor') !== FALSE || strpos($handleLib, 'paymentlib') !== FALSE) &&
			is_array($payConf['handleLib.']) &&
			$payConf['handleLib.']['gatewaymode'] == $request &&
			t3lib_extMgm::isLoaded($handleLib)
		) {
			$rc = $handleLib;
		}

		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']);
}

?>