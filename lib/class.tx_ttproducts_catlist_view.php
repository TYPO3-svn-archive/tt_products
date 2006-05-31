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
 * Part of the tt_products (Shopping System) extension.
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

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_marker.php');

class tx_ttproducts_catlist_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $basket;
	var $page;
	var $tt_content; // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db
	var $pid; // pid where to go
	var $marker; // marker functions

	var $searchFieldList='';

	function init(&$pibase, &$conf, &$config, &$basket, &$pid_list, &$tt_content, &$tt_products_cat, $pid) {
		$this->pibase = &$pibase;
		$this->conf = &$conf;
		$this->config = &$config;
		$this->basket = &$basket;
		$this->tt_content = &$tt_content;
		$this->tt_products_cat = &$tt_products_cat;
		$this->pid = $pid;		
		$this->page = tx_ttproducts_page::createPageTable(
			$this->pibase,
			$this->conf,
			$this->config,
			$this->tt_content,
			$this->pibase->LLkey,
			$this->conf['table.']['pages'], 
			$this->conf['table.']['pages.'],
			$this->conf['conf.']['pages.'],
			$this->page,
			$pid_list,
			99
		);
		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($pibase, $conf, $config, $basket);

			//extend standard search fields with user setup
		$this->searchFieldList = trim($this->conf['stdSearchFieldExt']) ? implode(',', array_unique(t3lib_div::trimExplode(',',$this->searchFieldList.','.trim($this->conf['stdSearchFieldExt']),1))) : 'title,note,'.$this->tt_products->fields['itemnumber'];
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
	function &printView(&$templateCode, &$error_code, $pageAsCategory) {
		global $TSFE, $TCA, $TYPO3_CONF_VARS;
		$content='';
		$out='';
		$where='';
		$bFinished = false;
		$bSeparated = false;
		$categoryArray = array();
		$htmlTagMain = $this->conf['displayCatListType'];
		$htmlTagElement = ($htmlTagMain == 'ul' ? 'li' : 'option');
		$currentCat = $this->tt_products_cat->getParamDefault();

		if ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'])	{
			$categoryArray = $this->page->getRelationArray($this->pibase->cObj->data['pages']);
		} else {
				// read in all categories
			$this->tt_products_cat->get(0, $this->page->pid_list);
			ksort ($this->tt_products_cat->dataArray);
			$categoryArray = $this->tt_products_cat->getRelationArray();
		}
	//	if ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'])	{
//			$pageArray = t3lib_div::trimExplode (',', $this->page->pid_list);  
//			foreach ($pageArray as $k => $uid)	{
//				$row = $this->page->get ($uid);
//				$categoryArray [$uid]['title'] = $row['title'];
//				$pid = $row['pid']; 
//				$categoryArray[$pid]['child'][$uid] = $uid;
//			}
	//	}

		$t['listFrameWork'] = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker('###ITEM_CATLIST_TEMPLATE###'));
		$t['categoryFrameWork'] = $this->pibase->cObj->getSubpart($t['listFrameWork'],'###CATEGORY_SINGLE###');
		$t['linkCategoryFrameWork'] = $this->pibase->cObj->getSubpart($t['categoryFrameWork'],'###LINK_CATEGORY###');

		$subpartArray = array();
		$subpartArray['###LINK_CATEGORY###'] = '###CATEGORY_TMP###';
		$tmp = $this->pibase->cObj->substituteMarkerArrayCached($t['categoryFrameWork'],array(),$subpartArray);
		$htmlParts = t3lib_div::trimExplode ('###CATEGORY_TMP###', $tmp);
		$htmlPartsMarkers = array('###ITEM_SINGLE_PRE_HTML###', '###ITEM_SINGLE_POST_HTML###');
		
		if($pos = strstr($t['listFrameWork'],'###CATEGORY_SINGLE_'))	{
			$bSeparated = true;
		}
		
		$maxDepth = 3;
		$rootArray = $this->getRootArray($categoryArray);
		$parentArray = $this->getParentsArray($currentCat, $categoryArray);
		$this->setDepths($rootArray, $categoryArray);
		$outArray = array();
		$count = 0;
		$depth = 1;
		$catArray = array();
		$catArray [(int) $depth] = &$rootArray;
		$countArray = array();
		$countArray[0] = 0;
		$countArray[1] = 0;
		$count = 0;
		$menu = $this->conf['CSS.'][$this->tt_products_cat->table->name.'.']['menu'];
		$menu = ($menu ? $menu : 'cat'.$depth);
		$fill = '';
		if ($bSeparated)	{
			$fill = ' onchange="fillSelect(this,2,1);"';
		}
		$out = '<'.$htmlTagMain.' id="'.$menu.'"'.$fill.'>';
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
					
					// $t['linkCategoryFrameWork']
					$addQueryString = array('cat' => $actCategory);
					if (is_array($TSFE->fe_user->user))	 {
						$addQueryString = array(); // array('fegroup' => 1);
					}
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
		} else {  // if ($htmlTagMain == 'ul')
			while ($depth > 0 && $count < 50)	{
				$count++;
				if($countArray[$depth] < count ($catArray[$depth]))	{
					$actCategory = $catArray[$depth][$countArray[$depth]];
					$subCategories = $categoryArray[$actCategory]['child_category'];
					$countArray[$depth]++;				
					$css = ($actCategory == $currentCat ? 'class="act"' : $css);
					$preOut = '<'.$htmlTagElement.($css ? ' '.$css : '').' value="'.$actCategory.'">';
					$out .= str_replace($htmlPartsMarkers[0], $preOut, $htmlParts[0]);
					$linkOut = $categoryArray[$actCategory]['title'];
					$out .= str_replace('###LIST_LINK###', $linkOut, $t['linkCategoryFrameWork']);
					$postOut = '</'.$htmlTagElement.'>';
					$out .= str_replace($htmlPartsMarkers[1], $postOut, $htmlParts[1]);				
				}
			}		
		}
		$out .= '</'.$htmlTagMain.'>';

		$markerArray = array();
		$subpartArray = array();
		$wrappedSubpartArray = array();
		$subpartArray['###CATEGORY_SINGLE###'] = $out;
		if ($bSeparated)	{
			$count = intval(substr_count($t['listFrameWork'], '###CATEGORY_SINGLE_') / 2);
			$this->pibase->javascript->set('catselect', $categoryArray);
			
			for ($i = 2; $i <= 1+$count; ++$i)	{
				$menu = 'cat'.$i;
				$bShowSubcategories = ($i < 1+$count ? 1 : 0); 
				$boxNumber = ($i < 1+$count ? ($i+1) : 0);
				$fill = ' onchange="fillSelect(this,'.$boxNumber.','.$bShowSubcategories.');"';
				$tmp = '<'.$htmlTagMain.' id="'.$menu.'"'.$fill.'>';
				$tmp .= '</'.$htmlTagMain.'>';
				$subpartArray['###CATEGORY_SINGLE_'.$i.'###'] = $tmp;
			}
		}
		$out = $this->pibase->cObj->substituteMarkerArrayCached($t['listFrameWork'],$markerArray,$subpartArray,$wrappedSubpartArray);
		$content = $out;

		return $content;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_catlist_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_catlist_view.php']);
}

?>
