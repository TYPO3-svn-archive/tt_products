<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2010 Franz Holzinger <franz@ttproducts.de>
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


require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_article_base.php');


class tx_ttproducts_article extends tx_ttproducts_article_base {
	public $fields = array();
	public $tt_products; // element of class tx_table_db to get the parent product
	public $marker = 'ARTICLE';
	public $type = 'article';
	public $tableArray;
	protected $tableAlias = 'article';


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init (&$cObj, $functablename)  {
		global $TYPO3_DB,$TSFE,$TCA;

		parent::init($cObj, $functablename);
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$tableConfig = array();
		$tableConfig['orderBy'] = $cnf->conf['orderBy'];

		if (!$tableConfig['orderBy'])	{
			 $tableConfig['orderBy'] = $this->getOrderBy ();
		}

		$tableObj = &$this->getTableObj();
		$tableObj->setConfig($tableConfig);
		$tableObj->addDefaultFieldArray(array('sorting' => 'sorting'));
	} // init


	function &getWhereArray ($prodUid, $where) {
		global $TYPO3_DB;

		$rowArray = array();
		$enableWhere = $this->getTableObj()->enableFields();
		$where = ($where ? $where . ' ' . $enableWhere : '1=1 ' . $enableWhere);
		$alias = $this->getAlias();

		if (in_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['articleMode'], array(1,2)))	{

			$finalWhere = 'tt_products_products_mm_articles.uid_local=' . intval($prodUid) . ' AND tt_products_products_mm_articles.deleted=0 AND tt_products_products_mm_articles.hidden=0' . ($where!='' ? ' AND '.$where : '');
			$mmTable = 'tt_products_products_mm_articles';
			$uidForeignArticle = 'uid_foreign';
			$fromJoin = 'tt_products_articles ' . $alias . ' JOIN ' . $mmTable . ' ON ' . $alias . '.uid=' . $mmTable . '.' . $uidForeignArticle;
			$finalOrderBy = $mmTable . '.sorting';
		} else {
			// $fromJoin = 'tt_products_articles ' . $alias;
			$finalWhere = $alias . '.uid_product=' . intval($prodUid) . ($where ? ' AND ' . $where : '');
			$finalOrderBy = '';
		}
		$res = $this->getTableObj()->exec_SELECTquery('*',$finalWhere,'',$finalOrderBy,'',$fromJoin);
		$variantFieldArray = $this->variant->getFieldArray();

		while($row = $TYPO3_DB->sql_fetch_assoc($res))	{
			$uid = intval($row['uid']);
			$this->getTableObj()->substituteMarkerArray($row, $variantFieldArray);
			$this->dataArray[$uid] = $row;	// remember for later queries
			$uidArray[] = $uid;
			$rowArray[] = $row;
		}
		$TYPO3_DB->sql_free_result($res);
		return $rowArray;
	}


	function getProductField (&$row, $field)	{
		$rc = '';
		if ($field != 'uid')	{
			$rowProducts = $this->tt_products->get($row['uid_product']);
			$rc = $rowProducts[$field];
		} else {
			$rc = $row['uid_product'];
		}
		return $rc;
	}


	function &getProductRow ($row)	{
		$rc = $this->tt_products->get($row['uid_product']);
		return $rc;
	}


	public function getRequiredFieldArray ($theCode='')	{
		$tableConf = $this->getTableConf($theCode);
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$rc = array();
		if ($tableConf['requiredFields']!='')	{
			$requiredFields = $tableConf['requiredFields'];
		} else {
			$requiredFields = 'uid,pid,category,price,price2,directcost';
		}
		$instockField = $cnf->getTableDesc($functablename,'inStock');
		if ($instockField && !$this->conf['alwaysInStock'])	{
			$requiredFields = $requiredFields . ',' . $instockField;
		}

		$rc = $requiredFields;
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_article.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_article.php']);
}

?>
