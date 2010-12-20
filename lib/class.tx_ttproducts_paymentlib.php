<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once(t3lib_extMgm::extPath('paymentlib').'lib/class.tx_paymentlib_providerfactory.php');

class tx_ttproducts_paymentlib {

	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $basket;
	var $basketView;
	var $infoView;
	var $urlObj;

	function init(&$pibase, &$basketView, &$infoView, &$urlObj)	{
		$this->pibase = &$pibase;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
 		$this->conf = &$cnf->conf;
 		$this->config = &$cnf->config;

		$this->basket = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$this->basketView = &$basketView;
		$this->infoView = &$infoView;
		$this->urlObj = &$urlObj;
	}


	/**
	 * returns the gateway mode from the settings
	 *
	 * @param	[type]		$$confScript: ...
	 * @return	[type]		...
	 */
	function getGatewayMode (&$confScript) 	{
		$gatewayModeArray = array('form' => TX_PAYMENTLIB_GATEWAYMODE_FORM, 'webservice' => TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE);

		$gatewayMode = $gatewayModeArray[$confScript['gatewaymode']];
		if (!$gatewayMode)	{
			$gatewayMode = TX_PAYMENTLIB_GATEWAYMODE_FORM;
		}
		return $gatewayMode;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$providerObject: ...
	 * @return	[type]		...
	 */
	function getTransactionId ($providerObject)	{
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

		$orderObj = &$tablesObj->get('sys_products_orders');
		$orderUid = $orderObj->getBlankUid();
		$libObj = $providerObject->getLibObj();
		if (is_object($libObj))	{
			$transactionId = $libObj->createUniqueID($orderUid, TT_PRODUCTS_EXTkey);
		}
		return $transactionId;
	}

	/**
	 * Include handle extension library
	 *
	 * @param	[type]		$handleLib: ...
	 * @param	[type]		$confScript: ...
	 * @param	[type]		$bFinalize: ...
	 * @return	[type]		...
	 */
	function includeHandleLib($handleLib, &$confScript, &$bFinalize)	{
		global $TSFE;
		$lConf = $confScript;
		$content = '';

		if ($handleLib == 'paymentlib')	{
			$providerFactoryObj = &tx_paymentlib_providerfactory::getInstance();
			$paymentMethod = $confScript['paymentMethod'];
			$providerProxyObject = &$providerFactoryObj->getProviderObjectByPaymentMethod($paymentMethod);
			if (is_object($providerProxyObject))	{
				$providerObject = $providerProxyObject->getRealInstance();
				$providerKey = $providerObject->getProviderKey();
				$gatewayMode = $this->getGatewayMode($confScript);
				$ok =  $providerObject->transaction_init (TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER, $paymentMethod, $gatewayMode, TT_PRODUCTS_EXTkey);

///######### Florian Strauß -  Hier Aufruf ändern  TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER
				if (!$ok) return 'ERROR: Could not initialize transaction.';

				$this->getPaymentBasket($totalArr, $addrArr, $paymentBasketArray);

				$transactionId = $this->getTransactionId ($providerObject);
				if (!$transactionId)	{
					return 'ERROR: transaction ID could not be generated';
				}

					// Get results of a possible earlier submit and display messages:
				$transactionResultsArr = $providerObject->transaction_getResults($transactionId);
				if ($providerObject->transaction_succeded($transactionResultsArr)) {
					$bFinalize = true;
				} else if ($providerObject->transaction_failed($transactionResultsArr))	{
					$content = '<span style="color:red;">'.htmlspecialchars($providerObject->transaction_message($transactionResultsArr)).'</span><br />';
					$content .= '<br />';
				} else {
					$transactionDetailsArr = &$this->getTransactionDetails($transactionId, $confScript, $totalArr, $addrArr, $paymentBasketArray);

						// Set payment details and get the form data:
					$ok = $providerObject->transaction_setDetails ($transactionDetailsArr);
					if (!$ok) {
						return 'ERROR: Setting details of transaction failed.';
					}
					$providerObject->transaction_setOkPage ($transactionDetailsArr['transaction']['successlink']);
					$providerObject->transaction_setErrorPage ($transactionDetailsArr['transaction']['faillink']);

					if ($gatewayMode == TX_PAYMENTLIB_GATEWAYMODE_FORM)	{
						$localTemplateCode = $this->pibase->cObj->fileResource($lConf['templateFile'] ? $lConf['templateFile'] : 'EXT:'.TT_PRODUCTS_EXTkey.'/template/paymentlib.tmpl');
						$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
						$localTemplateCode = $this->pibase->cObj->substituteMarkerArrayCached($localTemplateCode, $markerObj->getGlobalMarkerArray());

							// Render hidden fields:
						$hiddenFields = '';
						$hiddenFieldsArr = $providerObject->transaction_formGetHiddenFields();
						foreach ($hiddenFieldsArr as $key => $value) {
							$hiddenFields .= '<input name="'.$key.'" type="hidden" value="'.htmlspecialchars($value).'" />'.chr(10);
						}

						$formuri = $providerObject->transaction_formGetActionURI();
						if (strstr ($formuri, 'ERROR') != FALSE)	{
							$bError = TRUE;
						}

						if ($formuri && !$bError) {
							$markerArray=array();
							$markerArray['###HIDDEN_FIELDS###'] = $markerArray['###HIDDENFIELDS###'] = $hiddenFields;
							$markerArray['###REDIRECT_URL###'] = $formuri;
							$markerArray['###PAYMENTLIB_TITLE###'] = $lConf['extTitle'];
							$markerArray['###PAYMENTLIB_INFO###'] = $lConf['extInfo'];
							$markerArray['###PAYMENTLIB_IMAGE###'] = $lConf['extImage'];
							$content=$this->basketView->getView($localTemplateCode,'PAYMENT', $this->info, FALSE, FALSE, TRUE, '###PAYMENTLIB_FORM_TEMPLATE###',$markerArray);
	// Changed by Udo Gerhards to correct misspelled call of function, end

						} else {
							if ($bError)	{
								$content = $formuri;
							} else {
								$content = 'NO .relayURL given!!';
							}
						}
					} else if ($gatewayMode == TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE)	{
						/// ####### Florian Strauß - Beginn
						$content = $providerObject->transaction_process (); // Betrag buchen
						if ($content) 	{
							echo $content;
							exit;
						}
						$resultsArray = $providerObject->transaction_getResults ($transactionId);//array holen mit allen daten

						if ($providerObject->transaction_succeded() == FALSE) 	{
							$content = $providerObject->transaction_message(); // message auslesen
						} else {
							$bFinalize = TRUE;
						}

						$contentArray=array();
						/// ####### Florian Strauß - Ende
					}
				}
			} else {
				return 'ERROR: Could not find provider object for payment method \''.$paymentMethod.'\' .';
			}
		}
		return $content;
	} // includeHandleLib


	/**
	 * Checks if required fields for credit cards and bank accounts are filled in correctly
	 *
	 * @param	[type]		$transactionId: ...
	 * @param	[type]		$confScript: ...
	 * @return	[type]		...
	 */
	function checkRequired($transactionId, &$confScript)	{
		$rc = '';

		$providerFactoryObj = tx_paymentlib_providerfactory::getInstance();
		$paymentMethod = $confScript['paymentMethod'];
		$providerObject = $providerFactoryObj->getProviderObjectByPaymentMethod($paymentMethod);
		if (is_object($providerObject))	{
			$providerKey = $providerObject->getProviderKey();
			$paymentBasketArray = array();
			$addrArr = array();
			$totalArr = array();
			$transactionDetailsArr = &$this->GetTransactionDetails($transactionId, $confScript, $totalArr, $addrArr, $paymentBasketArray);
			echo "<br><br>ausgabe details: ";
			print_r ($transactionDetailsArr);
			echo "<br><br>";
			$set = $providerObject->transaction_setDetails ($transactionDetailsArr);
			$ok = $providerObject->transaction_validate ();

			if (!$ok) return 'ERROR: invalide data.';
			if ($providerObject->transaction_succeded() == false) 	{
				$rc = $providerObject->transaction_message();
			}
		}

		return $rc;
	} // checkRequired


	function getUrl($conf,$pid)	{
		global $TSFE;

		if (!$pid)	{
			$pid = $TSFE->id;
		}
		$addQueryString = array();
		$excludeList = '';
		$target = '';
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->urlObj->getLinkParams($excludeList,$addQueryString,true),$target,$conf);

		return $url;
	}


