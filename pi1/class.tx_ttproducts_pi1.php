<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * @author	Els Verberne <verberne@bendoo.nl>
 * @package TYPO3
 * @subpackage tt_products
 * @see file tt_products/ext_typoscript_constants.txt
 * @see TSref
 *
 *
 */

//require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(PATH_BE_fh_library.'/sysext/cms/tslib/class.fhlibrary_pibase.php');
require_once(PATH_t3lib.'class.t3lib_parsehtml.php');

require_once(PATH_BE_table.'lib/class.tx_table_db.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_article.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_basket.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_basket_view.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_category.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_config.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_content.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_creditpoints_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_javascript.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_email.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_fe_users.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_gifts_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_marker.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_memo_view.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_order.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_page.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_paymentshipping.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_price.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricecalc.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_product.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_single_view.php');

//define('XAJAX_DEFAULT_ENCODING',  'iso-8859-1');

class tx_ttproducts_pi1 extends fhlibrary_pibase {
	var $prefixId = 'tx_ttproducts_pi1';	// Same as class name
	var $scriptRelPath = 'pi1/class.tx_ttproducts_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = TT_PRODUCTS_EXTkey;	// The extension key.
	var $version;			// version number

	var $cObj;		// The backReference to the mother cObj object set at call time

		// Internal
	var $uid_list='';					// List of existing uid's from the basket, set by initBasket()
	var $pid_list;						// list of page ids
	var $orderRecord = array();			// Will hold the order record if fetched.

		// Internal: init():
	var $templateCode='';				// In init(), set to the content of the templateFile. Used by default in basketView->getView()
	var $config=array();				// updated configuration
	var $cnf;							// object for configuration purposes
	var $tt_product_single=array();
	var $globalMarkerArray=array();
	var $externalCObject='';
		// mkl - multicurrency support
	var $currency = '';				// currency iso code for selected currency
	var $baseCurrency = '';			// currency iso code for default shop currency
	var $xrate = 1.0;				// currency exchange rate (currency/baseCurrency)

	var $mkl; 						// if compatible to mkl_products
	var $tt_products; 				// object of the type tx_ttproducts_product
	var $tt_products_articles;		// object of the type tx_table_db
	var $tt_products_cat; 					// object of the type tx_ttproducts_category

	var $tt_content; 				// object of the type tx_ttproducts_content
	var $order;	 					// object of the type tx_ttproducts_order
	var $page;	 					// object of the type tx_ttproducts_page
	var $paymentshipping; 			// object of the type tx_ttproducts_paymentshipping
	var $fe_users;					// object of the type tx_table_db
	var $price;	 					// object for price functions

	var $basket;					// basket object
	var $singleView;				// single view object
	var $memoView;					// memo view and data object
	
	var $pi_checkCHash = TRUE;		// activate cHash
	var $pid;						// the page to which the script shall go
	
