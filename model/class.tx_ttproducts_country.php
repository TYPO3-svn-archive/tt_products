<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * functions for the static_info_countries table
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

global $TYPO3_CONF_VARS;

require_once(PATH_BE_table.'lib/class.tx_table_db.php');


class tx_ttproducts_country  {
	var $cnf;
	var $dataArray; // array of read in contents
	var $table;		 // object of the type tx_table_db

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, &$cnf, $LLkey, $tablename)	{
		global $TYPO3_DB;

		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$tablename = ($tablename ? $tablename : 'pages');
		$this->tableconf = $this->cnf->getTableConf('static_countries');
		$this->table = t3lib_div::makeInstance('tx_table_db');
		$this->table->setDefaultFieldArray(array('uid'=>'uid', 'pid'=>'pid'));		
		$this->table->setTCAFieldArray('static_countries');

		$requiredFields = 'uid,pid';
		if ($this->tableconf['requiredFields'])	{
			$tmp = $this->tableconf['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}	
		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$this->table->setRequiredFieldArray($requiredListArray);

		if (is_array($this->tableconf['generatePath.']) &&
			$this->tableconf['generatePath.']['type'] == 'tablefields' &&
			is_array($this->tableconf['generatePath.']['field.'])
			)	{
			$addRequiredFields = array();
			foreach ($this->tableconf['generatePath.']['field.'] as $field => $value)	{
				$addRequiredFields[] = $field;
			}
			$this->table->addRequiredFieldArray ($addRequiredFields);
		}
	} // init



	function get ($country_code, $where, $fields='') {
		global $TYPO3_DB, $TCA;

		if (!$fields)	{
			$rc = $this->dataArray[$country_code];
		}
		if (!$rc || $where) {
			if ($country_code)	{
				$whereString = 'cn_iso_3 = '.$TYPO3_DB->fullQuoteStr($country_code, $this->table->name);
			} else {
				$whereString = '1=1';
			}
			if ($where)	{
				$whereString .= ' AND '.$where;
			}

			$whereString .= ' '.$this->table->enableFields();
			$fields = ($fields ? $fields : '*');
			// Fetching the countries

			$res = $this->table->exec_SELECTquery($fields, $whereString);
			if ($country_code)	{
				$row = $TYPO3_DB->sql_fetch_assoc($res);
			 	$rc = $row;
			 	if ($row)	{
			 		$this->dataArray[$row['cn_iso_3']] = $row;
			 	}
			} else {
				while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
					$rc []= $this->dataArray[$row['uid']] = $row;
				}
			}
		}
		return $rc;
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	array		marker array
	 * @return	array
	 * @access private
	 */
	function getItemMarkerArray (&$row, &$markerArray, &$fieldsArray)	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		global $TSFE;
		$markerTable = implode('',t3lib_div::trimExplode('_',$this->table->name));
		
		foreach ($fieldsArray as $k => $field)	{
			$markerArray['###'.strtoupper($markerTable.'_'.$field).'###'] = htmlentities($row [$field],ENT_QUOTES,$TSFE->renderCharset);
		}
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_country.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_country.php']);
}


?>
