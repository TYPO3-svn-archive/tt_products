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
 * article functions without object instance
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *  
 */



class tx_ttproducts_variant {
	var $pibase;
	var $conf;
	var $itemTable;
	var $useArticles;
	var $bSelectableArray;
	

	/**
	 * setting the local variables
	 */
	function init(&$pibase, &$cnf, &$conf, &$itemTable, $useArticles)  {
		$this->pibase = &$pibase;
		$this->conf = &$conf;
		$this->itemTable = &$itemTable;
		$this->useArticles = $useArticles;
		$this->bSelectableArray = array();
		if ($cnf->conf['selectColor'])
			$this->bSelectableArray[0] = true;
		if ($cnf->conf['selectSize'])
			$this->bSelectableArray[1] = true;
		if ($cnf->conf['selectDescription'])
			$this->bSelectableArray[2] = true;
		if ($cnf->conf['selectGradings'])
			$this->bSelectableArray[3] = true;

	} // init


	/**
	 * fills in the row fields from the variant extVar string
	 *
	 * @param	array		the row
	 * @param	string	  variants separated by ';'
	 * @return  void
	 * @access private
	 * @see getVariantFromRow
	 */
	 function getRowFromVariant (&$row, $variant) {
		$variantArray = explode(';', $variant);
		
		if (is_array($this->conf) && ($this->useArticles == 1 || count ($this->bSelectableArray)))	{
			foreach ($this->conf as $key => $field)	{
				if ($key != 5)	{
					$row[$field] = $variantArray[$key-1];
				}
			}
		}
	 }


	/**
	 * Returns the variant extVar string from the variant values in the row
	 *
	 * @param	array		the row
	 * @param	string	  variants separated by ';'
	 * @return  void
	 * @access private
	 * @see getRowFromVariant
	 */
	 function getVariantFromRow (&$row) {
		// take only the first color, size and gradings, if there are more entries from the item table		
		$variantArray = array();

		if (is_array($this->conf) && ($this->useArticles == 1 || count ($this->bSelectableArray)))	{
			foreach ($this->conf as $key => $field)	{
				if ($key != 5)	{
					$tmpArr = t3lib_div::trimExplode(';', $row[$field]);
					$variantArray[] = $tmpArr[0];
				}
			}
		}
		
		$rc = implode (';', $variantArray);
		return $rc; 
	 }


	function getVariantSubpartArray (&$subpartArray, &$row, &$tempContent, $useSelects, &$conf)  {
		
		if ($useSelects) {
			$areaArray = array();
			if (is_array($this->conf))	{
				foreach ($this->conf as $key => $field)	{
					if ($key != 5)	{
						if (trim($row[$field]) != '')  {
							$areaArray[] = 'display_variant'.$key;
						}
					}
				}
			}
					
			if ($this->itemTable->hasAdditional($row,'isSingle')) {
				$areaArray[] = 'display_variant5_isSingle';
			} else {
				$areaArray[] = 'display_variant5_isNotSingle';
			}
			
			foreach ($areaArray as $k => $area) {
				$subpartArray['###'.$area.'###'] = $this->pibase->cObj->getSubpart($tempContent,'###'.$area.'###');
			}
		}
		$this->removeEmptySubpartArray($subpartArray, $row, $conf);
	}


	function removeEmptySubpartArray (&$subpartArray, &$row, &$conf) {
		$areaArray = array();

		if (is_array($this->conf))	{
			foreach ($this->conf as $key => $field)	{
				if ($key != 5)	{
					if (trim($row[$field]) == '' || !$conf['select'.ucfirst($field)])	{
						$areaArray[] = 'display_variant'.$key;
					}
				}
			}
		}

		if ($this->itemTable->hasAdditional($row,'isSingle')) {
			$areaArray[] = 'display_variant5_isNotSingle';
		} else {
			$areaArray[] = 'display_variant5_isSingle';
		}

		foreach ($areaArray as $k => $area) {
			$subpartArray['###'.$area.'###'] = '';
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
	 * @return	array
	 * @access private
	 */
	function &getItemMarkerArray (&$item, &$markerArray, &$basketExt, &$tagArray, $code='', $id)	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row

		$row = &$item['rec'];
		$variants = $this->getVariantFromRow ($row);
		$basketQuantityName = 'ttp_basket['.$row['uid'].'][quantity]';
		$quantity = $basketExt[$row['uid']][$variants];
		$markerArray['###FIELD_NAME###']=$basketQuantityName;
		$quantity = $basketExt[$row['uid']][$variants];
		$markerArray['###FIELD_QTY###']= $quantity ? $quantity : '';
		$markerArray['###FIELD_ID###'] = TT_PRODUCTS_EXTkey.'_'.strtolower($code).'_id_'.$id;

		$markerArray['###FIELD_SIZE_NAME###'] = 'ttp_basket['.$row['uid'].'][size]';
		$markerArray['###FIELD_SIZE_VALUE###'] = $row['size'];
		$markerArray['###FIELD_SIZE_ONCHANGE'] = ''; // TODO:  use $forminfoArray['###FORM_NAME###' in something like onChange="Go(this.form.Auswahl.options[this.form.Auswahl.options.selectedIndex].value)"
		$markerArray['###FIELD_COLOR_NAME###'] = 'ttp_basket['.$row['uid'].'][color]';
		$markerArray['###FIELD_COLOR_VALUE###'] = $row['color'];
		$markerArray['###FIELD_DESCRIPTION_NAME###'] = 'ttp_basket['.$row['uid'].'][description]';
		$markerArray['###FIELD_DESCRIPTION_VALUE###'] = $row['description'];
		$markerArray['###FIELD_GRADINGS_NAME###'] = 'ttp_basket['.$row['uid'].'][gradings]';
		$markerArray['###FIELD_GRADINGS_VALUE###'] = $row['gradings'];
		$markerArray['###FIELD_ADDITIONAL_NAME###'] = 'ttp_basket['.$row['uid'].'][additional]';
		
		$prodAdditionalText['single'] = '';	
		if ($this->conf['selectAdditional']) {
			$isSingleProduct = $this->hasAdditional($row,'isSingle');
			if ($isSingleProduct)	{
				$message = $this->pibase->pi_getLL('additional_single');
				$prodAdditionalText['single'] = $message.'<input type="checkbox" name="'.$basketQuantityName.'" '.($quantity ? 'checked="checked"':'').'onchange = "this.form[this.name+\'[1]\'].value=(this.checked ? 1 : 0);"'.' value="1">';
				$prodAdditionalText['single'] .= '<input type="hidden" name="'.$basketQuantityName.'[1]" value="'.($quantity ? '1' : '0') .'">';
			}
 		}
		$markerArray['###PRODUCT_ADDITIONAL_SINGLE###'] = $prodAdditionalText['single'];
	} // getItemMarkerArray


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_variant.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_variant.php']);
}


?>
