<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricecalc_base.php');

class tx_ttproducts_pricetablescalc extends tx_ttproducts_pricecalc_base {
	var $conftablename = 'tt_products_graduated_price';


	function init(&$pibase)	{
	// nothing
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$itemArray: ...
	 * @param	[type]		$conf: ...
	 * @param	[type]		$type: ...
	 * @param	[type]		$priceReduction: ...
	 * @param	[type]		$priceTotalTax: ...
	 * @return	[type]		...
	 */
	function getCalculatedData(&$itemArray, &$conf, $type, &$priceReduction, $priceTotalTax ) {

		if (!$itemArray || !count($itemArray)) {
			return;
		}

		$graduatedPriceObj = &t3lib_div::getUserObj('&tx_ttproducts_graduated_price');

		// loop over all items in the basket indexed by sort string
		foreach ($itemArray as $sort => $actItemArray) {
			foreach ($actItemArray as $k2 => $actItem) {
				$row = $actItem['rec'];
				$count = floatval($actItem['count']);
				if ($row['graduated_price_uid'])	{
					$priceProduct = floatval($row['price']);
					$priceFormulaArray = $graduatedPriceObj->getFormulasByProduct($row['uid']);
					foreach ($priceFormulaArray as $k => $priceFormula)	{
						if ($count >= floatval($priceFormula['startamount']) && $priceProduct > floatval($priceFormula['formula']))	{
							$priceProduct = $priceFormula['formula'];
						}
					}
					if ($priceProduct > $itemArray [$sort][$k2][$type])	{
						$itemArray [$sort][$k2][$type] = $priceProduct;
					}
					$priceReduction[$row['uid']] = 1;
				}
			}
		}
	} // getCalculatedData


}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_pricetablescalc.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_pricetablescalc.php']);
}

?>
