<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2009 Klaus Zierer <zierer@pz-systeme.de>
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
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_memo_view {
	var $cObj;
	var $pid_list;
	var $pid; // pid where to go
	var $useArticles;
	var $memoItems;
	var $pibaseClass;
	var $conf;


	public function init (
			$pibaseClass,
			$theCode,
			$pid_list,
			$conf,
			$useArticles
		) {
		global $TSFE, $TYPO3_DB;

		$this->pibaseClass = $pibaseClass;
		$pibaseObj = t3lib_div::getUserObj('&' . $pibaseClass);
		$this->cObj = $pibaseObj->cObj;
		$this->conf = $conf;

		$this->pid_list = $pid_list;
		$this->useArticles = $useArticles;
		$fe_user_uid = $TSFE->fe_user->user['uid'];
		$this->memoItems = array();

		if (
			tx_ttproducts_control_memo::bUseFeuser($conf) ||
			tx_ttproducts_control_memo::bUseSession($conf)
		) {
			$functablename = 'tt_products';
			if (strpos($theCode, 'DAM') !== FALSE) {
				$functablename = 'tx_dam';
			}
			$this->memoItems = tx_ttproducts_control_memo::getMemoItems($functablename);
		}
	}

	/**
	 * Displays the memo
	 */
	public function &printView ($theCode,&$templateCode, $pid, &$error_code)	{
		global $TSFE;

		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$content = '';

		if (
			tx_ttproducts_control_memo::bUseFeuser($this->conf) ||
			tx_ttproducts_control_memo::bUseSession($this->conf)
		) {
			if ($this->memoItems)	{
				// List all products:
				$listView = t3lib_div::makeInstance('tx_ttproducts_list_view');
				$listView->init (
					$this->pibaseClass,
					$pid,
					$this->useArticles,
					array(),
					$this->pid_list,
					99
				);

				if ($theCode == 'MEMO')	{
					$theTable = 'tt_products';
					$templateArea = 'MEMO_TEMPLATE';
				} else if ($theCode == 'MEMODAM')	{
					$theTable = 'tx_dam';
					$templateArea = 'MEMODAM_TEMPLATE';
				} else if ($theCode == 'MEMODAMOVERVIEW')	{
					$theTable = 'tx_dam';
					$templateArea = 'MEMODAM_OVERVIEW_TEMPLATE';
				} else {
					return 'error';
				}

				$content = $listView->printView(
					$templateCode,
					$theCode,
					$theTable,
					($this->memoItems ? implode(',', $this->memoItems) : array()),
					FALSE,
					$error_code,
					$templateArea,
					$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'],
					array()
				);
			} else {
				$subpartmarkerObj = t3lib_div::makeInstance('tx_ttproducts_subpartmarker');
				$subpartmarkerObj->init(
					$this->cObj
				);

				$templateArea = 'MEMO_EMPTY';
				$content = $this->cObj->getSubpart($templateCode, $subpartmarkerObj->spMarker('###' . $templateArea . '###'));
				$content = $markerObj->replaceGlobalMarkers($content);
			}
		} else if (tx_ttproducts_control_memo::bIsAllowed('fe_users', $this->conf)) {
			$subpartmarkerObj = t3lib_div::makeInstance('tx_ttproducts_subpartmarker');
			$subpartmarkerObj->init(
				$this->cObj
			);

			$templateArea = 'MEMO_NOT_LOGGED_IN';
			$templateAreaMarker = $subpartmarkerObj->spMarker('###' . $templateArea . '###');
			$content = $this->cObj->getSubpart($templateCode, $templateAreaMarker);
			$content = $markerObj->replaceGlobalMarkers($content);
		}

		if (!$content && !count($error_code)) {
			$templateObj = t3lib_div::getUserObj('&tx_ttproducts_template');
			$error_code[0] = 'no_subtemplate';
			$error_code[1] = '###' . $templateArea . $templateObj->getTemplateSuffix() . '###';
			$error_code[2] = $templateObj->getTemplateFile();
			$content = FALSE;
		}
		return $content;
	}

	public function getFieldMarkerArray (
		&$row,
		$markerKey,
		&$markerArray,
		$tagArray,
		&$bUseCheckBox
	)	{
		$pibaseObj = t3lib_div::getUserObj('&' . $this->pibaseClass);
		$fieldKey = 'FIELD_' . $markerKey . '_NAME';
		if (isset($tagArray[$fieldKey]))	{
			$markerArray['###' . $fieldKey . '###'] = $pibaseObj->prefixId  .'[memo][' . $row['uid'] . ']';
		}
		$fieldKey = 'FIELD_' . $markerKey . '_CHECK';

		if (isset($tagArray[$fieldKey]))	{
			$bUseCheckBox = TRUE;
			if (in_array($row['uid'], $this->memoItems))	{
				$value = 1;
			} else {
				$value = 0;
			}
			$checkString = ($value ? 'checked="checked"' : '');
			$markerArray['###' . $fieldKey . '###'] = $checkString;
		} else {
			$bUseCheckBox = FALSE;
		}
	}

	public function getHiddenFields (
		$uidArray,
		&$markerArray,
		$bUseCheckBox
	)	{

		if ($bUseCheckBox)	{
			$pibaseObj = t3lib_div::getUserObj('&' . $this->pibaseClass);
			$markerArray['###HIDDENFIELDS###'] .= '<input type="hidden" name="' . $pibaseObj->prefixId . '[memo][uids]" value="' . implode(',' , $uidArray) . '" />';
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_memo_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_memo_view.php']);
}


?>