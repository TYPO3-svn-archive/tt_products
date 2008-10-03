<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2001 Kasper Skårhøj (kasper@curbysoft.dk)
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
/***************************************************************

products.inc

Creates a list of products for the shopping basket in Typo3.


TypoScript config:

.templateFile	[resource]				The template-file. Default is "media/scripts/products_template.tmpl"
.pid_list		[string /stdWrap]
.recursive		[boolean /stdWrap]
.image			[IMAGE cObject] for displaying the images of products.
.code			[string / stdWrap]
.limit		

.wrap1			
.wrap2			
.parseFunc		[->parsefunc]


.....	(undocumented... see static_template "content.tt_products")

.displayCurrentRecord	[boolean]		If set, certain settings are manipulated in order to let the script render a single item - the $cObj->data.

****************************************************************/

$content="";
$GLOBALS[tt]->tt("PRODUCTS.INC: start","PRODUCTS.INC_start");

//debug();

set_no_cache();


			
// *************************************
// *** getting configuration values:
// *************************************

		// getting configuration values:
	$config[code] = strtolower(trim($this->stdWrap($conf["code"],$conf["code."])));
	$config[limit] = intInRange($conf["limit"],0,1000);
	$config[limit] = $config[limit] ? $config[limit] : 50;
	
	$config[pid_list] = trim($this->stdWrap($conf["pid_list"],$conf["pid_list."]));
	$config[pid_list] = $config[pid_list] ? $config[pid_list] : $GLOBALS[id];

	$config[recursive] = $this->stdWrap($conf["recursive"],$conf["recursive."]);
	$config[storeRootPid] = $conf["PIDstoreRoot"] ? $conf["PIDstoreRoot"] : $GLOBALS[tmpl]->rootLine[0][uid];

		// If the current record should be displayed.
	$config["displayCurrentRecord"] = $conf["displayCurrentRecord"];
	if ($config["displayCurrentRecord"])	{
		$config["code"]="";
		$tt_product_single = true;
	} else {
		$tt_product_single = $GLOBALS["HTTP_GET_VARS"]["tt_products"];
	}
	
	$splitMark = md5(uniqid(""));
	$globalMarkerArray=array();
	list($globalMarkerArray["###GLOBAL_WRAP_1_BEGIN###"],$globalMarkerArray["###GLOBAL_WRAP_1_END###"]) = explode($splitMark,$this->stdWrap($splitMark,$conf["wrap1."]));
	list($globalMarkerArray["###GLOBAL_WRAP_2_BEGIN###"],$globalMarkerArray["###GLOBAL_WRAP_2_END###"]) = explode($splitMark,$this->stdWrap($splitMark,$conf["wrap2."]));
	$globalMarkerArray["###GLOBAL_COLOR_1###"] = $this->stdWrap($conf["color1"],$conf["color1."]);
	$globalMarkerArray["###GLOBAL_COLOR_2###"] = $this->stdWrap($conf["color2"],$conf["color2."]);
	
	$templateCode = $this->fileResource($conf[templateFile] ? $conf[templateFile] : "media/scripts/products_template.tmpl");

		// Create products object
	$prodObj = new ts_products;
	$prodObj->init($this,$config[pid_list],$conf);
	$prodObj->initCategories();	
	$prodObj->initBasket($GLOBALS["fe_user"]->getKey("ses","recs"));


	
	/*
	

  listImage < .image
  listImage.file.maxW =100
  listImage.file.maxH =100

  wrap1 = {$content.tt_products.wrap1}
  wrap2 = {$content.tt_products.wrap2}
  generalColor = {$content.tt_products.color.items}
  headerColor = {$content.tt_products.color.header}
  parseFunc < styles.content.parseFunc

  categoryHeader = TEXT
  categoryHeader.current = 1	

	*/
	
	
// *************************************
// *** Listing items:
// *************************************

