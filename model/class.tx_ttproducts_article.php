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
 * functions for the product
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

/*
require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_article_base.php');*/


class tx_ttproducts_article extends tx_ttproducts_article_base {
	var $fields = array();
	var $tt_products; // element of class tx_table_db to get the parent product
	var $marker = 'ARTICLE';
	var $type = 'article';
	var $tableArray;

	/**
	 * Getting all tt_products_cat categories into internal array
	 *
	 * @param	[type]		$$cObj: ...
	 * @param	[type]		$tablename: ...
	 * @return	[type]		...
	 */
	function init($cObj, $tablename)  {
		global $TYPO3_DB,$TSFE,$TCA;

		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$conftablename = 'tt_products_articles';
		parent::init($cObj, $conftablename);
		$tablename = ($tablename ? $tablename : $conftablename);

		$this->getTableObj()->addDefaultFieldArray(array('sorting' => 'sorting'));
		$this->getTableObj()->setTCAFieldArray($tablename);

		$requiredFields = 'uid,pid,uid_product,price,price2,directcost';
		$tableconf = $cnf->getTableConf($conftablename,$theCode);
		if ($tableconf['requiredFields'])	{
			$tmp = $tableconf['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}

		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$this->getTableObj()->setRequiredFieldArray($requiredListArray);

		if ($tableconf['language.'] && is_array($tableconf['language.']['marker.']))	{
			$this->getTableObj()->initMarkerFile($tableconf['language.']['marker.']['file']);
		}
	} // init


	function get($uid = 0, $pid = 0, $bStore = true, $where_clause = '', $limit = '', $fields = '', $bCount = FALSE) {
		global $TYPO3_DB;

		$rc = $this->dataArray[$uid];
		if (!$rc && $uid) {
			$where = '1=1 '.$this->getTableObj()->enableFields().' AND uid = ' . intval($uid);
			if ($where_clause)	{
				$where .= ' ' . $where_clause;
			}
			// Fetching the articles
			$res = $this->getTableObj()->exec_SELECTquery('*', $where);
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$TYPO3_DB->sql_free_result($res);
			$variantFieldArray = $this->variant->getFieldArray();
			$this->getTableObj()->substituteMarkerArray($row, $variantFieldArray);
			$rc = $this->dataArray[$uid] = $row;
		}
		return $rc;
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$where: ...
	 * @return	[type]		...
	 */
	function getWhereArray ($where) {
		global $TYPO3_DB;
		$rowArray = array();
		$enableWhere = $this->getTableObj()->enableFields();
		$where = ($where ? $where.' '.$enableWhere : '1=1 ' . $enableWhere);

		$res = $this->getTableObj()->exec_SELECTquery('*', $where);
		$variantFieldArray = $this->variant->getFieldArray();

		while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
			$uid = intval($row[uid]);

			$this->getTableObj()->substituteMarkerArray($row, $variantFieldArray);
			$this->dataArray[$uid] = $row;	// remember for later queries
			$rowArray[] = $row;
		}
		$TYPO3_DB->sql_free_result($res);
		return $rowArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$row: ...
	 * @param	[type]		$field: ...
	 * @return	[type]		...
	 */
	function getProductField(&$row, $field)	{
		$rc = '';
		if ($field != 'uid')	{
			$rowProducts = $this->tt_products->get($row['uid_product']);
			$rc = $rowProducts[$field];
		} else {
			$rc = $row['uid_product'];
		}
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function getProductRow($row)	{
		$rc = $this->tt_products->get($row['uid_product']);
		return $rc;
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_article.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_article.php']);
}


?>