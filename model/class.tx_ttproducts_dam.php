<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2008 Franz Holzinger <contact@fholzinger.com>
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
 * functions for the DAM images
 *
 * $Id$
 *
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

global $TYPO3_CONF_VARS;

require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_article_base.php');


class tx_ttproducts_dam extends tx_ttproducts_article_base {
	var $dataArray; // array of read in categories

	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $tt_content; // element of class tx_table_db to get the content elements
	var $tt_products; // element of class tx_table_db to get the parent product
	var $paymentshipping;		// payment and shipping object to make the price dependant on it

	var $bIsProduct=true;	// if this is the base for a product
	var $marker = 'DAM';
	var $type = 'dam';
	var $piVar = 'dam';

	var $tableconf;
	var $tabledesc;
	var $mm_table = 'tx_dam_mm_cat';

	var $image;
	var $variant; // object for the product variant attributes, must initialized in the init function

	/**
	 * DAM elements
	 */
	function init(&$pibase, &$cnf, &$tt_products, &$tt_content, &$paymentshipping, $LLkey, $tablename)  {
		global $TYPO3_DB,$TSFE,$TCA,$TYPO3_CONF_VARS;

		parent::init($pibase, $cnf, 'tx_dam', $tt_content, $paymentshipping, 0);

		$this->tt_products = &$tt_products;

		$this->table = t3lib_div::makeInstance('tx_table_db');
		$this->table->addDefaultFieldArray(array('sorting' => 'sorting'));
		$this->table->setTCAFieldArray($tablename);

		$requiredFields = 'uid,pid,parent_id,category,file_mime_type,file_name,file_path';
		if ($this->tableconf['requiredFields'])	{
			$tmp = $this->tableconf['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}

		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$this->table->setRequiredFieldArray($requiredListArray);

		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->tt_content = &$tt_content;
		$this->tableconf = $this->cnf->getTableConf($tablename);
		$this->tabledesc = $this->cnf->getTableDesc($tablename);
 		$this->paymentshipping = &$paymentshipping;
	} // init



	function get ($uid) {
		global $TYPO3_DB;
		$rc = $this->dataArray[$uid];
		if (!$rc) {
			$this->table->enableFields();
			$res = $this->table->exec_SELECTquery('*','uid = '.intval($uid));
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$rc = $this->dataArray[$uid] = $row;
		}
		return $rc;
	}


	function &getWhereArray ($where) {
		global $TYPO3_DB;

		$rowArray = array();
		$this->table->enableFields();
		$res = $this->table->exec_SELECTquery('*',$where);

		while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
			$uid = intval($row[uid]);
			$this->dataArray[$uid] = $row;	// remember for later queries
			$rowArray[] = $row;
		}
		return $rowArray;
	}


	function addWhereCat($cat, $pid_list)	{
		global $TYPO3_CONF_VARS;
		$bOpenBracket = FALSE;
		$where = '';

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['DAMCategory'])) {
			foreach  ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['DAMCategory'] as $classRef) {
				$hookObj= t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addWhereCat')) {
					$whereNew = $hookObj->addWhereCat($this, $cat, $where, $operator, $pid_list, $this->mm_table);
					$operator = ($operator ? $operator : 'OR');
					$where .= ($whereNew ? ' '.$operator.' '.$whereNew : '');
				}
			}
		} else if($cat || $cat=='0') {
			$cat = implode(',',t3lib_div::intExplode(',', $cat));
			$where = ' AND ( category IN ('.$cat.') )';
		}

		return $where;
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
	 * @return	array		Returns a markerArray ready for substitution with information
	 * 			 			for the tt_producst record, $row
	 * @access private
	 */
	function getItemMarkerArray (&$item, &$markerArray, $catTitle, &$basketExt, $imageNum=0, $imageRenderObj='image', $tagArray, $forminfoArray=array(), $code='', $id='1', $prefix='', $linkWrap='')	{
		global $TSFE, $TYPO3_CONF_VARS;
		$row = &$item['rec'];

			// Get image
		$this->image->getItemMarkerArray ($row, $markerArray, $row['pid'], $imageNum, $imageRenderObj, $tagArray, $code, $id, $prefix, $linkWrap);

		foreach ($row as $field => $value)	{
			if (!is_array($row[$field]))	{
				$markerArray['###'.$this->marker.'_'.strtoupper($field).'###'] = htmlentities($row[$field],ENT_QUOTES,$TSFE->renderCharset);
			}
		}

			// Call all getItemMarkerArray hooks at the end of this method
		if (is_array ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey][$this->marker])) {
			foreach  ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey][$this->marker] as $classRef) {
				$hookObj= t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getItemMarkerArray')) {
					$hookObj->getItemMarkerArray ($this, $markerArray, $item, $catTitle, $imageNum, $imageRenderObj, $forminfoArray, $code, $id, $linkWrap);
				}
			}
		}
	}


	/**
	 * Returns true if the item has the $check value checked
	 *
	 */
	function hasAdditional(&$row, $check)  {
		$hasAdditional = false;
		return $hasAdditional;
	}


	/**
	 * Sets the markers for DAM specific FORM fields
	 *
	 */
	function setFormMarkerArray($uid, &$markerArray)  {
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
	 function modifyItemRow (&$row, $uid) {
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
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_dam.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_dam.php']);
}


?>
