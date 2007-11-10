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
 * Part of the tt_products (Shop System) extension.
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

global $TYPO3_CONF_VARS;

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_email_div.php');

class tx_ttproducts_order {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $basket;
	var $tt_products; // element of class tx_table_db
	var $tt_products_articles; // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db
	var $useArticles;
	var $alwaysInStock;


	function init(&$pibase, &$cnf, &$tt_products, &$tt_products_articles, &$tt_products_cat, &$basket, $useArticles) {
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->basket = &$basket;

		$this->tt_products = &$tt_products;
		$this->tt_products_articles = &$tt_products_articles;
		$this->tt_products_cat = &$tt_products_cat;
		$this->useArticles = $useArticles;

		if (intval($this->conf['alwaysInStock'])) {
			$this->alwaysInStock = 1;
		} else {
			$this->alwaysInStock = 0;
		}
	}


	// **************************
	// ORDER related functions
	// **************************

	/**
	 * Create a new order record
	 *
	 * This creates a new order-record on the page with pid PID_sys_products_orders. That page must exist!
	 * Should be called only internally by eg. $order->getBlankUid, that first checks if a blank record is already created.
	 */
	function create()	{
		global $TSFE, $TYPO3_DB;

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
			$insertFields = array (
				'pid' => intval($pid),
				'tstamp' => $time,
				'crdate' => $time,
				'deleted' => 1
			);
			if ($advanceUid > 0)	{
				$insertFields['uid'] = intval($advanceUid);
			}

			$TYPO3_DB->exec_INSERTquery('sys_products_orders', $insertFields);
			$newId = $TYPO3_DB->sql_insert_id();
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
		global $TYPO3_DB;
		$res = $TYPO3_DB->exec_SELECTquery('*', 'sys_products_orders', ($tracking ? 'tracking_code='.$TYPO3_DB->fullQuoteStr($tracking, 'sys_products_orders') : 'uid='.intval($orderUid)).' AND NOT deleted');
		return $TYPO3_DB->sql_fetch_assoc($res);
	} //getRecord


	/**
	 * This returns the order-number (opposed to the order_uid) for display in the shop, confirmation notes and so on.
	 * Basically this prefixes the .orderNumberPrefix, if any
	 */
	function getNumber($orderUid)	{
		$orderNumberPrefix = substr($this->conf['orderNumberPrefix'],0,15);
		if ($orderNumberPrefix[0]=='%')
			$orderNumberPrefix = date(substr($orderNumberPrefix, 1));
		return $orderNumberPrefix.$orderUid;
	} // getNumber


