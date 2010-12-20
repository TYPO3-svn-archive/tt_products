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
class tx_ttproducts_language {
	/**
	 * The backReference to the mother cObj object set at call time
	 *
	 * @var tslib_cObj
	 */
	public $cObj;
	public $prefixId = 'tx_ttproducts_pi1';	// Should be same as classname of the plugin, used for CSS classes, variables
	public $scriptRelPath = 'pi1/class.tx_ttproducts_pi1.php';	// Path to the plugin class script relative to extension directory, eg. 'pi1/class.tx_newfaq_pi1.php'

	public $extKey = TT_PRODUCTS_EXTkey;	// Extension key.
	public $piVars = Array (	// This is the incoming array by name $this->prefixId merged between POST and GET, POST taking precedence. Eg. if the class name is 'tx_myext' then the content of this array will be whatever comes into &tx_myext[...]=...
		'pointer' => '',		// Used as a pointer for lists
		'mode' => '',			// List mode
		'sword' => '',			// Search word
		'sort' => '',			// [Sorting column]:[ASC=0/DESC=1]
	);

	public $LOCAL_LANG = Array();		// Local Language content
	public $LOCAL_LANG_charset = Array();	// Local Language content charset for individual labels (overriding)
	public $LOCAL_LANG_loaded = 0;		// Flag that tells if the locallang file has been fetch (or tried to be fetch) already.
	public $LLkey='default';		// Pointer to the language to use.
	public $altLLkey='';			// Pointer to alternative fall-back language to use.
	public $LLtestPrefix='';		// You can set this during development to some value that makes it easy for you to spot all labels that ARe delivered by the getLL function.
	public $LLtestPrefixAlt='';		// Save as LLtestPrefix, but additional prefix for the alternative value in getLL() function calls

	/**
	 * Should normally be set in the main function with the TypoScript content passed to the method.
	 *
	 * $conf[LOCAL_LANG][_key_] is reserved for Local Language overrides.
	 * $conf[userFunc] / $conf[includeLibs]  reserved for setting up the USER / USER_INT object. See TSref
	 */
	public $conf = Array();


	public function init (&$cObj, &$conf)	{
		global $TSFE;

		$this->cObj = &$cObj;
		$this->conf = &$conf;
		if ($TSFE->config['config']['language'])	{
			$this->LLkey = $TSFE->config['config']['language'];
			if ($TSFE->config['config']['language_alt'])	{
				$this->altLLkey = $TSFE->config['config']['language_alt'];
			}
		}
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_language.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_language.php']);
}


?>
