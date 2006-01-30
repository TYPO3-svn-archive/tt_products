<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2006 Klaus Zierer <zierer@pz-systeme.de>
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
 * memo functions
 *
 * $Id$
 *
 * @author  Klaus Zierer <zierer@pz-systeme.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_memo_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $basket;
	var $page;
	var $tt_content; // element of class tx_table_db
	var $tt_products; // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db
	var $pid; // pid where to go

	var $searchFieldList='';

	function init(&$pibase, &$conf, &$config, &$basket, &$page, &$tt_content, &$tt_products, &$tt_products_cat, $pid) {
		$this->pibase = &$pibase;
		$this->conf = &$conf;
		$this->config = &$config;
		$this->basket = &$basket;
		$this->page = &$page;
		$this->tt_content = &$tt_content;
		$this->tt_products = &$tt_products;
		$this->tt_products_cat = &$tt_products_cat;
		$this->pid = $pid;	

	}



	/**
	 * Displays and manages the memo
	 */
	function &printView(&$templateCode, &$error_code)
	{
		global $TSFE;
		
		$content = '';

		$fe_user_uid = $TSFE->fe_user->user['uid'];
		if ($fe_user_uid)	{
			if ($TSFE->fe_user->user['tt_products_memoItems'] != '')
				$memoItems = explode(',', $TSFE->fe_user->user['tt_products_memoItems']);
			else
				$memoItems = array();
	
			if (t3lib_div::GPvar('addmemo'))
			{
				$addMemo = explode(',', t3lib_div::GPvar('addmemo'));
	
				foreach ($addMemo as $addMemoSingle)
					if (!in_array($addMemoSingle, $memoItems))
						$memoItems[] = $addMemoSingle;
	
				$fieldsArray = array();
				$fieldsArray['tt_products_memoItems']=implode(',', $memoItems);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$fe_user_uid, $fieldsArray);
			}
	
			if (t3lib_div::GPvar('delmemo'))
			{
				$delMemo = explode(',', t3lib_div::GPvar('delmemo'));
	
				foreach ($delMemo as $delMemoSingle)
					if (in_array($delMemoSingle, $memoItems))
						unset($memoItems[array_search($delMemoSingle, $memoItems)]);
	
				$fieldsArray = array();
				$fieldsArray['tt_products_memoItems']=implode(',', $memoItems);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$fe_user_uid, $fieldsArray);
			}
			
			include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_list_view.php');
	
			// List all products:
			$listView = t3lib_div::makeInstance('tx_ttproducts_list_view');
			$listView->init ($this->pibase, $this->conf, $this->config, $this->basket, $this->page, $this->content, $this->tt_products, $this->tt_products_cat, $this->pid);
			
			$content = $listView->printView($templateCode, 'MEMO', implode(',', $memoItems), $error_code);
		} else {
			include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_marker.php');

			$marker = t3lib_div::makeInstance('tx_ttproducts_marker');
			$marker->init($this->pibase, $this->conf, $this->config, $this->basket);
			
			$content = $this->pibase->cObj->getSubpart($templateCode,$marker->spMarker('###MEMO_NOT_LOGGED_IN###'));
		}

		return $content;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_memo_view.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_memo_view.php']);
}


?>
