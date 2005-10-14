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
 * view functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_gifts_div {


	/**
	 * returns if the product has been put into the basket as a gift
	 *
	 * @param	integer		uid of the product
	 * @param	integer		variant of the product only size is used now --> TODO
	 * @return	array		all gift numbers for this product
	 */
	function getGiftNumbers($uid, $variant)	{
		$giftArray = array();

		if ($this->basketExt['gift']) {
			foreach ($this->basketExt['gift'] as $giftnumber => $giftItem) {
				if ($giftItem['item'][$uid][$variant]) {
					$giftArray [] = $giftnumber;
				}
			}
		}

		return $giftArray;
	}



	/**
	 * Adds gift markers to a markerArray
	 */
	function addGiftMarkers($markerArray, $giftnumber)	{

		$markerArray['###GIFTNO###'] = $giftnumber;
		$markerArray['###GIFT_PERSON_NAME###'] = $this->basketExt['gift'][$giftnumber]['personname'];
		$markerArray['###GIFT_PERSON_EMAIL###'] = $this->basketExt['gift'][$giftnumber]['personemail'];
		$markerArray['###GIFT_DELIVERY_NAME###'] = $this->basketExt['gift'][$giftnumber]['deliveryname'];
		$markerArray['###GIFT_DELIVERY_EMAIL###'] = $this->basketExt['gift'][$giftnumber]['deliveryemail'];
		$markerArray['###GIFT_NOTE###'] = $this->basketExt['gift'][$giftnumber]['note'];
//		$markerArray['###FIELD_NAME###']='ttp_basket['.$row['uid'].'][quantity]'; // here again, because this is here in ITEM_LIST view
//		$markerArray['###FIELD_QTY###'] =  '';

		$markerArray['###FIELD_NAME_PERSON_NAME###']='ttp_gift[personname]';
		$markerArray['###FIELD_NAME_PERSON_EMAIL###']='ttp_gift[personemail]';
		$markerArray['###FIELD_NAME_DELIVERY_NAME###']='ttp_gift[deliveryname]';
		$markerArray['###FIELD_NAME_DELIVERY_EMAIL###']='ttp_gift[deliveryemail]';
		$markerArray['###FIELD_NAME_GIFT_NOTE###']='ttp_gift[note]';

		return $markerArray;
	} // addGiftMarkers



}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_gifts_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_gifts_div.php']);
}


?>