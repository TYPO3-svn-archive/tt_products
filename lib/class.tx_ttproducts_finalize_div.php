<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
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
 * order finalize functions
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Els Verberne <verberne@bendoo.nl>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_article_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_creditpoints_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_csv.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_email_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_order.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_view_div.php');



class tx_ttproducts_finalize_div {

	/**
	 * Finalize an order
	 *
	 * This finalizes an order by saving all the basket info in the current order_record.
	 * A finalized order is then marked 'not deleted' and with status=1
	 * The basket is also emptied, but address info is preserved for any new orders.
	 * $orderUid is the order-uid to finalize
	 * $mainMarkerArray is optional and may be pre-prepared fields for substitutiong in the template.
	 */
	function finalizeOrder(&$pibase, &$conf, $templateCode, &$basket, &$tt_products, &$tt_products_cat, &$price, $orderUid, $orderConfirmationHTML, &$error_message)	{
		global $TSFE;
		global $TYPO3_DB;

		$content = '';
		$recipientsArray = array();

		// CBY 11/11/2005 I moved the user creation in front so that when we create the order we have a fe_userid so that the order lists work.
		// Is no user is logged in --> create one
		if ($conf['createUsers'] && $basket->personInfo['email'] != '' && $conf['PIDuserFolder'] && (trim($TSFE->fe_user->user['username']) == ''))
		{
			$username = strtolower(trim($basket->personInfo['email']));

			$res = $TYPO3_DB->exec_SELECTquery('username', 'fe_users', 'username="'.$username . '" AND deleted=0');
			$num_rows = $TYPO3_DB->sql_num_rows($res);

			if (!$num_rows)
			{
				$this->password = substr(md5(rand()), 0, 6);

				$insertFields = array(
					'pid' => $conf['PIDuserFolder'],
					'tstamp' => time(),
					'username' => $username,
					'password' => $this->password,
					'usergroup' => $conf['memberOfGroup'],
/* Added Els: introduce a field into sys_products_orders containing the uid of the fe_user */
					'uid' => $basket->personInfo['feusers_uid'],
					'company' => $basket->personInfo['company'],
					'name' => $basket->personInfo['name'],
					'first_name' => $basket->personInfo['first_name'],
					'last_name' => $basket->personInfo['last_name'],
					'address' => $basket->personInfo['address'],
					'telephone' => $basket->personInfo['telephone'],
					'fax' => $basket->personInfo['fax'],
					'email' => $basket->personInfo['email'],
					'zip' => $basket->personInfo['zip'],
					'city' => $basket->personInfo['city'],
					'crdate' => time()
				);

				$countryKey = ($conf['useStaticInfoCountry'] ? 'static_info_country':'country');
				$insertFields[$countryKey] =  $basket->personInfo['country'];

				$res = $TYPO3_DB->exec_INSERTquery('fe_users', $insertFields);
				// send new user mail
				if (count($basket->personInfo['email'])) {
					$emailContent=trim($basket->getBasket($tmp='','###EMAIL_NEWUSER_TEMPLATE###'));
					if ($emailContent) {
						$parts = split(chr(10),$emailContent,2);
						$subject=trim($parts[0]);
						$plain_message=trim($parts[1]);

						tx_ttproducts_email_div::send_mail($basket->personInfo['email'], $subject, $plain_message, $conf['orderEmail_from'], $conf['orderEmail_fromName']);
					}
				}
				$res = $TYPO3_DB->exec_SELECTquery(uid, 'fe_users', 'username="'.$username . '" AND deleted=0');
           				while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
             					 $basket->personInfo['feusers_uid']= $row['uid'];
            			}

			}
		}
		// CBY 11/11/2005 modification end

		$rc = $basket->order->putOrderRecord(
			$orderUid,
			$basket->deliveryInfo,
			$basket->personInfo['feusers_uid'],
			$conf['email_notify_default'],		// Email notification is set here. Default email address is delivery email contact
			$basket->basketExtra['payment'].': '.$basket->basketExtra['payment.']['title'],
			$basket->basketExtra['shipping'].': '.$basket->basketExtra['shipping.']['title'],
			$basket->calculatedArray['priceTax']['total'],
			$orderConfirmationHTML
			);

		// any gift orders in the extended basket?
		if ($basket->basketExt['gift']) {
			$pid = intval($conf['PIDGiftsTable']);
			if (!$pid)	$pid = intval($TSFE->id);
			$rc = tx_ttproducts_gifts_div::saveOrderRecord(
				$orderUid,
				$pid,
				$basket->basketExt['gift']
			);
		}

			// Fetching the orderRecord by selecing the newly saved one...
		$this->orderRecord = $basket->order->getOrderRecord($orderUid);
		$content .= $basket->getBasket($tmp='','###BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE###');

		if (!$conf['alwaysInStock'] && !$conf['AlwaysInStock']) {
			$rc = $tt_products->reduceInStock($basket->itemArray, $conf['useArticles']);
		}

		$basket->order->createMM($conf,$orderUid, $basket->itemArray);

		$addcsv = '';
		// Generate CSV for each order
		if ($conf['generateCSV'])
		{
			$csv = t3lib_div::makeInstance('tx_ttproducts_csv');
			$csv->init($this,$conf,$basket->itemArray,$basket->calculatedArray,$price);
			$csvfilepath = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') .'/'. $conf['CSVdestination'];
			$csvorderuid = $basket->recs['tt_products']['orderUid'];

			$csv->create($basket, $csvorderuid, $csvfilepath, $error_message);
			$addcsv = $csvfilepath;
		}

