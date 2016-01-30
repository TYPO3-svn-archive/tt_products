<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * dummy variant class for tables which do not have any variants
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_variant_dummy_view implements tx_ttproducts_variant_view_int {
	public $modelObj;
	public $cObj;
	public $langObj;

	public function init($langObj, $modelObj)	{
		$this->langObj = $langObj;
		$this->cObj = $langObj->cObj;
		$this->modelObj = $modelObj;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$subpartArray: ...
	 * @param	[type]		$markerArray: ...
	 * @param	[type]		$row: ...
	 * @param	[type]		$tempContent: ...
	 * @param	[type]		$useSelects: ...
	 * @param	[type]		$conf: ...
	 * @param	[type]		$bHasAdditional: ...
	 * @param	[type]		$bGiftService: ...
	 * @return	[type]		...
	 */
	function getVariantSubpartMarkerArray (&$subpartArray, &$markerArray, &$row, &$tempContent, $useSelects, &$conf, $bHasAdditional, $bGiftService)  {
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$markerArray: ...
	 * @param	[type]		$subpartArray: ...
	 * @param	[type]		$row: ...
	 * @param	[type]		$conf: ...
	 * @param	[type]		$bHasAdditional: ...
	 * @param	[type]		$bGiftService: ...
	 * @return	[type]		...
	 */
	function removeEmptyMarkerSubpartArray (&$markerArray, &$subpartArray, &$row, &$conf, $bHasAdditional, $bGiftService) {
	}

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_variant_dummy_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_variant_dummy_view.php']);
}


?>