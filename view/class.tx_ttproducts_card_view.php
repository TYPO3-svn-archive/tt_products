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
 * credit card functions
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


class tx_ttproducts_card_view extends tx_ttproducts_table_base_view {
	public $marker='CARD';

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
		global $TCA, $TSFE;

		include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_form_div.php');

		$modelObj = &$this->getModelObj();
		$ccNumberArray = array();
		$ccTypeTextSelected = '';
		$tablename = $modelObj->getTablename();

		if (count($modelObj->allowedArray))	{
			$ccTypeText =
				tx_ttproducts_form_div::createSelect (
					$this->langObj,
					$TCA[$tablename]['columns']['cc_type']['config']['items'],
					'recs[creditcard][cc_type]',
					$modelObj->ccArray['cc_type'],
					true,
					true,
					$modelObj->allowedArray
				);
			for ($i = 1; $i <= 4; ++$i)	{
				$ccNumberArray[$i - 1] = $modelObj->ccArray['cc_number_'.$i];
			}
			$ccOwnerName = $modelObj->ccArray['owner_name'];
		} else {
			$ccTypeText = '';
			$ccNumber = '';
			$ccOwnerName = '';
		}

		$markerArray['###PERSON_CARDS_OWNER_NAME###'] = htmlentities($ccOwnerName,ENT_QUOTES,$TSFE->renderCharset);
		$markerArray['###PERSON_CARDS_CC_TYPE###'] = $ccTypeText;
		$markerArray['###PERSON_CARDS_CC_TYPE_SELECTED###'] = $modelObj->ccArray['cc_type'];
		if (isset($modelObj->ccArray['cc_type']))	{ //
			$tmp = $TCA[$tablename]['columns']['cc_type']['config']['items'][$modelObj->ccArray['cc_type']]['0'];
			$tmp = tx_div2007_alpha::sL_fh001($tmp);
			$ccTypeTextSelected = tx_div2007_alpha::getLL($this->langObj, $tmp);
		}
		$markerArray['###PERSON_CARDS_CC_TYPE_SELECTED###'] = $ccTypeTextSelected;
		for ($i = 1; $i <= 4; ++$i)	{
			$markerArray['###PERSON_CARDS_CC_NUMBER_'.$i.'###'] = $ccNumberArray[$i - 1];
		}
		$markerArray['###PERSON_CARDS_CVV2###'] = $modelObj->ccArray['cvv2'];
		$markerArray['###PERSON_CARDS_ENDTIME_MM###'] = $modelObj->ccArray['endtime_mm'];
		$markerArray['###PERSON_CARDS_ENDTIME_YY###'] = $modelObj->ccArray['endtime_yy'];
		$markerArray['###PERSON_CARDS_ENDTIME_YY_SELECT###'] = '';
		$markerArray['###PERSON_CARDS_ENDTIME_MM_SELECT###'] = '';

		if (is_array($this->conf['payment.']['creditcardSelect.']))	{
			$mmArray = $this->conf['payment.']['creditcardSelect.']['mm.'];
			if (is_array($mmArray))	{
				$valueArray = tx_ttproducts_form_div::fetchValueArray($mmArray['valueArray.']);
				$markerArray['###PERSON_CARDS_ENDTIME_MM_SELECT###'] =
					tx_ttproducts_form_div::createSelect (
						$this->langObj,
						$valueArray,
						'recs[creditcard][endtime_mm]',
						$modelObj->ccArray['endtime_mm'],
						true,
						true
					);
			}
			$yyArray = $this->conf['payment.']['creditcardSelect.']['yy.'];
			if (is_array($yyArray))	{
				$valueArray = tx_ttproducts_form_div::fetchValueArray($yyArray['valueArray.']);
				$markerArray['###PERSON_CARDS_ENDTIME_YY_SELECT###'] =
					tx_ttproducts_form_div::createSelect (
						$this->langObj,
						$valueArray,
						'recs[creditcard][endtime_yy]',
						$modelObj->ccArray['endtime_yy'],
						true,
						true
					);
			}
		}
	} // getMarkerArray
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_card_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_card_view.php']);
}


?>