			// Sends order emails:
		$recipientsArray['customer'] = array();
		$recipientsArray['customer'][] = ($conf['orderEmail_toDelivery'] ? $basket->deliveryInfo['email'] : $basket->personInfo['email']); // former: deliveryInfo
		$recipientsArray['shop'] = $tt_products_cat->getEmail($basket->itemArray);
		$recipientsArray['shop'][] = $conf['orderEmail_to'];

		if ($recipientsArray['customer'])	{	// If there is a customer as recipient, then compile and send the mail.
			$emailTemplateArray = array();
			$emailTemplateArray['customer'] = 'EMAIL_PLAINTEXT_TEMPLATE';
			$emailTemplateArray['shop'] = 'EMAIL_PLAINTEXT_TEMPLATE_SHOP';
			$emailContentArray = array();
			$subjectArray = array();
			$plainMessageArray = array();

			
			foreach ($emailTemplateArray as $key => $emailTemplate) {
				$emailContentArray[$key] = trim($basket->getBasket($tmp='','###'.$emailTemplate.'###'));
				if ($emailContentArray[$key])	{		// If there is plain text content - which is required!!
					$parts = preg_split('/[\n\r]+/',$emailContentArray[$key],2);		// First line is subject
					$subjectArray[$key]=trim($parts[0]);
					$plainMessageArray[$key]=trim($parts[1]);
					if (empty($plainMessageArray[$key])) {	// the user did not use the subject field
						$plainMessageArray[$key] = $subjectArray[$key];
					}
					if (empty($subjectArray[$key])) {	
						$subjectArray[$key] = $conf['orderEmail_subject'];
					}
				}
			}
	
			if ($emailContentArray['customer'])	{		// If there is plain text content - which is required!!	
				$cls  = t3lib_div::makeInstanceClassName('tx_ttproducts_htmlmail');
				if (class_exists($cls) && $conf['orderEmail_htmlmail'])	{	// If htmlmail lib is included, then generate a nice HTML-email
					$HTMLmailShell=$pibase->cObj->getSubpart($this->templateCode,'###EMAIL_HTML_SHELL###');
					$HTMLmailContent=$pibase->cObj->substituteMarker($HTMLmailShell,'###HTML_BODY###',$orderConfirmationHTML);
					$HTMLmailContent=$pibase->cObj->substituteMarkerArray($HTMLmailContent, $this->globalMarkerArray);

						// Remove image tags to products:
					if ($conf['orderEmail_htmlmail.']['removeImagesWithPrefix'])	{
						$parser = t3lib_div::makeInstance('t3lib_parsehtml');
						$htmlMailParts = $parser->splitTags('img',$HTMLmailContent);

						reset($htmlMailParts);
						while(list($kkk,$vvv)=each($htmlMailParts))	{
							if ($kkk%2)	{
								list($attrib) = $parser->get_tag_attributes($vvv);
								if (t3lib_div::isFirstPartOfStr($attrib['src'],$conf['orderEmail_htmlmail.']['removeImagesWithPrefix']))	{
									$htmlMailParts[$kkk]='';
								}
							}
						}
						$HTMLmailContent=implode('',$htmlMailParts);
					}

					$Typo3_htmlmail = t3lib_div::makeInstance('tx_ttproducts_htmlmail');
					$Typo3_htmlmail->useBase64();

					if ($emailContentArray['shop']) {
						$V = array (
							'from_email' => $basket->personInfo['email'],
							'from_name' => $basket->personInfo['name']
						);
						$V['attachment'] = $addcsv;
						$Typo3_htmlmail->start(implode($recipientsArray['shop'],','), $subjectArray['shop'], $plainMessageArray['shop'], $HTMLmailContent, $V);
						$Typo3_htmlmail->sendtheMail();
					} else {
						$recipientsArray['customer']=array_merge($recipientsArray['customer'], $recipientsArray['shop']);
					}
					$V = array (
						'from_email' => $conf['orderEmail_from'],
						'from_name' => $conf['orderEmail_fromName']
					);
					$V['attachment'] = ($conf['AGBattachment'] ? $conf['AGBattachment'] : '');
					$Typo3_htmlmail->start(implode($recipientsArray['customer'],','), $subjectArray['customer'], $plainMessageArray['customer'], $HTMLmailContent, $V);
					$Typo3_htmlmail->sendtheMail();

				} else {		// ... else just plain text...
					$plainMessageShop = '';
					if ($plainMessageArray['shop'])	{
						$plainMessageShop = $plainMessageArray['shop'];
					} else {
						$plainMessageShop = $plainMessageArray['customer'];
						$recipientsArray['customer']=array_merge($recipientsArray['customer'], $recipientsArray['shop']);
						$subjectArray['shop'] = $subjectArray['customer'];
					}
					foreach ($recipientsArray['customer'] as $key => $recipient) {
						tx_ttproducts_email_div::send_mail($recipient, $subjectArray['customer'], $plainMessageArray['customer'], $conf['orderEmail_from'], $conf['orderEmail_fromName'], $conf['AGBattachment']);
					}
					foreach ($recipientsArray['shop'] as $key => $recipient) {
						// $headers variable removed everywhere!
						tx_ttproducts_email_div::send_mail($recipient, $subjectArray['shop'], $plainMessageShop, $basket->personInfo['email'], $basket->personInfo['name'], $addcsv);
					}
				}
			}
		}

			// This cObject may be used to call a function which clears settings in an external order system.
			// The output is NOT included anywhere
		$pibase->getExternalCObject('externalFinalizing');

		return $content;
	} // finalizeOrder

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_finalize_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_finalize_div.php']);
}


?>
