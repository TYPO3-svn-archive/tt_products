<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2010 Franz Holzinger <franz@ttproducts.de>
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
 * Part of the tt_products (Shopping System) extension.
 *
 * Payment Library extra functions
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

require_once (PATH_BE_ttproducts . 'model/class.tx_ttproducts_language.php');


class tx_ttproducts_paymentlib {
	public $pibase; // reference to object of pibase
	public $conf;
	public $config;
	public $basket;
	public $basketView;
	public $urlObj;
	private $providerObject;


	public function init (&$pibase, &$basketView, &$urlObj)	{
		$this->pibase = &$pibase;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		$this->basket = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$this->basketView = &$basketView;
		$this->urlObj = &$urlObj;
	}


	/**
	 * returns the gateway mode from the settings
	 */
	public function getGatewayMode ($handleLib, $confScript) 	{

		if ($handleLib == 'paymentlib')	{
			$gatewayModeArray = array('form' => TX_PAYMENTLIB_GATEWAYMODE_FORM, 'webservice' => TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE);
		} else {
			$gatewayModeArray = array('form' => TX_PAYMENTLIB2_GATEWAYMODE_FORM, 'webservice' => TX_PAYMENTLIB2_GATEWAYMODE_WEBSERVICE);
		}

		$gatewayMode = $gatewayModeArray[$confScript['gatewaymode']];
		if (!$gatewayMode)	{
			$gatewayMode = $gatewayModeArray['form'];
		}
		return $gatewayMode;
	}


	public function getReferenceUid ()	{
		$referenceId = FALSE;
		$providerObject = $this->getProviderObject();

		if (is_object($providerObject))	{
			$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

			$orderObj = &$tablesObj->get('sys_products_orders');
			$orderUid = $orderObj->getUid();

			if (!$orderUid)	{
				$orderUid = $orderObj->getBlankUid();
			}
			if (method_exists($providerObject, 'generateReferenceUid'))	{
				$referenceId = $providerObject->generateReferenceUid($orderUid, TT_PRODUCTS_EXTkey);
			} else if (method_exists($providerObject, 'createUniqueID'))	{
				$referenceId = $providerObject->createUniqueID($orderUid, TT_PRODUCTS_EXTkey);
			} else if (method_exists($providerObject, 'getLibObj'))	{
				$libObj = $providerObject->getLibObj();
				if (is_object($libObj))	{
					$referenceId = $libObj->createUniqueID($orderUid, TT_PRODUCTS_EXTkey);
				}
			}
		}
		return $referenceId;
	}


	private function setProviderObject ($providerObject)	{
		$this->providerObject = $providerObject;
	}


	public function getProviderObject ()	{
		return $this->providerObject;
	}