$theCode = (string)strtoupper(trim($config[code]));
switch($theCode)	{
	case "BASKET":
		$prodObj->setPidlist($config[storeRootPid]);
		$prodObj->initRecursive(999);
		$prodObj->generatePageArray();
		
		if (count($prodObj->basket))	{
		 	$query = "SELECT * FROM tt_products WHERE uid IN (".$prodObj->uid_list.") AND pid IN (".$prodObj->pid_list.")".$this->enableFields("tt_products");
			$res = mysql($GLOBALS[typo_db],$query);
			echo mysql_error();
			$productsArray=array();
			while($row=mysql_fetch_array($res))		{
				$productsArray[$row["pid"]][]=$row;
			}
			
			$t=array();
			$t[basketFrameWork] = $this->getSubpart($templateCode,"###BASKET_TEMPLATE###");
			$t[categoryTitle] = $this->getSubpart($t[basketFrameWork],"###ITEM_CATEGORY###");
			$t[itemFrameWork] = $this->getSubpart($t[basketFrameWork],"###ITEM_LIST###");
			$t[item] = $this->getSubpart($t[itemFrameWork],"###ITEM_SINGLE###");

			$pageArr=explode(",",$prodObj->pid_list);
			$currentP="";
			$out="";
			$bTotal=0;
			while(list(,$v)=each($pageArr))	{
				if (is_array($productsArray[$v]))	{
					reset($productsArray[$v]);
					$itemsOut="";
					while(list(,$row)=each($productsArray[$v]))	{
							// Print Category Title
						if ($row["pid"]."_".$row["category"]!=$currentP)	{
							if ($itemsOut)	{
								$out.=$this->substituteSubpart($t[itemFrameWork], "###ITEM_SINGLE###", $itemsOut);
							}
							$itemsOut="";			// Clear the item-code var
							$currentP = $row["pid"]."_".$row["category"];
							if ($conf["displayBasketCatHeader"])	{
								$markerArray=array();
								$catTitle= $prodObj->pageArray[$row[pid]]["title"].($row["category"]?"/".$prodObj->categories[$row["category"]]:"");
								$this->setCurrentVal($catTitle);
								$markerArray["###CATEGORY_TITLE###"]=$this->cObjGetSingle($conf["categoryHeader"],$conf["categoryHeader."]);
								$out.= $this->substituteMarkerArray($t[categoryTitle], $markerArray);
							}
						}
							// Print Item Title
						$item=$t[item];
						$item = $this->substituteSubpart($item, "###LINK_ITEM###", array('<A href="'.$prodObj->getLinkUrl($conf["PIDitemDisplay"]).'&tt_products='.$row[uid].'">','</A>')); 
						$markerArray = $prodObj->getItemMarkerArray ($row,$catTitle,1,"basketImage");
						$iTotal=$row["price"]*$prodObj->basket[$row["uid"]];
						$bTotal+=$iTotal;
						$markerArray["###PRICE_TOTAL_TAX###"]=$prodObj->priceFormat($iTotal);
						$itemsOut.= $this->substituteMarkerArray($item, $markerArray);
					}
					if ($itemsOut)	{
						$out.=$this->substituteSubpart($t[itemFrameWork], "###ITEM_SINGLE###", $itemsOut);
					}
				}
			}
			$out=$this->substituteSubpart($t[basketFrameWork], "###ITEM_CATEGORY_AND_ITEMS###", $out);
			$out=$this->substituteMarker($out, "###FORM_URL###", $prodObj->getLinkUrl($conf["PIDbasket"]));
			$out=$this->substituteMarker($out, "###PRICE_TOTAL_TAX###", $prodObj->priceFormat($bTotal));
			$content.=$out;
		} else {
			$content.=$this->getSubpart($templateCode,"###BASKET_TEMPLATE_EMPTY###");
		}
		$content= $this->substituteMarkerArray($content, $globalMarkerArray);
	break;
	case "SEARCH":
	default:
		if ($tt_product_single)	{
	// List single product:			
				// performing query:
			$prodObj->setPidlist($config[storeRootPid]);
			$prodObj->initRecursive(999);
			$prodObj->generatePageArray();
			
		 	$query = "SELECT * FROM tt_products WHERE uid=".intval($tt_product_single).$this->enableFields("tt_products");
			$res = mysql($GLOBALS[typo_db],$query);
			echo mysql_error();

			if($config["displayCurrentRecord"] || $row=mysql_fetch_array($res))		{
				$item ="";
				if ($config["displayCurrentRecord"])	{
					$row=$this->data;
					$item = trim($this->getSubpart($templateCode,"###ITEM_SINGLE_DISPLAY_RECORDINSERT###"));
				}
				$catTitle= $prodObj->pageArray[$row[pid]]["title"].($row["category"]?"/".$prodObj->categories[$row["category"]]:"");
				if (!$item)	{$item = $this->getSubpart($templateCode,"###ITEM_SINGLE_DISPLAY###");}
				$item = $this->substituteSubpart($item, "###LINK_ITEM###", array('<A href="'.$prodObj->getLinkUrl($GLOBALS["backPID"]).'">','</A>')); 
				$item=$this->substituteMarker($item, "###FORM_URL###", $prodObj->getLinkUrl($conf["PIDbasket"]));
				$markerArray = $prodObj->getItemMarkerArray ($row,$catTitle,10);
				$content= $this->substituteMarkerArray($item, $markerArray);
			}
		} else {		
			$content="";
	// List products:
			$where="";			
			if ($theCode=="SEARCH")	{
				$t[search] = $this->getSubpart($templateCode,"###ITEM_SEARCH###");
				$out=$t[search];				
				$out=$this->substituteMarker($out, "###FORM_URL###", $prodObj->getLinkUrl($conf["PIDsearch"]));
				$out=$this->substituteMarker($out, "###SWORDS###", htmlspecialchars($GLOBALS["swords"]));
				$content.=$out;
				if ($GLOBALS["swords"])	{
					$where = $prodObj->searchWhere(trim($GLOBALS["swords"]));
				}
			}
			$begin_at=intInRange($GLOBALS["begin_at"],0,100000);
			if (($theCode!="SEARCH" && !$GLOBALS["swords"]) || $where)	{
			
				$prodObj->initRecursive($config[recursive]);
				$prodObj->generatePageArray();
	
					// Get products
				$selectConf = Array();
				$selectConf["pidInList"] = $prodObj->pid_list;
				$selectConf["orderBy"] = "pid,category,title";
				$selectConf["where"] = "1=1 ".$where;
//			debug($selectConf);
					// performing query:
			 	$query = $this->getQuery("tt_products",$selectConf);
				$query.=" LIMIT ".$begin_at.",".($config["limit"]+1);
//debug($query);
				$res = mysql($GLOBALS[typo_db],$query);
				echo mysql_error();
				$productsArray=array();
				while($row=mysql_fetch_array($res))		{
					$productsArray[$row["pid"]][]=$row;
				}
				
					// Print list:
				$t[listFrameWork] = $this->getSubpart($templateCode,"###ITEM_LIST_TEMPLATE###");
				$t[categoryTitle] = $this->getSubpart($t[listFrameWork],"###ITEM_CATEGORY###");
				$t[itemFrameWork] = $this->getSubpart($t[listFrameWork],"###ITEM_LIST###");
				$t[item] = $this->getSubpart($t[itemFrameWork],"###ITEM_SINGLE###");
				
				$pageArr=explode(",",$prodObj->pid_list);
				$currentP="";
				$out="";
				$iCount=0;
				$more=0;		// If set during this loop, the next-item is drawn
				while(list(,$v)=each($pageArr))	{
					if (is_array($productsArray[$v]))	{
						reset($productsArray[$v]);
						$itemsOut="";
						while(list(,$row)=each($productsArray[$v]))	{
							$iCount++;
							if ($iCount>$config[limit])	{
								$more=1;
								break;
							}


								// Print Category Title
							if ($row["pid"]."_".$row["category"]!=$currentP)	{
								if ($itemsOut)	{
									$out.=$this->substituteSubpart($t[itemFrameWork], "###ITEM_SINGLE###", $itemsOut);
								}
								$itemsOut="";			// Clear the item-code var
			
								$currentP = $row["pid"]."_".$row["category"];
								if ($where || $conf["displayListCatHeader"])	{
									$markerArray=array();
									$catTitle= $prodObj->pageArray[$row[pid]]["title"].($row["category"]?"/".$prodObj->categories[$row["category"]]:"");
									$this->setCurrentVal($catTitle);
									$markerArray["###CATEGORY_TITLE###"]=$this->cObjGetSingle($conf["categoryHeader"],$conf["categoryHeader."]);
									$out.= $this->substituteMarkerArray($t[categoryTitle], $markerArray);
								}
							}
	
								// Print Item Title
							$item=$t[item];
							$item = $this->substituteSubpart($item, "###LINK_ITEM###", array('<A href="'.$prodObj->getLinkUrl($conf["PIDitemDisplay"]).'&tt_products='.$row[uid].'">','</A>')); 
							$markerArray = $prodObj->getItemMarkerArray ($row,$catTitle,1,"listImage");
							$itemsOut.= $this->substituteMarkerArray($item, $markerArray);
						}
						if ($itemsOut)	{
							$out.=$this->substituteSubpart($t[itemFrameWork], "###ITEM_SINGLE###", $itemsOut);
						}
					}
				}
			}
			if ($out)	{
					// next / prev.
				$url = $prodObj->getLinkUrl("","begin_at");
				if ($more)	{
					$next = $begin_at+$config["limit"];
					$t[listFrameWork] = $this->substituteSubpart($t[listFrameWork], "###LINK_NEXT###", '<A href="'.$url.'&begin_at='.$next.'">'.$this->getSubpart($t[listFrameWork], "###LINK_NEXT###").'</A>'); 
				} else {
					$t[listFrameWork] = $this->substituteSubpart($t[listFrameWork], "###LINK_NEXT###", ''); 
				}
				if ($begin_at)	{
					$prev = $begin_at-$config["limit"];
					$t[listFrameWork] = $this->substituteSubpart($t[listFrameWork], "###LINK_PREV###", '<A href="'.$url.'&begin_at='.$prev.'">'.$this->getSubpart($t[listFrameWork], "###LINK_PREV###").'</A>'); 
				} else {
					$t[listFrameWork] = $this->substituteSubpart($t[listFrameWork], "###LINK_PREV###", ''); 
				}

					// 
				$out=$this->substituteSubpart($t[listFrameWork], "###ITEM_CATEGORY_AND_ITEMS###", $out);
				$out=$this->substituteMarker($out, "###FORM_URL###", $prodObj->getLinkUrl($conf["PIDbasket"]));
				$content.=$out;
			} elseif ($where)	{
				$content.=$this->getSubpart($templateCode,"###ITEM_SEARCH_EMPTY###");
			}
		}
		$content= $this->substituteMarkerArray($content, $globalMarkerArray);
	break;
}
	
$GLOBALS[tt]->endtt("PRODUCTS.INC_start");
$RESTORE_OLD_DATA = 1;		// secures a restore of the original ->data-array

?>