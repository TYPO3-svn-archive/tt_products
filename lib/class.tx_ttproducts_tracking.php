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



require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_email_div.php');


class tx_ttproducts_tracking {
	var $cObj;
	var $conf;		  // original configuration
	private $statusCodeArray;


	/**
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */
	function init (&$cObj) {
		global $TSFE;

		$this->cObj = &$cObj;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;

		if ($this->conf['statusCodesSource'])	{

			switch ($this->conf['statusCodesSource'])	{
				case 'marker_locallang':
					$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
					$langArray = $markerObj->getLangArray();
					if (is_array($langArray))	{
						$statusMessage = 'tracking_status_message_';
						$len = strlen($statusMessage);
						foreach ($langArray as $k => $v)	{
							if (($pos = strpos($k, $statusMessage))===0)	{
								$rest = substr($k, $len);
								if (t3lib_div::testInt($rest))	{
									$statusCodeArray[$rest] = $v;
								}
							}
						}
					}
				break;
			}
		}
		if (isset($this->conf['statusCodes.']) && is_array($this->conf['statusCodes.']))	{
			foreach ($this->conf['statusCodes.'] as $k => $v)	{
				if (t3lib_div::testInt($k))	{
					$statusCodeArray[$k] = $v;
				}
			}
		}

		$this->setStatusCodeArray ($statusCodeArray);
	}


	function setStatusCodeArray (&$statusCodeArray)	{
		$this->statusCodeArray = $statusCodeArray;
	}


	function getStatusCodeArray ()	{
		return $this->statusCodeArray;
	}


	/* search the order status for paid and closed */
	function searchOrderStatus ($status_log,&$orderPaid, &$orderClosed)	{
		$orderPaid = FALSE;
		$orderClosed = FALSE;
		if (isset($status_log) && is_array($status_log)) {
			foreach($status_log as $key=>$val)  {
				if ($val['status'] == 13)	{// Numbers 13 means order has been payed
					$orderPaid = TRUE;
				}
				if ($val['status'] >= 100)  {// Numbers 13 means order has been payed
					$orderClosed = TRUE;
					break;
				}
			}
		}
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
	function getTrackingInformation ($orderRow, $templateCode, $trackingCode, $updateCode, &$orderRecord, $admin) {
		global $TSFE, $TYPO3_DB;

		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$orderObj = &$tablesObj->get('sys_products_orders');
		$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
		$pibaseObj = &t3lib_div::getUserObj('&tx_ttproducts_pi1_base');
		$statusCodeArray = $this->getStatusCodeArray();
		$allowUpdateFields = array('email', 'email_notify', 'status', 'status_log');
		$newData = $pibaseObj->piVars['data'];
		$bStatusValid = FALSE;

		if (isset($orderRow) && is_array($orderRow) && $orderRow['uid'])	{
			$TSFE->page['title'] = $orderRow['uid'].' ('.$orderRow['bill_no'].'): '.$orderRow['name'].'-'.$orderRow['zip'].'-'.$orderRow['city'].'-'.$orderRow['country'];

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

			if (is_array($orderRecord['status']) && isset($statusCodeArray) && is_array($statusCodeArray))	{
				$bStatusValid = TRUE;
				$status_log = unserialize($orderRow['status_log']);
				reset($orderRecord['status']);
				$update=0;
				$count=0;
				foreach($orderRecord['status'] as $val) {

					if (!isset($statusCodeArray[$val]))	{

						$bStatusValid = FALSE;
						break;
					}

					$status_log_element = array(
						'time' => time(),
						'info' => $statusCodeArray[$val],
						'status' => $val,
						'comment' => ($count == 0 ? $orderRecord['status_comment'].($newData != '' ? '|'.$newData : '') : ''), // comment is inserted only to the fist status
					);

					if ($admin && $newData)	{

						if ($val>=31 && $val<=32)	{// Numbers 31,32 are for storing of bill no. of external software
							$fieldsArray['bill_no'] = $newData;
							$update = 1;
						}
						if ($val==13)	{// Number 13 is that order has been paid. The date muss be entered in format dd-mm-yyyy
							$dateArray = t3lib_div::trimExplode('-', $newData);
							$date = mktime(0,0,0,$dateArray[1],$dateArray[0],$dateArray[2]);
							$fieldsArray['date_of_payment'] = $date;
							$fieldsArray['pay_mode'] = '1';
						}
						if ($val==20)	{// Number 20 is that items have been shipped. The date muss be entered in format dd-mm-yyyy
							$dateArray = t3lib_div::trimExplode('-', $newData);
							$date = mktime(0,0,0,$dateArray[1],$dateArray[0],$dateArray[2]);
							$fieldsArray['date_of_delivery'] = $date;
						}
					}
					if ($admin || ($val>=50 && $val<59))	{// Numbers 50-59 are usermessages.
						$recipient = $this->conf['orderEmail_to'];
						if ($orderRow['email'] && ($orderRow['email_notify']))  {
							$recipient .= ','.$orderRow['email'];
						}
						$templateMarker = 'TRACKING_EMAILNOTIFY_TEMPLATE';
						$feusersObj = &$tablesObj->get('fe_users', TRUE);
						tx_ttproducts_email_div::sendNotifyEmail(
							$this->cObj,
							$this->conf,
							$this->config,
							$feusersObj,
							$orderObj->getNumber($orderRow['uid']),
							$recipient,
							$status_log_element,
							$statusCodeArray,
							t3lib_div::_GP('tracking'),
							$orderRow,
							$templateCode,
							$templateMarker
						);
						$status_log[] = $status_log_element;
						$update=1;
					} else if ($val>=60 && $val<69) { //  60 -69 are special messages
						$templateMarker = 'TRACKING_EMAIL_GIFTNOTIFY_TEMPLATE';
						$query = 'ordernumber=\''.intval($orderRow['uid']).'\'';
						$giftRes = $TYPO3_DB->exec_SELECTquery('*', 'tt_products_gifts', $query);
						while ($giftRow = $TYPO3_DB->sql_fetch_assoc($giftRes)) {
							$recipient = $giftRow['deliveryemail'].','.$giftRow['personemail'];
							tx_ttproducts_email_div::sendGiftEmail(
								$this->cObj,
								$this->conf,
								$recipient,
								$orderRecord['status_comment'],
								$giftRow,
								$templateCode,
								$templateMarker,
								$this->conf['orderEmail_htmlmail']
							);
						}
						$status_log[] = $status_log_element;
						$update=1;
						$TYPO3_DB->sql_free_result($giftRes);
					}
					$count++;
				}
				if ($update)	{
					$fieldsArray['status_log'] = serialize($status_log);
					$fieldsArray['status'] = intval($status_log_element['status']);
				}
			}

			if (count($fieldsArray))	{		// If any items in the field array, save them
				if (!$admin)	{	// only these fields may be updated in an already stored order
					$fieldsArray = array_intersect_key($fieldsArray, array_flip($allowUpdateFields));
				}
				if (count($fieldsArray))	{
					$fieldsArray['tstamp'] = time();
					$TYPO3_DB->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderRow['uid']), $fieldsArray);
					$orderRow = $orderObj->getRecord($orderRow['uid']);
				}
			}
			$status_log = unserialize($orderRow['status_log']);
			$orderData = unserialize($orderRow['orderData']);
			if ($orderData === FALSE)	{
				$orderData = tx_div2007_alpha::unserialize_fh001($orderRow['orderData'],FALSE);
			}
		}

