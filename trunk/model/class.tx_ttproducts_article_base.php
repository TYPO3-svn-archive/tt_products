<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2008 Franz Holzinger <contact@fholzinger.com>
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
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

// require_once (PATH_BE_table.'lib/class.tx_table_db.php');

// require_once (PATH_BE_ttproducts.'view/field/class.tx_ttproducts_field_datafield_view.php');


abstract class tx_ttproducts_article_base extends tx_ttproducts_table_base {
	var $dataArray = array(); // array of read in products
	var $table;	 // object of the type tx_table_db
	var $conf;
	var $config;

	var $tabledesc;
	var $fields = array();
	var $marker;	// marker prefix in the template file. must be overridden
	var $type; 	// the type of table 'article' or 'product'
			// this gets in lower case also used for the URL parameter
	var $variant;       // object for the product variant attributes, must initialized in the init function
	var $mm_table = ''; // only set if a mm table is used

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init ($cObj, $functablename)	{
		parent::init($cObj, $functablename);
		$tablename = $this->getTablename();
		$useArticles = $this->conf['useArticles'];
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->tabledesc = $cnf->getTableDesc($functablename);
		if ($this->type == 'product')	{
			include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_variant.php');

			$this->variant = t3lib_div::getUserObj('&tx_ttproducts_variant');
			$this->variant->init($this, $tablename, $useArticles);
		} else {
			include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_variant_dummy.php');

			$this->variant = t3lib_div::getUserObj('&tx_ttproducts_variant_dummy');
		}

			// image
		$this->fields['address'] = ($this->tabledesc['address'] ? $this->tabledesc['address'] : 'address');
		$this->fields['itemnumber'] = ($this->tabledesc['itemnumber'] ? $this->tabledesc['itemnumber'] : 'itemnumber');
	} // init


	/**
	 * Reduces the instock value of the orderRecord with the amount and returns the result
	 *
	 * @param	[type]		$uid: ...
	 * @param	[type]		$count: ...
	 * @return	[type]		...
	 */
	function reduceInStock($uid, $count)	{
		global $TYPO3_DB;

		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$instockField = $cnf->getTableDesc($this->getTableObj()->name, 'inStock');
		$instockField = ($instockField ? $instockField : 'inStock');

		if (is_array($this->getTableObj()->tableFieldArray[$instockField]))	{
			$uid = intval($uid);
			$fieldsArray = array();
			$fieldsArray[$instockField] = $instockField.'-'.$count;
			$res = $TYPO3_DB->exec_UPDATEquery($this->getTableObj()->name, 'uid=\'' . $uid . '\'', $fieldsArray, $instockField);
		}
	}


	/**
	 * Reduces the instock value of the orderRecords with the sold items and returns the result
	 *
	 * @param	[type]		$$itemArray: ...
	 * @param	[type]		$useArticles: ...
	 * @return	[type]		...
	 */
	function reduceInStockItems(&$itemArray, $useArticles)	{

	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$uid: ...
	 * @param	[type]		$type: ...
	 * @return	[type]		...
	 */
	function getRelated ($uid, $type) {
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$type: ...
	 * @param	[type]		$val: ...
	 * @return	[type]		...
	 */
	function getFlexQuery ($type,$val=1)	{
		$spacectrl = '[[:space:][:cntrl:]]*';

		 $rc = '<field index="'.$type.'">'.$spacectrl.'<value index="vDEF">'.($val ? '1': '0').'</value>'.$spacectrl.'</field>'.$spacectrl;
		 return $rc;
	}


	public function addWhereCat (&$catObject, $theCode, $cat, $pid_list) {
		$where = '';

		return $where;
	}


	public function addselectConfCat ($catObject, $cat, &$selectConf) {
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$cat: ...
	 * @return	[type]		...
	 */
	function getPageUidsCat($cat)	{
		$uids = '';

		return $uids;
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$row: ...
	 * @param	[type]		$field: ...
	 * @return	[type]		...
	 */
	function getProductField(&$row, $field)	{
		return '';
	}


	/**
	 * Returns true if the item has the $check value checked
	 *
	 * @param	[type]		$$row: ...
	 * @param	[type]		$check: ...
	 * @return	[type]		...
	 */
	function hasAdditional(&$row, $check)  {
		$hasAdditional = false;
		return $hasAdditional;
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$where: ...
	 * @param	[type]		$theCode: ...
	 * @return	[type]		...
	 */
	function getWhere ($where, $theCode = '', $orderBy = '') {
		global $TYPO3_DB;

		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$tableconf = $cnf->getTableConf($this->getFuncTablename(), $theCode);
		$rc = array();

		$where = ($where ? $where : '1=1 ').$this->getTableObj()->enableFields();

		// Fetching the products
		$res = $this->getTableObj()->exec_SELECTquery('*', $where, '', $TYPO3_DB->stripOrderBy($orderBy));
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
	 *
	 * @param	[type]		$$searchFieldList: ...
	 * @param	[type]		$sw: ...
	 * @return	[type]		...
	 */
	function searchWhere(&$searchFieldList, $sw)	{
		$where = $this->cObj->searchWhere($sw, $searchFieldList, $this->getTableObj()->getAlias());
		return $where;
	} // searchWhere


	function getNeededUrlParams($conftablename, $theCode)	{
		$rc = '';
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$tableconf = $cnf->getTableConf($conftablename, $theCode);
		if (is_array($tableconf) && $tableconf['urlparams'])	{
			$rc = $tableconf['urlparams'];
		}
		return $rc;
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$targetRow: ...
	 * @param	[type]		$sourceRow: ...
	 * @param	[type]		$bKeepNotEmpty: ...
	 * @return	[type]		...
	 */
	function mergeAttributeFields(&$targetRow, &$sourceRow, $bKeepNotEmpty = TRUE)	{

		$fieldArray = array();
		$fieldArray['data'] = array('title', 'subtitle', 'itemnumber', 'image', 'weight', 'inStock');
		$fieldArray['number'] = array('price', 'price2', 'directcost');

		foreach ($fieldArray as $type => $fieldTypeArray)	{
			foreach ($fieldTypeArray as $k => $field)	{

				if (isset($sourceRow[$field]))	{

					$value = $sourceRow[$field];

					if ($type == 'number') {
						if($bKeepNotEmpty)	{
							if (!floatval($targetRow[$field]))	{
								$targetRow[$field] = $value;
							}
						} else { // $bKeepNotEmpty == FALSE
							$targetRow[$field] = $value;
						}
					} else if ($type == 'data')	{

						if($bKeepNotEmpty)	{
							if (!$targetRow[$field])	{
								$targetRow[$field] = $value;
							}
						} else { // $bKeepNotEmpty == FALSE
							if (!$targetRow[$field] || $sourceRow[$field] != '')	{
								$targetRow[$field] = $value;
							}
						}
					}
				}
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_article_base.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_article_base.php']);
}


?>