<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2009 Franz Holzinger <franz@ttproducts.de>
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
 * class with functions to control all activities
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

require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_model_activity.php');
require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_info_view.php');
require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_subpartmarker.php');
require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_url_view.php');
require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_basket_view.php');


class tx_ttproducts_control {
	public $pibase; // reference to object of pibase
	public $pibaseClass;
	public $cObj;
	public $conf;
	public $config;
	public $basket; 	// the basket object
	public $templateCode='';		// In init(), set to the content of the templateFile. Used by default in getView()
	public $activityArray;		// activities for the CODEs
	public $funcTablename;
	public $error_code = array();
	public $subpartmarkerObj; // subpart marker functions
	public $urlObj; // url functions
	public $urlArray; // overridden url destinations
	public $useArtcles;


	public function init ($pibaseClass, $funcTablename, &$templateCode, $useArtcles, &$error_code)  {
		global $TYPO3_DB,$TSFE,$TCA;

		$this->pibaseClass = $pibaseClass;
		$this->pibase = &t3lib_div::getUserObj('&' . $pibaseClass);
		$this->cObj = &$this->pibase->cObj;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->templateCode = &$templateCode;
		$this->basket = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$this->funcTablename = $funcTablename;
		$this->useArtcles = $useArtcles;
		$this->error_code = &$error_code;

		$this->subpartmarkerObj = t3lib_div::makeInstance('tx_ttproducts_subpartmarker');
		$this->subpartmarkerObj->init($this->cObj);
		$this->urlObj = t3lib_div::getUserObj('&tx_ttproducts_url_view'); // a copy of it
		// This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
		$this->urlArray = array();
		if ($this->basket->basketExtra['payment.']['handleURL'])	{
			$this->urlArray['form_url_thanks'] = $this->basket->basketExtra['payment.']['handleURL'];
		}
		if ($this->basket->basketExtra['payment.']['handleTarget'])	{	// Alternative target
			$this->urlArray['form_url_target'] = $this->basket->basketExtra['payment.']['handleTarget'];
		}
		$this->urlObj->setUrlArray($this->urlArray);
	} // init


	protected function getOrderUid ()	{
		$rc = FALSE;
		if (count($this->basket->itemArray))	{
			$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
			$orderObj = &$tablesObj->get('sys_products_orders');
			$orderUid = $orderObj->getUid();
			if (!$orderUid)	{
				$orderUid = $orderObj->getBlankUid();
			}
			$rc = $orderUid;
		}
		return $rc;
	}


	/**
	 * returns the activities in the order in which they have to be processed
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */
	public function transformActivities ($activities)	{

		$retActivities = array();
		$codeActivities = array();
		$codeActivityArray = array (
			'1' =>
				'products_overview',
				'products_basket',
				'products_info',
				'products_payment',
				'products_customized_payment',
				'products_verify',
				'products_finalize',
		);

		$activityArray =  array (
			'1' =>
			'products_redeem_gift',
			'products_clear_basket'
		);

		if (is_array($activities)) {
			foreach ($codeActivityArray as $k => $activity) {
				if ($activities[$activity]) {
					$codeActivities[$activity] = TRUE;
				}
			}
		}

		if ($codeActivities['products_info']) {
			if($codeActivities['products_payment']) {
				$codeActivities['products_payment'] = FALSE;
			}
		}
		if ($codeActivities['products_basket'] && count($codeActivities)>1) {
			$codeActivities['products_basket'] = FALSE;
		}

		if (is_array($activities)) {
			foreach ($activityArray as $k => $activity) {
				if ($activities[$activity]) {
					$retActivities[$activity] = TRUE;
				}
			}
			$retActivities = array_merge($retActivities, $codeActivities);
		}
		return ($retActivities);
	}


