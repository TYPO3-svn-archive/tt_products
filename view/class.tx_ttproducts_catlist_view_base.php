<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2009 Franz Holzinger <franz@ttproducts.de>
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
 * category list view functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



abstract class tx_ttproducts_catlist_view_base {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $pidListObj; // pid where to go
	var $subpartmarkerObj; // marker functions
	var $urlObj; // url functions
	var $htmlTagMain = '';	// main HTML tag
	var $htmlTagElement = ''; // HTML tag element
	var $htmlPartsMarkers = array('###ITEM_SINGLE_PRE_HTML###', '###ITEM_SINGLE_POST_HTML###');


	public function init ($pibase, $pid_list, $recursive, $pid) {
		$this->pibase = $pibase;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->pid = $pid;

		$this->urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view');
		$this->pidListObj = t3lib_div::getUserObj('tx_ttproducts_pid_list');
		$this->pidListObj->init($this->pibase->cObj);
		$this->pidListObj->applyRecursive($recursive, $pid_list, TRUE);
		$this->pidListObj->setPageArray();

		$this->subpartmarkerObj = t3lib_div::makeInstance('tx_ttproducts_subpartmarker');
		$this->subpartmarkerObj->init($pibase->cObj);
		$this->htmlTagMain = ($this->htmlTagMain ? $this->htmlTagMain : $this->conf['displayCatListType']);

		if (!$this->htmlTagElement)	{
			switch ($this->htmlTagMain)	{
				case 'ul':
					$this->htmlTagElement = 'li';
					break;
				case 'div':
					$this->htmlTagElement = 'div';
					break;
				case 'tr':
					$this->htmlTagElement = 'td';
					break;
			}
		}
	}


	public function getIsParentArray ($cat, &$categoryArray)	{
		$isParentArray = array();

		if ($cat)	{
			$uid = $cat;
			$isParentArray[$uid] = true;
			// get all forefathers
			while ($uid = $categoryArray[$uid]['parent_category'])	{
				$isParentArray[$uid] = true;
			}
		}
		return $isParentArray;
	}


	// sets the 'depth' field
	public function setDepths (&$categoryRootArray, &$categoryArray)	{
		$depth = 1;
		$endArray = array();
		foreach ($categoryArray as $category => $row)	{
				// is it a leaf in a tree ?
			if (!is_array($row['child_category']))	{
				$endArray [] = (int) $category;
			}
		}
		foreach ($endArray as $k => $category)	{
			$count = 0;
			$actCategory = (int) $category;
			// determine the highest parent
			while ($actCategory && !$categoryArray[$actCategory]['depth'] && $count < 100) {
				$count++;
				$lastCategory = $actCategory;
				$actCategory = (int) $categoryArray[$actCategory]['parent_category'];
			}

			$depth = $count + $categoryArray[$lastCategory]['depth'];
			// now write the calculated count into the fields
			$actCategory = $category;
			while ($actCategory && isset($categoryArray[$actCategory]) && !$categoryArray[$actCategory]['depth']) {
				$categoryArray[$actCategory]['depth'] = $depth--;
				$actCategory = (int) $categoryArray[$actCategory]['parent_category'];
			}
		}
	}


	public function getFrameWork (&$t, &$templateCode, $area)	{
		$subpart = $this->subpartmarkerObj->spMarker('###'.$area.'###');
		$t['listFrameWork'] = $this->pibase->cObj->getSubpart($templateCode,$subpart);

		if ($t['listFrameWork'])	{
			$t['categoryFrameWork'] = $this->pibase->cObj->getSubpart($t['listFrameWork'],'###CATEGORY_SINGLE###');

			$t['linkCategoryFrameWork'] = $this->pibase->cObj->getSubpart($t['categoryFrameWork'],'###LINK_CATEGORY###');
		}
	}


