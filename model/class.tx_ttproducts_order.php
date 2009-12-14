<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Franz Holzinger <franz@ttproducts.de>
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
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

global $TYPO3_CONF_VARS;


class tx_ttproducts_order {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $basket;
	var $tt_products; // element of class tx_table_db
	var $tt_products_articles; // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db
	var $useArticles;


	function init (&$pibase, &$cnf, &$tt_products, &$tt_products_articles, &$tt_products_cat, &$basket, $useArticles) {
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->basket = &$basket;

		$this->tt_products = &$tt_products;
		$this->tt_products_articles = &$tt_products_articles;
		$this->tt_products_cat = &$tt_products_cat;
		$this->useArticles = $useArticles;
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
	function create ()	{
		global $TSFE, $TYPO3_DB;

		$newId = 0;
		$pid = intval($this->conf['PID_sys_products_orders']);
		if (!$pid)	$pid = intval($TSFE->id);

		if ($TSFE->sys_page->getPage_noCheck($pid))	{
			$advanceUid = 0;
			if ($this->conf['advanceOrderNumberWithInteger'] || $this->conf['alwaysAdvanceOrderNumber'])	{
				$res = $TYPO3_DB->exec_SELECTquery('uid', 'sys_products_orders', '', '', 'uid DESC', '1');
				list($prevUid) = $TYPO3_DB->sql_fetch_row($res);

				if ($this->conf['advanceOrderNumberWithInteger']) {
					$rndParts = explode(',',$this->conf['advanceOrderNumberWithInteger']);
					$advanceUid = $prevUid + t3lib_div::intInRange(rand(intval($rndParts[0]),intval($rndParts[1])),1);
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
	function getBlankUid ()	{
		global $TSFE;

	// an new orderUid has been created always because also payment systems can be used which do not accept a duplicate order id
		$orderUid = intval($this->basket->order['orderUid']);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', 'uid='.intval($orderUid).' AND deleted AND NOT status');	// Checks if record exists, is marked deleted (all blank orders are deleted by default) and is not finished.
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res) || $this->conf['alwaysAdvanceOrderNumber'])	{
			$orderUid = $this->create();
			$this->basket->order['orderUid'] = $orderUid;
			$this->basket->order['orderDate'] = time();
			$this->basket->order['orderTrackingNo'] = $this->getNumber($orderUid).'-'.strtolower(substr(md5(uniqid(time())),0,6));
			$TSFE->fe_user->setKey('ses','order',$this->basket->order);
		}
		return $orderUid;
	} // getBlankUid


	function getUid ()	{
		$rc = $this->basket->order['orderUid'];
		return $rc;
	}


	function clearUid ()	{
		global $TSFE;

		$this->basket->order['orderUid'] = '';
		$TSFE->fe_user->setKey('ses','order',array());
	}


	/**
	 * Returns the order record if $orderUid.
	 * If $tracking is set, then the order with the tracking number is fetched instead.
	 */
	function getRecord ($orderUid,$tracking='')	{
		global $TYPO3_DB;
		$res = $TYPO3_DB->exec_SELECTquery('*', 'sys_products_orders', ($tracking ? 'tracking_code='.$TYPO3_DB->fullQuoteStr($tracking, 'sys_products_orders') : 'uid='.intval($orderUid)).' AND NOT deleted');
		return $TYPO3_DB->sql_fetch_assoc($res);
	} //getRecord


	/**
	 * This returns the order-number (opposed to the order_uid) for display in the shop, confirmation notes and so on.
	 * Basically this prefixes the .orderNumberPrefix, if any
	 */
	function getNumber ($orderUid)	{
		$orderNumberPrefix = substr($this->conf['orderNumberPrefix'],0,15);
		if ($orderNumberPrefix[0] == '%')
			$orderNumberPrefix = date(substr($orderNumberPrefix, 1));
		return $orderNumberPrefix . $orderUid;
	} // getNumber


	/**
	 * Saves the order record and returns the result
	 *
	 */
	function putRecord ($orderUid, &$deliveryInfo, $feusers_uid, $cardUid, $accountUid,
		$email_notify, $payment, $shipping, $amount, &$orderConfirmationHTML, &$address)	{
		global $TYPO3_DB;
		global $TSFE;

		if ($this->conf['debug'])	{
			$debugOutput = $TYPO3_DB->debugOutput;
			$TYPO3_DB->debugOutput = 1;
		}

			// Fix delivery address
		$address->mapPersonIntoDelivery();	// This maps the billing address into the blank fields of the delivery address

			// Saving order data
		$fieldsArray=array();
		$fieldsArray['feusers_uid'] = $feusers_uid;
		$fieldsArray['first_name'] = $deliveryInfo['first_name'];
		$fieldsArray['last_name'] = $deliveryInfo['last_name'];
		$fieldsArray['name'] = ($deliveryInfo['name'] ? $deliveryInfo['name'] : $deliveryInfo['last_name'].' '.$deliveryInfo['first_name']);
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
		$fieldsArray['ac_uid'] = $accountUid;

		$fieldsArrayFeUsers = array();
		$uid_voucher = ''; // define it here
		$cpArray = $TSFE->fe_user->getKey('ses','cp');

		if ($this->conf['creditpoints.']) {
			$fieldsArrayFeUsers['tt_products_creditpoints'] =
				floatval($TSFE->fe_user->user['tt_products_creditpoints'] -
					$this->basket->recs['tt_products']['creditpoints'] +
					t3lib_div::_GP('creditpoints_saved'));
		}

		if ($address->infoArray['billing']['date_of_birth'])	{

			$dateArray = t3lib_div::trimExplode ('-', $address->infoArray['billing']['date_of_birth']);
			if (t3lib_extMgm::isLoaded('sr_feuser_register')) {
				require_once(PATH_BE_srfeuserregister.'pi1/class.tx_srfeuserregister_pi1_adodb_time.php');

				// prepare for handling dates before 1970
				$adodbTime = &t3lib_div::getUserObj('&tx_srfeuserregister_pi1_adodb_time');
				$dateBirth = $adodbTime->adodb_mktime(0,0,0,$dateArray[1],$dateArray[0],$dateArray[2]);
			} else {
				$dateBirth = mktime(0,0,0,$dateArray[1],$dateArray[0],$dateArray[2]);
			}

			$fieldsArrayFeUsers['date_of_birth'] = $dateBirth;
		}

		if ($this->basket->recs['tt_products']['vouchercode'] != '') {
			// first check if vouchercode exist and is not their own vouchercode
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'fe_users', 'username="'.$this->basket->recs['tt_products']['vouchercode'].'"');
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$uid_voucher = $row['uid'];
			}
			if (($uid_voucher != '') && ($address->infoArray['delivery']['feusers_uid'] > 0) && ($address->infoArray['delivery']['feusers_uid'] != $uid_voucher) ) {
				$fieldsArrayFeUsers['tt_products_vouchercode'] = $this->basket->recs['tt_products']['vouchercode'];
			}
		}

		if ($address->infoArray['delivery']['feusers_uid']) {
			tx_ttproducts_creditpoints_div::addCreditPoints($this->basket->recs['tt_products']['vouchercode'], $this->conf['voucher.']['price']);
		}

		if ($TSFE->fe_user->user['uid'] && count($fieldsArrayFeUsers))	{
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.intval($TSFE->fe_user->user['uid']), $fieldsArrayFeUsers);
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
			'html_output'		=>	$orderConfirmationHTML,
			'delivery'		=>	$address->infoArray['delivery'],
			'billing'		=>	$address->infoArray['billing'],
			'itemArray'		=>	$itemArray,
			'calculatedArray'	=>	$this->basket->calculatedArray,
			'version'		=> 	$this->pibase->version
		));