	public function &processPayment ($orderUid, $cardRow, $pidArray, $currentPaymentActivity, &$bFinalize, &$errorMessage)	{
		global $TSFE;

		$content = '';
		$basketView = &t3lib_div::getUserObj('&tx_ttproducts_basket_view');
		$handleScript = $TSFE->tmpl->getFileName($this->basket->basketExtra['payment.']['handleScript']);

		$handleLib = $this->basket->basketExtra['payment.']['handleLib'];
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

		if ($handleScript)	{
			$paymentshippingObj = &t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
			$content = $paymentshippingObj->includeHandleScript($handleScript, $this->basket->basketExtra['payment.']['handleScript.'], $this->conf['paymentActivity'], $bFinalize, $this->pibase);
		} else if (strpos($handleLib,'transactor') !== FALSE && t3lib_extMgm::isLoaded($handleLib))	{
				// Payment Transactor
			require_once(t3lib_extMgm::extPath($handleLib) . 'lib/class.tx_' . $handleLib . '_api.php');
		// Get references to the concerning baskets
			$calculatedArray = $this->basket->getCalculatedArray();
			$infoViewObj = &t3lib_div::getUserObj('&tx_ttproducts_info_view');
			$addQueryString = array();
			$excludeList = '';
			$linkParams = $this->urlObj->getLinkParams($excludeList,$addQueryString,TRUE);

			$markerArray = array();
			tx_transactor_api::init($this->pibase, $this->cObj, $this->conf);
			$content = tx_transactor_api::includeHandleLib(
				$handleLib,
				$this->basket->basketExtra['payment.']['handleLib.'],
				TT_PRODUCTS_EXTkey,
				$this->basket->getItemArray(),
				$calculatedArray,
				$this->basket->recs['delivery']['note'],
				$this->conf['paymentActivity'],
				$currentPaymentActivity,
				$infoViewObj->infoArray,
				$pidArray,
				$linkParams,
				$this->basket->order['orderTrackingNo'],
				$orderUid,
				$cardRow,
				$bFinalize,
				$bFinalVerify,
				$markerArray,
				$templateFilename,
				$localTemplateCode,
				$errorMessage
			);

			if (!$errorMessage && $content=='' && !$bFinalize && $localTemplateCode != '')	{
				$content = $basketView->getView(
					$localTemplateCode,
					'PAYMENT',
					$infoViewObj,
					FALSE,
					FALSE,
					TRUE,
					'TRANSACTOR_FORM_TEMPLATE',
					$markerArray,
					$templateFilename
				);
			}
		} else if (strpos($handleLib,'paymentlib') !== FALSE && t3lib_extMgm::isLoaded($handleLib)) {
			$eInfo = tx_div2007_alpha::getExtensionInfo_fh001($handleLib);

			$paymentlibVersion = $eInfo['version'];
			$phpVersion = phpversion();
			if (isset($this->basket->basketExtra['payment.']['handleLib.']) && is_array($this->basket->basketExtra['payment.']['handleLib.']))	{
				$gatewayExtName = $this->basket->basketExtra['payment.']['handleLib.']['extName'];
			}

			if (version_compare($paymentlibVersion, '0.2.1', '>=') && version_compare($paymentlibVersion, '0.4.0', '<') && version_compare($phpVersion, '5.0.0', '>='))	{

				if ($gatewayExtName != '' && t3lib_extMgm::isLoaded($gatewayExtName))	{

					// Payment Library
					require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_paymentlib.php');

					$paymentObj = t3lib_div::makeInstance('tx_ttproducts_paymentlib');
					$paymentObj->init(
						$this->pibase,
						$basketView,
						$this->urlObj
					);

					$content = $paymentObj->includeHandleLib(
						$handleLib,
						$this->basket->basketExtra['payment.']['handleLib.'],
						$bFinalize
					);
				} else {
					$langObj = &t3lib_div::getUserObj('&tx_ttproducts_language');
					if ($gatewayExtName == '')	{
						$message = tx_div2007_alpha::getLL($langObj,'extension_payment_missing');
					} else {
						$message = tx_div2007_alpha::getLL($langObj,'extension_missing');
						$messageArr =  explode('|', $message);
						$errorMessage = $messageArr[0] . $gatewayExtName . $messageArr[1];
					}
				}
			}
		}
		return $content;
	}


