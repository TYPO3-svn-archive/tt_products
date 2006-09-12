<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * basket functions for a basket object
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Els Verberne <verberne@bendoo.nl>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once(PATH_t3lib.'class.t3lib_div.php');

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricecalc.php');


class tx_ttproducts_basket {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;

	var $page;
	var $tt_content; // element of class tx_table_db
	var $tt_products; // element of class tx_table_db
	var $tt_products_articles; // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db
	var $fe_users; // element of class tx_table_db

	var $price; // price object
	var $pricecalc; // price calculation object
	var $paymentshipping; // paymentshipping object

		// Internal: initBasket():

	var $basket=array();				// initBasket() sets this array based on the registered items
	var $basketExtra;					// initBasket() uses this for additional information like the current payment/shipping methods
	var $recs = Array(); 				// in initBasket this is set to the recs-array of fe_user.

	var $basketExt=array();				// "Basket Extension" - holds extended attributes
	var $giftnumber;					// current counter of the gifts

	var $itemArray;						// the items in the basket; database row, how many (quantity, count) and the price; this has replaced the former $calculatedBasket
	var $calculatedArray;				// all calculated totals from the basket e.g. priceTax and weight

	var $viewTable;	// link to tt_products or tt_products_articles
	var $useArticles; 


	/**
	 * Removes a gift from the basket
	 *
	 * @param		int		 index of the gift
	 * @param 		int			uid of the product
	 * @param		string		variant of the product
	 * @return	  void
 	 */
	function removeGift($giftnumber, $uid, $variant) {
		if($this->basketExt['gift'][$giftnumber]['item'][$uid][$variant] >= 0) {
			unset($this->basketExt['gift'][$giftnumber]['item'][$uid][$variant]);
			if (!count($this->basketExt['gift'][$giftnumber]['item'][$uid])) {
				unset($this->basketExt['gift'][$giftnumber]['item'][$uid]);
			}
			if (!($this->basketExt['gift'][$giftnumber]['item'])) {
				unset($this->basketExt['gift'][$giftnumber]);
			}
		}
	}


	function getMaxCount ($quantity, $uid = 0)	{
		$count = 0;

		if ($this->conf['basketMaxQuantity'] == 'inStock' && !$this->conf['alwaysInStock'] && !empty($uid)) {
			$row = $this->tt_products->get($uid);
			$count = t3lib_div::intInRange($quantity,0,$row['inStock'],0);
		} elseif ($this->conf['quantityIsFloat'])	{
			$count = (float) $quantity;
			if ($count < 0)	{
				$count = 0;
			}
			if ($count > $this->conf['basketMaxQuantity'])	{
				$count = $this->conf['basketMaxQuantity'];
			}
		} else {
			$count=t3lib_div::intInRange($quantity,0,$this->conf['basketMaxQuantity'],0);		
		}
		
		return $count;
	}


	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
 	 */

