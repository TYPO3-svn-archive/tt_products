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
 * functions for the DAM images
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_article_base.php');


class tx_ttproducts_dam extends tx_ttproducts_article_base {
	public $dataArray; // array of read in categories
	public $tableArray;

	public $marker='DAM';
	public $type='dam';
	public $piVar='dam';

	public $mm_table='tx_dam_mm_cat';
	public $image;
	public $variant; // object for the product variant attributes, must initialized in the init function

	/**
	 * DAM elements
	 */
	public function init (&$cObj, $functablename)  {
		global $TYPO3_DB,$TSFE,$TCA;

		parent::init($cObj, $functablename);

		$this->tableArray = &$tableArray;
		$tableObj = &$this->getTableObj();
		$tableObj->addDefaultFieldArray(array('sorting' => 'sorting'));

		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$tablename = $cnf->getTableName($functablename);
		$tableObj->setTCAFieldArray($tablename, 'dam');
	} // init

	function getRelated ($uid, $type) {
		global $TYPO3_DB;

		$rcArray = array();
		if ($type == 'products')	{
			$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
			$productTable = &$tablesObj->get('tt_products', FALSE);
			$additional = $productTable->getFlexQuery('isImage',1);
			$rowArray = $productTable->getWhere('additional REGEXP ' . $TYPO3_DB->fullQuoteStr($additional,$productTable->getTablename)); // quotemeta
			$rcArray = array_keys($rowArray);
		}
		return $rcArray;
	}

	/**
	 * Returns true if the item has the $check value checked
	 *
	 */
	public function hasAdditional (&$row, $check)  {
		$hasAdditional = false;
		return $hasAdditional;
	}

	/**
	 * Sets the markers for DAM specific FORM fields
	 *
	 */
	public function setFormMarkerArray ($uid, &$markerArray)  {
		$markerArray['###DAM_FIELD_NAME###'] = 'ttp_basket[dam]';
		$markerArray['###DAM_UID###'] = intval($uid);
	}

	/**
	 * fills in the row fields from a DAM record
	 *
	 * @param	array		the row
	 * @param	string	  variants separated by ';'
	 * @return  void
	 * @access private
	 * @see getVariantFromRow
	 */
	public function modifyItemRow (&$row, $uid) {
		$damRow = $this->get($uid);

		if ($damRow)	{
// 			$damRow['damdescription'] = $damRow['description'];
// 			unset($damRow['description']);
// 			foreach ($damRow as $field => $value)	{
// 				if (isset($row[$field]) && !$row[$field])	{
// 					$row[$field] = $value;
// 				}
// 			}
			if ($damRow['file_mime_type'] == 'image' && !$row['image'])	{
				$row['image'] = $damRow['file_name'];
				$row['file_mime_type'] = 'image';
				$row['file_path'] = $damRow['file_path'];
			}
		}
	}

	public function addWhereCat (&$catObject, $theCode, $cat, $pid_list, $bLeadingOperator=TRUE) {
		$bOpenBracket = FALSE;
		$where = '';

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['DAMCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['DAMCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addWhereCat')) {
					$whereNew = $hookObj->addWhereCat($this, $catObject, $cat, $where, $operator, $pid_list, $catObject->getDepth($theCode));
					if ($bLeadingOperator)	{
						$operator = ($operator ? $operator : 'OR');
						$where .= ($whereNew ? ' '.$operator.' '.$whereNew : '');
					} else {
						$where .= $whereNew;
					}
				}
			}
		} else if($cat || $cat == '0') {
			$cat = implode(',',t3lib_div::intExplode(',', $cat));
			$where = 'category IN ('.$cat.')';
			if ($bLeadingOperator)	{
				$where = ' AND ( ' . $where . ')';
			}
		}
		return $where;
	}

	public function addConfCat ($catObject, &$selectConf, $aliasArray)	{
		$tableNameArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['DAMCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['DAMCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addConfCatProduct')) {
					$newTablenames = $hookObj->addConfCatProduct($this, $catObject, $selectConf, $aliasArray);

					if ($newTablenames != '')	{
						$tableNameArray[] = $newTablenames;
					}
				}
			}
		}

		return implode(',', $tableNameArray);
	}

	public function addselectConfCat ($catObject, $cat, &$selectConf)	{
		$tableNameArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['DAMCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['DAMCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addselectConfCat')) {
					$newTablenames = $hookObj->addselectConfCat($this, $catObject, $cat, $selectConf,$catObject->getDepth());
					if ($newTablenames != '')	{
						$tableNameArray[] = $newTablenames;
					}
				}
			}
		}
		return implode(',', $tableNameArray);
	}

	public function getPageUidsCat ($cat)	{
		$uidArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['DAMCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['DAMCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getPageUidsCat')) {
					$hookObj->getPageUidsCat($this, $cat, $uidArray);
				}
			}
		}
		$uidArray = array_unique($uidArray);
		return (implode(',',$uidArray));
	}

	public function getRequiredFields ($theCode='')	{
		$tableConf = $this->getTableConf($theCode);
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

		if ($tableConf['requiredFields'])	{
			$requiredFields = $tableConf['requiredFields'];
		} else {
			$requiredFields = 'uid,pid,parent_id,category,file_mime_type,file_name,file_path';
		}

		$rc = $requiredFields;
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_dam.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_dam.php']);
}


?>
