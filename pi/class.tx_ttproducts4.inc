


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

		$this->calculatedSums_number["goodstotal"] = 0;

		$uidArr = array();
		reset($this->basketExt);
		while(list($uidTmp,)=each($this->basketExt))
			if (!in_array($uidTmp, $uidArr))
				$uidArr[] = $uidTmp;

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', 'uid IN ('.implode(',',$uidArr).') AND pid IN ('.$this->pid_list.')'.$this->cObj->enableFields('tt_products'));

		$productsArray = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
			reset($this->basketExt[$row["uid"]]);
			while(list($bextVars,)=each($this->basketExt[$row["uid"]])) {
				$groesseFarbe = explode(";", $bextVars);
				$row["size"] = $groesseFarbe[0];
				$row["color"] = $groesseFarbe[1];
				$row["accessory"] = $groesseFarbe[2];
				$row["extVars"] = $bextVars;
				$productsArray[$row["pid"]][]=$row;
						// Fills this array with the product records. Reason: Sorting them by category (based on the page, they reside on)
			}
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

		$itemCategory = array();
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

					// if reseller is logged in then take 'price2', default is 'price'
					$priceTax = $this->getResellerPrice($row,1);
					$priceNoTax = $this->getResellerPrice($row,0);
					$calculatedBasketItem = array(
						"itemnumber" => intval($row["itemnumber"]),
						"priceTax" => $priceTax,
						"priceNoTax" => $priceNoTax,
						"weight" => $row["weight"],
						"accessory" => $row["accessory"],
						"count" => intval($this->basketExt[$row["uid"]][$row["size"].";".$row["color"].";".$row["accessory"]]),
						"rec" => $row
					);

					// If accesssory has been selected, add the price of it, multiplicated with the count :
					if($calculatedBasketItem["rec"]["accessory"] AND $calculatedBasketItem["rec"]["accessory"] != 0 ){
						$calculatedBasketItem["totalTax"] = ($calculatedBasketItem["priceTax"]+ $this->getPrice($calculatedBasketItem["rec"]["accessory"],1,$row["tax"]))*$calculatedBasketItem["count"];
						$calculatedBasketItem["totalNoTax"] = ($calculatedBasketItem["priceNoTax"]+getPrice($calculatedBasketItem["rec"]["accessory"],0,$row["tax"]))*$calculatedBasketItem["count"];
						$markerArray["###PRICE_ACCESSORY_TEXT###"]= $this->conf['accessoryText'];
						$markerArray["###PRICE_ACCESSORY_COUNT###"]= '<INPUT size="3" maxlength="4" type="text" class="readonly" name="'.$calculatedBasketItem["count"].'" value="'.$calculatedBasketItem["count"].'" readonly="readonly">';
						$markerArray["###ACCESSORY_VALUE_TAX###"]= $this->printPrice($this->priceFormat($this->getPrice($row["accessory".$this->config['priceNoReseller']],1,$row["tax"])));
						$markerArray["###ACCESSORY_VALUE_NO_TAX###"]= $this->printPrice($this->priceFormat($this->getPrice($row["accessory".$this->config['priceNoReseller']],0,$row["tax"])));
					}
					else {
						$calculatedBasketItem["totalTax"] = $calculatedBasketItem["priceTax"]*$calculatedBasketItem["count"];
						$calculatedBasketItem["totalNoTax"] = $calculatedBasketItem["priceNoTax"]*$calculatedBasketItem["count"];

						$markerArray["###PRICE_ACCESSORY_TEXT###"]= '';
						$markerArray["###PRICE_ACCESSORY_COUNT###"]= '';
						$markerArray["###ACCESSORY_VALUE_TAX###"]= '';
						$markerArray["###ACCESSORY_VALUE_NO_TAX###"]= '';
					}

					$itemCategory[$calculatedBasketItem["itemnumber"]] = $row["category"];
					$itemPrice[$calculatedBasketItem["itemnumber"]] = $calculatedBasketItem["priceTax"];
					$itemCount[$calculatedBasketItem["itemnumber"]] = $calculatedBasketItem["count"];

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
			$itemCategory,
			$itemPrice, $itemCount, $priceShippingTax,
			$countTotal, $goodsTotal, $countCategory);

			$this->calculatedSums_tax["goodstotal"] = $this->getPrice($goodsTotal,1);
			$this->calculatedSums_no_tax["goodstotal"] = $this->getPrice($goodsTotal,0);

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

			// Shipping (must be before payment)
		$this->calculatedSums_tax["shipping"]=doubleVal($this->basketExtra["shipping."]["priceTax"]);
		if ($this->basketExtra["shipping."]["priceTax."]["1"] != NULL)
		{
			$this->calculatedSums_tax["shipping"]=$this->getPrice($priceShippingTax,1);
			$this->calculatedSums_no_tax["shipping"]=$this->getPrice($priceShippingTax,0);
		}
		$this->calculatedSums_no_tax["shipping"]=doubleVal($this->basketExtra["shipping."]["priceNoTax"]);
		$perc = doubleVal($this->basketExtra["shipping."]["percentOfGoodstotal"]);
		if ($perc)	{
			$this->calculatedSums_tax["shipping"]+= $this->calculatedSums_tax["goodstotal"]/100*$perc;
			$this->calculatedSums_no_tax["shipping"]+= $this->calculatedSums_no_tax["goodstotal"]/100*$perc;
		}

		$weigthFactor = doubleVal($this->basketExtra["shipping."]["priceFactWeight"]);
		if($weigthFactor > 0) {
			$priceShippingTax = $this->calculatedWeight * $weigthFactor;
			$this->calculatedSums_tax["shipping"] += $this->getPrice($priceShippingTax,1);
			$this->calculatedSums_no_tax["shipping"] += $this->getPrice($priceShippingTax,0);
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
					$value = ($this->conf["bulkilyAddition"] * $this->basketExt[$prodSingle["uid"]][$prodSingle["size"].";".$prodSingle["color"].";".$prodSingle["accessory"]]);
					$this->calculatedSums_tax["shipping"] += $value  * (1+$conf["bulkilyFeeTax"]/100);
					$this->calculatedSums_no_tax["shipping"] += $value;
				}
			}

		// TAXpercentage replaces priceNoTax
		if ($this->conf["shipping."]["TAXpercentage"]) {
			$this->calculatedSums_no_tax["shipping"] = $this->calculatedSums_tax["shipping"]/(1+doubleVal($this->conf["shipping."]["TAXpercentage"])/100);
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
			$payment = ($this->calculatedSums_tax["goodstotal"] + $this->calculatedSums_tax["shipping"] ) * doubleVal($perc);

			$this->calculatedSums_tax["payment"]=$payment;
			$this->calculatedSums_no_tax["payment"]=$this->getPrice($payment,0);
		}

		$perc = doubleVal($this->basketExtra["payment."]["percentOfGoodstotal"]);
		if ($perc)	{
			$this->calculatedSums_tax["payment"]+= $this->calculatedSums_tax["goodstotal"]/100*$perc;
			$this->calculatedSums_no_tax["payment"]+= $this->calculatedSums_no_tax["goodstotal"]/100*$perc;
		}
		// TAXpercentage replaces priceNoTax
		if ($this->conf["payment."]["TAXpercentage"]) {
			$this->calculatedSums_no_tax["payment"] = $this->calculatedSums_tax["payment"]/(1+doubleVal($this->conf["payment."]["TAXpercentage"])/100);
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
		$this->getCalculateSums();

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
		$countCategory = array();
		$category = array();

		reset($basket);

		$itemCategory = array();
		$itemCount = array();

		// Calculate quantities for all categories
		foreach ($basket as $actBasket)
		{
			$currentCategory=$actBasket["rec"]["category"];
			$category[$currentCategory] = 1;
			$itemCategory[] = $currentCategory;
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
			$itemCategory,
			$itemPrice, $itemCount, $priceShippingTax,
			$countTotal, $goodsTotal, $countCategory); // $countCategory will be used later ...

			$this->calculatedSums_tax["goodstotal"] = $this->getPrice($goodsTotal,1);
			$this->calculatedSums_no_tax["goodstotal"] = $this->getPrice($goodsTotal,0);
		}
		$currentCategory=0;

		reset($basket);
		$itemsOut="";
		$out="";

		foreach ($category as $currentCategory=>$value)
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

						$markerArray["###PRICE_GOODS_TAX###"]= $this->priceFormat($this->calculatedSums_tax["goodstotal"]);
						$markerArray["###PRICE_GOODS_NO_TAX###"]= $this->priceFormat($this->calculatedSums_no_tax["goodstotal"]);

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
		$markerArray["###PRICE_SHIPPING_NO_TAX###"] = $this->priceFormat($orderData["calculatedSum_no_tax"]["shipping"]);
		$markerArray["###PRICE_PAYMENT_TAX###"] = $this->priceFormat($orderData["calculatedSum_tax"]["payment"]);
		$markerArray["###PRICE_PAYMENT_NO_TAX###"] = $this->priceFormat($orderData["calculatedSum_no_tax"]["payment"]);
		$markerArray["###PRICE_TOTAL_TAX###"] = $this->priceFormat($orderData["calculatedSum_tax"]["total"]);
		$markerArray["###PRICE_TOTAL_NO_TAX###"] = $this->priceFormat($orderData["calculatedSums_no_tax"]["total"]);

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
				$this->send_mail(implode($recipients,","), $subject, $plain_message, $this->conf["orderEmail_from"], $this->conf["orderEmail_fromName"]);
			}
		}
	}

	/**
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
	 * Extended mail function
	 */
	function send_mail($email,$subject,$message,$fromEMail,$fromName,$attachment="")
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
