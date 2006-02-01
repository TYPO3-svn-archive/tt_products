<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Skaarhoj (kasperYYYY@typo3.com)
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

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_article_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricecalc.php');


class tx_ttproducts_basket {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;

	var $page;
	var $tt_content; // element of class tx_table_db
	var $tt_products; // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db

	var $price; // price object
	var $pricecalc; // price calculation object
	var $paymentshipping; // paymentshipping object

		// Internal: initBasket():

	var $basket=array();				// initBasket() sets this array based on the registered items
	var $basketExtra;					// initBasket() uses this for additional information like the current payment/shipping methods
	var $recs = Array(); 				// in initBasket this is set to the recs-array of fe_user.
	var $personInfo;					// Set by initBasket to the billing address
	var $deliveryInfo; 					// Set by initBasket to the delivery address

	var $basketExt=array();				// "Basket Extension" - holds extended attributes
	var $giftnumber;					// current counter of the gifts

		// Internal: Arrays from getView() function
	var $itemArray;						// the items in the basket; database row, how many (quantity, count) and the price; this has replaced the former $calculatedBasket
	var $calculatedArray;				// all calculated totals from the basket e.g. priceTax and weight

	var $feuserextrafields;			// exension with additional fe_users fields


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

	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
 	 */

	function init(&$pibase, &$conf, &$config, $formerBasket, $updateMode,
				&$page, &$tt_content, &$tt_products, &$tt_products_cat, &$price, &$paymentshipping)	{
		global $TSFE;

 		$this->pibase = &$pibase;
 		$this->conf = &$conf;
 		$this->config = &$config;
 		$this->page = &$page;
 		$this->tt_content = &$tt_content;
 		$this->tt_products = &$tt_products;
 		$this->tt_products_cat = &$tt_products_cat;
		$this->recs = $formerBasket;	// Sets it internally
		$this->basket=array();
		$this->price = &$price;
 		$this->pricecalc = t3lib_div::makeInstance('tx_ttproducts_pricecalc');
 		$this->pricecalc->init($pibase, $this, $tt_products);		
		$this->paymentshipping = &$paymentshipping;

		// store if feuserextrafields is loaded
		$this->feuserextrafields = t3lib_extMgm::isLoaded('feuserextrafields');

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
		$extVars= t3lib_div::_GP('ttp_extvars');
		$uid = t3lib_div::_GP('tt_products');
		
		$sameGiftData = true;
		$identGiftnumber = 0;

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
			while(list($uid,$basketItem)=each($basketExtRaw)) {
				$variant = tx_ttproducts_article_div::getVariantFromRow($basketItem);
				if (t3lib_div::testInt($uid))	{
					// quantities for single values are stored in an array. This is necessary because a HTML checkbox does not send any values if it has been unchecked  
					if (is_array($basketItem['quantity']))	{
						$basketItem['quantity'] = current($basketItem['quantity']);
					}
					
					if (!$updateMode) {
						$count=t3lib_div::intInRange($basketItem['quantity'],0,100000,0);
						if ($count >= 0) {
							$newcount = $count;
							$oldcount = intval($this->basketExt[$uid][$variant]);
							if ($newGiftData) {
								$giftnumber = 0;
								if ($sameGiftData) {
									$giftnumber = $identGiftnumber;
									$oldcount -= intval($this->basketExt['gift'][$giftnumber]['item'][$uid][$variant]);
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
							if (is_array($this->basketExt[$uid]))
							{
								reset($this->basketExt[$uid]);
								while(list($variant,)=each($this->basketExt[$uid])) {
									 // useArticles if you have different prices and therefore articles for color, size, additional and gradings
									if (md5($variant)==$md5) {
										$this->basketExt[$uid][$variant] = $quantity;
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

		if (is_array($this->basketExt) && count($this->basketExt))
			$TSFE->fe_user->setKey('ses','basketExt',$this->basketExt);
		else
			$TSFE->fe_user->setKey('ses','basketExt',array());
		$TSFE->fe_user->storeSessionData(); // Franz: The basket shall not get lost

		$this->paymentshipping->setBasketExtras($formerBasket);

		$this->personInfo = $formerBasket['personinfo'];
		$this->deliveryInfo = $formerBasket['delivery'];
		
		if ($this->conf['useStaticInfoCountry'] && $this->personInfo['country_code'])	{
			$this->personInfo['country'] = $this->pibase->staticInfo->getStaticInfoName('COUNTRIES', $this->personInfo['country_code'],'','');
			$this->deliveryInfo['country'] = $this->pibase->staticInfo->getStaticInfoName('COUNTRIES', $this->deliveryInfo['country_code'],'','');
		}
		
		if ($TSFE->loginUser && (!$this->personInfo || !$this->personInfo['name']) && $this->conf['lockLoginUserInfo'])	{
			$address = '';

			if ($this->conf['loginUserInfoAddress']) {
				$address = implode(chr(10),
					t3lib_div::trimExplode(chr(10),
						$TSFE->fe_user->user['address'].chr(10).
						$TSFE->fe_user->user['zip'].' '.$TSFE->fe_user->user['city'].chr(10).
						($this->conf['useStaticInfoCountry'] ? $TSFE->fe_user->user['static_info_country']:$TSFE->fe_user->user['country'])
						,1)
					);
			}
			else {
				$address = $TSFE->fe_user->user['address'];
			}

			$this->personInfo['feusers_uid'] = $TSFE->fe_user->user['uid'];
			$this->personInfo['name'] = $TSFE->fe_user->user['name'];
			$this->personInfo['first_name'] = $TSFE->fe_user->user['first_name'];
			$this->personInfo['last_name'] = $TSFE->fe_user->user['last_name'];

			$this->personInfo['address'] = $address;
			$this->personInfo['email'] = $TSFE->fe_user->user['email'];

			$this->personInfo['telephone'] = $TSFE->fe_user->user['telephone'];
			$this->personInfo['fax'] = $TSFE->fe_user->user['fax'];
			$this->personInfo['zip'] = $TSFE->fe_user->user['zip'];
			$this->personInfo['city'] = $TSFE->fe_user->user['city'];
			$this->personInfo['country'] = ($this->conf['useStaticInfoCountry'] ? $TSFE->fe_user->user['static_info_country']:$TSFE->fe_user->user['country']);
			$this->personInfo['agb'] = (isset($this->personInfo['agb']) ? $this->personInfo['agb'] : $TSFE->fe_user->user['agb']);
			$this->personInfo['date_of_birth'] = date( 'd-m-Y', $TSFE->fe_user->user['date_of_birth']);
			$this->personInfo['company'] = $TSFE->fe_user->user['company'];

			if ($this->feuserextrafields) {
				/* Added Els: getting the fields for displaying in the BASKET_PAYMENT_TEMPLATE  from fe_user */
				$this->personInfo['tx_feuserextrafields_initials_name'] = $TSFE->fe_user->user['tx_feuserextrafields_initials_name'];
				$this->personInfo['tx_feuserextrafields_prefix_name'] = $TSFE->fe_user->user['tx_feuserextrafields_prefix_name'];
				$this->personInfo['tx_feuserextrafields_gsm_tel'] = $TSFE->fe_user->user['tx_feuserextrafields_gsm_tel'];
				$this->personInfo['tx_feuserextrafields_company_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_company_deliv'];
				$this->personInfo['tx_feuserextrafields_address_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_address_deliv'];
				$this->personInfo['tx_feuserextrafields_housenumber'] = $TSFE->fe_user->user['tx_feuserextrafields_housenumber'];
				$this->personInfo['tx_feuserextrafields_housenumber_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_housenumber_deliv'];
				$this->personInfo['tx_feuserextrafields_housenumberadd'] = $TSFE->fe_user->user['tx_feuserextrafields_housenumberadd'];
				$this->personInfo['tx_feuserextrafields_housenumberadd_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_housenumberadd_deliv'];
				$this->personInfo['tx_feuserextrafields_pobox'] = $TSFE->fe_user->user['tx_feuserextrafields_pobox'];
				$this->personInfo['tx_feuserextrafields_pobox_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_pobox_deliv'];
				$this->personInfo['tx_feuserextrafields_zip_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_zip_deliv'];
				$this->personInfo['tx_feuserextrafields_city_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_city_deliv'];
				$this->personInfo['tx_feuserextrafields_country'] = $TSFE->fe_user->user['tx_feuserextrafields_country'];
				$this->personInfo['tx_feuserextrafields_country_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_country_deliv'];
			}

/* Added Els: getting the field tt_products_creditpoints and tt_products_vouchercode from fe_user */
			$this->personInfo['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'];
			$this->personInfo['tt_products_vouchercode'] = $TSFE->fe_user->user['tt_products_vouchercode'];
		}
		
	} // init




	/**
	 * Checks if required fields are filled in
	 */
	function checkRequired()	{
		$flag = '';
		$requiredInfoFields = trim($this->basket->conf['requiredInfoFields']);
		if ($this->basketExtra['payment.']['addRequiredInfoFields'] != '')
			$requiredInfoFields .= ','.trim($this->basketExtra['payment.']['addRequiredInfoFields']);

		if ($requiredInfoFields)	{
			$infoFields = t3lib_div::trimExplode(',',$requiredInfoFields);
			while(list(,$fName)=each($infoFields))	{
				if (trim($this->personInfo[$fName])=='' || trim($this->deliveryInfo[$fName])=='')	{
					$flag=$fName;
					break;
				}
			}
		}
		return $flag;
	} // checkRequired



	/**
	 * Fills in all empty fields in the delivery info array
	 */
	function mapPersonIntoToDelivery()	{
		global $TCA;

			// all of the delivery address will be overwritten when no address and no email address have been filled in
		if (!trim($this->deliveryInfo['address']) && !trim($this->deliveryInfo['email'])) {
/* Added Els: 'feusers_uid,' and more fields */
			$infoExtraFields = ($this->feuserextrafields ? ',tx_feuserextrafields_initials_name,tx_feuserextrafields_prefix_name,tx_feuserextrafields_gsm_tel,tx_feuserextrafields_company_deliv,tx_feuserextrafields_address_deliv,tx_feuserextrafields_housenumber,tx_feuserextrafields_housenumber_deliv,tx_feuserextrafields_housenumberadd,tx_feuserextrafields_housenumberadd_deliv,tx_feuserextrafields_pobox,tx_feuserextrafields_pobox_deliv,tx_feuserextrafields_zip_deliv,tx_feuserextrafields_city_deliv,tx_feuserextrafields_country,tx_feuserextrafields_country_deliv':'');
			$infoFields = explode(',','feusers_uid,telephone,salutation,name,first_name,last_name,email,date_of_birth,company,address,city,zip,country,country_code'.$infoExtraFields); // Fields...
			while(list(,$fName)=each($infoFields))	{
				$this->deliveryInfo[$fName] = $this->personInfo[$fName];
			}
		}

	} // mapPersonIntoToDelivery



	/**
	 * Returns a clear 'recs[tt_products]' array - so clears the basket.
	 */
	function getClearBasketRecord()	{
			// Returns a basket-record cleared of tt_product items
		unset($this->recs['tt_products']);
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
	} // clearBasket



	/**
	 * This calculates the totals. Very important function.
	This function also calculates the internal arrays

	$this->itemArray				- The basked elements, how many (quantity, count) and the price
	$this->calculatedArray	- Sums of goods, shipping, payment and total amount WITH TAX included

	... which holds the total amount, the final list of products and the price of payment and shipping!!

	 */
	function getCalculatedBasket()	{
		if ($this->itemArray[0]) {// the item array contains all the data for the elements found in the basket
			return;	// this function is called in a loop from getView
					// all the calculations however need to be done only once
					// the global member variables must already be filled in
		}

		$uidArr = array();
		reset($this->basketExt);
		while(list($uidTmp,)=each($this->basketExt))
			if ($uidTmp != 'gift' && !in_array($uidTmp, $uidArr))
				$uidArr[] = $uidTmp;

		if (count($uidArr) == 0) {
			return;
		}
		//$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', 'uid IN ('.implode(',',$uidArr).') AND pid IN ('.$this->page->pid_list.')'.$this->pibase->cObj->enableFields('tt_products'));
		$res = $this->tt_products->table->exec_SELECTquery('*','uid IN ('.implode(',',$uidArr).') AND pid IN ('.$this->page->pid_list.')'.$this->tt_products->table->enableFields());

		$this->productsArray = array(); // TODO: use only local products array
		$productsArray = &$this->productsArray;
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
			reset($this->basketExt[$row['uid']]);
			while(list($bextVars,)=each($this->basketExt[$row['uid']])) {
				tx_ttproducts_article_div::getRowFromVariant ($row, $bextVars);
				$row['extVars'] = $bextVars;
				if ($this->conf['useArticles']) {
					// get the article uid with these colors, sizes and gradings
					$query='uid_product=\''.intval($row['uid']).'\' AND color=\''.$row['color'].'\' AND size=\''.$row['size'].'\' AND description=\''.$row['description'].'\' AND gradings=\''.$row['gradings'].'\'';
					$articleRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products_articles', $query);
					if ($articleRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($articleRes)) {
							// use the fields of the article instead of the product
						foreach ($articleRow as $field => $fieldValue) {
							if ($field != 'uid') {
								$row[$field] = $fieldValue;
							}
						}
					}
				}
				$productsArray[$row['pid']][]=$row;
			}
		}

		$pageArr=explode(',',$this->page->pid_list);

		$this->itemArray = array(); // array of the items in the basket
		$this->calculatedArray = array(); // this array is usede for all calculated things

		while(list(,$v)=each($pageArr))	{
			if (is_array($productsArray[$v]))	{
				reset($productsArray[$v]);
				while(list(,$row)=each($productsArray[$v]))	{
					// if reseller is logged in then take 'price2', default is 'price'
					$newItem = $this->getItem($row);
					$this->itemArray [intval($row['pid'])] [intval($row[$this->tt_products->fields['itemnumber']])][] = $newItem;
					$count = $newItem['count'];
					$priceTax = $newItem['priceTax'];
					$priceNoTax = $newItem['priceNoTax'];

					$this->calculatedArray['count']			+= $count;
					$this->calculatedArray['weight']		+= $row['weight']*$count;

// Franz: is this needed?
//					$oldPriceTax = $this->price->getPrice($row['price'],1,$row['tax']);
//					$oldPriceNoTax = $this->price->getPrice($row['price'],0,$row['tax']);

					$price2Tax = $this->price->getPrice($row['price2'],1,$row['tax']);
					$price2NoTax = $this->price->getPrice($row['price2'],0,$row['tax']);

//					/* Added Els3: if oldpricenotax is 0 -> oldpricenotax = pricenotax, otherwise price_discount doesn't calculate correctly */
//					if (doubleval($oldPriceNoTax) == 0) {
//						$oldPriceNoTax = $priceNoTax;
//					}
//
//					$this->calculatedArray['oldPriceTax']	+= $oldPriceTax * $count;
//					$this->calculatedArray['oldPriceNoTax']	+= $oldPriceNoTax * $count;
					$this->calculatedArray['price2Tax']['goodstotal']	+= $price2Tax * $count;
					$this->calculatedArray['price2NoTax']['goodstotal']	+= $price2NoTax * $count;
				}
			}
		}

		// set the 'calcprice' in itemArray
		if ($this->conf['pricecalc.'] || $this->conf['discountprice.']) {
			// do the price calculation
			$this->pricecalc->GetCalculatedData($this->conf);
		}

		// loop over all items in the basket indexed by page and itemnumber
		foreach ($this->itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					// has the price been calculated before take it if it gets cheaper now
					if (($actItem['calcprice'] > 0) && ($actItem['calcprice'] < $actItem['priceTax'])) {
						$this->itemArray[$pid][$itemnumber][$k1]['priceTax'] = $this->price->getPrice($actItem['calcprice'],1,$actItem['rec']['tax']);
						$this->itemArray[$pid][$itemnumber][$k1]['priceNoTax'] = $this->price->getPrice($actItem['calcprice'],0,$actItem['rec']['tax']);
					}
					//  multiplicate it with the count :
					$this->itemArray[$pid][$itemnumber][$k1]['totalTax'] = $this->itemArray[$pid][$itemnumber][$k1]['priceTax'] * $actItem['count'];
					$this->itemArray[$pid][$itemnumber][$k1]['totalNoTax'] = $this->itemArray[$pid][$itemnumber][$k1]['priceNoTax'] * $actItem['count'];
							// Fills this array with the product records. Reason: Sorting them by category (based on the page, they reside on)
					$this->calculatedArray['priceTax']['goodstotal'] += $this->itemArray[$pid][$itemnumber][$k1]['totalTax'];
					$this->calculatedArray['priceNoTax']['goodstotal'] += $this->itemArray[$pid][$itemnumber][$k1]['totalNoTax'];

					$this->calculatedArray['categoryPriceTax']['goodstotal'][$actItem['rec']['category']]+= $this->itemArray[$pid][$itemnumber][$k1]['totalTax'];
					$this->calculatedArray['categoryPriceNoTax']['goodstotal'][$actItem['rec']['category']]+= $this->itemArray[$pid][$itemnumber][$k1]['totalNoTax'];
				}
			}
		}

		foreach ($this->productsArray as $prodUID) {
			foreach ($prodUID as $prodSingle)
			{
				if ($prodSingle['bulkily'])
				{
					$value = ($this->conf['bulkilyAddition'] * $this->basketExt[$prodSingle['uid']][$prodSingle['color'].';'.$prodSingle['size'].';'.$prodSingle['description'].';'.$prodSingle['gradings']]);
					$this->calculatedArray['priceTax']['shipping'] += $value  * (1+$conf['bulkilyFeeTax']/100);
					$this->calculatedArray['priceNoTax']['shipping'] += $value;
				}
			}
		}

		// TAXpercentage replaces priceNoTax
		if ($this->conf['shipping.']['TAXpercentage']) {
			$this->calculatedArray['priceNoTax']['shipping'] += $this->calculatedArray['priceTax']['shipping']/(1+doubleVal($this->conf['shipping.']['TAXpercentage'])/100);
		}

			// Shipping must be at the end in order to use the calculated values from before
		$this->paymentshipping->GetPaymentShippingData(
			$this->calculatedArray['count'],
/* Added Els: necessary to calculate shipping price which depends on total no-tax price */
			$this->calculatedArray['priceTax']['goodstotal'],
			$this->calculatedArray['priceTax']['shipping'],
			$this->calculatedArray['priceNoTax']['shipping'],
			$this->calculatedArray['priceTax']['payment'],
			$this->calculatedArray['priceNoTax']['payment']
		);
	} // getCalculatedBasket


	function &getItem (&$row) {
		$count = intval($this->basketExt[$row['uid']][tx_ttproducts_article_div::getVariantFromRow ($row)]);
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
		$this->calculatedArray['priceTax']['total'] = $this->calculatedArray['priceTax']['goodstotal'];
		$this->calculatedArray['priceTax']['total']+= $this->calculatedArray['priceTax']['payment'];
		$this->calculatedArray['priceTax']['total']+= $this->calculatedArray['priceTax']['shipping'];

		$this->calculatedArray['priceTax']['total']-= $this->calculatedArray['priceTax']['creditpoints'];
		$this->calculatedArray['priceTax']['total']-= $this->calculatedArray['priceTax']['voucher'];

		$this->calculatedArray['priceNoTax']['total']  = $this->calculatedArray['priceNoTax']['goodstotal'];
		$this->calculatedArray['priceNoTax']['total'] += $this->calculatedArray['priceNoTax']['payment'];
		$this->calculatedArray['priceNoTax']['total'] += $this->calculatedArray['priceNoTax']['shipping'];
	}


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_basket.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_basket.php']);
}


?>
