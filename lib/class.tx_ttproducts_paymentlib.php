<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
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

global $TYPO3_CONF_VARS;


require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_marker.php');
require_once(t3lib_extMgm::extPath('paymentlib').'lib/class.tx_paymentlib_providerfactory.php');

class tx_ttproducts_paymentlib {

	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $basket;
	var $basketView;
	var $price;
	var $order;
	var $card;
	var $account;
	var $info;
	var $marker;


	function init(&$pibase, &$cnf, &$basket, &$basketView, &$price, &$order, &$info, &$card, &$account)	{
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;

		$this->basket = &$basket;
		$this->basketView = &$basketView;
		$this->price = &$price;
		$this->order = &$order;
		$this->info = &$info;
		$this->card = &$card;
		$this->account = &$account;

		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($this, $this->conf, $this->config, $this->basket);
	}


	/**
	 * returns the gateway mode from the settings
	 */
	function getGatewayMode (&$confScript) 	{
		$gatewayModeArray = array('form' => TX_PAYMENTLIB_GATEWAYMODE_FORM, 'webservice' => TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE);
		
		$gatewayMode = $gatewayModeArray[$confScript['gatewaymode']];
		if (!$gatewayMode)	{
			$gatewayMode = TX_PAYMENTLIB_GATEWAYMODE_FORM;
		}
		return $gatewayMode;
	}


	function getTransactionId ($providerObject)	{

		$orderUid = $this->order->getBlankUid();
		$libObj = $providerObject->getLibObj();
		if (is_object($libObj))	{
			$transactionId = $libObj->createUniqueID($orderUid, TT_PRODUCTS_EXTkey);
		}
		return $transactionId;
	}