			// Setting tstamp, deleted and tracking code
		$fieldsArray['tstamp'] = time();
		$fieldsArray['deleted'] = 0;
		$fieldsArray['tracking_code'] = $this->basket->order['orderTrackingNo'];
		$fieldsArray['agb'] = intval($address->infoArray['billing']['agb']);

		if ($this->conf['creditpoints.'] && $this->basket->recs['tt_products']['creditpoints'] != '')	{

			$fieldsArray['creditpoints'] = $this->basket->recs['tt_products']['creditpoints'];
			$fieldsArray['creditpoints_spended'] = t3lib_div::_GP('creditpoints_spended');
			$fieldsArray['creditpoints_saved'] = t3lib_div::_GP('creditpoints_saved');
			$fieldsArray['creditpoints_gifts'] = $cpArray['gift']['amount'];
		}

			// Saving the order record
		$TYPO3_DB->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderUid), $fieldsArray);

		if ($this->conf['debug'])	{
			$TYPO3_DB->debugOutput = $debugOutput;
		}
	} //putRecord


	/**
	 * Creates M-M relations for the products with tt_products and maybe also the tt_products_articles table.
	 * Isn't really used yet, but later will be used to display stock-status by looking up how many items are
	 * already ordered.
	 *
	 */
	function createMM ($orderUid, &$itemArray)	{
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
