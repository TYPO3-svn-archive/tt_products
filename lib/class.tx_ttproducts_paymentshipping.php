<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2010 Franz Holzinger <franz@ttproducts.de>
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
	protected $typeArray = array('handling','shipping','payment');


	public function init (&$cObj)	{
		global $TSFE;

		$this->cObj = &$cObj;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->basket = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$this->priceObj = &t3lib_div::getUserObj('tx_ttproducts_field_price');	// new independant price object
	}


	public function getTypeArray ()	{
		return $this->typeArray;
	}


	public function getScriptPrices ($pskey='shipping', &$calculatedArray, &$itemArray)	{
		$hookVar = 'scriptPrices';
		if ($hookVar && isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar]) &&
			is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar]) &&
			isset ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar][$pskey]) &&
			is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar][$pskey])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar][$pskey] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
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
	public function storeHandlingShipping ($basketRec, $pskey, $subkey, $confArray, &$excludePayment, &$excludeHandling) {
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		ksort($confArray);
		if ($subkey != '')	{
			$valueArray = t3lib_div::trimExplode('-', $basketRec['tt_products'][$pskey][$subkey]);
		} else {
			$valueArray = t3lib_div::trimExplode('-', $basketRec['tt_products'][$pskey]);
		}
		$k = intval($valueArray[0]);
		if (!$this->checkExtraAvailable($confArray[$k . '.'])) {
			$temp = $this->cleanConfArr($confArray,1);
			$valueArray[0] = $k = intval(key($temp));
		}
		if ($subkey != '')	{
			$basketObj->basketExtra[$pskey . '.'][$subkey] = $valueArray;
			$basketObj->basketExtra[$pskey . '.'][$subkey . '.'] = $confArray[$k . '.'];
			if ($pskey == 'shipping')	{
				$newExcludePayment = trim($basketObj->basketExtra[$pskey . '.'][$subkey . '.']['excludePayment']);
				$newExcludeHandling = trim($basketObj->basketExtra[$pskey . '.'][$subkey . '.']['excludeHandling']);
			}
		} else {
			$basketObj->basketExtra[$pskey] = $valueArray;
			$basketObj->basketExtra[$pskey . '.'] = $confArray[$k . '.'];
			if ($pskey == 'shipping')	{
				$newExcludePayment = trim($basketObj->basketExtra[$pskey . '.']['excludePayment']);
				$newExcludeHandling = trim($basketObj->basketExtra[$pskey . '.']['excludeHandling']);
			}
		}
		if ($newExcludePayment != '')	{
			$excludePayment = ($excludePayment != '' ? $excludePayment . ',' : '') . $newExcludePayment;
		}
		if ($newExcludeHandling != '')	{
			$excludeHandling = ($excludeHandling != '' ? $excludeHandling . ',' : '') . $newExcludeHandling;
		}
	}


	/**
	 * Setting shipping, payment methods
	 */
	public function setBasketExtras (&$basketRec) {
		global $TSFE;

		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');

		// handling and shipping
		$pskeyArray = array('shipping' => FALSE, 'handling' =>TRUE);	// keep this order, because shipping can unable some handling configuration
		$excludePayment = '';
		$excludeHandling = '';
		foreach ($pskeyArray as $pskey => $bIsMulti)	{

			if ($this->conf[$pskey . '.']) {

				if ($bIsMulti) 	{
					ksort($this->conf[$pskey . '.']);
					foreach ($this->conf[$pskey . '.'] as $k => $confArray)	{

						if (strpos($k,'.') == strlen($k) - 1)	{
							$k1 = substr($k,0,strlen($k) - 1);
							if (
								class_exists('t3lib_utility_Math') ?
								t3lib_utility_Math::canBeInterpretedAsInteger($k1) :
								t3lib_div::testInt($k1)
							) {
								$this->storeHandlingShipping($basketRec, $pskey, $k1, $confArray, $excludePayment, $excludeHandling);
							}
						}
					}
				} else {
					$confArray = $this->conf[$pskey . '.'];
					$this->storeHandlingShipping($basketRec, $pskey, '', $confArray, $excludePayment, $excludeHandling);
				}
			}
				// overwrite handling from shipping
			if ($pskey == 'shipping' && $this->conf['handling.']) {
				if ($excludeHandling)	{
					$exclArr = t3lib_div::intExplode(',', $excludeHandling);
					foreach($exclArr as $theVal)	{
						unset($this->conf['handling.'][$theVal]);
						unset($this->conf['handling.'][$theVal . '.']);
					}
				}
			}
		}

		// overwrite payment from shipping
		if (is_array($basketObj->basketExtra['shipping.']) &&
			is_array($basketObj->basketExtra['shipping.']['replacePayment.']))	{
			if (!$this->conf['payment.'])	{
				$this->conf['payment.'] = array();
			}

			foreach ($basketObj->basketExtra['shipping.']['replacePayment.'] as $k1 => $replaceArray)	{
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
				$exclArr = t3lib_div::intExplode(',', $excludePayment);
				foreach($exclArr as $theVal)	{
					unset($this->conf['payment.'][$theVal]);
					unset($this->conf['payment.'][$theVal.'.']);
				}
			}

			$confArray = $this->cleanConfArr($this->conf['payment.']);
			foreach($confArray as $key => $val) {
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
			$k = intval($basketRec['tt_products']['payment']);
			if (!$this->checkExtraAvailable($this->conf['payment.'][$k . '.']))	{
				$temp = $this->cleanConfArr($this->conf['payment.'],1);
				$k = intval(key($temp));
			}
			$basketObj->basketExtra['payment'] = array($k);
			$basketObj->basketExtra['payment.'] = $this->conf['payment.'][$k.'.'];
		}
	} // setBasketExtras


	/**
	 * Check if payment/shipping option is available
	 */
	public function checkExtraAvailable ($confArray)	{
		$result = FALSE;

		if (is_array($confArray) && (!isset($confArray['show']) || $confArray['show']))	{
			$result = TRUE;
		}

		return $result;
	} // checkExtraAvailable


	protected function helperSubpartArray ($markerPrefix, $bActive, $keyMarker, $confRow, $framework, $markerArray, &$subpartArray, &$wrappedSubpartArray)	{

		$theMarker = '###' . $markerPrefix . '_' . $keyMarker . '###';

		if ($bActive)	{
			$wrappedSubpartArray[$theMarker] = '';
			// $tmpSubpart = $this->cObj->getSubpart($framework,$theMarker);
			// $subpartArray[$theMarker] = $this->cObj->substituteMarkerArrayCached($tmpSubpart,$markerArray);
// +++
		} else {
			$subpartArray[$theMarker] = '';
		}
	}


	/**
	 * Template marker substitution
	 * Fills in the subpartArray with data depending on payment and shipping
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @access private
	 */
	public function getSubpartArrays (&$markerArray, &$subpartArray, &$wrappedSubpartArray, &$framework)	{

		$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');

		$typeArray = $this->getTypeArray();
		$psArray = array('payment', 'shipping');
		$psMessageArray = array();
		$tmpSubpartArray = array();

		$handleLib = $basketObj->basketExtra['payment.']['handleLib'];

		if (strpos($handleLib,'transactor') !== FALSE && t3lib_extMgm::isLoaded($handleLib))	{

			$langObj = &t3lib_div::getUserObj('&tx_ttproducts_language');
				// Payment Transactor
			require_once(t3lib_extMgm::extPath($handleLib) . 'lib/class.tx_' . $handleLib . '_api.php');
			tx_transactor_api::init($langObj, $this->cObj, $this->conf);

			tx_transactor_api::getItemMarkerSubpartArrays(
				$basketObj->basketExtra['payment.']['handleLib.'],
				$subpartArray,
				$wrappedSubpartArray
			);
		} else {	// markers for the missing payment transactor extension
			$wrappedSubpartArray['###MESSAGE_PAYMENT_TRANSACTOR_NO###'] = '';
			$subpartArray['###MESSAGE_PAYMENT_TRANSACTOR_YES###'] = '';
		}

		foreach($typeArray as $k => $pskey)	{

			if (in_array($pskey, $psArray))	{
				$marker = strtoupper($pskey);
				$keyArray = $basketObj->basketExtra[$pskey];
				if (!is_array($keyArray))	{
					$keyArray = array($keyArray);
				}
				$psKey = '';
				$psMessageArray[$pskey] = '';

				foreach ($keyArray as $k => $value)	{
					if ($psKey)	{
						$psKey .= '_';
					}
					$psKey .= $value;
					$tmpSubpartArray[$pskey] = $this->cObj->getSubpart($framework,'###MESSAGE_' . $marker . '_' . $psKey . '###');
					$psMessageArray[$pskey] .= $this->cObj->substituteMarkerArray($tmpSubpartArray[$pskey],$markerArray);
					$subpartArray['###MESSAGE_' . $marker . '_NE_' . $psKey . '###'] = '';
				}
			}
		}
		$tagArray = &$markerObj->getAllMarkers($framework);

		foreach($typeArray as $k => $pskey)	{
			$marker = strtoupper($pskey);
			$markerPrefix = 'MESSAGE_' . $marker;

			if (isset($this->conf[$pskey . '.']) && is_array($this->conf[$pskey . '.']))	{
				foreach($this->conf[$pskey . '.'] as $k2 => $v2)	{

					$k2int = substr($k2,0,-1);
					if (
						class_exists('t3lib_utility_Math') ?
						!t3lib_utility_Math::canBeInterpretedAsInteger($k2int) :
						!t3lib_div::testInt($k2int)
					) {
						continue;
					}

					if ($pskey == 'handling')	{
						if (is_array($v2))	{
							foreach ($v2 as $k3 => $v3)	{
								$k3int = substr($k3,0,-1);
								if (
									class_exists('t3lib_utility_Math') ?
									!t3lib_utility_Math::canBeInterpretedAsInteger($k3int) :
									!t3lib_div::testInt($k3int)
								) {
									continue;
								}
								$bActive = ($k3int == $basketObj->basketExtra[$pskey . '.'][$k3int]['0']);
								$this->helperSubpartArray($markerPrefix . '_' . $k2int, $bActive, $k3int, $v3, $framework, $markerArray, $subpartArray, $wrappedSubpartArray);
							}
						}
					} else {
						$bActive = ($k2int == $basketObj->basketExtra[$pskey][0]);
						$this->helperSubpartArray(
							$markerPrefix,
							$bActive,
							$k2int,
							$v2,
							$framework,
							$markerArray,
							$subpartArray,
							$wrappedSubpartArray
						);
					}
				}
			}
			$bCheckNE = in_array($pskey, $psArray);

			foreach($tagArray as $k3 => $v3)	{

				if (strpos($k3, $markerPrefix) === 0 && !isset($subpartArray['###' . $k3 . '###']))	{

					if ($bCheckNE && strpos($k3,'_NE_') !== FALSE)	{
						$wrappedSubpartArray['###' . $k3 . '###'] = '';
						$tmpSubpartArray[$pskey] = $this->cObj->getSubpart($framework,'###' . $k3 . '###');
						$psMessageArray[$pskey] .=
							$this->cObj->substituteMarkerArrayCached(
								$tmpSubpartArray[$pskey],
								$markerArray
							);
					} else if (!isset($wrappedSubpartArray['###' . $k3 . '###'])) {

						$subpartArray['###' . $k3 . '###'] = '';
					}
				}
			}
			$subpartArray['###' . $markerPrefix . '###'] = $psMessageArray[$pskey];
		}
	}


	public function getTypeMarkerArray (&$markerArray, $pskey, $subkey, $linkUrl, $calculatedArray)	{
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');

		if ($subkey != '')	{
			$theCalculateArray = $calculatedArray[$pskey][$subkey];
		} else {
			$theCalculateArray = $calculatedArray[$pskey];
		}
		if (!is_array($theCalculateArray))	{
			$theCalculateArray = array();
		}

		$markerkey = strtoupper($pskey) . ($subkey != '' ? '_' . $subkey : '');
		$markerArray['###PRICE_' . $markerkey . '_TAX###'] = $priceViewObj->priceFormat($theCalculateArray['priceTax']);
		$markerArray['###PRICE_' . $markerkey . '_NO_TAX###'] = $priceViewObj->priceFormat($theCalculateArray['priceNoTax']);
		$markerArray['###PRICE_' . $markerkey . '_ONLY_TAX###'] = $priceViewObj->priceFormat($theCalculateArray['priceTax'] - $theCalculateArray['priceNoTax']);
		$markerArray['###' . $markerkey . '_SELECTOR###'] = $this->generateRadioSelect($pskey, $subkey, $calculatedArray, $linkUrl);

		if ($subkey != '')	{
			$markerArray['###' . $markerkey . '_IMAGE###'] = $this->cObj->IMAGE($basketObj->basketExtra[$pskey . '.'][$subkey . '.']['image.']);
			$markerArray['###' . $markerkey . '_TITLE###'] = $basketObj->basketExtra[$pskey . '.'][$subkey . '.']['title'];
		} else {
			$markerArray['###' . $markerkey . '_IMAGE###'] = $this->cObj->IMAGE($basketObj->basketExtra[$pskey . '.']['image.']);
			$markerArray['###' . $markerkey . '_TITLE###'] = $basketObj->basketExtra[$pskey . '.']['title'];
		}
	}


	public function getMarkerArray (&$markerArray, $pid, $bUseBackPid, $calculatedArray)	{

		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view');
		$basketUrl = htmlspecialchars(
			tx_div2007_alpha::getTypoLink_URL_fh002(
				$this->cObj,
				$pid,
				$urlObj->getLinkParams(
					'',
					array(),
					TRUE,
					$bUseBackPid
				),
				'',
				''
			)
		);

		// payment
		$this->getTypeMarkerArray($markerArray, 'payment', '', $basketUrl, $calculatedArray);

		// shipping
		$this->getTypeMarkerArray($markerArray, 'shipping', '', $basketUrl, $calculatedArray);

		$markerArray['###SHIPPING_WEIGHT###'] = doubleval($calculatedArray['weight']);
		$markerArray['###DELIVERYCOSTS###'] = $priceViewObj->priceFormat($this->getDeliveryCosts($calculatedArray));
 		if (isset($basketObj->basketExtra['handling.']))	{

 			foreach ($basketObj->basketExtra['handling.'] as $k => $confArray)	{
				if (strpos($k,'.') == strlen($k) - 1)	{

					$k1 = substr($k,0,strlen($k) - 1);
					if (
						class_exists('t3lib_utility_Math') ?
						t3lib_utility_Math::canBeInterpretedAsInteger($k1) :
						t3lib_div::testInt($k1)
					) {
						$this->getTypeMarkerArray($markerArray, 'handling', $k1, $basketUrl, $calculatedArray);
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
	public function generateRadioSelect ($pskey, $subkey, $calculatedArray, $basketUrl)	{
			/*
			 The conf-array for the payment/shipping/handling configuration has numeric keys for the elements
			 But there are also these properties:
				.radio	  [boolean]	Enables radiobuttons instead of the default, selector-boxes
				.wrap		[string]	<select>|</select> - wrap for the selectorboxes.  Only if .radio is FALSE. See default value below
				.template	[string]	Template string for the display of radiobuttons.  Only if .radio is TRUE. See default below
			 */
		global $TSFE;

		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$active = $basketObj->basketExtra[$pskey];
		$activeArray = is_array($active) ? $active : array($active);
		if ($subkey != '')	{
			$confArray = $this->conf[$pskey . '.'][$subkey . '.'];
			// $confArray = array('TAXpercentage' => 19, '10.' => array('title' => 'Druckkosten', 'price' => 17));
 			$confArray = $this->cleanConfArr($confArray);
			$htmlInputAddition = '[' . $subkey . ']';
			if (is_array($this->conf[$pskey . '.'][$subkey . '.']))	{
				$type = $this->conf[$pskey . '.'][$subkey . '.']['radio'];
			}
		} else {
			$confArray = $this->cleanConfArr($this->conf[$pskey . '.']);
			$htmlInputAddition = '';
			if (is_array($this->conf[$pskey . '.']))	{
				$type = $this->conf[$pskey . '.']['radio'];
			}
		}
		if (
			class_exists('t3lib_utility_Math') ?
			!t3lib_utility_Math::canBeInterpretedAsInteger($type) :
			!t3lib_div::testInt($type)
		) {
			$type = 0;
		}

		$out='';
		$submitCode = 'this.form.action=\''.$basketUrl.'\';this.form.submit();';
		// $template = $confArray['template'] ? ereg_replace('\' *\. *\$pskey *\. *\'',$pskey, $confArray['template']) : '###IMAGE### <input type="radio" name="recs[tt_products][' . $pskey . ']' . $htmlInputAddition . '" onClick="'.$submitCode.'" value="###VALUE###"###CHECKED###> ###TITLE###<br>';

		$template = (
			$this->conf[$pskey.'.']['template'] ?
				preg_replace('/[[:space:]]*\\.[[:space:]]*' . $pskey . '[[:space:]]*\\.[[:space:]]*/',$pskey, $this->conf[$pskey.'.']['template']) :
				'###IMAGE### <input type="radio" name="recs[tt_products][' . $pskey . ']' . $htmlInputAddition . '" onClick="'.$submitCode.'" value="###VALUE###"###CHECKED###> ###TITLE###<br>'
			);
		$wrap = $confArray['wrap'] ? $confArray['wrap'] :'<select id="' . $pskey . ($subkey != '' ? '-' . $subkey : '') . '-select" name="recs[tt_products][' . $pskey . ']' . $htmlInputAddition . '" onChange="' . $submitCode . '">|</select>';
		$bWrapSelect = (count($confArray) > 1);
		$t = array();
		if ($subkey != '')	{
			$localBasketExtra = &$basketObj->basketExtra[$pskey . '.'][$subkey . '.'];
// 			$actTitle = $basketObj->basketExtra[$pskey . '.'][$subkey . '.']['title'];
		} else {
			$localBasketExtra = &$basketObj->basketExtra[$pskey . '.'];
// 			$actTitle = $basketObj->basketExtra[$pskey . '.']['title'];
		}
		$actTitle = $localBasketExtra['title'];

		if (is_array($confArray))	{
			foreach($confArray as $key => $item)	{
				if (
					($item['show'] || !isset($item['show'])) &&
					(!isset($item['showLimit']) || doubleval($item['showLimit']) >= doubleval($calculatedArray['count']) ||
					intval($item['showLimit']) == 0)
				) {
					$addItems = array();
					$itemTable = '';
					$t['title'] = $item['title'];
					if ($item['where.'] && strstr($t['title'], '###'))	{
						$tableName = key($item['where.']);
						if (($tableName == 'static_countries') && t3lib_extMgm::isLoaded('static_info_tables')) {
							$viewTagArray = array();
							$itemTableView = $tablesObj->get($tableName,TRUE);
							$itemTable = $itemTableView->getModelObj();
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
								if (isset($item['image.'])) {
									$image = $item['image.'];
								}
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
								}
								$markerArray = array();
								$imageCode = $this->cObj->IMAGE($image);
								if ($theCode == 'EMAIL') {
									tx_div2007_alpha5::fixImageCodeAbsRefPrefix($imageCode);
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
									$itemTableView->getRowMarkerArray($row, $markerArray, $fieldsArray);
									$title = $this->cObj->substituteMarkerArrayCached($t['title'], $markerArray);
									$title = htmlentities($title,ENT_QUOTES,$TSFE->renderCharset);
									$value = $key . '-' . $row['uid'];
									if ($value == implode('-',$activeArray))	{
										$actTitle = $item['title'];
									}
								} else {
									$value = $key;
									$title = $item['title'];
								}

								if ($bWrapSelect)	{
									$out .= '<option value="' . $value . '"' . ($value == implode('-',$activeArray) ? ' selected':'').'>' . $title . '</option>' . chr(10);
								} else {
									$out .= $title;
								}
							}
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
				$markerArray['###' . $tag . '###'] = '?';
			}
			$actTitle = $this->cObj->substituteMarkerArrayCached($actTitle, $markerArray);
		}
		if ($subkey != '')	{

			$basketObj->basketExtra[$pskey . '.'][$subkey . '.']['title'] = $actTitle;
		} else {

			$basketObj->basketExtra[$pskey.'.']['title'] = $actTitle;
		}

		if (!$type && $bWrapSelect) {
			$out = $this->cObj->wrap($out,$wrap);
		}
		return $out;
	} // generateRadioSelect


	public function cleanConfArr ($confArray,$checkShow=0)	{
		$outArr=array();
		if (is_array($confArray)) {
			foreach($confArray as $key => &$val)	{
				if (
					intval($key) &&
					is_array($val) &&
					(
						class_exists('t3lib_utility_Math') ?
						!t3lib_utility_Math::canBeInterpretedAsInteger($key) :
						!t3lib_div::testInt($key)
					) &&
					(!$checkShow || !isset($val['show']) || $val['show'])
				) {
					$i = intval($key);
 					$outArr[$i]=$val;
				}
			}
		}
		ksort($outArr);
		reset($outArr);
		return $outArr;
	} // cleanConfArr


	public function getConfiguredPrice (
		$pskey,
		$subkey,
		$row,
		$calculatedArray,
		&$confArray,
		&$countTotal,
		&$priceTotalTax,
		&$priceTax,
		&$priceNoTax,
		&$funcParams=''
	) {
		if (is_array($confArray))	{
			$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');

			$minPrice=0;
			$priceNew=0;
			if ($confArray['WherePIDMinPrice.']) {
					// compare PIDList with values set in priceTaxWherePIDMinPrice in the SETUP
					// if they match, get the min. price
					// if more than one entry for priceTaxWherePIDMinPrice exists, the highest is value will be taken into account
				foreach ($confArray['WherePIDMinPrice.'] as $minPricePID=>$minPriceValue) {
					foreach ($basketObj->itemArray as $sort=>$actItemArray) {
						foreach ($actItemArray as $k1=>$actItem) {
							$tmpRow = &$actItem['rec'];
							$pid = intval($tmpRow['pid']);
							if ($pid == $minPricePID) {
								$minPrice = $minPriceValue;
							}
						}
					}
				}
			}
			krsort($confArray);
			if ($confArray['type'] == 'count') {
				foreach ($confArray as $k1 => $price1)	{
					if (
						(
							class_exists('t3lib_utility_Math') ?
							t3lib_utility_Math::canBeInterpretedAsInteger($k1) :
							t3lib_div::testInt($k1)
						) && $countTotal >= $k1
					) {
						$priceNew = $price1;
						break;
					}
				}
			} else if ($confArray['type'] == 'weight') {

				foreach ($confArray as $k1 => $price1)	{
					if (
						(
							class_exists('t3lib_utility_Math') ?
							t3lib_utility_Math::canBeInterpretedAsInteger($k1) :
							t3lib_div::testInt($k1)
						) && $calculatedArray['weight'] * 1000 >= $k1
					) {
						$priceNew = $price1;
						break;
					}
				}
			} else if ($confArray['type'] == 'price') {
				foreach ($confArray as $k1 => $price1)	{
					if (
						(
							class_exists('t3lib_utility_Math') ?
							t3lib_utility_Math::canBeInterpretedAsInteger($k1) :
							t3lib_div::testInt($k1)
						) && $priceTotalTax >= $k1
					) {
						$priceNew = $price1;
						break;
					}
				}
			} else if ($confArray['type'] == 'objectMethod' && isset($confArray['class'])) {
				$obj= &t3lib_div::getUserObj($confArray['class']);
				if (method_exists($obj,'getConfiguredPrice')){
					$funcParams = $confArray['method.'];
					$priceNew = $obj->getConfiguredPrice($pskey, $subkey, $row, $calculatedArray, $confArray, $countTotal, $priceTotalTax, $priceTax, $priceNoTax, $funcParams);
				} else {
					$priceNew='0';
				}
			}

			if(is_array($funcParams)){
				$hookObj= &t3lib_div::getUserObj($funcParams['class']);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this);
				}
				if (method_exists($hookObj, 'getConfiguredPrice')) {
					$tmpArray = $hookObj->getConfiguredPrice(
						$pskey,
						$subkey,
						$row,
						$calculatedArray,
						$confArray,
						$countTotal,
						$priceTotalTax,
						$priceTax,
						$priceNoTax,
						$funcParams
					);
				};
			}

			// compare the price to the min. price
			if ($minPrice > $priceNew) {
				$priceNew = $minPrice;
			}
			// the total products price as from the payment/shipping is free
			$noCostsAmount = (double) $confArray['noCostsAmount'];

			if ($noCostsAmount && ($priceTotalTax >= $noCostsAmount)) {
				$priceNew = 0;
				$priceTax = $priceNoTax = 0;
			}
			$taxIncluded = $this->priceObj->getTaxIncluded();
			$priceTax += $this->priceObj->getPrice($priceNew,1,$row,$taxIncluded,TRUE);
			$priceNoTax += $this->priceObj->getPrice($priceNew,0,$row,$taxIncluded,TRUE);
		}
	}


	public function getDiscountPrices (
		$pskey,
		$confArray,
		$row,
		$itemArray,
		$taxIncluded,
		$priceTotalTax,
		&$discountArray,
		&$priceTax,
		&$priceNoTax
	)	{

		if ($pskey == 'shipping')	{
			$calcSetup = 'shippingcalc';
		} else if ($pskey == 'handling')	{
			$calcSetup = 'handlingcalc';
		}

		if ($calcSetup != '' && is_array($confArray['price.']) && isset($confArray['price.']['calc.']) && isset($confArray['price.']['calc.']['use']) && isset($this->conf[$calcSetup . '.']) && is_array($this->conf[$calcSetup . '.']))	{
			$useArray = t3lib_div::trimExplode(',', $confArray['price.']['calc.']['use']);
			$specialCalc = array();

			foreach ($this->conf[$calcSetup . '.'] as $k => $v)	{
				$kInt = trim($k, '.'); // substr($k, 0, strlen($k) - 1);
				if (in_array($kInt, $useArray))	{
					$specialCalc[$k] = $v;
				}
			}
			include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_discountprice.php');
			$discountPriceObj = &t3lib_div::makeInstance('tx_ttproducts_discountprice');
			$priceReduction = array();
			$extMergeArray = array('tt_products_articles');

			$discountPriceObj->getCalculatedData(
				$itemArray,
				$specialCalc,
				$pskey,
				$priceReduction,
				$discountArray,
				$priceTotalTax,
				FALSE,
				TRUE
			);

			if (count($discountArray))	{
				$localPriceTotal = 0;
				foreach ($discountArray as $uid => $price)	{
					$localPriceTotal += $price;
				}

				$priceTax = $priceTax + $this->priceObj->getPrice($localPriceTotal,TRUE,$row,$taxIncluded,TRUE);
				$priceNoTax = $priceNoTax + $this->priceObj->getPrice($localPriceTotal,FALSE,$row,$taxIncluded,TRUE);
			}
		}
	}


	public function addItemShippingPrices (&$priceShippingTax, &$priceShippingNoTax, $row, $taxIncluded)	{
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');

		foreach ($basketObj->itemArray as $sort=>$actItemArray) {

			// $actItemArray = all items array
			foreach ($actItemArray as $k2=>$actItem) {
				$row = &$actItem['rec'];

				if ($row['bulkily'])	{
					$value = floatval($basketObj->basketExtra['shipping.']['bulkilyAddition']) * $actItem['count'];
					$row['tax'] = floatval($basketObj->basketExtra['shipping.']['bulkilyFeeTax']);
					$priceShippingTax += $this->priceObj->getPrice($value,TRUE,$row,$taxIncluded,TRUE);
					$priceShippingNoTax += $this->priceObj->getPrice($value,FALSE,$row,$taxIncluded,TRUE);
				}
			}
		}
	}


	public function getPrices ($pskey, $subkey, $row, $countTotal, $priceTotalTax, $calculatedArray, &$priceTax, &$priceNoTax)	{

		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		if (isset($basketObj->basketExtra[$pskey.'.']))	{
			if ($subkey != '' && isset($basketObj->basketExtra[$pskey . '.'][$subkey . '.']))	{
				$basketConf = $basketObj->basketExtra[$pskey.'.'][$subkey . '.'];
			} else {
				$basketConf = $basketObj->basketExtra[$pskey.'.'];
			}
		} else {
			$basketConf = array();
		}
		$taxIncluded = $this->conf['TAXincluded'];
		if (isset($basketConf['TAXincluded']))	{
			$taxIncluded = $basketConf['TAXincluded'];
		}
		$confArray = $basketConf['price.'];
		$confArray = ($confArray ? $confArray : $basketConf['priceTax.']);
		$this->priceObj->init($this->cObj, $this->conf[$pskey.'.'], 0);
		if ($confArray) {
			$this->getConfiguredPrice($pskey, $subkey, $row, $calculatedArray, $confArray, $countTotal, $priceTotalTax, $priceTax, $priceNoTax, $tmp='');
		} else {
			$priceAdd = doubleVal($basketConf['price']);

			if ($priceAdd)	{
				$priceTaxAdd = $this->priceObj->getPrice($priceAdd,TRUE,$row,$taxIncluded,TRUE);
			} else {
				$priceTaxAdd = doubleVal($basketConf['priceTax']);
			}
			$priceTax += $priceTaxAdd;
			$priceNoTaxAdd = doubleVal($basketConf['priceNoTax']);

			if (!$priceNoTaxAdd) {
				$priceNoTaxAdd = $this->priceObj->getPrice($priceTaxAdd,FALSE,$row,TRUE,TRUE);
			}
			$priceNoTax += $priceNoTaxAdd;
		}

		if ($pskey == 'shipping')	{
			$this->addItemShippingPrices($priceTax, $priceNoTax, $row, $taxIncluded);
		}
	}


	public function getBasketConf ($pskey, $subkey='')	{
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');

		if (isset($basketObj->basketExtra[$pskey.'.']))	{
			if ($subkey != '' && isset($basketObj->basketExtra[$pskey . '.'][$subkey . '.']))	{
				$basketConf = $basketObj->basketExtra[$pskey.'.'][$subkey . '.'];
			} else {
				$basketConf = $basketObj->basketExtra[$pskey.'.'];
			}
		} else {
			$basketConf = array();
		}
		return $basketConf;
	}


	public function getSpecialPrices ($pskey, $subkey, $row, $calculatedArray, &$priceShippingTax, &$priceShippingNoTax)	{
		global $TSFE;

		$basketConf = $this->getBasketConf($pskey, $subkey);

		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$perc = doubleVal($basketConf['percentOfGoodstotal']);
		if ($perc)  {
			$priceShipping = doubleVal(($calculatedArray['priceTax']['goodstotal']/100) * $perc);
			$dum = $this->priceObj->getPrice($priceShipping,TRUE,$row);
			$taxIncluded = $this->priceObj->getTaxIncluded();
			$priceShippingTax = $priceShippingTax + $this->priceObj->getPrice($priceShipping,TRUE,$row,$taxIncluded,TRUE);
			$priceShippingNoTax = $priceShippingNoTax + $this->priceObj->getPrice($priceShipping,FALSE,$row,$taxIncluded,TRUE);
		}

		$calculationScript = $basketConf['calculationScript'];
		if ($calculationScript) {
			$calcScript = $TSFE->tmpl->getFileName($calculationScript);
			if ($calcScript)	{
				$confScript = &$basketConf['calculationScript.'];
				include($calcScript);
			}
		}
	}


	public function getPaymentShippingData (
			$countTotal,
			$priceTotalTax,
			$shippingRow,
			$paymentRow,
			$itemArray,
			$calculatedArray,
			&$priceShippingTax,
			&$priceShippingNoTax,
			&$pricePaymentTax,
			&$pricePaymentNoTax
		) {
		global $TSFE;

		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$row = $shippingRow;
		$taxIncluded = $this->priceObj->getTaxIncluded();

		// Shipping
		$weigthFactor = doubleVal($basketObj->basketExtra['shipping.']['priceFactWeight']);
		if($weigthFactor > 0) {
			$priceShipping = $calculatedArray['weight'] * $weigthFactor;
			$priceShippingTax += $this->priceObj->getPrice($priceShipping,TRUE,$row,$taxIncluded,TRUE);
			$priceShippingNoTax += $this->priceObj->getPrice($priceShipping,FALSE,$row,$taxIncluded,TRUE);
		}
		$countFactor = doubleVal($basketObj->basketExtra['shipping.']['priceFactCount']);

		if($countFactor > 0) {
			$priceShipping = $countTotal * $countFactor;
			$priceShippingTax += $this->priceObj->getPrice($priceShipping,TRUE,$row,$taxIncluded,TRUE);
			$priceShippingNoTax += $this->priceObj->getPrice($priceShipping,FALSE,$row,$taxIncluded,TRUE);
		}
		$this->getSpecialPrices('shipping', '', $row, $calculatedArray, $priceShippingTax, $priceShippingNoTax);
		$this->getPrices('shipping', '', $row, $countTotal, $priceTotalTax, $calculatedArray, $priceShippingTax, $priceShippingNoTax);
		$discountArray = array();
		$basketConf = $this->getBasketConf('shipping');

		$this->getDiscountPrices(
			'shipping',
			$basketConf,
			$row,
			$itemArray,
			$taxIncluded,
			$priceTotalTax,
			$discountArray,
			$priceShippingTax,
			$priceShippingNoTax
		);

			// Payment
		$pricePayment = $pricePaymentTax = $pricePaymentNoTax = 0;
		$taxpercentage = '';
		$row = $paymentRow;
		$perc = doubleVal($basketObj->basketExtra['payment.']['percentOfTotalShipping']);

		if ($perc)  {
			$payment = ($calculatedArray['priceTax']['goodstotal'] + $calculatedArray['priceTax']['shipping'] ) * doubleVal($perc);
			$pricePaymentTax += $this->priceObj->getPrice($payment,TRUE,$row,$taxIncluded,TRUE);
			$pricePaymentNoTax += $this->priceObj->getPrice($payment,FALSE,$row,$taxIncluded,TRUE);
		}
		$this->getSpecialPrices('payment', '', $row, $calculatedArray, $pricePaymentTax, $pricePaymentNoTax);
		$this->getPrices('payment', '', $row, $countTotal, $priceTotalTax, $calculatedArray, $pricePaymentTax, $pricePaymentNoTax);
	} // getPaymentShippingData


	public function getHandlingData (
			$countTotal,
			$priceTotalTax,
			&$calculatedArray,
			$itemArray
		)	{
		global $TSFE;

		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$taxIncluded = $this->priceObj->getTaxIncluded();
		$rc = '';

		if (isset($basketObj->basketExtra['handling.']) && is_array($basketObj->basketExtra['handling.']))	{
			$taxObj = &t3lib_div::getUserObj('&tx_ttproducts_field_tax');
			$pskey = 'handling';

			foreach ($basketObj->basketExtra[$pskey . '.'] as $k => $handlingRow)	{

				if (strpos($k,'.') == strlen($k) - 1)	{
					$k1 = substr($k,0,strlen($k) - 1);
					if (
						class_exists('t3lib_utility_Math') ?
						t3lib_utility_Math::canBeInterpretedAsInteger($k1) :
						t3lib_div::testInt($k1)
					) {
						$tax = $this->getTaxPercentage($pskey,$k1);
						$row = array('tax' => $tax);

						$priceTax = '';
						$priceNoTax = '';

						$discountArray = array();
						$basketConf = $this->getBasketConf($pskey, $k1);

						$this->getDiscountPrices(
							$pskey,
							$basketConf,
							$row,
							$itemArray,
							$taxIncluded,
							$priceTotalTax,
							$discountArray,
							$priceTax,
							$priceNoTax
						);
						$this->getSpecialPrices($pskey, $k1, $row, $calculatedArray, $priceTax, $priceNoTax);
						$this->getPrices($pskey, $k1, $row, $countTotal, $priceTotalTax, $calculatedArray, $priceTax, $priceNoTax);
						$calculatedArray[$pskey][$k1]['priceTax'] = $priceTax;
						$calculatedArray[$pskey][$k1]['priceNoTax'] = $priceNoTax;
					}
				}
			}
		}
	} // getHandlingData


	/**
	 * Include handle script
	 */
	public function includeHandleScript ($handleScript, &$confScript, $activity, &$bFinalize, &$pibase, &$infoViewObj)	{
		$content = '';
		$infoViewObj = &t3lib_div::getUserObj('&tx_ttproducts_info_view');

		include($handleScript);
		return $content;
	} // includeHandleScript


	/**
	 * get the TAXpercentage from the shipping if available
	 */
	public function getTaxPercentage ($pskey='shipping',$subkey)	{

		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');

		if ($subkey == '' && is_array($basketObj->basketExtra[$pskey.'.']) && isset($basketObj->basketExtra[$pskey.'.']['TAXpercentage']))	{
			$rc = doubleval($basketObj->basketExtra[$pskey.'.']['TAXpercentage']);
		} else if (
			$subkey != '' &&
			is_array($basketObj->basketExtra[$pskey . '.']) &&
			isset($basketObj->basketExtra[$pskey . '.'][$subkey . '.']) &&
			is_array($basketObj->basketExtra[$pskey . '.'][$subkey . '.']) &&
			isset($basketObj->basketExtra[$pskey . '.'][$subkey . '.']['TAXpercentage'])
		)	{
			$rc = doubleval($basketObj->basketExtra[$pskey . '.'][$subkey . '.']['TAXpercentage']);
		} else {
			if ($subkey == '')	{
				$rc = $this->conf[$pskey . '.']['TAXpercentage'];
			} else {
				$rc = $this->conf[$pskey . '.'][$subkey . '.']['TAXpercentage'];
			}
		}
		$rc = doubleval($rc);
		return $rc;
	}


	/**
	 * get the replaceTAXpercentage from the shipping if available
	 */
	public function getReplaceTaxPercentage ($pskey='shipping')	{
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');

		if (is_array($basketObj->basketExtra[$pskey.'.']) && isset($basketObj->basketExtra[$pskey.'.']['replaceTAXpercentage']))	{
			$rc = doubleval($this->basket->basketExtra[$pskey.'.']['replaceTAXpercentage']);
		}
		return $rc;
	}


	/**
	 * get the delivery costs
	 */
	public function getDeliveryCosts ($calculatedArray)	{
		$rc = $calculatedArray['priceTax']['shipping'] + $calculatedArray['priceTax']['payment'];
		return $rc;
	}


	/**
	 * get the where condition for a shipping entry
	 * E.g.:  30.where.static_countries = cn_short_local = 'Deutschland'
	 */
	public function getWhere ($tablename)	{
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');

		if (is_array($basketObj->basketExtra['shipping.']) && isset($basketObj->basketExtra['shipping.']['where.']))	{
			switch ($tablename) {
				case 'static_countries':
					if (t3lib_extMgm::isLoaded('static_info_tables')) {
						$eInfo = tx_div2007_alpha::getExtensionInfo_fh001('static_info_tables');
						$sitVersion = $eInfo['version'];
					}
					if (version_compare($sitVersion, '2.0.1', '>='))	{
						$rc = $basketObj->basketExtra['shipping.']['where.'][$tablename];
					}
				break;
			}
		}
		return $rc;
	}


	public function getAddRequiredInfoFields ()	{
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$rc = '';
		$tmp = $basketObj->basketExtra['payment.']['addRequiredInfoFields'];
		if ($tmp != '')	{
			$rc = trim($tmp);
		}
		return $rc;
	}


	public function get ($pskey, $setup)	{
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$rc = '';
		$tmp = $basketObj->basketExtra[$pskey.'.'][$setup];
		if ($tmp != '')	{
			$rc = trim($tmp);
		}
		return $rc;
	}


	public function useCreditcard ()	{
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$rc = FALSE;
		$payConf = &$basketObj->basketExtra['payment.'];
		if (is_array($payConf) && $payConf['creditcards'] != '')	{
			$rc = TRUE;
		}
		return $rc;
	}


	public function useAccount ()	{
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$rc = FALSE;
		$payConf = &$basketObj->basketExtra['payment.'];
		if (is_array($payConf) && $payConf['accounts'] != '')	{
			$rc = TRUE;
		}
		return $rc;
	}

/*
	public function getGatewayRequestExt ()	{
		$rc = FALSE;
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$payConf = &$basketObj->basketExtra['payment.'];
		if (is_array($payConf))	{
			$handleLib = $payConf['handleLib'];
		}

		if (
			(strpos($handleLib,'transactor') !== FALSE || strpos($handleLib,'paymentlib') !== FALSE) &&
			is_array($payConf['handleLib.']) &&
			$payConf['handleLib.']['gatewaymode'] == 'request' &&
			t3lib_extMgm::isLoaded($handleLib)
		)	{
			$rc = $handleLib;
		}

		return $rc;
	}*/


	public function getHandleLib ($request)	{ // getGatewayRequestExt

		$rc = FALSE;
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$payConf = &$basketObj->basketExtra['payment.'];

		if (is_array($payConf))	{
			$handleLib = $payConf['handleLib'];
		}

		if (
			(strpos($handleLib,'transactor') !== FALSE || strpos($handleLib,'paymentlib') !== FALSE) &&
			is_array($payConf['handleLib.']) &&
			$payConf['handleLib.']['gatewaymode'] == $request &&
			t3lib_extMgm::isLoaded($handleLib)
		)	{
			$rc = $handleLib;
		}
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']);
}

?>
