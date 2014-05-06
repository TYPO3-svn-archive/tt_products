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
 * eID compatible AJAX functions
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_ttproducts.'control/class.tx_ttproducts_javascript.php');


class tx_ttproducts_ajax {
	var $taxajax;	// xajax object
	var $conf; 	// conf coming from JavaScript via Ajax

	/**
	 * initialization
	 *
	 * @return	void
	 */
	function init()	{
		global $TSFE;

// 		include_once(PATH_BE_taxajax.'class.tx_taxajax.php');
		$this->taxajax = t3lib_div::makeInstance('tx_taxajax');
		$this->taxajax->setCharEncoding('utf-8');
	}


	/**
	 * set the setup configuration
	 *
	 * @param	array		$$conf: setup
	 * @return	void
	 */
	function setConf(&$conf)	{
		$this->conf = $conf;
	}


	/**
	 * get the configuration
	 *
	 * @return	array		setup
	 */
	function &getConf()	{
		return $this->conf;
	}

	/**
	 * main code
	 *
	 * @param	boolean		$debug: enable or disable debug for Ajax
	 * @return	void
	 */
	function main ($debug)	{

			// Encoding of the response to utf-8.
		// $this->taxajax->setCharEncoding('utf-8');
			// Do you want messages in the status bar?
		// $this->taxajax->statusMessagesOn();

			// Decode form vars from utf8
		// $this->taxajax->decodeUTF8InputOn();

			// Turn only on during testing
		if ($debug)	{
			$this->taxajax->debugOn();
		} else	{
			$this->taxajax->debugOff();
		}
			// To prevent conflicts, prepend the extension prefix.
		// $this->taxajax->setWrapperPrefix($this->prefixId);
		$this->taxajax->setWrapperPrefix('');

// 		$param = '&FE_SESSION_KEY='.rawurlencode(
// 			$TSFE->fe_user->id.'-'.
// 				md5(
// 				$TSFE->fe_user->idcheckRequired.'/'.
// 				$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
// 				)
// 			);

			// Encoding of the response to FE charset
	//	$reqURI = t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT') . '?' . t3lib_div::getIndpEnv('QUERY_STRING');
		$reqURI = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
		if (strpos($reqURI, '?'))	{
			$nextDelimiter = '&';
		} else {
			$nextDelimiter = '?';
		}
		$reqURI .= $nextDelimiter.'no_cache=1&eID='.TT_PRODUCTS_EXT.$param;
		// $reqURI = htmlspecialchars ($reqURI,ENT_QUOTES,$charset);  ==> funktioniert mit einigen Browsern nicht!
		// $reqURI = tx_ttproducts_javascript::jsspecialchars($reqURI);
		$this->taxajax->setRequestURI($reqURI);
	}
}


?>