<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2012 Franz Holzinger <franz@ttproducts.de>
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
 * price view functions
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

require_once (PATH_BE_ttproducts.'view/field/interface.tx_ttproducts_field_view_int.php');

class tx_ttproducts_field_price_view implements tx_ttproducts_field_view_int {
	public $langObj;
	public $cObj;
	public $conf;			// original configuration
	public $modelObj;
	static $convertArray = array(
		'price' => array(
			'tax' => 'PRICE_TAX',
			'taxperc' => 'TAX',
			'0tax' => 'OLD_PRICE_TAX',
			'0notax' => 'OLD_PRICE_NO_TAX',
			'calc' => 'calcprice',
			'notax' => 'PRICE_NO_TAX',
			'onlytax' => 'PRICE_ONLY_TAX',
			'skontotax' => 'PRICE_TAX_DISCOUNT',
			'skontotaxperc' => 'PRICE_TAX_DISCOUNT_PERCENT',
			'unotax' => 'UNIT_PRICE_NO_TAX',
			'utax' => 'UNIT_PRICE_TAX',
			'wnotax' => 'WEIGHT_UNIT_PRICE_NO_TAX',
			'wtax' => 'WEIGHT_UNIT_PRICE_TAX',
		),
		'price2' => array(
			'2tax' => 'PRICE2_TAX',
			'2notax' => 'PRICE2_NO_TAX',
			'2onlytax' => 'PRICE2_ONLY_TAX',
			'skonto2tax' => 'PRICE2_TAX_DISCOUNT',
			'skonto2taxperc' => 'PRICE2_TAX_DISCOUNT_PERCENT',
		),
		'directcost' => array(
			'dctax' => 'DIRECTCOST_TAX',
			'dcnotax' => 'DIRECTCOST_NO_TAX',
		)
	);


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init ($langObj, $cObj, $modelObj)	{
		$this->langObj = $langObj;
		$this->cObj = $cObj;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->modelObj = $modelObj;
		$this->bHasBeenInitialised = true;
	} // init

	public function needsInit ()	{
		return !$this->bHasBeenInitialised;
	}

	public function getModelObj ()	{
		return $this->modelObj;
	}

	/**
	 * Generate a graphical price tag or print the price as text
	 */
	public function printPrice ($priceText,$taxInclExcl='')	{
		if (($this->conf['usePriceTag']) && (isset($this->conf['priceTagObj.'])))	{
			$ptconf = $this->conf['priceTagObj.'];
			$markContentArray = array();
			$markContentArray['###PRICE###'] = $priceText;
			$markContentArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? tx_div2007_alpha5::getLL_fh002($this->langObj, $taxInclExcl) : '');

			$this->cObj->substituteMarkerInObject($ptconf, $markContentArray);
			return $this->cObj->cObjGetSingle($this->conf['priceTagObj'], $ptconf);
		} else {
			return $priceText;
		}
	}

	/**
	 * Formatting a price
	 */
	public function priceFormat ($double)	{

		$double = round($double, 10);
		$rc = number_format($double,intval($this->conf['priceDec']),$this->conf['priceDecPoint'],$this->conf['priceThousandPoint']);

		return $rc;
	} // priceFormat

	public static function convertKey ($k, $fieldname)	{
		$rc = FALSE;

		if (isset(self::$convertArray[$fieldname]) && is_array(self::$convertArray[$fieldname]))	{
			$rc = self::$convertArray[$fieldname][$k];
		}
		return $rc;
	}

	public function getItemMarkerArray ($functablename, $fieldname, &$row, $markerKey, &$markerArray, $tagArray, $theCode, $id, &$bSkip, $bHtml=TRUE, $charset='', $prefix='', $imageRenderObj='')	{

		$priceArray = array();
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$prodTable = $tablesObj->get($functablename, TRUE);
		$modelObj = $this->getModelObj();
		$paymentshippingObj = t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
		$markerProd = $prodTable->getMarker();
		$taxFromShipping = $paymentshippingObj->getReplaceTaxPercentage();
		$taxInclExcl = (isset($taxFromShipping) && is_double($taxFromShipping) && ($taxFromShipping == 0) ? 'tax_zero' : 'tax_included');

// tt-products-single-1-pricetax
		$priceArray = $modelObj->getPriceArray($fieldname,$row);
		$priceMarkerPrefix = $prodTable->getMarker() . '_';

		foreach ($priceArray as $displayTax => $priceValue)	{
			$marker = $this->convertKey($displayTax,$fieldname);
			$taxMarker = ($markerProd != 'PRODUCT' ? $markerProd . '_' : '') . $marker;
			$markerArray['###'.$taxMarker.'###'] = $this->printPrice($this->priceFormat($priceValue), $taxInclExcl);

			$displaySuffixId = str_replace('_', '', strtolower($displayTax));
			$displaySuffixId = str_replace($fieldname, '', $displaySuffixId);
			$markerArray['###'.$taxMarker.'_ID###'] = $id . $displaySuffixId;
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_price_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_price_view.php']);
}


?>