<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2006 Franz Holzinger <kontakt@fholzinger.com>
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
 * Part of the tt_products (Shopping System) extension.
 *
 * functions for the product
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_article_base.php');


class tx_ttproducts_article extends tx_ttproducts_article_base {
	var $dataArray; // array of read in categories
	var $fields = array();
	var $tt_products; // element of class tx_table_db to get the parent product
	var $bIsProduct=false;
	var $marker = 'ARTICLE';
	var $type = 'article';

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, &$conf, &$config, &$tt_products, &$tt_content, $LLkey, $tablename, &$tableconf, &$articleConf)  {
		global $TYPO3_DB,$TSFE,$TCA;
		
		
		$this->tt_products = &$tt_products;
		$tablename = ($tablename ? $tablename : 'tt_products_articles');
		$this->table = t3lib_div::makeInstance('tx_table_db');
		$this->table->setConfig($conf['conf.'][$tablename.'.']);
		$this->table->addDefaultFieldArray(array('sorting' => 'sorting'));
		$this->table->setTCAFieldArray($tablename);

		$requiredListFields = 'uid,pid,uid_product,price,price2';
		if (is_array($tableconf['ALL.']))	{
			$tmp = $tableconf['ALL.']['requiredListFields'];
			$requiredListFields = ($tmp ? $tmp : $requiredListFields);
		}
		$requiredListArray = t3lib_div::trimExplode(',', $requiredListFields);
		$this->table->setRequiredFieldArray($requiredListArray);

		parent::init($pibase, $conf, $config, $tt_content);


		$this->variant = t3lib_div::makeInstance('tx_ttproducts_variant');
		$this->variant->init($this->pibase, $tableconf['variant.'], $this, 0);


	} // init


	function get ($uid) {
		global $TYPO3_DB;
		$rc = $this->dataArray[$uid];
		if (!$rc) {
			$this->table->enableFields($this->table->name);		
			$res = $this->table->exec_SELECTquery('*','uid = '.$uid);
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$rc = $this->dataArray[$row['uid']] = $row;
		}
		return $rc;
	}


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

	function &getProductRow($row)	{
		$rc = $this->tt_products->get($row['uid_product']);
		return $rc;
	}


	function mergeProductRow(&$row, &$productRow)	{
		$fieldArray = array();
		$fieldArray['text'] = array('title', 'subtitle', 'itemnumber', 'image');
		$fieldArray['number'] = array('price', 'price2', 'weight');
		
		foreach ($fieldArray as $type => $fieldTypeArray)	{
			foreach ($fieldTypeArray as $k => $field)	{
				if ($type == 'number' && !floatval($row[$field]))	{
					$row[$field] = $productRow[$field];
				} else if ($type == 'text' && !$row[$field])	{
					$row[$field] = $productRow[$field];
				}
			}
		}
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_article.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_article.php']);
}


?>