			// Getting the template stuff and initialize order data.
		$template=$this->cObj->getSubpart($templateCode,'###TRACKING_DISPLAY_INFO###');
		$this->searchOrderStatus($status_log, $orderPaid, $orderClosed);
		$globalMarkerArray = &$markerObj->getGlobalMarkerArray();

		// making status code 60 disappear if the order has not been payed yet
		if (!$orderPaid || $orderClosed) {
				// Fill marker arrays
			$markerArray = $globalMarkerArray;
			$subpartArray=Array();
			$subpartArray['###STATUS_CODE_60###']= '';

			$template = $this->cObj->substituteMarkerArrayCached($template,$markerArray,$subpartArray);
		}

			// Status:
		$STATUS_ITEM = $this->cObj->getSubpart($template,'###STATUS_ITEM###');
		$STATUS_ITEM_c='';
		if (is_array($status_log))  {
			reset($status_log);
			foreach($status_log as $k => $v)	{
				$markerArray=Array();
				$markerArray['###ORDER_STATUS_TIME###'] = $this->cObj->stdWrap($v['time'],$this->conf['statusDate_stdWrap.']);
				$markerArray['###ORDER_STATUS###'] = $v['status'];
				$info = $statusCodeArray[$v['status']];
				$markerArray['###ORDER_STATUS_INFO###'] = ($info ? $info : $v['info']);
				$markerArray['###ORDER_STATUS_COMMENT###'] = nl2br($v['comment']);

				$STATUS_ITEM_c .= $this->cObj->substituteMarkerArrayCached($STATUS_ITEM, $markerArray);
			}
		}

		$markerArray=$globalMarkerArray;
		$subpartArray=array();
		$wrappedSubpartArray=array();
		$subpartArray['###STATUS_ITEM###']=$STATUS_ITEM_c;

