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
 * functions for the DAM images
 *
 * $Id$
 *
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_article_base_view.php');


class tx_ttproducts_dam_view extends tx_ttproducts_article_base_view {
	public $marker = 'DAM';
	public $piVar = 'dam';

	function init(&$langObj, &$modelObj)	{
		include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_variant_dummy_view.php');

		$this->variant = &t3lib_div::getUserObj('&tx_ttproducts_variant_dummy_view');
		parent::init($langObj, $modelObj);
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
	 * @return	array		Returns a markerArray ready for substitution with information
	 * 		 			for the tt_producst record, $row
	 * @access private
	 */
	public function getRowMarkerArray (
		$row,
		$markerKey,
		&$markerArray,
		&$variantFieldArray,
		&$variantMarkerArray,
		&$tagArray,
		$theCode,
		$bHtml=TRUE,
		$charset='',
		$imageNum=0,
		$imageRenderObj='image',
		$id='',	// id part to be added
		$prefix='', // if FALSE, then no table marker will be added
		$suffix='',	// this could be a number to discern between repeated rows
		$linkWrap=''
	)	{
		global $TSFE,$TCA;

		parent::getRowMarkerArray (
			$row,
			$markerKey,
			$markerArray,
			$variantFieldArray,
			$variantMarkerArray,
			$tagArray,
			$theCode,
			$bHtml,
			$charset,
			$imageNum,
			$imageRenderObj,
			$id,
			$prefix,
			$suffix,
			$linkWrap
		);
		$imageObj = &t3lib_div::getUserObj('&tx_ttproducts_field_image_view');
		$markerKey = $this->getMarkerKey($markerKey);

			// Get image
		$imageObj->getRowMarkerArrayEnhanced (
			$this->getModelObj()->getFuncTablename(),
			$row,
			$markerKey,
			$markerArray,
			$row['pid'],
			$imageNum,
			$imageRenderObj,
			$tagArray,
			$theCode,
			$id,
			$prefix,
			$suffix,
			$linkWrap
		);

/*		foreach ($row as $field => $value)	{
			if (!is_array($row[$field]))	{
				$markerArray['###'.$this->marker.'_'.strtoupper($field).'###'] = htmlentities($row[$field],ENT_QUOTES,$TSFE->renderCharset);
			}
		}*/
	}


	/**
	 * Sets the markers for DAM specific FORM fields
	 *
	 */
	function setFormMarkerArray($uid, &$markerArray)  {
		$markerArray['###DAM_FIELD_NAME###'] = 'ttp_basket[dam]';
		$markerArray['###DAM_UID###'] = intval($uid);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_dam_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_dam_view.php']);
}


?>
