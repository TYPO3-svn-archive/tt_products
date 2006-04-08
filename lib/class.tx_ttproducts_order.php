<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Franz Holzinger <kontakt@fholzinger.com>
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
 * order functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_email_div.php');

class tx_ttproducts_order {

	var $pibase; // reference to object of pibase
	var $conf;
	var $basket;


//	var $config;
//	var $page;
//	var $tt_content; // element of class tx_table_db
//	var $tt_products; // element of class tx_table_db
//	var $tt_products_cat; // element of class tx_table_db


	function init(&$pibase, &$conf, &$basket) {
		 $this->pibase = &$pibase;
		 $this->conf = &$conf;
		 $this->basket = &$basket;


//		 $this->config = &$config;
//		 $this->page = &$page;
//		 $this->tt_content = &$tt_content;
//		 $this->tt_products = &$tt_products;
//		 $this->tt_products_cat = &$tt_products_cat;

	}


	// **************************
	// ORDER related functions
	// **************************

	/**
	 * Create a new order record
	 *
	 * This creates a new order-record on the page with pid, .PID_sys_products_orders. That page must exist!
	 * Should be called only internally by eg. $order->getBlankUid, that first checks if a blank record is already created.
	 */
	function create()	{
		global $TSFE;

		$newId = 0;
		$pid = intval($this->conf['PID_sys_products_orders']);
		if (!$pid)	$pid = intval($TSFE->id);

		if ($TSFE->sys_page->getPage_noCheck ($pid))	{
			$advanceUid = 0;
			if ($this->conf['advanceOrderNumberWithInteger'] || $this->conf['alwaysAdvanceOrderNumber'])	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', '', '', 'uid DESC', '1');
				list($prevUid) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

				if ($this->conf['advanceOrderNumberWithInteger']) {
					$rndParts = explode(',',$this->conf['advanceOrderNumberWithInteger']);
					$advanceUid = $prevUid+t3lib_div::intInRange(rand(intval($rndParts[0]),intval($rndParts[1])),1);
				} else {
					$advanceUid = $prevUid + 1;
				}
			}

			$time = time();
			$insertFields = array(
				'pid' => $pid,
				'tstamp' => $time,
				'crdate' => $time,
				'deleted' => 1
			);
			if ($advanceUid > 0)	{
				$insertFields['uid'] = $advanceUid;
			}

			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_products_orders', $insertFields);

			$newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
		}
		return $newId;
	} // create


	/**
	 * Returns a blank order uid. If there was no order id already, a new one is created.
	 *
	 * Blank orders are marked deleted and with status=0 initialy. Blank orders are not necessarily finalized because users may abort instead of buying.
	 * A finalized order is marked 'not deleted' and with status=1.
	 * Returns this uid which is a blank order record uid.
	 */
	function getBlankUid()	{
		global $TSFE;

	// an new orderUid has been created always because also payment systems can be used which do not accept a duplicate order id
		$orderUid = intval($this->basket->recs['tt_products']['orderUid']);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', 'uid='.intval($orderUid).' AND deleted AND NOT status');	// Checks if record exists, is marked deleted (all blank orders are deleted by default) and is not finished.
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res) || $this->conf['alwaysAdvanceOrderNumber'])	{
			$orderUid = $this->create();
			$this->basket->recs['tt_products']['orderUid'] = $orderUid;
			$this->basket->recs['tt_products']['orderDate'] = time();
			$this->basket->recs['tt_products']['orderTrackingNo'] = $this->getNumber($orderUid).'-'.strtolower(substr(md5(uniqid(time())),0,6));
			$TSFE->fe_user->setKey('ses','recs',$this->basket->recs);
		}
		return $orderUid;
	} // getBlankUid


	/**
	 * Returns the order record if $orderUid.
	 * If $tracking is set, then the order with the tracking number is fetched instead.
	 */
	function getRecord($orderUid,$tracking='')	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_products_orders', ($tracking ? 'tracking_code="'.$GLOBALS['TYPO3_DB']->quoteStr($tracking, 'sys_products_orders').'"' : 'uid='.intval($orderUid)).' AND NOT deleted');
		return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	} //getRecord


	/**
	 * This returns the order-number (opposed to the order_uid) for display in the shop, confirmation notes and so on.
	 * Basically this prefixes the .orderNumberPrefix, if any
	 */
	function getNumber($orderUid)	{
		$orderNumberPrefix = substr($this->conf['orderNumberPrefix'],0,10);
		if ($orderNumberPrefix[0]=='%')
			$orderNumberPrefix = date(substr($orderNumberPrefix, 1));
		return $orderNumberPrefix.$orderUid;
	} // getNumber


	/**
	 * Saves the order record and returns the result
	 *
	 */
	function putRecord($orderUid,&$deliveryInfo, $feusers_uid, $email_notify, $payment, $shipping, $amount, &$orderConfirmationHTML)	{
		global $TYPO3_DB;
		global $TSFE;

			// Fix delivery address
		$this->basket->mapPersonIntoToDelivery();	// This maps the billing address into the blank fields of the delivery address
//		$mainMarkerArray['###EXTERNAL_COBJECT###'] = $this->externalCObject.'';

			// Saving order data
		$fieldsArray=array();
/* Added Els: introduce a field into sys_products_orders containing the uid of the fe_user */
		$fieldsArray['feusers_uid']=$feusers_uid;
		$fieldsArray['name']=$deliveryInfo['name'];
		$fieldsArray['first_name']=$deliveryInfo['first_name'];
		$fieldsArray['last_name']=$deliveryInfo['last_name'];
		$fieldsArray['salutation']=$deliveryInfo['salutation'];
		$fieldsArray['address']=$deliveryInfo['address'];
		$fieldsArray['zip']=$deliveryInfo['zip'];
		$fieldsArray['city']=$deliveryInfo['city'];
		$fieldsArray['country']=$deliveryInfo['country'];
		$fieldsArray['telephone']=$deliveryInfo['telephone'];
		$fieldsArray['fax']=$deliveryInfo['fax'];
		$fieldsArray['email']=$deliveryInfo['email'];
		$fieldsArray['email_notify']= $email_notify;

			// can be changed after order is set.
		$fieldsArray['payment']=$payment;
		$fieldsArray['shipping']=$shipping;
		$fieldsArray['amount']=$amount;
		$fieldsArray['desired_date']=$deliveryInfo['desired_date'];
		$fieldsArray['status']=1;	// This means, "Order confirmed on website, next step: confirm from shop that order is received"
		$fieldsArray['note']=$deliveryInfo['note'];
		$fieldsArray['client_ip']=t3lib_div::getIndpEnv('REMOTE_ADDR');

/*
		//<-- MKL 2004.09.21
		$fieldsArray['company']=$this->personInfo['company'];
		$fieldsArray['vat_id']=$this->personInfo['vat_id'];
		$fieldsArray['country_code']=$this->personInfo['country_code'];
		//--> MKL 2004.09.21
*/


/* Added Els: update fe_user with amount of creditpoints and subtract creditpoints used in order*/
		$fieldsArrayFeUsers = array();
		$uid_voucher = ''; // define it here

		if ($this->conf['creditpoints.']) {
			$creditpoints = tx_ttproducts_creditpoints_div::getCreditPoints($fieldsArray['amount']);
/* Added els4: update fe_user with amount of creditpoints (= exisitng amount - used_creditpoints - spended_creditpoints + saved_creditpoints */
//			$fieldsArrayFeUsers['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'] + ($creditpoints * $this->calculatedArray['priceTax']['total']) - $this->basket->recs['tt_products']['creditpoints'];
			$fieldsArrayFeUsers['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'] - $this->basket->recs['tt_products']['creditpoints'] - t3lib_div::_GP('creditpoints_spended') + t3lib_div::_GP('creditpoints_saved');
		}

/* Added Els: update fe_user with vouchercode */
		if ($this->basket->recs['tt_products']['vouchercode'] != '') {
			// first check if vouchercode exist and is not their own vouchercode
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'fe_users', 'username="'.$this->basket->recs['tt_products']['vouchercode'].'"');
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$uid_voucher = $row['uid'];
			}
			if (($uid_voucher != '') && ($this->basket->deliveryInfo['feusers_uid'] != $uid_voucher) ) {
				$fieldsArrayFeUsers['tt_products_vouchercode'] = $this->basket->recs['tt_products']['vouchercode'];
			}
		}

		if ($this->basket->deliveryInfo['feusers_uid']) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$this->basket->deliveryInfo['feusers_uid'], $fieldsArrayFeUsers);
	/* Added ELS2: update user from vouchercode with 5 credits */
			tx_ttproducts_creditpoints_div::addCreditPoints($this->basket->recs['tt_products']['vouchercode'], 5);
		}

				// Default status_log entry
		$status_log=array();
		$status_log[] = array(
			'time' => time(),
			'info' => $this->conf['statusCodes.'][$fieldsArray['status']],
			'status' => $fieldsArray['status'],
			'comment' => $this->basket->deliveryInfo['note']
		);
		$fieldsArray['status_log']=serialize($status_log);

			// Order Data serialized
		$fieldsArray['orderData']=serialize(array(
				'html_output' 			=>	$orderConfirmationHTML,
				'deliveryInfo' 			=>	$this->basket->deliveryInfo,
				'personInfo' 			=>	$this->basket->personInfo,
				'itemArray'				=>	$this->basket->itemArray,
				'calculatedArray'		=>	$this->basket->calculatedArray
		));

			// Setting tstamp, deleted and tracking code
		$fieldsArray['tstamp']=time();
		$fieldsArray['deleted']=0;
		$fieldsArray['tracking_code']=$this->basket->recs['tt_products']['orderTrackingNo'];
		$fieldsArray['agb']		= $this->basket->personInfo['agb'];