	var $xajax;						// xajax object
	var $javascript;				// JavaScript object
	var $codeArray;					// Codes
	var $template_suffix; 			// suffix for template subpart area markers
	var $bNoCachePossible = TRUE;	// if the cache may be turned off
	var $pageAsCategory;			// > 0 if pages are used as categories

	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	function main($content,$conf)	{
		global $TSFE;

		$this->conf = &$conf;
		$backPID = $this->piVars['backPID'];
		$backPID = ($backPID ? $backPID : t3lib_div::_GP('backPID'));

		$eInfo = $this->getExtensionInfo(PATH_BE_ttproducts);
		$this->version = $eInfo['version'];
		$this->pi_initPIflexForm();
		$config['code'] = 
			$this->pi_getSetupOrFFvalue(
	 			$this->conf['code'], 
	 			$this->conf['code.'], 
				$this->conf['defaultCode'], 
				$this->cObj->data['pi_flexform'],
				'display_mode',
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['useFlexforms']
				);

		$this->codeArray=t3lib_div::trimExplode(',', $config['code'],1);	
		
			// initialise AJAX at the beginning because the AJAX functions can set piVars
		if (t3lib_extMgm::isLoaded('xajax')) {
			include_once(t3lib_extMgm::extPath('xajax').'class.tx_xajax.php');
			$this->xajax = t3lib_div::makeInstance('tx_xajax');
				// Decode form vars from utf8
			// $this->xajax->decodeUTF8InputOn();
				// Encoding of the response to iso-8859-1.
			$this->xajax->setCharEncoding('iso-8859-1');
			
				// Encoding of the response to utf-8. 
			// $this->xajax->setCharEncoding('utf-8');
				// To prevent conflicts, prepend the extension prefix.
			// $this->xajax->setWrapperPrefix($this->prefixId);
			$this->xajax->setWrapperPrefix('');
				// Do you want messages in the status bar?
			// $this->xajax->statusMessagesOn();
				// Turn only on during testing
			// $this->xajax->debugOff();

			$this->xajax->setRequestURI(t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT') . '?' . htmlspecialchars(t3lib_div::getIndpEnv('QUERY_STRING') ));
			$this->xajax->setWrapperPrefix('');
			$this->xajax->registerFunction(array('tt_products_showArticle',&$this,'tt_products_showArticle'));
            $this->xajax->registerFunction(array('tt_products_changeValue',&$this,'tt_products_changeValue'));
            $this->xajax->registerFunction(array('tt_products_processFormData',&$this,'tt_products_processFormData'));
		}
	
		// page where to go usually
		$this->pid = ($conf['PIDbasket'] && $conf['clickIntoBasket'] ? $conf['PIDbasket'] : ($backPID ? $backPID : $TSFE->id));

		if (t3lib_extMgm::isLoaded('xajax')) {
			if($_POST['xajax']){
				global $trans;
				$trans = $this;
				$this->xajax->processRequests();
				exit();
			}
		}

		if (is_array($conf['userFunc.']) && strstr($conf['userFunc.'],'callTime'))	{
			$this->bNoCachePossible = FALSE;
			foreach ($conf['userFunc.'] as $part => $setup) {		
			    if(!strpos($part,'.')) {
				$content .= $this->cObj->cObjGetSingle($setup,$conf['userFunc.'][$part.'.']);
			    }
			}
		} else {
			$content = $this->doProcessing($content, false);		
		}

		return $content;
	}


	function callTimeBasket() {
		$this->codeArray[] = 'BASKET';
		$content = $this->doProcessing($content, false);
		$content .= strftime('%D - %H:%M:%S',time()); 
	    return $content;
	}

	function callTimeSingle() {
		$this->codeArray[] = 'SINGLE';
		$content = $this->doProcessing($content, false);
		$content .= strftime('%D - %H:%M:%S',time()); 
	    return $content;
	}

	function callTimeCombined1() {
		$this->codeArray[] = 'BASKET';
		$this->codeArray[] = 'SINGLE';
		$content = $this->doProcessing($content, false);
		$content .= strftime('%D - %H:%M:%S',time()); 
	    return $content;
	}

	function callTimeCombined2() {
		$this->codeArray[] = 'SINGLE';
		$this->codeArray[] = 'BASKET';
		$content = $this->doProcessing($content, false);
		$content .= strftime('%D - %H:%M:%S',time()); 
	    return $content;
	}

	function set_no_cache() {
		global $TSFE;

		if ($this->bNoCachePossible)	{
			$TSFE->set_no_cache();
		}
	}

	function doProcessing($content='', $bRunAjax = false)	{
		global $TSFE;
		$bStoreBasket = false;

		if (!count($this->codeArray) && !$bRunAjax)	 $this->codeArray=array('HELP');
		$this->codeArray = $this->pi_sortCodes($this->codeArray, $bStoreBasket);

		$this->init ($content, $this->config);

		if ((t3lib_extMgm::isLoaded('xajax')) && !$bRunAjax)	{
			$this->javascript->set('ttpajax');
		}

		$error_code = array();		

		if (t3lib_div::_GP('mode_update'))	{
			$updateMode = 1;
		} else {
			$updateMode = 0;
		}

		if (!$this->errorMessage) {
			$this->basket->init(
				$this,
				$this->cnf,
				$TSFE->fe_user->getKey('ses','recs'),
				$updateMode, 
				$this->pid_list,
				$this->tt_content,
				$this->tt_products,
				$this->tt_products_articles,
				$this->tt_products_cat,
				$this->fe_users,
				$this->price,
				$this->paymentshipping,
				$bStoreBasket
			);
		}

		// *************************************
		// *** Listing items:
		// *************************************
		if (!$this->errorMessage) {
			$this->basketView->init ($this->basket, $this->order, $this->templateCode);
			
			$this->basket->getCalculatedBasket(); // get the basket->itemArray
			$content .= $this->basketView->printView($this->codeArray, $this->errorMessage);
		}
		reset($this->codeArray);
		$TSFE->reqCHash();

		// $TSFE->set_no_cache(); uncomment this line if you have a problem with the cache
		while(!$this->errorMessage && list($key,$theCode)=each($this->codeArray))	{
			$theCode = (string) trim($theCode);
			$contentTmp = '';
			switch($theCode)	{
				case 'SEARCH':
					$this->set_no_cache();
					// no break!
				case 'LIST':
				case 'LISTGIFTS':
				case 'LISTHIGHLIGHTS':
				case 'LISTNEWITEMS':
				case 'LISTOFFERS':
				case 'LISTARTICLES':
					if (count($this->tt_product_single) || !$this->conf['NoSingleViewOnList']) {
						$this->set_no_cache();
					}

					if (count($this->basket->itemArray)) {
						$this->set_no_cache();
					}
					$contentTmp=$this->products_display($theCode, $this->errorMessage, $error_code);
				break;
				case 'LISTCAT':
					include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_catlist_view.php');
						// category view
					$categoryView = t3lib_div::makeInstance('tx_ttproducts_catlist_view');
					$categoryView->init($this, $this->cnf, $this->basket, $this->pid_list, $this->tt_content, $this->tt_products_cat, $this->pid);
//					if (count($codes) > 1)	{
//						$idClass = 'tx-ttproducts-pi1-'.strtolower($codes[1]);
//					} else {
//						$idClass = 'ArticleId';
//					}
					$contentTmp = $categoryView->printView($this->templateCode, $error_code, $this->pageAsCategory);
				break;
				case 'SINGLE':
					if (count($this->basket->itemArray) || !$this->conf['NoSingleViewOnList'] && 
						!$this->conf['PIDitemDisplay'] && !$this->conf['PIDitemDisplay.']) {
						$this->set_no_cache();
					}
					$contentTmp = $this->products_display($theCode, $this->errorMessage, $error_code);				
				break;
				case 'OVERVIEW':
					if (count($this->basket->itemArray)) {
						$this->set_no_cache();
					}
					break;
				case 'BASKET':
				case 'FINALIZE':
				case 'INFO':
				case 'PAYMENT':
					$this->set_no_cache();
						// nothing here any more. This work is done in the call of $this->basketView->printView before
						// This is necessary because some activities might have overriden these CODEs
				break;
				case 'BILL':
				case 'DELIVERY':
					include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_billdelivery.php');
					$this->set_no_cache();
					$contentTmp=$this->products_tracking($theCode);
				break;
				case 'TRACKING':
					include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_tracking.php');
					$this->set_no_cache();
					$contentTmp=$this->products_tracking($theCode);
				break;
				case 'MEMO':
					$this->set_no_cache();
					$contentTmp=$this->memoView->printView($this->templateCode, $error_code);
				break;
				case 'CURRENCY':
					include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_currency_view.php');
					$this->set_no_cache();
						// currency view
					$currencyView = t3lib_div::makeInstance('tx_ttproducts_currency_view');
					$currencyView->init($this, $this->cnf, $this->basket);

					$contentTmp=$currencyView->printView();
				break;
				case 'ORDERS':
					include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_order_view.php');
					$this->set_no_cache();

						// order view
					$orderView = t3lib_div::makeInstance('tx_ttproducts_order_view');
					$orderView->init($this, $this->cnf, $this->basket, $this->order);
					$contentTmp=$orderView->printView($this->templateCode, $error_code);
				break;
				default:	// 'HELP'
					$this->set_no_cache();
					$contentTmp = 'error';
				break;
			}

			if ($error_code[0]) {
				$messageArr = array(); 
				$i = 0;
				foreach ($error_code as $key => $indice) {
					if ($key == 0) {
						$messageArr =  explode('|', $message = $this->pi_getLL($indice));
						$contentTmp.=$this->pi_getLL('tt_products').' '.$messageArr[0];
					} else {
						$contentTmp.=$indice.$messageArr[$i];
					}
					$i++;
				}
				// $contentTmp.=$messageArr[0].intval($this->uid) .$messageArr[1];
			}
			
			if ($contentTmp == 'error') {
					$fileName = 'EXT:'.TT_PRODUCTS_EXTkey.'/template/products_help.tmpl';
					$content .= $this->pi_displayHelpPage($this->cObj->fileResource($fileName));
					unset($this->errorMessage);
					break; // while
			} else {
				if ($contentTmp)	{
					$idClass = 'tx-ttproducts-pi1-'.strtolower($theCode);
					$content .='<div id="'.$idClass.'">'.$contentTmp.'</div><!-- END: '.$idClass.' -->';
				}
			}
		}

		if ($this->errorMessage) {
			$content = '<p><b>'.$this->errorMessage.'</b></p>';
		}
		$rc = ($bRunAjax ? $content : $this->pi_wrapInBaseClass($content));
		return $rc;	
	}


	/**
	 * does the initialization stuff
	 *
	 * @param		string		  content string
	 * @param		string		  configuration array
	 * @param		string		  modified configuration array
	 * @return	  void
 	 */
	function init (&$content, &$config) {
		global $TSFE;

		$this->pi_setPiVarDefaults();

		$this->cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$this->cnf->init(
			$this->conf,
			$config
		);
		
		$typoVersion = t3lib_div::int_from_ver($GLOBALS['TYPO_VERSION']);
		if ($typoVersion < 3008000)	{
			$this->pi_loadLL('../locallang_tca.php');
		} else {
			$this->pi_loadLL('../locallang_db.xml');
		}

			// get all extending TCAs
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['extendingTCA']))	{
			$this->pi_mergeExtendingTCAs($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['extendingTCA']);
		}

