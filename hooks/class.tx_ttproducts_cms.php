<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Franz Holzinger <contact@fholzinger.com>
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
 * hook functions for the TYPO3 cms
 *
 * $Id$
 *
 * @author	Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com> 
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

global $TYPO3_CONF_VARS;


class tx_ttproducts_cms {
	var $cObj;
	var $conf;
	var $config;
	var $markerArray;
	var $globalMarkerArray;
	var $urlArray;


	/**
	 * Draw the item in the page module
	 *
	 * @param	array		parameters
	 * @param	object		the parent object
	 * @return	  string
	 */
	function pmDrawItem(&$params, &$pObj)	{
		if (defined ('PATH_BE_div2007') && $pObj->pageRecord['doktype'] == 1 && $params['row']['pi_flexform'])	{
			include_once (PATH_BE_div2007.'class.tx_div2007_ff.php');

			tx_div2007_ff::load($params['row']['pi_flexform'],TT_PRODUCTS_EXTkey);
			$codes = 'CODE: '.tx_div2007_ff::get(TT_PRODUCTS_EXTkey, 'display_mode');
		}
		return $codes;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_cms.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_cms.php']);
}

?>
