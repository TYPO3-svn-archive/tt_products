<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2008 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * payment_DIBS.php
 *
 * This script handles payment via the danish payment gateway, DIBS.
 * Support: DIBS premium with Credit-cards  and Unibank Solo-E
 *
 * This script is used as a "handleScript" with the default lib/class.tx_ttproducts_basket_view.php of the Shop System.
 *
 * DIBS:	http://www.dibs.dk
 *
 * $Id: payment_DIBS.php 4269 2006-12-08 21:22:21Z franzholz $
 *
 * @author  Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 *
 */


if (!is_object($pibase) || !is_object($this->cObj)  || !is_object($this->basket))	die('tt_products: $pibase and $pibase->cObj must be objects!');


// $lConf = $this->basketExtra["payment."]["handleScript."];
// Loads the handleScript TypoScript into $lConf.
$lConf = $confScript;


$localTemplateCode = $this->cObj->fileResource($lConf['templateFile'] ? $lConf['templateFile'] : 'EXT:tt_products/template/payment_DIBS_template.tmpl');		// Fetches the DIBS template file
if (!is_object($basketView))	{
	$error_code = '';
	$basketView = &t3lib_div::getUserObj('tx_ttproducts_basket_view',1);
	$basketView->init ($pibase, array(), FALSE, $this->templateCode, $error_code);
}
$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
$localTemplateCode = $this->cObj->substituteMarkerArrayCached($localTemplateCode, $markerObj->getGlobalMarkerArray());
$calculatedArray = $this->basket->getCalculatedArray();

$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
$order = &$tablesObj->get('sys_products_orders');

$orderUid = $order->getBlankUid();	// Gets an order number, creates a new order if no order is associated with the current session

$param = '&FE_SESSION_KEY='.rawurlencode(
$GLOBALS['TSFE']->fe_user->id.'-'.
	md5(
	$GLOBALS['TSFE']->fe_user->id.'/'.
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
	)
);

$products_cmd = $pibase->piVars['products_cmd'];
$products_cmd = ($products_cmd ? $products_cmd : t3lib_div::_GP('products_cmd'));
switch($products_cmd)	{
	case 'cardno':
		$tSubpart = $lConf['soloe'] ? 'DIBS_SOLOE_TEMPLATE' : 'DIBS_CARDNO_TEMPLATE';		// If solo-e is selected, use different subpart from template
		$tSubpart = $lConf['direct'] ? 'DIBS_DIRECT_TEMPLATE' : $tSubpart;		// If direct is selected, use different subpart from template
		$content=$basketView->getView($localTemplateCode,'PAYMENT', $infoViewObj, FALSE, FALSE, TRUE, $tSubpart);		// This not only gets the output but also calculates the basket total, so it's NECESSARY!
		$markerArray=array();
		$markerArray['###HIDDENFIELDS###'] =
' <input type="hidden" name="merchant"
value="'.$lConf['merchant'].'">
<input type="hidden" name="amount"
value="'.round($calculatedArray['priceTax']['total']
*100).'">
<input type="hidden" name="currency"
value="'.$lConf['currency'].'">		<!--Valuta som angivet i
ISO4217, danske kroner=208-->
<input type="hidden" name="orderid"
value="'.$this->order->getNumber($orderUid).'">
<!--Butikkens ordrenummer der skal knyttes til denne transaktion-->
<input type="hidden" name="uniqueoid" value="1">
<input type="hidden" name="accepturl"
value="https://payment.architrade.com/cgi-ssl/relay.cgi/'.$lConf['relayURL'].'&products_cmd=accept&products_finalize=1'.$param.'">
<input type="hidden" name="declineurl"
value="https://payment.architrade.com/cgi-ssl/relay.cgi/'.$lConf['relayURL'].'&products_cmd=decline&products_finalize=1'.$param.'">';

		if ($lConf['soloe'] || $lConf['direct'])	{
		$markerArray['###HIDDENFIELDS###'].= '
<input type="hidden" name="cancelurl"
value="https://payment.architrade.com/cgi-ssl/relay.cgi/'.$lConf['relayURL'].'&products_cmd=cancel&products_finalize=1'.$param.'">';
		}
		if ($lConf['direct'])	{
			$markerArray['###HIDDENFIELDS###'].= '<input
type="hidden" name="opener" value="">' .
					'<input type="hidden" name="callbackurl"
value="https://payment.architrade.com/cgi-ssl/relay.cgi/'.$lConf['relayURL'].'&products_cmd=accept&products_finalize=1'.$param.'">';
			$markerArray['###WINDOW_OPENER###'] = 'onsubmit="return
doPopup(this);" target="Betaling"'; // if this is empty then no popup window will be opened
		}

		if ($lConf['test']) {
			$markerArray['###HIDDENFIELDS###'].= '
				<input type="hidden" name="test" value="foo">
			';
		}
		if ($lConf['cardType'] && !$lConf['soloe'] && !$lConf['direct'])	{
				/*
				Examples:
					DK		  Dankort
					V-DK		Visa-Dankort
					MC(DK)	  Mastercard/Eurocard udstedt i Danmark
					VISA		Visakort udstedt i udlandet
					MC		  Mastercard/Eurocard udstedt i udlandet
					DIN(DK)	 Diners Club, Danmark
					DIN		 Diners Club, international
				*/

			$markerArray['###HIDDENFIELDS###'].= '
				<input type="hidden" name="cardtype" value="'.$lConf['cardType'].'">
			';
		}
		if ($lConf['account'])  {		// DIBS account feature
			$markerArray['###HIDDENFIELDS###'].= '
				<input type="hidden" name="account" value="'.$lConf['account'].'">
			';
		}


				// Adds order info to hiddenfields.
		if ($lConf['addOrderInfo']) {
			$theFields="";
				// Delivery info
			reset($this->address->infoArray['delivery']);
			$cc=0;
			while(list($field,$value)=each($this->address->infoArray['delivery']))		{
				$value = trim($value);
				if ($value) {
					$cc++;
					$theFields.=chr(10).'<input type="hidden" name="delivery'.$cc.'.'.$field.'" value="'.htmlspecialchars($value).'">';
				}
			}

				// Order items
			reset($this->basket->itemArray);
			$theFields.='
<input type="hidden" name="ordline1-1" value="Varenummer">
<input type="hidden" name="ordline1-2" value="Beskrivelse">
<input type="hidden" name="ordline1-3" value="Antal">
<input type="hidden" name="ordline1-4" value="Pris">
';
			$cc=1;
			$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
			// loop over all items in the basket indexed by a sorting text
			foreach ($this->basket->itemArray as $sort=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					$cc++;
					$theFields.='
	<input type="hidden" name="ordline'.$cc.'-1" value="'.htmlspecialchars($actItem['rec']['itemnumber']).'">
	<input type="hidden" name="ordline'.$cc.'-2" value="'.htmlspecialchars($actItem['rec']['title']).'">
	<input type="hidden" name="ordline'.$cc.'-3" value="'.$actItem['count'].'">
	<input type="hidden" name="ordline'.$cc.'-4" value="'.$priceViewObj->priceFormat($actItem['totalTax']).'">';
				}
			}

			$theFields.='
