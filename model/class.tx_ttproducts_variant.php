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
 * article functions without object instance
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


// require_once (PATH_BE_ttproducts.'model/interface.tx_ttproducts_variant_int.php');


class tx_ttproducts_variant implements tx_ttproducts_variant_int {
	public $conf;	// reduced local conf
	var $itemTable;
	private $useArticles;
	private $selectableArray;
	var $fieldArray = array();	// array of fields which are variants with comma separated values
	private $selectableFieldArray = array();
	var $firstVariantRow = '';
	var $additionalKey;
	public $additionalField = 'additional';


	/**
	 * setting the local variables
	 */
	public function init ($itemTable, $tablename, $useArticles)  {
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

		$tmpArray = $cnf->getTableDesc($tablename);
		$this->conf = (is_array($tmpArray) && is_array($tmpArray['variant.']) ? $tmpArray['variant.'] : array());
		$this->itemTable = $itemTable;
		$this->useArticles = $useArticles;
		$this->selectableArray = array();
		$firstVariantArray = array();
		$fieldArray = $this->conf;
		$additionalKey = '';

		foreach ($fieldArray as $k => $field)	{
			if ($field == $this->additionalField)	{
				$additionalKey = $k;
			} else if (intval($cnf->conf['select'.ucfirst($field)]))	{
				$this->selectableArray[$k] = intval($cnf->conf[$this->getSelectConfKey($field)]);
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


	public function getUseArticles ()	{
		return $this->useArticles;
	}


	public function getSelectConfKey ($field)	{
		$rc = 'select'.ucfirst($field);
		return $rc;
	}


	/**
	 * fills in the row fields from the variant extVar string
	 *
	 * @param	array		the row
	 * @param	string	  variants separated by ';'
	 * @return  void
	 * @access private
	 * @see getVariantFromRow
	 */
	public function modifyRowFromVariant (&$row, $variant='') {
		if (!$variant)	{
			$variant = $this->getVariantFromRow($row);
		}
		$variantArray = explode(';', $variant);
		$useArticles = $this->getUseArticles();
		if (in_array($useArticles, array(1,3)) || !$useArticles && count($this->selectableArray))	{
			$fieldArray = $this->getFieldArray();
			$count = 0;
			foreach ($fieldArray as $key => $field)	{
				if ($this->selectableArray[$key])	{
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
	 * @return  string	  variants separated by ';'
	 * @access private
	 * @see modifyRowFromVariant
	 */
	public function getVariantFromRow (&$row) {
		$extArray = $row['ext'];
		if (is_array($extArray['tt_products']))	{
			reset($extArray['tt_products']);
			$variantRow = current($extArray['tt_products']);
			$variant = $variantRow['vars'];
		}
		return $variant;
	}


	/**
	 * Returns the variant extVar number from the incoming product row and the index in the variant array
	 *
	 * @param	array	the basket raw row
	 * @return  string	  variants separated by ';'
	 * @access private
	 * @see modifyRowFromVariant
	 */
	public function getVariantFromProductRow (&$row, $index) {
		$variantArray = array();
		$bVariantRow = array();
		if ($index == 0)	{
			$variantRow = $this->getVariantRow($row,array());
		}
		$useArticles = $this->getUseArticles();

		if (isset($variantRow) && count($variantRow) && ($useArticles == 1 || count($this->selectableArray)))	{
			$fieldArray = $this->getFieldArray();
			$count = 0;

			foreach ($fieldArray as $key => $field)	{
				if ($this->selectableArray[$key])	{
					$variantValue = $variantRow[$field];
					$prodVariantArray = t3lib_div::trimExplode(';', $row[$field]);
					if ($variantValue != '')	{
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
	 * @param	array	the basket raw row
	 * @return  string	  variants separated by ';'
	 * @access private
	 * @see modifyRowFromVariant
	 */
	public function getVariantFromRawRow (&$row) {
		$variantArray = array();
		$useArticles = $this->getUseArticles();

		if ($useArticles == 1 || count($this->selectableArray))	{
			$fieldArray = $this->getFieldArray();
			$count = 0;

			foreach ($fieldArray as $key => $field)	{
				if ($this->selectableArray[$key])	{
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
/*
	public function getFirstVariantRow($row='')	{
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
	}*/


	public function getVariantRow ($row = '', $varianArray = array())	{
		$rc = '';

		if (isset($row) && is_array($row))	{
			if (!isset($varianArray))	{
				$varianArray = array();
			}
			$fieldArray = $this->getFieldArray();
			$rcRow = $row;
			foreach ($fieldArray as $field)	{
				$variants = $row[$field];
				$tmpArray = t3lib_div::trimExplode(';', $variants);
				$index = (isset($varianArray[$field]) ? $varianArray[$field] : 0);
				$rcRow[$field] = $tmpArray[$index];
			}
			$rc = $rcRow;
		} else {
			$rc = $this->firstVariantRow;
		}
		return $rc;
	}


	public function getTableUid ($table, $uid)	{
		$rc = '|'.$table.'|'.$uid;
		return $rc;
	}


	public function getSelectableArray ()	{
		return $this->selectableArray;
	}


	public function getVariantValuesByArticle ($articleRowArray,$row='')	{
		$rc = array();

		$selectableFieldArray = $this->getSelectableFieldArray();

		foreach ($selectableFieldArray as $field)	{

			if (!$row || isset($row[$field]))	{
				$valueArray = array();

				foreach ($articleRowArray as $articleRow)	{
					$articleValueArray = t3lib_div::trimExplode(';', $articleRow[$field]);

					if ($articleValueArray[0])	{
						$valueArray = array_merge($valueArray, $articleValueArray);
					}
				}
				$valueArray = array_values(array_unique($valueArray));

				if ($row)	{
					$rc[$field] = implode(';', $valueArray);
				} else {
					$rc[$field] = $valueArray;
				}
			}
		}
		return $rc;
	}


	// the article rows must be in the correct order already
	public function filterArticleRowsByVariant ($articleRowArray, $variant, $bCombined = FALSE) {

		$variantRowArray = $this->getVariantValuesByArticle($articleRowArray);
		$variantArray = explode(';', $variant);
		$selectableFieldArray = $this->getSelectableFieldArray();
		$possibleArticleArray = array();

		foreach ($articleRowArray as $articleRow)	{
			$bMatches = TRUE;
			$vCount = 0;

			foreach ($this->selectableArray as $k => $v)	{

				$variantIndex = $variantArray[$vCount]; // $k-1

				if ($v && $variantIndex != '')	{
					$field = $selectableFieldArray[$k];
					$value = $articleRow[$field];

					if ($value != '')	{
						$tmpArray = t3lib_div::trimExplode (';', $value);
						$variantValue = $variantRowArray[$field][$variantIndex];

						if (!in_array($variantValue, $tmpArray))	{
							$bMatches = FALSE;
							break;
						}
					} else if (!$bCombined)	{
						$bMatches = FALSE;
					}
				}
				$vCount++;
			}
			if ($bMatches)	{
				$rc[] = $articleRow;
			}
		}

		return $rc;
	}


	public function getFieldArray ()	{
		return $this->fieldArray;
	}


	public function getSelectableFieldArray ()	{
		return $this->selectableFieldArray;
	}


	public function getAdditionalKey ()	{
		return $this->additionalKey;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_variant.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_variant.php']);
}


?>