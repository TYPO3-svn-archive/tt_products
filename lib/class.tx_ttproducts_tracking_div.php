<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2005 Franz Holzinger <kontakt@fholzinger.com>
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
 * tracking functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_order_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_order_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_page_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_view_div.php');


class tx_ttproducts_tracking_div {


	/**
	 * Order tracking
	 *
	 *
	 * @param	integer		Code: TRACKING, BILL or DELIVERY
	 * @return	void
	 * @see enableFields()
	 */

	function products_tracking($theCode)	{
		global $TSFE;

		if (strcmp($theCode, 'TRACKING')!=0) { // bill and delivery tracking need more data
			tx_ttproducts_view_div::mapPersonIntoToDelivery();	// This maps the billing address into the blank fields of the delivery address
			tx_ttproducts_page_div::setPidlist($this->config['storeRootPid']);	// Set list of page id's to the storeRootPid.
			tx_ttproducts_page_div::initRecursive(999);		// This add's all subpart ids to the pid_list based on the rootPid set in previous line
			tx_ttproducts_page_div::generatePageArray();		// Creates an array with page titles from the internal pid_list. Used for the display of category titles.
		}
		$admin = tx_ttproducts_tracking_div::shopAdmin();
		if (t3lib_div::_GP('tracking') || $admin)	{		// Tracking number must be set
			$orderRow = tx_ttproducts_order_div::getOrderRecord('',t3lib_div::_GP('tracking'));
			if (is_array($orderRow) || $admin)	{		// If order is associated with tracking id.
				if (!is_array($orderRow)) {
					$orderRow=array('uid'=>0);
				}
				switch ($theCode) {
					case 'TRACKING':
						$content = tx_ttproducts_tracking_div::getTrackingInformation($orderRow,$this->templateCode);
						break;
					case 'BILL':
						$content = tx_ttproducts_billdelivery_div::getInformation('bill',$orderRow, $this->templateCode,t3lib_div::_GP('tracking'));
						break;
					case 'DELIVERY':
						$content = tx_ttproducts_billdelivery_div::getInformation('delivery',$orderRow, $this->templateCode,t3lib_div::_GP('tracking'));
						break;
					default:
						debug('error in '.TT_PRODUCTS_EXTkey.' calling function products_tracking with $type = "'.$type.'"');
				}
			} else {	// ... else output error page
				$content=$this->cObj->getSubpart($this->templateCode,tx_ttproducts_view_div::spMarker('###TRACKING_WRONG_NUMBER###'));
				if (!$TSFE->beUserLogin)	{$content = $this->cObj->substituteSubpart($content,'###ADMIN_CONTROL###','');}
			}
		} else {	// No tracking number - show form with tracking number
			$content=$this->cObj->getSubpart($this->templateCode,tx_ttproducts_view_div::spMarker('###TRACKING_ENTER_NUMBER###'));
			if (!$TSFE->beUserLogin)	{$content = $this->cObj->substituteSubpart($content,'###ADMIN_CONTROL###','');}
		}
		$markerArray=array();
		$markerArray['###FORM_URL###'] = $this->pi_getPageLink($TSFE->id,'',tx_ttproducts_view_div::getLinkParams()) ; // $this->getLinkUrl();	// Add FORM_URL to globalMarkerArray, linking to self.
		$content= $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	}  // products_tracking



	// **************************
	// tracking information
	// **************************

	/**
	 * Returns 1 if user is a shop admin
	 */
	function shopAdmin()	{
		$admin=0;
		if ($GLOBALS['TSFE']->beUserLogin)	{
			if (t3lib_div::_GP('update_code')==$this->conf['update_code'])	{
				$admin= 1;		// Means that the administrator of the website is authenticated.
			}
		}
		return $admin;
	}

