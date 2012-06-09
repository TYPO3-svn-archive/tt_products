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
 * functions for the voucher system
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_voucher_view extends tx_ttproducts_table_base_view {
	var $amount;
	var $code;
	var $bValid;
	var $marker = 'VOUCHER';
	var $usedCodeArray = array();


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for the voucher
	 *
	 * @param	[type]		$&$subpartArray: ...
	 * @param	[type]		$wrappedSubpartArray: ...
	 * @param	[type]		$charset: ...
	 * @return	void
	 * @access private
	 */
	function getsubpartMarkerArray (
		&$subpartArray,
		&$wrappedSubpartArray,
		$charset=''
	)	{
		$modelObj = &$this->getModelObj();
		$subpartArray['###SUB_VOUCHERCODE###'] = '';

		if ($modelObj->getValid())	{
			$subpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = '';
			$wrappedSubpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = array();
		} else {
			$tmp = tx_div2007_alpha5::getLL_fh002($this->langObj, 'voucher_invalid');
			$tmpArray = explode('|',$tmp);
			$subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = $tmpArray[0] . htmlspecialchars($modelObj->getCode()) . $tmpArray[1];
			$wrappedSubpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = array();
		}
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for the voucher
	 *
	 * @param	[type]		$&$markerArray: ...
	 * @return	void
	 * @access private
	 */
	function getMarkerArray (
		&$markerArray
	)	{
		$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$modelObj = &$this->getModelObj();
		$markerArray['###INSERT_VOUCHERCODE###'] = 'recs[tt_products][vouchercode]';

		$voucherCode = $modelObj->getCode();
		if (!$voucherCode)	{
			$voucherCode = $modelObj->getLastCodeUsed();
		}

		$markerArray['###VALUE_VOUCHERCODE###'] = htmlspecialchars($voucherCode);
		$markerArray['###VOUCHER_DISCOUNT###'] = $priceViewObj->priceFormat($modelObj->getAmount());
	} // getItemMarkerArray

}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_voucher_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_voucher_view.php']);
}


?>