	/**
	 * Include handle extension library
	 */
	public function includeHandleLib ($handleLib, &$confScript, &$bFinalize)	{
		global $TSFE;

		$lConf = $confScript;
		$content = '';
		if (strpos($handleLib,'paymentlib') !== FALSE)	{

			if (t3lib_extMgm::isLoaded($handleLib))	{
				require_once(t3lib_extMgm::extPath($handleLib) . 'lib/class.tx_' . $handleLib . '_providerfactory.php');
			}
			$langObj = &t3lib_div::getUserObj('&tx_ttproducts_language');
			$providerFactoryObj = ($handleLib == 'paymentlib' ? tx_paymentlib_providerfactory::getInstance() : tx_paymentlib2_providerfactory::getInstance());
			$paymentMethod = $confScript['paymentMethod'];
			$providerProxyObject = &$providerFactoryObj->getProviderObjectByPaymentMethod($paymentMethod);
			if (is_object($providerProxyObject))	{
				if (method_exists($providerProxyObject, 'getRealInstance'))	{
					$providerObject = $providerProxyObject->getRealInstance();
				} else {
					$providerObject = $providerProxyObject;
				}
				$this->setProviderObject($providerObject);
				$providerKey = $providerObject->getProviderKey();
				$gatewayMode = $this->getGatewayMode($handleLib, $confScript);
				$ok = $providerObject->transaction_init(
					($handleLib == 'paymentlib' ? TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER : TX_PAYMENTLIB2_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER),
					$paymentMethod,
					$gatewayMode,
					TT_PRODUCTS_EXTkey,
					$confScript['conf.']
				);
				if (!$ok)	{
					return 'ERROR: Could not initialize transaction.';
				}
				$this->getPaymentBasket($totalArr, $addrArr, $paymentBasketArray);
				$referenceId = $this->getReferenceUid();
				if (!$referenceId)	{
					$rc = tx_div2007_alpha::getLL($langObj,'error_reference_id');
					return $rc;
				}

					// Get results of a possible earlier submit and display messages:
				$transactionResultsArr = $providerObject->transaction_getResults($referenceId);
				$referenceId = $this->getReferenceUid(); // in the case of a callback, a former order than the current would have been read in
				if ($providerObject->transaction_succeded($transactionResultsArr)) {
					$bFinalize = TRUE;
				} else if ($providerObject->transaction_failed($transactionResultsArr))	{
					$content = '<span style="color:red;">'.htmlspecialchars($providerObject->transaction_message($transactionResultsArr)).'</span><br />';
					$content .= '<br />';
				} else {
					$transactionDetailsArr = &$this->getTransactionDetails($referenceId, $handleLib, $confScript, $totalArr, $addrArr, $paymentBasketArray);

						// Set payment details and get the form data:
					$ok = $providerObject->transaction_setDetails($transactionDetailsArr);
					if (!$ok) {
						$rc = tx_div2007_alpha::getLL($langObj,'error_transaction_details');
						return $rc;
					}
					$providerObject->transaction_setOkPage($transactionDetailsArr['transaction']['successlink']);
					$providerObject->transaction_setErrorPage($transactionDetailsArr['transaction']['faillink']);

					$compGatewayForm = ($handleLib == 'paymentlib' ? TX_PAYMENTLIB_GATEWAYMODE_FORM : TX_PAYMENTLIB2_GATEWAYMODE_FORM);
					$compGatewayWebservice = ($handleLib == 'paymentlib' ? TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE : TX_PAYMENTLIB2_GATEWAYMODE_WEBSERVICE);

					if ($gatewayMode == $compGatewayForm)	{

						$templateFilename = $lConf['templateFile'] ? $lConf['templateFile'] : (t3lib_extMgm::isLoaded('addons_tt_products') ? 'EXT:'.TT_PRODUCTS_EXTkey.'/template/paymentlib.tmpl' : '');
						if (!$templateFilename)	{
							$templateObj = &t3lib_div::getUserObj('&tx_ttproducts_template');
							$templateFilename = $templateObj->getTemplateFile();
						}
						$localTemplateCode = $this->pibase->cObj->fileResource($templateFilename);
						$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
						$localTemplateCode = $this->pibase->cObj->substituteMarkerArrayCached($localTemplateCode, $markerObj->getGlobalMarkerArray());

							// Render hidden fields:
						$hiddenFields = '';
						$hiddenFieldsArr = $providerObject->transaction_formGetHiddenFields();
						foreach ($hiddenFieldsArr as $key => $value) {
							$hiddenFields .= '<input name="' . $key . '" type="hidden" value="' . htmlspecialchars($value) . '" />' . chr(10);
						}

						$formuri = $providerObject->transaction_formGetActionURI();
						if (strstr ($formuri, 'ERROR') != FALSE)	{
							$bError = TRUE;
						}

						if ($formuri && !$bError) {
							$markerArray=array();
							$markerArray['###HIDDENFIELDS###'] = $markerArray['###HIDDEN_FIELDS###'] = $hiddenFields;
							$markerArray['###REDIRECT_URL###'] = $formuri;
							$markerArray['###PAYMENTLIB_TITLE###'] = $lConf['extTitle'];
							$markerArray['###PAYMENTLIB_INFO###'] = $lConf['extInfo'];
							$markerArray['###PAYMENTLIB_IMAGE###'] = ($lConf['extImage'] == 'IMAGE' && isset($lConf['extImage.']) && is_array($lConf['extImage.']) ? $this->pibase->cObj->IMAGE($lConf['extImage.']) : $lConf['extImage']);
							$markerArray['###PAYMENTLIB_WWW###'] = $lConf['extWww'];

							$content=$this->basketView->getView($localTemplateCode,'PAYMENT', $this->info, FALSE, FALSE, TRUE, 'PAYMENTLIB_FORM_TEMPLATE', $markerArray, $templateFilename);
						} else {
							if ($bError)	{
								$content = $formuri;
							} else {
								$content = tx_div2007_alpha::getLL($langObj,'error_relay_url');
							}
						}
					} else if ($gatewayMode == $compGatewayWebservice)	{
						$rc = $providerObject->transaction_process();
						$resultsArray = $providerObject->transaction_getResults($referenceId);//array holen mit allen daten

						if ($providerObject->transaction_succeded($resultsArray) == FALSE) 	{
							$content = $providerObject->transaction_message($resultsArray); // message auslesen
						} else {
							$bFinalize = TRUE;
						}
						$contentArray = array();
					}
				}
			} else {
				$rc = tx_div2007_alpha::getLL($langObj,'error_relay_url');
				return 'ERROR: Could not find provider object for payment method \''.$paymentMethod.'\' .';
			}
		}
		return $content;
	} // includeHandleLib


