<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2008 Franz Holzinger <contact@fholzinger.com>
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
 * functions for the order addresses
 *
 * $Id: class.tx_ttproducts_feuser.php 3741 2006-09-19 08:31:50Z franzholz $
 *
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_orderaddress_view extends tx_ttproducts_table_base_view {
	var $dataArray; // array of read in frontend users
	var $table;		 // object of the type tx_table_db
	var $fields = array();
	var $tableconf;
	var $piVar = 'fe';
	var $marker = 'FEUSER';
	var $image;


	function getWrappedSubpartArray(&$subpartArray, &$wrappedSubpartArray, $funcTablename)	{
		global $TSFE;

		if ($TSFE->fe_user->user)	{
			$wrappedSubpartArray['###FE_GROUP_1_TEMPLATE###'] = array('','');
			$subpartArray['###FE_GROUP_0_TEMPLATE###'] = '';
		} else {
			$wrappedSubpartArray['###FE_GROUP_0_TEMPLATE###'] = array('','');
			$subpartArray['###FE_GROUP_1_TEMPLATE###'] = '';
		}

		if ($this->getModelObj()->getCondition() || !$this->getModelObj()->getConditionRecord())	{
			$wrappedSubpartArray['###FE_CONDITION1_TRUE_TEMPLATE###'] = array('','');
			$subpartArray['###FE_CONDITION1_FALSE_TEMPLATE###'] = '';
		} else {
			$wrappedSubpartArray['###FE_CONDITION1_FALSE_TEMPLATE###'] = array('','');
			$subpartArray['###FE_CONDITION1_TRUE_TEMPLATE###'] = '';
		}
		return;
	}


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
	function getRowMarkerArray ($row, &$markerArray, $bSelect, $type)	{
		global $TCA;

		if ($bSelect)	{
			include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_form_div.php');
			$typeSalutationText =
				tx_ttproducts_form_div::createSelect(
					$this->langObj,
					$TCA['sys_products_orders']['columns']['salutation']['config']['items'],
					'recs['.$type.'][salutation]',
					(is_array($row) ? $row['salutation'] : ''),
					TRUE,
					TRUE,
					array(),
					'select',
					array('id' => 'field_' . $type . '_salutation') /* Add ID for field to be able to use labels. */
				);
		} else if (is_numeric($row['salutation'])) {
			$salutation = $TCA['sys_products_orders']['columns']['salutation']['config']['items'][$row['salutation']];
			$tmp = tx_div2007_alpha::sL_fh001($salutation[0]);
			$typeSalutationText = htmlspecialchars(tx_div2007_alpha5::getLL_fh002($this->langObj, $tmp));
		} else {
			$typeSalutationText = '';
		}
		$markerArray['###'.($type=='personinfo' ? 'PERSON' : 'DELIVERY').'_SALUTATION###'] = $typeSalutationText;
	} // getMarkerArray
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_orderaddress_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_orderaddress_view.php']);
}


?>
