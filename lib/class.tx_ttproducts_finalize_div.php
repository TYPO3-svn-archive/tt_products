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
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_order_div.php');
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
	function finalizeOrder(&$pibase, &$product, $orderUid, $orderConfirmationHTML, &$error_message)	{
		global $TSFE;
		global $TYPO3_DB;

		$content = '';

		$rc = tx_ttproducts_order_div::putOrderRecord(
			$orderUid,
			$this->deliveryInfo, 
			$this->personInfo['feusers_uid'],
			$this->conf['email_notify_default'],		// Email notification is set here. Default email address is delivery email contact
			$this->basketExtra['payment'].': '.$this->basketExtra['payment.']['title'],
			$this->basketExtra['shipping'].': '.$this->basketExtra['shipping.']['title'],
			$this->calculatedArray['priceTax']['total'],
			$orderConfirmationHTML
			);

		// any gift orders in the extended basket?
		if ($this->basketExt['gift']) {
			$pid = intval($this->conf['PIDGiftsTable']);
			if (!$pid)	$pid = intval($TSFE->id);
			$rc = tx_ttproducts_gifts_div::saveOrderRecord(
				$orderUid,
				$pid, 
				$this->basketExt['gift']
			);
		}

			// Fetching the orderRecord by selecing the newly saved one...
		$this->orderRecord = tx_ttproducts_order_div::getOrderRecord($orderUid);
		$content .= tx_ttproducts_basket_div::getBasket($pibase,'###BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE###');


		// Is no user is logged in --> create one
		if ($this->conf['createUsers'] && $this->personInfo['email'] != '' && $this->conf['PIDuserFolder'] && (trim($TSFE->fe_user->user['username']) == ''))
		{
			$username = strtolower(trim($this->personInfo['email']));

			$res = $TYPO3_DB->exec_SELECTquery('username', 'fe_users', 'username="'.$username . '" AND deleted=0');
			$num_rows = $TYPO3_DB->sql_num_rows($res);

			if (!$num_rows)
			{
				$this->password = substr(md5(rand()), 0, 6);

				$insertFields = array(
					'pid' => $this->conf['PIDuserFolder'],
					'tstamp' => time(),
					'username' => $username,
					'password' => $this->password,
					'usergroup' => $this->conf['memberOfGroup'],
/* Added Els: introduce a field into sys_products_orders containing the uid of the fe_user */
					'uid' => $this->personInfo['feusers_uid'],
					'company' => $this->personInfo['company'],
					'name' => $this->personInfo['name'],
					'first_name' => $this->personInfo['first_name'],
					'last_name' => $this->personInfo['last_name'],
					'address' => $this->personInfo['address'],
					'telephone' => $this->personInfo['telephone'],
					'fax' => $this->personInfo['fax'],
					'email' => $this->personInfo['email'],
					'zip' => $this->personInfo['zip'],
					'city' => $this->personInfo['city'],
					'crdate' => time()
				);

				$countryKey = ($this->conf['useStaticInfoCountry'] ? 'static_info_country':'country');
				$insertFields[$countryKey] =  $this->personInfo['country'];

				$res = $TYPO3->exec_INSERTquery('fe_users', $insertFields);
				// send new user mail
				if (count($this->personInfo['email'])) {
					$emailContent=trim(tx_ttproducts_basket_div::getBasket($pibase,'###EMAIL_NEWUSER_TEMPLATE###'));
					if ($emailContent) {
						$parts = split(chr(10),$emailContent,2);
						$subject=trim($parts[0]);
						$plain_message=trim($parts[1]);

						tx_ttproducts_email_div::send_mail($this->personInfo['email'], $subject, $plain_message, $this->conf['orderEmail_from'], $this->conf['orderEmail_fromName']);
					}
				}
			}
		}


		if (!$this->conf['alwaysInStock'] && !$this->conf['AlwaysInStock']) {
			$rc = $product->reduceInStock($this->itemArray, $this->conf['useArticles']);
		}

		tx_ttproducts_order_div::createMM($orderUid, $this->itemArray);

		// Generate CSV for each order
		if ($this->conf['generateCSV'])
		{
			$csv = t3lib_div::makeInstance('tx_ttproducts_csv');
			$csv->init($this,$this->itemArray,$this->calculatedArray);
			$csvfilepath = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') .'/'. $this->conf['CSVdestination'];
			$csvorderuid = $this->recs['tt_products']['orderUid'];
						
			$csv->create($csvorderuid, $csvfilepath, $error_message);
		}

			// Sends order emails:
		$recipients = $this->conf['orderEmail_to'];
		$recipients.=','.$this->personInfo['email']; // former: deliveryInfo

		$emailArray = $this->category->getEmail($this->itemArray);
		if (count ($emailArray)) {
			foreach ($emailArray as $key => $email) {
				$recipients.=','.$email['email'];
			}
		}
		$recipients=t3lib_div::trimExplode(',',$recipients,1);

		if (count($recipients))	{	// If any recipients, then compile and send the mail.
			$emailContent=trim(tx_ttproducts_basket_div::getBasket($pibase,'###EMAIL_PLAINTEXT_TEMPLATE###'));
			if ($emailContent)	{		// If there is plain text content - which is required!!
				$parts = split(chr(13),$emailContent,2);		// First line is subject
				$subject=trim($parts[0]);
				$plain_message=trim($parts[1]);
				if (empty($plain_message)) {	// the user did not use the subject field
					$plain_message = $subject;
					$subject = $this->conf['orderEmail_subject'];
				}

				$cls  = t3lib_div::makeInstanceClassName('tx_ttproducts_htmlmail');
				if (class_exists($cls) && $this->conf['orderEmail_htmlmail'])	{	// If htmlmail lib is included, then generate a nice HTML-email
					$HTMLmailShell=$this->cObj->getSubpart($this->templateCode,'###EMAIL_HTML_SHELL###');
					$HTMLmailContent=$this->cObj->substituteMarker($HTMLmailShell,'###HTML_BODY###',$orderConfirmationHTML);
					$HTMLmailContent=$this->cObj->substituteMarkerArray($HTMLmailContent, $this->globalMarkerArray);


						// Remove image tags to products:
					if ($this->conf['orderEmail_htmlmail.']['removeImagesWithPrefix'])	{
						$parser = t3lib_div::makeInstance('t3lib_parsehtml');
						$htmlMailParts = $parser->splitTags('img',$HTMLmailContent);

						reset($htmlMailParts);
						while(list($kkk,$vvv)=each($htmlMailParts))	{
							if ($kkk%2)	{
								list($attrib) = $parser->get_tag_attributes($vvv);
								if (t3lib_div::isFirstPartOfStr($attrib['src'],$this->conf['orderEmail_htmlmail.']['removeImagesWithPrefix']))	{
									$htmlMailParts[$kkk]='';
								}
							}
						}
						$HTMLmailContent=implode('',$htmlMailParts);
					}

					$V = array (
						'from_email' => $this->conf['orderEmail_from'],
						'from_name' => $this->conf['orderEmail_fromName'],
						'attachment' => ($this->conf['AGBattachment'] ? $this->conf['AGBattachment'] : '')
					);

					$Typo3_htmlmail = t3lib_div::makeInstance('tx_ttproducts_htmlmail');
					$Typo3_htmlmail->useBase64();
					$Typo3_htmlmail->start(implode($recipients,','), $subject, $plain_message, $HTMLmailContent, $V);
					$Typo3_htmlmail->sendtheMail();
				} else {		// ... else just plain text...
					// $headers variable removed everywhere!
					tx_ttproducts_email_div::send_mail($this->personInfo['email'], $subject, $plain_message, $this->conf['orderEmail_from'], $this->conf['orderEmail_fromName'], $this->conf['AGBattachment']);
					if ($this->conf['generateCSV'])
						$addcsv = $csvfilepath;
					else
						$addcsv = '';
					tx_ttproducts_email_div::send_mail($this->conf['orderEmail_to'], $subject, $plain_message, $this->personInfo['email'], $this->personInfo['name'], $addcsv);
				}
			}
		}

			// This cObject may be used to call a function which clears settings in an external order system.
			// The output is NOT included anywhere
		$this->getExternalCObject('externalFinalizing');

		return $content;
	} // finalizeOrder

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_finalize_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_finalize_div.php']);
}


?>
