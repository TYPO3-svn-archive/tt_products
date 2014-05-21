<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * functions for the product
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_field_graduated_price_view extends tx_ttproducts_field_base_view {
	var $dataArray = array(); // array of read in products
	var $mmArray = array();
	var $tableObj;	// object of the type tx_table_db
	var $pibase;	// reference to object of pibase
	var $conf;
	var $config;
	var $tableconf;
	var $tabledesc;
	var $conftablename;	// table name of the configuration
	var $mm_table = ''; // mm table


	public function &getItemSubpartArrays (
		$templateCode,
		$markerKey,
		$functablename,
		&$row,
		$fieldname,
		$tableConf,
		&$subpartArray,
		&$wrappedSubpartArray,
		&$tagArray,
		$theCode = '',
		$id = '1'
	) {
		global $TCA;

		$priceTablesViewObj = t3lib_div::getUserObj('&tx_ttproducts_graduated_price_view');
		$priceTablesViewObj->getItemSubpartArrays($templateCode, $row, $fieldname, $subpartArray, $wrappedSubpartArray, $tagArray, $theCode, $id);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$functablename: ...
	 * @param	[type]		$fieldname: ...
	 * @param	[type]		$row: ...
	 * @param	[type]		$markerKey: ...
	 * @param	[type]		$markerArray: ...
	 * @param	[type]		$tagArray: ...
	 * @param	[type]		$theCode: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$bSkip: ...
	 * @param	[type]		$bHtml: ...
	 * @param	[type]		$charset: ...
	 * @param	[type]		$prefix: ...
	 * @param	[type]		$imageRenderObj: ...
	 * @return	[type]		...
	 */
	function getItemMarkerArray ($functablename, $fieldname, &$row, $markerKey, &$markerArray, $tagArray, $theCode, $id, &$bSkip, $bHtml=true, $charset='', $prefix='', $imageRenderObj='')	{

		$priceTablesViewObj = t3lib_div::getUserObj('&tx_ttproducts_graduated_price_view');
		$priceTablesViewObj->getItemMarkerArray ($row, $markerArray, $tagArray);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_graduated_price_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_graduated_price_view.php']);
}


?>