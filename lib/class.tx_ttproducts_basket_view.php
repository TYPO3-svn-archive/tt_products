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
 * basket functions for a basket object
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Els Verberne <verberne@bendoo.nl>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_article_div.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_creditpoints_div.php');



class tx_ttproducts_basket_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	
	var $order; // order object
	var $page;
	var $tt_content; // element of class tx_table_db
	var $tt_products; // element of class tx_table_db
	var $tt_products_cat; // element of class tx_table_db

	var $price; // price object

	var $basket; 	// the basket object

	var $templateCode='';				// In init(), set to the content of the templateFile. Used by default in getView()

	var $activityArray;					// activities for the CODEs
	var $marker; // marker functions

	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
 	 */

	function init(&$pibase, &$conf, &$config, &$basket, &$page, &$order, &$tt_content, &$tt_products, &$tt_products_cat, &$price, &$templateCode )	{
 		$this->pibase = &$pibase;
 		$this->conf = &$conf;
 		$this->config = &$config;
 		$this->basket = &$basket;
 		$this->page = &$page;
		$this->order = &$order;
 		$this->tt_content = &$tt_content;
 		$this->tt_products = &$tt_products;
 		$this->tt_products_cat = &$tt_products_cat;
 		$this->price = &$price;
 		$this->templateCode = &$templateCode;
 
		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($pibase, $conf, $config, $basket);
	} // init




	/**
	 * returns the activities in the order in which they have to be processed
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
 	 */
	function transfromActivities($activities)	{
		$retActivities = array();
		$activityArray =  Array (
			'1' =>  'products_overview', 'products_basket', 'products_info', 'products_payment', 'products_customized_payment', 'products_finalize',
			);

		if (is_array($activities)) {
			foreach ($activityArray as $k => $activity) {
				if ($activities[$activity]) {
					$retActivities[$activity] = true;
				}
			}
		}

		if ($retActivities['products_info']) {
			if($retActivities['products_payment']) {
				$retActivities['products_payment'] = false;
			}
			if($retActivities['products_finalize']) {
				$retActivities['products_finalize'] = false;
			}
		}
		if ($retActivities['products_payment']) {
			if($retActivities['products_finalize']) {
				$retActivities['products_finalize'] = false;
			}
		}
		if ($retActivities['products_basket'] && count($retActivities)>1) {
			$retActivities['products_basket'] = false;
		}

		return ($retActivities);
	}


	/**
	 * Takes care of basket, address info, confirmation and gate to payment
	 * Also the 'products_...' script parameters are used here.
	 *
	 * @param		array		  CODEs for display mode
	 * @return	  void
 	 */
	function &printView(&$codes, &$error_message) {
		global $TSFE;
		global $TYPO3_DB;

		$content = '';
		$empty = '';

		$activityArray=array();

		if (t3lib_div::_GP('products_redeem_gift'))	{
		 	$activityArray['products_redeem_gift']=true;
		}

		if (t3lib_div::_GP('products_overview'))	{
			$activityArray['products_overview']=true;
		}
		if (t3lib_div::_GP('products_info'))	{
			$activityArray['products_info']=true;
		}
		if (t3lib_div::_GP('products_payment'))	{
			$activityArray['products_payment']=true;
		}
		if (t3lib_div::_GP('products_customized_payment'))	{
			$activityArray['products_customized_payment']=true;
		}
		if (t3lib_div::_GP('products_finalize'))	{
			$activityArray['products_finalize']=true;
		}

		if (is_array($codes)) {
			foreach ($codes as $k => $code) {
				if ($code=='BASKET')	{
					$activityArray['products_basket']=true;
				} elseif ($code=='INFO' && !($activityArray['products_payment'] || $activityArray['products_finalize']))	{
					$activityArray['products_info']=true;
				} elseif ($code=='OVERVIEW') {
					$activityArray['products_overview']=true;
				} elseif ($code=='PAYMENT' && !($activityArray['products_finalize']))	{
					$activityArray['products_payment']=true;
				} elseif ($code=='FINALIZE')	{
					$activityArray['products_finalize']=true;
				}
			}
		}

		$this->activityArray = $this->transfromActivities($activityArray);

		if (count($this->basket->basketExt) && count($this->activityArray))	{	// If there is content in the shopping basket, we are going display some basket code
				// prepare action
			$basket_tmpl = '';
			if (count($this->activityArray)) {
				if (!$this->page->pid_list) {
					$this->page->setPidlist($this->config['storeRootPid']);	// Set list of page id's to the storeRootPid.
				}
				$this->page->initRecursive(999);		// This add's all subpart ids to the pid_list based on the rootPid set in previous line
				// $this->pibase->page->generatePageArray();		// Creates an array with page titles from the internal pid_list. Used for the display of category titles.
				$this->basket->getCalculatedBasket();  // all the basket calculation is done in this function once and not multiple times here

				$mainMarkerArray=array();
				$mainMarkerArray['###EXTERNAL_COBJECT###'] = $this->pibase->externalCObject.'';  // adding extra preprocessing CObject
				
				foreach ($this->activityArray as $activity=>$value) {
					if ($value) {
							// perform action
						switch($activity)	{
							case 'products_basket':
								if (count($this->activityArray) == 1) {
									$content.=$this->getView($empty);
								}
							break;
							case 'products_overview':
								$this->pibase->load_noLinkExtCobj();	// TODO
								$basket_tmpl  = 'BASKET_OVERVIEW_TEMPLATE';
							break;
							case 'products_redeem_gift': 	// this shall never be the only activity
								if (trim($TSFE->fe_user->user['username']) == '') {
									$basket_tmpl = 'BASKET_TEMPLATE_NOT_LOGGED_IN';
								} else {
									$uniqueId = t3lib_div::trimExplode ('-', $this->basket->recs['tt_products']['gift_certificate_unique_number'], true);

									$query='uid=\''.intval($uniqueId[0]).'\' AND crdate=\''.$uniqueId[1].'\''.' AND NOT deleted' ;
									$giftRes = $TYPO3_DB->exec_SELECTquery('*', 'tt_products_gifts', $query);

									$row = $TYPO3_DB->sql_fetch_assoc($giftRes);

									$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);
									if ($row && $pricefactor > 0) {
										$money = $row['amount'];
										$uid = $row['uid'];
										$fieldsArray = array();
										$fieldsArray['deleted']=1;
											// Delete the gift record
										$TYPO3_DB->exec_UPDATEquery('tt_products_gifts', 'uid='.intval($uid), $fieldsArray);

										$creditpoints = $money / $pricefactor;

										tx_ttproducts_creditpoints_div::addCreditPoints($TSFE->fe_user->user['username'], $creditpoints);

	/* Added els5: extra markers for inline comments */
										// Fill marker arrays
										$markerArray=Array();
										$subpartArray=Array();
										$markerArray['###GIFT_DISCOUNT###'] = $creditpoints;
										$markerArray['###VALUE_GIFTCODE###'] = $this->basket->recs['tt_products']['gift_certificate_unique_number'];
										$subpartArray['###SUB_GIFTCODE_DISCOUNTWRONG###']= '';
										$content = $this->pibase->cObj->substituteMarkerArrayCached($content,$markerArray,$subpartArray);

									} else {
	/* Added els5: inline comments and errors in stead of new page */
										//$basket_tmpl = 'BASKET_TEMPLATE_INVALID_GIFT_UNIQUE_ID';

										// Fill marker arrays
										$markerArray=Array();
										$subpartArray=Array();
										$markerArray['###VALUE_GIFTCODE###'] = $this->basket->recs['tt_products']['gift_certificate_unique_number'];
										$subpartArray['###SUB_GIFTCODE_DISCOUNT###']= '';
										$content = $this->pibase->cObj->substituteMarkerArrayCached($content,$markerArray,$subpartArray);
									}
								}
							break;
							case 'products_info':
								// if (!$this->activityArray['products_payment'] && !$this->activityArray['products_finalize']) {
								$this->pibase->load_noLinkExtCobj(); // TODO
								$basket_tmpl  = 'BASKET_INFO_TEMPLATE';
								// }
							break;
							case 'products_payment':
								$this->pibase->load_noLinkExtCobj();	// TODO
								$pidagb = intval($this->conf['PIDagb']);
								$this->basket->mapPersonIntoToDelivery();
								$check = $this->basket->checkRequired();
								if ($check=='' &&
									(empty($pidagb) || isset($_REQUEST['recs']['personinfo']['agb']))) {
									$content.=$this->getView($tmp='', '###BASKET_PAYMENT_TEMPLATE###', $mainMarkerArray);

									$handleScript = $TSFE->tmpl->getFileName($this->basket->basketExtra['payment.']['handleScript']);
									$orderUid = $this->order->getBlankOrderUid();
									if (trim($this->conf['paymentActivity'])=='payment' && $handleScript)	{
										$this->basket->getCalculatedSums();
										$content.= $this->basket->pricecalc->includeHandleScript($handleScript,  $this->basket->basketExtra['payment.']['handleScript.'], $this);
									}

								} else {	// If not all required info-fields are filled in, this is shown instead:
									$content.=$this->pibase->cObj->getSubpart($this->templateCode,$this->marker->spMarker('###BASKET_REQUIRED_INFO_MISSING###'));
									$markerArray = $this->marker->addURLMarkers(0, array());
									$label = '';
									if ($check=='') {
										 // so AGB has not been accepted
										$label = $this->pibase->pi_getLL('accept_AGB');
									} else {
										if (t3lib_extMgm::isLoaded('sr_feuser_register')) {
											$label = $TSFE->sL('LLL:EXT:sr_feuser_register/pi1/locallang.php:missing_'.$check);
											$editPID = $TSFE->tmpl->setup['plugin.']['tx_srfeuserregister_pi1.']['editPID'];
											if ($TSFE->loginUser && $editPID) {
												$addParams = array ('products_payment' => 1);
												$addParams = $this->marker->getLinkParams('',$addParams);
												$srfeuserBackUrl = $this->pibase->pi_getPageLink($TSFE->id,'',$addParams);
												$srfeuserParams = array('tx_srfeuserregister_pi1[backURL]' => $srfeuserBackUrl);
												$addParams = $this->marker->getLinkParams('',$srfeuserParams);
												$markerArray['###FORM_URL_INFO###'] = $this->pibase->pi_getPageLink($editPID,'',$addParams);
											}
										} else {
											$label = 'field: '.$check;
										}
									}
									$markerArray['###ERROR_DETAILS###'] = $label;
									$content = $this->pibase->cObj->substituteMarkerArray($content, $markerArray);
								}
							break;
							case 'products_customized_payment':
								$this->basket->mapPersonIntoToDelivery();
								$this->pibase->load_noLinkExtCobj();	// TODO
								$handleScript = $TSFE->tmpl->getFileName($this->basket->basketExtra['payment.']['handleScript']);
								$orderUid = $this->order->getBlankOrderUid();
								if (trim($this->conf['paymentActivity'])=='customized' && $handleScript)	{
									$this->getCalculatedSums();
									$content.= $this->basket->pricecalc->includeHandleScript($handleScript,  $this->basket->basketExtra['payment.']['handleScript.'], $this);
								}
							break; 
							case 'products_finalize':
								$this->basket->mapPersonIntoToDelivery();
								$check = $this->basket->checkRequired();
								if ($check=='')	{
									$this->pibase->load_noLinkExtCobj();	// TODO
									$handleScript = $TSFE->tmpl->getFileName($this->basket->basketExtra['payment.']['handleScript']);
									$orderUid = $this->order->getBlankOrderUid();
									if (trim($this->conf['paymentActivity'])=='finalize' && $handleScript)	{
										//$this->getCalculatedBasket();
										$this->basket->getCalculatedSums();
										$content.= $this->basket->pricecalc->includeHandleScript($handleScript, $this->basket->basketExtra['payment.']['handleScript.']);
									}

									// Added Els4: to get the orderconfirmation template as html email and the thanks template as thanks page
									$tmpl = 'BASKET_ORDERCONFIRMATION_TEMPLATE';
									$orderConfirmationHTML=$this->getView($empty, '###'.$tmpl.'###', $mainMarkerArray);
									$contentTmp = $orderConfirmationHTML;
									include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_finalize_div.php');
									
									tx_ttproducts_finalize_div::finalizeOrder($this->pibase, $this->conf, $this->templateCode, 
										$this->basket, $this, $this->tt_products, $this->tt_products_cat, $this->price, 
										$this->order, $orderUid, $orderConfirmationHTML, $error_message); // Important: 	 MUST come after the call of prodObj->getView, because this function, getView, calculates the order! And that information is used in the finalize-function

									if ($this->conf['PIDthanks'] > 0) {
										$tmpl = 'BASKET_ORDERTHANKS_TEMPLATE';
										$contentTmp = $this->getView($empty, '###'.$tmpl.'###', $mainMarkerArray);
									}
									$content.=$contentTmp;
									$content .= $this->getView($empty,'###BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE###');
									
									// Empties the shopping basket!
									$this->basket->clearBasket();
								} else {	// If not all required info-fields are filled in, this is shown instead:
									$content.=$this->pibase->cObj->getSubpart($this->templateCode,$this->marker->spMarker('###BASKET_REQUIRED_INFO_MISSING###'));
									$content = $this->pibase->cObj->substituteMarkerArray($content, $this->marker->addURLMarkers(0, array()));
								}
							break;
							default:
								// nothing yet
							break;
						} // switch
					}	// if ($value)
					if ($basket_tmpl) {
						$content.=$this->getView($empty, '###'.$basket_tmpl.'###',$mainMarkerArray);
						break; // foreach
					}
				} // foreach ($this->activityArray as $activity=>$value)
			} else { // if (count($this->activityArray))
				// nothing. no BASKET code or similar thing
			}
		} else { // if (count($this->basket->basketExt))
			if ($this->activityArray['products_overview']) {
				$this->pibase->load_noLinkExtCobj();	//
				$content.=$this->pibase->cObj->getSubpart($this->templateCode,$this->marker->spMarker('###BASKET_OVERVIEW_EMPTY###'));
			}
			else if ($this->activityArray['products_basket']) {
				$content.=$this->pibase->cObj->getSubpart($this->templateCode,$this->marker->spMarker('###BASKET_TEMPLATE_EMPTY###'));
			}
		}
		$markerArray=array();
		$markerArray['###EXTERNAL_COBJECT###'] = $this->pibase->externalCObject;	// adding extra preprocessing CObject
		$content= $this->pibase->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	} // products_basket



	/**
	 * This generates the shopping basket layout and also calculates the totals. Very important function.
	 */
	function getView(&$templateCode, $subpartMarker='###BASKET_TEMPLATE###', $mainMarkerArray=array())	{
			/*
				Very central function in the library.
				By default it extracts the subpart, ###BASKET_TEMPLATE###, from the $templateCode (if given, else the default $this->templateCode)
				and substitutes a lot of fields and subparts.
				Any pre-preparred fields can be set in $mainMarkerArray, which is substituted in the subpart before the item-and-categories part is substituted.
			*/

		global $TSFE, $TCA;
/* ADDED Els: need for vouchercode conditions */
		global $TYPO3_DB;

		if (!$templateCode)	{
			$templateCode = &$this->templateCode;		
		}

//		$this->basket->getCalculatedBasket();  // all the basket calculation is done in this function once and not multiple times here

			// Getting subparts from the template code.
		$t=array();
		$t['basketFrameWork'] = $this->pibase->cObj->getSubpart($templateCode,$this->marker->spMarker($subpartMarker));

			// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
		if (trim($this->pibase->cObj->getSubpart($t['basketFrameWork'],'###BILLING_ADDRESS_LOGIN###')))	{
			//if ($GLOBALS['TSFE']->loginUser)	{
			if ($TSFE->loginUser && $this->conf['lockLoginUserInfo']) {
				$t['basketFrameWork'] = $this->pibase->cObj->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS###', '');
			} else {
				$t['basketFrameWork'] = $this->pibase->cObj->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS_LOGIN###', '');
			}
		}

		$t['categoryTitle'] = $this->pibase->cObj->getSubpart($t['basketFrameWork'],'###ITEM_CATEGORY###');
		$t['itemFrameWork'] = $this->pibase->cObj->getSubpart($t['basketFrameWork'],'###ITEM_LIST###');
		$t['item'] = $this->pibase->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');

		$currentP='';
		$out='';
		$itemsOut='';

		// loop over all items in the basket indexed by page and itemnumber
		foreach ($this->basket->itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					$pidcategory = ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'] == 1 ? $pid : '');
					$currentPnew = $pidcategory.'_'.$actItem['rec']['category'];
						// Print Category Title
					if ($currentPnew!=$currentP)	{
						if ($itemsOut)	{
							$out.=$this->pibase->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
						}
						$itemsOut='';			// Clear the item-code var
						$currentP = $currentPnew;
						if ($this->conf['displayBasketCatHeader'])	{
							$markerArray=array();
							$pageCatTitle = '';
							if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'] == 1) {
								$pageTmp = $this->page->get($pid);
								$pageCatTitle = $pageTmp['title'].'/';
							}
							$catTmp = '';
							if ($actItem['rec']['category']) {
								$catTmp = $this->tt_products_cat->get($actItem['rec']['category']);
								$catTmp = $catTmp['title'];
							}
							$catTitle = $pageCatTitle.$catTmp;

							$this->pibase->cObj->setCurrentVal($catTitle);
							$markerArray['###CATEGORY_TITLE###']=$this->pibase->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
							$out.= $this->pibase->cObj->substituteMarkerArray($t['categoryTitle'], $markerArray);
						}
					}

						// Fill marker arrays
					$wrappedSubpartArray=array();
					$subpartArray=array();
					$markerArray = $this->marker->getItemMarkerArray ($actItem,$catTitle, $this->tt_products, $this->tt_content,1,'basketImage');

					$markerArray['###PRODUCT_COLOR###'] = $actItem['rec']['color'];
					$markerArray['###PRODUCT_SIZE###'] = $actItem['rec']['size'];
					$markerArray['###PRODUCT_DESCRIPTION###'] = $actItem['rec']['description'];
					$markerArray['###PRODUCT_GRADINGS###'] = $actItem['rec']['gradings'];
					//$markerArray['###PRODUCT_ADDITIONAL###'] = $actItem['rec']['additional'];

					$catTitle= $actItem['rec']['category'] ? $this->tt_products_cat->get($actItem['rec']['category']) : '';
					$this->pibase->cObj->setCurrentVal($catTitle);
					$markerArray['###CATEGORY_TITLE###']=$this->pibase->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');

					$markerArray['###PRICE_TOTAL_TAX###']=$this->price->priceFormat($actItem['totalTax']);
					$markerArray['###PRICE_TOTAL_NO_TAX###']=$this->price->priceFormat($actItem['totalNoTax']);
					$markerArray['###PRICE_TOTAL_ONLY_TAX###']=$this->price->priceFormat($actItem['totalTax']-$actItem['totalNoTax']);

/* Added els4: calculating of price_discount necessary in winkelwagen.tmpl (articles in kurkenshop are excluded, because these articled will be payed with creditpoints) */
					if ( ($actItem['rec']['price'] != '0.00') && doubleval($actItem['rec']['price2']) && ($actItem['rec']['category'] != $this->conf['creditsCategory']) ) {
						$pricediscount_total_tot_units = "";

/* Added els7: different calculation of pricediscount_total_tot_units */
//						$oldprice_total_tot_units = ($actItem['totalNoTax']/$actItem['rec']['price2'])*$actItem['rec']['price'];
//						$pricediscount_total_tot_units = ($oldprice_total_tot_units - $actItem['totalNoTax']) * $actItem['rec']['unit_factor'];
						$pricediscount_total_tot_units = ($actItem['rec']['price'] - $actItem['rec']['price2']) * $actItem['rec']['unit_factor'] * $actItem['count'];
						$sum_pricediscount_total_totunits += $pricediscount_total_tot_units;
					}

/* Added els4: TOTUNITS_: both prices mulitplied by unit_factor and third line is calculating the sum, necessary in winkelwagen.tmpl. All articles in kurkenshop are payed with creditpoints*/
					$markerArray['###PRICE_TOTAL_TOTUNITS_TAX###']=$this->price->priceFormat($actItem['totalTax']*$actItem['rec']['unit_factor']);
					if ($actItem['rec']['category'] == $this->conf['creditsCategory']) {
/* Added els7: different calculation of PRICECREDITS_TOTAL_TOTUNITS_NO_TAX */
//						$markerArray['###PRICECREDITS_TOTAL_TOTUNITS_NO_TAX###']=$this->price->priceFormat($actItem['totalNoTax']*$actItem['rec']['unit_factor']);
						$markerArray['###PRICECREDITS_TOTAL_TOTUNITS_NO_TAX###']=$this->price->priceFormat($actItem['rec']['price2']*$actItem['rec']['unit_factor']) * $actItem['count'];
					} else {
/* Added els7: different calculation of PRICECREDITS_TOTAL_TOTUNITS_NO_TAX */
//						$markerArray['###PRICE_TOTAL_TOTUNITS_NO_TAX###']=$actItem['totalNoTax']*$actItem['rec']['unit_factor'];
						$markerArray['###PRICE_TOTAL_TOTUNITS_NO_TAX###']=$actItem['rec']['price2']*$actItem['rec']['unit_factor'] * $actItem['count'];
					}

					$sum_pricecredits_total_totunits_no_tax += $markerArray['###PRICECREDITS_TOTAL_TOTUNITS_NO_TAX###'];
					$sum_price_total_totunits_no_tax += $markerArray['###PRICE_TOTAL_TOTUNITS_NO_TAX###'];

/* Added els4: calculating of price_creditpoints necessary in winkelwagen.tmpl, only if article contains special_prep then one can gains creditpoints */
					if ($actItem['rec']['special_preparation'] != '0.00') {
						$sum_pricecreditpoints_total_totunits += $markerArray['###PRICE_TOTAL_TOTUNITS_NO_TAX###'];
					}

/* Added els4: if catTitle = shop-a-box then handling costs exists (winkelwagen.tmpl)*/
					if ($shopabox != "1") {
						if ($actItem['rec']['category'] == $this->conf['shopaboxCategory']) {
							$show_handling_cost = 1;
							$shopabox = 1;
						}
					}

					$pid = $this->page->getPID($this->conf['PIDitemDisplay'], $this->conf['PIDitemDisplay.'], $actItem['rec']);

					$splitMark = md5(microtime());
					$addQueryString=array();
					$addQueryString['tt_products'] = intval($actItem['rec']['uid']);
					$addQueryString['ttp_extvars'] = htmlspecialchars($actItem['rec']['extVars']);
					$wrappedSubpartArray['###LINK_ITEM###'] =  array('<a href="'. $this->pibase->pi_getPageLink($pid,'',$this->marker->getLinkParams('', $addQueryString)).'"'.$css_current.'>','</a>'); 

/* Added els6: also for creditscategory: different lay-out */
/* Added Els5: different display for quantities and recycle output in shop-a-box and gifts (in payment_template, winkelwagen.tmpl) */
					if ($actItem['rec']['category'] == $this->conf['shopaboxCategory']) {
						$subpartArray['###QUANTITY_CAT###'] = "";
						$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="index.php?id=shopabox">','</a>');
						$subpartArray['###RECYCLE_CAT###'] = "";
					} else if ($actItem['rec']['category'] == $this->conf['creditsCategory']) {
						$subpartArray['###QUANTITY_CAT###'] = "";
						$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="index.php?id=creditscategory">','</a>');
					} else {
						$subpartArray['###RECYCLE_CAT_SHOPABOX###'] = "";
						//  in case of gift certificates
						if ($actItem['rec']['uid'] == $this->conf['giftID']) {
							$subpartArray['###QUANTITY_CAT###'] = "";
/* Added els6: Exceptions for gifts no discount possible */
							$markerArray['###OLD_PRICE_NO_TAX_CLASS###'] ='rightalign';
							$markerArray['###PRICE_NO_TAX_CUR_SYM###'] = '';
						} else {
							$subpartArray['###QUANTITY_SHOPABOX_GIFT###'] = "";
						}
					}

					// Substitute
					$tempContent = $this->pibase->cObj->substituteMarkerArrayCached($t['item'],$markerArray,$subpartArray,$wrappedSubpartArray);

					tx_ttproducts_article_div::getVariantSubpartArray ($this->pibase, $this->tt_products, $subpartArray, $actItem['rec'], $tempContent, 
						($subpartMarker == '###EMAIL_PLAINTEXT_TEMPLATE###'), $this->conf );
						
					$tempContent = $this->pibase->cObj->substituteMarkerArrayCached($tempContent,$markerArray,$subpartArray,$wrappedSubpartArray);

					$itemsOut .= $tempContent;
				}
				if ($itemsOut)	{
					$tempContent=$this->pibase->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
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
		$markerArray['###PRICE_GOODSTOTAL_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['goodstotal']);
		$markerArray['###PRICE_GOODSTOTAL_NO_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceNoTax']['goodstotal']);
		$markerArray['###PRICE_GOODSTOTAL_ONLY_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['goodstotal']-$this->basket->calculatedArray['priceNoTax']['goodstotal']);

		$markerArray['###PRICE2_GOODSTOTAL_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['price2Tax']['goodstotal']);
		$markerArray['###PRICE2_GOODSTOTAL_NO_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['price2NoTax']['goodstotal']);
		$markerArray['###PRICE2_GOODSTOTAL_ONLY_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['price2Tax']['goodstotal']-$this->basket->calculatedArray['price2NoTax']['goodstotal']);

		// This is for the Basketoverview
		$markerArray['###NUMBER_GOODSTOTAL###'] = $this->basket->calculatedArray['count'];
		$markerArray['###IMAGE_BASKET###'] = '<img src="'.$this->conf['basketPic'].'">';

		$splitMark = md5(microtime());
		$pid = ( $this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);
		$tempUrl = $this->pibase->pi_linkToPage($splitMark,$pid,'',$this->marker->getLinkParams());
		$wrappedSubpartArray['###LINK_BASKET###'] = explode ($splitMark, $tempUrl);
		//$wrappedSubpartArray['###LINK_BASKET###']= array('<a href="'.$this->getLinkUrl($this->conf['PIDbasket']).'">','</a>');

		//$markerArray['###PRICE_SHIPPING_PERCENT###'] = $perc;
		$markerArray['###PRICE_SHIPPING_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['shipping']);
		$markerArray['###PRICE_SHIPPING_NO_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceNoTax']['shipping']);
		$markerArray['###PRICE_SHIPPING_ONLY_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['shipping']-$this->basket->calculatedArray['priceNoTax']['shipping']);

		$markerArray['###SHIPPING_SELECTOR###'] = $this->basket->paymentshipping->generateRadioSelect('shipping', $this->basket->calculatedArray);
		$markerArray['###SHIPPING_IMAGE###'] = $this->pibase->cObj->IMAGE($this->basket->basketExtra['shipping.']['image.']);
		$markerArray['###SHIPPING_TITLE###'] = $this->basket->basketExtra['shipping.']['title'];

		$markerArray['###SHIPPING_WEIGHT###'] = doubleval($this->basket->calculatedArray['weight']);


		$markerArray['###DELIVERYCOSTS###']=$this->price->priceFormat($this->basket->calculatedArray['priceTax']['shipping'] + $this->basket->calculatedArray['priceTax']['payment']);

		//$markerArray['###PRICE_PAYMENT_PERCENT###'] = $perc;
		$markerArray['###PRICE_PAYMENT_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['payment']);
		$markerArray['###PRICE_PAYMENT_NO_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceNoTax']['payment']);
		$markerArray['###PRICE_PAYMENT_ONLY_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['payment']-$this->basket->calculatedArray['priceNoTax']['payment'] );

/* Added els4: payment layout (used in basket_payment_template, winkelwagen.tmpl) */
		$markerArray['###PAYMENT_SELECTOR###'] = $this->basket->paymentshipping->generateRadioSelect('payment', $countTotal);
		$markerArray['###PAYMENT_IMAGE###'] = $this->pibase->cObj->IMAGE($this->basket->basketExtra['payment.']['image.']);
		$markerArray['###PAYMENT_TITLE###'] = $this->basket->basketExtra['payment.']['title'];
		if ($markerArray['###PAYMENT_TITLE###'] == "Vooruitbetaling") { // TODO start
 			$subpartArray['###SUB_PAYMENT_MACHT###'] = '';
 		} elseif ($markerArray['###PAYMENT_TITLE###'] == "Eénmalige machtiging") {
 			$subpartArray['###SUB_PAYMENT_VOORUIT###'] = '';
 		} elseif ($markerArray['###PAYMENT_TITLE###'] == "Factuur") {
 			$subpartArray['###SUB_PAYMENT_MACHT###'] = '';
 			$subpartArray['###SUB_PAYMENT_VOORUIT###'] = ''; // TODO end
 		}

/* Added els4: output for payment (used in basket_payment_template, winkelwagen.tmpl)*/
		$markerArray['###PAYMENT_NUMBER###'] = t3lib_div::_GP('payment_number');
		$markerArray['###PAYMENT_NAME###'] = t3lib_div::_GP('payment_name');
		$markerArray['###PAYMENT_CITY###'] = t3lib_div::_GP('payment_city');

		// for receipt from DIBS script
		$markerArray['###TRANSACT_CODE###'] = t3lib_div::_GP('transact');

			// Fill the Currency Symbol or not
		if ($this->conf['showcurSymbol']) {
			$markerArray['###CUR_SYM###'] = ' '.$this->conf['currencySymbol'];
		} else {
			$markerArray['###CUR_SYM###'] = '';
		}

/* Added Els: below 3 lines after creditpoint calculation! */
		// This is the total for everything
//		$this->getCalculatedSums();
//		$markerArray['###PRICE_TOTAL_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['total']);
//		$markerArray['###PRICE_TOTAL_NO_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceNoTax']['total']);
/* Added Els: marker PRICE_DISCOUNT += uw voordeel (PRICE_GOODSTOTAL_NO_TAX-oldPriceNoTax) and PRICE_VAT = BTW (= ['priceTax']['goodstotal'] - ['priceNoTax']['goodstotal']*/
		$markerArray['###PRICE_DISCOUNT###'] = $this->price->priceFormat($this->basket->calculatedArray['oldPriceNoTax']-$this->basket->calculatedArray['priceNoTax']['goodstotal']);
		$markerArray['###PRICE_VAT###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['goodstotal']-$this->basket->calculatedArray['priceNoTax']['goodstotal']);

/* Added els4: discount based on total units (without articles in kurkenshop), necessary in winkelwagen.tmpl */
		$markerArray['###PRICE_TOTUNITS_DISCOUNT###'] = $this->price->priceFormat($sum_pricediscount_total_totunits);

/* Added Els4: if show_handling_cost (calculated above) then handling costs exists (winkelwagen.tmpl) */
		if ($show_handling_cost == "1") {
			$markerArray['###CAT3_HANDLING_COSTS###'] = $this->conf['shopaboxPrice'];
		} else {
			$subpartArray['###SUB_CAT3_HANDLING_COSTS###'] = '';
/* Added Els8: put cat3_handling_costs 0 for plain text email */
			$markerArray['###CAT3_HANDLING_COSTS###'] = '0.00';
		}

			// Personal and delivery info:
/* Add ELS: more fields */
		$list = 'name,first_name,last_name,address,telephone,fax,email,company,city,zip,state,country';
		if ($this->basket->feuserextrafields) {
			$list .= ',tx_feuserextrafields_initials_name,tx_feuserextrafields_prefix_name,tx_feuserextrafields_gsm_tel,name,date_of_birth,tx_feuserextrafields_company_deliv,address,tx_feuserextrafields_address_deliv,tx_feuserextrafields_housenumber,tx_feuserextrafields_housenumber_deliv,tx_feuserextrafields_housenumberadd,tx_feuserextrafields_housenumberadd_deliv,tx_feuserextrafields_pobox,tx_feuserextrafields_pobox_deliv,zip,tx_feuserextrafields_zip_deliv,tx_feuserextrafields_city_deliv,tx_feuserextrafields_country,tx_feuserextrafields_country_deliv';
		}
		$infoFields = explode(',',$list); // Fields...
// mkl: 	$infoFields = explode(',','forename,name,address,telephone,fax,email,company,city,zip,state,street,street_n1,street_n2,country_code,vat_id');

		while(list(,$fName)=each($infoFields))	{
			$markerArray['###PERSON_'.strtoupper($fName).'###'] = $this->basket->personInfo[$fName];
			$markerArray['###DELIVERY_'.strtoupper($fName).'###'] = $this->basket->deliveryInfo[$fName];

		}
				
		if ($this->conf['useStaticInfoCountry'] && is_object($this->pibase->staticInfo))	{
			$markerArray['###PERSON_COUNTRY_CODE###'] =
				$this->pibase->staticInfo->buildStaticInfoSelector('COUNTRIES', 'recs[personinfo][country_code]', '', $this->basket->personInfo['country_code'], '');
			$markerArray['###PERSON_COUNTRY###'] =
				$this->pibase->staticInfo->getStaticInfoName('COUNTRIES', $this->basket->personInfo['country_code'],'','');
			$markerArray['###DELIVERY_COUNTRY_CODE###'] =
				$this->pibase->staticInfo->buildStaticInfoSelector('COUNTRIES', 'recs[delivery][country_code]', '', $this->basket->deliveryInfo['country_code'], '');
			$markerArray['###DELIVERY_COUNTRY###'] =
				$this->pibase->staticInfo->getStaticInfoName('COUNTRIES', $this->basket->deliveryInfo['country_code'],'','');
		}
				
			// Markers for use if you want to output line-broken address information
		$markerArray['###PERSON_ADDRESS_DISPLAY###'] = nl2br($markerArray['###PERSON_ADDRESS###']);
		$markerArray['###DELIVERY_ADDRESS_DISPLAY###'] = nl2br($markerArray['###DELIVERY_ADDRESS###']);

		$personSalutationText = $deliverySalutationText = '';
		if ($this->activityArray['products_info'])	{
				// Salutation		
			$salutationText = '';
			$prodTmp = explode(';', $row['size']);
			foreach ($TCA['sys_products_orders']['columns']['salutation']['config']['items'] as $key => $salutation) {
//				$temp = $this->pibase->sL($salutation[0]);
//				debug ($temp, '$temp', __LINE__, __FILE__);
//				$text = $this->pibase->pi_getLL($temp);
				$text = $this->pibase->pi_getLL('salutation'.$salutation[1]);
				$salutationText .= '<OPTION value="'.$salutation[1].'">'.$text.'</OPTION>';
			}
			$salutationText = '[salutation]">' . $salutationText.'</SELECT>';
			$salutationPreText = '<SELECT name="recs';
			$personSalutationText = $salutationPreText . '[personinfo]' . $salutationText;  
			$deliverySalutationText = $salutationPreText . '[delivery]' . $salutationText;
		} else {
			$personSalutationText = $this->pibase->pi_getLL('salutation'.$this->basket->personInfo['salutation']); 
			$deliverySalutationText = $this->pibase->pi_getLL('salutation'.$this->basket->deliveryInfo['salutation']);
		}
		$markerArray['###PERSON_SALUTATION###'] = $personSalutationText;
		$markerArray['###DELIVERY_SALUTATION###'] = $deliverySalutationText;

			// Delivery note.
		$markerArray['###DELIVERY_NOTE###'] = $this->basket->deliveryInfo['note'];
		$markerArray['###DELIVERY_NOTE_DISPLAY###'] = nl2br($markerArray['###DELIVERY_NOTE###']);

			// Desired delivery date.
		$markerArray['###DELIVERY_DESIRED_DATE###'] = $this->basket->deliveryInfo['desired_date'];

			// Order:	NOTE: Data exist only if the getBlankOrderUid() has been called. Therefore this field in the template should be used only when an order has been established
		$markerArray['###ORDER_UID###'] = $this->order->getOrderNumber($this->basket->recs['tt_products']['orderUid']);
		$markerArray['###ORDER_DATE###'] = $this->pibase->cObj->stdWrap($this->basket->recs['tt_products']['orderDate'],$this->conf['orderDate_stdWrap.']);
		$markerArray['###ORDER_TRACKING_NO###'] = $this->basket->recs['tt_products']['orderTrackingNo'];

			// Fe users:
		$markerArray['###FE_USER_USERNAME###'] = $TSFE->fe_user->user['username'];
		$markerArray['###FE_USER_UID###'] = $TSFE->fe_user->user['uid'];

		$markerArray['###PERSON_AGB###'] = $this->basket->personInfo['agb'] ? ' checked' : '';

		$markerArray['###USERNAME###'] = $this->basket->personInfo['email'];
		$markerArray['###PASSWORD###'] = $this->basket->password;

			// URL
		$markerArray =  $this->marker->addURLMarkers(0, $markerArray);

		$agb_url=array();
		$pidagb = intval($this->conf['PIDagb']);
		$agb_url['id'] = 'id='.$pidagb;
		$agb_url['type']= $TSFE->type ? 'type='.$TSFE->type : '';
		$agb_url['backPID']= 'backPID='.$TSFE->id;

/* Added els6: reorganized this part. First calculating amount in euros, then calculate voucher discount, then calcualte the creditpoints */

/* Added Els: below 3 lines moved from above */
			// This is the total for everything
		$this->basket->getCalculatedSums();
		$markerArray['###PRICE_TOTAL_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['total']);
		$markerArray['###PRICE_TOTAL_NO_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceNoTax']['total']);
		$markerArray['###PRICE_TOTAL_ONLY_TAX###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['total']-$this->basket->calculatedArray['priceNoTax']['total']);

/* Added els4: PRICE_GOODSTOTAL_TOTUNITS_NO_TAX: sum total price (winkelwagen.tmpl) */
		$markerArray['###PRICE_GOODSTOTAL_TOTUNITS_NO_TAX###'] = $this->price->priceFormat($sum_price_total_totunits_no_tax);

/* Added els8: shipping project meerwijn depends on subtotal amount, below shipping.NoTaxPrice, shipping price is shipping.fixprice, above no shiiping price */
		if ($markerArray['###PRICE_GOODSTOTAL_TOTUNITS_NO_TAX###'] <= $this->conf['shipping.']['NoTaxPrice']) {
			$markerArray['###PRICE_SHIPPING_NO_TAX###'] = $this->conf['shipping.']['fixprice'];
		}
//		} else { Franz: This is wrong here!
//			$markerArray['###PRICE_SHIPPING_NO_TAX###'] = '0.00';
//		}
		

/* Added Els: voucher marker inclusive conditions */
/* Added Els5: small changes in voucher marker inclusive conditions */
		if ($TSFE->fe_user->user['tt_products_vouchercode'] == '') {
			$subpartArray['###SUB_VOUCHERCODE###'] = '';
			$markerArray['###INSERT_VOUCHERCODE###'] = 'recs[tt_products][vouchercode]';
			$markerArray['###VALUE_VOUCHERCODE###'] = $this->basket->recs['tt_products']['vouchercode'];
			if ($this->basket->recs['tt_products']['vouchercode'] == '') {
				$subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = '';
				$subpartArray['###SUB_VOUCHERCODE_DISCOUNTOWNID###'] = '';
				$subpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = '';
			} else {
				$res = $TYPO3_DB->exec_SELECTquery('uid', 'fe_users', 'username="'.$this->basket->recs['tt_products']['vouchercode'].'"');
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
						//$this->basket->calculatedArray['priceTax']['voucher'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['shipping']);
						$this->basket->calculatedArray['priceTax']['voucher'] = $this->conf['voucherPrice'];
						$markerArray['###VOUCHER_DISCOUNT###'] = $this->price->priceFormat($this->basket->calculatedArray['priceTax']['voucher']);
						$subpartArray['###SUB_VOUCHERCODE_NODISCOUNT###'] = '';
					}
				} else {
					$subpartArray['###SUB_VOUCHERCODE_DISCOUNTOWNID###'] = '';
					$subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = '';
				}
			}
		} else {
			$subpartArray['###SUB_VOUCHERCODE_EMPTY###'] = '';
/* Added Els8: put voucher_discount 0 for plain text email */
			$markerArray['###VOUCHER_DISCOUNT###'] = '0.00';
		}

/* Added Els: creditpoint inclusive conditions*/
/* Added Els6: more conditions on creditpoints calculations inclusive redeeming of gift certificates */

/* Added els6: do not execute the redeeming of the gift certificate if template = OVERVIEW */
		if ($subpartMarker != '###BASKET_OVERVIEW_TEMPLATE###') {

// Added Franz: GIFT CERTIFICATE
			$markerArray['###GIFT_CERTIFICATE_UNIQUE_NUMBER_NAME###']='recs[tt_products][gift_certificate_unique_number]';
			$markerArray['###FORM_NAME###']='BasketForm';
			$markerArray['###FORM_NAME_GIFT_CERTIFICATE###']='BasketGiftForm';

/* Added els5: markerarrays for gift certificates */
/* Added Els6: routine for redeeming the gift certificate (other way then proposed by Franz */
			$markerArray['###INSERT_GIFTCODE###'] = 'recs[tt_products][giftcode]';
			$markerArray['###VALUE_GIFTCODE###'] = $this->basket->recs['tt_products']['giftcode'];
			if ($this->basket->recs['tt_products']['giftcode'] == '') {
				$subpartArray['###SUB_GIFTCODE_DISCOUNT###'] = '';
				$subpartArray['###SUB_GIFTCODE_DISCOUNTWRONG###'] = '';
				if (t3lib_div::_GP('creditpoints_gifts') == '') {
					$markerArray['###CREDITPOINTS_GIFTS###'] = '';
					$subpartArray['###SUB_GIFTCODE_DISCOUNT_TRUE###'] = '';
				} else {
					$markerArray['###CREDITPOINTS_GIFTS###'] = t3lib_div::_GP('creditpoints_gifts');
				}
			} else {
				$uniqueId = t3lib_div::trimExplode ('-', $this->basket->recs['tt_products']['giftcode'], true);

				$query='uid=\''.intval($uniqueId[0]).'\' AND crdate=\''.$uniqueId[1].'\''.' AND NOT deleted' ;
				$giftRes = $TYPO3_DB->exec_SELECTquery('*', 'tt_products_gifts', $query);

				$row = $TYPO3_DB->sql_fetch_assoc($giftRes);
				$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);

				if ($row && $pricefactor > 0) {
					$money = $row['amount'];
					$uid = $row['uid'];
					$fieldsArray = array();
					$fieldsArray['deleted']=1;
						// Delete the gift record
					$TYPO3_DB->exec_UPDATEquery('tt_products_gifts', 'uid='.intval($uid), $fieldsArray);

					$creditpoints_gift = $money / $pricefactor;

					tx_ttproducts_creditpoints_div::addCreditPoints($TSFE->fe_user->user['username'], $creditpoints_gift);

					$markerArray['###GIFT_DISCOUNT###'] = $creditpoints_gift;
					$markerArray['###VALUE_GIFTCODE_USED###'] = $this->basket->recs['tt_products']['giftcode'];
					$markerArray['###VALUE_GIFTCODE###'] = '';
					$subpartArray['###SUB_GIFTCODE_DISCOUNTWRONG###']= '';
					if (t3lib_div::_GP('creditpoints_gifts') == '') {
						$markerArray['###CREDITPOINTS_GIFTS###'] = '';
						$subpartArray['###SUB_GIFTCODE_DISCOUNT_TRUE###'] = '';
					} else {
						$markerArray['###CREDITPOINTS_GIFTS###'] = t3lib_div::_GP('creditpoints_gifts');
					}
					$creditpoints_gift_tot = $markerArray['###CREDITPOINTS_GIFTS###'] + $creditpoints_gift;
					$markerArray['###CREDITPOINTS_GIFTS###'] = $creditpoints_gift_tot;
				} else {
					$subpartArray['###SUB_GIFTCODE_DISCOUNT###'] = '';
					if (t3lib_div::_GP('creditpoints_gifts') == '') {
						$markerArray['###CREDITPOINTS_GIFTS###'] = '';
						$subpartArray['###SUB_GIFTCODE_DISCOUNT_TRUE###'] = '';
					} else {
						$markerArray['###CREDITPOINTS_GIFTS###'] = t3lib_div::_GP('creditpoints_gifts');
					}
				}
			}
		}

		$markerArray['###AMOUNT_CREDITPOINTS###'] = $TSFE->fe_user->user['tt_products_creditpoints']+$creditpoints_gift;

		// maximum1 amount of creditpoint to change is amount on account minus amount already spended in the credit-shop
		$max1_creditpoints = $TSFE->fe_user->user['tt_products_creditpoints']+$creditpoints_gift - $sum_pricecredits_total_totunits_no_tax;
		// maximum2 amount of creditpoint to change is amount bought multiplied with creditpointfactor
		$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);
		if ($pricefactor > 0) {
			$max2_creditpoints = explode (".",($markerArray['###PRICE_GOODSTOTAL_TOTUNITS_NO_TAX###'] + $markerArray['###PRICE_SHIPPING_NO_TAX###'] - $markerArray['###VOUCHER_DISCOUNT###'])/$pricefactor );
		}
		// real maximum amount of creditpoint to change is minimum of both maximums
		$markerArray['###AMOUNT_CREDITPOINTS_MAX###'] = number_format( min ($max1_creditpoints,$max2_creditpoints[0]),0);

		// if quantity is 0 than
		if ($markerArray['###AMOUNT_CREDITPOINTS###'] == '0') {
			$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
			$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
			$subpartArray['###SUB_CREDITPOINTS_AMOUNT###'] = '';
		} else {
			$subpartArray['###SUB_CREDITPOINTS_AMOUNT_EMPTY###'] = '';
		}
		$markerArray['###CHANGE_AMOUNT_CREDITPOINTS###'] = 'recs[tt_products][creditpoints]';
		if ($this->basket->recs['tt_products']['creditpoints'] == '') {
			$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = 0;
			$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
/* Added Els8: put credit_discount 0 for plain text email */
			$markerArray['###CREDIT_DISCOUNT###'] = '0.00';
		} else {
			// quantity chosen can not be larger than the maximum amount, above calculated
			if ($this->basket->recs['tt_products']['creditpoints'] > number_format( min ($max1_creditpoints,$max2_creditpoints[0]),0)) $this->basket->recs['tt_products']['creditpoints'] = number_format( min ($max1_creditpoints,$max2_creditpoints[0]),0);
			$this->basket->calculatedArray['priceTax']['creditpoints'] = $this->price->priceFormat($this->basket->recs['tt_products']['creditpoints']*$pricefactor);
			$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = $this->basket->recs['tt_products']['creditpoints'];
			$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
			$markerArray['###CREDIT_DISCOUNT###'] = $this->basket->calculatedArray['priceTax']['creditpoints'];
		}

/* Added els5: CREDITPOINTS_SPENDED: creditpoint needed, check if user has this amount of creditpoints on his account (winkelwagen.tmpl), only if user has logged in */
		$markerArray['###CREDITPOINTS_SPENDED###'] = $sum_pricecredits_total_totunits_no_tax;
		if ($sum_pricecredits_total_totunits_no_tax <= $markerArray['###AMOUNT_CREDITPOINTS###']) {
			$subpartArray['###SUB_CREDITPOINTS_SPENDED_EMPTY###'] = '';
			$markerArray['###CREDITPOINTS_SPENDED###'] = $sum_pricecredits_total_totunits_no_tax;
			// new saldo: creditpoints
			$markerArray['###AMOUNT_CREDITPOINTS###'] = $markerArray['###AMOUNT_CREDITPOINTS###'] - $markerArray['###CREDITPOINTS_SPENDED###'];
		} else {
			if (!$markerArray['###FE_USER_UID###']) {
				$subpartArray['###SUB_CREDITPOINTS_SPENDED_EMPTY###'] = '';
			} else {
				$markerArray['###CREDITPOINTS_SPENDED_ERROR###'] = "Wijzig de artikelen in de kurkenshop: onvoldoende kurken op uw saldo (".$markerArray['###AMOUNT_CREDITPOINTS###'].")."; // TODO
				$markerArray['###CREDITPOINTS_SPENDED###'] = '&nbsp;';
			}
		}

		$creditpoints = tx_ttproducts_creditpoints_div::getCreditPoints($sum_pricecreditpoints_total_totunits);

		$markerArray['###CREDITPOINTS_SAVED###'] = number_format($creditpoints * $sum_pricecreditpoints_total_totunits,'0');

/* Added Els4: total price = subtotal - bezorgkosten + voucher + gift + giftcertificate (winkelwagen.tmpl) */
/* Added Els7: error in calcualtion */
		$markerArray['###PRICE_TOTAL_MEERWIJN###'] = $this->price->priceFormat($markerArray['###PRICE_GOODSTOTAL_TOTUNITS_NO_TAX###'] + $markerArray['###PRICE_SHIPPING_NO_TAX###'] - $markerArray['###VOUCHER_DISCOUNT###'] - $markerArray['###CREDIT_DISCOUNT###']);

		$wrappedSubpartArray['###LINK_AGB###']=array('<a href="'.$TSFE->absRefPrefix.'index.php?'.implode($agb_url,'&').'" target="'.$this->conf['agbtarget'].'">','</a>');

			// Final substitution:
		if (!$TSFE->loginUser)	{		// Remove section for FE_USERs only, if there are no fe_user
			$subpartArray['###FE_USER_SECTION###']='';
		}
		$bFrameWork = $t['basketFrameWork'];
		$subpartArray['###MESSAGE_SHIPPING###'] = $this->pibase->cObj->substituteMarkerArrayCached($this->pibase->cObj->getSubpart($bFrameWork,'###MESSAGE_SHIPPING_'.$this->basket->basketExtra['shipping'].'###'),$markerArray);
		$subpartArray['###MESSAGE_PAYMENT###'] = $this->pibase->cObj->substituteMarkerArrayCached($this->pibase->cObj->getSubpart($bFrameWork,'###MESSAGE_PAYMENT_'.$this->basket->basketExtra['payment'].'###'),$markerArray);



		$bFrameWork=$this->pibase->cObj->substituteMarkerArrayCached($t['basketFrameWork'],$markerArray,$subpartArray,$wrappedSubpartArray);

			// substitute the main subpart with the rendered content.
		$out=$this->pibase->cObj->substituteSubpart($bFrameWork, '###ITEM_CATEGORY_AND_ITEMS###', $out);

		return $out;
	} // getView


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_basket_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_basket_view.php']);
}


?>