	/**
	 * Do all the things to be done for this activity
	 * former functions products_basket and basketView::printView
	 * Takes care of basket, address info, confirmation and gate to payment
	 * Also the 'products_...' script parameters are used here.
	 *
	 * @param	array		  CODEs for display mode
	 * @return	string	text to display
	 */
	public function &doProcessing (&$codes, &$errorMessage) {
		global $TSFE;
		global $TYPO3_DB;

		$content = '';
		$empty = '';
		$activityArray = array();
		$bBasketEmpty = FALSE;
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
		$langObj = &t3lib_div::getUserObj('&tx_ttproducts_language');
		$globalMarkerArray = $markerObj->getGlobalMarkerArray();
		$basketView = &t3lib_div::getUserObj('&tx_ttproducts_basket_view');
		$basketView->init(
			$this->pibaseClass,
			$this->urlArray,
			$this->useArtcles,
			$this->templateCode,
			$this->error_code
		);
		$pidTypeArray = array('PIDthanks','PIDfinalize','PIDpayment');
		$pidArray = array();
		foreach ($pidTypeArray as $pidType)	{
			if ($cnf->conf[$pidType])	{
				$pidArray[$pidType] = $cnf->conf[$pidType];
			}
		}
		$activityVarsArray = array(
			'clear_basket' => 'products_clear_basket',
			'customized_payment' => 'products_customized_payment',
			'finalize' => 'products_finalize',
			'info' => 'products_info',
			'overview' => 'products_overview',
			'payment' => 'products_payment',
			'redeem_gift' => 'products_redeem_gift',
			'verify' => 'products_verify'
		);
		$update = t3lib_div::_POST('products_update');
		$payment = t3lib_div::_POST('products_payment');
		$gpVars = t3lib_div::_GP(TT_PRODUCTS_EXTkey);

		if (!$update && !$payment && isset($gpVars) && is_array($gpVars) && isset($gpVars['activity']) && is_array($gpVars['activity']))	{
			$changedActivity = key($gpVars['activity']);
			$theActivity = $activityVarsArray[$changedActivity];
			if ($theActivity)	{
				$activityArray[$theActivity] = $gpVars['activity'][$changedActivity];
			}
		}

			// use '_x' for coordinates from Internet Explorer if button images are used
		if (t3lib_div::_GP('products_redeem_gift') || t3lib_div::_GP('products_redeem_gift_x'))    {
		 	$activityArray['products_redeem_gift'] = TRUE;
		}
		if (t3lib_div::_GP('products_clear_basket') || t3lib_div::_GP('products_clear_basket_x'))    {
			$activityArray['products_clear_basket'] = TRUE;
		}
		if (t3lib_div::_GP('products_overview') || t3lib_div::_GP('products_overview_x'))    {
			$activityArray['products_overview'] = TRUE;
		}
		if (t3lib_div::_GP('products_info') || t3lib_div::_GP('products_info_x'))    {
			$activityArray['products_info'] = TRUE;
		}
		if (t3lib_div::_GP('products_payment') || t3lib_div::_GP('products_payment_x'))    {
			$activityArray['products_payment'] = TRUE;
		}
		if (t3lib_div::_GP('products_customized_payment') || t3lib_div::_GP('products_customized_payment_x'))    {
			$activityArray['products_customized_payment'] = TRUE;
		}
		if (t3lib_div::_GP('products_verify') || t3lib_div::_GP('products_verify_x'))    {
			$activityArray['products_verify'] = TRUE;
		}
		if (t3lib_div::_GP('products_finalize') || t3lib_div::_GP('products_finalize_x'))    {
			$activityArray['products_finalize'] = TRUE;
		}

		$codeActivityArray=array();
		$bBasketCode = FALSE;
		if (is_array($codes)) {
			foreach ($codes as $k => $code) {
				if ($code=='BASKET')	{
					$codeActivityArray['products_basket']=TRUE;
					$bBasketCode = TRUE;
				} elseif ($code=='INFO' && !($activityArray['products_payment'] || $activityArray['products_verify'] || $activityArray['products_finalize']))	{
					$codeActivityArray['products_info']=TRUE;
					$bBasketCode = TRUE;
				} elseif ($code=='OVERVIEW') {
					$codeActivityArray['products_overview']=TRUE;
				} elseif ($code=='PAYMENT' && !($activityArray['products_finalize']))	{
					$codeActivityArray['products_payment']=TRUE;
				} elseif ($code=='FINALIZE')	{
					$codeActivityArray['products_finalize']=TRUE;
				}
			}
		}

		if ($bBasketCode)	{
			$activityArray = array_merge($activityArray, $codeActivityArray);
			$this->activityArray = $this->transformActivities($activityArray);
		} else {
			// only the code activities if there is no code BASKET or INFO set
			$this->activityArray = $codeActivityArray;
		}
		tx_ttproducts_model_activity::setActivityArray($this->activityArray);

		$infoViewObj = &t3lib_div::getUserObj('&tx_ttproducts_info_view');
		$infoViewObj->init(
			$this->pibase,
			$this->basket->recs,
			$activityArray['products_payment']
		);
		if ($infoViewObj->checkRequired('billing')=='')	{

			$infoViewObj->mapPersonIntoDelivery();
		}

		if (count($this->basket->itemArray) && count($this->activityArray))	{	// If there is content in the shopping basket, we are going display some basket code
				// prepare action
			$basket_tmpl = '';
			if (count($this->activityArray)) {
				// $this->basket->getCalculatedBasket();  // all the basket calculation is done in this function once and not multiple times here
				$mainMarkerArray = array();
				$mainMarkerArray['###EXTERNAL_COBJECT###'] = $this->pibase->externalCObject.'';  // adding extra preprocessing CObject
				$bFinalize = FALSE; // no finalization must be called.

				if ($this->activityArray['products_info'] || $this->activityArray['products_payment'] || $this->activityArray['products_customized_payment'] || $this->activityArray['products_verify'] || $this->activityArray['products_finalize'])	{
					// get credit card info
					$cardViewObj = &$tablesObj->get('sys_products_cards',TRUE);
					$cardObj = &$cardViewObj->getModelObj();
					$cardUid = $cardObj->getUid();
					$cardRow = $cardObj->get($cardUid);
					$cardViewObj->getMarkerArray($mainMarkerArray);

					// get bank account info
					$accountViewObj = &$tablesObj->get('sys_products_accounts',TRUE);
					$accountObj = &$accountViewObj->getModelObj();
					$accountViewObj->getMarkerArray($mainMarkerArray);
				}

				foreach ($this->activityArray as $activity => $value) {

					if ($value) {
						$currentPaymentActivity = array_search($activity, $activityVarsArray);

							// perform action
						switch($activity)	{
							case 'products_clear_basket':
								// Empties the shopping basket!
								$this->basket->clearBasket(TRUE);
								$bBasketEmpty = TRUE;
							break;
							case 'products_basket':
								if (count($this->activityArray) == 1) {
									$basket_tmpl = 'BASKET_TEMPLATE';
								}
							break;
							case 'products_overview':
								tx_div2007_alpha::load_noLinkExtCobj_fh001($this->pibase);	// TODO
								$basket_tmpl = 'BASKET_OVERVIEW_TEMPLATE';
							break;
							case 'products_redeem_gift': 	// this shall never be the only activity
								if (trim($TSFE->fe_user->user['username']) == '') {
									$basket_tmpl = 'BASKET_TEMPLATE_NOT_LOGGED_IN';
								} else {
									$uniqueId = t3lib_div::trimExplode ('-', $this->basket->recs['tt_products']['giftcode'], TRUE);
									$query='uid=\''.intval($uniqueId[0]).'\' AND crdate=\''.intval($uniqueId[1]).'\''.' AND NOT deleted' ;
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
										$cpArray = $TSFE->fe_user->getKey('ses','cp');
										$cpArray['gift']['amount'] += $creditpoints;
										$TSFE->fe_user->setKey('ses','cp',$cpArray);
									}
								}
							break;
							case 'products_info':
								// if (!$this->activityArray['products_payment'] && !$this->activityArray['products_finalize']) {
								tx_div2007_alpha::load_noLinkExtCobj_fh001($this->pibase); // TODO
								$basket_tmpl = 'BASKET_INFO_TEMPLATE';
								// }
							break;
							case 'products_payment':
								$orderUid = $this->getOrderUid();
								tx_div2007_alpha::load_noLinkExtCobj_fh001($this->pibase);	// TODO
								$pidagb = intval($this->conf['PIDagb']);
								$checkRequired = $infoViewObj->checkRequired('billing');
								if (!$checkRequired)	{
									$checkRequired = $infoViewObj->checkRequired('delivery');
								}
								$checkAllowed = $infoViewObj->checkAllowed();

								$paymentshippingObj = &t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
								if ($paymentshippingObj->useCreditcard ())	{
									$cardRequired = $cardObj->checkRequired();
								}

								if ($paymentshippingObj->useAccount())	{
									$accountRequired = $accountObj->checkRequired();
								}

								if ($this->conf['paymentActivity'] == 'payment' || $this->conf['paymentActivity'] == 'verify')	{
									$handleLib = $paymentshippingObj->getHandleLib('request');

									if (strpos($handleLib,'transactor') !== FALSE)	{
										// Payment Transactor
										require_once(t3lib_extMgm::extPath($handleLib) . 'lib/class.tx_' . $handleLib . '_api.php');
										tx_transactor_api::init($this->pibase, $this->cObj, $this->conf);
										$referenceId = tx_transactor_api::getReferenceUid(
											$handleLib,
											$this->basket->basketExtra['payment.']['handleLib.'],
											TT_PRODUCTS_EXTkey,
											$orderUid
										);
										$addQueryString = array();
										$excludeList = '';
										$linkParams = $this->urlObj->getLinkParams($excludeList,$addQueryString,TRUE);
										$paymentErrorMsg = tx_transactor_api::checkRequired(
											$referenceId,
											$this->basket->basketExtra['payment.']['handleLib'],
											$this->basket->basketExtra['payment.']['handleLib.'],
											TT_PRODUCTS_EXTkey,
											$this->basket->getCalculatedArray(),
											$this->conf['paymentActivity'],
											$pidArray,
											$linkParams,
											$this->basket->order['orderTrackingNo'],
											$orderUid,
											$cardRow
										);
									} else if (strpos($handleLib,'paymentlib') !== FALSE)	{
										require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_paymentlib.php');
										$paymentlib = t3lib_div::makeInstance('tx_ttproducts_paymentlib');
										$paymentlib->init($this->pibase, $basketView, $this->urlObj);
										$referenceId = $paymentlib->getReferenceUid();
										$paymentErrorMsg = $paymentlib->checkRequired($referenceId, $this->basket->basketExtra['payment.']['handleLib'], $this->basket->basketExtra['payment.']['handleLib.']);
									}
								}

								if (empty($checkRequired) && empty($checkAllowed) && empty($cardRequired) && empty($accountRequired) && empty($paymentErrorMsg) &&
									(empty($pidagb) ||
									$_REQUEST['recs']['personinfo']['agb'] || t3lib_div::_GET('products_payment') || $infoViewObj->infoArray['billing']['agb'])) {

									if ($this->conf['paymentActivity'] == 'payment' || $this->conf['paymentActivity'] == 'verify')	{
										$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] =
											$this->processPayment(
												$orderUid,
												$cardRow,
												$pidArray,
												$currentPaymentActivity,
												$bFinalize,
												$errorMessage
											);

									} else {
										$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
									}
									if (!$bFinalize)	{
										$paymentHTML = $basketView->getView($empty, 'PAYMENT', $infoViewObj, $this->activityArray['products_info'], FALSE, TRUE, 'BASKET_PAYMENT_TEMPLATE', $mainMarkerArray);
										$content .= $paymentHTML;
									}

									if (count($this->basket->itemArray))	{
										$orderObj = &$tablesObj->get('sys_products_orders');
										$orderObj->setData($orderUid, $paymentHTML, 0);
									}
								} else {	// If not all required info-fields are filled in, this is shown instead:
									$infoViewObj->infoArray['billing']['error'] = 1;
									$content .= $markerObj->replaceGlobalMarkers($this->cObj->getSubpart($this->templateCode,$this->subpartmarkerObj->spMarker('###BASKET_REQUIRED_INFO_MISSING###')));
									$label = '';
									$addQueryString = array();
									$overwriteMarkerArray = array();
									if ($pidagb && !$_REQUEST['recs']['personinfo']['agb'] && !t3lib_div::_GET('products_payment') && !$infoViewObj->infoArray['billing']['agb']) {
										 // so AGB has not been accepted
										$label = tx_div2007_alpha::getLL($langObj,'accept_AGB');
										$addQueryString['agb']=0;
									} else if ($checkRequired || $checkAllowed) {
										if (t3lib_extMgm::isLoaded('sr_feuser_register')) {
											$check = ($checkRequired ? $checkRequired: $checkAllowed);
											$label = $TSFE->sL('LLL:EXT:sr_feuser_register/pi1/locallang.xml:missing_'.$check);
											$editPID = $TSFE->tmpl->setup['plugin.']['tx_srfeuserregister_pi1.']['editPID'];

											if ($TSFE->loginUser && $editPID) {
												$addParams = array ('products_payment' => 1);
												$addParams = $this->urlObj->getLinkParams('',$addParams,TRUE);
												$srfeuserBackUrl = $this->pibase->pi_getPageLink($TSFE->id,'',$addParams);
												$srfeuserParams = array('tx_srfeuserregister_pi1[backURL]' => $srfeuserBackUrl);
												$addParams = $this->urlObj->getLinkParams('',$srfeuserParams,TRUE);
												$markerArray['###FORM_URL_INFO###'] = $this->pibase->pi_getPageLink($editPID,'',$addParams);
											}
										} else {
											$tmpArray = t3lib_div::trimExplode('|', tx_div2007_alpha::getLL($langObj,'missing'));
											$label = tx_div2007_alpha::getLL($langObj,'missing_'.$checkRequired);

											if ($label)	{
												$label = $tmpArray[0] .' '. $label . ' '. $tmpArray[1];
											} else {
												$label = 'field: '.$checkRequired;
											}
										}
									} else if ($cardRequired)	{
										$label = '*'.tx_div2007_alpha::getLL($langObj,$cardObj->getTablename().'.'.$cardRequired).'*';
									} else if ($accountRequired)	{
										$label = '*'.tx_div2007_alpha::getLL($langObj,$accountObj->getTablename()).': '.tx_div2007_alpha::getLL($langObj,$accountObj->getTablename().'.'.$accountRequired).'*';
									} else if ($paymentErrorMsg)	{
										$label = $paymentErrorMsg;
									} else {
										$message = tx_div2007_alpha::getLL($langObj,'internal_error');
										$messageArr = explode('|', $message);
										$label = $messageArr[0].'TTP_2'.$messageArr[1].'products_payment'.$messageArr[2];
									}
									$markerArray = $this->urlObj->addURLMarkers(0, array(),$addQueryString);
									$markerArray['###ERROR_DETAILS###'] = $label;
									$markerArray = array_merge($markerArray,$overwriteMarkerArray);
									$content = $this->cObj->substituteMarkerArray($content, $markerArray);
								}
							break;
							// a special step after payment and before finalization needed for some payment methods
							case 'products_customized_payment': // deprecated
							case 'products_verify':
								if ($this->conf['paymentActivity']=='verify' || $this->conf['paymentActivity']=='customized' /* deprecated */)	{

									$orderUid = $this->getOrderUid();
									$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] =
										$this->processPayment(
											$orderUid,
											$cardRow,
											$pidArray,
											$currentPaymentActivity,
											$bFinalize,
											$errorMessage
										);
									if (!$bFinalize)	{
										$basket_tmpl = 'BASKET_PAYMENT_TEMPLATE';
									}
								} else {
									$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
								}
							break;
							case 'products_finalize':
								$paymentshippingObj = &t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
								$handleLib = $paymentshippingObj->getHandleLib('request');
								if ($handleLib == '')	{
									$handleLib = $paymentshippingObj->getHandleLib('form');
								}
								if ($handleLib != '') {
									$orderUid = $this->getOrderUid();
									$rc = $this->processPayment(
										$orderUid,
										$cardRow,
										$pidArray,
										$currentPaymentActivity,
										$bFinalize,
										$errorMessage
									);

									if($bFinalize == FALSE ){
										$label = $errorMessage;
										$content = $this->cObj->getSubpart($this->templateCode,$this->subpartmarkerObj->spMarker('###BASKET_REQUIRED_INFO_MISSING###'));
										$markerArray['###ERROR_DETAILS###'] = $label;
										$content = $this->cObj->substituteMarkerArray($content, $markerArray);
									} else {
										$content = ''; // do not show the content of payment again
									}
								} else {
									$bFinalize = TRUE;
								}
							break;
							default:
								// nothing yet
								$activity = 'unknown';
							break;
						} // switch
					}	// if ($value)
					if ($bBasketEmpty)	{
						break;
					}

					if ($this->basket->checkMinPrice)	{
						$basketConf = $cnf->getBasketConf('minPrice'); // check the basket limits
						$bNeedsMinCheck = in_array($activity, array('products_info','products_payment', 'products_customized_payment',  'products_verify', 'products_finalize', 'unknown'));
						if ($bNeedsMinCheck && $basketConf['type'] == 'price')	{
							$value = $this->basket->calculatedArray['priceTax'][$basketConf['collect']];
							if (isset($value) && isset($basketConf['collect']) && $value < doubleval($basketConf['value']))	{
								$basket_tmpl = 'BASKET_TEMPLATE_MINPRICE_ERROR';
								$bFinalize = FALSE;
							}
						}
					}
						// in case the basket template has been set
					if ($basket_tmpl) {
						// basket view
						$content .= $basketView->getView(
							$empty,
							'BASKET',
							$infoViewObj,
							$this->activityArray['products_info'],
							FALSE,
							TRUE,
							$basket_tmpl,
							$mainMarkerArray
						);
						$bFinalize = FALSE;
						// stop here as soon as the first basket content has been drawn
						break; // foreach
					}
				} // foreach ($this->activityArray as $activity=>$value)

