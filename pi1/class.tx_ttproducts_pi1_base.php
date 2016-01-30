<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2012 Franz Holzinger <franz@ttproducts.de>
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
 * Creates a list of products for the shopping basket in TYPO3.
 * Also controls basket, searching and payment.
 *
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 * @see file tt_products/static/old_style/constants.txt
 * @see TSref
 *
 *
 */

tx_div2007_core::activateCompatibility6();

class tx_ttproducts_pi1_base extends tslib_pibase {
	var $prefixId = 'tt_products';	// Same as class name
	var $scriptRelPath = 'pi1/class.tx_ttproducts_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = TT_PRODUCTS_EXT;	// The extension key.
	var $bRunAjax = false;			// overrride this


	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 *
	 * @param	[type]		$content: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function main ($content,$conf)	{
		global $TSFE;

		if ($conf['templateFile'] != '')	{

			$this->pi_setPiVarDefaults();
			$this->conf = &$conf;
			$config = array();
			$mainObj = t3lib_div::getUserObj('&tx_ttproducts_main');	// fetch and store it as persistent object
			$errorCode = array();
			$bDoProcessing = $mainObj->init ($this, $content, $conf, $config, $errorCode);

			if ($bDoProcessing)	{
				$content = $mainObj->run($content);
			}
		} else {
			tx_div2007_alpha5::loadLL_fh002($this, 'EXT:' . TT_PRODUCTS_EXT . '/pi1/locallang.xml');
			$content = tx_div2007_alpha5::getLL_fh002($this,'no_template').' plugin.tt_products.templateFile';
		}

		return $content;
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$bRunAjax: ...
	 * @return	[type]		...
	 */
	function set ($bRunAjax)	{
		$this->bRunAjax = $bRunAjax;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/pi1/class.tx_ttproducts_pi1_base.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/pi1/class.tx_ttproducts_pi1_base.php']);
}


?>