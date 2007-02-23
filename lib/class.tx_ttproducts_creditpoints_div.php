<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Els Verberne <verberne@bendoo.nl>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
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
 * creditpoints functions
 *
 * $Id$
 *
 * @author  Els Verberne <verberne@bendoo.nl>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_creditpoints_div {

	/**
	 * Returns the number of creditpoints for the frontend user
	 */
	function getCreditPoints($amount)	{
		$type = '';
		$creditpoints = 0;
		foreach ($this->conf['creditpoints.'] as $k1=>$priceCalcTemp) {
			if (is_array($priceCalcTemp)) {
				foreach ($priceCalcTemp as $k2=>$v2) {
					if (!is_array($v2)) {
						switch ($k2) {
							case 'type':
								$type = $v2;
								break;
						}
					}
				}

				$dumCount = 0;
				$creditpoints = doubleval($priceCalcTemp['prod.']['1']);

				if ($type != 'price') {
					break;
				}
				krsort($priceCalcTemp['prod.']);
				reset($priceCalcTemp['prod.']);
	
				foreach ($priceCalcTemp['prod.'] as $k2=>$points) {
					if ($amount >= intval($k2)) { // only the highest value for this count will be used; 1 should never be reached, this would not be logical
						$creditpoints = $points;
						break; // finish
					}
				}
			}
		}
		return $creditpoints;
	} // getCreditPoints



	/**
	 * adds the number of creditpoints for the frontend user
	 */
	function addCreditPoints($username, $creditpoints)  {
		global $TYPO3_DB;

		if ($username) {
			$uid_voucher = '';
			// get the "old" creditpoints for the user
			$res1 = $TYPO3_DB->exec_SELECTquery('uid, tt_products_creditpoints', 'fe_users', 'username='.$TYPO3_DB->fullQuoteStr($username,'tt_products_creditpoints'));
			if ($row = $TYPO3_DB->sql_fetch_assoc($res1)) {
				$ttproductscreditpoints = $row['tt_products_creditpoints'];
				$uid_voucher = $row['uid'];
			}
			if ($uid_voucher) {
				$fieldsArrayFeUserCredit = array();
				$fieldsArrayFeUserCredit['tt_products_creditpoints'] = $ttproductscreditpoints + $creditpoints;
		
				$TYPO3_DB->exec_UPDATEquery('fe_users', 'uid='.intval($uid_voucher), $fieldsArrayFeUserCredit);
			}
		}
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_creditpoints_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_creditpoints_div.php']);
}


?>
