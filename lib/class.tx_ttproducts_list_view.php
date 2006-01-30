<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * product list view functions
 *
 * $Id$
 *
 * @author	Kasper Sk&aring;rh&oslash;j <kasperYYYY@typo3.com>
 * @author	Ren&eacute; Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_marker.php');

class tx_ttproducts_list_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $basket;
	var $page;
	var $tt_content; // element of class tx_table_db
	var $tt_products; // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db
	var $pid; // pid where to go
	var $marker; // marker functions

	var $searchFieldList='';

	function init(&$pibase, &$conf, &$config, &$basket, &$page, &$tt_content, &$tt_products, &$tt_products_cat, $pid) {
		$this->pibase = &$pibase;
		$this->conf = &$conf;
		$this->config = &$config;
		$this->basket = &$basket;
		$this->page = &$page;
		$this->tt_content = &$tt_content;
		$this->tt_products = &$tt_products;
		$this->tt_products_cat = &$tt_products_cat;
		$this->pid = $pid;	

		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($pibase, $conf, $config, $basket);

			//extend standard search fields with user setup
		$this->searchFieldList = trim($this->conf['stdSearchFieldExt']) ? implode(',', array_unique(t3lib_div::trimExplode(',',$this->searchFieldList.','.trim($this->conf['stdSearchFieldExt']),1))) : 'title,note,'.$this->tt_products->fields['itemnumber'];
	}

  
	function categorycomp($row1, $row2)  {
		return strcmp($this->tt_products_cat->get[$row1['category']],$this->tt_products_cat->get[$row2['category']]);
	} // comp

	// returns the products list view
	function &printView(&$templateCode, $theCode, &$memoItems, &$error_code) {
		global $TSFE, $TCA;
		$content='';
		$out='';
		$more=0;
		$where='';
		$error_code = array();

		// if parameter 'newitemdays' is specified, only new items from the last X days are displayed
		if (t3lib_div::_GP('newitemdays')) {
			$temptime = time() - 86400*intval(trim(t3lib_div::_GP('newitemdays')));
			$where = ' AND tstamp >= '.$temptime;
		}			
		if(isset($this->pibase->piVars['cat'])){
			$where .= ' AND category IN ('.implode(",",t3lib_div::intExplode(',', $this->pibase->piVars['cat'])).')';
		} 

		switch ($theCode) {
			case 'SEARCH':
					// Get search subpart
				$t['search'] = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker('###ITEM_SEARCH###'));
					// Substitute a few markers
				$out=$t['search'];
				$swords = t3lib_div::_GP('swords');
				$pid = ( $this->conf['PIDsearch'] ? $this->conf['PIDsearch'] : $this->pid);
				$markerArray = $this->marker->addURLMarkers($pid,array());
				$out = $this->pibase->cObj->substituteMarkerArrayCached($out,$markerArray);
				
				$htmlSwords = htmlspecialchars($swords);
				$out=$this->pibase->cObj->substituteMarker($out, '###SWORDS###', $htmlSwords);
					// Add to content
				$content.=$out;
				// $entitySwords = htmlentities($swords); if the data has been entered e.g. with '&uuml;' instead of '&uuml;' 
				if ($htmlSwords)	{
					$where .= tx_ttproducts_div::searchWhere($this->pibase, $this->searchFieldList, trim($htmlSwords));
				}
			break;
			case 'LISTGIFTS':
				$where .= ' AND '.($this->conf['whereGift'] ? $this->conf['whereGift'] : '1=0');
			break;
			case 'LISTOFFERS':
				$where .= ' AND offer';
			break;
			case 'LISTHIGHLIGHTS':
				$where .= ' AND highlight';
			break;
			case 'LISTNEWITEMS':
				$temptime = time() - 86400*intval(trim($this->conf['newItemDays']));
				$where .= 'AND tstamp >= '.$temptime;
			break;
			case 'MEMO':
				$where = ' AND '.($memoItems != '' ? 'uid IN ('.$memoItems.')' : '1=0' );
			break;
			default:
				// nothing here
			break;
		}
			

		$begin_at=t3lib_div::intInRange(t3lib_div::_GP('begin_at'),0,100000);
		if ($where || ($theCode!='SEARCH' && !t3lib_div::_GP('swords')))	{
			$this->page->initRecursive($this->config['recursive']);
			//tx_ttproducts_page_div::generatePageArray();

				// Get products
			$selectConf = Array();
			$selectConf['pidInList'] = $this->page->pid_list;
	
			$wherestock = ($this->conf['showNotinStock'] || !is_array(($TCA[$this->tt_products->table->name]['columns']['inStock'])) ? '' : 'AND (inStock <> 0) ');
			$selectConf['where'] = '1=1 '.$wherestock.$where;
	
				// performing query to count all products (we need to know it for browsing):
			$selectConf['selectFields'] = 'count(*)';
			$queryParts = $this->pibase->cObj->getQuery($this->tt_products->table->name, $selectConf, TRUE);
			$res = $this->tt_products->table->exec_SELECT_queryArray($queryParts);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$productsCount = $row[0];
	
				// range check to current productsCount
			$begin_at = t3lib_div::intInRange(($begin_at >= $productsCount)?($productsCount > $this->config['limit'] ? $productsCount-$this->config['limit'] : $productsCount):$begin_at,0);
	
				// performing query for display:
			$selectConf['orderBy'] = ($this->conf['orderBy'] ? $this->conf['orderBy'] : 'pid,category,title');
			$selectConf['selectFields'] = '*';
			$selectConf['max'] = ($this->config['limit']+1);
			$selectConf['begin'] = $begin_at;
			
			$queryParts = $this->pibase->cObj->getQuery($this->tt_products->table->name, $selectConf, TRUE);

			$res = $this->tt_products->table->exec_SELECT_queryArray($queryParts);
			//$res = $this->pibase->cObj->exec_getQuery('tt_products',$selectConf);
	
			$orderByArray = split (',', $selectConf['orderBy']);
			$firstOrderField = $orderByArray[0];
			$productsArray=array();
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
				$productsArray[$row[$firstOrderField]][]=$row;
			}
			
			// Getting various subparts we're going to use here:
			$area = '';
			if ($memoItems != '') {
				$area = '###MEMO_TEMPLATE###';
			} else if ($theCode=='LISTGIFTS') {
				$area = '###ITEM_LIST_GIFTS_TEMPLATE###';
			} else {
				$area = '###ITEM_LIST_TEMPLATE###';
			}
			
			$t['listFrameWork'] = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker($area));
	
			if (!$t['listFrameWork']) {
				$error_code[0] = 'no_subtemplate';
				$error_code[1] = $area;
				$error_code[2] = $this->conf['templateFile'];
			}
 
			$t['categoryTitle'] = $this->pibase->cObj->getSubpart($t['listFrameWork'],'###ITEM_CATEGORY###');
			$t['itemFrameWork'] = $this->pibase->cObj->getSubpart($t['listFrameWork'],'###ITEM_LIST###');
			$t['item'] = $this->pibase->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');
	
