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
 * article functions without object instance
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

require_once (PATH_BE_ttproducts.'model/int.tx_ttproducts_variant_int.php');


class tx_ttproducts_variant_dummy implements tx_ttproducts_variant_int {
	var $bSelectableArray = array();

	/**
	 * setting the local variables
	 *
	 * @param	[type]		$$itemTable: ...
	 * @param	[type]		$tablename: ...
	 * @param	[type]		$useArticles: ...
	 * @return	[type]		...
	 */
	function init(&$itemTable, $tablename, $useArticles)  {

	} // init


	/**
	 * fills in the row fields from the variant extVar string
	 *
	 * @param	array		the row
	 * @param	string		variants separated by ';'
	 * @return	void
	 * @access private
	 * @see getVariantFromRow
	 */
	 function modifyRowFromVariant (&$row, $variant) {
	}


	/**
	 * Returns the variant extVar string from the variant values in the row
	 *
	 * @param	array		the row
	 * @return	string		variants separated by ';'
	 * @access private
	 * @see modifyRowFromVariant
	 */
	function getVariantFromRow (&$row) {
	}


	/**
	 * Returns the variant extVar string from the incoming raw row into the basket
	 *
	 * @param	array		the basket raw row
	 * @return	string		variants separated by ';'
	 * @access private
	 * @see modifyRowFromVariant
	 */
	function getVariantFromRawRow (&$row) {
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function getFirstVariantRow($row='')	{
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function getTableUid ($table, $uid)	{
		$rc = '|'.$table.'|'.$uid;
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getSelectableArray()	{
		return $this->bSelectableArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$productRow: ...
	 * @param	[type]		$articleRows: ...
	 * @return	[type]		...
	 */
	function fetchArticle($productRow, $articleRows) {
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getFieldArray()	{
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getAdditionalKey()	{
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_variant_dummy.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_variant_dummy.php']);
}


?>
