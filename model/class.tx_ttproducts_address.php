<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * functions for the frontend users
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


global $TYPO3_CONF_VARS;

require_once(PATH_BE_table.'lib/class.tx_table_db.php');


class tx_ttproducts_address extends tx_ttproducts_category_base {
	var $dataArray; // array of read in categories
	var $table;		 // object of the type tx_table_db
	var $fields = array();
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;

	/**
	 * Getting all tt_address values into internal array
	 */
	function init(&$pibase, &$cnf, $tablename)  {
		global $TYPO3_DB,$TSFE,$TCA;
		
		$this->pibase = $pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;

		$tablename = ($tablename ? $tablename : 'tt_address');
		$this->conftablename = 'tt_address';
		$this->table = t3lib_div::makeInstance('tx_table_db');
		$this->tableconf = $this->cnf->getTableConf('tt_address');
		$this->tabledesc = $this->cnf->getTableDesc('tt_address');

		$defaultFieldArray = array('uid'=>'uid', 'pid'=>'pid', 'tstamp'=>'tstamp', 'hidden'=>'hidden', 'deleted' => 'deleted');
		$this->table->setDefaultFieldArray($defaultFieldArray);
		$this->table->setTCAFieldArray($tablename);
		$this->table->setNewFieldArray();

		$requiredListFields = 'uid,pid,title';
		$requiredListArray = t3lib_div::trimExplode(',', $requiredListFields);
		$this->table->setRequiredFieldArray($requiredListArray);

		$this->fields['name'] = ($this->tabledesc['name'] ? $this->tabledesc['name'] : 'name');
	} // init


	/**
	 * Getting all addresses into internal array
	 */
 	function get ($uid=0,$pid=0,$bStore=true) {
		global $TYPO3_DB;

		$where = '';
		$pid = $this->conf['pidsAddresses'];
		if ($pid)	{
			$where = ' pid IN ('.$pid.')';
		}
		if ($uid)	{
			$whereUid = 'uid = '.intval($uid);
			if ($where)	{
				$where .= ' AND '.$whereUid;
			} else {
				$where = $whereUid;
			}
		}
		$orderBy = $this->tableconf['orderBy'];
		$res = $this->table->exec_SELECTquery('*',$where,'',$orderBy);

		while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
			$rc = $this->dataArray[$row['uid']] = $row;
		}
		return $rc;
	}


	function getRootCat()	{
		$rc = $this->cnf->config['rootAddressID'];
		return $rc;
	}


	function &getRelationArray ($excludeCat=0,$currentCat=0,$rootUids='',$pid=0) {
		$relationArray = array();
		$rootArray = t3lib_div::trimExplode(',', $rootUids);

		if (is_array($this->dataArray))	{
			foreach ($this->dataArray as $k => $row)	{
				$uid = $row['uid'];
				$title = $row[$this->fields['name']];
				$relationArray [$uid]['title'] = $title;
				$relationArray [$uid]['pid'] = $row['pid'];
				$relationArray [$uid]['parent_category'] = '';
			}
		}
		
		return $relationArray;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_address.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_address.php']);
}


?>
