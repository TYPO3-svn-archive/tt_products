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
 * @author	Els Verberne <verberne@bendoo.nl>
 * @package TYPO3
 * @subpackage tt_products
 * @see file tt_products/ext_typoscript_constants.txt
 * @see TSref
 *
 *
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(PATH_BE_fh_library.'/sysext/cms/tslib/class.fhlibrary_pibase.php');

require_once(PATH_t3lib.'class.t3lib_parsehtml.php');
require_once(PATH_BE_ttproducts.'pi1/class.tx_ttproducts_htmlmail.php');

require_once(PATH_BE_table.'lib/class.tx_table_db.php');
require_once(PATH_BE_table.'lib/class.tx_table_db_access.php');

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_article_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_basket.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_billdelivery_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_category.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_content.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_creditpoints_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_currency_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_db.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_email.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_email_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_finalize_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_gifts_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_list_view.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_memo_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_order.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_page.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_paymentshipping.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricecalc_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_price.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_product.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_single_view.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_tracking_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_view_div.php');



class tx_ttproducts_pi1 extends fhlibrary_pibase {
	var $prefixId = 'tx_ttproducts_pi1';	// Same as class name
	var $scriptRelPath = 'pi1/class.tx_ttproducts_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = TT_PRODUCTS_EXTkey;	// The extension key.

	var $cObj;		// The backReference to the mother cObj object set at call time

		// Internal
	//var $pid_list='';

	var $uid_list='';					// List of existing uid's from the basket, set by initBasket()
	//var $pageArray=array();				// Is initialized with an array of the pages in the pid-list
	var $orderRecord = array();			// Will hold the order record if fetched.


		// Internal: init():
	var $templateCode='';				// In init(), set to the content of the templateFile. Used by default in getBasket()

	var $config=array();				// updated configuration
	var $conf=array();					// original configuration
	var $tt_product_single='';
	var $globalMarkerArray=array();
	var $externalCObject='';
       // mkl - multicurrency support
	var $currency = '';				// currency iso code for selected currency
	var $baseCurrency = '';			// currency iso code for default shop currency
	var $xrate = 1.0;				// currency exchange rate (currency/baseCurrency)

	var $mkl; 					// if compatible to mkl_products
	var $errorMessage;			// if an error occurs, set the output text here.
	var $tt_products; 				// object of the type tx_table_db
	var $tt_products_articles;		// object of the type tx_table_db

	var $category; 					// object of the type tx_ttproducts_category
	var $content; 					// object of the type tx_ttproducts_content
	var $order;	 					// object of the type tx_ttproducts_page
	var $page;	 					// object of the type tx_ttproducts_page
	var $paymentshipping; 			// object of the type tx_ttproducts_paymentshipping
	var $price;	 					// object for price functions

	var $basket;				// basket object
	
