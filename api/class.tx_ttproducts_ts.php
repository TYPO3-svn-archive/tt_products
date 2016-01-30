<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Holzinger (franz@ttproducts.de)
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
 * functions to be called from TypoScript
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_ts {
	static $count = 0;

	protected function getChilds ($uid = 0) {
		$cObj = t3lib_div::getUserObj('&tx_div2007_cobj');
		$where = 'pid = ' . $uid . $cObj->enableFields('pages');

		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'pages', $where);
		$childs = array();

		if (isset($rows) && is_array($rows) && count($rows)) {
			foreach ($rows as $row) {
				$childs[] = $row['uid'];
			}
		}

		return $childs;
	}


	protected function getAllChilds ($uid = 0) {

		$childs = $this->getChilds($uid);
		$allChilds = $childs;

		if (isset($childs) && is_array($childs) && count($childs)) {

			foreach ($childs as $child) {
				$grandchilds = $this->getAllChilds($child);
				if (isset($grandchilds) && is_array($grandchilds) && count($grandchilds)) {
					$allChilds = array_merge($allChilds, $grandchilds);
				}
			}
		}

		return $allChilds;
	}


	protected function getProductCount ($uid = 0) {
		$result = 0;
		$cObj = t3lib_div::getUserObj('&tx_div2007_cobj');

		$allChilds = $this->getAllChilds($uid);

		if (isset($allChilds) && is_array($allChilds) && count($allChilds)) {
			$pids = implode(',', $allChilds);
			$where = 'pid IN (' . $pids . ')' . $cObj->enableFields('tt_products');
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'tt_products', $where);
			$result = FALSE;

			if (isset($rows) && is_array($rows)) {
				$result = count($rows);
			}
		}

		return $result;
	}


	protected function getMemoCount ($uid = 0) {
		$result = 0;
		$cObj = t3lib_div::getUserObj('&tx_div2007_cobj');
		$where = 'pid = ' . $uid . $cObj->enableFields('tt_content');

		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,CType,list_type,pi_flexform', 'tt_content', $where);

		if (isset($rows) && is_array($rows) && count($rows)) {
			$count = 0;
			$fieldName = 'display_mode';

			$bMemoFound = FALSE;

			foreach ($rows as $row) {
				if (
					$row['CType'] == 'list' &&
					$row['list_type'] == '5' &&
					$row['pi_flexform'] != ''
				) {
					$flexformArray = t3lib_div::xml2array($row['pi_flexform']);
					$codes =
						tx_div2007_ff::get(
							$flexformArray,
							$fieldName,
							'sDEF',
							'lDEF',
							'vDEF'
						);

					$codeArray = t3lib_div::trimExplode(',' , $codes);
					if (in_array('MEMO', $codeArray)) {

						$bMemoFound = TRUE;
						break;
					}
				}
			}

			if ($bMemoFound) {
				$functablename = 'tt_products';
				$memoItems = tx_ttproducts_control_memo::readSessionMemoItems($functablename);
				if ($memoItems != '') {
					$memoArray = t3lib_div::trimExplode(',', $memoItems);
					$count = count($memoArray);
				}
			}

			$result = $count;
		}

		return $result;
	}


	/**
	* Used to show the product count in a page menu used with pages as categories
	*
	* @param	array		The menu item array, $this->I (in the parent object)
	* @param	array		TypoScript configuration for the function. Notice that the property "parentObj" is a reference to the parent (calling) object (the tslib_Xmenu class instantiated)
	* @return	array		The processed $I array returned (and stored in $this->I of the parent object again)
	* @see tslib_menu::userProcess(), tslib_tmenu::writeMenu(), tslib_gmenu::writeMenu()
	*/
	public function pageProductCount_IProcFunc($I, $conf) {

		self::$count++;
		$itemRow = $conf['parentObj']->menuArr[$I['key']];
		$memoCount = $this->getMemoCount($itemRow['uid']);

		if (is_int($memoCount) && $memoCount > 0) {
			$I['parts']['title'] .= ' (' . $memoCount . ')';
		} else {
			$productCount = $this->getProductCount($itemRow['uid']);

			if (is_int($productCount) && $productCount > 0) {
				$I['parts']['title'] .= ' (' . $productCount . ')';
			}
		}

		return $I;
	}


	public function processMemo() {

		$functablename = 'tt_products';
		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][TT_PRODUCTS_EXT.'.'];
		$piVars = t3lib_div::_GPmerged('tt_products');

		tx_ttproducts_control_memo::process($functablename, $piVars, $conf);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/api/class.tx_ttproducts_ts.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/api/class.tx_ttproducts_ts.php']);
}


?>