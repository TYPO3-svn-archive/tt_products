<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * product single view functions
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_marker.php');


class tx_ttproducts_single_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $basket;
	var $uid; 	// product id
	var $variants; 	// different attributes
	var $tt_content; // element of class tx_table_db
	var $tt_products; // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db

	var $marker; // marker functions
	var $pid; // PID where to go

 	function init(&$pibase, &$conf, &$config, &$basket, &$page, &$tt_content, &$tt_products, &$tt_products_cat, $uid, $extVars, $pid) {
 		$this->pibase = &$pibase;
 		$this->conf = &$conf;
 		$this->config = &$config;
		$this->basket = &$basket;
 		$this->page = &$page;
 		$this->tt_content = &$tt_content;
 		$this->uid = $uid;
 		$this->variants = $extVars;
 		$this->tt_products = &$tt_products;
 		$this->tt_products_cat = &$tt_products_cat;
 		$this->pid = $pid;

		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($pibase, $conf, $config, $basket);
 	}

	// returns the single view
	function &printView(&$templateCode, &$error_code) {
		global $TSFE, $TCA;
		
		$content = '';

		$where = 'uid='.intval($this->uid);

		$this->tt_products->table->enableFields();
	 	$res = $this->tt_products->table->exec_SELECTquery('*', $where .' AND pid IN ('.$this->page->pid_list.')');
	 	$row = '';
		if ($this->config['displayCurrentRecord'])	{
			$row=$this->pibase->cObj->data;
		} else {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		}

		if ($this->variants) {
			$this->tt_products->variant->getRowFromVariant ($row, $this->variants);
		}

		if($row) {
		 	// $this->uid = intval ($row['uid']); // store the uid for later usage here

				// Get the subpart code
			$itemFrameTemplate ='';
			$giftNumberArray = tx_ttproducts_gifts_div::getGiftNumbers ($this->basket, $row['uid'], $this->variants);

			if ($this->config['displayCurrentRecord'])	{
				$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY_RECORDINSERT###';
			} else if (count($giftNumberArray)) {
				$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY_GIFT###';
			} else if ($row['inStock']==0 && $this->conf['showNotInStock'] && is_array($TCA[$this->tt_products->table->name]['columns']['inStock']) ) {
				$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY_NOT_IN_STOCK###';
			} else {
				$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY###';
			}
			$itemFrameWork = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker($itemFrameTemplate));

			if (count($giftNumberArray)) {
				$personDataFrameWork = $this->pibase->cObj->getSubpart($itemFrameWork,'###PERSON_DATA###');
				// the itemFramework is a smaller part here
				$itemFrameWork = $this->pibase->cObj->getSubpart($itemFrameWork,'###PRODUCT_DATA###');
			}

			// set the title of the single view
			switch ($this->conf['substitutePagetitle']) {
				case 1:
					$TSFE->page['title'] = $row['title'];
					break;
				case 2:
					$TSFE->page['title'] = $row['subtitle'] ? $row['subtitle'] : $row['title'];
					break;
				case 12:
					$TSFE->page['title'] = $row['title'] . ' / ' . $row['subtitle'];
					break;
				case 21:
					$TSFE->page['title'] = $row['subtitle'] . ' / ' . $row['title'];
					break;
			}

			$pageCatTitle = '';
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'] == 1) {
					$pageTmp = $this->page->get($row['pid']);
					$pageCatTitle = $pageTmp['title'].'/';
			}

			$catTmp = '';
			if ($row['category']) {
				$catTmp = $this->tt_products_cat->get($row['category']);
				$catTmp = $catTmp['title'];
			}
			$catTitle = $pageCatTitle.$catTmp;
			$datasheetFile = $row['datasheet'];

				// Fill marker arrays
			$wrappedSubpartArray=array();
			$backPID = $this->pibase->piVars['backPID'];
			$backPID = ($backPID ? $backPID : t3lib_div::_GP('backPID'));
			$pid = ( $backPID ? $backPID : $TSFE->id);
			$wrappedSubpartArray['###LINK_ITEM###']= array('<a href="'. $this->pibase->pi_getPageLink($pid,'',$this->marker->getLinkParams()) .'">','</a>');

			if( $datasheetFile == '' )  {
				$wrappedSubpartArray['###LINK_DATASHEET###']= array('<!--','-->');
			}  else  {
				$wrappedSubpartArray['###LINK_DATASHEET###']= array('<a href="uploads/tx_ttproducts/datasheet/'.$datasheetFile.'">','</a>');
			}

			$item = $this->basket->getItem($row);
			$forminfoArray = array ('###FORM_NAME###' => 'item_'.$this->uid);
			$markerArray = $this->tt_products->getItemMarkerArray ($item,$catTitle,$this->basket->basketExt,$this->tt_content, $this->config['limitImageSingle'],'image', $forminfoArray);
			$subpartArray = array();
			$markerArray['###FORM_NAME###']=$forminfoArray['###FORM_NAME###'];

			//$markerArray['###FORM_URL###']=$this->formUrl.'&tt_products='.$this->uid ;
			$addQueryString=array();
			$addQueryString[$this->pibase->prefixId.'[product]']= $this->uid;
			
			// $markerArray = $this->marker->addURLMarkers($this->pid,$markerArray, array('tt_products' => $this->uid)); // Applied it here also...
			$markerArray = $this->marker->addURLMarkers($this->pid,$markerArray, $addQueryString); // Applied it here also...
			// $url = $this->pibase->pi_getPageLink($TSFE->id,'',$this->marker->getLinkParams()) ; // $this->getLinkUrl('','tt_products');
			$queryPrevPrefix = '';
			$queryNextPrefix = '';
			$prevOrderby = '';
			$nextOrderby = ''; 

			if ($this->conf['orderByItemNumberSg']) {
				$itemnumberField = $this->tt_products->fields['itemnumber'];
				$queryPrevPrefix = $itemnumberField.' < '.intval($row[$itemnumberField]);
				$queryNextPrefix = $itemnumberField.' > '.intval($row[$itemnumberField]);
				$prevOrderby= $itemnumberField.' DESC';
				$nextOrderby= $itemnumberField.' ASC';

			} else {
				$queryPrevPrefix = 'uid < '.intval($this->uid);
				$queryNextPrefix = 'uid > '.intval($this->uid);
				$prevOrderby = 'uid DESC';
				$nextOrderby = 'uid ASC';
			}

			$queryprev = '';
			$wherestock = ($this->conf['showNotinStock'] || !is_array($TCA[$this->tt_products->table->name]['columns']['inStock']) ? '' : 'AND (inStock <>0) ');
			$queryprev = $queryPrevPrefix .' AND pid IN ('.$this->page->pid_list.')'. $wherestock . $this->pibase->cObj->enableFields($this->tt_products->table->name);
			// $resprev = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $queryprev,'', $prevOrderby);
			$resprev = $this->tt_products->table->exec_SELECTquery('*', $queryprev, '', $prevOrderby);

			
			if ($rowprev = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resprev) )	{
				$addQueryString=array();
				$addQueryString[$this->pibase->prefixId.'[product]']= $rowprev['uid'];
				$addQueryString[$this->pibase->prefixId.'[backPID]'] = $backPID;
				// $wrappedSubpartArray['###LINK_PREV_SINGLE###']=array('<a href="'.$url.'&tt_products='.$rowprev['uid'].'">','</a>');
				$wrappedSubpartArray['###LINK_PREV_SINGLE###']= array('<a href="'. $this->pibase->pi_getPageLink($TSFE->id,'',$this->marker->getLinkParams('', $addQueryString)) .'">','</a>');
			} else	{
				$subpartArray['###LINK_PREV_SINGLE###']='';
			}

			$querynext = $queryNextPrefix.' AND pid IN ('.$this->page->pid_list.')'. $wherestock . $this->pibase->cObj->enableFields($this->tt_products->table->name);
			// $resnext = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $querynext, $nextOrderby);
			$resnext = $this->tt_products->table->exec_SELECTquery('*', $querynext, '', $nextOrderby);

			if ($rownext = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resnext) )	{
				$addQueryString=array();
				$addQueryString[$this->pibase->prefixId.'[product]']= $rownext['uid'];
				$addQueryString[$this->pibase->prefixId.'[backPID]'] = $backPID;				
				// $wrappedSubpartArray['###LINK_NEXT_SINGLE###']=array('<a href="'.$url.'&tt_products='.$rownext['uid'].'">','</a>');
				$wrappedSubpartArray['###LINK_NEXT_SINGLE###']= array('<a href="'. $this->pibase->pi_getPageLink($TSFE->id,'',$this->marker->getLinkParams('', $addQueryString)) .'">','</a>');
			} else {
				$subpartArray['###LINK_NEXT_SINGLE###']='';
			}

			$this->tt_products->variant->removeEmptySubpartArray($this->pibase, $this->tt_products, $subpartArray, $row, $this->conf);

				// Substitute	
			$content= $this->pibase->cObj->substituteMarkerArrayCached($itemFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);
			if ($personDataFrameWork) {
				$subpartArray = array();
				$wrappedSubpartArray=array();
				foreach ($giftNumberArray as $k => $giftnumber) {
					$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($this->basket, $markerArray, $giftnumber);
					$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'].'_'.$giftnumber;
					$markerArray['###FORM_ONSUBMIT###']='return checkParams (document.'.$markerArray['###FORM_NAME###'].')';
					//$markerArray['###FORM_URL###'] = $this->pibase->pi_getPageLink(t3lib_div::_GP('backPID'),'',$this->marker->getLinkParams('', array('tt_products' => $row['uid'], 'ttp_extvars' => htmlspecialchars($this->variants))));
					$addQueryString=array();
					$addQueryString[$this->pibase->prefixId.'[product]']= intval($row['uid']);
					$addQueryString[$this->pibase->prefixId.'[variants]']= htmlspecialchars($this->variants);
					$markerArray = $this->marker->addURLMarkers($backPID,$markerArray, $addQueryString); // Applied it here also...
					
					$markerArray['###FIELD_NAME###']='ttp_gift[item]['.$row['uid'].']['.$this->variants.']'; // here again, because this is here in ITEM_LIST view
					$markerArray['###FIELD_QTY###'] = $this->basket->basketExt['gift'][$giftnumber]['item'][$row['uid']][$this->variants];
					$content.=$this->pibase->cObj->substituteMarkerArrayCached($personDataFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);
				}
			}
			$this->pibase->javascript->set('email');  // other JavaScript checks can come here
		} else {
			$error_code[0] = 'wrong_parameter';
			$error_code[1] = intval($this->uid);
		}
	return $content;
	} // print
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_single_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_single_view.php']);
}


?>