<input type="hidden" name="priceinfo1.Shipping"
value="'.$priceViewObj->priceFormat($calculatedArray['shipping']['priceTax']).'">';
			$theFields.='
<input type="hidden" name="priceinfo2.Payment"
value="'.$priceViewObj->priceFormat($calculatedArray['payment']['priceTax']).'">';
			$theFields.='
<input type="hidden" name="priceinfo3.Tax"
value="'.$priceViewObj->priceFormat($calculatedArray['priceTax']['total'] - $calculatedArray['priceNoTax']['total']).'">';
			$markerArray['###HIDDENFIELDS###'].=$theFields;
		}
		$content= $pibase->cObj->substituteMarkerArrayCached($content, $markerArray);
	break;
	case 'decline':
		$markerArray=array();
		$markerArray['###REASON_CODE###'] = t3lib_div::_GP('reason');
		$content=$basketView->getView($localTemplateCode, 'PAYMENT', $infoViewObj, FALSE, FALSE, TRUE, 'DIBS_DECLINE_TEMPLATE',$markerArray);	  // This not only gets the output but also calculates the basket total, so it's NECESSARY!
	break;
	case 'cancel':
		$content=$basketView->getView($localTemplateCode, 'PAYMENT',$infoViewObj, FALSE, FALSE, TRUE, 'DIBS_SOLOE_CANCEL_TEMPLATE',$markerArray);	 // This not only gets the output but also calculates the basket total, so it's NECESSARY!
	break;
	case 'accept':
		$content=$basketView->getView($localTemplateCode, 'PAYMENT',$infoViewObj, FALSE, FALSE, TRUE, 'DIBS_ACCEPT_TEMPLATE');
	// This is just done to calculate stuff

			// DIBS md5 keys
		$k1=$lConf['k1'];
		$k2=$lConf['k2'];

			// Checking transaction
		$amount=round($calculatedArray['priceTax']['total'] *100);
		$currency='208';
		$transact=t3lib_div::_GP('transact');
		$md5key= md5($k2.md5($k1.'transact='.$transact.'&amount='.$amount.'&currency='.$currency));
		$authkey=t3lib_div::_GP('authkey');
		if ($md5key != $authkey)	{
			$content=$basketView->getView($localTemplateCode, 'PAYMENT', $infoViewObj, FALSE, FALSE, TRUE, 'DIBS_DECLINE_MD5_TEMPLATE');		// This not only gets the output but also calculates the basket total, so it's NECESSARY!
		} elseif (t3lib_div::_GP('orderid')!=$order->getNumber($orderUid)) {
			$content=$basketView->getView($localTemplateCode, 'PAYMENT',$infoViewObj, FALSE, FALSE, TRUE, 'DIBS_DECLINE_ORDERID_TEMPLATE');		// This not only gets the output but also calculates the basket total, so it's NECESSARY!
		} else {
			$markerArray=array();
			$markerArray['###TRANSACT_CODE###'] = t3lib_div::_GP('transact');

			$content=$basketView->getView($tmp='', 'PAYMENT', $infoViewObj, FALSE, FALSE, TRUE, 'BASKET_ORDERCONFIRMATION_TEMPLATE',$markerArray);
			$error=''; // TODO
			$this->order->finalize($basketView->templateCode, $basketView, $this->basket->tt_products /* TODO */,$this->basket->tt_products_cat, $this->basket->price, $orderUid,$content,$error);  // Important: $oder->finalize MUST come after the call of prodObj->getBasket, because this function, getBasket, calculates the order! And that information is used in the finalize-function
		}
	break;
	default:
		if ($lConf['relayURL']) {
			$markerArray=array();
			$markerArray['###REDIRECT_URL###'] = 'https://payment.architrade.com/cgi-ssl/relay.cgi/'.$lConf['relayURL'].'&products_cmd=cardno&products_finalize=1'.$param;
			$content=$basketView->getView($localTemplateCode, 'PAYMENT', $infoViewObj, FALSE, FALSE, TRUE, 'DIBS_REDIRECT_TEMPLATE',$markerArray);
		} else {
			$content = 'NO .relayURL given!!';
		}
	break;
}
?>
