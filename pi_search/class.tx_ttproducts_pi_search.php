<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2008 Franz Holzinger <contact@fholzinger.com>
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
 * Search plugins for the shop system.
 *
 *
 * $Id$
 *
 * @author	Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 * @see file tt_products/static/old_style/constants.txt
 * @see TSref
 *
 */

require_once (PATH_BE_div2007.'class.tx_div2007_alpha.php');
require_once (PATH_BE_ttproducts.'pi_search/class.tx_ttproducts_pi_search_base.php');


class tx_ttproducts_pi_search {
	/**
	 * The backReference to the mother cObj object set at call time
	 *
	 * @var tslib_cObj
	 */
	var $cObj;

	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	public function main($content, &$conf)	{

		$pibaseObj = &t3lib_div::getUserObj('&tx_ttproducts_pi_search_base');
		$pibaseObj->cObj = &$this->cObj;

		if ($conf['templateFile'] != '')	{

			$content = $pibaseObj->main($content,$conf);
		} else {
			tx_div2007_alpha::loadLL_fh001($pibaseObj,'EXT:'.TT_PRODUCTS_EXTkey.'/pi_search/locallang.xml');

			$content = tx_div2007_alpha::getLL($pibaseObj,'no_template').' plugin.tt_products_pi_search.templateFile';
		}

		return $content;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/pi1/class.tx_ttproducts_pi_search.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/pi1/class.tx_ttproducts_pi_search.php']);
}

?>
