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
 * functions for the category
 *
 * $Id$
 *
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_category_view extends tx_ttproducts_category_base_view {
	public $piVar = 'cat';

	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array		Returns a markerArray ready for substitution with information
	 * 	 			for the tt_producst record, $row
	 * @access private
	 */
	public function getMarkerArray (&$markerArray, $category, $pid, $imageNum=0, $imageRenderObj='image', &$viewCatTagArray, $forminfoArray=array(), $pageAsCategory=0, $theCode, $id, $prefix, $linkWrap='')	{

		$modelObj = $this->getModelObj();
		$row = ($category ? $modelObj->get($category) : array ('title' => '', 'pid' => $pid));
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$functablename = $modelObj->getFuncTablename();
		$imageObj = t3lib_div::getUserObj('&tx_ttproducts_field_image_view');

			// Get image
		$imageObj->getItemMarkerArray($functablename, 'image', $row, $this->marker, $markerArray, $viewCatTagArray, $theCode, $id, $tmp, TRUE, '', $prefix, $imageRenderObj);

		$pageCatTitle = '';
		if ($pageAsCategory == 1) {
			$pageObj = $tablesObj->get('pages');
			$pageTmp = $pageObj->get($pid);
			$pageCatTitle = $pageTmp['title'];
		}

		$catTitle = $pageCatTitle;
		if (($row['title']))	{
			$catTitle .= ($this->tableconf['separator'].$row['title']);
		}
		$this->setMarkerArrayCatTitle ($markerArray, $catTitle, $prefix);
		parent::getItemMarkerArray($row, $markerArray, $variantFieldArray, $variantMarkerArray, $viewCatTagArray, $theCode, TRUE, '', $prefix, $imageRenderObj);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_category_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_category_view.php']);
}

?>