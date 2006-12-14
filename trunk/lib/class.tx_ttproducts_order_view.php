<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2006 Franz Holzinger <kontakt@fholzinger.com>
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


require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_marker.php');



class tx_ttproducts_order_view {

	var $pibase; // reference to object of pibase
	var $conf;
	var $basket;
	var $order;					 // object of the type tx_ttproducts_order
	var $marker; // marker functions


	function init(&$pibase, &$cnf, &$basket, &$order) {
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->basket = &$basket;
		$this->order = &$order;

		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init(
			$pibase,
			$cnf,
			$basket
		);
	}


	function &printView(&$templateCode, &$error_code)	 {
		global $TSFE, $TYPO3_DB;

		$feusers_uid = $TSFE->fe_user->user['uid'];

		if (!$feusers_uid)
			return $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker('###MEMO_NOT_LOGGED_IN###'));

		$res = $TYPO3_DB->exec_SELECTquery('*', 'sys_products_orders','feusers_uid='.intval($feusers_uid).' AND NOT deleted ORDER BY crdate');

		$content=$this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker('###ORDERS_LIST_TEMPLATE###'));
 //CBY 11/11/2005 modifications : integrating order list template start
	  //$orderlist=$this->pibase->cObj->getSubpart($content,'###ORDER_LIST###');
		$orderitem=$this->pibase->cObj->getSubpart($content,'###ORDER_ITEM###');
		if ($TYPO3_DB->sql_num_rows($res)) {
			// Fill marker arrays
			$markerArray=Array();
			$subpartArray=Array();
			$tot_creditpoints_saved = 0;
			$tot_creditpoints_changed= 0;
			$tot_creditpoints_spended= 0;
			$tot_creditpoints_gifts= 0;
			$this->orders = array();
			while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
				//$this->orders[$row['uid']] = $row['tracking_code'];
				$markerArray['###TRACKING_CODE###']=$row['tracking_code'];
				$markerArray['###ORDER_DATE###'] = $this->pibase->cObj->stdWrap($row['crdate'],$this->conf['orderDate_stdWrap.']);
				$markerArray['###ORDER_NUMBER###'] = $this->order->getNumber($row['uid']);
				//$rt= $row['creditpoints_saved'] + $row['creditpoints_gifts'] - $row['creditpoints_spended'] - $row['creditpoints'];
				$markerArray['###ORDER_CREDITS###']=$row['creditpoints_saved'] + $row['creditpoints_gifts'] - $row['creditpoints_spended'] - $row['creditpoints'];
				$markerArray['###ORDER_AMOUNT###'] = $this->pibase->price->printPrice($this->pibase->price->priceFormat($row['amount']));
				
				// total amount of saved creditpoints
				$tot_creditpoints_saved += $row['creditpoints_saved'];
				
				// total amount of changed creditpoints
				$tot_creditpoints_changed+= $row['creditpoints'];
				
				// total amount of spended creditpoints
				$tot_creditpoints_spended+= $row['creditpoints_spended'];
				
				// total amount of creditpoints from gifts
				$tot_creditpoints_gifts+= $row['creditpoints_gifts'];
				$orderlistc.= $this->pibase->cObj->substituteMarkerArray($orderitem, $markerArray);
			}

			$res1 = $TYPO3_DB->exec_SELECTquery('username ', 'fe_users', 'uid="'.intval($feusers_uid).'"');
			if ($row = $TYPO3_DB->sql_fetch_assoc($res1)) {
				$username = $row['username'];
			}
	
			$res2 = $TYPO3_DB->exec_SELECTquery('username', 'fe_users', 'tt_products_vouchercode='.$TYPO3_DB->fullQuoteStr($username, 'fe_users'));
			$num_rows = $TYPO3_DB->sql_num_rows($res2) * 5;
			$res3 = $TYPO3_DB->exec_SELECTquery('tt_products_creditpoints ', 'fe_users', 'uid='.intval($feusers_uid).' AND NOT deleted');
			$this->creditpoints = array();
			while($row = $TYPO3_DB->sql_fetch_assoc($res3)) {
				$this->creditpoints[$row['uid']] = $row['tt_products_creditpoints'];
				$totalcreditpoints= $row['tt_products_creditpoints'];
			}
	
			$markerArray=Array();
			$subpartArray=Array();
	
			$markerArray['###CLIENT_NUMBER###'] = $feusers_uid;
			$markerArray['###CLIENT_NAME###'] = $username;
			$markerArray['###CREDIT_POINTS_SAVED###'] = number_format($tot_creditpoints_saved,0);
			$markerArray['###CREDIT_POINTS_SPENT###'] = number_format($tot_creditpoints_spended,0);
			$markerArray['###CREDIT_POINTS_CHANGED###'] = number_format($tot_creditpoints_changed,0);
			$markerArray['###CREDIT_POINTS_USED###'] = number_format($tot_creditpoints_spended,0) + number_format($tot_creditpoints_changed,0);
			$markerArray['###CREDIT_POINTS_GIFTS###'] = number_format($tot_creditpoints_gifts,0);
			$markerArray['###CREDIT_POINTS_TOTAL###'] = number_format($totalcreditpoints,0);
			$markerArray['###CREDIT_POINTS_VOUCHER###'] = $num_rows;
			$markerArray['###CALC_DATE###'] = date('d M Y');
			$subpartArray['###ORDER_LIST###'] = $orderlistc;
			$subpartArray['###ORDER_NOROWS###'] = '';
		 	$content= $this->pibase->cObj->substituteMarkerArrayCached($content,$markerArray,$subpartArray);
		} else {
			$norows=$this->pibase->cObj->getSubpart($content,'###ORDER_NOROWS###');
			$content = $norows;
		} // else of if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))
  
		return $content;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_order_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_order_view.php']);
}


?>
