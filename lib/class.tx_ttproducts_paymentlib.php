<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2006 Franz Holzinger <kontakt@fholzinger.com>
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

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_marker.php');


class tx_ttproducts_paymentlib {

	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $basket;
	var $basketView;
	var $price;
	var $order;
	var $marker;


	function init(&$pibase, &$conf, &$config, &$basket, &$basketView, &$price, &$order)	{
		$this->pibase = &$pibase;
		$this->conf = &$conf;
		$this->config = &$config;
		$this->basket = &$basket;
		$this->basketView = &$basketView;
		$this->price = &$price;
		$this->order = &$order;
		
		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($this, $this->conf, $this->config, $this->basket);
	}


	/**
	 * Include handle extension library
	 */
	function includeHandleLib($handleLib, &$confScript, &$bFinalize)	{
		global $TSFE;

		$content = '';
		if ($handleLib=='paymentlib')	{
			require_once(t3lib_extMgm::extPath('paymentlib').'lib/class.tx_paymentlib_providerfactory.php');
			$providerFactoryObj = tx_paymentlib_providerfactory::getInstance();
			$paymentMethod = $confScript['paymentMethod'];
			$providerObject = $providerFactoryObj->getProviderObjectByPaymentMethod($paymentMethod);
			if (is_object($providerObject))	{
				$providerKey = $providerObject->getProviderKey();
				$ok =  $providerObject->transaction_init (TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER, $paymentMethod, TX_PAYMENTLIB_GATEWAYMODE_FORM, 'tt_products');
				if (!$ok) return 'ERROR: Could not initialize transaction.';	

					// Get results of a possible earlier submit and display messages:
				$transactionResultsArr = $providerObject->transaction_getResults();
				if ($providerObject->transaction_succeded($transactionResultsArr)) {
					$bFinalize = true;
				} else if ($providerObject->transaction_failed($transactionResultsArr))	{
					$content = '<span style="color:red;">'.htmlspecialchars($providerObject->transaction_message()).'</span><br />';
					$content .= '<br />';	
				} else {
					// perform the access to the Gateway

					$param = '&FE_SESSION_KEY='.rawurlencode(
						$TSFE->fe_user->id.'-'.
							md5(
							$TSFE->fe_user->id.'/'.
							$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
							)
						);
					$param = '&products_'.$this->conf['paymentActivity'].'=1'.$param;
			
						// Prepare some values for the form fields:
					$totalPrice = $this->basket->calculatedArray['priceTax']['total'];
					$totalPriceFormatted = $this->price->priceFormat($totalPrice);
					$orderUid = $this->order->getBlankUid();		// Gets an order number, creates a new order if no order is associated with the current session
					
					$transactionDetailsArr = array (
						'transaction' => array (
							'amount' => $totalPrice,
							'currency' => $confScript['Currency'],
							'orderuid' => $orderUid,
							'returi' => t3lib_div::getIndpEnv ('TYPO3_REQUEST_URL').$param
						),
					);
			
						// Set payment details and get the form data:
					$ok = $providerObject->transaction_setDetails ($transactionDetailsArr);
					if (!$ok) return 'ERROR: Setting details of transaction failed.';
					
					$localTemplateCode = $this->pibase->cObj->fileResource($lConf['templateFile'] ? $lConf['templateFile'] : 'EXT:tt_products/template/paymentlib.tmpl');
					$localTemplateCode = $this->pibase->cObj->substituteMarkerArrayCached($localTemplateCode, $this->pibase->globalMarkerArray);
	
						// Render hidden fields:
					$hiddenFields = '';
					$hiddenFieldsArr = $providerObject->transaction_formGetHiddenFields();
					foreach ($hiddenFieldsArr as $key => $value) {
						$hiddenFields .= '<input name="'.$key.'" type="hidden" value="'.htmlspecialchars($value).'" />'.chr(10);
					}
			
					$formuri = $providerObject->transaction_formGetActionURI();
					if ($formuri) {
						$markerArray=array();
						$markerArray['###HIDDEN_FIELDS###'] = $hiddenFields;
						$markerArray['###REDIRECT_URL###'] = $formuri;
						$content=$this->basketView->getView($localTemplateCode, '###PAYMENTLIB_FORM_TEMPLATE###',$markerArray);
					} else {
						$content = 'NO .relayURL given!!';
					}
				}				
			}
		}
		return $content;
	} // includeHandleLib


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentlib.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentlib.php']);
}


?>
