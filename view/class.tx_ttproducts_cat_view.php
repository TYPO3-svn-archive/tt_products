<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * category single view functions
 * may be used for the category, partner/address, dam category and pages table
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_cat_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;

	var $subpartmarkerObj; // subpart marker functions
	var $urlObj; // url functions
	var $javascript; // JavaScript functions
	var $javaScriptMarker; // JavaScript marker functions
	var $pid; // PID where to go
	var $pidListObj;
	var $cOjb;

	function init(&$pibase, $pid, $pid_list, $recursive) {
		$this->pibase = &$pibase;
		$this->cObj = &$pibase->cObj;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		$this->pid = $pid;
		$this->subpartmarkerObj = t3lib_div::makeInstance('tx_ttproducts_subpartmarker');
		$this->subpartmarkerObj->init($this->cObj);
		$this->urlObj = &t3lib_div::getUserObj('&tx_ttproducts_url_view');

		$this->pidListObj = &t3lib_div::getUserObj('tx_ttproducts_pid_list');
		$this->pidListObj->init($this->cObj);
		$this->pidListObj->applyRecursive($recursive, $pid_list, TRUE);
		$this->pidListObj->setPageArray();

		$this->javaScriptMarker = t3lib_div::makeInstance('tx_ttproducts_javascript_marker');
		$this->javaScriptMarker->init($pibase);
	}


	// returns the single view
	function &printView(&$templateCode, $functablename, $uid, $theCode, &$error_code, $templateSuffix = '') {
		global $TSFE, $TCA, $TYPO3_DB;

		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$tableObj = &$tablesObj->get($functablename);
		$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
		$javaScriptObj = &t3lib_div::getUserObj('&tx_ttproducts_javascript');
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

		if ($this->config['displayCurrentRecord'])	{
			$row = $this->cObj->data;
		} else if ($uid) {
			$pidField = ($functablename == 'pages' ? 'uid' : 'pid');
			$where = ' AND '.$pidField.' IN ('.$this->pidListObj->getPidlist().')';
			$row = $tableObj->get($uid, 0, TRUE, $where);

			$tableConf = $cnf->getTableConf($functablename, $theCode);
			$tableLangFields = $cnf->getTranslationFields($tableConf);
		}
		foreach ($tableLangFields as $type => $fieldArray)	{
			if (is_array($fieldArray))	{
				foreach ($fieldArray as $field => $langfield)	{
					$row[$field] = $row[$langfield];
				}
			}
		}

		if ($row) {
			// $this->uid = intval ($row['uid']); // store the uid for later usage here

			$markerArray = array();
			$subpartArray = array();
			$wrappedSubpartArray = array();
			$pageObj = $tablesObj->get('pages');

			if ($this->config['displayCurrentRecord'])	{
				$subPartMarker = '###'.$tableObj->marker.'_SINGLE_DISPLAY_RECORDINSERT###';
			} else {
				$subPartMarker = '###'.$tableObj->marker.'_SINGLE_DISPLAY###';
			}

			// Add the template suffix
			$subPartMarker = substr($subPartMarker, 0, -3).$templateSuffix.'###';
			$itemFrameWork = $this->cObj->getSubpart($templateCode,$this->subpartmarkerObj->spMarker($subPartMarker));
			$tablesObj->get('fe_users')->getWrappedSubpartArray($subpartArray, $wrappedSubpartArray, $itemTable->getFuncTablename());
			$itemFrameWork = $this->cObj->substituteMarkerArrayCached($itemFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);
			$markerFieldArray = array();
			$viewTagArray = array();
			$parentArray = array();
			$fieldsArray = $markerObj->getMarkerFields(
				$itemFrameWork,
				$tableObj->getTableObj()->tableFieldArray,
				$tableObj->getTableObj()->requiredFieldArray,
				$markerFieldArray,
				$tableObj->marker,
				$viewTagArray,
				$parentArray
			);

				// Fill marker arrays
			$backPID = $this->pibase->piVars['backPID'];
			$backPID = ($backPID ? $backPID : t3lib_div::_GP('backPID'));
			$basketPID = $this->conf['PIDbasket'];
			$pid = $backPID;

			$param = array($functablename => $variantFieldArray);
			$javaScriptObj->set('fetchdata', $param);
			$bUseBackPid = true;

			$addQueryString = array();
			$linkPid = $pid;
			if ($bUseBackPid && $backPID)	{
				$linkPid = $backPID;
			}

			if ($viewTagArray['LINK_ITEM'])	{
				$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'. $this->pibase->pi_getPageLink($linkPid,'',$this->urlObj->getLinkParams('',$addQueryString,true,$bUseBackPid,$tableObj->piVar),array('useCacheHash' => true)) .'">','</a>');
			}
			if ($viewCatTagArray['LINK_CATEGORY'])	{
				$catListPid = $pageObj->getPID(
					$this->conf['PIDlistDisplay'],
					$this->conf['PIDlistDisplay.'],
					$row
				);
				$tableObj->getSubpartArrays(
					$this->urlObj,
					$row,
					$subpartArray,
					$wrappedSubpartArray,
					$viewTagArray,
					$catListPid,
					'LINK_CATEGORY'
				);
			}

			$viewParentCatTagArray = array();
			$tableObj->getParentMarkerArray (
				$parentArray,
				$row,
				$catParentArray,
				$uid,
				$row['pid'],
				$this->config['limitImage'],
				'listcatImage',
				$viewParentCatTagArray,
				array(),
				$pageAsCategory,
				$theCode,
				1,
				''
			);

			if ($viewCatTagArray['LINK_PARENT1_CATEGORY'])	{
				$catRow = $tableObj->getParent($cat);
				$catListPid = $pageObj->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $catRow);
				$viewCatTable->getSubpartArrays($this->urlObj, $catRow, $subpartArray, $wrappedSubpartArray, $viewTagArray, $catListPid, 'LINK_PARENT1_CATEGORY');
			}

			$tableObj->getItemMarkerArray (
				$row,
				$markerArray,
				$variantFieldArray,
				$variantMarkerArray,
				$viewTagArray,
				$theCode,
				TRUE,
				''
			);

			$subpartArray = array();
			$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'];
			$addQueryString = array();
			if ($pid == $TSFE->id)	{
				$addQueryString[$tableObj->getPivar()] = $uid;
			}

			$markerArray = $this->urlObj->addURLMarkers($pid, $markerArray, $addQueryString); // Applied it here also...

			$queryPrevPrefix = '';
			$queryNextPrefix = '';
			$prevOrderby = '';
			$nextOrderby = '';

			if(is_array($tableConf) && isset($tableConf['orderBy']) && strpos($itemTableConf['orderBy'],',') === FALSE)	{
				$orderByField = $tableConf['orderBy'];
				$queryPrevPrefix = $orderByField.' < '.$TYPO3_DB->fullQuoteStr($row[$orderByField],$tableObj->getTableObj()->name);
				$queryNextPrefix = $orderByField.' > '.$TYPO3_DB->fullQuoteStr($row[$orderByField],$tableObj->getTableObj()->name);
				$prevOrderby = $orderByField.' DESC';;
				$nextOrderby = $orderByField.' ASC';
			} else {
				$queryPrevPrefix = 'uid < '.intval($uid);
				$queryNextPrefix = 'uid > '.intval($uid);
				$prevOrderby = 'uid DESC';
				$nextOrderby = 'uid ASC';
			}

			$prevOrderby = $tableObj->getTableObj()->transformOrderby($prevOrderby);
			$nextOrderby = $tableObj->getTableObj()->transformOrderby($nextOrderby);

			$whereFilter = '';
			if (is_array($tableConf['filter.']) && is_array($tableConf['filter.']['regexp.']))	{
				if (is_array($tableConf['filter.']['regexp.']['field.']))	{
					foreach ($tableConf['filter.']['field.'] as $field => $value)	{
						$whereFilter .= ' AND '.$field.' REGEXP \''.$value.'\'';
					}
				}
			}

			$queryprev = '';
			$queryprev = $queryPrevPrefix .' AND pid IN ('.$this->pidListObj->getPidlist().')'. $tableObj->getTableObj()->enableFields();
			// $resprev = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $queryprev,'', $prevOrderby);
			$resprev = $tableObj->getTableObj()->exec_SELECTquery('*', $queryprev, '', $TYPO3_DB->stripOrderBy($prevOrderby));

			if ($rowprev = $TYPO3_DB->sql_fetch_assoc($resprev) )	{
				$addQueryString=array();
				$addQueryString[$tableObj->getPivar()] = $rowprev['uid'];

				if ($bUseBackPid) 	{
					$addQueryString ['backPID'] = $backPID;
				}
				$wrappedSubpartArray['###LINK_PREV_SINGLE###']= array('<a href="'. $this->pibase->pi_getPageLink($TSFE->id,'',$this->urlObj->getLinkParams('',$addQueryString,true,$bUseBackPid,''),array('useCacheHash' => true)) .'">','</a>');
			} else	{
				$subpartArray['###LINK_PREV_SINGLE###']='';
			}
			$TYPO3_DB->sql_free_result($resprev);

			$querynext = $queryNextPrefix.' AND pid IN ('.$this->pidListObj->getPidlist().')'. $wherestock . $tableObj->getTableObj()->enableFields();
			// $resnext = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $querynext, $nextOrderby);
			$resnext = $tableObj->getTableObj()->exec_SELECTquery('*', $querynext, '', $TYPO3_DB->stripOrderBy($nextOrderby));

			if ($rownext = $TYPO3_DB->sql_fetch_assoc($resnext) )	{
				$addQueryString=array();
				$addQueryString[$this->type] = $rownext['uid'];
				$addQueryString['backPID'] = $backPID;
				if ($bUseBackPid) 	{
					$addQueryString ['backPID'] = $backPID;
				} else if ($cat)	{
					$addQueryString [$viewCatTable->getPivar()] = $linkCat;
				}

				$wrappedSubpartArray['###LINK_NEXT_SINGLE###'] = array('<a href="'. $this->pibase->pi_getPageLink($TSFE->id,'',$this->urlObj->getLinkParams('', $addQueryString,true,$bUseBackPid,''),array('useCacheHash' => true)) .'">','</a>');
			} else {
				$subpartArray['###LINK_NEXT_SINGLE###'] = '';
			}
			$TYPO3_DB->sql_free_result($resnext);

			$jsMarkerArray = array();
			$this->javaScriptMarker->getMarkerArray($jsMarkerArray, $markerArray);
			$markerArray = array_merge ($jsMarkerArray, $markerArray);

				// Substitute
			$content = $this->cObj->substituteMarkerArrayCached($itemFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);

		} else {
			$error_code[0] = 'wrong_parameter';
			$error_code[1] = intval($uid);
			$error_code[2] = $this->pidListObj->getPidlist();
		}
		return $content;
	} // print
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_cat_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_cat_view.php']);
}


?>
