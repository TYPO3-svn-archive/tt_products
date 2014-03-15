<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Franz Holzinger <franz@ttproducts.de>
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


require_once(PATH_BE_div2007.'class.tx_div2007_ff.php');
require_once(PATH_BE_div2007.'class.tx_div2007_alpha.php');
require_once(PATH_BE_div2007.'class.tx_div2007_alpha5.php');


require_once (PATH_BE_table.'lib/class.tx_table_db.php');
require_once (PATH_BE_ttproducts . 'cache/class.tx_ttproducts_cache.php');

require_once (PATH_BE_ttproducts.'view/field/class.tx_ttproducts_field_base_view.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_tables.php');
require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_pid_list.php');
require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_basket.php');
require_once (PATH_BE_ttproducts.'control/class.tx_ttproducts_control.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_config.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_creditpoints_div.php');
require_once (PATH_BE_ttproducts.'control/class.tx_ttproducts_javascript.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_gifts_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_paymentshipping.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_pricetablescalc.php');

require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_marker.php');
require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_subpartmarker.php');
require_once (PATH_BE_ttproducts.'model/field/class.tx_ttproducts_field_price.php');
require_once (PATH_BE_ttproducts.'model/field/class.tx_ttproducts_field_image.php');
require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_graduated_price.php');
require_once (PATH_BE_ttproducts.'view/field/class.tx_ttproducts_field_image_view.php');

require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_single_view.php');
require_once (PATH_BE_ttproducts.'view/field/class.tx_ttproducts_field_price_view.php');
require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_url_view.php');
require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_graduated_price_view.php');



class tx_ttproducts_main {
		// Internal
	var $uid_list='';			// List of existing uid's from the basket, set by initBasket()
	var $orderRecord = array();		// Will hold the order record if fetched.

		// Internal: init():
	var $templateCode='';			// In init(), set to the content of the templateFile.
	var $config=array();			// updated configuration
	var $conf;
	var $css; 				// object for CSS configuration
	var $tt_product_single=array();
	var $externalCObject='';
	var $basket;				// basket object
	var $control;				// object for the control of the application
	var $singleView;			// single view object
	var $memoView;				// memo view and data object

	var $pid;				// the page to which the script shall go
	var $piVars;

