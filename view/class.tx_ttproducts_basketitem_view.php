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
 * view functions for a basket item object
 *
 * $Id$
 *
 * @author	Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_basketitem_view {
	var $tt_products_cat; // element of class tx_table_db
	var $conf;
	var $config;
	var $basketExt; 	// basket
	var $basketVar = 'ttp_basket';
	var $pibase; // reference to object of pibase
	var $pricetablescalc;

	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param	string		$fieldname is the field in the table you want to create a JavaScript for
	 * @param	[type]		$basketExt: ...
	 * @return	void
	 */
	function init($pibase, &$basketExt)	{
		$this->pibase = $pibase;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
 		$this->conf = &$cnf->conf;
 		$this->config = &$cnf->config;
		$this->basketExt = &$basketExt;
	} // init


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @param	[type]		$theCode: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$bSelect: ...
	 * @param	[type]		$charset: ...
	 * @return	array
	 * @access private
	 */
	function &getItemMarkerArray (
		$functablename,
		&$item,
		&$markerArray,
		&$tagArray,
		&$hiddenText,
		$theCode='',
		$id='1',
		$bSelect=true,
		$charset=''
	)	{
		global $TCA, $TSFE;

		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');
		$viewTable = $tablesObj->get($functablename);
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		$row = &$item['rec'];
		$extArray = $row['ext'];

		if (is_array($extArray) && is_array($extArray['tt_products']))	{
			$variant = $extArray['tt_products'][0]['vars'];
		} else if (is_array($extArray) && is_array($extArray['tx_dam']))	{
			$variant = $extArray['tx_dam'][0]['vars'];
		}

		$hiddenText = '';
		$basketQuantityName = $this->basketVar.'['.$row['uid'].'][quantity]';
		$quantity = htmlspecialchars($item['count']);
		$jsTableName = str_replace('_','-',$functablename);
		$markerArray['###FIELD_NAME###'] = $basketQuantityName;
		$markerArray['###FIELD_QTY###'] = $quantity ? $quantity : '';
		$markerArray['###FIELD_ID###'] = $jsTableName.'-'.strtolower($theCode).'-id-'.$id;

		$priceFieldObj = t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$fieldIdTax = $id.'tax';
		$markerArray['###PRICE_TAX###'] = $priceFieldObj->printPrice($priceFieldObj->priceFormat($item['priceTax'], $taxInclExcl));
		$markerArray['###PRICE_NO_TAX###'] = $priceFieldObj->printPrice($priceFieldObj->priceFormat($item['priceNoTax'], $taxInclExcl));
		$markerArray['###PRICE_ONLY_TAX###'] = $priceFieldObj->printPrice($priceFieldObj->priceFormat($item['priceTax']-$item['priceNoTax']));
		$markerArray['###PRICE_TAX_ID###'] = $fieldIdTax;

		$skonto = ($item['price0Tax'] - $item['priceTax']);
		$markerArray['###PRICE_TAX_DISCOUNT###'] = number_format($skonto,intval($this->conf['priceDec']),$this->conf['priceDecPoint'],$this->conf['priceThousandPoint']);
		$basketFile = $TSFE->tmpl->getFileName($this->conf['basketPic']);
		$markerArray['###IMAGE_BASKET_SRC###'] = $basketFile;
		$fileresource = $this->pibase->cObj->fileResource($this->conf['basketPic']);
		$markerArray['###IMAGE_BASKET###'] = $fileresource;

		// $skonto = ($basePriceTax - $priceTax);
		if ($item['price0Tax'])	{
			$tmpPercent = number_format(($skonto / $item['price0Tax']) * 100, $this->conf['percentDec']);
		} else {
			$tmpPercent = 'infinite';
		}
		$markerArray['###PRICE_TAX_DISCOUNT_PERCENT###'] = $tmpPercent;

		$skonto = ($item['price0Tax'] - $item['price2Tax']);
		if ($item['price0Tax'])	{
			$tmpPercent = number_format(($skonto / $item['price0Tax']) * 100, $this->conf['percentDec']);
		} else {
			$tmpPercent = 'infinite';
		}
		$markerArray['###PRICE_TAX_PRICE2_PERCENT###'] = $tmpPercent;
		$fieldArray = $viewTable->variant->getFieldArray();
		$keyAdditional = $viewTable->variant->getAdditionalKey();
		$bSelectableArray = $viewTable->variant->getSelectableArray();

		if (isset($fieldArray) && is_array($fieldArray))	{
			foreach ($fieldArray as $k => $field)	{
				$fieldConf = $TCA[$viewTable->getTableObj()->name]['columns'][$field];

				if (is_array($fieldConf))	{
					$fieldMarker = strtoupper($field);
					$text = '';
					$variantRow = $row[$field];
					$prodTmpRow = explode(';', $variantRow);

					if ($bSelect && $variantRow && $prodTmpRow[0])	{
// 						include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_form_div.php');

						$prodTranslatedRow = $prodTmpRow;
						$viewTable->getTableObj()->substituteMarkerArray($prodTranslatedRow);
						$text = tx_ttproducts_form_div::createSelect(
							$this->pibase,
							$prodTranslatedRow,
							'',
							0,
							FALSE,
							FALSE,
							array()
						);
					} else {
						$text = $prodTmpRow[0];
					}

					$markerArray['###FIELD_'.$fieldMarker.'_NAME###'] = $this->basketVar.'['.$row['uid'].']['.$field.']';
					$markerArray['###FIELD_'.$fieldMarker.'_VALUE###'] = $row[$field];
					$markerArray['###FIELD_'.$fieldMarker.'_ONCHANGE'] = ''; // TODO:  use $forminfoArray['###FORM_NAME###' in something like onChange="Go(this.form.Auswahl.options[this.form.Auswahl.options.selectedIndex].value)"

					// ###PRODUCT_SIZE_FUNCTION### +++
					$markerKey = '###'.$viewTable->marker.'_'.$fieldMarker.'###';
					$markerArray[$markerKey] = $text;
					$markerKey = '###'.$viewTable->marker.'_'.$fieldMarker.'_FUNCTION1###';
					$markerArray[$markerKey] = 'doFetchRow(\''.$functablename.'\',\''.strtolower($theCode).'\','.$row['uid'].');';

	/*				if (isset($row['ext']))	{
						$markerArray['###PRODUCT_'.$fieldMarker.'###'] = $row[$field];
					}*/
				}
			}
		}
		// $markerArray['###FIELD_ADDITIONAL_NAME###'] = 'ttp_basket['.$row['uid'].'][additional]';
		$prodAdditionalText['single'] = '';
		// $variant = $row['extVars'];

		if ($keyAdditional !== FALSE) {
			$isSingleProduct = $viewTable->hasAdditional($row,'isSingle');
			if ($isSingleProduct)	{
				$message = tx_div2007_alpha5::getLL_fh002($langObj, 'additional_single');
				$prodAdditionalText['single'] = $message.'<input type="checkbox" name="'.$basketQuantityName.'" '.($quantity ? 'checked="checked"':'').'onchange = "this.form[this.name+\'[1]\'].value=(this.checked ? 1 : 0);"'.' value="1">';
				$hiddenText .= '<input type="hidden" name="'.$basketQuantityName.'[1]" value="'.($quantity ? '1' : '0') .'">';
			}

			$isImageProduct = $viewTable->hasAdditional($row,'isImage');
			if ($functablename == 'tt_products' && is_array($extArray) && is_array($extArray['tx_dam']))	{
				reset ($extArray['tx_dam']);
				$damext = current($extArray['tx_dam']);
				$damUid = $damext['uid'];
			} else if ($isImageProduct && isset($this->pibase->piVars['dam']))	{
				$damUid = $this->pibase->piVars['dam'];
			}
			if ($damUid)	{
				$tableVariant = $viewTable->variant->getTableUid ('tx_dam', $damUid);
				$variant .= $tableVariant;
			}
			$markerArray['###DAM_UID###'] = $damUid;
			$giftService = !$viewTable->hasAdditional($row,'noGiftService');
		}

		if ($giftService)	{
			$basketAdditionalName = $this->basketVar.'['.$row['uid'].'][additional]['.md5($variant).']';
			$bGiftService = $this->basketExt[$row['uid']][$variant.'.']['additional']['giftservice'];
			$giftServicePostfix = '[giftservice]';
			$message = tx_div2007_alpha5::getLL_fh002($langObj, 'additional_gift_service');
			$value = ($bGiftService ? '1' : '0');
			$prodAdditionalText['giftService'] = $message.'<input type="checkbox" name="'.$basketAdditionalName.$giftServicePostfix.'" '.($value ? 'checked="checked"':'').'onchange = "this.form[this.name+\'[1]\'].value=(this.checked ? 1 : 0);"'.' value="'.$value.'">';
			$hiddenText .= '<input type="hidden" name="'.$basketAdditionalName.$giftServicePostfix.'[1]" value="'.$value.'">';
		} else {
			$prodAdditionalText['giftService'] = '';
		}

		$markerArray['###FIELD_NAME_BASKET###'] = $this->basketVar.'['.$row['uid'].']['.md5($variant).']';
		$markerArray['###PRODUCT_ADDITIONAL_SINGLE###'] = $prodAdditionalText['single'];
		$markerArray['###PRODUCT_ADDITIONAL_GIFT_SERVICE###'] = $prodAdditionalText['giftService'];
		$markerArray['###PRODUCT_ADDITIONAL_GIFT_SERVICE_DISPLAY###'] = ($value ? '1' : '');
		if ($tagArray['PRODUCT_HIDDEN_TEXT'])	{
			$markerArray['###PRODUCT_HIDDEN_TEXT###'] = $hiddenText;
			$hiddenText = '';
		}
	} // getItemMarkerArray
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_basketitem_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_basketitem_view.php']);
}


?>