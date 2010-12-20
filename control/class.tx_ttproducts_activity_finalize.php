<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2008 Franz Holzinger <contact@fholzinger.com>
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
 * base class for the finalization activity
 *
 * $Id$
 *
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_email_div.php');

class tx_ttproducts_activity_finalize {

	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;

	var $alwaysInStock;
	var $useArticles;

	/**
	 * Initialization of member variables
	 *
	 * @param	object		$$pibase: object derived from tslib_pibase
	 * @return	void
	 */
	function init(&$pibase)  {
		$this->pibase = &$pibase;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		if (intval($this->conf['alwaysInStock'])) {
			$this->alwaysInStock = 1;
		} else {
			$this->alwaysInStock = 0;
		}
		$this->useArticles = $this->conf['useArticles'];
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
	 *
	 * @param	string		$templateCode: HTML template content
	 * @param	object		$basketView: tx_ttproducts_basket_view
	 * @param	string		$funcTablename: functional table name
	 * @param	integer		$orderUid: order uid
	 * @param	string		$orderConfirmationHTML: HTML template for the order confirmation
	 * @param	string		out: $error_message: error message with details
	 * @param	object		$address: tx_ttproducts_info_view
	 * @return	void
	 */
	function doProcessing($templateCode, &$basketView, $funcTablename, $orderUid, &$orderConfirmationHTML, &$error_message, &$address)	{
		global $TSFE;
		global $TYPO3_DB;

		$basket = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

		$instockTableArray = '';
		$recipientsArray = array();
		$recipientsArray['customer'] = array();
		$customerEmail = ($this->conf['orderEmail_toDelivery'] && $address->infoArray['delivery']['email'] || !$address->infoArray['billing']['email'] ? $address->infoArray['delivery']['email'] : $address->infoArray['billing']['email']); // former: deliveryInfo
		$recipientsArray['customer'][] = $customerEmail;
		$recipientsArray['shop'] = $tablesObj->get('tt_products_cat')->getEmail($basket->itemArray);
		$recipientsArray['shop'][] = $this->conf['orderEmail_to'];
		$markerArray = array('###CUSTOMER_RECIPIENTS_EMAIL###' => implode(',', $recipientsArray['customer']));
		if (
			isset ($this->conf['orderEmail_radio.']) && is_array($this->conf['orderEmail_radio.']) &&
			isset ($this->conf['orderEmail_radio.']['1.']) && is_array($this->conf['orderEmail_radio.']) &&
			isset ($this->conf['orderEmail_radio.']['1.'][$address->infoArray['delivery']['radio1']])
		)	{
			$recipientsArray['radio1'][] = $this->conf['orderEmail_radio.']['1.'][$address->infoArray['delivery']['radio1']];
		}
		$orderConfirmationHTML = $this->pibase->cObj->substituteMarkerArray($orderConfirmationHTML,$markerArray);
		$order = &$tablesObj->get('sys_products_orders');
		$apostrophe = $this->conf['orderEmail_apostrophe'];

		// Move the user creation in front so that when we create the order we have a fe_userid so that the order lists work.
		// Is no user is logged in --> create one
		if ($this->conf['createUsers'] && $address->infoArray['billing']['email'] != '' && (trim($TSFE->fe_user->user['username']) == '')) {
			$pid = ($this->conf['PIDuserFolder'] ? $this->conf['PIDuserFolder'] : ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id));
			$pid = intval($pid);
			$username = strtolower(trim($address->infoArray['billing']['email']));
			$res = $TYPO3_DB->exec_SELECTquery('username', 'fe_users', 'username=\''.$username.'\''.' AND pid='. $pid.' AND deleted=0');
			$num_rows = $TYPO3_DB->sql_num_rows($res);
			$TYPO3_DB->sql_free_result($res);

			if (!$num_rows)	{
				$address->password = substr(md5(rand()), 0, 12);
				$tableFieldArray = $tablesObj->get('fe_users')->getTableObj()->tableFieldArray;
				$insertFields = array(	// TODO: check with TCA
					'pid' => intval($pid),
					'tstamp' => time(),
					'crdate' => time(),
					'username' => $username,
					'password' => $address->password,
					'usergroup' => $this->conf['memberOfGroup'],
					'uid' => $address->infoArray['billing']['feusers_uid'],
				);

				foreach ($tableFieldArray as $fieldname => $value)	{
					$fieldvalue = $address->infoArray['billing'][$fieldname];
					if (isset($fieldvalue))	{
						$insertFields[$fieldname] = $fieldvalue;
					}
				}

				$countryKey = ($this->conf['useStaticInfoCountry'] ? 'static_info_country' : 'country');
				$insertFields[$countryKey] = $address->infoArray['billing']['country'];

				if($address->infoArray['billing']['date_of_birth'])	{
					$date = str_replace ('-', '/', $address->infoArray['billing']['date_of_birth']);
					$insertFields['date_of_birth'] = strtotime($date);
				}
				$res = $TYPO3_DB->exec_INSERTquery('fe_users', $insertFields);
				// send new user mail
				if (count($address->infoArray['billing']['email'])) {
					$empty='';
					$emailContent=trim($basketView->getView($empty, 'EMAIL', $address, false, false, false, '###EMAIL_NEWUSER_TEMPLATE###'));
					if ($emailContent) {
						$parts = split(chr(10),$emailContent,2);
						$subject=trim($parts[0]);
						$plain_message = trim($parts[1]);
						tx_ttproducts_email_div::send_mail(
							$address->infoArray['billing']['email'],
							$apostrophe.$subject.$apostrophe,
							$plain_message,
							$tmp='',
							$this->conf['orderEmail_from'],
							$apostrophe.$this->conf['orderEmail_fromName'].$apostrophe
						);
					}
				}
				$res = $TYPO3_DB->exec_SELECTquery(
					'uid',
					'fe_users',
					'username='.$TYPO3_DB->fullQuoteStr($username,'fe_users') .
						' AND pid='. $pid.' AND deleted=0');
				while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
			 		 $address->infoArray['billing']['feusers_uid'] = intval($row['uid']);
				}
				$TYPO3_DB->sql_free_result($res);
			}
		}
		$voucherObj = $tablesObj->get('voucher');
		$voucherObj->delete();

