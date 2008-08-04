<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
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


global $TYPO3_CONF_VARS;


class tx_ttproducts_variant {
	var $pibase;
	var $conf;	// reduced local conf
	var $itemTable;
	var $useArticles;
	var $bSelectableArray;
	var $fieldArray = array();	// array of fields which are variants with comma separated values
	var $additionalKey;

	/**
	 * setting the local variables
	 */
	function init(&$pibase, &$cnf, &$itemTable, $tablename, $useArticles)  {
		$this->pibase = &$pibase;
		$tmpArray = $cnf->getTableDesc($tablename);
		$this->conf = (is_array($tmpArray) && is_array($tmpArray['variant.']) ? $tmpArray['variant.'] : array());
		$this->itemTable = &$itemTable;
		$this->useArticles = $useArticles;
		$this->bSelectableArray = array();
		$fieldArray = $this->conf;
		$additionalKey = '';
		foreach ($fieldArray as $k => $field)	{
			if ($field == 'additional')	{
				$additionalKey = $k;
			} else if ($cnf->conf['select'.ucfirst($field)])	{
				$this->bSelectableArray[$k] = true;
			}
		}
		if (isset($additionalKey))	{
			unset($fieldArray[$additionalKey]);
		}
		$this->fieldArray = $fieldArray;
		$this->additionalKey = $additionalKey;
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
	 function modifyRowFromVariant (&$row, $variant) {
		$variantArray = explode(';', $variant);

		if (is_array($this->conf) && ($this->useArticles == 1 || count ($this->bSelectableArray)))	{
			$count = 0;
			foreach ($this->conf as $key => $field)	{
				if ($field != 'additional')	{	// no additional here
					if ($variantArray[$count])	{
						$row[$field] = $variantArray[$count];
					} else {
						$tmpArray = t3lib_div::trimExplode(';', $row[$field]);
						$row[$field] = $tmpArray[0];
					}
					$count++;
				}
			}
		}
	 }


	/**
	 * Returns the variant extVar string from the variant values in the row
	 *
	 * @param	array		the row
	 * @return  string	  variants separated by ';'
	 * @access private
	 * @see modifyRowFromVariant
	 */
	 function getVariantFromRow (&$row) {
		// take only the first color, size and gradings, if there are more entries from the item table		
		$variantArray = array();

		if (is_array($this->conf) && ($this->useArticles == 1 || count ($this->bSelectableArray)))	{
			foreach ($this->conf as $key => $field)	{
				if ($field != 'additional')	{	// no additional here
					$tmpArr = t3lib_div::trimExplode(';', $row[$field]);
					$variantArray[] = $tmpArr[0];
				}
			}
		}

		$rc = implode (';', $variantArray);
		return $rc; 
	 }


	function getTableUid ($table, $uid)	{
		$rc = '|'.$table.'|'.$uid;
		return $rc;
	}


	function getVariantSubpartArray (&$subpartArray, &$row, &$tempContent, $useSelects, &$conf)  {
		if ($useSelects) {
			$areaArray = array();
			if (is_array($this->conf))	{
				foreach ($this->conf as $key => $field)	{
					if ($field != 'additional')	{	// no additional here
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
		$markerArray = array();
		$this->removeEmptyMarkerSubpartArray($markerArray, $subpartArray, $row, $conf);
	}


	function removeEmptyMarkerSubpartArray (&$markerArray, &$subpartArray, &$row, &$conf) {
		$areaArray = array();
		$remMarkerArray = array();

		if (is_array($this->conf))	{
			foreach ($this->conf as $key => $field)	{
				if ($field != 'additional')	{	// no additional here
					if (trim($row[$field]) == '' || !$conf['select'.ucfirst($field)])	{
						$remSubpartArray[] = 'display_variant'.$key;
					} else {
						$remMarkerArray[] = 'display_variant'.$key;
					}
				}
			}
		}

		if ($this->itemTable->hasAdditional($row,'isSingle')) {
			$remSubpartArray[] = 'display_variant5_isNotSingle';
			$remMarkerArray[] = 'display_variant5_isSingle';
		} else {
			$remSubpartArray[] = 'display_variant5_isSingle';
			$remMarkerArray[] = 'display_variant5_isNotSingle';
		}

		foreach ($remSubpartArray as $k => $subpart) {
			$subpartArray['###'.$subpart.'###'] = '';
		}
		
		foreach ($remMarkerArray as $k => $marker)	{
			$markerArray['<!-- ###'.$marker.'### -->'] = '';
		}
	}


	function fetchArticle($productRow, $articleRows) {
		$fieldArray = array();
		foreach ($this->conf as $k => $field)	{
			if ($productRow[$field] && $field != 'additional')	{
				$fieldArray[$field] = t3lib_div::trimExplode(';', $productRow[$field]);
			}
		}
		$articleRow = array();
		if (count($fieldArray))	{
			foreach ($articleRows as $k => $row)	{
				$bFits = true;
				foreach ($fieldArray as $field => $valueArray)	{
					if ($row[$field] && !in_array($row[$field], $valueArray) && $field != 'additional')	{
						$bFits = false;
						break;
					}
				}
				if ($bFits)	{
					$articleRow = $row;
					break;
				}
			}
		}
		return $articleRow;
	}


	function &getFieldArray()	{	
		return $this->fieldArray;
	}

	function getAdditionalKey()	{	
		return $this->additionalKey;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_variant.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_variant.php']);
}


?>
