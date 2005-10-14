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
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_email_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_order_div.php');
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
	function finalizeOrder($orderUid, $orderConfirmationHTML)	{
		global $TSFE;
		global $TYPO3_DB;

		$content = '';

			// Fix delivery address
		tx_ttproducts_view_div::mapPersonIntoToDelivery();	// This maps the billing address into the blank fields of the delivery address
//		$mainMarkerArray['###EXTERNAL_COBJECT###'] = $this->externalCObject.'';

		#debug ($this->basketExt, '$this->basketExt', __LINE__, __FILE__);

			// Saving order data
		$fieldsArray=array();
		$fieldsArray['note']=$this->deliveryInfo['note'];
/* Added Els: introduce a field into sys_products_orders containing the uid of the fe_user */
		$fieldsArray['feusers_uid']=$this->personInfo['feusers_uid'];
		$fieldsArray['name']=$this->deliveryInfo['name'];
		$fieldsArray['telephone']=$this->deliveryInfo['telephone'];
		$fieldsArray['fax']=$this->deliveryInfo['fax'];
		$fieldsArray['email']=$this->deliveryInfo['email'];
//		debug ($this->conf['email_notify_default'], "this->conf['email_notify_default']", __LINE__, __FILE__);
		$fieldsArray['email_notify']=  $this->conf['email_notify_default'];		// Email notification is set here. Default email address is delivery email contact

			// can be changed after order is set.
		$fieldsArray['payment']=$this->basketExtra['payment'].': '.$this->basketExtra['payment.']['title'];
		$fieldsArray['shipping']=$this->basketExtra['shipping'].': '.$this->basketExtra['shipping.']['title'];
		$fieldsArray['amount']=$this->calculatedArray['priceTax']['total'];
		$fieldsArray['status']=1;	// This means, "Order confirmed on website, next step: confirm from shop that order is received"

/* Added Els: update fe_user with amount of creditpoints and subtract creditpoints used in order*/
		$fieldsArrayFeUsers = array();
		$uid_voucher = ''; // define it here
		/* example:
  creditpoints {
  10.where =
  10.type = price
  10.prod.1   = 0.02
  10.prod.101 = 0.04
  10.prod.501 = 0.06
}
		 */
		if ($this->conf['creditpoints.']) {
			$creditpoints = tx_ttproducts_creditpoints_div::getCreditPoints($fieldsArray['amount']);
/* Added els4: update fe_user with amount of creditpoints (= exisitng amount - used_creditpoints - spended_creditpoints + saved_creditpoints */
//			$fieldsArrayFeUsers['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'] + ($creditpoints * $this->calculatedArray['priceTax']['total']) - $this->recs['tt_products']['creditpoints'];
			$fieldsArrayFeUsers['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'] - $this->recs['tt_products']['creditpoints'] - t3lib_div::_GP('creditpoints_spended') + t3lib_div::_GP('creditpoints_saved');
					}

/* Added Els: update fe_user with vouchercode */
		if ($this->recs['tt_products']['vouchercode'] != '') {
			// first check if vouchercode exist and is not their own vouchercode
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'fe_users', 'username="'.$this->recs['tt_products']['vouchercode'].'"');
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$uid_voucher = $row['uid'];
			}
			if (($uid_voucher != '') && ($this->deliveryInfo['feusers_uid'] != $uid_voucher) ) {
				$fieldsArrayFeUsers['tt_products_vouchercode'] = $this->recs['tt_products']['vouchercode'];
			}

		}

		if ($this->deliveryInfo['feusers_uid']) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$this->deliveryInfo['feusers_uid'], $fieldsArrayFeUsers);
	/* Added ELS2: update user from vouchercode with 5 credits */
			tx_ttproducts_creditpoints_div::addCreditPoints($this->recs['tt_products']['vouchercode'], 5);
		}


