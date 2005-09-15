<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Part of the tt_products (Shopping System) extension.
 *
 * Creates a list of products for the shopping basket in TYPO3.
 * Also controls basket, searching and payment.
 *
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Milosz Klosowicz <typo3@miklobit.com>
 * @author	Bert Hiddink <hiddink@bendoo.com>
 * @author	Els Verberne <verberne@bendoo.nl>
 * @package TYPO3
 * @subpackage tt_products
 * @see static_template "plugin.tt_products"
 * @see TSref.pdf
 *
 *  
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

require_once(PATH_t3lib.'class.t3lib_parsehtml.php');
require_once('class.tx_ttproducts_htmlmail.php');

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_article_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_basket_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricecalc_div.php');

class tx_ttproducts extends tslib_pibase {
	var $prefixId = 'tx_ttproducts';	// Same as class name
	var $scriptRelPath = 'pi/class.tx_ttproducts.php';	// Path to this script relative to the extension dir.
	var $extKey = TT_PRODUCTS_EXTkey;	// The extension key.

	var $cObj;		// The backReference to the mother cObj object set at call time

	var $searchFieldList='title,note,itemnumber';

		// Internal
	var $pid_list='';
	var $basketExt=array();				// "Basket Extension" - holds extended attributes

	var $uid_list='';					// List of existing uid's from the basket, set by initBasket()
	var $categories=array();			// Is initialized with the categories of the shopping system
	var $pageArray=array();				// Is initialized with an array of the pages in the pid-list
	var $orderRecord = array();			// Will hold the order record if fetched.


		// Internal: init():
	var $templateCode='';				// In init(), set to the content of the templateFile. Used by default in getBasket()

		// Internal: initBasket():
	var $basket=array();				// initBasket() sets this array based on the registered items
	var $basketExtra;					// initBasket() uses this for additional information like the current payment/shipping methods
	var $recs = Array(); 				// in initBasket this is set to the recs-array of fe_user.
	var $personInfo;					// Set by initBasket to the billing address
	var $deliveryInfo; 					// Set by initBasket to the delivery address

		// Internal: Arrays from getBasket() function
	var $itemArray;						// the items in the basket; database row, how many (quantity, count) and the price; this has replaced the former $calculatedBasket
	var $calculatedArray;				// all calculated totals from the basket e.g. priceTax and weight

	var $config=array();
	var $conf=array();
	var $tt_product_single='';
	var $globalMarkerArray=array();
	var $externalCObject='';
       // mkl - multilanguage support
	var $language = 0;
	var $langKey;
       // mkl - multicurrency support
	var $currency = '';				// currency iso code for selected currency
	var $baseCurrency = '';			// currency iso code for default shop currency
	var $xrate = 1.0;				// currency exchange rate (currency/baseCurrency)

	var $mkl; 					// if compatible to mkl_products


	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	function main_products($content,$conf)	{
		global $TSFE;

			// getting configuration values:
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$TSFE->set_no_cache();
    	// multilanguage support
        $this->language = $TSFE->config['config']['sys_language_uid'];
        $this->langKey = strtoupper($TSFE->config['config']['language']);	// TYPO3_languages

		// *************************************
		// *** getting configuration values:
		// *************************************

		// mkl - multicurrency support
		if (t3lib_extMgm::isLoaded('mkl_currxrate')) {
			include_once(t3lib_extMgm::extPath('mkl_currxrate').'pi1/class.tx_mklcurrxrate_pi1.php');
			$this->baseCurrency = $TSFE->tmpl->setup['plugin.']['tx_mklcurrxrate_pi1.']['currencyCode'];
			$this->currency = t3lib_div::GPvar('C') ? 	t3lib_div::GPvar('C') : $this->baseCurrency;

			// mkl - Initialise exchange rate library and get

			$this->exchangeRate = t3lib_div::makeInstance('tx_mklcurrxrate_pi1');
			$this->exchangeRate->init();
			$result = $this->exchangeRate->getExchangeRate($this->baseCurrency, $this->currency) ;
			$this->xrate = floatval ( $result['rate'] );
		}
		
		if (t3lib_extMgm::isLoaded('sr_static_info')) {
			include_once(t3lib_extMgm::extPath('sr_static_info').'pi1/class.tx_srstaticinfo_pi1.php');
			// Initialise static info library
			$this->staticInfo = t3lib_div::makeInstance('tx_srstaticinfo_pi1');
			$this->staticInfo->init();
		}

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['useFlexforms'] == 1) {
			// Converting flexform data into array:
			$this->pi_initPIflexForm();
			$this->config['code'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'display_mode');
		} else {
			$this->config['code'] = strtoupper(trim($this->cObj->stdWrap($this->conf['code'],$this->conf['code.'])));		
		}
		
		$this->config['limit'] = $this->conf['limit'] ? $this->conf['limit'] : 50;
		$this->config['limitImage'] = t3lib_div::intInRange($this->conf['limitImage'],0,9);
		$this->config['limitImage'] = $this->config['limitImage'] ? $this->config['limitImage'] : 1;

		$this->config['pid_list'] = trim($this->cObj->stdWrap($this->conf['pid_list'],$this->conf['pid_list.']));
		$this->config['pid_list'] = $this->config['pid_list'] ? $this->config['pid_list'] : $TSFE->id;

		$this->config['recursive'] = $this->cObj->stdWrap($this->conf['recursive'],$this->conf['recursive.']);
		$this->config['storeRootPid'] = $this->conf['PIDstoreRoot'] ? $this->conf['PIDstoreRoot'] : $TSFE->tmpl->rootLine[0][uid];
		$this->config['priceNoReseller'] = $this->conf['priceNoReseller'] ? t3lib_div::intInRange($this->conf['priceNoReseller'],2,2) : NULL;

			//extend standard search fields with user setup
		$this->searchFieldList = trim($this->conf['stdSearchFieldExt']) ? implode(',', array_unique(t3lib_div::trimExplode(',',$this->searchFieldList.','.trim($this->conf['stdSearchFieldExt']),1))) : $this->searchFieldList;

			// If the current record should be displayed.
		$this->config['displayCurrentRecord'] = $this->conf['displayCurrentRecord'];
		if ($this->config['displayCurrentRecord'])	{
			$this->config['code']='SINGLE';
			$this->tt_product_single = true;
		} else {
			$this->tt_product_single = t3lib_div::_GP('tt_products');			
		}

			// template file is fetched. The whole template file from which the various subpart are extracted.
		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);

			// globally substituted markers, fonts and colors.
		$splitMark = md5(microtime());
		$globalMarkerArray=array();
		list($globalMarkerArray['###GW1B###'],$globalMarkerArray['###GW1E###']) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf['wrap1.']));
		list($globalMarkerArray['###GW2B###'],$globalMarkerArray['###GW2E###']) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf['wrap2.']));
		$globalMarkerArray['###GC1###'] = $this->cObj->stdWrap($this->conf['color1'],$this->conf['color1.']);
		$globalMarkerArray['###GC2###'] = $this->cObj->stdWrap($this->conf['color2'],$this->conf['color2.']);
		$globalMarkerArray['###GC3###'] = $this->cObj->stdWrap($this->conf['color3'],$this->conf['color3.']);
		$globalMarkerArray['###DOMAIN###'] = $this->conf['domain'];

			// Substitute Global Marker Array
		$this->templateCode= $this->cObj->substituteMarkerArrayCached($this->templateCode, $globalMarkerArray);


			// This cObject may be used to call a function which manipulates the shopping basket based on settings in an external order system. The output is included in the top of the order (HTML) on the basket-page.
		$this->externalCObject = $this->getExternalCObject('externalProcessing');

			// Initializes object
		$this->setPidlist($this->config['pid_list']);				// The list of pid's we're operation on. All tt_products records must be in the pidlist in order to be selected.
		$this->TAXpercentage = doubleval($this->conf['TAXpercentage']);		// Set the TAX percentage.
		$this->globalMarkerArray = $globalMarkerArray;

		$this->initCategories();

		$codes=t3lib_div::trimExplode(',', $this->config['code']?$this->config['code']:strtoupper($this->conf['defaultCode']),1);
		if (!count($codes))     $codes=array('HELP');


		if (t3lib_div::_GP('mode_update'))
			$updateMode = 1;
		else
			$updateMode = 0;
	
		tx_ttproducts_basket_div::initBasket($TSFE->fe_user->getKey('ses','recs'), $updateMode); // Must do this to initialize the basket...

		// *************************************
		// *** Listing items:
		// *************************************

		$this->$itemArray = array();
		$codes = $this->sort_codes($codes);
		$content .= tx_ttproducts_basket_div::products_basket($codes);
		reset($codes);
		while(list(,$theCode)=each($codes))	{
			$theCode = (string) trim($theCode);
			switch($theCode)	{
				case 'LIST':
				case 'LISTGIFTS':
				case 'LISTHIGHLIGHTS':
				case 'LISTNEWITEMS':
				case 'LISTOFFERS':
				case 'SEARCH':
				case 'SINGLE':
					$content.=$this->products_display($theCode);
				break;
				case 'BASKET':
				case 'FINALIZE':
				case 'INFO':
				case 'OVERVIEW':
				case 'PAYMENT':
						// nothing here any more
				break;
				case 'BILL':
				case 'DELIVERY':
				case 'TRACKING':
					$content.=$this->products_tracking($theCode);
				break;
				case 'MEMO':
					$content.=$this->memo_display($theCode);
				break;
				case 'CURRENCY':
					$content.=$this->currency_selector($theCode);
				break;	
/* Added Els: case ORDERS line 253-255 */
				case 'ORDERS':
					$content.=$this->orders_display($theCode);
				break;
				default:	// 'HELP'
					$helpTemplate = $this->cObj->fileResource('EXT:'.TT_PRODUCTS_EXTkey.'/template/products_help.tmpl');

						// Get language version
					$helpTemplate_lang='';
					if ($this->langKey)	{$helpTemplate_lang = $this->cObj->getSubpart($helpTemplate,'###TEMPLATE_'.$this->langKey.'###');}
					$helpTemplate = $helpTemplate_lang ? $helpTemplate_lang : $this->cObj->getSubpart($helpTemplate,'###TEMPLATE_DEFAULT###');

						// Markers and substitution:
					$markerArray['###CODE###'] = $theCode;
					$markerArray['###PATH###'] = t3lib_extMgm::siteRelPath(TT_PRODUCTS_EXTkey);
					$content.=$this->cObj->substituteMarkerArray($helpTemplate,$markerArray);
				break;
			}
		}
		return $this->pi_wrapInBaseClass($content);
	}


	/**
	 * returns the codes in the order in which they have to be processed
     *
     * @param       string          $fieldname is the field in the table you want to create a JavaScript for
     * @return      void
 	 */
	function sort_codes($codes)	{
		$retCodes = array();
		$codeArray =  Array (
			'1' =>  'OVERVIEW', 'BASKET', 'LIST', 'LISTOFFERS', 'LISTHIGHLIGHTS', 
			'LISTNEWITEMS', 'SINGLE', 'SEARCH', 
			'MEMO', 'INFO',
			'PAYMENT', 'FINALIZE',
			'TRACKING', 'BILL', 'DELIVERY',
			'CURRENCY', 'ORDERS',
			'LISTGIFTS', 'HELP',
			);

		if (is_array($codes)) {
			foreach ($codes as $k => $code) {
				$theCode = trim($code);
				$key = array_search($theCode, $codeArray);
				if ($key!=false) {
					$retCodes[$key] = $theCode;
				}
			}
		}
		
		return ($retCodes);	
	}



	/**
	 * Get External CObjects
	 */
	function getExternalCObject($mConfKey)	{
		if ($this->conf[$mConfKey] && $this->conf[$mConfKey.'.'])	{
			$this->cObj->regObj = &$this;
			return $this->cObj->cObjGetSingle($this->conf[$mConfKey],$this->conf[$mConfKey.'.'],'/'.$mConfKey.'/').'';
		}
	}


	/**
	 * currency selector
	 */
	function currency_selector($theCode)	{
		global $TSFE;
		
		$currList = $this->exchangeRate->initCurrencies($this->BaseCurrency);
		$jScript =  '	var currlink = new Array(); '.chr(10);
		$index = 0;
		foreach( $currList as $key => $value)	{
			//$url = $this->getLinkUrl('','',array('C' => 'C='.$key));
			$url = $this->pi_getPageLink($TSFE->id,'',$this->getLinkParams('',array('C' => 'C='.$key)));
			$jScript .= '	currlink['.$index.'] = "'.$url.'"; '.chr(10) ;
			$index ++ ;
		}

		$content = $this->cObj->getSubpart($this->templateCode,$this->spMarker('###CURRENCY_SELECTOR###'));
		$content = $this->cObj->substituteMarker( $content, '###CURRENCY_FORM_NAME###', 'tt_products_currsel_form' );
		$onChange = 'if (!document.tt_products_currsel_form.C.options[document.tt_products_currsel_form.C.selectedIndex].value) return; top.location.replace(currlink[document.tt_products_currsel_form.C.selectedIndex] );';
		$selector = $this->exchangeRate->buildCurrSelector($this->BaseCurrency,'C','',$this->currency, $onChange);
		$content = $this->cObj->substituteMarker( $content, '###SELECTOR###', $selector );

		// javascript to submit correct get parameters for each currency
		$GLOBALS['TSFE']->additionalHeaderData['tx_ttproducts'] = '<script type="text/javascript">'.chr(10).$jScript.'</script>';
		return $content ;
	}




	/**
	 * Order tracking
	 *
	 *
	 * @param	integer		Code: TRACKING, BILL or DELIVERY
	 * @return	void
	 * @see enableFields()
	 */

	function products_tracking($theCode)	{
		global $TSFE;

		if (strcmp($theCode, 'TRACKING')!=0) { // bill and delivery tracking need more data
			$this->mapPersonIntoToDelivery();	// This maps the billing address into the blank fields of the delivery address
			$this->setPidlist($this->config['storeRootPid']);	// Set list of page id's to the storeRootPid.
			$this->initRecursive(999);		// This add's all subpart ids to the pid_list based on the rootPid set in previous line
			$this->generatePageArray();		// Creates an array with page titles from the internal pid_list. Used for the display of category titles.
		}
		$admin = $this->shopAdmin();
		if (t3lib_div::_GP('tracking') || $admin)	{		// Tracking number must be set
			$orderRow = $this->getOrderRecord('',t3lib_div::_GP('tracking'));
			if (is_array($orderRow) || $admin)	{		// If order is associated with tracking id.
				if (!is_array($orderRow)) {
					$orderRow=array('uid'=>0);
				}
				switch ($theCode) {
					case 'TRACKING':
						$content = $this->getTrackingInformation($orderRow,$this->templateCode);
						break;
					case 'BILL':
						$content = $this->getInformation('bill',$orderRow, $this->templateCode,t3lib_div::_GP('tracking'));
						break;
					case 'DELIVERY':
						$content = $this->getInformation('delivery',$orderRow, $this->templateCode,t3lib_div::_GP('tracking'));
						break;
					default:
						debug('error in tt_products calling function products_tracking with $type = "'.$type.'"');
				}
			} else {	// ... else output error page
				$content=$this->cObj->getSubpart($this->templateCode,$this->spMarker('###TRACKING_WRONG_NUMBER###'));
				if (!$TSFE->beUserLogin)	{$content = $this->cObj->substituteSubpart($content,'###ADMIN_CONTROL###','');}
			}
		} else {	// No tracking number - show form with tracking number
			$content=$this->cObj->getSubpart($this->templateCode,$this->spMarker('###TRACKING_ENTER_NUMBER###'));
			if (!$TSFE->beUserLogin)	{$content = $this->cObj->substituteSubpart($content,'###ADMIN_CONTROL###','');}
		}
		$markerArray=array();
		$markerArray['###FORM_URL###'] = $this->pi_getPageLink($TSFE->id,'',$this->getLinkParams()) ; // $this->getLinkUrl();	// Add FORM_URL to globalMarkerArray, linking to self.
		$content= $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	}  // products_tracking



	function load_noLinkExtCobj()	{
		if ($this->conf['externalProcessing_final'] || is_array($this->conf['externalProcessing_final.']))	{	// If there is given another cObject for the final order confirmation template!
			$this->externalCObject = $this->getExternalCObject('externalProcessing_final');
		}
	} // load_noLinkExtCobj



	/**
	 * Returning template subpart marker
	 */
	function spMarker($subpartMarker)	{
		$sPBody = substr($subpartMarker,3,-3);
		$altSPM = '';
		if (isset($this->conf['altMainMarkers.']))	{
			$altSPM = trim($this->cObj->stdWrap($this->conf['altMainMarkers.'][$sPBody],$this->conf['altMainMarkers.'][$sPBody.'.']));
			$GLOBALS['TT']->setTSlogMessage('Using alternative subpart marker for "'.$subpartMarker.'": '.$altSPM,1);
		}
		return $altSPM ? $altSPM : $subpartMarker;
	} // spMarker



	function categorycomp($row1, $row2)  {
		return strcmp($this->categories[$row1['category']], $this->categories[$row2['category']]);
	} // categorycomp



	/**
	 * Returning the pid out from the row using the where clause
	 */
	function getPID($conf, $confExt, $row) {
		$rc = 0;
		if ($confExt) {
			foreach ($confExt as $k1 => $param) {
				$type  = $param['type'];
				$where = $param['where'];
				$isValid = false;
				if ($where) {
					$wherelist = explode ('AND', $where);
					$isValid = true;
					foreach ($wherelist as $k2 => $condition) {
						$args = explode ('=', $condition);
						if ($row[$args[0]] != $args[1]) {
							$isValid = false;
						}
					}
				} else {
					$isValid = true;
				}

				if ($isValid == true) {
					switch ($type) {
						case 'sql':					
							$rc = $param['pid'];
							break;
						case 'pid':
							$rc = intval ($this->pageArray[$row['pid']]['pid']);
							break;
					}
					break;  //ready with the foreach loop
				}
			}
		} else
		{
			$rc = $conf;
		}
		return $rc;
	} // getPID



	/**
	 * returns if the product has been put into the basket as a gift
	 * 
	 * @param	integer		uid of the product
	 * @param	integer		variant of the product only size is used now --> TODO
	 * @return	array		all gift numbers for this product		
	 */
	function getGiftNumbers($uid, $variant)	{
		$giftArray = array();
		
		if ($this->basketExt['gift']) {
			foreach ($this->basketExt['gift'] as $giftnumber => $giftItem) {
				if ($giftItem['item'][$uid][$variant]) {
					$giftArray [] = $giftnumber;
				}
			}
		} 

		return $giftArray;
	}



	/**
	 * Displaying single products/ the products list / searching
	 */
	function products_display($theCode, $memoItems='')	{
		global $TSFE;

/*
			$query = "select tt_products.uid,tt_products.pid";
 			$query .= ",tt_products.title,tt_products.note";
 			$query .= ",tt_products.price,tt_products.price2,tt_products.unit,tt_products.unit_factor";
 			$query .= ",tt_products.image,tt_products.datasheet,tt_products.www";
 			$query .= ",tt_products.itemnumber,tt_products.category";
 			$query .= ",tt_products.inStock,tt_products.ordered";
 			$query .= ",tt_products.fe_group";

 	       		// language overlay
			if ($this->language > 0) {
				$query .= ",tt_products_language.title AS o_title";
				$query .= ",tt_products_language.note AS o_note";
				$query .= ",tt_products_language.unit AS o_unit";
				$query .= ",tt_products_language.datasheet AS o_datasheet";
				$query .= ",tt_products_language.www AS o_www";
			}
			$query .= " FROM tt_products";
			if ($this->language > 0) {
				$query .= " LEFT JOIN tt_products_language";
				$query .= " ON (tt_products.uid=tt_products_language.prd_uid";
				$query .= " AND tt_products_language.sys_language_uid=$this->language";
				$query .= $this->cObj->enableFields("tt_products_language");
				$query .= ")";
			}
			$query .= " WHERE 1=1";
			$query .= " AND tt_products.uid=".intval($this->tt_product_single);
			$query .= " AND tt_products.pid IN ($this->pid_list) ";
			$query .= $this->cObj->enableFields("tt_products");


			$res = mysql(TYPO3_db,$query);

*/

		$pid = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : (t3lib_div::_GP('backPID') ? t3lib_div::_GP('backPID') : $TSFE->id)); 
		$formUrl = $this->pi_getPageLink($pid,'',$this->getLinkParams());  //  $this->getLinkUrl($this->conf['PIDbasket']);
