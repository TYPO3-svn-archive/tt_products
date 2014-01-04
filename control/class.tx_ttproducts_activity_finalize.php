<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2011 Franz Holzinger <franz@ttproducts.de>
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
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
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
	function init ($pibase)  {
		$this->pibase = $pibase;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
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
	function doProcessing ($templateCode, $basketView, $funcTablename, $orderUid, &$orderConfirmationHTML, &$error_message, &$address, $mainMarkerArray)	{
		global $TSFE;
		global $TYPO3_DB;

		$basket = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');

		$instockTableArray = '';
		$empty = '';
		$recipientsArray = array();
		$recipientsArray['customer'] = array();
		$emailTemplateArray = array();
		$emailTemplateArray['customer'] = 'EMAIL_PLAINTEXT_TEMPLATE';
		if ($recipientsArray['radio1'])	{
			$emailTemplateArray['radio1'] = 'EMAIL_PLAINTEXT_TEMPLATE_RADIO1';
		}
		$customerEmail = ($this->conf['orderEmail_toDelivery'] && $address->infoArray['delivery']['email'] || !$address->infoArray['billing']['email'] ? $address->infoArray['delivery']['email'] : $address->infoArray['billing']['email']); // former: deliveryInfo

		$defaultFromArray = array();
		$defaultFromArray['shop'] = array(
			'email' => $this->conf['orderEmail_from'],
			'name' => $this->conf['orderEmail_fromName']
		);
		$defaultFromArray['customer'] = array(
			'email' => $customerEmail,
			'name' => $address->infoArray['billing']['name']
		);

		$fromArray = $defaultFromArray;
		$recipientsArray['customer'][] = $customerEmail;
		$recipientsArray['shop'] = $tablesObj->get('tt_products_cat')->getEmail($basket->itemArray);
		$recipientsArray['shop'][] = $this->conf['orderEmail_to'];
### START

		if (isset($this->conf['orderEmail.']) && is_array($this->conf['orderEmail.']))	{
			foreach ($this->conf['orderEmail.'] as $k => $emailConfig) {
				$suffix = $emailConfig['suffix'];
				if ($suffix != '' && ($emailConfig['to'] != '' || $suffix == 'shop' || $suffix == 'customer')) {
					if ($emailConfig['to'] != '') {
						$emailArray = t3lib_div::trimExplode(',', $emailConfig['to']);
						foreach ($emailArray as $email) {
							$recipientsArray[$suffix][] = $email;
						}
					}
					if ($suffix != 'customer') {
						$emailTemplateArray[$suffix] = 'EMAIL_PLAINTEXT_TEMPLATE_' . strtoupper($suffix);
					}
					if (!$emailConfig['from'] || $emailConfig['from'] == 'shop') {
						$fromArray[$suffix] = $defaultFromArray['shop'];
					} else if ($emailConfig['from'] == 'customer') {
						$fromArray[$suffix] = $defaultFromArray['customer'];
					} else if (isset($emailConfig['from.'])) {
						$fromArray[$suffix] = array(
							'email' => $emailConfig['from.']['email'],
							'name' => $emailConfig['from.']['name']
						);
					}
				}
			}
		}
### ENDE

		$markerArray = array_merge($mainMarkerArray,$markerObj->getGlobalMarkerArray());
		$markerArray['###CUSTOMER_RECIPIENTS_EMAIL###'] = implode(',', $recipientsArray['customer']);

		if (
			isset ($this->conf['orderEmail_radio.']) && is_array($this->conf['orderEmail_radio.']) &&
			isset ($this->conf['orderEmail_radio.']['1.']) && is_array($this->conf['orderEmail_radio.']) &&
			isset ($this->conf['orderEmail_radio.']['1.'][$address->infoArray['delivery']['radio1']])
		)	{
			$recipientsArray['radio1'][] = $this->conf['orderEmail_radio.']['1.'][$address->infoArray['delivery']['radio1']];
		}
		$orderConfirmationHTML = $this->pibase->cObj->substituteMarkerArray($orderConfirmationHTML, $markerArray);
		$orderObj = $tablesObj->get('sys_products_orders');
		$apostrophe = $this->conf['orderEmail_apostrophe'];

		// Move the user creation in front so that when we create the order we have a fe_userid so that the order lists work.
		// Is no user is logged in --> create one
		if ($this->conf['createUsers'] && $address->infoArray['billing']['email'] != '' && (trim($TSFE->fe_user->user['username']) == '')) {
			$pid = ($this->conf['PIDuserFolder'] ? $this->conf['PIDuserFolder'] : ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id));
			$pid = intval($pid);
			$username = strtolower(trim($address->infoArray['billing']['email']));
			$res = $TYPO3_DB->exec_SELECTquery('username', 'fe_users', 'username='.$TYPO3_DB->fullQuoteStr($username, 'fe_users') . ' AND pid=' . $pid . ' AND deleted=0');
			$num_rows = $TYPO3_DB->sql_num_rows($res);
			$TYPO3_DB->sql_free_result($res);

			if (!$num_rows)	{
				$password = $address->password = substr(md5(rand()), 0, 12);
				if ($this->conf['useMd5Password'])	{
					$password = md5($password);
				}
				$tableFieldArray = $tablesObj->get('fe_users')->getTableObj()->tableFieldArray;
				$insertFields = array(	// TODO: check with TCA
					'pid' => intval($pid),
					'tstamp' => time(),
					'crdate' => time(),
					'username' => $username,
					'password' => $password,
					'usergroup' => $this->conf['memberOfGroup'],
					'uid' => $address->infoArray['billing']['feusers_uid'],
				);

				foreach ($tableFieldArray as $fieldname => $value)	{
					$fieldvalue = $address->infoArray['billing'][$fieldname];
					if (isset($fieldvalue))	{
						$insertFields[$fieldname] = $fieldvalue;
					}
				}

				if ($this->conf['useStaticInfoCountry'])	{
					$insertFields['static_info_country'] = $address->infoArray['billing']['country_code'];
				}

				if($address->infoArray['billing']['date_of_birth'])	{
					$date = str_replace ('-', '/', $address->infoArray['billing']['date_of_birth']);
					$insertFields['date_of_birth'] = strtotime($date);
				}
				$res = $TYPO3_DB->exec_INSERTquery('fe_users', $insertFields);
				// send new user mail
				if (count($address->infoArray['billing']['email'])) {
					$emailContent=trim($basketView->getView($empty, 'EMAIL', $address, false, false, false, '###EMAIL_NEWUSER_TEMPLATE###', $markerObj->getGlobalMarkerArray()));
					if ($emailContent) {
						$parts = explode(chr(10), $emailContent, 2);
						$subject=trim($parts[0]);
						$plain_message = trim($parts[1]);
						tx_ttproducts_email_div::send_mail(
							$address->infoArray['billing']['email'],
							$apostrophe.$subject.$apostrophe,
							$plain_message,
							$tmp='',
							$fromArray['customer']['email'],
							$fromArray['customer']['name']
						);
					}
				}
				$res = $TYPO3_DB->exec_SELECTquery(
					'uid',
					'fe_users',
					'username='.$TYPO3_DB->fullQuoteStr($username, 'fe_users') .
						' AND pid='. $pid.' AND deleted=0');
				while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
			 		 $address->infoArray['billing']['feusers_uid'] = intval($row['uid']);
				}
				$TYPO3_DB->sql_free_result($res);
			}
		}

		$orderObj->setData($orderUid, $orderConfirmationHTML, 1);
		$creditpointsObj = t3lib_div::getUserObj('&tx_ttproducts_field_creditpoints');
		$creditpointsObj->pay();

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
			$viewTable = $tablesObj->get($funcTablename);
			$instockTableArray =
				$viewTable->reduceInStockItems(
					$basket->itemArray,
					$this->useArticles
				);
			// loop over all items in the basket indexed by a sort string
		}

		$addcsv = '';
		// Generate CSV for each order
		if ($this->conf['generateCSV'])	{
			$account = $tablesObj->get('sys_products_accounts');
			$accountUid = $account->getUid();

			include_once (PATH_BE_ttproducts . 'lib/class.tx_ttproducts_csv.php');
			$csv = t3lib_div::makeInstance('tx_ttproducts_csv');
			$csv->init(
				$this->pibase,
				$basket->itemArray,
				$basket->calculatedArray,
				$accountUid
			);

			$csvfilepath = PATH_site.$this->conf['CSVdestination'];
			$csvorderuid = $basket->order['orderUid'];
			$csv->create($funcTablename, $address, $csvorderuid, $csvfilepath, $error_message);
			if (!$this->conf['CSVnotInEmail'])	{
				$addcsv = $csvfilepath;
			}
		}

			// Sends order emails:
		$recipientsGroupsArray = array ('shop', 'customer');
		if ($GLOBALS['TSFE']->absRefPrefix == '') {
			$absRefPrefix = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
			$markerArray['"index.php'] = '"' . $absRefPrefix . 'index.php';
		}

		$tmpl = 'BASKET_ORDERCONFIRMATION_TEMPLATE';
		$customerEmailHTML =
			$basketView->getView(
				$empty,
				'EMAIL',
				$address,
				FALSE,
				FALSE,
				TRUE,
				'###'.$tmpl.'###',
				$markerArray
			);
		$customerEmailHTML = $this->pibase->cObj->substituteMarkerArray($customerEmailHTML, $markerArray);

		if ($recipientsArray['customer'])	{	// If there is a customer as recipient, then compile and send the mail.
			$emailContentArray = array();
			$subjectArray = array();
			$plainMessageArray = array();
			$markerArray = array();
			$markerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
			if ($GLOBALS['TSFE']->absRefPrefix == '') {
				$absRefPrefix = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
				$markerArray['"index.php'] = '"' . $absRefPrefix . 'index.php';
			}

			foreach ($emailTemplateArray as $key => $emailTemplate) {
				$emailContentArray[$key] = trim($basketView->getView($empty, 'EMAIL', $address, false, true, $this->conf['orderEmail_htmlmail'], '###' . $emailTemplate . '###', $mainMarkerArray));
				if ($emailContentArray[$key]) {	// If there is plain text content - which is required!!
					$parts = preg_split('/[\n\r]+/', $emailContentArray[$key], 2);	// First line is subject
					$subjectArray[$key] = trim($parts[0]);
					$plainMessageArray[$key]=trim($parts[1]);
					if (empty($plainMessageArray[$key])) {	// the user did not use the subject field
						$plainMessageArray[$key] = $subjectArray[$key];
					}
					$plainMessageArray[$key] = $this->pibase->cObj->substituteMarkerArrayCached($plainMessageArray[$key], $markerArray);
					if (empty($subjectArray[$key])) {
						$subjectArray[$key] = $this->conf['orderEmail_subject'];
					}
				}

				if ($key != 'customer' && $plainMessageArray[$key] == '')	{
					$plainMessageArray[$key] = $plainMessageArray['customer'];
					$subjectArray[$key] = $subjectArray['customer'];
				}
			}

			if ($plainMessageArray['shop'] && $this->conf['orderEmail_order2'])	{
				$recipientsArray['customer'] = array_merge($recipientsArray['customer'], $recipientsArray['shop']);
			}

			$HTMLmailContent = '';
			$markerArray = array_merge($mainMarkerArray, $markerObj->getGlobalMarkerArray());

			if ($plainMessageArray['customer'] || $this->conf['orderEmail_htmlmail'])	{	// If there is plain text content - which is required!!


				if ($this->conf['orderEmail_htmlmail'])	{	// If htmlmail lib is included, then generate a nice HTML-email
					$HTMLmailShell = $this->pibase->cObj->getSubpart($templateCode, '###EMAIL_HTML_SHELL###');
					$HTMLmailContent = $this->pibase->cObj->substituteMarker($HTMLmailShell, '###HTML_BODY###', $customerEmailHTML);
					$HTMLmailContent =
						$this->pibase->cObj->substituteMarkerArray(
							$HTMLmailContent,
							$markerArray
						);

						// Remove image tags to the products:
					if ($this->conf['orderEmail_htmlmail.']['removeImagesWithPrefix'])	{

						$parser = tx_div2007_core::newHtmlParser();
						$htmlMailParts = $parser->splitTags('img', $HTMLmailContent);

						foreach($htmlMailParts as $kkk => $vvv)	{
							if ($kkk%2)	{
								list($attrib) = $parser->get_tag_attributes($vvv);
								if (t3lib_div::isFirstPartOfStr($attrib['src'], $this->conf['orderEmail_htmlmail.']['removeImagesWithPrefix']))	{
									$htmlMailParts[$kkk] = '';
								}
							}
						}
						$HTMLmailContent=implode('', $htmlMailParts);
					}
				} else {	// ... else just plain text...
					// nothing to initialize
				}
				$agbAttachment = ($this->conf['AGBattachment'] ? t3lib_div::getFileAbsFileName($this->conf['AGBattachment']) : '');

				if (is_array($recipientsArray['customer']))	{

					foreach ($recipientsArray['customer'] as $key => $recipient) {
						tx_ttproducts_email_div::send_mail(
							$recipient,
							$apostrophe . $subjectArray['customer'] . $apostrophe,
							$plainMessageArray['customer'],
							$HTMLmailContent,
							$fromArray['customer']['email'],
							$fromArray['customer']['name'],
							$agbAttachment
						);
					}
				}
### START
				foreach ($recipientsArray as $type => $recipientTypeArray) {
					if ($type != 'customer' && $type != 'radio1' && is_array($recipientTypeArray))	{
						foreach ($recipientTypeArray as $key => $recipient) {

							// $headers variable removed everywhere!
							tx_ttproducts_email_div::send_mail(
								$recipient,
								$apostrophe . $subjectArray[$type] . $apostrophe,
								$plainMessageArray[$type],
								$HTMLmailContent,
								$fromArray[$type]['email'],
								$fromArray[$type]['name'],
								$addcsv,
								$this->conf['orderEmail_bcc']
							);
						}
					}
				}
### ENDE

				if ($plainMessageArray['radio1'] && is_array($recipientsArray['radio1']))	{
					foreach ($recipientsArray['radio1'] as $key => $recipient) {

						tx_ttproducts_email_div::send_mail(
							$recipient,
							$apostrophe.$subjectArray['radio1'].$apostrophe,
							$plainMessageArray['radio1'],
							$HTMLmailContent,
							$fromArray['shop']['email'],
							$fromArray['shop']['name'],
							$agbAttachment
						);
					}
				}

				if (is_array($instockTableArray) && $this->conf['warningInStockLimit'])	{
					$tableDescArray = array ('tt_products' => 'product', 'tt_products_articles' => 'article');
					foreach ($instockTableArray as $tablename => $instockArray)	{
						$tableDesc = tx_div2007_alpha5::getLL_fh002($langObj, $tableDescArray[$tablename]);
						foreach ($instockArray as $instockTmp => $count)	{
							$uidItemnrTitle = t3lib_div::trimExplode(',', $instockTmp);
							if ($count <= $this->conf['warningInStockLimit'])	{
								$messageArr =  explode('|', $message = tx_div2007_alpha5::getLL_fh002($langObj, 'instock_warning'));
								$subject = $messageArr[0] . $tableDesc . ' "' . $uidItemnrTitle[2] . '"' . $messageArr[1] . $uidItemnrTitle[1] . $messageArr[2];
								foreach ($recipientsArray['shop'] as $key => $recipient) {
									// $headers variable removed everywhere!
									tx_ttproducts_email_div::send_mail(
										$recipient,
										$apostrophe.$subject.$apostrophe,
										$subject,
										$tmp='',	// no HTML order confirmation email for shop admins
										$fromArray['shop']['email'],
										$fromArray['shop']['name']
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
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['finalizeOrder'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['finalizeOrder'] as $classRef) {
				$hookObj= t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'finalizeOrder')) {
					$hookObj->finalizeOrder($this, $address, $templateCode, $basketView, $funcTablename, $orderUid, $orderConfirmationHTML, $error_message);
				}
			}
		}

		$orderObj->clearUid();
	} // doProcessing
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_activity_finalize.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_activity_finalize.php']);
}


?>