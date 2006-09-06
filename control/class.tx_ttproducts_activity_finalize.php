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
 * Part of the tt_products (Shop System) extension.
 *
 * base class for the finalization activity
 *
 * $Id:$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



require_once (PATH_BE_ttproducts.'control/class.tx_ttproducts_activity_base.php');

class tx_ttproducts_activity_finalize {

	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;

	var $basket; 	// the basket object
	var $tt_products_cat;
	var $order;


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, &$cnf, &$basket, &$tt_products_cat, &$order)  {
		global $TYPO3_DB,$TSFE,$TCA;
		
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

 		$this->basket = &$basket;	
		$this->tt_products_cat = &$tt_products_cat;
		$this->order = &$order;
	} // init


	/**
	 * Finalize an order
	 *
	 * This finalizes an order by saving all the basket info in the current order_record.
	 * A finalized order is then marked 'not deleted' and with status=1
	 * The basket is also emptied, but address info is preserved for any new orders.
	 * $orderUid is the order-uid to finalize
	 * $mainMarkerArray is optional and may be pre-prepared fields for substitutiong in the template.
	 * 
	 * returns the email address of the customer to whom the order notification has been sent
	 */
	function doProcessing($templateCode, &$basketView, &$viewTable, &$price, $orderUid, &$orderConfirmationHTML, &$error_message, &$address)	{
		global $TSFE;
		global $TYPO3_DB;

		$instockTableArray = array();
		$recipientsArray = array();
		$recipientsArray['customer'] = array();
		$recipientsArray['customer'][] = ($this->conf['orderEmail_toDelivery'] ? $address->infoArray['delivery']['email'] : $address->infoArray['billing']['email']); // former: deliveryInfo
		$recipientsArray['shop'] = $this->tt_products_cat->getEmail($this->basket->itemArray);
		$recipientsArray['shop'][] = $this->conf['orderEmail_to'];
		$markerArray = array('###CUSTOMER_RECIPIENTS_EMAIL###' => implode(',', $recipientsArray['customer']));
		$orderConfirmationHTML = $this->pibase->cObj->substituteMarkerArray($orderConfirmationHTML,$markerArray);
		
		// Move the user creation in front so that when we create the order we have a fe_userid so that the order lists work.
		// Is no user is logged in --> create one
		if ($this->conf['createUsers'] && $address->infoArray['billing']['email'] != '' && $this->conf['PIDuserFolder'] && (trim($TSFE->fe_user->user['username']) == '')) {
			$username = strtolower(trim($address->infoArray['billing']['email']));
			$res = $TYPO3_DB->exec_SELECTquery('username', 'fe_users', 'username=\''.$username.'\''.' AND pid='. $this->conf['PIDuserFolder'].' AND deleted=0');
			$num_rows = $TYPO3_DB->sql_num_rows($res);

			if (!$num_rows)	{
				$address->password = substr(md5(rand()), 0, 6);
				$insertFields = array(	// TODO: check with TCA
					'pid' => intval($this->conf['PIDuserFolder']),
					'tstamp' => time(),
					'username' => $username,
					'password' => $address->password,
					'usergroup' => $this->conf['memberOfGroup'],
					'uid' => $address->infoArray['billing']['feusers_uid'],
					'company' => $address->infoArray['billing']['company'],
					'name' => $address->infoArray['billing']['name'],
					'first_name' => $address->infoArray['billing']['first_name'],
					'last_name' => $address->infoArray['billing']['last_name'],
					'address' => $address->infoArray['billing']['address'],
					'telephone' => $address->infoArray['billing']['telephone'],
					'fax' => $address->infoArray['billing']['fax'],
					'email' => $address->infoArray['billing']['email'],
					'zip' => $address->infoArray['billing']['zip'],
					'city' => $address->infoArray['billing']['city'],
					'crdate' => time()
				);

				$countryKey = ($this->conf['useStaticInfoCountry'] ? 'static_info_country' : 'country');
				$insertFields[$countryKey] =  $address->infoArray['billing']['country'];
				$res = $TYPO3_DB->exec_INSERTquery('fe_users', $insertFields);
				// send new user mail
				if (count($address->infoArray['billing']['email'])) {
					$empty='';
					$emailContent=trim($basketView->getView($empty,'EMAIL',$address, false, false, '###EMAIL_NEWUSER_TEMPLATE###'));
					if ($emailContent) {
						$parts = split(chr(10),$emailContent,2);
						$subject=trim($parts[0]);
						$plain_message = trim($parts[1]);
						tx_ttproducts_email_div::send_mail(
							$address->infoArray['billing']['email'],
							$subject,
							$plain_message,
							$tmp='',
							$this->conf['orderEmail_from'],
							$this->conf['orderEmail_fromName']
						);
					}
				}
				$res = $TYPO3_DB->exec_SELECTquery(
					'uid',
					'fe_users',
					'username='.$TYPO3_DB->fullQuoteStr($username,'fe_users') . 
						' AND pid='. intval($this->conf['PIDuserFolder']).' AND deleted=0');
				while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
			 		 $address->infoArray['billing']['feusers_uid'] = intval($row['uid']);
				}
			}
		}

		$rc = $this->order->putRecord(
			$orderUid,
			$address->infoArray['delivery'],
			$address->infoArray['billing']['feusers_uid'],
			$this->conf['email_notify_default'],		// Email notification is set here. Default email address is delivery email contact
			$this->basket->basketExtra['payment'].': '.$this->basket->basketExtra['payment.']['title'],
			$this->basket->basketExtra['shipping'].': '.$this->basket->basketExtra['shipping.']['title'],
			$this->basket->calculatedArray['priceTax']['total'],
			$orderConfirmationHTML,
			$address
		);

		// any gift orders in the extended basket?
		if ($this->basket->basketExt['gift']) {
			$pid = intval($this->conf['PIDGiftsTable']);
			if (!$pid)	$pid = intval($TSFE->id);
			$rc = tx_ttproducts_gifts_div::saveOrderRecord(
				$this->tt_products,
				$orderUid,
				$pid,
				$this->basket->basketExt['gift']
			);
		}

			// Fetching the order Record by selecing the newly saved one...
		// $orderRecord = $this->getRecord($orderUid);  needed?

		if (!$this->alwaysInStock) {
			$instockTableArray = 
				$viewTable->reduceInStockItems(
					$this->basket->itemArray, 
					$this->useArticles
				);
			// loop over all items in the basket indexed by a sort string
		}

		$this->order->createMM($orderUid, $this->basket->itemArray);
		$addcsv = '';
		// Generate CSV for each order
		if ($this->conf['generateCSV'])	{
			include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_csv.php');
			$csv = t3lib_div::makeInstance('tx_ttproducts_csv');
			$csv->init(
				$this->pibase,
				$this->cnf,
				$this->basket->itemArray,
				$this->basket->calculatedArray,
				$price,
				$this->order
			);
			
			$csvfilepath = PATH_site.'/'. $this->conf['CSVdestination'];
			$csvorderuid = $this->basket->recs['tt_products']['orderUid'];
			$csv->create($this->basket, $address, $csvorderuid, $csvfilepath, $error_message);
			$addcsv = $csvfilepath;
		}

			// Sends order emails:
		$recipientsGroupsArray = array ('shop', 'customer');
		if ($recipientsArray['customer'])	{	// If there is a customer as recipient, then compile and send the mail.
			$emailTemplateArray = array();
			$emailTemplateArray['customer'] = 'EMAIL_PLAINTEXT_TEMPLATE';
			$emailTemplateArray['shop'] = 'EMAIL_PLAINTEXT_TEMPLATE_SHOP';
			$emailContentArray = array();
			$subjectArray = array();
			$plainMessageArray = array();
			$empty = '';
			foreach ($emailTemplateArray as $key => $emailTemplate) {
				$emailContentArray[$key] = trim($basketView->getView($empty, 'EMAIL', $address, false, true, '###'.$emailTemplate.'###'));
				if ($emailContentArray[$key])	{		// If there is plain text content - which is required!!
					$parts = preg_split('/[\n\r]+/',$emailContentArray[$key],2);		// First line is subject
					$subjectArray[$key]=trim($parts[0]);
					$plainMessageArray[$key]=trim($parts[1]);
					if (empty($plainMessageArray[$key])) {	// the user did not use the subject field
						$plainMessageArray[$key] = $subjectArray[$key];
					}
					if (empty($subjectArray[$key])) {	
						$subjectArray[$key] = $this->conf['orderEmail_subject'];
					}
				}
			}

			if (!$plainMessageArray['shop'])	{
				$plainMessageArray['shop'] = $plainMessageArray['customer'];
				$subjectArray['shop'] = $subjectArray['customer'];
			}
	
			$HTMLmailContent = '';
			if ($emailContentArray['customer'])	{		// If there is plain text content - which is required!!
				if ($this->conf['orderEmail_htmlmail'])	{
					include_once (PATH_t3lib.'class.t3lib_htmlmail.php');
					$cls = t3lib_div::makeInstanceClassName('t3lib_htmlmail');				
				}	
				if (class_exists($cls))	{	// If htmlmail lib is included, then generate a nice HTML-email
					$HTMLmailShell=$this->pibase->cObj->getSubpart($templateCode,'###EMAIL_HTML_SHELL###');
					$HTMLmailContent=$this->pibase->cObj->substituteMarker($HTMLmailShell,'###HTML_BODY###',$orderConfirmationHTML);
					$HTMLmailContent=$this->pibase->cObj->substituteMarkerArray($HTMLmailContent, $this->pibase->globalMarkerArray);

						// Remove image tags to the products:
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

				} else {		// ... else just plain text...
					// nothing to initialize
				}

				$agbAttachment = ($this->conf['AGBattachment'] ? t3lib_div::getFileAbsFileName($this->conf['AGBattachment']) : '');
				foreach ($recipientsArray['customer'] as $key => $recipient) {
					tx_ttproducts_email_div::send_mail(
						$recipient,
						$subjectArray['customer'],
						$plainMessageArray['customer'],
						$HTMLmailContent,
						$this->conf['orderEmail_from'],
						$this->conf['orderEmail_fromName'],
						$agbAttachment
					);
				}
				foreach ($recipientsArray['shop'] as $key => $recipient) {
					// $headers variable removed everywhere!
					tx_ttproducts_email_div::send_mail(
						$recipient,
						$subjectArray['shop'],
						$plainMessageArray['shop'],
						$HTMLmailContent,
						$address->infoArray['billing']['email'],
						$address->infoArray['billing']['name'],
						$addcsv
					);
				}
				if (is_array($instockTableArray) && $this->conf['warningInStockLimit'])	{
					$tableDescArray = array ('tt_products' => 'product', 'tt_products_articles' => 'article');
					foreach ($instockTableArray as $tablename => $instockArray)	{
						$tableDesc = $this->pibase->pi_getLL($tableDescArray[$tablename]);
						foreach ($instockArray as $instockTmp => $count)	{
							$uidItemnrTitle = t3lib_div::trimExplode(',', $instockTmp);
							if ($count <= $this->conf['warningInStockLimit'])	{
								$messageArr =  explode('|', $message = $this->pibase->pi_getLL('instock_warning'));
								$subject = $messageArr[0].$tableDesc.' "'.$uidItemnrTitle[2].'"'.$messageArr[1].$uidItemnrTitle[1].$messageArr[2];
	
								foreach ($recipientsArray['shop'] as $key => $recipient) {
									// $headers variable removed everywhere!
									tx_ttproducts_email_div::send_mail(
										$recipient,
										$subject,
										$subject,
										$tmp='',	// no HTML order confirmation email for shop admins
										$this->conf['orderEmail_from'],
										$this->conf['orderEmail_fromName']
									);
								}							
							}
						}
					}
				}
			}
		}
		
		// 3 different hook methods - There must be one for your needs, too.

			// This cObject may be used to call a function which clears settings in an external order system.
			// The output is NOT included anywhere
		$this->pibase->getExternalCObject('externalFinalizing');

		if ($this->conf['externalOrderProcessFunc'])    {
			$this->pibase->userProcess('externalOrderProcessFunc',$this->basket);
		}
		
			// Call all finalizeOrder hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['finalizeOrder'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['finalizeOrder'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'finalizeOrder')) {
					$hookObj->finalizeOrder($this, $address, $templateCode, $basketView, $viewTable, $price, $orderUid, $orderConfirmationHTML, $error_message);
				}
			}
		}	
	} // doProcessing


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_activity_finalize.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_activity_finalize.php']);
}


?>
