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


require_once (PATH_BE_table.'lib/class.tx_table_db.php');
require_once (PATH_BE_ttproducts.'view/field/class.tx_ttproducts_field_datafield_view.php');


abstract class tx_ttproducts_article_base extends tx_ttproducts_table_base {
	public $table;	 // object of the type tx_table_db
	public $conf;
	public $config;

	public $marker;	// marker prefix in the template file. must be overridden
	public $type; 	// the type of table 'article' or 'product'
			// this gets in lower case also used for the URL parameter
	public $variant;       // object for the product variant attributes, must initialized in the init function
	public $mm_table = ''; // only set if a mm table is used


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init (&$cObj, $functablename)	{
		parent::init($cObj, $functablename);
		$tablename = $this->getTablename();
		$useArticles = $this->conf['useArticles'];
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

		if ($this->type == 'product')	{
			include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_variant.php');

			$this->variant = &t3lib_div::getUserObj('&tx_ttproducts_variant');
			$this->variant->init($this, $tablename, $useArticles);
		} else {
			include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_variant_dummy.php');

			$this->variant = &t3lib_div::getUserObj('&tx_ttproducts_variant_dummy');
		}
		$tableDesc = $this->getTableDesc();

		$this->fieldArray['address'] = ($tableDesc['address'] ? $tableDesc['address'] : 'address');
		$this->fieldArray['itemnumber'] = ($tableDesc['itemnumber'] ? $tableDesc['itemnumber'] : 'itemnumber');
	} // init


	/**
	 * Reduces the instock value of the orderRecord with the amount and returns the result
	 *
	 */
	public function reduceInStock ($uid, $count)	{
		global $TYPO3_DB;

		$tableDesc = $this->getTableDesc();
		$instockField = $tableDesc['inStock'];
		$instockField = ($instockField ? $instockField : 'inStock');

		if (is_array($this->getTableObj()->tableFieldArray[$instockField]))	{
			$uid = intval($uid);
			$fieldsArray = array();
			$fieldsArray[$instockField] = $instockField.'-'.$count;
			$res = $TYPO3_DB->exec_UPDATEquery($this->getTableObj()->name,'uid=\''.$uid.'\'', $fieldsArray,$instockField);
		}
	}


	/**
	 * Reduces the instock value of the orderRecords with the sold items and returns the result
	 *
	 */
	public function reduceInStockItems (&$itemArray, $useArticles)	{
	}


	public function getRelated ($uid, $type) {
	}


	public function getType ()	{
		return $this->type;
	}


	public function getFlexQuery ($type,$val=1)	{
		$spacectrl = '[[:space:][:cntrl:]]*';

		 $rc = '<field index="'.$type.'">'.$spacectrl.'<value index="vDEF">'.($val ? '1': '0').'</value>'.$spacectrl.'</field>'.$spacectrl;
		 return $rc;
	}


	public function addWhereCat (&$catObject, $theCode, $cat, $pid_list)	{
		$where = '';

		return $where;
	}


	public function addselectConfCat ($catObject, $cat, &$selectConf)	{
	}


	public function getPageUidsCat ($cat)	{
		$uids = '';

		return $uids;
	}


	public function getProductField (&$row, $field)	{
		return '';
	}


	/**
	 * Returns true if the item has the $check value checked
	 *
	 */
	public function hasAdditional (&$row, $check)  {
		$hasAdditional = false;
		return $hasAdditional;
	}


	public function getWhere ($where, $theCode='') {
		global $TYPO3_DB;

		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$tableconf = $cnf->getTableConf($this->getFuncTablename(), $theCode);
		$rc = array();
		$where = ($where ? $where : '1=1 ') . $this->getTableObj()->enableFields();

		// Fetching the products
		$res = $this->getTableObj()->exec_SELECTquery('*', $where);
		$translateFields = $cnf->getTranslationFields($tableconf);

		while($row = $TYPO3_DB->sql_fetch_assoc($res))	{

			foreach ($translateFields as $field => $transfield)	{
				$row[$field] = $row[$transfield];
			}
			$rc[$row['uid']] = $this->dataArray[$row['uid']] = $row;
		}
		$TYPO3_DB->sql_free_result($res);
		return $rc;
	}


