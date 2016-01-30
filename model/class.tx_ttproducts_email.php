<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * functions for the category
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_email extends tx_ttproducts_table_base {
	var $emailArray;	// array of read in emails
	var $table;		 // object of the type tx_table_db

	/**
	 * Getting all tt_products_cat categories into internal array
	 *
	 * @param	[type]		$$pibase: ...
	 * @param	[type]		$functablename: ...
	 * @return	[type]		...
	 */
	function init(&$pibase, $functablename)  {
		parent::init($pibase, $functablename);
		$tablename = $this->getTablename();
		$this->getTableObj()->addDefaultFieldArray(array('sorting' => 'sorting'));
		$this->getTableObj()->setTCAFieldArray('tt_products_emails');
	} // init


	function getEmail ($uid) {
		global $TYPO3_DB;
		$rc = $this->emailArray[$uid];;
		if ($uid && !$rc) {
			$sql = t3lib_div::makeInstance('tx_table_db_access');
			$sql->prepareFields($this->getTableObj(), 'select', '*');
			$sql->prepareWhereFields ($this->getTableObj(), 'uid', '=', intval($uid));
			$this->getTableObj()->enableFields();
			// Fetching the email
			$res = $sql->exec_SELECTquery();
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$rc = $this->emailArray[$row['uid']] = $row;
		}
		return $rc;
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_email.php'])  {
  include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_email.php']);
}


?>