/*
		//<-- MKL 2004.09.21
		$fieldsArray['forename']=$this->personInfo['forename'];
		$fieldsArray['company']=$this->personInfo['company'];
		$fieldsArray['vat_id']=$this->personInfo['vat_id'];
		$fieldsArray['street']=$this->deliveryInfo['street'];
		$fieldsArray['street_n1']=$this->deliveryInfo['street_n1'];
		$fieldsArray['street_n2']=$this->deliveryInfo['street_n2'];
		$fieldsArray['city']=$this->deliveryInfo['city'];
		$fieldsArray['zip']=$this->deliveryInfo['zip'];
		$fieldsArray['country_code']=$this->personInfo['country_code'];
		$fieldsArray['client_ip']=t3lib_div::getIndpEnv('REMOTE_ADDR');
		//--> MKL 2004.09.21
*/

				// Default status_log entry
		$status_log=array();
		$status_log[] = array(
			'time' => time(),
			'info' => $this->conf['statusCodes.'][$fieldsArray['status']],
			'status' => $fieldsArray['status'],
			'comment' => $this->deliveryInfo['note']
		);
		$fieldsArray['status_log']=serialize($status_log);

			// Order Data serialized
		$fieldsArray['orderData']=serialize(array(
				'html_output' 			=>	$orderConfirmationHTML,
				'deliveryInfo' 			=>	$this->deliveryInfo,
				'personInfo' 			=>	$this->personInfo,
				'itemArray'				=>	$this->itemArray,
				'calculatedArray'		=>	$this->calculatedArray
		));

			// Setting tstamp, deleted and tracking code
		$fieldsArray['tstamp']=time();
		$fieldsArray['deleted']=0;
		$fieldsArray['tracking_code']=$this->recs['tt_products']['orderTrackingNo'];
		$fieldsArray['agb']		= $this->personInfo['agb'];
/* Added Els: write creditpointvalue into sys_products_order */
		$fieldsArray['creditpoints'] = $this->recs['tt_products']['creditpoints'];
/* Added Els4: write creditpoint_spended and saved value into sys_products_order */
		$fieldsArray['creditpoints_spended'] = t3lib_div::_GP('creditpoints_spended');
		$fieldsArray['creditpoints_saved'] = t3lib_div::_GP('creditpoints_saved');
