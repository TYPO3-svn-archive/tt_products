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
 * functions for the product
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

//
abstract class tx_ttproducts_article_base_view extends tx_ttproducts_table_base_view {
	private $dataArray = array(); // array of read in products
	private $table;	 // object of the type tx_table_db

	public $tabledesc;
	public $fields = array();
	public $type; 	// the type of table 'article' or 'product'
			// this gets in lower case also used for the URL parameter
	public $variant;       // object for the product variant attributes, must initialized in the init function
	protected $mm_table = ''; // only set if a mm table is used


	public function init ($langObj, $modelObj)	{
		parent::init($langObj, $modelObj);

		$this->variant->init($langObj, $modelObj->variant);
	}


	public function getItemMarkerSubpartArrays (
		&$templateCode,
		$functablename,
		&$row,
		$markerArray,
		&$subpartArray,
		&$wrappedSubpartArray,
		&$tagArray,
		$theCode='',
		$iCount=''
	)	{
		$this->getItemSubpartArrays($templateCode, $functablename, $row, $subpartArray, $wrappedSubpartArray, $tagArray, $theCode, $iCount);
	}


	public function getItemSubpartArrays ($templateCode, $functablename, &$row, &$subpartArray, &$wrappedSubpartArray, &$tagArray, $theCode='', $id='1')	{
		global $TCA;

		parent::getItemSubpartArrays($templateCode, $functablename, $row, $subpartArray, $wrappedSubpartArray, $tagArray, $theCode, $id);
	}


	public function getCurrentPriceMarkerArray (
		&$markerArray,
		$originalName,
		$originalRow,
		$mergedName,
		$mergedRow,
		$id,
		$theCode
	)	{

		if (is_array($mergedRow))	{
			$row = $mergedRow;
			if (is_array($originalRow) && count($originalRow))	{
				if ($mergedName != '') {
					$id .= 'from-' . str_replace('_', '-', $mergedName);
				}
				$row['uid'] = $originalRow['uid'];
			}
		} else {
			$row = $originalRow;
		}
		$this->getPriceMarkerArray($markerArray, $row, '', $id, $theCode);
	}


	public function getPriceMarkerArray (&$markerArray, $row, $markerKey, $id, $theCode)	{
		global $TCA;

		$modelObj = $this->getModelObj();
		$priceViewObj = t3lib_div::getUserObj('&tx_ttproducts_field_price_view');

		$functablename = $modelObj->getFuncTablename();
		$mainId = $this->getId($row, $id, $theCode);

		foreach ($TCA[$functablename]['columns'] as $field => $fieldTCA)	{
			if (strpos($field, 'price') === 0)	{
				$priceViewObj->getModelMarkerArray($field, $row, $markerArray, $markerKey, $mainId);
			}
		}
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
	 * 			 		for the tt_producst record, $row
	 * @access private
	 */
	public function getModelMarkerArray (
		&$row,
		$markerKey,
		&$markerArray,
		$catTitle,
		$imageNum=0,
		$imageRenderObj='image',
		&$tagArray,
		$forminfoArray=array(),
		$theCode='',
		$id='',
		$prefix='',
		$suffix='',
		$linkWrap='',
		$bHtml=TRUE,
		$charset=''
	)	{
		global $TSFE, $TCA;

		$modelObj = $this->getModelObj();
		$priceObj = t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$imageObj = t3lib_div::getUserObj('&tx_ttproducts_field_image_view');

		if ($markerKey)	{
			$marker = $markerKey;
		} else {
			$marker = $this->marker;
		}

		if (!$marker)	{
			return array();
		}
		$variantFieldArray = $modelObj->variant->getFieldArray();
		$variantMarkerArray = array();

		$this->getRowMarkerArray (
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

// 		$markerArray = array_merge($markerArray, $variantMarkerArray); // neu +++

		$this->getPriceMarkerArray($markerArray, $row, $markerKey, $id, $theCode);

		if (isset($row['delivery']))	{
			$imageObj->getSingleImageMarkerArray ($marker.'_DELIVERY', $markerArray, $this->conf['delivery.'][$row['delivery'].'.']['image.'], $theCode);
		} else {
			$markerArray['###'.$marker.'_DELIVERY###'] = '';
		}

	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_article_base_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_article_base_view.php']);
}

?>