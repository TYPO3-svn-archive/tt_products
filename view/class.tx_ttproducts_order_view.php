<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2008 Franz Holzinger <kontakt@fholzinger.com>
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
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_order_view {

	var $pibase; // reference to object of pibase
	var $conf;
	var $subpartmarkerObj; 		// subpart marker functions

	function init($pibase, $formerBasket) {

		$this->pibase = $pibase;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

		$this->conf = &$cnf->conf;

		$this->subpartmarkerObj = t3lib_div::makeInstance('tx_ttproducts_subpartmarker');
		$this->subpartmarkerObj->init(
			$pibase->cObj
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$templateCode: ...
	 * @param	[type]		$error_code: ...
	 * @return	[type]		...
	 */
	function &printView(&$templateCode, &$error_code)	 {
		global $TSFE, $TYPO3_DB;

		$feusers_uid = $TSFE->fe_user->user['uid'];
		$priceViewObj = t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');

			// order
		$orderObj = $tablesObj->get('sys_products_orders');

		if (!$feusers_uid)	{
			return $this->pibase->cObj->getSubpart($templateCode,$this->subpartmarkerObj->spMarker('###MEMO_NOT_LOGGED_IN###'));
		}

		$where = 'feusers_uid=' . intval($feusers_uid) . $this->pibase->cObj->enableFields('sys_products_orders') . ' ORDER BY crdate';
		$res = $TYPO3_DB->exec_SELECTquery('*', 'sys_products_orders', $where);

		$content = $this->pibase->cObj->getSubpart($templateCode,$this->subpartmarkerObj->spMarker('###ORDERS_LIST_TEMPLATE###'));

		$orderitem = $this->pibase->cObj->getSubpart($content,'###ORDER_ITEM###');
		$count = $TYPO3_DB->sql_num_rows($res);
		if ($count) {
			// Fill marker arrays
			$markerArray=array();
			$subpartArray=array();
			$tot_creditpoints_saved = 0;
			$tot_creditpoints_changed = 0;
			$tot_creditpoints_spended = 0;
			$tot_creditpoints_gifts = 0;
			$this->orders = array();
			while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
				//$this->orders[$row['uid']] = $row['tracking_code'];
				$markerArray['###TRACKING_CODE###'] = $row['tracking_code'];
				$markerArray['###ORDER_DATE###'] = $this->pibase->cObj->stdWrap($row['crdate'],$this->conf['orderDate_stdWrap.']);
				$markerArray['###ORDER_NUMBER###'] = $orderObj->getNumber($row['uid']);
				//$rt= $row['creditpoints_saved'] + $row['creditpoints_gifts'] - $row['creditpoints_spended'] - $row['creditpoints'];
				$markerArray['###ORDER_CREDITS###'] =$row['creditpoints_saved'] + $row['creditpoints_gifts'] - $row['creditpoints_spended'] - $row['creditpoints'];
				$markerArray['###ORDER_AMOUNT###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($row['amount']));

				// total amount of saved creditpoints
				$tot_creditpoints_saved += $row['creditpoints_saved'];

				// total amount of changed creditpoints
				$tot_creditpoints_changed += $row['creditpoints'];

				// total amount of spended creditpoints
				$tot_creditpoints_spended += $row['creditpoints_spended'];

				// total amount of creditpoints from gifts
				$tot_creditpoints_gifts += $row['creditpoints_gifts'];
				$orderlistc.= $this->pibase->cObj->substituteMarkerArray($orderitem, $markerArray);
			}
			$TYPO3_DB->sql_free_result($res);

			$res1 = $TYPO3_DB->exec_SELECTquery('username ', 'fe_users', 'uid="'.intval($feusers_uid).'"');
			if ($row = $TYPO3_DB->sql_fetch_assoc($res1)) {
				$username = $row['username'];
			}
			$TYPO3_DB->sql_free_result($res1);

			$res2 = $TYPO3_DB->exec_SELECTquery('username', 'fe_users', 'tt_products_vouchercode='.$TYPO3_DB->fullQuoteStr($username, 'fe_users'));
			$num_rows = $TYPO3_DB->sql_num_rows($res2) * 5;
			$TYPO3_DB->sql_free_result($res2);

			$res3 = $TYPO3_DB->exec_SELECTquery('tt_products_creditpoints ', 'fe_users', 'uid='.intval($feusers_uid).' AND NOT deleted');
			$this->creditpoints = array();
			while($row = $TYPO3_DB->sql_fetch_assoc($res3)) {
				$this->creditpoints[$row['uid']] = $row['tt_products_creditpoints'];
				$totalcreditpoints= $row['tt_products_creditpoints'];
			}
			$TYPO3_DB->sql_free_result($res3);

			$markerArray=array();
			$subpartArray=array();

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
		 	$content= $this->pibase->cObj->substituteMarkerArrayCached($content, $markerArray, $subpartArray);
		} else {
			$TYPO3_DB->sql_free_result($res);
			$norows=$this->pibase->cObj->getSubpart($content, '###ORDER_NOROWS###');
			$content = $norows;
		} // else of if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))

		return $content;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_order_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_order_view.php']);
}


?>