	/**
	 * Checks if required fields for credit cards and bank accounts are filled in correctly
	 */
	public function checkRequired ($referenceId, $handleLib, &$confScript)	{
		$rc = '';

		if (strpos($handleLib,'paymentlib') !== FALSE)	{
			$providerFactoryObj = ($handleLib == 'paymentlib' ? tx_paymentlib_providerfactory::getInstance() : tx_paymentlib2_providerfactory::getInstance());
			$paymentMethod = $confScript['paymentMethod'];
			$providerObject = $providerFactoryObj->getProviderObjectByPaymentMethod($paymentMethod);
			if (is_object($providerObject))	{
				$providerKey = $providerObject->getProviderKey();
				$paymentBasketArray = array();
				$addrArr = array();
				$totalArr = array();
				$transactionDetailsArr = &$this->GetTransactionDetails($referenceId, $confScript, $totalArr, $addrArr, $paymentBasketArray);
				echo "<br><br>ausgabe details: ";
				print_r ($transactionDetailsArr);
				echo "<br><br>";
				$set = $providerObject->transaction_setDetails($transactionDetailsArr);
				$ok = $providerObject->transaction_validate();

				if (!$ok) return 'ERROR: invalide data.';
				if ($providerObject->transaction_succeded() == FALSE) 	{
					$rc = $providerObject->transaction_message();
				}
			}
		}
		return $rc;
	} // checkRequired


	public function getUrl ($conf,$pid)	{
		global $TSFE;

		if (!$pid)	{
			$pid = $TSFE->id;
		}
		$addQueryString = array();
		$excludeList = '';
		$target = '';
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->urlObj->getLinkParams($excludeList,$addQueryString,TRUE),$target,$conf);
		return $url;
	}


	/**
	 * Gets all the data needed for the transaction or the verification check
	 */
	public function &getTransactionDetails ($referenceId, $handleLib, &$confScript, &$totalArr, &$addrArr, &$paymentBasketArray)	{
		global $TSFE;

		$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

		$param = '';
		// perform the access to the Gateway
		$paramNameActivity = '&products_' . $this->conf['paymentActivity'];
		$paramFailLink = $paramNameActivity . '=0' . $param;
		$paramSuccessLink = $paramNameActivity . '=1' . $param;
		$paramReturi = $param;

			// Prepare some values for the form fields:
		$totalPrice = $this->basket->calculatedArray['priceNoTax']['total'];
		$totalPriceFormatted = $priceViewObj->priceFormat($totalPrice);
		$orderObj = &$tablesObj->get('sys_products_orders');
		$orderUid = $orderObj->getUid();
		if (!$orderUid)	{
			$orderUid = $orderObj->getBlankUid(); // Gets an order number, creates a new order if no order is associated with the current session
		}

		if ($this->conf['paymentActivity'] == 'finalize' && $confScript['returnPID'])	{
			$successPid = $confScript['returnPID'];
		} else {
			$successPid = ($this->conf['paymentActivity'] == 'payment' || $this->conf['paymentActivity'] == 'verify' ? ($this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : $this->conf['PIDfinalize']) : $TSFE->id);
		}
		$conf = array('returnLast' => 'url');
		$urlDir = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR');
		$retlink = $urlDir . $this->getUrl($conf, $TSFE->id);
		$returi = $retlink . $paramReturi;
		$faillink = $urlDir . $this->getUrl($conf, $this->conf['PIDpayment']) . $paramFailLink;
		$successlink = $urlDir . $this->getUrl($conf, $successPid) . $paramSuccessLink;
		$transactionDetailsArr = array (
			'transaction' => array (
				'amount' => $totalPrice,
				'currency' => $confScript['currency'] ? $confScript['currency'] : $confScript['Currency'],
				'orderuid' => $orderUid,
				'returi' => $returi,
				'faillink' => $faillink,
				'successlink' => $successlink
			),
			'total' => $totalArr,
			'tracking' => $this->basket->order['orderTrackingNo'],
			'address' => $addrArr,
			'basket' => $paymentBasketArray,
		);
		if ($this->conf['paymentActivity'] == 'verify')	{
			$transactionDetailsArr['transaction']['verifylink'] = $retlink . $paramNameActivity . '=1';
		}

		if (isset($confScript['conf.']) && is_array($confScript['conf.']))	{
			$transactionDetailsArr['options'] = $confScript['conf.'];
		}
		$transactionDetailsArr['options']['reference'] = $referenceId;

		$gatewayMode = $this->getGatewayMode($handleLib, $confScript);
		$cardObj = &$tablesObj->get('sys_products_cards');
		if (is_object($this->card) && $gatewayMode == TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE)	{
			$cardUid = $cardObj->getUid();
			$cardRow = $cardObj->get($cardUid);
			$transactionDetailsArr['cc'] = $cardRow;
		}
		return $transactionDetailsArr;
	}
