
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

			// empty all image fields with no availble image
		for ($i=count($theImgCode) + 1; $i<=15; ++$i) {
			$markerArray["###PRODUCT_IMAGE" .  $i. "###"] = '';
		}

		$markerArray["###PRODUCT_SUBTITLE###"] = $row["subtitle"];
		$markerArray["###PRODUCT_WWW###"] = $row["www"];
		$markerArray["###PRODUCT_ID###"] = $row["uid"];

		$markerArray["###PRICE_TAX###"] = $this->printPrice($this->priceFormat($this->getResellerPrice($row,1)));
		$markerArray["###PRICE_NO_TAX###"] = $this->printPrice($this->priceFormat($this->getResellerPrice($row,0)));

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

		$markerArray["###FIELD_QTY###"]= $this->basketExt[$row["uid"]][$row["size"].";".$row["color"].";".$row["accessory"]] ? $this->basketExt[$row["uid"]][$row["size"].";".$row["color"].";".$row["accessory"]] : "";
		$markerArray["###FIELD_NAME_BASKET###"]="ttp_basket[".$row["uid"]."][".md5($row["extVars"])."]";

		$markerArray["###FIELD_SIZE_NAME###"]="ttp_basket[".$row["uid"]."][size]";
		$markerArray["###FIELD_SIZE_VALUE###"]=$row["size"];
		$markerArray["###FIELD_COLOR_NAME###"]="ttp_basket[".$row["uid"]."][color]";
		$markerArray["###FIELD_COLOR_VALUE###"]=$row["color"];
		$markerArray["###FIELD_ACCESSORY_NAME###"]="ttp_basket[".$row["uid"]."][accessory]";
		$markerArray["###FIELD_ACCESSORY_VALUE###"]=$row["accessory"];

		$prodColorText = '';
		$prodColTmp = explode(';', $row["color"]);
		foreach ($prodColTmp as $prodCol)
			$prodColorText = $prodColorText . '<OPTION value="'.$prodCol.'">'.$prodCol.'</OPTION>';

		$prodSizeText = '';
		$prodSizeTmp = explode(';', $row["size"]);
		foreach ($prodSizeTmp as $prodSize)
			$prodSizeText = $prodSizeText . '<OPTION value="'.$prodSize.'">'.$prodSize.'</OPTION>';

		$prodAccessoryText = '';
		$prodAccessoryTmp = explode(';', $row["accessory"]);
		foreach ($prodSizeTmp as $prodAccesssory)
			$prodAccessoryText = $prodAccessoryText . '<OPTION value="'.$prodAccessory.'">'.$prodAccessory.'</OPTION>';

		$markerArray["###PRODUCT_WEIGHT###"] = doubleval($row["weight"]);
		$markerArray["###BULKILY_WARNING###"] = $row["bulkily"] ? $this->conf["bulkilyWarning"] : "";
		$markerArray["###PRODUCT_COLOR###"] = $prodColorText;
		$markerArray["###PRODUCT_SIZE###"] = $prodSizeText;
		$markerArray["###PRICE_ACCESSORY_TAX###"] = $this->printPrice($this->priceFormat($this->getPrice($row["accessory".$this->config['priceNoReseller']],1,$row["tax"])));
		$markerArray["###PRICE_ACCESSORY_NO_TAX###"] = $this->printPrice($this->priceFormat($this->getPrice($row["accessory".$this->config['priceNoReseller']],0,$row["tax"])));
		$markerArray["###PRICE_WITH_ACCESSORY_TAX###"] = $this->printPrice($this->priceFormat($this->getPrice($row["accessory".$this->conf['priceNoReseller']]+$row["price".$this->config['priceNoReseller']],1,$row["tax"])));
		$markerArray["###PRICE_WITH_ACCESSORY_NO_TAX###"] = $this->printPrice($this->priceFormat($this->getPrice($row["accessory".$this->conf['priceNoReseller']]+$row["price".$this->config['priceNoReseller']],0,$row["tax"])));

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

		$template = $this->conf[$key."."]["template"] ? ereg_replace('\' *\. *\$key *\. *\'',$key, $this->conf[$key."."]["template"]) : '<nobr>###IMAGE### <input type="radio" name="recs[tt_products]['.$key.']" onClick="submit()" value="###VALUE###"###CHECKED###> ###TITLE###</nobr><BR>';

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
			&$categoryItem,
			&$priceItemTax, &$countItem, &$priceShippingTax,
			&$countTotal, &$goodstotalSum, &$countCategory)
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

		$goodstotalSum = 0;
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
						$countCategory[$categoryItem[$k2]] += $count2;
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
				$goodstotalSum += $priceTotal [$k1];
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
					$countCategory[$categoryItem[$k2]] += $count2;
				}
			}

			$countTotal += $dumCount;

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

			$goodstotalSum += $priceTotal [$k1];
		}

		//$this->calculatedSums_tax["payment"] = 0;
		//$this->calculatedSums_no_tax["payment"] = 0;

		$priceSingleShippingTax = 0;
		$confArr = $this->basketExtra["shipping."]["priceTax."];
		if ($confArr) {
			krsort($confArr);
			reset($confArr);

			while (list ($k1, $price1) = each ($confArr))
			{
				if ($countTotal >= intval($k1))
				{
					$priceShippingTax = $price1;
					break;
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

	// This calculates the total for everything
	function getCalculateSums () {
		$this->calculatedSums_tax["total"] = $this->calculatedSums_tax["goodstotal"];
		$this->calculatedSums_tax["total"]+= $this->calculatedSums_tax["payment"];
		$this->calculatedSums_tax["total"]+= $this->calculatedSums_tax["shipping"];

		$this->calculatedSums_no_tax["total"] = $this->calculatedSums_no_tax["goodstotal"];
		$this->calculatedSums_no_tax["total"]+= $this->calculatedSums_no_tax["payment"];
		$this->calculatedSums_no_tax["total"]+= $this->calculatedSums_no_tax["shipping"];
	}
