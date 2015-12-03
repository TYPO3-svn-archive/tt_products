<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Franz Holzinger <franz@ttproducts.de>
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
 * control functions for a product item object
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

// require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_model_control.php');


class tx_ttproducts_control_product {

	/**
	 */
	static public function &getPresetVariantArray (
		$uid
	)	{
		$basketVar = tx_ttproducts_model_control::getBasketVar();
		$presetVarianArray = array();
		$basketArray = t3lib_div::_GP($basketVar);
		if (
			isset($basketArray) && is_array($basketArray) &&
			isset($basketArray[$uid]) && is_array($basketArray[$uid])
		)	{
			$presetVarianArray = $_POST[$basketVar][$uid];
		}

		return $presetVarianArray;
	} // getPresetVariantArray


	static public function getActiveArticleNo ()	{
		$piVars = tx_ttproducts_model_control::getPiVars();
		$piVar = tx_ttproducts_model_control::getPiVar('tt_products_articles');
		$rc = FALSE;
		if (isset($piVars) && is_array($piVars) && isset($piVars[$piVar]))	{
			$rc = $piVars[$piVar];
		}
		return $rc;
	}
}



?>