		// get credit card info
		$card = &$tablesObj->get('sys_products_cards');
		$cardUid = $card->getUid();

		// get bank account info
		$account = &$tablesObj->get('sys_products_accounts');
		$accountUid = $account->getUid();
		$rc = $order->putRecord(
			$orderUid,
			$address->infoArray['delivery'],
			$address->infoArray['billing']['feusers_uid'],
			$cardUid,
			$accountUid,
			$this->conf['email_notify_default'],	// Email notification is set here. Default email address is delivery email contact
			$basket->basketExtra['payment'][0].': '.$basket->basketExtra['payment.']['title'],
			$basket->basketExtra['shipping'][0].': '.$basket->basketExtra['shipping.']['title'],
			$basket->calculatedArray['priceTax']['total'],
			$orderConfirmationHTML,
			$address
		);

		// any gift orders in the extended basket?
		if ($basket->basketExt['gift']) {
			$pid = intval($this->conf['PIDGiftsTable']);
			if (!$pid)	$pid = intval($TSFE->id);
			$rc = tx_ttproducts_gifts_div::saveOrderRecord(
				$orderUid,
				$pid,
				$basket->basketExt['gift']
			);
		}

			// Fetching the order Record by selecing the newly saved one...
		// $orderRecord = $this->getRecord($orderUid);  needed?

		if (!$this->alwaysInStock) {
			$viewTable = &$tablesObj->get($funcTablename);
			$instockTableArray =
				$viewTable->reduceInStockItems(
					$basket->itemArray,
					$this->useArticles
				);
			// loop over all items in the basket indexed by a sort string
		}

