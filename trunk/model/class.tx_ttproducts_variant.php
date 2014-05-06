<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * article functions without object instance
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


// require_once (PATH_BE_ttproducts.'model/int.tx_ttproducts_variant_int.php');


class tx_ttproducts_variant implements tx_ttproducts_variant_int {
	var $conf;	// reduced local conf
	var $itemTable;
	var $useArticles;
	var $bSelectableArray;
	var $fieldArray = array();	// array of fields which are variants with comma separated values
	var $firstVariantRow = '';
	var $additionalKey;

	/**
	 * setting the local variables
	 *
	 * @param	[type]		$$itemTable: ...
	 * @param	[type]		$tablename: ...
	 * @param	[type]		$useArticles: ...
	 * @return	[type]		...
	 */
	function init($itemTable, $tablename, $useArticles)  {
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

		$tmpArray = $cnf->getTableDesc($tablename);
		$this->conf = (is_array($tmpArray) && is_array($tmpArray['variant.']) ? $tmpArray['variant.'] : array());
		$this->itemTable = $itemTable;
		$this->useArticles = $useArticles;
		$this->bSelectableArray = array();
		$firstVariantArray = array();
		$fieldArray = $this->conf;
		$additionalKey = '';

		foreach ($fieldArray as $k => $field)	{
			if ($field == 'additional')	{
				$additionalKey = $k;
			} else if ($cnf->conf['select'.ucfirst($field)])	{
				$this->bSelectableArray[$k] = true;
				$this->selectableFieldArray[$k] = $field;
				$firstVariantArray[$k] = 0;
			} else {
				$firstVariantArray[$k] = '';
			}
		}

		$this->firstVariantRow = implode(';', $firstVariantArray);
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
	 * @param	string		variants separated by ';'
	 * @return	void
	 * @access private
	 * @see getVariantFromRow
	 */
	 function modifyRowFromVariant (&$row, $variant) {
		$variantArray = explode(';', $variant);

		if ($this->useArticles == 1 || count ($this->bSelectableArray))	{
			$fieldArray = $this->getFieldArray();
			$count = 0;
			foreach ($fieldArray as $key => $field)	{
				if ($this->bSelectableArray[$key])	{
					$variantRow = $row[$field];
					$variantValueArray = t3lib_div::trimExplode(';', $variantRow);
					$variantIndex = $variantArray[$count];
					if (isset($variantValueArray[$variantIndex]))	{
						$row[$field] = $variantValueArray[$variantIndex];
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
	 * @return	string		variants separated by ';'
	 * @access private
	 * @see modifyRowFromVariant
	 */
	function getVariantFromRow (&$row) {
		$extArray = $row['ext'];
		if (is_array($extArray['tt_products']))	{
			reset ($extArray['tt_products']);
			$variantRow = current($extArray['tt_products']);
			$variant = $variantRow['vars'];
		}
		return $variant;
	}


	/**
	 * Returns the variant extVar number from the incoming product row and the index in the variant array
	 *
	 * @param	array		the basket raw row
	 * @param	[type]		$index: ...
	 * @return	string		variants separated by ';'
	 * @access private
	 * @see modifyRowFromVariant
	 */
	function getVariantFromProductRow (&$row, $index) {
		$variantArray = array();
		$bVariantRow = array();
		if ($index == 0)	{
			$bVariantRow = $this->getFirstVariantRow($row);
		}

		if ($this->useArticles == 1 || count ($this->bSelectableArray))	{
			$fieldArray = $this->getFieldArray();
			$count = 0;

			foreach ($fieldArray as $key => $field)	{
				if ($this->bSelectableArray[$key])	{
					$variantValue = $bVariantRow[$field];
					$prodVariantArray = t3lib_div::trimExplode(';', $row[$field]);
					if ($variantValue!='')	{
						$varantIndex = array_search($variantValue, $prodVariantArray);
						$variantArray[] = $varantIndex;
					} else {
						$variantArray[] = '';
					}
					$count++;
				}
			}
		}
		$variant = implode (';', $variantArray);
		return $variant;
	}


	/**
	 * Returns the variant extVar number from the incoming raw row into the basket
	 *
	 * @param	array		the basket raw row
	 * @return	string		variants separated by ';'
	 * @access private
	 * @see modifyRowFromVariant
	 */
	function getVariantFromRawRow (&$row) {
		$variantArray = array();

		if ($this->useArticles == 1 || count ($this->bSelectableArray))	{
			$fieldArray = $this->getFieldArray();
			$count = 0;

			foreach ($fieldArray as $key => $field)	{
				if ($this->bSelectableArray[$key])	{
					$variantValue = $row[$field];
					if (isset($variantValue))	{
						$variantArray[] = $variantValue;
					} else {
						$variantArray[] = '';
					}
					$count++;
				}
			}
		}
		$variant = implode (';', $variantArray);
		return $variant;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function getFirstVariantRow($row='')	{
		$rc = '';
		if (is_array($row))	{
			$fieldArray = $this->getFieldArray();
			$firstRow = $row;
			foreach ($fieldArray as $field)	{
				$variants = $row[$field];
				$variantArray = t3lib_div::trimExplode (';', $variants);
				$firstRow[$field] = $variantArray[0];
			}
			$rc = $firstRow;
		} else {
			$rc = $this->firstVariantRow;
		}
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function getTableUid ($table, $uid)	{
		$rc = '|'.$table.'|'.$uid;
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getSelectableArray()	{
		return $this->bSelectableArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$productRow: ...
	 * @param	[type]		$articleRows: ...
	 * @return	[type]		...
	 */
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

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getFieldArray()	{
		return $this->fieldArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getSelectableFieldArray()	{
		return $this->selectableFieldArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getAdditionalKey()	{
		return $this->additionalKey;
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_variant.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_variant.php']);
}


?>