<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2008 Franz Holzinger <contact@fholzinger.com>
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
 * account functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com> 
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

class tx_ttproducts_account_view extends tx_ttproducts_table_base_view {
	public $marker = 'ACCOUNT';

	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	function getMarkerArray (&$markerArray)	{
		global $TCA;

		include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_form_div.php');

		$modelObj = &$this->getModelObj();
		if ($modelObj->bIsAllowed)	{
			$acNumber = $modelObj->acArray['ac_number'];
			$acOwnerName = $modelObj->acArray['owner_name'];
			$acBic = $modelObj->acArray['bic'];
		} else {
			$acNumber = '';
			$acOwnerName = '';
			$acBic = '';
		}

		$markerArray['###PERSON_ACCOUNTS_OWNER_NAME###'] = $acOwnerName;
		$markerArray['###PERSON_ACCOUNTS_AC_NUMBER###'] = $acNumber;
		$markerArray['###PERSON_ACCOUNTS_BIC###'] = $acBic; 
	} // getMarkerArray
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_account_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_account_view.php']);
}


?>
