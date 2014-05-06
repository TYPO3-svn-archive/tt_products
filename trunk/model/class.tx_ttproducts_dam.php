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
 * functions for the DAM images
 *
 * $Id$
 *
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


// require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_article_base.php');


class tx_ttproducts_dam extends tx_ttproducts_article_base {
	var $dataArray; // array of read in categories

	var $marker='DAM';
	var $type='dam';
	var $piVar='dam';

	var $mm_table='tx_dam_mm_cat';
	var $image;
	var $variant; // object for the product variant attributes, must initialized in the init function

	/**
	 * DAM elements
	 *
	 * @param	[type]		$$cObj: ...
	 * @param	[type]		$functablename: ...
	 * @return	[type]		...
	 */
	function init($cObj, $functablename)  {
		global $TYPO3_DB,$TSFE,$TCA;

		parent::init($cObj, $functablename);
		$tableObj = $this->getTableObj();
		$tableObj->addDefaultFieldArray(array('sorting' => 'sorting'));

		$requiredFields = 'uid,pid,parent_id,category,file_mime_type,file_name,file_path';
		if ($this->tableconf['requiredFields'])	{
			$tmp = $this->tableconf['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}

		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$tableObj->setRequiredFieldArray($requiredListArray);

		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$tablename = $cnf->getTableName($functablename);
		$tableObj->setTCAFieldArray($tablename, 'dam');
	} // init


	function getRelated ($uid, $type) {
		$rcArray = array();

		if ($type == 'products')	{
			$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
			$productTable = $tablesObj->get('tt_products', FALSE);

			$additional = $productTable->getFlexQuery ('isImage',1);
			$rowArray = $productTable->getWhere ('additional REGEXP \'' . $additional . '\'');

			$rcArray = array_keys($rowArray);
		}
		return $rcArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$where: ...
	 * @return	[type]		...
	 */
	function &getWhereArray ($where) {
		global $TYPO3_DB;

		$rowArray = array();
		$this->getTableObj()->enableFields();
		$res = $this->getTableObj()->exec_SELECTquery('*', $where);

		while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
			$uid = intval($row[uid]);
			$this->dataArray[$uid] = $row;	// remember for later queries
			$rowArray[] = $row;
		}
		$TYPO3_DB->sql_free_result($res);
		return $rowArray;
	}

	public function addWhereCat ($catObject, $theCode, $cat, $pid_list, $bLeadingOperator=TRUE) {
		$bOpenBracket = FALSE;
		$where = '';

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory'] as $classRef) {
				$hookObj= t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addWhereCat')) {
					$whereNew = $hookObj->addWhereCat($this, $catObject, $cat, $where, $operator, $pid_list, $catObject->getDepth($theCode));
					if ($bLeadingOperator) {
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
			if ($bLeadingOperator) {
				$where = ' AND ( ' . $where . ')';
			}
		}
		return $where;
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * 			 			for the tt_producst record, $row
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @param	[type]		$imageRenderObj: ...
	 * @param	[type]		$tagArray: ...
	 * @param	[type]		$forminfoArray: ...
	 * @param	[type]		$code: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$prefix: ...
	 * @param	[type]		$linkWrap: ...
	 * @return	array		Returns a markerArray ready for substitution with information
	 * @access private
	 */
	function getItemMarkerArray (&$item, &$markerArray, $catTitle, &$basketExt, $imageNum=0, $imageRenderObj='image', $tagArray, $forminfoArray=array(), $code='', $id='1', $prefix='', $linkWrap='')	{
		global $TSFE;
		$row = &$item['rec'];

		$imageObj = t3lib_div::getUserObj('&tx_ttproducts_field_image_view');
			// Get image
		$imageObj->getItemMarkerArrayEnhanced ($this->getFuncTablename(), $row, $this->marker, $markerArray, $row['pid'], $imageNum, $imageRenderObj, $tagArray, $code, $id, $prefix, $linkWrap);

		foreach ($row as $field => $value)	{
			if (!is_array($row[$field]))	{
				$markerArray['###'.$this->marker.'_'.strtoupper($field).'###'] = htmlentities($row[$field],ENT_QUOTES,$TSFE->renderCharset);
			}
		}

			// Call all getItemMarkerArray hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$this->marker])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$this->marker] as $classRef) {
				$hookObj= t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getItemMarkerArray')) {
					$hookObj->getItemMarkerArray ($this, $item, $markerArray, $catTitle, $basketExt, $imageNum, $imageRenderObj, $tagArray, $forminfoArray, $code, $id, $prefix, $linkWrap);
				}
			}
		}
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
	 * Sets the markers for DAM specific FORM fields
	 *
	 * @param	[type]		$uid: ...
	 * @param	[type]		$markerArray: ...
	 * @return	[type]		...
	 */
	function setFormMarkerArray($uid, &$markerArray)  {
		$markerArray['###DAM_FIELD_NAME###'] = 'ttp_basket[dam]';
		$markerArray['###DAM_UID###'] = intval($uid);
	}


	/**
	 * fills in the row fields from a DAM record
	 *
	 * @param	array		the row
	 * @param	string		variants separated by ';'
	 * @return	void
	 * @access private
	 * @see getVariantFromRow
	 */
	 function modifyItemRow (&$row, $uid) {
		$damRow = $this->get($uid);

		if ($damRow)	{

			if ($damRow['file_mime_type'] == 'image' && !$row['image'])	{
				$row['image'] = $damRow['file_name'];
				$row['file_mime_type'] = 'image';
				$row['file_path'] = $damRow['file_path'];
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_dam.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_dam.php']);
}


?>