	/**
	 * Generates a search where clause.
	 */
	public function searchWhere (&$searchFieldList, $sw, $theCode)	{
		$tableConf = $this->getTableConf($theCode);
		$bUseLanguageTable = $this->bUseLanguageTable($tableConf);
		$searchTable = ($bUseLanguageTable ? $this->getTableObj()->getLangAlias() : $this->getTableObj()->getAlias());
		$where = $this->cObj->searchWhere($sw, $searchFieldList, $searchTable);
		return $where;
	} // searchWhere


	public function getNeededUrlParams ($functablename, $theCode)	{
		$rc = '';
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$tableconf = $cnf->getTableConf($functablename, $theCode);
		if (is_array($tableconf) && $tableconf['urlparams'])	{
			$rc = $tableconf['urlparams'];
		}
		return $rc;
	}


	public function mergeAttributeFields (&$targetRow, &$sourceRow, $bKeepNotEmpty=TRUE, $bAddValues=FALSE, $bUseExt=FALSE)	{
		$fieldArray = array();
		$fieldArray['config'] = array('config');
		$fieldArray['data'] = array('itemnumber', 'image', 'weight', 'inStock');
		$fieldArray['price'] = array('price', 'price2', 'directcost');
		$bIsAddedPrice = FALSE;
		$cnfObj = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$tableDesc = $this->getTableDesc();

		if (isset($tableDesc['conf.']) && is_array($tableDesc['conf.']) && isset($tableDesc['conf.']['mergeAppendFields']))	{
			$mergeAppendArray = t3lib_div::trimExplode(',',$tableDesc['conf.']['mergeAppendFields']);
			$fieldArray['text'] = $mergeAppendArray;
		} else {
			$mergeAppendArray = array();
		}

		$fieldArray['text'][] = 'title';
		$fieldArray['text'][] = 'subtitle';
		$fieldArray['text'][] = 'note';
		$fieldArray['text'][] = 'note2';
		$fieldArray['text'] = array_unique($fieldArray['text']);

		$variantFieldArray = $this->variant->getFieldArray();
		$previouslyAddedPrice = 0;
		if ($bUseExt && isset($targetRow['ext']) && is_array($targetRow['ext']) && isset($targetRow['ext']['addedPrice']))	{
			$previouslyAddedPrice = $targetRow['addedPrice'];
		}

		foreach ($fieldArray as $type => $fieldTypeArray)	{
			foreach ($fieldTypeArray as $k => $field)	{
				$mergedFieldArray[] = $field;

				if (isset($sourceRow[$field]))	{
					$value = $sourceRow[$field];

					if ($type == 'config') {
						if ($field == 'config')	{
							$bIsAddedPrice = $cnfObj->hasConfig($sourceRow, 'isAddedPrice');
						}
					}
					if ($type == 'price') {
						if ($bIsAddedPrice)	{
							$value += $targetRow[$field];
							if ($bUseExt)	{
								if (!isset($targetRow['ext']))	{
									$targetRow['ext'] = array();
								}
								$targetRow['ext']['addedPrice'] += $targetRow[$field];
							}
						}
						if ($previouslyAddedPrice)	{
							$value += $previouslyAddedPrice;
						}
						if($bKeepNotEmpty)	{
							if (!floatval($targetRow[$field]))	{
								$targetRow[$field] = $value;
							}
						} else { // $bKeepNotEmpty == FALSE
							$targetRow[$field] = $value;
						}
					} else if (($type == 'text') || ($type == 'data'))	{
						if($bKeepNotEmpty)	{
							if (!$targetRow[$field])	{
								$targetRow[$field] = $value;
							}
						} else { // $bKeepNotEmpty == FALSE
							if (!$targetRow[$field] || $value != '')	{
								if (($bAddValues == TRUE) && in_array($field, $mergeAppendArray))	{
									$targetRow[$field] .= ' ' . $value;
								} else {
									$targetRow[$field] = $value;
								}
							}
						}
					}
				}
			}
		}
		// copy the normal fields

		if (isset($variantFieldArray) && is_array($variantFieldArray))	{
			foreach ($variantFieldArray as $field)	{
				if (isset($sourceRow[$field]))	{
					$value = $sourceRow[$field];
					if($bKeepNotEmpty)	{
						if (!$targetRow[$field])	{
							$targetRow[$field] = $value;
						}
					} else {
						$targetRow[$field] = $value;
					}
				}
			}
		}
	} // mergeAttributeFields
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_article_base.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_article_base.php']);
}


?>