					// finalization at the end so that after every activity this can be called
				if ($bFinalize)	{
					$checkRequired = $infoViewObj->checkRequired('billing');

					if (!$checkRequired)	{
						$checkRequired = $infoViewObj->checkRequired('delivery');
					}

					$checkAllowed = $infoViewObj->checkAllowed();
					if ($checkRequired == '' && $checkAllowed == '')	{
						tx_div2007_alpha::load_noLinkExtCobj_fh001($this->pibase);	// TODO
						$handleScript = $TSFE->tmpl->getFileName($this->basket->basketExtra['payment.']['handleScript']);
						$orderObj = &$tablesObj->get('sys_products_orders');
						$orderUid = $this->getOrderUid();

						if (trim($this->conf['paymentActivity']) == 'finalize')	{
							$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] =
								$this->processPayment(
									$orderUid,
									$cardRow,
									$pidArray,
									'finalize',
									$bFinalize,
									$errorMessage
								);
						} else {
							$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
						}

						// Added Els4: to get the orderconfirmation template as html email and the thanks template as thanks page
						$tmpl = 'BASKET_ORDERCONFIRMATION_TEMPLATE';
						$orderConfirmationHTML = $basketView->getView($empty, 'BASKET', $infoViewObj, FALSE, FALSE, TRUE, $tmpl, $mainMarkerArray);

						include_once (PATH_BE_ttproducts.'control/class.tx_ttproducts_activity_finalize.php');

							// order finalization
						$activityFinalize = t3lib_div::makeInstance('tx_ttproducts_activity_finalize');
						$activityFinalize->init(
							$this->pibase,
							$orderObj
						);
						$activityFinalize->doProcessing(
							$this->templateCode,
							$mainMarkerArray,
							$this->funcTablename,
							$orderUid,
							$orderConfirmationHTML,
							$errorMessage,
							$infoViewObj,
							$mainMarkerArray
						);
								// Important: 	 MUST come after the call of prodObj->getView, because this function, getView, calculates the order! And that information is used in the finalize-function
						$contentTmp = $orderConfirmationHTML;

						if ($this->conf['PIDthanks'] > 0) {
							$tmpl = 'BASKET_ORDERTHANKS_TEMPLATE';
							$contentTmpThanks = $basketView->getView(
								$empty,
								'BASKET',
								$infoViewObj,
								FALSE,
								FALSE,
								TRUE,
								$tmpl,
								$mainMarkerArray
							);
							if ($contentTmpThanks != '')	{
								$contentTmp = $contentTmpThanks;
							}
						}
						if ($this->activityArray['products_payment'])	{	// forget the payment output from before if it comes to finalize
							$content = '';
						}
						$content .= $contentTmp;
						$contentNoSave = $basketView->getView(
							$empty,
							'BASKET',
							$infoViewObj,
							FALSE,
							FALSE,
							TRUE,
							'BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE',
							$mainMarkerArray
						);
						$content .= $contentNoSave;

						// Empties the shopping basket!
						$this->basket->clearBasket();
					} else {	// If not all required info-fields are filled in, this is shown instead:
						$requiredOut = $this->cObj->getSubpart(
							$this->templateCode,
							$this->subpartmarkerObj->spMarker('###BASKET_REQUIRED_INFO_MISSING###')
						);
						if (!$requiredOut) {
							$templateObj = &t3lib_div::getUserObj('&tx_ttproducts_template');
							$this->error_code[0] = 'no_subtemplate';
							$this->error_code[1] = '###BASKET_REQUIRED_INFO_MISSING###';
							$this->error_code[2] = $templateObj->getTemplateFile();
							return '';
						}

						$content .= $requiredOut;
						$content = $markerObj->replaceGlobalMarkers(
							$this->cObj->substituteMarkerArray(
								$content,
								$this->urlObj->addURLMarkers(0, array())
							)
						);
					}
				}
			} else { // if (count($this->activityArray))
				// nothing. no BASKET code or similar thing
			}
		} else { // if (count($this->basket->basketExt))
			$bBasketEmpty = TRUE;
		}

		$basketMarkerArray = array();
		if ($bBasketEmpty)	{
			$contentEmpty = '';
			if ($this->activityArray['products_overview']) {
				tx_div2007_alpha::load_noLinkExtCobj_fh001($this->pibase);	//
				$contentEmpty = $this->cObj->getSubpart(
					$this->templateCode,
					$this->subpartmarkerObj->spMarker('###BASKET_OVERVIEW_EMPTY' . $this->config['templateSuffix'] . '###')
				);

				if (!$contentEmpty)	{
					$contentEmpty = $this->cObj->getSubpart(
						$this->templateCode,
						$this->subpartmarkerObj->spMarker('###BASKET_OVERVIEW_EMPTY###')
					);
				}
			} else if ($this->activityArray['products_basket'] || $this->activityArray['products_info'] || $this->activityArray['products_payment']) {
				$contentEmpty = $this->cObj->getSubpart(
					$this->templateCode,
					$this->subpartmarkerObj->spMarker('###BASKET_TEMPLATE_EMPTY' . $this->config['templateSuffix'] . '###')
				);

				if (!$contentEmpty)	{
					$contentEmpty = $this->cObj->getSubpart(
						$this->templateCode,
						$this->subpartmarkerObj->spMarker('###BASKET_TEMPLATE_EMPTY###')
					);
				}
			} else if ($this->activityArray['products_finalize'])	{
				// Todo: Neuabsenden einer bereits abgesendeten Bestellung. Der Warenkorb ist schon gelöscht.
				if (!$this->basket->order)	{
					$contentEmpty = tx_div2007_alpha::getLL($langObj, 'order_already_finalized');
				}
			}
			if ($contentEmpty != '')	{

				$contentEmpty = $markerObj->replaceGlobalMarkers($contentEmpty);
			}
			$content .= $contentEmpty;
			$basketMarkerArray = $basketView->getMarkerArray();
		}
		$markerArray = $basketMarkerArray;
		$markerArray['###EXTERNAL_COBJECT###'] = $this->pibase->externalCObject;	// adding extra preprocessing CObject
		$content = $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	} //
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control.php']);
}


?>
