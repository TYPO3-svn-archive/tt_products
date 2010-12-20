<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * basket price calculation functions
 *
 * $Id $
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_ttproducts.'model/field/interface.tx_ttproducts_field_int.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_paymentshipping.php');


class tx_ttproducts_field_price implements tx_ttproducts_field_int {
	private $bHasBeenInitialised = false;
	private $taxIncluded;	// if tax is already included in the price
	var $priceConf; 	// price configuration
	public $priceFieldArray = array ('price', 'price2', 'pricetax', 'price2tax', 'priceonlytax', 'price2onlytax', 'pricenotax', 'price2notax');

	/**
	 * Getting all tt_products_cat categories into internal array
	 * Here $conf needs not be a member of $cnf in order to have local settings e.g. with shipping
	 *
	 * @param	[type]		$$cObj: ...
	 * @param	[type]		$priceConf: ...
	 * @return	[type]		...
	 */
	function init(&$cObj, &$priceConf)	{
		$this->priceConf = &$priceConf;
		if (!isset($this->priceConf['TAXincluded']))	{
			$this->priceConf['TAXincluded'] = '1';	// default '1' for TAXincluded
		}
		$this->setTaxIncluded($this->priceConf['TAXincluded']);
		$this->bHasBeenInitialised = true;
	} // init