	/**
	 * Saves the order record and returns the result
	 *
	 */
	function putRecord($orderUid, &$deliveryInfo, $feusers_uid, $cardUid,
		$email_notify, $payment, $shipping, $amount, &$orderConfirmationHTML, &$address)	{
		global $TYPO3_DB;
		global $TSFE;

			// Fix delivery address
		$address->mapPersonIntoDelivery();	// This maps the billing address into the blank fields of the delivery address
//		$mainMarkerArray['###EXTERNAL_COBJECT###'] = $this->externalCObject.'';

			// Saving order data
		$fieldsArray=array();
/* Added Els: introduce a field into sys_products_orders containing the uid of the fe_user */
		$fieldsArray['feusers_uid'] = $feusers_uid;
		$fieldsArray['name'] = $deliveryInfo['name'];
		$fieldsArray['first_name'] = $deliveryInfo['first_name'];
		$fieldsArray['last_name'] = $deliveryInfo['last_name'];
		$fieldsArray['salutation'] = $deliveryInfo['salutation'];
		$fieldsArray['address'] = $deliveryInfo['address'];
		$fieldsArray['zip'] = $deliveryInfo['zip'];
		$fieldsArray['city'] = $deliveryInfo['city'];
		$fieldsArray['country'] = $deliveryInfo['country'];
		$fieldsArray['telephone'] = $deliveryInfo['telephone'];
		$fieldsArray['fax'] = $deliveryInfo['fax'];
		$fieldsArray['email'] = $deliveryInfo['email'];
		$fieldsArray['email_notify'] = $email_notify;

			// can be changed after order is set.
		$fieldsArray['payment'] = $payment;
		$fieldsArray['shipping'] = $shipping;
		$fieldsArray['amount'] = $amount;
		$fieldsArray['desired_date'] = $deliveryInfo['desired_date'];
		$fieldsArray['status'] = 1;	// This means, "Order confirmed on website, next step: confirm from shop that order is received"
		$fieldsArray['note'] = $deliveryInfo['note'];
		$fieldsArray['client_ip'] = t3lib_div::getIndpEnv('REMOTE_ADDR');
		$fieldsArray['cc_uid'] = $cardUid;

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
/* Added Els: update fe_user with amount of creditpoints (= exisitng amount - used_creditpoints - spended_creditpoints + saved_creditpoints */
//			$fieldsArrayFeUsers['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'] + ($creditpoints * $this->calculatedArray['priceTax']['total']) - $this->basket->recs['tt_products']['creditpoints'];
			$fieldsArrayFeUsers['tt_products_creditpoints'] = 
				floatval($TSFE->fe_user->user['tt_products_creditpoints'] - 
					$this->basket->recs['tt_products']['creditpoints'] -
					t3lib_div::_GP('creditpoints_spended') + 
					t3lib_div::_GP('creditpoints_saved'));
		}

/* Added Els: update fe_user with vouchercode */
		if ($this->basket->recs['tt_products']['vouchercode'] != '') {
			// first check if vouchercode exist and is not their own vouchercode
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'fe_users', 'username="'.$this->basket->recs['tt_products']['vouchercode'].'"');
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$uid_voucher = $row['uid'];
			}
			if (($uid_voucher != '') && ($address->infoArray['delivery']['feusers_uid'] != $uid_voucher) ) {
				$fieldsArrayFeUsers['tt_products_vouchercode'] = $this->basket->recs['tt_products']['vouchercode'];
			}
		}

		if ($address->infoArray['delivery']['feusers_uid']) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.intval($address->infoArray['delivery']['feusers_uid']), $fieldsArrayFeUsers);
	/* Added Els: update user from vouchercode with 5 credits */
			tx_ttproducts_creditpoints_div::addCreditPoints($this->basket->recs['tt_products']['vouchercode'], 5);
		}

				// Default status_log entry
		$status_log=array();
		$status_log[] = array(
			'time' => time(),
			'info' => $this->conf['statusCodes.'][$fieldsArray['status']],
			'status' => $fieldsArray['status'],
			'comment' => $address->infoArray['delivery']['note']
		);
		$fieldsArray['status_log']=serialize($status_log);
		$itemArray = array();
		$itemArray [$this->basket->viewTable->name] = $this->basket->itemArray;

			// Order Data serialized
		$fieldsArray['orderData'] = serialize(array(
				'html_output' 			=>	$orderConfirmationHTML,
				'delivery' 				=>	$address->infoArray['delivery'],
				'billing' 				=>	$address->infoArray['billing'],
				'itemArray'				=>	$itemArray,
				'calculatedArray'		=>	$this->basket->calculatedArray,
				'version'				=>  $this->pibase->version
		));

			// Setting tstamp, deleted and tracking code
		$fieldsArray['tstamp'] = time();
		$fieldsArray['deleted'] = 0;
		$fieldsArray['tracking_code'] = $this->basket->recs['tt_products']['orderTrackingNo'];
		$fieldsArray['agb'] = $address->infoArray['billing']['agb'];
		$fieldsArray['creditpoints'] = $this->basket->recs['tt_products']['creditpoints'];
		$fieldsArray['creditpoints_spended'] = t3lib_div::_GP('creditpoints_spended');
		$fieldsArray['creditpoints_saved'] = t3lib_div::_GP('creditpoints_saved');
		$fieldsArray['creditpoints_gifts'] = t3lib_div::_GP('creditpoints_gifts');

			// Saving the order record
		$TYPO3_DB->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderUid), $fieldsArray);
	} //putRecord



	/**
	 * Creates M-M relations for the products with tt_products and maybe also the tt_products_articles table. 
	 * Isn't really used yet, but later will be used to display stock-status by looking up how many items are 
	 * already ordered.
	 *
	 */
	function createMM($orderUid, &$itemArray)	{
		global $TYPO3_DB;

			// First: delete any existing. Shouldn't be any
		$where='sys_products_orders_uid='.$orderUid;
		$res = $TYPO3_DB->exec_DELETEquery('sys_products_orders_mm_tt_products',$where);

			// Second: Insert a new relation for each ordered item
		reset($itemArray);

		// loop over all items in the basket indexed by a sorting text
		foreach ($itemArray as $sort=>$actItemArray) {
			foreach ($actItemArray as $k1=>$actItem) {
				$row = &$actItem['rec'];
				$pid = intval($row['pid']);
				if (!isset($this->basket->page->pageArray[$pid]))	{
					// product belongs to another basket	
					continue;
				}

				$insertFields = array (
					'sys_products_orders_uid' => intval($orderUid),
					'sys_products_orders_qty' => intval($actItem['count']),
					'tt_products_uid' => intval($actItem['rec']['uid'])
				);

				if ($this->useArticles == 1) {
					// get the article uid with these colors, sizes and gradings
					$row = $this->tt_products->getArticleRow ($actItem['rec']);
					if ($row) {
						$insertFields['tt_products_articles_uid'] = intval($row['uid']);
					}
				}
				$TYPO3_DB->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
			}
		}
	}


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_order.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_order.php']);
}


?>
