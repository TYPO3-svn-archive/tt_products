



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

						$this->send_mail($this->personInfo["email"], $subject, $plain_message, $this->conf["orderEmail_from"], $this->conf["orderEmail_fromName"]);
					}
				}
			}
		}


		if (!$this->conf['AlwaysInStock']) {
			// Reduce inStock
			reset($this->calculatedBasket);
			while(list(,$itemInfo)=each($this->calculatedBasket))   {
				$query="uid='".intval($itemInfo["rec"]["uid"])."'";

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
						$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_products', 'uid='.intval($itemInfo["rec"]["uid"]), $fieldsArray);
					}
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
				$csvdescr = '"uid";"count";"size";"color";"accessory"';
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
		$recipients.=",".$this->personInfo["email"]; // +++ deliveryInfo
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
					$this->send_mail($this->personInfo["email"], $subject, $plain_message, $this->conf["orderEmail_from"], $this->conf["orderEmail_fromName"], $this->conf["AGBattachment"]);
					if ($this->conf["generateCSV"])
						$addcsv = $csvfilepath;
					else
						$addcsv = "";
					$this->send_mail($this->conf["orderEmail_to"], $subject, $plain_message, $this->personInfo["email"], $this->personInfo["name"], $addcsv);
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
	function getPrice($price,$tax=1,$taxpercentage=0)	{
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

	// function using getPrice and considering a reduced price for resellers
	function getResellerPrice($row,$tax=1)	{
		$returnPrice = 0;
			// get reseller group number
		$priceNo = intval($this->config['priceNoReseller']);

		if ($priceNo > 0) {
			$returnPrice = $this->getPrice($row["price".$priceNo],$tax,$row["tax"]);
		}
		// normal price; if reseller price is zero then also the normal price applies
		if ($returnPrice == 0) {
			$returnPrice = $this->getPrice($row["price"],$tax,$row["tax"]);
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

			// all of the delivery address will be overwritten when no address and no city have been filled in
		if (!trim($this->deliveryInfo['city']) && !trim($this->deliveryInfo['email'])) {
			$infoFields = explode(",","name,address,telephone,fax,email,company,city,zip,state,country");
			while(list(,$fName)=each($infoFields))	{
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
				if (trim($this->personInfo[$fName])=='')	{
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

