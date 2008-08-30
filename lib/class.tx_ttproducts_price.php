<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005-2006 Franz Holzinger <kontakt@fholzinger.com>
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
 * Part of the tt_products (Shop System) extension.
 *
 * basket price calculation functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *  
 */

global $TYPO3_CONF_VARS;


class tx_ttproducts_price {
	var $pibase;
	var $conf;					// original configuration
	var $config;				// modified configuration
	var $cnf; 					// configuration object
	var $basket;
	var $paymentshipping;		// payment and shipping object to make the price dependant on it
	var $taxIncluded;			// if tax is already included in the price

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, &$conf, &$cnf, &$basket, &$paymentshipping)	{
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$conf;
		if (!isset($this->conf['TAXincluded']))	{
			$this->conf['TAXincluded'] = '1';	// default '1' for TAXincluded
		}
		$this->taxIncluded = $this->conf['TAXincluded'];
		$this->config = &$this->cnf->config;
 		$this->basket = &$basket;
 		$this->paymentshipping = &$paymentshipping;
	} // init


	/**
	 * Changes the string value to integer or float and considers the German float ',' separator
	 *
	 * @param		bool	convert to float?
	 * @param		string	quantity
	 * @return	    float or integer string value
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

	function getTaxIncluded ()	{
		return $this->taxIncluded; 
	}

	function getPriceTax($price, $bTax, $taxIncluded, $taxFactor)	{
		if ($bTax)	{
			if ($taxIncluded)	{	// If the configuration says that prices in the database is with tax included
				$rc = $price;
			} else {
				$rc = $price*$taxFactor;
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
	 */
	function getModePrice($taxMode,$price,$tax=true,&$taxpercentage,$taxIncluded=false,$bEnableTaxZero=false)	{
		$rc = $this->getPrice($price,$tax,$taxpercentage,$taxIncluded,$bEnableTaxZero);
		if ($taxMode == '2')	{
			$rc = round ($rc, 2);
		}
		return $rc;
	}

	/** reduces price by discount for FE user **/
	function getDiscountPrice($price, $discount)	{
		if (floatval($discount) != 0)	{
			$price = $price - ($price * ($discount / 100));
		}
		return $price;
	}

	/**
	 * Returns the $price with either tax or not tax, based on if $tax is true or false. 
	 * This function reads the TypoScript configuration to see whether prices in the database 
	 * are entered with or without tax. That's why this function is needed.
	 */
	function getPrice($price,$tax=true,&$taxpercentage,$taxIncluded=false,$bEnableTaxZero=false)	{
		global $TSFE;

		$rc = 0;
		$bTax = ($tax==1);

//		if (!$this->checkVatInclude())	{
//			$bTax = false;
//		}
		$price = $this->toNumber(true, $price);

		if (doubleval($taxpercentage) == 0 && !$bEnableTaxZero)	{
			$taxpercentage = doubleval($this->conf['TAXpercentage']);
		}

		$taxFactor = 1 + $taxpercentage / 100;
		// $taxIncluded = ($taxIncluded ? $taxIncluded : $this->conf['TAXincluded']);
		$taxFromShipping = $this->paymentshipping->getReplaceTAXpercentage(); 		// if set then this has a tax which will override the tax of the products

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
	function getResellerPrice($row,$tax=1)	{
		$returnPrice = 0;
			// get reseller group number
		$priceNo = intval($this->config['priceNoReseller']);

		if ($priceNo > 0) {
			$returnPrice = $this->getPrice($row['price'.$priceNo],$tax,$row['tax'],$this->conf['TAXincluded']);
		}
		// normal price; if reseller price is zero then also the normal price applies
		if ($returnPrice == 0) {
			$returnPrice = $this->getPrice($row['price'],$tax,$row['tax'],$this->conf['TAXincluded']);
		}
		return $returnPrice;
	} // getResellerPrice


	/**
	 * Generate a graphical price tag or print the price as text
	 */
	function printPrice($priceText,$taxInclExcl='')
	{
		if (($this->conf['usePriceTag']) && (isset($this->conf['priceTagObj.'])))	{
			$ptconf = $this->conf['priceTagObj.'];
			$markContentArray = array();
			$markContentArray['###PRICE###'] = $priceText;
			// $taxInclExcl = ($tax ? 'tax_included' : 'tax_zero');
			$markContentArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? $this->pibase->pi_getLL($taxInclExcl) : '');
			
			// $taxFromShipping = $this->paymentshipping->getReplaceTAXpercentage(); 		// if set then this has a tax which will override the tax of the products
			
			$this->pibase->cObj->substituteMarkerInObject($ptconf, $markContentArray);
			return $this->pibase->cObj->cObjGetSingle($this->conf['priceTagObj'], $ptconf);
		}
		else	{
			return $priceText;
		}
	}


	/**
	 * Formatting a price
	 */
	function priceFormat($double)	{
		return number_format($double,intval($this->conf['priceDec']),$this->conf['priceDecPoint'],$this->conf['priceThousandPoint']);
	} // priceFormat

	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_price.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_price.php']);
}


?>
