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

				$queryPrevPrefix = '';
				$queryNextPrefix = '';
				if ($this->conf["orderByItemNumberSg"]) {
					$queryPrevPrefix = 'itemnumber < '.intval($row['itemnumber']);
					$queryNextPrefix = 'itemnumber > '.intval($row['itemnumber']);
				} else {
					$queryPrevPrefix = 'uid < '.intval($this->tt_product_single);
					$queryNextPrefix = 'uid > '.intval($this->tt_product_single);
				}
				$queryprev = '';
				$queryprev = $queryPrevPrefix .' AND pid IN ('.$TSFE->id.') AND (inStock <>0)' . $this->cObj->enableFields('tt_products');
				$resprev = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $queryprev);

				if ($rowprev = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resprev) )
					$wrappedSubpartArray["###LINK_PREV_SINGLE###"]=array('<A href="'.$url.'&tt_products='.$rowprev["uid"].'">','</A>');
				else
					$subpartArray["###LINK_PREV_SINGLE###"]="";

				$querynext = $queryNextPrefix .' AND pid IN ('.$TSFE->id .') AND (inStock <> 0)' . $this->cObj->enableFields('tt_products');
				$resnext = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $querynext);

				if ($rownext = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resnext) )
					$wrappedSubpartArray["###LINK_NEXT_SINGLE###"]=array('<A href="'.$url.'&tt_products='.$rownext["uid"].'">','</A>');
				else
					$subpartArray["###LINK_NEXT_SINGLE###"]="";

					// Substitute
				$content= $this->cObj->substituteMarkerArrayCached($item,$markerArray,array(),$wrappedSubpartArray);

				if (trim($row["color"]) == '')
					$content = $this->cObj->substituteSubpart($content, "###display_variant1###", "");
				if (trim($row["size"]) == '')
					$content = $this->cObj->substituteSubpart($content, "###display_variant2###", "");
				if (trim($row["accessory"]) == '')
					$content = $this->cObj->substituteSubpart($content, "###display_variant3###", "");
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

					// Getting various subparts we're going to use here:
				if ($memoItems != "")
					$t["listFrameWork"] = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###MEMO_TEMPLATE###"));
				else
					$t["listFrameWork"] = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###ITEM_LIST_TEMPLATE###"));

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

							$markerArray["###FORM_MEMO###"] = $this->getLinkUrl($this->conf["PIDmemo"]);
							// cuts note in list view
							if (strlen($markerArray["###PRODUCT_NOTE###"]) > $this->conf["max_note_length"])
								$markerArray["###PRODUCT_NOTE###"] = substr($markerArray["###PRODUCT_NOTE###"], 0, $this->conf["max_note_length"]) . "...";

							$tempContent = $this->cObj->substituteMarkerArrayCached($t["item"],$markerArray,array(),$wrappedSubpartArray);
							if (trim($row["color"]) == '')
								$tempContent = $this->cObj->substituteSubpart($tempContent, "###display_variant1###", "");
							if (trim($row["size"]) == '')
								$tempContent = $this->cObj->substituteSubpart($tempContent, "###display_variant2###", "");
							if (trim($row["accessory"]) == '')
								$tempContent = $this->cObj->substituteSubpart($tempContent, "###display_variant3###", "");
							$itemsOut .= $tempContent;
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
	 * $basket is the TYPO3 default shopping basket array from ses-data
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
						$extVars = $basketItem['size'].";".$basketItem['color'].";".$basketItem['accessory'];

						if ($this->config["useCategories"] == 1)
						{
							$this->basketExt[$uid][$extVars] = $count;
						}
						else
						{
							$this->basketExt[$uid][$extVars] += $count;
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
			$this->personInfo["country"] = $TSFE->fe_user->user["static_info_country"];
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


