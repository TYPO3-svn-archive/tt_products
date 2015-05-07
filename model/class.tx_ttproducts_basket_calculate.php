<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Franz Holzinger <franz@ttproducts.de>
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
 * basket calculation functions for a basket object
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


include_once (PATH_BE_ttproducts.'model/field/class.tx_ttproducts_field_creditpoints.php');


class tx_ttproducts_basket_calculate {

	protected $calculatedArray = array();

	public function getCalculatedArray ()	{
		return $this->calculatedArray;
	}


	public function setCalculatedArray ($calculatedArray)	{
		$this->calculatedArray = $calculatedArray;
	}


	public static function getGoodsTotalTax ($itemArray)	{
		$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');

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


	/**
	 * This calculates the totals. Very important function.
	This function also calculates the internal arrays

	$this->itemArray	The basked elements, how many (quantity, count) and the price
	$this->calculatedArray	- Sums of goods, shipping, payment and total amount WITH TAX included

	... which holds the total amount, the final list of products and the price of payment and shipping!!

	 */
	public function calculate ($basketExt, $funcTablename, $useArticles, $maxTax, &$itemArray)	{
		global $TYPO3_DB, $TSFE;

		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$paymentshippingObj = &t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
		$taxObj = &t3lib_div::getUserObj('&tx_ttproducts_field_tax');
		$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

		$viewTableObj = &$tablesObj->get($funcTablename);

		$conf = &$cnf->conf;

		if (count($itemArray))	{
			if ($conf['discountprice.'])	{
				$getDiscount = 0;
				$gr_list = explode (',' , $TSFE->gr_list);
				if ($conf['getDiscountPrice']) {
					$getDiscount = 1;
				} else {
					foreach ($gr_list as $k1 => $val) {
						if (((float) $val > 0) && ($getDiscount == 0)) {
							$getDiscount = 1 - strcmp($TSFE->fe_user->groupData->title, $conf['discountGroupName'] );

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
					$discountArray = array();
					$goodsTotalTax = self::getGoodsTotalTax($itemArray);
					$discountPrice->getCalculatedData(
						$itemArray,
						$conf['discountprice.'],
						'calcprice',
						$priceReduction,
						$discountArray,
						$goodsTotalTax,
						FALSE,
						TRUE
					);
				}
			}

			// set the 'calcprice' in itemArray
			if ($conf['pricecalc.']) {
				$pricecalc = t3lib_div::makeInstance('tx_ttproducts_pricecalc');
				$discountArray = array();

				// do the price calculation
				$pricecalc->getCalculatedData(
					$itemArray,
					$conf['pricecalc.'],
					'calcprice',
					$priceReduction,
					$discountArray,
					'',
					FALSE,
					TRUE
				);
			}

			$pricetablescalc = &t3lib_div::getUserObj('&tx_ttproducts_pricetablescalc');
			$pricetablescalc->init($pibaseObj);
			$discountArray = array();
			$pricetablescalc->getCalculatedData(
				$itemArray,
				$tmp='',
				'calcprice',
				$priceReduction,
				$discountArray,
				'',
				TRUE,
				TRUE
			);

			$this->calculatedArray['priceTax'] = array();
			$this->calculatedArray['priceNoTax'] = array();
			$this->calculatedArray['price0Tax'] = array();
			$this->calculatedArray['price0NoTax']['total'] = array();

			if ($conf['TAXmode'] == '1')	{
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
				$shippingTax = $paymentshippingObj->getTaxPercentage('shipping', '');
			}

			if ($shippingTax > $maxTax)	{
				$maxTax = $shippingTax;
			} else if ($shippingTax == '')	{
				$shippingTax = $maxTax;
			}
			$shippingRow = array('tax' => $shippingTax);

			$bulkilyFeeTax = floatval($conf['bulkilyFeeTax']);

			// loop over all items in the basket indexed by a sort string
			foreach ($itemArray as $sort => $actItemArray) {
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

					// has the price been calculated before take it if it gets cheaper now
					if ($actItem['calcprice'] > 0) /*&& ($actItem['calcprice'] < $actItem['priceTax']) */ {
						$itemArray[$sort][$k1]['priceTax'] = $priceObj->getModePrice($conf['TAXmode'], $actItem['calcprice'], TRUE, $row, $conf['TAXincluded']);
						$itemArray[$sort][$k1]['priceNoTax'] = $priceObj->getModePrice($conf['TAXmode'], $actItem['calcprice'], FALSE, $row, $conf['TAXincluded']);
					}

					//  multiplicate it with the count :
					$itemArray[$sort][$k1]['totalNoTax'] = $itemArray[$sort][$k1]['priceNoTax'] * $count;
					$itemArray[$sort][$k1]['total0NoTax'] = $itemArray[$sort][$k1]['price0NoTax'] * $count;
					$itemArray[$sort][$k1]['total2NoTax'] = $itemArray[$sort][$k1]['price2NoTax'] * $count;

					$this->calculatedArray['price0NoTax']['goodstotal'] += $itemArray[$sort][$k1]['total0NoTax'];
					$this->calculatedArray['priceNoTax']['goodstotal'] += $itemArray[$sort][$k1]['totalNoTax'];
					$this->calculatedArray['categoryPriceNoTax']['goodstotal'][$row['category']]+= $itemArray[$sort][$k1]['totalNoTax'];
					$this->calculatedArray['price2NoTax']['goodstotal'] += $price2NoTax * $count;

					$this->calculatedArray['noDiscountPriceTax']['goodstotal']  += $priceObj->getPrice($row['price'] * $actItem['count'], TRUE, $row);
					$this->calculatedArray['noDiscountPriceNoTax']['goodstotal'] += $priceObj->getPrice($row['price'] * $actItem['count'], FALSE, $row);
// 					$this->calculatedArray['noDiscountPriceTax']['goodstotal']  += $priceObj->getPrice($row['price2'] * $actItem['count'], TRUE, $row);
// 					$this->calculatedArray['noDiscountPriceNoTax']['goodstotal'] += $priceObj->getPrice($row['price2'] * $actItem['count'], FALSE, $row);

					if ($conf['TAXmode'] == '1')	{
						$taxstr = strval(number_format($tax,2)); // needed for floating point taxes as in Swizzerland
						$itemArray[$sort][$k1]['totalTax'] = $priceObj->getPrice($itemArray[$sort][$k1]['totalNoTax'],TRUE,$row,FALSE);
						$itemArray[$sort][$k1]['total0Tax'] = $priceObj->getPrice($itemArray[$sort][$k1]['total0NoTax'],TRUE,$row,FALSE);
						$this->calculatedArray['priceNoTax']['goodssametaxtotal'][$taxstr] +=  $itemArray[$sort][$k1]['totalNoTax'];
						$this->calculatedArray['price2NoTax']['goodssametaxtotal'][$taxstr] += $itemArray[$sort][$k1]['total2NoTax'];
						$this->calculatedArray['categoryPriceNoTax']['goodssametaxtotal'][$taxstr][$row['category']] +=  $itemArray[$sort][$k1]['totalNoTax'];
						$this->calculatedArray['price0NoTax']['goodssametaxtotal'][$taxstr] += $itemArray[$sort][$k1]['total0NoTax'];
					} else if ($conf['TAXmode'] == '2')	{
						$itemArray[$sort][$k1]['totalTax'] = $itemArray[$sort][$k1]['priceTax'] * $count;
						$itemArray[$sort][$k1]['total0Tax'] = $itemArray[$sort][$k1]['price0Tax'] * $count;

							// Fills this array with the product records. Reason: Sorting them by category (based on the page, they reside on)
						$this->calculatedArray['priceTax']['goodstotal'] += $itemArray[$sort][$k1]['totalTax'];
						$this->calculatedArray['price0Tax']['goodstotal'] += $itemArray[$sort][$k1]['total0Tax'];

						$this->calculatedArray['categoryPriceTax']['goodstotal'][$row['category']]+= $itemArray[$sort][$k1]['totalTax'];

						$this->calculatedArray['price2Tax']['goodstotal']	+= $price2Tax * $count;

						$value = $row['handling'];
						$this->calculatedArray['handling']['0']['priceTax'] += $priceObj->getModePrice($conf['TAXmode'], $value, TRUE, $shippingRow, $conf['TAXincluded'], TRUE);
						$value = $row['shipping'];
						$this->calculatedArray['shipping']['priceTax'] += $priceObj->getModePrice($conf['TAXmode'], $value, TRUE, $shippingRow, $conf['TAXincluded'], TRUE);
						$value = $row['shipping2'];
						if ($count > 1)	{
							$this->calculatedArray['shipping']['priceTax'] += $priceObj->getModePrice($conf['TAXmode'], $value * ($count-1), TRUE, $shippingRow, $conf['TAXincluded'], TRUE);
						}
 					}
					$value = $row['handling'];
					$this->calculatedArray['handling']['0']['priceNoTax'] += $priceObj->getModePrice($conf['TAXmode'], $value, FALSE, $shippingRow, $conf['TAXincluded'], TRUE);

					$value = $row['shipping'];
					$this->calculatedArray['shipping']['priceNoTax'] += $priceObj->getModePrice($conf['TAXmode'], $value, FALSE, $shippingRow, $conf['TAXincluded'], TRUE);

					$value = $row['shipping2'];
					if ($count > 1)	{
						$this->calculatedArray['shipping']['priceNoTax'] += $priceObj->getModePrice($conf['TAXmode'], $value * ($count-1), FALSE, $shippingRow, $conf['TAXincluded'], TRUE);
					}
				} // foreach ($actItemArray as $k1 => $actItem) {
			} // foreach ($this->itemArray

			$paymentshippingObj->getScriptPrices('payment', $this->calculatedArray, $itemArray);
			$paymentshippingObj->getScriptPrices('shipping', $this->calculatedArray, $itemArray);
			$paymentshippingObj->getScriptPrices('handling', $this->calculatedArray, $itemArray);
			$this->calculatedArray['maxtax']['goodstotal'] = $maxTax;

			$taxRow = array();
			foreach ($itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {	// TODO: remove this because it has been moved to the shipping configuration
					$row = &$actItem['rec'];
					if ($row['bulkily'])	{
						$value = floatval($this->conf['bulkilyAddition']) * $basketExt[$row['uid']][$viewTableObj->variant->getVariantFromRow($row)];
						$tax = ($bulkilyFeeTax != '' ? $bulkilyFeeTax : $shippingTax);
						$taxRow['tax'] = $tax;
						$this->calculatedArray['shipping']['priceTax'] += $priceObj->getModePrice($conf['TAXmode'], $value, TRUE, $taxRow, $conf['TAXincluded'], TRUE);
						$this->calculatedArray['shipping']['priceNoTax'] += $priceObj->getModePrice($conf['TAXmode'], $value, FALSE, $taxRow, $conf['TAXincluded'], TRUE);
					}
				}
			}

			if ($conf['TAXmode'] == '1')	{
				$controlCalcArray = array('priceTax' => 'priceNoTax', 'price0Tax' => 'price0NoTax', 'price2Tax' => 'price2NoTax');

				$taxRow = array();
				foreach ($controlCalcArray as $keyTax => $keyNoTax)	{
					$priceTax = 0;
					foreach ($this->calculatedArray[$keyNoTax]['goodssametaxtotal'] as $tax => $value)	{
						$taxRow['tax'] = $tax;
						$newPriceTax = $priceObj->getModePrice($conf['TAXmode'],$value,TRUE,$taxRow,FALSE,TRUE);
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
						$taxRow['tax'] = $tax;
						if (is_array($catArray))	{
							foreach ($catArray as $cat => $value)	{
								$newPriceTax = $priceObj->getModePrice($conf['TAXmode'], $value, TRUE, $taxRow, FALSE);
								$priceTax += $newPriceTax;
								$priceTaxArray[$cat] = $newPriceTax;
							}
						}
					}
					$this->calculatedArray[$keyTax]['goodstotal'] = $priceTaxArray;
					// $this->calculatedArray['priceTax']['total'] += $priceTax;
				}
				$this->calculatedArray['handling']['0']['priceTax'] = $priceObj->getModePrice($conf['TAXmode'],$this->calculatedArray['handling']['0']['priceNoTax'],TRUE,$shippingRow,FALSE,FALSE);
				$this->calculatedArray['shipping']['priceTax'] = $priceObj->getModePrice($conf['TAXmode'],$this->calculatedArray['shipping']['priceNoTax'],TRUE,$shippingRow,FALSE,FALSE);
			}
		} // if (count($itemArray))
		$paymentTax = $paymentshippingObj->getTaxPercentage('payment', '');
		$paymentRow = array('tax' => $paymentTax);

			// Shipping must be at the end in order to use the calculated values from before
		$paymentshippingObj->getPaymentShippingData(
			$this->calculatedArray['count'],
			$this->calculatedArray['priceTax']['goodstotal'],
			$shippingRow,
			$paymentRow,
			$itemArray,
			$this->calculatedArray,
			$this->calculatedArray['shipping']['priceTax'],
			$this->calculatedArray['shipping']['priceNoTax'],
			$this->calculatedArray['payment']['priceTax'],
			$this->calculatedArray['payment']['priceNoTax']
		);
		$paymentshippingObj->getHandlingData(
			$this->calculatedArray['count'],
			$this->calculatedArray['priceTax']['goodstotal'],
			$this->calculatedArray,
			$itemArray
		);

		if ($shippingTax) {
			$this->calculatedArray['priceNoTax']['sametaxtotal'][strval(number_format($shippingTax, 2))] += $this->calculatedArray['shipping']['priceNoTax'];
		}
	} // getCalculatedBasket


	// This calculates the total for everything in the basket
	public function calculateSums ($recs) {
		$pricefactor = doubleval($this->conf['creditpoints.']['priceprod']);

		$creditpointsObj = &t3lib_div::getUserObj('&tx_ttproducts_field_creditpoints');
		$autoCreditpointsTotal = $creditpointsObj->getBasketTotal();
		if ($autoCreditpointsTotal > 0)	{
			$creditpoints = $autoCreditpointsTotal;
		} else {
			$creditpoints = $recs['tt_products']['creditpoints'];
		}
		$this->calculatedArray['priceTax']['creditpoints'] = $this->calculatedArray['priceNoTax']['creditpoints'] = $creditpointsObj*$pricefactor;


		$this->calculatedArray['priceNoTax']['total'] = round($this->calculatedArray['priceNoTax']['goodstotal'], 2);
		$this->calculatedArray['priceTax']['total']  = $this->calculatedArray['priceTax']['goodstotal'];

		if (isset($this->calculatedArray['handling']) && is_array($this->calculatedArray['handling']))	{
			foreach ($this->calculatedArray['handling'] as $subkey => $handlingConf)	{
				$this->calculatedArray['priceNoTax']['total'] += round($handlingConf['priceNoTax'], 2);
				$this->calculatedArray['priceTax']['total'] += $handlingConf['priceTax'];
			}
		}
		$this->calculatedArray['priceNoTax']['total'] += round($this->calculatedArray['payment']['priceNoTax'], 2);
		$this->calculatedArray['priceNoTax']['total'] += round($this->calculatedArray['shipping']['priceNoTax'], 2);
		$this->calculatedArray['price0NoTax']['total']  = $this->calculatedArray['price0NoTax']['goodstotal'];
		$this->calculatedArray['price0Tax']['total']  = $this->calculatedArray['price0Tax']['goodstotal'];

		$this->calculatedArray['price2NoTax']['total']  = $this->calculatedArray['price2NoTax']['goodstotal'];
		$this->calculatedArray['price2Tax']['total']  = $this->calculatedArray['price2Tax']['goodstotal'];

		$this->calculatedArray['priceTax']['total'] += $this->calculatedArray['payment']['priceTax'];
		$this->calculatedArray['priceTax']['total'] += $this->calculatedArray['shipping']['priceTax'];
	}

	// This calculates the total for the voucher in the basket
	public function addVoucherSums () {
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$voucher = $tablesObj->get('voucher');
		$voucherAmount = $voucher->getRebateAmount();

		$this->calculatedArray['priceNoTax']['vouchertotal'] = $this->calculatedArray['priceNoTax']['total'] - $voucherAmount - $this->calculatedArray['priceNoTax']['creditpoints'];
		$this->calculatedArray['priceTax']['vouchertotal'] = $this->calculatedArray['priceTax']['total'] - $voucherAmount - $this->calculatedArray['priceTax']['creditpoints'];
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basket_calculate.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basket_calculate.php']);
}


?>