		$this->initTables();
 
		// *************************************
		// *** getting configuration values:
		// *************************************

		$this->pageAsCategory = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'];

		// get template suffix string
		$this->template_suffix = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_suffix');
		$this->template_suffix = strtoupper($this->template_suffix);

		$config['limit'] = $this->conf['limit'] ? $this->conf['limit'] : 50;
		$config['limitImage'] = t3lib_div::intInRange($this->conf['limitImage'],0,50);
		$config['limitImage'] = $config['limitImage'] ? $config['limitImage'] : 1;
		$config['limitImageSingle'] = t3lib_div::intInRange($this->conf['limitImageSingle'],0,50);
		$config['limitImageSingle'] = $config['limitImageSingle'] ? $config['limitImageSingle'] : 1;
		$recursive = ($this->cObj->data['recursive'] ? $this->cObj->data['recursive']: $this->conf['recursive']);
		$config['recursive'] = t3lib_div::intInRange($recursive,0,100);
		if ($this->conf['PIDstoreRoot'])	{
			$config['storeRootPid'] = $this->conf['PIDstoreRoot'];
		} else {
			foreach ($TSFE->tmpl->rootLine as $k => $row)	{
				if ($row['doktype'] == 1)	{
					$config['storeRootPid'] = $row['uid'];
					break;
				}
			}
		}
		$config['priceNoReseller'] = $this->conf['priceNoReseller'] ? t3lib_div::intInRange($this->conf['priceNoReseller'],2,2) : NULL;
		$tmp = $this->cObj->stdWrap($this->conf['pid_list'],$this->conf['pid_list.']);
		$pid_list = $config['pid_list'] = ($this->piVars['pid_list'] ? $this->piVars['pid_list'] : ($this->conf['pid_list.'] ? trim($this->cObj->stdWrap($this->conf['pid_list'],$this->conf['pid_list.'])) : $this->conf['pid_list']));
		$pid_list = ($pid_list ? $pid_list : $this->cObj->data['pages']);
		$this->pid_list = ($pid_list ? $pid_list : $config['storeRootPid']);

