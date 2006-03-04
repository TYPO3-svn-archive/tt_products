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
	var $conf;
	var $bUseArticles;
	

	/**
	 * setting the local variables
	 */
	function init(&$conf, $bUseArticles)  {
		$this->conf = &$conf;
		$this->bUseArticles = $bUseArticles;
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
		
		if (is_array($this->conf) && $this->bUseArticles)	{
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
		// take only the first color, size and gradings, if there are more entries from the products table		
		$variantArray = array();

		if (is_array($this->conf) && $this->bUseArticles)	{
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


	function getVariantSubpartArray (&$pibase, &$tt_products, &$subpartArray, &$row, &$tempContent, $useSelects, &$conf)  {
		
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
					
			if ($tt_products->isSingle($row)) {
				$areaArray[] = 'display_variant5_isSingle';
			} else {
				$areaArray[] = 'display_variant5_isNotSingle';
			}
			
			foreach ($areaArray as $k => $area) {
				$subpartArray['###'.$area.'###'] = $pibase->cObj->getSubpart($tempContent,'###'.$area.'###');
			}
		}
		$this->removeEmptySubpartArray($pibase, $tt_products, $subpartArray, $row, $conf);
	}


	function removeEmptySubpartArray (&$pibase, &$tt_products, &$subpartArray, &$row, &$conf) {
				
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

		if ($tt_products->isSingle($row)) {
			$areaArray[] = 'display_variant5_isNotSingle';
		} else {
			$areaArray[] = 'display_variant5_isSingle';
		}

		foreach ($areaArray as $k => $area) {
			$subpartArray['###'.$area.'###'] = '';
		}
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_variant.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_variant.php']);
}


?>
