<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2007 Klaus Zierer <zierer@pz-systeme.de>
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
 * memo functions
 *
 * $Id$
 *
 * @author  Klaus Zierer <zierer@pz-systeme.de>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_memo_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $pid_list;
	var $pid; // pid where to go
	var $useArticles;
	var $memoItems;

	public function init(
			$pibase,
			$pid_list,
			$pid,
			$conf,
			$useArticles
		) {
		$this->pibase = $pibase;
		$this->pid_list = $pid_list;
		$this->pid = $pid;
		$this->conf = $conf;
		$this->useArticles = $useArticles;
// 		$fe_user_uid = $TSFE->fe_user->user['uid'];
		$this->memoItems = array();

		if (
			tx_ttproducts_control_memo::bUseFeuser($conf) ||
			tx_ttproducts_control_memo::bUseSession($conf)
		) {
			$functablename = 'tt_products';
			$this->memoItems = tx_ttproducts_control_memo::getMemoItems($functablename);
		}
	}


	/**
	 * Displays the memo
	 *
	 * @param	[type]		$$templateCode: ...
	 * @param	[type]		$error_code: ...
	 * @return	[type]		...
	 */
	function printView($templateCode, &$error_code)	{
		global $TSFE;

		$content = '';

// 		$fe_user_uid = $TSFE->fe_user->user['uid'];

		if (
			tx_ttproducts_control_memo::bUseFeuser($this->conf) ||
			tx_ttproducts_control_memo::bUseSession($this->conf)
		) {
			if ($this->memoItems) {
				include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_list_view.php');

				// List all products:
				$listView = t3lib_div::makeInstance('tx_ttproducts_list_view');
				$listView->init (
					$this->pibase,
					$this->pid,
					$this->useArticles,
					array(),
					$this->pid_list,
					99
				);

				$templateArea = 'MEMO_TEMPLATE';
				$content = $listView->printView(
					$templateCode,
					'MEMO',
					'tt_products',
					implode(',', $this->memoItems),
					false,
					$error_code,
					$templateArea,
					$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'],
					array()
				);
			} else {
				include_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_subpartmarker.php');

				$subpartmarkerObj = t3lib_div::makeInstance('tx_ttproducts_subpartmarker');
				$subpartmarkerObj->init(
					$this->pibase->cObj
				);

				$content = $this->pibase->cObj->getSubpart($templateCode,$subpartmarkerObj->spMarker('###MEMO_EMPTY###'));
			}
		} else if (tx_ttproducts_control_memo::bIsAllowed('fe_users', $this->conf)) {
			include_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_subpartmarker.php');

			$subpartmarkerObj = t3lib_div::makeInstance('tx_ttproducts_subpartmarker');
			$subpartmarkerObj->init(
				$this->pibase->cObj
			);

			$content = $this->pibase->cObj->getSubpart($templateCode,$subpartmarkerObj->spMarker('###MEMO_NOT_LOGGED_IN###'));
		}

		return $content;
	}

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_memo_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_memo_view.php']);
}


?>