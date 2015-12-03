<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Kasper Skårhøj <kasperYYYY@typo3.com>
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

// require_once (PATH_t3lib.'class.tx_div2007_core.php');
// require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricecalc.php');
// require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricetablescalc.php');
// require_once (PATH_BE_ttproducts.'model/field/class.tx_ttproducts_field_graduated_price.php');
// require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_pid_list.php');
// require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_basketitem.php');
// include_once (PATH_BE_ttproducts.'model/field/class.tx_ttproducts_field_creditpoints.php');


class tx_ttproducts_basket {
	public $conf;
	public $config;

	public $paymentshippingObj; // paymentshipping object

		// Internal: initBasket():
	public $basket=array();			// initBasket() sets this array based on the registered items
	public $basketExtra;			// initBasket() uses this for additional information like the current payment/shipping methods
	public $recs = array(); 		// in initBasket this is set to the recs-array of fe_user.
	public $basketExt=array();		// "Basket Extension" - holds extended attributes
	public $order = array(); 		// order data
	public $giftnumber;			// current counter of the gifts

	public $itemArray = array();		// the items in the basket; database row, how many (quantity, count) and the price; this has replaced the former $calculatedBasket
	public $calculatedArray = array();	// all calculated totals from the basket e.g. priceTax and weight

	public $funcTablename;			// tt_products or tt_products_articles
	public $useArticles;
	public $checkMinPrice;			// if the minimum price needs to be checked
	protected $pidListObj;
	public $formulaArray;
	public $giftServiceRow;
	protected $itemObj;


	public function getCalculatedArray ()	{
		return $this->calculatedArray;
	}


	static public function getGoodsTotalTax ($basketExtra, $itemArray) {
		$priceObj = t3lib_div::getUserObj('&tx_ttproducts_field_price');

		$goodsTotalTax = 0;

		foreach ($itemArray as $sort => $actItemArray) {
			foreach ($actItemArray as $k1 => $actItem) {

				$row = $actItem['rec'];
				$count = $actItem['count'];
				$tax = $actItem['tax'];
				$priceTax = $actItem['priceTax'];
				$priceNoTax = $actItem['priceNoTax'];
				$totalNoTax = $priceNoTax * $count;
				$goodsTotalTax += $priceObj->getPrice($totalNoTax, TRUE, $row, FALSE);
			}
		}

		return $goodsTotalTax;
	}


