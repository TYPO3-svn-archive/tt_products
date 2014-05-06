<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * view functions
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
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
		$basket = t3lib_div::getUserObj('&tx_ttproducts_basket');
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
	 *
	 * @param	[type]		$markerArray: ...
	 * @param	[type]		$giftnumber: ...
	 * @param	[type]		$code: ...
	 * @param	[type]		$id: ...
	 * @return	[type]		...
	 */
	function addGiftMarkers($markerArray, $giftnumber, $code='LISTGIFTS', $id='1')	{

		$basket = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$markerArray['###GIFTNO###'] = $giftnumber;
		$markerArray['###GIFT_PERSON_NAME###'] = $basket->basketExt['gift'][$giftnumber]['personname'];
		$markerArray['###GIFT_PERSON_EMAIL###'] = $basket->basketExt['gift'][$giftnumber]['personemail'];
		$markerArray['###GIFT_DELIVERY_NAME###'] = $basket->basketExt['gift'][$giftnumber]['deliveryname'];
		$markerArray['###GIFT_DELIVERY_EMAIL###'] = $basket->basketExt['gift'][$giftnumber]['deliveryemail'];
		$markerArray['###GIFT_NOTE###'] = $basket->basketExt['gift'][$giftnumber]['note'];
		//
		$markerArray['###FIELD_ID###'] = TT_PRODUCTS_EXT.'_'.strtolower($code).'_id_'.$id;
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
	 * @param	[type]		$orderUid: ...
	 * @param	[type]		$pid: ...
	 * @param	[type]		$giftBasket: ...
	 * @return	[type]		...
	 */
	function saveOrderRecord($orderUid, $pid, &$giftBasket) {
		global $TYPO3_DB;
		$rc = '';

		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$productObj = $tablesObj->get('tt_products');
		foreach ($giftBasket as $giftnumber => $rec) {
			$amount = 0;
			foreach ($rec['item'] as $productid => $product) {
				$row = $productObj->get($productid);
				$articleRows = $productObj->getArticleRows($productid);
				foreach ($product as $variant => $count) {
					$productObj->variant->modifyRowFromVariant ($row, $variant);
					$articleRow = $productObj->getArticleRow ($row, $theCode);
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
				'note'			=> $rec['note'],
				'amount'		=> $amount,
			);
			// Saving the gifts order record

			$TYPO3_DB->exec_INSERTquery('tt_products_gifts', $insertFields);
			$newId = $TYPO3_DB->sql_insert_id();
			$insertFields = array();
			$insertFields['uid_local'] = $newId;
			$variantFields = $productObj->variant->getFieldArray();

			foreach ($rec['item'] as $productid => $product) {
				foreach ($product as $variant => $count) {
					$row = array();
					$productObj->variant->modifyRowFromVariant	($row, $variant);

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
							$GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_products_gifts_articles_mm',	$insertFields);
					}
				}
			}
		}

		return $rc;
	}

}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_gifts_div.php'])  {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_gifts_div.php']);
}


?>