	/**
	 * Gets all the data needed for the transaction or the verification check
	 *
	 * @param	[type]		$transactionId: ...
	 * @param	[type]		$confScript: ...
	 * @param	[type]		$totalArr: ...
	 * @param	[type]		$addrArr: ...
	 * @param	[type]		$paymentBasketArray: ...
	 * @return	[type]		...
	 */
	function &getTransactionDetails($transactionId, &$confScript, &$totalArr, &$addrArr, &$paymentBasketArray)	{
		global $TSFE;

		$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

		// perform the access to the Gateway
		$param = '&FE_SESSION_KEY='.rawurlencode(
			$TSFE->fe_user->id.'-'.
				md5(
				$TSFE->fe_user->id.'/'.
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
				)
			);
		$paramFaillink = '&products_'.$this->conf['paymentActivity'].'=0'.$param;
		$paramReturi = $param;
		if ($this->conf['paymentActivity'] == 'payment')	{
			$param .= '&products_finalize=1';
		}

			// Prepare some values for the form fields:
		$totalPrice = $this->basket->calculatedArray['priceNoTax']['total'];
		$totalPriceFormatted = $priceViewObj->priceFormat($totalPrice);
		$orderObj = &$tablesObj->get('sys_products_orders');
		$orderUid = $orderObj->getBlankUid();	// Gets an order number, creates a new order if no order is associated with the current session

		$successPid = ($this->conf['paymentActivity'] == 'payment' ? ($this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : $this->conf['PIDfinalize']) : $TSFE->id);
		$conf = array('returnLast' => 'url');
		$urlDir = t3lib_div::getIndpEnv ('TYPO3_REQUEST_DIR');
		$returi = $urlDir.$this->getUrl($conf, $TSFE->id).$paramReturi;
		$faillink = $urlDir.$this->getUrl($conf, $this->conf['PIDpayment']).$paramFaillink;
		$successlink = $urlDir.$this->getUrl($conf, $successPid).$param;
		$transactionDetailsArr = array (
			'transaction' => array (
				'amount' => $totalPrice,
				'currency' => $confScript['Currency'],
				'orderuid' => $orderUid,
				'returi' => $returi,
				'faillink' => $faillink,
				'successlink' => $successlink
			),
			'total' => $totalArr,
			'address' => $addrArr,
			'basket' => $paymentBasketArray,
			'options' => array (
				'reference' => $transactionId,
			)
		);

		$gatewayMode = $this->getGatewayMode ($confScript);
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

	function &getPaymentBasket (&$totalArr, &$addrArr, &$basketArr) {
		global $TYPO3_DB;

		$bUseStaticInfo = FALSE;
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
		$totalArr['amountnotax'] = $this->fFloat($calculatedArray['priceNoTax']['total']);
		$totalArr['amounttax'] = $this->fFloat($calculatedArray['priceTax']['total']);
		$totalArr['shippingnotax'] = $this->fFloat($calculatedArray['priceNoTax']['shipping']);
		$totalArr['shippingtax'] = $this->fFloat($calculatedArray['priceTax']['shipping']);
		$totalArr['handlingnotax'] = $this->fFloat($calculatedArray['priceNoTax']['handling']);
		$totalArr['handlingtax'] = $this->fFloat($calculatedArray['priceTax']['handling']);

		$totalArr['totaltax'] = $this->fFloat($totalArr['amounttax'] - $totalArr['amountnotax']);
		$totalArr['totalamountnotax'] = $this->fFloat($totalArr['amountnotax'] + $totalArr['shippingnotax'] + $totalArr['handlingnotax']);
		$totalArr['totalamount'] = $this->fFloat($totalArr['amounttax'] + $totalArr['shippingtax'] + $totalArr['handlingtax']);

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
			'person' => &$this->infoView->infoArray['billing'],
			'delivery' => &$this->infoView->infoArray['delivery']
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

		// Fill the basket array
		$basketArr = array();
		$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');

		$totalCount = 0;
		foreach ($items as $sort=>$actItemArray) {
			foreach ($actItemArray as $k1=>$actItem) {
				$totalCount += intval($actItem['count']);
			}
		}

		foreach ($items as $sort=>$actItemArray) {
			$basketArr[$sort] = array();
			foreach ($actItemArray as $k1=>$actItem) {
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
// 					'singlepricenotax' => $this->fFloat($actItem['priceNoTax']),
// 					'singleprice' =>  $this->fFloat($actItem['priceTax']),
					'amount' => $this->fFloat($actItem['priceNoTax']),
					'shipping' => $count * $totalArr['shippingtax'] / $totalCount,
					'handling' => $this->fFloat($priceObj->getPrice($row['handling'],0,$tax)),
					'taxpercent' => $tax,
					'tax' => $this->fFloat($actItem['priceTax'] - $actItem['priceNoTax']),
					'totaltax' => $this->fFloat($actItem['rec']['totalTax'])-$this->fFloat($row['totalNoTax']),
					'item_number' => $row['itemnumber'],
				);
				$basketArr[$sort][] = $basketRow;
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function fFloat($value=0)	{
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