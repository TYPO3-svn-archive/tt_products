<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
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
 * main loop
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_main {
		// Internal
	public $uid_list='';			// List of existing uid's from the basket, set by initBasket()
	public $orderRecord = array();		// Will hold the order record if fetched.

		// Internal: init():
	public $config=array();			// updated configuration
	public $conf;
	public $tt_product_single=array();
	public $externalCObject='';
	public $control;			// object for the control of the application
	public $singleView;			// single view object
	public $memoView;			// memo view and data object

	public $pid;				// the page to which the script shall go
	public $piVars;
	public $ajax;				// ajax object
	public $javaScriptObj;			// JavaScript object
	public $codeArray;			// Codes
	public $tableArray;			// contains the table name as key and the part of the file name for the corresponding data and view
	public $bNoCachePossible = TRUE;	// if the cache may be turned off
	public $pageAsCategory;			// > 0 if pages are used as categories
	/**
	 * Content object
	 * @var tslib_cObj
	 */
	public $cObj;
	protected $bSingleFromList = FALSE;	// if the single view shall be shown instead of a list view
	public $pibaseClass;			// class of the pibase object
	/**
	 * The list of codes that must run uncached. Note that if you combine any
	 * of these codes with cached codes in TS or flexform, those cached will
	 * be rendered uncached too! Better insert two or more instances of
	 * tt_products where cached and uncached codes are separate.
	 *
	 * @var array
	 */
	protected static $uncachedCodes = array(
		'BASKET',
		'BILL',
		'CURRENCY',
		'DELIVERY',
		'FINALIZE',
		'HELP',
		'INFO',
		'MEMO',
		'MEMODAM',
		'MEMODAMOVERVIEW',
		'ORDERS',
		'OVERVIEW',
		'PAYMENT',
		'SEARCH',
		'TRACKING'
	);



	/**
	 * does the initialization stuff
	 *
	 * @param	string		content string
	 * @param	string		configuration array
	 * @param	string		modified configuration array
	 * @return	  bool		if TRUE processing should be done
	 */
	public function init (&$content, $conf, &$config, $pibaseClass, &$error_code, $bRunAjax = FALSE) {
		global $TSFE;

		$this->setSingleFromList(FALSE);
		$pibaseObj = t3lib_div::getUserObj('&'.$pibaseClass);
		$this->cObj = $pibaseObj->cObj;
		$this->tt_product_single = array();

		if ($conf['no_cache'] && $this->convertToUserInt()) {
			// Compatibility with previous versions where users could set
			// 'no_cache' TS option. This option does not exist anymore and we
			// simply convert the plugin to USER_INT if that old option is set.
			return FALSE;
		}

		$cacheObj = t3lib_div::getUserObj('tx_ttproducts_cache');

		$rc = TRUE;
		$flexformArray = t3lib_div::xml2array($this->cObj->data['pi_flexform']);
		$flexformParamMode = tx_div2007_ff::get($flexformArray, 'param_mode');
		$mode = intval(t3lib_div::_GP('mode'));

		if($flexformParamMode != '' && !t3lib_div::inList($flexformParamMode, $mode)) {
			return FALSE;
		}

		$flexformTyposcript = tx_div2007_ff::get($flexformArray, 'myTS');

		if($flexformTyposcript) {
			$tsparser = tx_div2007_core::newTsParser();
			// Copy conf into existing setup
			$tsparser->setup = $conf;
			// Parse the new Typoscript
			$tsparser->parse($flexformTyposcript);
			// Copy the resulting setup back into conf
			$conf = $tsparser->setup;
		}

		$this->conf = &$conf;
		$this->config = &$config;
		$this->piVars = &$pibaseObj->piVars;
		$this->pibaseClass = $pibaseClass;
		$config['LLkey'] = $pibaseObj->LLkey;

			// basket
		$basketObj = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$eInfo = tx_div2007_alpha5::getExtensionInfo_fh003(TT_PRODUCTS_EXT);
		$this->config['version'] = $eInfo['version'];

		// Save the original flexform in case if we need it later as USER_INT
		$this->cObj->data['_original_pi_flexform'] = $this->cObj->data['pi_flexform'];
		$this->cObj->data['pi_flexform'] = t3lib_div::xml2array($this->cObj->data['pi_flexform']);

		$config['code'] =
			tx_div2007_alpha5::getSetupOrFFvalue_fh002(
				$this->cObj,
	 			$conf['code'],
	 			$conf['code.'],
				$conf['defaultCode'],
				$this->cObj->data['pi_flexform'],
				'display_mode',
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['useFlexforms']
			);

		$this->codeArray = t3lib_div::trimExplode(',', $config['code'], 1);
		$required_pivars = tx_div2007_ff::get($flexformArray, 'required_pivars');
		$requiredArray = t3lib_div::trimExplode(',', $required_pivars);
		$bDoProcessing = TRUE;
		if (count ($requiredArray))	{
			foreach ($requiredArray as $k => $pivar)	{
				if ($pivar && $pivar != 'empty')	   {
					$gpVar = t3lib_div::_GP($pivar);
					if (
						!isset($this->piVars[$pivar]) &&
						!isset($gpVar)
					)	{
						$bDoProcessing = FALSE;
						break;
					}
				}
			}
		}

		if ($bDoProcessing && $this->cObj->getUserObjectType() == tslib_cObj::OBJECTTYPE_USER) {
			$intersection = array_intersect(self::$uncachedCodes, $this->codeArray);
			if (count($intersection)) {
				$this->convertToUserInt();
				$bDoProcessing = FALSE;
			}
		}
		if (!$bDoProcessing)	{
			return FALSE;
		}

			// initialise AJAX at the beginning because the AJAX functions can set piVars
		if (!$bRunAjax && t3lib_extMgm::isLoaded('taxajax')) {
			include_once(PATH_BE_ttproducts.'eid/class.tx_ttproducts_ajax.php');
			$this->ajax = t3lib_div::makeInstance('tx_ttproducts_ajax');
			$this->ajax->init();
			$this->ajax->main($conf['ajaxDebug']);

			if (in_array('LISTARTICLES', $this->codeArray))	{
				$this->ajax->taxajax->registerFunction(array(TT_PRODUCTS_EXT . '_showArticle', $this, 'showArticle'));
			}
			$this->ajax->taxajax->registerFunction(array(TT_PRODUCTS_EXT . '_commands', $this, TT_PRODUCTS_EXT . '_commands'));
		}

		if (!$bRunAjax) {
			require_once(PATH_BE_ttproducts . 'eid/class.tx_ttproducts_db.php');
			$db = t3lib_div::makeInstance('tx_ttproducts_db');
			$db->init($conf, $config, $this->ajax, $pibaseObj);
		}

//            $this->taxajax->registerFunction(array('tt_products_changeValue',&$this,'tt_products_changeValue'));
//            $this->taxajax->registerFunction(array('tt_products_processFormData',&$this,'tt_products_processFormData'));

		if (!$bRunAjax && t3lib_extMgm::isLoaded('taxajax')) {
			if($_POST['xajax']){
				global $trans;
				$trans = $this;
				$this->ajax->taxajax->processRequests();
				exit();
			}
		}

		// *************************************
		// *** getting configuration values:
		// *************************************

		$backPID = $this->piVars['backPID'];
		$backPID = ($backPID ? $backPID : t3lib_div::_GP('backPID'));

		// page where to go usually
		$this->pid = ($this->conf['PIDbasket'] && $this->conf['clickIntoBasket'] ? $this->conf['PIDbasket'] : ($backPID ? $backPID : $TSFE->id));

		if ($this->conf['TAXmode'] == '' ||  $this->conf['TAXmode'] == '{$plugin.tt_products.TAXmode}')	{
			$this->conf['TAXmode'] == 1;
		}
		$this->pageAsCategory = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'];
		// get template suffix string
		$config['templateSuffix'] = strtoupper($this->conf['templateSuffix']);
		$templateSuffix = tx_div2007_ff::get($flexformArray, 'template_suffix');
		$templateSuffix = strtoupper($templateSuffix);
		$config['templateSuffix'] = ($templateSuffix ? $templateSuffix : $config['templateSuffix']);
		$config['templateSuffix'] = ($config['templateSuffix'] ? '_' . $config['templateSuffix'] : '');
		$config['limit'] = $this->conf['limit'] ? $this->conf['limit'] : 50;

		$config['limitImage'] = tx_div2007_core::intInRange($this->conf['limitImage'], 0, 50, 1);
		$config['limitImage'] = $config['limitImage'] ? $config['limitImage'] : 1;
		$config['limitImageSingle'] = tx_div2007_core::intInRange($this->conf['limitImageSingle'], 0, 50, 1);
		$config['limitImageSingle'] = $config['limitImageSingle'] ? $config['limitImageSingle'] : 1;
		$recursive = ($this->cObj->data['recursive'] ? $this->cObj->data['recursive']: $this->conf['recursive']);
		$config['recursive'] = tx_div2007_core::intInRange($recursive, 0, 100);

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

		if ($this->conf['priceNoReseller']) {
			$config['priceNoReseller'] = tx_div2007_core::intInRange($this->conf['priceNoReseller'], 2, 10);
		}

		if ($this->conf['pid_list'] == '{$plugin.tt_products.pid_list}')	{
			$this->conf['pid_list'] = '';
		}

		$tmp = $this->cObj->stdWrap($this->conf['pid_list'], $this->conf['pid_list.']);
// ($this->piVars['pid_list'] ? $this->piVars['pid_list']
		$pid_list = ($this->cObj->data['pages'] ? $this->cObj->data['pages'] : ($this->conf['pid_list.'] ? trim($tmp) : ''));
		$pid_list = ($pid_list ? $pid_list : $this->conf['pid_list']);

		$config['pid_list'] = ($pid_list ? $pid_list : $config['storeRootPid']);

			// If the current record should be displayed.
		$config['displayCurrentRecord'] = $this->conf['displayCurrentRecord'];
		if ($config['displayCurrentRecord'])	{
			// $config['code']='SINGLE';
			$row = $this->cObj->data;
			$this->tt_product_single['product'] = $row['uid'];
		} else {
			$tmp = $this->piVars['product'];
			if (!$tmp)	{
				$ttpOld = t3lib_div::_GP('tt_products');
				if (isset($ttpOld) && !is_array($ttpOld))	{
					$tmp = $ttpOld;
				}
			}
			if (
				tx_div2007_core::testInt($tmp)
			) {
				$this->tt_product_single['product'] = intval($tmp);
			}
			$tmp = ($this->piVars['article'] ? $this->piVars['article'] : '');
			if (
				tx_div2007_core::testInt($tmp)
			) {
				$this->tt_product_single['article'] = $tmp;
			}
			$tmp = ($this->piVars['dam'] ? $this->piVars['dam'] : '');
			if (
				tx_div2007_core::testInt($tmp)
			) {
				$this->tt_product_single['dam'] = $tmp;
			}
		}

		$urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view');
		$urlObj->init($pibaseObj);

			// image
		$imageObj = t3lib_div::getUserObj('&tx_ttproducts_field_image');
		$imageObj->init($pibaseObj->cObj, $this->pibase);

			// image view
		$imageViewObj = t3lib_div::getUserObj('&tx_ttproducts_field_image_view');
		$imageViewObj->init($pibaseObj, $imageObj);

// 		tx_div2007_alpha5::loadLL_fh002($langObj,'EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml');
// 		tx_div2007_alpha5::loadLL_fh002($langObj,'EXT:'.TT_PRODUCTS_EXT.'/pi_search/locallang_db.xml');
// 		tx_div2007_alpha5::loadLL_fh002($langObj,'EXT:'.TT_PRODUCTS_EXT.'/pi1/locallang.xml');

			// get all extending TCAs
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['extendingTCA']))	{
			tx_div2007_alpha5::loadTcaAdditions_fh002($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['extendingTCA']);
		}

		$tmpObj = t3lib_div::getUserObj('&tx_ttproducts_css');
		$tmpObj->init(
			$pibaseObj
		);

			// price
		$priceObj = t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$priceViewObj = t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$priceViewObj->init(
			$pibaseObj,
			$this->cObj,
			$priceObj
		);

			// graduated price
		$graduatedPriceObj = t3lib_div::getUserObj('&tx_ttproducts_graduated_price');
		$graduatedPriceObj->init($this->conf['table.']['tt_products_graduated_price'], $this->conf['table.']['tt_products_mm_graduated_price']);

		$graduatedPriceViewObj = t3lib_div::getUserObj('&tx_ttproducts_graduated_price_view');
		$graduatedPriceViewObj->init($pibaseObj, $graduatedPriceObj);

		$this->javaScriptObj = t3lib_div::getUserObj('&tx_ttproducts_javascript');

			// JavaScript
		$this->javaScriptObj->init(
			$pibaseObj,
			$this->ajax
		);

		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$res = $markerObj->init(
			$this->cObj,
			$pibaseObj->piVars
		);
		$rc &= $res;

		if ($res == FALSE)	{
			$error_code = $markerObj->getErrorCode();
		}

			// basket view
		$this->control = t3lib_div::makeInstance('tx_ttproducts_control');

			// This cObject may be used to call a function which manipulates the shopping basket based on settings in an external order system. The output is included in the top of the order (HTML) on the basket-page.
		$this->externalCObject = tx_div2007_alpha5::getExternalCObject_fh003($pibaseObj, 'externalProcessing');

		$subpartmarkerObj = t3lib_div::getUserObj('&tx_ttproducts_subpartmarker');
		$subpartmarkerObj->init($this->cObj);

		$billdeliveryObj = t3lib_div::getUserObj('&tx_ttproducts_billdelivery');
		$billdeliveryObj->init(
			$this->cObj
		);

		return $rc;
	} // init


	public function &run ($pibaseClass, &$error_code, $content = '', $bRunAjax = FALSE) {
		global $TSFE;

		$bStoreBasket = TRUE;
		$errorMessage = '';
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');
		$pibaseObj = t3lib_div::getUserObj('&'.$pibaseClass);
		$templateObj = t3lib_div::getUserObj('&tx_ttproducts_template');
		$errorObj = t3lib_div::getUserObj('&tx_ttproducts_model_error');
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$showAmount = $cnf->getBasketConf('view','showAmount');
		$basketObj = t3lib_div::getUserObj('&tx_ttproducts_basket');

		if (!count($this->codeArray) && !$bRunAjax)	{
			$this->codeArray = array('HELP');
		}

		if ((t3lib_extMgm::isLoaded('taxajax')))	{
			if ($bRunAjax)	{
				// TODO: get AJAX configuration
			} else {
				$this->javaScriptObj->set('xajax');
			}
		}
		if (t3lib_div::_GP('mode_update'))	{
			$updateMode = 1;
		} else {
			$updateMode = 0;
		}

		if (
			isset($conf['basket.']) && $conf['basket.']['store'] == '0' ||
			count($this->codeArray) == 1 &&
			$this->codeArray[0] == 'OVERVIEW' &&
			isset($conf['basket.']) &&
			isset($conf['basket.']['activity.']) &&
			isset($conf['basket.']['activity.']['overview.']) &&
			$conf['basket.']['activity.']['overview.']['store'] == '0'
		) {
			$bStoreBasket = FALSE;
		}

		$recs = $TSFE->fe_user->getKey('ses','recs');

		$basketObj->init(
			$pibaseClass,
			$recs,
			$updateMode,
			$this->config['pid_list'],
			$this->conf['useArticles'],
			$bStoreBasket
		);

		// *************************************
		// *** Listing items:
		// *************************************
		$basketObj->getCalculatedBasket(); // get the basket->itemArray
		$basketObj->getCalculatedSums();

		$voucher = $tablesObj->get('voucher');
		$voucher->doProcessing($recs);
		$basketObj->addVoucherSums();

		$templateCode = $templateObj->get('BASKET', $langObj, $this->cObj, $templateFile, $errorMessage); // this is needed also if fe=0, because you could want so send emails.

		if (!$errorMessage && !count($error_code))	{
			$functablename = 'tt_products';
			tx_ttproducts_control_memo::process($functablename, $pibaseObj->piVars, $this->conf);

			$this->control->init (
				$pibaseClass,
				$basketObj->getFuncTablename(),
				$templateCode,
				$this->conf['useArticles'],
				$error_code
			);
			$content .= $this->control->doProcessing($this->codeArray, $errorMessage);
		}

		$contentBasket = $content;
		$content = '';
		foreach($this->codeArray as $theCode)	{
			$theCode = (string) trim($theCode);
			$contentTmp = '';

			if ($this->conf['fe'])	{
				$templateCode = $templateObj->get($theCode, $langObj, $this->cObj, $templateFile, $errorMessage);
			}
			if ($errorMessage)	{
				break;
			}

			switch($theCode)	{
				case 'SEARCH':
					if ($this->convertToUserInt()) {
						return '';
					}
					// no break!
				case 'LIST':
				case 'LISTGIFTS':
				case 'LISTHIGHLIGHTS':
				case 'LISTNEWITEMS':
				case 'LISTOFFERS':
				case 'LISTARTICLES':
				case 'LISTDAM':
					if ($theCode == 'LISTDAM' && !t3lib_extMgm::isLoaded('dam'))	{
						continue;
					}
					if (count($this->tt_product_single) && !$this->conf['NoSingleViewOnList']) {
						if ($this->convertToUserInt()) {
							return '';
						}
					}

					if (count($basketObj->itemArray) && $showAmount == 'basket') {
						if ($this->convertToUserInt()) {
							return '';
						}
					}
					$contentTmp = $this->products_display($templateCode, $theCode, $errorMessage, $error_code);
				break;
				case 'SELECTCAT':
				case 'SELECTDAMCAT':
				case 'SELECTAD':
				case 'LISTCAT':
				case 'LISTDAMCAT':
				case 'LISTAD':
				case 'MENUCAT':
				case 'MENUDAMCAT':
				case 'MENUAD':
					if (strpos($theCode,'DAM') !== FALSE && !t3lib_extMgm::isLoaded('dam'))	{
						continue;
					}
					$codeTemplateArray = array(
						'SELECTCAT' => 'ITEM_CATEGORY_SELECT_TEMPLATE',
						'SELECTDAMCAT' => 'ITEM_DAMCATSELECT_TEMPLATE',
						'SELECTAD' => 'ITEM_ADDRESS_SELECT_TEMPLATE',
						'LISTCAT' => 'ITEM_CATLIST_TEMPLATE',
						'LISTDAMCAT' => 'ITEM_DAMCATLIST_TEMPLATE',
						'LISTAD' => 'ITEM_ADLIST_TEMPLATE',
						'MENUCAT' => 'ITEM_CATEGORY_MENU_TEMPLATE',
						'MENUDAMCAT' => 'ITEM_DAMCATMENU_TEMPLATE',
						'MENUAD' => 'ITEM_ADDRESS_MENU_TEMPLATE',
					);
// 					t3lib_div::requireOnce(PATH_BE_ttproducts . 'view/class.tx_ttproducts_catlist_view.php');

					if (substr($theCode, -2, 2) == 'AD')	{
						$tablename = '';
						$functablename = 'address';
						if (is_array($this->conf['table.']))	{
							$tablename = $this->conf['table.'][$functablename];
						}
						if (
							$tablename == 'tx_party_addresses' && !t3lib_extMgm::isLoaded(PARTY_EXTkey) ||
							$tablename == 'tx_partner_main' && !t3lib_extMgm::isLoaded(PARTNER_EXTkey) ||
							$tablename == 'tt_address' && !t3lib_extMgm::isLoaded(TT_ADDRESS_EXTkey)
						) {
							$message = tx_div2007_alpha5::getLL_fh002($langObj, 'extension_missing');
							$messageArr =  explode('|', $message);
							$extTableArray = array('tt_address' => TT_ADDRESS_EXTkey, 'tx_partner_main' => PARTNER_EXTkey, 'tx_party_addresses' => PARTY_EXTkey);
							$errorMessage = $messageArr[0] . $extTableArray[$tablename] . $messageArr[1];
						} else if (!$tablename) {
							$message = tx_div2007_alpha5::getLL_fh002($langObj, 'setup_missing');
							$messageArr =  explode('|', $message);
							$errorMessage = $messageArr[0] . 'table.address' . $messageArr[1];
						}
					} else if (substr($theCode,-6,6) == 'DAMCAT')	{
						$functablename = 'tx_dam_cat';
					} else if (substr($theCode,-3,3) == 'CAT')	{
						if ($this->pageAsCategory)	{
							$functablename = 'pages';
						} else {
							$functablename = 'tt_products_cat';
						}
					}
					if (!$errorMessage)	{
						$templateArea = $codeTemplateArray[$theCode];
						if (substr($theCode,0, 6) == 'SELECT') {
							$categoryClass = 'tx_ttproducts_selectcat_view';
						} else if (substr($theCode,0, 4) == 'LIST') {
							$categoryClass = 'tx_ttproducts_catlist_view';
						} else if (substr($theCode,0, 4) == 'MENU') {
							$categoryClass = 'tx_ttproducts_menucat_view';
						}
// 						t3lib_div::requireOnce(PATH_BE_ttproducts . 'view/class.' . $categoryClass . '.php');

							// category view
						$categoryView = t3lib_div::makeInstance($categoryClass);
						$categoryView->init($pibaseClass, $this->config['pid_list'], $this->pid);
						$contentTmp = $categoryView->printView(
							$functablename,
							$templateCode,
							$theCode,
							$error_code,
							$templateArea,
							$this->pageAsCategory,
							$this->config['templateSuffix']
						);
					}
				break;
				case 'SINGLE':
					$contentTmp = $this->products_display($templateCode, $theCode, $errorMessage, $error_code);
				break;
				break;
				case 'BASKET':
				case 'FINALIZE':
				case 'INFO':
				case 'OVERVIEW':
				case 'PAYMENT':
					if ($this->convertToUserInt()) {
						return '';
					}
					$contentTmp = $contentBasket;
					$contentBasket = '';
						// nothing here any more. This work is done in the control processing before
						// This line is necessary because some activities might have overriden these CODEs
				break;
				case 'BILL':
				case 'DELIVERY':
					if ($this->convertToUserInt()) {
						return '';
					}
					$contentTmp = $this->products_tracking($templateCode,$theCode);
				break;
				case 'TRACKING':
					if ($this->convertToUserInt()) {
						return '';
					}
					$contentTmp = $this->products_tracking($templateCode,$theCode);
				break;
				case 'MEMODAM':
				case 'MEMODAMOVERVIEW':
				case 'MEMO':
					if ($this->convertToUserInt()) {
						return '';
					}

					// memo view: has to be called always because it reads parameters from the list
					$this->memoView = t3lib_div::makeInstance('tx_ttproducts_memo_view');
					$this->memoView->init(
						$pibaseClass,
						$theCode,
						$this->config['pid_list'],
						$this->conf['useArticles']
					);
					$contentTmp = $this->memoView->printView($theCode, $templateCode, $this->pid, $this->conf, $error_code);
				break;
				case 'CURRENCY':
					if ($this->convertToUserInt()) {
						return '';
					}
						// currency view
					$currencyView = t3lib_div::makeInstance('tx_ttproducts_currency_view');
					$currencyView->init($pibaseObj);

					$contentTmp = $currencyView->printView();
				break;
				case 'ORDERS':
					if ($this->convertToUserInt()) {
						return '';
					}
						// order view
					$orderView = $tablesObj->get('sys_products_orders', TRUE);
					$contentTmp = $orderView->printView($templateCode, $error_code);
				break;
				case 'SINGLECAT':
				case 'SINGLEDAMCAT':
				case 'SINGLEAD':
					$catView = t3lib_div::makeInstance('tx_ttproducts_cat_view');
					$catView->init(
						$pibaseObj,
						$this->pid,
						$this->config['pid_list'],
						$this->config['recursive']
					);
					$tableInfoArray = array('SINGLECAT' => 'tt_products_cat', 'SINGLEDAMCAT' => 'tx_dam_cat', 'SINGLEAD' => 'address');
					$functablename = $tableInfoArray[$theCode];
					$uid = $pibaseObj->piVars[tx_ttproducts_model_control::getPivar($functablename)];

					if ($uid)	{
						$contentTmp = $catView->printView(
							$templateCode,
							$functablename,
							$uid,
							$theCode,
							$error_code,
							$this->config['templateSuffix']
						);
					}
				break;
				case 'SCRIPT':
					$contentTmp = '';
				break;
				default:	// 'HELP'
					if ($this->convertToUserInt()) {
						return '';
					}
					$contentTmp = 'error';
				break;
			}

			if ($error_code[0]) {

				$messageArr = array();
				$i = 0;

				$errorConf = array();
				if (isset($this->conf['error.'])) {
					$errorConf = $this->conf['error.'];
					$urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view');
				}

				foreach ($error_code as $key => $indice) {

					if (
						isset($errorConf[$indice . '.']) &&
						isset($errorConf[$indice . '.']['redirect.']) &&
						isset($errorConf[$indice . '.']['redirect.']['pid'])
					) {
						$pid = $errorConf[$indice . '.']['redirect.']['pid'];
						$url = tx_div2007_alpha5::getTypoLink_URL_fh003(
							$this->cObj,
							$pid,
							$urlObj->getLinkParams(
								'product,article,dam',
								'',
								TRUE,
								FALSE
							),
							'',
							array()
						);

						if ($url != '') {
							t3lib_utility_http::redirect($url);
						}
					}

					if ($key == 0) {
						$messageArr = explode('|', $message = tx_div2007_alpha5::getLL_fh002($langObj, $indice));
						$contentTmp .= '<b>'.tx_div2007_alpha5::getLL_fh002($langObj, 'tt_products') . ': ' . $messageArr[0] . '</b>';
					} else {
						$contentTmp .= '<b>' . $indice . $messageArr[$i] . '</b>';
					}
					$i++;
				}
				$error_code = array();
			}

			if ($contentTmp == 'error') {
				$fileName = 'EXT:'.TT_PRODUCTS_EXT.'/template/products_help.tmpl';
				$helpTemplate = $this->cObj->fileResource($fileName);
				$content .=
					tx_div2007_alpha5::displayHelpPage_fh003(
						$langObj,
						$this->cObj,
						$helpTemplate,
						TT_PRODUCTS_EXT,
						$errorMessage,
						$theCode
					);
				$bErrorFound = TRUE;
				unset($errorMessage);
			}
			if (intval($this->conf['wrapInCode']))	{
				$content .= tx_div2007_alpha5::wrapContentCode_fh004($contentTmp, $theCode, $pibaseObj->prefixId, $this->cObj->data['uid']);
			} else if (!$bErrorFound) {
				$content .= $contentTmp;
			}
		}

		if ($errorMessage) {
			$content = '<p><b>'.$errorMessage.'</b></p>';
		}
		if ($bRunAjax || !intval($this->conf['wrapInBaseClass']))	{
			$rc = $content;
		} else {
			$content = $pibaseObj->pi_wrapInBaseClass($content);
			$cssObj = t3lib_div::getUserObj('&tx_ttproducts_css');
			if ($cssObj->isCSSStyled())	{
				$rc = '<style type="text/css">'.$this->cObj->fileResource($cssObj->conf['file']) . '</style>' . chr(13) . $content;
			} else {
				$rc = $content;
			}
		}

		if (!$this->conf['fe'])	{
			$rc = '';
		}

		if (!$this->conf['defaultSetup'])	{
			$rc .= '<h>Error: The default tt_products setup is missing.</h>';
		}

		return $rc;
	}


	/**
	 * Converts the plugin to USER_INT if it is not USER_INT already. After
	 * calling this function the plugin should return if the function returns
	 * TRUE. The content will be ignored and the plugin will be called again
	 * later as USER_INT.
	 *
	 * @return boolean TRUE if the plugin should return immediately
	 */
	protected function convertToUserInt() {
		$result = FALSE;
		if (method_exists($this->cObj, 'getUserObjectType') && $this->cObj->getUserObjectType() == tslib_cObj::OBJECTTYPE_USER) {
			$this->cObj->convertToUserIntObject();
			$this->cObj->data['pi_flexform'] = $this->cObj->data['_original_pi_flexform'];
			unset($this->cObj->data['_original_pi_flexform']);
			$result = TRUE;
		}
		return $result;
	}


	public function set_no_cache () {
		// Should never be used!
	}


	/**
	 * Returns 1 if user is a shop admin
	 */
	public function shopAdmin (&$updateCode)	{
		$admin=0;
		if ($GLOBALS['TSFE']->beUserLogin || $this->conf['shopAdmin'] != 'BE')	{
			$updateCode = t3lib_div::_GP('update_code');
			if ($updateCode == $this->conf['update_code'])	{
				$admin = 1;	// Means that the administrator of the website is authenticated.
			}
		}
		return $admin;
	}


	/**
	 * Order tracking
	 *
	 *
	 * @param	integer		Code: TRACKING, BILL or DELIVERY
	 * @return	void
	 * @see enableFields()
	 */
	public function products_tracking (&$templateCode, $theCode)	{ // t3lib_div::_GP('tracking')
		global $TSFE;

		$pibaseObj = t3lib_div::getUserObj('&tx_ttproducts_pi1_base');
		$urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view');
		$updateCode = '';
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$subpartmarkerObj = t3lib_div::getUserObj('&tx_ttproducts_subpartmarker');
		$trackingCode = t3lib_div::_GP('tracking');
		$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');
		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$globalMarkerArray = $markerObj->getGlobalMarkerArray();

		$trackingTemplateCode = &$templateCode;
		$admin = $this->shopAdmin($updateCode);
		$msgSubpart = '';
		if ($trackingCode || $admin)	{	// Tracking number must be set
			$orderObj = $tablesObj->get('sys_products_orders');
			$orderRow = $orderObj->getRecord('', $trackingCode);

			if (isset($orderRow) && is_array($orderRow) || $admin)	{	// If order is associated with tracking id.

				$type = strtolower($theCode);
				switch ($theCode) {
					case 'TRACKING':
				 		$tracking = t3lib_div::makeInstance('tx_ttproducts_tracking');
				 		$tracking->init(
				 			$this->cObj
				 		);
						$orderRecord = t3lib_div::_GP('orderRecord');
						$content = $tracking->getTrackingInformation($orderRow, $trackingTemplateCode, $trackingCode, $updateCode, $orderRecord, $admin);
						break;
					case 'BILL':
					case 'DELIVERY':
						$billdeliveryObj = t3lib_div::getUserObj('&tx_ttproducts_billdelivery');
						$content = $billdeliveryObj->getInformation($theCode, $orderRow, $trackingTemplateCode, $trackingCode, $type);
						$billdeliveryObj->writeFile($type, $trackingCode, $content);
						$relfilename = $billdeliveryObj->getRelFilename($trackingCode, $type);
						$message = tx_div2007_alpha5::getLL_fh002($langObj, 'open_' . $type);
						$content = '<a href="' . $relfilename . '" >' . $message . '</a>';
						break;
					default:
						debug('error in ' . TT_PRODUCTS_EXT . ' calling function products_tracking with $theCode = "' . $theCode . '"');
				}
			} else {	// ... else output error page
				$msgSubpart = '###TRACKING_WRONG_NUMBER###';
			}
		} else {	// No tracking number - show form with tracking number
			$msgSubpart = '###TRACKING_ENTER_NUMBER###';
		}

		if ($msgSubpart)	{
			$content=$this->cObj->getSubpart($trackingTemplateCode, $subpartmarkerObj->spMarker($msgSubpart));
			if (!$TSFE->beUserLogin)	{
				$content = $this->cObj->substituteSubpart($content, '###ADMIN_CONTROL###','');
			}
		}

		$markerArray = $globalMarkerArray;
		$markerArray['###FORM_URL###'] = $pibaseObj->pi_getPageLink($TSFE->id, '', $urlObj->getLinkParams('', array(), TRUE)) ;
		$content = $this->cObj->substituteMarkerArray($content, $markerArray);
		return $content;
	}  // products_tracking


		// XAJAX functions cannot be in classes
	public function showArticle ($data)	{
		$rc = '';
		$pibaseObj = t3lib_div::getUserObj('&tx_ttproducts_pi1_base');
        // We put our incomming data to the regular piVars
		$pibaseObj->piVars = array_merge($this->piVars, $data[$pibaseObj->prefixId]);
		$this->excludeCode = '';
		$this->ajax->conf = $data['conf'];
		$objResponse = new tx_taxajax_response('iso-8859-1');
		$artListKey = array_search('SELECTCAT', $this->codeArray);
		if ($this->codeArray[$artListKey] == 'SELECTCAT')	{
			unset($this->codeArray[$artListKey]);
		}
		if (count($this->codeArray))	{
			foreach ($this->codeArray as $k => $code)	{
				if ($code != 'LISTARTICLES')	{
					$tagId = 'tx-ttproducts-pi1-' . strtolower($code);
					unset($this->codeArray[$k]);
					// $objResponse->addAssign($idClass, 'innerHTML', '');
				}
			}
		}

		$tagId = ($tagId ? $tagId : 'ArticleId');
		$content = $this->run($this->pibaseClass, $error_code, '', TRUE);
	    // $content = '<br /><strong>Content from AJAX server</strong><br />';
		$objResponse->addAssign($tagId, 'innerHTML', $content);
		$rc = $objResponse->getXML();
	    //return the XML response generated by the tx_taxajax_response object
		return $rc;
	}


	 public function &tt_products_commands($cmd, $param1 = '', $param2 = '', $param3 = ''){
		$objResponse = new tx_taxajax_response('iso-8859-1');

		return $objResponse->getXML();
	}


	public function setSingleFromList ($bValue)	{
		$this->bSingleFromList = $bValue;
	}


	public function getSingleFromList ()	{
		return $this->bSingleFromList;
	}


	/**
	 * Displaying single products/ the products list / searching
	 */
	public function products_display ($templateCode, $theCode, &$errorMessage, &$error_code)	{
		global $TSFE;

		$pibaseObj = t3lib_div::getUserObj('&tx_ttproducts_pi1_base');
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$basketObj = t3lib_div::getUserObj('&tx_ttproducts_basket');

		$bSingleFromList = FALSE;

		if (
			(
				(
					($theCode == 'SEARCH') && $this->conf['listViewOnSearch'] == '1' || (strpos($theCode, 'LIST') !== FALSE)
				) &&
				$theCode != 'LISTARTICLES' && count($this->tt_product_single) && !$this->conf['NoSingleViewOnList'] &&
			!$this->getSingleFromList()
			)
		) {
			$this->setSingleFromList(TRUE);
			$bSingleFromList = TRUE;
		}

		if (($theCode=='SINGLE') || $bSingleFromList) {

			$extVars = $this->piVars['variants'];
			$extVars = ($extVars ? $extVars : t3lib_div::_GP('ttp_extvars'));
			$showAmount = $cnf->getBasketConf('view','showAmount');

			if (!count($this->tt_product_single))	{
				if ($this->conf['defaultProductID'])	{
					$this->tt_product_single['product'] = $this->conf['defaultProductID'];
				} else if ($this->conf['defaultArticleID'])	{
					$this->tt_product_single['article'] = $this->conf['defaultArticleID'];
				}
			}

			if ($showAmount == 'basket' && $this->tt_product_single['product'] && $basketObj->isInBasket($this->tt_product_single['product']) ||
				!$this->conf['NoSingleViewOnList'] && !$this->conf['PIDitemDisplay'] && !$this->conf['PIDitemDisplay.']
			) {
				if ($this->convertToUserInt()) {
					return '';
				}
			}

			if (!is_object($this->singleView)) {
				// List single product:
				$this->singleView = t3lib_div::makeInstance('tx_ttproducts_single_view');
			}
			$this->singleView->init(
				$this->pibaseClass,
				$this->tt_product_single,
				$extVars,
				$this->pid,
				$this->conf['useArticles'],
				$this->config['pid_list'],
				$this->config['recursive']
			);
			$content = $this->singleView->printView(
				$templateCode,
				$error_code,
				$this->pageAsCategory,
				$this->config['templateSuffix']
			);
		} else {
// 			t3lib_div::requireOnce(PATH_BE_ttproducts . 'view/class.tx_ttproducts_list_view.php');

	// page where to go usually
			$pid = ($this->conf['PIDbasket'] && $this->conf['clickIntoBasket'] ? $this->conf['PIDbasket'] : $TSFE->id);

			// List all products:
			$listView = t3lib_div::makeInstance('tx_ttproducts_list_view');
			$listView->init (
				$this->pibaseClass,
				$pid,
				$this->conf['useArticles'],
				$this->tt_product_single,
				$this->config['pid_list'],
				$this->config['recursive']
			);

			if ($theCode == 'LISTARTICLES' && $this->conf['useArticles'])	{
				$templateArea = 'ARTICLE_LIST_TEMPLATE';
			} else {
				$templateArea = 'ITEM_LIST_TEMPLATE';
			}

			if ($theCode == 'LISTARTICLES' && $this->conf['useArticles'])	{
				$functablename = 'tt_products_articles';
			} else if ($theCode == 'LISTDAM')	{
				$functablename = 'tx_dam';
			} else {
				$functablename = 'tt_products';
			}
			$allowedItems = '';
			$allowedItems = tx_div2007_ff::get($this->cObj->data['pi_flexform'], 'productSelection');

			$templateArea = $templateArea.$this->config['templateSuffix'];
			$content = $listView->printView(
				$templateCode,
				$theCode,
				$functablename,
				$allowedItems,
				$bAllPages,
				$error_code,
				$templateArea,
				$this->pageAsCategory,
				array()
			);
		}
		return $content;
	}	// products_display
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_main.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_main.php']);
}


?>