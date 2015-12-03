<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2009 Franz Holzinger <franz@ttproducts.de>
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
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


abstract class tx_ttproducts_category_base_view extends tx_ttproducts_table_base_view {
	var $dataArray;  // array of read in categories
	var $titleArray; // associative array of read in categories with title as index
	public $marker = 'CATEGORY';
	var $markerObj;
	var $mm_table = ''; // only set if a mm table is used
	var $parentField; // reference field name for parent


	public function setMarkerArrayCatTitle (&$markerArray, $catTitle, $prefix)	{
		global $TSFE;

		$this->cObj->setCurrentVal($catTitle);
		$title = $this->cObj->cObjGetSingle($this->conf['categoryHeader'], $this->conf['categoryHeader.'], 'categoryHeader');
		$markerArray['###' . $prefix . $this->marker . '_TITLE###'] = htmlentities($title, ENT_QUOTES, $TSFE->renderCharset);
	}


	public function getMarkerArrayCatTitle(&$markerArray, $prefix = '')	{
		return ($markerArray['###' . $prefix . $this->marker . '_TITLE###']);
	}


	public function &getSubpartArrays (&$urlmarkerObj, $row, &$subpartArray, &$wrappedSubpartArray, &$tagArray, $pid, $linkMarker)	{
		$addQueryString = array();
		$addQueryString[$this->piVar] = $row['uid'];
		$wrappedSubpartArray['###' . $linkMarker . '###'] = array('<a href="' .  htmlspecialchars(tx_div2007_alpha5::getPageLink_fh003($this->cObj, $pid, '', $urlmarkerObj->getLinkParams('', $addQueryString, TRUE, FALSE, 'product', $this->piVar), array('useCacheHash' => TRUE))) . '">', '</a>');
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array		Returns a markerArray ready for substitution with information
	 * 			 			for the tt_producst record, $row
	 * @access private
	 */
	abstract function getMarkerArray (&$markerArray, $markerKey, $category, $pid, $imageNum = 0, $imageRenderObj = 'image', &$viewCatTagArray, $forminfoArray = array(), $pageAsCategory = 0, $theCode, $id, $prefix, $linkWrap = '');


	public function getParentMarkerArray ($parentArray, $row, &$markerArray, $category, $pid, $imageNum = 0, $imageRenderObj = 'image', &$viewCatTagArray, $forminfoArray = array(), $pageAsCategory = 0, $code, $id, $prefix)	{

		if (is_array($parentArray) && count($parentArray)) {
			$currentRow = $row;
			$count = 0;
			$currentCategory = $this->getModelObj()->getRowCategory($row);
			$parentCategory = '';

			foreach ($parentArray as $key => $parent)	{
				do	{
					$parentRow = $this->getModelObj()->getParent($currentCategory);
					$parentCategory = $parentRow['uid'];
					$parentPid = $this->getModelObj()->getRowPid($parentRow);
					$count++;
					if ($count < $parent) {
						$currentCategory = $parentCategory;
					}
				} while ($count < $parent && count($currentRow));
				$currentCategory = $parentCategory;

				if (count($currentRow))	{
					$this->getMarkerArray (
						$markerArray,
						'',
						$parentCategory,
						$parentPid,
						$this->config['limitImage'],
						'listcatImage',
						$viewCatTagArray,
						$tmp = array(),
						$pageAsCategory,
						'SINGLE',
						1,
						'PARENT' . $parent . '_',
						$prefix
					);
				}
			}
		}
	}


	public function getRowMarkerArray (&$row, $markerKey, &$markerArray, &$variantFieldArray, &$variantMarkerArray, &$tagArray, $theCode, $bHtml=TRUE, $charset='', $imageNum=0, $imageRenderObj='image', $id='',$prefix='', $suffix='', $linkWrap='')	{

		$functablename = $this->getModelObj()->getFuncTablename();
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$cssConf = $cnf->getCSSConf($functablename, $theCode);
		parent::getRowMarkerArray($row, $markerKey, $markerArray, $variantFieldArray, $variantMarkerArray, $tagArray, $theCode, $bHtml, $charset, $imageNum, $imageRenderObj, $id,$prefix, $suffix, $linkWrap);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_category_base_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_category_base_view.php']);
}


?>