//		if (!$formUrl) {
//			$formUrl = $this->pi_getPageLink(t3lib_div::_GP('backPID'),'',$this->getLinkParams());  // $this->getLinkUrl(t3lib_div::_GP('backPID'));
//			debug ($formUrl, '$formUrl', __LINE__, __FILE__);
//		}
		if (($theCode=='SINGLE') || ($this->tt_product_single && !$this->conf['NoSingleViewOnList'])) {
			// List single product:
			
			if (!$this->tt_product_single) {
				$this->tt_product_single = $this->conf['defaultProductID'];	
			}
			
			$extVars= t3lib_div::_GP('ttp_extvars'); 
			
				// performing query:
			$this->setPidlist($this->config['storeRootPid']);
			$this->initRecursive(999);
			$this->generatePageArray();

			$where = 'uid='.intval($this->tt_product_single);

		 	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $where .' AND pid IN ('.$this->pid_list.')'.$this->cObj->enableFields('tt_products'));
		 	$row = '';
			if ($this->config['displayCurrentRecord'])	{
				$row=$this->cObj->data;
			} else {
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			}
			
			if ($extVars) {
				tx_ttproducts_article_div::getRowFromVariant ($row, $extVars);
			}

			if($row) {
			 	// $this->tt_product_single = intval ($row['uid']); // store the uid for later usage here

					// Get the subpart code
				$itemFrameTemplate ='';
				#debug ($extVars, '$extVars', __LINE__, __FILE__);
				#debug ($row['uid'], '$row[\'uid\']', __LINE__, __FILE__);
				$giftNumberArray = $this->getGiftNumbers ($row['uid'], $extVars);

		#debug ($giftNumberArray, '$giftNumberArray', __LINE__, __FILE__);
			
				if ($this->config['displayCurrentRecord'])	{
					$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY_RECORDINSERT###';
				} else if (count($giftNumberArray)) {
					$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY_GIFT###';
				} else {
					$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY###';
				}
				$itemFrameWork = $this->cObj->getSubpart($this->templateCode,$this->spMarker($itemFrameTemplate));
				
				
				if (count($giftNumberArray)) {
					$personDataFrameWork = $this->cObj->getSubpart($itemFrameWork,'###PERSON_DATA###');
					// the itemFramework is a smaller part here
					$itemFrameWork = $this->cObj->getSubpart($itemFrameWork,'###PRODUCT_DATA###');					
				}

				// set the title of the single view
				if($this->conf['substitutePagetitle']== 2) {
					$TSFE->page['title'] = $row['subtitle'] ? $row['subtitle'] : $row['title'];
				} elseif ($this->conf['substitutePagetitle']) {
					$TSFE->page['title'] = $row['title'];
				}

				$catTitle= $this->pageArray[$row['pid']]['title'].($row['category']?'/'.$this->categories[$row['category']]:'');

/*
				$catTitle= $this->categories[$row['category']]['title'];
				if ($this->language > 0 && $row['o_datasheet'] != '') {
					$datasheetFile = $row['o_datasheet'] ;
				} else  {
					$datasheetFile = $row['datasheet'] ;
				}
*/

				$datasheetFile = $row['datasheet'];

					// Fill marker arrays
				$wrappedSubpartArray=array();
				$pid = ( t3lib_div::_GP('backPID') ? t3lib_div::_GP('backPID') : $TSFE->id);
				$wrappedSubpartArray['###LINK_ITEM###']= array('<a href="'. $this->pi_getPageLink($pid,'',$this->getLinkParams())  /* $this->getLinkUrl(t3lib_div::_GP('backPID'))*/ .'">','</a>');

				if( $datasheetFile == '' )  {
					$wrappedSubpartArray['###LINK_DATASHEET###']= array('<!--','-->');
				}  else  {
					$wrappedSubpartArray['###LINK_DATASHEET###']= array('<a href="uploads/tx_ttproducts/datasheet/'.$datasheetFile.'">','</a>');
				}

				$item = $this->getItem($row);
				$forminfoArray = array ('###FORM_NAME###' => 'item_'.$this->tt_product_single);
				$markerArray = $this->getItemMarkerArray ($item,$catTitle,$this->config['limitImage'],'image', $forminfoArray);
				
				$subpartArray = array();

				$markerArray['###FORM_NAME###']=$forminfoArray['###FORM_NAME###'];				

				$markerArray['###FORM_URL###']=$formUrl.'&tt_products='.$this->tt_product_single ;

				$url = $this->pi_getPageLink($TSFE->id,'',$this->getLinkParams()) ; // $this->getLinkUrl('','tt_products');

				$queryPrevPrefix = '';
				$queryNextPrefix = '';
				if ($this->conf['orderByItemNumberSg']) {
					$queryPrevPrefix = 'itemnumber < '.intval($row['itemnumber']);
					$queryNextPrefix = 'itemnumber > '.intval($row['itemnumber']);
				} else {
					$queryPrevPrefix = 'uid < '.intval($this->tt_product_single);
					$queryNextPrefix = 'uid > '.intval($this->tt_product_single);
				}
				$queryprev = '';
				$wherestock = ($this->conf['showNotinStock'] ? '' : 'AND (inStock >0) ');
				$queryprev = $queryPrevPrefix .' AND pid IN ('.$this->pid_list.')'. $wherestock . $this->cObj->enableFields('tt_products');
				$resprev = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $queryprev);

				if ($rowprev = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resprev) )
					$wrappedSubpartArray['###LINK_PREV_SINGLE###']=array('<a href="'.$url.'&tt_products='.$rowprev['uid'].'">','</a>');
				else
					$subpartArray['###LINK_PREV_SINGLE###']='';

				$querynext = $queryNextPrefix.' AND pid IN ('.$this->pid_list.')'. $wherestock . $this->cObj->enableFields('tt_products');
				$resnext = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $querynext);

				if ($rownext = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resnext) )
					$wrappedSubpartArray['###LINK_NEXT_SINGLE###']=array('<a href="'.$url.'&tt_products='.$rownext['uid'].'">','</a>');
				else
					$subpartArray['###LINK_NEXT_SINGLE###']='';

				if (trim($row['color']) == '')
					$subpartArray['###display_variant1###'] = '';
				if (trim($row['size']) == '')
					$subpartArray['###display_variant2###'] = '';
				if (trim($row['accessory']) == '0')
					$subpartArray['###display_variant3###'] = '';
				if (trim($row['gradings']) == '')
					$subpartArray['###display_variant4###'] = '';

					// Substitute
				$content= $this->cObj->substituteMarkerArrayCached($itemFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);
				
				if ($personDataFrameWork) {
										
					$subpartArray = array();
					$wrappedSubpartArray=array();
					foreach ($giftNumberArray as $k => $giftnumber) {
						#debug ($this->basketExt['gift'], '$this->basketExt[\'gift\']', __LINE__, __FILE__);
						$markerArray = $this->addGiftMarkers ($markerArray, $giftnumber);
						$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'].'_'.$giftnumber;
						$markerArray['###FORM_ONSUBMIT###']='return checkParams (document.'.$markerArray['###FORM_NAME###'].')'; 
						$markerArray['###FORM_URL###'] = $this->pi_getPageLink(t3lib_div::_GP('backPID'),'',$this->getLinkParams('', array('tt_products' => $row['uid'], 'ttp_extvars' => htmlspecialchars($extVars)))); // $this->getLinkUrl(t3lib_div::_GP('backPID')).'&tt_products='.$row['uid'].'&ttp_extvars='.htmlspecialchars($extVars);

						#debug ($TSFE->id, '$TSFE->id', __LINE__, __FILE__);
						$markerArray['###FIELD_NAME###']='ttp_gift[item]['.$row['uid'].']['.$extVars.']'; // here again, because this is here in ITEM_LIST view
						#debug ($this->basketExt['gift'][$giftnumber]['item'], '$this->basketExt[\'gift\'][$giftnumber][\'item\']', __LINE__, __FILE__);
						#debug ($extVars, '$extVars', __LINE__, __FILE__);
						#debug ($this->basketExt['gift'][$giftnumber]['item'][$extVars], '$this->basketExt[\'gift\'][$giftnumber][\'item\'][$extVars]', __LINE__, __FILE__);
						$markerArray['###FIELD_QTY###'] = $this->basketExt['gift'][$giftnumber]['item'][$row['uid']][$extVars];

						$content.=$this->cObj->substituteMarkerArrayCached($personDataFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);
					}
				}
				tx_ttproducts_div::setJS('email');  // other JavaScript checks can come here
			} else {
				$content.='Wrong parameters, GET/POST var \'tt_products\' was missing or no product with uid = '.intval($this->tt_product_single) .' found.';
			}
		} else {
			$content='';
	// List products:
			$where='';
			if ($theCode=='SEARCH')	{
					// Get search subpart
				$t['search'] = $this->cObj->getSubpart($this->templateCode,$this->spMarker('###ITEM_SEARCH###'));
					// Substitute a few markers
				$out=$t['search'];
				$pid = ( $this->conf['PIDsearch'] ? $this->conf['PIDsearch'] : $TSFE->id);
				$out=$this->cObj->substituteMarker($out, '###FORM_URL###', $this->pi_getPageLink($pid,'',$this->getLinkParams())); // $this->getLinkUrl($this->conf['PIDsearch']));
				$out=$this->cObj->substituteMarker($out, '###SWORDS###', htmlspecialchars(t3lib_div::_GP('swords')));
					// Add to content
				$content.=$out;
				if (t3lib_div::_GP('swords'))	{
					$where = $this->searchWhere(trim(t3lib_div::_GP('swords')));
				}

				// if parameter 'newitemdays' is specified, only new items from the last X days are displayed
				if (t3lib_div::_GP('newitemdays')) {
					$temptime = time() - 86400*intval(trim(t3lib_div::_GP('newitemdays')));
					$where = 'AND tstamp >= '.$temptime;
				}

			}

			if ($theCode=='LISTGIFTS') {
				$where .= ' AND '.($this->conf['whereGift'] ? $this->conf['whereGift'] : '1=0');
			}
			if ($theCode=='LISTOFFERS') {
				$where .= ' AND offer';
			}
			if ($theCode=='LISTHIGHLIGHTS') {
				$where .= ' AND highlight';
			}
			if ($theCode=='LISTNEWITEMS') {
				$temptime = time() - 86400*intval(trim($this->conf['newItemDays']));
				$where = 'AND tstamp >= '.$temptime;
			}
			if ($theCode=='MEMO') {
				$where = ' AND '.($memoItems != '' ? 'uid IN ('.$memoItems.')' : '1=0' ); 
			}

			$begin_at=t3lib_div::intInRange(t3lib_div::_GP('begin_at'),0,100000);
			if (($theCode!='SEARCH' && !t3lib_div::_GP('swords')) || $where)	{

				$this->initRecursive($this->config['recursive']);
				$this->generatePageArray();

					// Get products
				$selectConf = Array();
				$selectConf['pidInList'] = $this->pid_list;
				$wherestock = ($this->config['showNotinStock'] ? '' : 'AND (inStock > 0) ');
				$selectConf['where'] = '1=1 '.$wherestock.$where;

					// performing query to count all products (we need to know it for browsing):
				$selectConf['selectFields'] = 'count(*)';
				$res = $this->cObj->exec_getQuery('tt_products',$selectConf);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$productsCount = $row[0];

					// range check to current productsCount
				$begin_at = t3lib_div::intInRange(($begin_at >= $productsCount)?($productsCount-$this->config['limit']):$begin_at,0);

					// performing query for display:
				$selectConf['orderBy'] = ($this->conf['orderBy'] ? $this->conf['orderBy'] : 'pid,category,title');
				$selectConf['selectFields'] = '*';
				$selectConf['max'] = ($this->config['limit']+1);
				$selectConf['begin'] = $begin_at;

			 	$res = $this->cObj->exec_getQuery('tt_products',$selectConf);

				$productsArray=array();
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
					$productsArray[$row['pid']][]=$row;
				}


/*
				// Fetching products:
	 			$query = "select tt_products.uid,tt_products.pid";
	 			$query .= ",tt_products.title,tt_products.note";
	 			$query .= ",tt_products.price,tt_products.price2,tt_products.unit,tt_products.unit_factor";
	 			$query .= ",tt_products.image,tt_products.datasheet,tt_products.www";
	 			$query .= ",tt_products.itemnumber,tt_products.category";
	 			$query .= ",tt_products.inStock,tt_products.ordered";
	 			$query .= ",tt_products.fe_group";

	 	       		// language ovelay
				if ($this->language > 0) {
					$query .= ",tt_products_language.title AS o_title";
					$query .= ",tt_products_language.note AS o_note";
					$query .= ",tt_products_language.unit AS o_unit";
					$query .= ",tt_products_language.datasheet AS o_datasheet";
					$query .= ",tt_products_language.www AS o_www";
				}
				$query .= " FROM tt_products";
				if ($this->language > 0) {
					$query .= " LEFT JOIN tt_products_language";
					$query .= " ON (tt_products.uid=tt_products_language.prd_uid";
					$query .= " AND tt_products_language.sys_language_uid=$this->language";
					$query .= $this->cObj->enableFields("tt_products_language");
					$query .= ")";
				}
				$query .= " WHERE 1=1";
				$query .= " AND tt_products.pid IN ($this->pid_list) ";
				$query .= $this->cObj->enableFields("tt_products");
				$query .= " ORDER BY pid,category,sorting,title";
				$query .=" LIMIT ".$begin_at.",".($this->config["limit"]+1);

*/
				// Getting various subparts we're going to use here:
				if ($memoItems != '') {
					$t['listFrameWork'] = $this->cObj->getSubpart($this->templateCode,$this->spMarker('###MEMO_TEMPLATE###'));
				} else if ($theCode=='LISTGIFTS') {
					$t['listFrameWork'] = $this->cObj->getSubpart($this->templateCode,$this->spMarker('###ITEM_LIST_GIFTS_TEMPLATE###'));
				} else {
					$t['listFrameWork'] = $this->cObj->getSubpart($this->templateCode,$this->spMarker('###ITEM_LIST_TEMPLATE###'));
				}

				$t['categoryTitle'] = $this->cObj->getSubpart($t['listFrameWork'],'###ITEM_CATEGORY###');
				$t['itemFrameWork'] = $this->cObj->getSubpart($t['listFrameWork'],'###ITEM_LIST###');
				$t['item'] = $this->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');

				$markerArray=array();
				$markerArray['###FORM_URL###']=$formUrl; // Applied later as well.
				
				if ($theCode=='LISTGIFTS') {
					$markerArray = $this->addGiftMarkers ($markerArray, $this->giftnumber);
					$markerArray['###FORM_NAME###']= 'GiftForm';
					$markerArray['###FORM_ONSUBMIT###']='return checkParams (document.GiftForm)'; 

					$markerFramework = 'listFrameWork';
					$t['listFrameWork'] = $this->cObj->substituteMarkerArrayCached($t['listFrameWork'],$markerArray,array(),array());

				} else {
					$markerArray['###FORM_NAME###']= 'ShopForm';
					$markerArray['###FORM_ONSUBMIT###']='return checkParams (document.ShopForm)';				
				}

				tx_ttproducts_div::setJS('email');  
				
				$t['itemFrameWork'] = $this->cObj->substituteMarkerArrayCached($t['itemFrameWork'],$markerArray,array(),array());					
			
				$pageArr=explode(',',$this->pid_list);

				$currentP='';
				$out='';
				$iCount=0;
				$more=0;		// If set during this loop, the next-item is drawn
				while(list(,$v)=each($pageArr))	{
					if (is_array($productsArray[$v]))	{
						if ($this->conf['orderByCategoryTitle'] >= 1) { // category means it should be sorted by the category title in this case
							uasort ($productsArray[$v], array(&$this, 'categorycomp'));
						}

						reset($productsArray[$v]);
						$itemsOut='';
						$iColCount=1;
						$tableRowOpen=0;
						while(list(,$row)=each($productsArray[$v]))	{
							$iCount++;
							if ($iCount>$this->config['limit'])	{
								$more=1;
								break;
							}

							// max. number of columns reached?
							if ($iColCount > $this->conf['displayBasketColumns'] || !$this->conf['displayBasketColumns'])
							{
								$iColCount = 1; // restart in the first column
							}

								// Print Category Title
							if ($row['pid'].'_'.$row['category']!=$currentP)	{
								if ($itemsOut)	{
									$out.=$this->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
								}
								$itemsOut='';			// Clear the item-code var

								$currentP = $row['pid'].'_'.$row['category'];
								if ($where || $this->conf['displayListCatHeader'])	{
									$markerArray=array();
									$pageCatTitle = '';
									if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_products']['pageAsCategory'] == 1) {
										$pageCatTitle = $this->pageArray[$row['pid']]['title'].'/';
									}
									$catTitle= $pageCatTitle.($row['category']?$this->categories[$row['category']]:'');
									// mkl: $catTitle= $this->categories[$row['category']]["title'];
									$this->cObj->setCurrentVal($catTitle);
									$markerArray['###CATEGORY_TITLE###']=$this->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
									$out.= $this->cObj->substituteMarkerArray($t['categoryTitle'], $markerArray);
								}
							}


/*
							if ($this->language > 0 && $row['o_datasheet'] != '') {
								$datasheetFile = $row['o_datasheet'] ;
							} else  {
								$datasheetFile = $row['datasheet'] ;
							}
*/
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
							$pid = $this->getPID($this->conf['PIDitemDisplay'], $this->conf['PIDitemDisplay.'], $row);
							
							$dum1 = $this->getLinkParams('', $addQueryString); // delete+++
							$dum = $this->pi_getPageLink($pid,'',$dum1); // delete+++
							$wrappedSubpartArray['###LINK_ITEM###']=  array('<a href="'. $this->pi_getPageLink($pid,'',$this->getLinkParams('', $addQueryString)).'"'.$css_current.'>','</a>'); // array('<a href="'.$this->getLinkUrl($pid,'',$addQueryString).'"'.$css_current.'>','</a>');
							
							if( $datasheetFile == '' )  {
								$wrappedSubpartArray['###LINK_DATASHEET###']= array('<!--','-->');
							}  else  {
								$wrappedSubpartArray['###LINK_DATASHEET###']= array('<a href="uploads/tx_ttproducts/datasheet/'.$datasheetFile.'">','</a>');
							}
							

							$item = $this->getItem($row);
/* Added Bert: in stead of listImage -> Image, reason: images are read from directory */
//							$markerArray = $this->getItemMarkerArray ($item,$catTitle, $this->config['limitImage'],'image');
							$markerArray = $this->getItemMarkerArray ($item,$catTitle, $this->config['limitImage'],'listImage');
							if ($theCode=='LISTGIFTS') {
								$markerArray = $this->addGiftMarkers ($markerArray, $this->giftnumber);
							}
							
							$subpartArray = array();

							if (!$this->conf['displayBasketColumns'])
							{
								$markerArray['###FORM_URL###']=$formUrl; // Applied later as well.
								$markerArray['###FORM_NAME###']='item_'.$iCount;
							}
														
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
							$markerArray['###FORM_MEMO###'] = $this->pi_getPageLink($pid,'',$this->getLinkParams()); //$this->getLinkUrl($this->conf['PIDmemo']);
							// cuts note in list view
							if (strlen($markerArray['###PRODUCT_NOTE###']) > $this->conf['max_note_length'])
								$markerArray['###PRODUCT_NOTE###'] = substr($markerArray['###PRODUCT_NOTE###'], 0, $this->conf['max_note_length']) . '...';

							if (trim($row['color']) == '')
								$subpartArray['###display_variant1###'] = '';
							if (trim($row['size']) == '')
								$subpartArray['###display_variant2###'] = '';
							if (trim($row['accessory']) == '0')
								$subpartArray['###display_variant3###'] = '';
							if (trim($row['gradings']) == '')
								$subpartArray['###display_variant4###'] = '';

							$tempContent = $this->cObj->substituteMarkerArrayCached($t['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
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
							$out.=$this->cObj->substituteMarkerArrayCached($t['itemFrameWork'], $subpartArray, array('###ITEM_SINGLE###'=>$itemsOut));
						}
					}
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
					$tempUrl = $this->pi_linkToPage($splitMark,$TSFE->id,$this->getLinkParams('', array('begin_at' => $next))); 
					
					$wrappedSubpartArray['###LINK_NEXT###']=  explode ($splitMark, $tempUrl);  // array('<a href="'.$url.'&begin_at='.$next.'">','</a>');
				} else {
					$subpartArray['###LINK_NEXT###']='';
				}
				if ($begin_at)	{
					$prev = ($begin_at-$this->config['limit'] < 0) ? 0 : $begin_at-$this->config['limit'];
					$tempUrl = $this->pi_linkToPage($splitMark,$TSFE->id,$this->getLinkParams('', array('begin_at' => $prev)));
					$wrappedSubpartArray['###LINK_PREV###']=explode ($splitMark, $tempUrl); // array('<a href="'.$url.'&begin_at='.$prev.'">','</a>');
				} else {
					$subpartArray['###LINK_PREV###']='';
				}
				if ($productsCount > $this->config['limit'] )	{ // there is more than one page, so let's browse
					$wrappedSubpartArray['###LINK_BROWSE###']=array('',''); // <- this could be done better I think, or not?
					$markerArray['###BROWSE_LINKS###']='';
					for ($i = 0 ; $i < ($productsCount/$this->config['limit']); $i++) 	{
						if (($begin_at >= $i*$this->config['limit']) && ($begin_at < $i*$this->config['limit']+$this->config['limit'])) 	{
							$markerArray['###BROWSE_LINKS###'].= ' <b>'.(string)($i+1).'</b> ';
							//	you may use this if you want to link to the current page also
							//
						} else {
							$tempUrl = $this->pi_linkToPage((string)($i+1),$TSFE->id,$this->getLinkParams('', array('begin_at' => (string)($i * $this->config['limit']))));
							$markerArray['###BROWSE_LINKS###'].= explode ($splitMark, $tempUrl); // ' <a href="'.$url.'&begin_at='.(string)($i * $this->config['limit']).'">'.(string)($i+1).'</a> ';
						}
					}
				} else {
					$subpartArray['###LINK_BROWSE###']='';
				}

				$subpartArray['###ITEM_CATEGORY_AND_ITEMS###']=$out;
				$markerArray['###FORM_URL###']=$formUrl;      // Applied it here also...
				$markerArray['###ITEMS_SELECT_COUNT###']=$productsCount;
				
				$content.= $this->cObj->substituteMarkerArrayCached($t['listFrameWork'],$markerArray,$subpartArray,$wrappedSubpartArray);
			} elseif ($where)	{
				$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker('###ITEM_SEARCH_EMPTY###'));
			}
		}
		return $content;
	}	// products_display


	/**
	 * Sets the pid_list internal var
	 */
	function setPidlist($pid_list)	{
		$this->pid_list = $pid_list;
	}


	/**
	 * Extends the internal pid_list by the levels given by $recursive
	 */
	function initRecursive($recursive)	{
		if ($recursive)	{		// get pid-list if recursivity is enabled
			$pid_list_arr = explode(',',$this->pid_list);
			$this->pid_list='';
			while(list(,$val)=each($pid_list_arr))	{
				$this->pid_list.=$val.','.$this->cObj->getTreeList($val,intval($recursive));
			}
			$this->pid_list = ereg_replace(',$','',$this->pid_list);
		}
	}


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function initCategories()	{
			// Fetching catagories:
	 	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products_cat', '1=1'.$this->cObj->enableFields('tt_products_cat'));
		$this->categories = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$this->categories[$row['uid']] = $row['title'];
		}
	} // initCategories
