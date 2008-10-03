<?

class ts_products {
	var $searchFieldList="title,note,itemnumber";

		// Internal
	var $cObj="";	// 
	var $pid_list="";
	var $uid_list="";
	var $categories=array();
	var $pageArray=array();
	var $conf;
	var $basket=array();
	
	function init($cObj,$pid_list,$conf)	{
		$this->cObj = $cObj;
		$this->setPidlist($pid_list);
		$this->conf = $conf;
	}
	function setPidlist($pid_list)	{
		$this->pid_list = $pid_list;
	}
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
	function initCategories()	{
			// Fetching catagories:
	 	$query = "select * from tt_products_cat where 1=1".$this->cObj->enableFields("tt_products_cat");
		$res = mysql($GLOBALS[typo_db],$query);
		echo mysql_error();
		$this->categories=array();
		while($row = mysql_fetch_array($res))	{
			$this->categories[$row[uid]] = $row[title];
		}	
	}
	function initBasket($basket)	{
		$this->basket=array();
		$uidArr=array();
		if (is_array($basket["tt_products"]))	{
			reset($basket["tt_products"]);
			while(list($uid,$count)=each($basket["tt_products"]))	{
				$count=intInRange($count,0,100000);
				if ($count)	{
					$this->basket[$uid]=$count;
					$uidArr[]=$uid;
				}
			}
		}
		$this->uid_list=implode($uidArr,",");
	}
	function generatePageArray()	{
			// Get pages (for category titles)		
		$query="SELECT title,uid FROM pages WHERE uid IN(".$this->pid_list.")";
		$res = mysql($GLOBALS[typo_db],$query);
		echo mysql_error();
		$this->pageArray=array();
		while($row=mysql_fetch_array($res))		{
			$this->pageArray[$row["uid"]] = $row;
		}
	}
	function getItemMarkerArray ($row,$catTitle, $imageNum=0, $imageRenderObj="image")	{
		$markerArray=array();
			// Get image
		$theImgCode="";
		$imgs = explode(",",$row[image]);
		$val = $imgs[0];
		while(list($c,$val)=each($imgs))	{
			if ($c==$imageNum)	break;
			if ($val)	{
				$this->conf[$imageRenderObj."."]["file"] = "uploads/pics/".$val;
			} else {
				$this->conf[$imageRenderObj."."]["file"] = $this->conf["noImageAvailable"];
			}
			$theImgCode.=$this->cObj->IMAGE($this->conf[$imageRenderObj."."]);
		}
			// Subst. fields
		$markerArray["###PRODUCT_TITLE###"] = $row["title"];
		$markerArray["###PRODUCT_NOTE###"] = nl2br($row["note"]);
		if (is_array($this->conf["parseFunc."]))	{
			$markerArray["###PRODUCT_NOTE###"] = $this->cObj->parseFunc($markerArray["###PRODUCT_NOTE###"],$this->conf["parseFunc."]);
		}
		$markerArray["###PRODUCT_ITEMNUMBER###"] = $row["itemnumber"];
		$markerArray["###PRODUCT_IMAGE###"] = $theImgCode;
		$markerArray["###PRICE_TAX###"] = $this->priceFormat($row["price"]);
		$markerArray["###PRICE_NO_TAX###"] = $this->priceFormat($row["price"]/1.25);
		$markerArray["###PRODUCT_INSTOCK###"] = $row[inStock];


		
		$markerArray["###CATEGORY_TITLE###"] = $catTitle;
		
		$markerArray["###FIELD_NAME###"]="recs[tt_products][".$row[uid]."]";
		$markerArray["###FIELD_VALUE###"]= $this->basket[$row[uid]] ? $this->basket[$row[uid]] : "";
		return $markerArray;
	}
	function searchWhere($sw)	{
		$where="";
		if ($sw)	{
			$searchFields=explode(",",$this->searchFieldList);
			$kw=split("[ ,]",$sw);

			while(list(,$val)=each($kw))	{
				$val=trim($val);
				$where_p=array();
				if (strlen($val)>=2)	{
					reset($searchFields);
					while(list(,$field)=each($searchFields))	{
						$where_p[]=$field." LIKE '%".addslashes($val)."%'";
					}
				}
				if (count($where_p))	{
					$where.=" AND (".implode($where_p," OR ").")";
				}
			}
		}
		return $where;
	}
	function getLinkUrl($id="",$excludeList="")	{
		$queryString=array();
		$queryString["id"] = ($id ? $id : $GLOBALS["id"]);
		$queryString["type"]= $GLOBALS["type"] ? 'type='.$GLOBALS["type"] : "";
		$queryString["backPID"]= 'backPID='.$GLOBALS["id"];
		$queryString["begin_at"]= $GLOBALS["begin_at"] ? 'begin_at='.$GLOBALS["begin_at"] : "";
		$queryString["swords"]= $GLOBALS["swords"] ? "swords=".rawurlencode(stripslashes($GLOBALS["swords"])) : "";

		reset($queryString);
		while(list($key,$val)=each($queryString))	{
			if (!$val || ($excludeList && inList($excludeList,$key)))	{
				unset($queryString[$key]);
			}
		}
		return 'index.php?'.implode($queryString,"&");
	}
	function priceFormat($double)	{
		return number_format($double,intval($this->conf["priceDec"]),$this->conf["priceDecPoint"],$this->conf["priceThousandPoint"]);
		 
	
	
		$prefix = $double<0 ? "-" : "";
		$double=abs($double);
		$parts = explode(".",(string)$double);
		$intPart = $parts[0];
		$decPart = $parts[1]."00000";
			// Get price-format
		$priceFormat = strrev($this->conf["priceFormat"] ? $this->conf["priceFormat"] : "###,###,###.%%");
		$newString="";
			// Integer part
		$parts=explode("#",$priceFormat);
		$intRevPart = strrev($intPart);
		for($a=0;$a<strlen($intRevPart);$a++)	{
			$newString.=$parts[$a];
			$newString.=substr($intRevPart,$a,1);
		}
		$newString = strrev($newString);
			// Dec part
		$parts=explode("%",$newString);
		$finalString=$parts[0];
		for ($a=1;$a<count($parts);$a++)	{
			$finalString.=substr($decPart,$a-1,1).$parts[$a];
		}
		return $prefix.$finalString;
	}
}


?>