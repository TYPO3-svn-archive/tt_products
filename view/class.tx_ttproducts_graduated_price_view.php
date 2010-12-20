<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Franz Holzinger <kontakt@fholzinger.com>
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
 */
class tx_ttproducts_graduated_price_view {
	public $marker = 'GRADPRICE';
	public $modelObj;
	public $langObj;

	public function init(&$langObj, &$modelObj)	{
		$this->langObj = &$langObj;
		$this->modelObj = &$modelObj;
	}

	private function getFormulaMarkerArray($row, $priceFormula, &$markerArray, $suffix='')	{
		global $TCA;

		if (isset($priceFormula) && is_array($priceFormula))	{
			$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');
			$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
			foreach ($priceFormula as $field => $value)	{
				$keyMarker = '###'.$this->marker.'_'.strtoupper($field).$suffix.'###';
				if (strpos($TCA[$this->modelObj->conftablename]['interface']['showRecordFieldList'], $field) == FALSE)	{
					$value = '';
				}
				$markerArray[$keyMarker] = $value;
			}
			$priceNoTax = $priceObj->getPrice($priceFormula['formula'],false,$row['tax'],false);
			$priceTax = $priceObj->getPrice($priceNoTax,true,$row['tax'],false);
			$keyMarker = '###'.$this->marker.'_'.'PRICE_TAX'.$suffix.'###';
			$markerArray[$keyMarker] = $priceViewObj->priceFormat($priceTax);
			$keyMarker = '###'.$this->marker.'_'.'PRICE_NO_TAX'.$suffix.'###';
			$markerArray[$keyMarker] = $priceViewObj->priceFormat($priceNoTax);

			$basePriceTax = $priceObj->getResellerPrice($row,1);
			$basePriceNoTax = $priceObj->getResellerPrice($row,0);

			if ($basePriceTax)	{
				$skontoTax = ($basePriceTax - $priceTax);
				$tmpPercentTax = number_format(($skontoTax / $basePriceTax) * 100, $this->conf['percentDec']);
				$skontoNoTax = ($basePriceNoTax - $priceNoTax);
				$tmpPercentNoTax = number_format(($skontoNoTax / $basePriceNoTax) * 100, $this->conf['percentDec']);
			} else {
				$skontoTax = 'total';
				$skontoNoTax = 'total';
				$tmpPercentTax = 'infinite';
				$tmpPercentNoTax = 'infinite';
			}

			$keyMarker = '###'.$this->marker.'_'.'PRICE_TAX_DISCOUNT'.$suffix.'###';
			$markerArray[$keyMarker] = $priceViewObj->priceFormat($skontoTax);
			$keyMarker = '###'.$this->marker.'_'.'PRICE_NO_TAX_DISCOUNT'.$suffix.'###';
			$markerArray[$keyMarker] = $priceViewObj->priceFormat($skontoNoTax);
			$keyMarker = '###'.$this->marker.'_'.'PRICE_TAX_DISCOUNT_PERCENT'.$suffix.'###';
			$markerArray[$keyMarker] = $priceViewObj->priceFormat($tmpPercentTax);
			$keyMarker = '###'.$this->marker.'_'.'PRICE_NO_TAX_DISCOUNT_PERCENT'.$suffix.'###';
			$markerArray[$keyMarker] = $priceViewObj->priceFormat($tmpPercentNoTax);
		}
	}

	public function &getItemSubpartArrays (&$templateCode, &$row, $fieldname, &$subpartArray, &$wrappedSubpartArray, &$tagArray, $theCode='', $id='1')	{

		$subpartmarkerObj = &t3lib_div::getUserObj('&tx_ttproducts_subpartmarker');
		$t = array();
		$t['listFrameWork'] = $this->langObj->cObj->getSubpart($templateCode,'###GRADPRICE_FORMULA_ITEMS###');
		$t['itemFrameWork'] = $this->langObj->cObj->getSubpart($t['listFrameWork'],'###ITEM_FORMULA###');

// 		$t['listFrameWork'] = $this->pibase->cObj->substituteMarkerArrayCached(
// 				$t['listFrameWork'],
// 				$markerArray,
// 				$subPartArray,
// 				$wrappedSubpartArray
// 			);

		$priceFormulaArray = $this->modelObj->getFormulasByProduct($row['uid']);
		if (count($priceFormulaArray))	{

			$content = '';
			foreach ($priceFormulaArray as $k => $priceFormula)	{
				if (isset($priceFormula) && is_array($priceFormula))	{
					$itemMarkerArray = array();
					$this->getFormulaMarkerArray($row, $priceFormula, $itemMarkerArray);
					$formulaContent = $this->langObj->cObj->substituteMarkerArray($t['itemFrameWork'],$itemMarkerArray);
					$content .= $this->langObj->cObj->substituteSubpart($t['listFrameWork'],'###ITEM_FORMULA###',$formulaContent) ;
				}
			}
			$subpartArray['###GRADPRICE_FORMULA_ITEMS###'] = $content;
		} else {
			$subpartArray['###GRADPRICE_FORMULA_ITEMS###'] = '';
		}

//  ###GRADPRICE_PRICE_TAX###. ###GRADPRICE_PRICE_NO_TAX### ###GRADPRICE_PRICE_ONLY_TAX###
//  ###GRADPRICE_FORMULA1_PRICE_NO_TAX###  ###GRADPRICE_FORMULA1_PRICE_TAX###
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	public function getItemMarkerArray (
		&$row,
		&$markerArray,
		&$tagArray
	)	{

		if ($row['graduated_price_uid'])	{
			$priceFormulaArray = $this->modelObj->getFormulasByProduct($row['uid']);
			foreach ($priceFormulaArray as $k => $priceFormula)	{
				if (isset($priceFormula) && is_array($priceFormula))	{
// 					foreach ($priceFormula as $field => $value)	{
// 						if (strpos($TCA[$this->modelObj->conftablename]['interface']['showRecordFieldList'], $field) !== FALSE)	{
// 							$keyMarker = '###'.$this->marker.'_'.strtoupper($field).($k+1).'###';
// 							$markerArray[$keyMarker] = $value;
// 						}
// 					}
					$this->getFormulaMarkerArray($row, $priceFormula, $markerArray, ($k+1));
				}
			}
		}

		// empty all fields with no available entry
		foreach ($tagArray as $value => $k1)	{
			$keyMarker = '###'.$value.'###';
			if (strstr($value, $this->marker.'_') && !$markerArray[$keyMarker])	{
				$markerArray[$keyMarker] = '';
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_graduated_price_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_graduated_price_view.php']);
}

?>