	public function init (
		$pibaseClass,
		&$formerBasket,
		$updateMode,
		$pid_list,
		$useArticles,
		$bStoreBasket
	)	{
		global $TSFE;

		$pibaseObj = t3lib_div::getUserObj('&'.$pibaseClass);
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->recs = $formerBasket;	// Sets it internally
		$this->itemObj = t3lib_div::getUserObj('&tx_ttproducts_basketitem');

		if (isset($pibaseObj->piVars) && is_array($pibaseObj->piVars) && isset($pibaseObj->piVars['type']) && is_array($pibaseObj->piVars['type']))	{
			$typeArray = $pibaseObj->piVars['type'];
		}

		if (t3lib_div::_POST('products_payment'))	{	// use AGB checkbox if coming from INFO page. It could have been unchecked again.
			$bAgbSet = $this->recs['personinfo']['agb'];
			$this->recs['personinfo']['agb'] = (boolean) $_REQUEST['recs']['personinfo']['agb'];
			if ($bAgbSet != $this->recs['personinfo']['agb'])	{
				$TSFE->fe_user->setKey('ses', 'recs', $this->recs);	// store this change
			}
		}
		$this->basket = array();
		$this->itemArray = array();
		$this->paymentshippingObj = t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
		$this->pidListObj = t3lib_div::getUserObj('tx_ttproducts_pid_list');
		$this->pidListObj->init($pibaseObj->cObj);
		$this->pidListObj->applyRecursive(99, $pid_list, TRUE);
		$this->pidListObj->setPageArray();

		$this->useArticles = $useArticles;
		if ($this->useArticles == 2)	{
			$funcTablename = 'tt_products_articles';
		} else {
			$funcTablename = 'tt_products';
		}

		$this->setFuncTablename($funcTablename);
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$viewTableObj = $tablesObj->get($funcTablename);
		$tmpBasketExt = $TSFE->fe_user->getKey('ses','basketExt');

		$this->order = $TSFE->fe_user->getKey('ses','order');

		$basketExtRaw = t3lib_div::_GP('ttp_basket');
		$basketInputConf = &$cnf->getBasketConf('view','input');

		if (isset($basketInputConf) && is_array($basketInputConf))	{
			foreach ($basketInputConf as $lineNo => $inputConf)	{

				if (strpos($lineNo,'.') !== FALSE && $inputConf['type'] == 'radio' && $inputConf['where'] && $inputConf['name'] != '')	{
					$radioUid = t3lib_div::_GP($inputConf['name']);

					if ($radioUid)	{
						$rowArray = $viewTableObj->get('',0,FALSE,$inputConf['where']);

						if (count($rowArray))	{
							foreach($rowArray as $uid => $row)	{
								if ($uid == $radioUid)	{
									$basketExtRaw[$uid]['quantity'] = 1;
								} else {
									unset($tmpBasketExt[$uid]);
								}
							}
						}
					}
				}
			}
		}

		if (is_array($tmpBasketExt)) {
			$this->basketExt = $tmpBasketExt;
		} else {
			$this->basketExt = array();
		}

		$this->giftnumber = count($this->basketExt['gift']) + 1;
		$newGiftData = t3lib_div::_GP('ttp_gift');
		$extVars = $pibaseObj->piVars['variants'];
		$extVars = ($extVars ? $extVars : t3lib_div::_GP('ttp_extvars'));
		$paramProduct = strtolower($viewTableObj->marker);
		$uid = $pibaseObj->piVars[$paramProduct];
		$uid = ($uid ? $uid : t3lib_div::_GP('tt_products'));
		$sameGiftData = TRUE;
		$identGiftnumber = 0;

		$addMemo = $pibaseObj->piVars['addmemo'];
		if ($addMemo)	{
			$basketExtRaw = '';
			$newGiftData = '';
		}

			// Call all changeBasket hooks
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasket'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasket'] as $classRef) {
				$hookObj= t3lib_div::getUserObj($classRef);
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
			if (isset($basketExtRaw['dam']))	{
				$damUid = intval($basketExtRaw['dam']);
			}

			foreach ($basketExtRaw as $uid => $basketItem)	{

				if (
					tx_div2007_core::testInt($uid)
				) {
					if (isset($typeArray) && is_array($typeArray) && $typeArray[0] == 'product' && $typeArray[1] != '' || $basketExtRaw['dam'])	{

						foreach ($basketItem as $damUid => $damBasketItem)	{
							$this->addItem($viewTableObj, $uid, $damUid, $damBasketItem, $updateMode, $bStoreBasket, $newGiftData, $identGiftnumber, $sameGiftData);
						}
					} else {
						$this->addItem($viewTableObj, $uid, $damUid, $basketItem, $updateMode, $bStoreBasket, $newGiftData, $identGiftnumber, $sameGiftData);
					}
				}
			}
			// I did not find another possibility to delete elements completely from a multidimensional array
			// than to recreate the array
			$basketExtNew = array();

			foreach($this->basketExt as $tmpUid => $tmpSubArr) {
				if (is_array($tmpSubArr) && count($tmpSubArr))	{
					foreach($tmpSubArr as $tmpExtVar => $tmpCount) {

						if (
							$tmpCount > 0 &&
							(
								$this->conf['quantityIsFloat'] ||
								tx_div2007_core::testInt($tmpCount)
							)
						) {
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


	public function getRadioInputArray (
		$row
	)	{
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$basketConf = $cnf->getBasketConf('view','input');
		$result = FALSE;

		if (count($basketConf))	{
			$sql = t3lib_div::makeInstance('tx_ttproducts_sql');
			foreach ($basketConf as $lineNo => $inputConf)	{
				if (strpos($lineNo,'.') !== FALSE)	{
					$bIsValid = $sql->isValid($row, $inputConf['where']);
					if ($bIsValid && $inputConf['type'] == 'radio')	{
						$result = $inputConf;
					}
				}
			}
		}

		return $result;
	}


	public function &getItemObj ()	{
		return $this->itemObj;
	}


	public function &getItemArray ()	{
		return $this->itemArray;
	}


	public function addItem ($viewTableObj, $uid, $damUid, $item, $updateMode, $bStoreBasket, $newGiftData = '', $identGiftnumber = 0, $sameGiftData = FALSE)	{

		$newGiftData = t3lib_div::_GP('ttp_gift');

		$priceObj = t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$variant = $viewTableObj->variant->getVariantFromRawRow($item);
		$oldcount = $this->basketExt[$uid][$variant];

		if ($damUid)	{
			$tableVariant = $viewTableObj->variant->getTableUid('tx_dam', $damUid);
			$variant .= $tableVariant;
		}

		// quantities for single values are stored in an array. This is necessary because a HTML checkbox does not send any values if it has been unchecked
		if (isset($item['quantity']) && is_array($item['quantity']))	{
			reset($item['quantity']);
			$item['quantity'] = current($item['quantity']);
		}

		if (!$updateMode) {
			if (!isset($item['quantity']))	{
				return;
			}
			$quantity = 0;
			$quantity = $priceObj->toNumber($this->conf['quantityIsFloat'],$item['quantity']);
			$count = $this->getMaxCount($quantity, $uid);

			if ($count >= 0 && $bStoreBasket) {
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

			foreach($item as $md5 => $quantity) {
				$quantity = $priceObj->toNumber($this->conf['quantityIsFloat'],$quantity);

				if (is_array($this->basketExt[$uid]) && $md5 != 'additional')	{
					foreach($this->basketExt[$uid] as $variant => $tmp) {
						$actMd5 = md5($variant);
							// useArticles if you have different prices and therefore articles for color, size, additional and gradings
						if ($actMd5==$md5) {
							$count=$this->getMaxCount($quantity, $uid);
							$this->basketExt[$uid][$variant] = $count;

							if (isset($item['additional']) && is_array($item['additional']) &&
							isset($item['additional'][$actMd5]['giftservice']) && is_array($item['additional'][$actMd5]['giftservice']))	{
								if (isset($this->basketExt[$uid][$variant.'.']) && !is_array($this->basketExt[$uid][$variant.'.']))	{
									$this->basketExt[$uid][$variant.'.'] = array();
								}
								if (isset($this->basketExt[$uid][$variant.'.']['additional']) && !is_array($this->basketExt[$uid][$variant.'.']['additional']))	{
									$this->basketExt[$uid][$variant.'.']['additional'] = array();
								}
								$bHasGiftService = $item['additional'][$actMd5]['giftservice']['1'];
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


	public function getBasketExt ()	{
		return $this->basketExt;
	}


	public function getPidListObj ()	{
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


	public function getMaxCount ($quantity, $uid=0)	{
		$count = 0;

		if ($this->conf['basketMaxQuantity'] == 'inStock' && !$this->conf['alwaysInStock'] && !empty($uid)) {
			$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
			$viewTableObj = $tablesObj->get('tt_products');
			$row = $viewTableObj->get($uid);
			$count = tx_div2007_core::intInRange($quantity, 0, $row['inStock'], 0);
		} elseif ($this->conf['quantityIsFloat'])	{
			$count = (float) $quantity;
			if ($count < 0)	{
				$count = 0;
			}
			if ($count > $this->conf['basketMaxQuantity'])	{
				$count = $this->conf['basketMaxQuantity'];
			}
		} else {
			$count = tx_div2007_core::intInRange($quantity, 0, $this->conf['basketMaxQuantity'], 0);
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
			$this->store('recs',$this->getClearBasketRecord());
			$this->store('basketExt',array());
			$this->store('order',array());
			unset($this->itemArray);
			unset($this->basketExt);
			unset($this->order);
		}
		$this->store('ac',array());
		$this->store('cc',array());
		$this->store('cp',array());
		$this->store('vo',array());
	} // clearBasket


	public function store ($type,$valArray)	{
		global $TSFE;

		$TSFE->fe_user->setKey('ses',$type,$valArray);
	}


	/**
	 * This calculates the totals. Very important function.
	This function also calculates the internal arrays

	$this->itemArray	The basked elements, how many (quantity, count) and the price
	$this->calculatedArray	- Sums of goods, shipping, payment and total amount WITH TAX included

	... which holds the total amount, the final list of products and the price of payment and shipping!!

	 */
	public function getCalculatedBasket ()	{
		global $TYPO3_DB, $TSFE;

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
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$viewTableObj = $tablesObj->get($funcTablename);

		$cnfObj = t3lib_div::getUserObj('&tx_ttproducts_config');
		$conf = &$cnfObj->conf;
		$itemTableConf = $cnfObj->getTableConf($funcTablename, 'BASKET');
		$orderBy = $viewTableObj->getTableObj()->transformOrderby($itemTableConf['orderBy']);

		$where = 'uid IN ('.implode(',',$uidArr).') AND pid IN ('. $this->pidListObj->getPidlist().')'.$viewTableObj->getTableObj()->enableFields();

		$rcArray = $viewTableObj->getWhere($where, $theCode, $orderBy);

		$productsArray = array();
		$this->extTableItemArray = array();
		$prodCount = 0;
		$bAddGiftService = FALSE;

		foreach ($rcArray as $uid => $row)	{

			$viewTableObj->getTableObj()->transformRow($row, TT_PRODUCTS_EXT);
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

					$bextVarArray = t3lib_div::trimExplode('|', $bextVarLine);
					$bextVars = $bextVarArray[0];
					$currRow = $row;

					if ($this->useArticles != 3)	{
						$viewTableObj->variant->modifyRowFromVariant($currRow, $bextVars);
					}
					$extTable = $funcTablename;
					$extUid = $uid;
					$extArray = array('uid' => $extUid, 'vars' => $bextVars);
					$currRow['ext'][$extTable][] = $extArray;

					if ($bextVarArray[1] == 'tx_dam' && $bextVarArray[2])	{
						if (!is_array($this->extTableItemArray['tx_dam']))	{
							$this->extTableItemArray['tx_dam'] = array();
						}
// 						$damUid = intval($bextVarArray[2]);
/*						$damRow = $this->tx_dam->get($damUid);
						$this->extTableItemArray['tx_dam'][$damUid] = $damRow;*/
						$extTable = $bextVarArray[1];
						$extUid = intval($bextVarArray[2]);
						$damObj = $tablesObj->get('tx_dam');
						$damObj->modifyItemRow($currRow, $extUid);
						$currRow['ext'][$extTable][] = array('uid' => $extUid);
					}
					// $currRow['extVars'] = $bextVars;

					if (in_array($this->useArticles, array(1,3)) && $funcTablename == 'tt_products') {
						// get the article uid with these colors, sizes and gradings
						$articleRowArray = array();
						if ($this->useArticles == 1)	 {
							$articleRowArray[] = $viewTableObj->getArticleRow($currRow, 'BASKET',FALSE);
						} else if ($this->useArticles == 3) {

							$articleRowArray = $viewTableObj->getArticleRowsFromVariant($currRow, 'BASKET', $bextVars);
						}

						if (count($articleRowArray))	{
							foreach ($articleRowArray as $articleRow)	{

									// use the fields of the article instead of the product
								// $viewTableObj->mergeAttributeFields($currRow, $articleRow, FALSE, TRUE); Preis wird sonst doppelt addiert!
								$currRow['ext']['tt_products_articles'][] = array('uid' => $articleRow['uid']);
							}
						}

					} else if ($this->useArticles == 2)	{
						$productRow = $viewTableObj->getProductRow($currRow);
						$viewTableObj->mergeAttributeFields($currRow, $productRow, TRUE);
					}
					$productsArray[$prodCount] = $currRow;
					$prodCount++;
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
		$priceObj = t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$maxTax = 0;
		$taxObj = t3lib_div::getUserObj('&tx_ttproducts_field_tax');
		$uidArray = array();

		foreach ($productsArray as $k1 => $row)	{

			$uid = $row['uid'];
			$tax = $taxObj->getFieldValue($row, 'tax');
			if ($tax > $maxTax)	{
				$maxTax = $tax;
			}

			// $variant = $viewTableObj->variant->getVariantFromRow($row);
			$newItem = $this->getItem($row, 'useExt');
			$count = $newItem['count'];

			if($count > 0)	{
				$weight = $newItem['weight'];
				$this->itemArray[$row[$viewTableObj->fieldArray['itemnumber']]][] = $newItem;
				$this->calculatedArray['count']		+= $count;
				$this->calculatedArray['weight']	+= $weight * $count;
			}
			// if reseller is logged in then take 'price2', default is 'price'
			$uidArray[] = $uid;
		}
		$this->setUidArray($uidArray);

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

					$goodsTotalTax = self::getGoodsTotalTax($this->basketExtra, $this->itemArray);
					include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_discountprice.php');
					$discountPrice = t3lib_div::makeInstance('tx_ttproducts_discountprice');
					$discountPrice->getCalculatedData($this->itemArray, $this->conf['discountprice.'], 'calcprice', $priceReduction, $goodsTotalTax, FALSE);
				}
			}

			// set the 'calcprice' in itemArray
			if ($this->conf['pricecalc.']) {
				$pricecalc = t3lib_div::makeInstance('tx_ttproducts_pricecalc');

				// do the price calculation
				$pricecalc->getCalculatedData($this->itemArray, $this->conf['pricecalc.'], 'calcprice', $priceReduction, '', FALSE);
			}

			$pricetablescalc = t3lib_div::getUserObj('&tx_ttproducts_pricetablescalc');
			$pricetablescalc->init($pibaseObj);
			$pricetablescalc->getCalculatedData($this->itemArray, $tmp='', 'calcprice', $priceReduction, '', TRUE);

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

			if ($taxObj->getUseStaticTaxes())	{
				$shippingTax = $taxObj->getTax($row);
			} else {
				$shippingTax = $this->paymentshippingObj->getTaxPercentage('shipping');
			}

			if ($shippingTax > $maxTax)	{
				$maxTax = $shippingTax;
			} else if ($shippingTax == '')	{
				$shippingTax = $maxTax;
			}
			$shippingRow = array('tax' => $shippingTax);
			$bulkilyFeeTax = floatval($this->conf['bulkilyFeeTax']);

			// loop over all items in the basket indexed by a sort string
			foreach ($this->itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {
					$row = &$actItem['rec'];
					$count = $actItem['count'];
					$tax = $actItem['tax'];
					$priceTax = $actItem['priceTax'];
					$priceNoTax = $actItem['priceNoTax'];
					$price0Tax = $actItem['price0Tax'];
					$price0NoTax = $actItem['price0NoTax'];
					$price2Tax = $actItem['price2Tax'];
					$price2NoTax = $actItem['price2NoTax'];

					if ($actItem['calcprice'] > 0) {
						$this->itemArray[$sort][$k1]['priceTax'] = $priceObj->getModePrice($this->conf['TAXmode'], $actItem['calcprice'], TRUE, $row, $this->conf['TAXincluded']);
						$this->itemArray[$sort][$k1]['priceNoTax'] = $priceObj->getModePrice($this->conf['TAXmode'], $actItem['calcprice'], FALSE, $row, $this->conf['TAXincluded']);
					}

					//  multiplicate it with the count :
					$this->itemArray[$sort][$k1]['totalNoTax'] = $this->itemArray[$sort][$k1]['priceNoTax'] * $count;
					$this->itemArray[$sort][$k1]['total0NoTax'] = $this->itemArray[$sort][$k1]['price0NoTax'] * $count;
					$this->itemArray[$sort][$k1]['total2NoTax'] = $this->itemArray[$sort][$k1]['price2NoTax'] * $count;
					$this->calculatedArray['price0NoTax']['goodstotal'] += $this->itemArray[$sort][$k1]['total0NoTax'];
					$this->calculatedArray['priceNoTax']['goodstotal'] += $this->itemArray[$sort][$k1]['totalNoTax'];
					$this->calculatedArray['categoryPriceNoTax']['goodstotal'][$row['category']]+= $this->itemArray[$sort][$k1]['totalNoTax'];
					$this->calculatedArray['price2NoTax']['goodstotal'] += $price2NoTax * $count;

					$this->calculatedArray['noDiscountPriceTax']['goodstotal']  += $priceObj->getPrice($row['price'] * $actItem['count'], TRUE, $row, $conf['TAXincluded']);
					$this->calculatedArray['noDiscountPriceNoTax']['goodstotal'] += $priceObj->getPrice($row['price'] * $actItem['count'], FALSE, $row, $conf['TAXincluded']);

					if ($this->conf['TAXmode'] == '1')	{
						$taxstr = strval(number_format($tax,2)); // needed for floating point taxes as in Swizzerland
						$this->itemArray[$sort][$k1]['totalTax'] = $priceObj->getPrice($this->itemArray[$sort][$k1]['totalNoTax'],TRUE,$row,FALSE);
						$this->itemArray[$sort][$k1]['total0Tax'] = $priceObj->getPrice($this->itemArray[$sort][$k1]['total0NoTax'],TRUE,$row,FALSE);
						$this->calculatedArray['priceNoTax']['goodssametaxtotal'][$taxstr] +=  $this->itemArray[$sort][$k1]['totalNoTax'];
						$this->calculatedArray['price2NoTax']['goodssametaxtotal'][$taxstr] += $this->itemArray[$sort][$k1]['total2NoTax'];
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
						$this->calculatedArray['priceTax']['handling'] += $priceObj->getModePrice($this->conf['TAXmode'], $value, TRUE, $shippingRow, $this->conf['TAXincluded'], TRUE);
						$value = $row['shipping'];
						$this->calculatedArray['priceTax']['shipping'] += $priceObj->getModePrice($this->conf['TAXmode'],$value,TRUE,$shippingRow,$this->conf['TAXincluded'],TRUE);
						$value = $row['shipping2'];
						if ($count > 1)	{
							$this->calculatedArray['priceTax']['shipping'] += $priceObj->getModePrice($this->conf['TAXmode'], $value*($count-1),TRUE,$shippingRow,$this->conf['TAXincluded'],TRUE);
						}
					}

					$value = $row['handling'];
					$this->calculatedArray['priceNoTax']['handling'] += $priceObj->getModePrice($this->conf['TAXmode'], $value, FALSE, $shippingRow, $this->conf['TAXincluded'], TRUE);
					$value = $row['shipping'];
					$this->calculatedArray['priceNoTax']['shipping'] += $priceObj->getModePrice($this->conf['TAXmode'], $value, FALSE, $shippingRow, $this->conf['TAXincluded'], TRUE);

					$value = $row['shipping2'];

					if ($count > 1)	{
						$this->calculatedArray['priceNoTax']['shipping'] += $priceObj->getModePrice($this->conf['TAXmode'], $value*($count-1), FALSE, $shippingRow, $this->conf['TAXincluded'], TRUE);
					}
				} // foreach ($actItemArray as $k1 => $actItem) {
			} // foreach ($this->itemArray

			$this->paymentshippingObj->getScriptPrices('payment', $this->calculatedArray, $this->itemArray);
			$this->paymentshippingObj->getScriptPrices('shipping', $this->calculatedArray, $this->itemArray);

			$this->calculatedArray['maxtax']['goodstotal'] = $maxTax;
			$taxRow = array();
			foreach ($productsArray as $row) {	// TODO: remove this because it has been moved to the shipping configuration
				if ($row['bulkily'])	{
					$value = floatval($this->conf['bulkilyAddition']) * $this->basketExt[$row['uid']][$viewTableObj->variant->getVariantFromRow($row)];
					$tax = ($bulkilyFeeTax != '' ? $bulkilyFeeTax : $shippingTax);
					$taxRow['tax'] = $tax;
					$this->calculatedArray['priceTax']['shipping'] += $priceObj->getModePrice($this->conf['TAXmode'], $value, TRUE, $taxRow, $this->conf['TAXincluded'], TRUE);
					$this->calculatedArray['priceNoTax']['shipping'] += $priceObj->getModePrice($this->conf['TAXmode'], $value, FALSE, $taxRow, $this->conf['TAXincluded'], TRUE);
				}
			}

			if ($this->conf['TAXmode'] == '1')	{
				$controlCalcArray = array('priceTax' => 'priceNoTax', 'price0Tax' => 'price0NoTax', 'price2Tax' => 'price2NoTax');

				$taxRow = array();
				foreach ($controlCalcArray as $keyTax => $keyNoTax)	{
					$priceTax = 0;
					foreach ($this->calculatedArray[$keyNoTax]['goodssametaxtotal'] as $tax => $value)	{
						$taxRow['tax'] = $tax;
						$newPriceTax = $priceObj->getModePrice($this->conf['TAXmode'],$value,TRUE,$taxRow,FALSE,TRUE);
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

				$this->calculatedArray['priceTax']['handling'] = $priceObj->getModePrice($this->conf['TAXmode'], $this->calculatedArray['priceNoTax']['handling'], TRUE, $shippingRow, FALSE, FALSE);
				$this->calculatedArray['priceTax']['shipping'] = $priceObj->getModePrice($this->conf['TAXmode'], $this->calculatedArray['priceNoTax']['shipping'], TRUE, $shippingRow, FALSE, FALSE);
			}
		} // if (count($this->itemArray))
		$paymentTax = $this->paymentshippingObj->getTaxPercentage('payment');
		$paymentRow = array('tax' => $paymentTax);

			// Shipping must be at the end in order to use the calculated values from before
		$this->paymentshippingObj->getPaymentShippingData(
			$this->calculatedArray['count'],
			$this->calculatedArray['priceTax']['goodstotal'],
			$shippingRow,
			$paymentRow,
			$this->calculatedArray['priceTax']['shipping'],
			$this->calculatedArray['priceNoTax']['shipping'],
			$this->calculatedArray['priceTax']['payment'],
			$this->calculatedArray['priceNoTax']['payment']
		);

		if ($shippingTax)	{
			$this->calculatedArray['priceNoTax']['sametaxtotal'][strval(number_format($shippingTax, 2))] += $this->calculatedArray['priceNoTax']['shipping'];
		}
		if ($paymentTax) {
			$this->calculatedArray['priceNoTax']['sametaxtotal'][strval(number_format($paymentTax, 2))] += $this->calculatedArray['priceNoTax']['payment'];
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
	public function getGraduatedPrices ($uid)	{
		$graduatedPriceObj = t3lib_div::getUserObj('&tx_ttproducts_graduated_price');
		$this->formulaArray = $graduatedPriceObj->getFormulasByProduct($uid);
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


	public function getUidArray ()	{
		return $this->uidArray;
	}


	public function setUidArray ($uidArray)	{
		$this->uidArray = $uidArray;
	}


	public function getAddressArray ()	{
		$rc = array();
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$addressObj = $tablesObj->get('address',FALSE);

		foreach ($this->itemArray as $sort => $actItemArray) {
			foreach ($actItemArray as $k1 => $actItem) {
				$row = &$actItem['rec'];
				$addressUid = $row['address'];

				if ($addressUid)	{
					$addressRow = $addressObj->get($addressUid);
					$rc[$addressUid] = $addressRow;
				}
			}
		}

		return $rc;
	}


	public function getQuantityArray ($uidArray, &$rowArray)	{
		$rc = array();

		if (isset($rowArray) && is_array($rowArray))	{
			// loop over all items in the basket indexed by a sort string
			foreach ($this->itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {

					$row = &$actItem['rec'];
					$uid = $row['uid'];
					$count = $actItem['count'];
					$extArray = $row['ext'];

					if (in_array($uid, $uidArray) && isset($extArray) && is_array($extArray) && isset($extArray) && is_array($extArray))	{

						foreach ($rowArray as $functablename => $functableRowArray)	{
							$subExtArray = $extArray[$functablename];
							if (isset($subExtArray) && is_array($subExtArray))	{
								foreach ($functableRowArray as $subRow)	{
									$extItem = array('uid' => $subRow['uid']);
									if (in_array($extItem, $subExtArray))	{
										$rc[$uid][$functablename][$subRow['uid']] = $actItem['count'];
									}
								}
							}
						}
					}
				}
			}
		}
		return $rc;
	}


	public function &getItem ($row, $fetchMode, $funcTablename='') {
		$item = array();
		$priceObj = t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$priceRow = $row;

		if (!$funcTablename)	{
			$funcTablename = $this->getFuncTablename();
		}

		if ($funcTablename == 'tt_products') {
			$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
			$viewTableObj = $tablesObj->get($funcTablename);
			$cnfObj = t3lib_div::getUserObj('&tx_ttproducts_config');

			$count = 0;
			if ($fetchMode == 'useExt')	{
				$variant = $viewTableObj->variant->getVariantFromRow($row);
				$priceRow = $viewTableObj->getRowFromExt($funcTablename, $row, $this->useArticles);
			} else if ($fetchMode == 'rawRow') {
				$variant = $viewTableObj->variant->getVariantFromRawRow($row);
			} else if ($fetchMode == 'firstVariant') {
				$variant = $viewTableObj->variant->getVariantFromProductRow($row, 0);
			}
			$priceTaxArray = $priceObj->getPriceTaxArray('price', tx_ttproducts_control_basket::getRoundFormat(),$priceRow);
			$price2TaxArray = $priceObj->getPriceTaxArray('price2', tx_ttproducts_control_basket::getRoundFormat(), $priceRow);
			$priceTaxArray = array_merge($priceTaxArray, $price2TaxArray);

			$tax = $priceTaxArray['tax_perc'];
			$oldPriceTaxArray = $priceObj->convertOldPriceArray($priceTaxArray);
			$extArray = $row['ext'];

			if (is_array($extArray['tx_dam']))	{
				reset($extArray['tx_dam']);
				$firstDam = current($extArray['tx_dam']);
				$extUid = $firstDam['uid'];
				$tableVariant = $viewTableObj->variant->getTableUid('tx_dam', $extUid);
				$variant .= $tableVariant;
			}

			if (isset($this->basketExt[$row['uid']]) && is_array($this->basketExt[$row['uid']]) && isset($this->basketExt[$row['uid']][$variant]))	{
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

	// 		$priceTax = $priceObj->getResellerPrice($priceRow,1);
	// 		$priceNoTax = $priceObj->getResellerPrice($priceRow,0);
	// 		$price0Tax = $priceObj->getResellerPrice($priceRow,1,0);
	// 		$price0NoTax = $priceObj->getResellerPrice($priceRow,0,0);
	// 		$priceUnitNoTax = $priceObj->getPrice(($priceRow['unit_factor'] > 0 ? ($priceNoTax / $priceRow['unit_factor']) : 0),FALSE,$priceRow['tax'],FALSE);
	// 		$priceUnitTax = $priceObj->getPrice($priceUnitNoTax,TRUE,$row['tax'],FALSE);
	// 		$priceWeightNoTax = $priceObj->getPrice(($priceRow['weight'] > 0 ? ($priceNoTax / $priceRow['weight']) : 0),FALSE,$priceRow['tax'],FALSE);
	// 		$priceWeightTax = $priceObj->getPrice($priceWeightNoTax,TRUE,$priceRow['tax'],FALSE);
	// 		$item = array (
	// 			'calcprice' => 0,
	// 			'count' => $count,
	// 			'priceTax' => $priceTax,
	// 			'priceNoTax' => $priceNoTax,
	// 			'priceUnitTax' => $priceUnitTax,
	// 			'priceUnitNoTax' => $priceUnitNoTax,
	// 			'priceWeightUnitNoTax' => $priceWeightNoTax,
	// 			'priceWeightUnitTax' => $priceWeightTax,
	// 			'price0Tax' => $price0Tax,
	// 			'price0NoTax' => $price0NoTax,
	// 			'totalTax' => 0,
	// 			'totalNoTax' => 0,
	// 			'tax' => $tax,
	// 			'rec' => $row,
	// 		);
	//
	//
	// 		if ($this->conf['TAXmode'] == '2')	{
	// 			$taxFieldArray = array('priceTax', 'priceNoTax', 'price0Tax', 'price0NoTax', 'price2Tax', 'price2NoTax');
	// 			foreach ($taxFieldArray as $k => $field)	{
	// 				$item[$field] = round ($item[$field], 2);
	// 			}
	// 		}

			$item = array (
				'count' => $count,
				'weight' => $priceRow['weight'],
				'totalTax' => 0,
				'totalNoTax' => 0,
				'tax' => $tax,
				'rec' => $row,
			);
			$item = array_merge($item, $oldPriceTaxArray);	// Todo: remove this line
		}

		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasketItem']) &&
			is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasketItem'])
		) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasketItem'] as $classRef) {
				$hookObj= t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'changeBasketItem')) {
					$hookObj->changeBasketItem($row, $fetchMode, $funcTablename, $item);
				}
			}
		}

		return $item;
	} // getItem


	// This calculates the total for everything in the basket
	public function getCalculatedSums () {


		$pricefactor = doubleval($this->conf['creditpoints.']['priceprod']);

		$creditpointsObj = t3lib_div::getUserObj('&tx_ttproducts_field_creditpoints');
		$autoCreditpointsTotal = $creditpointsObj->getBasketTotal();
		if ($autoCreditpointsTotal > 0)	{
			$creditpoints = $autoCreditpointsTotal;
		} else {
			$creditpoints = $recs['tt_products']['creditpoints'];
		}
		$this->calculatedArray['priceTax']['creditpoints'] = $this->calculatedArray['priceNoTax']['creditpoints'] = $creditpointsObj*$pricefactor;

		$this->calculatedArray['priceNoTax']['total']  = round($this->calculatedArray['priceNoTax']['goodstotal'], 2);
		$this->calculatedArray['priceNoTax']['total'] += round($this->calculatedArray['priceNoTax']['payment'], 2);
		$this->calculatedArray['priceNoTax']['total'] += round($this->calculatedArray['priceNoTax']['shipping'], 2);
		$this->calculatedArray['price0NoTax']['total']  = $this->calculatedArray['price0NoTax']['goodstotal'];
		$this->calculatedArray['price0Tax']['total']  = $this->calculatedArray['price0Tax']['goodstotal'];

		$this->calculatedArray['price2NoTax']['total']  = $this->calculatedArray['price2NoTax']['goodstotal'];
		$this->calculatedArray['price2Tax']['total']  = $this->calculatedArray['price2Tax']['goodstotal'];

		$this->calculatedArray['priceTax']['total']  = $this->calculatedArray['priceTax']['goodstotal'];
		$this->calculatedArray['priceTax']['total'] += $this->calculatedArray['priceTax']['payment'];
		$this->calculatedArray['priceTax']['total'] += $this->calculatedArray['priceTax']['shipping'];
	}

	// This calculates the total for everything in the basket
	public function addVoucherSums () {
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$voucher = $tablesObj->get('voucher');
		$voucherAmount = $voucher->getRebateAmount();

		$this->calculatedArray['priceNoTax']['vouchertotal'] = $this->calculatedArray['priceNoTax']['total'] - $voucherAmount - $this->calculatedArray['priceNoTax']['creditpoints'];
		$this->calculatedArray['priceTax']['vouchertotal'] = $this->calculatedArray['priceTax']['total'] - $voucherAmount - $this->calculatedArray['priceTax']['creditpoints'];
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basket.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basket.php']);
}


?>
