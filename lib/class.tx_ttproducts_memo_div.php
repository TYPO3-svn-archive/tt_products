<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2005 Klaus Zierer <zierer@pz-systeme.de>
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
 * memo functions
 *
 * $Id$
 *
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_view_div.php');


class tx_ttproducts_memo_div {

	/**
	 * Displays and manages the memo
	 */
	function memo_display($theCode, &$errorMessage)
	{
		global $TSFE;

		$fe_user_uid = $TSFE->fe_user->user['uid'];
		if (!$fe_user_uid)
			return $this->cObj->getSubpart($this->templateCode,tx_ttproducts_view_div::spMarker($this->pibase, $this->conf, '###MEMO_NOT_LOGGED_IN###'));

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

		return $this->products_display($theCode, $errorMessage, implode(',', $memoItems));
	}


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_memo_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_memo_div.php']);
}


?>
