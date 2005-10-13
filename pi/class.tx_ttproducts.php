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
require_once(PATH_BE_ttproducts.'pi/class.tx_ttproducts_htmlmail.php');

require_once(PATH_BE_table.'lib/class.tx_table_db.php');
require_once(PATH_BE_table.'lib/class.tx_table_db_access.php');

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_article_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_basket_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_billdelivery_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_category.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_creditpoints_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_currency_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_db.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_email.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_email_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_finalize_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_gifts_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_memo_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_order_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_page_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_paymentshipping_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_price_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricecalc_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_tracking_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_view_div.php');



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

	var $config=array();				// updated configuration
	var $conf=array();					// original configuration
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
	var $errorMessage;			// if an error occurs, set the output text here.
	var $tt_products;				// object of the type tx_table_db
	var $tt_products_articles;		// object of the type tx_table_db
	
	var $category; 					// object of the type tx_ttproducts_category
	var $feuserextrafields;			// exension with additional fe_users fields

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
			tx_ttproducts_basket_div::initBasket($TSFE->fe_user->getKey('ses','recs'), $updateMode); // Must do this to initialize the basket...
		}

		// *************************************
		// *** Listing items:
		// *************************************

		$this->itemArray = array();
		$codes = $this->sort_codes($codes);
		if (!$this->errorMessage) {
			$content .= tx_ttproducts_basket_div::products_basket($codes, $this->errorMessage);
		}
		reset($codes);
		while(!$this->errorMessage && list(,$theCode)=each($codes))	{
			$theCode = (string) trim($theCode);
			$contentTmp = '';
			switch($theCode)	{
				case 'LIST':
				case 'LISTGIFTS':
				case 'LISTHIGHLIGHTS':
				case 'LISTNEWITEMS':
				case 'LISTOFFERS':
				case 'SEARCH':
				case 'SINGLE':
					$contentTmp=$this->products_display($theCode, $this->errorMessage);
				break;
				case 'BASKET':
				case 'FINALIZE':
				case 'INFO':
				case 'OVERVIEW':
				case 'PAYMENT':
						// nothing here any more. This work is done in the call of tx_ttproducts_basket_div::products_basket($codes) before
				break;
				case 'BILL':
				case 'DELIVERY':
				case 'TRACKING':
					$contentTmp=tx_ttproducts_tracking_div::products_tracking($theCode);
				break;
				case 'MEMO':
					$contentTmp=tx_ttproducts_memo_div::memo_display($theCode, $this->errorMessage);
				break;
				case 'CURRENCY':
					$contentTmp=tx_ttproducts_currency_div::currency_selector($theCode);
				break;
/* Added Els: case ORDERS line 253-255 */
				case 'ORDERS':
					$contentTmp=tx_ttproducts_order_div::orders_display($theCode);
				break;
				default:	// 'HELP'
					$contentTmp = 'error';
				break;
			}
			if ($contentTmp == 'error') {				
					$helpTemplate = $this->cObj->fileResource('EXT:'.TT_PRODUCTS_EXTkey.'/template/products_help.tmpl');

						// Get language version
					$helpTemplate_lang='';
					if ($this->langKey)	{$helpTemplate_lang = $this->cObj->getSubpart($helpTemplate,'###TEMPLATE_'.$this->langKey.'###');}
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

		$TSFE->set_no_cache();
    	// multilanguage support
        $this->language = $TSFE->config['config']['sys_language_uid'];
        $this->langKey = strtoupper($TSFE->config['config']['language']);	// TYPO3_languages

		// *************************************
		// *** getting configuration values:
		// *************************************

		// store if feuserextrafields is loaded
		$this->feuserextrafields = t3lib_extMgm::isLoaded('feuserextrafields');
		
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

		if (empty($this->conf['code'])) {
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['useFlexforms'] == 1) {
				// Converting flexform data into array:
				$this->pi_initPIflexForm();
				$this->config['code'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'display_mode');
			} else {
				$this->config['code'] = strtoupper(trim($this->cObj->stdWrap($this->conf['code'],$this->conf['code.'])));
			}
			if (empty($this->config['code'])) {
				$this->config['code'] = strtoupper($this->conf['defaultCode']);
			}
		} else {
			$this->config['code'] = $this->conf['code'];
		}

		$this->config['limit'] = $this->conf['limit'] ? $this->conf['limit'] : 50;
		$this->config['limitImage'] = t3lib_div::intInRange($this->conf['limitImage'],0,15);
		$this->config['limitImage'] = $this->config['limitImage'] ? $this->config['limitImage'] : 1;
		$this->config['limitImageSingle'] = t3lib_div::intInRange($this->conf['limitImageSingle'],0,15);
		$this->config['limitImageSingle'] = $this->config['limitImageSingle'] ? $this->config['limitImageSingle'] : 1;

		$this->config['pid_list'] = trim($this->cObj->stdWrap($this->conf['pid_list'],$this->conf['pid_list.']));
		//$this->config['pid_list'] = $this->config['pid_list'] ? $this->config['pid_list'] : $TSFE->id;
		tx_ttproducts_page_div::setPidlist($this->config['pid_list']);				// The list of pid's we're operation on. All tt_products records must be in the pidlist in order to be selected.

		$this->config['recursive'] = t3lib_div::intInRange($this->conf['recursive'],0,100);
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

		if ($this->conf['templateFile']) {
			// template file is fetched. The whole template file from which the various subpart are extracted.
			$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);
		} else {
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

			// Substitute Global Marker Array
		$this->templateCode= $this->cObj->substituteMarkerArrayCached($this->templateCode, $globalMarkerArray);

			// This cObject may be used to call a function which manipulates the shopping basket based on settings in an external order system. The output is included in the top of the order (HTML) on the basket-page.
		$this->externalCObject = $this->getExternalCObject('externalProcessing');

			// Initializes object
		$this->TAXpercentage = doubleval($this->conf['TAXpercentage']);		// Set the TAX percentage.
		$this->globalMarkerArray = $globalMarkerArray;

		$this->category = t3lib_div::makeInstance('tx_ttproducts_category');
		$this->category->init();
	}


	/**
	 * Getting the table definitions
	 */
	function initTables()	{
		$this->tt_products = t3lib_div::makeInstance('tx_table_db');
		$this->tt_products->setTCAFieldArray('tt_products');
		$this->tt_products_articles = t3lib_div::makeInstance('tx_table_db');
		$this->tt_products_articles->setTCAFieldArray('tt_products_articles');
	} // initTables

	
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
		$formUrl = $this->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams());  //  $this->getLinkUrl($this->conf['PIDbasket']);