	var $ajax;				// ajax object
	var $javaScriptObj;			// JavaScript object
	var $codeArray;				// Codes
	var $tableArray;			// contains the table name as key and the part of the file name for the corresponding data and view
	var $templateSuffix; 			// suffix for template subpart area markers
	var $bNoCachePossible = TRUE;		// if the cache may be turned off
	var $pageAsCategory;			// > 0 if pages are used as categories
	var $templateFile; 			// template file currently used, may depend on the code
	var $pibase;
	/**
	 * Content object
	 * @var tslib_cObj
	 */
	var $cObj;
	var $bSingleFromList = FALSE;		// if the single view shall be shown instead of a list view
	var $errorMessage;
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
	 * @param	[type]		$config: ...
	 * @return	bool		if true processing should be done
	 */
	function init ($pibase, &$content, &$conf, &$config, &$error_code, $bRunAjax = FALSE) {
		global $TSFE;

		if ($conf['no_cache'] && $this->convertToUserInt()) {
			// Compatibility with previous versions where users could set
			// 'no_cache' TS option. This option does not exist anymore and we
			// simply convert the plugin to USER_INT if that old option is set.
			return FALSE;
		}

		$cacheObj = t3lib_div::getUserObj('tx_ttproducts_cache');
		$this->pibase = $pibase;
		$this->cObj = $pibase->cObj;
		$this->conf = &$conf;
		$this->config = &$config;
		$this->piVars = &$pibase->piVars;
		$config['LLkey'] = $pibase->LLkey;

			// basket
		$this->basket = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$eInfo = tx_div2007_alpha::getExtensionInfo_fh001(TT_PRODUCTS_EXT);

		$this->conf['version'] = $eInfo['version'];
		// Save the original flexform in case if we need it later as USER_INT
		$pibase->cObj->data['_original_pi_flexform'] = $pibase->cObj->data['pi_flexform'];
		$this->pibase->pi_initPIflexForm();

		$config['code'] =
			tx_div2007_alpha::getSetupOrFFvalue_fh003(
				$this->cObj,
	 			$conf['code'],
	 			$conf['code.'],
				$conf['defaultCode'],
				$this->cObj->data['pi_flexform'],
				'display_mode',
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['useFlexforms']
			);
		$this->codeArray = t3lib_div::trimExplode(',', $config['code'],1);
		$required_pivars = $this->pibase->pi_getFFvalue($this->cObj->data['pi_flexform'], 'required_pivars');
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
				$this->ajax->taxajax->registerFunction(array(TT_PRODUCTS_EXT.'_showArticle',$this,'showArticle'));
			}
		}

		if (!$bRunAjax) {
			// ### central initialization ###
			$db = t3lib_div::getUserObj('&tx_ttproducts_db');
			$result = $db->init($conf, $config, $this->ajax, $pibase, $error_code); // this initializes tx_ttproducts_config inside of creator
		}

		if (!$result) {
			return FALSE;
		}

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
		$this->templateSuffix = $this->pibase->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_suffix');
		$this->templateSuffix = strtoupper($this->templateSuffix);
		$this->templateSuffix = ($this->templateSuffix ? '_'.$this->templateSuffix : '');
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

		$tmp = $this->cObj->stdWrap($this->conf['pid_list'],$this->conf['pid_list.']);
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
			if (isset($tmp) && is_array($tmp) && isset($tmp['product']))	{	// upwards compatible to tt_products parameter
				$tmp = $tmp['product'];
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

		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$cnf->init(
			$conf,
			$config
		);

		$urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view');
		$urlObj->init($this->pibase);

			// image
		$imageObj = t3lib_div::getUserObj('&tx_ttproducts_field_image');
		$imageObj->init($this->pibase->cObj, $this->pibase);

			// image view
		$imageViewObj = t3lib_div::getUserObj('&tx_ttproducts_field_image_view');
		$imageViewObj->init($this->pibase, $imageObj);

			// get all extending TCAs
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['extendingTCA']))	{
				tx_div2007_alpha::loadTcaAdditions_fh001($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['extendingTCA']);
		}

		if ($this->conf['templateStyle'] == 'css-styled')	{
			include_once(PATH_BE_ttproducts . 'lib/class.tx_ttproducts_css.php');
			$this->css = t3lib_div::makeInstance('tx_ttproducts_css');
			$this->css->init(
				$this->pibase
			);
		}

			// price
		$priceObj = t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$priceObj->init(
			$this->pibase->cObj,
			$this->conf
		);

			// price
		$priceViewObj = t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$priceViewObj->init(
			$this->pibase,
			$this->pibase->cObj,
			$priceObj
		);

			// graduated price
		$graduatedPriceObj = t3lib_div::getUserObj('&tx_ttproducts_graduated_price');
		$graduatedPriceObj->init($this->conf['table.']['tt_products_graduated_price'], $this->conf['table.']['tt_products_mm_graduated_price']);

		$graduatedPriceViewObj = t3lib_div::getUserObj('&tx_ttproducts_graduated_price_view');
		$graduatedPriceViewObj->init($this->pibase, $graduatedPriceObj);

		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$tablesObj->init($this->pibase);

			// paymentshipping
		$paymentshippingObj = t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
		$paymentshippingObj->init(
			$this->pibase->cObj
		);

		$classRef = '&tx_ttproducts_paymentshipping';

		$this->javaScriptObj = t3lib_div::getUserObj('&tx_ttproducts_javascript');

			// JavaScript
		$this->javaScriptObj->init(
			$this->pibase,
			$this->ajax
		);

		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$markerObj->init(
			$this->cObj
		);

			// basket view
		$this->control = t3lib_div::makeInstance('tx_ttproducts_control');

			// This cObject may be used to call a function which manipulates the shopping basket based on settings in an external order system. The output is included in the top of the order (HTML) on the basket-page.
		$this->externalCObject = tx_div2007_alpha::getExternalCObject_fh001($this->pibase, 'externalProcessing');

		$subpartmarkerObj = t3lib_div::getUserObj('&tx_ttproducts_subpartmarker');
		$subpartmarkerObj->init($this->cObj);

		return TRUE;
	} // init

	/**
	 * do the things
	 *
	 * @param	string		$content
	 * @return	string		content for output
	 */
	function &run ($content = '', $bRunAjax = FALSE) {
		global $TSFE;

		$bStoreBasket = TRUE;

		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$orderObj = $tablesObj->get('sys_products_orders');
		$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');

		if (!count($this->codeArray) && !$bRunAjax)	{
			$this->codeArray = array('HELP');
		}
		// $this->codeArray = $this->checkCodes($this->codeArray, $bStoreBasket);

		if ((t3lib_extMgm::isLoaded('taxajax')))	{
			if ($bRunAjax)	{
				// TODO: get AJAX configuration
			} else {
				$this->javaScriptObj->set('xajax');
			}
		}

		$error_code = array();

		if (t3lib_div::_GP('mode_update'))	{
			$updateMode = 1;
		} else {
			$updateMode = 0;
		}

		if (isset($this->conf['basket.']) && $this->conf['basket.']['store'] == '0' || count($this->codeArray) == 1 && $this->codeArray[0] == 'OVERVIEW' && !$this->conf['basket.']['store'])	{
			$bStoreBasket = FALSE;
		}

		if (!$this->errorMessage) {
			$recs = $TSFE->fe_user->getKey('ses','recs');
			$voucher = $tablesObj->get('voucher');
			$voucher->doProcessing($recs);
			$this->basket->init(
				$this->pibase,
				$recs,
				$updateMode,
				$this->config['pid_list'],
				$this->conf['useArticles'],
				$bStoreBasket
			);
		}

		// *************************************
		// *** Listing items:
		// *************************************
		if (!$this->errorMessage) {
			$this->basket->getCalculatedBasket(); // get the basket->itemArray
			$this->basket->getCalculatedSums();
			$this->templateCode=$this->getTemplateCode('BASKET');

			$functablename = 'tt_products';
			tx_ttproducts_control_memo::process($functablename, $this->pibase->piVars, $this->conf);

			$this->control->init (
				$this->pibase,
				$this->basket->getFuncTablename(),
				$this->templateCode,
				$this->conf['useArticles'],
				$error_code
			);
			$content .= $this->control->doProcessing($this->codeArray, $this->errorMessage);
		}

		foreach($this->codeArray as $theCode)	{

			if ($this->errorMessage)	{
				break;
			}
			$theCode = (string) trim($theCode);
			$contentTmp = '';

			$this->templateCode=$this->getTemplateCode($theCode);
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
					if (count($this->tt_product_single) || !$this->conf['NoSingleViewOnList']) {
						if ($this->convertToUserInt()) {
							return '';
						}
					}

					if (count($this->basket->itemArray)) {
						if ($this->convertToUserInt()) {
							return '';
						}
					}
					$contentTmp = $this->products_display($theCode, $this->errorMessage, $error_code);
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
					if (substr($theCode,-2,2) == 'AD')	{
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
							$this->errorMessage=$messageArr[0]. $extTableArray[$tablename] .$messageArr[1];
						} else if (!$tablename) {
							$message = tx_div2007_alpha5::getLL_fh002($langObj, 'setup_missing');
							$messageArr =  explode('|', $message);
							$this->errorMessage=$messageArr[0]. 'table.address' .$messageArr[1];
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

					if (!$this->errorMessage)	{
						$templateArea = $codeTemplateArray[$theCode];
						if (substr($theCode,0, 6) == 'SELECT') {
							$categoryClass = 'tx_ttproducts_selectcat_view';
						} else if (substr($theCode,0, 4) == 'LIST') {
							$categoryClass = 'tx_ttproducts_catlist_view';
						} else if (substr($theCode,0, 4) == 'MENU') {
							$categoryClass = 'tx_ttproducts_menucat_view';
						}

							// category view
						$categoryView = t3lib_div::makeInstance($categoryClass);
						$categoryView->init($this->pibase, $this->config['pid_list'], $this->config['recursive'], $this->pid);

						$contentTmp = $categoryView->printView(
							$functablename,
							$this->templateCode,
							$theCode,
							$error_code,
							$templateArea,
							$this->pageAsCategory,
							$this->templateSuffix
						);
					}
				break;
				case 'SINGLE':
					$contentTmp = $this->products_display($theCode, $this->errorMessage, $error_code);
				break;
				case 'OVERVIEW':
				case 'PAYMENT':
				case 'BASKET':
				case 'FINALIZE':
				case 'INFO':
					if ($this->convertToUserInt()) {
						return '';
					}
					$contentTmp = $content;
					$content = '';
						// nothing here any more. This work is done in the control processing before
						// This line is necessary because some activities might have overriden these CODEs
				break;
				case 'BILL':
				case 'DELIVERY':
					if ($this->convertToUserInt()) {
						return '';
					}
					$contentTmp=$this->products_tracking($theCode);
				break;
				case 'TRACKING':
					if ($this->convertToUserInt()) {
						return '';
					}
					$contentTmp=$this->products_tracking($theCode);
				break;
				case 'MEMO':
					if ($this->convertToUserInt()) {
						return '';
					}

						// memo view: has to be called always because it reads parameters from the list
					$this->memoView = t3lib_div::makeInstance('tx_ttproducts_memo_view');
					$this->memoView->init(
						$this->pibase,
						$this->config['pid_list'],
						$this->pid,
						$this->conf,
						$this->conf['useArticles']
					);
					$contentTmp=$this->memoView->printView($this->templateCode, $error_code);
				break;
				case 'CURRENCY':
					if ($this->convertToUserInt()) {
						return '';
					}
						// currency view
					$currencyView = t3lib_div::makeInstance('tx_ttproducts_currency_view');
					$currencyView->init($this->pibase);

					$contentTmp=$currencyView->printView();
				break;
				case 'ORDERS':
					if ($this->convertToUserInt()) {
						return '';
					}

						// order view
					$orderView = t3lib_div::makeInstance('tx_ttproducts_order_view');
					$orderView->init($this->pibase, $TSFE->fe_user->getKey('ses','recs'));
					$contentTmp=$orderView->printView($this->templateCode, $error_code);
				break;
				case 'SINGLECAT':
				case 'SINGLEDAMCAT':
				case 'SINGLEAD':
					$catView = t3lib_div::makeInstance('tx_ttproducts_cat_view');
					$catView->init(
						$this->pibase,
						$this->pid,
						$this->config['pid_list'],
						$this->config['recursive']
					);
					$tableInfoArray = array('SINGLECAT' => 'tt_products_cat', 'SINGLEDAMCAT' => 'tx_dam_cat', 'SINGLEAD' => 'address');
					$functablename = $tableInfoArray[$theCode];
					$tableObj = $tablesObj->get($functablename, TRUE);
					$uid = $this->pibase->piVars[$tableObj->getPivar()];

					if ($uid)	{
						$contentTmp = $catView->printView(
							$this->templateCode,
							$functablename,
							$uid,
							$theCode,
							$error_code,
							$this->templateSuffix
						);
					}
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
						$url = tx_div2007_alpha::getTypoLink_URL_fh002(
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
						$contentTmp .= '<b>'.tx_div2007_alpha5::getLL_fh002($langObj, 'tt_products').': '.$messageArr[0].'</b>';
					} else {
						$contentTmp .= '<b>'.$indice.$messageArr[$i].'</b>';
					}
					$i++;
				}
				$error_code = array();
			}

			if ($contentTmp == 'error') {
					$fileName = 'EXT:'.TT_PRODUCTS_EXT.'/template/products_help.tmpl';
					$helpTemplate = $this->cObj->fileResource($fileName);
					$content .= tx_div2007_alpha::displayHelpPage_fh001($this->pibase, $helpTemplate, TT_PRODUCTS_EXT, $this->errorMessage, $theCode);

					unset($this->errorMessage);
					break; // while
			} else {
				$content .= tx_div2007_alpha::wrapContentCode_fh003($contentTmp, $theCode, $this->pibase->prefixId, $this->cObj->data['uid']);
			}
		}

		if ($this->errorMessage) {
			$content = '<p><b>'.$this->errorMessage.'</b></p>';
		}

		if ($bRunAjax || !intval($this->conf['wrapInBaseClass']))	{
			$rc = $content;
		} else {
			$content = $this->pibase->pi_wrapInBaseClass($content);
			if (is_object($this->css) && ($this->css->conf['file']))	{
				$rc = '<style type="text/css">' . $this->cObj->fileResource($this->css->conf['file']) . '</style>' . chr(13) . $content;
			} else {
				$rc = $content;
			}
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
		if ($this->cObj->getUserObjectType() == tslib_cObj::OBJECTTYPE_USER) {
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
	 * get the HTML template from the plugin's code
	 *
	 * @param	string		$theCode: plugin's CODE field
	 * @return	string		HTML template
	 */
	function &getTemplateCode ($theCode) {
		$templateCode = '';
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$templateFile = $cnf->getTemplateFile($theCode);
		$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');

		if ($templateFile) {
			// template file is fetched. The whole template file from which the various subpart are extracted.
			$templateCode = $this->cObj->fileResource($templateFile);
		}

		if (!$templateFile || empty($templateCode)) {
			if ($this->conf['templateFile.'][$theCode])	{
				$tmplText = $theCode.'.';
			}
			$tmplText .= 'templateFile';
			$this->errorMessage .= tx_div2007_alpha5::getLL_fh002($langObj, 'no_template') . ' plugin.' . TT_PRODUCTS_EXT . '.' . $tmplText . ' = ';
			$this->errorMessage .= ($this->conf['templateFile'] ? "'".$this->conf['templateFile']."'" : '""');
		} else {
			$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');

				// Substitute Global Marker Array
			$templateCode = $this->cObj->substituteMarkerArrayCached($templateCode, $markerObj->getGlobalMarkerArray());
		}
		$this->templateFile = $templateFile;
		return $templateCode;
	}

	/**
	 * Returns 1 if user is a shop admin and the correct FE password has been entered
	 *
	 * @param	string		out: $$updateCode: FE password for the admin
	 * @return	boolean
	 */
	function shopAdmin (&$updateCode)	{
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
	 * @param	string		Code: TRACKING, BILL or DELIVERY
	 * @return	void
	 * @see enableFields()
	 */
	function products_tracking ($theCode)	{ // t3lib_div::_GP('tracking')
		global $TSFE;

		$urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view');
		$updateCode = '';
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
// 		include_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_subpartmarker.php');
		$subpartmarkerObj = t3lib_div::getUserObj('&tx_ttproducts_subpartmarker');

		$trackingCode = t3lib_div::_GP('tracking');
		$admin = $this->shopAdmin($updateCode);
		$msgSubpart = '';
		if ($trackingCode || $admin)	{	// Tracking number must be set
			$orderObj = $tablesObj->get('sys_products_orders');
			$orderRow = $orderObj->getRecord('',$trackingCode);
			if (is_array($orderRow) || $admin)	{	// If order is associated with tracking id.
				if (!is_array($orderRow)) {
					$orderRow=array('uid'=>0);
				}
				switch ($theCode) {
					case 'TRACKING':
				 		$tracking = t3lib_div::makeInstance('tx_ttproducts_tracking');
				 		$tracking->init(
				 			$this->cObj
				 		);
						$orderRecord = t3lib_div::_GP('orderRecord');
						$content = $tracking->getTrackingInformation($orderRow, $this->templateCode, $trackingCode, $updateCode, $orderRecord, $admin);
						break;
					case 'BILL':
				 		$bill = t3lib_div::makeInstance('tx_ttproducts_billdelivery');
				 		$bill->init(
				 			$this->pibase,
							'bill'
						);

						$content = $bill->getInformation($theCode, $orderRow, $this->templateCode,$trackingCode);
						break;
					case 'DELIVERY':
				 		$delivery = t3lib_div::makeInstance('tx_ttproducts_billdelivery');
				 		$delivery->init(
				 			$this->pibase,
							'delivery'
						);
						$content = $delivery->getInformation(
							$theCode,
							$orderRow,
							$this->templateCode,
							$trackingCode
						);
						break;
					default:
						debug('error in '.TT_PRODUCTS_EXT.' calling function products_tracking with $theCode = "'.$theCode.'"');
				}
			} else {	// ... else output error page
				$msgSubpart = '###TRACKING_WRONG_NUMBER###';
			}
		} else {	// No tracking number - show form with tracking number
			$msgSubpart = '###TRACKING_ENTER_NUMBER###';
		}

		if ($msgSubpart)	{
			$content=$this->cObj->getSubpart($this->templateCode,$subpartmarkerObj->spMarker($msgSubpart));
			if (!$TSFE->beUserLogin)	{
				$content = $this->cObj->substituteSubpart($content,'###ADMIN_CONTROL###','');
			}
		}

		$markerArray = array();
		$markerArray['###FORM_URL###'] = $this->pibase->pi_getPageLink($TSFE->id, '', $urlObj->getLinkParams('', array(), true)) ; // $this->getLinkUrl();	// Add FORM_URL to globalMarkerArray, linking to self.
		$content = $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	}  // products_tracking

	/**
	 * Ajax method to show articles
	 *
	 * @param	array		$data
	 * @return	void
	 * @see enableFields()
	 */
	function showArticle ($data)	{
		$rc = '';
        // We put our incomming data to the regular piVars
		$this->pibase->piVars = array_merge($this->piVars, $data[$this->pibase->prefixId]);
		$this->excludeCode = '';
		$this->ajax->conf = $data['conf'];

		// Instantiate the tx_xajax_response object
		$objResponse = new tx_taxajax_response('iso-8859-1');

		$artListKey = array_search('SELECTCAT', $this->codeArray);
		if ($this->codeArray[$artListKey] == 'SELECTCAT')	{
			unset($this->codeArray[$artListKey]);
		}
		if (count($this->codeArray))	{
			foreach ($this->codeArray as $k => $code)	{
				if ($code != 'LISTARTICLES')	{
					$tagId = 'tx-ttproducts-pi1-'.strtolower($code);
					unset($this->codeArray[$k]);
					// $objResponse->addAssign($idClass, 'innerHTML', '');
				}
			}
		}
		$tagId = ($tagId ? $tagId : 'ArticleId');
		$content = $this->run('', TRUE);

		$objResponse->addAssign($tagId,'innerHTML', $content);
		$rc = $objResponse->getXML();
		return $rc;
	}

	/**
	 * set if the single view will be shown instead of a list view
	 *
	 * @param	boolean		$bValue:
	 * @return	void
	 */
	function setSingleFromList ($bValue)	{
		$this->bSingleFromList = $bValue;
	}

	/**
	 * Returns TRUE if the single view is shown instead of the list view
	 *
	 * @return	boolean
	 */
	function getSingleFromList ()	{
		return $this->bSingleFromList;
	}

	/**
	 * Displaying single products/ the products list / searching
	 *
	 * @param	string		$theCode: the plugin's CODE field
	 * @param	string		$errorMessage: filled in error case
	 * @param	array		out: $$error_code: contains error indices
	 * @return	string		content of the single view or list view
	 */
	function products_display ($theCode, &$errorMessage, &$error_code)	{
		global $TSFE;

		$bSingleFromList = FALSE;

		if (
			(
				(
					($theCode=='SEARCH') && $this->conf['listViewOnSearch'] == '1' || (strpos($theCode,'LIST') !== FALSE)
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

			if (!count($this->tt_product_single))	{
				if ($this->conf['defaultProductID'])	{
					$this->tt_product_single['product'] = $this->conf['defaultProductID'];
				} else if ($this->conf['defaultArticleID'])	{
					$this->tt_product_single['article'] = $this->conf['defaultArticleID'];
				}
			}

			if ($this->tt_product_single['product'] && $this->basket->isInBasket($this->tt_product_single['product']) ||
				!$this->conf['NoSingleViewOnList'] && !$this->conf['PIDitemDisplay'] && !$this->conf['PIDitemDisplay.']
			) {
				if ($this->convertToUserInt()) {
					return '';
				}
			}

			if (!is_object($this->singleView)) {
				// List single product:
				$this->singleView = t3lib_div::makeInstance('tx_ttproducts_single_view');
				$this->singleView->init (
					$this->pibase,
					$this->tt_product_single,
					$extVars,
					$this->pid,
					$this->conf['useArticles'],
					$this->config['pid_list'],
					$this->config['recursive']
				);
			}
			$content = $this->singleView->printView(
				$this->templateCode,
				$error_code,
				$this->pageAsCategory,
				$this->templateSuffix
			);
		} else {

	// page where to go usually
			$pid = ($this->conf['PIDbasket'] && $this->conf['clickIntoBasket'] ? $this->conf['PIDbasket'] : $TSFE->id);

			// List all products:
			$listView = t3lib_div::makeInstance('tx_ttproducts_list_view');
			$listView->init (
				$this->pibase,
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
			$bAllPages = false;
			$templateArea = $templateArea.$this->templateSuffix;
			$content = $listView->printView(
				$this->templateCode,
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
