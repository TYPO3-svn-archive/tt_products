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
 * Part of the TT_PRODUCTS (Shopping System) extension.
 *
 * Creates a list of products for the shopping basket in TYPO3.
 * Also controls basket, searching and payment.
 *
 * Typoscript config:
 * - See static_template "plugin.tt_products"
 * - See TSref.pdf
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
 *
 *  
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

require_once(PATH_t3lib.'class.t3lib_parsehtml.php');
require_once('class.tx_ttproducts_htmlmail.php');


class tx_ttproducts extends tslib_pibase {
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
	var $baseCurrency = '';				// currency iso code for default shop currency
	var $xrate = 1.0;				// currency exchange rate (currency/baseCurrency)

	var $isOverview = 0;				// overview mode of the basket
	var $mkl; 					// if compatible to mkl_products


	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	function main_products($content,$conf)	{
		global $TSFE, $LANG;

			// getting configuration values:
		$this->conf=$conf;

		$TSFE->set_no_cache();
    	// multilanguage support
        $this->language = $TSFE->config['config']['sys_language_uid'];
        $this->langKey = $langKey = strtoupper($TSFE->config['config']['language']);	// TYPO3_languages

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

		if ($this->config['useFlexforms']) {
			// Converting flexform data into array:
			$this->pi_initPIflexForm();
			$this->config['code'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'display_mode');
		} else {
			$this->config['code'] = strtolower(trim($this->cObj->stdWrap($this->conf['code'],$this->conf['code.'])));
		}
		$this->config['limit'] = t3lib_div::intInRange($this->conf['limit'],0,1000);
		$this->config['limit'] = $this->config['limit'] ? $this->config['limit'] : 50;
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
			$temp = t3lib_div::_GET('tt_products');
			$this->tt_product_single = $temp;			
//			$this->tt_product_single = ($temp ? $temp : $this->conf['defaultProductID']);
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

		$codes=t3lib_div::trimExplode(',', $this->config['code']?$this->config['code']:$this->conf['defaultCode'],1);
		if (!count($codes))     $codes=array('');

		while(list(,$theCode)=each($codes))
		{
			if (strtoupper($theCode)=='BASKET')
				$isBasket = 1;
			/* if (strtoupper($theCode)=='OVERVIEW')
				$this->isOverview = 1; */
		}

		if (t3lib_div::_GP('mode_update') && ($isBasket || ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_products']['pageAsCategory'] == 0)))
			$updateMode = 1;
		else
			$updateMode = 0;

		$this->initBasket($TSFE->fe_user->getKey('ses','recs'), $updateMode); // Must do this to initialize the basket...

		// *************************************
		// *** Listing items:
		// *************************************

		$this->$itemArray = array();
		reset($codes);
		while(list(,$theCode)=each($codes))	{
			$theCode = (string)strtoupper(trim($theCode));
			switch($theCode)	{
				case 'TRACKING':
				case 'BILL':
				case 'DELIVERY':
					$content.=$this->products_tracking($theCode);
				break;
				case 'BASKET':
				case 'PAYMENT':
				case 'FINALIZE':
				case 'OVERVIEW':
				case 'INFO':
					$content.=$this->products_basket($theCode);
				break;
				case 'SEARCH':
				case 'SINGLE':
				case 'LIST':
				case 'LISTOFFERS':
				case 'LISTHIGHLIGHTS':
				case 'LISTNEWITEMS':
					$content.=$this->products_display($theCode);
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
					$helpTemplate = $this->cObj->fileResource('EXT:'.TT_PRODUCTS_EXTkey.'/pi/products_help.tmpl');

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
		return $content;
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
		$currList = $this->exchangeRate->initCurrencies($this->BaseCurrency);
		$jScript =  '	var currlink = new Array(); '.chr(10);
		$index = 0;
		foreach( $currList as $key => $value)	{
			$url = $this->getLinkUrl('','',array('C' => 'C='.$key));
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
	 * Takes care of basket, address info, confirmation and gate to payment
	 */
	function products_basket($theCode)	{
		global $TSFE, $LANG;

		$content = '';
		$this->setPidlist($this->config['storeRootPid']);	// Set list of page id's to the storeRootPid.
		$this->initRecursive(999);		// This add's all subpart ids to the pid_list based on the rootPid set in previous line
		$this->generatePageArray();		// Creates an array with page titles from the internal pid_list. Used for the display of category titles.

		if (count($this->basketExt))	{	// If there is content in the shopping basket, we are going display some basket code
				// prepare action
			$activity='';
			if (t3lib_div::_GP('products_info'))	{
				$activity='products_info';
			} elseif (t3lib_div::_GP('products_payment'))	{
				$activity='products_payment';
			} elseif (t3lib_div::_GP('products_finalize'))	{
				$activity='products_finalize';
			}

			if ($theCode=='INFO')	{
				$activity='products_info';
			} elseif ($theCode=='OVERVIEW') {
				$activity='products_overview';
			} elseif ($theCode=='PAYMENT')	{
				$activity='products_payment';
			} elseif ($theCode=='FINALIZE')	{
				$activity='products_finalize';
			}

				// perform action
			switch($activity)	{
				case 'products_info':
					$this->load_noLinkExtCobj();
					$content.=$this->getBasket('###BASKET_INFO_TEMPLATE###');
				break;
				case 'products_overview':
					$this->load_noLinkExtCobj();
					$content.=$this->getBasket('###BASKET_OVERVIEW_TEMPLATE###');
				break;
				case 'products_payment':
					$this->load_noLinkExtCobj();
					$pidagb = intval($this->conf['PIDagb']);
					$check = $this->checkRequired();
					if ($check=='' &&
						(empty($pidagb) || isset($_REQUEST['recs']['personinfo']['agb']))) {
						$this->mapPersonIntoToDelivery();
						$content.=$this->getBasket('###BASKET_PAYMENT_TEMPLATE###');
					} else {	// If not all required info-fields are filled in, this is shown instead:
						$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker('###BASKET_REQUIRED_INFO_MISSING###'));
						$markerArray = $this->addURLMarkers(array());
						$label = '';
						if ($check=='') {
							$label = '*** AGB ***';
						} else {
							if (t3lib_extMgm::isLoaded('sr_feuser_register')) {
								$label = $LANG->sL('LLL:EXT:sr_feuser_register/pi1/locallang.php:missing_'.$check);
							} else {
								$label = 'field: '.$check;
							}
						}
						$markerArray['###ERROR_DETAILS###'] = $label;
						$content = $this->cObj->substituteMarkerArray($content, $markerArray);
					}
				break;
				case 'products_finalize':
					$check = $this->checkRequired();
					if ($check=='')	{
						$this->load_noLinkExtCobj();
						$this->mapPersonIntoToDelivery();
						$handleScript = $TSFE->tmpl->getFileName($this->basketExtra['payment.']['handleScript']);
						if ($handleScript)	{
							$content = $this->includeHandleScript($handleScript,$this->basketExtra['payment.']['handleScript.']);
						} else {
							$orderUid = $this->getBlankOrderUid();
							// Added Els: instead of orderconfirmation_template display a orderthanks_template (orderconfirmation_template is still used for sending the final email
							$tmpl = ($this->conf['PIDthanks'] > 0 ? 'BASKET_ORDERTHANKS_TEMPLATE' : 'BASKET_ORDERCONFIRMATION_TEMPLATE');
							$content.=$this->getBasket('###'.$tmpl.'###');
							$content.=$this->finalizeOrder($orderUid);	// Important: 	 MUST come after the call of prodObj->getBasket, because this function, getBasket, calculates the order! And that information is used in the finalize-function
						}
					} else {	// If not all required info-fields are filled in, this is shown instead:
						$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker('###BASKET_REQUIRED_INFO_MISSING###'));
						$content = $this->cObj->substituteMarkerArray($content, $this->addURLMarkers(array()));
					}
				break;
				default:
					$content.=$this->getBasket();
				break;
			}
		} else {
			if ($theCode=='OVERVIEW') {
				$this->load_noLinkExtCobj();
				$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker('###BASKET_OVERVIEW_EMPTY###'));
			}
			else {
				$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker('###BASKET_TEMPLATE_EMPTY###'));
			}
		}
		$markerArray=array();
		$markerArray['###EXTERNAL_COBJECT###'] = $this->externalCObject;	// adding extra preprocessing CObject
		$content= $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
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
		$markerArray['###FORM_URL###'] = $this->getLinkUrl();	// Add FORM_URL to globalMarkerArray, linking to self.
		$content= $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	}


	function load_noLinkExtCobj()	{
		if ($this->conf['externalProcessing_final'] || is_array($this->conf['externalProcessing_final.']))	{	// If there is given another cObject for the final order confirmation template!
			$this->externalCObject = $this->getExternalCObject('externalProcessing_final');
		}
	}

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
	}


	function categorycomp($row1, $row2)  {
		return strcmp($this->categories[$row1['category']], $this->categories[$row2['category']]);
	}

	/**
	 * Returning the pid out from the row using the where clause
	 */
	function getPID($conf, $confExt, $row) {
		$rc = 0;
		if ($confExt) {
			foreach ($confExt as $k1 => $param) {
				$type  = $param['type'];
				$where = $param['where'];
				switch ($type) {
					case 'sql':					
						if ($where) {
							$wherelist = explode ('AND', $where);
							$isValid = true;
							foreach ($wherelist as $k2 => $condition) {
								$args = explode ('=', $condition);
								if ($row[$args[0]] != $args[1]) {
									$isValid = false;
								}
							}
						}
						if ($isValid == true) {
							$rc = $param['pid'];
							break;
						}
						break;
					case 'pid':
						$rc = intval ($row['pid']);
						break;
				}
				if ($rc > 0) {
					break; //ready with the foreach loop
				}
			}
		} else
		{
			$rc = $conf;
		}
		return $rc;
	}

	/**
	 * Displaying single products/ the products list / searching
	 */
	function products_display($theCode, $memoItems='')	{
		global $TSFE, $LANG;


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

		$formUrl = $this->getLinkUrl($this->conf['PIDbasket']);
		if (!$formUrl) {
			$formUrl = $this->getLinkUrl(t3lib_div::_GP('backPID'));
		}
		if (($theCode=='SINGLE') || ($this->tt_product_single && !$this->conf['NoSingleViewOnList'])) {
			// List single product:
			
			if (!$this->tt_product_single) {
				$this->tt_product_single = $this->conf['defaultProductID'];	
			}
			
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

			if($row) {
			 	// $this->tt_product_single = intval ($row['uid']); // store the uid for later usage here

					// Get the subpart code
				$itemFrameWork ='';
				if ($this->config['displayCurrentRecord'])	{
					$itemFrameWork = trim($this->cObj->getSubpart($this->templateCode,$this->spMarker('###ITEM_SINGLE_DISPLAY_RECORDINSERT###')));
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
				if (!$itemFrameWork)	{$itemFrameWork = $this->cObj->getSubpart($this->templateCode,$this->spMarker('###ITEM_SINGLE_DISPLAY###'));}

					// Fill marker arrays
				$wrappedSubpartArray=array();
				$wrappedSubpartArray['###LINK_ITEM###']= array('<A href="'.$this->getLinkUrl(t3lib_div::_GP('backPID')).'">','</A>');


				if( $datasheetFile == '' )  {
					$wrappedSubpartArray['###LINK_DATASHEET###']= array('<!--','-->');
				}  else  {
					$wrappedSubpartArray['###LINK_DATASHEET###']= array('<A href="uploads/tx_ttproducts/datasheet/'.$datasheetFile.'">','</A>');
				}

				$item = $this->getItem($row);
				$markerArray = $this->getItemMarkerArray ($item,$catTitle,$this->config['limitImage']);
				$subpartArray = array();

				$markerArray['###FORM_NAME###']='item_'.$this->tt_product_single;

				$markerArray['###FORM_URL###']=$formUrl.'&tt_products='.$this->tt_product_single ;

				$url = $this->getLinkUrl('','tt_products');

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
				$wherestock = ($this->config['showNotinStock'] ? '' : 'AND (inStock >0) ');
				$queryprev = $queryPrevPrefix .' AND pid IN ('.$this->pid_list.')'. $wherestock . $this->cObj->enableFields('tt_products');
				$resprev = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $queryprev);

				if ($rowprev = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resprev) )
					$wrappedSubpartArray['###LINK_PREV_SINGLE###']=array('<A href="'.$url.'&tt_products='.$rowprev['uid'].'">','</A>');
				else
					$subpartArray['###LINK_PREV_SINGLE###']='';

				$querynext = $queryNextPrefix.' AND pid IN ('.$this->pid_list.')'. $wherestock . $this->cObj->enableFields('tt_products');
				$resnext = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $querynext);

				if ($rownext = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resnext) )
					$wrappedSubpartArray['###LINK_NEXT_SINGLE###']=array('<A href="'.$url.'&tt_products='.$rownext['uid'].'">','</A>');
				else
					$subpartArray['###LINK_NEXT_SINGLE###']='';

				if (trim($row['color']) == '')
					$subpartArray['###display_variant1###'] = '';
				if (trim($row['size']) == '')
					$subpartArray['###display_variant2###'] = '';
				if (trim($row['accessory']) == '0')
					$subpartArray['###display_variant3###'] = '';

					// Substitute
				$content= $this->cObj->substituteMarkerArrayCached($itemFrameWork,$markerArray,$subpartArray,$wrappedSubpartArray);
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
				$out=$this->cObj->substituteMarker($out, '###FORM_URL###', $this->getLinkUrl($this->conf['PIDsearch']));
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

			if ($theCode=='LISTOFFERS')
				$where .= ' AND offer';
			if ($theCode=='LISTHIGHLIGHTS')
				$where .= ' AND highlight';
			if ($theCode=='LISTNEWITEMS') {
				$temptime = time() - 86400*intval(trim($this->conf['newItemDays']));
				$where = 'AND tstamp >= '.$temptime;
			}
			if ($theCode=='MEMO') {
				if ($memoItems != '')
					$where = ' AND uid IN ('.$memoItems.')';
				else
					$where = ' AND 1=0';
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
				if ($memoItems != '')
					$t['listFrameWork'] = $this->cObj->getSubpart($this->templateCode,$this->spMarker('###MEMO_TEMPLATE###'));
				else
					$t['listFrameWork'] = $this->cObj->getSubpart($this->templateCode,$this->spMarker('###ITEM_LIST_TEMPLATE###'));

				$t['categoryTitle'] = $this->cObj->getSubpart($t['listFrameWork'],'###ITEM_CATEGORY###');
				$t['itemFrameWork'] = $this->cObj->getSubpart($t['listFrameWork'],'###ITEM_LIST###');
				$t['item'] = $this->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');

				if (!$this->conf['displayBasketColumns']) {
					$markerArray=array();
					$markerArray['###FORM_URL###']=$formUrl; // Applied later as well.
					$markerArray['###FORM_NAME###']='ShopForm';
					$t['itemFrameWork'] = $this->cObj->substituteMarkerArrayCached($t['itemFrameWork'],$markerArray,array(),array());
				}

				$pageArr=explode(',',$this->pid_list);

				$currentP='';
				$out='';
				$iCount=0;
				$more=0;		// If set during this loop, the next-item is drawn
				while(list(,$v)=each($pageArr))	{
					if (is_array($productsArray[$v]))	{
						global $categories1;

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
							$addQueryString['tt_products']= 'tt_products='.$row['uid'];
							$pid = $this->getPID($this->conf['PIDitemDisplay'], $this->conf['PIDitemDisplay.'], $row);
							$wrappedSubpartArray['###LINK_ITEM###']= array('<A href="'.$this->getLinkUrl($pid,'',$addQueryString).$css_current.'>','</A>');
							
							if( $datasheetFile == '' )  {
								$wrappedSubpartArray['###LINK_DATASHEET###']= array('<!--','-->');
							}  else  {
								$wrappedSubpartArray['###LINK_DATASHEET###']= array('<A href="uploads/tx_ttproducts/datasheet/'.$datasheetFile.'">','</A>');
							}

							$item = $this->getItem($row);
/* Added Bert: in stead of listImage -> Image, reason: images are read from directory */
//							$markerArray = $this->getItemMarkerArray ($item,$catTitle, $this->config['limitImage'],'image');
							$markerArray = $this->getItemMarkerArray ($item,$catTitle, $this->config['limitImage'],'listImage');
							$subpartArray = array();

							if (!$this->conf['displayBasketColumns'])
							{
								$markerArray['###FORM_URL###']=$formUrl; // Applied later as well.
								$markerArray['###FORM_NAME###']='item_'.$iCount;
							}
/*							else
							{ */
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
						/*	} */

							$markerArray['###FORM_MEMO###'] = $this->getLinkUrl($this->conf['PIDmemo']);
							// cuts note in list view
							if (strlen($markerArray['###PRODUCT_NOTE###']) > $this->conf['max_note_length'])
								$markerArray['###PRODUCT_NOTE###'] = substr($markerArray['###PRODUCT_NOTE###'], 0, $this->conf['max_note_length']) . '...';

							if (trim($row['color']) == '')
								$subpartArray['###display_variant1###'] = '';
							if (trim($row['size']) == '')
								$subpartArray['###display_variant2###'] = '';
							if (trim($row['accessory']) == '0')
								$subpartArray['###display_variant3###'] = '';

							$tempContent = $this->cObj->substituteMarkerArrayCached($t['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
							$itemsOut .= $tempContent;
							$iColCount++;
						}

						if ($this->conf['displayBasketColumns'] > 1) { // complete the last table row
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
				$url = $this->getLinkUrl('','begin_at');
					// Reset:
				$subpartArray=array();
				$wrappedSubpartArray=array();
				$markerArray=array();

				if ($more)	{
					$next = ($begin_at+$this->config['limit'] > $productsCount) ? $productsCount-$this->config['limit'] : $begin_at+$this->config['limit'];
					$wrappedSubpartArray['###LINK_NEXT###']=array('<A href="'.$url.'&begin_at='.$next.'">','</A>');
				} else {
					$subpartArray['###LINK_NEXT###']='';
				}
				if ($begin_at)	{
					$prev = ($begin_at-$this->config['limit'] < 0) ? 0 : $begin_at-$this->config['limit'];
					$wrappedSubpartArray['###LINK_PREV###']=array('<A href="'.$url.'&begin_at='.$prev.'">','</A>');
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
							$markerArray['###BROWSE_LINKS###'].= ' <A href="'.$url.'&begin_at='.(string)($i * $this->config['limit']).'">'.(string)($i+1).'</A> ';
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
	}

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
	}
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
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid', 'pages', 'uid IN ('.$this->pid_list.')');
		$this->pageArray = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
			$this->pageArray[$row['uid']] = $row;
		}
	}

	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 */
	function initBasket($basket, $updateMode)	{
		global $TSFE;
		$this->recs = $basket;	// Sets it internally
		$this->basket=array();

		error_log ('initBasket');
		$tmpBasketExt = $TSFE->fe_user->getKey('ses','basketExt');

		if (is_array($tmpBasketExt)) {
			$this->basketExt = $tmpBasketExt;
		} else {
			$this->basketExt = array();
		}

		$basketExtRaw = t3lib_div::_GP('ttp_basket');
		
		if ((!$this->isOverview) && is_array($basketExtRaw)) {

			while(list($uid,$basketItem)=each($basketExtRaw))       {
				if (t3lib_div::testInt($uid))   {
					if (!$updateMode)
					{
						$count=t3lib_div::intInRange($basketItem['quantity'],0,100000);
						$extVars = $basketItem['size'].';'.$basketItem['color'].';'.intval(100*$basketItem['accessory']);
						$this->basketExt[$uid][$extVars] = $count;

//						if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_products']['pageAsCategory'] == 0)
//						{
//							$this->basketExt[$uid][$extVars] = $count;
//						}
//						else
//						{
//							$this->basketExt[$uid][$extVars] += $count;
//						}
					}
					else
					{
						reset($basketItem); // TODO: has to be extended for color, size and accessory

						while(list($md5,$quantity)=each($basketItem)) {
							if (is_array($this->basketExt[$uid]))
							{
								reset($this->basketExt[$uid]);
								while(list($beVars,)=each($this->basketExt[$uid])) {
									//if (md5($beVars)==$md5) {
									$this->basketExt[$uid][$beVars] = $quantity;
									//}
								}
							}
						}
					}
				}
			}
		}

		// I did not find another possibility to delete elements complete from a multidimensional array
		// than to recreate the array
		$basketExtNew = array();
		reset($this->basketExt);
		while(list($tmpUid,$tmpSubArr)=each($this->basketExt)) {
			while(list($tmpExtVar,$tmpCount)=each($tmpSubArr)) {
				if ($tmpCount > 0) {
					$basketExtNew[$tmpUid][$tmpExtVar] = $tmpCount;
				}
			}
		}
		$this->basketExt = $basketExtNew;

		if (is_array($this->basketExt) && count($this->basketExt))
			$TSFE->fe_user->setKey('ses','basketExt',$this->basketExt);
		else
			$TSFE->fe_user->setKey('ses','basketExt',array());

		$this->setBasketExtras($basket);

		$this->personInfo = $basket['personinfo'];
		$this->deliveryInfo = $basket['delivery'];
		if ($TSFE->loginUser && (!$this->personInfo || $this->conf['lockLoginUserInfo']))	{
			$address = '';

			if ($this->conf['loginUserInfoAddress']) {
				$address = implode(chr(10),
					t3lib_div::trimExplode(chr(10),
						$TSFE->fe_user->user['address'].chr(10).
						$TSFE->fe_user->user['zip'].' '.$TSFE->fe_user->user['city'].chr(10).
						$TSFE->fe_user->user['country']
						,1)
					);
			}
			else {
				$address = $TSFE->fe_user->user['address'];
			}

/* Added Els: getting the field uid form fe_users and introducing the field feusers_uid into sys_products_orders */
			$this->personInfo['feusers_uid'] = $TSFE->fe_user->user['uid'];
			$this->personInfo['name'] = $TSFE->fe_user->user['name'];

			$this->personInfo['address'] = $address;
			$this->personInfo['email'] = $TSFE->fe_user->user['email'];
			$this->personInfo['telephone'] = $TSFE->fe_user->user['telephone'];
			$this->personInfo['fax'] = $TSFE->fe_user->user['fax'];
			$this->personInfo['zip'] = $TSFE->fe_user->user['zip'];
			$this->personInfo['city'] = $TSFE->fe_user->user['city'];
			$this->personInfo['country'] = $TSFE->fe_user->user['static_info_country'];
			$this->personInfo['agb'] = $TSFE->fe_user->user['agb'];
/* Added Els: getting the fields for displaying in the BASKET_PAYMENT_TEMPLATE  from fe_user */
			$this->personInfo['tx_feuserextrafields_initials_name'] = $TSFE->fe_user->user['tx_feuserextrafields_initials_name'];
			$this->personInfo['tx_feuserextrafields_prefix_name'] = $TSFE->fe_user->user['tx_feuserextrafields_prefix_name'];
			$this->personInfo['tx_feuserextrafields_gsm_tel'] = $TSFE->fe_user->user['tx_feuserextrafields_gsm_tel'];
			$this->personInfo['date_of_birth'] = date( 'd-m-Y', $TSFE->fe_user->user["date_of_birth"]); 
			$this->personInfo['company'] = $TSFE->fe_user->user['company'];
			$this->personInfo['tx_feuserextrafields_company_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_company_deliv'];
			$this->personInfo['address'] = $TSFE->fe_user->user['address'];
			$this->personInfo['tx_feuserextrafields_address_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_address_deliv'];
			$this->personInfo['tx_feuserextrafields_housenumber'] = $TSFE->fe_user->user['tx_feuserextrafields_housenumber'];
			$this->personInfo['tx_feuserextrafields_housenumber_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_housenumber_deliv'];
			$this->personInfo['tx_feuserextrafields_housenumberadd'] = $TSFE->fe_user->user['tx_feuserextrafields_housenumberadd'];
			$this->personInfo['tx_feuserextrafields_housenumberadd_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_housenumberadd_deliv'];
			$this->personInfo['tx_feuserextrafields_pobox'] = $TSFE->fe_user->user['tx_feuserextrafields_pobox'];
			$this->personInfo['tx_feuserextrafields_pobox_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_pobox_deliv'];
			$this->personInfo['tx_feuserextrafields_zip_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_zip_deliv'];
			$this->personInfo['tx_feuserextrafields_city_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_city_deliv'];
			$this->personInfo['tx_feuserextrafields_country'] = $TSFE->fe_user->user['tx_feuserextrafields_country'];
			$this->personInfo['tx_feuserextrafields_country_deliv'] = $TSFE->fe_user->user['tx_feuserextrafields_country_deliv'];

/* Added Els: getting the field tt_products_creditpoints and tt_products_vouchercode from fe_user */
			$this->personInfo['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'];
			$this->personInfo['tt_products_vouchercode'] = $TSFE->fe_user->user['tt_products_vouchercode'];
		}
	}

	/**
	 * Check if payment/shipping option is available
	 */
	function checkExtraAvailable($name,$key)	{
		$result = false;

		if (is_array($this->conf[$name.'.'][$key.'.']) && (!isset($this->conf[$name.'.'][$key.'.']['show']) || $this->conf[$name.'.'][$key.'.']['show']))	{
			$result = true;
		}

		return $result;
	}

	/**
	 * Setting shipping, payment methods
	 */
	function setBasketExtras($basket)	{
		global $TSFE;
		
			// shipping
		ksort($this->conf['shipping.']);
		reset($this->conf['shipping.']);
		$k=intval($basket['tt_products']['shipping']);
		if (!$this->checkExtraAvailable('shipping',$k))	{
			$k=intval(key($this->cleanConfArr($this->conf['shipping.'],1)));
		}
		$this->basketExtra['shipping'] = $k;
		$this->basketExtra['shipping.'] = $this->conf['shipping.'][$k.'.'];
		$excludePayment = trim($this->basketExtra['shipping.']['excludePayment']);

			// payment
		if ($excludePayment)	{
			$exclArr = t3lib_div::intExplode(',',$excludePayment);
			while(list(,$theVal)=each($exclArr))	{
				unset($this->conf['payment.'][$theVal]);
				unset($this->conf['payment.'][$theVal.'.']);
			}
		}

		$confArr = $this->cleanConfArr($this->conf['payment.']);
		while(list($key,$val)=each($confArr)) {
			if ($val['show'] || !isset($val['show']))
				if (($val['visibleForGroupID'] != '') &&
				    (!$this->isUserInGroup($TSFE->fe_user->user, $val['visibleForGroupID'])))
				{
					unset($this->conf['payment.'][$key.'.']);
				}
		}

		ksort($this->conf['payment.']);
		reset($this->conf['payment.']);
		$k=intval($basket['tt_products']['payment']);
		if (!$this->checkExtraAvailable('payment',$k))	{
			$k=intval(key($this->cleanConfArr($this->conf['payment.'],1)));
		}
		$this->basketExtra['payment'] = $k;
		$this->basketExtra['payment.'] = $this->conf['payment.'][$k.'.'];

	}

	/**
	 * Returns a clear 'recs[tt_products]' array - so clears the basket.
	 */
	function getClearBasketRecord()	{
			// Returns a basket-record cleared of tt_product items
		unset($this->recs['tt_products']);
		return ($this->recs);
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
			if ($this->conf['advanceOrderNumberWithInteger'])	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', '', '', 'uid DESC', '1');
				list($prevUid) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

				$rndParts = explode(',',$this->conf['advanceOrderNumberWithInteger']);
				$advanceUid = $prevUid+t3lib_div::intInRange(rand(intval($rndParts[0]),intval($rndParts[1])),1);
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
	}

	/**
	 * Returns a blank order uid. If there was no order id already, a new one is created.
	 *
	 * Blank orders are marked deleted and with status=0 initialy. Blank orders are not necessarily finalized because users may abort instead of buying.
	 * A finalized order is marked 'not deleted' and with status=1.
	 * Returns this uid which is a blank order record uid.
	 */
	function getBlankOrderUid()	{
		global $TSFE;

		$orderUid = intval($this->recs['tt_products']['orderUid']);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', 'uid='.intval($orderUid).' AND deleted AND NOT status');	// Checks if record exists, is marked deleted (all blank orders are deleted by default) and is not finished.
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
			$orderUid = $this->createOrder();
			$this->recs['tt_products']['orderUid'] = $orderUid;
			$this->recs['tt_products']['orderDate'] = time();
			$this->recs['tt_products']['orderTrackingNo'] = $this->getOrderNumber($orderUid).'-'.strtolower(substr(md5(uniqid(time())),0,6));
			$TSFE->fe_user->setKey('ses','recs',$this->recs);
		}
		return $orderUid;
	}

	/**
	 * Returns the orderRecord if $orderUid.
	 * If $tracking is set, then the order with the tracking number is fetched instead.
	 */
	function getOrderRecord($orderUid,$tracking='')	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_products_orders', ($tracking ? 'tracking_code="'.$GLOBALS['TYPO3_DB']->quoteStr($tracking, 'sys_products_orders').'"' : 'uid='.intval($orderUid)).' AND NOT deleted');
		return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	}

	/**
	 * This returns the order-number (opposed to the order_uid) for display in the shop, confirmation notes and so on.
	 * Basically this prefixes the .orderNumberPrefix, if any
	 */
	function getOrderNumber($orderUid)	{
		$orderNumberPrefix = substr($this->conf['orderNumberPrefix'],0,10);
		if ($orderNumberPrefix[0]=='%')
			$orderNumberPrefix = date(substr($orderNumberPrefix, 1));
		return $orderNumberPrefix.$orderUid;
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
	function finalizeOrder($orderUid,$mainMarkerArray=array())	{
		global $TSFE;
		global $TYPO3_DB;

			// Fix delivery address
		$this->mapPersonIntoToDelivery();	// This maps the billing address into the blank fields of the delivery address
		$mainMarkerArray['###EXTERNAL_COBJECT###'] = $this->externalCObject.'';
		$orderConfirmationHTML=trim($this->getBasket('###BASKET_ORDERCONFIRMATION_TEMPLATE###','',$mainMarkerArray));		// Getting the template subpart for the order confirmation!

			// Saving order data
		$fieldsArray=array();
		$fieldsArray['note']=$this->deliveryInfo['note'];
/* Added Els: introduce a field into sys_products_orders containing the uid of the fe_user */
		$fieldsArray['feusers_uid']=$this->deliveryInfo['feusers_uid'];
		$fieldsArray['name']=$this->deliveryInfo['name'];
		$fieldsArray['telephone']=$this->deliveryInfo['telephone'];
		$fieldsArray['fax']=$this->deliveryInfo['fax'];
		$fieldsArray['email']=$this->deliveryInfo['email'];
//		debug ($this->conf['email_notify_default'], "this->conf['email_notify_default']", __LINE__, __FILE__);
//Franz:		$fieldsArray['email_notify']=  $this->conf['email_notify_default'];		// Email notification is set here. Default email address is delivery email contact

			// can be changed after order is set.
		$fieldsArray['payment']=$this->basketExtra['payment'].': '.$this->basketExtra['payment.']['title'];
		$fieldsArray['shipping']=$this->basketExtra['shipping'].': '.$this->basketExtra['shipping.']['title'];
		$fieldsArray['amount']=$this->calculatedArray['priceTax']['total'];
		$fieldsArray['status']=1;	// This means, "Order confirmed on website, next step: confirm from shop that order is received"

/* Added Els: update fe_user with amount of creditpoints and subtract creditpoints used in order*/
		$fieldsArrayFeUsers = array();
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
					if ($this->calculatedArray['priceTax']['total'] >= intval($k2)) { // only the highest value for this count will be used; 1 should never be reached, this would not be logical
						$creditpoints = $points;		
						break; // finish
					}
				}
			}
			
			$fieldsArrayFeUsers['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'] + ($creditpoints * $this->calculatedArray['priceTax']['total']) - $this->recs['tt_products']['creditpoints'];
		}

/* Added Els: update fe_user with vouchercode */
		if ($this->recs['tt_products']['vouchercode'] != '') {
			// first check if vouchercode exist and is not their own vouchercode
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'fe_users', 'username="'.$this->recs['tt_products']['vouchercode'].'"');
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$uid_voucher = $row['uid'];
			}
			if (($uid_voucher != '') & ($this->deliveryInfo['feusers_uid'] != $uid_voucher) ) {
				$fieldsArrayFeUsers['tt_products_vouchercode'] = $this->recs['tt_products']['vouchercode'];
			}
		}
		
		if ($this->deliveryInfo['feusers_uid']) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$this->deliveryInfo['feusers_uid'], $fieldsArrayFeUsers);
	/* Added ELS2: update user from vouchercode with 5 credits */
	       // get the "old" creditpoints for the user
	       $res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tt_products_creditpoints', 'fe_users', 'username="'.$this->recs['tt_products']['vouchercode'].'"');
	       if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res1)) {
	           $ttproductscreditpoints = $row['tt_products_creditpoints'];
	       }
	       $fieldsArrayFeUserCredit = array();
	       $fieldsArrayFeUserCredit['tt_products_creditpoints'] = $ttproductscreditpoints + 5;
	       $GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$uid_voucher, $fieldsArrayFeUserCredit);
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

			// Fetching the orderRecord by selecing the newly saved one...
		$this->orderRecord = $this->getOrderRecord($orderUid);
		$content .= $this->getBasket('###BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE###');

		// Is no user is logged in --> create one
		if ($this->conf['createUsers'] && ($this->personInfo['email'] != '') && ($this->conf['PIDuserFolder']) && (trim($GLOBALS['TSFE']->fe_user->user['username']) == ''))
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
					'name' => $this->personInfo['name'],
					'address' => $this->personInfo['address'],
					'telephone' => $this->personInfo['telephone'],
					'fax' => $this->personInfo['fax'],
					'email' => $this->personInfo['email'],
					'zip' => $this->personInfo['zip'],
					'city' => $this->personInfo['city'],
					'country' => $this->personInfo['country'],
					'crdate' => time()
				);
				$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('fe_users', $insertFields);

				// send new user mail
				if (count($this->personInfo['email'])) {
					$emailContent=trim($this->getBasket('###EMAIL_NEWUSER_TEMPLATE###'));
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

			// loop over all items in the basket indexed by page and itemnumber
			foreach ($this->itemArray as $pid=>$pidItem) {
				foreach ($pidItem as $itemnumber=>$actItemArray) {					
					// foreach ($actItemArray as $k1=>$actItem) {
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
							
										// Saving the order record	TODO: support color, size and accessory here
							$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_products', 'uid='.intval($actItemArray[0]['rec']['uid']), $fieldsArray);
						}
					}
					// }
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
				
				// foreach ($actItemArray as $k1=>$actItem) {
				$insertFields = array (
					'sys_products_orders_uid' => $orderUid,
					'sys_products_orders_qty' => intval($actItemArray[0]['count']),
					'tt_products_uid' => intval($actItemArray[0]['rec']['uid'])
				);	// TODO: differentiate between colors, sizes and accessory
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
				//}
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
				$csvdescr = '"uid";"count";"size";"color";"accessory"';
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
							$sizecoloraccessory = explode(';', $actItem['rec']['extVars']);
							$csvdata = '"'.intval($actItem['rec']['uid']).'";"'.
										intval($actItem['count']).'";"'.
										$sizecoloraccessory[0].'";"'.
										$sizecoloraccessory[1].'";"'.
										$sizecoloraccessory[2]/100 .'"';
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
			$emailContent=trim($this->getBasket('###EMAIL_PLAINTEXT_TEMPLATE###'));
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
		$TSFE->fe_user->setKey('ses','recs',$this->getClearBasketRecord());
		$TSFE->fe_user->setKey('ses','basketExt',array());

			// This cObject may be used to call a function which clears settings in an external order system.
			// The output is NOT included anywhere
		$this->getExternalCObject('externalFinalizing');

		return $content;
	}


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
	}

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
	}

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
	}


	/**
	 * Takes an array with key/value pairs and returns it for use in an UPDATE query.
	 */
	function getUpdateQuery($Darray)	{
		reset($Darray);
		$query=array();
		while(list($field,$data)=each($Darray))	{
			$query[]=$field.'=\''.addslashes($data).'\'';
		}
		return implode($query,',');
	}

	/**
	 * Generates a search where clause.
	 */
	function searchWhere($sw)	{
		$where=$this->cObj->searchWhere($sw, $this->searchFieldList, 'tt_products');
		return $where;
	}

	/**
	 * Returns a url for use in forms and links
	 */
	function getLinkUrl($id='',$excludeList='',$addQueryString=array())	{
		global $TSFE;

		$queryString=array();
		$queryString['id'] = 'id=' . ($id ? $id : $TSFE->id);
		$queryString['type']= $TSFE->type ? 'type='.$TSFE->type : '';
		$queryString['L']= t3lib_div::GPvar('L') ? 'L='.t3lib_div::GPvar('L') : '';
		$queryString['C']= t3lib_div::GPvar('C') ? 'C='.t3lib_div::GPvar('C') : $this->currency ? 'C='.$this->currency : '';
		if( isset($addQueryString['C']) )  {
			$queryString['C'] = $addQueryString['C'] ;
			unset( $addQueryString['C'] );
		}
		$queryString['backPID']= 'backPID='.$TSFE->id;
		$queryString['begin_at']= t3lib_div::_GP('begin_at') ? 'begin_at='.t3lib_div::_GP('begin_at') : '';
		$queryString['swords']= t3lib_div::_GP('swords') ? 'swords='.rawurlencode(t3lib_div::_GP('swords')) : '';
		$queryString['newitemdays']= t3lib_div::GPvar('newitemdays') ? 'newitemdays='.rawurlencode(stripslashes(t3lib_div::GPvar('newitemdays'))) : '';

		reset($queryString);
		while(list($key,$val)=each($queryString))	{
			if (!$val || ($excludeList && t3lib_div::inList($excludeList,$key)))	{
				unset($queryString[$key]);
			}
		}

		if ($TSFE->config['config']['simulateStaticDocuments'])   {
			$pageId = $id ? $id : $TSFE->id ;
			$pageType = $TSFE->type ;
			unset($queryString['id']);
			unset($queryString['type']);

			$allQueryString = implode($queryString,'&');
			if( $addQueryString )	{
				$allQueryString .= '&'.implode($addQueryString,'&');
			}
            return $TSFE->makeSimulFileName('', $pageId, $pageType, $allQueryString ).'.html';

		}
		else	{
			$allQueryString = implode($queryString,'&');
			if( $addQueryString )	{
				$allQueryString .= '&'.implode($addQueryString,'&');
			}
			return $TSFE->absRefPrefix.'index.php?'.$allQueryString;
		}

	}


	/**
	 * convert amount to selected currency
	 */
	function getCurrencyAmount($double)	{
		if( $this->currency != $this->baseCurrency )	{
			$double = $double * $this->xrate ;
		}
		return $double;
	}

	/**
	 * Formatting a price
	 */
	function priceFormat($double)	{
		return number_format($double,intval($this->conf['priceDec']),$this->conf['priceDecPoint'],$this->conf['priceThousandPoint']);
	}

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
	}

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
	}

	/**
	 * Include calculation script which should be programmed to manipulate internal data.
	 */
	function includeCalcScript($calcScript,$conf)	{
		include($calcScript);
	}

	/**
	 * Include handle script
	 */
	function includeHandleScript($handleScript,$conf)	{
		include($handleScript);
		return $content;
	}


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
	}


	// **************************
	// Template marker substitution
	// **************************

	/**
	 * Fills in the markerArray with data for a product
	 */
	function &getItemMarkerArray (&$item,$catTitle, $imageNum=0, $imageRenderObj='image')	{
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

		$temp = $this->basketExt[$row['uid']][$row['size'].';'.$row['color'].';'.intval(100*$row['accessory'])];
		$markerArray['###FIELD_QTY###']= $temp ? $temp : '';
		$markerArray['###FIELD_NAME_BASKET###']='ttp_basket['.$row['uid'].']['.md5($row['extVars']).']';

		$markerArray['###FIELD_SIZE_NAME###']='ttp_basket['.$row['uid'].'][size]';
		$markerArray['###FIELD_SIZE_VALUE###']=$row['size'];
		$markerArray['###FIELD_COLOR_NAME###']='ttp_basket['.$row['uid'].'][color]';
		$markerArray['###FIELD_COLOR_VALUE###']=$row['color'];
		$markerArray['###FIELD_ACCESSORY_NAME###']='ttp_basket['.$row['uid'].'][accessory]';
		$markerArray['###FIELD_ACCESSORY_VALUE###']=$row['accessory'];


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

		$markerArray['###PRODUCT_WEIGHT###'] = doubleval($row['weight']);
		$markerArray['###BULKILY_WARNING###'] = $row['bulkily'] ? $this->conf['bulkilyWarning'] : '';
		$markerArray['###PRODUCT_COLOR###'] = $prodColorText;
		$markerArray['###PRODUCT_SIZE###'] = $prodSizeText;
		$markerArray['###PRODUCT_ACCESSORY###'] = $prodAccessoryText;
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
	}

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
	}

	/**
	 * Adds URL markers to a markerArray
	 */
	function addURLMarkers($markerArray)	{
			// Add's URL-markers to the $markerArray and returns it
		$markerArray['###FORM_URL###'] = $this->getLinkUrl($this->conf['PIDbasket']);
		$markerArray['###FORM_URL_INFO###'] = $this->getLinkUrl($this->conf['PIDinfo'] ? $this->conf['PIDinfo'] : $this->conf['PIDbasket']);
		$markerArray['###FORM_URL_FINALIZE###'] = $this->getLinkUrl($this->conf['PIDfinalize'] ? $this->conf['PIDfinalize'] : $this->conf['PIDbasket']);
		$markerArray['###FORM_URL_THANKS###'] = $this->getLinkUrl($this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : $this->conf['PIDbasket']);
		$markerArray['###FORM_URL_TARGET###'] = '_self';
		if ($this->basketExtra['payment.']['handleURL'])	{	// This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
			$markerArray['###FORM_URL_THANKS###'] = $this->basketExtra['payment.']['handleURL'];
		}
		if ($this->basketExtra['payment.']['handleTarget'])	{	// Alternative target
			$markerArray['###FORM_URL_TARGET###'] = $this->basketExtra['payment.']['handleTarget'];
		}
		return $markerArray;
	}

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
			 
		error_log ('generateRadioSelect');
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
	}


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

	}

		// result: fill in the  ['calcprice'] of $itemArray['pid'] ['itemnumber']
	function GetCalculatedData() { // delete countTotal if not neede any more
		global $TSFE;

		$getDiscount = 0;

		$gr_list = explode (',' , $TSFE->gr_list);

		if ($this->conf['getDiscountPrice']) {
			$getDiscount = 1;
		} else {
			while (list(,$val) = each ($gr_list)) {
				if ((intval($val) > 0) && ($getDiscount == 0)) {
					$getDiscount = 1 - strcmp($TSFE->fe_user->groupData->title, $this->conf['discountGroupName '] );

					if (strlen($TSFE->fe_user->groupData['title']) == 0)	// repair result of strcmp
						$getDiscount = 0;
				}
			}
		}

/*
		if($this->conf['pricecalc.'])
		{
			ksort($this->conf['pricecalc.']);
			reset($this->conf['pricecalc.']);
			foreach ($this->conf['pricecalc.'] as $k1 => $pricecalc)
			{
				$k=intval($k1);
				$this->basketExtra['pricecalc.'][$k] = $this->conf['pricecalc.'][$k1]['prod.'];
			}
		}

		if($this->conf['discountprice.'])
		{
			ksort($this->conf['discountprice.']);
			reset($this->conf['discountprice.']);
			foreach ($this->conf['discountprice.'] as $k1 => $discountprice)
			{
				$k=intval($k1);
				if ($k == 0)
					continue;
				$this->basketExtra['discountprice.'][$k] = $this->conf['discountprice.'][$k1]['prod.'];
			}
		}
*/

		$priceTotal = array();
		$priceReduction = array();

		$additive = 0;
		// Check if a special group price can be used
		if (($getDiscount == 1) && ($this->conf['discountprice.'] != NULL))
		{
			$countTotal = 0;
			$countedItems = array();

			ksort($this->conf['discountprice.']['prod.']);
			reset($this->conf['discountprice.']['prod.']);

			$type = '';
			$field = '';
			foreach ($this->conf['discountprice.'] as $k1=>$priceCalcTemp) {
				if (!is_array($priceCalcTemp)) {
					switch ($k1) {
						case 'type':
							$type = $priceCalcTemp;
							break;
						case 'field':
							$field = $priceCalcTemp;
							break;
						case 'additive':
							$additive = $priceCalcTemp;
							break;
					}
					continue;
				}
				$dumCount = 0;
				$pricefor1 = doubleval($priceCalcTemp['prod.']['1']);
				$pricefor1Index = 100*$pricefor1;

				// loop over all items in the basket indexed by page and itemnumber
				foreach ($this->itemArray as $pid=>$pidItem) {
					foreach ($pidItem as $itemnumber=>$actItemArray) {
						foreach ($actItemArray as $k2=>$actItem) {
					// count all items which will apply to the discount price
//				foreach ($this->itemArray as $pid=>$pidItem) {
//					foreach ($pidItem as $itemnumber=>$actItem) {
							$count2 = $actItem['count'];
							if (($count2 > 0) && ($actItem['rec']['price'] == $pricefor1)) {
								$countedItems [$pricefor1Index][] = array ('pid' => $pid, 'itemnumber' => $itemnumber);
								$dumCount += $count2;
							}
						}
					}
				}

				$countTotal += $dumCount;

				if ($additive == 0) {
					krsort($priceCalcTemp['prod.']);
					reset($priceCalcTemp['prod.']);

					foreach ($priceCalcTemp['prod.'] as $k2=>$price2) {
						if ($dumCount >= intval($k2)) { // only the highest value for this count will be used; 1 should never be reached, this would not be logical
							if (intval($k2) > 1) {
								// store the discount price in all calculated items from before
								foreach ($countedItems as $k3=>$v3) {
									foreach ($this->itemArray[$v3['pid']] [$v3['itemnumber']] as $k4=>$actItem) { 
									 	$this->itemArray[$v3['pid']] [$v3['itemnumber']][$k4] ['calcprice'] = $price2;
									}
								}
								$priceReduction[$pricefor1Index] = 1; // remember the reduction in order not to calculate another price with $priceCalc
							}
							else {
								$priceReduction[$pricefor1Index] = 0;
							}
							break; // finish
						}
					}
				}
			}
			if ($additive == 1) {

				reset($this->conf['discountprice.']);

				foreach ($this->conf['discountprice.'] as $k1=>$priceCalcTemp) {
					if (!is_array($priceCalcTemp)) {
						continue;
					}
					$pricefor1 = doubleval($priceCalcTemp['prod.']['1']);
					if ($countedItems [100*$pricefor1] == NULL) {
						continue;
					}

					krsort($priceCalcTemp['prod.']);
					reset($priceCalcTemp['prod.']);
					while (list ($k2, $price2) = each ($priceCalcTemp['prod.'])) {
						if ($countTotal >= intval($k2)) { // search the price from the total count
							if (intval($k2) > 1) {
								// store the discount price in all calculated items from before
								foreach ($countedItems[$pricefor1Index] as $k3=>$v3) {
									foreach ($this->itemArray[$v3['pid']] [$v3['itemnumber']] as $k1=>$actItem) { 
									 	$this->itemArray[$v3['pid']] [$v3['itemnumber']][$k1] ['calcprice'] = $price2;
									}
								}
								$priceReduction[$pricefor1Index] = 1; // remember the reduction in order not to calculate another price with $priceCalc later
							}
							else  {	// $priceTotal [$k1] contains the product count
								$priceReduction[$pricefor1Index] = 0;
							}
							break; // finish
						}
					}
				}
			}
			else
			{	// nothing
			}
		}

		if ($this->conf['pricecalc.']) {
			$countTotal = 0;

			ksort($this->conf['pricecalc.']);
			reset($this->conf['pricecalc.']);

			foreach ($this->conf['pricecalc.'] as $k1=>$priceCalcTemp) {
				if (!is_array($priceCalcTemp)) {
					continue;
				}
				$countedItems = array();

				$pricefor1 = doubleval($priceCalcTemp['prod.']['1']);
				$pricefor1Index = 100*$pricefor1;

				// has the price already been calculated before ?
				if ($priceReduction[$pricefor1Index] == 1) {
					continue;
				}
				$dumCount = 0;

				reset($this->itemArray);
				// loop over all items in the basket indexed by page and itemnumber
				foreach ($this->itemArray as $pid=>$pidItem) {
					foreach ($pidItem as $itemnumber=>$actItemArray) {
						foreach ($actItemArray as $k2=>$actItem) {
				//foreach ($this->itemArray as $pid=>$pidItem) {
					//foreach ($pidItem as $itemnumber=>$actItem) {
							// count all items which will apply to the discount price
							$count2 = $actItem['count'];
							if (($count2 > 0) && ($actItem['rec']['price'] == $pricefor1)) {
								$countedItems [$pricefor1Index][] = array ('pid' => $pid, 'itemnumber' => $itemnumber);
								$dumCount += $count2;
							}
						}
					}
				}
				
					// nothing found?
				if ($dumCount == 0) {
					continue;
				}

				$countTotal += $dumCount;

				$priceTotalTemp = 0;
				$countTemp = $dumCount;
				krsort($priceCalcTemp['prod.']);
				reset($priceCalcTemp['prod.']);
				foreach ($priceCalcTemp['prod.'] as $k2=>$price2) {
					if (intval($k2) > 0) {
						while ($countTemp >= intval($k2)) {
							$countTemp -= intval($k2);
							$priceTotalTemp += doubleval($price2);
						}
					}
				}

				$priceProduct = ($dumCount > 0 ? ($priceTotalTemp / $dumCount) : 0);
				foreach ($countedItems[$pricefor1Index] as $k3=>$v3) {
					foreach ($this->itemArray[$v3['pid']] [$v3['itemnumber']] as $k4=>$actItem) {
						$this->itemArray[$v3['pid']] [$v3['itemnumber']] [$k4] ['calcprice'] = $priceProduct;
					}
				}
			}
		}

	}

	function getValue(&$basketElement, $basketProperties, $countTotal)
	{
		$result = 0;

		// to remain downwards compatible
		if (is_string($basketElement))
		{
        	$result = $basketElement;
        }

		if(is_array($basketProperties) && count($basketProperties) > 0)
		{
			foreach ($basketProperties as $lowKey => $lowValue)
			{
				if (strlen($lowKey) > 0 && $countTotal >= $lowKey)
				{
					$result = doubleVal($lowValue);
				}
			}
		}

		return $result;
	}


	function &getItem (&$row) {
		$count = intval($this->basketExt[$row['uid']][$row['size'].';'.$row['color'].';'.intval(100*$row['accessory'])]);
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
	}

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

	/**
	 * This calculates the totals. Very important function.
	This function also calculates the internal arrays

	$this->itemArray				- The basked elements, how many (quantity, count) and the price
	$this->calculatedArray	- Sums of goods, shipping, payment and total amount WITH TAX included

	... which holds the total amount, the final list of products and the price of payment and shipping!!

	TODO: make all the basket calculations only here and call this function only once
	 */
	function getCalculatedBasket()	{
		if (count($this->itemArray)) {// the item array contains all the data for the elements found in the basket
			return;	// this function is called in a loop from getBasket
					// all the calculations however need to be done only once
					// the global member variables must already be filled in
		}

		$uidArr = array();
		reset($this->basketExt);
		while(list($uidTmp,)=each($this->basketExt))
			if (!in_array($uidTmp, $uidArr))
				$uidArr[] = $uidTmp;

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', 'uid IN ('.implode(',',$uidArr).') AND pid IN ('.$this->pid_list.')'.$this->cObj->enableFields('tt_products'));

		$this->productsArray = array(); // TODO: use only local products array
		$productsArray = &$this->productsArray;
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
			reset($this->basketExt[$row['uid']]);
			while(list($bextVars,)=each($this->basketExt[$row['uid']])) {
				$sizecoloraccessory = explode(';', $bextVars);
				$row['size'] = $sizecoloraccessory[0];
				$row['color'] = $sizecoloraccessory[1];
				$row['accessory'] = floatval($sizecoloraccessory[2]);
				$row['extVars'] = $bextVars;
				$productsArray[$row['pid']][]=$row;
			}
		}

		$pageArr=explode(',',$this->pid_list);

		$this->calculatedArray = array(); // this array is usede for all calculated things

		while(list(,$v)=each($pageArr))	{
			if (is_array($productsArray[$v]))	{
				reset($productsArray[$v]);
				while(list(,$row)=each($productsArray[$v]))	{
					// if reseller is logged in then take 'price2', default is 'price'
					$newItem = $this->getItem($row);
					$this->itemArray [intval($row['pid'])] [intval($row['itemnumber'])][] = $newItem;
					$count = $newItem['count'];
					$priceTax = $newItem['priceTax'];
					$priceNoTax = $newItem['priceNoTax'];
					
					$this->calculatedArray['count']			+= $count;
					$this->calculatedArray['weight']		+= $row['weight']*$count;
					
					$oldPriceTax = $this->getPrice($row['price'],1,$row['tax']);
					$oldPriceNoTax = $this->getPrice($row['price'],0,$row['tax']);
										
					/* Added Els3: if oldpricenotax is 0 -> oldpricenotax = pricenotax, otherwise price_discount doesn't calculate correctly */
					if (doubleval($oldPriceNoTax) == 0) {
					   $oldPriceNoTax = $priceNoTax;
					}
					
					$this->calculatedArray['oldPriceTax']	+= $oldPriceTax * $count;
					$this->calculatedArray['oldPriceNoTax']	+= $oldPriceNoTax * $count;
				}
			}
		}

		// set the 'calcprice' in itemArray
		if ($this->conf['pricecalc.'] || $this->conf['discountprice.']) {
			// do the price calculation
			$this->GetCalculatedData();
		}

		// loop over all items in the basket indexed by page and itemnumber
		foreach ($this->itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					// has the price been calculated before and no reseller price?
					if ($actItem['calcprice'] > 0) {
						$this->itemArray[$pid][$itemnumber][$k1]['priceTax'] = $this->getPrice($actItem['calcprice'],1,$actItem['rec']['tax']);
						$this->itemArray[$pid][$itemnumber][$k1]['priceNoTax'] = $this->getPrice($actItem['calcprice'],0,$actItem['rec']['tax']);
					}
					// If accesssory has been selected, add the price of it, multiplicated with the count :
					if($actItem['rec']['accessory'] > 0 ) {
						$this->itemArray[$pid][$itemnumber][$k1]['totalTax'] = ($this->itemArray[$pid][$itemnumber][$k1]['priceTax']+ $this->getPrice($actItem['rec']['accessory'],1,$actItem['rec']['tax']))*$actItem['count'];
						$this->itemArray[$pid][$itemnumber][$k1]['totalNoTax'] = ($this->itemArray[$pid][$itemnumber][$k1]['priceNoTax']+getPrice($actItem['rec']['accessory'],0,$actItem['rec']['tax']))*$actItem['count'];
					} else {
						$this->itemArray[$pid][$itemnumber][$k1]['totalTax'] = $this->itemArray[$pid][$itemnumber][$k1]['priceTax'] * $actItem['count'];
						$this->itemArray[$pid][$itemnumber][$k1]['totalNoTax'] = $this->itemArray[$pid][$itemnumber][$k1]['priceNoTax'] * $actItem['count'];
					}
							// Fills this array with the product records. Reason: Sorting them by category (based on the page, they reside on)
					$this->calculatedArray['priceTax']['goodstotal'] += $this->itemArray[$pid][$itemnumber][$k1]['totalTax'];
					$this->calculatedArray['priceNoTax']['goodstotal'] += $this->itemArray[$pid][$itemnumber][$k1]['totalNoTax'];
	
					$this->calculatedArray['categoryPriceTax']['goodstotal'][$actItem['rec']['category']]+= $this->itemArray[$pid][$itemnumber][$k1]['totalTax'];
					$this->calculatedArray['categoryPriceNoTax']['goodstotal'][$actItem['rec']['category']]+= $this->itemArray[$pid][$itemnumber][$k1]['totalNoTax'];
				}
			}
		}

		foreach ($this->productsArray as $prodUID) {
			foreach ($prodUID as $prodSingle)
			{
				if ($prodSingle['bulkily'])
				{
					$value = ($this->conf['bulkilyAddition'] * $this->basketExt[$prodSingle['uid']][$prodSingle['size'].';'.$prodSingle['color'].';'.intval(100*$prodSingle['accessory'])]);
					$this->$this->calculatedArray['priceTax']['shipping'] += $value  * (1+$conf['bulkilyFeeTax']/100);
					$this->calculatedArray['priceNoTax']['shipping'] += $value;
				}
			}
		}

		// TAXpercentage replaces priceNoTax
		if ($this->conf['shipping.']['TAXpercentage']) {
			$this->calculatedArray['priceNoTax']['shipping'] = $this->calculatedArray['priceTax']['shipping']/(1+doubleVal($this->conf['shipping.']['TAXpercentage'])/100);
		}

			// Shipping must be at the end in order to use the calculated values from before
		$this->GetPaymentShippingData(
			$this->calculatedArray['count'],
/* Added Els: necessary to calculate shipping price which depends on total no-tax price */
			$this->calculatedArray['priceNoTax']['goodstotal'],
			$this->calculatedArray['priceTax']['shipping'],
			$this->calculatedArray['priceNoTax']['shipping'],
			$this->calculatedArray['priceTax']['payment'],
			$this->calculatedArray['priceNoTax']['payment']
			);

	}


	/**
	 * This generates the shopping basket layout and also calculates the totals. Very important function.
	 */
	function getBasket($subpartMarker='###BASKET_TEMPLATE###', $templateCode='', 		$mainMarkerArray=array())	{
			/*
				Very central function in the library.
				By default it extracts the subpart, ###BASKET_TEMPLATE###, from the $templateCode (if given, else the default $this->templateCode)
				and substitutes a lot of fields and subparts.
				Any pre-preparred fields can be set in $mainMarkerArray, which is substituted in the subpart before the item-and-categories part is substituted.
			*/

		global $TSFE;
/* ADDED Els: need for vouchercode conditions */
		global $TYPO3_DB;
		$templateCode = $templateCode ? $templateCode : $this->templateCode;

		$this->getCalculatedBasket();  // all the basket calculation is done in this function once and not multiple times here

			// Getting subparts from the template code.
		$t=array();
			// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
		$t['basketFrameWork'] = $this->cObj->getSubpart($templateCode,$this->spMarker($subpartMarker));

		if (trim($this->cObj->getSubpart($t['basketFrameWork'],'###BILLING_ADDRESS_LOGIN###')))	{
			if ($GLOBALS['TSFE']->loginUser)	{
				$t['basketFrameWork'] = $this->cObj->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS###', '');
			} else {
				$t['basketFrameWork'] = $this->cObj->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS_LOGIN###', '');
			}
		}


		$t['categoryTitle'] = $this->cObj->getSubpart($t['basketFrameWork'],'###ITEM_CATEGORY###');
		$t['itemFrameWork'] = $this->cObj->getSubpart($t['basketFrameWork'],'###ITEM_LIST###');
		$t['item'] = $this->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');

		$currentP='';
		$out='';
		$itemsOut='';

/*
		while(list(,$v)=each($pageArr))	{
			if (is_array($productsArray[$v]))	{
				reset($productsArray[$v]);
				$itemsOut='';
				while(list(,$row)=each($productsArray[$v]))	{ */

		// loop over all items in the basket sorted by page and itemnumber
	//	foreach ($this->itemArray as $pid=>$pidItem) {
		//	foreach ($pidItem as $itemnumber=>$actItem) {

		reset ($this->itemArray);
		// loop over all items in the basket indexed by page and itemnumber
		foreach ($this->itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {

					$pidcategory = ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_products']['pageAsCategory'] == 1 ? $pid : '');
					$currentPnew = $pidcategory.'_'.$actItem['rec']['category'];
						// Print Category Title
					if ($currentPnew!=$currentP)	{
						if ($itemsOut)	{
							$out.=$this->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
						}
						$itemsOut='';			// Clear the item-code var
						$currentP = $currentPnew;
						if ($this->conf['displayBasketCatHeader'])	{
							$markerArray=array();
							$pageCatTitle = '';
							if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_products']['pageAsCategory'] == 1) {
									$pageCatTitle = $this->pageArray[$pid]['title'].'/';
							}
							$catTitle= $pageCatTitle.($actItem['rec']['category']?$this->categories[$actItem['rec']['category']]:'');
							$this->cObj->setCurrentVal($catTitle);
							$markerArray['###CATEGORY_TITLE###']=$this->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
							$out.= $this->cObj->substituteMarkerArray($t['categoryTitle'], $markerArray);
						}
					}
	
						// Fill marker arrays
					$wrappedSubpartArray=array();
					$subpartArray=array();
					$markerArray = $this->getItemMarkerArray ($actItem,$catTitle,1,'basketImage');
	
					$markerArray['###PRODUCT_COLOR###'] = $actItem['rec']['color'];
					$markerArray['###PRODUCT_SIZE###'] = $actItem['rec']['size'];
	
	                $catTitle= $actItem['rec']['category']?$this->categories[$actItem['rec']['category']]:'';
					$this->cObj->setCurrentVal($catTitle);
					$markerArray['###CATEGORY_TITLE###']=$this->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
	
					// If accesssory has been selected, add the price of it, multiplicated with the count :
					if($actItem['rec']['accessory'] > 0 ){
						$markerArray['###PRICE_ACCESSORY_TEXT###']= $this->conf['accessoryText'];
						$markerArray['###PRICE_ACCESSORY_COUNT###']= '<INPUT size="3" maxlength="4" type="text" class="readonly" name="'.$actItem['count'].'" value="'.$actItem['count'].'" readonly="readonly">';
						$markerArray['###ACCESSORY_VALUE_TAX###']= $this->printPrice($this->priceFormat($this->getPrice($actItem['rec']['accessory'.$this->config['priceNoReseller']],1,$actItem['rec']['tax'])));
						$markerArray['###ACCESSORY_VALUE_NO_TAX###']= $this->printPrice($this->priceFormat($this->getPrice($actItem['rec']['accessory'.$this->config['priceNoReseller']],0,$actItem['rec']['tax'])));
					}
					else {
						$markerArray['###PRICE_ACCESSORY_TEXT###']= '';
						$markerArray['###PRICE_ACCESSORY_COUNT###']= '';
						$markerArray['###ACCESSORY_VALUE_TAX###']= '';
						$markerArray['###ACCESSORY_VALUE_NO_TAX###']= '';
					}
	
					$markerArray['###PRICE_TOTAL_TAX###']=$this->priceFormat($actItem['totalTax']);
					$markerArray['###PRICE_TOTAL_NO_TAX###']=$this->priceFormat($actItem['totalNoTax']);
	
					$pid = $this->getPID($this->conf['PIDitemDisplay'], $this->conf['PIDitemDisplay.'], $actItem['rec']);
					$wrappedSubpartArray['###LINK_ITEM###']=array('<A href="'.$this->getLinkUrl($pid).'&tt_products='.$actItem['rec']['uid'].'">','</A>');
	
					if (trim($actItem['rec']['color']) == '')
						$subpartArray['###display_variant1###'] = ($subpartMarker == '###EMAIL_PLAINTEXT_TEMPLATE###' ? $this->cObj->getSubpart($tempContent,'###display_variant1###') : '');
					if (trim($actItem['rec']['size']) == '')
						$subpartArray['###display_variant2###'] = ($subpartMarker == '###EMAIL_PLAINTEXT_TEMPLATE###' ? $this->cObj->getSubpart($tempContent,'###display_variant2###') : '');
					if (trim($actItem['rec']['accessory']) == '0')
						$subpartArray['###display_variant3###'] = ($subpartMarker == '###EMAIL_PLAINTEXT_TEMPLATE###' ? $this->cObj->getSubpart($tempContent,'###display_variant3###') : '');
	
						// Substitute
					$tempContent = $this->cObj->substituteMarkerArrayCached($t['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
	
	/*				if ($subpartMarker == '###EMAIL_PLAINTEXT_TEMPLATE###') {
						$tempVar = $this->cObj->getSubpart($tempContent,"###display_variant1###");
						if (trim($actItem['rec']['color']) == '')
							$tempVar = '';
						$tempContent = $this->cObj->substituteSubpart($tempContent, "###display_variant1###", $tempVar);
						$tempVar = $this->cObj->getSubpart($tempContent,"###display_variant2###");
						if (trim($actItem['rec']['size']) == '')
							$tempVar = '';
						$tempContent = $this->cObj->substituteSubpart($tempContent, "###display_variant2###", $tempVar);
						$tempVar = $this->cObj->getSubpart($tempContent,"###display_variant3###");
						if (trim($actItem['rec']['accessory']) == '0')
							$tempVar = '';
						$tempContent = $this->cObj->substituteSubpart($tempContent, "###display_variant3###", $tempVar);
					} else {
						if (trim($actItem['rec']['color']) == '')
							$tempContent=$this->cObj->substituteSubpart($tempContent,"###display_variant1###","");
						if (trim($actItem['rec']['size']) == '')
		  					$tempContent = $this->cObj->substituteSubpart($tempContent,"###display_variant2###","");
						if (trim($actItem['rec']['accessory']) == '0')
		  					$tempContent = $this->cObj->substituteSubpart($tempContent,"###display_variant3###","");
					} */
	
					$itemsOut .= $tempContent;
				}
				if ($itemsOut)	{
					$tempContent=$this->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
					$out .= $tempContent;
					$itemsOut='';			// Clear the item-code var
				}
			}
		}

		$subpartArray = array();
		$wrappedSubpartArray = array();

			// Initializing the markerArray for the rest of the template
		$markerArray=$mainMarkerArray;

			// This is the total for the goods in the basket.
		$markerArray['###PRICE_GOODSTOTAL_TAX###'] = $this->priceFormat($this->calculatedArray['priceTax']['goodstotal']);
		$markerArray['###PRICE_GOODSTOTAL_NO_TAX###'] = $this->priceFormat($this->calculatedArray['priceNoTax']['goodstotal']);

		// This is for the Basketoverview
		$markerArray['###NUMBER_GOODSTOTAL###'] = $this->calculatedArray['count'];
		$markerArray['###IMAGE_BASKET###'] = '<img src="'.$this->conf['basketPic'].'">';
		$wrappedSubpartArray['###LINK_BASKET###']= array('<A href="'.$this->getLinkUrl($this->conf['PIDbasket']).'">','</A>');

		$markerArray['###PRICE_SHIPPING_PERCENT###'] = $perc;
		$markerArray['###PRICE_SHIPPING_TAX###'] = $this->priceFormat($this->calculatedArray['priceTax']['shipping']);
		$markerArray['###PRICE_SHIPPING_NO_TAX###'] = $this->priceFormat($this->calculatedArray['priceNoTax']['shipping']);

		$markerArray['###SHIPPING_SELECTOR###'] = $this->generateRadioSelect('shipping', $countTotal);
		$markerArray['###SHIPPING_IMAGE###'] = $this->cObj->IMAGE($this->basketExtra['shipping.']['image.']);
		$markerArray['###SHIPPING_TITLE###'] = $this->basketExtra['shipping.']['title'];

		$markerArray['###SHIPPING_WEIGHT###'] = doubleval($this->calculatedArray['weight']);


		$markerArray['###DELIVERYCOSTS###']=$this->priceFormat($this->calculatedArray['priceTax']['shipping'] + $this->calculatedArray['priceTax']['payment']);

		$markerArray['###PRICE_PAYMENT_PERCENT###'] = $perc;
		$markerArray['###PRICE_PAYMENT_TAX###'] = $this->priceFormat($this->calculatedArray['priceTax']['payment']);
		$markerArray['###PRICE_PAYMENT_NO_TAX###'] = $this->priceFormat($this->calculatedArray['priceNoTax']['payment']);

		$markerArray['###PAYMENT_SELECTOR###'] = $this->generateRadioSelect('payment', $countTotal);
		$markerArray['###PAYMENT_IMAGE###'] = $this->cObj->IMAGE($this->basketExtra['payment.']['image.']);
		$markerArray['###PAYMENT_TITLE###'] = $this->basketExtra['payment.']['title'];

			// Fill the Currency Symbol or not
		if ($this->conf['showcurSymbol']) {
			$markerArray['###CUR_SYM###'] = ' '.$this->conf['currencySymbol'];
		} else {
			$markerArray['###CUR_SYM###'] = '';
		}

/* Added Els: below 3 lines after creditpoint calculation! */
		// This is the total for everything
//		$this->getCalculateSums();
//		$markerArray['###PRICE_TOTAL_TAX###'] = $this->priceFormat($this->calculatedArray['priceTax']['total']);
//		$markerArray['###PRICE_TOTAL_NO_TAX###'] = $this->priceFormat($this->calculatedArray['priceNoTax']['total']);
/* Added Els: marker PRICE_DISCOUNT += uw voordeel (PRICE_GOODSTOTAL_NO_TAX-oldPriceNoTax) and PRICE_VAT = BTW (= ['priceTax']['goodstotal'] - ['priceNoTax']['goodstotal']*/
		$markerArray['###PRICE_DISCOUNT###'] = $this->priceFormat($this->calculatedArray['oldPriceNoTax']-$this->calculatedArray['priceNoTax']['goodstotal']);
		$markerArray['###PRICE_VAT###'] = $this->priceFormat($this->calculatedArray['priceTax']['goodstotal']-$this->calculatedArray['priceNoTax']['goodstotal']);

			// Personal and delivery info:
/* Add ELS: more fields */
		$list = 'name,address,telephone,fax,email,company,city,zip,state,country';
		if (t3lib_extMgm::isLoaded('feuserextrafields')) {
			$list .= ',tx_feuserextrafields_initials_name,tx_feuserextrafields_prefix_name,tx_feuserextrafields_gsm_tel,name,date_of_birth,tx_feuserextrafields_company_deliv,address,tx_feuserextrafields_address_deliv,tx_feuserextrafields_housenumber,tx_feuserextrafields_housenumber_deliv,tx_feuserextrafields_housenumberadd,tx_feuserextrafields_housenumberadd_deliv,tx_feuserextrafields_pobox,tx_feuserextrafields_pobox_deliv,zip,tx_feuserextrafields_zip_deliv,city,tx_feuserextrafields_city_deliv,tx_feuserextrafields_country,tx_feuserextrafields_country_deliv';
		}
		$infoFields = explode(',',$list); // Fields...
// mkl: 	$infoFields = explode(',','forename,name,address,telephone,fax,email,company,city,zip,state,street,street_n1,street_n2,country_code,vat_id');

		while(list(,$fName)=each($infoFields))	{
			$markerArray['###PERSON_'.strtoupper($fName).'###'] = $this->personInfo[$fName];
			$markerArray['###DELIVERY_'.strtoupper($fName).'###'] = $this->deliveryInfo[$fName];

/* mkl:
			if( $fName == 'country_code' ) 	{
				$markerArray['###PERSON_'.strtoupper($fName).'###'] =
					$this->staticInfo->buildStaticInfoSelector('COUNTRIES', 'recs[personinfo][country_code]', '',$this->personInfo['country_code'], '');
				$markerArray['###PERSON_COUNTRY###'] =
				        $this->staticInfo->getStaticInfoName('COUNTRIES', $this->personInfo['country_code'],'','');
				$markerArray['###DELIVERY_'.strtoupper($fName).'###'] =
					$this->staticInfo->buildStaticInfoSelector('COUNTRIES', 'recs[delivery][country_code]', '',$this->deliveryInfo['country_code'], '');
				$markerArray['###DELIVERY_COUNTRY###'] =
				        $this->staticInfo->getStaticInfoName('COUNTRIES', $this->deliveryInfo['country_code'],'','');
			}
			else   {
				$markerArray['###PERSON_'.strtoupper($fName).'###'] = $this->personInfo[$fName];
				$markerArray['###DELIVERY_'.strtoupper($fName).'###'] = $this->deliveryInfo[$fName];
			}
*/

		}
			// Markers for use if you want to output line-broken address information
		$markerArray['###PERSON_ADDRESS_DISPLAY###'] = nl2br($markerArray['###PERSON_ADDRESS###']);
		$markerArray['###DELIVERY_ADDRESS_DISPLAY###'] = nl2br($markerArray['###DELIVERY_ADDRESS###']);
			// Delivery note.
		$markerArray['###DELIVERY_NOTE###'] = $this->deliveryInfo['note'];
		$markerArray['###DELIVERY_NOTE_DISPLAY###'] = nl2br($markerArray['###DELIVERY_NOTE###']);

			// Order:	NOTE: Data exist only if the getBlankOrderUid() has been called. Therefore this field in the template should be used only when an order has been established
		$markerArray['###ORDER_UID###'] = $this->getOrderNumber($this->recs['tt_products']['orderUid']);
		$markerArray['###ORDER_DATE###'] = $this->cObj->stdWrap($this->recs['tt_products']['orderDate'],$this->conf['orderDate_stdWrap.']);
		$markerArray['###ORDER_TRACKING_NO###'] = $this->recs['tt_products']['orderTrackingNo'];

			// Fe users:
		$markerArray['###FE_USER_USERNAME###'] = $TSFE->fe_user->user['username'];
		$markerArray['###FE_USER_UID###'] = $TSFE->fe_user->user['uid'];

		$markerArray['###PERSON_AGB###'] = $TSFE->fe_user->user['agb'] ? ' checked' : '';

		$markerArray['###USERNAME###'] = $this->personInfo['email'];
		$markerArray['###PASSWORD###'] = $this->password;
		$markerArray['###PID_TRACKING###'] = $this->conf['PIDtracking'];


			// URL
		$markerArray = $this->addURLMarkers($markerArray);

		$agb_url=array();
		$pidagb = intval($this->conf['PIDagb']);
		$agb_url['id'] = 'id='.$pidagb;
		$agb_url['type']= $TSFE->type ? 'type='.$TSFE->type : '';
		$agb_url['backPID']= 'backPID='.$TSFE->id;

/* Added Els: creditpoint inclusive conditions*/
		$creditpoints_integer = explode('.',$TSFE->fe_user->user['tt_products_creditpoints']);
		$markerArray['###AMOUNT_CREDITPOINTS###'] = $creditpoints_integer[0];
		// if quantity is 0 than
		if ($TSFE->fe_user->user['tt_products_creditpoints'] == '0') {
			$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
			$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
			$subpartArray['###SUB_CREDITPOINTS_AMOUNT###'] = '';
		} else {
			$subpartArray['###SUB_CREDITPOINTS_AMOUNT_EMPTY###'] = '';
		}
		$markerArray['###CHANGE_AMOUNT_CREDITPOINTS###'] = 'recs[tt_products][creditpoints]';
		if ($this->recs['tt_products']['creditpoints'] == '') {
			$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = 0;
			$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
		} else {
			// quantity chosen can not be larger than the amount of creditpoints
			if ($this->recs['tt_products']['creditpoints'] > $creditpoints_integer[0]) $this->recs['tt_products']['creditpoints'] = $creditpoints_integer[0];
			$this->calculatedArray['priceTax']['creditpoints'] = $this->priceFormat($this->recs['tt_products']['creditpoints']*0.5);
			$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = $this->recs['tt_products']['creditpoints'];
			$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
		    $markerArray['###CREDIT_DISCOUNT###'] = $this->calculatedArray['priceTax']['creditpoints'];
		}

/* Added Els: voucher marker inclusive conditions */
		if ($TSFE->fe_user->user['tt_products_vouchercode'] == '') {
			$subpartArray['###SUB_VOUCHERCODE###'] = '';
			$markerArray['###INSERT_VOUCHERCODE###'] = 'recs[tt_products][vouchercode]';
			$markerArray['###VALUE_VOUCHERCODE###'] = $this->recs['tt_products']['vouchercode'];
			if ($this->recs['tt_products']['vouchercode'] == '') {
				$subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = '';
				$subpartArray['###SUB_VOUCHERCODE_DISCOUNTOWNID###'] = '';
				$subpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = '';
			} else {
				$subpartArray['###SUB_VOUCHERCODE_NODISCOUNT###'] = '';

				$res = $TYPO3_DB->exec_SELECTquery('uid', 'fe_users', 'username="'.$this->recs['tt_products']['vouchercode'].'"');
				if ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
					$uid_voucher = $row['uid'];
				}
				if ($uid_voucher != '') {
					// first check if not inserted own vouchercode
					if ($TSFE->fe_user->user['uid'] == $uid_voucher) {
						$subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = '';
						$subpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = '';
					} else {
						$subpartArray['###SUB_VOUCHERCODE_DISCOUNTOWNID###'] = '';
						$subpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = '';
						//$this->calculatedArray['priceTax']['voucher'] = $this->priceFormat($this->calculatedArray['priceTax']['shipping']);
						$this->calculatedArray['priceTax']['voucher'] = 5;
						$markerArray['###VOUCHER_DISCOUNT###'] = $this->priceFormat($this->calculatedArray['priceTax']['voucher']);
					}
				} else {
					$subpartArray['###SUB_VOUCHERCODE_DISCOUNTOWNID###'] = '';
					$subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = '';
				}
			}
		} else {
			$subpartArray['###SUB_VOUCHERCODE_EMPTY###'] = '';
		}