//		if (!$formUrl) {
//			$formUrl = $this->pi_getPageLink(t3lib_div::_GP('backPID'),'',tx_ttproducts_view_div::getLinkParams());  // $this->getLinkUrl(t3lib_div::_GP('backPID'));
//			debug ($formUrl, '$formUrl', __LINE__, __FILE__);
//		}
		if (($theCode=='SINGLE') || ($this->tt_product_single && !$this->conf['NoSingleViewOnList'])) {
			// List single product:

			if (!$this->tt_product_single) {
				$this->tt_product_single = $this->conf['defaultProductID'];
			}

			$extVars= t3lib_div::_GP('ttp_extvars');

				// performing query:
			if (!$this->pid_list) {
				tx_ttproducts_page_div::setPidlist($this->config['storeRootPid']);
			}
			
			tx_ttproducts_page_div::initRecursive(999);
			tx_ttproducts_page_div::generatePageArray();

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
				$giftNumberArray = tx_ttproducts_gifts_div::getGiftNumbers ($row['uid'], $extVars);

				if ($this->config['displayCurrentRecord'])	{
					$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY_RECORDINSERT###';
				} else if (count($giftNumberArray)) {
					$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY_GIFT###';
				} else if ($row['inStock']==0 && $this->conf['showProductsNotInStock']) {
					$itemFrameTemplate = "###ITEM_SINGLE_DISPLAY_NOT_IN_STOCK###";
				} else {
					$itemFrameTemplate = '###ITEM_SINGLE_DISPLAY###';
				}
				$itemFrameWork = $this->cObj->getSubpart($this->templateCode,tx_ttproducts_view_div::spMarker($itemFrameTemplate));


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
				$pageCatTitle = '';
				if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'] == 1) {
						$pageCatTitle = $this->pageArray[$row['pid']]['title'].'/';
				}		
				
				$catTmp = '';
				if ($row['category']) {
					$catTmp = $this->category->getCategory($row['category']);
					$catTmp = $catTmp['title'];	
				}
				$catTitle = $pageCatTitle.$catTmp;

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
				$wrappedSubpartArray['###LINK_ITEM###']= array('<a href="'. $this->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams())  /* $this->getLinkUrl(t3lib_div::_GP('backPID'))*/ .'">','</a>');

				if( $datasheetFile == '' )  {
					$wrappedSubpartArray['###LINK_DATASHEET###']= array('<!--','-->');
				}  else  {
					$wrappedSubpartArray['###LINK_DATASHEET###']= array('<a href="uploads/tx_ttproducts/datasheet/'.$datasheetFile.'">','</a>');
				}

				$item = tx_ttproducts_basket_div::getItem($row);
				$forminfoArray = array ('###FORM_NAME###' => 'item_'.$this->tt_product_single);
				$markerArray = tx_ttproducts_view_div::getItemMarkerArray ($item,$catTitle,$this->config['limitImageSingle'],'image', $forminfoArray);

				$subpartArray = array();

				$markerArray['###FORM_NAME###']=$forminfoArray['###FORM_NAME###'];

				$markerArray['###FORM_URL###']=$formUrl.'&tt_products='.$this->tt_product_single ;

				$url = $this->pi_getPageLink($TSFE->id,'',tx_ttproducts_view_div::getLinkParams()) ; // $this->getLinkUrl('','tt_products');

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
				$wherestock = ($this->conf['showNotinStock'] ? '' : 'AND (inStock <>0) ');
				$queryprev = $queryPrevPrefix .' AND pid IN ('.$this->pid_list.')'. $wherestock . $this->cObj->enableFields('tt_products');
				$resprev = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $queryprev,'','uid');

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
						$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($markerArray, $giftnumber);
						$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'].'_'.$giftnumber;
						$markerArray['###FORM_ONSUBMIT###']='return checkParams (document.'.$markerArray['###FORM_NAME###'].')';
						$markerArray['###FORM_URL###'] = $this->pi_getPageLink(t3lib_div::_GP('backPID'),'',tx_ttproducts_view_div::getLinkParams('', array('tt_products' => $row['uid'], 'ttp_extvars' => htmlspecialchars($extVars)))); // $this->getLinkUrl(t3lib_div::_GP('backPID')).'&tt_products='.$row['uid'].'&ttp_extvars='.htmlspecialchars($extVars);

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
				$messageArr =  explode('|', $message = $this->pi_getLL('wrong parameter'));
				$content.=$messageArr[0].intval($this->tt_product_single) .$messageArr[1];
			}
		} else {		
			$content='';
	// List products:
			$where='';
			if ($theCode=='SEARCH')	{
					// Get search subpart
				$t['search'] = $this->cObj->getSubpart($this->templateCode,tx_ttproducts_view_div::spMarker('###ITEM_SEARCH###'));
					// Substitute a few markers
				$out=$t['search'];
				$pid = ( $this->conf['PIDsearch'] ? $this->conf['PIDsearch'] : $TSFE->id);
				$out=$this->cObj->substituteMarker($out, '###FORM_URL###', $this->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams())); // $this->getLinkUrl($this->conf['PIDsearch']));
				$out=$this->cObj->substituteMarker($out, '###SWORDS###', htmlspecialchars(t3lib_div::_GP('swords')));
					// Add to content
				$content.=$out;
				if (t3lib_div::_GP('swords'))	{
					$where = tx_ttproducts_div::searchWhere(trim(t3lib_div::_GP('swords')));
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
				tx_ttproducts_page_div::initRecursive($this->config['recursive']);
				tx_ttproducts_page_div::generatePageArray();

					// Get products
				$selectConf = Array();
				$selectConf['pidInList'] = $this->pid_list;
			#debug ($this->pid_list, '$this->pid_list', __LINE__, __FILE__);
				
				$wherestock = ($this->config['showNotinStock'] ? '' : 'AND (inStock <> 0) ');
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
					$t['listFrameWork'] = $this->cObj->getSubpart($this->templateCode,tx_ttproducts_view_div::spMarker('###MEMO_TEMPLATE###'));
				} else if ($theCode=='LISTGIFTS') {
					$t['listFrameWork'] = $this->cObj->getSubpart($this->templateCode,tx_ttproducts_view_div::spMarker('###ITEM_LIST_GIFTS_TEMPLATE###'));
				} else {
					$t['listFrameWork'] = $this->cObj->getSubpart($this->templateCode,tx_ttproducts_view_div::spMarker('###ITEM_LIST_TEMPLATE###'));
				}

				$t['categoryTitle'] = $this->cObj->getSubpart($t['listFrameWork'],'###ITEM_CATEGORY###');
				$t['itemFrameWork'] = $this->cObj->getSubpart($t['listFrameWork'],'###ITEM_LIST###');
				$t['item'] = $this->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');

				$markerArray=array();
				$markerArray['###FORM_URL###']=$formUrl; // Applied later as well.

				if ($theCode=='LISTGIFTS') {
					$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($markerArray, $this->giftnumber);
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
									if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'] == 1) {
										$pageCatTitle = $this->pageArray[$row['pid']]['title'].'/';
									}
									$tmpCategory = ($row['category'] ? $this->category->getCategory($row['category']) : array ('title' => ''));
									$catTitle= $pageCatTitle.($tmpCategory['title']);
									
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
							$pid = tx_ttproducts_page_div::getPID($this->conf['PIDitemDisplay'], $this->conf['PIDitemDisplay.'], $row);
							$wrappedSubpartArray['###LINK_ITEM###']=  array('<a href="'. $this->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams('', $addQueryString)).'"'.$css_current.'>','</a>'); // array('<a href="'.$this->getLinkUrl($pid,'',$addQueryString).'"'.$css_current.'>','</a>');

							if( $datasheetFile == '' )  {
								$wrappedSubpartArray['###LINK_DATASHEET###']= array('<!--','-->');
							}  else  {
								$wrappedSubpartArray['###LINK_DATASHEET###']= array('<a href="uploads/tx_ttproducts/datasheet/'.$datasheetFile.'">','</a>');
							}

							$item = tx_ttproducts_basket_div::getItem($row);
