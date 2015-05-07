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
 * functions for the TypoScript conditions
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

require_once(PATH_BE_ttproducts . 'control/class.tx_ttproducts_control_basket.php');


class tx_ttproducts_match_condition {

	/**
	 * Adds link markers to a wrapped subpart array
	 */
	public function checkShipping (
		$params
	)	{
		global $TSFE;

		$rc = FALSE;

		if (isset($params) && is_array($params))	{
			tx_ttproducts_control_basket::init();
			$infoArray = tx_ttproducts_control_basket::getInfoArray();
			tx_ttproducts_control_basket::fixCountries($infoArray);

			$type = $params['0'];
			$field = $params['1'];
			$value = ltrim($params['2'],' =');
			$rc = ($infoArray[$type][$field] == $value);
			tx_ttproducts_control_basket::destruct();
		}

		return $rc;
	}


	public function hasBulkilyItem ($where) {
		global $TSFE, $TYPO3_DB;

		tx_ttproducts_control_basket::init();
		$recs = tx_ttproducts_control_basket::getRecs();

		include_once (PATH_tslib.'class.tslib_content.php');
		$cObj = &t3lib_div::makeInstance('tslib_cObj');


		$basketExt = tx_ttproducts_control_basket::getBasketExt();

		if (isset($basketExt) && is_array($basketExt))	{

			$uidArr = array();

			foreach($basketExt as $uidTmp => $tmp)	{
				if ($uidTmp != 'gift' && !in_array($uidTmp, $uidArr))	{
					$uidArr[] = intval($uidTmp);
				}
			}

			if (count($uidArr) == 0) {
				return FALSE;
			}
			$where .= ' AND uid IN ('.implode(',',$uidArr).')';
		}

		$where .= $cObj->enableFields('tt_products');

		$rcArray = $TYPO3_DB->exec_SELECTgetRows('*','tt_products',$where);
		$bBukily = FALSE;
		foreach ($rcArray as $uid => $row)	{
			if ($row['bulkily'])	{
				$bBukily = TRUE;
				break;
			}
		}

		tx_ttproducts_control_basket::destruct();
		return ($bBukily);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_match_condition.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_match_condition.php']);
}


?>
