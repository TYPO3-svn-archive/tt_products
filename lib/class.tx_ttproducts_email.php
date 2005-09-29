<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2005 Franz Holzinger <kontakt@fholzinger.com>
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
 * Part of the tt_products (Shopping System) extension.
 *
 * functions for the category
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once(PATH_BE_table.'lib/class.tx_table_db.php');
require_once(PATH_BE_table.'lib/class.tx_table_db_access.php');


class tx_ttproducts_email {
	var $emailArray;	// array of read in categories
	var $tt_products_emails;			// object of the type tx_table_db

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init()	{
		global $TYPO3_DB;
		
		$this->tt_products_emails = t3lib_div::makeInstance('tx_table_db');
		$this->tt_products_emails->setTCAFieldArray('tt_products_emails');

	} // init


	function getEmail ($uid) {
		global $TYPO3_DB;
		#debug ($uid, 'getEmail: $uid', __LINE__, __FILE__);
		$rc = $this->emailArray[$uid];;
		if (!$rc) {
			$sql = t3lib_div::makeInstance('tx_table_db_access');
			$sql->prepareFields($this->tt_products_emails, 'select', '*');
			$sql->prepareWhereFields ($this->tt_products_emails, 'uid', '=', $uid);
			$this->tt_products_emails->enableFields('tt_products_emails');
			// Fetching the email
		 	$res = $sql->exec_SELECTquery();
		 	$row = $TYPO3_DB->sql_fetch_assoc($res);
		 	$rc = $this->emailArray[$row['uid']] = $row;
		}
		return $rc;
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_email.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_email.php']);
}


?>