		$order->createMM($orderUid, $basket->itemArray);
		$addcsv = '';
		// Generate CSV for each order
		if ($this->conf['generateCSV'])	{
			include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_csv.php');
			$csv = t3lib_div::makeInstance('tx_ttproducts_csv');
			$csv->init(
				$this->pibase,
				$basket->itemArray,
				$basket->calculatedArray,
				$accountUid
			);

			$csvfilepath = PATH_site.$this->conf['CSVdestination'];
			$csvorderuid = $basket->recs['tt_products']['orderUid'];
			$csv->create($funcTablename, $address, $csvorderuid, $csvfilepath, $error_message);
			if (!$this->conf['CSVnotInEmail'])	{
				$addcsv = $csvfilepath;
			}
		}

			// Sends order emails:
		$recipientsGroupsArray = array ('shop', 'customer');
		if ($recipientsArray['customer'])	{	// If there is a customer as recipient, then compile and send the mail.
			$emailTemplateArray = array();
			$emailTemplateArray['customer'] = 'EMAIL_PLAINTEXT_TEMPLATE';
			$emailTemplateArray['shop'] = 'EMAIL_PLAINTEXT_TEMPLATE_SHOP';
			if ($recipientsArray['radio1'])	{
				$emailTemplateArray['radio1'] = 'EMAIL_PLAINTEXT_TEMPLATE_RADIO1';
			}
			$emailContentArray = array();
			$subjectArray = array();
			$plainMessageArray = array();
			$markerArray = array();
			$markerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
			$empty = '';
			foreach ($emailTemplateArray as $key => $emailTemplate) {
				$emailContentArray[$key] = trim($basketView->getView($empty, 'EMAIL', $address, false, true, $this->conf['orderEmail_htmlmail'], '###'.$emailTemplate.'###'));
				if ($emailContentArray[$key])	{	// If there is plain text content - which is required!!
					$parts = preg_split('/[\n\r]+/',$emailContentArray[$key],2);	// First line is subject
					$subjectArray[$key]=trim($parts[0]);
					$plainMessageArray[$key]=trim($parts[1]);
					if (empty($plainMessageArray[$key])) {	// the user did not use the subject field
						$plainMessageArray[$key] = $subjectArray[$key];
					}
					$plainMessageArray[$key] = $this->pibase->cObj->substituteMarkerArrayCached($plainMessageArray[$key],$markerArray);
					if (empty($subjectArray[$key])) {
						$subjectArray[$key] = $this->conf['orderEmail_subject'];
					}
				}
			}

			if ($plainMessageArray['shop'])	{
				if ($this->conf['orderEmail_order2'])	{
					$recipientsArray['customer'] = array_merge ($recipientsArray['customer'], $recipientsArray['shop']);
				}
			} else {
				$plainMessageArray['shop'] = $plainMessageArray['customer'];
				$subjectArray['shop'] = $subjectArray['customer'];
			}

			$HTMLmailContent = '';
			if ($plainMessageArray['customer'] || $this->conf['orderEmail_htmlmail'])	{	// If there is plain text content - which is required!!
				if ($this->conf['orderEmail_htmlmail'])	{
					include_once (PATH_t3lib.'class.t3lib_htmlmail.php');
					$cls = t3lib_div::makeInstanceClassName('t3lib_htmlmail');
				}
				if (class_exists($cls) && $this->conf['orderEmail_htmlmail'])	{	// If htmlmail lib is included, then generate a nice HTML-email
					$HTMLmailShell=$this->pibase->cObj->getSubpart($templateCode,'###EMAIL_HTML_SHELL###');
					$HTMLmailContent=$this->pibase->cObj->substituteMarker($HTMLmailShell,'###HTML_BODY###',$orderConfirmationHTML);
					$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
					$HTMLmailContent=
						$this->pibase->cObj->substituteMarkerArray(
							$HTMLmailContent,
							$markerObj->getGlobalMarkerArray()
						);

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
				} else {	// ... else just plain text...
					// nothing to initialize
				}

				$agbAttachment = ($this->conf['AGBattachment'] ? t3lib_div::getFileAbsFileName($this->conf['AGBattachment']) : '');

				if (is_array($recipientsArray['customer']))	{
					foreach ($recipientsArray['customer'] as $key => $recipient) {

						tx_ttproducts_email_div::send_mail(
							$recipient,
							$apostrophe.$subjectArray['customer'].$apostrophe,
							$plainMessageArray['customer'],
							$HTMLmailContent,
							$this->conf['orderEmail_from'],
							$apostrophe.$this->conf['orderEmail_fromName'].$apostrophe,
							$agbAttachment
						);
					}
				}
				if (is_array($recipientsArray['shop']))	{
					foreach ($recipientsArray['shop'] as $key => $recipient) {
						// $headers variable removed everywhere!
						tx_ttproducts_email_div::send_mail(
							$recipient,
							$apostrophe.$subjectArray['shop'].$apostrophe,
							$plainMessageArray['shop'],
							$HTMLmailContent,
							$customerEmail,
							$apostrophe.$address->infoArray['billing']['name'].$apostrophe,
							$addcsv
						);
					}
				}
				if ($plainMessageArray['radio1'] && is_array($recipientsArray['radio1']))	{
					foreach ($recipientsArray['radio1'] as $key => $recipient) {

						tx_ttproducts_email_div::send_mail(
							$recipient,
							$apostrophe.$subjectArray['radio1'].$apostrophe,
							$plainMessageArray['radio1'],
							$HTMLmailContent,
							$this->conf['orderEmail_from'],
							$apostrophe.$this->conf['orderEmail_fromName'].$apostrophe,
							$agbAttachment
						);
					}
				}

				if (is_array($instockTableArray) && $this->conf['warningInStockLimit'])	{
					$tableDescArray = array ('tt_products' => 'product', 'tt_products_articles' => 'article');
					foreach ($instockTableArray as $tablename => $instockArray)	{
						$tableDesc = tx_div2007_alpha::getLL($this->pibase, $tableDescArray[$tablename]);
						foreach ($instockArray as $instockTmp => $count)	{
							$uidItemnrTitle = t3lib_div::trimExplode(',', $instockTmp);
							if ($count <= $this->conf['warningInStockLimit'])	{
								$messageArr =  explode('|', $message = tx_div2007_alpha::getLL($this->pibase,'instock_warning'));
								$subject = $messageArr[0].$tableDesc.' "'.$uidItemnrTitle[2].'"'.$messageArr[1].$uidItemnrTitle[1].$messageArr[2];
								foreach ($recipientsArray['shop'] as $key => $recipient) {
									// $headers variable removed everywhere!
									tx_ttproducts_email_div::send_mail(
										$recipient,
										$apostrophe.$subject.$apostrophe,
										$subject,
										$tmp='',	// no HTML order confirmation email for shop admins
										$this->conf['orderEmail_from'],
										$apostrophe.$this->conf['orderEmail_fromName'].$apostrophe
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
		tx_div2007_alpha::getExternalCObject_fh001($this->pibase, 'externalFinalizing');
		if ($this->conf['externalOrderProcessFunc'])    {
			tx_div2007_alpha::userProcess_fh001($this->pibase, $this->conf, 'externalOrderProcessFunc', $basket);
		}

			// Call all finalizeOrder hooks
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['finalizeOrder'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['finalizeOrder'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'finalizeOrder')) {
					$hookObj->finalizeOrder($this, $address, $templateCode, $basketView, $funcTablename, $orderUid, $orderConfirmationHTML, $error_message);
				}
			}
		}
	} // doProcessing
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_activity_finalize.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_activity_finalize.php']);
}


?>