	// returns the products list view
	public function printView (
		$functablename,
		&$templateCode,
		&$t,
		&$htmlParts,
		$theCode,
		&$error_code,
		$templateArea,
		$pageAsCategory,
		$templateSuffix,
		&$currentCat,
		&$categoryArray,
		&$catArray,
		&$isParentArray,
		&$subCategoryMarkers
	 ) {
		global $TSFE, $TCA, $TYPO3_DB;

		$rc = TRUE;
		$mode = '';
		$this->getFrameWork($t, $templateCode, $templateArea.$templateSuffix);
		$bUseFilter = FALSE;
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$categoryTableView = $tablesObj->get($functablename,1);
		$categoryTable = $categoryTableView->getModelObj();
		$categoryTable->clear();
		$categoryTable->initCodeConf($theCode);
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$tableConf = $cnf->getTableConf($functablename, $theCode);
		$orderBy = $TYPO3_DB->stripOrderBy($tableConf['orderBy']);

		if (!count($error_code) && $t['listFrameWork'] && is_object($categoryTable))	{
			$subCategoryMarkers = array();
			$offset = 0;
			$depth = 0;

			while (($pos = strpos($t['linkCategoryFrameWork'], '###SUBCATEGORY_', $offset)) !== FALSE)	{
				if (($posEnd = strpos($t['linkCategoryFrameWork'], '###', $pos + 1)) !== FALSE)	{
					$marker = substr ($t['linkCategoryFrameWork'], $pos + 3, $posEnd - $pos - 3);
					$tmpArray = explode('_', $marker);
					$count = count($tmpArray);
					if ($count)	{
						$theDepth = intval($tmpArray[$count-1]);
						if ($theDepth > $depth)	{
							$depth = $theDepth;
						}
						$subCategoryMarkers[$theDepth] = $marker;
					}
				}
				$offset = $pos+1;
			}
			$subpartArray = array();
			$subpartArray['###LINK_CATEGORY###'] = '###CATEGORY_TMP###';
			$tmp = $this->pibase->cObj->substituteMarkerArrayCached($t['categoryFrameWork'],array(),$subpartArray);
			$htmlParts = t3lib_div::trimExplode('###CATEGORY_TMP###', $tmp);
			$rootCat = $categoryTable->getRootCat();
			$currentCat = $categoryTable->getParamDefault($theCode, $this->pibase->piVars[$categoryTableView->piVar]);

			if ($pageAsCategory && $functablename == 'pages')	{
				$excludeCat = $this->pibase->cObj->data['pages'];
				if (!$rootCat)	{
					$rootCat = $excludeCat;
				}
			} else {

				if (is_array($tableConf['special.']) &&
				isset($tableConf['special.']['all']) &&
				$currentCat == $tableConf['special.']['all']
				)	{
					$mode = 'all';
				}

				if (
					is_array($tableConf['filter.']) &&
					is_array($tableConf['filter.']['param.']) &&
					$tableConf['filter.']['param.']['cat'] == 'gp'
				)	{
					$bUseFilter = TRUE;
					if ($mode == 'all')	{
						$tmpRowArray = $categoryTable->get(0);
						unset ($tmpRowArray[$currentCat]);
						$childArray = array_keys($tmpRowArray);
					} else {
						$childArray = $categoryTable->getChildCategoryArray($currentCat);
					}
					$allowedCatArray = array();
					foreach ($childArray as $k => $cat)	{
						$bIsSpecial = $categoryTable->hasSpecialConf($cat, $theCode, 'no');

						if (!$bIsSpecial)	{
							$categoryTable->get($cat, $this->pidListObj->getPidlist(),TRUE,'','','',FALSE,'',$orderBy);	// read all categories

							if ($depth && !$tableConf['onlyChildsOfCurrent'])	{
								$subChildArray = $categoryTable->getChildCategoryArray($cat);
								foreach ($subChildArray as $k2 => $subCat)	{
									$categoryTable->get($subCat, $this->pidListObj->getPidlist());	// read the sub categories
								}
							}
							$allowedCatArray[] = $cat;
						}
					}
					$rootCat = $currentCat;
					if (
						tx_div2007_core::testInt($rootCat)
					) {
						$allowedCatArray[] = $rootCat;
					}
					$allowedCats = implode (',', $allowedCatArray);
					$excludeCat = $rootCat;
				} else if ($tableConf['onlyChildsOfCurrent'])	{
					$pids = $this->pidListObj->getPidlist();
					if ($rootCat == '') {
						$rootCat = $categoryTable->getAllChildCats($pids, $orderBy, '');
					}
					$relatedArray = $categoryTable->getRelated($rootCat, $currentCat, $pids, $orderBy);	// read only related categories
				} else if ($tableConf['rootChildsOfCurrent']) {
					$pids = $this->pidListObj->getPidlist();
					$rootCat = $categoryTable->getAllChildCats($pids, $orderBy, $currentCat);
					$relatedArray = $categoryTable->getRelated($rootCat, 0, $pids, $orderBy);	// read only related categories
				} else {
					// read in all categories
					$categoryTable->get('0', $this->pidListObj->getPidlist(),TRUE,'','','',FALSE,'',$orderBy);	// read all categories
					$excludeCat = 0;
				}
				if (is_array($categoryTable->dataArray))	{
					// ksort ($categoryTable->dataArray);
					$excludeCat = 0;
					$categoryTable->translateByFields($theCode);
				}

				if (is_array($tableConf['special.']) && strlen($tableConf['special.']['no']))	{
					$excludeCat = $tableConf['special.']['no'];
				}
			}
			if ($functablename == 'pages')	{
				$allowedCats = $this->pidListObj->getPidlist($rootCat);
			}

			$categoryArray = $categoryTable->getRelationArray($excludeCat, $rootCat, $allowedCats);
			$rootpathArray = $categoryTable->getRootpathArray($categoryArray, $rootCat, $currentCat);
			$rootArray = $categoryTable->getRootArray($rootCat, $categoryArray);
			$isParentArray = $this->getIsParentArray($currentCat, $categoryArray);
			$this->setDepths($rootArray, $categoryArray);
			$depth = 1;
			$catArray = array();
			if ($bUseFilter)	{
				$catArray [(int) $depth] = &$allowedCatArray;
			} else {
				$catArray [(int) $depth] = &$rootArray;
			}
		} else if (!$t['listFrameWork']) {
			$error_code[0] = 'no_subtemplate';
			$error_code[1] = '###'.$templateArea.$templateSuffix.'###';
			$error_code[2] = $this->pibase->templateFile;
			$rc = FALSE;
		} else if (!is_object($categoryTable))	{
			$error_code[0] = 'internal_error';
			$error_code[1] = 'TTP_1';
			$error_code[2] = $functablename;
			$rc = FALSE;
		}
		return $rc;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_catlist_view_base.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_catlist_view_base.php']);
}

?>