/*
			// Fetching categories:
	 	$query = "select tt_products_cat.uid,tt_products_cat.pid";
	 	$query .= ",tt_products_cat.tstamp,tt_products_cat.crdate";
	 	$query .= ",tt_products_cat.hidden,tt_products_cat.title";
	 	$query .= ",tt_products_cat.note,tt_products_cat.image,tt_products_cat.deleted";
	 	       // mkl: language ovelay
		if ($this->language > 0) {
			$query .= ",tt_products_cat_language.title AS o_title";
			$query .= ",tt_products_cat_language.note AS o_note";
		}
		$query .= " FROM tt_products_cat";
		if ($this->language > 0) {
			$query .= " LEFT JOIN tt_products_cat_language";
			$query .= " ON (tt_products_cat.uid=tt_products_cat_language.cat_uid";
			$query .= " AND tt_products_cat_language.sys_language_uid=$this->language";
			$query .= $this->cObj->enableFields("tt_products_cat_language");
			$query .= ")";
		}
		$query .= " WHERE 1=1";
		$query .= $this->cObj->enableFields("tt_products_cat");


		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		$this->categories=array();
		while($row = mysql_fetch_assoc($res))	{
		        if ( ($this->language > 0) && $row["o_title"] )	{
				$this->categories[$row["uid"]]["title"] = $row["o_title"];
		        }
		        else	{
				$this->categories[$row["uid"]]["title"] = $row["title"];
				}
		        if ( ($this->language > 0) && $row["o_note"] )	{
				$this->categories[$row["uid"]]["note"] = $this->pi_RTEcssText($row["o_note"]);
		        }
		        else	{
				$this->categories[$row["uid"]]["note"] = $this->pi_RTEcssText($row["note"]);
				}
				$this->categories[$row["uid"]]["image"] = $row["image"];
		}
	}
*/

	/**
	 * Generates an array, ->pageArray of the pagerecords from ->pid_list
	 */
	function generatePageArray()	{
			// Get pages (for category titles)
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid,pid', 'pages', 'uid IN ('.$this->pid_list.')');
		$this->pageArray = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
			$this->pageArray[$row['uid']] = $row;
		}
	} // generatePageArray




	// **************************
	// Utility functions
	// **************************

	function isUserInGroup($feuser, $group)
	{
		$groups = explode(',', $feuser['usergroup']);
		foreach ($groups as $singlegroup)
			if ($singlegroup == $group)
				return true;
		return false;
	} // isUserInGroup

	/**
	 * Returns the $price with either tax or not tax, based on if $tax is true or false. This function reads the TypoScript configuration to see whether prices in the database are entered with or without tax. That's why this function is needed.
	 */
	function getPrice($price,$tax=1,$taxpercentage=0)	{
		global $TSFE;

		if ($taxpercentage==0)
			$taxFactor = 1+$this->TAXpercentage/100;
		else
			$taxFactor = 1+$taxpercentage/100;

		if ($TSFE->fe_user->user['tt_products_discount'] != 0) {
			$price = $price - ($price * ($TSFE->fe_user->user['tt_products_discount'] / 100));
		}

		$taxIncluded = $this->conf['TAXincluded'];
		if ($tax)	{
			if ($taxIncluded)	{	// If the configuration says that prices in the database is with tax included
				return doubleval($price);
			} else {
				return doubleval($price)*$taxFactor;
			}
		} else {
			if ($taxIncluded)	{	// If the configuration says that prices in the database is with tax included
				return doubleval($price)/$taxFactor;
			} else {
				return doubleval($price);
			}
		}
	} // getPrice

	// function using getPrice and considering a reduced price for resellers
	function getResellerPrice($row,$tax=1)	{
		$returnPrice = 0;
			// get reseller group number
		$priceNo = intval($this->config['priceNoReseller']);

		if ($priceNo > 0) {
			$returnPrice = $this->getPrice($row['price'.$priceNo],$tax,$row['tax']);
		}
		// normal price; if reseller price is zero then also the normal price applies
		if ($returnPrice == 0) {
			$returnPrice = $this->getPrice($row['price'],$tax,$row['tax']);
		}
		return $returnPrice;
	} // getResellerPrice


	/**
	 * Generates a search where clause.
	 */
	function searchWhere($sw)	{
		$where=$this->cObj->searchWhere($sw, $this->searchFieldList, 'tt_products');
		return $where;
	} // searchWhere


	/**
	 * Returns a url for use in forms and links
	 */
	function getLinkParams($excludeList='',$addQueryString=array()) {
		global $TSFE;
		$queryString=array();
		$queryString['backPID']= $TSFE->id;
		$temp = t3lib_div::GPvar('C') ? t3lib_div::GPvar('C') : $this->currency;
		if ($temp)	{
			$queryString['C'] = $temp;
		}
		$temp =   t3lib_div::_GP('begin_at');
		if ($temp) {
			$queryString['begin_at'] = $temp;
		}
		$temp = t3lib_div::_GP('swords') ? rawurlencode(t3lib_div::_GP('swords')) : '';
		if ($temp) {
			$queryString['swords'] = $temp;
		} 
		$temp = t3lib_div::GPvar('newitemdays') ? rawurlencode(stripslashes(t3lib_div::GPvar('newitemdays'))) : '';
		if ($temp) {
			$queryString['newitemdays'] = $temp;
		} 
		foreach ($addQueryString as $param => $value){
			$queryString[$param] = $value;
		}
		reset($queryString);
		while(list($key,$val)=each($queryString))	{
			if (!$val || ($excludeList && t3lib_div::inList($excludeList,$key)))	{
				unset($queryString[$key]);
			}
		}	
	
		return $queryString;
	}

