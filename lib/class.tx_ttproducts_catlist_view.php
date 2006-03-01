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
		
		$this->page = tx_ttproducts_page::createPageTable($this->pibase,$this->page,$pid_list,99);
		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($pibase, $conf, $config, $basket);

			//extend standard search fields with user setup
		$this->searchFieldList = trim($this->conf['stdSearchFieldExt']) ? implode(',', array_unique(t3lib_div::trimExplode(',',$this->searchFieldList.','.trim($this->conf['stdSearchFieldExt']),1))) : 'title,note,'.$this->tt_products->fields['itemnumber'];
	}

	// sets the 'depth' field
	function setDepths (&$categoryRootArray, &$categoryArray)	{
		$depth = 1;
		
		$endArray = array();
		foreach ($categoryArray as $category => $row)	{
				// is it a leaf in a tree ?
			if (!is_array($this->tt_products_cat->dataArray[$category]['child_category']))	{
				$endArray [] = $category;				
			}
		}
		
		foreach ($endArray as $k => $category)	{
			$count = 0;
			$actCategory = $category;
			// determine the highest parent
			while ($actCategory && !$categoryArray[$actCategory]['depth'] && $count < 100) {
				$count++;
				$lastCategory = $actCategory; 
				$actCategory = $categoryArray[$actCategory]['parent_category'];
			}
			
			$depth = $count + $categoryArray[$lastCategory]['depth'];
			// now write the calculated count into the fields
			$actCategory = $category;
			while ($actCategory && !$categoryArray[$actCategory]['depth']) {
				$categoryArray[$actCategory]['depth'] = $depth--;
				$actCategory = $categoryArray[$actCategory]['parent_category'];
			}
			
		}
	}

	// returns the products list view
	function &printView(&$templateCode, &$error_code) {
		global $TSFE, $TCA;
		$content='';
		$out='';
		$where='';
		$bFinished = false;
		
		$t['listFrameWork'] = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker('###ITEM_CATLIST_TEMPLATE###'));
		$t['categoryFrameWork'] = $this->pibase->cObj->getSubpart($t['listFrameWork'],'###CATEGORY_SINGLE###');

			// read in all categories
		$this->tt_products_cat->get(0, $this->page->pid_list);
		ksort ($this->tt_products_cat->dataArray);
		
		$maxDepth = 3;
		$rootArray = array(); 
		$this->tt_products_cat->prepareCategories($rootArray);
		$this->setDepths($rootArray, $this->tt_products_cat->dataArray);
		
		$outArray = array();
		$count = 0;
		$depth = 1;
		$catArray = array();
		$catArray [$depth] = &$rootArray;  
		$countArray = array();
		$countArray[0] = 0;
		$countArray[1] = 0;
		$count = 0;
		$menu = $this->conf['CSS.'][$this->tt_products_cat->table->name.'.']['menu'];
		$out = '<ul id="'.$menu.'">';
		$currentCat = $this->pibase->piVars['cat'];
		
		while ($depth > 0 && $count<50)	{
			$count++;
			if($countArray[$depth] < count ($catArray[$depth]))	{
				$actCategory = $catArray[$depth][$countArray[$depth]];
				$countArray[$depth]++;
				$pid = $this->page->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $this->tt_products_cat->dataArray[$actCategory]);
				$css = ($actCategory == $currentCat ? 'class="act"' : '');
				$markerArray['###ITEM_SINGLE_PRE_HTML###'] = '<li'.($css ? ' '.$css : '').'>';
				$wrappedSubpartArray['###LINK_CATEGORY###'] = array('<a href="'. $this->pibase->pi_getPageLink($pid,'',$this->marker->getLinkParams('', array($this->pibase->prefixId.'[cat]' => $actCategory))).'"'.$css.'>','</a>');
				$markerArray['###LIST_LINK###'] = $this->tt_products_cat->dataArray[$actCategory]['title'];  
				$markerArray['###ITEM_SINGLE_POST_HTML###'] = '</li>';
				$subpartArray = array();
				$out.= $this->pibase->cObj->substituteMarkerArrayCached($t['categoryFrameWork'],$markerArray,$subpartArray,$wrappedSubpartArray);
				
				$subCategories = $this->tt_products_cat->dataArray[$actCategory]['child_category']; 
				if (is_array($subCategories))	{
					$depth++;
					$out .= '<ul class="w'.$count.'">';
					$countArray[$depth] = 0;
					$catArray[$depth] = $subCategories; 
				}
			} else {
				$out .= '</ul>';
				$depth--;
			}
		}
		$out .= '</ul>';
		
		$content = $out;

		return $content;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_catlist_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_catlist_view.php']);
}

?>
