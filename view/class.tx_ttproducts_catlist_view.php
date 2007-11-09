<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2006 Franz Holzinger <kontakt@fholzinger.com>
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
 * Part of the tt_products (Shop System) extension.
 *
 * category list view functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

global $TYPO3_CONF_VARS;


require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_marker.php');

class tx_ttproducts_catlist_view {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $basket;
	var $page;
	var $tt_content; // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db
	var $pid; // pid where to go
	var $marker; // marker functions


	function init(&$pibase, &$cnf, &$basket, &$pid_list, &$tt_content, &$tt_products_cat, $pid) {
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->basket = &$basket;
		$this->tt_content = &$tt_content;
		$this->tt_products_cat = &$tt_products_cat;
		$this->pid = $pid;		
		$this->page = tx_ttproducts_page::createPageTable(
			$this->pibase,
			$this->cnf,
			$this->tt_content,
			$this->pibase->LLkey,
			$this->conf['table.']['pages'], 
			$this->conf['conf.']['pages.'],
			$this->page,
			$pid_list,
			99
		);
		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($pibase, $cnf, $basket);
	}


	function &getRootArray(&$categoryArray)	{
		$rootArray = array();
		
		foreach ($categoryArray as $uid => $row)	{
			if (!$categoryArray[$uid]['parent_category'])	{
				$rootArray[] = $uid;
			}
		}
		
		return $rootArray;
	}


	function &getParentsArray($cat, &$categoryArray)	{
		$parentsArray = array();
		
		if ($cat)	{
			$uid = $cat;
			$parentsArray[$uid] = true;
			// get all forfathers
			while ($uid = $categoryArray[$uid]['parent_category'])	{
				$parentsArray[$uid] = true;
			}
		}

		return $parentsArray;
	}


	// sets the 'depth' field
	function setDepths (&$categoryRootArray, &$categoryArray)	{
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
			while ($actCategory && !$categoryArray[$actCategory]['depth']) {
				$categoryArray[$actCategory]['depth'] = $depth--;
				$actCategory = (int) $categoryArray[$actCategory]['parent_category'];
			}
			
		}
	}


	// returns the products list view
	function &printView(&$templateCode, &$error_code, $pageAsCategory, $htmlTagMain, $templateSuffix = '') {
		global $TSFE, $TCA, $TYPO3_CONF_VARS;
		$content='';
		$out='';
		$where='';
		$bFinished = false;
		$bSeparated = false;
		$categoryArray = array();
		$categoryTable = '';
		$htmlTagElement = ($htmlTagMain == 'ul' ? 'li' : $htmlTagMain == 'select' ? 'option' : 'null');

		if ($pageAsCategory)	{
			$excludeCat = $this->pibase->cObj->data['pid'];
			$rootCat = $this->cnf->config['storeRootPid'];
			$categoryTable = &$this->page;
		} else {
				// read in all categories
			$categoryTable = &$this->tt_products_cat;
			$categoryTable->get(0, $this->page->pid_list);	// read all categories
			ksort ($categoryTable->dataArray);
			$excludeCat = 0;
		}
		
		$currentCat = $categoryTable->getParamDefault();
		$rootpathArray = $categoryTable->getRootpathArray($rootCat, $currentCat);
		$categoryArray = $categoryTable->getRelationArray($excludeCat,$currentCat);

		$t['listFrameWork'] = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker('###ITEM_CATLIST_TEMPLATE'.$templateSuffix.'###'));
		$t['categoryFrameWork'] = $this->pibase->cObj->getSubpart($t['listFrameWork'],'###CATEGORY_SINGLE###');
		$t['linkCategoryFrameWork'] = $this->pibase->cObj->getSubpart($t['categoryFrameWork'],'###LINK_CATEGORY###');
		$subpartArray = array();
		$subpartArray['###LINK_CATEGORY###'] = '###CATEGORY_TMP###';
		$tmp = $this->pibase->cObj->substituteMarkerArrayCached($t['categoryFrameWork'],array(),$subpartArray);
		$htmlParts = t3lib_div::trimExplode ('###CATEGORY_TMP###', $tmp);
		$htmlPartsMarkers = array('###ITEM_SINGLE_PRE_HTML###', '###ITEM_SINGLE_POST_HTML###');
		
//		if($pos = strstr($t['listFrameWork'],'###CATEGORY_SINGLE_'))	{
//			$bSeparated = true;
//		}
		
		$maxDepth = 3;
		$rootArray = $this->getRootArray($categoryArray);
		$parentArray = $this->getParentsArray($currentCat, $categoryArray);
		$this->setDepths($rootArray, $categoryArray);

		$count = 0;
		$depth = 1;
		$catArray = array();
		$catArray [(int) $depth] = &$rootArray;
		$countArray = array();
		$countArray[0] = 0;
		$countArray[1] = 0;
		$count = 0;
		$menu = $this->conf['CSS.'][$categoryTable->table->name.'.']['menu'];
		$menu = ($menu ? $menu : $categoryTable->piVar.$depth);
		$fill = '';
