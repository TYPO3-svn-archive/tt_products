<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Franz Holzinger <franz@ttproducts.de>
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
 * model functions for a basket item object
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_basketitem {

	/**
	 * gets the quantity of an item
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		overwrite amount or 'basket'
	 * @return	array
	 * @access private
	 */
	function getQuantity (
		&$item,
		$overwriteAmount=''
	)	{
		global $TCA, $TSFE;

		$rc = $item['count'];
		if (
			$overwriteAmount != 'basket' &&
			(
				class_exists('t3lib_utility_Math') ?
				t3lib_utility_Math::canBeInterpretedAsInteger($overwriteAmount) :
				t3lib_div::testInt($overwriteAmount)
			)
		) {
			$rc = intval($overwriteAmount);
		}
		return $rc;
	}


	/**
	 * gets the minimum necessary quantity of an item
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		overwrite amount or 'basket'
	 * @return	array
	 * @access private
	 */
	function getMinQuantity (
		&$item
	)	{
		global $TCA, $TSFE;

		$row = $item['rec'];
		$rc = $row['basketminquantity'];
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$prodTable = &$tablesObj->get('tt_products', FALSE);
		$articleRow = $prodTable->getArticleRowFromExt($row);

// 		$extArray = $row['ext'];
//
// 		if (is_array($extArray) && is_array($extArray['tt_products_articles']) && is_array($extArray['tt_products_articles']['0']))	{
// 			$articleUid = $extArray['tt_products_articles']['0']['uid'];
// 			$articleTable = &$tablesObj->get('tt_products_articles', FALSE);
// 			$articleRow = $articleTable->get($articleUid);
// 			$rc = ($articleRow['basketminquantity'] != '' ? $articleRow['basketminquantity'] : $rc);
// 		}
		if (is_array($articleRow) && count($articleRow))	{
			$rc = ($articleRow['basketminquantity'] != '' ? $articleRow['basketminquantity'] : $rc);
		}
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basketitem.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basketitem.php']);
}


?>