//			$markerArray=array();
//			$markerArray['###FORM_URL###']=$this->formUrl; // Applied later as well.
			$markerArray = $this->marker->addURLMarkers($this->pid,array());
	
			if ($theCode=='LISTGIFTS') {
				$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($this->basket, $markerArray, $this->giftnumber);
				$markerArray['###FORM_NAME###']= 'GiftForm';
				$markerArray['###FORM_ONSUBMIT###']='return checkParams (document.GiftForm)';
	
				$markerFramework = 'listFrameWork'; 
				$t['listFrameWork'] = $this->pibase->cObj->substituteMarkerArrayCached($t['listFrameWork'],$markerArray,array(),array());
			} else {
				$markerArray['###FORM_NAME###']= 'ShopForm';
				$markerArray['###FORM_ONSUBMIT###']='return checkParams (document.ShopForm)';
			}
	
			tx_ttproducts_div::setJS($this->pibase,'email');

			$t['itemFrameWork'] = $this->pibase->cObj->substituteMarkerArrayCached($t['itemFrameWork'],$markerArray,array(),array());
		
			$currentP='';
			$out='';
			$iCount=0;
			$more=0;		// If set during this loop, the next-item is drawn

			foreach ($productsArray as $k1 => $productList) {
				if (($this->conf['orderByCategoryTitle'] >= 1) && ($firstOrderField != 'category')) { // category means it should be sorted by the category title in this case
					uasort ($productList, array(&$this, '$this->categorycomp'));
				}

				$itemsOut='';
				$iColCount=1;
				$tableRowOpen=0;
				foreach ($productList as $k2 => $row)	{
					$iCount++;
					if ($iCount>$this->config['limit'])	{
						$more=1;
						break;
					}

					// max. number of columns reached?
					if ($iColCount > $this->conf['displayBasketColumns'] || !$this->conf['displayBasketColumns']) {
						$iColCount = 1; // restart in the first column
					}

						// Print Category Title
					if ($row['pid'].'_'.$row['category']!=$currentP)	{
						if ($itemsOut)	{
							$out.=$this->pibase->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
						}
						$itemsOut='';			// Clear the item-code var

						$currentP = $row['pid'].'_'.$row['category'];
						if ($where || $this->conf['displayListCatHeader'])	{
							$markerArray=array();
							$pageCatTitle = '';
							if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'] == 1) {
								$pageTmp = $this->page->get($row['pid']);
								$pageCatTitle = $pageTmp['title'].'/';
							}
							$tmpCategory = ($row['category'] ? $this->tt_products_cat->get($row['category']) : array ('title' => ''));
							$catTitle = $pageCatTitle.($tmpCategory['title']);

							// mkl: $catTitle= $this->categories[$row['category']]["title'];
							$this->pibase->cObj->setCurrentVal($catTitle);
							$markerArray['###CATEGORY_TITLE###']=$this->pibase->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
							$out.= $this->pibase->cObj->substituteMarkerArray($t['categoryTitle'], $markerArray);
						}
					}

					$datasheetFile = $row['datasheet'] ;
					$css_current = $this->conf['CSSListDefault'];
					if ($row['uid']==$this->tt_product_single) {
						$css_current = $this->conf['CSSListCurrent'];
					}
					$css_current = ($css_current ? '" id="'.$css_current.'"' : '');

						// Print Item Title
					$wrappedSubpartArray=array();

					$addQueryString=array();
					$addQueryString['tt_products']= intval($row['uid']);
					$pid = $this->page->getPID($this->conf['PIDitemDisplay'], $this->conf['PIDitemDisplay.'], $row);
					$wrappedSubpartArray['###LINK_ITEM###']=  array('<a href="'. $this->pibase->pi_getPageLink($pid,'',$this->marker->getLinkParams('', $addQueryString)).'"'.$css_current.'>','</a>');

					if( $datasheetFile == '' )  {
						$wrappedSubpartArray['###LINK_DATASHEET###']= array('<!--','-->');
					}  else  {
						$wrappedSubpartArray['###LINK_DATASHEET###']= array('<a href="uploads/tx_ttproducts/datasheet/'.$datasheetFile.'">','</a>');
					}

					$item = $this->basket->getItem($row);
					$markerArray = $this->marker->getItemMarkerArray ($item, $catTitle, $this->tt_products, $this->tt_content, $this->config['limitImage'],'listImage');
					if ($theCode=='LISTGIFTS') {
						$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($this->basket, $markerArray, $this->basket->giftnumber);
					}
						
					$subpartArray = array();

					// $markerArray['###FORM_URL###']=$this->formUrl; // Applied later as well.
					$markerArray = $this->marker->addURLMarkers($this->pid,$markerArray);
					
					$markerArray['###FORM_NAME###']='item_'.$iCount;

					// alternating css-class eg. for different background-colors
					$even_uneven = (($iCount & 1) == 0 ? $this->conf['CSSRowEven'] : $this->conf['CSSRowUnEven']);

					$temp='';
					if ($iColCount == 1) {
						if ($even_uneven) {
							$temp = '<TR class="'.$even_uneven.'">';
						} else {
							$temp = '<TR>';
						}
						$tableRowOpen=1;
					}
					$markerArray['###ITEM_SINGLE_PRE_HTML###'] = $temp;
					$temp='';
					if ($iColCount == $this->conf['displayBasketColumns']) {
						$temp = '</TR>';
						$tableRowOpen=0;
					}
					$markerArray['###ITEM_SINGLE_POST_HTML###'] = $temp;

					$pid = ( $this->conf['PIDmemo'] ? $this->conf['PIDmemo'] : $TSFE->id);
					$markerArray['###FORM_MEMO###'] = $this->pibase->pi_getPageLink($pid,'',$this->marker->getLinkParams()); //$this->getLinkUrl($this->conf['PIDmemo']);
					
					// cuts note in list view
					if (strlen($markerArray['###PRODUCT_NOTE###']) > $this->conf['max_note_length']) {
						$markerArray['###PRODUCT_NOTE###'] = substr(strip_tags($markerArray['###PRODUCT_NOTE###']), 0, $this->conf['max_note_length']) . '...';
					}

					tx_ttproducts_article_div::removeEmptySubpartArray($this->pibase, $this->tt_products, $subpartArray, $row, $this->conf);

					$tempContent = $this->pibase->cObj->substituteMarkerArrayCached($t['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
					$itemsOut .= $tempContent;
					$iColCount++;
				}

				// multiple columns display and ITEM_SINGLE_POST_HTML is in the item's template?
				if (($this->conf['displayBasketColumns'] > 1) && strstr($t['item'], 'ITEM_SINGLE_POST_HTML')) { // complete the last table row
					while ($iColCount <= $this->conf['displayBasketColumns']) {
						$iColCount++;
						$itemsOut.= '<TD></TD>';
					}
					$itemsOut.= ($tableRowOpen ? '</TR>' : '');
				}

				if ($itemsOut)	{
					$out.=$this->pibase->cObj->substituteMarkerArrayCached($t['itemFrameWork'], $subpartArray, array('###ITEM_SINGLE###'=>$itemsOut));
				}
			}
			if (count($productsArray) == 0) {
				$content = '';  // keine Produkte gefunden
			}

		}
		
		if ($out)	{
			// next / prev:
			// $url = $this->getLinkUrl('','begin_at');
				// Reset:
			$subpartArray=array();
			$wrappedSubpartArray=array();
			$markerArray=array();
			$splitMark = md5(microtime());

			if ($more)	{
				$next = ($begin_at+$this->config['limit'] > $productsCount) ? $productsCount-$this->config['limit'] : $begin_at+$this->config['limit'];
				$splitMark = md5(microtime());
				$tempUrl = $this->pibase->pi_linkToPage($splitMark,$TSFE->id,'',$this->marker->getLinkParams('', array('begin_at' => $next)));

				$wrappedSubpartArray['###LINK_NEXT###']=  explode ($splitMark, $tempUrl);  // array('<a href="'.$url.'&begin_at='.$next.'">','</a>');
			} else {
				$subpartArray['###LINK_NEXT###']='';
			}
			if ($begin_at)	{
				$prev = ($begin_at-$this->config['limit'] < 0) ? 0 : $begin_at-$this->config['limit'];
				$tempUrl = $this->pibase->pi_linkToPage($splitMark,$TSFE->id,'',$this->marker->getLinkParams('', array('begin_at' => $prev)));
				$wrappedSubpartArray['###LINK_PREV###']=explode ($splitMark, $tempUrl); // array('<a href="'.$url.'&begin_at='.$prev.'">','</a>');
			} else {
				$subpartArray['###LINK_PREV###']='';
			}
			$markerArray['###BROWSE_LINKS###']='';
			if ($productsCount > $this->config['limit'] )	{ // there is more than one page, so let's browse
				$wrappedSubpartArray['###LINK_BROWSE###']=array('',''); // <- this could be done better I think, or not?
				for ($i = 0 ; $i < ($productsCount/$this->config['limit']); $i++)	 {
					if (($begin_at >= $i*$this->config['limit']) && ($begin_at < $i*$this->config['limit']+$this->config['limit']))	{
						$markerArray['###BROWSE_LINKS###'].= ' <b>'.(string)($i+1).'</b> ';
						//	you may use this if you want to link to the current page also
						//
					} else {
						$tempUrl = $this->pibase->pi_linkToPage((string)($i+1),$TSFE->id,'',$this->marker->getLinkParams('', array('begin_at' => (string)($i * $this->config['limit']))));
						$markerArray['###BROWSE_LINKS###'].= $tempUrl; // ' <a href="'.$url.'&begin_at='.(string)($i * $this->config['limit']).'">'.(string)($i+1).'</a> ';
					}
				}
			} else {
				$subpartArray['###LINK_BROWSE###']='';
			}

			$subpartArray['###ITEM_CATEGORY_AND_ITEMS###']=$out;
			// $markerArray['###FORM_URL###']=$this->formUrl;	  // Applied it here also...
			$markerArray = $this->marker->addURLMarkers($this->pid,$markerArray); //Applied it here also...
/* Added els6: needed to display creditpoints in the credits
category on the list page */
			$markerArray['###AMOUNT_CREDITPOINTS###'] = number_format($TSFE->fe_user->user['tt_products_creditpoints'],0);

			$markerArray['###ITEMS_SELECT_COUNT###']=$productsCount;

			$content.= $this->pibase->cObj->substituteMarkerArrayCached($t['listFrameWork'],$markerArray,$subpartArray,$wrappedSubpartArray);
		} elseif ($where)	{
			$content.=$this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker('###ITEM_SEARCH_EMPTY###'));
		}

		return $content;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_list_view.php'])
	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_list_view.php']);
}

?>
