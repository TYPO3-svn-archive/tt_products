<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2006 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * Part of the tt_products (Shopping System) extension.
 *
 * functions for the product
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_table.'lib/class.tx_table_db.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_image.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_variant.php');


class tx_ttproducts_article_base {
	var $table;	 // object of the type tx_table_db
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $variantArray; // array of variants which are selectable
	var $bIsProduct=true;	// if this is the base for a product
	var $marker;	// marker prefix in the template file. must be overridden
	var $type; 		// the type of table 'article' or 'product'
					// this gets in lower case also used for the URL parameter
	var $image;
	var $variant; // object for the product variant attributes, must initialized in the init function

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, &$cnf, &$tt_content)  {
		global $TYPO3_DB,$TSFE,$TCA;
		
		$this->pibase = &$pibase;
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		$this->variantArray = array();
		$this->variantArray[1] = array('color', ($this->bIsProduct && $this->conf['selectColor']));
		$this->variantArray[2] = array('size', ($this->bIsProduct && $this->conf['selectSize']));
		$this->variantArray[3] = array('description', ($this->bIsProduct && $this->conf['selectDescription']));
		$this->variantArray[4] = array('gradings', ($this->bIsProduct && $this->conf['selectGradings']));
		
			// image
		$this->image = t3lib_div::makeInstance('tx_ttproducts_image');
		$this->image->init($this->pibase, $cnf, $tt_content, $this->table, $this->marker);

	} // init


	/**
	 * Reduces the instock value of the orderRecord with the sold items and returns the result
	 * 
	 */
	function reduceInStock(&$itemArray, $useArticles)	{

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
	 * 			 			for the tt_producst record, $row
	 * @access private
	 */
	function getItemMarkerArray (&$item, &$markerArray, $catTitle, &$basketExt, $imageNum=0, $imageRenderObj='image', $tagArray, $forminfoArray=array(), $code='', $id='1')	{
		if (!$this->marker)
			return array();
		$row = &$item['rec'];

			// Get image	
		$this->image->getItemMarkerArray ($row, $markerArray, $row['pid'], $imageNum, $imageRenderObj, $tagArray, $code);
		$markerArray['###'.$this->marker.'_ID###'] = $row['uid'];
		$markerArray['###'.$this->marker.'_TITLE###'] = $row['title'];
		$markerArray['###'.$this->marker.'_SUBTITLE###'] = $row['subtitle'];
		$markerArray['###'.$this->marker.'_NOTE###'] = ($this->conf['nl2brNote'] ? nl2br($row['note']) : $row['note']);

			// Extension CSS styled content
		if (t3lib_extMgm::isLoaded('css_styled_content')) {
			$markerArray['###'.$this->marker.'_NOTE###'] = $this->pibase->pi_RTEcssText($markerArray['###'.$this->marker.'_NOTE###']);
		} else if (is_array($this->conf['parseFunc.']))	{
			$markerArray['###'.$this->marker.'_NOTE###'] = $this->pibase->cObj->parseFunc($markerArray['###'.$this->marker.'_NOTE###'],$this->conf['parseFunc.']);
		}
		$markerArray['###'.$this->marker.'_ITEMNUMBER###'] = $row[$this->fields['itemnumber']];
		$markerArray['###CUR_SYM###'] = ' '.($this->conf['currencySymbol'] ? $this->conf['currencySymbol'] : '');
		$markerArray['###PRICE_TAX###'] = $this->pibase->price->printPrice($this->pibase->price->priceFormat($item['priceTax']));
		$markerArray['###PRICE_NO_TAX###'] = $this->pibase->price->printPrice($this->pibase->price->priceFormat($item['priceNoTax']));
		$markerArray['###PRICE_ONLY_TAX###'] = $this->pibase->price->printPrice($this->pibase->price->priceFormat($item['priceTax']-$item['priceNoTax']));
		$oldPrice = $this->pibase->price->printPrice($this->pibase->price->priceFormat($this->pibase->price->getPrice($row['price'],1,$row['tax'])));
		$oldPriceNoTax = $this->pibase->price->printPrice($this->pibase->price->priceFormat($this->pibase->price->getPrice($row['price'],0,$row['tax'])));
		$price2 = $this->pibase->price->printPrice($this->pibase->price->priceFormat($this->pibase->price->getPrice($row['price2'],1,$row['tax'])));
		$price2NoTax = $this->pibase->price->printPrice($this->pibase->price->priceFormat($this->pibase->price->getPrice($row['price2'],0,$row['tax'])));
		$priceNo = intval($this->config['priceNoReseller']);
		if ($priceNo == 0) {	// no old price will be shown when the new price has not been reducted
			$oldPrice = $oldPriceNoTax = '';
		}

		$markerArray['###OLD_PRICE_TAX###'] = $oldPrice;
		$markerArray['###OLD_PRICE_NO_TAX###'] = $oldPriceNoTax;
		$markerArray['###PRICE2_TAX###'] = $price2;
		$markerArray['###PRICE2_NO_TAX###'] = $price2NoTax;
		$markerArray['###'.$this->marker.'_INSTOCK_UNIT###'] = '';
		if ($row['inStock'] <> 0) {
			$markerArray['###'.$this->marker.'_INSTOCK###'] = $row['inStock'];
			$markerArray['###'.$this->marker.'_INSTOCK_UNIT###'] = $this->conf['inStockPieces'];
		} else {
			$markerArray['###'.$this->marker.'_INSTOCK###'] = $this->conf['notInStockMessage'];
		}
		
		foreach ($this->variantArray as $variant => $variantRec)	{
			$text = '';
			$variantRow = $row[$variantRec[0]];
			$prodTmp = explode(';', $variantRow);
			if ($variantRow && $variantRec[1])	{
				foreach ($prodTmp as $prodVal)	{
					$text .= '<OPTION value="'.$prodVal.'">'.$prodVal.'</OPTION>';
				}
			} else {
				$text = $prodTmp[0];
			}
			$markerArray['###'.$this->marker.'_'.strtoupper($variantRec[0]).'###'] = $text;
		}

		$markerArray['###'.$this->marker.'_WEIGHT###'] = doubleval($row['weight']);

			// Call all getItemMarkerArray hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$this->marker])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$this->marker] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getItemMarkerArray')) {
					$hookObj->getItemMarkerArray ($this, $markerArray, $item, $catTitle, $imageNum, $imageRenderObj, $forminfoArray, $code, $id);
				}
			}
		}
		
	}


	function addWhereCat($cat, $pid_list)	{
		$where = '';	

		return $where;
	}


	function addselectConfCat($cat, &$selectConf)	{
	}


	function getPageUidsCat($cat)	{
		$uids = '';

		return $uids;
	}
	

	function getProductField(&$row, $field)	{
		return '';
	}


	/**
	 * Returns true if the item is a single item
	 * 
	 */
	function isSingle(&$row)  {
		$isSingle = false;
		return $isSingle; 
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_article_base.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_article_base.php']);
}


?>
