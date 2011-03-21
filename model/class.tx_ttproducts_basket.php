<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * basket functions for a basket object
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Els Verberne <verberne@bendoo.nl>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once(PATH_t3lib.'class.t3lib_div.php');
require_once(PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricecalc.php');
require_once(PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricetablescalc.php');
require_once(PATH_BE_ttproducts.'model/field/class.tx_ttproducts_field_graduated_price.php');
include_once (PATH_BE_ttproducts.'model/field/class.tx_ttproducts_field_creditpoints.php');


class tx_ttproducts_basket {
	public $pibase; // reference to object of pibase
	public $conf;
	public $config;

	public $paymentshippingObj; // paymentshipping object

		// Internal: initBasket():
	public $basket=array();			// initBasket() sets this array based on the registered items
	public $basketExtra;			// initBasket() uses this for additional information like the current payment/shipping methods
	public $recs = Array(); 		// in initBasket this is set to the recs-array of fe_user.
	public $basketExt=array();		// "Basket Extension" - holds extended attributes
	public $order = array(); 		// order data
	public $giftnumber;			// current counter of the gifts

	public $itemArray = array();		// the items in the basket; database row, how many (quantity, count) and the price; this has replaced the former $calculatedBasket
	public $calculatedArray = array();	// all calculated totals from the basket e.g. priceTax and weight

	public $funcTablename;		// tt_products or tt_products_articles
	public $useArticles;
	public $checkMinPrice;		// if the minimum price needs to be checked
	public $pidListObj;
	public $formulaArray;
	public $giftServiceRow;


	public function getCalculatedArray ()	{
		return $this->calculatedArray;
	}


	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */
	public function init (
		&$pibase,
		&$formerBasket,
		$updateMode,
		$pid_list,
		$useArticles,
		$bStoreBasket
	)	{
		global $TSFE;

		$this->pibase = &$pibase;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->recs = $formerBasket;	// Sets it internally
		$this->basket = array();
		$this->itemArray = array();
		$this->paymentshippingObj = &t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
		$this->pidListObj = &t3lib_div::getUserObj('tx_ttproducts_pid_list');
		$this->pidListObj->init($pibase->cObj);
		$this->pidListObj->applyRecursive(99, $pid_list, TRUE);
		$this->pidListObj->setPageArray();
		$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');

		$this->useArticles = $useArticles;
		if ($this->useArticles == 2)	{
			$funcTablename = 'tt_products_articles';
		} else {
			$funcTablename = 'tt_products';
		}
		$this->setFuncTablename ($funcTablename);
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$viewTableObj = &$tablesObj->get($funcTablename);
		$tmpBasketExt = $TSFE->fe_user->getKey('ses','basketExt');
		$this->order = $TSFE->fe_user->getKey('ses','order');

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
		$paramProduct = strtolower($viewTableObj->marker);
		$uid = $this->pibase->piVars[$paramProduct];
		$uid = ($uid ? $uid : t3lib_div::_GP('tt_products'));
		$sameGiftData = TRUE;
		$identGiftnumber = 0;

		$addMemo = $this->pibase->piVars['addmemo'];
		if ($addMemo)	{
			$basketExtRaw = '';
			$newGiftData = '';
		}

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
						$sameGiftData = TRUE;
						foreach ($rec as $field => $value) {
							// only the 'field' field can be different
							if ($field != 'item' && $field != 'note' && $value != $newGiftData[$field]) {
								$sameGiftData = FALSE;
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
					$sameGiftData = FALSE;
				}
				if (!$sameGiftData) {
					$this->basketExt['gift'][$this->giftnumber] = $newGiftData;
				}
			}
		}

		if (is_array($basketExtRaw)) {
			// while(list($uid,$basketItem) = each($basketExtRaw)) {
			foreach ($basketExtRaw as $uid => $basketItem)	{
				if (t3lib_div::testInt($uid))	{
					$variant = $viewTableObj->variant->getVariantFromRawRow($basketItem);
					$oldcount = $this->basketExt[$uid][$variant];
					$damUid = intval($basketExtRaw['dam']);
					if ($damUid)	{
						$tableVariant = $viewTableObj->variant->getTableUid ('tx_dam', $damUid);
						$variant .= $tableVariant;
					}

					// quantities for single values are stored in an array. This is necessary because a HTML checkbox does not send any values if it has been unchecked
					if (isset($basketItem['quantity']) && is_array($basketItem['quantity']))	{
						reset ($basketItem['quantity']);
						$basketItem['quantity'] = current($basketItem['quantity']);
					}

// 					if ($this->conf['quantityIsFloat'])	{
// 						$this->basketExt[$uid][$variant] = $quantity;
// 					}

					if (!$updateMode) {
						if (!isset($basketItem['quantity']))	{
							continue;
						}

						$quantity = 0;
						$quantity = $priceObj->toNumber($this->conf['quantityIsFloat'],$basketItem['quantity']);

						$count = $this->getMaxCount ($quantity, $uid);
						if ($count >= 0) {
							$newcount = $count;
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
								if ($this->conf['alwaysUpdateOrderAmount'] == 1)	{
									$this->basketExt[$uid][$variant] = $newcount;
								} else {
									$this->basketExt[$uid][$variant] = $oldcount + $newcount;
								}
							} else {
								unset ($this->basketExt[$uid][$variant]);
							}
						}
					} else {
						foreach($basketItem as $md5 => $quantity) {
							$quantity = $priceObj->toNumber($this->conf['quantityIsFloat'],$quantity);

							if (is_array($this->basketExt[$uid]) && $md5 != 'additional')	{
								foreach($this->basketExt[$uid] as $variant => $tmp) {
									$actMd5 = md5($variant);
										// useArticles if you have different prices and therefore articles for color, size, additional and gradings
									if ($actMd5==$md5) {
										$count=$this->getMaxCount ($quantity, $uid);
										$this->basketExt[$uid][$variant] = $count;

										if (isset($basketItem['additional']) && is_array($basketItem['additional']) &&
										isset ($basketItem['additional'][$actMd5]['giftservice']) && is_array($basketItem['additional'][$actMd5]['giftservice']))	{
											if (isset($this->basketExt[$uid][$variant.'.']) && !is_array($this->basketExt[$uid][$variant.'.']))	{
												$this->basketExt[$uid][$variant.'.'] = array();
											}
											if (isset($this->basketExt[$uid][$variant.'.']['additional']) && !is_array($this->basketExt[$uid][$variant.'.']['additional']))	{
												$this->basketExt[$uid][$variant.'.']['additional'] = array();
											}
											$bHasGiftService = $basketItem['additional'][$actMd5]['giftservice']['1'];
											if ($bHasGiftService)	{
												$this->basketExt[$uid][$variant.'.']['additional']['giftservice'] = '1';
											} else {
												unset ($this->basketExt[$uid][$variant.'.']);
											}
										}

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

			// I did not find another possibility to delete elements completely from a multidimensional array
			// than to recreate the array
			$basketExtNew = array();
			foreach($this->basketExt as $tmpUid => $tmpSubArr) {
				if (is_array($tmpSubArr) && count($tmpSubArr))	{
					foreach($tmpSubArr as $tmpExtVar => $tmpCount) {
						if (t3lib_div::testInt($tmpCount) && $tmpCount > 0) {
							$basketExtNew[$tmpUid][$tmpExtVar] = $this->basketExt[$tmpUid][$tmpExtVar];
							if (isset($this->basketExt[$tmpUid][$tmpExtVar.'.']) && is_array($this->basketExt[$tmpUid][$tmpExtVar.'.']))	{
								$basketExtNew[$tmpUid][$tmpExtVar.'.'] = $this->basketExt[$tmpUid][$tmpExtVar.'.'];
							}
						} else if (is_array($tmpCount))	{
							$basketExtNew[$tmpUid][$tmpExtVar] = $tmpCount;
						} else {
							// nothing
						}
					}
				} else {
					$basketExtNew[$tmpUid] = $tmpSubArr;
				}
			}
			$this->basketExt = $basketExtNew;

			if ($bStoreBasket)	{
				if (is_array($this->basketExt) && count($this->basketExt))	{
					$TSFE->fe_user->setKey('ses','basketExt',$this->basketExt);
				} else {
					$TSFE->fe_user->setKey('ses','basketExt',array());
				}
				$TSFE->fe_user->storeSessionData(); // The basket shall not get lost when coming back from external scripts
			}
		}
		$this->paymentshippingObj->setBasketExtras($formerBasket);
	} // init


	public function &getItemArray ()	{
		return $this->itemArray;
	}


	public function &getPidListObj ()	{
		return $this->pidListObj;
	}


	public function setFuncTablename ($funcTablename)	{
		$this->funcTablename = $funcTablename;
	}


	public function getFuncTablename ()	{
		return $this->funcTablename;
	}


	/**
	 * Removes a gift from the basket
	 *
	 * @param		int		 index of the gift
	 * @param 		int			uid of the product
	 * @param		string		variant of the product
	 * @return	  void
 	 */
	public function removeGift ($giftnumber, $uid, $variant) {
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


	public function getMaxCount ($quantity, $uid = 0)	{
		$count = 0;

		if ($this->conf['basketMaxQuantity'] == 'inStock' && !$this->conf['alwaysInStock'] && !empty($uid)) {
			$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
			$viewTableObj = &$tablesObj->get('tt_products');
			$row = $viewTableObj->get($uid);
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
	 * Returns a clear 'recs[tt_products]' array - so clears the basket.
	 */
	public function getClearBasketRecord ()	{
			// Returns a basket-record cleared of tt_product items
		unset($this->recs['tt_products']);
		unset($this->recs['personinfo']);
		unset($this->recs['delivery']);
		unset($this->recs['creditcard']);
		unset($this->recs['account']);
		return ($this->recs);
	} // getClearBasketRecord


	/**
	 * Empties the shopping basket!
	 */
	public function clearBasket ($bForce=FALSE)	{
		global $TSFE;

		if ($this->conf['debug'] != '1' || $bForce)	{
			// TODO: delete only records from relevant pages
				// Empties the shopping basket!
			$TSFE->fe_user->setKey('ses','recs',$this->getClearBasketRecord());
			$TSFE->fe_user->setKey('ses','basketExt',array());
			unset($this->itemArray);
			unset($this->basketExt);
			unset($this->order);
		}

		$TSFE->fe_user->setKey('ses','ac',array());
		$TSFE->fe_user->setKey('ses','cc',array());
		$TSFE->fe_user->setKey('ses','cp',array());
		$TSFE->fe_user->setKey('ses','vo',array());
	} // clearBasket


	/**
	 * This calculates the totals. Very important function.
	This function also calculates the internal arrays

	$this->itemArray	The basked elements, how many (quantity, count) and the price
	$this->calculatedArray	- Sums of goods, shipping, payment and total amount WITH TAX included

	... which holds the total amount, the final list of products and the price of payment and shipping!!

	 */
	public function getCalculatedBasket ()	{
		global $TYPO3_DB, $TSFE;

// error_reporting(E_ALL); // +++
// 		if (isset($this->itemArray[0])) {// the item array contains all the data for the elements found in the basket
// 			return;	// this function is called in a loop from basketView->getView
// 				// all the calculations however need to be done only once
// 				// the global member variables must already be filled in
// 		}

		$uidArr = array();

		foreach($this->basketExt as $uidTmp => $v)	{
			if ($uidTmp != 'gift' && !in_array($uidTmp, $uidArr))	{
				$uidArr[] = intval($uidTmp);
			}
		}

		if (count($uidArr) == 0) {
			return;
		}

		$funcTablename = $this->getFuncTablename();
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$viewTableObj = &$tablesObj->get($funcTablename);
		$where = 'uid IN ('.implode(',',$uidArr).') AND pid IN ('. $this->pidListObj->getPidlist().')'.$viewTableObj->getTableObj()->enableFields();
		$rcArray = $viewTableObj->getWhere($where);
		$productsArray = array();
		$this->extTableItemArray = array();

		$prodCount = 0;
		$bAddGiftService = FALSE;

		foreach ($rcArray as $uid => $row)	{
			$pid = $row['pid'];
			$uid = $row['uid'];
			$isValidPage = $this->pidListObj->getPageArray($pid);
			// only the basket items for the pages belonging to this shop shall be used here
			if ($isValidPage)	{
				foreach ($this->basketExt[$uid] as $bextVarLine => $bRow)	{
					if (substr($bextVarLine,-1) == '.')	{
						// this is an additional array which is no basket item
						if ($this->conf['whereGiftService'])	{
							$bAddGiftService = TRUE;
						}
						continue;
					}
				// while(list($bextVars,)=each($this->basketExt[$row['uid']])) {
					$bextVarArray = t3lib_div::trimExplode('|', $bextVarLine);
					$bextVars = $bextVarArray[0];
					$currRow = $row;
					$viewTableObj->variant->modifyRowFromVariant($currRow, $bextVars);
					$extTable = $funcTablename;
					$extUid = $uid;
					$currRow['ext'][$extTable][] = array('uid' => $extUid, 'vars' => $bextVars);
					if ($bextVarArray[1] == 'tx_dam' && $bextVarArray[2])	{
						if (!is_array($this->extTableItemArray['tx_dam']))	{
							$this->extTableItemArray['tx_dam'] = array();
						}
// 						$damUid = intval($bextVarArray[2]);
/*						$damRow = $this->tx_dam->get($damUid);
						$this->extTableItemArray['tx_dam'][$damUid] = $damRow;*/
						$extTable = $bextVarArray[1];
						$extUid = intval($bextVarArray[2]);
						$damObj = &$tablesObj->get('tx_dam');
						$damObj->modifyItemRow ($currRow, $extUid);
						$currRow['ext'][$extTable][] = array('uid' => $extUid);
					}

					if ($this->useArticles == 1 && $funcTablename == 'tt_products') {
						// get the article uid with these colors, sizes and gradings
						$articleRow = $viewTableObj->getArticleRow($currRow, 'BASKET');

							// use the fields of the article instead of the product
						$viewTableObj->mergeAttributeFields($currRow, $articleRow, FALSE, TRUE);
						$currRow['ext']['tt_products_articles'][] = array('uid' => $articleRow['uid']);
					} else if ($this->useArticles == 2)	{
						$productRow = $viewTableObj->getProductRow($currRow);
						$viewTableObj->mergeAttributeFields($currRow, $productRow, TRUE);
					}
					$productsArray[$prodCount++] = $currRow;
				}
			}
		}

		if ($bAddGiftService)	{
			$where = $this->conf['whereGiftService'].' AND pid IN ('. $this->pidListObj->getPidlist().')'.$viewTableObj->getTableObj()->enableFields();
			$giftServiceArray = $viewTableObj->getWhere($where);
			if (isset($giftServiceArray) && is_array($giftServiceArray))	{
				reset ($giftServiceArray);
				$this->giftServiceRow = current($giftServiceArray);
				if (isset($this->giftServiceRow) && is_array($this->giftServiceRow))	{
					$productsArray[$prodCount++] = $this->giftServiceRow;
				}
			}
		}

		$this->itemArray = array(); // array of the items in the basket
		$this->calculatedArray = array(); // this array is usede for all calculated things
		$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$maxTax = 0;

		foreach ($productsArray as $k1 => $row)	{
			$uid = $row['uid'];
			$tax = $row['tax'];
			if ($tax > $maxTax)	{
				$maxTax = $tax;
			}

			// $variant = $viewTableObj->variant->getVariantFromRow($row);
			$newItem = $this->getItem($row, 'useExt');
			$count = $newItem['count'];

			if($count > 0)	{
				$weight = $newItem['weight'];
				$this->itemArray[$row[$viewTableObj->fields['itemnumber']]][] = $newItem;
				$this->calculatedArray['count']		+= $count;
				$this->calculatedArray['weight']	+= $weight * $count;
			}

			// if reseller is logged in then take 'price2', default is 'price'
		}

		if (count($this->itemArray))	{

			if ($this->conf['discountprice.'])	{
				$getDiscount = 0;
				$gr_list = explode (',' , $TSFE->gr_list);
				if ($this->conf['getDiscountPrice']) {
					$getDiscount = 1;
				} else {
					foreach ($gr_list as $k1 => $val) {
						if (((float) $val > 0) && ($getDiscount == 0)) {
							$getDiscount = 1 - strcmp($TSFE->fe_user->groupData->title, $this->conf['discountGroupName'] );

							if (strlen($TSFE->fe_user->groupData['title']) == 0)	// repair result of strcmp
								$getDiscount = 0;
						}
					}
				}

				$priceTotal = array();
				$priceReduction = array();
				$additive = 0;
				// Check if a special group price can be used
				if ($getDiscount == 1)	{
					include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_discountprice.php');
					$discountPrice = t3lib_div::makeInstance('tx_ttproducts_discountprice');
					$discountPrice->getCalculatedData($this->itemArray, $this->conf['discountprice.'], 'calcprice', $priceReduction, '');
				}
			}

			// set the 'calcprice' in itemArray
			if ($this->conf['pricecalc.']) {
				$pricecalc = t3lib_div::makeInstance('tx_ttproducts_pricecalc');

				// do the price calculation
				$pricecalc->getCalculatedData($this->itemArray, $this->conf['pricecalc.'], 'calcprice', $priceReduction, '');
			}

			$pricetablescalc = &t3lib_div::getUserObj('&tx_ttproducts_pricetablescalc');
			$pricetablescalc->init($this->pibase);
			$pricetablescalc->getCalculatedData($this->itemArray, $tmp='', 'calcprice', $priceReduction, '');

			$this->calculatedArray['priceTax'] = array();
			$this->calculatedArray['priceNoTax'] = array();
			$this->calculatedArray['price0Tax'] = array();
			$this->calculatedArray['price0NoTax']['total'] = array();

			if ($this->conf['TAXmode'] == '1')	{
				$this->calculatedArray['priceNoTax']['goodssametaxtotal'] = array();
				$this->calculatedArray['price2NoTax']['goodssametaxtotal'] = array();
				$this->calculatedArray['price0NoTax']['goodssametaxtotal'] = array();
				$this->calculatedArray['categoryPriceNoTax']['goodssametaxtotal'] = array();
			}

			$this->calculatedArray['priceNoTax']['sametaxtotal'] = array();
			$this->calculatedArray['price2NoTax']['sametaxtotal'] = array();
			$this->calculatedArray['price0NoTax']['sametaxtotal'] = array();
			$this->calculatedArray['categoryPriceNoTax']['sametaxtotal'] = array();

			$shippingTax = $this->conf['shipping.']['TAXpercentage'];
			if ($shippingTax > $maxTax)	{
				$maxTax = $shippingTax;
			} else if ($shippingTax == '')	{
				$shippingTax = $maxTax;
			}
			$bulkilyFeeTax = floatval($this->conf['bulkilyFeeTax']);

			// loop over all items in the basket indexed by a sort string
			foreach ($this->itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {
					$row = &$actItem['rec'];
					$count = $actItem['count'];
					$tax = doubleval($row['tax']);
					if (!$tax)	{
						$tax = doubleval($this->conf['TAXpercentage']);
					}

					$priceTax = $actItem['priceTax'];
					$priceNoTax = $actItem['priceNoTax'];
					$price0Tax = $actItem['price0Tax'];
					$price0NoTax = $actItem['price0NoTax'];
					$price2Tax = $actItem['price2Tax'];
					$price2NoTax = $actItem['price2NoTax'];

					// has the price been calculated before take it if it gets cheaper now
					if (($actItem['calcprice'] > 0) && ($actItem['calcprice'] < $actItem['priceTax'])) {
						$this->itemArray[$sort][$k1]['priceTax'] = $priceObj->getModePrice($this->conf['TAXmode'],$actItem['calcprice'],TRUE,$tax,$this->conf['TAXincluded']);
						$this->itemArray[$sort][$k1]['priceNoTax'] = $priceObj->getModePrice($this->conf['TAXmode'],$actItem['calcprice'],FALSE,$tax,$this->conf['TAXincluded']);
					}

					//  multiplicate it with the count :
					$this->itemArray[$sort][$k1]['totalNoTax'] = $this->itemArray[$sort][$k1]['priceNoTax'] * $count;
					$this->itemArray[$sort][$k1]['total0NoTax'] = $this->itemArray[$sort][$k1]['price0NoTax'] * $count;
					$this->calculatedArray['price0NoTax']['goodstotal'] += $this->itemArray[$sort][$k1]['total0NoTax'];
					$this->calculatedArray['priceNoTax']['goodstotal'] += $this->itemArray[$sort][$k1]['totalNoTax'];
					$this->calculatedArray['categoryPriceNoTax']['goodstotal'][$row['category']]+= $this->itemArray[$sort][$k1]['totalNoTax'];
					$this->calculatedArray['price2NoTax']['goodstotal'] += $price2NoTax * $count;

					$this->calculatedArray['noDiscountPriceTax']['goodstotal']  += $priceObj->getPrice($row['price'] * $actItem['count'], TRUE, $this->conf['TAXpercentage']);
					$this->calculatedArray['noDiscountPriceNoTax']['goodstotal'] += $priceObj->getPrice($row['price'] * $actItem['count'], FALSE, $this->conf['TAXpercentage']);
					$this->calculatedArray['noDiscountPriceTax']['goodstotal']  += $priceObj->getPrice($row['price2'] * $actItem['count'], TRUE, $this->conf['TAXpercentage']);
					$this->calculatedArray['noDiscountPriceNoTax']['goodstotal'] += $priceObj->getPrice($row['price2'] * $actItem['count'], FALSE, $this->conf['TAXpercentage']);


					if ($this->conf['TAXmode'] == '1')	{
						$taxstr = strval(number_format($tax,2)); // needed for floating point taxes as in Swizzerland
						$this->itemArray[$sort][$k1]['totalTax'] = $priceObj->getPrice($this->itemArray[$sort][$k1]['totalNoTax'],TRUE,$tax,FALSE);
						$this->itemArray[$sort][$k1]['total0Tax'] = $priceObj->getPrice($this->itemArray[$sort][$k1]['total0NoTax'],TRUE,$tax,FALSE);
						$this->calculatedArray['priceNoTax']['goodssametaxtotal'][$taxstr] +=  $this->itemArray[$sort][$k1]['totalNoTax'];
						$this->calculatedArray['price2NoTax']['goodssametaxtotal'][$taxstr] += $this->itemArray[$sort][$k1]['totalNoTax'];
						$this->calculatedArray['categoryPriceNoTax']['goodssametaxtotal'][$taxstr][$row['category']] +=  $this->itemArray[$sort][$k1]['totalNoTax'];
						$this->calculatedArray['price0NoTax']['goodssametaxtotal'][$taxstr] +=  $this->itemArray[$sort][$k1]['total0NoTax'];
					} else if ($this->conf['TAXmode'] == '2')	{
						$this->itemArray[$sort][$k1]['totalTax'] = $this->itemArray[$sort][$k1]['priceTax'] * $count;
						$this->itemArray[$sort][$k1]['total0Tax'] = $this->itemArray[$sort][$k1]['price0Tax'] * $count;

							// Fills this array with the product records. Reason: Sorting them by category (based on the page, they reside on)
						$this->calculatedArray['priceTax']['goodstotal'] += $this->itemArray[$sort][$k1]['totalTax'];
						$this->calculatedArray['price0Tax']['goodstotal'] += $this->itemArray[$sort][$k1]['total0Tax'];

						$this->calculatedArray['categoryPriceTax']['goodstotal'][$row['category']]+= $this->itemArray[$sort][$k1]['totalTax'];

						$this->calculatedArray['price2Tax']['goodstotal']	+= $price2Tax * $count;

						$value = $row['handling'];
						$this->calculatedArray['priceTax']['handling'] += $priceObj->getModePrice($this->conf['TAXmode'],$value,TRUE,$shippingTax,$this->conf['TAXincluded'],TRUE);
						$value = $row['shipping'];
						$this->calculatedArray['priceTax']['shipping'] += $priceObj->getModePrice($this->conf['TAXmode'],$value*($count-1),TRUE,$shippingTax,$this->conf['TAXincluded'],TRUE);

						$value = $row['shipping2'];
						if ($count > 1)	{
							$this->calculatedArray['priceTax']['shipping'] += $priceObj->getModePrice($this->conf['TAXmode'],$value*($count-1),TRUE,$shippingTax,$this->conf['TAXincluded'],TRUE);
						}
					}

					$value = $row['handling'];
					$this->calculatedArray['priceNoTax']['handling'] += $priceObj->getModePrice($this->conf['TAXmode'],$value,FALSE,$shippingTax,$this->conf['TAXincluded'],TRUE);

					$value = $row['shipping'];
					$this->calculatedArray['priceNoTax']['shipping'] += $priceObj->getModePrice($this->conf['TAXmode'],$value*($count-1),FALSE,$shippingTax,$this->conf['TAXincluded'],TRUE);

					$value = $row['shipping2'];
					if ($count > 1)	{
						$this->calculatedArray['priceNoTax']['shipping'] += $priceObj->getModePrice($this->conf['TAXmode'],$value*($count-1),FALSE,$shippingTax,$this->conf['TAXincluded'],TRUE);
					}
				}
			}

			$this->calculatedArray['maxtax']['goodstotal'] = $maxTax;

			foreach ($productsArray as $row) {	// TODO: remove this because it has been moved to the shipping configuration
				if ($row['bulkily'])	{
					$value = floatval($this->conf['bulkilyAddition']) * $this->basketExt[$row['uid']][$viewTableObj->variant->getVariantFromRow($row)];
					$tax = ($bulkilyFeeTax != '' ? $bulkilyFeeTax : $shippingTax);
					$this->calculatedArray['priceTax']['shipping'] += $priceObj->getModePrice($this->conf['TAXmode'],$value,TRUE,$tax,$this->conf['TAXincluded'],TRUE);
					$this->calculatedArray['priceNoTax']['shipping'] += $priceObj->getModePrice($this->conf['TAXmode'],$value,FALSE,$tax,$this->conf['TAXincluded'],TRUE);
				}
			}

			if ($this->conf['TAXmode'] == '1')	{
				$controlCalcArray = array('priceTax' => 'priceNoTax', 'price0Tax' => 'price0NoTax', 'price2Tax' => 'price2NoTax');

				foreach ($controlCalcArray as $keyTax => $keyNoTax)	{
					$priceTax = 0;
					foreach ($this->calculatedArray[$keyNoTax]['goodssametaxtotal'] as $tax => $value)	{
						$newPriceTax = $priceObj->getModePrice($this->conf['TAXmode'],$value,TRUE,$tax,FALSE);
						$priceTax += $newPriceTax;

					}
					$this->calculatedArray[$keyNoTax]['sametaxtotal'] = $this->calculatedArray[$keyNoTax]['goodssametaxtotal'];
					$this->calculatedArray[$keyTax]['goodstotal'] = $priceTax;
					// $this->calculatedArray['priceTax']['total'] += $priceTax;
				}

				$controlCatCalcCatArray = array('categoryPriceTax' => 'categoryPriceNoTax');
				foreach ($controlCatCalcCatArray as $keyTax => $keyNoTax)	{
					$priceTaxArray = array();
					$priceTax = 0;
					foreach ($this->calculatedArray[$keyNoTax]['goodssametaxtotal'] as $tax => $catArray)	{
						if (is_array($catArray))	{
							foreach ($catArray as $cat => $value)	{
								$newPriceTax = $priceObj->getModePrice($this->conf['TAXmode'],$value,TRUE,$tax,FALSE);
								$priceTax += $newPriceTax;
								$priceTaxArray [$cat] = $newPriceTax;
							}
						}
					}
					$this->calculatedArray[$keyTax]['goodstotal'] = $priceTaxArray;
					// $this->calculatedArray['priceTax']['total'] += $priceTax;
				}

				$this->calculatedArray['priceTax']['handling'] = $priceObj->getModePrice($this->conf['TAXmode'],$this->calculatedArray['priceNoTax']['handling'],TRUE,$shippingTax,FALSE,FALSE);
				$this->calculatedArray['priceTax']['shipping'] = $priceObj->getModePrice($this->conf['TAXmode'],$this->calculatedArray['priceNoTax']['shipping'],TRUE,$shippingTax,FALSE,FALSE);
			}
		}
		$paymentTax = $this->paymentshippingObj->getTaxPercentage('payment');

			// Shipping must be at the end in order to use the calculated values from before
		$this->paymentshippingObj->getPaymentShippingData(
			$this->calculatedArray['count'],
			$this->calculatedArray['priceTax']['goodstotal'],
			$shippingTax,
			$paymentTax,
			$this->calculatedArray['priceTax']['shipping'],
			$this->calculatedArray['priceNoTax']['shipping'],
			$this->calculatedArray['priceTax']['payment'],
			$this->calculatedArray['priceNoTax']['payment']
		);

		if ($shippingTax)	{
			$this->calculatedArray['priceNoTax']['sametaxtotal'][strval($shippingTax)] += $this->calculatedArray['priceNoTax']['shipping'];
		}
	} // getCalculatedBasket


	public function isInBasket ($prod_uid)	{
		$rc = FALSE;
		if (count($this->itemArray))	{
			// loop over all items in the basket indexed by a sort string
			foreach ($this->itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {
					$row = &$actItem['rec'];
					if ($prod_uid == $row['uid'])	{
						$rc = TRUE;
						break;
					}
				}
				if ($rc == TRUE)	{
					break;
				}
			}
		}
		return $rc;
	}


	// get gradutated prices for all products in a list view or a single product in a single view
	function getGraduatedPrices ($uid)	{
		$graduatedPriceObj = &t3lib_div::getUserObj('&tx_ttproducts_graduated_price');
		$this->formulaArray = $graduatedPriceObj->getFormulasByProduct ($uid);
	}


	public function get ($uid, $variant)	{
		$rc = array();
		foreach ($this->itemArray as $sort => $actItemArray) {
			foreach ($actItemArray as $k1 => $actItem) {
				$row = &$actItem['rec'];
				if (
					$row['uid'] == $uid &&
					isset($row['ext']) &&
					is_array($row['ext']) &&
					isset($row['ext']['tt_products']) &&
					is_array($row['ext']['tt_products'])
				) 	{
					$extVarArray = $row['ext']['tt_products'][0];
					if ($extVarArray['uid'] == $uid && $extVarArray['vars'] == $variant)	{
						$rc = $row;
					}
				}
			}
		}
		return $rc;
	}


	public function &getItem ($row, $fetchMode, $funcTablename='') {
		global $TSFE;

		$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$discount = $TSFE->fe_user->user['tt_products_discount'];

		if (!$funcTablename)	{
			$funcTablename = $this->getFuncTablename();
		}
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$viewTableObj = &$tablesObj->get($funcTablename);
		$count = 0;
		$tax = $row['tax'];
		$priceRow = array();
		$priceRow['price'] = $priceObj->getDiscountPrice($row['price'], $discount);
		$priceRow['price2'] = $row['price2'];
		$priceRow['tax'] = $row['tax'];
		$priceRow['unit_factor'] = $row['unit_factor'];
		$priceRow['weight'] = $row['weight'];
		$priceRow['inStock'] = $row['inStock'];

		if ($fetchMode == 'useExt')	{
			$variant = $viewTableObj->variant->getVariantFromRow($row);

			if (
				in_array($this->useArticles, array(1,3)) &&
				$funcTablename == 'tt_products' &&
				isset($row['ext']['tt_products_articles']) &&
				is_array($row['ext']['tt_products_articles'])
			) {
				$articleObj = &$tablesObj->get('tt_products_articles');
				reset($row['ext']['tt_products_articles']);
				$articleInfo = current ($row['ext']['tt_products_articles']);
				$articleUid = $articleInfo['uid'];

				if (isset($articleUid))	{
					$articleRow = $articleObj->get($articleUid);
					$articleObj->mergeAttributeFields($priceRow, $articleRow, FALSE,TRUE);
				}

				if ($articleRow)	{
					$priceRow['weight'] = ($articleRow['weight'] ? $articleRow['weight'] : $row['weight']);
					$priceRow['inStock'] = $articleRow['inStock'];
				}
			}
		} else if ($fetchMode == 'rawRow') {
			$variant = $viewTableObj->variant->getVariantFromRawRow($row);
		} else if ($fetchMode == 'firstVariant') {
			$variant = $viewTableObj->variant->getVariantFromProductRow($row, 0);
		}
		$extArray = $row['ext'];
		if (is_array($extArray['tx_dam']))	{
			reset ($extArray['tx_dam']);
			$firstDam = current($extArray['tx_dam']);
			$extUid = $firstDam['uid'];
			$tableVariant = $viewTableObj->variant->getTableUid('tx_dam', $extUid);
			$variant .= $tableVariant;
		}

		if (isset($this->basketExt[$row['uid']]) && is_array($this->basketExt[$row['uid']]))	{
			$count = $this->basketExt[$row['uid']][$variant];
		}
		if (!$count && is_array($this->giftServiceRow) && $row['uid'] == $this->giftServiceRow['uid'])	{
			$count = 1;
		}

		if ($count > $priceRow['inStock'] && !$this->conf['alwaysInStock'])	{
			$count = $priceRow['inStock'];
		}
		if (!$this->conf['quantityIsFloat'])	{
			$count = intval($count);
		}
		$priceTax = $priceObj->getResellerPrice($priceRow,1);
		$priceNoTax = $priceObj->getResellerPrice($priceRow,0);
		if ($priceNoTax > $priceTax)	{
			$priceNoTax = $priceTax;
		}
		$price0Tax = $priceObj->getResellerPrice($priceRow,1,0);
		$price0NoTax = $priceObj->getResellerPrice($priceRow,0,0);
		$price2Tax = $priceObj->getPrice($priceRow['price2'],1,$tax,$this->conf['TAXincluded']);
		$price2NoTax = $priceObj->getPrice($priceRow['price2'],0,$tax,$this->conf['TAXincluded']);

		$priceUnitNoTax = $priceObj->getPrice(($priceRow['unit_factor'] > 0 ? ($priceNoTax / $priceRow['unit_factor']) : 0),FALSE,$priceRow['tax'],FALSE);
		$priceUnitTax = $priceObj->getPrice($priceUnitNoTax,TRUE,$row['tax'],FALSE);
		$priceWeightNoTax = $priceObj->getPrice(($priceRow['weight'] > 0 ? ($priceNoTax / $priceRow['weight']) : 0),FALSE,$priceRow['tax'],FALSE);
		$priceWeightTax = $priceObj->getPrice($priceWeightNoTax,TRUE,$priceRow['tax'],FALSE);
		$item = array (
			'calcprice' => 0,
			'count' => $count,
			'priceTax' => $priceTax,
			'priceNoTax' => $priceNoTax,
			'priceUnitTax' => $priceUnitTax,
			'priceUnitNoTax' => $priceUnitNoTax,
			'priceWeightUnitNoTax' => $priceWeightNoTax,
			'priceWeightUnitTax' => $priceWeightTax,
			'price0Tax' => $price0Tax,
			'price0NoTax' => $price0NoTax,
			'totalTax' => 0,
			'totalNoTax' => 0,
			'rec' => $row,
			'weight' => $priceRow['weight'],
			'inStock' => $priceRow['inStock'],
		);

		if ($this->conf['TAXmode'] == '2')	{
			$taxFieldArray = array('priceTax', 'priceNoTax', 'price0Tax', 'price0NoTax', 'price2Tax', 'price2NoTax');
			foreach ($taxFieldArray as $k => $field)	{
				$item[$field] = round($item[$field], 2);
			}
		}

		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['changeBasketItem']) &&
			is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['changeBasketItem'])
		) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['changeBasketItem'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'changeBasketItem')) {
					$hookObj->changeBasketItem($row, $fetchMode, $funcTablename, $item);
				}
			}
		}

		return $item;
	} // getItem


	// This calculates the total for everything in the basket
	public function getCalculatedSums () {
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$voucher = $tablesObj->get('voucher');
		$voucherAmount = $voucher->getAmount();
		$pricefactor = doubleval($this->conf['creditpoints.']['priceprod']);
		$creditpointsObj = &t3lib_div::getUserObj('&tx_ttproducts_field_creditpoints');
		$autoCreditpointsTotal = $creditpointsObj->getBasketTotal();
		if ($autoCreditpointsTotal > 0)	{
			$creditpoints = $autoCreditpointsTotal;
		} else {
			$creditpoints = $recs['tt_products']['creditpoints'];
		}
		$this->calculatedArray['priceTax']['creditpoints'] = $this->calculatedArray['priceNoTax']['creditpoints'] = $creditpointsObj*$pricefactor;

		$this->calculatedArray['priceNoTax']['total']  = $this->calculatedArray['priceNoTax']['goodstotal'];
		$this->calculatedArray['priceNoTax']['total'] += $this->calculatedArray['priceNoTax']['payment'];
		$this->calculatedArray['priceNoTax']['total'] += $this->calculatedArray['priceNoTax']['shipping'];
		$this->calculatedArray['priceNoTax']['vouchertotal'] = $this->calculatedArray['priceNoTax']['total'] - $voucherAmount - $this->calculatedArray['priceNoTax']['creditpoints'];
		$this->calculatedArray['price0NoTax']['total']  = $this->calculatedArray['price0NoTax']['goodstotal'];
		$this->calculatedArray['price0Tax']['total']  = $this->calculatedArray['price0Tax']['goodstotal'];

		$this->calculatedArray['priceTax']['total']  = $this->calculatedArray['priceTax']['goodstotal'];
		$this->calculatedArray['priceTax']['total'] += $this->calculatedArray['priceTax']['payment'];
		$this->calculatedArray['priceTax']['total'] += $this->calculatedArray['priceTax']['shipping'];
		$this->calculatedArray['priceTax']['vouchertotal'] = $this->calculatedArray['priceTax']['total'] - $voucherAmount - $this->calculatedArray['priceTax']['creditpoints'];
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basket.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basket.php']);
}


?>