			// Display admin-interface if access.
		if (!$TSFE->beUserLogin)	{
			$subpartArray['###ADMIN_CONTROL###']='';
		} elseif ($admin) {
			$subpartArray['###ADMIN_CONTROL_DENY###']='';
			$wrappedSubpartArray['###ADMIN_CONTROL_OK###']='';
			$wrappedSubpartArray['###ADMIN_CONTROL###']='';
		} else {
			$subpartArray['###ADMIN_CONTROL_OK###']='';
			$wrappedSubpartArray['###ADMIN_CONTROL_DENY###']='';
			$wrappedSubpartArray['###ADMIN_CONTROL###']='';
		}
		$orderView = &$tablesObj->get('sys_products_orders', TRUE);
		$orderObj = &$orderView->getModelObj();
		$orderMarkerArray = $globalMarkerArray;
		$viewTagArray = array();
		$parentArray = array();
		$t = array();
		$t['orderFrameWork'] = $this->cObj->getSubpart($template,'###ORDER_ITEM###');
		$fieldsArray = $markerObj->getMarkerFields(
			$t['orderFrameWork'],
			$orderObj->getTableObj()->tableFieldArray,
			$orderObj->getTableObj()->requiredFieldArray,
			$markerFieldArray,
			$orderObj->marker,
			$viewTagArray,
			$parentArray
		);

		if ($orderRow)	{
			$orderView->getRowMarkerArray (
				$orderRow,
				'',
				$orderMarkerArray,
				$tmp=array(),
				$tmp=array(),
				$viewTagArray,
				'TRACKING'
			);
			$subpartArray['###ORDER_ITEM###'] = $this->cObj->substituteMarkerArrayCached($t['orderFrameWork'], $orderMarkerArray);
		} else {
			$subpartArray['###ORDER_ITEM###'] = '';
		}

		//
		if ($TSFE->beUserLogin) {
				// Status admin:
			if (isset($statusCodeArray) && is_array($statusCodeArray))	{
				foreach($statusCodeArray as $k => $v)  {
					if ($k!=1)  {
						$markerArray['###STATUS_OPTIONS###'] .= '<option value="' . $k . '">' . htmlspecialchars($k . ': ' . $v) . '</option>';
					}
				}
			}
			$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');

				// Get unprocessed orders.
			$res = $TYPO3_DB->exec_SELECTquery('uid,name,tracking_code,amount,status,status_log,bill_no', 'sys_products_orders', 'NOT deleted AND status!=0 AND status<100', '', 'crdate');
			while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
				$tmpStatuslog = unserialize($row['status_log']);
				$tmpOrderData = unserialize($row['orderData']);
				$classPrefix = str_replace('_','-',$pibaseObj->prefixId);
				$this->searchOrderStatus($tmpStatuslog, $tmpPaid, $tmpClosed);
				$class = ($tmpPaid ? $classPrefix.'-paid' : '');
				$class = ($class ? $class.' ' : '' ) . ($tmpClosed ? $classPrefix.'-closed' : '');
				$class = ($class ? ' class="'.$class.'"' : '');
				$markerArray['###OTHER_ORDERS_OPTIONS###'] .=
					'<option ' . $class . ' value="' . $row['tracking_code'] . '"' . ($row['uid'] == $orderRow['uid'] ? 'selected="selected"' : '') . '>'.
						htmlspecialchars($row['uid'].' ('.$row['bill_no'].'): '.
							$row['name'] . ' (' . $priceViewObj->priceFormat($row['amount']) . ' ' . $this->conf['currencySymbol'] . ') /' . $row['status']
						) .
					'</option>';
			}
			$TYPO3_DB->sql_free_result($res);
		}
			// Final things
		$markerArray['###ORDER_HTML_OUTPUT###'] = $orderData['html_output'];	// The save order-information in HTML-format
		$markerArray['###FIELD_EMAIL_NOTIFY###'] = $orderRow['email_notify'] ? ' checked' : '';
		$markerArray['###FIELD_EMAIL###'] = $orderRow['email'];
		$markerArray['###ORDER_UID###'] = $orderObj->getNumber($orderRow['uid']);
		$markerArray['###ORDER_DATE###'] = $this->cObj->stdWrap($orderRow['crdate'],$this->conf['orderDate_stdWrap.']);
		$markerArray['###TRACKING_NUMBER###'] =  $trackingCode;
		$markerArray['###UPDATE_CODE###'] = $updateCode;
		$markerArray['###TRACKING_DATA_NAME###'] = $pibaseObj->prefixId.'[data]';
		$markerArray['###TRACKING_DATA_VALUE###'] = ($bStatusValid ? '' : $newData);
		$markerArray['###TRACKING_STATUS_COMMENT_NAME###'] = 'orderRecord[status_comment]';
		$markerArray['###TRACKING_STATUS_COMMENT_VALUE###'] = ($bStatusValid ? '' : $orderRecord['status_comment']);

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['tracking'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['tracking'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getTrackingInformation')) {
					$hookObj->getTrackingInformation ($this, $orderRow, $templateCode, $trackingCode, $updateCode, $orderRecord, $admin, $template, $markerArray,$subpartArray);
				}
			}
		}
		$content = $this->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		return $content;
	} // getTrackingInformation
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_tracking.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_tracking.php']);
}


?>
