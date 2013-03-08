<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_order extends tx_ttproducts_table_base {
	var $basket;


	function init ($pibase, $functablename) {
		parent::init($pibase, $functablename);
		$this->basket = t3lib_div::getUserObj('&tx_ttproducts_basket');
	}


	// **************************
	// ORDER related functions
	// **************************

	/**
	 * Create a new order record
	 *
	 * This creates a new order-record on the page with pid PID_sys_products_orders. That page must exist!
	 * Should be called only internally by eg. $order->getBlankUid, that first checks if a blank record is already created.
	 *
	 * @return	[type]		...
	 */
	function create ()	{
		global $TSFE, $TYPO3_DB;

		$newId = 0;
		$pid = intval($this->conf['PID_sys_products_orders']);
		if (!$pid)	{
			$pid = intval($TSFE->id);
		}

		if ($TSFE->sys_page->getPage_noCheck($pid))	{
			$advanceUid = 0;
			if ($this->conf['advanceOrderNumberWithInteger'] || $this->conf['alwaysAdvanceOrderNumber'])	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', '', '', 'uid DESC', '1');
				list($prevUid) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$TYPO3_DB->sql_free_result($res);

				if ($this->conf['advanceOrderNumberWithInteger']) {
					$rndParts = explode(',',$this->conf['advanceOrderNumberWithInteger']);
					$randomValue = rand(intval($rndParts[0]), intval($rndParts[1]));
					$advanceUid = $prevUid +
						(
							class_exists('t3lib_utility_Math') ? t3lib_utility_Math::forceIntegerInRange($randomValue, 1) : t3lib_div::intInRange($randomValue, 1)
						);
				} else {
					$advanceUid = $prevUid + 1;
				}
			}

			$time = time();
			$insertFields = array (
				'pid' => intval($pid),
				'tstamp' => $time,
				'crdate' => $time,
				'deleted' => 0,
				'hidden' => 1
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
	 *
	 * @return	[type]		...
	 */
	function getBlankUid ()	{
		global $TSFE, $TYPO3_DB;;

	// an new orderUid has been created always because also payment systems can be used which do not accept a duplicate order id

		$orderUid = intval($this->basket->order['orderUid']);
		$res = $TYPO3_DB->exec_SELECTquery('uid', 'sys_products_orders', 'uid='.intval($orderUid).' AND hidden=1 AND NOT status');	// Checks if record exists, is marked hidden (all blank orders are hidden by default) and is not finished.
		if (!$TYPO3_DB->sql_num_rows($res) || $this->conf['alwaysAdvanceOrderNumber'])	{
			$orderUid = $this->create();
			$this->basket->order['orderUid'] = $orderUid;
			$this->basket->order['orderDate'] = time();
			$this->basket->order['orderTrackingNo'] = $this->getNumber($orderUid).'-'.strtolower(substr(md5(uniqid(time())),0,6));
			$TSFE->fe_user->setKey('ses','order',$this->basket->order);
		}
		$TYPO3_DB->sql_free_result($res);
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
	 *
	 * @param	[type]		$orderUid: ...
	 * @param	[type]		$tracking: ...
	 * @return	[type]		...
	 */
	function getRecord ($orderUid,$tracking='')	{
		global $TYPO3_DB;
		$res = $TYPO3_DB->exec_SELECTquery('*', 'sys_products_orders', ($tracking ? 'tracking_code='.$TYPO3_DB->fullQuoteStr($tracking, 'sys_products_orders') : 'uid='.intval($orderUid)).' AND NOT deleted');
		$rc = $TYPO3_DB->sql_fetch_assoc($res);
		$TYPO3_DB->sql_free_result($res);
		return $rc;
	} //getRecord


	/**
	 * This returns the order-number (opposed to the order_uid) for display in the shop, confirmation notes and so on.
	 * Basically this prefixes the .orderNumberPrefix, if any
	 *
	 * @param	[type]		$orderUid: ...
	 * @return	[type]		...
	 */
	function getNumber ($orderUid)	{
		$orderNumberPrefix = substr($this->conf['orderNumberPrefix'],0,15);
		if ($orderNumberPrefix[0] == '%')
			$orderNumberPrefix = date(substr($orderNumberPrefix, 1));
		return $orderNumberPrefix.$orderUid;
	} // getNumber


	/**
	 * Saves the order record and returns the result
	 *
	 * @param	[type]		$orderUid: ...
	 * @param	[type]		$deliveryInfo: ...
	 * @param	[type]		$feusers_uid: ...
	 * @param	[type]		$cardUid: ...
	 * @param	[type]		$accountUid: ...
	 * @param	[type]		$email_notify: ...
	 * @param	[type]		$payment: ...
	 * @param	[type]		$shipping: ...
	 * @param	[type]		$amount: ...
	 * @param	[type]		$orderConfirmationHTML: ...
	 * @param	[type]		$address: ...
	 * @return	[type]		...
	 */
	function putRecord (
		$orderUid,
		&$deliveryInfo,
		$feusers_uid,
		$cardUid,
		$accountUid,
		$email_notify,
		$payment,
		$shipping,
		$amount,
		&$orderConfirmationHTML,
		&$address,
		$status=0
	)	{
		global $TYPO3_DB;
		global $TSFE;

		if (!$feusers_uid && isset($TSFE->fe_user->user) && is_array($TSFE->fe_user->user) && $TSFE->fe_user->user['uid'])	{
			$feusers_uid = $TSFE->fe_user->user['uid'];
		}

			// Fix delivery address

		if ($deliveryInfo['date_of_birth'])	{
			$dateArray = t3lib_div::trimExplode ('-', $deliveryInfo['date_of_birth']);

			if (
				class_exists('t3lib_utility_Math') ?
					(
						t3lib_utility_Math::canBeInterpretedAsInteger($dateArray[0]) &&
						t3lib_utility_Math::canBeInterpretedAsInteger($dateArray[1]) &&
						t3lib_utility_Math::canBeInterpretedAsInteger($dateArray[2])
					) :
					(
						t3lib_div::testInt($dateArray[0]) &&
						t3lib_div::testInt($dateArray[1]) &&
						t3lib_div::testInt($dateArray[2])
					)
			) {
				$dateBirth = mktime(0,0,0,$dateArray[1],$dateArray[0],$dateArray[2]);
			}
		}

			// Saving order data
		$fieldsArray=array();
/* Added Els: introduce a field into sys_products_orders containing the uid of the fe_user */
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

		$fieldsArray['business_partner'] = $deliveryInfo['tt_products_business_partner'];
		$fieldsArray['organisation_form'] = $deliveryInfo['tt_products_organisation_form'];

			// can be changed after order is set.
		$fieldsArray['payment'] = $payment;
		$fieldsArray['shipping'] = $shipping;
		$fieldsArray['amount'] = $amount;
		$fieldsArray['desired_date'] = $deliveryInfo['desired_date'];
		$fieldsArray['status'] = $status;	// If 1, then this means, "Order confirmed on website, next step: confirm from shop that order is received"
		if ($status == 1)	{
			$fieldsArray['hidden'] = 0;
		}
		$fieldsArray['note'] = $deliveryInfo['note'];
		$fieldsArray['date_of_birth'] = $dateBirth;
		$fieldsArray['radio1'] = $deliveryInfo['radio1'];

		$giftServiceArticleArray = array();
		if (isset($this->basket->basketExt) && is_array($this->basket->basketExt)) {
			foreach ($this->basket->basketExt as $tmpUid => $tmpSubArr)	{
				if (is_array($tmpSubArr))	{
					foreach ($tmpSubArr as $tmpKey => $tmpSubSubArr)	{
						if (
							substr($tmpKey,-1) == '.' &&
							isset($tmpSubSubArr['additional']) &&
							is_array($tmpSubSubArr['additional'])
						)	{
								$variant = substr($tmpKey,0,-1);
								$row = $this->basket->get($tmpUid, $variant);
								if ($tmpSubSubArr['additional']['giftservice'] == 1)	{
									$giftServiceArticleArray[] = $row['title'];
								}
						}
					}
				}
			}
 		}

		$fieldsArray['giftservice'] = $deliveryInfo['giftservice'].'||'.implode(',',$giftServiceArticleArray);
		$fieldsArray['foundby'] = $deliveryInfo['foundby'];
		$fieldsArray['client_ip'] = t3lib_div::getIndpEnv('REMOTE_ADDR');
		$fieldsArray['cc_uid'] = $cardUid;
		$fieldsArray['ac_uid'] = $accountUid;
		$fieldsArray['giftcode'] = $this->basket->recs['tt_products']['giftcode'];

/* Added Els: update fe_user with amount of creditpoints and subtract creditpoints used in order*/
		$fieldsArrayFeUsers = array();
		$uid_voucher = ''; // define it here
		$cpArray = $TSFE->fe_user->getKey('ses','cp');

		if ($deliveryInfo['date_of_birth'])	{
			$fieldsArrayFeUsers['date_of_birth'] = $dateBirth;
		}

		$usedCreditpoints = 0;
		if (isset($_REQUEST['recs']) && is_array($_REQUEST['recs']) && isset($_REQUEST['recs']['tt_products']) && is_array($_REQUEST['recs']['tt_products'])) {
			$usedCreditpoints = floatval($_REQUEST['recs']['tt_products']['creditpoints']);
		}

		if ($status == 1 && $this->conf['creditpoints.'] && $usedCreditpoints) {

/* Added Els: update fe_user with amount of creditpoints (= exisitng amount - used_creditpoints - spended_creditpoints + saved_creditpoints */
//			$fieldsArrayFeUsers['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'] + ($creditpoints * $this->calculatedArray['priceTax']['total']) - $this->basket->recs['tt_products']['creditpoints'];

			$fieldsArrayFeUsers['tt_products_creditpoints'] =
				floatval($TSFE->fe_user->user['tt_products_creditpoints'] -
					$usedCreditpoints
					/*+ t3lib_div::_GP('creditpoints_saved')*/);

			if ($fieldsArrayFeUsers['tt_products_creditpoints'] < 0) {
				$fieldsArrayFeUsers['tt_products_creditpoints'] = 0;
			}
		}


/* Added Els: update fe_user with vouchercode */
		if ($status == 1 && $this->basket->recs['tt_products']['vouchercode'] != '') {
			// first check if vouchercode exist and is not their own vouchercode
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'fe_users', 'username=' . $TYPO3_DB->fullQuoteStr($this->basket->recs['tt_products']['vouchercode'], 'fe_users'));
			if ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
				$uid_voucher = $row['uid'];
			}

			$TYPO3_DB->sql_free_result($res);
			if (($uid_voucher != '') && ($address->infoArray['delivery']['feusers_uid'] > 0) && ($address->infoArray['delivery']['feusers_uid'] != $uid_voucher) ) {
				$fieldsArrayFeUsers['tt_products_vouchercode'] = $this->basket->recs['tt_products']['vouchercode'];
			}
		}

		if ($status == 1 && $address->infoArray['delivery']['feusers_uid']) {
	/* Added Els: update user from vouchercode with 5 credits */
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
			'version'		=>	$this->conf['version']
		));

			// Setting tstamp, deleted and tracking code
		$fieldsArray['tstamp'] = time();
		$fieldsArray['deleted'] = 0;
		$fieldsArray['tracking_code'] = $this->basket->order['orderTrackingNo'];
		$fieldsArray['agb'] = intval($address->infoArray['billing']['agb']);

		if ($status == 1 && $this->conf['creditpoints.'] && $this->basket->recs['tt_products']['creditpoints'] != '')	{
			$fieldsArray['creditpoints'] = $usedCreditpoints;
			$fieldsArray['creditpoints_spended'] = t3lib_div::_GP('creditpoints_spended');
			$fieldsArray['creditpoints_saved'] = t3lib_div::_GP('creditpoints_saved');
			$fieldsArray['creditpoints_gifts'] = $cpArray['gift']['amount'];
		}

			// Saving the order record
		$TYPO3_DB->exec_UPDATEquery(
			'sys_products_orders',
			'uid='.intval($orderUid),
			$fieldsArray
		);
	} //putRecord


	/**
	 * Creates M-M relations for the products with tt_products and maybe also the tt_products_articles table.
	 * Isn't really used yet, but later will be used to display stock-status by looking up how many items are
	 * already ordered.
	 *
	 * @param	[type]		$orderUid: ...
	 * @param	[type]		$itemArray: ...
	 * @return	[type]		...
	 */
	function createMM ($orderUid, &$itemArray)	{
		global $TYPO3_DB;

		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		if ($this->conf['useArticles'] != 2) {
			$productTable = $tablesObj->get('tt_products', FALSE);
			$productTablename = $productTable->getTablename();
		} else {
			$productTablename = '';
		}

		if ($this->conf['useArticles'] > 0) {
			$articleTable = $tablesObj->get('tt_products_articles', FALSE);
			$articleTablename = $articleTable->getTablename();
		} else {
			$articleTablename = '';
		}

			// First: delete any existing. Shouldn't be any
		$where='sys_products_orders_uid='.$orderUid;
		$res = $TYPO3_DB->exec_DELETEquery('sys_products_orders_mm_tt_products',$where);

		if (isset($itemArray) && is_array($itemArray)) {
			// loop over all items in the basket indexed by a sorting text
			foreach ($itemArray as $sort=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					$row = &$actItem['rec'];
					$pid = intval($row['pid']);
					if (!isset($this->basket->getPidListObj()->pageArray[$pid]))	{
						// product belongs to another basket
						continue;
					}

					$insertFields = array (
						'sys_products_orders_uid' => intval($orderUid),
						'sys_products_orders_qty' => intval($actItem['count']),
						'tt_products_uid' => intval($actItem['rec']['uid'])
					);

					if ($this->conf['useArticles'] == 1) {
						// get the article uid with these colors, sizes and gradings
						$row = $productTable->getArticleRow ($actItem['rec'], $theCode);
						if ($row) {
							$insertFields['tt_products_articles_uid'] = intval($row['uid']);
						}
					}
					$TYPO3_DB->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
				}
			}
		}
	}


	/**
	 * Sets the user order in dummy order record
	 *
	 * @param	integer		$orderID: uid of dummy record
	 * @return	void
	 */
	function setData ($orderUid, &$orderHTML, $status) {

		$basket = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');

		$voucherObj = $tablesObj->get('voucher');
		if ($status == 1)	{
			$voucherObj->delete();
		}

		// get credit card info
		$card = $tablesObj->get('sys_products_cards');
		$cardUid = $card->getUid();

		// get bank account info
		$account = $tablesObj->get('sys_products_accounts');
		$accountUid = $account->getUid();

		// get bank account info
		$account = $tablesObj->get('sys_products_accounts');
		$accountUid = $account->getUid();

		$address = t3lib_div::getUserObj('&tx_ttproducts_info_view');
		$rc = $this->putRecord(
			$orderUid,
			$address->infoArray['delivery'],
			$address->infoArray['billing']['feusers_uid'],
			$cardUid,
			$accountUid,
			$this->conf['email_notify_default'],	// Email notification is set here. Default email address is delivery email contact
			$basket->basketExtra['payment'][0].': '.$basket->basketExtra['payment.']['title'],
			$basket->basketExtra['shipping'][0].': '.$basket->basketExtra['shipping.']['title'],
			$basket->calculatedArray['priceTax']['total'],
			$orderHTML,
			$address,
			$status
		);

		$this->createMM($orderUid, $basket->itemArray);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_order.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_order.php']);
}


?>