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
				if (method_exists($providerObject,'usesBasket') && $providerObject->usesBasket())	{
					$this->initPaymentBasket($providerObject);
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
					$transactionDetailsArr = &$this->getTransactionDetails($confScript);

						// Set payment details and get the form data:
					$ok = $providerObject->transaction_setDetails ($transactionDetailsArr);
					if (!$ok) {
						return 'ERROR: Setting details of transaction failed.';
					}
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

							if (version_compare($this->pibase->version, '2.5.2', '=='))	{
								$content=$this->basketView->getView($localTemplateCode,'PAYMENT', $this->info, false, false, '###PAYMENTLIB_FORM_TEMPLATE###',$markerArray);
							} else if (version_compare($this->pibase->version, '2.5.3', '==')) {
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
	function checkRequired(&$confScript)	{
		$rc = '';

		$providerFactoryObj = tx_paymentlib_providerfactory::getInstance();
		$paymentMethod = $confScript['paymentMethod'];
		$providerObject = $providerFactoryObj->getProviderObjectByPaymentMethod($paymentMethod);
		if (is_object($providerObject))	{
			$providerKey = $providerObject->getProviderKey();
			$transactionDetailsArr = &$this->GetTransactionDetails($confScript);
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

	function &getTransactionDetails(&$confScript)	{
		global $TSFE;

		$param = '&FE_SESSION_KEY='.rawurlencode(
			$TSFE->fe_user->id.'-'.
				md5(
				$TSFE->fe_user->idcheckRequired.'/'.
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
		
// 		$transactionDetailsArr = array (
// 			'transaction' => array (
// 				'amount' => $totalPrice,
// 				'currency' => $confScript['Currency'],
// 				'orderuid' => $orderUid,
// 				'returi' => t3lib_div::getIndpEnv ('TYPO3_REQUEST_URL').$param
// 			),
// 		);

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
			'total' => array(),
			'address' => array(),
			'basket' => array()
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
	
	function initPaymentBasket (&$providerObject) {
		// Get references to the concerning baskets
		//$pOBasket = &$providerObject->payment_basket;
		$shopBasket = &$this->basket;

		// Get references from the shop basket
		$items = &$shopBasket->itemArray;
		$total = &$shopBasket->calculatedArray;
		// Setting up total values
		$totalArr = array();
		$totalArr['amountnotax'] = $this->fFloat($total['priceNoTax']['total']);
		$totalArr['amounttax'] = $this->fFloat($total['priceTax']['total']);
		$totalArr['shippingnotax'] = $this->fFloat($total['priceNoTax']['shipping']);
		$totalArr['shippingtax'] = $this->fFloat($total['priceTax']['shipping']);
		$totalArr['handlingnotax'] = $this->fFloat($total['priceNoTax']['handling']);
		$totalArr['handlingtax'] = $this->fFloat($total['priceTax']['handling']);
		$totalArr['totaltax'] = $totalArr['amounttax']-$totalArr['amountnotax'];
		$totalArr['totalamountnotax']=$totalArr['amountnotax']+$totalArr['shippingnotax']+$totalArr['handlingnotax'];
		$totalArr['totalamount']=$totalArr['amounttax']+$totalArr['shippingtax']+$totalArr['handlingtax'];	

		// Setting up address info values
		$mapAddrFields = array(
			'name' => 'fullname',
			'first_name' => 'firstname',
			'last_name' => 'lastname',
			'address' => 'address',
			'company' => 'business',
			'zip' => 'zip',
			'city' => 'city',
			'telephone' => 'phone',
			'email' => 'email',
			'country' => 'country'
		);

		$tmpAddrArr = array(
			'inv' => &$shopBasket->personInfo,
			'del' => &$shopBasket->deliveryInfo,
		);

		$addrArr = array();

		foreach($tmpAddrArr as $key => $basketAddrArr)
			{	
			$addrArr[$key] = array();

			// Correct fullname-field if it has no value
			if ($basketAddrArr['name'] == '')
				$basketAddrArr['name'] = $basketAddrArr['first_name'].' '.$$addrArr[$key]['last_name'];

			// Correct firstname- and lastname-field if they have no value
			if ($basketAddrArr['first_name'] == '' && $basketAddrArr['last_name'] == '')
				{
				$tmpNameArr = explode(" ", $basketAddrArr['name'], 2);
				$basketAddrArr['first_name'] = $tmpNameArr[0];
				$basketAddrArr['last_name'] = $tmpNameArr[1];
				}

			// Map address fields
			foreach ($basketAddrArr as $mapKey => $value)
				if ($mapAddrFields[$mapKey] != '')
					$addrArr[$key][$mapAddrFields[$mapKey]] = $value;

			// correct country and language settings for invoice address. One of these vars has to be set: country, countryISO2, $countryISO3 or countryISONr
			// you can also set 2 or more of these codes. The codes will be joined with 'OR' in the select-statement and only the first
			// record which is found will be returned. If there is no record at all, the codes will be returned untouched
			$this->correctCountry($addrArr[$key]['country'], $addrArr[$key]['countryISO2'], $addrArr[$key]['countryISO3'], $addrArr[$key]['countryISONr'], $addrArr[$key]['language']);	
		}

		// Fill the basket array 
		$basketArr = array();

		foreach ($items as $sort=>$actItemArray) {
			foreach ($actItemArray as $k1=>$actItem) {
				$tax = $actItem['rec']['tax'];
				$basketArr[$actItem['rec']['category']] = array();		
				$basketArr[$actItem['rec']['category']][$actItem['rec']['itemnumber']] = array(
					'title' => $actItem['rec']['title'],
					'description' => $actItem['rec']['subtitle'],
					'count' => intval($actItem['count']),
					'singlepricenotax' => $this->fFloat($this->price->getPrice($actItem['rec']['price'],0,$tax)),
					'singleprice' =>$this->fFloat($this->price->getPrice($actItem['rec']['price'],1,$tax)),
					'totalpricenotax' => $this->fFloat($this->price->getPrice($actItem['rec']['price'],0,$tax))*$actItem['count'],
					'totalprice' => $this->fFloat($this->price->getPrice($actItem['rec']['price'],0,$tax))*$actItem['count'],	
					'itemshippingnotax' => $this->fFloat($this->price->getPrice($actItem['rec']['shipping'],0,$tax)),
					'itemshipping' => $this->fFloat($this->price->getPrice($actItem['rec']['shipping'],1,$tax)),
					'addshippingnotax' => $this->fFloat($this->price->getPrice($actItem['rec']['shipping2'],0,$tax)),
					'addshipping' => $this->fFloat($this->price->getPrice($actItem['rec']['shipping2'],1,$tax)),
					'totalshippingnotax' => 0.00,
					'totalshipping' => 0.00,
					'itemhandlingnotax' => $this->fFloat($this->price->getPrice($actItem['rec']['handling'],0,$tax)),	
					'itemhandling' => $this->fFloat($this->price->getPrice($actItem['rec']['handling'],1,$tax)),
					'totalhandlingnotax' =>$this->fFloat($this->price->getPrice($actItem['rec']['handling'],0,$tax)) * $actItem['count'],
					'totalhandling' => $this->fFloat($this->price->getPrice($actItem['rec']['handling'],1,$tax))  * $actItem['count'],
					'taxpercent' => $tax,
					'itemtax' => $this->fFloat($actItem['rec']['tax'])-$this->fFloat($actItem['rec']['priceNoTax']),
					'totaltax' => $this->fFloat($actItem['rec']['totalTax'])-$this->fFloat($actItem['rec']['totalNoTax']),
					'itemnumber' => $actItem['rec']['itemnumber'],
				);
			}
		}
		$providerObject->payment_basket->fillBasket($totalArr, $basketArr, $addrArr['inv'], $addArr['del']);
		return true;
}


	//********************************************************************//
	//* This function provides the possibility to retrieve informations  *//
	//* about language settings                                          *//
	//* Function is temporarly postet here. Will be removed if included  *//
	//* in 'sr_static_info' Function code was mailed to stanislas rolland *//
	//* the author of 'sr_static_info'                                   *//
	//*******************************************************************//

	function correctCountry(&$country, &$iso2, &$iso3, &$isonr, &$language)	{
		if ($isonr)
			$whereArr['cn_iso_nr']='='.trim($isonr);

		if ($iso2 != '')
			$whereArr['cn_iso_2']="LIKE '".trim($iso2)."'";

		if ($iso3 !='')
			$whereArr['cn_iso_3']="LIKE '".trim($iso3)."'";

		if ($country != '')
			$whereArr['cn_official_name_local']=$whereArr['cn_official_name_en']=$whereArr['cn_short_local']="LIKE '".trim($country)."'";

		if (is_array($whereArr))
			{

		if (count($whereArr) >0 )
			{
			foreach($whereArr as $key => $value)
				$where .= $key.' '.$value.' OR ';
				
			$where = substr($where,0,-4);
			}

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'static_countries', $where);

			if ($res)
				{
				$staticInfo = @$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$country = $staticInfo['cn_official_name_local'];
				$iso3 = $staticInfo['cn_iso_3'];
				$iso2 = $staticInfo['cn_iso_2'];
				$isonr = $staticInfo['cn_iso_nr'];	

				$langres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'static_languages', "lg_iso_2 LIKE '".$staticInfo['cn_iso_2']."'");
				if ($langres)
					{
						$staticLang = @$GLOBALS['TYPO3_DB']->sql_fetch_assoc($langres);
						$language = $staticLang['lg_name_en'];
					}

				return true;
				}
			}

		return false;
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