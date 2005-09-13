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
 * basket price calculation functions without any object
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *  
 */


class tx_ttproducts_pricecalc_div {


		// result: fill in the  ['calcprice'] of $itemArray['pid'] ['itemnumber']
	function GetCalculatedData() { // delete countTotal if not neede any more
		global $TSFE;

		$getDiscount = 0;

		$gr_list = explode (',' , $TSFE->gr_list);

		if ($this->conf['getDiscountPrice']) {
			$getDiscount = 1;
		} else {
			while (list(,$val) = each ($gr_list)) {
				if ((intval($val) > 0) && ($getDiscount == 0)) {
					$getDiscount = 1 - strcmp($TSFE->fe_user->groupData->title, $this->conf['discountGroupName '] );

					if (strlen($TSFE->fe_user->groupData['title']) == 0)	// repair result of strcmp
						$getDiscount = 0;
				}
			}
		}

/*
		if($this->conf['pricecalc.'])
		{
			ksort($this->conf['pricecalc.']);
			reset($this->conf['pricecalc.']);
			foreach ($this->conf['pricecalc.'] as $k1 => $pricecalc)
			{
				$k=intval($k1);
				$this->basketExtra['pricecalc.'][$k] = $this->conf['pricecalc.'][$k1]['prod.'];
			}
		}

		if($this->conf['discountprice.'])
		{
			ksort($this->conf['discountprice.']);
			reset($this->conf['discountprice.']);
			foreach ($this->conf['discountprice.'] as $k1 => $discountprice)
			{
				$k=intval($k1);
				if ($k == 0)
					continue;
				$this->basketExtra['discountprice.'][$k] = $this->conf['discountprice.'][$k1]['prod.'];
			}
		}
*/

		$priceTotal = array();
		$priceReduction = array();

		$additive = 0;
		// Check if a special group price can be used
		if (($getDiscount == 1) && ($this->conf['discountprice.'] != NULL && $this->conf['discountprice.']['prod.'] != NULL))
		{
			$countTotal = 0;
			$countedItems = array();

			ksort($this->conf['discountprice.']['prod.']);
			reset($this->conf['discountprice.']['prod.']);

			$type = '';
			$field = '';
			foreach ($this->conf['discountprice.'] as $k1=>$priceCalcTemp) {
				if (!is_array($priceCalcTemp)) {
					switch ($k1) {
						case 'type':
							$type = $priceCalcTemp;
							break;
						case 'field':
							$field = $priceCalcTemp;
							break;
						case 'additive':
							$additive = $priceCalcTemp;
							break;
					}
					continue;
				}
				$dumCount = 0;
				$pricefor1 = doubleval($priceCalcTemp['prod.']['1']);
				$pricefor1Index = 100*$pricefor1;

				// loop over all items in the basket indexed by page and itemnumber
				foreach ($this->itemArray as $pid=>$pidItem) {
					foreach ($pidItem as $itemnumber=>$actItemArray) {
						foreach ($actItemArray as $k2=>$actItem) {
					// count all items which will apply to the discount price
							$count2 = $actItem['count'];
							if (($count2 > 0) && ($actItem['rec']['price'] == $pricefor1)) {
								$countedItems [$pricefor1Index][] = array ('pid' => $pid, 'itemnumber' => $itemnumber);
								$dumCount += $count2;
							}
						}
					}
				}

				$countTotal += $dumCount;

				if ($additive == 0) {
					krsort($priceCalcTemp['prod.']);
					reset($priceCalcTemp['prod.']);

					foreach ($priceCalcTemp['prod.'] as $k2=>$price2) {
						if ($dumCount >= intval($k2)) { // only the highest value for this count will be used; 1 should never be reached, this would not be logical
							if (intval($k2) > 1) {
								// store the discount price in all calculated items from before
								foreach ($countedItems as $k3=>$v3) {
									foreach ($this->itemArray[$v3['pid']] [$v3['itemnumber']] as $k4=>$actItem) { 
									 	$this->itemArray[$v3['pid']] [$v3['itemnumber']][$k4] ['calcprice'] = $price2;
									}
								}
								$priceReduction[$pricefor1Index] = 1; // remember the reduction in order not to calculate another price with $priceCalc
							}
							else {
								$priceReduction[$pricefor1Index] = 0;
							}
							break; // finish
						}
					}
				}
			}
			if ($additive == 1) {

				reset($this->conf['discountprice.']);

				foreach ($this->conf['discountprice.'] as $k1=>$priceCalcTemp) {
					if (!is_array($priceCalcTemp)) {
						continue;
					}
					$pricefor1 = doubleval($priceCalcTemp['prod.']['1']);
					if ($countedItems [100*$pricefor1] == NULL) {
						continue;
					}

					krsort($priceCalcTemp['prod.']);
					reset($priceCalcTemp['prod.']);
					while (list ($k2, $price2) = each ($priceCalcTemp['prod.'])) {
						if ($countTotal >= intval($k2)) { // search the price from the total count
							if (intval($k2) > 1) {
								// store the discount price in all calculated items from before
								foreach ($countedItems[$pricefor1Index] as $k3=>$v3) {
									foreach ($this->itemArray[$v3['pid']] [$v3['itemnumber']] as $k1=>$actItem) { 
									 	$this->itemArray[$v3['pid']] [$v3['itemnumber']][$k1] ['calcprice'] = $price2;
									}
								}
								$priceReduction[$pricefor1Index] = 1; // remember the reduction in order not to calculate another price with $priceCalc later
							}
							else  {	// $priceTotal [$k1] contains the product count
								$priceReduction[$pricefor1Index] = 0;
							}
							break; // finish
						}
					}
				}
			}
			else
			{	// nothing
			}
		}

		if ($this->conf['pricecalc.']) {
			$countTotal = 0;

			ksort($this->conf['pricecalc.']);
			reset($this->conf['pricecalc.']);

			foreach ($this->conf['pricecalc.'] as $k1=>$priceCalcTemp) {
				if (!is_array($priceCalcTemp)) {
					continue;
				}
				$countedItems = array();

				$pricefor1 = doubleval($priceCalcTemp['prod.']['1']);
				$pricefor1Index = 100*$pricefor1;

				// has the price already been calculated before ?
				if ($priceReduction[$pricefor1Index] == 1) {
					continue;
				}
				$dumCount = 0;

				reset($this->itemArray);
				// loop over all items in the basket indexed by page and itemnumber
				foreach ($this->itemArray as $pid=>$pidItem) {
					foreach ($pidItem as $itemnumber=>$actItemArray) {
						foreach ($actItemArray as $k2=>$actItem) {
				//foreach ($this->itemArray as $pid=>$pidItem) {
					//foreach ($pidItem as $itemnumber=>$actItem) {
							// count all items which will apply to the discount price
							$count2 = $actItem['count'];
							if (($count2 > 0) && ($actItem['rec']['price'] == $pricefor1)) {
								$countedItems [$pricefor1Index][] = array ('pid' => $pid, 'itemnumber' => $itemnumber);
								$dumCount += $count2;
							}
						}
					}
				}
				
					// nothing found?
				if ($dumCount == 0) {
					continue;
				}

				$countTotal += $dumCount;

				$priceTotalTemp = 0;
				$countTemp = $dumCount;
				krsort($priceCalcTemp['prod.']);
				reset($priceCalcTemp['prod.']);
				foreach ($priceCalcTemp['prod.'] as $k2=>$price2) {
					if (intval($k2) > 0) {
						while ($countTemp >= intval($k2)) {
							$countTemp -= intval($k2);
							$priceTotalTemp += doubleval($price2);
						}
					}
				}

				$priceProduct = ($dumCount > 0 ? ($priceTotalTemp / $dumCount) : 0);
				foreach ($countedItems[$pricefor1Index] as $k3=>$v3) {
					foreach ($this->itemArray[$v3['pid']] [$v3['itemnumber']] as $k4=>$actItem) {
						$this->itemArray[$v3['pid']] [$v3['itemnumber']] [$k4] ['calcprice'] = $priceProduct;
					}
				}
			}
		}

	} // GetCalculatedData


	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/pi/class.tx_ttproducts_pricecalc_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/pi/class.tx_ttproducts_pricecalc_div.php']);
}


?>
