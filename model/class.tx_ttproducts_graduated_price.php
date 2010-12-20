<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
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
 * basket price calculation functions using the price tables
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_graduated_price {
	public $mmArray = array();
	public $dataArray = array(); // array of read in products
	public $tableObj;	// object of the type tx_table_db
	public $conf;
	public $config;
	public $tableconf;
	public $tabledesc;
	public $conftablename = 'tt_products_graduated_price';
	public $mm_table = ''; // mm table

	/**
	 * Getting all tt_products_cat categories into internal array
	 *
	 * @param	[type]		$tablename: ...
	 * @param	[type]		$mmtablename: ...
	 * @return	[type]		...
	 */
	function init($tablename, $mmtablename)  {
		global $TYPO3_DB,$TSFE,$TCA;

		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		if ($tablename=='')	{
			$tablename = 'tt_products_graduated_price';
			$mmtablename = 'tt_products_mm_graduated_price';
		}
		$this->conftablename = $tablename;
		$this->mm_table = $mmtablename;
		$this->tableconf = $cnf->getTableConf($tablename);
		$this->tabledesc = $cnf->getTableDesc($tablename);

		$this->tableObj = t3lib_div::makeInstance('tx_table_db');
		$this->tableObj->setTCAFieldArray($tablename, 'tt_products_graduated_price');
	} // init


	function getFormulasByProduct ($uid=0,$where_clause='') {
		global $TYPO3_DB;

		if ($uid && !is_array($uid) && isset($this->mmArray[$uid]) && is_array($this->mmArray[$uid]))	{
			$rc = array();
			foreach ($this->mmArray[$uid] as $v)	{
				$rc[] = $this->dataArray[$v];
			}
		}
		if (!$rc) {
			$where = '1=1 '.$this->tableObj->enableFields();
			if ($uid)	{
				$uidWhere = $this->mm_table.'.product_uid ';
				if (is_array($uid))	{
					foreach ($uid as $v)	{
						if (!t3lib_div::testInt($v))	{
							return 'ERROR: not integer '.$v;
						}
					}
					$uidWhere .= 'IN ('.implode(',',$uid).')';
				} else {
					$uidWhere .= '='.intval($uid);
				}
				$where .= ' AND '.$uidWhere;
			}
			if ($where_clause)	{
				$where .= ' '.$where_clause;
			}
			// SELECT *
			// FROM tt_products_graduated_price
			// LEFT OUTER JOIN tt_products_mm_graduated_price ON tt_products_graduated_price.uid = tt_products_mm_graduated_price.graduated_price_uid

			$from = $this->tableObj->name.' LEFT OUTER JOIN '.$this->mm_table.' ON '.$this->tableObj->name.'.uid='.$this->mm_table.'.graduated_price_uid';

			// Fetching the products
			$res = $this->tableObj->exec_SELECTquery('*', $where, $groupBy, $orderBy, $limit, $from);
			$rc = array();

			while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
				$rc[] = $this->dataArray[$row['uid']] = $row;
			}
			$TYPO3_DB->sql_free_result($res);

			if (is_array($uid))	{
				$res = $TYPO3_DB->exec_SELECTquery('*', $this->mm_table, $uidWhere, '', '', $limit);
				while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
					$this->mmArray[$row['product_uid']][] = $row['graduated_price_uid'];
				}
				$TYPO3_DB->sql_free_result($res);
			} else {
				foreach ($this->dataArray as $k => $v)	{
					$this->mmArray[$uid][] = $k;
				}
			}
		}
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_graduated_price.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_graduated_price.php']);
}

?>
