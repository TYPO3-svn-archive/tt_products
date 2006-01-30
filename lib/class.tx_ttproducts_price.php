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
 * Part of the tt_products (Shopping System) extension.
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



class tx_ttproducts_price {
	var $pibase;
	var $conf;					// original configuration
	var $config;				// modified configuration


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, &$conf, &$config)	{
		$this->pibase = &$pibase;
 		$this->conf = &$conf;
 		$this->config = &$config;
 		
	} // init


	/**
	 * Returns the $price with either tax or not tax, based on if $tax is true or false. This function reads the TypoScript configuration to see whether prices in the database are entered with or without tax. That's why this function is needed.
	 */
	function getPrice($price,$tax=1,$taxpercentage=0,$taxIncluded=0)	{
		global $TSFE;

		if ($taxpercentage==0)
			$taxFactor = 1 + doubleval($this->conf['TAXpercentage'])/100;
		else
			$taxFactor = 1+$taxpercentage/100;
	
		if ($TSFE->fe_user->user['tt_products_discount'] != 0) {
			$price = $price - ($price * ($TSFE->fe_user->user['tt_products_discount'] / 100));
		}

		$taxIncluded = ($taxIncluded ? $taxIncluded: $this->conf['TAXincluded']);
		if ($tax)	{
			if ($taxIncluded)	{	// If the configuration says that prices in the database is with tax included
				return doubleval($price);
			} else {
				return doubleval($price)*$taxFactor;
			}
		} else {
			if ($taxIncluded)	{	// If the configuration says that prices in the database is with tax included
				return doubleval($price)/$taxFactor;
			} else {
				return doubleval($price);
			}
		}
	} // getPrice



	// function using getPrice and considering a reduced price for resellers
	function getResellerPrice($row,$tax=1)	{
		$returnPrice = 0;
			// get reseller group number
		$priceNo = intval($this->config['priceNoReseller']);

		if ($priceNo > 0) {
			$returnPrice = $this->getPrice($row['price'.$priceNo],$tax,$row['tax']);
		}
		// normal price; if reseller price is zero then also the normal price applies
		if ($returnPrice == 0) {
			$returnPrice = $this->getPrice($row['price'],$tax,$row['tax']);
		}
		return $returnPrice;
	} // getResellerPrice


	/** mkl:
	 * For shop inside EU country: check if TAX should be included
	 */
	function checkVatInclude()	{
//		$include = 1;
//		if( $this->conf['TAXeu'] )	{
//			if( ($this->personInfo['country_code'] != '') && ($this->personInfo['country_code'] != $this->conf['countryCode']) )	{
//				$whereString =  'cn_iso_3 = "'.$this->personInfo['country_code'].'"';
//				$euMember = 0 ;
//				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','static_countries', $whereString);
//				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
//					$euMember = $row['cn_eu_member'];
//				}
//				// exclude VAT for EU companies with valid VAT id and for everyone outside EU
//				if( !$euMember  ||  ($euMember && $this->personInfo['vat_id'] != '') )	{
//					$include = 0;
//				}
//			}
//		}
//		return $include ;
	} // checkVatInclude



	/**
	 * Generate a graphical price tag or print the price as text
	 */
	function printPrice($priceText)
	{
		if (($this->conf['usePriceTag']) && (isset($this->conf['priceTagObj.'])))
		{
			$ptconf = $this->conf['priceTagObj.'];
			$markContentArray = array();
			$markContentArray['###PRICE###'] = $priceText;
			$this->pibase->cObj->substituteMarkerInObject($ptconf, $markContentArray);
			return $this->pibase->cObj->cObjGetSingle($this->conf['priceTagObj'], $ptconf);
		}
		else
			return $priceText;
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
