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
 * productsLib.inc
 *
 * Creates a list of products for the shopping basket in TYPO3.
 * Also controls basket, searching and payment.
 *
 * TypoScript config:
 * - See static_template "plugin.tt_products"
 * - See TS_ref.pdf
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor René Fritz <r.fritz@colorcube.de>
 * improvements:
 * 2005 Franz Holzinger <kontakt@fholzinger.com>
 * 2005 Klaus Zierer    <klaus@ziererk.de / zierer@pz-systeme.de>:
 */


/**
 * changes:
 *
 * 12.9.2001
 *
 * added 'page browsing': <- 1 2 3 ->
 * - see ###LINK_BROWSE### and ###BROWSE_LINKS###
 * added ###ITEMS_SELECT_COUNT### for displaying the amount of the current available items (in this category or search)
 *
 * 13.9.2001 René Fritz
 *
 * added range check for $begin_at
 *
 * 14.9.2001 René Fritz
 * bugfix: with adding of page browsing 'orderby' was damaged
 *
 * 19.9.2001 René Fritz
 * changed counting select to 'select count(*)'
 *
 * 20.9.2001 René Fritz
 * new TS value 'stdSearchFieldExt' extends the search fields. Example: 'stdSearchFieldExt = note2,year'
 *
 * 26.03.2005 Franz Holzinger
 * bring in of zk_products by Klaus Zierer
 * display in the columns of a table
 * bill and delivery sheet
 * special price calculation
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
				// ZK+
				case "products_overview":
					$this->load_noLinkExtCobj();
					$content.=$this->getBasket("###BASKET_OVERVIEW_TEMPLATE###");
				break;
				// ZK-
				case "products_payment":
					$this->load_noLinkExtCobj();
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


	function load_noLinkExtCobj()	{
		if ($this->conf["externalProcessing_final"] || is_array($this->conf["externalProcessing_final."]))	{	// If there is given another cObject for the final order confirmation template!
			$this->externalCObject = $this->getExternalCObject("externalProcessing_final");
		}
	}

	/**
	 * Returning template subpart marker
	 */
	function spMarker($subpartMarker)	{
		$sPBody = substr($subpartMarker,3,-3);
		$altSPM = "";
		if (isset($this->conf["altMainMarkers."]))	{
			$altSPM = trim($this->cObj->stdWrap($this->conf["altMainMarkers."][$sPBody],$this->conf["altMainMarkers."][$sPBody."."]));
			$GLOBALS["TT"]->setTSlogMessage("Using alternative subpart marker for '".$subpartMarker."': ".$altSPM,1);
		}
		return $altSPM ? $altSPM : $subpartMarker;
	}


	function categorycomp($row1, $row2)  {
		return strcmp($this->categories[$row1['category']], $this->categories[$row2['category']]);
	}


	/**
	 * Displaying single products/ the products list / searching
	 */
	function products_display($theCode, $memoItems="")	{
		global $TSFE;

		$formUrl = $this->getLinkUrl($this->conf["PIDbasket"]);
		if ($this->tt_product_single)	{
	// List single product:
				// performing query:
			$this->setPidlist($this->config["storeRootPid"]);
			$this->initRecursive(999);
			$this->generatePageArray();

		 	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', 'uid='.intval($this->tt_product_single).' AND pid IN ('.$this->pid_list.')'.$this->cObj->enableFields('tt_products'));

			if($this->config["displayCurrentRecord"] || $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
				// set the title of the single view
				if($this->conf['substitutePagetitle']== 2) {
					$TSFE->page['title'] = $row['subtitle'] ? $row['subtitle'] : $row['title'];
				} elseif ($this->conf['substitutePagetitle']) {
					$TSFE->page['title'] = $row['title'];
				}

					// Get the subpart code
				$item ="";
				if ($this->config["displayCurrentRecord"])	{
					$row=$this->cObj->data;
					$item = trim($this->cObj->getSubpart($this->templateCode,$this->spMarker("###ITEM_SINGLE_DISPLAY_RECORDINSERT###")));
				}

				$catTitle= $this->pageArray[$row["pid"]]["title"].($row["category"]?"/".$this->categories[$row["category"]]:"");
				if (!$item)	{$item = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###ITEM_SINGLE_DISPLAY###"));}

					// Fill marker arrays
				$wrappedSubpartArray=array();
				$wrappedSubpartArray["###LINK_ITEM###"]= array('<A href="'.$this->getLinkUrl(t3lib_div::_GP("backPID")).'">','</A>');

				$markerArray = $this->getItemMarkerArray ($row,$catTitle,$this->config["limitImage"]);

				$markerArray["###FORM_NAME###"]="item_".$this->tt_product_single;
				$markerArray["###FORM_URL###"]=$formUrl;

				$url = $this->getLinkUrl("","tt_products");

				$resnext = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', 'uid > '.intval($this->tt_product_single).' AND pid IN ('.$TSFE->id .') AND (inStock <> 0)' . $this->cObj->enableFields('tt_products'));

				if ($rownext = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resnext) )
					$wrappedSubpartArray["###LINK_NEXT_SINGLE###"]=array('<A href="'.$url.'&tt_products='.$rownext["uid"].'">','</A>');
				else
					$subpartArray["###LINK_NEXT_SINGLE###"]="";

				$resprev = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', 'uid < '.intval($this->tt_product_single).' AND pid IN ('.$TSFE->id.') AND (inStock <>0)' . $this->cObj->enableFields('tt_products'));

				if ($rowprev = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resprev) )
					$wrappedSubpartArray["###LINK_PREV_SINGLE###"]=array('<A href="'.$url.'&tt_products='.$rowprev["uid"].'">','</A>');
				else
					$subpartArray["###LINK_PREV_SINGLE###"]="";

					// Substitute
				$content= $this->cObj->substituteMarkerArrayCached($item,$markerArray,array(),$wrappedSubpartArray);
			}
		} elseif ($theCode=="SINGLE") {
			$content.="Wrong parameters, GET/POST var 'tt_products' was missing.";
		} else {
			$content="";
	// List products:
			$where="";
			if ($theCode=="SEARCH")	{
					// Get search subpart
				$t["search"] = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###ITEM_SEARCH###"));
					// Substitute a few markers
				$out=$t["search"];
				if (!$this->conf["displayBasketColumns"])
				{
					$out=$this->cObj->substituteMarker($out, "###FORM_URL###", $this->getLinkUrl($this->conf["PIDsearch"]));
				}
				$out=$this->cObj->substituteMarker($out, "###SWORDS###", htmlspecialchars(t3lib_div::_GP("swords")));
					// Add to content
				$content.=$out;
				if (t3lib_div::_GP("swords"))	{
					$where = $this->searchWhere(trim(t3lib_div::_GP("swords")));
				}

				// if parameter "newitemdays" is specified, only new items from the last X days are displayed
				if (t3lib_div::_GP("newitemdays")) {
					$temptime = time() - 86400*intval(trim(t3lib_div::_GP("newitemdays")));
					$where = 'AND tstamp >= '.$temptime;
				}

			}

			if ($theCode=="LISTOFFERS")
				$where .= " AND offer";
			if ($theCode=="LISTHIGHLIGHTS")
				$where .= " AND highlight";
			if ($theCode=="LISTNEWITEMS") {
				$temptime = time() - 86400*intval(trim($this->conf["newItemDays"]));
				$where = 'AND tstamp >= '.$temptime;
			}
			if ($theCode=="MEMO") {
				if ($memoItems != "")
					$where = " AND uid IN ($memoItems)";
				else
					$where = " AND 1=0";
			}

			$begin_at=t3lib_div::intInRange(t3lib_div::_GP("begin_at"),0,100000);
			if (($theCode!="SEARCH" && !t3lib_div::_GP("swords")) || $where)	{

				$this->initRecursive($this->config["recursive"]);
				$this->generatePageArray();

					// Get products
				$selectConf = Array();
				$selectConf["pidInList"] = $this->pid_list;
				$selectConf["where"] = "1=1 AND (inStock<>0) ".$where;

					// performing query to count all products (we need to know it for browsing):
				$selectConf["selectFields"] = 'count(*)';
				$res = $this->cObj->exec_getQuery("tt_products",$selectConf);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$productsCount = $row[0];

					// range check to current productsCount
				$begin_at = t3lib_div::intInRange(($begin_at >= $productsCount)?($productsCount-$this->config["limit"]):$begin_at,0);

					// performing query for display:
				$selectConf['orderBy'] = ($this->conf['orderBy'] ? $this->conf['orderBy'] : 'pid,category,title');
				$selectConf['selectFields'] = '*';
				$selectConf['max'] = ($this->config['limit']+1);
				$selectConf['begin'] = $begin_at;

			 	$res = $this->cObj->exec_getQuery('tt_products',$selectConf);

				$productsArray=array();
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
					$productsArray[$row["pid"]][]=$row;
				}

				// ZK+
					// Getting various subparts we're going to use here:
				if ($memoItems != "")
					$t["listFrameWork"] = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###MEMO_TEMPLATE###"));
				else
					$t["listFrameWork"] = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###ITEM_LIST_TEMPLATE###"));
				// ZK-

				$t["categoryTitle"] = $this->cObj->getSubpart($t["listFrameWork"],"###ITEM_CATEGORY###");
				$t["itemFrameWork"] = $this->cObj->getSubpart($t["listFrameWork"],"###ITEM_LIST###");
				$t["item"] = $this->cObj->getSubpart($t["itemFrameWork"],"###ITEM_SINGLE###");

				$pageArr=explode(",",$this->pid_list);

				$currentP="";
				$out="";
				$iCount=0;
				$more=0;		// If set during this loop, the next-item is drawn
				while(list(,$v)=each($pageArr))	{
					if (is_array($productsArray[$v]))	{
						global $categories1;

						if ($this->conf['orderByCategoryTitle'] >= 1) { // category means it should be sorted by the category title in this case
							uasort ($productsArray[$v], array(&$this, 'categorycomp'));
						}

						reset($productsArray[$v]);
						$itemsOut="";
						$iColCount=0;
						while(list(,$row)=each($productsArray[$v]))	{
							$iColCount++;
							$iCount++;
							if ($iCount>$this->config["limit"])	{
								$more=1;
								break;
							}

							// max. number of columns reached?
							if ($iColCount > $this->conf["displayBasketColumns"])
							{
								$iColCount = 1; // restart in the first column
							}

								// Print Category Title
							if ($row["pid"]."_".$row["category"]!=$currentP)	{
								if ($itemsOut)	{
									$out.=$this->cObj->substituteSubpart($t["itemFrameWork"], "###ITEM_SINGLE###", $itemsOut);
								}
								$itemsOut="";			// Clear the item-code var

								$currentP = $row["pid"]."_".$row["category"];
								if ($where || $this->conf["displayListCatHeader"])	{
									$markerArray=array();
									$catTitle= $this->pageArray[$row["pid"]]["title"].($row["category"]?"/".$this->categories[$row["category"]]:"");
									$this->cObj->setCurrentVal($catTitle);
									$markerArray["###CATEGORY_TITLE###"]=$this->cObj->cObjGetSingle($this->conf["categoryHeader"],$this->conf["categoryHeader."], "categoryHeader");
									$out.= $this->cObj->substituteMarkerArray($t["categoryTitle"], $markerArray);
								}
							}

								// Print Item Title
							$wrappedSubpartArray=array();
							$wrappedSubpartArray["###LINK_ITEM###"]= array('<A href="'.$this->getLinkUrl($this->conf["PIDitemDisplay"]).'&tt_products='.$row["uid"].'">','</A>');
							$markerArray = $this->getItemMarkerArray ($row,$catTitle, $this->config["limitImage"],"listImage");

							if (!$this->conf["displayBasketColumns"])
							{
								$markerArray["###FORM_URL###"]=$formUrl; // Applied later as well.
								$markerArray["###FORM_NAME###"]="item_".$iCount;
							}
							else
							{
								$markerArray["###ITEM_SINGLE_PRE_HTML###"] = ($iColCount == 1 ? "<TR>" : "");
								$markerArray["###ITEM_SINGLE_POST_HTML###"] = ($iColCount == $this->conf["displayBasketColumns"] ? "</TR>" : "");
							}

							// ZK+
							$markerArray["###FORM_MEMO###"] = $this->getLinkUrl($this->conf["PIDmemo"]);
							// cuts note in list view
							if (strlen($markerArray["###PRODUCT_NOTE###"]) > $this->conf["max_note_length"])
								$markerArray["###PRODUCT_NOTE###"] = substr($markerArray["###PRODUCT_NOTE###"], 0, $this->conf["max_note_length"]) . "...";
							// ZK-

							$itemsOut.= $this->cObj->substituteMarkerArrayCached($t["item"],$markerArray,array(),$wrappedSubpartArray);
						}

						if ($this->conf["displayBasketColumns"] > 1) { // complete the last table row
							while ($iColCount++ < $this->conf["displayBasketColumns"]) {
								$itemsOut.= "<TD></TD>";
								$itemsOut.= ($iColCount == $this->conf["displayBasketColumns"] ? "</TR>" : "");
							}
						}

						if ($itemsOut)	{
							$out.=$this->cObj->substituteMarkerArrayCached($t["itemFrameWork"], array(), array("###ITEM_SINGLE###"=>$itemsOut));
						}
					}
				}
			}
			if ($out)	{
				// next / prev:
				$url = $this->getLinkUrl("","begin_at");
					// Reset:
				$subpartArray=array();
				$wrappedSubpartArray=array();
				$markerArray=array();

				if ($more)	{
					$next = ($begin_at+$this->config["limit"] > $productsCount) ? $productsCount-$this->config["limit"] : $begin_at+$this->config["limit"];
					$wrappedSubpartArray["###LINK_NEXT###"]=array('<A href="'.$url.'&begin_at='.$next.'">','</A>');
				} else {
					$subpartArray["###LINK_NEXT###"]="";
				}
				if ($begin_at)	{
					$prev = ($begin_at-$this->config["limit"] < 0) ? 0 : $begin_at-$this->config["limit"];
					$wrappedSubpartArray["###LINK_PREV###"]=array('<A href="'.$url.'&begin_at='.$prev.'">','</A>');
				} else {
					$subpartArray["###LINK_PREV###"]="";
				}
				if ($productsCount > $this->config["limit"] )	{ // there is more than one page, so let's browse
					$wrappedSubpartArray["###LINK_BROWSE###"]=array('',''); // <- this could be done better I think, or not?
					$markerArray["###BROWSE_LINKS###"]="";
					for ($i = 0 ; $i < ($productsCount/$this->config["limit"]); $i++) 	{
						if (($begin_at >= $i*$this->config["limit"]) && ($begin_at < $i*$this->config["limit"]+$this->config["limit"])) 	{
							$markerArray["###BROWSE_LINKS###"].= ' <b>'.(string)($i+1).'</b> ';
							//	you may use this if you want to link to the current page also
							//	$markerArray["###BROWSE_LINKS###"].= ' <A href="'.$url.'&begin_at='.(string)($i * $this->config["limit"]).'"><b>'.(string)($i+1).'</b></A> ';
						} else {
							$markerArray["###BROWSE_LINKS###"].= ' <A href="'.$url.'&begin_at='.(string)($i * $this->config["limit"]).'">'.(string)($i+1).'</A> ';
						}
					}
				} else {
					$subpartArray["###LINK_BROWSE###"]="";
				}

				$subpartArray["###ITEM_CATEGORY_AND_ITEMS###"]=$out;
				$markerArray["###FORM_URL###"]=$formUrl;      // Applied it here also...
				$markerArray["###ITEMS_SELECT_COUNT###"]=$productsCount;

				$content.= $this->cObj->substituteMarkerArrayCached($t["listFrameWork"],$markerArray,$subpartArray,$wrappedSubpartArray);
			} elseif ($where)	{
				$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###ITEM_SEARCH_EMPTY###"));
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
			$pid_list_arr = explode(",",$this->pid_list);
			$this->pid_list="";
			while(list(,$val)=each($pid_list_arr))	{
				$this->pid_list.=$val.",".$this->cObj->getTreeList($val,intval($recursive));
			}
			$this->pid_list = ereg_replace(",$","",$this->pid_list);
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
			$this->categories[$row["uid"]] = $row["title"];
		}
	}

	/**
	 * Generates an array, ->pageArray of the pagerecords from ->pid_list
	 */
	function generatePageArray()	{
			// Get pages (for category titles)
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid', 'pages', 'uid IN ('.$this->pid_list.')');
		$this->pageArray = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
			$this->pageArray[$row["uid"]] = $row;
		}
	}

	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 *
     * $basket is the TYPO3 default shopping basket array from ses-data
	 * // ZK++
	 */
	function initBasket($basket, $updateMode)	{
		global $TSFE;
		$this->recs = $basket;	// Sets it internally
		$this->basket=array();

		$tmpBasketExt = $TSFE->fe_user->getKey("ses","basketExt");

		if (is_array($tmpBasketExt))
			$this->basketExt = $tmpBasketExt;
		else
			$this->basketExt = array();

		$basketExtRaw = t3lib_div::_GP("ttp_basket");

		if ((!$this->isOverview) && is_array($basketExtRaw)) {

			while(list($uid,$basketItem)=each($basketExtRaw))       {
				if (t3lib_div::testInt($uid))   {
					if (!$updateMode)
					{
						$count=t3lib_div::intInRange($basketItem['quantity'],0,100000);
						$extVars = $basketItem['size'].";".$basketItem['color'];

						if ($this->config["useCategories"] == 1)
						{
							$this->basketExt[$uid][$extVars] = $count;
						}
						else
						{
							$this->basketExt[$uid][$extVars] = $this->basketExt[$uid][$extVars] + $count;
						}
					}
					else
					{
						reset($basketItem);
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
		while(list($tmpUid,$tmpSubArr)=each($this->basketExt))
			while(list($tmpExtVar,$tmpCount)=each($tmpSubArr))
				if ($tmpCount > 0)
					$basketExtNew[$tmpUid][$tmpExtVar] = $tmpCount;
		$this->basketExt = $basketExtNew;

		if (is_array($this->basketExt) && count($this->basketExt))
			$TSFE->fe_user->setKey("ses","basketExt",$this->basketExt);
		else
			$TSFE->fe_user->setKey("ses","basketExt",array());

		$this->setBasketExtras($basket);

		$this->personInfo = $basket["personinfo"];
		$this->deliveryInfo = $basket["delivery"];
		if ($TSFE->loginUser && (!$this->personInfo || $this->conf["lockLoginUserInfo"]))	{
			$address = "";

			if ($this->conf["loginUserInfoAddress"])
			{
				$address = implode(chr(10),
					t3lib_div::trimExplode(chr(10),
						$TSFE->fe_user->user["address"].chr(10).
						$TSFE->fe_user->user["zip"]." ".$GLOBALS["TSFE"]->fe_user->user["city"].chr(10).
						$TSFE->fe_user->user["country"]
						,1)
					);
			}
			else
			{
				$address = $TSFE->fe_user->user["address"];
			}

			$this->personInfo["name"] = $TSFE->fe_user->user["name"];

			$this->personInfo["address"] = $address;
			$this->personInfo["email"] = $TSFE->fe_user->user["email"];
			$this->personInfo["telephone"] = $TSFE->fe_user->user["telephone"];
			$this->personInfo["fax"] = $TSFE->fe_user->user["fax"];
			$this->personInfo["zip"] = $TSFE->fe_user->user["zip"];
			$this->personInfo["city"] = $TSFE->fe_user->user["city"];
			$this->personInfo["country"] = $TSFE->fe_user->user["country"];
			$this->personInfo["agb"] = $TSFE->fe_user->user["agb"];
		}
	}

	/**
	 * Check if payment/shipping option is available
	 */
	function checkExtraAvailable($name,$key)	{
		$result = false;

		if (is_array($this->conf[$name."."][$key."."]) && (!isset($this->conf[$name."."][$key."."]["show"]) || $this->conf[$name."."][$key."."]["show"]))	{
			$result = true;
		}

		return $result;
	}

	/**
	 * Setting shipping and payment methods
	 */
	function setBasketExtras($basket)	{
		global $TSFE;

			// shipping
		ksort($this->conf["shipping."]);
		reset($this->conf["shipping."]);
		$k=intval($basket["tt_products"]["shipping"]);
		if (!$this->checkExtraAvailable("shipping",$k))	{
			$k=intval(key($this->cleanConfArr($this->conf["shipping."],1)));
		}
		$this->basketExtra["shipping"] = $k;
		$this->basketExtra["shipping."] = $this->conf["shipping."][$k."."];
		$excludePayment = trim($this->basketExtra["shipping."]["excludePayment"]);

			// payment
		if ($excludePayment)	{
			$exclArr = t3lib_div::intExplode(",",$excludePayment);
			while(list(,$theVal)=each($exclArr))	{
				unset($this->conf["payment."][$theVal]);
				unset($this->conf["payment."][$theVal."."]);
			}
		}

		$confArr = $this->cleanConfArr($this->conf["payment."]);
		while(list($key,$val)=each($confArr))
			if ($val["show"] || !isset($val["show"]))
				if (($val["visibleForGroupID"] != "") &&
				    (!$this->isUserInGroup($TSFE->fe_user->user, $val["visibleForGroupID"])))
				{
					unset($this->conf["payment."][$key."."]);
				}

		ksort($this->conf["payment."]);
		reset($this->conf["payment."]);
		$k=intval($basket["tt_products"]["payment"]);
		if (!$this->checkExtraAvailable("payment",$k))	{
			$k=intval(key($this->cleanConfArr($this->conf["payment."],1)));
		}
		$this->basketExtra["payment"] = $k;
		$this->basketExtra["payment."] = $this->conf["payment."][$k."."];

		if($this->conf["pricecalc."])
		{
			ksort($this->conf["pricecalc."]);
			reset($this->conf["pricecalc."]);
			foreach ($this->conf["pricecalc."] as $k1 => $pricecalc)
			{
				$k=intval($k1);
				$this->basketExtra["pricecalc."][$k] = $this->conf["pricecalc."][$k1]["prod."];
			}
		}

		if($this->conf["discountprice."])
		{
			ksort($this->conf["discountprice."]);
			reset($this->conf["discountprice."]);
			foreach ($this->conf["discountprice."] as $k1 => $discountprice)
			{
				$k=intval($k1);
				if ($k == 0)
					continue;
				$this->basketExtra["discountprice."][$k] = $this->conf["discountprice."][$k1]["prod."];
			}
		}
	}

	/**
	 * Returns a clear 'recs[tt_products]' array - so clears the basket.
	 */
	function getClearBasketRecord()	{
			// Returns a basket-record cleared of tt_product items
		unset($this->recs["tt_products"]);
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
		$pid = intval($this->conf["PID_sys_products_orders"]);
		if (!$pid)	$pid = intval($TSFE->id);

		if ($TSFE->sys_page->getPage_noCheck ($pid))	{
			$advanceUid = 0;
			if ($this->conf["advanceOrderNumberWithInteger"])	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', '', '', 'uid DESC', '1');
				list($prevUid) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

				$rndParts = explode(",",$this->conf["advanceOrderNumberWithInteger"]);
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
	 * A finalized order is marked "not deleted" and with status=1.
	 * Returns this uid which is a blank order record uid.
	 */
	function getBlankOrderUid()	{
		global $TSFE;

		$orderUid = intval($this->recs["tt_products"]["orderUid"]);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', 'uid='.intval($orderUid).' AND deleted AND NOT status');	// Checks if record exists, is marked deleted (all blank orders are deleted by default) and is not finished.
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
			$orderUid = $this->createOrder();
			$this->recs["tt_products"]["orderUid"] = $orderUid;
			$this->recs["tt_products"]["orderDate"] = time();
			$this->recs["tt_products"]["orderTrackingNo"] = $this->getOrderNumber($orderUid)."-".strtolower(substr(md5(uniqid(time())),0,6));
			$TSFE->fe_user->setKey("ses","recs",$this->recs);
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
		$orderNumberPrefix = substr($this->conf["orderNumberPrefix"],0,10);
		if ($orderNumberPrefix[0]=="%")
			$orderNumberPrefix = date(substr($orderNumberPrefix, 1));
		return $orderNumberPrefix.$orderUid;
	}

	/**
	 * Finalize an order
	 *
	 * This finalizes an order by saving all the basket info in the current order_record.
	 * A finalized order is then marked "not deleted" and with status=1
	 * The basket is also emptied, but address info is preserved for any new orders.
	 * $orderUid is the order-uid to finalize
	 * $mainMarkerArray is optional and may be pre-prepared fields for substitutiong in the template.
	 */
	function finalizeOrder($orderUid,$mainMarkerArray=array())	{
		global $TSFE;
		global $TYPO3_DB;

			// Fix delivery address
		$this->mapPersonIntoToDelivery();	// This maps the billing address into the blank fields of the delivery address
		$mainMarkerArray["###EXTERNAL_COBJECT###"] = $this->externalCObject."";
		$orderConfirmationHTML=trim($this->getBasket("###BASKET_ORDERCONFIRMATION_TEMPLATE###","",$mainMarkerArray));		// Getting the template subpart for the order confirmation!

			// Saving order data
		$fieldsArray=array();
		$fieldsArray["note"]=$this->deliveryInfo["note"];
		$fieldsArray["name"]=$this->deliveryInfo["name"];
		$fieldsArray["telephone"]=$this->deliveryInfo["telephone"];
		$fieldsArray["fax"]=$this->deliveryInfo["fax"];
		$fieldsArray["email"]=$this->deliveryInfo["email"];
		$fieldsArray["email_notify"]=  $this->conf["email_notify_default"];		// Email notification is set here. Default email address is delivery email contact

			// can be changed after order is set.
		$fieldsArray["payment"]=$this->basketExtra["payment"].": ".$this->basketExtra["payment."]["title"];
		$fieldsArray["shipping"]=$this->basketExtra["shipping"].": ".$this->basketExtra["shipping."]["title"];
		$fieldsArray["amount"]=$this->calculatedSums_tax["total"];
		$fieldsArray["status"]=1;	// This means, "Order confirmed on website, next step: confirm from shop that order is received"

				// Default status_log entry
		$status_log=array();
		$status_log[] = array(
			"time" => time(),
			"info" => $this->conf["statusCodes."][$fieldsArray["status"]],
			"status" => $fieldsArray["status"],
			"comment" => $this->deliveryInfo["note"]
		);
		$fieldsArray["status_log"]=serialize($status_log);

			// Order Data serialized
		$fieldsArray["orderData"]=serialize(array(
				"html_output" 			=> $orderConfirmationHTML,
				"deliveryInfo" 			=> $this->deliveryInfo,
				"personInfo" 			=> $this->personInfo,
				"calculatedBasket"		=>	$this->calculatedBasket,
				"calculatedSum_tax"		=>	$this->calculatedSums_tax,
				"calculatedSums_no_tax"	=>	$this->calculatedSums_no_tax
		));

			// Setting tstamp, deleted and tracking code
		$fieldsArray["tstamp"]=time();
		$fieldsArray["deleted"]=0;
		$fieldsArray["tracking_code"]=$this->recs["tt_products"]["orderTrackingNo"];
		$fieldsArray["agb"]		= $this->personInfo["agb"];

			// Saving the order record
		$TYPO3_DB->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderUid), $fieldsArray);

			// Fetching the orderRecord by selecing the newly saved one...
		$this->orderRecord = $this->getOrderRecord($orderUid);
		$content .= $this->getBasket("###BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE###");

		// Is no user is logged in --> create one
		if ($this->conf["createUsers"] && ($this->personInfo["email"] != "") && ($this->conf["PIDuserFolder"]) && (trim($GLOBALS["TSFE"]->fe_user->user["username"]) == ""))
		{
			$username = strtolower(trim($this->personInfo["email"]));

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('username', 'fe_users', 'username='.$username . ' AND deleted=0');
			if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))
			{
				$this->password = substr(md5(rand()), 0, 6);

				$insertFields = array(
					'pid' => $this->conf["PIDuserFolder"],
					'tstamp' => time(),
					'username' => $username,
					'password' => $this->password,
					'usergroup' => $this->conf["memberOfGroup"],
					'name' => $this->personInfo["name"],
					'address' => $this->personInfo["address"],
					'telephone' => $this->personInfo["telephone"],
					'fax' => $this->personInfo["fax"],
					'email' => $this->personInfo["email"],
					'zip' => $this->personInfo["zip"],
					'city' => $this->personInfo["city"],
					'country' => $this->personInfo["country"],
					'crdate' => time()
				);
				$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('fe_users', $insertFields);

				// send new user mail
				if (count($this->personInfo["email"])) {
					$emailContent=trim($this->getBasket("###EMAIL_NEWUSER_TEMPLATE###"));
					if ($emailContent) {
						$parts = split(chr(10),$emailContent,2);
						$subject=trim($parts[0]);
						$plain_message=trim($parts[1]);

						$this->zk_mail($this->personInfo["email"], $subject, $plain_message, $this->conf["orderEmail_from"], $this->conf["orderEmail_fromName"]);
					}
				}
			}
		}


		// Reduce inStock
		reset($this->calculatedBasket);
		while(list(,$itemInfo)=each($this->calculatedBasket))   {
			$query="uid='".intval($itemInfo["rec"]["uid"])."';";

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('inStock', 'tt_products', $query);

			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if ($row['inStock'] > 0)
				{
					$newInStock = intval($row['inStock'])-intval($itemInfo["count"]);
					if ($newInStock < 0)
						$newInStock = 0;

					$fieldsArray =array();
								// Setting tstamp, deleted and tracking code
					$fieldsArray["inStock"]=$newInStock;

								// Saving the order record
					$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_products_orders', 'uid='.intval($itemInfo["rec"]["uid"]), $fieldsArray);

				}
			}
		}


			// Creates M-M relations for the products with tt_products table. Isn't really used yet, but later will be used to display stock-status by looking up how many items are already ordered.
			// First: delete any existing. Shouldn't be any
		$where="sys_products_orders_uid=".$orderUid;
		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_products_orders_mm_tt_products',$where);


			// Second: Insert a new relation for each ordered item
		reset($this->calculatedBasket);
		while(list(,$itemInfo)=each($this->calculatedBasket))	{
			$insertFields = array(
				'sys_products_orders_uid' => $orderUid,
				'sys_products_orders_qty' => intval($itemInfo['count']),
				'tt_products_uid' => intval($itemInfo['rec']['uid'])
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
		}


		// Generate CSV for each order
		if ($this->conf["generateCSV"])
		{
			$csvfilepath = $this->conf["CSVdestination"];
			if ($csvfilepath[strlen($csvfilepath)-1] != '/')
				$csvfilepath .= "/";
			$csvfilepath .= $this->getOrderNumber($this->recs["tt_products"]["orderUid"]).".csv";
			$csvfile = fopen($csvfilepath, "w");
			if ($csvfile !== FALSE)
			{
				// Generate invoice and delivery address
				$csvlinehead = "";
				$csvlineperson = "";
				$csvlinedelivery = "";
				$infoFields = explode(",","name,address,telephone,fax,email,company,city,zip,state,country,kk_fa,kk_nr,kk_ablauf,kk_pruefcode,agb");
				while(list(,$fName)=each($infoFields)) {
					if ($csvlinehead != '') {
						$csvlinehead .= ";";
						$csvlineperson .= ";";
						$csvlinedelivery .= ";";
					}
					$csvlinehead .= "\"" . $fName . "\"";
					$csvlineperson .= "\"" . $this->personInfo[$fName] . "\"";
					$csvlinedelivery .= "\"" . $this->deliveryInfo[$fName] . "\"";
				}

				// Generate shipping/payment information and delivery note
				$csvlineshipping = "\"" . $this->basketExtra["shipping."]["title"] . "\";\"" .
					$this->priceFormat($this->calculatedSums_tax["shipping"]) . "\";\"" .
					$this->priceFormat($this->calculatedSums_no_tax["shipping"]) . "\"";

				$csvlinepayment = "\"" . $this->basketExtra["payment."]["title"] . "\";\"" .
					$this->priceFormat($this->calculatedSums_tax["payment"]) . "\";\"" .
					$this->priceFormat($this->calculatedSums_no_tax["payment"]) . "\"";

				$csvlinedeliverynote = "\"".$this->deliveryInfo["note"]."\"";


				// Build field list
				$csvfields = explode(",", $this->conf["CSVfields"]);
				$csvfieldcount = count($csvfields);
				for ($a=0;$a<$csvfieldcount;$a++)
					$csvfields[$a] = trim($csvfields[$a]);

				// Write description header
				$csvdescr = '"uid";"count";"size";"color"';
				reset($csvfields);
				foreach($csvfields as $csvfield)
					$csvdescr .= ";\"".$csvfield."\"";
				if ($this->conf["CSVinOneLine"])
				{
					$csvdescr .= ";\"deliverynote\";\"shipping method\";\"shipping_price\";\"shipping_no_tax\";\"payment method\";\"payment_price\";\"payment_no_tax\"";
					$csvdescr .= ";".$csvlinehead.";".$csvlinehead;
				}
				$csvdescr .= "\n";
				fwrite($csvfile, $csvdescr);

				// Write ordered product list
				reset($this->calculatedBasket);
				$infoWritten = false;
				while(list(,$itemInfo)=each($this->calculatedBasket)) {
					$sizecolor = explode(";", $itemInfo["rec"]["extVars"]);
					$csvdata = "\"".intval($itemInfo["rec"]["uid"])."\";\"".
							intval($itemInfo["count"])."\";\"".
							$sizecolor[0]."\";\"".$sizecolor[1]."\"";
					reset($csvfields);
					foreach($csvfields as $csvfield)
						$csvdata .= ";\"".$itemInfo["rec"][$csvfield]."\"";
					if ($this->conf["CSVinOneLine"] && (!$infoWritten))
					{
						$infoWritten = true;
						$csvdata .= ";".$csvlinedeliverynote.";".$csvlineshipping.";".$csvlinepayment.";".$csvlineperson.";".$csvlinedelivery;
					}
					$csvdata .= "\n";
					fwrite($csvfile, $csvdata);
				}

				if (!$this->conf["CSVinOneLine"])
				{
					fwrite($csvfile, "\n");
					fwrite($csvfile, $csvlinehead . "\n");
					fwrite($csvfile, $csvlineperson . "\n");
					fwrite($csvfile, $csvlinedelivery . "\n");
					fwrite($csvfile, "\n");
					fwrite($csvfile, $csvlinedeliverynote. "\n");
					fwrite($csvfile, $csvlineshipping . "\n");
					fwrite($csvfile, $csvlinepayment . "\n");
				}

				fclose($csvfile);
			}
			else
				echo "Warning: Cannot create CSV file '".$csvfilepath."' for this order!";
		}

			// Sends order emails:
		$recipients = $this->conf["orderEmail_to"];
		$recipients.=",".$this->deliveryInfo["email"];
		$recipients=t3lib_div::trimExplode(",",$recipients,1);

		if (count($recipients))	{	// If any recipients, then compile and send the mail.
			$emailContent=trim($this->getBasket("###EMAIL_PLAINTEXT_TEMPLATE###"));
			if ($emailContent)	{		// If there is plain text content - which is required!!
				$parts = split(chr(10),$emailContent,2);		// First line is subject
				$subject=trim($parts[0]);
				$plain_message=trim($parts[1]);


				$cls  = t3lib_div::makeInstanceClassName("tt_products_htmlmail");
				if (class_exists($cls) && $this->conf["orderEmail_htmlmail"])	{	// If htmlmail lib is included, then generate a nice HTML-email
					$HTMLmailShell=$this->cObj->getSubpart($this->templateCode,"###EMAIL_HTML_SHELL###");
					$HTMLmailContent=$this->cObj->substituteMarker($HTMLmailShell,"###HTML_BODY###",$orderConfirmationHTML);
					$HTMLmailContent=$this->cObj->substituteMarkerArray($HTMLmailContent, $this->globalMarkerArray);


						// Remove image tags to products:
					if ($this->conf["orderEmail_htmlmail."]["removeImagesWithPrefix"])	{
						$parser = t3lib_div::makeInstance("t3lib_parsehtml");
						$htmlMailParts = $parser->splitTags("img",$HTMLmailContent);

						reset($htmlMailParts);
						while(list($kkk,$vvv)=each($htmlMailParts))	{
							if ($kkk%2)	{
								list($attrib) = $parser->get_tag_attributes($vvv);
								if (t3lib_div::isFirstPartOfStr($attrib["src"],$this->conf["orderEmail_htmlmail."]["removeImagesWithPrefix"]))	{
									$htmlMailParts[$kkk]="";
								}
							}
						}
						$HTMLmailContent=implode("",$htmlMailParts);
					}

					$V = array (
						"from_email" => $this->conf["orderEmail_from"],
						"from_name" => $this->conf["orderEmail_fromName"]
					);

					$Typo3_htmlmail = t3lib_div::makeInstance("tt_products_htmlmail");
					$Typo3_htmlmail->useBase64();
					$Typo3_htmlmail->start(implode($recipients,","), $subject, $plain_message, $HTMLmailContent, $V);
						$Typo3_htmlmail->sendtheMail();
				} else {		// ... else just plain text...
					// $headers variable überall entfernt!
					$this->zk_mail($this->deliveryInfo["email"], $subject, $plain_message, $this->conf["orderEmail_from"], $this->conf["orderEmail_fromName"], $this->conf["AGBattachment"]);
					if ($this->conf["generateCSV"])
						$addcsv = $csvfilepath;
					else
						$addcsv = "";
					$this->zk_mail($this->conf["orderEmail_to"], $subject, $plain_message, $this->deliveryInfo["email"], $this->deliveryInfo["name"], $addcsv);
				}
			}
		}

			// Empties the shopping basket!
		$TSFE->fe_user->setKey("ses","recs",$this->getClearBasketRecord());
		$TSFE->fe_user->setKey("ses","basketExt",array());

			// This cObject may be used to call a function which clears settings in an external order system.
			// The output is NOT included anywhere
		$this->getExternalCObject("externalFinalizing");

		return $content;
	}


	// **************************
	// Utility functions
	// **************************

	function isUserInGroup($feuser, $group)
	{
		$groups = explode(",", $feuser["usergroup"]);
		foreach ($groups as $singlegroup)
			if ($singlegroup == $group)
				return true;
		return false;
	}

	/**
	 * Returns the $price with either tax or not tax, based on if $tax is true or false. This function reads the TypoScript configuration to see whether prices in the database are entered with or without tax. That's why this function is needed.
	 */
	function getPrice($price,$tax=1)	{
		global $TSFE;

		if ($taxpercentage==0)
			$taxFactor = 1+$this->TAXpercentage/100;
		else
			$taxFactor = 1+$taxpercentage/100;

		if ($TSFE->fe_user->user["tt_products_discount"] != 0) {
			$price = $price - ($price * ($TSFE->fe_user->user["tt_products_discount"] / 100));
		}

		$taxIncluded = $this->conf["TAXincluded"];
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

	/**
	 * Takes an array with key/value pairs and returns it for use in an UPDATE query.
	 */
	function getUpdateQuery($Darray)	{
		reset($Darray);
		$query=array();
		while(list($field,$data)=each($Darray))	{
			$query[]=$field."='".addslashes($data)."'";
		}
		return implode($query,",");
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
	function getLinkUrl($id="",$excludeList="")	{
		global $TSFE;

		$queryString=array();
		$queryString["id"] = ($id ? $id : $TSFE->id);
		$queryString["type"]= $TSFE->type ? 'type='.$TSFE->type : "";
		$queryString["backPID"]= 'backPID='.$TSFE->id;
		$queryString["begin_at"]= t3lib_div::_GP("begin_at") ? 'begin_at='.t3lib_div::_GP("begin_at") : "";
		$queryString["swords"]= t3lib_div::_GP("swords") ? "swords=".rawurlencode(t3lib_div::_GP("swords")) : "";
		$queryString["newitemdays"]= t3lib_div::GPvar("newitemdays") ? "newitemdays=".rawurlencode(stripslashes(t3lib_div::GPvar("newitemdays"))) : "";

		reset($queryString);
		while(list($key,$val)=each($queryString))	{
			if (!$val || ($excludeList && t3lib_div::inList($excludeList,$key)))	{
				unset($queryString[$key]);
			}
		}
		return $TSFE->absRefPrefix.'index.php?'.implode($queryString,"&");
	}

	/**
	 * Formatting a price
	 */
	function priceFormat($double)	{
		return number_format($double,intval($this->conf["priceDec"]),$this->conf["priceDecPoint"],$this->conf["priceThousandPoint"]);
	}

	/**
	 * Fills in all empty fields in the delivery info array
	 */
	function mapPersonIntoToDelivery()	{
		$infoFields = explode(",","name,address,telephone,fax,email,company,city,zip,state,country");
		while(list(,$fName)=each($infoFields))	{
			if (!trim($this->deliveryInfo[$fName]))	{
				$this->deliveryInfo[$fName] = $this->personInfo[$fName];
			}
		}
	}

	/**
	 * Checks if required fields are filled in
	 */
	function checkRequired()	{
		$requiredInfoFields = trim($this->conf["requiredInfoFields"]);
		if ($this->basketExtra["payment."]["addRequiredInfoFields"] != "")
			$requiredInfoFields .= ",".trim($this->basketExtra["payment."]["addRequiredInfoFields"]);

		$flag=1;
		if (trim($this->conf["requiredInfoFields"]))	{
			$infoFields = t3lib_div::trimExplode(",",$this->conf["requiredInfoFields"]);
			while(list(,$fName)=each($infoFields))	{
				if (!trim($this->personInfo[$fName]))	{
					$flag=0;
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







	// **************************
	// Template marker substitution
	// **************************

	/**
	 * Fills in the markerArray with data for a product
	 */
	function getItemMarkerArray ($row,$catTitle, $imageNum=0, $imageRenderObj="image")	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row

		$markerArray=array();
			// Get image
		$theImgCode=array();

		$imgs = explode(",",$row["image"]);

		while(list($c,$val)=each($imgs))	{
			if ($c==$imageNum)	break;
			if ($val)	{
				$this->conf[$imageRenderObj."."]["file"] = "uploads/pics/".$val;
			} else {
				$this->conf[$imageRenderObj."."]["file"] = $this->conf["noImageAvailable"];
			}
			if (!$this->conf["separateImage"])
			{
				$c = 0;  // show all images together as one image
			}
			$theImgCode[$c] .= $this->cObj->IMAGE($this->conf[$imageRenderObj."."]);
		}

			// Subst. fields
		$markerArray["###PRODUCT_TITLE###"] = $row["title"];
		$markerArray["###PRODUCT_NOTE###"] = nl2br($row["note"]);
		if (is_array($this->conf["parseFunc."]))	{
			$markerArray["###PRODUCT_NOTE###"] = $this->cObj->parseFunc($markerArray["###PRODUCT_NOTE###"],$this->conf["parseFunc."]);
		}
		$markerArray["###PRODUCT_ITEMNUMBER###"] = $row["itemnumber"];

		$markerArray["###PRODUCT_IMAGE###"] = $theImgCode[0]; // for compatibility only

		while ((list($c,$val)=each($theImgCode)))
		{
			$markerArray["###PRODUCT_IMAGE" .  $c. "###"] = $theImgCode[$c];
		}

		$markerArray["###PRODUCT_SUBTITLE###"] = $row["subtitle"];
		$markerArray["###PRODUCT_WWW###"] = $row["www"];
		$markerArray["###PRODUCT_ID###"] = $row["uid"];
		$markerArray["###PRICE_TAX###"] = $this->printPrice($this->priceFormat($this->getPrice($row["price"],1,$row["tax"])));
		$markerArray["###PRICE_NO_TAX###"] = $this->printPrice($this->priceFormat($this->getPrice($row["price"],0,$row["tax"])));

		if ($row["inStock"] < 0) {
			$markerArray["###PRODUCT_INSTOCK###"] = $this->conf["alwaysInStockMessage"];
			$markerArray["###PRODUCT_INSTOCK_UNIT###"] = "";
		}
		else {
			$markerArray["###PRODUCT_INSTOCK###"] = $row["inStock"];
			$markerArray["###PRODUCT_INSTOCK_UNIT###"] = $this->conf["inStockPieces"];
		}

		$markerArray["###CATEGORY_TITLE###"] = $catTitle;

		$markerArray["###FIELD_NAME###"]="ttp_basket[".$row["uid"]."][quantity]";

//		$markerArray["###FIELD_NAME###"]="recs[tt_products][".$row["uid"]."]";

		$markerArray["###FIELD_QTY###"]= $this->basketExt[$row["uid"]][$row["size"].";".$row["color"]] ? $this->basketExt[$row["uid"]][$row["size"].";".$row["color"]] : "";
		$markerArray["###FIELD_NAME_BASKET###"]="ttp_basket[".$row["uid"]."][".md5($row["extVars"])."]";

		$markerArray["###FIELD_SIZE_NAME###"]="ttp_basket[".$row["uid"]."][size]";
		$markerArray["###FIELD_SIZE_VALUE###"]=$row["size"];
		$markerArray["###FIELD_COLOR_NAME###"]="ttp_basket[".$row["uid"]."][color]";
		$markerArray["###FIELD_COLOR_VALUE###"]=$row["color"];

		$prodColorText = '';
		$prodColTmp = explode(';', $row["color"]);
		foreach ($prodColTmp as $prodCol)
			$prodColorText = $prodColorText . '<OPTION value="'.$prodCol.'">'.$prodCol.'</OPTION>';

		$prodSizeText = '';
		$prodSizeTmp = explode(';', $row["size"]);
		foreach ($prodSizeTmp as $prodSize)
			$prodSizeText = $prodSizeText . '<OPTION value="'.$prodSize.'">'.$prodSize.'</OPTION>';

		$markerArray["###PRODUCT_WEIGHT###"] = doubleval($row["weight"]);
		$markerArray["###BULKILY_WARNING###"] = $row["bulkily"] ? $this->conf["bulkilyWarning"] : "";
		$markerArray["###PRODUCT_COLOR###"] = $prodColorText;
		$markerArray["###PRODUCT_SIZE###"] = $prodSizeText;

		if ($row["special_preparation"])
			$markerArray["###PRODUCT_SPECIAL_PREP###"] = $this->cObj->substituteMarkerArray($this->conf["specialPreparation"],$markerArray);
		else
			$markerArray["###PRODUCT_SPECIAL_PREP###"] = "";
			// Fill the Currency Symbol or not
		if ($this->conf["showcurSymbol"]) {
			$markerArray["###CUR_SYM###"] = " ".$this->conf["currencySymbol"];
		} else {
			$markerArray["###CUR_SYM###"] = "";
		}

		if ($this->conf["itemMarkerArrayFunc"])	{
			$markerArray = $this->userProcess("itemMarkerArrayFunc",$markerArray);
		}

		return $markerArray;
	}

	/**
	 * Calls user function
	 */
	function userProcess($mConfKey,$passVar)	{
		global $TSFE;

		if ($this->conf[$mConfKey])	{
			$funcConf = $this->conf[$mConfKey."."];
			$funcConf["parentObj"]=&$this;
			$passVar = $TSFE->cObj->callUserFunction($this->conf[$mConfKey], $funcConf, $passVar);
		}
		return $passVar;
	}

	/**
	 * Adds URL markers to a markerArray
	 */
	function addURLMarkers($markerArray)	{
			// Add's URL-markers to the $markerArray and returns it
		$markerArray["###FORM_URL###"] = $this->getLinkUrl($this->conf["PIDbasket"]);
		$markerArray["###FORM_URL_INFO###"] = $this->getLinkUrl($this->conf["PIDinfo"] ? $this->conf["PIDinfo"] : $this->conf["PIDbasket"]);
		$markerArray["###FORM_URL_FINALIZE###"] = $this->getLinkUrl($this->conf["PIDfinalize"] ? $this->conf["PIDfinalize"] : $this->conf["PIDbasket"]);
		$markerArray["###FORM_URL_THANKS###"] = $this->getLinkUrl($this->conf["PIDthanks"] ? $this->conf["PIDthanks"] : $this->conf["PIDbasket"]);
		$markerArray["###FORM_URL_TARGET###"] = "_self";
		if ($this->basketExtra["payment."]["handleURL"])	{	// This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
			$markerArray["###FORM_URL_THANKS###"] = $this->basketExtra["payment."]["handleURL"];
		}
		if ($this->basketExtra["payment."]["handleTarget"])	{	// Alternative target
			$markerArray["###FORM_URL_TARGET###"] = $this->basketExtra["payment."]["handleTarget"];
		}
		return $markerArray;
	}

	/**
	 * Generates a radio or selector box for payment shipping
	 */
	function generateRadioSelect($key, $countTotal=0)	{
			/*
			 The conf-array for the payment/shipping configuration has numeric keys for the elements
			 But there are also these properties:

			 	.radio 		[boolean]	Enables radiobuttons instead of the default, selector-boxes
			 	.wrap 		[string]	<select>|</select> - wrap for the selectorboxes.  Only if .radio is false. See default value below
			 	.template	[string]	Template string for the display of radiobuttons.  Only if .radio is true. See default below

			 */
		$type=$this->conf[$key."."]["radio"];
		$active = $this->basketExtra[$key];
		$confArr = $this->cleanConfArr($this->conf[$key."."]);
		$out="";

		$template = $this->conf[$key."."]["template"] ? $this->conf[$key."."]["template"] : '<nobr>###IMAGE### <input type="radio" name="recs[tt_products]['.$key.']" onClick="submit()" value="###VALUE###"###CHECKED###> ###TITLE###</nobr><BR>';
		$wrap = $this->conf[$key."."]["wrap"] ? $this->conf[$key."."]["wrap"] :'<select name="recs[tt_products]['.$key.']" onChange="submit()">|</select>';

		while(list($key,$val)=each($confArr))	{
			if (($val["show"] || !isset($val["show"])) &&
				(doubleval($val["showLimit"]) >= doubleval($countTotal) || !isset($val["showLimit"]) ||
				 intval($val["showLimit"]) == 0)) {
				if ($type)	{	// radio
					$markerArray=array();
					$markerArray["###VALUE###"]=intval($key);
					$markerArray["###CHECKED###"]=(intval($key)==$active?" checked":"");
					$markerArray["###TITLE###"]=$val["title"];
					$markerArray["###IMAGE###"]=$this->cObj->IMAGE($val["image."]);
					$out.=$this->cObj->substituteMarkerArrayCached($template, $markerArray);
				} else {
					$out.='<option value="'.intval($key).'"'.(intval($key)==$active?" selected":"").'>'.htmlspecialchars($val["title"]).'</option>';
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
				if (!t3lib_div::testInt($key) && intval($key) && is_array($val) && (!$checkShow || $val["show"] || !isset($val["show"])))	{
					$outArr[intval($key)]=$val;
				}
			}
		}
		ksort($outArr);
		reset($outArr);
		return $outArr;
	}



	function GetCalculatedData(
			&$priceCalc, &$discountPrice,
			&$priceItemTax, &$countItem,
			&$priceShippingTax, &$countTotal)
	{
		global $TSFE;

		$inGroup = 0;

		$gr_list = explode (',' , $TSFE->gr_list);

		while (list(,$val) = each ($gr_list))
		{
			if ((intval($val) > 0) && ($inGroup == 0))
			{
				$inGroup = 1 - strcmp($TSFE->fe_user->groupData->title, $this->conf["discountGroupName "] );

				if (strlen($TSFE->fe_user->groupData['title']) == 0)
					$inGroup = 0;
			}
		}

		$this->calculatedSums_tax["goodstotal"] = 0;
		$priceTotal = array();
		$priceReduction = array();
		$countTotal = 0;

		ksort($discountPrice);
		reset($discountPrice);

		// Check if a special group price can be used
		if (($inGroup == 1) && ($discountPrice != NULL))
		{
			while (list ($k1, $price1) = each ($discountPrice))
			{
				$dumCount = 0;
				reset($countItem);
				while (list ($k2, $count2) = each ($countItem))
				{
					if ((intval($count2) > 0) &&
						(doubleval($priceItemTax[$k2]) == doubleval($price1["1"])))
					{
						$dumCount += $count2;
					}
				}

				$countTotal += $dumCount;

				$priceCalcTemp = $discountPrice[$k1];
				krsort($priceCalcTemp);
				reset($priceCalcTemp);

				if ($this->conf["discountprice."]["additive"] == 0)
				{
					while (list ($k2, $price2) = each ($priceCalcTemp))
					{
						if ($dumCount >= intval($k2)) // only the highest value for this count will be used; 1 should never be reached, this would not be logical
						{
							$priceTotal [$k1] = doubleval($price2) * $dumCount;
							if (intval($k2) > 1)
							{
								$priceReduction[$k1] = 1; // remember the reduction in order not to calculate another price with $priceCalc
							}
							else
							{
								$priceReduction[$k1] = 0;
							}
							break; // finish
						}
					}
				}
				else
				{
					$priceTotal [$k1] = $dumCount; // save only the product count
				}
			}
			if ($this->conf["discountprice."]["additive"] == 1)
			{
				$newCountTotal = $countTotal;

				reset($discountPrice);
				while (list ($k1, $price1) = each ($discountPrice))
				{
					$priceCalcTemp = $discountPrice[$k1];
					krsort($priceCalcTemp);
					reset($priceCalcTemp);
					while (list ($k2, $price2) = each ($priceCalcTemp))
					{
						if ($countTotal >= intval($k2)) // search the price from the total count
						{
							if (intval($k2) > 1)
							{
								$priceTotal [$k1] = doubleval($price2) * $priceTotal [$k1];

								$priceReduction[$k1] = 1; // remember the reduction in order not to calculate another price with $priceCalc
							}
							else
							{	// $priceTotal [$k1] contains the product count
								$priceReduction[$k1] = 0;
								$newCountTotal -= $priceTotal [$k1];
							}
							break; // finish
						}
					}
				}
				$countTotal = $newCountTotal;
			}
			else
			{	// nothing
			}
		}

		ksort($priceCalc);
		reset($priceCalc);

		while (list ($k1, $price1) = each ($priceCalc))
		{
			// has the price already been calculated before ?
			if ($priceReduction[$k1] == 1) {
				$this->calculatedSums_tax["goodstotal"] += $priceTotal [$k1];
				continue;
			}
		    $priceTotal[$k1] = 0;

		    $dumCount = 0;

			reset($countItem);
		    while (list ($k2, $count2) = each ($countItem))
			{
				if ((intval($count2) > 0) &&
					(doubleval($priceItemTax[$k2]) == doubleval($price1["1"])))
				{
					$dumCount += $count2;
				}
			}

			$countTotal += $dumCount;

			$priceCalcTemp = $priceCalc[$k1];
			krsort($priceCalcTemp);
			reset($priceCalcTemp);

			$priceCalcTemp = $priceCalc[$k1];
			krsort($priceCalcTemp);
			reset($priceCalcTemp);
			while (list ($k2, $price2) = each ($priceCalcTemp))
			{
				if (intval($k2) > 0)
				{
					while ($dumCount >= intval($k2))
					{
						$dumCount -= intval($k2);
						$priceTotal [$k1] += doubleval($price2);
					}
				}
			}

			$this->calculatedSums_tax["goodstotal"] += $priceTotal [$k1];
		}
		$this->calculatedSums_no_tax["goodstotal"] = $this->calculatedSums_tax["goodstotal"];

		$this->calculatedSums_tax["total"] = $this->calculatedSums_tax["goodstotal"];

		$this->calculatedSums_tax["payment"] = 0;

		$priceSingleShippingTax = 0;
		krsort($this->basketExtra["shipping."]["priceTax."]);
		reset($this->basketExtra["shipping."]["priceTax."]);

		while (list ($k1, $price1) = each ($this->basketExtra["shipping."]["priceTax."]))
		{
			if ($countTotal >= intval($k1))
			{
				$priceShippingTax = $price1;
				break;
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


	/**
	 * This generates the shopping basket layout and also calculates the totals. Very important function.
	 */
	function getBasket($subpartMarker="###BASKET_TEMPLATE###", $templateCode="", 		$mainMarkerArray=array())	{
			/*
				Very central function in the library.
				By default it extracts the subpart, ###BASKET_TEMPLATE###, from the $templateCode (if given, else the default $this->templateCode)
				and substitutes a lot of fields and subparts.
				Any pre-preparred fields can be set in $mainMarkerArray, which is substituted in the subpart before the item-and-categories part is substituted.

				This function also calculates the internal arrays

				$this->calculatedBasket		- The basked elements, how many (quantity, count) and the price and total
				$this->calculatedSums_tax		- Sums of goods, shipping, payment and total amount WITH TAX included
				$this->calculatedSums_no_tax	- Sums of goods, shipping, payment and total amount WITHOUT TAX

				... which holds the total amount, the final list of products and the price of payment and shipping!!

			*/

		global $TSFE;
		$templateCode = $templateCode ? $templateCode : $this->templateCode;
		$this->calculatedBasket = array();		// array that holds the final list of items, shipping and payment + total amounts

		// ZK+
		$this->calculatedSums_number["goodstotal"] = 0;

		$uidArr = array();
		reset($this->basketExt);
		while(list($uidTmp,)=each($this->basketExt))
			if (!in_array($uidTmp, $uidArr))
				$uidArr[] = $uidTmp;

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', 'uid IN ('.implode(',',$uidArr).') AND pid IN ('.$this->pid_list.')'.$this->cObj->enableFields('tt_products'));
		// ZK-

		$productsArray = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
			// ZK+
			reset($this->basketExt[$row["uid"]]);
			while(list($bextVars,)=each($this->basketExt[$row["uid"]])) {
				$groesseFarbe = explode(";", $bextVars);
				$row["size"] = $groesseFarbe[0];
				$row["color"] = $groesseFarbe[1];
				$row["extVars"] = $bextVars;
				$productsArray[$row["pid"]][]=$row;
						// Fills this array with the product records. Reason: Sorting them by category (based on the page, they reside on)
			}
				// ZK-
		}

			// Getting subparts from the template code.
		$t=array();
			// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
		$t["basketFrameWork"] = $this->cObj->getSubpart($templateCode,$this->spMarker($subpartMarker));
		if (trim($this->cObj->getSubpart($t["basketFrameWork"],"###BILLING_ADDRESS_LOGIN###")))	{
			if ($GLOBALS["TSFE"]->loginUser)	{
				$t["basketFrameWork"] = $this->cObj->substituteSubpart($t["basketFrameWork"], "###BILLING_ADDRESS###", "");
			} else {
				$t["basketFrameWork"] = $this->cObj->substituteSubpart($t["basketFrameWork"], "###BILLING_ADDRESS_LOGIN###", "");
			}
		}

		$t["categoryTitle"] = $this->cObj->getSubpart($t["basketFrameWork"],"###ITEM_CATEGORY###");
		$t["itemFrameWork"] = $this->cObj->getSubpart($t["basketFrameWork"],"###ITEM_LIST###");
		$t["item"] = $this->cObj->getSubpart($t["itemFrameWork"],"###ITEM_SINGLE###");

		$pageArr=explode(",",$this->pid_list);
		$currentP="";
		$out="";

			// Initialize traversing the items in the basket
		$this->calculatedSums_tax=array();
		$this->calculatedSums_no_tax=array();

		$itemPrice = array ();
		$itemCount = array ();

		while(list(,$v)=each($pageArr))	{
			if (is_array($productsArray[$v]))	{
				reset($productsArray[$v]);
				$itemsOut="";
				while(list(,$row)=each($productsArray[$v]))	{
						// Print Category Title
					if ($row["pid"]."_".$row["category"]!=$currentP)	{
						if ($itemsOut)	{
							$out.=$this->cObj->substituteSubpart($t["itemFrameWork"], "###ITEM_SINGLE###", $itemsOut);
						}
						$itemsOut="";			// Clear the item-code var
						$currentP = $row["pid"]."_".$row["category"];
						if ($this->conf["displayBasketCatHeader"])	{
							$markerArray=array();
							$catTitle= $this->pageArray[$row["pid"]]["title"].($row["category"]?"/".$this->categories[$row["category"]]:"");
							$this->cObj->setCurrentVal($catTitle);
							$markerArray["###CATEGORY_TITLE###"]=$this->cObj->cObjGetSingle($this->conf["categoryHeader"],$this->conf["categoryHeader."], "categoryHeader");
							$out.= $this->cObj->substituteMarkerArray($t["categoryTitle"], $markerArray);
						}
					}

						// Fill marker arrays
					$wrappedSubpartArray=array();
					$markerArray = $this->getItemMarkerArray ($row,$catTitle,1,"basketImage");

					$calculatedBasketItem = array(
						"itemnumber" => intval($row["itemnumber"]),
						"priceTax" => $this->getPrice($row["price"],1,$row["tax"]),
						"priceNoTax" => $this->getPrice($row["price"],0,$row["tax"]),
						"weight" => $row["weight"],
						"count" => intval($this->basketExt[$row["uid"]][$row["size"].";".$row["color"]]),
						"rec" => $row
					);

					$itemPrice[$calculatedBasketItem["itemnumber"]] = $calculatedBasketItem["priceTax"];
					$itemCount[$calculatedBasketItem["itemnumber"]] = $calculatedBasketItem["count"];

					$preis[$calculatedBasketItem["itemnumber"]] =
							$calculatedBasketItem["priceTax"]; // FHO
					$anzahl[$calculatedBasketItem["itemnumber"]] =
							$calculatedBasketItem["count"]; // FHO

					$calculatedBasketItem["totalTax"] = $calculatedBasketItem["priceTax"]*$calculatedBasketItem["count"];
					$calculatedBasketItem["totalNoTax"] = $calculatedBasketItem["priceNoTax"]*$calculatedBasketItem["count"];
					$calculatedBasketItem["totalWeight"] = $calculatedBasketItem["weight"]*$calculatedBasketItem["count"];
					$this->calculatedWeight+= $calculatedBasketItem["totalWeight"];
					$this->calculatedSums_number["goodstotal"] += $calculatedBasketItem["count"];

					$markerArray["###PRICE_TOTAL_TAX###"]=$this->priceFormat($calculatedBasketItem["totalTax"]);
					$markerArray["###PRICE_TOTAL_NO_TAX###"]=$this->priceFormat($calculatedBasketItem["totalNoTax"]);

					$wrappedSubpartArray["###LINK_ITEM###"]=array('<A href="'.$this->getLinkUrl($this->conf["PIDitemDisplay"]).'&tt_products='.$row["uid"].'">','</A>');
						// Substitute
					$itemsOut.= $this->cObj->substituteMarkerArrayCached($t["item"],$markerArray,array(),$wrappedSubpartArray);

					$this->calculatedSums_tax["goodstotal"]+= $calculatedBasketItem["totalTax"];
					$this->calculatedSums_no_tax["goodstotal"]+= $calculatedBasketItem["totalNoTax"];
					$this->calculatedBasket[] = $calculatedBasketItem;
				}
				if ($itemsOut)	{
					$out.=$this->cObj->substituteSubpart($t["itemFrameWork"], "###ITEM_SINGLE###", $itemsOut);
				}
			}
		}

		if ($this->conf["pricecalc."] || $this->conf["discountprice."])
		{
			$this->GetCalculatedData(
			$this->basketExtra["pricecalc."],
			$this->basketExtra["discountprice."],
			$itemPrice, $itemCount, $priceShippingTax,
			$countTotal);
		}

			// Initializing the markerArray for the rest of the template
		$markerArray=$mainMarkerArray;

			// This is the total for the goods in the basket.
		$markerArray["###PRICE_GOODSTOTAL_TAX###"] = $this->priceFormat($this->calculatedSums_tax["goodstotal"]);
		$markerArray["###PRICE_GOODSTOTAL_NO_TAX###"] = $this->priceFormat($this->calculatedSums_no_tax["goodstotal"]);

		// This is for the Basketoverview
		$markerArray["###NUMBER_GOODSTOTAL###"] = $this->calculatedSums_number["goodstotal"];
		$markerArray["###IMAGE_BASKET###"] = '<img src="'.$this->conf["basket_pic"].'">';
		$wrappedSubpartArray["###LINK_BASKET###"]= array('<A href="'.$this->getLinkUrl($this->conf["PIDbasket"]).'">','</A>');

			// Shipping
		$this->calculatedSums_tax["shipping"]=doubleVal($this->basketExtra["shipping."]["priceTax"]);
		if ($this->basketExtra["shipping."]["priceTax."]["1"] != NULL)
		{
			$this->calculatedSums_tax["shipping"]=$priceShippingTax;
		}
		$this->calculatedSums_no_tax["shipping"]=doubleVal($this->basketExtra["shipping."]["priceNoTax"]);
		$perc = doubleVal($this->basketExtra["shipping."]["percentOfGoodstotal"]);
		if ($perc)	{
			$this->calculatedSums_tax["shipping"]+= $this->calculatedSums_tax["goodstotal"]/100*$perc;
			$this->calculatedSums_no_tax["shipping"]+= $this->calculatedSums_no_tax["goodstotal"]/100*$perc;
		}
		if ($this->basketExtra["shipping."]["calculationScript"])	{
			$calcScript = $TSFE->tmpl->getFileName($this->basketExtra["shipping."]["calculationScript"]);
			if ($calcScript)	{
				$this->includeCalcScript($calcScript,$this->basketExtra["shipping."]["calculationScript."]);
			}
		}

		foreach ($productsArray as $prodUID)
			foreach ($prodUID as $prodSingle)
			{
				if ($prodSingle["bulkily"])
				{
					$this->calculatedSums_no_tax["shipping"] += ($this->conf["bulkilyAddition"] * $this->basketExt[$prodSingle["uid"]][$prodSingle["size"].";".$prodSingle["color"]]);
					$this->calculatedSums_tax["shipping"] += ($this->conf["bulkilyAddition"] * $this->basketExt[$prodSingle["uid"]][$prodSingle["size"].";".$prodSingle["color"]]) * (1+$conf["feeTax"]/100);
				}
			}

		$markerArray["###PRICE_SHIPPING_PERCENT###"] = $perc;
		$markerArray["###PRICE_SHIPPING_TAX###"] = $this->priceFormat($this->calculatedSums_tax["shipping"]);
		$markerArray["###PRICE_SHIPPING_NO_TAX###"] = $this->priceFormat($this->calculatedSums_no_tax["shipping"]);

		$markerArray["###SHIPPING_SELECTOR###"] = $this->generateRadioSelect("shipping", $countTotal);
		$markerArray["###SHIPPING_IMAGE###"] = $this->cObj->IMAGE($this->basketExtra["shipping."]["image."]);
		$markerArray["###SHIPPING_TITLE###"] = $this->basketExtra["shipping."]["title"];

		$markerArray["###SHIPPING_WEIGHT###"] = doubleval($this->calculatedWeight);

			// Payment
		$this->calculatedSums_tax["payment"]=
						  $this->getValue($this->basketExtra["payment."]["priceTax"],
		                  		$this->basketExtra["payment."]["priceTax."],
		                  		$countTotal);
		$this->calculatedSums_no_tax["payment"]=
						  $this->getValue($this->basketExtra["payment."]["priceNoTax"],
		                  		$this->basketExtra["payment."]["priceNoTax."],
		                  		$countTotal);
		$perc = doubleVal($this->basketExtra["payment."]["percentOfTotalShipping"]);
		if ($perc)	{
			$this->calculatedSums_tax["payment"]=
			($this->calculatedSums_tax["goodstotal"] + $this->calculatedSums_tax["shipping"] ) *
				doubleVal($perc);
		}

		$perc = doubleVal($this->basketExtra["payment."]["percentOfGoodstotal"]);
		if ($perc)	{
			$this->calculatedSums_tax["payment"]+= $this->calculatedSums_tax["goodstotal"]/100*$perc;
			$this->calculatedSums_no_tax["payment"]+= $this->calculatedSums_no_tax["goodstotal"]/100*$perc;
		}
		if ($this->basketExtra["payment."]["calculationScript"])	{
			$calcScript = $TSFE->tmpl->getFileName($this->basketExtra["payment."]["calculationScript"]);
			if ($calcScript)	{
				$this->includeCalcScript($calcScript,$this->basketExtra["payment."]["calculationScript."]);
			}
		}

		$markerArray["###DELIVERYCOSTS###"]=$this->priceFormat($this->calculatedSums_tax["shipping"]
					+ $this->calculatedSums_tax["payment"]);

		$markerArray["###PRICE_PAYMENT_PERCENT###"] = $perc;
		$markerArray["###PRICE_PAYMENT_TAX###"] = $this->priceFormat($this->calculatedSums_tax["payment"]);
		$markerArray["###PRICE_PAYMENT_NO_TAX###"] = $this->priceFormat($this->calculatedSums_no_tax["payment"]);

		$markerArray["###PAYMENT_SELECTOR###"] = $this->generateRadioSelect("payment", $countTotal);
		$markerArray["###PAYMENT_IMAGE###"] = $this->cObj->IMAGE($this->basketExtra["payment."]["image."]);
		$markerArray["###PAYMENT_TITLE###"] = $this->basketExtra["payment."]["title"];

			// Fill the Currency Symbol or not
		if ($this->conf["showcurSymbol"]) {
			$markerArray["###CUR_SYM###"] = " ".$this->conf["currencySymbol"];
		} else {
			$markerArray["###CUR_SYM###"] = "";
		}

			// This is the total for everything
		$this->calculatedSums_tax["total"] = $this->calculatedSums_tax["goodstotal"];
		$this->calculatedSums_tax["total"]+= $this->calculatedSums_tax["payment"];
		$this->calculatedSums_tax["total"]+= $this->calculatedSums_tax["shipping"];

		$this->calculatedSums_no_tax["total"] = $this->calculatedSums_no_tax["goodstotal"];
		$this->calculatedSums_no_tax["total"]+= $this->calculatedSums_no_tax["payment"];
		$this->calculatedSums_no_tax["total"]+= $this->calculatedSums_no_tax["shipping"];

		$markerArray["###PRICE_TOTAL_TAX###"] = $this->priceFormat($this->calculatedSums_tax["total"]);
		$markerArray["###PRICE_TOTAL_NO_TAX###"] = $this->priceFormat($this->calculatedSums_no_tax["total"]);


			// Personal and delivery info:
		$infoFields = explode(",","name,address,telephone,fax,email,company,city,zip,state,country");		// Fields...
		while(list(,$fName)=each($infoFields))	{
			$markerArray["###PERSON_".strtoupper($fName)."###"] = $this->personInfo[$fName];
			$markerArray["###DELIVERY_".strtoupper($fName)."###"] = $this->deliveryInfo[$fName];
		}
			// Markers for use if you want to output line-broken address information
		$markerArray["###PERSON_ADDRESS_DISPLAY###"] = nl2br($markerArray["###PERSON_ADDRESS###"]);
		$markerArray["###DELIVERY_ADDRESS_DISPLAY###"] = nl2br($markerArray["###DELIVERY_ADDRESS###"]);
			// Delivery note.
		$markerArray["###DELIVERY_NOTE###"] = $this->deliveryInfo["note"];
		$markerArray["###DELIVERY_NOTE_DISPLAY###"] = nl2br($markerArray["###DELIVERY_NOTE###"]);


			// Order:	NOTE: Data exist only if the getBlankOrderUid() has been called. Therefore this field in the template should be used only when an order has been established
		$markerArray["###ORDER_UID###"] = $this->getOrderNumber($this->recs["tt_products"]["orderUid"]);
		$markerArray["###ORDER_DATE###"] = $this->cObj->stdWrap($this->recs["tt_products"]["orderDate"],$this->conf["orderDate_stdWrap."]);
		$markerArray["###ORDER_TRACKING_NO###"] = $this->recs["tt_products"]["orderTrackingNo"];

			// Fe users:
		$markerArray["###FE_USER_USERNAME###"] = $GLOBALS["TSFE"]->fe_user->user["username"];
		$markerArray["###FE_USER_UID###"] = $GLOBALS["TSFE"]->fe_user->user["uid"];

		$markerArray["###PERSON_AGB###"] = $GLOBALS["TSFE"]->fe_user->user["agb"] ? " checked" : "";

		$markerArray["###USERNAME###"] = $this->personInfo["email"];
		$markerArray["###PASSWORD###"] = $this->password;

			// URL
		$markerArray = $this->addURLMarkers($markerArray);
		$subpartArray = array();
		$wrappedSubpartArray = array();

		$agb_url=array();
		$pidagb = intval($this->conf["PIDagb"]);
		$agb_url["id"] = 'id='.$pidagb;
		$agb_url["type"]= $TSFE->type ? 'type='.$GLOBALS["TSFE"]->type : "";
		$agb_url["backPID"]= 'backPID='.$GLOBALS["TSFE"]->id;

		$wrappedSubpartArray["###LINK_AGB###"]=array('<A href="'.$this->$GLOBALS["TSFE"]->absRefPrefix.'index.php?'.implode($agb_url,"&").'" target="'.$this->conf["agbtarget"].'">','</A>');

			// Final substitution:
		if (!$TSFE->loginUser)	{		// Remove section for FE_USERs only, if there are no fe_user
			$subpartArray["###FE_USER_SECTION###"]="";
		}
		$bFrameWork = $t["basketFrameWork"];
		$subpartArray["###MESSAGE_SHIPPING###"] = $this->cObj->substituteMarkerArrayCached($this->cObj->getSubpart($bFrameWork,"###MESSAGE_SHIPPING_".$this->basketExtra["shipping"]."###"),$markerArray);
		$subpartArray["###MESSAGE_PAYMENT###"] = $this->cObj->substituteMarkerArrayCached($this->cObj->getSubpart($bFrameWork,"###MESSAGE_PAYMENT_".$this->basketExtra["payment"]."###"),$markerArray);

		$bFrameWork=$this->cObj->substituteMarkerArrayCached($t["basketFrameWork"],$markerArray,$subpartArray,$wrappedSubpartArray);

			// substitute the main subpart with the rendered content.
		$out=$this->cObj->substituteSubpart($bFrameWork, "###ITEM_CATEGORY_AND_ITEMS###", $out);
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
		if ($GLOBALS["TSFE"]->beUserLogin)	{
			if (t3lib_div::_GP("update_code")==$this->conf["update_code"])	{
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

		if ($orderRow["uid"])	{
				// Initialize update of status...
			$fieldsArray = array();
			$orderRecord = t3lib_div::_GP("orderRecord");
			if (isset($orderRecord["email_notify"]))	{
				$fieldsArray["email_notify"]=$orderRecord["email_notify"];
				$orderRow["email_notify"] = $fieldsArray["email_notify"];
			}
			if (isset($orderRecord["email"]))	{
				$fieldsArray["email"]=$orderRecord["email"];
				$orderRow["email"] = $fieldsArray["email"];
			}

			if (is_array($orderRecord["status"]))	{
				$status_log = unserialize($orderRow["status_log"]);
				reset($orderRecord["status"]);
				$update=0;
				while(list(,$val)=each($orderRecord["status"]))	{
					if ($admin || ($val>=50 && $val<59))	{// Numbers 50-59 are usermessages.
						$status_log_element = array(
							"time" => time(),
							"info" => $this->conf["statusCodes."][$val],
							"status" => $val,
							"comment" => $orderRecord["status_comment"]
						);
						if ($orderRow["email"] && $orderRow["email_notify"])	{
							$this->sendNotifyEmail($orderRow["email"], $status_log_element, t3lib_div::_GP("tracking"), $this->getOrderNumber($orderRow["uid"]),$templateCode);
						}
						$status_log[] = $status_log_element;
						$update=1;
					}
				}
				if ($update)	{
					$fieldsArray["status_log"]=serialize($status_log);
					$fieldsArray["status"]=$status_log_element["status"];
					if ($fieldsArray["status"] >= 100)	{

							// Deletes any M-M relations between the tt_products table and the order.
							// In the future this should maybe also automatically count down the stock number of the product records. Else it doesn't make sense.
						$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_products_orders_mm_tt_products', 'sys_products_orders_uid='.intval($orderRow['uid']));
					}
				}
			}

			if (count($fieldsArray))	{		// If any items in the field array, save them
				$fieldsArray["tstamp"] = time();

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderRow["uid"]), $fieldsArray);

				$orderRow = $this->getOrderRecord($orderRow["uid"]);
			}
		}


			// Getting the template stuff and initialize order data.
		$content=$this->cObj->getSubpart($templateCode,"###TRACKING_DISPLAY_INFO###");
		$status_log = unserialize($orderRow["status_log"]);
		$orderData = unserialize($orderRow["orderData"]);

			// Status:
		$STATUS_ITEM=$this->cObj->getSubpart($content,"###STATUS_ITEM###");
		$STATUS_ITEM_c="";
		if (is_array($status_log))	{
			reset($status_log);
			while(list($k,$v)=each($status_log))	{
				$markerArray=Array();
				$markerArray["###ORDER_STATUS_TIME###"]=$this->cObj->stdWrap($v["time"],$this->conf["statusDate_stdWrap."]);
				$markerArray["###ORDER_STATUS###"]=$v["status"];
				$markerArray["###ORDER_STATUS_INFO###"]=$v["info"];
				$markerArray["###ORDER_STATUS_COMMENT###"]=nl2br($v["comment"]);

				$STATUS_ITEM_c.=$this->cObj->substituteMarkerArrayCached($STATUS_ITEM, $markerArray);
			}
		}

		$subpartArray=array();
		$subpartArray["###STATUS_ITEM###"]=$STATUS_ITEM_c;


		$markerArray=Array();

			// Display admin-interface if access.
		if (!$TSFE->beUserLogin)	{
			$subpartArray["###ADMIN_CONTROL###"]="";
		} elseif ($admin) {
			$subpartArray["###ADMIN_CONTROL_DENY###"]="";
		} else {
			$subpartArray["###ADMIN_CONTROL_OK###"]="";
		}
		if ($TSFE->beUserLogin)	{
				// Status admin:
			if (is_array($this->conf["statusCodes."]))	{
				reset($this->conf["statusCodes."]);
				while(list($k,$v)=each($this->conf["statusCodes."]))	{
					if ($k!=1)	{
						$markerArray["###STATUS_OPTIONS###"].='<option value="'.$k.'">'.htmlspecialchars($k.": ".$v).'</option>';
					}
				}
			}

				// Get unprocessed orders.
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,name,tracking_code,amount', 'sys_products_orders', 'NOT deleted AND status!=0 AND status<100', '', 'crdate');
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$markerArray["###OTHER_ORDERS_OPTIONS###"].='<option value="'.$row["tracking_code"].'">'.htmlspecialchars($this->getOrderNumber($row["uid"]).": ".$row["name"]." (".$this->priceFormat($row["amount"])." ".$this->conf["currencySymbol"].")").'</option>';
			}
		}


			// Final things
		$markerArray["###ORDER_HTML_OUTPUT###"] = $orderData["html_output"];		// The save order-information in HTML-format
		$markerArray["###FIELD_EMAIL_NOTIFY###"] = $orderRow["email_notify"] ? " checked" : "";
		$markerArray["###FIELD_EMAIL###"] = $orderRow["email"];
		$markerArray["###ORDER_UID###"] = $this->getOrderNumber($orderRow["uid"]);
		$markerArray["###ORDER_DATE###"] = $this->cObj->stdWrap($orderRow["crdate"],$this->conf["orderDate_stdWrap."]);
		$markerArray["###TRACKING_NUMBER###"] = t3lib_div::_GP("tracking");
		$markerArray["###UPDATE_CODE###"] = t3lib_div::_GP("update_code");

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
		$admin = $this->shopAdmin();

			// initialize order data.
		$orderData = unserialize($orderRow["orderData"]);

		$basket = $orderData["calculatedBasket"];

		$markerArray=Array();
		$subpartArray = array();
		$wrappedSubpartArray = array();

			// Display admin-interface if access.
		if (!$GLOBALS["TSFE"]->beUserLogin)	{
			$subpartArray["###ADMIN_CONTROL###"]="";
		} elseif ($admin) {
			$subpartArray["###ADMIN_CONTROL_DENY###"]="";
		} else {
			$subpartArray["###ADMIN_CONTROL_OK###"]="";
		}

		if ($type == "bill")
		{
			$subpartMarker="###BILL_TEMPLATE###";
		}
		else
		{
			$subpartMarker="###DELIVERY_TEMPLATE###";
		}

			// Getting subparts from the template code.
		$t=array();
			// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
		$t["orderFrameWork"] = $this->cObj->getSubpart($templateCode,$this->spMarker($subpartMarker));

		$t["categoryTitle"] = $this->cObj->getSubpart($t["orderFrameWork"],"###ITEM_CATEGORY###");
		$t["itemFrameWork"] = $this->cObj->getSubpart($t["orderFrameWork"],"###ITEM_LIST###");
		$t["item"] = $this->cObj->getSubpart($t["itemFrameWork"],"###ITEM_SINGLE###");



		$categoryQty = array();
		$category = array();

		reset($basket);


		$priceItemTax = array();
		$countItem = array();
		// $preis and $count must start with index 1
		$itemPrice[] = 0;
		$itemCount[] = 0;

		// Calculate quantities for all categories
		foreach ($basket as $actBasket)
		{
			$currentCategory=$actBasket["rec"]["category"];
			$category[] = $currentCategory;
			$itemPrice[] = $actBasket["priceTax"];
			$itemCount[] = $actBasket["count"];
			if(isset($categoryQty[$currentCategory]))
			{
				$categoryQty[$currentCategory] += $actBasket["count"];
			}
			else
			{
				$categoryQty[$currentCategory] = $actBasket["count"];
			}
		}
			// Initialize traversing the items in the calculated basket

		if ($this->conf["pricecalc."] || $this->conf["discountprice."])
		{
			$this->GetCalculatedData(
			$this->basketExtra["pricecalc."],
			$this->basketExtra["discountprice."],
			$itemPrice, $itemCount, $priceShippingTax,
			$countTotal);
		}
		$currentCategory=0;

		reset($basket);
		$itemsOut="";
		$out="";

		foreach ($category as $currentCategory)
		{
			$categoryChanged = 1;

			while(list(,$row)=each($basket))
			{
					// Print Category Title
				if ($row["rec"]["category"]==$currentCategory)
				{

					if ($categoryChanged == 1)
					{
						$markerArray=array();
						$catTitle= ($this->categories[$currentCategory] ? $this->categories[$currentCategory]:"");
						$this->cObj->setCurrentVal($catTitle);
						$markerArray["###CATEGORY_TITLE###"]=$this->cObj->cObjGetSingle($this->conf["categoryHeader"],$this->conf["categoryHeader."], "categoryHeader");
						$markerArray["###CATEGORY_QTY###"]=$categoryQty[$currentCategory];
						$markerArray["###CATEGORY_PRICE###"]= $this->priceFormat($this->calculatedSums_tax["goodstotal"]);
						$out2 = $this->cObj->substituteMarkerArray($t["categoryTitle"], $markerArray);
						$out.= $out2;
					}

						// Print Item Title
					$wrappedSubpartArray=array();
					$markerArray = $this->getItemMarkerArray ($row["rec"],$catTitle,1,"listImage");
					$markerArray["###FIELD_QTY###"] = $row["count"];

					$itemsOut = $this->cObj->substituteMarkerArrayCached($t["item"],$markerArray,array(),$wrappedSubpartArray);
					if ($itemsOut)
					{
						$out2 =$this->cObj->substituteSubpart($t["itemFrameWork"], "###ITEM_SINGLE###", $itemsOut);
						$out .= $out2;
					}
					$itemsOut="";			// Clear the item-code var

				$categoryChanged = 0;
				}
			}
		}


		$subpartArray["###ITEM_CATEGORY_AND_ITEMS###"] = $out;

			// Final things
			// Personal and delivery info:

		$infoFields = explode(",","name,address,telephone,fax,email,company,city,zip,state,country");		// Fields...
		while(list(,$fName)=each($infoFields))
		{
			$markerArray["###PERSON_".strtoupper($fName)."###"] = $orderData["personInfo"][$fName];
			$markerArray["###DELIVERY_".strtoupper($fName)."###"] = $orderData["deliveryInfo"][$fName]; // $this->deliveryInfo[$fName];
		}

		$markerArray["###PERSON_ADDRESS_DISPLAY###"] = nl2br($markerArray["###PERSON_ADDRESS###"]);
		$markerArray["###DELIVERY_ADDRESS_DISPLAY###"] = nl2br($markerArray["###DELIVERY_ADDRESS###"]);

		$temp = explode(" ", $orderRow["payment"]);
		$markerArray["###PAYMENT_TITLE###"] = $temp[1];
		$markerArray["###PRICE_SHIPPING_TAX###"] = $this->priceFormat($orderData["calculatedSum_tax"]["shipping"]);
		$markerArray["###PRICE_PAYMENT_TAX###"] = $this->priceFormat($orderData["calculatedSum_tax"]["payment"]);
		$markerArray["###PRICE_TOTAL_TAX###"] = $this->priceFormat($orderData["calculatedSum_tax"]["total"]);
		$markerArray["###ORDER_UID###"] = $this->getOrderNumber($orderRow["uid"]);
		$markerArray["###ORDER_DATE###"] = $this->cObj->stdWrap($orderRow["crdate"],$this->conf["orderDate_stdWrap."]);

		$content= $this->cObj->substituteMarkerArrayCached($t["orderFrameWork"], $markerArray, $subpartArray);
		$dateiname = $this->conf["outputFolder"] . "/" . $type . "/" . $tracking . ".htm";

		$datei = fopen($dateiname, "w");
		fwrite ($datei, $content);
		fclose ($datei);

		if ($type == "bill")
		{
			$content = "<A href=\"" . $dateiname . "\" >zum &Ouml;ffnen der Rechnung hier klicken</A>";
		}
		else
		{
			$content = "<A href=\"" . $dateiname . "\" >zum &Ouml;ffnen des Lieferscheins hier klicken</A>";
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
		if ($this->conf["orderEmail_from"])	{$headers[]="FROM: ".$this->conf["orderEmail_fromName"]." <".$this->conf["orderEmail_from"].">";}

		$recipients = $recipient;
		$recipients=t3lib_div::trimExplode(",",$recipients,1);

		if (count($recipients))	{	// If any recipients, then compile and send the mail.
			$emailContent=trim($this->cObj->getSubpart($templateCode,"###TRACKING_EMAILNOTIFY_TEMPLATE###"));
			if ($emailContent)	{		// If there is plain text content - which is required!!

				$markerArray["###ORDER_STATUS_TIME###"]=$this->cObj->stdWrap($v["time"],$this->conf["statusDate_stdWrap."]);
				$markerArray["###ORDER_STATUS###"]=$v["status"];
				$markerArray["###ORDER_STATUS_INFO###"]=$v["info"];
				$markerArray["###ORDER_STATUS_COMMENT###"]=$v["comment"];

				$markerArray["###ORDER_TRACKING_NO###"]=$tracking;
				$markerArray["###ORDER_UID###"]=$uid;

				$emailContent=$this->cObj->substituteMarkerArrayCached($emailContent, $markerArray);

				$parts = split(chr(10),$emailContent,2);
				$subject=trim($parts[0]);
				$plain_message=trim($parts[1]);

//				$TSFE->plainMailEncoded(implode($recipients,","), $subject, $plain_message, implode($headers,chr(10)));
				$this->zk_mail(implode($recipients,","), $subject, $plain_message, $this->conf["orderEmail_from"], $this->conf["orderEmail_fromName"]);
			}
		}
	}

	/**
	 * // ZK++
	 * Generate a graphical price tag or print the price as text
	 */
	function printPrice($priceText)
	{
		if (($this->conf["usePriceTag"]) && (isset($this->conf["priceTagObj."])))
		{
			$ptconf = $this->conf["priceTagObj."];
			$markContentArray = array();
			$markContentArray["###PRICE###"] = $priceText;
			$this->cObj->substituteMarkerInObject($ptconf, $markContentArray);
			return $this->cObj->cObjGetSingle($this->conf["priceTagObj"], $ptconf);
		}
		else
			return $priceText;
	}


	/**
	 * // ZK++
	 * ZKs extended mail function
	 */
	function zk_mail($email,$subject,$message,$fromEMail,$fromName,$attachment="")
	{
		$cls=t3lib_div::makeInstanceClassName("t3lib_htmlmail");
		if (class_exists($cls))
		{
			$Typo3_htmlmail = t3lib_div::makeInstance("t3lib_htmlmail");
			$Typo3_htmlmail->start();
			$Typo3_htmlmail->useBase64();

			$Typo3_htmlmail->subject = $subject;
			$Typo3_htmlmail->from_email = $fromEMail;
			$Typo3_htmlmail->from_name = $fromName;
			$Typo3_htmlmail->replyto_email = $Typo3_htmlmail->from_email;
			$Typo3_htmlmail->replyto_name = $Typo3_htmlmail->from_name;
			$Typo3_htmlmail->organisation = "";
			$Typo3_htmlmail->priority = 3;

			$Typo3_htmlmail->addPlain($message);
			if ($attachment != "")
				$Typo3_htmlmail->addAttachment($attachment);

			$Typo3_htmlmail->setHeaders();
			$Typo3_htmlmail->setContent();
			$Typo3_htmlmail->setRecipient(explode(",", $email));
			$Typo3_htmlmail->sendtheMail();
		}
	}

	/**
	 * // ZK++
	 * Displays and manages the memo
	 */
	function memo_display($theCode)
	{
		global $TSFE;

		$fe_user_uid = $TSFE->fe_user->user["uid"];
		if (!$fe_user_uid)
			return $this->cObj->getSubpart($this->templateCode,$this->spMarker("###MEMO_NOT_LOGGED_IN###"));

		if ($TSFE->fe_user->user["tt_products_memoItems"] != "")
			$memoItems = explode(",", $TSFE->fe_user->user["tt_products_memoItems"]);
		else
			$memoItems = array();

		if (t3lib_div::GPvar("addmemo"))
		{
			$addMemo = explode(",", t3lib_div::GPvar("addmemo"));

			foreach ($addMemo as $addMemoSingle)
				if (!in_array($addMemoSingle, $memoItems))
					$memoItems[] = $addMemoSingle;

			$fieldsArray = array();
			$fieldsArray["tt_products_memoItems"]=implode(",", $memoItems);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$fe_user_uid, $fieldsArray);
		}

		if (t3lib_div::GPvar("delmemo"))
		{
			$delMemo = explode(",", t3lib_div::GPvar("delmemo"));

			foreach ($delMemo as $delMemoSingle)
				if (in_array($delMemoSingle, $memoItems))
					unset($memoItems[array_search($delMemoSingle, $memoItems)]);

			$fieldsArray = array();
			$fieldsArray["tt_products_memoItems"]=implode(",", $memoItems);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$fe_user_uid, $fieldsArray);
		}

		return $this->products_display($theCode, implode(",", $memoItems));
	}
}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tt_products/pi/class.tx_ttproducts.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tt_products/pi/class.tx_ttproducts.php"]);
}


?>
