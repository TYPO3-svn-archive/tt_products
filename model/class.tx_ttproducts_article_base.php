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
 * Part of the tt_products (Shop System) extension.
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
	var $cnf;
	var $conf;
	var $config;
	var $tt_content; // element of class tx_table_db to get the content elements
	var $paymentshipping;		// payment and shipping object to make the price dependant on it

	var $tableconf;
	var $tabledesc;
	var $fields = array();
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
	function init(&$pibase, &$cnf, $tablename, &$tt_content, &$paymentshipping)  {
		global $TYPO3_DB,$TSFE,$TCA;
		
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->tt_content = &$tt_content;
		$this->tableconf = $this->cnf->getTableConf($tablename);
		$this->tabledesc = $this->cnf->getTableDesc($tablename);
 		$this->paymentshipping = &$paymentshipping;
 
		$this->variantArray = array();
		$this->variantArray[1] = array('color', ($this->bIsProduct && $this->conf['selectColor']));
		$this->variantArray[2] = array('size', ($this->bIsProduct && $this->conf['selectSize']));
		$this->variantArray[3] = array('description', ($this->bIsProduct && $this->conf['selectDescription']));
		$this->variantArray[4] = array('gradings', ($this->bIsProduct && $this->conf['selectGradings']));
		
			// image
		$this->image = t3lib_div::makeInstance('tx_ttproducts_image');
		$this->image->init($this->pibase, $cnf, $tt_content, $this->table, $this->marker);
		$this->fields['address'] = ($this->tabledesc['address'] ? $this->tabledesc['address'] : 'address');
		$this->fields['itemnumber'] = ($this->tabledesc['itemnumber'] ? $this->tabledesc['itemnumber'] : 'itemnumber');
	} // init


	/**
	 * Reduces the instock value of the orderRecord with the amount and returns the result
	 * 
	 */
	function reduceInStock($uid, $count)	{
		global $TYPO3_DB;

		$instockField = $this->cnf->getTableDesc['inStock'];
		$instockField = ($instockField ? $instockField : 'inStock');
		$uid = intval($uid);
		$fieldsArray = array();
		$fieldsArray[$instockField] = $instockField.'-'.$count;
		$res = $TYPO3_DB->exec_UPDATEquery($this->table->name,'uid=\''.$uid.'\'', $fieldsArray,$instockField);
	}


	/**
	 * Reduces the instock value of the orderRecords with the sold items and returns the result
	 * 
	 */
	function reduceInStockItems(&$itemArray, $useArticles)	{

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
		global $TYPO3_CONF_VARS;

		if (!$this->marker)
			return array();
		$row = &$item['rec'];
			// Get image	
		$this->image->getItemMarkerArray ($row, $markerArray, $row['pid'], $imageNum, $imageRenderObj, $tagArray, $code);

		if (isset($row['delivery']))	{
			$this->image->getSingleImageMarkerArray ($this->marker.'_DELIVERY', $markerArray, $this->conf['delivery.'][$row['delivery'].'.']['image.']);
		} else {
			$markerArray['###'.$this->marker.'_DELIVERY###'] = '';
		}

		$markerArray['###'.$this->marker.'_ID###'] = $row['uid'];
		$markerArray['###'.$this->marker.'_UID###'] = $row['uid'];
		$markerArray['###'.$this->marker.'_TITLE###'] = $row['title'];
		$markerArray['###'.$this->marker.'_SUBTITLE###'] = $row['subtitle'];
		if ($code == 'EMAIL' && !$this->conf['orderEmail_htmlmail'])	{ // no formatting for emails
			$markerArray['###'.$this->marker.'_NOTE###'] = $row['note'];
			$markerArray['###'.$this->marker.'_NOTE2###'] = $row['note2'];			
		} else {
			$markerArray['###'.$this->marker.'_NOTE###'] = ($this->conf['nl2brNote'] ? nl2br($row['note']) : $row['note']);
			$markerArray['###'.$this->marker.'_NOTE2###'] = ($this->conf['nl2brNote'] ? nl2br($row['note2']) : $row['note2']);
	
				// Extension CSS styled content
			if (t3lib_extMgm::isLoaded('css_styled_content')) {
				$markerArray['###'.$this->marker.'_NOTE###'] = $this->pibase->pi_RTEcssText($markerArray['###'.$this->marker.'_NOTE###']);
				$markerArray['###'.$this->marker.'_NOTE2###'] = $this->pibase->pi_RTEcssText($markerArray['###'.$this->marker.'_NOTE2###']);
			} else if (is_array($this->conf['parseFunc.']))	{
				$markerArray['###'.$this->marker.'_NOTE###'] = $this->pibase->cObj->parseFunc($markerArray['###'.$this->marker.'_NOTE###'],$this->conf['parseFunc.']);
				$markerArray['###'.$this->marker.'_NOTE2###'] = $this->pibase->cObj->parseFunc($markerArray['###'.$this->marker.'_NOTE2###'],$this->conf['parseFunc.']);
			}
		}
		$markerArray['###'.$this->marker.'_ITEMNUMBER###'] = $row[$this->fields['itemnumber']];
		$markerArray['###'.$this->marker.'_TAX###'] = (!empty($row['tax'])) ? $row['tax'] : $this->conf['TAXpercentage'];

		$markerArray['###CUR_SYM###'] = ' '.($this->conf['currencySymbol'] ? $this->conf['currencySymbol'] : '');

		$priceNo = intval($this->config['priceNoReseller']);

		$taxFromShipping = $this->paymentshipping->getReplaceTAXpercentage();
		$taxInclExcl = (isset($taxFromShipping) && is_double($taxFromShipping) && $taxFromShipping == 0 ? 'tax_zero' : 'tax_included');
		$markerArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? $this->pibase->pi_getLL($taxInclExcl) : '');
		$markerArray['###PRICE_TAX###'] = $this->pibase->price->printPrice($this->pibase->price->priceFormat($item['priceTax'], $taxInclExcl));
		$price2 = $this->pibase->price->printPrice($this->pibase->price->priceFormat($this->pibase->price->getPrice($row['price2'],1,$row['tax'],$this->conf['TAXincluded']), $taxInclExcl));
		$markerArray['###PRICE2_TAX###'] = $price2;
		$oldPrice = $this->pibase->price->printPrice($this->pibase->price->priceFormat($this->pibase->price->getPrice($row['price'],1,$row['tax'],$this->conf['TAXincluded']), $taxInclExcl));
		$oldPriceNoTax = $this->pibase->price->printPrice($this->pibase->price->priceFormat($this->pibase->price->getPrice($row['price'],0,$row['tax'],$this->conf['TAXincluded']), $taxInclExcl));
		if ($priceNo == 0) {	// no old price will be shown when the new price has not been reducted
			$oldPrice = $oldPriceNoTax = '';
		}
		$markerArray['###OLD_PRICE_TAX###'] = $oldPrice;
		$markerArray['###PRICE_NO_TAX###'] = $this->pibase->price->printPrice($this->pibase->price->priceFormat($item['priceNoTax'], $taxInclExcl));
		$markerArray['###OLD_PRICE_NO_TAX###'] = $oldPriceNoTax;
		$markerArray['###PRICE_ONLY_TAX###'] = $this->pibase->price->printPrice($this->pibase->price->priceFormat($item['priceTax']-$item['priceNoTax']));

		$price2NoTax = $this->pibase->price->printPrice($this->pibase->price->priceFormat($this->pibase->price->getPrice($row['price2'],0,$row['tax'],$this->conf['TAXincluded'])));
		$markerArray['###PRICE2_NO_TAX###'] = $price2NoTax;


		$instockField = $this->cnf->getTableDesc['inStock'];
		$instockField = ($instockField ? $instockField : 'inStock');
		$markerArray['###'.$this->marker.'_INSTOCK_UNIT###'] = '';
		if (isset($row[$instockField]))	{
			if ($row[$instockField] <> 0) {
				$markerArray['###'.$this->marker.'_INSTOCK###'] = $row['inStock'];
				$markerArray['###'.$this->marker.'_INSTOCK_UNIT###'] = $this->conf['inStockPieces'];
			} else {
				$markerArray['###'.$this->marker.'_INSTOCK###'] = $this->conf['notInStockMessage'];
			}
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
		if (is_array ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey][$this->marker])) {
			foreach  ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey][$this->marker] as $classRef) {
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
	 * Returns true if the item has the $check value checked
	 * 
	 */
	function hasAdditional(&$row, $check)  {
		$hasAdditional = false;
		return $hasAdditional; 
	}


	function getNeededUrlParams($theCode)	{
		$rc = '';
		$this->tableconf = $this->cnf->getTableConf($this->conftablename, $theCode);
		if (is_array($this->tableconf) && $this->tableconf['urlparams'])	{
			$rc = $this->tableconf['urlparams'];
		}
		return $rc;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_article_base.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_article_base.php']);
}


?>
