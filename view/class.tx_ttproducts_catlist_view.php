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


class tx_ttproducts_catlist_view extends tx_ttproducts_catlist_view_base {

	// returns the products list view
	public function printView($functablename, &$templateCode, $theCode, &$error_code, $templateArea = 'ITEM_CATLIST_TEMPLATE', $pageAsCategory, $templateSuffix = '') {
		global $TSFE, $TCA;

		$t = array();
		parent::printView(
			$functablename,
			$templateCode,
			$t,
			$htmlParts,
			$theCode,
			$error_code,
			$templateArea,
			$pageAsCategory,
			$templateSuffix,
			$currentCat,
			$categoryArray,
			$catArray,
			$isParentArray,
			$subCategoryMarkers
		);

		$content='';
		$out='';
		$bFinished = false;
		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$catTableObj = $tablesObj->get($functablename);

		if (count($error_code)) {
			// nothing
		} else if (count($categoryArray)) {
			$count = 0;
			$depth = 1;
			$countArray = array();
			$countArray[0] = 0;
			$countArray[1] = 0;
			$count = 0;
			$out = $htmlParts[0];
			$parentArray = array();
			$viewCatTagArray = array();
			$catfieldsArray = $markerObj->getMarkerFields(
				$t['linkCategoryFrameWork'],
				$catTableObj->getTableObj()->tableFieldArray,
				$catTableObj->getTableObj()->requiredFieldArray,
				$tmp = array(),
				$catTableObj->marker,
				$viewCatTagArray,
				$parentArray
			);

			$iCount = 0;
			$tSubParts = $this->subpartmarkerObj->getTemplateSubParts($templateCode, $subCategoryMarkers);
			foreach ($tSubParts as $marker => $area)	{
				$this->getFrameWork($t[$marker], $templateCode, $area.$templateSuffix);
			}

			foreach ($catArray[$depth] as $k => $actCategory)	{
				$row = $categoryArray[$actCategory];
				$markerArray = array();
				$iCount++;
				$this->getMarkerArray(
					$functablename,
					$markerArray,
					$linkOutArray,
					$iCount,
					$actCategory,
					$viewCatTagArray,
					$currentCat,
					$pageAsCategory,
					$row
				);
				$childArray = $row['child_category'];

				if (is_array($childArray))	{
					foreach ($subCategoryMarkers as $depth => $subCategoryMarker)	{
						if ($depth == 1)	{
							$icCount = 0;
							$childsOut = '';
							foreach ($childArray as $k => $child)	{
								$childRow = $categoryArray[$child];
								$childMarkerArray = array();
								$icCount++;
								$this->getMarkerArray($functablename,$childMarkerArray, $linkOutArray, $icCount, $child, $viewCatTagArray, $currentCat, $pageAsCategory, $childRow);
								if ($t[$subCategoryMarker]['linkCategoryFrameWork'])	{
									$newOut = $this->pibase->cObj->substituteMarkerArray($t[$subCategoryMarker]['linkCategoryFrameWork'], $childMarkerArray);
									$childOut = $linkOutArray[0].$newOut.$linkOutArray[1];
								}

								$wrappedSubpartArray = array();
								$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
								$subpartArray = array();
								$subpartArray['###CATEGORY_SINGLE###'] = $childOut;
								$childsOut .= $this->pibase->cObj->substituteMarkerArrayCached($t[$subCategoryMarker]['categoryFrameWork'], $childMarkerArray, $subpartArray, $wrappedSubpartArray);
							}

							$subpartArray = array();
							$wrappedSubpartArray = array();
							$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
							$subpartArray['###CATEGORY_SINGLE###'] = $childsOut;
							$childsOut = $this->pibase->cObj->substituteMarkerArrayCached($t[$subCategoryMarker]['listFrameWork'], array(), $subpartArray, $wrappedSubpartArray);
							$markerArray['###'.$subCategoryMarker.'###'] = $childsOut;
						}
					}
				} else {
					foreach ($subCategoryMarkers as $depth => $subCategoryMarker)	{
						$markerArray['###'.$subCategoryMarker.'###'] = '';
					}
				}

				if ($t['linkCategoryFrameWork'])	{
					$categoryOut = $this->pibase->cObj->substituteMarkerArray($t['linkCategoryFrameWork'], $markerArray);
					$out .= $categoryOut;
				}
			}
			$out .= chr(13).$htmlParts[1];

			$markerArray = array();
			$markerArray[$this->htmlPartsMarkers[0]] = '';
			$markerArray[$this->htmlPartsMarkers[1]] = '';
			$out = $this->pibase->cObj->substituteMarkerArrayCached($out, $markerArray);
			$markerArray = array();
			$subpartArray = array();
			$wrappedSubpartArray = array();
			$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
			$subpartArray['###CATEGORY_SINGLE###'] = $out;
			$out = $this->pibase->cObj->substituteMarkerArrayCached($t['listFrameWork'], $markerArray, $subpartArray, $wrappedSubpartArray);
			$content = $out;
		} else {
			$contentEmpty = $this->pibase->cObj->getSubpart($templateCode, $this->subpartmarkerObj->spMarker('###' . $templateArea . $templateSuffix . '_EMPTY###'), $error_code);
		}

		if ($contentEmpty != '') {

			$globalMarkerArray = $markerObj->getGlobalMarkerArray();
			$content = $this->pibase->cObj->substituteMarkerArray($contentEmpty, $globalMarkerArray);
		}

		return $content;
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a category
	 *
	 */
	public function getMarkerArray (
		$functablename,
		&$markerArray,
		&$linkOutArray,
		$iCount,
		$actCategory,
		$viewCatTagArray,
		$currentCat,
		$pageAsCategory,
		$row
	) {
		$css = 'class="w'.$iCount.'"';
		$css = ($actCategory == $currentCat ? 'class="act"' : $css);
		// $pid = $row['pid'];
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$pageObj = $tablesObj->get('pages');
		$categoryTableViewObj = $tablesObj->get($functablename,TRUE);
		$categoryTable = $categoryTableViewObj->getModelObj();
		$pid = $pageObj->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $row);
		$addQueryString = array($categoryTableViewObj->getPivar() => $actCategory);
		$linkUrl = $this->pibase->pi_linkTP_keepPIvars_url($addQueryString,1,1,$pid);
		$linkOutArray = array('<a href="' . htmlspecialchars($linkUrl) . '" ' . $css . '>', '</a>');
		$linkOut = $linkOutArray[0].$row['title'].$linkOutArray[1];
		$categoryTableViewObj->getMarkerArray (
			$markerArray,
			$actCategory,
			$row['pid'],
			$this->config['limitImage'],
			'listcatImage',
			$viewCatTagArray,
			array(),
			$pageAsCategory,
			'LISTCAT',
			$iCount,
			''
		);
		$markerArray['###LIST_LINK###'] = $linkOut;
		$markerArray['###LIST_LINK_URL###'] = htmlspecialchars($linkUrl);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_catlist_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_catlist_view.php']);
}

?>