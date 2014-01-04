<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * AJAX control over select boxes for categories
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


// require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_catlist_view_base.php');

class tx_ttproducts_selectcat_view extends tx_ttproducts_catlist_view_base {

	var $htmlTagMain = 'select';	// main HTML tag
	var $htmlTagElement = 'option';


	// returns the products list view
	function printView($functablename, &$templateCode, $theCode, &$error_code, $templateArea = 'ITEM_CATEGORY_SELECT_TEMPLATE', $pageAsCategory, $templateSuffix = '') {
		global $TSFE, $TCA;
		$content='';
		$out='';
		$where='';

		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$categoryTableView = $tablesObj->get($functablename,1);
		$categoryTable = $categoryTableView->getModelObj();

		$bSeparated = false;
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

		if (!count($error_code)) 	{
			$count = 0;
			$depth = 1;
			if($pos = strstr($t['listFrameWork'],'###CATEGORY_SINGLE_'))	{
				$bSeparated = true;
			}


			$menu = $this->conf['CSS.'][$functablename.'.']['menu'];
			$menu = ($menu ? $menu : $categoryTableView->piVar.$depth);
			$fill = '';
			if ($bSeparated)	{
				$fill = ' onchange="fillSelect(this,2,1);"';
			}

			$selectArray = array();
			if (is_array($this->conf['form.'][$theCode.'.']) && is_array($this->conf['form.'][$theCode.'.']['dataArray.']))	{
				foreach ($this->conf['form.'][$theCode.'.']['dataArray.'] as $k => $setting)	{
					if (is_array($setting))	{
						$selectArray[$k] = array();
						$type = $setting['type'];
						if ($type)	{
							$parts = t3lib_div::trimExplode('=', $type);
							if ($parts[1] == 'select')	{
								$selectArray[$k]['name'] = $parts[0];
							}
						}
						$label = $setting['label'];
						if ($label)	{
							$selectArray[$k]['label'] = $label;
						}
						$params = $setting['params'];
						if ($params)	{
							$selectArray[$k]['params'] = $params;
						}
					}
				}
			}

			reset ($selectArray);
			$select = current($selectArray);
			if (is_array($select))	{
				if ($select['name'])	{
					$name = 'name="'.$select['name'].'" ';
				}
				if ($select['label'])	{
					$label = $select['label'].' ';
				}
				if ($select['params'])	{
					$params = $select['params'];
				}
			}
			$out = $label.'<'.$this->htmlTagMain.' id="'.$menu.'" '.$name.$fill.$params.'>';
			// empty select entry at the beginning
			$out .= '<option value="0"></option>';
			$out = str_replace($this->htmlPartsMarkers[0], $out, $htmlParts[0]);

			foreach ($catArray[$depth] as $k => $actCategory)	{
				$css = ($actCategory == $currentCat ? 'class="act"' : $css);
				$preOut = '<'.$this->htmlTagElement.($css ? ' '.$css : '').' value="'.$actCategory.'">';
				$out .= str_replace($this->htmlPartsMarkers[0], $preOut, $htmlParts[0]);
				$linkOut = htmlspecialchars($categoryArray[$actCategory]['title']);
				$out .= str_replace('###LIST_LINK###', $linkOut, $t['linkCategoryFrameWork']);
				$postOut = '</'.$this->htmlTagElement.'>';
				$out .= str_replace($this->htmlPartsMarkers[1], $postOut, $htmlParts[1]);
			}

			$out .= '</'.$this->htmlTagMain.'>';
			$markerArray = array();
			$subpartArray = array();
			$wrappedSubpartArray = array();
			$markerArray = $this->urlObj->addURLMarkers($this->conf['PIDlistDisplay'],$markerArray);
			$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
			$subpartArray['###CATEGORY_SINGLE###'] = $out;

			if ($bSeparated)	{
				$count = intval(substr_count($t['listFrameWork'], '###CATEGORY_SINGLE_') / 2);
				if ($pageAsCategory == 2)	{
					// $catid = 'pid';TODO
					$parentFieldArray = array('pid');
				} else {
					// $catid = 'cat';
					$parentFieldArray = array('parent_category');
				}
				$piVar = $categoryTableView->piVar;
				$javaScriptObj = t3lib_div::getUserObj('&tx_ttproducts_javascript');
				$javaScriptObj->set('selectcat', array($categoryArray), 1+$count, $piVar, $parentFieldArray, array($catid), array(), 'clickShow');

				for ($i = 2; $i <= 1+$count; ++$i)	{
					$menu = $piVar.$i;
					$bShowSubcategories = ($i < 1+$count ? 1 : 0);
					$boxNumber = ($i < 1+$count ? ($i+1) : 0);
					$fill = ' onchange="fillSelect(this, '.$boxNumber.','.$bShowSubcategories.');"';
					$tmp = '<'.$this->htmlTagMain.' id="'.$menu.'"'.$fill.'>';
					$tmp .= '<option value="0"></option>';
					$tmp .= '</'.$this->htmlTagMain.'>';
					$subpartArray['###CATEGORY_SINGLE_'.$i.'###'] = $tmp;
				}
				// $subpartArray['###CATEGORY_SINGLE_BUTTON'] = '<input type="button" value="Laden" onclick="fillSelect(0, '.$boxNumber.','.$bShowSubcategories.');">';
			}

			$out = $this->pibase->cObj->substituteMarkerArrayCached($t['listFrameWork'],$markerArray,$subpartArray,$wrappedSubpartArray);
			$content = $out;
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_selectcat_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_selectcat_view.php']);
}

?>