//	/**
//	 * Returns a url for use in forms and links
//	 */
//	function getLinkUrl($id='',$excludeList='',$addQueryString=array())	{
//		global $TSFE;
//		$rc = '';
//
//		$queryString=array();
//		$queryString['id'] = 'id=' . ($id ? $id : $TSFE->id);
//		$queryString['type']= $TSFE->type ? 'type='.$TSFE->type : '';
//		$queryString['L']= t3lib_div::GPvar('L') ? 'L='.t3lib_div::GPvar('L') : '';
//		$queryString['C']= t3lib_div::GPvar('C') ? 'C='.t3lib_div::GPvar('C') : $this->currency ? 'C='.$this->currency : '';
//		if( isset($addQueryString['C']) )  {
//			$queryString['C'] = $addQueryString['C'] ;
//			unset( $addQueryString['C'] );
//		}
//		$queryString['backPID']= 'backPID='.$TSFE->id;
//		$queryString['begin_at']= t3lib_div::_GP('begin_at') ? 'begin_at='.t3lib_div::_GP('begin_at') : '';
//		$queryString['swords']= t3lib_div::_GP('swords') ? 'swords='.rawurlencode(t3lib_div::_GP('swords')) : '';
//		$queryString['newitemdays']= t3lib_div::GPvar('newitemdays') ? 'newitemdays='.rawurlencode(stripslashes(t3lib_div::GPvar('newitemdays'))) : '';
//
//		reset($queryString);
//		while(list($key,$val)=each($queryString))	{
//			if (!$val || ($excludeList && t3lib_div::inList($excludeList,$key)))	{
//				unset($queryString[$key]);
//			}
//		}
//
//		if ($TSFE->config['config']['simulateStaticDocuments'])   {
//			$pageId = $id ? $id : $TSFE->id ;
//			$pageType = $TSFE->type ;
//			unset($queryString['id']);
//			unset($queryString['type']);
//
//			$allQueryString = implode($queryString,'&');
//			if( $addQueryString )	{
//				$allQueryString .= '&'.implode($addQueryString,'&');
//			}
//            $rc = $TSFE->makeSimulFileName('', $pageId, $pageType, $allQueryString ).'.html';
//            if (!$this->config['config']['simulateStaticDocuments_pEnc']) {
//            	// add the parameters
//            	$rc .= '?'.$allQueryString;
//            }
//		}
//		else	{
//			$allQueryString = implode($queryString,'&');
//			if( $addQueryString )	{
//				$allQueryString .= '&'.implode($addQueryString,'&');
//			}
//			$rc = $TSFE->absRefPrefix.'index.php?'.$allQueryString;
//		}
//		
//		return $rc;
//
//	} // getLinkUrl


	/**
	 * convert amount to selected currency
	 */
	function getCurrencyAmount($double)	{
		if( $this->currency != $this->baseCurrency )	{
			$double = $double * $this->xrate ;
		}
		return $double;
	} // getCurrencyAmount

	/**
	 * Formatting a price
	 */
	function priceFormat($double)	{
		return number_format($double,intval($this->conf['priceDec']),$this->conf['priceDecPoint'],$this->conf['priceThousandPoint']);
	} // priceFormat

	/**
	 * Fills in all empty fields in the delivery info array
	 */
	function mapPersonIntoToDelivery()	{
			// all of the delivery address will be overwritten when no city and not email address have been filled in
		if (!trim($this->deliveryInfo['city']) && !trim($this->deliveryInfo['email'])) {
/* Added Els: 'feusers_uid,' and more fields */
			$infoFields = explode(',','feusers_uid,tx_feuserextrafields_initials_name,telephone,tx_feuserextrafields_prefix_name,tx_feuserextrafields_gsm_tel,name,email,date_of_birth,company,tx_feuserextrafields_company_deliv,address,tx_feuserextrafields_address_deliv,tx_feuserextrafields_housenumber,tx_feuserextrafields_housenumber_deliv,tx_feuserextrafields_housenumberadd,tx_feuserextrafields_housenumberadd_deliv,tx_feuserextrafields_pobox,tx_feuserextrafields_pobox_deliv,zip,tx_feuserextrafields_zip_deliv,city,tx_feuserextrafields_city_deliv,tx_feuserextrafields_country,tx_feuserextrafields_country_deliv'); // Fields...
			while(list(,$fName)=each($infoFields))	{
				$this->deliveryInfo[$fName] = $this->personInfo[$fName];
			}
		}
	} // mapPersonIntoToDelivery

	/**
	 * Checks if required fields are filled in
	 */
	function checkRequired()	{
		$flag = '';
		$requiredInfoFields = trim($this->conf['requiredInfoFields']);
		if ($this->basketExtra['payment.']['addRequiredInfoFields'] != '')
			$requiredInfoFields .= ','.trim($this->basketExtra['payment.']['addRequiredInfoFields']);

		if ($requiredInfoFields)	{
			$infoFields = t3lib_div::trimExplode(',',$requiredInfoFields);
			while(list(,$fName)=each($infoFields))	{
				if (trim($this->personInfo[$fName])=='')	{
					$flag=$fName;
					break;
				}
			}
		}
		return $flag;
	} // checkRequired

	/**
	 * Include calculation script which should be programmed to manipulate internal data.
	 */
	function includeCalcScript($calcScript,$conf)	{
		include($calcScript);
	} // includeCalcScript

	/**
	 * Include handle script
	 */
	function includeHandleScript($handleScript,$conf)	{
		include($handleScript);
		return $content;
	} // includeHandleScript


	/** mkl:
	 * For shop inside EU country: check if TAX should be included
	 */
	function checkVatInclude()	{
		$include = 1;
		if( $this->conf['TAXeu'] )   {
			if( ($this->personInfo['country_code'] != '') && ($this->personInfo['country_code'] != $this->conf['countryCode']) )    {
				$whereString =  'cn_iso_3 = "'.$this->personInfo['country_code'].'"';
				$euMember = 0 ;
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','static_countries', $whereString);
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
					$euMember = $row['cn_eu_member'];
				}
				// exclude VAT for EU companies with valid VAT id and for everyone outside EU
				if( !$euMember  ||  ($euMember && $this->personInfo['vat_id'] != '') )   {
					$include = 0;
				}
			}
		}
		return $include ;
	} // checkVatInclude


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param   array		information about the parent HTML form
	 * @return	string
	 * @access private
	 */
	function &getItemMarkerArray (&$item,$catTitle, $imageNum=0, $imageRenderObj='image', $forminfoArray = array())	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row

		$row = &$item['rec'];
		$markerArray=array();
			// Get image
		$theImgCode=array();

		$imgs = explode(',',$row['image']);

		while(list($c,$val)=each($imgs))	{
			if ($c==$imageNum)	break;
			if ($val)	{
				$this->conf[$imageRenderObj.'.']['file'] = 'uploads/pics/'.$val;
			} else {
				$this->conf[$imageRenderObj.'.']['file'] = $this->conf['noImageAvailable'];
			}
			if (!$this->conf['separateImage'])
			{
				$c = 0;  // show all images together as one image
			}
			$theImgCode[$c] .= $this->cObj->IMAGE($this->conf[$imageRenderObj.'.']);
		}

		$iconImgCode = $this->cObj->IMAGE($this->conf['datasheetIcon.']);

			// Subst. fields
/* mkl:
		if ( ($this->language > 0) && $row['o_title'] )	{
			$markerArray['###PRODUCT_TITLE###'] = $row['o_title'];
		}
		else  {
			$markerArray['###PRODUCT_TITLE###'] = $row['title'];
		}

		if ( ($this->language > 0) && $row['o_unit'] )	{
			$markerArray['###UNIT###'] = $row['o_unit'];
		}
		else  {
			$markerArray['###UNIT###'] = $row['unit'];
		}

*/
		$markerArray['###UNIT###'] = $row['unit'];
		$markerArray['###UNIT_FACTOR###'] = $row['unit_factor'];

		$markerArray['###ICON_DATASHEET###']=$iconImgCode;

		$markerArray['###PRODUCT_TITLE###'] = $row['title'];
		$markerArray['###PRODUCT_NOTE###'] = nl2br($row['note']);

//		if ( ($this->language > 0) && $row['o_note'] )	{
////			$markerArray['###PRODUCT_NOTE###'] = nl2br($row['o_note']);
//			$markerArray['###PRODUCT_NOTE###'] = $this->pi_RTEcssText($row['o_note']);
//		}
//		else  {
////			$markerArray['###PRODUCT_NOTE###'] = nl2br($row['note']);
//			$markerArray['###PRODUCT_NOTE###'] = $this->pi_RTEcssText($row['note']);
//		}

		if (is_array($this->conf['parseFunc.']))	{
			$markerArray['###PRODUCT_NOTE###'] = $this->cObj->parseFunc($markerArray['###PRODUCT_NOTE###'],$this->conf['parseFunc.']);
		}
		$markerArray['###PRODUCT_ITEMNUMBER###'] = $row['itemnumber'];

		$markerArray['###PRODUCT_IMAGE###'] = $theImgCode[0]; // for compatibility only

		while ((list($c,$val)=each($theImgCode)))
		{
			$markerArray['###PRODUCT_IMAGE' .  $c. '###'] = $theImgCode[$c];
		}

			// empty all image fields with no availble image
		for ($i=1; $i<=15; ++$i) {
			if (!$markerArray['###PRODUCT_IMAGE' .  $i. '###']) {
				$markerArray['###PRODUCT_IMAGE' .  $i. '###'] = '';
			}
		}

		$markerArray['###PRODUCT_SUBTITLE###'] = $row['subtitle'];
		$markerArray['###PRODUCT_WWW###'] = $row['www'];
		$markerArray['###PRODUCT_ID###'] = $row['uid'];

		$markerArray['###PRICE_TAX###'] = $this->printPrice($this->priceFormat($item['priceTax']));
		$markerArray['###PRICE_NO_TAX###'] = $this->printPrice($this->priceFormat($item['priceNoTax']));

		$oldPrice = $this->printPrice($this->priceFormat($this->getPrice($row['price'],1,$row['tax'])));
		$oldPriceNoTax = $this->printPrice($this->priceFormat($this->getPrice($row['price'],0,$row['tax'])));
		$priceNo = intval($this->config['priceNoReseller']);
		if ($priceNo == 0) {	// no old price will be shown when the new price has not been reducted
			$oldPrice = $oldPriceNoTax = '';
		}

		$markerArray['###OLD_PRICE_TAX###'] = $oldPrice;
		/* Added els3: if oldpricenotax is empty do print other marker */
		//$markerArray['###OLD_PRICE_NO_TAX###'] = $oldPriceNoTax;
		if ($oldPriceNoTax == '0.00') {
			$markerArray['###OLD_PRICE_NO_TAX_CLASS###'] = 'prijsvoorleeg';
			$markerArray['###OLD_PRICE_NO_TAX###'] = '-';
		} else {
			$markerArray['###OLD_PRICE_NO_TAX_CLASS###'] = 'prijsvan';
			$markerArray['###OLD_PRICE_NO_TAX###'] = '&euro; '.$oldPriceNoTax;
		}
		
		$markerArray['###PRODUCT_INSTOCK_UNIT###'] = '';
		if ($row['inStock'] > 0) {
			$markerArray['###PRODUCT_INSTOCK###'] = $row['inStock'];
			$markerArray['###PRODUCT_INSTOCK_UNIT###'] = $this->conf['inStockPieces'];
		} else {
			$markerArray['###PRODUCT_INSTOCK###'] = $this->conf['notInStockMessage'];
		}

		$markerArray['###CATEGORY_TITLE###'] = $catTitle;

		$markerArray['###FIELD_NAME###']='ttp_basket['.$row['uid'].'][quantity]';

