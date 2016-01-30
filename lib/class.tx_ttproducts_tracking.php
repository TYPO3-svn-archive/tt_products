<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2010 Franz Holzinger <franz@ttproducts.de>
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
 * tracking functions
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



class tx_ttproducts_tracking {

	var $cObj;
	var $conf;		  // original configuration

	/**
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param	string		$fieldname is the field in the table you want to create a JavaScript for
	 * @return	void
	 */
	function init(&$cObj) {
		global $TSFE;

		$this->cObj = $cObj;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

		$this->conf = &$cnf->conf;
	}



	/*
		Tracking information display and maintenance.

		status-values are
			0:  Blank order
		1-1 Incoming orders
			1:  Order confirmed at website
		2-49: Useable by the shop admin
			2 = Order is received and accepted by store
			10 = Shop is awaiting goods from third-party
			11 = Shop is awaiting customer payment
			12 = Shop is awaiting material from customer
			13 = Order has been payed
			20 = Goods shipped to customer
			21 = Gift certificates shipped to customer
			30 = Other message from store
			...
		50-99:  Useable by the customer
		50-59: General user messages, may be updated by the ordinary users.
			50 = Customer request for cancelling
			51 = Message from customer to shop
		60-69:  Special user messages by the customer
			60 = Send gift certificate message to receiver

		100-299:  Order finalized.
			100 = Order shipped and closed
			101 = Order closed
			200 = Order cancelled

		All status values can be altered only if you're logged in as a BE-user and if you know the correct code (setup as .update_code in TypoScript config)
	*/

