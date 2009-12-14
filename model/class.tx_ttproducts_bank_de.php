<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2009 Franz Holzinger <franz@ttproducts.de>
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
 * functions for the static_banks_de table
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once(PATH_BE_table.'lib/class.tx_table_db.php');


class tx_ttproducts_bank_de {
	var $table;
	var $dataArray;

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init ()  {
		global $TYPO3_DB,$TSFE,$TCA;

		if (t3lib_extMgm::isLoaded('static_info_tables_banks_de')) {

			$tablename = 'static_banks_de';

			$this->table = t3lib_div::makeInstance('tx_table_db');
			$this->table->setDefaultFieldArray(array('uid'=>'uid', 'pid'=>'pid', 'starttime'=>'starttime', 'endtime'=>'endtime'));

			$this->table->setName($tablename);
			$this->table->setTCAFieldArray($tablename, 'bank');
		}
	} // init



	/**
	 * Getting all bank data into internal array
	 */
	function get ($uid=0,$pid=0,$bStore=true,$where) {
		global $TYPO3_DB;

		if (is_object($this->table))	{
			if (is_array($this->dataArray[$uid]))	{
				if (($pid && $this->dataArray[$uid]['pid'] == $pid) || ($pid == 0))	{
					$rc = $this->dataArray[$uid];
				} else {
					$rc = array();
				}
			}

			if (!$rc) {
				$where = '1=1 '.$this->table->enableFields().($where!='' ? ' AND '.$where : '');
				$where .= ($uid ? ' AND uid='.intval($uid) : '');
				$where .= ($pid ? ' AND pid IN ('.$pid.')' : '');
				$res = $this->table->exec_SELECTquery('*',$where);

				if ($uid)	{
					$row = $TYPO3_DB->sql_fetch_assoc($res);
					$rc =  $row;
					if ($bStore)	{
						$this->dataArray[$uid] = $rc;
					}
				} else {

					while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
						$rc = $row;
						if ($bStore)	{
							$this->dataArray[$row['uid']] = $rc;
						} else {
							break;
						}
					}
				}
			}
			if (!$rc) {
				$rc = array();
				$this->dataArray = array();
			}
		} else {
			$rc = array('uid' => $uid);
		}

		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_bank_de.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_bank_de.php']);
}


?>