//		$markerArray["###FIELD_NAME###"]="recs[tt_products][".$row["uid"]."]";

		$temp = $this->basketExt[$row['uid']][tx_ttproducts_article_div::getVariantFromRow ($row)];
		$markerArray['###FIELD_QTY###']= $temp ? $temp : '';
		$markerArray['###FIELD_NAME_BASKET###']='ttp_basket['.$row['uid'].']['.md5($row['extVars']).']';

		$markerArray['###FIELD_SIZE_NAME###']='ttp_basket['.$row['uid'].'][size]';
		$markerArray['###FIELD_SIZE_VALUE###']=$row['size'];
		$markerArray['###FIELD_SIZE_ONCHANGE']= ''; // TODO:  use $forminfoArray['###FORM_NAME###' in something like onChange="Go(this.form.Auswahl.options[this.form.Auswahl.options.selectedIndex].value)"
		
		$markerArray['###FIELD_COLOR_NAME###']='ttp_basket['.$row['uid'].'][color]';
		$markerArray['###FIELD_COLOR_VALUE###']=$row['color'];
		
		$markerArray['###FIELD_ACCESSORY_NAME###']='ttp_basket['.$row['uid'].'][accessory]';
		$markerArray['###FIELD_ACCESSORY_VALUE###']=$row['accessory'];
		
		$markerArray['###FIELD_GRADINGS_NAME###']='ttp_basket['.$row['uid'].'][gradings]';
		$markerArray['###FIELD_GRADINGS_VALUE###']=$row['gradings'];

		$prodColorText = '';
		$prodTmp = explode(';', $row['color']);
		if ($this->conf['selectColor']) {
			foreach ($prodTmp as $prodCol)
				$prodColorText = $prodColorText . '<OPTION value="'.$prodCol.'">'.$prodCol.'</OPTION>';
		} else {
			$prodColorText = $prodTmp[0];
		}

		$prodSizeText = '';
		$prodTmp = explode(';', $row['size']);
		if ($this->conf['selectSize']) {
			foreach ($prodTmp as $prodSize) {
				$prodSizeText = $prodSizeText . '<OPTION value="'.$prodSize.'">'.$prodSize.'</OPTION>';
			}
		} else {
			$prodSizeText = $prodTmp[0];
		}

		$prodAccessoryText = '';
		if ($this->conf['selectAccessory']) {
			$prodAccessoryText =  '<OPTION value="0">no accessory</OPTION>';	// TODO put this into the locallang.php
			$prodAccessoryText .= '<OPTION value="1">with accessory</OPTION>';
		} else {
			$prodAccessoryText = $prodSize;
		}

		$prodGradingsText = '';
		$prodTmp = explode(';', $row['gradings']);
		if ($this->conf['selectGradings']) {
			foreach ($prodTmp as $prodGradings) {
				$prodGradingsText = $prodGradingsText . '<OPTION value="'.$prodGradings.'">'.$prodGradings.'</OPTION>';
			}
		} else {
			$prodGradingsText = $prodTmp[0];
		}

		$markerArray['###PRODUCT_WEIGHT###'] = doubleval($row['weight']);
		$markerArray['###BULKILY_WARNING###'] = $row['bulkily'] ? $this->conf['bulkilyWarning'] : '';
		$markerArray['###PRODUCT_COLOR###'] = $prodColorText;
		$markerArray['###PRODUCT_SIZE###'] = $prodSizeText;
		$markerArray['###PRODUCT_ACCESSORY###'] = $prodAccessoryText;
		$markerArray['###PRODUCT_GRADINGS###'] = $prodGradingsText;
		$markerArray['###PRICE_ACCESSORY_TAX###'] = $this->printPrice($this->priceFormat($this->getPrice($row['accessory'.$this->config['priceNoReseller']],1,$row['tax'])));
		$markerArray['###PRICE_ACCESSORY_NO_TAX###'] = $this->printPrice($this->priceFormat($this->getPrice($row['accessory'.$this->config['priceNoReseller']],0,$row['tax'])));
		$markerArray['###PRICE_WITH_ACCESSORY_TAX###'] = $this->printPrice($this->priceFormat($this->getPrice($row['accessory'.$this->conf['priceNoReseller']]+$row['price'.$this->config['priceNoReseller']],1,$row['tax'])));
		$markerArray['###PRICE_WITH_ACCESSORY_NO_TAX###'] = $this->printPrice($this->priceFormat($this->getPrice($row['accessory'.$this->conf['priceNoReseller']]+$row['price'.$this->config['priceNoReseller']],0,$row['tax'])));

		if ($row['special_preparation'])
			$markerArray['###PRODUCT_SPECIAL_PREP###'] = $this->cObj->substituteMarkerArray($this->conf['specialPreparation'],$markerArray);
		else
			$markerArray['###PRODUCT_SPECIAL_PREP###'] = '';
			// Fill the Currency Symbol or not

		$markerArray['###CUR_SYM###'] = ' '.($this->conf['currencySymbol'] ? $this->conf['currencySymbol'] : '');

		if ($this->conf['itemMarkerArrayFunc'])	{
			$markerArray = $this->userProcess('itemMarkerArrayFunc',$markerArray);
		}

		return $markerArray;
	} // getItemMarkerArray


	/**
	 * Calls user function
	 */
	function userProcess($mConfKey,$passVar)	{
		global $TSFE;

		if ($this->conf[$mConfKey])	{
			$funcConf = $this->conf[$mConfKey.'.'];
			$funcConf['parentObj']=&$this;
			$passVar = $TSFE->cObj->callUserFunction($this->conf[$mConfKey], $funcConf, $passVar);
		}
		return $passVar;
	} // userProcess


	/**
	 * Adds URL markers to a markerArray
	 */
	function addURLMarkers($markerArray)	{
			// Add's URL-markers to the $markerArray and returns it
		$pid = ( $this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);
		$markerArray['###FORM_URL###'] = $this->pi_getPageLink($pid,'',$this->getLinkParams()) ;	 // $this->getLinkUrl($this->conf['PIDbasket']);
		$pid = ( $this->conf['PIDinfo'] ? $this->conf['PIDinfo'] : ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] :$TSFE->id));
		$markerArray['###FORM_URL_INFO###'] = $this->pi_getPageLink($pid,'',$this->getLinkParams()) ; // $this->getLinkUrl($this->conf['PIDinfo'] ? $this->conf['PIDinfo'] : $this->conf['PIDbasket']);
		$pid = ( $this->conf['PIDfinalize'] ? $this->conf['PIDfinalize'] : ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id));
		$markerArray['###FORM_URL_FINALIZE###'] = $this->pi_getPageLink($pid,'',$this->getLinkParams()) ;// $this->getLinkUrl($this->conf['PIDfinalize'] ? $this->conf['PIDfinalize'] : $this->conf['PIDbasket']);
		$pid = ( $this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id));
		$markerArray['###FORM_URL_THANKS###'] = $this->pi_getPageLink($pid,'',$this->getLinkParams()) ;	 // $this->getLinkUrl($this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : $this->conf['PIDbasket']);
		$markerArray['###FORM_URL_TARGET###'] = '_self';
		if ($this->basketExtra['payment.']['handleURL'])	{	// This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
			$markerArray['###FORM_URL_THANKS###'] = $this->basketExtra['payment.']['handleURL'];
		}
		if ($this->basketExtra['payment.']['handleTarget'])	{	// Alternative target
			$markerArray['###FORM_URL_TARGET###'] = $this->basketExtra['payment.']['handleTarget'];
		}
		return $markerArray;
	} // addURLMarkers


	/**
	 * Adds gift markers to a markerArray
	 */
	function addGiftMarkers($markerArray, $giftnumber)	{
		
		$markerArray['###GIFTNO###'] = $giftnumber;
		$markerArray['###GIFT_PERSON_NAME###'] = $this->basketExt['gift'][$giftnumber]['personname']; 
		$markerArray['###GIFT_PERSON_EMAIL###'] = $this->basketExt['gift'][$giftnumber]['personemail']; 
		$markerArray['###GIFT_DELIVERY_NAME###'] = $this->basketExt['gift'][$giftnumber]['deliveryname']; 
		$markerArray['###GIFT_DELIVERY_EMAIL###'] = $this->basketExt['gift'][$giftnumber]['deliveryemail']; 
		$markerArray['###GIFT_NOTE###'] = $this->basketExt['gift'][$giftnumber]['note'];
//		$markerArray['###FIELD_NAME###']='ttp_basket['.$row['uid'].'][quantity]'; // here again, because this is here in ITEM_LIST view
//		$markerArray['###FIELD_QTY###'] =  '';
		
		$markerArray['###FIELD_NAME_PERSON_NAME###']='ttp_gift[personname]';
		$markerArray['###FIELD_NAME_PERSON_EMAIL###']='ttp_gift[personemail]';
		$markerArray['###FIELD_NAME_DELIVERY_NAME###']='ttp_gift[deliveryname]';
		$markerArray['###FIELD_NAME_DELIVERY_EMAIL###']='ttp_gift[deliveryemail]';
		$markerArray['###FIELD_NAME_GIFT_NOTE###']='ttp_gift[note]';
		
		return $markerArray;
	} // addGiftMarkers


	/**
	 * Generates a radio or selector box for payment shipping
	 */
	function generateRadioSelect($key)	{
			/*
			 The conf-array for the payment/shipping configuration has numeric keys for the elements
			 But there are also these properties:

			 	.radio 		[boolean]	Enables radiobuttons instead of the default, selector-boxes
			 	.wrap 		[string]	<select>|</select> - wrap for the selectorboxes.  Only if .radio is false. See default value below
			 	.template	[string]	Template string for the display of radiobuttons.  Only if .radio is true. See default below

			 */
			 
		$type=$this->conf[$key.'.']['radio'];
		$active = $this->basketExtra[$key];
		$confArr = $this->cleanConfArr($this->conf[$key.'.']);
		$out='';

		$template = $this->conf[$key.'.']['template'] ? ereg_replace('\' *\. *\$key *\. *\'',$key, $this->conf[$key.'.']['template']) : '<nobr>###IMAGE### <input type="radio" name="recs[tt_products]['.$key.']" onClick="submit()" value="###VALUE###"###CHECKED###> ###TITLE###</nobr><BR>';

		$wrap = $this->conf[$key."."]["wrap"] ? $this->conf[$key."."]["wrap"] :'<select name="recs[tt_products]['.$key.']" onChange="submit()">|</select>';

		while(list($key,$val)=each($confArr))	{
			if (($val['show'] || !isset($val['show'])) &&
				(doubleval($val['showLimit']) >= doubleval($this->calculatedArray['count']) || !isset($val['showLimit']) ||
				 intval($val['showLimit']) == 0)) {
				if ($type)	{	// radio
					$markerArray=array();
					$markerArray['###VALUE###']=intval($key);
					$markerArray['###CHECKED###']=(intval($key)==$active?' checked':'');
					$markerArray['###TITLE###']=$val['title'];
					$markerArray['###IMAGE###']=$this->cObj->IMAGE($val['image.']);
					$out.=$this->cObj->substituteMarkerArrayCached($template, $markerArray);
				} else {
					$out.='<option value="'.intval($key).'"'.(intval($key)==$active?' selected':'').'>'.htmlspecialchars($val['title']).'</option>';
				}
			}
		}
		if (!$type)	{
			$out=$this->cObj->wrap($out,$wrap);
		}
		return $out;
	} // generateRadioSelect


	function cleanConfArr($confArr,$checkShow=0)	{
		$outArr=array();
		if (is_array($confArr))	{
			reset($confArr);
			while(list($key,$val)=each($confArr))	{
				if (!t3lib_div::testInt($key) && intval($key) && is_array($val) && (!$checkShow || $val['show'] || !isset($val['show'])))	{
					$outArr[intval($key)]=$val;
				}
			}
		}
		ksort($outArr);
		reset($outArr);
		return $outArr;
	} // cleanConfArr


	function GetPaymentShippingData(
			$countTotal,
/* Added Els: necessary to calculate shipping price which depends on total no-tax price */
			&$priceTotalNoTax,
			&$priceShippingTax,
			&$priceShippingNoTax,
			&$pricePaymentTax,
			&$pricePaymentNoTax
			) {
		global $TSFE;

			// shipping
		$priceShipping = $priceShippingTax = $priceShippingNoTax = 0;
		$confArr = $this->basketExtra['shipping.']['priceTax.'];
		$tax = doubleVal($this->conf['shipping.']['TAXpercentage']);

		if ($confArr) {
	        $minPrice=0;
	        if ($this->basketExtra['shipping.']['priceTax.']['WherePIDMinPrice.']) {
	                // compare PIDList with values set in priceTaxWherePIDMinPrice in the SETUP
	                // if they match, get the min. price
	                // if more than one entry for priceTaxWherePIDMinPrice exists, the highest is value will be taken into account
	            foreach ($this->basketExtra['shipping.']['priceTax.']['WherePIDMinPrice.'] as $minPricePID=>$minPriceValue) {
	                if (is_array($this->itemArray[$pid]) && $minPrice<doubleval($minPriceValue)) {
	                    $minPrice=$minPriceValue;
	                }
	            }
	        }
	
			krsort($confArr);
			reset($confArr);

			if ($confArr['type'] == 'count') {
				while (list ($k1, $price1) = each ($confArr)) {
					if ($countTotal >= intval($k1)) {
						$priceShipping = $price1;
						break;
					}
				}
			} else if ($confArr['type'] == 'weight') {
				while (list ($k1, $price1) = each ($confArr)) {
					if ($this->calculatedArray['weight'] * 1000 >= intval($k1)) {
						$priceShipping = $price1;
						break;
					}
				}
			/* Added Els: shipping price (verzendkosten) depends on price of goodstotal */
			} else if ($confArr['type'] == 'price') {
				while (list ($k1, $price1) = each ($confArr)) {
					if ($priceTotalNoTax >= intval($k1)) {
						$priceShipping = $price1;
						break;
					}
				}
			}
			// compare the price to the min. price
			if ($minPrice > $priceShipping) {
				$priceShipping = $minPrice;
			} 

			$priceShippingTax = $this->getPrice($priceShipping,1,$tax);
			$priceShippingNoTax = $this->getPrice($priceShipping,0,$tax);
		} else {
			$priceShippingTax = doubleVal($this->basketExtra['shipping.']['priceTax']);
			$priceShippingNoTax = doubleVal($this->basketExtra['shipping.']['priceNoTax']);
		}
		

		$perc = doubleVal($this->basketExtra['shipping.']['percentOfGoodstotal']);
		if ($perc)	{
			$priceShipping = doubleVal(($this->calculatedArray['priceTax']['goodstotal']/100)*$perc);
			$dum = $this->getPrice($priceShipping,1,$tax);
			$priceShippingTax = $priceShippingTax + $this->getPrice($priceShipping,1,$tax);
			$priceShippingNoTax = $priceShippingNoTax + $this->getPrice($priceShipping,0,$tax);
		}

		$weigthFactor = doubleVal($this->basketExtra['shipping.']['priceFactWeight']);
		if($weigthFactor > 0) {
			$priceShipping = $this->calculatedArray['weight'] * $weigthFactor;
			$priceShippingTax += $this->getPrice($priceShipping,1,$tax);
			$priceShippingNoTax += $this->getPrice($priceShipping,0,$tax);
		}

		if ($this->basketExtra['shipping.']['calculationScript'])	{
			$calcScript = $TSFE->tmpl->getFileName($this->basketExtra['shipping.']['calculationScript']);
			if ($calcScript)	{
				$this->includeCalcScript($calcScript,$this->basketExtra['shipping.']['calculationScript.']);
			}
		}

			// Payment
		$pricePayment = $pricePaymentTax = $pricePaymentNoTax = 0;
		// TAXpercentage replaces priceNoTax
		$tax = doubleVal($this->conf['payment.']['TAXpercentage']);

		$pricePaymentTax = $this->getValue($this->basketExtra['payment.']['priceTax'],
		                  		$this->basketExtra['payment.']['priceTax.'],
		                  		$this->calculatedArray['count']);
		if ($tax) {
			$pricePaymentNoTax = $this->getPrice($pricePaymentTax,0,$tax);

		} else {
			$pricePaymentNoTax = $this->getValue($this->basketExtra['payment.']['priceNoTax'],
		                  		$this->basketExtra['payment.']['priceNoTax.'],
		                  		$this->calculatedArray['count']);
		}

		$perc = doubleVal($this->basketExtra['payment.']['percentOfTotalShipping']);
		if ($perc)	{

			$payment = ($this->calculatedArray['priceTax']['goodstotal'] + $this->calculatedArray['priceTax']['shipping'] ) * doubleVal($perc);

			$pricePaymentTax = $this->getPrice($payment,1,$tax);
			$pricePaymentNoTax = $this->getPrice($payment,0,$tax);
		}

		$perc = doubleVal($this->basketExtra['payment.']['percentOfGoodstotal']);
		if ($perc)	{
			$pricePaymentTax += ($this->calculatedArray['priceTax']['goodstotal']/100)*$perc;
			$pricePaymentNoTax += ($this->calculatedArray['priceNoTax']['goodstotal']/100)*$perc;
		}

		if ($this->basketExtra['payment.']['calculationScript'])	{
			$calcScript = $TSFE->tmpl->getFileName($this->basketExtra['payment.']['calculationScript']);
			if ($calcScript)	{
				$this->includeCalcScript($calcScript,$this->basketExtra['payment.']['calculationScript.']);
			}
		}

	} // GetPaymentShippingData



	function getValue(&$basketElement, $basketProperties, $countTotal)
	{
		$result = 0;

		// to remain downwards compatible
		if (is_string($basketElement))	{
        	$result = $basketElement;
        }

		if(is_array($basketProperties) && count($basketProperties) > 0) {
			foreach ($basketProperties as $lowKey => $lowValue)	{
				if (strlen($lowKey) > 0 && $countTotal >= $lowKey)	{
					$result = doubleVal($lowValue);
				}
			}
		}

		return $result;
	} // getValue


	function &getItem (&$row) {
		$count = intval($this->basketExt[$row['uid']][tx_ttproducts_article_div::getVariantFromRow ($row)]);
		$priceTax = $this->getResellerPrice($row,1);
		$priceNoTax = $this->getResellerPrice($row,0);
		$item = array (
			'calcprice' => 0,
			'count' => $count,
			'priceTax' => $priceTax,
			'priceNoTax' => $priceNoTax,
			'totalTax' => 0,
			'totalNoTax' => 0,
			'rec' => $row,
			);
		return $item;
	}

	// This calculates the total for everything
	function getCalculateSums () {
		$this->calculatedArray['priceTax']['total'] = $this->calculatedArray['priceTax']['goodstotal'];
		$this->calculatedArray['priceTax']['total']+= $this->calculatedArray['priceTax']['payment'];
		$this->calculatedArray['priceTax']['total']+= $this->calculatedArray['priceTax']['shipping'];
/* Added Els: $this->calculatedArray['priceTax']['creditpoints'] and coucher */
		$this->calculatedArray['priceTax']['total']-= $this->calculatedArray['priceTax']['creditpoints'];
		$this->calculatedArray['priceTax']['total']-= $this->calculatedArray['priceTax']['voucher'];

		$this->calculatedArray['priceNoTax']['total']  = $this->calculatedArray['priceNoTax']['goodstotal'];
		$this->calculatedArray['priceNoTax']['total'] += $this->calculatedArray['priceNoTax']['payment'];
		$this->calculatedArray['priceNoTax']['total'] += $this->calculatedArray['priceNoTax']['shipping'];
	} // getItem
	
