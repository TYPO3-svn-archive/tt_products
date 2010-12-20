<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2008 Franz Holzinger <kontakt@fholzinger.com>
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
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_table.'lib/class.tx_table_db.php');
require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_variant_view.php');
require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_variant_dummy_view.php');


abstract class tx_ttproducts_article_base_view extends tx_ttproducts_table_base_view {
	var $dataArray = array(); // array of read in products
	var $table;	 // object of the type tx_table_db

	var $tabledesc;
	var $fields = array();
	var $type; 	// the type of table 'article' or 'product'
			// this gets in lower case also used for the URL parameter
	var $variant;       // object for the product variant attributes, must initialized in the init function
	var $mm_table = ''; // only set if a mm table is used


	function init(&$langObj, &$modelObj)	{
		parent::init($langObj, $modelObj);

		if ($modelObj->type == 'product')	{
			include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_variant_view.php');

			$this->variant = &t3lib_div::getUserObj('&tx_ttproducts_variant_view');
		} else {
			include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_variant_dummy_view.php');

			$this->variant = &t3lib_div::getUserObj('&tx_ttproducts_variant_dummy_view');
		}
		$this->variant->init($langObj, $modelObj->variant);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$&$templateCode: ...
	 * @param	[type]		$row: ...
	 * @param	[type]		$markerArray: ...
	 * @param	[type]		$subpartArray: ...
	 * @param	[type]		$wrappedSubpartArray: ...
	 * @param	[type]		$tagArray: ...
	 * @param	[type]		$code: ...
	 * @return	[type]		...
	 */
	function &getItemMarkerSubpartArrays (
		&$templateCode,
		&$row,
		$markerArray,
		&$subpartArray,
		&$wrappedSubpartArray,
		&$tagArray,
		$code=''
	)	{
		$this->getItemSubpartArrays ($templateCode, $row, $subpartArray, $wrappedSubpartArray, $tagArray, $code);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$templateCode: ...
	 * @param	[type]		$row: ...
	 * @param	[type]		$subpartArray: ...
	 * @param	[type]		$wrappedSubpartArray: ...
	 * @param	[type]		$tagArray: ...
	 * @param	[type]		$theCode: ...
	 * @param	[type]		$id: ...
	 * @return	[type]		...
	 */
	function &getItemSubpartArrays ($templateCode, &$row, &$subpartArray, &$wrappedSubpartArray, &$tagArray, $theCode='', $id='1')	{
		global $TCA;

		parent::getItemSubpartArrays ($templateCode, $row, $subpartArray, $wrappedSubpartArray, $tagArray, $theCode, $id);

		foreach ($row as $field => $value)	{

			$upperField = strtoupper($field);
			$markerKey = $this->marker.'_HAS_'.$upperField;
			if ($TCA[$this->getModelObj()->getTableObj()->name]['columns'][$field]['config']['type'] == 'group')	{
				$valueArray = t3lib_div::trimExplode(',', $value);

				foreach ($valueArray as $k => $partValue)	{
					$partMarkerKey = $markerKey.($k+1);
					if (isset($tagArray[$partMarkerKey]))	{
						if ($partValue)	{
							$wrappedSubpartArray['###'.$partMarkerKey.'###'] = array('','');
						} else {
							$subpartArray['###'.$partMarkerKey.'###'] = '';
						}
					}
				}
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
	public function getItemMarkerArray (
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
		global $TSFE, $TCA;

		$modelObj = &$this->getModelObj();
		$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$tableconf = $modelObj->getTableConf($theCode);
		$imageObj = &t3lib_div::getUserObj('&tx_ttproducts_field_image_view');

		if (!$this->marker)	{
			return array();
		}
		$row = &$item['rec'];
		if ($this->type == 'article')	{
			$priceMarkerPrefix = $this->marker.'_';
		}

		$variantFieldArray = $modelObj->variant->getFieldArray();
		$variantMarkerArray = array();
		$htmlentitiesArray = array();
		if (is_array($tableconf['functions.']) && isset($tableconf['functions.']['htmlentities']))	{
			$htmlentitiesArray = t3lib_div::trimExplode(',', $tableconf['functions.']['htmlentities']);
		}
		$fieldId = TT_PRODUCTS_EXTkey.'_'.strtolower($theCode).'_id_'.$id;
		$functablename = $modelObj->getFunctablename();

		parent::getItemMarkerArray ($row, $markerArray, $variantFieldArray, $variantMarkerArray, $tagArray, $theCode, $bHtml, $charset, $prefix, $imageRenderObj);

			// Get image
		// +++ $imageObj->getItemMarkerArrayEnhanced ($functablename, $row, $this->marker, $markerArray, $row['pid'], $imageNum, $imageRenderObj, $tagArray, $theCode, $id, '', $linkWrap, $charset);

		if (isset($row['delivery']))	{
			$imageObj->getSingleImageMarkerArray ($this->marker.'_DELIVERY', $markerArray, $this->conf['delivery.'][$row['delivery'].'.']['image.']);
		} else {
			$markerArray['###'.$this->marker.'_DELIVERY###'] = '';
		}

		$markerArray['###'.$this->marker.'_TAX###'] = (!empty($row['tax'])) ? $row['tax'] : $this->conf['TAXpercentage'];
		$markerArray['###CUR_SYM###'] = ' '.($this->conf['currencySymbol'] ? ($charset ? htmlentities($this->conf['currencySymbol'],ENT_QUOTES,$charset) : $this->conf['currencySymbol']) : '');
		$priceNo = intval($this->config['priceNoReseller']);
		$paymentshippingObj = &t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
		$taxFromShipping = $paymentshippingObj->getReplaceTaxPercentage();
		$taxInclExcl = (isset($taxFromShipping) && is_double($taxFromShipping) && $taxFromShipping == 0 ? 'tax_zero' : 'tax_included');

		$markerArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? tx_div2007_alpha::getLL($this->langObj, $taxInclExcl) : '');
	//	$markerArray['###'.$priceMarkerPrefix.'PRICE_TAX###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($item['priceTax'], $taxInclExcl));

		$oldPrice = $priceViewObj->printPrice($priceViewObj->priceFormat($priceObj->getPrice($row['price'],1,$row['tax'],$this->conf['TAXincluded']), $taxInclExcl));
		$oldPriceNoTax = $priceViewObj->printPrice($priceViewObj->priceFormat($priceObj->getPrice($row['price'],0,$row['tax'],$this->conf['TAXincluded']), $taxInclExcl));

		if ($priceNo == 0) {	// no old price will be shown when the new price has not been reducted
			$oldPrice = $oldPriceNoTax = '';
		}
		$markerArray['###'.$priceMarkerPrefix.'OLD_PRICE_TAX###'] = $oldPrice;
	//	$markerArray['###'.$priceMarkerPrefix.'PRICE_NO_TAX###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($item['priceNoTax'], $taxInclExcl));
		$markerArray['###'.$priceMarkerPrefix.'OLD_PRICE_NO_TAX###'] = $oldPriceNoTax;

		$markerArray['###'.$priceMarkerPrefix.'UNIT_PRICE_NO_TAX###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($item['priceUnitNoTax'], $taxInclExcl));
		$markerArray['###'.$priceMarkerPrefix.'UNIT_PRICE_TAX###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($item['priceUnitTax'], $taxInclExcl));

		$markerArray['###'.$priceMarkerPrefix.'WEIGHT_UNIT_PRICE_NO_TAX###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($item['priceWeightUnitNoTax'], $taxInclExcl));
		$markerArray['###'.$priceMarkerPrefix.'WEIGHT_UNIT_PRICE_TAX###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($item['priceWeightUnitTax'], $taxInclExcl));

		$markerArray['###'.$priceMarkerPrefix.'DIRECTCOST_TAX###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($priceObj->getPrice($row['directcost'],1,$row['tax'],$this->conf['TAXincluded'],$taxInclExcl)));
		$markerArray['###'.$priceMarkerPrefix.'DIRECTCOST_NO_TAX###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($priceObj->getPrice($row['directcost'],0,$row['tax'],$this->conf['TAXincluded'],$taxInclExcl)));

//		$markerArray['###'.$this->marker.'_WEIGHT###'] = doubleval($row['weight']);
		$cObjectMarkerArray = array();

			// Call all getItemMarkerArray hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$this->marker])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$this->marker] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getItemMarkerArray')) {
					$hookObj->getItemMarkerArray ($this, $markerArray, $cObjectMarkerArray, $item, $catTitle, $imageNum, $imageRenderObj, $forminfoArray, $theCode, $id, $linkWrap);
				}
			}
		}

		if (is_array($tableconf['field.']))	{
			$fieldMarkerArray = array_merge($markerArray, $variantMarkerArray);
			foreach ($row as $field => $value)	{
				$markerKey = '###'.$this->marker.'_'.strtoupper($field).'###';
				if (is_array($tableconf['field.'][$field.'.']) && !isset($cObjectMarkerArray[$markerKey]))	{
					$fieldContent = $this->cObj->cObjGetSingle($tableconf['field.'][$field],$tableconf['field.'][$field.'.'],$this->pibase->extKey);
					$cObjectMarkerArray[$markerKey] = $this->cObj->substituteMarkerArray($fieldContent,$fieldMarkerArray);
				}
			}
		}

		$markerArray = array_merge ($markerArray, $cObjectMarkerArray);
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_article_base_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_article_base_view.php']);
}


?>
