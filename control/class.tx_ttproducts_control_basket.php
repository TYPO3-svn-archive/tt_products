<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2010 Franz Holzinger <franz@ttproducts.de>
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
 * control function for the basket.
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

require_once(PATH_BE_ttproducts . 'model/class.tx_ttproducts_pid_list.php');
require_once(PATH_BE_ttproducts . 'lib/class.tx_ttproducts_paymentshipping.php');


class tx_ttproducts_control_basket {
	protected static $recs;
	protected static $basketExt;
	private static $bHasBeenInitialised = FALSE;


	public static function init ()	{
		if (!self::$bHasBeenInitialised)	{
			self::$recs = self::getStoredRecs();
			self::$basketExt = self::getStoredBasketExt();
			self::$bHasBeenInitialised = TRUE;
		}
	}


	public static function getRecs ()	{
		return self::$recs;
	}


	public static function setRecs ($recs) {
		self::$recs = $recs;
	}


	public static function getStoredRecs ()	{
		global $TSFE;

		$rc = $TSFE->fe_user->getKey('ses','recs');
		return $rc;
	}


	public static function getBasketExt ()	{
		return self::$basketExt;
	}


	public static function getStoredBasketExt ()	{
		global $TSFE;

		$rc = $TSFE->fe_user->getKey('ses','basketExt');
		return $rc;
	}


	public static function getInfoArray ()	{
		$formerBasket = self::getRecs();

		$infoArray = array();

		if (isset($formerBasket) && is_array($formerBasket))	{
			$infoArray['billing'] = $formerBasket['personinfo'];
			$infoArray['delivery'] = $formerBasket['delivery'];
		}
		if (!$infoArray['billing'])	{
			$infoArray['billing'] = array();
		}
		if (!$infoArray['delivery'])	{
			$infoArray['delivery'] = array();
		}
		return $infoArray;
	}


	public static function fixCountries (&$infoArray)	{
		$rc = FALSE;

		if (
			$infoArray['billing']['country_code'] != '' &&
			(
				$infoArray['delivery']['zip'] == '' ||
				($infoArray['delivery']['zip'] != '' && $infoArray['delivery']['zip'] == $infoArray['billing']['zip'])
			)
		)	{
			// a country change in the select box shall be copied
			$infoArray['delivery']['country_code'] = $infoArray['billing']['country_code'];
			$rc = TRUE;
		}
		return $rc;
	}


	public static function destruct () {
		self::$bHasBeenInitialised = FALSE;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_basket.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_basket.php']);
}


?>
