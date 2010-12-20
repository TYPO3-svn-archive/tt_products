<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2008 Franz Holzinger <contact@fholzinger.com>
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
 * $Id $
 *
 * @author	Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
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

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init(&$langObj, &$cObj, &$modelObj)	{
		$this->langObj = &$langObj;
		$this->cObj = &$cObj;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->modelObj = &$modelObj;
		$this->bHasBeenInitialised = true;
	} // init

	public function needsInit()	{
		return !$this->bHasBeenInitialised;
	}

	public function &getModelObj ()	{
		return $this->modelObj;
	}

	/**
	 * Generate a graphical price tag or print the price as text
	 */
	public function printPrice($priceText,$taxInclExcl='')	{
		if (($this->conf['usePriceTag']) && (isset($this->conf['priceTagObj.'])))	{
			$ptconf = $this->conf['priceTagObj.'];
			$markContentArray = array();
			$markContentArray['###PRICE###'] = $priceText;
			$markContentArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? tx_div2007_alpha::getLL($this->langObj, $taxInclExcl) : '');

			$this->cObj->substituteMarkerInObject($ptconf, $markContentArray);
			return $this->cObj->cObjGetSingle($this->conf['priceTagObj'], $ptconf);
		} else {
			return $priceText;
		}
	}

	/**
	 * Formatting a price
	 */
	public function priceFormat($double)	{
		return number_format($double,intval($this->conf['priceDec']),$this->conf['priceDecPoint'],$this->conf['priceThousandPoint']);
	} // priceFormat

	public function getItemMarkerArray ($functablename, $fieldname, &$row, $markerKey, &$markerArray, $tagArray, $theCode, $id, &$bSkip, $bHtml=true, $charset='', $prefix='', $imageRenderObj='')	{

		$priceArray = array();
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$prodTable = &$tablesObj->get($functablename);
		$modelObj = &$this->getModelObj();
		$marker = strtoupper($fieldname);
		$paymentshippingObj = &t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');

		$taxFromShipping = $paymentshippingObj->getReplaceTaxPercentage();
		$taxInclExcl = (isset($taxFromShipping) && is_double($taxFromShipping) && ($taxFromShipping == 0) ? 'tax_zero' : 'tax_included');

// tt-products-single-1-pricetax
		$priceArray = $modelObj->getPriceArray($fieldname,$row);

		$priceMarkerPrefix = $prodTable->marker.'_';
		foreach ($priceArray as $displayTax => $priceValue)	{
			$taxMarker = $priceMarkerPrefix.$marker.'_'.strtoupper($displayTax);
			$markerArray['###'.$taxMarker.'###'] = $this->printPrice($this->priceFormat($priceValue, $taxInclExcl));
			$markerArray['###'.$taxMarker.'_ID###'] = $id.str_replace('_','',$displayTax);
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_price_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_price_view.php']);
}


?>