/* Added Els: below 3 lines moved from above */
			// This is the total for everything
		$this->getCalculateSums();
		$markerArray['###PRICE_TOTAL_TAX###'] = $this->priceFormat($this->calculatedArray['priceTax']['total']);
		$markerArray['###PRICE_TOTAL_NO_TAX###'] = $this->priceFormat($this->calculatedArray['priceNoTax']['total']);

		$wrappedSubpartArray['###LINK_AGB###']=array('<A href="'.$TSFE->absRefPrefix.'index.php?'.implode($agb_url,'&').'" target="'.$this->conf['agbtarget'].'">','</A>');

			// Final substitution:
		if (!$TSFE->loginUser)	{		// Remove section for FE_USERs only, if there are no fe_user
			$subpartArray['###FE_USER_SECTION###']='';
		}
		$bFrameWork = $t['basketFrameWork'];
		$subpartArray['###MESSAGE_SHIPPING###'] = $this->cObj->substituteMarkerArrayCached($this->cObj->getSubpart($bFrameWork,'###MESSAGE_SHIPPING_'.$this->basketExtra['shipping'].'###'),$markerArray);
		$subpartArray['###MESSAGE_PAYMENT###'] = $this->cObj->substituteMarkerArrayCached($this->cObj->getSubpart($bFrameWork,'###MESSAGE_PAYMENT_'.$this->basketExtra['payment'].'###'),$markerArray);

		$bFrameWork=$this->cObj->substituteMarkerArrayCached($t['basketFrameWork'],$markerArray,$subpartArray,$wrappedSubpartArray);

			// substitute the main subpart with the rendered content.
		$out=$this->cObj->substituteSubpart($bFrameWork, '###ITEM_CATEGORY_AND_ITEMS###', $out);
		return $out;
	}




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
					1: 	Order confirmed at website
					...
					50-59:	User messages, may be updated by the ordinary users.
					100-:	Order finalized.


					All status values can be altered only if you're logged in as a BE-user and if you know the correct code (setup as .update_code in TypoScript config)
			*/
		global $TSFE;

		$admin = $this->shopAdmin();

		if ($orderRow['uid'])	{
				// Initialize update of status...
			$fieldsArray = array();
			$orderRecord = t3lib_div::_GP('orderRecord');
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
					if ($admin || ($val>=50 && $val<59))	{// Numbers 50-59 are usermessages.
						$status_log_element = array(
							'time' => time(),
							'info' => $this->conf['statusCodes.'][$val],
							'status' => $val,
							'comment' => $orderRecord['status_comment']
						);
						$recipient = $this->conf['orderEmail_to'];
						if ($orderRow['email'] && $orderRow['email_notify'])	{
							$recipient .= ','.$orderRow['email'];
						}
						$this->sendNotifyEmail($recipient, $status_log_element, t3lib_div::_GP('tracking'), $this->getOrderNumber($orderRow['uid']),$templateCode);
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
	}

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

		if ($type == 'bill')
		{		// TODO: +++
			$content = '<A href="' . $reldateiname . '" >zum &Ouml;ffnen der Rechnung hier klicken</A>';
		}
		else
		{
			$content = '<A href="' . $reldateiname . '" >zum &Ouml;ffnen des Lieferscheins hier klicken</A>';
		}

		return $content;
	}


	/**
	 * Send notification email for tracking
	 */
	function sendNotifyEmail($recipient, $v, $tracking, $uid, $templateCode)	{
		global $TSFE;

			// Notification email
		$headers=array();
		if ($this->conf['orderEmail_from'])	{$headers[]='FROM: '.$this->conf['orderEmail_fromName'].' <'.$this->conf['orderEmail_from'].'>';}

		$recipients = $recipient;
		$recipients=t3lib_div::trimExplode(',',$recipients,1);

		if (count($recipients))	{	// If any recipients, then compile and send the mail.
			$emailContent=trim($this->cObj->getSubpart($templateCode,'###TRACKING_EMAILNOTIFY_TEMPLATE###'));
			if ($emailContent)	{		// If there is plain text content - which is required!!

				$markerArray['###ORDER_STATUS_TIME###']=$this->cObj->stdWrap($v['time'],$this->conf['statusDate_stdWrap.']);
				$markerArray['###ORDER_STATUS###']=$v['status'];
				$markerArray['###ORDER_STATUS_INFO###']=$v['info'];
				$markerArray['###ORDER_STATUS_COMMENT###']=$v['comment'];
				$markerArray['###PID_TRACKING###'] = $this->conf['PIDtracking'];
				

				$markerArray['###ORDER_TRACKING_NO###']=$tracking;
				$markerArray['###ORDER_UID###']=$uid;

				$emailContent=$this->cObj->substituteMarkerArrayCached($emailContent, $markerArray);

				$parts = split(chr(10),$emailContent,2);
				$subject=trim($parts[0]);
				$plain_message=trim($parts[1]);

//				$TSFE->plainMailEncoded(implode($recipients,','), $subject, $plain_message, implode($headers,chr(10)));
				$this->send_mail(implode($recipients,','), $subject, $plain_message, $this->conf['orderEmail_from'], $this->conf['orderEmail_fromName']);
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
           return $this->cObj->getSubpart($this->templateCode,$this->spMarker('###MEMO_NOT_LOGGED_IN###'));

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
           if ($row['amount'] <= 100) {
               $creditpoints = 0.02;
           } elseif ($row['amount'] <= 500) {
               $creditpoints = 0.04;
           } else {
               $creditpoints = 0.06;
           }
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