	/**
	 * Include handle extension library
	 */
	function includeHandleLib($handleLib, &$confScript, &$bFinalize)	{
		global $TSFE;

		$lConf = $confScript;
		$content = '';

		if ($handleLib == 'paymentlib')	{
			$providerFactoryObj = &tx_paymentlib_providerfactory::getInstance();
			$paymentMethod = $confScript['paymentMethod'];
			$providerProxyObject = $providerFactoryObj->getProviderObjectByPaymentMethod($paymentMethod);
			if (is_object($providerProxyObject))	{
				$providerObject = $providerProxyObject->getRealInstance();
				$providerKey = $providerObject->getProviderKey();
				$gatewayMode = $this->getGatewayMode($confScript);
				$ok =  $providerObject->transaction_init (TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER, $paymentMethod, $gatewayMode, TT_PRODUCTS_EXTkey);
///######### Florian Strauß -  Hier Aufruf ändern  TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER

				if (!$ok) return 'ERROR: Could not initialize transaction.';

//*******************************************************************************//
//* Changed by Udo Gerhards: If the $providerObject has a basket fill it, begin *//
//*******************************************************************************//
				if (method_exists($providerObject,'usesBasket') && $providerObject->usesBasket() && t3lib_extMgm::isLoaded('static_info_tables'))	{
					include_once(PATH_BE_fh_library.'lib/class.tx_fhlibrary_system.php');
					$eInfo = tx_fhlibrary_system::getExtensionInfo('static_info_tables');
					$sitVersion = $eInfo['version'];
					if (version_compare($sitVersion, '2.0.5', '>='))	{
						$this->getPaymentBasket($totalArr, $addrArr, $paymentBasketArray);
					}
				}

//*******************************************************************************//
//* Changed by Udo Gerhards: If the $providerObject has a basket fill it, end   *//
//*******************************************************************************//
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
					$providerObject->transaction_setOkPage ($transactionDetailsArr['successlink']);
					$providerObject->transaction_setErrorPage ($transactionDetailsArr['faillink']);

					if ($gatewayMode == TX_PAYMENTLIB_GATEWAYMODE_FORM)	{

						$localTemplateCode = $this->pibase->cObj->fileResource($lConf['templateFile'] ? $lConf['templateFile'] : 'EXT:tt_products/template/paymentlib.tmpl');
						$localTemplateCode = $this->pibase->cObj->substituteMarkerArrayCached($localTemplateCode, $this->pibase->globalMarkerArray);

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
							$markerArray['###HIDDEN_FIELDS###'] = $hiddenFields;
							$markerArray['###REDIRECT_URL###'] = $formuri;
							$markerArray['###PAYMENTLIB_TITLE###'] = $lConf['extTitle'];
							$markerArray['###PAYMENTLIB_INFO###'] = $lConf['extInfo'];
							$markerArray['###PAYMENTLIB_IMAGE###'] = $lConf['extImage'];

							if (version_compare($this->pibase->version, '2.5.2', '>=') &&
							version_compare($this->pibase->version, '2.6.0', '<'))	{
								$content=$this->basketView->getView($localTemplateCode,'PAYMENT', $this->info, false, false, '###PAYMENTLIB_FORM_TEMPLATE###',$markerArray);
							} else if (version_compare($this->pibase->version, '2.6.0', '==')) {
								$content=$this->basketView->getView($localTemplateCode,'PAYMENT', $this->info, false, false, true, '###PAYMENTLIB_FORM_TEMPLATE###',$markerArray);

							} else {
								$tmp = t3lib_div::debug_trail();
								t3lib_div::debug($tmp);
								die ('This payment library code does not work with tt_products '.$this->pibase->version);
							}
						} else {
							if ($bError)	{
								$content = $formuri;
							} else {
								$content = 'NO .relayURL given!!';
							}
						}
					} else if ($gatewayMode == TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE)	{
						/// ####### Florian Strauß -
						$content = $providerObject->transaction_process (); //betrag buchen
						if ($content) 	{
							echo $content;
							exit;
						}
						$resultsArray = $providerObject->transaction_getResults ($transactionId);//array holen mit allen daten

						if ($providerObject->transaction_succeded() == false) 	{
							$content = $providerObject->transaction_message(); // message auslesen
						} else {
							$bFinalize = true;
						}

						$contentArray=array();

						/// ####### Florian Strauß -
					}
				}
			}
		}
		return $content;
	} // includeHandleLib


	/**
	 * Checks if required fields for credit cards and bank accounts are filled in correctly
	 */
	function checkRequired($transactionId, &$confScript)	{
		$rc = '';

		$providerFactoryObj = tx_paymentlib_providerfactory::getInstance();
		$paymentMethod = $confScript['paymentMethod'];
		$providerObject = $providerFactoryObj->getProviderObjectByPaymentMethod($paymentMethod);
		if (is_object($providerObject))	{
			$providerKey = $providerObject->getProviderKey();
			$transactionDetailsArr = &$this->GetTransactionDetails($transactionId, $confScript);
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
		$url = $this->pibase->pi_getTypoLink_URL($pid,$this->marker->getLinkParams($excludeList,$addQueryString,true),$target,$conf);
		return $url;
	}


	/**
	 * Gets all the data needed for the transaction or the verification check
	 */

	function &getTransactionDetails($transactionId, &$confScript, &$totalArr, &$addrArr, &$paymentBasketArray)	{
		global $TSFE;

		$param = '&FE_SESSION_KEY='.rawurlencode(
			$TSFE->fe_user->id.'-'.
				md5(
				$TSFE->fe_user->id.'/'.
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
				)
			);
		$paramFaillink = '&products_'.$this->conf['paymentActivity'].'=0'.$param;
		if ($this->conf['paymentActivity'] == 'payment')	{
			$param .= '&products_finalize=1';
		}
		$paramReturi = '';

			// Prepare some values for the form fields:
		$totalPrice = $this->basket->calculatedArray['priceTax']['total'];
		$totalPriceFormatted = $this->price->priceFormat($totalPrice);
		$orderUid = $this->order->getBlankUid();	// Gets an order number, creates a new order if no order is associated with the current session

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
		if (is_object($this->card) && $gatewayMode == TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE)	{
			$cardUid = $this->card->getUid();
			$cardRow = $this->card->get($cardUid);
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

		$path = t3lib_extMgm::extPath('static_info_tables');
		include_once($path.'class.tx_staticinfotables_div.php');

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
			'company' => 'business',
			'zip' => 'zip',
			'city' => 'city',
			'telephone' => 'phone',
			'email' => 'email',
			'country' => 'country'
		);

		$tmpAddrArr = array(
			'person' => &$shopBasket->recs['personinfo'],
			'delivery' => &$shopBasket->recs['delivery']
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

			$countryArray = tx_staticinfotables_div::fetchCountries($addrArr[$key]['country'], $addrArr[$key]['countryISO2'], $addrArr[$key]['countryISO3'], $addrArr[$key]['countryISONr']);
			$countryRow = $countryArray[0];

			if (count($countryRow))	{
				$addrArr[$key]['country'] = $countryRow['cn_iso_2'];

				$table = 'static_languages';
				$langres = $TYPO3_DB->exec_SELECTquery('*', $table, 'lg_iso_2='. $TYPO3_DB->fullQuoteStr(trim($countryRow['cn_iso_2']),$table));
				if ($langres)	{
					$staticLang = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($langres);
					$addrArr[$key]['lc'] = $staticLang['lg_iso_2'];
				}
			}
		}

		// Fill the basket array 
		$basketArr = array();

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
/*					'singlepricenotax' => $this->fFloat($actItem['priceNoTax']),
					'singleprice' =>  $this->fFloat($actItem['priceTax']),*/
					'amount' => $this->fFloat($actItem['priceNoTax']),
					'shipping' => $this->fFloat($count * $totalArr['shippingtax'] / $totalCount),
					'handling' => $this->fFloat($this->price->getPrice($row['handling'],0,$tax)),
					'taxpercent' => $tax,
					'tax' => $this->fFloat($actItem['priceTax'] - $actItem['priceNoTax']),
					'totaltax' => $this->fFloat($actItem['rec']['totalTax'])-$this->fFloat($row['totalNoTax']),
					'item_number' => $row['itemnumber'],
				);
				$basketArr[$sort][] = $basketRow;
			}
		}

	}


	function fFloat($value ='0.00')	{
			if (is_float($value))
			 	$float = $value;
			 else
			 	$float = floatval($value);

			 return round($float,2);
		}

//*******************************************************************************//
//* Changed by Udo Gerhards: If the $providerObject has a basket fill it, end   *//
//*******************************************************************************//


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentlib.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentlib.php']);
}


?>