/* mkl:
	function cleanConfArr($confArr,$checkShow=0)	{
		$outArr=array();
		if (is_array($confArr))	{
			reset($confArr);
			while(list($key,$val)=each($confArr))	{
				if (!t3lib_div::testInt($key) && intval($key) && is_array($val) && (!$checkShow || $val['show'] || !isset($val['show'])))	{
					$outArr[intval($key)]=$val;
				}
			}
		}
		ksort($outArr);
		reset($outArr);
		return $outArr;
	}
*/




	function getVariantSubpartArray (&$subpartArray, &$row, &$tempContent, $condition)  {
		if ($condition) {
			if (trim($row['color']) != '')
				$subpartArray['###display_variant1###'] = $this->cObj->getSubpart($tempContent,'###display_variant1###');
			if (trim($row['size']) != '')
				$subpartArray['###display_variant2###'] = $this->cObj->getSubpart($tempContent,'###display_variant2###');
			if (trim($row['accessory']) != '0')
				$subpartArray['###display_variant3###'] = $this->cObj->getSubpart($tempContent,'###display_variant3###');
			if (trim($row['gradings']) != '')
				$subpartArray['###display_variant4###'] = $this->cObj->getSubpart($tempContent,'###display_variant4###');
		}
		if (trim($row['color']) == '')
			$subpartArray['###display_variant1###'] =  '';
		if (trim($row['size']) == '')
			$subpartArray['###display_variant2###'] =  '';
		if (trim($row['accessory']) == '0')
			$subpartArray['###display_variant3###'] = '';
		if (trim($row['gradings']) == '')
			$subpartArray['###display_variant4###'] = '';
		
	}





	// **************************
	// ORDER related functions
	// **************************

	/**
	 * Create a new order record
	 *
	 * This creates a new order-record on the page with pid, .PID_sys_products_orders. That page must exist!
	 * Should be called only internally by eg. getBlankOrderUid, that first checks if a blank record is already created.
	 */
	function createOrder()	{
		global $TSFE;

		$newId = 0;
		$pid = intval($this->conf['PID_sys_products_orders']);
		if (!$pid)	$pid = intval($TSFE->id);

		if ($TSFE->sys_page->getPage_noCheck ($pid))	{
			$advanceUid = 0; 
			if ($this->conf['advanceOrderNumberWithInteger'] || $this->conf['alwaysAdvanceOrderNumber'])	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', '', '', 'uid DESC', '1');
				list($prevUid) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

				if ($this->conf['advanceOrderNumberWithInteger']) {
					$rndParts = explode(',',$this->conf['advanceOrderNumberWithInteger']);
					$advanceUid = $prevUid+t3lib_div::intInRange(rand(intval($rndParts[0]),intval($rndParts[1])),1);
				} else {
					$advanceUid = $prevUid + 1;
				}
			}

			$insertFields = array(
				'pid' => $pid,
				'tstamp' => time(),
				'crdate' => time(),
				'deleted' => 1
			);
			if ($advanceUid > 0)	{
				$insertFields['uid'] = $advanceUid;
			}

			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_products_orders', $insertFields);

			$newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
		}
		return $newId;
	} // createOrder

	/**
	 * Returns a blank order uid. If there was no order id already, a new one is created.
	 *
	 * Blank orders are marked deleted and with status=0 initialy. Blank orders are not necessarily finalized because users may abort instead of buying.
	 * A finalized order is marked 'not deleted' and with status=1.
	 * Returns this uid which is a blank order record uid.
	 */
	function getBlankOrderUid()	{
		global $TSFE;

	// an new orderUid has been created always because also payment systems can be used which do not accept a duplicate order id
		$orderUid = intval($this->recs['tt_products']['orderUid']);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', 'uid='.intval($orderUid).' AND deleted AND NOT status');	// Checks if record exists, is marked deleted (all blank orders are deleted by default) and is not finished.
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res) || $this->conf['alwaysAdvanceOrderNumber'])	{
			$orderUid = $this->createOrder();
			$this->recs['tt_products']['orderUid'] = $orderUid;
			$this->recs['tt_products']['orderDate'] = time();
			$this->recs['tt_products']['orderTrackingNo'] = $this->getOrderNumber($orderUid).'-'.strtolower(substr(md5(uniqid(time())),0,6));
			$TSFE->fe_user->setKey('ses','recs',$this->recs);
		}
		return $orderUid;
	} // getBlankOrderUid

	/**
	 * Returns the orderRecord if $orderUid.
	 * If $tracking is set, then the order with the tracking number is fetched instead.
	 */
	function getOrderRecord($orderUid,$tracking='')	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_products_orders', ($tracking ? 'tracking_code="'.$GLOBALS['TYPO3_DB']->quoteStr($tracking, 'sys_products_orders').'"' : 'uid='.intval($orderUid)).' AND NOT deleted');
		return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	} //getOrderRecord

	/**
	 * This returns the order-number (opposed to the order_uid) for display in the shop, confirmation notes and so on.
	 * Basically this prefixes the .orderNumberPrefix, if any
	 */
	function getOrderNumber($orderUid)	{
		$orderNumberPrefix = substr($this->conf['orderNumberPrefix'],0,10);
		if ($orderNumberPrefix[0]=='%')
			$orderNumberPrefix = date(substr($orderNumberPrefix, 1));
		return $orderNumberPrefix.$orderUid;
	} // getOrderNumber

	
	/**
	 * Returns the number of creditpoints for the frontend user
	 */
	function getCreditPoints($amount)	{
		$type = '';
		$where = '';
		$creditpoints = 0;
		foreach ($this->conf['creditpoints.'] as $k1=>$priceCalcTemp) {
			if (!is_array($priceCalcTemp)) {
				switch ($k1) {
					case 'type':
						$type = $priceCalcTemp;
						break;
					case 'where':
						$where = $priceCalcTemp;
						break;
				}
				continue;
			}
			$dumCount = 0;
			$creditpoints = doubleval($priceCalcTemp['prod.']['1']);
		
			if ($type != 'price') {
				break;
			}
			krsort($priceCalcTemp['prod.']);
			reset($priceCalcTemp['prod.']);

			foreach ($priceCalcTemp['prod.'] as $k2=>$points) {
				if ($amount >= intval($k2)) { // only the highest value for this count will be used; 1 should never be reached, this would not be logical
					$creditpoints = $points;		
					break; // finish
				}
			}
		}
		return $creditpoints;
	} // getCreditPoints


	
	/**
	 * adds the number of creditpoints for the frontend user
	 */
	function addCreditPoints($username, $creditpoints)	{
		$uid_voucher = '';
	    // get the "old" creditpoints for the user
	    $res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, tt_products_creditpoints', 'fe_users', 'username="'.$username.'"');
	    if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res1)) {
	        $ttproductscreditpoints = $row['tt_products_creditpoints'];
	        $uid_voucher = $row['uid'];
	    }
	    $fieldsArrayFeUserCredit = array();
	    $fieldsArrayFeUserCredit['tt_products_creditpoints'] = $ttproductscreditpoints + $creditpoints;
	    $GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$uid_voucher, $fieldsArrayFeUserCredit);
	}



	/**
	 * Finalize an order
	 *
	 * This finalizes an order by saving all the basket info in the current order_record.
	 * A finalized order is then marked 'not deleted' and with status=1
	 * The basket is also emptied, but address info is preserved for any new orders.
	 * $orderUid is the order-uid to finalize
	 * $mainMarkerArray is optional and may be pre-prepared fields for substitutiong in the template.
	 */
	function finalizeOrder($orderUid, $orderConfirmationHTML)	{
		global $TSFE;
		global $TYPO3_DB;
		
		$content = '';

			// Fix delivery address
		$this->mapPersonIntoToDelivery();	// This maps the billing address into the blank fields of the delivery address
//		$mainMarkerArray['###EXTERNAL_COBJECT###'] = $this->externalCObject.'';
//		$orderConfirmationHTML=trim(tx_ttproducts_basket_div::getBasket('###BASKET_ORDERCONFIRMATION_TEMPLATE###','',$mainMarkerArray));		// Getting the template subpart for the order confirmation!

		#debug ($this->basketExt, '$this->basketExt', __LINE__, __FILE__);
	
			// Saving order data
		$fieldsArray=array();
		$fieldsArray['note']=$this->deliveryInfo['note'];
/* Added Els: introduce a field into sys_products_orders containing the uid of the fe_user */
		$fieldsArray['feusers_uid']=$this->personInfo['feusers_uid'];
		$fieldsArray['name']=$this->deliveryInfo['name'];
		$fieldsArray['telephone']=$this->deliveryInfo['telephone'];
		$fieldsArray['fax']=$this->deliveryInfo['fax'];
		$fieldsArray['email']=$this->deliveryInfo['email'];
//		debug ($this->conf['email_notify_default'], "this->conf['email_notify_default']", __LINE__, __FILE__);
		$fieldsArray['email_notify']=  $this->conf['email_notify_default'];		// Email notification is set here. Default email address is delivery email contact

			// can be changed after order is set.
		$fieldsArray['payment']=$this->basketExtra['payment'].': '.$this->basketExtra['payment.']['title'];
		$fieldsArray['shipping']=$this->basketExtra['shipping'].': '.$this->basketExtra['shipping.']['title'];
		$fieldsArray['amount']=$this->calculatedArray['priceTax']['total'];
		$fieldsArray['status']=1;	// This means, "Order confirmed on website, next step: confirm from shop that order is received"

/* Added Els: update fe_user with amount of creditpoints and subtract creditpoints used in order*/
		$fieldsArrayFeUsers = array();
		$uid_voucher = ''; // define it here
		/* example:
  creditpoints {
  10.where =
  10.type = price
  10.prod.1   = 0.02
  10.prod.101 = 0.04
  10.prod.501 = 0.06
}
		 */
		if ($this->conf['creditpoints.']) {
			$creditpoints = $this->getCreditPoints($fieldsArray['amount']);
			
			$fieldsArrayFeUsers['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'] + ($creditpoints * $this->calculatedArray['priceTax']['total']) - $this->recs['tt_products']['creditpoints'];
		}

/* Added Els: update fe_user with vouchercode */
		if ($this->recs['tt_products']['vouchercode'] != '') {
			// first check if vouchercode exist and is not their own vouchercode
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'fe_users', 'username="'.$this->recs['tt_products']['vouchercode'].'"');
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$uid_voucher = $row['uid'];
			}
			if (($uid_voucher != '') && ($this->deliveryInfo['feusers_uid'] != $uid_voucher) ) {
				$fieldsArrayFeUsers['tt_products_vouchercode'] = $this->recs['tt_products']['vouchercode'];
			}

		}

		if ($this->deliveryInfo['feusers_uid']) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$this->deliveryInfo['feusers_uid'], $fieldsArrayFeUsers);
	/* Added ELS2: update user from vouchercode with 5 credits */
			$this->addCreditPoints($this->recs['tt_products']['vouchercode'], 5);
		}


/*
		//<-- MKL 2004.09.21
		$fieldsArray['forename']=$this->personInfo['forename'];
		$fieldsArray['company']=$this->personInfo['company'];
		$fieldsArray['vat_id']=$this->personInfo['vat_id'];
		$fieldsArray['street']=$this->deliveryInfo['street'];
		$fieldsArray['street_n1']=$this->deliveryInfo['street_n1'];
		$fieldsArray['street_n2']=$this->deliveryInfo['street_n2'];
		$fieldsArray['city']=$this->deliveryInfo['city'];
		$fieldsArray['zip']=$this->deliveryInfo['zip'];
		$fieldsArray['country_code']=$this->personInfo['country_code'];
		$fieldsArray['client_ip']=t3lib_div::getIndpEnv('REMOTE_ADDR');
		//--> MKL 2004.09.21
*/

				// Default status_log entry
		$status_log=array();
		$status_log[] = array(
			'time' => time(),
			'info' => $this->conf['statusCodes.'][$fieldsArray['status']],
			'status' => $fieldsArray['status'],
			'comment' => $this->deliveryInfo['note']
		);
		$fieldsArray['status_log']=serialize($status_log);

			// Order Data serialized
		$fieldsArray['orderData']=serialize(array(
				'html_output' 			=>	$orderConfirmationHTML,
				'deliveryInfo' 			=>	$this->deliveryInfo,
				'personInfo' 			=>	$this->personInfo,
				'itemArray'				=>	$this->itemArray,
				'calculatedArray'		=>	$this->calculatedArray
		));

			// Setting tstamp, deleted and tracking code
		$fieldsArray['tstamp']=time();
		$fieldsArray['deleted']=0;
		$fieldsArray['tracking_code']=$this->recs['tt_products']['orderTrackingNo'];
		$fieldsArray['agb']		= $this->personInfo['agb'];