	function getTrackingInformation($orderRow, $templateCode, $trackingCode, $updateCode, &$orderRecord, $admin) {
		global $TSFE, $TYPO3_DB;

		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$orderObj = $tablesObj->get('sys_products_orders');

		if ($orderRow['uid'])	{
				// Initialize update of status...
			$fieldsArray = array();
			if (isset($orderRecord['email_notify']))	{
				$fieldsArray['email_notify'] = $orderRecord['email_notify'];
				$orderRow['email_notify'] = $orderRecord['email_notify'];
			}
			if (isset($orderRecord['email']))	{
				$fieldsArray['email'] = $orderRecord['email'];
				$orderRow['email'] = $orderRecord['email'];
			}

			if (is_array($orderRecord['status']))	{
				$status_log = unserialize($orderRow['status_log']);
				reset($orderRecord['status']);
				$update=0;
				foreach($orderRecord['status'] as $val) {
					$status_log_element = array(
						'time' => time(),
						'info' => $this->conf['statusCodes.'][$val],
						'status' => $val,
						'comment' => $orderRecord['status_comment']
					);

					if ($admin || ($val>=50 && $val<59))	{// Numbers 50-59 are usermessages.
						$recipient = $this->conf['orderEmail_to'];
						if ($orderRow['email'] && ($orderRow['email_notify']))  {
							$recipient .= ','.$orderRow['email'];
						}
						$templateMarker = 'TRACKING_EMAILNOTIFY_TEMPLATE';
						$feusersObj = $tablesObj->get('fe_users', TRUE);
						tx_ttproducts_email_div::sendNotifyEmail($this->cObj, $this->conf, $feusersObj, $orderObj->getNumber($orderRow['uid']), $recipient, $status_log_element, t3lib_div::_GP('tracking'), $orderRow, $templateCode, $templateMarker);
						$status_log[] = $status_log_element;
						$update=1;
					} else if ($val>=60 && $val<69) { //  60 -69 are special messages
						$templateMarker = 'TRACKING_EMAIL_GIFTNOTIFY_TEMPLATE';
						$query = 'ordernumber=\''.intval($orderRow['uid']).'\'';
						$giftRes = $TYPO3_DB->exec_SELECTquery('*', 'tt_products_gifts', $query);
						while ($giftRow = $TYPO3_DB->sql_fetch_assoc($giftRes)) {
							$recipient = $giftRow['deliveryemail'].','.$giftRow['personemail'];
							tx_ttproducts_email_div::sendGiftEmail($this->cObj, $this->conf, $recipient, $orderRecord['status_comment'], $giftRow, $templateCode, $templateMarker, $this->conf['orderEmail_htmlmail']);
						}
						$status_log[] = $status_log_element;
						$update=1;
						$TYPO3_DB->sql_free_result($giftRes);
					}
				}
				if ($update)	{
					$fieldsArray['status_log'] = serialize($status_log);
					$fieldsArray['status'] = intval($status_log_element['status']);
					if ($fieldsArray['status'] >= 100)  {

							// Deletes any M-M relations between the tt_products table and the order.
							// In the future this should maybe also automatically count down the stock number of the product records. Else it doesn't make sense.
						$TYPO3_DB->exec_DELETEquery('sys_products_orders_mm_tt_products', 'sys_products_orders_uid=' . intval($orderRow['uid']));
					}
				}
			}

			if (count($fieldsArray))	{		// If any items in the field array, save them
				$fieldsArray['tstamp'] = time();
				$TYPO3_DB->exec_UPDATEquery('sys_products_orders', 'uid=' . intval($orderRow['uid']), $fieldsArray);
				$orderRow = $orderObj->getRecord($orderRow['uid']);
			}
		}

			// Getting the template stuff and initialize order data.
		$content=$this->cObj->getSubpart($templateCode, '###TRACKING_DISPLAY_INFO###');
		$status_log = unserialize($orderRow['status_log']);
		$orderData = unserialize($orderRow['orderData']);
		$orderPayed = false;
		$orderClosed = false;
		if (is_array($status_log)) {
			foreach($status_log as $key=>$val)  {
				if ($val['status'] == 13)	{// Numbers 13 means order has been payed
					$orderPayed = true;
				}
				if ($val['status'] >= 100)  {// Numbers 13 means order has been payed
					$orderClosed = true;
					break;
				}
			}
		}

		// making status code 60 disappear if the order has not been payed yet
		if (!$orderPayed || $orderClosed) {
				// Fill marker arrays
			$markerArray=Array();
			$subpartArray=Array();
			$subpartArray['###STATUS_CODE_60###']= '';

			$content = $this->cObj->substituteMarkerArrayCached($content, $markerArray, $subpartArray);
		}

			// Status:
		$STATUS_ITEM = $this->cObj->getSubpart($content, '###STATUS_ITEM###');
		$STATUS_ITEM_c = '';
		if (is_array($status_log))  {
			reset($status_log);

			while(list($k,$v)=each($status_log))	{
				$markerArray=Array();
				$markerArray['###ORDER_STATUS_TIME###'] = $this->cObj->stdWrap($v['time'], $this->conf['statusDate_stdWrap.']);
				$markerArray['###ORDER_STATUS###'] = $v['status'];
				$markerArray['###ORDER_STATUS_INFO###'] = $v['info'];
				$markerArray['###ORDER_STATUS_COMMENT###'] = nl2br($v['comment']);

				$STATUS_ITEM_c.=$this->cObj->substituteMarkerArrayCached($STATUS_ITEM, $markerArray);
			}
		}

		$subpartArray=array();
		$subpartArray['###STATUS_ITEM###']=$STATUS_ITEM_c;

		$markerArray=Array();

			// Display admin-interface if access.
		if (!$TSFE->beUserLogin)	{
			$subpartArray['###ADMIN_CONTROL###']='';
		} elseif ($admin) {
			$subpartArray['###ADMIN_CONTROL_DENY###']='';
		} else {
			$subpartArray['###ADMIN_CONTROL_OK###']='';
		}
		if ($TSFE->beUserLogin) {
				// Status admin:
			if (is_array($this->conf['statusCodes.']))	{
				reset($this->conf['statusCodes.']);
				while(list($k,$v)=each($this->conf['statusCodes.']))  {
					if ($k!=1)  {
						$markerArray['###STATUS_OPTIONS###'].='<option value="' . $k . '">' . htmlspecialchars($k . ': ' . $v) . '</option>';
					}
				}
			}
			$priceViewObj = t3lib_div::getUserObj('&tx_ttproducts_field_price_view');

				// Get unprocessed orders.
			$res = $TYPO3_DB->exec_SELECTquery('uid,name,tracking_code,amount,status', 'sys_products_orders', 'NOT deleted AND status!=0 AND status<100', '', 'crdate');
			while($row = $TYPO3_DB->sql_fetch_assoc($res))	{
				$markerArray['###OTHER_ORDERS_OPTIONS###'] .=
					'<option value="'.$row['tracking_code'].'">'.
					htmlspecialchars($orderObj->getNumber($row['uid']).': '.
					$row['name'].' ('.$priceViewObj->priceFormat($row['amount']) . ' ' . $this->conf['currencySymbol'] . ') /' . $row['status']) . '</option>';
			}
			$TYPO3_DB->sql_free_result($res);
		}

			// Final things
		$markerArray['###ORDER_HTML_OUTPUT###'] = $orderData['html_output'];	// The save order-information in HTML-format
		$markerArray['###FIELD_EMAIL_NOTIFY###'] = $orderRow['email_notify'] ? ' checked' : '';
		$markerArray['###FIELD_EMAIL###'] = $orderRow['email'];
		$markerArray['###ORDER_UID###'] = $orderObj->getNumber($orderRow['uid']);
		$markerArray['###ORDER_DATE###'] = $this->cObj->stdWrap($orderRow['crdate'], $this->conf['orderDate_stdWrap.']);
		$markerArray['###TRACKING_NUMBER###'] =  $trackingCode;
		$markerArray['###UPDATE_CODE###'] = $updateCode;
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['tracking'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['tracking'] as $classRef) {
				$hookObj= t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getTrackingInformation')) {
					$hookObj->getTrackingInformation ($this, $orderRow, $templateCode, $trackingCode, $updateCode, $orderRecord, $admin, $content, $markerArray,$subpartArray);
				}
			}
		}

		$content= $this->cObj->substituteMarkerArrayCached($content, $markerArray, $subpartArray);
		return $content;
	} // getTrackingInformation

}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_tracking.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_tracking.php']);
}


?>