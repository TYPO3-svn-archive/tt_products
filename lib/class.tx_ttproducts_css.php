<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Franz Holzinger <franz@ttproducts.de>
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
 * functions for CSS matters
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_css {
	var $pibase; // reference to object of pibase
	public $conf;
	protected $isCssStyled;

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init ($pibase)	{
		global $TYPO3_DB;
		$this->pibase = $pibase;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->isCssStyled = ($cnf->conf['templateStyle'] == 'css-styled');
		$this->conf = &$cnf->conf['CSS.']['ALL.'];
	} // init

	public function isCSSStyled ()	{
		if (isset($this->conf) && is_array($this->conf) && $this->isCssStyled && $this->conf['file'] != '')	{
			$rc = TRUE;
		} else {
			$rc = FALSE;
		}
		return $rc;
	}

	public function &getConf ($tablename = '', $theCode = 'ALL')	{

		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$cssConf = $cnf->getSpecialConf('CSS', $tablename, $theCode);
		return $cssConf;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_css.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_css.php']);
}

?>