/* Added Bert: in stead of listImage -> Image, reason: images are read from directory */
//							$markerArray = tx_ttproducts_view_div::getItemMarkerArray ($item,$catTitle, $this->config['limitImage'],'image');
							$markerArray = tx_ttproducts_view_div::getItemMarkerArray ($item,$catTitle, $this->config['limitImage'],'listImage');
							if ($theCode=='LISTGIFTS') {
								$markerArray = tx_ttproducts_gifts_div::addGiftMarkers ($markerArray, $this->giftnumber);
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
							$markerArray['###FORM_MEMO###'] = $this->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams()); //$this->getLinkUrl($this->conf['PIDmemo']);
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
				if (count($productsArray) == 0) {
					$content = 'error';
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
					$tempUrl = $this->pi_linkToPage($splitMark,$TSFE->id,'',tx_ttproducts_view_div::getLinkParams('', array('begin_at' => $next)));

					$wrappedSubpartArray['###LINK_NEXT###']=  explode ($splitMark, $tempUrl);  // array('<a href="'.$url.'&begin_at='.$next.'">','</a>');
				} else {
					$subpartArray['###LINK_NEXT###']='';
				}
				if ($begin_at)	{
					$prev = ($begin_at-$this->config['limit'] < 0) ? 0 : $begin_at-$this->config['limit'];
					$tempUrl = $this->pi_linkToPage($splitMark,$TSFE->id,'',tx_ttproducts_view_div::getLinkParams('', array('begin_at' => $prev)));
					$wrappedSubpartArray['###LINK_PREV###']=explode ($splitMark, $tempUrl); // array('<a href="'.$url.'&begin_at='.$prev.'">','</a>');
				} else {
					$subpartArray['###LINK_PREV###']='';
				}
				$markerArray['###BROWSE_LINKS###']='';
				if ($productsCount > $this->config['limit'] )	{ // there is more than one page, so let's browse
					$wrappedSubpartArray['###LINK_BROWSE###']=array('',''); // <- this could be done better I think, or not?
					for ($i = 0 ; $i < ($productsCount/$this->config['limit']); $i++) 	{
						if (($begin_at >= $i*$this->config['limit']) && ($begin_at < $i*$this->config['limit']+$this->config['limit'])) 	{
							$markerArray['###BROWSE_LINKS###'].= ' <b>'.(string)($i+1).'</b> ';
							//	you may use this if you want to link to the current page also
							//
						} else {
							$tempUrl = $this->pi_linkToPage((string)($i+1),$TSFE->id,'',tx_ttproducts_view_div::getLinkParams('', array('begin_at' => (string)($i * $this->config['limit']))));
							$markerArray['###BROWSE_LINKS###'].= $tempUrl; // ' <a href="'.$url.'&begin_at='.(string)($i * $this->config['limit']).'">'.(string)($i+1).'</a> ';
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
				$content.=$this->cObj->getSubpart($this->templateCode,tx_ttproducts_view_div::spMarker('###ITEM_SEARCH_EMPTY###'));
			}
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




}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/pi/class.tx_ttproducts.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/pi/class.tx_ttproducts.php']);
}


?>