/* Added Els6: write creditpoint_gifts value into sys_products_order */
		$fieldsArray['creditpoints_gifts'] = t3lib_div::_GP('creditpoints_gifts');

			// Saving the order record
		$TYPO3_DB->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderUid), $fieldsArray);

		#debug ($this->basketExt['gift'], '$this->basketExt[\'gift\']', __LINE__, __FILE__); // $this->basketExt['gift'][$this->giftnumber]['item'][$uid][$extVars] = $count;
		// any gift orders in the extended basket?
		if ($this->basketExt['gift']) {
			$pid = intval($this->conf['PIDGiftsTable']);
			if (!$pid)	$pid = intval($TSFE->id);

			foreach ($this->basketExt['gift'] as $giftnumber => $rec) {
				$amount = 0;
				foreach ($rec['item'] as $productid => $product) {
					foreach ($product as $variant => $count) {
						$row = array();
						tx_ttproducts_article_div::getRowFromVariant ($row, $variant);
						$amount += intval($row['size']) * $count;
					}
				}
				// Saving gift order data
				$insertFields = array(
					'pid' => $pid,
					'tstamp' => time(),
					'crdate' => time(),
					'deleted' => 0,

					'ordernumber'    => $orderUid,
					'personname'     => $rec['personname'],
					'personemail'    => $rec['personemail'],
					'deliveryname'   => $rec['deliveryname'],
					'deliveryemail'  => $rec['deliveryemail'],
					'note'           => $rec['note'],
					'amount'         => $amount
				);
				// Saving the gifts order record
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_products_gifts', $insertFields);
				$newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
				$insertFields = array();
				$insertFields['uid_local'] = $newId;

				foreach ($rec['item'] as $productid => $product) {
					#debug ($product, 'product', __LINE__, __FILE__);
					foreach ($product as $variant => $count) {
						$row = array();
						tx_ttproducts_article_div::getRowFromVariant ($row, $variant);

						$query='uid_product=\''.intval($productid).'\' AND color=\''.$row['color'].'\' AND size=\''.$row['size'].'\' AND gradings=\''.$row['gradings'].'\'' ;
						$articleRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tt_products_articles', $query);

						if ($articleRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($articleRes)) {
							$insertFields['uid_foreign'] = $articleRow['uid'];
							$insertFields['count'] = $count;
							// Saving the gifts mm order record
							$GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_products_gifts_articles_mm', $insertFields);
						}
					}
				}
			}
		}

			// Fetching the orderRecord by selecing the newly saved one...
		$this->orderRecord = tx_ttproducts_order_div::getOrderRecord($orderUid);
		$content .= tx_ttproducts_basket_div::getBasket('###BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE###');


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

				$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('fe_users', $insertFields);
				// send new user mail
				if (count($this->personInfo['email'])) {
					$emailContent=trim(tx_ttproducts_basket_div::getBasket('###EMAIL_NEWUSER_TEMPLATE###'));
					if ($emailContent) {
						$parts = split(chr(10),$emailContent,2);
						$subject=trim($parts[0]);
						$plain_message=trim($parts[1]);

						tx_ttproducts_email_div::send_mail($this->personInfo['email'], $subject, $plain_message, $this->conf['orderEmail_from'], $this->conf['orderEmail_fromName']);
					}
				}
			}
		}


		if (!$this->conf['AlwaysInStock']) {
			// Reduce inStock
			reset($this->itemArray);

			#debug ($this->itemArray, '$this->itemArray Reduce in stock', __LINE__, __FILE__);

			// loop over all items in the basket indexed by page and itemnumber
			foreach ($this->itemArray as $pid=>$pidItem) {
				foreach ($pidItem as $itemnumber=>$actItemArray) {
					#error_Log ("tt_products  $this->conf['useArticles'] = ".$this->conf['useArticles']);
					if ($this->conf['useArticles']) {
						foreach ($actItemArray as $k1=>$actItem) {
							$query='uid_product=\''.intval($actItem['rec']['uid']).'\' AND color=\''.$actItem['rec']['color'].'\' AND size=\''.$actItem['rec']['size'].'\' AND gradings=\''.$actItem['rec']['gradings'].'\'';

							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('inStock', 'tt_products_articles', $query);
								// 	TODO: Saving the order record support color, size and accessory here
						}
					} else {
						foreach ($actItemArray as $k1=>$actItem) {
							$query='uid=\''.intval($actItem['rec']['uid']).'\'';

							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('inStock', 'tt_products', $query);

							if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
								if ($row['inStock'] > 0) {
									$newInStock = intval($row['inStock'])-intval($actItem['count']);
									if ($newInStock < 0) {
										$newInStock = 0;
									}

									$fieldsArray =array();
											// Setting tstamp, deleted and tracking code
									$fieldsArray['inStock']=$newInStock;

									$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_products', 'uid='.intval($actItem['rec']['uid']), $fieldsArray);
								}
							}
						}
					}
				}
			}
		}

			// Creates M-M relations for the products with tt_products table. Isn't really used yet, but later will be used to display stock-status by looking up how many items are already ordered.
			// First: delete any existing. Shouldn't be any
		$where='sys_products_orders_uid='.$orderUid;
		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_products_orders_mm_tt_products',$where);

			// Second: Insert a new relation for each ordered item
		reset($this->itemArray);

		// loop over all items in the basket indexed by page and itemnumber
		foreach ($this->itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				if ($this->conf['useArticles']) {
					foreach ($actItemArray as $k1=>$actItem) {
						// get the article uid with these colors, sizes and gradings
						$query='uid_product=\''.intval($actItem['rec']['uid']).'\' AND color=\''.$actItem['rec']['color'].'\' AND size=\''.$actItem['rec']['size'].'\' AND gradings=\''.$actItem['rec']['gradings'].'\'';

						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tt_products_articles', $query);

						if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							$insertFields = array (
								'sys_products_orders_uid' => $orderUid,
								'sys_products_orders_qty' => intval($actItemArray[0]['count']),
								'tt_products_uid' => intval($actItemArray[0]['rec']['uid']),
								'tt_products_articles_uid' => intval($row['uid'])
							);
							$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
						}
					}
				} else {
					foreach ($actItemArray as $k1=>$actItem) {
						$insertFields = array (
							'sys_products_orders_uid' => $orderUid,
							'sys_products_orders_qty' => intval($actItem['count']),
							'tt_products_uid' => intval($actItem['rec']['uid'])
						);
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
					}
				}
			}
		}

		// Generate CSV for each order
		if ($this->conf['generateCSV'])
		{
			$csvfilepath = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') .'/'. $this->conf['CSVdestination'];
			if ($csvfilepath[strlen($csvfilepath)-1] != '/') {
				$csvfilepath .= '/';
			}
			$csvfilepath .= tx_ttproducts_order_div::getOrderNumber($this->recs['tt_products']['orderUid']).'.csv';
			$csvfile = fopen($csvfilepath, 'w');
			if ($csvfile !== FALSE)
			{
				// Generate invoice and delivery address
				$csvlinehead = '';
				$csvlineperson = '';
				$csvlinedelivery = '';
/* Added Els: 'feusers_uid,' */
				$infoFields = explode(',','feusers_uid,name,address,telephone,fax,email,company,city,zip,state,country,kk_fa,kk_nr,kk_ablauf,kk_pruefcode,agb');
				while(list(,$fName)=each($infoFields)) {
					if ($csvlinehead != '') {
						$csvlinehead .= ';';
						$csvlineperson .= ';';
						$csvlinedelivery .= ';';
					}
					$csvlinehead .= '"' . $fName . '"';
					$csvlineperson .= '"' . str_replace('\r\n', '|', $this->personInfo[$fName]) . '"';
					$csvlinedelivery .= '"' . $this->deliveryInfo[$fName] . '"';
				}

				// Generate shipping/payment information and delivery note
				$csvlineshipping = '"' . $this->basketExtra['shipping.']['title'] . '";"' .
					tx_ttproducts_view_div::priceFormat($this->calculatedArray['priceTax']['shipping']) . '";"' .
					tx_ttproducts_view_div::priceFormat($this->calculatedArray['priceNoTax']['shipping']) . '"';

				$csvlinepayment = '"' . $this->basketExtra['payment.']['title'] . '";"' .
					tx_ttproducts_view_div::priceFormat($this->calculatedArray['priceTax']['payment']) . '";"' .
					tx_ttproducts_view_div::priceFormat($this->calculatedArray['priceNoTax']['payment']) . '"';

				$csvlinedeliverynote = '"'.$this->deliveryInfo['note'].'"';

				// Build field list
				$csvfields = explode(',', $this->conf['CSVfields']);
				$csvfieldcount = count($csvfields);
				for ($a=0;$a<$csvfieldcount;$a++)
					$csvfields[$a] = trim($csvfields[$a]);

				// Write description header
				$csvdescr = '"uid";"count";"color";"size";"accessory";"gradings"';
				reset($csvfields);
				foreach($csvfields as $csvfield)
					$csvdescr .= ';"'.$csvfield.'"';
				if ($this->conf['CSVinOneLine'])
				{
					$csvdescr .= ';"deliverynote";"shipping method";"shipping_price";"shipping_no_tax";"payment method";"payment_price";"payment_no_tax"';
					$csvdescr .= ';'.$csvlinehead.';'.$csvlinehead;
				}
				$csvdescr .= '\n';
				fwrite($csvfile, $csvdescr);

				// Write ordered product list
				reset($this->itemArray);

				$infoWritten = false;
				// loop over all items in the basket indexed by page and itemnumber
				foreach ($this->itemArray as $pid=>$pidItem) {
					foreach ($pidItem as $itemnumber=>$actItemArray) {
						foreach ($actItemArray as $k1=>$actItem) {
							$variants = explode(';', $actItem['rec']['extVars']);
							$csvdata = '"'.intval($actItem['rec']['uid']).'";"'.
										intval($actItem['count']).'";"'.
										$variants[0].'";"'.
										$variants[1].'";"'.
										$variants[2]/100 .'";"'.
										$variants[3].'"';
							reset($csvfields);
							foreach($csvfields as $csvfield) {
								$csvdata .= ';"'.$actItem['rec'][$csvfield].'"';
							}
							if ($this->conf['CSVinOneLine'] && (!$infoWritten))	{
								$infoWritten = true;
								$csvdata .= ';'.$csvlinedeliverynote.';'.$csvlineshipping.';'.$csvlinepayment.';'.$csvlineperson.';'.$csvlinedelivery;
							}
							$csvdata .= '\n';
							fwrite($csvfile, $csvdata);
						}
					}
				}

				if (!$this->conf['CSVinOneLine']) {
					fwrite($csvfile, '\n');
					fwrite($csvfile, $csvlinehead . '\n');
					fwrite($csvfile, $csvlineperson . '\n');
					fwrite($csvfile, $csvlinedelivery . '\n');
					fwrite($csvfile, '\n');
					fwrite($csvfile, $csvlinedeliverynote. '\n');
					fwrite($csvfile, $csvlineshipping . '\n');
					fwrite($csvfile, $csvlinepayment . '\n');
				}

				fclose($csvfile);
			}
			else
				echo 'Warning: Cannot create CSV file \''.$csvfilepath.'\' for this order!';
		}

			// Sends order emails:
		$recipients = $this->conf['orderEmail_to'];
		$recipients.=','.$this->personInfo['email']; // former: deliveryInfo

		$emailArray = $this->category->getCategoryEmail($this->itemArray);
		//debug ($emailArray, '$emailArray', __LINE__, __FILE__);
		if (count ($emailArray)) {
			foreach ($emailArray as $key => $email) {
				$recipients.=','.$email['email'];
			}
		}
		$recipients=t3lib_div::trimExplode(',',$recipients,1);

		if (count($recipients))	{	// If any recipients, then compile and send the mail.
			$emailContent=trim(tx_ttproducts_basket_div::getBasket('###EMAIL_PLAINTEXT_TEMPLATE###'));
			if ($emailContent)	{		// If there is plain text content - which is required!!
				$parts = split(chr(10),$emailContent,2);		// First line is subject
				$subject=trim($parts[0]);
				$plain_message=trim($parts[1]);

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