/* Added Els: write creditpointvalue into sys_products_order */
		$fieldsArray['creditpoints'] = $this->recs['tt_products']['creditpoints'];

			// Saving the order record
		$TYPO3_DB->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderUid), $fieldsArray);

		#debug ($this->basketExt['gift'], '$this->basketExt[\'gift\']', __LINE__, __FILE__); // $this->basketExt['gift'][$this->giftnumber]['item'][$uid][$extVars] = $count;
		// any gift orders in the extended basket?
		if ($this->basketExt['gift']) {
			$pid = intval($this->conf['PIDGiftsTable']);
			if (!$pid)	$pid = intval($TSFE->id);
			
			foreach ($this->basketExt['gift'] as $giftnumber => $rec) {
				$amount = 0;
				foreach ($rec['item'] as $productid => $product) {
					foreach ($product as $variant => $count) {
						$row = array();
						tx_ttproducts_article_div::getRowFromVariant ($row, $variant);
						$amount += intval($row['size']) * $count;
					}
				}
				// Saving gift order data
				$insertFields = array(
					'pid' => $pid,
					'tstamp' => time(),
					'crdate' => time(),
					'deleted' => 0,
					
					'ordernumber'    => $orderUid,
					'personname'     => $rec['personname'],
					'personemail'    => $rec['personemail'],
					'deliveryname'   => $rec['deliveryname'],
					'deliveryemail'  => $rec['deliveryemail'],
					'note'           => $rec['note'],
					'amount'         => $amount
				);
				// Saving the gifts order record
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_products_gifts', $insertFields);
				$newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
				$insertFields = array();
				$insertFields['uid_local'] = $newId;
					
				foreach ($rec['item'] as $productid => $product) {
					#debug ($product, 'product', __LINE__, __FILE__);
					foreach ($product as $variant => $count) {
						$row = array();
						tx_ttproducts_article_div::getRowFromVariant ($row, $variant);
						
						$query='uid_product=\''.intval($productid).'\' AND color=\''.$row['color'].'\' AND size=\''.$row['size'].'\' AND gradings=\''.$row['gradings'].'\'' ;
						$articleRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tt_products_articles', $query);
						
						if ($articleRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($articleRes)) {
							$insertFields['uid_foreign'] = $articleRow['uid'];
							$insertFields['count'] = $count;
							// Saving the gifts mm order record
							$GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_products_gifts_articles_mm', $insertFields);
						}
					}
				}
			}
		}

			// Fetching the orderRecord by selecing the newly saved one...
		$this->orderRecord = $this->getOrderRecord($orderUid);
		$content .= tx_ttproducts_basket_div::getBasket('###BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE###');
		

		// Is no user is logged in --> create one
		if ($this->conf['createUsers'] && $this->personInfo['email'] != '' && $this->conf['PIDuserFolder'] && (trim($TSFE->fe_user->user['username']) == ''))
		{
			$username = strtolower(trim($this->personInfo['email']));

			$res = $TYPO3_DB->exec_SELECTquery('username', 'fe_users', 'username="'.$username . '" AND deleted=0');
			$num_rows = $TYPO3_DB->sql_num_rows($res);

			if (!$num_rows)
			{
				$this->password = substr(md5(rand()), 0, 6);

				$insertFields = array(
					'pid' => $this->conf['PIDuserFolder'],
					'tstamp' => time(),
					'username' => $username,
					'password' => $this->password,
					'usergroup' => $this->conf['memberOfGroup'],
/* Added Els: introduce a field into sys_products_orders containing the uid of the fe_user */
					'uid' => $this->personInfo['feusers_uid'],
					'company' => $this->personInfo['company'],
					'name' => $this->personInfo['name'],
					'address' => $this->personInfo['address'],
					'telephone' => $this->personInfo['telephone'],
					'fax' => $this->personInfo['fax'],
					'email' => $this->personInfo['email'],
					'zip' => $this->personInfo['zip'],
					'city' => $this->personInfo['city'],
					'crdate' => time()
				);

				$countryKey = ($this->conf['useStaticInfoCountry'] ? 'static_info_country':'country');
				$insertFields[$countryKey] =  $this->personInfo['country'];
				
				$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('fe_users', $insertFields);
				// send new user mail
				if (count($this->personInfo['email'])) {
					$emailContent=trim(tx_ttproducts_basket_div::getBasket('###EMAIL_NEWUSER_TEMPLATE###'));
					if ($emailContent) {
						$parts = split(chr(10),$emailContent,2);
						$subject=trim($parts[0]);
						$plain_message=trim($parts[1]);

						$this->send_mail($this->personInfo['email'], $subject, $plain_message, $this->conf['orderEmail_from'], $this->conf['orderEmail_fromName']);
					}
				}
			}
		}


		if (!$this->conf['AlwaysInStock']) {
			// Reduce inStock
			reset($this->itemArray);
			
			#debug ($this->itemArray, '$this->itemArray Reduce in stock', __LINE__, __FILE__);

			// loop over all items in the basket indexed by page and itemnumber
			foreach ($this->itemArray as $pid=>$pidItem) {
				foreach ($pidItem as $itemnumber=>$actItemArray) {
					#error_Log ("tt_products  $this->conf['useArticles'] = ".$this->conf['useArticles']);
					if ($this->conf['useArticles']) {
						foreach ($actItemArray as $k1=>$actItem) {
							$query='uid_product=\''.intval($actItem['rec']['uid']).'\' AND color=\''.$actItem['rec']['color'].'\' AND size=\''.$actItem['rec']['size'].'\' AND gradings=\''.$actItem['rec']['gradings'].'\'';
							
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('inStock', 'tt_products_articles', $query);
								// 	TODO: Saving the order record support color, size and accessory here
						}
					} else {	
						$query='uid=\''.intval($actItemArray[0]['rec']['uid']).'\'';

						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('inStock', 'tt_products', $query);
		
						if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							if ($row['inStock'] > 0) {
								$newInStock = intval($row['inStock'])-intval($actItemArray[0]['count']);
								if ($newInStock < 0) {
									$newInStock = 0;
								}
		
								$fieldsArray =array();
											// Setting tstamp, deleted and tracking code
								$fieldsArray['inStock']=$newInStock;
								
								$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_products', 'uid='.intval($actItemArray[0]['rec']['uid']), $fieldsArray);
							}
						}
					}
				}
			}
		}

			// Creates M-M relations for the products with tt_products table. Isn't really used yet, but later will be used to display stock-status by looking up how many items are already ordered.
			// First: delete any existing. Shouldn't be any
		$where='sys_products_orders_uid='.$orderUid;
		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_products_orders_mm_tt_products',$where);
	
			// Second: Insert a new relation for each ordered item
		reset($this->itemArray);

		// loop over all items in the basket indexed by page and itemnumber
		foreach ($this->itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				if ($this->conf['useArticles']) {
					foreach ($actItemArray as $k1=>$actItem) {
						// get the article uid with these colors, sizes and gradings
						$query='uid_product=\''.intval($actItem['rec']['uid']).'\' AND color=\''.$actItem['rec']['color'].'\' AND size=\''.$actItem['rec']['size'].'\' AND gradings=\''.$actItem['rec']['gradings'].'\'';
							
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tt_products_articles', $query);
		
						if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							$insertFields = array (
								'sys_products_orders_uid' => $orderUid,
								'sys_products_orders_qty' => intval($actItemArray[0]['count']),
								'tt_products_uid' => intval($actItemArray[0]['rec']['uid']),
								'tt_products_articles_uid' => intval($row['uid'])
							);	
							$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
						}						
					}
				} else {
					$insertFields = array (
						'sys_products_orders_uid' => $orderUid,
						'sys_products_orders_qty' => intval($actItemArray[0]['count']),
						'tt_products_uid' => intval($actItemArray[0]['rec']['uid'])
					);	// TODO: differentiate between colors, sizes, gradings and accessory
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
				}
			}
		}

		// Generate CSV for each order
		if ($this->conf['generateCSV'])
		{
			$csvfilepath = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') .'/'. $this->conf['CSVdestination'];
			if ($csvfilepath[strlen($csvfilepath)-1] != '/') {
				$csvfilepath .= '/';
			}
			$csvfilepath .= $this->getOrderNumber($this->recs['tt_products']['orderUid']).'.csv';
			$csvfile = fopen($csvfilepath, 'w');
			if ($csvfile !== FALSE)
			{
				// Generate invoice and delivery address
				$csvlinehead = '';
				$csvlineperson = '';
				$csvlinedelivery = '';
/* Added Els: 'feusers_uid,' */
				$infoFields = explode(',','feusers_uid,name,address,telephone,fax,email,company,city,zip,state,country,kk_fa,kk_nr,kk_ablauf,kk_pruefcode,agb');
				while(list(,$fName)=each($infoFields)) {
					if ($csvlinehead != '') {
						$csvlinehead .= ';';
						$csvlineperson .= ';';
						$csvlinedelivery .= ';';
					}
					$csvlinehead .= '"' . $fName . '"';
					$csvlineperson .= '"' . str_replace('\r\n', '|', $this->personInfo[$fName]) . '"';
					$csvlinedelivery .= '"' . $this->deliveryInfo[$fName] . '"';
				}

				// Generate shipping/payment information and delivery note
				$csvlineshipping = '"' . $this->basketExtra['shipping.']['title'] . '";"' .
					$this->priceFormat($this->calculatedArray['priceTax']['shipping']) . '";"' .
					$this->priceFormat($this->calculatedArray['priceNoTax']['shipping']) . '"';

				$csvlinepayment = '"' . $this->basketExtra['payment.']['title'] . '";"' .
					$this->priceFormat($this->calculatedArray['priceTax']['payment']) . '";"' .
					$this->priceFormat($this->calculatedArray['priceNoTax']['payment']) . '"';

				$csvlinedeliverynote = '"'.$this->deliveryInfo['note'].'"';

				// Build field list
				$csvfields = explode(',', $this->conf['CSVfields']);
				$csvfieldcount = count($csvfields);
				for ($a=0;$a<$csvfieldcount;$a++)
					$csvfields[$a] = trim($csvfields[$a]);

				// Write description header
				$csvdescr = '"uid";"count";"color";"size";"accessory";"gradings"';
				reset($csvfields);
				foreach($csvfields as $csvfield)
					$csvdescr .= ';"'.$csvfield.'"';
				if ($this->conf['CSVinOneLine'])
				{
					$csvdescr .= ';"deliverynote";"shipping method";"shipping_price";"shipping_no_tax";"payment method";"payment_price";"payment_no_tax"';
					$csvdescr .= ';'.$csvlinehead.';'.$csvlinehead;
				}
				$csvdescr .= '\n';
				fwrite($csvfile, $csvdescr);

				// Write ordered product list
				reset($this->itemArray);

				$infoWritten = false;
				// loop over all items in the basket indexed by page and itemnumber
				foreach ($this->itemArray as $pid=>$pidItem) {
					foreach ($pidItem as $itemnumber=>$actItemArray) {
						foreach ($actItemArray as $k1=>$actItem) {
							$variants = explode(';', $actItem['rec']['extVars']);
							$csvdata = '"'.intval($actItem['rec']['uid']).'";"'.
										intval($actItem['count']).'";"'.
										$variants[0].'";"'.
										$variants[1].'";"'.
										$variants[2]/100 .'";"'.
										$variants[3].'"';
							reset($csvfields);
							foreach($csvfields as $csvfield) {
								$csvdata .= ';"'.$actItem['rec'][$csvfield].'"';
							}
							if ($this->conf['CSVinOneLine'] && (!$infoWritten))	{
								$infoWritten = true;
								$csvdata .= ';'.$csvlinedeliverynote.';'.$csvlineshipping.';'.$csvlinepayment.';'.$csvlineperson.';'.$csvlinedelivery;
							}
							$csvdata .= '\n';
							fwrite($csvfile, $csvdata);
						}
					}
				}

				if (!$this->conf['CSVinOneLine']) {
					fwrite($csvfile, '\n');
					fwrite($csvfile, $csvlinehead . '\n');
					fwrite($csvfile, $csvlineperson . '\n');
					fwrite($csvfile, $csvlinedelivery . '\n');
					fwrite($csvfile, '\n');
					fwrite($csvfile, $csvlinedeliverynote. '\n');
					fwrite($csvfile, $csvlineshipping . '\n');
					fwrite($csvfile, $csvlinepayment . '\n');
				}

				fclose($csvfile);
			}
			else
				echo 'Warning: Cannot create CSV file \''.$csvfilepath.'\' for this order!';
		}

			// Sends order emails:
		$recipients = $this->conf['orderEmail_to'];
		$recipients.=','.$this->personInfo['email']; // former: deliveryInfo
		$recipients=t3lib_div::trimExplode(',',$recipients,1);

		if (count($recipients))	{	// If any recipients, then compile and send the mail.
			$emailContent=trim(tx_ttproducts_basket_div::getBasket('###EMAIL_PLAINTEXT_TEMPLATE###'));
			if ($emailContent)	{		// If there is plain text content - which is required!!
				$parts = split(chr(10),$emailContent,2);		// First line is subject
				$subject=trim($parts[0]);
				$plain_message=trim($parts[1]);

				$cls  = t3lib_div::makeInstanceClassName('tx_ttproducts_htmlmail');
				if (class_exists($cls) && $this->conf['orderEmail_htmlmail'])	{	// If htmlmail lib is included, then generate a nice HTML-email
					$HTMLmailShell=$this->cObj->getSubpart($this->templateCode,'###EMAIL_HTML_SHELL###');
					$HTMLmailContent=$this->cObj->substituteMarker($HTMLmailShell,'###HTML_BODY###',$orderConfirmationHTML);
					$HTMLmailContent=$this->cObj->substituteMarkerArray($HTMLmailContent, $this->globalMarkerArray);


						// Remove image tags to products:
					if ($this->conf['orderEmail_htmlmail.']['removeImagesWithPrefix'])	{
						$parser = t3lib_div::makeInstance('t3lib_parsehtml');
						$htmlMailParts = $parser->splitTags('img',$HTMLmailContent);

						reset($htmlMailParts);
						while(list($kkk,$vvv)=each($htmlMailParts))	{
							if ($kkk%2)	{
								list($attrib) = $parser->get_tag_attributes($vvv);
								if (t3lib_div::isFirstPartOfStr($attrib['src'],$this->conf['orderEmail_htmlmail.']['removeImagesWithPrefix']))	{
									$htmlMailParts[$kkk]='';
								}
							}
						}
						$HTMLmailContent=implode('',$htmlMailParts);
					}

					$V = array (
						'from_email' => $this->conf['orderEmail_from'],
						'from_name' => $this->conf['orderEmail_fromName'],
						'attachment' => ($this->conf['AGBattachment'] ? $this->conf['AGBattachment'] : '')
					);

					$Typo3_htmlmail = t3lib_div::makeInstance('tx_ttproducts_htmlmail');
					$Typo3_htmlmail->useBase64();
					$Typo3_htmlmail->start(implode($recipients,','), $subject, $plain_message, $HTMLmailContent, $V);
					$Typo3_htmlmail->sendtheMail();
				} else {		// ... else just plain text...
					// $headers variable überall entfernt!
					$this->send_mail($this->personInfo['email'], $subject, $plain_message, $this->conf['orderEmail_from'], $this->conf['orderEmail_fromName'], $this->conf['AGBattachment']);
					if ($this->conf['generateCSV'])
						$addcsv = $csvfilepath;
					else
						$addcsv = '';
					$this->send_mail($this->conf['orderEmail_to'], $subject, $plain_message, $this->personInfo['email'], $this->personInfo['name'], $addcsv);
				}
			}
		}

			// Empties the shopping basket!
		$TSFE->fe_user->setKey('ses','recs',tx_ttproducts_basket_div::getClearBasketRecord());
		$TSFE->fe_user->setKey('ses','basketExt',array());

			// This cObject may be used to call a function which clears settings in an external order system.
			// The output is NOT included anywhere
		$this->getExternalCObject('externalFinalizing');

		return $content;
	} // finalizeOrder


	// **************************
	// tracking information
	// **************************

	/**
	 * Returns 1 if user is a shop admin
	 */
	function shopAdmin()	{
		$admin=0;
		if ($GLOBALS['TSFE']->beUserLogin)	{
			if (t3lib_div::_GP('update_code')==$this->conf['update_code'])	{
				$admin= 1;		// Means that the administrator of the website is authenticated.
			}
		}
		return $admin;
	}

	/**
	 * Tracking administration
	 */
	function getTrackingInformation($orderRow, $templateCode)	{
			/*

					Tracking information display and maintenance.

					status-values are
						0:	Blank order
					1-1 Incoming orders
						1: 	Order confirmed at website
					2-49: Useable by the shop admin
				    	2 = Order is received and accepted by store
					    10 = Shop is awaiting goods from third-party
					    11 = Shop is awaiting customer payment
					    12 = Shop is awaiting material from customer
					    13 = Order has been payed    
					    20 = Goods shipped to customer
					    21 = Gift certificates shipped to customer
					    30 = Other message from store						
						...
					50-99:	Useable by the customer
					50-59: General user messages, may be updated by the ordinary users.
					    50 = Customer request for cancelling
					    51 = Message from customer to shop
					60-69:	Special user messages by the customer
				    	60 = Send gift certificate message to receiver
					
					100-299:  Order finalized.
					    100 = Order shipped and closed
					    101 = Order closed
					    200 = Order cancelled

					All status values can be altered only if you're logged in as a BE-user and if you know the correct code (setup as .update_code in TypoScript config)
			*/

		global $TSFE;

		$admin = $this->shopAdmin();
		
		if ($orderRow['uid'])	{
				// Initialize update of status...
			$fieldsArray = array();
			$orderRecord = t3lib_div::_GP('orderRecord');
			#debug ($orderRecord, '$orderRecord', __LINE__, __FILE__);
			if (isset($orderRecord['email_notify']))	{
				$fieldsArray['email_notify']=$orderRecord['email_notify'];
				$orderRow['email_notify'] = $fieldsArray['email_notify'];
			}
			if (isset($orderRecord['email']))	{
				$fieldsArray['email']=$orderRecord['email'];
				$orderRow['email'] = $fieldsArray['email'];
			}

			if (is_array($orderRecord['status']))	{
				$status_log = unserialize($orderRow['status_log']);
				reset($orderRecord['status']);
				$update=0;
				while(list(,$val)=each($orderRecord['status']))	{
					$status_log_element = array(
						'time' => time(),
						'info' => $this->conf['statusCodes.'][$val],
						'status' => $val,
						'comment' => $orderRecord['status_comment']
					);

					if ($admin || ($val>=50 && $val<59))	{// Numbers 50-59 are usermessages.
						$recipient = $this->conf['orderEmail_to'];
						if ($orderRow['email'] && ($orderRow['email_notify']))	{  
							$recipient .= ','.$orderRow['email'];
						}
						$templateMarker = 'TRACKING_EMAILNOTIFY_TEMPLATE';
						$this->sendNotifyEmail($recipient, $status_log_element, t3lib_div::_GP('tracking'), $orderRow, $templateCode, $templateMarker);
						
						$status_log[] = $status_log_element;
						$update=1;
					} else if ($val>=60 && $val<69) { //  60 -69 are special messages
						$templateMarker = 'TRACKING_EMAIL_GIFTNOTIFY_TEMPLATE';
						$query = 'ordernumber=\''.$orderRow['uid'].'\'';
						$giftRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products_gifts', $query);
						while ($giftRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($giftRes)) {
							$recipient = $giftRow['deliveryemail'].','.$giftRow['personemail'];
							$this->sendGiftEmail($recipient, $orderRecord['status_comment'], $giftRow, $templateCode, $templateMarker, $giftRow['personname'], $giftRow['personemail']);
						}
						$status_log[] = $status_log_element;
						$update=1;
					}
				}
				if ($update)	{
					$fieldsArray['status_log']=serialize($status_log);
					$fieldsArray['status']=$status_log_element['status'];
					if ($fieldsArray['status'] >= 100)	{

							// Deletes any M-M relations between the tt_products table and the order.
							// In the future this should maybe also automatically count down the stock number of the product records. Else it doesn't make sense.
						$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_products_orders_mm_tt_products', 'sys_products_orders_uid='.intval($orderRow['uid']));
					}
				}
			}

			if (count($fieldsArray))	{		// If any items in the field array, save them
				$fieldsArray['tstamp'] = time();

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderRow['uid']), $fieldsArray);

				$orderRow = $this->getOrderRecord($orderRow['uid']);
			}
		}

			// Getting the template stuff and initialize order data.
		$content=$this->cObj->getSubpart($templateCode,'###TRACKING_DISPLAY_INFO###');

		$status_log = unserialize($orderRow['status_log']);
		$orderData = unserialize($orderRow['orderData']);

		// added by Franz begin
		$orderPayed = false;
		$orderClosed = false;
		if (is_array($status_log)) {
			foreach($status_log as $key=>$val)	{
				#debug ($val, '$val', __LINE__, __FILE__);
				if ($val['status'] == 13)	{// Numbers 13 means order has been payed
					$orderPayed = true;
				}
				if ($val['status'] >= 100)	{// Numbers 13 means order has been payed
					$orderClosed = true;
					break;
				}
			}
		}
		
		
		// making status code 60 disappear if the order has not been payed yet
		if (!$orderPayed || $orderClosed) {
				// Fill marker arrays
			$markerArray=Array();
			$subpartArray=Array();
			$subpartArray['###STATUS_CODE_60###']= '';
	
			$content = $this->cObj->substituteMarkerArrayCached($content,$markerArray,$subpartArray);
		}
		
		// added by Franz end

			// Status:
		$STATUS_ITEM=$this->cObj->getSubpart($content,'###STATUS_ITEM###');
		$STATUS_ITEM_c='';
		if (is_array($status_log))	{
			reset($status_log);

			while(list($k,$v)=each($status_log))	{
				$markerArray=Array();
				$markerArray['###ORDER_STATUS_TIME###']=$this->cObj->stdWrap($v['time'],$this->conf['statusDate_stdWrap.']);
				$markerArray['###ORDER_STATUS###']=$v['status'];
				$markerArray['###ORDER_STATUS_INFO###']=$v['info'];
				$markerArray['###ORDER_STATUS_COMMENT###']=nl2br($v['comment']);
				
				$STATUS_ITEM_c.=$this->cObj->substituteMarkerArrayCached($STATUS_ITEM, $markerArray);
			}
		}

		$subpartArray=array();
		$subpartArray['###STATUS_ITEM###']=$STATUS_ITEM_c;

		$markerArray=Array();

			// Display admin-interface if access.
		if (!$TSFE->beUserLogin)	{
			$subpartArray['###ADMIN_CONTROL###']='';
		} elseif ($admin) {
			$subpartArray['###ADMIN_CONTROL_DENY###']='';
		} else {
			$subpartArray['###ADMIN_CONTROL_OK###']='';
		}
		if ($TSFE->beUserLogin)	{
				// Status admin:
			if (is_array($this->conf['statusCodes.']))	{
				reset($this->conf['statusCodes.']);
				while(list($k,$v)=each($this->conf['statusCodes.']))	{
					if ($k!=1)	{
						$markerArray['###STATUS_OPTIONS###'].='<option value="'.$k.'">'.htmlspecialchars($k.': '.$v).'</option>';
					}
				}
			}

				// Get unprocessed orders.
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,name,tracking_code,amount', 'sys_products_orders', 'NOT deleted AND status!=0 AND status<100', '', 'crdate');
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$markerArray['###OTHER_ORDERS_OPTIONS###'].='<option value="'.$row['tracking_code'].'">'.htmlspecialchars($this->getOrderNumber($row['uid']).': '.$row['name'].' ('.$this->priceFormat($row['amount']).' '.$this->conf['currencySymbol'].')').'</option>';
			}
		}

			// Final things
		$markerArray['###ORDER_HTML_OUTPUT###'] = $orderData['html_output'];		// The save order-information in HTML-format
		$markerArray['###FIELD_EMAIL_NOTIFY###'] = $orderRow['email_notify'] ? ' checked' : '';
		$markerArray['###FIELD_EMAIL###'] = $orderRow['email'];
		$markerArray['###ORDER_UID###'] = $this->getOrderNumber($orderRow['uid']);
		$markerArray['###ORDER_DATE###'] = $this->cObj->stdWrap($orderRow['crdate'],$this->conf['orderDate_stdWrap.']);
		$markerArray['###TRACKING_NUMBER###'] = t3lib_div::_GP('tracking');
		$markerArray['###UPDATE_CODE###'] = t3lib_div::_GP('update_code');

		$content= $this->cObj->substituteMarkerArrayCached($content, $markerArray, $subpartArray);
		return $content;
	} // getTrackingInformation



	/**
	 * Bill,Delivery Tracking
	 */
	function getInformation($type, $orderRow, $templateCode, $tracking)
	{
			/*

					Bill or delivery information display, which needs tracking code to be shown

   					This is extension information to tracking at another page
					See Tracking for further information
			*/
		global $TSFE;

			// initialize order data.
		$orderData = unserialize($orderRow['orderData']);

		//$this->initCategories();
		$basket = $orderData[''];

		$markerArray = array();
		$subpartArray = array();
		$wrappedSubpartArray = array();

		$this->itemArray = $orderData['itemArray'];
		$this->calculatedArray = $orderData['calculatedArray'];

		if ($type == 'bill')
		{
			$subpartMarker='###BILL_TEMPLATE###';
		}
		else
		{
			$subpartMarker='###DELIVERY_TEMPLATE###';
		}

			// Getting subparts from the template code.
		$t=array();
			// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
		$t['orderFrameWork'] = $this->cObj->getSubpart($templateCode,$this->spMarker($subpartMarker));

		$t['categoryTitle'] = $this->cObj->getSubpart($t['orderFrameWork'],'###ITEM_CATEGORY###');
		$t['itemFrameWork'] = $this->cObj->getSubpart($t['orderFrameWork'],'###ITEM_LIST###');
		$t['item'] = $this->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');

		$categoryQty = array();
//		$categoryPrice = array();
		$category = array();
//
//		reset($basket);
//		$countTotal = 0;
//
		// Calculate quantities for all categories
		// loop over all items in the basket sorted by page and itemnumber
		//foreach ($this->itemArray as $pid=>$pidItem) {
			//foreach ($pidItem as $itemnumber=>$actItem) {

		// loop over all items in the basket indexed by page and itemnumber
		foreach ($this->itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					$currentCategory=$actItem['rec']['category'];
					$category[$currentCategory] = 1;
	//			$countTotal += $actBasket['count'];
					$categoryQty[$currentCategory] += intval($actItem['count']);
	//			$categoryPrice[$currentCategory] += doubleval($actBasket['priceTax']) * intval($actBasket['count']);
				}
			}
		}
