<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Sk�rh�j (kasperYYYY@typo3.com)
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
 * @author	Kasper Sk�rh�j <kasperYYYY@typo3.com>
 * @author	Ren� Fritz <r.fritz@colorcube.de>
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
	var $personInfo;					// Set by initBasket to the billing address
	var $deliveryInfo; 					// Set by initBasket to the delivery address

	var $basketExt=array();				// "Basket Extension" - holds extended attributes
	var $giftnumber;					// current counter of the gifts

	var $itemArray;						// the items in the basket; database row, how many (quantity, count) and the price; this has replaced the former $calculatedBasket
	var $calculatedArray;				// all calculated totals from the basket e.g. priceTax and weight

	var $feuserextrafields;			// exension with additional fe_users fields
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

	/**
	 * Changes the quantitiy to integer or float
	 *
	 * @param		bool	convert to float?
	 * @param		string	quantity
	 * @return	    float or integer quantity
 	 */
	function numberQuantity($bToFloat, $quantity)	{
		$rc = '';
		if ($bToFloat)	{
			$quantity = (string) $quantity;
			// enable the German display of float
			$quantity = (float) str_replace (',', '.', $quantity);
		} else {
			$rc = (int) $quantity;
		}
		$rc = $quantity;
		
		return $rc;
	}


	function getMaxCount ($quantity)	{
		$count = 0;

		if ($this->conf['quantityIsFloat'])	{
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
		// $this->page = tx_ttproducts_page::createPageTable($this->pibase,$this->page,$pid_list,99);
			// pages
		$this->page = tx_ttproducts_page::createPageTable(
			$this->pibase,
			$this->cnf,
			$this->tt_content,
			$this->pibase->LLkey,
			$this->conf['table.']['pages'], 
			$this->conf['table.']['pages.'],
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
		$extVars = $this->pibase->piVars['variants'];
		$extVars = ($extVars ? $extVars : t3lib_div::_GP('ttp_extvars'));
		$paramProduct = strtolower($this->viewTable->marker);
		$uid = $this->pibase->piVars[$paramProduct];
		$uid = ($uid ? $uid : t3lib_div::_GP('tt_products'));
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
			while(list($uid,$basketItem) = each($basketExtRaw)) {
				$variant = $this->viewTable->variant->getVariantFromRow($basketItem);
				if (t3lib_div::testInt($uid))	{
					// quantities for single values are stored in an array. This is necessary because a HTML checkbox does not send any values if it has been unchecked  
					if (is_array($basketItem['quantity']))	{
						$basketItem['quantity'] = current($basketItem['quantity']);
					}
					$quantity = 0;
					$quantity = $this->numberQuantity($this->conf['quantityIsFloat'],$basketItem['quantity']);

					if ($this->conf['quantityIsFloat'])	{
						$this->basketExt[$uid][$variant] = $quantity;
					}
					
					if (!$updateMode) {
						$count=$this->getMaxCount ($quantity);
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
							$quantity = $this->numberQuantity($this->conf['quantityIsFloat'],$quantity);
							if (is_array($this->basketExt[$uid]))
							{
								reset($this->basketExt[$uid]);
								while(list($variant,)=each($this->basketExt[$uid])) {
									 // useArticles if you have different prices and therefore articles for color, size, additional and gradings
									if (md5($variant)==$md5) {
										$count=$this->getMaxCount ($quantity);
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
				$TSFE->fe_user->storeSessionData(); // Franz: The basket shall not get lost
			}
		}
		$this->paymentshipping->setBasketExtras($formerBasket);
		$this->personInfo = $formerBasket['personinfo'];
		$this->deliveryInfo = $formerBasket['delivery'];
		
		if ($this->conf['useStaticInfoCountry'] && $this->personInfo['country_code'])	{
			$this->personInfo['country'] = $this->pibase->staticInfo->getStaticInfoName('COUNTRIES', $this->personInfo['country_code'],'','');
			$this->deliveryInfo['country'] = $this->pibase->staticInfo->getStaticInfoName('COUNTRIES', $this->deliveryInfo['country_code'],'','');
		}

		if ($TSFE->loginUser && (!$this->personInfo || !$this->personInfo['name'] || $this->conf['editLockedLoginInfo']) && $this->conf['lockLoginUserInfo'])	{
			$address = '';
			$this->personInfo['feusers_uid'] = $TSFE->fe_user->user['uid'];

			if ($this->conf['loginUserInfoAddress']) {
				$address = implode(chr(10),
					t3lib_div::trimExplode(chr(10),
						$TSFE->fe_user->user['address'].chr(10).
						$TSFE->fe_user->user['zip'].' '.$TSFE->fe_user->user['city'].chr(10).
						($this->conf['useStaticInfoCountry'] ? $TSFE->fe_user->user['static_info_country']:$TSFE->fe_user->user['country'])
						,1)
					);
			} else {
				$address = $TSFE->fe_user->user['address'];
			}
			$this->personInfo['address'] = $address;
			$fields = 'name, first_name, last_name, email, telephone, fax, zip, city, company';
			$fields .= ',tt_products_creditpoints, tt_products_vouchercode';
			if ($this->feuserextrafields) {
				$fields .= ',tx_feuserextrafields_initials_name, tx_feuserextrafields_prefix_name, tx_feuserextrafields_gsm_tel'.
						'tx_feuserextrafields_company_deliv, tx_feuserextrafields_address_deliv, tx_feuserextrafields_housenumber'.
						'tx_feuserextrafields_housenumber_deliv, tx_feuserextrafields_housenumberadd, tx_feuserextrafields_housenumberadd_deliv'.
						'tx_feuserextrafields_pobox, tx_feuserextrafields_pobox_deliv, tx_feuserextrafields_zip_deliv, tx_feuserextrafields_city_deliv'.
						'tx_feuserextrafields_country, tx_feuserextrafields_country_deliv';				
			}
			$fieldArray = t3lib_div::trimExplode(',',$fields);
			foreach ($fieldArray as $k => $field)	{
				$this->personInfo[$field] = ($this->personInfo[$field] ? $this->personInfo[$field]: $TSFE->fe_user->user[$field]);
			}					
			$this->personInfo['country'] = ($this->personInfo['country'] ? $this->personInfo['country'] : ($this->conf['useStaticInfoCountry'] ? $TSFE->fe_user->user['static_info_country']:$TSFE->fe_user->user['country']));
			$this->personInfo['agb'] = (isset($this->personInfo['agb']) ? $this->personInfo['agb'] : $TSFE->fe_user->user['agb']);
			$this->personInfo['date_of_birth'] = date( 'd-m-Y', $TSFE->fe_user->user['date_of_birth']);
		}
		
	} // init



	/**
	 * Checks if required fields are filled in
	 */
	function checkRequired()	{
		$flag = '';
		$requiredInfoFields = trim($this->conf['requiredInfoFields']);
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
			$infoExtraFields = '';
			if ($this->feuserextrafields)	{
				$infoExtraFields = ',tx_feuserextrafields_initials_name,tx_feuserextrafields_prefix_name,' .
					'tx_feuserextrafields_gsm_tel,tx_feuserextrafields_company_deliv,' .
					'tx_feuserextrafields_address_deliv,tx_feuserextrafields_housenumber,' .
					'tx_feuserextrafields_housenumber_deliv,tx_feuserextrafields_housenumberadd,' .
					'tx_feuserextrafields_housenumberadd_deliv,tx_feuserextrafields_pobox,' .
					'tx_feuserextrafields_pobox_deliv,tx_feuserextrafields_zip_deliv,' .
					'tx_feuserextrafields_city_deliv,tx_feuserextrafields_country,' .
					'tx_feuserextrafields_country_deliv';
			}
			$infoFields = explode(',','feusers_uid,telephone,salutation,name,first_name,last_name,email,' .
				'date_of_birth,company,address,city,zip,country,country_code'.
				$infoExtraFields
			); // Fields...
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
	
					$this->viewTable->variant->getRowFromVariant ($row, $bextVars);
					$row['extVars'] = $bextVars;
					if ($this->useArticles == 1) {
						// get the article uid with these colors, sizes and gradings
						$query='uid_product=\''.intval($row['uid']).'\' AND color='.$TYPO3_DB->fullQuoteStr($row['color'],'tt_products').' AND size='.$TYPO3_DB->fullQuoteStr($row['size'],'tt_products').' AND description='.$TYPO3_DB->fullQuoteStr($row['description'],'tt_products').' AND gradings='.$TYPO3_DB->fullQuoteStr($row['gradings'],'tt_products');
						$articleRes = $TYPO3_DB->exec_SELECTquery('*', 'tt_products_articles', $query);
						if ($articleRow = $TYPO3_DB->sql_fetch_assoc($articleRes)) {
								// use the fields of the article instead of the product
							foreach ($articleRow as $field => $fieldValue) {
								if ($field != 'uid') {
									$row[$field] = $fieldValue;
								}
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
			$this->itemArray [intval($row['pid'])] [$row[$this->viewTable->fields['itemnumber']]][] = $newItem;
			$count = $newItem['count'];
			$priceTax = $newItem['priceTax'];
			$priceNoTax = $newItem['priceNoTax'];

			$this->calculatedArray['count']		+= $count;
			$this->calculatedArray['weight']	+= $row['weight']*$count;
			// if reseller is logged in then take 'price2', default is 'price'
			$price2Tax = $this->price->getPrice($row['price2'],1,$row['tax']);
			$price2NoTax = $this->price->getPrice($row['price2'],0,$row['tax']);

			$this->calculatedArray['price2Tax']['goodstotal']	+= $price2Tax * $count;
			$this->calculatedArray['price2NoTax']['goodstotal']	+= $price2NoTax * $count;

// Franz: is this needed?
//			$oldPriceTax = $this->price->getPrice($row['price'],1,$row['tax']);
//			$oldPriceNoTax = $this->price->getPrice($row['price'],0,$row['tax']);
//			/* Added Els3: if oldpricenotax is 0 -> oldpricenotax = pricenotax, otherwise price_discount doesn't calculate correctly */
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

		foreach ($productsArray as $row) {
			if ($row['bulkily'])	{
				$value = $this->conf['bulkilyAddition'] * $this->basketExt[$row['uid']][$this->viewTable->variant->getVariantFromRow($row)];
				$this->calculatedArray['priceTax']['shipping'] += $value  * (1+$this->conf['bulkilyFeeTax']/100);
				$this->calculatedArray['priceNoTax']['shipping'] += $value;
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



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_basket.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_basket.php']);
}


?>
