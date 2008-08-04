<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2008 Franz Holzinger <kontakt@fholzinger.com>
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
 * view functions
 *
 * $Id: class.tx_ttproducts_gifts_div.php,v 1.5 2005/12/12 17:39:39
franzholz Exp $
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


global $TYPO3_CONF_VARS;


class tx_ttproducts_gifts_div {
	/**
	 * returns if the product has been put into the basket as a gift
	 *
	 * @param	integer	 uid of the product
	 * @param	integer	 variant of the product only size is used now --> TODO
	 * @return  array		all gift numbers for this product
	 */
	function getGiftNumbers(&$basket, $uid, $variant)	{
		$giftArray = array();

		if ($basket->basketExt['gift']) {
			foreach ($basket->basketExt['gift'] as $giftnumber => $giftItem) {
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
	function addGiftMarkers(&$basket, &$markerArray, $giftnumber, $code='LISTGIFTS', $id='1')	
{

		$markerArray['###GIFTNO###'] = $giftnumber;
		$markerArray['###GIFT_PERSON_NAME###'] = $basket->basketExt['gift'][$giftnumber]['personname'];
		$markerArray['###GIFT_PERSON_EMAIL###'] = $basket->basketExt['gift'][$giftnumber]['personemail'];
		$markerArray['###GIFT_DELIVERY_NAME###'] = $basket->basketExt['gift'][$giftnumber]['deliveryname'];
		$markerArray['###GIFT_DELIVERY_EMAIL###'] = $basket->basketExt['gift'][$giftnumber]['deliveryemail'];
		$markerArray['###GIFT_NOTE###'] = $basket->basketExt['gift'][$giftnumber]['note'];
		//	 
		$markerArray['###FIELD_ID###'] = TT_PRODUCTS_EXTkey.'_'.strtolower($code).'_id_'.$id;
		// here again, because this is here in ITEM_LIST view
		//	  $markerArray['###FIELD_QTY###'] =  '';

	 	$markerArray['###FIELD_NAME_PERSON_NAME###']='ttp_gift[personname]';
		$markerArray['###FIELD_NAME_PERSON_EMAIL###']='ttp_gift[personemail]';
		$markerArray['###FIELD_NAME_DELIVERY_NAME###']='ttp_gift[deliveryname]';
		$markerArray['###FIELD_NAME_DELIVERY_EMAIL###']='ttp_gift[deliveryemail]';
		$markerArray['###FIELD_NAME_GIFT_NOTE###']='ttp_gift[note]';

		return $markerArray;
	} // addGiftMarkers



	/**
	 * Saves the orderRecord and returns the result
	 * 
	 */
	function saveOrderRecord(&$tt_products,$orderUid, $pid, &$giftBasket) {
		global $TYPO3_DB;
		$rc = '';

		foreach ($giftBasket as $giftnumber => $rec) {
			$amount = 0;
			foreach ($rec['item'] as $productid => $product) {
				$row = $tt_products->get($productid);
				$articleRows = $tt_products->getArticleRows($productid);
				foreach ($product as $variant => $count) {
					$tt_products->variant->modifyRowFromVariant ($row, $variant);
					$articleRow = $tt_products->getArticleRow ($row);
					if (count ($articleRow))	{
						$amount += intval($articleRow['price']) * $count;
					} else {
						$amount += intval($row['price']) * $count;
					}
				}
			}

			// Saving gift order data
			$insertFields = array(
				'pid' => intval($pid),
				'tstamp' => time(),
				'crdate' => time(),
				'deleted' => 0,

				'ordernumber'	=> $orderUid,
				'personname'	=> $rec['personname'],
				'personemail'	=> $rec['personemail'],
				'deliveryname'	=> $rec['deliveryname'],
				'deliveryemail' => $rec['deliveryemail'],
				'note'		=> $rec['note'],
				'amount'	=> $amount,
			);
			// Saving the gifts order record
			$TYPO3_DB->exec_INSERTquery('tt_products_gifts',	$insertFields);
			$newId = $TYPO3_DB->sql_insert_id();
			$insertFields = array();
			$insertFields['uid_local'] = $newId;
			$variantFields = $tt_products->variant->getFieldArray();

			foreach ($rec['item'] as $productid => $product) {
				foreach ($product as $variant => $count) {
					$row = array();
					$tt_products->variant->modifyRowFromVariant ($row, $variant);

					$query='uid_product=\''.intval($productid).'\'';
					foreach ($variantFields as $k => $field)	{
						if ($row[$field])	{
							$query .= ' AND '.$field.'='.$TYPO3_DB->fullQuoteStr($row[$field],'tt_products_articles');
						}
					}
					$articleRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tt_products_articles', $query);

					if ($articleRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($articleRes)) {
						$insertFields['uid_foreign'] = $articleRow['uid'];
						$insertFields['count'] = $count;
						// Saving the gifts mm order record
							$GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_products_gifts_articles_mm', $insertFields);
					}
				}
			}
		}

		return $rc;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_gifts_div.php'])  {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_gifts_div.php']);
}


?>