//			// Initialize traversing the items in the calculated basket
//
//		$this->GetPaymentShippingData(
//			$countTotal,
//			$priceShippingTax);

		reset($this->itemArray);
		reset($category);
		$itemsOut='';
		$out='';

		foreach ($category as $currentCategory=>$value)
		{
			$categoryChanged = 1;
//			foreach ($this->itemArray as $pid=>$pidItem) {
	//			foreach ($pidItem as $itemnumber=>$actItem) {

			// loop over all items in the basket indexed by page and itemnumber
			foreach ($this->itemArray as $pid=>$pidItem) {
				foreach ($pidItem as $itemnumber=>$actItemArray) {
					foreach ($actItemArray as $k1=>$actItem) {

							// Print Category Title
						if ($actItem['rec']['category']==$currentCategory)
						{
	
							if ($categoryChanged == 1)
							{
								$markerArray=array();
								$catTitle= ($this->categories[$currentCategory] ? $this->categories[$currentCategory]:'');
								$this->cObj->setCurrentVal($catTitle);
								$markerArray['###CATEGORY_TITLE###'] = $this->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
								$markerArray['###CATEGORY_QTY###'] = $categoryQty[$currentCategory];
	
								$markerArray['###PRICE_GOODS_TAX###']= $this->priceFormat($this->calculatedArray['categoryPriceTax']['goodstotal'][$currentCategory]);
								$markerArray['###PRICE_GOODS_NO_TAX###']= $this->priceFormat($this->calculatedArray['categoryPriceNoTax']['goodstotal'][$currentCategory]);
	
								$out2 = $this->cObj->substituteMarkerArray($t['categoryTitle'], $markerArray);
								$out.= $out2;
							}
	
								// Print Item Title
							$wrappedSubpartArray=array();
	/* Added Bert: in stead of listImage -> Image, reason: images are read from directory */
	//						$markerArray = $this->getItemMarkerArray ($actItem,$catTitle,1,'listImage');
							$markerArray = $this->getItemMarkerArray ($actItem,$catTitle,1,'image');
	
							$markerArray['###FIELD_QTY###'] = $actItem['count'];
	
							$itemsOut = $this->cObj->substituteMarkerArrayCached($t['item'],$markerArray,array(),$wrappedSubpartArray);
							if ($itemsOut)
							{
								$out2 =$this->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
								$out .= $out2;
							}
							$itemsOut='';			// Clear the item-code var
	
						$categoryChanged = 0;
						}
					}
				}
			}
		}


		$subpartArray['###ITEM_CATEGORY_AND_ITEMS###'] = $out;

			// Final things
			// Personal and delivery info:

/* Added Els: 'feusers_uid,'*/
		$infoFields = explode(',','feusers_uid,name,address,telephone,fax,email,company,city,zip,state,country');		// Fields...
		while(list(,$fName)=each($infoFields))
		{
			$markerArray['###PERSON_'.strtoupper($fName).'###'] = $orderData['personInfo'][$fName];
			$markerArray['###DELIVERY_'.strtoupper($fName).'###'] = $orderData['deliveryInfo'][$fName]; // $this->deliveryInfo[$fName];
		}

		$markerArray['###PERSON_ADDRESS_DISPLAY###'] = nl2br($markerArray['###PERSON_ADDRESS###']);
		$markerArray['###DELIVERY_ADDRESS_DISPLAY###'] = nl2br($markerArray['###DELIVERY_ADDRESS###']);

		$temp = explode(' ', $orderRow['payment']);
		$markerArray['###PAYMENT_TITLE###'] = $temp[1];
		$markerArray['###PRICE_SHIPPING_TAX###'] = $this->priceFormat($this->calculatedArray['priceTax']['shipping']);
		$markerArray['###PRICE_SHIPPING_NO_TAX###'] = $this->priceFormat($this->calculatedArray['priceNoTax']['shipping']);
		$markerArray['###PRICE_PAYMENT_TAX###'] = $this->priceFormat($this->calculatedArray['priceTax']['payment']);
		$markerArray['###PRICE_PAYMENT_NO_TAX###'] = $this->priceFormat($this->calculatedArray['priceNoTax']['payment']);
		$markerArray['###PRICE_TOTAL_TAX###'] = $this->priceFormat($this->calculatedArray['priceTax']['total']);
		$markerArray['###PRICE_TOTAL_NO_TAX###'] = $this->priceFormat($this->calculatedArray['priceNoTax']['total']);

		$markerArray['###ORDER_UID###'] = $this->getOrderNumber($orderRow['uid']);
		$markerArray['###ORDER_DATE###'] = $this->cObj->stdWrap($orderRow['crdate'],$this->conf['orderDate_stdWrap.']);

		$content= $this->cObj->substituteMarkerArrayCached($t['orderFrameWork'], $markerArray, $subpartArray);
		$reldateiname = $this->conf['outputFolder'] . '/' . $type . '/' . $tracking . '.htm';
		
		$dateiname = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') .'/'. $reldateiname;
		$datei = fopen($dateiname, 'w');
		fwrite ($datei, $content);
		fclose ($datei);

		$message = $this->pi_getLL('open_'.$type);
		$content = '<a href="' . $reldateiname . '" >'.$message.'</a>';

		return $content;
	}


	/**
	 * Send notification email for gift certificates
	 */
	function sendGiftEmail($recipient, $comment, $giftRow, $templateCode, $templateMarker)	{
		global $TSFE;

		$sendername = ($giftRow['personname'] ? $giftRow['personname'] : $this->conf['orderEmail_fromName']);
		$senderemail = ($giftRow['personemail'] ? $giftRow['personemail'] : $this->conf['orderEmail_from']);

		$recipients = $recipient;
		$recipients=t3lib_div::trimExplode(',',$recipients,1);

		if (count($recipients))	{	// If any recipients, then compile and send the mail.
			$emailContent=trim($this->cObj->getSubpart($templateCode,'###'.$templateMarker.'###'));
			if ($emailContent)	{		// If there is plain text content - which is required!!
				$parts = split(chr(10),$emailContent,2);		// First line is subject
				$subject=trim($parts[0]);
				$plain_message=trim($parts[1]);

				$markerArray = array();
				$markerArray['###CERTIFICATES_TOTAL###'] = $giftRow['amount'];
				$markerArray['###CERTIFICATES_UNIQUE_CODE###'] =  $giftRow['uid'].'-'.$giftRow['crdate'];
				$markerArray['###PERSON_NAME###'] = $giftRow['personname'];
				$markerArray['###DELIVERY_NAME###'] = $giftRow['deliveryname'];
				$markerArray['###ORDER_STATUS_COMMENT###'] = $giftRow['note'].'\n'.$comment;   
								
				$emailContent = $this->cObj->substituteMarkerArrayCached($plain_message, $markerArray);
					
				$cls  = t3lib_div::makeInstanceClassName('tx_ttproducts_htmlmail');
				if (class_exists($cls) && $this->conf['orderEmail_htmlmail'])	{	// If htmlmail lib is included, then generate a nice HTML-email
					$HTMLmailShell=$this->cObj->getSubpart($this->templateCode,'###EMAIL_HTML_SHELL###');
					$HTMLmailContent=$this->cObj->substituteMarker($HTMLmailShell,'###HTML_BODY###',$emailContent);
					$HTMLmailContent=$this->cObj->substituteMarkerArray($HTMLmailContent, $this->globalMarkerArray);
	
					$V = array (
						'from_email' => $senderemail,
						'from_name'  => $sendername,
						'attachment' => $this->conf['GiftAttachment']
					);
		
					$Typo3_htmlmail = t3lib_div::makeInstance('tx_ttproducts_htmlmail');
					$Typo3_htmlmail->useBase64();
					$Typo3_htmlmail->start(implode($recipients,','), $subject, $emailContent, $HTMLmailContent, $V);
					$Typo3_htmlmail->sendtheMail();
				} else {		// ... else just plain text...
					// $headers variable überall entfernt!
					$this->send_mail($recipients, $subject, $emailContent, $senderemail, $sendername, $this->conf['GiftAttachment']);
					$this->send_mail($this->conf['orderEmail_to'], $subject, $emailContent, $this->personInfo['email'], $this->personInfo['name'], $this->conf['GiftAttachment']);
				}
			}
		}

	}


	/**
	 * Send notification email for tracking
	 */
	function sendNotifyEmail($recipient, $v, $tracking, $orderRow, $templateCode, $templateMarker, $sendername='', $senderemail='')	{
		global $TSFE;

		$uid = $this->getOrderNumber($orderRow['uid']);
			// initialize order data.
		$orderData = unserialize($orderRow['orderData']);

		$sendername = ($sendername ? $sendername : $this->conf['orderEmail_fromName']);
		$senderemail = ($senderemail ? $senderemail : $this->conf['orderEmail_from']);
		
			// Notification email

		$recipients = $recipient;
		$recipients=t3lib_div::trimExplode(',',$recipients,1);

		if (count($recipients))	{	// If any recipients, then compile and send the mail.
			$emailContent=trim($this->cObj->getSubpart($templateCode,'###'.$templateMarker.'###'));
			if ($emailContent)	{		// If there is plain text content - which is required!!
				$markerArray['###ORDER_STATUS_TIME###']=$this->cObj->stdWrap($v['time'],$this->conf['statusDate_stdWrap.']);
				$markerArray['###ORDER_STATUS###']=$v['status'];
				$markerArray['###ORDER_STATUS_INFO###']=$v['info'];
				$markerArray['###ORDER_STATUS_COMMENT###']=$v['comment'];
				$markerArray['###PID_TRACKING###'] = $this->conf['PIDtracking'];
				$markerArray['###PERSON_NAME###'] =  $orderData['personInfo']['name'];
				$markerArray['###DELIVERY_NAME###'] =  $orderData['deliveryInfo']['name'];

				$markerArray['###ORDER_TRACKING_NO###']=$tracking;
				$markerArray['###ORDER_UID###']=$uid;

				$emailContent=$this->cObj->substituteMarkerArrayCached($emailContent, $markerArray);

				$parts = split(chr(10),$emailContent,2);
				$subject=trim($parts[0]);
				$plain_message=trim($parts[1]);

				$this->send_mail(implode($recipients,','), $subject, $plain_message, $senderemail, $sendername);
			}
		}
	}

	/**
	 * Generate a graphical price tag or print the price as text
	 */
	function printPrice($priceText)
	{
		if (($this->conf['usePriceTag']) && (isset($this->conf['priceTagObj.'])))
		{
			$ptconf = $this->conf['priceTagObj.'];
			$markContentArray = array();
			$markContentArray['###PRICE###'] = $priceText;
			$this->cObj->substituteMarkerInObject($ptconf, $markContentArray);
			return $this->cObj->cObjGetSingle($this->conf['priceTagObj'], $ptconf);
		}
		else
			return $priceText;
	}


	/**
	 * Extended mail function
	 */
	function send_mail($email,$subject,$message,$fromEMail,$fromName,$attachment='')
	{
		$cls=t3lib_div::makeInstanceClassName('t3lib_htmlmail');
		if (class_exists($cls))
		{
			$Typo3_htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
			$Typo3_htmlmail->start();
			$Typo3_htmlmail->useBase64();

			$Typo3_htmlmail->subject = $subject;
			$Typo3_htmlmail->from_email = $fromEMail;
			$Typo3_htmlmail->from_name = $fromName;
			$Typo3_htmlmail->replyto_email = $Typo3_htmlmail->from_email;
			$Typo3_htmlmail->replyto_name = $Typo3_htmlmail->from_name;
			$Typo3_htmlmail->organisation = '';
			$Typo3_htmlmail->priority = 3;

			$Typo3_htmlmail->addPlain($message);
			if ($attachment != '')
				$Typo3_htmlmail->addAttachment($attachment);

			$Typo3_htmlmail->setHeaders();
			$Typo3_htmlmail->setContent();
			$Typo3_htmlmail->setRecipient(explode(',', $email));
			$Typo3_htmlmail->sendtheMail();
		}
	}

	/**
	 * Displays and manages the memo
	 */
	function memo_display($theCode)
	{
		global $TSFE;

		$fe_user_uid = $TSFE->fe_user->user['uid'];
		if (!$fe_user_uid)
			return $this->cObj->getSubpart($this->templateCode,$this->spMarker('###MEMO_NOT_LOGGED_IN###'));

		if ($TSFE->fe_user->user['tt_products_memoItems'] != '')
			$memoItems = explode(',', $TSFE->fe_user->user['tt_products_memoItems']);
		else
			$memoItems = array();

		if (t3lib_div::GPvar('addmemo'))
		{
			$addMemo = explode(',', t3lib_div::GPvar('addmemo'));

			foreach ($addMemo as $addMemoSingle)
				if (!in_array($addMemoSingle, $memoItems))
					$memoItems[] = $addMemoSingle;

			$fieldsArray = array();
			$fieldsArray['tt_products_memoItems']=implode(',', $memoItems);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$fe_user_uid, $fieldsArray);
		}

		if (t3lib_div::GPvar('delmemo'))
		{
			$delMemo = explode(',', t3lib_div::GPvar('delmemo'));

			foreach ($delMemo as $delMemoSingle)
				if (in_array($delMemoSingle, $memoItems))
					unset($memoItems[array_search($delMemoSingle, $memoItems)]);

			$fieldsArray = array();
			$fieldsArray['tt_products_memoItems']=implode(',', $memoItems);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$fe_user_uid, $fieldsArray);
		}

		return $this->products_display($theCode, implode(',', $memoItems));
	}


/* Added Els2: Displays and manages the orders */
   function orders_display($theCode)
   {
       global $TSFE;

       $feusers_uid = $TSFE->fe_user->user['uid'];

       if (!$feusers_uid)
           return $this->cObj->getSubpart($this->templateCode,$this->spMarker('###BASKET_TEMPLATE_NOT_LOGGED_IN###'));

       $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_products_orders', 'feusers_uid='.$feusers_uid.' AND NOT deleted');

       $content=$this->cObj->getSubpart($this->templateCode,$this->spMarker('###ORDERS_LIST_TEMPLATE###'));
       //$content .= '<p class=\'bodytext\'><br>';

       $content .= "<div style='width:714px;'>
<table border='0' cellspacing='0' cellpadding='0'>
 <tr>
   <th width='20%' scope='col'><b>Spaarhistorie</b></th>
   <th width='80%' scope='col' colspan='2'><b>Klantnummer: $feusers_uid</b></th>
 </tr>
 <tr>
   <th width='20%' scope='col'>Datum</th>
   <th width='20%' scope='col'>Factuurnr</th>
   <th width='60%' scope='col'>Kurken</th>
 </tr>
";

       $this->orders = array();
       while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
           $this->orders[$row['uid']] = $row['tracking_code'];
           $content .= "<tr><td width='20%'>".$this->cObj->stdWrap($row['crdate'],$this->conf['orderDate_stdWrap.']).'</td>';
           $number = str_replace('mw_order', '', $row['tracking_code']);
           $content .= '<td width="20%"><a href="index.php?id=215&tracking='.$row['tracking_code'].'">'.$number.'</a></td>';
           $creditpoints = $this->getCreditPoints($row['amount']);
           $content .= "<td width='60%'>".($this->priceFormat($creditpoints*$row['amount']) - number_format($row['creditpoints'],0)).'</td>';
       }

       $res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('username ', 'fe_users', 'uid="'.$feusers_uid.'"');
       if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res1)) {
           $username = $row['username'];
       }

       $content .= "
 <tr>
   <th width='40%' scope='col' colspan='2'>Verdiende kurken met uw vouchercode <i>".$row['username'].'</i></th>';

       $res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('username', 'fe_users', 'tt_products_vouchercode="'.$username.'"');
       $num_rows = $GLOBALS['TYPO3_DB']->sql_num_rows($res2);

       $content .= "<th width='60%' scope='col'>".($num_rows * 5).'</th></tr>';

       $res3 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tt_products_creditpoints ', 'fe_users', 'uid='.$feusers_uid.' AND NOT deleted');
       $content .= "
 <tr>
   <th width='40%' scope='col' colspan='2'>Uw kurkensaldo</th>";

       $this->creditpoints = array();
       while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res3)) {
           $this->creditpoints[$row['uid']] = $row['tt_products_creditpoints'];
           $content .= "<th width='60%' scope='col'><span class='kurkensaldo'>";
           $content .= $this->priceFormat($row['tt_products_creditpoints']);
           $content .= '</span></th></tr>';
       }
       $content .= "
</table>
</div>
<div class='shopTable-content-white'>
</div>
       ";

       return $content;

   }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/pi/class.tx_ttproducts.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/pi/class.tx_ttproducts.php']);
}


?>