	/**
	 * Tracking administration
	 */
	function getTrackingInformation($orderRow, $templateCode)	{
			/*

					Tracking information display and maintenance.

					status-values are
						0:	Blank order
					1-1 Incoming orders
						1: 	Order confirmed at website
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
					50-99:	Useable by the customer
					50-59: General user messages, may be updated by the ordinary users.
					    50 = Customer request for cancelling
					    51 = Message from customer to shop
					60-69:	Special user messages by the customer
				    	60 = Send gift certificate message to receiver

					100-299:  Order finalized.
					    100 = Order shipped and closed
					    101 = Order closed
					    200 = Order cancelled

					All status values can be altered only if you're logged in as a BE-user and if you know the correct code (setup as .update_code in TypoScript config)
			*/

		global $TSFE;

		$admin = tx_ttproducts_tracking_div::shopAdmin();

		if ($orderRow['uid'])	{
				// Initialize update of status...
			$fieldsArray = array();
			$orderRecord = t3lib_div::_GP('orderRecord');
			if (isset($orderRecord['email_notify']))	{
				$fieldsArray['email_notify']=$orderRecord['email_notify'];
				$orderRow['email_notify'] = $fieldsArray['email_notify'];
			}
			if (isset($orderRecord['email']))	{
				$fieldsArray['email']=$orderRecord['email'];
				$orderRow['email'] = $fieldsArray['email'];
			}

			if (is_array($orderRecord['status']))	{
				$status_log = unserialize($orderRow['status_log']);
				reset($orderRecord['status']);
				$update=0;
				while(list(,$val)=each($orderRecord['status']))	{
					$status_log_element = array(
						'time' => time(),
						'info' => $this->conf['statusCodes.'][$val],
						'status' => $val,
						'comment' => $orderRecord['status_comment']
					);

					if ($admin || ($val>=50 && $val<59))	{// Numbers 50-59 are usermessages.
						$recipient = $this->conf['orderEmail_to'];
						if ($orderRow['email'] && ($orderRow['email_notify']))	{
							$recipient .= ','.$orderRow['email'];
						}
						$templateMarker = 'TRACKING_EMAILNOTIFY_TEMPLATE';
						tx_ttproducts_email_div::sendNotifyEmail($recipient, $status_log_element, t3lib_div::_GP('tracking'), $orderRow, $templateCode, $templateMarker);

						$status_log[] = $status_log_element;
						$update=1;
					} else if ($val>=60 && $val<69) { //  60 -69 are special messages
						$templateMarker = 'TRACKING_EMAIL_GIFTNOTIFY_TEMPLATE';
						$query = 'ordernumber=\''.$orderRow['uid'].'\'';
						$giftRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products_gifts', $query);
						while ($giftRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($giftRes)) {
							$recipient = $giftRow['deliveryemail'].','.$giftRow['personemail'];
							$this->sendGiftEmail($recipient, $orderRecord['status_comment'], $giftRow, $templateCode, $templateMarker, $giftRow['personname'], $giftRow['personemail']);
						}
						$status_log[] = $status_log_element;
						$update=1;
					}
				}
				if ($update)	{
					$fieldsArray['status_log']=serialize($status_log);
					$fieldsArray['status']=$status_log_element['status'];
					if ($fieldsArray['status'] >= 100)	{

							// Deletes any M-M relations between the tt_products table and the order.
							// In the future this should maybe also automatically count down the stock number of the product records. Else it doesn't make sense.
						$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_products_orders_mm_tt_products', 'sys_products_orders_uid='.intval($orderRow['uid']));
					}
				}
			}

			if (count($fieldsArray))	{		// If any items in the field array, save them
				$fieldsArray['tstamp'] = time();

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderRow['uid']), $fieldsArray);

				$orderRow = tx_ttproducts_order_div::getOrderRecord($orderRow['uid']);
			}
		}

			// Getting the template stuff and initialize order data.
		$content=$this->cObj->getSubpart($templateCode,'###TRACKING_DISPLAY_INFO###');

		$status_log = unserialize($orderRow['status_log']);
		$orderData = unserialize($orderRow['orderData']);

		// added by Franz begin
		$orderPayed = false;
		$orderClosed = false;
		if (is_array($status_log)) {
			foreach($status_log as $key=>$val)	{
				if ($val['status'] == 13)	{// Numbers 13 means order has been payed
					$orderPayed = true;
				}
				if ($val['status'] >= 100)	{// Numbers 13 means order has been payed
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

			$content = $this->cObj->substituteMarkerArrayCached($content,$markerArray,$subpartArray);
		}

		// added by Franz end

			// Status:
		$STATUS_ITEM=$this->cObj->getSubpart($content,'###STATUS_ITEM###');
		$STATUS_ITEM_c='';
		if (is_array($status_log))	{
			reset($status_log);

			while(list($k,$v)=each($status_log))	{
				$markerArray=Array();
				$markerArray['###ORDER_STATUS_TIME###']=$this->cObj->stdWrap($v['time'],$this->conf['statusDate_stdWrap.']);
				$markerArray['###ORDER_STATUS###']=$v['status'];
				$markerArray['###ORDER_STATUS_INFO###']=$v['info'];
				$markerArray['###ORDER_STATUS_COMMENT###']=nl2br($v['comment']);

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
		if ($TSFE->beUserLogin)	{
				// Status admin:
			if (is_array($this->conf['statusCodes.']))	{
				reset($this->conf['statusCodes.']);
				while(list($k,$v)=each($this->conf['statusCodes.']))	{
					if ($k!=1)	{
						$markerArray['###STATUS_OPTIONS###'].='<option value="'.$k.'">'.htmlspecialchars($k.': '.$v).'</option>';
					}
				}
			}

				// Get unprocessed orders.
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,name,tracking_code,amount', 'sys_products_orders', 'NOT deleted AND status!=0 AND status<100', '', 'crdate');
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$markerArray['###OTHER_ORDERS_OPTIONS###'].='<option value="'.$row['tracking_code'].'">'.htmlspecialchars(tx_ttproducts_order_div::getOrderNumber($row['uid']).': '.$row['name'].' ('.tx_ttproducts_view_div::priceFormat($row['amount']).' '.$this->conf['currencySymbol'].')').'</option>';
			}
		}

			// Final things
		$markerArray['###ORDER_HTML_OUTPUT###'] = $orderData['html_output'];		// The save order-information in HTML-format
		$markerArray['###FIELD_EMAIL_NOTIFY###'] = $orderRow['email_notify'] ? ' checked' : '';
		$markerArray['###FIELD_EMAIL###'] = $orderRow['email'];
		$markerArray['###ORDER_UID###'] = tx_ttproducts_order_div::getOrderNumber($orderRow['uid']);
		$markerArray['###ORDER_DATE###'] = $this->cObj->stdWrap($orderRow['crdate'],$this->conf['orderDate_stdWrap.']);
		$markerArray['###TRACKING_NUMBER###'] = t3lib_div::_GP('tracking');
		$markerArray['###UPDATE_CODE###'] = t3lib_div::_GP('update_code');

		$content= $this->cObj->substituteMarkerArrayCached($content, $markerArray, $subpartArray);
		return $content;
	} // getTrackingInformation





}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_tracking_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_tracking_div.php']);
}


?>