	function init( &$pibase, &$cnf, $formerBasket, $updateMode, &$pid_list,
					&$tt_content, &$tt_products, &$tt_products_articles, 
					&$tt_products_cat, &$fe_users, &$price,
					&$paymentshipping, $bStoreBasket)	{
		global $TSFE;

 		$this->pibase = &$pibase;
 		$this->cnf = &$cnf;
 		$this->conf = &$this->cnf->conf;
 		$this->config = &$this->cnf->config;
 		$this->tt_content = &$tt_content;
 		$this->tt_products = &$tt_products;
 		$this->tt_products_articles = &$tt_products_articles;
 		$this->tt_products_cat = &$tt_products_cat;
 		$this->fe_users = &$fe_users;
		$this->recs = $formerBasket;	// Sets it internally
		$this->basket=array();
		$this->price = &$price;
 		$this->pricecalc = t3lib_div::makeInstance('tx_ttproducts_pricecalc');
 		$this->pricecalc->init($pibase, $this, $tt_products);		
		$this->paymentshipping = &$paymentshipping;

			// pages
		$this->page = tx_ttproducts_page::createPageTable(
			$this->pibase,
			$this->cnf,
			$this->tt_content,
			$this->pibase->LLkey,
			$this->conf['table.']['pages'], 
			$this->conf['conf.']['pages.'],
			$this->page,
			$pid_list,
			99
		);
		
		$this->useArticles = $this->conf['useArticles'];
		
		if ($this->useArticles == 2)	{
			$this->viewTable = &$this->tt_products_articles; 
		} else {
			$this->viewTable = &$this->tt_products;
		}

		$this->itemArray = array();
		$tmpBasketExt = $TSFE->fe_user->getKey('ses','basketExt');

		if (is_array($tmpBasketExt)) {
			$this->basketExt = $tmpBasketExt;
		} else {
			$this->basketExt = array();
		}
		
		$basketExtRaw = t3lib_div::_GP('ttp_basket');
		$this->giftnumber = count ($this->basketExt['gift']) + 1;
		$newGiftData = t3lib_div::_GP('ttp_gift');
		$extVars = $this->pibase->piVars['variants'];
		$extVars = ($extVars ? $extVars : t3lib_div::_GP('ttp_extvars'));
		$paramProduct = strtolower($this->viewTable->marker);
		$uid = $this->pibase->piVars[$paramProduct];
		$uid = ($uid ? $uid : t3lib_div::_GP('tt_products'));
		$sameGiftData = true;
		$identGiftnumber = 0;

			// Call all changeBasket hooks
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['changeBasket'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['changeBasket'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'changeBasket')) {
					$hookObj->changeBasket($this, $basketExtRaw, $extVars, $paramProduct, $uid, $sameGiftData, $identGiftnumber);
				}
			}
		}

		if ($newGiftData) {
	 		$giftnumber = t3lib_div::_GP('giftnumber');
			if ($updateMode) {
				$this->basketExt['gift'][$giftnumber] = $newGiftData;
				$giftcount = intval($this->basketExt['gift'][$giftnumber]['item'][$uid][$extVars]);
				if ($giftcount == 0) {
					$this->removeGift($giftnumber, $uid, $extVars);
				}
				$count = 0;
				foreach ($this->basketExt['gift'] as $prevgiftnumber => $rec) {
					$count += $rec['item'][$uid][$extVars];
				}
				// update the general basket entry for this product
				$this->basketExt[$uid][$extVars] = $count;
			} else {
			 	if (is_array($this->basketExt['gift'])) {
					foreach ($this->basketExt['gift'] as $prevgiftnumber => $rec) {
						$sameGiftData = true;
						foreach ($rec as $field => $value) {
							// only the 'field' field can be different
							if ($field != 'item' && $field != 'note' && $value != $newGiftData[$field]) {
								$sameGiftData = false;
								break;
							}
						}
						if ($sameGiftData) {
							$identGiftnumber = $prevgiftnumber;
							// always use the latest note
							$this->basketExt['gift'][$identGiftnumber]['note'] = $newGiftData['note'];
							break;
						}
					}
			 	} else {
					$sameGiftData = false;
				}
				if (!$sameGiftData) {
					$this->basketExt['gift'][$this->giftnumber] = $newGiftData;
				}
			}
		}

		if (is_array($basketExtRaw)) {
			while(list($uid,$basketItem) = each($basketExtRaw)) {
			
				$variant = $this->viewTable->variant->getVariantFromRow($basketItem);
				if (t3lib_div::testInt($uid))	{
					// quantities for single values are stored in an array. This is necessary because a HTML checkbox does not send any values if it has been unchecked  
					if (is_array($basketItem['quantity']))	{
						$basketItem['quantity'] = current($basketItem['quantity']);
					}
					$quantity = 0;
					$quantity = $this->price->toNumber($this->conf['quantityIsFloat'],$basketItem['quantity']);

					if ($this->conf['quantityIsFloat'])	{
						$this->basketExt[$uid][$variant] = $quantity;
					}
					
					if (!$updateMode) {
						$count=$this->getMaxCount ($quantity, $uid);
						if ($count >= 0) {
							$newcount = $count;
							$oldcount = $this->basketExt[$uid][$variant];
							if ($newGiftData) {
								$giftnumber = 0;
								if ($sameGiftData) {
									$giftnumber = $identGiftnumber;
									$oldcount -= $this->basketExt['gift'][$giftnumber]['item'][$uid][$variant];
								}
								else {
									$giftnumber = $this->giftnumber;
								}
								$newcount += $oldcount;
								$this->basketExt['gift'][$giftnumber]['item'][$uid][$variant] = $count;
								if ($count == 0) {
									$this->removeGift($giftnumber, $uid, $variant);
								}
							}
							if ($newcount)	{
								$this->basketExt[$uid][$variant] = $newcount;
							} else {
								unset ($this->basketExt[$uid][$variant]);
							}
						}
					} else {
						reset($basketItem);

						while(list($md5,$quantity)=each($basketItem)) {
							$quantity = $this->price->toNumber($this->conf['quantityIsFloat'],$quantity);
							if (is_array($this->basketExt[$uid]))
							{
								reset($this->basketExt[$uid]);
								while(list($variant,)=each($this->basketExt[$uid])) {
									 // useArticles if you have different prices and therefore articles for color, size, additional and gradings
									if (md5($variant)==$md5) {
										$count=$this->getMaxCount ($quantity, $uid);
										$this->basketExt[$uid][$variant] = $count;
									 	if (is_array($this->basketExt['gift'])) {
									 		$count = count($this->basketExt['gift']);
									 		$giftCount = 0;
									 		$restQuantity = $quantity;
									 		for ($giftnumber = 1; $giftnumber <= $count; ++$giftnumber) {
									 			if ($restQuantity == 0) {
									 				$this->removeGift($giftnumber, $uid, $variant);
									 			} else {
										 			if ($this->basketExt['gift'][$giftnumber]['item'][$uid][$variant] > $restQuantity) {
										 				$this->basketExt['gift'][$giftnumber]['item'][$uid][$variant] = $restQuantity;
										 				$restQuantity = 0;
										 			} else if ($giftnumber < $count) {
										 				$restQuantity -= $this->basketExt['gift'][$giftnumber]['item'][$uid][$variant];
										 			} else {
										 				$this->basketExt['gift'][$giftnumber]['item'][$uid][$variant] = $restQuantity;
										 			}
									 			}
									 		}
									 	}
									}
								}
							}
						}
					}
				}
			}
	
	
			// I did not find another possibility to delete elements complete from a multidimensional array
			// than to recreate the array
			$basketExtNew = array();
			reset($this->basketExt);
			while(list($tmpUid,$tmpSubArr)=each($this->basketExt)) {
				while(list($tmpExtVar,$tmpCount)=each($tmpSubArr)) {
					if ($tmpCount > 0) {
						$basketExtNew[$tmpUid][$tmpExtVar] = $tmpCount;
					}
				}
			}
			$this->basketExt = $basketExtNew;

			if ($bStoreBasket)	{		
				if (is_array($this->basketExt) && count($this->basketExt))
					$TSFE->fe_user->setKey('ses','basketExt',$this->basketExt);
				else
					$TSFE->fe_user->setKey('ses','basketExt',array());
				$TSFE->fe_user->storeSessionData(); // The basket shall not get lost when coming back from external scripts
			}
		}
		$this->paymentshipping->setBasketExtras($formerBasket);

	} // init



	/**
	 * Returns a clear 'recs[tt_products]' array - so clears the basket.
	 */
	function getClearBasketRecord()	{
			// Returns a basket-record cleared of tt_product items
		unset($this->recs['tt_products']);
		unset($this->recs['personinfo']);
		unset($this->recs['delivery']);
		return ($this->recs);
	} // getClearBasketRecord



	/**
	 * Empties the shopping basket!
	 */
	function clearBasket()	{
		global $TSFE;

			// Empties the shopping basket!
		$TSFE->fe_user->setKey('ses','recs',$this->getClearBasketRecord());
		$TSFE->fe_user->setKey('ses','basketExt',array());
		unset($this->itemArray);
		unset($this->basketExt);
		
	} // clearBasket



	/**
	 * This calculates the totals. Very important function.
	This function also calculates the internal arrays

	$this->itemArray				- The basked elements, how many (quantity, count) and the price
	$this->calculatedArray	- Sums of goods, shipping, payment and total amount WITH TAX included

	... which holds the total amount, the final list of products and the price of payment and shipping!!

	 */
	function getCalculatedBasket()	{
		global $TYPO3_DB;
		
		if ($this->itemArray[0]) {// the item array contains all the data for the elements found in the basket
			return;	// this function is called in a loop from basketView->getView
					// all the calculations however need to be done only once
					// the global member variables must already be filled in
		}

		$uidArr = array();
		reset($this->basketExt);
		while(list($uidTmp,)=each($this->basketExt))
			if ($uidTmp != 'gift' && !in_array($uidTmp, $uidArr))
				$uidArr[] = intval($uidTmp);

		if (count($uidArr) == 0) {
			return;
		}
		
		// $taxFromShipping = $this->paymentshipping->getReplaceTAXpercentage();
		
		//$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', 'uid IN ('.implode(',',$uidArr).') AND pid IN ('.$this->page->pid_list.')'.$this->pibase->cObj->enableFields('tt_products'));
		$res = $this->viewTable->table->exec_SELECTquery('*','uid IN ('.implode(',',$uidArr).') AND pid IN ('.$this->page->pid_list.')'.$this->viewTable->table->enableFields());

		$productsArray = array();
		$this->page->setPageArray();
		while($row = $TYPO3_DB->sql_fetch_assoc($res))		{
			$pid = $row['pid'];

			// only the basket items for the pages belonging to this shop shall be used here
			if (isset($this->page->pageArray[$pid]))	{
				reset($this->basketExt[$row['uid']]);
				while(list($bextVars,)=each($this->basketExt[$row['uid']])) {
					$this->viewTable->variant->modifyRowFromVariant ($row, $bextVars);
					$row['extVars'] = $bextVars;
					if ($this->useArticles == 1 && $this->viewTable->table->name == 'tt_products') {
						// get the article uid with these colors, sizes and gradings
						$articleRow = $this->viewTable->getArticleRow($row);
							// use the fields of the article instead of the product
						foreach ($articleRow as $field => $fieldValue) {
							if ($field != 'uid') {
								$row[$field] = $fieldValue;
							}
						}
					} else if ($this->useArticles == 2)	{
						$productRow = $this->viewTable->getProductRow($row);
						$this->viewTable->mergeProductRow($row, $productRow);
					}
					$productsArray[] = $row;
					
				}
			}
		}
		
		$this->itemArray = array(); // array of the items in the basket
		$this->calculatedArray = array(); // this array is usede for all calculated things

		foreach ($productsArray as $k1 => $row)	{
			$variant = $this->viewTable->variant->getVariantFromRow($row);
			$newItem = $this->getItem($row,$variant);
			$this->itemArray [$row[$this->viewTable->fields['itemnumber']]][] = $newItem;
			$count = $newItem['count'];
			$priceTax = $newItem['priceTax'];
			$priceNoTax = $newItem['priceNoTax'];

			$this->calculatedArray['count']		+= $count;
			$this->calculatedArray['weight']	+= $row['weight']*$count;
			// if reseller is logged in then take 'price2', default is 'price'
			$price2Tax = $this->price->getPrice($row['price2'],1,$row['tax'],$this->conf['TAXincluded']);
			$price2NoTax = $this->price->getPrice($row['price2'],0,$row['tax'],$this->conf['TAXincluded']);
			$this->calculatedArray['price2Tax']['goodstotal']	+= $price2Tax * $count;
			$this->calculatedArray['price2NoTax']['goodstotal']	+= $price2NoTax * $count;

// Franz: is this needed?
//			$oldPriceTax = $this->price->getPrice($row['price'],1,$row['tax']);
//			$oldPriceNoTax = $this->price->getPrice($row['price'],0,$row['tax']);
//			if (doubleval($oldPriceNoTax) == 0) {
//				$oldPriceNoTax = $priceNoTax;
//			}
//			$this->calculatedArray['oldPriceTax']	+= $oldPriceTax * $count;
//			$this->calculatedArray['oldPriceNoTax']	+= $oldPriceNoTax * $count;
		}
		
		// set the 'calcprice' in itemArray
		if ($this->conf['pricecalc.'] || $this->conf['discountprice.']) {
			// do the price calculation
			$this->pricecalc->GetCalculatedData($this->conf);
		}
		
		// loop over all items in the basket indexed by a sort string
		foreach ($this->itemArray as $sort=>$actItemArray) {
			foreach ($actItemArray as $k1=>$actItem) {
				$row = &$actItem['rec'];

				// has the price been calculated before take it if it gets cheaper now
				if (($actItem['calcprice'] > 0) && ($actItem['calcprice'] < $actItem['priceTax'])) {
					$this->itemArray[$sort][$k1]['priceTax'] = $this->price->getPrice($actItem['calcprice'],true,$row['tax'],$this->conf['TAXincluded']);
					$this->itemArray[$sort][$k1]['priceNoTax'] = $this->price->getPrice($actItem['calcprice'],false,$row['tax'],$this->conf['TAXincluded']);
				}
				//  multiplicate it with the count :
				$this->itemArray[$sort][$k1]['totalTax'] = $this->itemArray[$sort][$k1]['priceTax'] * $actItem['count'];
				$this->itemArray[$sort][$k1]['totalNoTax'] = $this->itemArray[$sort][$k1]['priceNoTax'] * $actItem['count'];
						// Fills this array with the product records. Reason: Sorting them by category (based on the page, they reside on)
				$this->calculatedArray['priceTax']['goodstotal'] += $this->itemArray[$sort][$k1]['totalTax'];
				$this->calculatedArray['priceNoTax']['goodstotal'] += $this->itemArray[$sort][$k1]['totalNoTax'];

				$this->calculatedArray['categoryPriceTax']['goodstotal'][$row['category']]+= $this->itemArray[$sort][$k1]['totalTax'];
				$this->calculatedArray['categoryPriceNoTax']['goodstotal'][$row['category']]+= $this->itemArray[$sort][$k1]['totalNoTax'];
			}
		}

		foreach ($productsArray as $row) {
			if ($row['bulkily'])	{
				$value = $this->conf['bulkilyAddition'] * $this->basketExt[$row['uid']][$this->viewTable->variant->getVariantFromRow($row)];
				$tax = $this->conf['bulkilyFeeTax'];
				$this->calculatedArray['priceTax']['shipping'] += $this->price->getPrice($value,true,$tax,$this->conf['TAXincluded'],true);
				$this->calculatedArray['priceNoTax']['shipping'] += $this->price->getPrice($value,false,$tax,$this->conf['TAXincluded'],true);
			}
		}

		// TAXpercentage replaces priceNoTax
		if ($this->conf['shipping.']['TAXpercentage']) {
			$taxPercentage = $this->conf['shipping.']['TAXpercentage'];
			
//			if (isset($taxFromShipping) && is_double($taxFromShipping))	{
//				$taxPercentage = $taxFromShipping; 
//			}
			$this->calculatedArray['priceNoTax']['shipping'] = $this->price->getPrice($this->calculatedArray['priceTax']['shipping'],0,$taxPercentage,$this->conf['TAXincluded'],true); 
		}
		
			// Shipping must be at the end in order to use the calculated values from before
		$this->paymentshipping->getPaymentShippingData(
			$this->calculatedArray['count'],
			$this->calculatedArray['priceTax']['goodstotal'],
			$this->calculatedArray['priceTax']['shipping'],
			$this->calculatedArray['priceNoTax']['shipping'],
			$this->calculatedArray['priceTax']['payment'],
			$this->calculatedArray['priceNoTax']['payment']
		);
		
	} // getCalculatedBasket



	function &getItem (&$row, $variant) {
		$count = $this->basketExt[$row['uid']][$variant];
		if (!$this->conf['quantityIsFloat'])	{
			$count = intval($count);	
		}
		$priceTax = $this->price->getResellerPrice($row,1);
		$priceNoTax = $this->price->getResellerPrice($row,0);
		$item = array (
			'calcprice' => 0,
			'count' => $count,
			'priceTax' => $priceTax,
			'priceNoTax' => $priceNoTax,
			'totalTax' => 0,
			'totalNoTax' => 0,
			'rec' => $row,
			);
		return $item;
	}



	// This calculates the total for everything in the basket
	function getCalculatedSums () {
		$this->calculatedArray['priceTax']['total']  = $this->calculatedArray['priceTax']['goodstotal'];
		$this->calculatedArray['priceTax']['total'] += $this->calculatedArray['priceTax']['payment'];
		$this->calculatedArray['priceTax']['total'] += $this->calculatedArray['priceTax']['shipping'];

		$this->calculatedArray['priceTax']['total'] -= $this->calculatedArray['priceTax']['creditpoints'];
		$this->calculatedArray['priceTax']['total'] -= $this->calculatedArray['priceTax']['voucher'];

		$this->calculatedArray['priceNoTax']['total']  = $this->calculatedArray['priceNoTax']['goodstotal'];
		$this->calculatedArray['priceNoTax']['total'] += $this->calculatedArray['priceNoTax']['payment'];
		$this->calculatedArray['priceNoTax']['total'] += $this->calculatedArray['priceNoTax']['shipping'];
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basket.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basket.php']);
}


?>