			// If the current record should be displayed.
		$config['displayCurrentRecord'] = $this->conf['displayCurrentRecord'];
		if ($config['displayCurrentRecord'])	{
			$config['code']='SINGLE';
			$this->tt_product_single['product'] = true;
		} else {
			$tmp = ($this->piVars['product'] ? $this->piVars['product'] : t3lib_div::_GP('tt_products'));
			if ($tmp)	{
				$this->tt_product_single['product'] = $tmp;
			}
			$tmp = ($this->piVars['article'] ? $this->piVars['article'] : '');
			if ($tmp)	{
				$this->tt_product_single['article'] = $tmp;
			}			
		}

		if ($this->conf['templateFile']) {
			// template file is fetched. The whole template file from which the various subpart are extracted.
			$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);
		}

		if (!$this->conf['templateFile'] || empty($this->templateCode)) {
			$this->errorMessage .= $this->pi_getLL('no_template').' plugin.tt_products.file.templateFile = ';
			$this->errorMessage .= ($this->conf['templateFile'] ? "'".$this->conf['templateFile']."'" : '""');
		}

		// mkl - multicurrency support
//		if (t3lib_extMgm::isLoaded('mkl_currxrate')) {
//			include_once(t3lib_extMgm::extPath('mkl_currxrate').'pi1/class.tx_mklcurrxrate_pi1.php');
//			$this->baseCurrency = $TSFE->tmpl->setup['plugin.']['tx_mklcurrxrate_pi1.']['currencyCode'];
//			$this->currency = t3lib_div::GPvar('C') ? 	t3lib_div::GPvar('C') : $this->baseCurrency;
//
//			// mkl - Initialise exchange rate library and get
//
//			$this->exchangeRate = t3lib_div::makeInstance('tx_mklcurrxrate_pi1');
//			$this->exchangeRate->init();
//			$result = $this->exchangeRate->getExchangeRate($this->baseCurrency, $this->currency) ;
//			$this->xrate = floatval ( $result['rate'] );
//		}

		if (t3lib_extMgm::isLoaded('sr_static_info')) {
			include_once(t3lib_extMgm::extPath('sr_static_info').'pi1/class.tx_srstaticinfo_pi1.php');
			// Initialise static info library
			$this->staticInfo = t3lib_div::makeInstance('tx_srstaticinfo_pi1');
			$this->staticInfo->init();
		}

			// globally substituted markers, fonts and colors.
		$splitMark = md5(microtime());
		$globalMarkerArray=array();
		list($globalMarkerArray['###GW1B###'],$globalMarkerArray['###GW1E###']) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf['wrap1.']));
		list($globalMarkerArray['###GW2B###'],$globalMarkerArray['###GW2E###']) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf['wrap2.']));
		list($globalMarkerArray['###GW3B###'],$globalMarkerArray['###GW3E###']) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf['wrap3.'])); 
		$globalMarkerArray['###GC1###'] = $this->cObj->stdWrap($this->conf['color1'],$this->conf['color1.']);
		$globalMarkerArray['###GC2###'] = $this->cObj->stdWrap($this->conf['color2'],$this->conf['color2.']);
		$globalMarkerArray['###GC3###'] = $this->cObj->stdWrap($this->conf['color3'],$this->conf['color3.']);
		$globalMarkerArray['###DOMAIN###'] = $this->conf['domain'];
		$globalMarkerArray['###PID_BASKET###'] = $this->conf['PIDbasket'];
		$globalMarkerArray['###PID_BILLING###'] = $this->conf['PIDbilling'];
		$globalMarkerArray['###PID_DELIVERY###'] = $this->conf['PIDdelivery'];
		$globalMarkerArray['###PID_TRACKING###'] = $this->conf['PIDtracking'];
		$globalMarkerArray['###SHOPADMIN_EMAIL###'] = $this->conf['orderEmail_from'];
		
		if (is_array($this->conf['marks.']))	{
				// Substitute Marker Array from TypoScript Setup
			foreach ($this->conf['marks.'] as $key => $value)	{
				$globalMarkerArray['###'.$key.'###'] = $value;
			}
		}

			// Substitute Global Marker Array
		$this->templateCode= $this->cObj->substituteMarkerArrayCached($this->templateCode, $globalMarkerArray);
		
			// This cObject may be used to call a function which manipulates the shopping basket based on settings in an external order system. The output is included in the top of the order (HTML) on the basket-page.
		$this->externalCObject = $this->getExternalCObject('externalProcessing');
		$this->globalMarkerArray = $globalMarkerArray;

			// pages
		$this->page = tx_ttproducts_page::createPageTable(
			$this,
			$this->cnf,
			$this->tt_content,
			$this->LLkey,
			$this->conf['table.']['pages'], 
			$this->conf['table.']['pages.'],
			$this->conf['conf.']['pages.'],
			$this->page,
			$this->pid_list,
			$this->config['recursive']
		);

			// JavaScript
		$this->javascript = t3lib_div::makeInstance('tx_ttproducts_javascript');
		$this->javascript->init(
			$this,
			$this->cnf,
			$this->page,
			$this->xajax
		);
		
			// basket
		$this->basket = t3lib_div::makeInstance('tx_ttproducts_basket');

			// basket view
		$this->basketView = t3lib_div::makeInstance('tx_ttproducts_basket_view');

			// memo view: has to be called always because it reads parameters from the list
		$this->memoView = t3lib_div::makeInstance('tx_ttproducts_memo_view');
		$this->memoView->init(
			$this,
			$this->cnf,
			$this->basket,
			$this->basketView,
			$this->pid_list,
			$this->tt_content,
			$this->tt_products,
			$this->tt_products_cat,
			$this->tt_products_articles,
			$this->fe_users,
			$this->pid,
			$this->LLkey,
			$this->conf['useArticles']
		);

				
			// price
		$this->price = t3lib_div::makeInstance('tx_ttproducts_price');
		$this->price->init(
			$this,
			$this->conf,
			$config
		);
	
			// paymentshipping
		$this->paymentshipping = t3lib_div::makeInstance('tx_ttproducts_paymentshipping');
		$this->paymentshipping->init(
			$this,
			$this->cnf,
			$this->basket,
			$this->basketView,
			$this->fe_users
		);

			// order
		$this->order = t3lib_div::makeInstance('tx_ttproducts_order');
		$this->order->init(
			$this,
			$this->cnf,
			$this->tt_products,
			$this->tt_products_articles,
			$this->tt_products_cat,
			$this->basket
		);
		
	} // init


	/**
	 * Getting the table definitions
	 */
	function initTables()	{		
		$this->tt_products_cat = t3lib_div::makeInstance('tx_ttproducts_category');
		$this->tt_products_cat->init(
			$this,
			$this->cnf,
			$this->tt_content,
			$this->LLkey,
			$this->conf['table.']['tt_products_cat'], 
			$this->conf['table.']['tt_products_cat.']
		);

		$this->tt_content = t3lib_div::makeInstance('tx_ttproducts_content');
		$this->tt_content->init();
		$this->tt_products = t3lib_div::makeInstance('tx_ttproducts_product');
		$this->tt_products->init(
			$this,
			$this->cnf,
			$this->tt_content,
			$this->LLkey,
			$this->conf['table.']['tt_products'],
			$this->conf['table.']['tt_products.'],
			$this->conf['conf.']['tt_products.'],
			$this->conf['useArticles']
		);

		$this->tt_products_articles = t3lib_div::makeInstance('tx_ttproducts_article');
		$this->tt_products_articles->init(
			$this,
			$this->cnf,
			$this->tt_products,
			$this->tt_content,
			$this->LLkey,
			$this->conf['table.']['tt_products_articles'],
			$this->conf['table.']['tt_products_articles.'],
			$this->conf['conf.']['tt_products_articles.']
		);

		$this->fe_users = t3lib_div::makeInstance('tx_ttproducts_fe_users');
		$this->fe_users->init(
			$this,
			$this->cnf,
			$this->conf['table.']['fe_users'],
			$this->conf['table.']['fe_users.']
		);
		
	} // initTables


	function tt_products_changeValue($arg)        {
		 // do some stuff based on $arg like query data from a database and
		 // put it into a variable like $ajax_content
		
	     $ajax_content = $arg;  // here will use the argument as output
	
	     // Instantiate the tx_xajax_response object
	     $objResponse = new tx_xajax_response();
	     $objResponse->addAssign("change","value", $ajax_content);
		
	     //return the XML response generated by the tx_xajax_response object
	     return $objResponse->getXML();
	}

	
	function tt_products_processFormData($arg)        {		
	     $xajax_content = "<br /><br /><strong>submitted values</strong><br /><br />
	                      ".t3lib_div::view_array($arg);  // here will output the Array, it looks like the $_POST/$_GET, which would be generated.
	
	     // Instantiate the tx_xajax_response object
	     $objResponse = new tx_xajax_response();
	     $objResponse->addAssign("formDiv","innerHTML", $xajax_content);
	
	     //return the XML response generated by the tx_xajax_response object
	     return $objResponse->getXML();
	}


		// XAJAX functions cannot be in classes
	function tt_products_ShowArticle($data)	{
//		error_log ('tt_products_ShowArticle $data = '.$data);
//		foreach ($data as $k1 => $v1)	{
//			error_log ('|'.$k1.':'.$v1);
//			foreach ($v1 as $k2 => $v2)	{
//				error_log ('|'.$k2.':'.$v2);				
//			}
//		}
        // We put our incomming data to the regular piVars 
        $this->piVars = array_merge($this->piVars,$data[$this->prefixId]);
//        error_log ('$this->piVars = '.$this->piVars); 		
//        error_log ('tt_products_showArticle: $this->piVars[\'cat\'] = '.$this->piVars['cat']);
//        error_log ('tt_products_showArticle: $this->piVars[\'pid_list\'] = '.$this->piVars['pid_list']);

		$this->excludeCode = '';
		$artListKey = array_search('LISTCAT', $this->codeArray);
		if (count($this->codeArray))	{
			unset($this->codeArray[$artListKey]);
//			foreach ($this->codeArray as $k1 => $v1)	{
//				error_log($k1.':'.$v1);
//			}
			if (count($this->codeArray))	{
				reset($this->codeArray);
				$theCode = current($this->codeArray);
//				error_log ('$theCode = '.$theCode);
				$idClass = 'tx-ttproducts-pi1-'.strtolower($theCode);
//				error_log ('$idClass = '.$idClass);
			}
		}
	    $content = $this->doProcessing('',true);
	    // $content = '<br /><strong>Content from AJAX server</strong><br />';
	
	    // Instantiate the tx_xajax_response object
	    $objResponse = new tx_xajax_response();
	    
//	    error_log ('$idClass = '.$idClass);
	    // add a command to the response to assign the innerHTML attribute of
	    // the element with id="ArticleId" to whatever the new content is
	    $objResponse->addAssign(($idClass ? $idClass : 'ArticleId'),'innerHTML', $content);
	
	    //return the XML response generated by the tx_xajax_response object
	    return $objResponse->getXML();	
	}


	/**
	 * Order tracking
	 *
	 *
	 * @param	integer		Code: TRACKING, BILL or DELIVERY
	 * @return	void
	 * @see enableFields()
	 */

	function products_tracking($theCode)	{ // t3lib_div::_GP('tracking')
		global $TSFE;

		$updateCode = '';
		
		$marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$marker->init($this, $this->conf, $this->config, $this->basket);

		$trackingCode = t3lib_div::_GP('tracking');
		$admin = $this->shopAdmin($updateCode);
			
		$msgSubpart = '';
		if ($trackingCode || $admin)	{		// Tracking number must be set
			$orderRow = $this->order->getRecord('',$trackingCode);
			if (is_array($orderRow) || $admin)	{		// If order is associated with tracking id.
				if (!is_array($orderRow)) {
					$orderRow=array('uid'=>0);
				}
				switch ($theCode) {
					case 'TRACKING':
				 		$tracking = t3lib_div::makeInstance('tx_ttproducts_tracking');
				 		$tracking->init(
				 			$this,
				 			$this->cnf,
				 			$this->basket,
				 			$this->order,
				 			$this->price
				 		);
						$orderRecord = t3lib_div::_GP('orderRecord');
						$content = $tracking->getTrackingInformation($orderRow, $this->templateCode, $trackingCode, $updateCode, $orderRecord, $admin);
						break;
					case 'BILL':
				 		$bill = t3lib_div::makeInstance('tx_ttproducts_billdelivery');
				 		$bill->init(
				 			$this,
				 			$this->cnf,
				 			$this->basket,
				 			$this->tt_products,
				 			$this->tt_products_cat,
				 			$this->tt_content,
				 			$this->order,
				 			$this->price,
							'bill'
						);
				 		
						$content = $bill->getInformation($orderRow, $this->templateCode,$trackingCode);
						break;
					case 'DELIVERY':
				 		$delivery = t3lib_div::makeInstance('tx_ttproducts_billdelivery');
				 		$delivery->init(
				 			$this,
				 			$this->cnf,
				 			$this->basket,
				 			$this->tt_products,
				 			$this->tt_products_cat,
				 			$this->tt_content,
				 			$this->order,
				 			$this->price,
							'delivery'
						);
						$content = $delivery->getInformation(
							$orderRow,
							$this->templateCode,
							$trackingCode
						);
						break;
					default:
						debug('error in '.TT_PRODUCTS_EXTkey.' calling function products_tracking with $theCode = "'.$theCode.'"');
				}
			} else {	// ... else output error page
				$msgSubpart = '###TRACKING_WRONG_NUMBER###';
			}
		} else {	// No tracking number - show form with tracking number
			$msgSubpart = '###TRACKING_ENTER_NUMBER###';
		}

		if ($msgSubpart)	{
			$content=$this->cObj->getSubpart($this->templateCode,$marker->spMarker($msgSubpart));
			if (!$TSFE->beUserLogin)	{
				$content = $this->cObj->substituteSubpart($content,'###ADMIN_CONTROL###','');
			}			
		}
		
		$markerArray=array();
		$markerArray['###FORM_URL###'] = $this->pi_getPageLink($TSFE->id,'',$marker->getLinkParams('',array(),true)) ; // $this->getLinkUrl();	// Add FORM_URL to globalMarkerArray, linking to self.
		$content= $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	}  // products_tracking


	/**
	 * Returns 1 if user is a shop admin
	 */
	function shopAdmin(&$updateCode)	{
		$admin=0;
		if ($GLOBALS['TSFE']->beUserLogin)	{
			$updateCode = t3lib_div::_GP('update_code');
			if ($updateCode == $this->conf['update_code'])	{
				$admin = 1;		// Means that the administrator of the website is authenticated.
			}
		}
		return $admin;
	}


	/**
	 * returns the codes in the order in which they have to be processed
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
 	 */
	function pi_sortCodes($codes,&$bStoreBasket)	{
		$bStoreBasket = true;
		
		$retCodes = $codes;
		// if the code field has been filled in from TS Setup
		// This has to be done because no articles shall be put into the basket in this case.
		if (count($codes) == 1)	{
			$changeBasketArray = array ('BASKET', 'LIST', 'LISTOFFERS', 'LISTHIGHLIGHTS', 'LISTNEWITEMS', 'LISTGIFTS', 'LISTCAT', 'LISTARTICLES', 'SINGLE', 'SEARCH');
			$tmpCodeArray = array_flip($changeBasketArray);
			if (!isset($tmpCodeArray[current($retCodes)]))	{
				$bStoreBasket = false;
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


	function load_noLinkExtCobj()	{
		if ($this->conf['externalProcessing_final'] || is_array($this->conf['externalProcessing_final.']))	{	// If there is given another cObject for the final order confirmation template!
			$this->externalCObject = $this->getExternalCObject('externalProcessing_final');
		}
	} // load_noLinkExtCobj


	/**
	 * Displaying single products/ the products list / searching
	 */
	function products_display($theCode, &$errorMessage, $error_code)	{
		global $TSFE;

		if (($theCode=='SINGLE') || (strstr($theCode,'LIST') && count($this->tt_product_single) && !$this->conf['NoSingleViewOnList'])) {
			if (!$this->tt_product_single) {
				$this->tt_product_single['product'] = $this->conf['defaultProductID'];
			}
			$extVars = $this->piVars['variants'];
			$extVars = ($extVars ? $extVars : t3lib_div::_GP('ttp_extvars'));
				// performing query:
		
			if (!is_object($this->singleView)) {
				// List single product:
				$this->singleView = t3lib_div::makeInstance('tx_ttproducts_single_view');
				$this->singleView->init (
					$this,
					$this->cnf,
					$this->basket,
					$this->basketView,
					$this->page,
					$this->tt_content, 
					$this->tt_products,
					$this->tt_products_articles,
					$this->tt_products_cat,
					$this->fe_users,
					$this->tt_product_single,
					$extVars,
					$this->pid,
					$this->LLkey,
					$this->conf['useArticles']
				);

				$content = $this->singleView->printView(
					$this->templateCode, 
					$error_code,
					$this->pageAsCategory,
					$this->template_suffix
				);
			}
		} else {
			include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_list_view.php');

			// List all products:
			$listView = t3lib_div::makeInstance('tx_ttproducts_list_view');
			$listView->init (
				$this,
				$this->cnf,
				$this->basket,
				$this->basketView,
				$this->page,
				$this->tt_content,
				$this->tt_products,
				$this->tt_products_articles,
				$this->tt_products_cat,
				$this->fe_users,
				$this->pid,
				$this->LLkey,
				$this->conf['useArticles']
			);
			if ($theCode == 'LISTARTICLES' && $this->conf['useArticles'])	{
				$templateArea = 'ARTICLE_LIST_TEMPLATE';						
			} else {
				$templateArea = 'ITEM_LIST_TEMPLATE';
			}
			$templateArea = '###'.$templateArea.($this->template_suffix ? '_'.$this->template_suffix : '').'###';
			$content = $listView->printView(
				$this->templateCode,
				$theCode,
				'',
				$error_code,
				$templateArea,
				$this->pageAsCategory
			);
		}

		return $content;
	}	// products_display
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/pi1/class.tx_ttproducts_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/pi1/class.tx_ttproducts_pi1.php']);
}


?>