	var $pi_checkCHash = TRUE;

	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	function main_products($content,$conf)	{
		global $TSFE;

		$this->init ($content, $conf, $this->config);

		$codes=t3lib_div::trimExplode(',', $this->config['code'],1);
		if (!count($codes))     $codes=array('HELP');

		if (t3lib_div::_GP('mode_update'))
			$updateMode = 1;
		else
			$updateMode = 0;

		if (!$this->errorMessage) {
			$this->basket->init($this, $this->conf, $this->config, $this->templateCode, $TSFE->fe_user->getKey('ses','recs'), $updateMode, 
						$this->page, $this->content, $this->tt_products, $this->category, $this->price, $this->paymentshipping, $this->order); // Must do this to initialize the basket...
		}

		// *************************************
		// *** Listing items:
		// *************************************

		$codes = $this->sortCodes($codes);
		if (!$this->errorMessage) {
			$content .= $this->basket->products_basket($codes, $this->errorMessage);
		}
		reset($codes);
		//$TSFE->set_no_cache(); // uncomment this line if you have a problem with the cache
		while(!$this->errorMessage && list(,$theCode)=each($codes))	{
			$theCode = (string) trim($theCode);
			$contentTmp = '';
			switch($theCode)	{
				case 'SEARCH':
					$TSFE->set_no_cache();
					// no break!
				case 'LIST':
					if ($this->conf['PIDbasket']) {
						$TSFE->set_no_cache();
					}
					// no break!
				case 'LISTGIFTS':
				case 'LISTHIGHLIGHTS':
				case 'LISTNEWITEMS':
				case 'LISTOFFERS':
				case 'SINGLE':
					if (count($this->basket->itemArray)) {
						$TSFE->set_no_cache();
					}
					$contentTmp=$this->products_display($theCode, $this->errorMessage);
				break;
				case 'OVERVIEW':
					if (count($this->basket->itemArray)) {
						$TSFE->set_no_cache();
					}
					break;
				case 'BASKET':
				case 'FINALIZE':
				case 'INFO':
				case 'PAYMENT':
					$TSFE->set_no_cache();
						// nothing here any more. This work is done in the call of $this->basket->products_basket($codes) before
				break;
				case 'BILL':
				case 'DELIVERY':
				case 'TRACKING':
					$TSFE->set_no_cache();
					$contentTmp=tx_ttproducts_tracking_div::products_tracking($this, $this->conf, $this->config, $this->basket, $this->content, $this->category, $this->price, $theCode);
				break;
				case 'MEMO':
					$TSFE->set_no_cache();
					$contentTmp=tx_ttproducts_memo_div::memo_display($this, $theCode, $this->errorMessage);
					break;
				case 'CURRENCY':
					$TSFE->set_no_cache();
					$contentTmp=tx_ttproducts_currency_div::currency_selector($theCode);
				break;
/* Added Els: case ORDERS line 253-255 */
				case 'ORDERS':
					$TSFE->set_no_cache();
					$contentTmp=$this->order->orders_display($theCode);
				break;
				default:	// 'HELP'
					$contentTmp = 'error';
				break;
			}
			if ($contentTmp == 'error') {
					$helpTemplate = $this->cObj->fileResource('EXT:'.TT_PRODUCTS_EXTkey.'/template/products_help.tmpl');

						// Get language version
					$helpTemplate_lang='';
					if ($this->LLkey)	{$helpTemplate_lang = $this->cObj->getSubpart($helpTemplate,'###TEMPLATE_'.$this->LLkey.'###');}
					$helpTemplate = $helpTemplate_lang ? $helpTemplate_lang : $this->cObj->getSubpart($helpTemplate,'###TEMPLATE_DEFAULT###');
						// Markers and substitution:
					$markerArray['###PATH###'] = t3lib_extMgm::siteRelPath(TT_PRODUCTS_EXTkey);
					$markerArray['###ERROR_MESSAGE###'] = ($this->errorMessage ? '<b>'.$this->errorMessage.'</b><br/>' : '');
					$content.=$this->cObj->substituteMarkerArray($helpTemplate,$markerArray);
					unset($this->errorMessage);
					break; // while
			} else {
				$content.=$contentTmp;
			}
		}

		if ($this->errorMessage) {
			$content = '<p><b>'.$this->errorMessage.'</b></p>';
		}
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * does the initialization stuff
     *
     * @param       string          content string
     * @param       string          configuration array
     * @param       string          modified configuration array
     * @return      void
 	 */
	function init (&$content,&$conf, &$config) {
		global $TSFE;

			// getting configuration values:
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$this->initTables();
		
		$this->basket = t3lib_div::makeInstance('tx_ttproducts_basket');

		// $TSFE->set_no_cache();
 
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

		$config['code'] = $this->getCodes($this->conf['code'], $this->conf['code.'], $this->conf['defaultCode'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['useFlexforms']);

		$this->config['limit'] = $this->conf['limit'] ? $this->conf['limit'] : 50;
		$this->config['limitImage'] = t3lib_div::intInRange($this->conf['limitImage'],0,15);
		$this->config['limitImage'] = $this->config['limitImage'] ? $this->config['limitImage'] : 1;
		$this->config['limitImageSingle'] = t3lib_div::intInRange($this->conf['limitImageSingle'],0,15);
		$this->config['limitImageSingle'] = $this->config['limitImageSingle'] ? $this->config['limitImageSingle'] : 1;

		$this->config['pid_list'] = trim($this->cObj->stdWrap($this->conf['pid_list'],$this->conf['pid_list.']));
		//$this->config['pid_list'] = $this->config['pid_list'] ? $this->config['pid_list'] : $TSFE->id;
		$this->page->setPidlist($this->config['pid_list']);				// The list of pid's we're operation on. All tt_products records must be in the pidlist in order to be selected.

		$this->config['recursive'] = t3lib_div::intInRange($this->conf['recursive'],0,100);
		$this->config['storeRootPid'] = $this->conf['PIDstoreRoot'] ? $this->conf['PIDstoreRoot'] : $TSFE->tmpl->rootLine[0][uid];
		$this->config['priceNoReseller'] = $this->conf['priceNoReseller'] ? t3lib_div::intInRange($this->conf['priceNoReseller'],2,2) : NULL;

			// If the current record should be displayed.
		$this->config['displayCurrentRecord'] = $this->conf['displayCurrentRecord'];
		if ($this->config['displayCurrentRecord'])	{
			$this->config['code']='SINGLE';
			$this->tt_product_single = true;
		} else {
			$this->tt_product_single = t3lib_div::_GP('tt_products');
		}

		if ($this->conf['templateFile']) {
			// template file is fetched. The whole template file from which the various subpart are extracted.
			$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);
		}
		if (!$this->conf['templateFile'] || empty($this->templateCode)) {
			$this->errorMessage = $this->pi_getLL('no template').' tt_products.file.templateFile.';
		}

			// globally substituted markers, fonts and colors.
		$splitMark = md5(microtime());
		$globalMarkerArray=array();
		list($globalMarkerArray['###GW1B###'],$globalMarkerArray['###GW1E###']) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf['wrap1.']));
		list($globalMarkerArray['###GW2B###'],$globalMarkerArray['###GW2E###']) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf['wrap2.']));
		$globalMarkerArray['###GC1###'] = $this->cObj->stdWrap($this->conf['color1'],$this->conf['color1.']);
		$globalMarkerArray['###GC2###'] = $this->cObj->stdWrap($this->conf['color2'],$this->conf['color2.']);
		$globalMarkerArray['###GC3###'] = $this->cObj->stdWrap($this->conf['color3'],$this->conf['color3.']);
		$globalMarkerArray['###DOMAIN###'] = $this->conf['domain'];
            // Shopadmin eMail by WEBcoast.ch
        $globalMarkerArray['###SHOPADMIN_EMAIL###'] = $this->conf['orderEmail_from'];

			// Substitute Global Marker Array
		$this->templateCode= $this->cObj->substituteMarkerArrayCached($this->templateCode, $globalMarkerArray);

			// This cObject may be used to call a function which manipulates the shopping basket based on settings in an external order system. The output is included in the top of the order (HTML) on the basket-page.
		$this->externalCObject = $this->getExternalCObject('externalProcessing');

			// Initializes object
		$this->globalMarkerArray = $globalMarkerArray;

			// price
		$this->price = t3lib_div::makeInstance('tx_ttproducts_price');
		$this->price->init($this, $this->conf, $this->config);
	
			// paymentshipping
		$this->paymentshipping = t3lib_div::makeInstance('tx_ttproducts_paymentshipping');
		$this->paymentshipping->init($this, $this->conf, $this->price);

			// order
		$this->order = t3lib_div::makeInstance('tx_ttproducts_order');
		$this->order->init($this, $this->conf, $this->basket);

	} // init



	/**
	 * Getting the table definitions
	 */
	function initTables()	{
//		$this->tt_products = t3lib_div::makeInstance('tx_table_db');
//		$this->tt_products->setTCAFieldArray('tt_products','products');
		$this->tt_products_articles = t3lib_div::makeInstance('tx_table_db');
		$this->tt_products_articles->setTCAFieldArray('tt_products_articles','articles');

		$this->category = t3lib_div::makeInstance('tx_ttproducts_category');
		$this->category->init();
		$this->content = t3lib_div::makeInstance('tx_ttproducts_content');
		$this->content->init();
		$this->page = t3lib_div::makeInstance('tx_ttproducts_page');
		$this->page->init();
		$this->tt_products = t3lib_div::makeInstance('tx_ttproducts_product');
		$this->tt_products->init();	
	} // initTables


	/**
	 * returns the codes in the order in which they have to be processed
     *
     * @param       string          $fieldname is the field in the table you want to create a JavaScript for
     * @return      void
 	 */
	function sortCodes($codes)	{
		$retCodes = array();
		$codeArray =  array (
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
					$retCodes[$key-1] = $theCode;
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



	function load_noLinkExtCobj()	{
		if ($this->conf['externalProcessing_final'] || is_array($this->conf['externalProcessing_final.']))	{	// If there is given another cObject for the final order confirmation template!
			$this->externalCObject = $this->getExternalCObject('externalProcessing_final');
		}
	} // load_noLinkExtCobj




	/**
	 * Displaying single products/ the products list / searching
	 */
	function products_display($theCode, &$errorMessage, $memoItems='')	{
		global $TSFE;


		$pid = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : (t3lib_div::_GP('backPID') ? t3lib_div::_GP('backPID') : $TSFE->id));
		$formUrl = $this->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams());  //  $this->getLinkUrl($this->conf['PIDbasket']);
//		if (!$formUrl) {
//			$formUrl = $this->pi_getPageLink(t3lib_div::_GP('backPID'),'',tx_ttproducts_view_div::getLinkParams());  // $this->getLinkUrl(t3lib_div::_GP('backPID'));
//		}

		$error_code = array();
		if (($theCode=='SINGLE') || ($this->tt_product_single && !$this->conf['NoSingleViewOnList'])) {
			if (!$this->tt_product_single) {
				$this->tt_product_single = $this->conf['defaultProductID'];
			}
			$extVars= t3lib_div::_GP('ttp_extvars');
				// performing query:
			if (!$this->page->pid_list) {
				$this->page->setPidlist($this->config['storeRootPid']);
			}
		
			$this->page->initRecursive(999, $this);
			//tx_ttproducts_page_div::generatePageArray();

			// List single product:
			$singleView = t3lib_div::makeInstance('tx_ttproducts_single_view');
			$singleView->init ($this, $this->conf, $this->config, $this->page, $this->content, $this->tt_products, $this->category, $this->tt_product_single, $extVars, $formUrl);
			
			$content = $singleView->printView($this->templateCode, $this->basket, $error_code);
		} else {
			// List all products:
			$listView = t3lib_div::makeInstance('tx_ttproducts_list_view');
			$listView->init ($this, $this->conf, $this->config, $this->page, $this->content, $this->tt_products, $this->category, $formUrl);
			
			$content = $listView->printView($this->templateCode, $theCode, $this->basket, $memoItems, $error_code);
		}
		
		if ($error_code[0]) {
			$messageArr = array(); 
			if ($error_code[1]) {
				$i = 0;
				foreach ($error_code as $key => $indice) {
					if ($key == 0) {
						$messageArr =  explode('|', $message = $this->pi_getLL($indice));
						$content.=$messageArr[0];
					} else {
						$content.=$indice.$messageArr[$i];
					}
					$i++;
				}
			}
			// $content.=$messageArr[0].intval($this->uid) .$messageArr[1];
		}

		return $content;
	}	// products_display



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


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/pi1/class.tx_ttproducts_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/pi1/class.tx_ttproducts_pi1.php']);
}


?>
