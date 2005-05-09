<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * class.tx_ttproducts.php
 *
 * Creates a list of products for the shopping basket in TYPO3.
 * Also controls basket, searching and payment.
 *
 * Typoscript config:
 * - See static_template "plugin.tt_products"
 * - See TSref.pdf
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Klaus Zierer <klaus@ziererk.de> / <zierer@pz-systeme.de>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 */


require_once(PATH_tslib."class.tslib_pibase.php");
require_once(PATH_t3lib."class.t3lib_parsehtml.php");
require_once(PATH_t3lib."class.t3lib_htmlmail.php");


class tx_ttproducts extends tslib_pibase {
	var $cObj;		// The backReference to the mother cObj object set at call time

	var $searchFieldList="title,note,itemnumber";

		// Internal
	var $pid_list="";
	var $basketExt=array();				// "Basket Extension" - holds extended attributes
	var $calculatedWeigth;				// - Sums of weigth of goods

	var $uid_list="";					// List of existing uid's from the basket, set by initBasket()
	var $categories=array();			// Is initialized with the categories of the shopping system
	var $pageArray=array();				// Is initialized with an array of the pages in the pid-list
	var $orderRecord = array();			// Will hold the order record if fetched.


		// Internal: init():
	var $templateCode="";				// In init(), set to the content of the templateFile. Used by default in getBasket()

		// Internal: initBasket():
	var $basket=array();				// initBasket() sets this array based on the registered items
	var $basketExtra;					// initBasket() uses this for additional information like the current payment/shipping methods
	var $recs = Array(); 				// in initBasket this is set to the recs-array of fe_user.
	var $personInfo;					// Set by initBasket to the billing address
	var $deliveryInfo; 					// Set by initBasket to the delivery address

		// Internal: Arrays from getBasket() function
	var $calculatedBasket;				// - The basked elements, how many (quantity, count) and the price and total
	var $calculatedSums_tax;			// - Sums of goods, shipping, payment and total amount WITH TAX included
	var $calculatedSums_no_tax;			// - Sums of goods, shipping, payment and total amount WITHOUT TAX

