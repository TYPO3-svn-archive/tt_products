<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012-2012 Franz Holzinger <franz@ttproducts.de>
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
 * control of memo functions for the customer.
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */



class tx_ttproducts_control_memo {

	static protected $memoTableFieldArray = array(
		'tt_products' => 'memoItems',
		'tx_dam' => 'memodam'
	);
	static protected $memoItemArray = array();

	static public function getMemoTableFieldArray () {
		return self::$memoTableFieldArray;
	}

	static private function bIsAllowed ($type, $conf) {
		$result = FALSE;

		if (
			isset($conf['memo.']) &&
			isset($conf['memo.']['allow'])
		) {
			if (t3lib_div::inList($conf['memo.']['allow'], $type)) {
				$result = TRUE;
			}
		}
		return $result;
	}

	static public function bUseFeuser ($conf) {

		$result = FALSE;
		$fe_user_uid = tx_div2007::getFrontEndUser('uid');

		if ($fe_user_uid) {
			$result = self::bIsAllowed('fe_users', $conf);
		}

		return $result;
	}

	static public function bUseSession ($conf) {
		$result = self::bIsAllowed('session', $conf);
		return $result;
	}

	static public function process ($functablename, $piVars, $conf) {

		$bMemoChanged = FALSE;
		self::loadMemo($functablename, $conf);

		$memoItems = self::getMemoItems($functablename);

		if ($piVars['addmemo']) {
			$addMemo = explode(',', $piVars['addmemo']);
		}

		if ($piVars['delmemo']) {
			$delMemo = explode(',', $piVars['delmemo']);
		}

		if (isset($piVars['memo']) && is_array($piVars['memo'])) {
			if (!isset($addMemo)) {
				$addMemo = array();
			}
			if (!isset($delMemo)) {
				$delMemo = array();
			}

			foreach ($piVars['memo'] as $k => $v) {
				if (t3lib_div::testInt($k) && $k != '' && $v) {
					$addMemo[] = intval($k);
				} else if ($k == 'uids') {
					$uidArray = explode(',', $v);
					foreach ($uidArray as $uid) {
						if (t3lib_div::testInt($uid) && $uid != '' && in_array($uid, $memoItems)) {
							$delMemo[] = $uid;
						}
					}
				}
			}
		}

		if (isset($addMemo) && is_array($addMemo)) {
			foreach ($addMemo as $addMemoSingle) {
				if (!in_array($addMemoSingle, $memoItems)) {
					$uid = intval($addMemoSingle);
					if ($uid) {
						$memoItems[] = $uid;
						$bMemoChanged = TRUE;
					}
				}
			}
		}

		if ($delMemo) {
			foreach ($delMemo as $delMemoSingle) {
				$val = intval($delMemoSingle);
				if (in_array($val, $memoItems)) {
					unset($memoItems[array_search($val, $memoItems)]);
					$bMemoChanged = TRUE;
				}
			}
		}

		if ($bMemoChanged) {

			self::saveMemo($functablename, $memoItems, $conf);
			self::setMemoItems($functablename, $memoItems);
		}
	}

	static public function getMemoField ($functablename, $bFeuser) {
		if (isset(self::$memoTableFieldArray[$functablename])) {
			$result = ($bFeuser ? 'tt_products_' : '') . self::$memoTableFieldArray[$functablename];
		} else {
			$result = FALSE;
		}
		return $result;
	}

	static public function getMemoItems ($functablename) {
		$result = self::$memoItemArray[$functablename];
		return $result;
	}

	static public function setMemoItems ($functablename, $v) {
		if (!is_array($v)) {
			if ($v == '') {
				$v = array();
			} else {
				$v = explode(',', $v);
			}
		}
		self::$memoItemArray[$functablename] = $v;
	}

	static public function readSessionMemoItems ($functablename) {
		$result = '';
		$session = tx_ttproducts_control_session::readSessionData();
		$tableArray = self::getMemoTableFieldArray();
		$field = $tableArray[$functablename];

		if (
			$field != '' &&
			is_array($session) &&
			isset($session[$field])
		) {
			$result = $session[$field];
		}

		return $result;
	}

	static public function readFeUserMemoItems ($functablename) {
		$result = '';
		$tableArray = self::getMemoTableFieldArray();
		$field = $tableArray[$functablename];

		if ($GLOBALS['TSFE']->fe_user->user[$field]) {
			$result = explode(',', $GLOBALS['TSFE']->fe_user->user[$field]);
		}

		return $result;
	}

	static public function loadMemo ($functablename, $conf) {
		$memoItems = '';
		$bFeuser = self::bUseFeuser($conf);
		$theField = self::getMemoField($functablename, $bFeuser);

		if (self::bUseFeuser($conf)) {

			$memoItems = self::readFeUserMemoItems($functablename);
		} else {
			$memoItems = self::readSessionMemoItems($functablename);
		}
		self::setMemoItems($functablename, $memoItems);
	}

	static public function saveMemo ($functablename, $memoItems, $conf) {
		$bFeuser = self::bUseFeuser($conf);
		$feuserField = self::getMemoField($functablename, $bFeuser);

		$fieldsArray = array();
		$fieldsArray[$feuserField] = implode(',', $memoItems);

		if ($bFeuser) {
			$fe_user_uid = tx_div2007::getFrontEndUser('uid');
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid=' . $fe_user_uid, $fieldsArray);
		} else {
			tx_ttproducts_control_session::writeSessionData($fieldsArray);
		}
	}

	static public function copySession2Feuser ($params, $pObj, $conf) {

		$tableArray = self::getMemoTableFieldArray();
		foreach ($tableArray as $functablename) {
			$memoItems = self::readSessionMemoItems($functablename);

			if (strlen($memoItems)) {
				$feuserMemoItems = self::readFeUserMemoItems($functablename);
				if (isset($feuserMemoItems) && is_array($feuserMemoItems)) {
					$memoItems = array_merge($feuserMemoItems, $memoItems);
				}
				self::saveMemo($functablename, $memoItems, $conf);
			}
		}
	}
}

?>