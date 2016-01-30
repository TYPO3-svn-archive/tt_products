<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2007 Franz Holzinger <contact@fholzinger.com>
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
 * class for language texts. This shall replace pi_base.
 *
 * $Id$
 *
 * @author	Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */




class tx_ttproducts_language extends tx_div2007_alpha_language_base {
	public function init ($pObj, $cObj, &$conf, $scriptRelPath)	{

		parent::init(
			$cObj,
			TT_PRODUCTS_EXT,
			$conf,
			$scriptRelPath
		);

		if (isset($pObj->LOCAL_LANG) && is_array($pObj->LOCAL_LANG))	{
			$this->LOCAL_LANG = &$pObj->LOCAL_LANG;
		}
		if (isset($pObj->LOCAL_LANG_charset) && is_array($pObj->LOCAL_LANG_charset))	{
			$this->LOCAL_LANG_charset = &$pObj->LOCAL_LANG_charset;
		}
		if (isset($pObj->LOCAL_LANG_loaded) && is_array($pObj->LOCAL_LANG_loaded))	{
			$this->LOCAL_LANG_loaded = &$pObj->LOCAL_LANG_loaded;
		}
	}

	public function getLanguage ()	{
		global $TSFE;

		if (isset($TSFE->config) && is_array($TSFE->config) && isset($TSFE->config['config']) && is_array($TSFE->config['config']))	{
			$rc = $TSFE->config['config']['language'];
		} else {
			$rc = 'default';
		}
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_language.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_language.php']);
}


?>