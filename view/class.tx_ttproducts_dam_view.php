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


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * 		 			for the tt_producst record, $row
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @param	[type]		$tagArray: ...
	 * @param	[type]		$forminfoArray: ...
	 * @param	[type]		$theCode: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$prefix: ...
	 * @param	[type]		$linkWrap: ...
	 * @param	[type]		$bHtml: ...
	 * @param	[type]		$charset: ...
	 * @return	array		Returns a markerArray ready for substitution with information
	 * @access private
	 */
	function getItemMarkerArray (
			&$item,
			&$markerArray,
			$catTitle,
			$imageNum=0,
			$imageRenderObj='image',
			&$tagArray,
			$forminfoArray=array(),
			$theCode='',
			$id='1',
			$prefix='',
			$linkWrap='',
			$bHtml=true,
			$charset=''
		)	{

		global $TSFE;

		$row = &$item['rec'];
		$imageObj = t3lib_div::getUserObj('&tx_ttproducts_field_image_view');

			// Get image
		$imageObj->getItemMarkerArrayEnhanced ($this->getModelObj()->getFuncTablename(), $row, $this->marker, $markerArray, $row['pid'], $imageNum, $imageRenderObj, $tagArray, $code, $id, $prefix, $linkWrap);
		foreach ($row as $field => $value)	{
			if (!is_array($row[$field]))	{
				$markerArray['###'.$this->marker.'_'.strtoupper($field).'###'] = htmlentities($row[$field],ENT_QUOTES,$TSFE->renderCharset);
			}
		}
	}


	/**
	 * Sets the markers for DAM specific FORM fields
	 *
	 * @param	[type]		$uid: ...
	 * @param	[type]		$markerArray: ...
	 * @return	[type]		...
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