	function needsInit()	{
		return !$this->bHasBeenInitialised;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$row: ...
	 * @param	[type]		$fieldname: ...
	 * @return	[type]		...
	 */
	function getFieldValue($row, $fieldname)	{
		return $row[$fieldname];
	}


	/**
	 * Changes the string value to integer or float and considers the German float ',' separator
	 *
	 * @param	bool		convert to float?
	 * @param	string		quantity
	 * @return	float		or integer string value
	 */
	function toNumber($bToFloat, $text)	{
		$rc = '';
		if ($bToFloat)	{
			$text = (string) $text;
			// enable the German display of float
			$rc = (float) str_replace (',', '.', $text);
		} else {
			$rc = (int) $text;
		}

		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getTaxIncluded ()	{
		return $this->taxIncluded;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$bTaxIncluded: ...
	 * @return	[type]		...
	 */
	function setTaxIncluded ($bTaxIncluded=TRUE)	{
		$this->taxIncluded = $bTaxIncluded;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$price: ...
	 * @param	[type]		$bTax: ...
	 * @param	[type]		$taxIncluded: ...
	 * @param	[type]		$taxFactor: ...
	 * @return	[type]		...
	 */
	function getPriceTax($price, $bTax, $taxIncluded, $taxFactor)	{
		if ($bTax)	{
			if ($taxIncluded)	{	// If the configuration says that prices in the database is with tax included
				$rc = $price;
			} else {
				$rc = $price * $taxFactor;
			}
		} else {
			if ($taxIncluded)	{	// If the configuration says that prices in the database is with tax included
				$rc = $price/$taxFactor;
			} else {
				$rc = $price;
			}
		}
		return $rc;
	}

	/**
	 * return the price with tax mode considered
	 *
	 * @param	[type]		$taxMode: ...
	 * @param	[type]		$price: ...
	 * @param	[type]		$tax: ...
	 * @param	[type]		$taxpercentage: ...
	 * @param	[type]		$taxIncluded: ...
	 * @param	[type]		$bEnableTaxZero: ...
	 * @return	[type]		...
	 */
	function getModePrice($taxMode, $price,$tax=true,$taxpercentage,$taxIncluded=false,$bEnableTaxZero=false)	{
		$rc = $this->getPrice($price,$tax,$taxpercentage,$taxIncluded,$bEnableTaxZero);
		if ($taxMode == '2')	{
			$rc = round ($rc, 2);
		}
		return $rc;
	}


	/**
	 * reduces price by discount for FE user
	 *
	 * @param	[type]		$price: ...
	 * @param	[type]		$discount: ...
	 * @return	[type]		...
	 */
	function getDiscountPrice($price, $discount='')	{
		if ($discount == '')	{
			$discount = $this->discount;
		}
		if (floatval($discount) != 0)	{
			$price = $price - ($price * ($discount / 100));
		}
		return $price;
	}


	/**
	 * Returns the $price with either tax or not tax, based on if $tax is true or false.
	 * This function reads the TypoScript configuration to see whether prices in the database
	 * are entered with or without tax. That's why this function is needed.
	 *
	 * @param	[type]		$price: ...
	 * @param	[type]		$tax: ...
	 * @param	[type]		$taxpercentage: ...
	 * @param	[type]		$taxIncluded: ...
	 * @param	[type]		$bEnableTaxZero: ...
	 * @return	[type]		...
	 */
	function getPrice($price, $tax=true, $taxpercentage, $taxIncluded=false, $bEnableTaxZero=false)	{
		global $TSFE;

		$rc = 0;
		$bTax = ($tax==1);

//		if (!$this->checkVatInclude())	{
//			$bTax = false;
//		}
		$price = $this->toNumber(true, $price);

		if (doubleval($taxpercentage) == 0 && !$bEnableTaxZero)	{
			$taxpercentage = doubleval($this->priceConf['TAXpercentage']);
		}

//		Buch 'Der TYPO3 Webshop'
// 		if (doubleval($taxpercentage) == -1)  {
// 			$taxpercentage = 0;
// 		}

		$taxFactor = 1 + $taxpercentage / 100;
		// $taxIncluded = ($taxIncluded ? $taxIncluded : $this->conf['TAXincluded']);

		$paymentshippingObj = &t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
		if (isset($paymentshippingObj) && is_object($paymentshippingObj))	{
			$taxFromShipping = $paymentshippingObj->getReplaceTaxPercentage();	// if set then this has a tax which will override the tax of the products
		}

		if (isset($taxFromShipping) && is_double($taxFromShipping))	{
//			$bUseTaxFromShopping = true;
			$newtaxFactor = 1 + $taxFromShipping / 100;
			$corrTaxFactor = 1;
			// we need the net price in order to apply another tax
			if ($taxIncluded)	{
				$price = $price / $taxFactor;
				$taxIncluded = false;
			}
			$taxFactor = $newtaxFactor * $corrTaxFactor;
		}

		$rc = $this->getPriceTax($price, $bTax, $taxIncluded, $taxFactor);
		return $rc;
	} // getPrice


	// function using getPrice and considering a reduced price for resellers
	function getResellerPrice($row, $tax=1, $priceNo='')	{
		$returnPrice = 0;
		if (!t3lib_div::testInt($priceNo))	{
				// get reseller group number
			$priceNo = intval($this->priceConf['priceNoReseller']);
		}

		if ($priceNo > 0) {
			$returnPrice = $this->getPrice($row['price'.$priceNo],$tax,$row['tax'],$this->priceConf['TAXincluded']);
		}
		// normal price; if reseller price is zero then also the normal price applies
		if ($returnPrice == 0) {
			$returnPrice = $this->getPrice($row['price'],$tax,$row['tax'],$this->priceConf['TAXincluded']);
		}
		return $returnPrice;
	} // getResellerPrice

	// fetches all calculated prices for a row
	function getPriceArray ($fieldname, $row)	{
		if ($fieldname == 'price')	{
			$priceArray['tax'] = $this->getResellerPrice($row,1);
			$priceArray['no_tax'] = $this->getResellerPrice($row,0);
			$price0Tax = $this->getResellerPrice($row,1,0);
		} else {
			$value = $row[$fieldname];
			$priceArray['tax'] = $this->getPrice($value,1,$row['tax'],$this->conf['TAXincluded']);
			$priceArray['no_tax'] = $this->getPrice($value,0,$row['tax'],$this->conf['TAXincluded']);
		}

		$priceArray['only_tax'] = $priceArray['tax'] - $priceArray['no_tax'];
		return $priceArray;
	}

	public function getPriceFieldArray ()	{
		return $this->priceFieldArray;
	}

}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/field/class.tx_ttproducts_field_price.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/field/class.tx_ttproducts_field_price.php']);
}


?>