//*******************************************************************************//
//* Added by Udo Gerhards: If the $providerObject has a basket fill it, begin   *//
//*******************************************************************************//


	//****************************************************//
	//* Filling the basket of a paymentlib basket if the *//
	//* selected payment-method has a own basket for its *//
	//* needs                                            *//
	//*--------------------------------------------------*//
	//* @providerObject		The paymentlib-object which  *//
	//*                     holds the payment-basket     *//
	//****************************************************//
	public function &getPaymentBasket (&$totalArr, &$addrArr, &$basketArr) {
		global $TYPO3_DB;

		$bUseStaticInfo = FALSE;
		$infoViewObj = &t3lib_div::getUserObj('&tx_ttproducts_info_view');
		$langObj = &t3lib_div::getUserObj('&tx_ttproducts_language');

		if (t3lib_extMgm::isLoaded('static_info_tables'))	{
			$eInfo = tx_div2007_alpha::getExtensionInfo_fh001('static_info_tables');
			$sitVersion = $eInfo['version'];
			if (version_compare($sitVersion, '2.0.5', '>='))	{
				$bUseStaticInfo = TRUE;
			}
		}

		if ($bUseStaticInfo)	{
			$path = t3lib_extMgm::extPath('static_info_tables');
			include_once($path.'class.tx_staticinfotables_div.php');
		}

		// Get references to the concerning baskets
		//$pOBasket = &$providerObject->payment_basket;
		$shopBasket = &$this->basket;

		// Get references from the shop basket
		$items = &$shopBasket->itemArray;
		$calculatedArray = &$shopBasket->calculatedArray;

		// Setting up total values
		$totalArr = array();
		$totalArr['goodsnotax'] = $this->fFloat($calculatedArray['priceNoTax']['goodstotal']);
		$totalArr['goodstax'] = $this->fFloat($calculatedArray['priceTax']['goodstotal']);
		$totalArr['paymentnotax'] = $this->fFloat($calculatedArray['priceNoTax']['payment']);
		$totalArr['paymenttax'] = $this->fFloat($calculatedArray['priceTax']['payment']);
		$totalArr['shippingnotax'] = $this->fFloat($calculatedArray['priceNoTax']['shipping']);
		$totalArr['shippingtax'] = $this->fFloat($calculatedArray['priceTax']['shipping']);
		$totalArr['handlingnotax'] = $this->fFloat($calculatedArray['priceNoTax']['handling']);
		$totalArr['handlingtax'] = $this->fFloat($calculatedArray['priceTax']['handling']);
/*		$totalArr['amountnotax'] = $this->fFloat($calculatedArray['priceNoTax']['total']);
		$totalArr['amounttax'] = $this->fFloat($calculatedArray['priceTax']['total']);*/
		$totalArr['amountnotax'] = $this->fFloat($calculatedArray['priceNoTax']['vouchertotal']);
		$totalArr['amounttax'] = $this->fFloat($calculatedArray['priceTax']['vouchertotal']);
		$totalArr['taxrate'] = $calculatedArray['maxtax']['goodstotal'];
		$totalArr['totaltax'] = $this->fFloat($totalArr['amounttax'] - $totalArr['amountnotax']);
// 		$totalArr['totalamountnotax'] = $this->fFloat($totalArr['amountnotax'] + $totalArr['shippingnotax'] + $totalArr['handlingnotax']);
// 		$totalArr['totalamount'] = $this->fFloat($totalArr['amounttax'] + $totalArr['shippingtax'] + $totalArr['handlingtax']);

		// Setting up address info values
		$mapAddrFields = array(
			'first_name' => 'first_name',
			'last_name' => 'last_name',
			'address' => 'address1',
			'zip' => 'zip',
			'city' => 'city',
			'telephone' => 'phone',
			'email' => 'email',
			'country' => 'country'
		);
		$tmpAddrArr = array(
			'person' => &$infoViewObj->infoArray['billing'],
			'delivery' => &$infoViewObj->infoArray['delivery']
		);
		$addrArr = array();

		foreach($tmpAddrArr as $key => $basketAddrArr)	{
			$addrArr[$key] = array();

			// Correct firstname- and lastname-field if they have no value
			if ($basketAddrArr['first_name'] == '' && $basketAddrArr['last_name'] == '')	{
				$tmpNameArr = explode(" ", $basketAddrArr['name'], 2);
				$basketAddrArr['first_name'] = $tmpNameArr[0];
				$basketAddrArr['last_name'] = $tmpNameArr[1];
			}

			// Map address fields
			foreach ($basketAddrArr as $mapKey => $value)	{
				$paymentLibKey = $mapAddrFields[$mapKey];
				if ($paymentLibKey != '')	{
					$addrArr[$key][$paymentLibKey] = $value;
				}
			}

			// guess country and language settings for invoice address. One of these vars has to be set: country, countryISO2, $countryISO3 or countryISONr
			// you can also set 2 or more of these codes. The codes will be joined with 'OR' in the select-statement and only the first
			// record which is found will be returned. If there is no record at all, the codes will be returned untouched

			if ($bUseStaticInfo)	{
				$countryArray = tx_staticinfotables_div::fetchCountries($addrArr[$key]['country'], $addrArr[$key]['countryISO2'], $addrArr[$key]['countryISO3'], $addrArr[$key]['countryISONr']);
				$countryRow = $countryArray[0];

				if (count($countryRow))	{
					$addrArr[$key]['country'] = $countryRow['cn_iso_2'];
				}
			}
		}
		$addrArr['delivery']['note'] = $this->basket->recs['delivery']['note'];

		// Fill the basket array
		$basketArr = array();
		$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');

		$totalCount = 0;
		foreach ($items as $sort => $actItemArray) {
			foreach ($actItemArray as $k1 => $actItem) {
				$totalCount += intval($actItem['count']);
			}
		}

		foreach ($items as $sort => $actItemArray) {
			$basketArr[$sort] = array();
			foreach ($actItemArray as $k1 => $actItem) {
				$row = $actItem['rec'];
				$tax = $row['tax'];
				$count = intval($actItem['count']);
				$basketRow = array(
					'item_name' => $row['title'],
					'on0' => $row['title'],
					'os0' => $row['note'],
					'on1' => $row['www'],
					'os2' => $row['note2'],
					'quantity' => $count,
					'amount' => $this->fFloat($actItem['priceNoTax']),
					'shipping' => $count * $totalArr['shippingtax'] / $totalCount,
					'handling' => $this->fFloat($priceObj->getPrice($row['handling'],0,$row)),
					'taxpercent' => $tax,
					'tax' => $this->fFloat($actItem['priceTax'] - $actItem['priceNoTax']),
					'totaltax' => $this->fFloat($actItem['totalTax'])-$this->fFloat($actItem['totalNoTax']),
					'item_number' => $row['itemnumber'],
				);
				$basketArr[$sort][] = $basketRow;
			}
		}

		if ($calculatedArray['priceTax']['vouchertotal'] != $calculatedArray['priceTax']['total'])	{
			$voucherAmount = $calculatedArray['priceTax']['vouchertotal'] - $calculatedArray['priceTax']['total'];
			$voucherText = tx_div2007_alpha::getLL($langObj,'voucher_payment_article');

			$basketArr['VOUCHER'][] =
				array(
					'item_name' => $voucherText,
					'on0' => $voucherText,
					'quantity' => 1,
					'amount' => $voucherAmount,
					'taxpercent' => 0,
					'item_number' => 'VOUCHER'
				);

			$totalArr['goodsnotax'] = $this->fFloat($calculatedArray['priceNoTax']['goodstotal'] + $voucherAmount);
			$totalArr['goodstax'] = $this->fFloat($calculatedArray['priceTax']['goodstotal'] + $voucherAmount);
		}
	}


	public function fFloat ($value=0)	{
		if (is_float($value))	{
			$float = $value;
		} else {
			$float = floatval($value);
		}

		return round($float,2);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentlib.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentlib.php']);
}

?>