/* Added Els: write creditpointvalue into sys_products_order */
		$fieldsArray['creditpoints'] = $this->basket->recs['tt_products']['creditpoints'];
/* Added Els4: write creditpoint_spended and saved value into sys_products_order */
		$fieldsArray['creditpoints_spended'] = t3lib_div::_GP('creditpoints_spended');
		$fieldsArray['creditpoints_saved'] = t3lib_div::_GP('creditpoints_saved');
/* Added Els6: write creditpoint_gifts value into sys_products_order */
		$fieldsArray['creditpoints_gifts'] = t3lib_div::_GP('creditpoints_gifts');
		
			// Saving the order record
		$TYPO3_DB->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderUid), $fieldsArray);

	} //putRecord




	/**
	 * Creates M-M relations for the products with tt_products table. Isn't really used yet, but later will be used to display stock-status by looking up how many items are already ordered.
	 *
	 */
	function createMM($orderUid, &$itemArray)	{
		global $TYPO3_DB;

			// First: delete any existing. Shouldn't be any
		$where='sys_products_orders_uid='.$orderUid;
		$res = $TYPO3_DB->exec_DELETEquery('sys_products_orders_mm_tt_products',$where);

			// Second: Insert a new relation for each ordered item
		reset($itemArray);

		// loop over all items in the basket indexed by page and itemnumber
		foreach ($itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				if ($this->conf['useArticles']) {
					foreach ($actItemArray as $k1=>$actItem) {
						// get the article uid with these colors, sizes and gradings
						$query='uid_product=\''.intval($actItem['rec']['uid']).'\' AND color=\''.$actItem['rec']['color'].'\' AND size=\''.$actItem['rec']['size'].'\' AND description=\''.$actItem['rec']['description'].'\' AND gradings=\''.$actItem['rec']['gradings'].'\'';

						$res = $TYPO3_DB->exec_SELECTquery('uid', 'tt_products_articles', $query);

						if ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
							$insertFields = array (
								'sys_products_orders_uid' => $orderUid,
								'sys_products_orders_qty' => intval($actItem['count']),
								'tt_products_uid' => intval($actItem['rec']['uid']),
								'tt_products_articles_uid' => intval($row['uid'])
							);
							$TYPO3_DB->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
						}
					}
				} else {
					foreach ($actItemArray as $k1=>$actItem) {
						$insertFields = array (
							'sys_products_orders_uid' => $orderUid,
							'sys_products_orders_qty' => intval($actItem['count']),
							'tt_products_uid' => intval($actItem['rec']['uid'])
						);
						$TYPO3_DB->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
					}
				}
			}
		}
	}


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
	function finalize($templateCode, &$basketView, &$tt_products, &$tt_products_cat, &$price, $orderUid, &$orderConfirmationHTML, &$error_message)	{
		global $TSFE;
		global $TYPO3_DB;

		$recipientsArray = array();
		$recipientsArray['customer'] = array();
		$recipientsArray['customer'][] = ($this->conf['orderEmail_toDelivery'] ? $this->basket->deliveryInfo['email'] : $this->basket->personInfo['email']); // former: deliveryInfo
		$recipientsArray['shop'] = $tt_products_cat->getEmail($this->basket->itemArray);
		$recipientsArray['shop'][] = $this->conf['orderEmail_to'];
		$markerArray = array('###CUSTOMER_RECIPIENTS_EMAIL###' => implode(',', $recipientsArray['customer']));
		$orderConfirmationHTML= $this->pibase->cObj->substituteMarkerArray($orderConfirmationHTML,$markerArray);
		
		// Move the user creation in front so that when we create the order we have a fe_userid so that the order lists work.
		// Is no user is logged in --> create one
		if ($this->conf['createUsers'] && $this->basket->personInfo['email'] != '' && $this->conf['PIDuserFolder'] && (trim($TSFE->fe_user->user['username']) == ''))
		{
			$username = strtolower(trim($this->basket->personInfo['email']));

			$res = $TYPO3_DB->exec_SELECTquery('username', 'fe_users', 'username=\''.$username.'\''.' AND pid='. $this->conf['PIDuserFolder'].' AND deleted=0');
			$num_rows = $TYPO3_DB->sql_num_rows($res);

			if (!$num_rows)
			{
				$this->basket->password = substr(md5(rand()), 0, 6);

				$insertFields = array(
					'pid' => $this->conf['PIDuserFolder'],
					'tstamp' => time(),
					'username' => $username,
					'password' => $this->basket->password,
					'usergroup' => $this->conf['memberOfGroup'],
					'uid' => $this->basket->personInfo['feusers_uid'],
					'company' => $this->basket->personInfo['company'],
					'name' => $this->basket->personInfo['name'],
					'first_name' => $this->basket->personInfo['first_name'],
					'last_name' => $this->basket->personInfo['last_name'],
					'address' => $this->basket->personInfo['address'],
					'telephone' => $this->basket->personInfo['telephone'],
					'fax' => $this->basket->personInfo['fax'],
					'email' => $this->basket->personInfo['email'],
					'zip' => $this->basket->personInfo['zip'],
					'city' => $this->basket->personInfo['city'],
					'crdate' => time()
				);

				$countryKey = ($this->conf['useStaticInfoCountry'] ? 'static_info_country':'country');
				$insertFields[$countryKey] =  $this->basket->personInfo['country'];

				$res = $TYPO3_DB->exec_INSERTquery('fe_users', $insertFields);
				// send new user mail
				if (count($this->basket->personInfo['email'])) {
					$emailContent=trim($basketView->getView($tmp='','###EMAIL_NEWUSER_TEMPLATE###'));
					if ($emailContent) {
						$parts = split(chr(10),$emailContent,2);
						$subject=trim($parts[0]);
						$plain_message=trim($parts[1]);

						tx_ttproducts_email_div::send_mail($this->basket->personInfo['email'], $subject, $plain_message, $tmp='', $this->conf['orderEmail_from'], $this->conf['orderEmail_fromName']);
					}
				}
				$res = $TYPO3_DB->exec_SELECTquery(uid, 'fe_users', 'username=\''.$username . '\' AND pid='. $this->conf['PIDuserFolder'].' AND deleted=0');
							while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
			 					 $this->basket->personInfo['feusers_uid']= $row['uid'];
						}

			}
		}

		$rc = $this->putRecord(
			$orderUid,
			$this->basket->deliveryInfo,
			$this->basket->personInfo['feusers_uid'],
			$this->conf['email_notify_default'],		// Email notification is set here. Default email address is delivery email contact
			$this->basket->basketExtra['payment'].': '.$this->basket->basketExtra['payment.']['title'],
			$this->basket->basketExtra['shipping'].': '.$this->basket->basketExtra['shipping.']['title'],
			$this->basket->calculatedArray['priceTax']['total'],
			$orderConfirmationHTML
			);

		// any gift orders in the extended basket?
		if ($this->basket->basketExt['gift']) {
			$pid = intval($this->conf['PIDGiftsTable']);
			if (!$pid)	$pid = intval($TSFE->id);
			$rc = tx_ttproducts_gifts_div::saveOrderRecord(
				$tt_products,
				$orderUid,
				$pid,
				$this->basket->basketExt['gift']
			);
		}

			// Fetching the order Record by selecing the newly saved one...
		// $orderRecord = $this->getRecord($orderUid);  needed?

		if (!$this->conf['alwaysInStock'] && !$this->conf['AlwaysInStock']) {
			$rc = $tt_products->reduceInStock($this->basket->itemArray, $this->conf['useArticles']);
		}

		$this->createMM($orderUid, $this->basket->itemArray);

		$addcsv = '';
		// Generate CSV for each order
		if ($this->conf['generateCSV'])
		{
			include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_csv.php');
			$csv = t3lib_div::makeInstance('tx_ttproducts_csv');
			$csv->init($this->pibase,$this->conf,$this->basket->itemArray,$this->basket->calculatedArray,$price,$this);
			$csvfilepath = PATH_site.'/'. $this->conf['CSVdestination'];
			$csvorderuid = $this->basket->recs['tt_products']['orderUid'];

			$csv->create($this->basket, $csvorderuid, $csvfilepath, $error_message);
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
			foreach ($emailTemplateArray as $key => $emailTemplate) {
				$emailContentArray[$key] = trim($basketView->getView($tmp='','###'.$emailTemplate.'###'));
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

					$V = array ();
					$V['shop']['from_email'] = $this->basket->personInfo['email'];
					$V['shop']['from_name'] = $this->basket->personInfo['name'];
					$V['shop']['attachment'] = $addcsv;
					$V['default']['from_email'] = $this->conf['orderEmail_from'];
					$V['default']['from_name'] = $this->conf['orderEmail_fromName'];							
					$V['default']['attachment'] = ($this->conf['AGBattachment'] ? t3lib_div::getFileAbsFileName($this->conf['AGBattachment']) : '');

					foreach ($recipientsGroupsArray as $key => $group)	{
						$vGroup = ($group == 'shop' ? $group : 'default');
						
						tx_ttproducts_email_div::send_mail(
							implode($recipientsArray[$group],','),
							$subjectArray[$group],
							$plainMessageArray[$group],
							$HTMLmailContent,
							$V[$vGroup]['from_email'],
							$this->conf['orderEmail_from'],
							$V[$vGroup]['from_name']);
						
//						$Typo3_htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
//						$Typo3_htmlmail->start();
//						$Typo3_htmlmail->useBase64();
//						$Typo3_htmlmail->subject = $subjectArray[$group];
//						$Typo3_htmlmail->from_name = $V[$vGroup]['from_name'];
//						$Typo3_htmlmail->from_email = $V[$vGroup]['from_email'];
//						$Typo3_htmlmail->replyto_email = $Typo3_htmlmail->from_email;
//						$Typo3_htmlmail->replyto_name = $Typo3_htmlmail->from_name;
//						$Typo3_htmlmail->addAttachment($V[$vGroup]['attachment']);
//						$Typo3_htmlmail->theParts['html']['content'] = $HTMLmailContent; // Fetches the content of the page
//						$Typo3_htmlmail->theParts['html']['path'] = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/';
//						$Typo3_htmlmail->extractMediaLinks();
//						$Typo3_htmlmail->extractHyperLinks();
//						$Typo3_htmlmail->fetchHTMLMedia();
//						$Typo3_htmlmail->substMediaNamesInHTML(0);	// 0 = relative
//						$Typo3_htmlmail->substHREFsInHTML();  
//						$Typo3_htmlmail->setHTML($Typo3_htmlmail->encodeMsg($Typo3_htmlmail->theParts['html']['content']));
//						$Typo3_htmlmail->addPlain($plainMessageArray[$group]);
//						$Typo3_htmlmail->setHeaders();
//						$Typo3_htmlmail->setContent();
//						$Typo3_htmlmail->setRecipient(implode($recipientsArray[$group],','));
//						// $Typo3_htmlmail->start(implode($recipientsArray[$group],','), $subjectArray[$group], $plainMessageArray[$group], $HTMLmailContent, $V);
//						$Typo3_htmlmail->sendtheMail();
					}

				} else {		// ... else just plain text...
					$agbAttachment = t3lib_div::getFileAbsFileName($this->conf['AGBattachment']);
					foreach ($recipientsArray['customer'] as $key => $recipient) {
						tx_ttproducts_email_div::send_mail($recipient, $subjectArray['customer'], $plainMessageArray['customer'], $tmp='', $this->conf['orderEmail_from'], $this->conf['orderEmail_fromName'], $agbAttachment);
					}
					foreach ($recipientsArray['shop'] as $key => $recipient) {
						// $headers variable removed everywhere!
						tx_ttproducts_email_div::send_mail($recipient, $subjectArray['shop'], $plainMessageArray['shop'], $tmp='', $this->basket->personInfo['email'], $this->basket->personInfo['name'], $addcsv);
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
					$hookObj->finalizeOrder($this, $templateCode, $basketView, $tt_products, $tt_products_cat, $price, $orderUid, $orderConfirmationHTML, $error_message);
				}
			}
		}	
		
	} // finalize

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_order.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_order.php']);
}


?>
