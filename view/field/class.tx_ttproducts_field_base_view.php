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
 * base class for all database table fields view classes
 *
 * $Id$
 *
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

require_once (PATH_BE_ttproducts.'view/field/interface.tx_ttproducts_field_view_int.php');


abstract class tx_ttproducts_field_base_view implements tx_ttproducts_field_view_int	{
	private $bHasBeenInitialised = false;
	public $modelObj;
	public $cObj;
	public $conf;		// original configuration
	public $config;		// modified configuration
	public $langObj;

	public function init (&$langObj, &$modelObj)	{
		$this->langObj = &$langObj;
		$this->modelObj = &$modelObj;
		$this->cObj = &$modelObj->cObj;
		$this->conf = &$modelObj->conf;
		$this->config = &$modelObj->config;

		$this->bHasBeenInitialised = true;
	}

	public function needsInit ()	{
		return !$this->bHasBeenInitialised;
	}

	public function &getModelObj ()	{
		return $this->modelObj;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_base_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_base_view.php']);
}


?>