	var $config=array();
	var $conf=array();
	var $tt_product_single="";
	var $globalMarkerArray=array();
	var $externalCObject="";


	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	function main_products($content,$conf)	{
		global $TSFE;

		$TSFE->set_no_cache();

		// *************************************
		// *** getting configuration values:
		// *************************************

			// getting configuration values:
		$this->conf=$conf;
		$this->config["code"] = strtolower(trim($this->cObj->stdWrap($this->conf["code"],$this->conf["code."])));
		$this->config["limit"] = t3lib_div::intInRange($this->conf["limit"],0,1000);
		$this->config["limit"] = $this->config["limit"] ? $this->config["limit"] : 50;
		$this->config["limitImage"] = t3lib_div::intInRange($this->conf["limitImage"],0,9);
		$this->config["limitImage"] = $this->config["limitImage"] ? $this->config["limitImage"] : 1;

		$this->config["pid_list"] = trim($this->cObj->stdWrap($this->conf["pid_list"],$this->conf["pid_list."]));
		$this->config["pid_list"] = $this->config["pid_list"] ? $this->config["pid_list"] : $TSFE->id;

		$this->config["recursive"] = $this->cObj->stdWrap($this->conf["recursive"],$this->conf["recursive."]);
		$this->config["storeRootPid"] = $this->conf["PIDstoreRoot"] ? $this->conf["PIDstoreRoot"] : $TSFE->tmpl->rootLine[0][uid];
		$this->config["useCategories"] = $this->conf["useCategories"] ? $this->conf["useCategories"] : 1;
		$this->config["reseller"] = t3lib_div::intInRange($this->conf['reseller'],2,2);

			//extend standard search fields with user setup
		$this->searchFieldList = trim($this->conf["stdSearchFieldExt"]) ? implode(",", array_unique(t3lib_div::trimExplode(",",$this->searchFieldList.",".trim($this->conf["stdSearchFieldExt"]),1))) : $this->searchFieldList;

			// If the current record should be displayed.
		$this->config["displayCurrentRecord"] = $this->conf["displayCurrentRecord"];
		if ($this->config["displayCurrentRecord"])	{
			$this->config["code"]="SINGLE";
			$this->tt_product_single = true;
		} else {
			$this->tt_product_single = t3lib_div::_GET("tt_products");
		}

			// template file is fetched. The whole template file from which the various subpart are extracted.
		$this->templateCode = $this->cObj->fileResource($this->conf["templateFile"]);

			// globally substituted markers, fonts and colors.
		$splitMark = md5(microtime());
		$globalMarkerArray=array();
		list($globalMarkerArray["###GW1B###"],$globalMarkerArray["###GW1E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf["wrap1."]));
		list($globalMarkerArray["###GW2B###"],$globalMarkerArray["###GW2E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf["wrap2."]));
		$globalMarkerArray["###GC1###"] = $this->cObj->stdWrap($this->conf["color1"],$this->conf["color1."]);
		$globalMarkerArray["###GC2###"] = $this->cObj->stdWrap($this->conf["color2"],$this->conf["color2."]);
		$globalMarkerArray["###GC3###"] = $this->cObj->stdWrap($this->conf["color3"],$this->conf["color3."]);

		if ($this->conf["displayBasketColumns"])
		{
			$formUrl = $this->getLinkUrl($this->conf["PIDbasket"]);
			$globalMarkerArray["###FORM_URL###"]=$formUrl;
			$globalMarkerArray["###FORM_NAME###"]="ShopForm";
		}

			// Substitute Global Marker Array
		$this->templateCode= $this->cObj->substituteMarkerArrayCached($this->templateCode, $globalMarkerArray);


			// This cObject may be used to call a function which manipulates the shopping basket based on settings in an external order system. The output is included in the top of the order (HTML) on the basket-page.
		$this->externalCObject = $this->getExternalCObject("externalProcessing");

			// Initializes object
		$this->setPidlist($this->config["pid_list"]);				// The list of pid's we're operation on. All tt_products records must be in the pidlist in order to be selected.
		$this->TAXpercentage = doubleval($this->conf["TAXpercentage"]);		// Set the TAX percentage.
		$this->globalMarkerArray = $globalMarkerArray;

		$this->initCategories();

		$codes=t3lib_div::trimExplode(",", $this->config["code"]?$this->config["code"]:$this->conf["defaultCode"],1);
		if (!count($codes))     $codes=array("");

		while(list(,$theCode)=each($codes))
		{
			if (strtoupper($theCode)=="BASKET")
				$isBasket = 1;
			if (strtoupper($theCode)=="OVERVIEW")
				$this->isOverview = 1;
		}

		if (t3lib_div::_GP("mode_update") && ($isBasket || $this->config["useCategories"] == 1 ))
			$updateMode = 1;
		else
			$updateMode = 0;

		$this->initBasket($TSFE->fe_user->getKey("ses","recs"), $updateMode); // Must do this to initialize the basket...

		// *************************************
		// *** Listing items:
		// *************************************

		reset($codes);
		while(list(,$theCode)=each($codes))	{
			$theCode = (string)strtoupper(trim($theCode));

			switch($theCode)	{
				case "TRACKING":
					$content.=$this->products_tracking($theCode);
				break;
				case "BILL":
					$content.=$this->products_bill($theCode);
				break;
				case "DELIVERY":
					$content.=$this->products_delivery($theCode);
				break;
				case "BASKET":
				case "PAYMENT":
				case "FINALIZE":
				case "OVERVIEW":
				case "INFO":
					$content.=$this->products_basket($theCode);
				break;
				case "SEARCH":
				case "SINGLE":
				case "LIST":
				case "LISTOFFERS":
				case "LISTHIGHLIGHTS":
				case "LISTNEWITEMS":
					$content.=$this->products_display($theCode);
				break;
				case "MEMO":
					$content.=$this->memo_display($theCode);
				break;
				default:
					$langKey = strtoupper($TSFE->config["config"]["language"]);
					$helpTemplate = $this->cObj->fileResource("EXT:tt_products/pi/products_help.tmpl");

						// Get language version
					$helpTemplate_lang="";
					if ($langKey)	{$helpTemplate_lang = $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_".$langKey."###");}
					$helpTemplate = $helpTemplate_lang ? $helpTemplate_lang : $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_DEFAULT###");

						// Markers and substitution:
					$markerArray["###CODE###"] = $theCode;
					$markerArray["###PATH###"] = PATH_ttproducts;
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
		if ($this->conf[$mConfKey] && $this->conf[$mConfKey."."])	{
			$this->cObj->regObj = &$this;
			return $this->cObj->cObjGetSingle($this->conf[$mConfKey],$this->conf[$mConfKey."."],"/".$mConfKey."/")."";
		}
	}

	/**
	 * Order tracking
	 */
	function products_tracking($theCode)	{
		global $TSFE;

		$admin = $this->shopAdmin();
		if (t3lib_div::_GP("tracking") || $admin)	{		// Tracking number must be set
			$orderRow = $this->getOrderRecord("",t3lib_div::_GP("tracking"));
			if (is_array($orderRow) || $admin)	{		// If order is associated with tracking id.
				if (!is_array($orderRow))	$orderRow=array("uid"=>0);
				$content = $this->getTrackingInformation($orderRow,$this->templateCode);
			} else {	// ... else output error page
				$content=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###TRACKING_WRONG_NUMBER###"));
				if (!$TSFE->beUserLogin)	{$content = $this->cObj->substituteSubpart($content,"###ADMIN_CONTROL###","");}
			}
		} else {	// No tracking number - show form with tracking number
			$content=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###TRACKING_ENTER_NUMBER###"));
			if (!$TSFE->beUserLogin)	{$content = $this->cObj->substituteSubpart($content,"###ADMIN_CONTROL###","");}
		}
		$markerArray=array();
		$markerArray["###FORM_URL###"] = $this->getLinkUrl();	// Add FORM_URL to globalMarkerArray, linking to self.
		$content= $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	}

	/**
	 * Takes care of basket, address info, confirmation and gate to payment
	 */
	function products_basket($theCode)	{
		global $TSFE;

		$this->setPidlist($this->config["storeRootPid"]);	// Set list of page id's to the storeRootPid.
		$this->initRecursive(999);		// This add's all subpart ids to the pid_list based on the rootPid set in previous line
		$this->generatePageArray();		// Creates an array with page titles from the internal pid_list. Used for the display of category titles.

		if (count($this->basketExt))	{	// If there is content in the shopping basket, we are going display some basket code
				// prepare action
			$activity="";
			if (t3lib_div::_GP("products_info"))	{
				$activity="products_info";
			} elseif (t3lib_div::_GP("products_payment"))	{
				$activity="products_payment";
			} elseif (t3lib_div::_GP("products_finalize"))	{
				$activity="products_finalize";
			}

			if ($theCode=="INFO")	{
				$activity="products_info";
			} elseif ($theCode=="OVERVIEW") {
				$activity="products_overview";
			} elseif ($theCode=="PAYMENT")	{
				$activity="products_payment";
			} elseif ($theCode=="FINALIZE")	{
				$activity="products_finalize";
			}

				// perform action
			switch($activity)	{
				case "products_info":
					$this->load_noLinkExtCobj();
					$content.=$this->getBasket("###BASKET_INFO_TEMPLATE###");
				break;
				case "products_overview":
					$this->load_noLinkExtCobj();
					$content.=$this->getBasket("###BASKET_OVERVIEW_TEMPLATE###");
				break;
				case "products_payment":
					$this->load_noLinkExtCobj();
					$pidagb = intval($this->conf["PIDagb"]);
					if ($this->checkRequired() &&
						(empty($pidagb) || isset($_REQUEST["recs"]["personinfo"]["agb"]))) {
						$this->mapPersonIntoToDelivery();
						$content=$this->getBasket("###BASKET_PAYMENT_TEMPLATE###");
					} else {	// If not all required info-fields are filled in, this is shown instead:
						$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###BASKET_REQUIRED_INFO_MISSING###"));
						$content = $this->cObj->substituteMarkerArray($content, $this->addURLMarkers(array()));
					}
				break;
				case "products_finalize":
					if ($this->checkRequired())	{
						$this->load_noLinkExtCobj();
						$this->mapPersonIntoToDelivery();
						$handleScript = $TSFE->tmpl->getFileName($this->basketExtra["payment."]["handleScript"]);
						if ($handleScript)	{
							$content = $this->includeHandleScript($handleScript,$this->basketExtra["payment."]["handleScript."]);
						} else {
							$orderUid = $this->getBlankOrderUid();
							$content=$this->getBasket("###BASKET_ORDERCONFIRMATION_TEMPLATE###");
							$content.=$this->finalizeOrder($orderUid);	// Important: 	 MUST come after the call of prodObj->getBasket, because this function, getBasket, calculates the order! And that information is used in the finalize-function
						}
					} else {	// If not all required info-fields are filled in, this is shown instead:
						$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###BASKET_REQUIRED_INFO_MISSING###"));
						$content = $this->cObj->substituteMarkerArray($content, $this->addURLMarkers(array()));
					}
				break;
				default:
					$content.=$this->getBasket();
				break;
			}
		} else {
			if ($theCode=="OVERVIEW") {
				$this->load_noLinkExtCobj();
				$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###BASKET_OVERVIEW_EMPTY###"));
			}
			else {
				$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###BASKET_TEMPLATE_EMPTY###"));
			}
		}
		$markerArray=array();
		$markerArray["###EXTERNAL_COBJECT###"] = $this->externalCObject;	// adding extra preprocessing CObject
		$content= $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	}


	/**
	 * Order bill
	 */
	function products_bill($theCode)	{
		global $TSFE;

		$this->setPidlist($this->config["storeRootPid"]);	// Set list of page id's to the storeRootPid.
		$this->initRecursive(999);		// This add's all subpart ids to the pid_list based on the rootPid set in previous line
		$this->generatePageArray();		// Creates an array with page titles from the internal pid_list. Used for the display of category titles.

		$admin = $this->shopAdmin();
		if (t3lib_div::_GP("tracking") || $admin)	{		// Tracking number must be set
			$orderRow = $this->getOrderRecord("",t3lib_div::_GP("tracking"));
			if (is_array($orderRow) || $admin)	{		// If order is associated with tracking id.
				if (!is_array($orderRow))	$orderRow=array("uid"=>0);
				$content = $this->getInformation("bill",$orderRow,
				           $this->templateCode,t3lib_div::_GP("tracking"));
			} else {	// ... else output error page
				$content=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###TRACKING_WRONG_NUMBER###"));
				if (!$TSFE->beUserLogin)	{$content = $this->cObj->substituteSubpart($content,"###ADMIN_CONTROL###","");}
			}
		} else {	// No tracking number - show form with tracking number
			$content=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###TRACKING_ENTER_NUMBER###"));
			if (!$TSFE->beUserLogin)	{$content = $this->cObj->substituteSubpart($content,"###ADMIN_CONTROL###","");}
		}
		$markerArray=array();
		$markerArray["###FORM_URL###"] = $this->getLinkUrl();	// Add FORM_URL to globalMarkerArray, linking to self.
		$content= $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	}


	/**
	 * Order delivery
	 */
	function products_delivery($theCode)	{
		global $TSFE;

	    $this->mapPersonIntoToDelivery();	// This maps the billing address into the blank fields of the delivery address
		$this->setPidlist($this->config["storeRootPid"]);	// Set list of page id's to the storeRootPid.
		$this->initRecursive(999);		// This add's all subpart ids to the pid_list based on the rootPid set in previous line
		$this->generatePageArray();		// Creates an array with page titles from the internal pid_list. Used for the display of category titles.

		$admin = $this->shopAdmin();
		if (t3lib_div::_GP("tracking") || $admin)	{		// Tracking number must be set
			$orderRow = $this->getOrderRecord("",t3lib_div::_GP("tracking"));
			if (is_array($orderRow) || $admin)	{		// If order is associated with tracking id.
				if (!is_array($orderRow))	$orderRow=array("uid"=>0);
				$content = $this->getInformation("delivery",$orderRow,
				           $this->templateCode,t3lib_div::_GP("tracking"));
			} else {	// ... else output error page
				$content=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###TRACKING_WRONG_NUMBER###"));
				if (!$TSFE->beUserLogin)	{$content = $this->cObj->substituteSubpart($content,"###ADMIN_CONTROL###","");}
			}
		} else {	// No tracking number - show form with tracking number
			$content=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###TRACKING_ENTER_NUMBER###"));
			if (!$TSFE->beUserLogin)	{$content = $this->cObj->substituteSubpart($content,"###ADMIN_CONTROL###","");}
		}
		$markerArray=array();
		$markerArray["###FORM_URL###"] = $this->getLinkUrl();	// Add FORM_URL to globalMarkerArray, linking to self.
		$content= $this->cObj->substituteMarkerArray($content, $markerArray);

		return $content;
	}


include_once(PATH_tslib."class.tx_ttproducts1.inc");
include_once(PATH_tslib."class.tx_ttproducts2.inc");
include_once(PATH_tslib."class.tx_ttproducts3.inc");
include_once(PATH_tslib."class.tx_ttproducts4.inc");

}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tt_products/pi/class.tx_ttproducts.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tt_products/pi/class.tx_ttproducts.php"]);
}


?>
