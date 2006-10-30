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
 * credit card functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_card {
	var $pibase; // reference to object of pibase
	var $conf;
	var $ccArray;	// credit card data


	function init(&$pibase, &$cnf, $formerBasket) {
		$asterisk = '***********************';

		$this->pibase = &$pibase;
		$this->conf = &$cnf->conf;
		$this->ccArray = array();
		$this->ccArray = $formerBasket['creditcard'];
		if ($this->ccArray['cc_number'] && strcmp ($this->ccArray['cc_number'], $asterisk) != 0)	{
			global $TSFE;
			
			$ccArray = $TSFE->fe_user->getKey('ses','cc');
			if (!$ccArray)	{
				$ccArray = array();
			}
			$ccArray['cc_uid'] = $this->create ($ccArray['cc_uid'], $this->ccArray['cc_number'], $this->ccArray['cc_type']);
			$TSFE->fe_user->setKey('ses','cc',$ccArray);
			$this->ccArray['cc_number'] = $asterisk;
		}
		
	}


	// **************************
	// ORDER related functions
	// **************************

	/**
	 * Create a new credit card record
	 *
	 * This creates a new credit card record on the page with pid PID_sys_products_orders. That page must exist!
	 * Should be called only internally by eg. $order->getBlankUid, that first checks if a blank record is already created.
	 */
	function create($uid, $number, $type)	{
		global $TSFE, $TYPO3_DB;

		$newId = 0;
		$pid = intval($this->conf['PID_sys_products_orders']);
		if (!$pid)	$pid = intval($TSFE->id);

		if ($number && $type && $TSFE->sys_page->getPage_noCheck ($pid))	{
			$time = time();
			$newFields = array (
				'pid' => intval($pid),
				'tstamp' => $time,
				'crdate' => $time,
				'cc_number' => $number,
				'cc_type' => $type
			);

			if ($uid)	{
				$TYPO3_DB->exec_UPDATEquery('sys_products_cards','uid='.$uid,$newFields);
				$newId = $uid;
			} else {
				$TYPO3_DB->exec_INSERTquery('sys_products_cards', $newFields);
				$newId = $TYPO3_DB->sql_insert_id();
			}
		}
		return $newId;
	} // create


	function getUid()	{
		global $TSFE;

		$ccArray = $TSFE->fe_user->getKey('ses','cc');
		return $ccArray['cc_uid'];
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	function getItemMarkerArray (&$markerArray)	{
		global $TCA;
		global $TYPO3_CONF_VARS;

		include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_form_div.php');

		$ccTypeText = tx_ttproducts_form_div::createSelect ($this->pibase, $TCA['sys_products_cards']['columns']['cc_type']['config']['items'], 'recs[creditcard][cc_type]');

		$markerArray['###PERSON_CARDS_CC_TYPE###'] = $ccTypeText;
		$markerArray['###PERSON_CARDS_CC_NUMBER###'] = $this->ccArray['cc_number'];		
	} // getMarkerArray


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_card.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_card.php']);
}


?>
