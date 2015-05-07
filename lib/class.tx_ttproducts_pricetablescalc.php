<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Franz Holzinger <franz@ttproducts.de>
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
 * basket price calculation functions using the price tables
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



require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricecalc_base.php');

class tx_ttproducts_pricetablescalc extends tx_ttproducts_pricecalc_base {
//	var $conftablename = 'tt_products_graduated_price';


	function init (&$pibase)	{
	// nothing
	}

	function getCalculatedData (
		&$itemArray,
		&$conf,
		$type,
		&$priceReduction,
		&$discountArray,
		$priceTotalTax,
		$bUseArticles,
		$bMergeArticles=TRUE
	) {
		if (!$itemArray || !count($itemArray)) {
			return;
		}

		$graduatedPriceObj = &t3lib_div::getUserObj('&tx_ttproducts_graduated_price');
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$useArticles = $cnf->getUseArticles();
		if ($bUseArticles && ($useArticles == 1 || $useArticles == 3)) {
			$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
			$articleTable = &$tablesObj->get('tt_products_articles', FALSE);
		}

		$prodArray = array();
		// loop over all items in the basket indexed by sort string
		foreach ($itemArray as $sort => $actItemArray) {

			foreach ($actItemArray as $k2 => $actItem) {
				$row = $actItem['rec'];
				$actItem['sort'] = $sort;
				$actItem['k2'] = $k2;
				$prodArray[$row['uid']][] = $actItem;
			}
		}

		// loop over all items in the basket indexed by product uid
		foreach ($prodArray as $uid => $actItemArray) {
			$row1 = $actItemArray['0']['rec'];

			if ($row1['graduated_price_uid'])	{
				$count = 0;
				$priceProduct = $row1['price'];

				foreach($actItemArray as $actItem)	{
					$count += floatval($actItem['count']);
					$priceProduct += floatval($actItem['rec']['price']);
				}
				$priceFormulaArray = $graduatedPriceObj->getFormulasByProduct($uid);

				foreach ($priceFormulaArray as $k => $priceFormula)	{

					if ($count >= floatval($priceFormula['startamount']))	{
						$formula = trim($priceFormula['formula']);
						$len = strlen($formula);
						$lastChar = substr($formula,-1,1);

						if (
							class_exists('t3lib_utility_Math') ?
							!t3lib_utility_Math::canBeInterpretedAsInteger($lastChar) :
							!t3lib_div::testInt($lastChar)
						) {
							$formula = substr($formula,0,strlen($formula)-1);
							switch ($lastChar)	{
								case '%':
									if ($formula > 100)	{
										$formula = 100;
									}
									$priceProduct = $priceProduct * (1 - $formula/100);
									break;
							}
						} else	{
							$priceProduct = $formula;
						}
					}
				}

				foreach($actItemArray as $actItem)	{

					$row = $actItem['rec'];
					$count = floatval($actItem['count']);
					$sort = $actItem['sort'];
					$k2 = $actItem['k2'];

					if (isset($articleTable) && is_object($articleTable))	{
						$extArray = $row['ext'];
						$articleUid = $extArray['tt_products_articles']['0']['uid'];

						if (
							class_exists('t3lib_utility_Math') ?
							t3lib_utility_Math::canBeInterpretedAsInteger($articleUid) :
							t3lib_div::testInt($articleUid)
						) {
							$articleRow = $articleTable->get($articleUid);
							$bIsAddedPrice = $cnf->hasConfig($articleRow, 'isAddedPrice');
							if ($bIsAddedPrice)	{
								$actPrice = $priceProduct + $articleRow['price'];
							}
						}
					} else {
						$actPrice = $priceProduct;
					}
					if ($actPrice > $itemArray[$sort][$k2][$type])	{
						$itemArray[$sort][$k2][$type] = $actPrice;
					}
				}
				$priceReduction[$uid] = 1;
			}
		}
	} // getCalculatedData
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_pricetablescalc.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_pricetablescalc.php']);
}

?>
