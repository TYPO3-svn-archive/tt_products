<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * class with functions to control all activities
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


global $TYPO3_CONF_VARS;


class tx_ttproducts_control {

	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $basket; 	// the basket object

	var $tt_products; 				// object of the type tx_ttproducts_product
	var $tt_products_articles;		// object of the type tx_table_db
	var $tt_products_cat; 			// object of the type tx_ttproducts_category

	var $paymentshipping; 			// object of the type tx_ttproducts_paymentshipping

	var $templateCode='';				// In init(), set to the content of the templateFile. Used by default in getView()

	var $fe_users;					// object of the type tx_table_db
	var $activityArray;				// activities for the CODEs
	var $viewTable;
	var $price;

	var $marker; // marker functions

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, &$cnf, &$templateCode, &$basket, &$tt_products, &$tt_products_articles, &$tt_products_cat, &$fe_users, &$price, &$paymentshipping)  {
		global $TYPO3_DB,$TSFE,$TCA;
		
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
 		$this->templateCode = &$templateCode;
 		$this->basket = &$basket;

		$this->tt_products = &$tt_products;
		$this->tt_products_articles = &$tt_products_articles;
		$this->tt_products_cat = &$tt_products_cat;
		$this->fe_users = &$fe_users;
		$this->price = &$price;
		$this->paymentshipping = &$paymentshipping;

 		$this->viewTable = &$basket->viewTable;

		$this->marker = t3lib_div::makeInstance('tx_ttproducts_marker');
		$this->marker->init($this->pibase, $this->cnf, $this->basket);
	} // init



	/**
	 * returns the activities in the order in which they have to be processed
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
 	 */
	function transformActivities($activities)	{
		$retActivities = array();
		$codeActivities = array();
		$codeActivityArray =  Array (
			'1' =>  
				'products_overview',
				'products_basket',
				'products_info',
				'products_payment',
				'products_customized_payment',
				'products_finalize',
		);

		$activityArray =  Array (
			'1' =>  
			'products_redeem_gift',
			'products_clear_basket'
		);


		if (is_array($activities)) {
			foreach ($codeActivityArray as $k => $activity) {
				if ($activities[$activity]) {
					$codeActivities[$activity] = true;
				}
			}
		}

		if ($codeActivities['products_info']) {
			if($codeActivities['products_payment']) {
				$codeActivities['products_payment'] = false;
			}
			if($codeActivities['products_finalize']) {
				$codeActivities['products_finalize'] = false;
			}
		}
		if ($codeActivities['products_payment']) {
			if($codeActivities['products_finalize']) {
				$codeActivities['products_finalize'] = false;
			}
		}
		if ($codeActivities['products_basket'] && count($codeActivities)>1) {
			$codeActivities['products_basket'] = false;
		}

		
		if (is_array($activities)) {
			foreach ($activityArray as $k => $activity) {
				if ($activities[$activity]) {
					$retActivities[$activity] = true;
				}
			}
			$retActivities = array_merge ($retActivities, $codeActivities);
		}

		return ($retActivities);
	}



	function processPayment(&$content, &$bFinalize, &$order)	{
		global $TSFE;

		$handleScript = $TSFE->tmpl->getFileName($this->basket->basketExtra['payment.']['handleScript']);
		$this->basket->getCalculatedSums();
		if ($handleScript)	{
			$content.= $this->paymentshipping->includeHandleScript($handleScript, $this->basket->basketExtra['payment.']['handleScript.'], $this->conf['paymentActivity'], $bFinalize);
		} else if (t3lib_extMgm::isLoaded ('paymentlib') && version_compare(phpversion(), '5.0.0', '>=') ) {
			$handleLib = $this->basket->basketExtra['payment.']['handleLib'];
			if ($handleLib == 'paymentlib')	{
				// Payment Library
				require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_paymentlib.php');
				
				$paymentlib = t3lib_div::makeInstance('tx_ttproducts_paymentlib');
				$paymentlib->init($this->pibase, $this->cnf, $this->basket, $this, $this->price, $order);
				$content.= $paymentlib->includeHandleLib($handleLib,$this->basket->basketExtra['payment.']['handleLib.'], $bFinalize);
			}
		}		
	}



	/**
	 * Do all the things to be done for this activity
	 * former functions products_basket and basketView::printView 
	 * Takes care of basket, address info, confirmation and gate to payment
	 * Also the 'products_...' script parameters are used here.
	 *
	 * @param		array		  CODEs for display mode
	 * @return	  void
	 */	
	function doProcessing (&$codes, &$error_message) {
		global $TSFE;
		global $TYPO3_DB;

		$content = '';
		$empty = '';
		$activityArray = array();
		$bBasketEmpty = false;
		$basketView = '';
		$address = '';
		$order = '';

			// use '_x' for coordinates from Internet Explorer if button images are used
		if (t3lib_div::_GP('products_redeem_gift') || t3lib_div::_GP('products_redeem_gift_x'))    {
		 	$activityArray['products_redeem_gift'] = true;
		}
		if (t3lib_div::_GP('products_clear_basket') || t3lib_div::_GP('products_clear_basket_x'))    {
			$activityArray['products_clear_basket'] = true;
		}

      
       	if (t3lib_div::_GP('products_overview') || t3lib_div::_GP('products_overview_x'))    { 
			$activityArray['products_overview'] = true; 
		}
		if (t3lib_div::_GP('products_info') || t3lib_div::_GP('products_info_x'))    { 
			$activityArray['products_info'] = true;
		}
		if (t3lib_div::_GP('products_payment') || t3lib_div::_GP('products_payment_x'))    { 
			$activityArray['products_payment'] = true;
		}
		if (t3lib_div::_GP('products_customized_payment') || t3lib_div::_GP('products_customized_payment_x'))    {
			$activityArray['products_customized_payment'] = true;
		}
		if (t3lib_div::_GP('products_finalize') || t3lib_div::_GP('products_finalize_x'))    { 
			$activityArray['products_finalize'] = true; 
		}

		$codeActivityArray=array();
		$bBasketCode = false;
		if (is_array($codes)) {
			foreach ($codes as $k => $code) {
				if ($code=='BASKET')	{
					$codeActivityArray['products_basket']=true;
					$bBasketCode = true;
				} elseif ($code=='INFO' && !($activityArray['products_payment'] || $activityArray['products_finalize']))	{
					$codeActivityArray['products_info']=true;
					$bBasketCode = true;
				} elseif ($code=='OVERVIEW') {
					$codeActivityArray['products_overview']=true;
				} elseif ($code=='PAYMENT' && !($activityArray['products_finalize']))	{
					$codeActivityArray['products_payment']=true;
				} elseif ($code=='FINALIZE')	{
					$codeActivityArray['products_finalize']=true;
				}
			}
		}
		
		if ($bBasketCode)	{
			$activityArray = array_merge ($activityArray, $codeActivityArray);
			$this->activityArray = $this->transformActivities($activityArray);
		} else {
			// only the code activities if there is no code BASKET or INFO set
			$this->activityArray = $codeActivityArray;
		}
		if (count($this->basket->basketExt) && count($this->activityArray))	{	// If there is content in the shopping basket, we are going display some basket code
				// prepare action
			$basket_tmpl = '';
			if (count($this->activityArray)) {
				// $this->basket->getCalculatedBasket();  // all the basket calculation is done in this function once and not multiple times here
				$mainMarkerArray=array();
				$mainMarkerArray['###EXTERNAL_COBJECT###'] = $this->pibase->externalCObject.'';  // adding extra preprocessing CObject
				$bFinalize = false; // no finalization must be called.

				if ($this->activityArray['products_info'] || $this->activityArray['products_payment'] || $this->activityArray['products_customized'] || $this->activityArray['products_finalize'])	{
					// get credit card info
					include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_card.php');
					$card = t3lib_div::makeInstance('tx_ttproducts_card');
					$card->init(
						$this->pibase,
						$this->cnf,
						$this->basket->recs
					);
					$card->getItemMarkerArray ($mainMarkerArray);
				}	

				
				foreach ($this->activityArray as $activity => $value) {
					if ($value) {
							// perform action
						switch($activity)	{
							case 'products_clear_basket':
								// Empties the shopping basket!
								$this->basket->clearBasket();
								$bBasketEmpty = true;
							break;
							case 'products_basket':
								if (count($this->activityArray) == 1) {
									$basket_tmpl = 'BASKET_TEMPLATE';
								}
							break;
							case 'products_overview':
								$this->pibase->load_noLinkExtCobj();	// TODO
								$basket_tmpl = 'BASKET_OVERVIEW_TEMPLATE';
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
								$basket_tmpl = 'BASKET_INFO_TEMPLATE';
								// }
							break;
							case 'products_payment':
								$this->pibase->load_noLinkExtCobj();	// TODO
								$pidagb = intval($this->conf['PIDagb']);

								if (!is_object($address))	{
									include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_address.php');
									$address = &t3lib_div::getUserObj('tx_ttproducts_address');
									$address->init($this->pibase, $this->cnf, $this->basket->recs, $this->fe_users, $this->paymentshipping);
									$address->mapPersonIntoDelivery();
								}

								$checkRequired = $address->checkRequired();
								$checkAllowed = $address->checkAllowed();
								if ($checkRequired == '' && $checkAllowed == '' &&
									(empty($pidagb) || isset($_REQUEST['recs']['personinfo']['agb']))) {

									// basket view
									if (!is_object($basketView))	{
										include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_basket_view.php');
										$basketView = &t3lib_div::getUserObj('tx_ttproducts_basket_view');
										$basketView->init ($this->basket, $this->templateCode);
									}					

									$content.=$basketView->getView($empty, 'PAYMENT', $address, $this->activityArray['products_info'], false, '###BASKET_PAYMENT_TEMPLATE###', $mainMarkerArray);
									
									if (trim($this->conf['paymentActivity'])=='payment')	{
										if (!is_object($order))	{
											include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_order.php');
							
												// order
											$order = &t3lib_div::getUserObj('tx_ttproducts_order');
											$order->init(
												$this->pibase,
												$this->cnf,
												$this->tt_products,
												$this->tt_products_articles,
												$this->tt_products_cat,
												$this->basket,
												$this->conf['useArticles']
											);
										}
										$this->processPayment($content, $bFinalize, $order);
									}
								} else {	// If not all required info-fields are filled in, this is shown instead:
									$content.=$this->pibase->cObj->getSubpart($this->templateCode,$this->marker->spMarker('###BASKET_REQUIRED_INFO_MISSING###'));
									$markerArray = $this->marker->addURLMarkers(0, array());
									$label = '';

									if ($pidagb && !isset($_REQUEST['recs']['personinfo']['agb'])) {
										 // so AGB has not been accepted
										$label = $this->pibase->pi_getLL('accept_AGB');
									} else {
										if (t3lib_extMgm::isLoaded('sr_feuser_register')) {
											$check = ($checkRequired ? $checkRequired: $checkAllowed);
											$label = $TSFE->sL('LLL:EXT:sr_feuser_register/pi1/locallang.php:missing_'.$check);
											$editPID = $TSFE->tmpl->setup['plugin.']['tx_srfeuserregister_pi1.']['editPID'];
											if ($TSFE->loginUser && $editPID) {
												$addParams = array ('products_payment' => 1);
												$addParams = $this->marker->getLinkParams('',$addParams,true);
												$srfeuserBackUrl = $this->pibase->pi_getPageLink($TSFE->id,'',$addParams);
												$srfeuserParams = array('tx_srfeuserregister_pi1[backURL]' => $srfeuserBackUrl);
												$addParams = $this->marker->getLinkParams('',$srfeuserParams,true);
												$markerArray['###FORM_URL_INFO###'] = $this->pibase->pi_getPageLink($editPID,'',$addParams);
											}
										} else {
											$label = 'field: '.$checkRequired;
										}
									}
									$markerArray['###ERROR_DETAILS###'] = $label;
									$content = $this->pibase->cObj->substituteMarkerArray($content, $markerArray);
								}
							break;
							// a special step after payment and before finalization needed for some payment systems
							case 'products_customized':
								if (trim($this->conf['paymentActivity'])=='customized')	{
									if (!is_object($order))	{
										include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_order.php');
						
											// order
										$order = &t3lib_div::getUserObj('tx_ttproducts_order');
										$order->init(
											$this->pibase,
											$this->cnf,
											$this->tt_products,
											$this->tt_products_articles,
											$this->tt_products_cat,
											$this->basket,
											$this->conf['useArticles']
										);
									}
									$this->processPayment($content, $bFinalize, $order);
								}
							break;
							case 'products_finalize':
								$bFinalize = true;
							break;
							default:
								// nothing yet
							break;
						} // switch
					}	// if ($value)
					if ($bBasketEmpty)	{
						break;
					}

						// in case the basket template has been set
					if ($basket_tmpl) {
						// basket view
						if (!is_object($basketView))	{
							include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_basket_view.php');
							$basketView = &t3lib_div::getUserObj('tx_ttproducts_basket_view');
							$basketView->init ($this->basket, $this->templateCode);
						}

						if (!is_object($address))	{
							include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_address.php');
							$address = &t3lib_div::getUserObj('tx_ttproducts_address');
							$address->init($this->pibase, $this->cnf, $this->basket->recs, $this->fe_users, $this->paymentshipping);
							$address->mapPersonIntoDelivery();
						}

						$content .= $basketView->getView($empty, 'BASKET', $address, $this->activityArray['products_info'], false, '###'.$basket_tmpl.'###',$mainMarkerArray);
						$bFinalize = false;
						// stop here as soon as the first basket content has been drawn
						break; // foreach
					}
				} // foreach ($this->activityArray as $activity=>$value)

					// finalization at the end so that after every activity this can be called
				if ($bFinalize)	{
					if (!is_object($address))	{
						include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_address.php');
						$address = &t3lib_div::getUserObj('tx_ttproducts_address');
						$address->init($this->pibase, $this->cnf, $this->basket->recs, $this->fe_users, $this->paymentshipping);
						$address->mapPersonIntoDelivery();
					}
					$checkRequired = $address->checkRequired();
					$checkAllowed = $address->checkAllowed();
					if ($checkRequired == '' && $checkAllowed == '')	{
						$this->pibase->load_noLinkExtCobj();	// TODO
						$handleScript = $TSFE->tmpl->getFileName($this->basket->basketExtra['payment.']['handleScript']);
						if (!is_object($order))	{
							include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_order.php');

								// order
							$order = &t3lib_div::getUserObj('tx_ttproducts_order');
							$order->init(
								$this->pibase,
								$this->cnf,
								$this->tt_products,
								$this->tt_products_articles,
								$this->tt_products_cat,
								$this->basket,
								$this->conf['useArticles']
							);
						}
						$orderUid = $order->getBlankUid();
						if (trim($this->conf['paymentActivity']) == 'finalize')	{
							$this->processPayment($content, $bFinalize, $order);
						}

						if (!is_object($basketView))	{
							include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_basket_view.php');
							$basketView = &t3lib_div::getUserObj('tx_ttproducts_basket_view');
							$basketView->init ($this->basket, $this->templateCode);
						}

						// Added Els4: to get the orderconfirmation template as html email and the thanks template as thanks page
						$tmpl = 'BASKET_ORDERCONFIRMATION_TEMPLATE';
						$orderConfirmationHTML = $basketView->getView($empty, 'BASKET', $address, false, false, '###'.$tmpl.'###', $mainMarkerArray);
						include_once (PATH_BE_ttproducts.'control/class.tx_ttproducts_activity_finalize.php');
							// order finalization
						$activityFinalize = t3lib_div::makeInstance('tx_ttproducts_activity_finalize');
						$activityFinalize->init($this->pibase, $this->cnf, $this->basket, $this->tt_products_cat, $order);

						$activityFinalize->doProcessing($this->templateCode, 
							$basketView, $this->viewTable, $this->price, 
							$orderUid, $orderConfirmationHTML, $error_message, $address); 
								// Important: 	 MUST come after the call of prodObj->getView, because this function, getView, calculates the order! And that information is used in the finalize-function
						$contentTmp = $orderConfirmationHTML;
						if ($this->conf['PIDthanks'] > 0) {
							$tmpl = 'BASKET_ORDERTHANKS_TEMPLATE';
							$contentTmp = $basketView->getView($empty, 'BASKET', $address, false, false, '###'.$tmpl.'###', $mainMarkerArray);
						}
						$content .= $contentTmp;
						$content .= $basketView->getView($empty, 'BASKET', $address, false, false, '###BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE###');
						// Empties the shopping basket!
						$this->basket->clearBasket();
					} else {	// If not all required info-fields are filled in, this is shown instead:
						$content .= $this->pibase->cObj->getSubpart($this->templateCode,$this->marker->spMarker('###BASKET_REQUIRED_INFO_MISSING###'));
						$content = $this->pibase->cObj->substituteMarkerArray($content, $this->marker->addURLMarkers(0, array()));
					}
				}
			} else { // if (count($this->activityArray))
				// nothing. no BASKET code or similar thing
			}
		} else { // if (count($this->basket->basketExt))
			$bBasketEmpty = true;
		}
		
		if ($bBasketEmpty)	{
			if ($this->activityArray['products_overview']) {
				$this->pibase->load_noLinkExtCobj();	//
				$content .= $this->pibase->cObj->getSubpart($this->templateCode,$this->marker->spMarker('###BASKET_OVERVIEW_EMPTY###'));
			} else if ($this->activityArray['products_basket'] || $this->activityArray['products_info']) {
				$content .= $this->pibase->cObj->getSubpart($this->templateCode,$this->marker->spMarker('###BASKET_TEMPLATE_EMPTY###'));
			}			
		}
		$markerArray = array();
		$markerArray['###EXTERNAL_COBJECT###'] = $this->pibase->externalCObject;	// adding extra preprocessing CObject
		$content = $this->pibase->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	} // 

	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control.php']);
}


?>