//		if ($bSeparated)	{
//			$fill = ' onchange="fillSelect(this,2,1);"';
//		}
		if ($htmlTagMain != 'null')	{
			$out = '<'.$htmlTagMain.' id="'.$menu.'"'.$fill.'>';
		}
		if ($htmlTagMain == 'select')	{
			// empty select entry at the beginning
			$out .= '<option value="0"></option>';
		}
		$out = str_replace($htmlPartsMarkers[0], $out, $htmlParts[0]);
		if ($htmlTagMain == 'ul')	{
			while ($depth > 0 && $count < 300)	{
				$count++;
				$css = 'class="w'.$count.'"';
				if($countArray[$depth] < count ($catArray[$depth]))	{
					$markerArray = array();
					$actCategory = $catArray[$depth][$countArray[$depth]];
					$subCategories = $categoryArray[$actCategory]['child_category'];
					$countArray[$depth]++;				
					$css = ($actCategory == $currentCat ? 'class="act"' : $css);
					$preOut = '<'.$htmlTagElement.($css ? ' '.$css : '').' value="'.$actCategory.'">';
					$out .= str_replace($htmlPartsMarkers[0], $preOut, $htmlParts[0]);
	
					if ($pageAsCategory > 0)	{
						$pid = $categoryArray[$actCategory]['pid'];
					} else {
						$pid = $this->page->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $categoryArray[$actCategory]);
					}
					
					$addQueryString = array($categoryTable->piVar => $actCategory);
//					if (is_array($TSFE->fe_user->user))	 {
//						$addQueryString[] = array('fegroup' => 1);
//					}
					$tempUrl = $this->pibase->pi_linkTP_keepPIvars_url($addQueryString,1,1,$pid);
					$linkOut = '<a href="'.$tempUrl.'" '.$css.'>'.$categoryArray[$actCategory]['title'].'</a>';
					$out .= str_replace('###LIST_LINK###', $linkOut, $t['linkCategoryFrameWork']);
					if (is_array($subCategories)	&&
						(!$this->conf['clickIntoSubmenu'] || $parentArray[$actCategory] == true))	{
						$depth++;
						$preOut = '<'.$htmlTagMain.' '.$css.'" >';
						$countArray[(int) $depth] = 0;
						$catArray[(int) $depth] = $subCategories;
						$out .= str_replace($htmlPartsMarkers[0], $preOut, $htmlParts[0]);
					}
				} else {
					$depth--;
					$prevCategory = $catArray[$depth][$countArray[$depth]-1];
					$subCategories = $categoryArray[$prevCategory]['child_category'];
	
					$postOut = '</'.$htmlTagElement.'>';
					if (is_array($subCategories))	{
						$postOut .= '</'.$htmlTagMain.'>';
					}
					$out .= str_replace($htmlPartsMarkers[1], $postOut, $htmlParts[1]);
				}
			}
		} else if ($htmlTagMain == 'select') {  // if ($htmlTagMain == 'ul')
			// nothing: use the SELECTCAT code
		} else {
			// ($htmlTagMain == 'null')  -- where no special HTML tags will be created

			$viewCatTagArray = array();
			$catfieldsArray = $this->marker->getMarkerFields(
				$t['linkCategoryFrameWork'],
				$viewCatTable->table->tableFieldArray,
				$viewCatTable->table->requiredFieldArray,
				$tmp = array(),
				$viewCatTable->marker,
				$viewCatTagArray,
				$parentArray
			);

			$iCount = 0;
			foreach ($catArray[$depth] as $k => $actCategory)	{
				$iCount++;
				$css = 'class="w'.$iCount.'"';
				$css = ($actCategory == $currentCat ? 'class="act"' : $css);
				$row = $categoryArray[$actCategory];
				$pid = $row['pid'];
				$pid = $this->page->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $row);
				$addQueryString = array($categoryTable->piVar => $actCategory);
				$tempUrl = $this->pibase->pi_linkTP_keepPIvars_url($addQueryString,1,1,$pid);
				$linkOutArray = array('<a href="'. $tempUrl .'"'.$css.'>','</a>');
				$linkOut = $linkOutArray[0].$row['title'].$linkOutArray[1];
				$markerArray = array();
				$categoryTable->getMarkerArray (
					$markerArray, 
					$this->page,
					$actCategory, 
					$row['pid'],
					$this->cnf->config['limitImage'], 
					'listImage', 
					$viewCatTagArray, 
					array(), 
					$pageAsCategory,
					'LISTCAT',
					$iCount,
					''
				);
				$catTitle = $categoryTable->getMarkerArrayCatTitle($markerArray);
				$markerArray['###LIST_LINK###'] = $linkOut;
				
				if ($t['linkCategoryFrameWork'])	{
					$categoryOut = $this->pibase->cObj->substituteMarkerArray($t['linkCategoryFrameWork'], $markerArray);
					$out .= $linkOutArray[0].$categoryOut.$linkOutArray[1];
				}

			}
		}
		if ($htmlTagMain != 'null')	{
			$out .= '</'.$htmlTagMain.'>';
		}

		$markerArray = array();
		$subpartArray = array();
		$wrappedSubpartArray = array();
		$this->marker->getWrappedSubpartArray($wrappedSubpartArray);
		$subpartArray['###CATEGORY_SINGLE###'] = $out;
	
//		if ($bSeparated)	{
//			$count = intval(substr_count($t['listFrameWork'], '###CATEGORY_SINGLE_') / 2);
//		//	$this->pibase->javascript->set('selectcat', $categoryArray, 1+$count);
//			
//			for ($i = 2; $i <= 1+$count; ++$i)	{
//				$menu = $categoryTable->piVar.$i;
//				$bShowSubcategories = ($i < 1+$count ? 1 : 0); 
//				$boxNumber = ($i < 1+$count ? ($i+1) : 0);
//				$fill = ' onchange="fillSelect(this,'.$boxNumber.','.$bShowSubcategories.');"';
//				$tmp = '<'.$htmlTagMain.' id="'.$menu.'"'.$fill.'>';
//				$tmp .= '</'.$htmlTagMain.'>';
//				$subpartArray['###CATEGORY_SINGLE_'.$i.'###'] = $tmp;
//			}
//		}
		$out = $this->pibase->cObj->substituteMarkerArrayCached($t['listFrameWork'],$markerArray,$subpartArray,$wrappedSubpartArray);
		$content = $out;

		return $content;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_catlist_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_catlist_view.php']);
}

?>
