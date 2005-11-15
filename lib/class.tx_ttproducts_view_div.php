<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
/**
 * Part of the tt_products (Shopping System) extension.
 *
 * view functions
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Els Verberne <verberne@bendoo.nl>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_price.php');


class tx_ttproducts_view_div {


	/**
	 * Adds URL markers to a markerArray
	 */
	function addURLMarkers(&$pibase,&$conf,&$basket,$markerArray)	{
		global $TSFE;

			// Add's URL-markers to the $markerArray and returns it
		$pid = ( $conf['PIDbasket'] ? $conf['PIDbasket'] : $TSFE->id);
		$markerArray['###FORM_URL###'] = $pibase->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams()) ;	 // $this->getLinkUrl($this->conf['PIDbasket']);
		$pid = ( $conf['PIDinfo'] ? $conf['PIDinfo'] : ($conf['PIDbasket'] ? $conf['PIDbasket'] :$TSFE->id));
		$markerArray['###FORM_URL_INFO###'] = $pibase->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams()) ; // $this->getLinkUrl($this->conf['PIDinfo'] ? $this->conf['PIDinfo'] : $this->conf['PIDbasket']);
		$pid = ( $conf['PIDfinalize'] ? $conf['PIDfinalize'] : ($conf['PIDbasket'] ? $conf['PIDbasket'] : $TSFE->id));
		$markerArray['###FORM_URL_FINALIZE###'] = $pibase->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams()) ;// $this->getLinkUrl($this->conf['PIDfinalize'] ? $this->conf['PIDfinalize'] : $this->conf['PIDbasket']);
		$pid = ( $conf['PIDthanks'] ? $conf['PIDthanks'] : ($conf['PIDbasket'] ? $conf['PIDbasket'] : $TSFE->id));
		$markerArray['###FORM_URL_THANKS###'] = $pibase->pi_getPageLink($pid,'',tx_ttproducts_view_div::getLinkParams()) ;	 // $this->getLinkUrl($this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : $this->conf['PIDbasket']);
		$markerArray['###FORM_URL_TARGET###'] = '_self';
		if ($basket->basketExtra['payment.']['handleURL'])	{	// This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
			$markerArray['###FORM_URL_THANKS###'] = $basket->basketExtra['payment.']['handleURL'];
		}
		if ($basket->basketExtra['payment.']['handleTarget'])	{	// Alternative target
			$markerArray['###FORM_URL_TARGET###'] = $basket->basketExtra['payment.']['handleTarget'];
		}

		return $markerArray;
	} // addURLMarkers



	/**
	 * Returning template subpart marker
	 */
	function spMarker(&$pibase,&$conf,$subpartMarker)	{
		$sPBody = substr($subpartMarker,3,-3);
		$altSPM = '';
		if (isset($conf['altMainMarkers.']))	{
			$altSPM = trim($pibase->cObj->stdWrap($conf['altMainMarkers.'][$sPBody],$conf['altMainMarkers.'][$sPBody.'.']));
			$GLOBALS['TT']->setTSlogMessage('Using alternative subpart marker for "'.$subpartMarker.'": '.$altSPM,1);
		}
		return $altSPM ? $altSPM : $subpartMarker;
	} // spMarker


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param   array		information about the parent HTML form
	 * @return	string
	 * @access private
	 */
	function getItemMarkerArray (&$pibase, &$conf, &$config, &$item, &$basketExt, $catTitle, &$contentTable, $imageNum=0, $imageRenderObj='image', $forminfoArray = array())	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row

		$row = &$item['rec'];
		$markerArray=array();
			// Get image
		$theImgCode=array();

		$imgs = array();

		if ($conf['usePageContentImage']) {
			$pageContent = $contentTable->getFromPid($row['pid']);
			foreach ($pageContent as $pid => $contentRow) {
				if ($contentRow['image']) {
					$imgs[] = $contentRow['image'];
				}
			}
		} else {
			$imgs = explode(',',$row['image']);
		}

		while(list($c,$val)=each($imgs))	{
			if ($c==$imageNum)	break;
			if ($val)	{
				$conf[$imageRenderObj.'.']['file'] = 'uploads/pics/'.$val;
			} else {
				$conf[$imageRenderObj.'.']['file'] = $this->conf['noImageAvailable'];
			}
			$i = $c;
			if (!$conf['separateImage'])
			{
				$i = 0;  // show all images together as one image
			}
			$theImgCode[$i] .= $pibase->cObj->IMAGE($conf[$imageRenderObj.'.']);
		}

		$iconImgCode = $pibase->cObj->IMAGE($conf['datasheetIcon.']);

			// Subst. fields
/* mkl:
		if ( ($this->language > 0) && $row['o_title'] )	{
			$markerArray['###PRODUCT_TITLE###'] = $row['o_title'];
		}
		else  {
			$markerArray['###PRODUCT_TITLE###'] = $row['title'];
		}

		if ( ($this->language > 0) && $row['o_unit'] )	{
			$markerArray['###UNIT###'] = $row['o_unit'];
		}
		else  {
			$markerArray['###UNIT###'] = $row['unit'];
		}

*/
		$markerArray['###UNIT###'] = $row['unit'];
		$markerArray['###UNIT_FACTOR###'] = $row['unit_factor'];

		$markerArray['###ICON_DATASHEET###']=$iconImgCode;

		$markerArray['###PRODUCT_TITLE###'] = $row['title'];
		$markerArray['###PRODUCT_NOTE###'] = nl2br($row['note']);

//		if ( ($this->language > 0) && $row['o_note'] )	{
////			$markerArray['###PRODUCT_NOTE###'] = nl2br($row['o_note']);
//			$markerArray['###PRODUCT_NOTE###'] = $this->pi_RTEcssText($row['o_note']);
//		}
//		else  {
////			$markerArray['###PRODUCT_NOTE###'] = nl2br($row['note']);
//			$markerArray['###PRODUCT_NOTE###'] = $this->pi_RTEcssText($row['note']);
//		}

		if (is_array($conf['parseFunc.']))	{
			$markerArray['###PRODUCT_NOTE###'] = $pibase->cObj->parseFunc($markerArray['###PRODUCT_NOTE###'],$conf['parseFunc.']);
		}
		$markerArray['###PRODUCT_ITEMNUMBER###'] = $row['itemnumber'];

		$markerArray['###PRODUCT_IMAGE###'] = $theImgCode[0]; // for compatibility only

		while ((list($c,$val)=each($theImgCode)))
		{
			$markerArray['###PRODUCT_IMAGE' .  intval($c + 1) . '###'] = $theImgCode[$c];
		}

			// empty all image fields with no availble image
		for ($i=1; $i<=15; ++$i) {
			if (!$markerArray['###PRODUCT_IMAGE' .  $i. '###']) {
				$markerArray['###PRODUCT_IMAGE' .  $i. '###'] = '';
			}
		}

		$markerArray['###PRODUCT_SUBTITLE###'] = $row['subtitle'];
		$markerArray['###PRODUCT_WWW###'] = $row['www'];
		$markerArray['###PRODUCT_ID###'] = $row['uid'];

/* Added Els4: cur_sym moved from after product_special to this place, necessary to put currency symbol */
		$markerArray['###CUR_SYM###'] = ' '.($conf['currencySymbol'] ? $conf['currencySymbol'] : '');

		$markerArray['###PRICE_TAX###'] = $pibase->price->printPrice($pibase->price->priceFormat($item['priceTax']));
		$markerArray['###PRICE_NO_TAX###'] = $pibase->price->printPrice($pibase->price->priceFormat($item['priceNoTax']));

/* Added els4: printing of pric_no_tax with currency symbol (used in totaal-_.tmpl and winkelwagen.tmpl) */
		if ($row['category'] == $conf['creditsCategory']) {
			$markerArray['###PRICE_NO_TAX_CUR_SYM###'] = $pibase->price->printPrice($item['priceNoTax']);
		} else {
			$markerArray['###PRICE_NO_TAX_CUR_SYM###'] = $markerArray['###CUR_SYM###'].'&nbsp;'.$pibase->price->printPrice($pibase->price->priceFormat($item['priceNoTax']));
		}

		$oldPrice = $pibase->price->printPrice($pibase->price->priceFormat($pibase->price->getPrice($row['price'],1,$row['tax'])));
		$oldPriceNoTax = $pibase->price->printPrice($pibase->price->priceFormat($pibase->price->getPrice($row['price'],0,$row['tax'])));
		$price2 = $pibase->price->printPrice($pibase->price->priceFormat($pibase->price->getPrice($row['price2'],1,$row['tax'])));
		$price2NoTax = $pibase->price->printPrice($pibase->price->priceFormat($pibase->price->getPrice($row['price2'],0,$row['tax'])));
		$priceNo = intval($config['priceNoReseller']);
		if ($priceNo == 0) {	// no old price will be shown when the new price has not been reducted
			$oldPrice = $oldPriceNoTax = '';
		}

		$markerArray['###OLD_PRICE_TAX###'] = $oldPrice;
		$markerArray['###OLD_PRICE_NO_TAX###'] = $oldPriceNoTax;
		$markerArray['###PRICE2_TAX###'] = $price2;
		$markerArray['###PRICE2_NO_TAX###'] = $price2NoTax;

/* Added els4: changed whole block: if OLD_PRICE_NO_TAX = 0 then print PRICE_NO_TAX and set PRICE_NO_TAX to empty,
/* Added els4: Markers SUB_NO_DISCOUNT and SUB_DISCOUNT used in detail template
		calculating with $item['priceNoTax'] */
/* Added els4: Exceptions for category = kurkenshop */
		if ($oldPriceNoTax == '0.00') {
			$markerArray['###OLD_PRICE_NO_TAX_CLASS###'] = 'rightalign';
			$markerArray['###OLD_PRICE_NO_TAX###'] = $price2NoTax;
			if ($row['category'] == $conf['creditsCategory']) {
				$markerArray['###CUR_SYM###'] ="";
				$markerArray['###OLD_PRICE_NO_TAX###'] = number_format($price2NoTax,0)."&nbsp;<img src='fileadmin/html/img/bullets/kurk.gif' width='17' height='17'>";
			}
			$markerArray['###PRICE_NO_TAX###'] = "";
/* Added els8: pric_no_tax with currency symbol empty (used in totaal-_.tmpl and winkelwagen.tmpl) */
			$markerArray['###PRICE_NO_TAX_CUR_SYM###'] = '';

			$markerArray['###DETAIL_PRICE_ITEMLIST###'] = '<span class="flesprijs">flesprijs&nbsp;'.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###OLD_PRICE_NO_TAX###'].'</span>';
			$markerArray['###DETAIL_PRICE_ITEMLIST_PRESENT###'] = '<span class="flesprijs">prijs&nbsp;'.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###OLD_PRICE_NO_TAX###'].'</span>';
			$markerArray['###DETAIL_PRICE_ITEMSINGLE###'] = '<p><span class="flesprijs"><nobr>flesprijs&nbsp;'.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###OLD_PRICE_NO_TAX###'].'</nobr></span></p>';
			$markerArray['###DETAIL_PRICE_ITEMSINGLE_PRESENT###'] = '<p><span class="flesprijs"><nobr>prijs&nbsp;'.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###OLD_PRICE_NO_TAX###'].'</nobr></span></p>';
		} else {
			$markerArray['###OLD_PRICE_NO_TAX_CLASS###'] = 'prijsvan';
			$markerArray['###OLD_PRICE_NO_TAX###'] = $oldPriceNoTax;
			if ($row['category'] == $conf['creditsCategory']) {
				$markerArray['###CUR_SYM###'] ="";
				$markerArray['###OLD_PRICE_NO_TAX###'] = $pibase->price->getPrice($row['price'],0,$row['tax'])."&nbsp;<img src='fileadmin/html/img/bullets/kurk.gif' width='17' height='17'>";
			}
			$markerArray['###DETAIL_PRICE_ITEMLIST###'] = '<span class="prijsvan">van&nbsp; '.$markerArray['###OLD_PRICE_NO_TAX###'].'</span> <span class="prijsvoor">voor '.$markerArray['###PRICE_NO_TAX###'].'</span>';
			$markerArray['###DETAIL_PRICE_ITEMLIST_PRESENT###'] = '<span class="prijsvan">van&nbsp; '.$markerArray['###OLD_PRICE_NO_TAX###'].'</span> <span class="prijsvoor">voor '.$markerArray['###PRICE_NO_TAX###'].'</span>';
			$markerArray['###DETAIL_PRICE_ITEMSINGLE###'] = '<p class="prijsvan">van&nbsp; '.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###OLD_PRICE_NO_TAX###'].'</p> <p class="prijsvoor"><nobr>voor '.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###PRICE_NO_TAX###'].'</nobr></p>';
			$markerArray['###DETAIL_PRICE_ITEMSINGLE_PRESENT###'] = '<p class="prijsvan">van&nbsp; '.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###OLD_PRICE_NO_TAX###'].'</p> <p class="prijsvoor"><nobr>voor '.$markerArray['###CUR_SYM###'].'&nbsp;'.$markerArray['###PRICE_NO_TAX###'].'</nobr></p>';
		}

		$markerArray['###PRODUCT_INSTOCK_UNIT###'] = '';
		if ($row['inStock'] <> 0) {
			$markerArray['###PRODUCT_INSTOCK###'] = $row['inStock'];
			$markerArray['###PRODUCT_INSTOCK_UNIT###'] = $conf['inStockPieces'];
		} else {
			$markerArray['###PRODUCT_INSTOCK###'] = $conf['notInStockMessage'];
		}

		$markerArray['###CATEGORY_TITLE###'] = $catTitle;

		$markerArray['###FIELD_NAME###']='ttp_basket['.$row['uid'].'][quantity]';

//		$markerArray["###FIELD_NAME###"]="recs[tt_products][".$row["uid"]."]";

		$temp = tx_ttproducts_article_div::getVariantFromRow ($row);
		$temp = $basketExt[$row['uid']][$temp];

		$markerArray['###FIELD_QTY###']= $temp ? $temp : '';
		$markerArray['###FIELD_NAME_BASKET###']='ttp_basket['.$row['uid'].']['.md5($row['extVars']).']';

		$markerArray['###FIELD_SIZE_NAME###']='ttp_basket['.$row['uid'].'][size]';
		$markerArray['###FIELD_SIZE_VALUE###']=$row['size'];
		$markerArray['###FIELD_SIZE_ONCHANGE']= ''; // TODO:  use $forminfoArray['###FORM_NAME###' in something like onChange="Go(this.form.Auswahl.options[this.form.Auswahl.options.selectedIndex].value)"

		$markerArray['###FIELD_COLOR_NAME###']='ttp_basket['.$row['uid'].'][color]';
		$markerArray['###FIELD_COLOR_VALUE###']=$row['color'];

		$markerArray['###FIELD_ACCESSORY_NAME###']='ttp_basket['.$row['uid'].'][accessory]';
		$markerArray['###FIELD_ACCESSORY_VALUE###']=$row['accessory'];

		$markerArray['###FIELD_GRADINGS_NAME###']='ttp_basket['.$row['uid'].'][gradings]';
		$markerArray['###FIELD_GRADINGS_VALUE###']=$row['gradings'];

/* Added Els4: total price is quantity multiplied with pricenottax mulitplied with unit_factor (exception for kurkenshop), _credits is necessary for "kurkenshop", without decimal and currency symbol */
		if ($row['category'] == $conf['creditsCategory']) {
			$markerArray['###PRICE_ITEM_X_QTY###'] = $pibase->price->printPrice($markerArray['###FIELD_QTY###']*$item['priceNoTax']*$row['unit_factor']);
		} else {
/* Added Els8: &nbsp; -> space */
			$markerArray['###PRICE_ITEM_X_QTY###'] = $markerArray['###CUR_SYM###'].' '.$pibase->price->printPrice($pibase->price->priceFormat($markerArray['###FIELD_QTY###']*$item['priceNoTax']*$row['unit_factor']));
		}

		$prodColorText = '';
		$prodTmp = explode(';', $row['color']);
		if ($conf['selectColor']) {
			foreach ($prodTmp as $prodCol)
				$prodColorText = $prodColorText . '<OPTION value="'.$prodCol.'">'.$prodCol.'</OPTION>';
		} else {
			$prodColorText = $prodTmp[0];
		}

		$prodSizeText = '';
		$prodTmp = explode(';', $row['size']);
		if ($conf['selectSize']) {
			foreach ($prodTmp as $prodSize) {
				$prodSizeText = $prodSizeText . '<OPTION value="'.$prodSize.'">'.$prodSize.'</OPTION>';
			}
		} else {
			$prodSizeText = $prodTmp[0];
		}

//		$prodAccessoryText = '';
//		$prodTmp = explode(';', $row['accessory']);
//		if ($conf['selectAccessory']) {
//			$message = $this->pi_getLL('accessory no');
//			$prodAccessoryText =  '<OPTION value="0">'.$message.'</OPTION>';
//			$message = $this->pi_getLL('accessory yes');
//			$prodAccessoryText .= '<OPTION value="1">'.$message.'</OPTION>';
//		} else {
//			$prodAccessoryText = $prodTmp;
//		}

		$prodGradingsText = '';
		$prodTmp = explode(';', $row['gradings']);
		if ($conf['selectGradings']) {
			foreach ($prodTmp as $prodGradings) {
				$prodGradingsText = $prodGradingsText . '<OPTION value="'.$prodGradings.'">'.$prodGradings.'</OPTION>';
			}
		} else {
			$prodGradingsText = $prodTmp[0];
		}

		$markerArray['###PRODUCT_WEIGHT###'] = doubleval($row['weight']);
		$markerArray['###BULKILY_WARNING###'] = $row['bulkily'] ? $conf['bulkilyWarning'] : '';
		$markerArray['###PRODUCT_COLOR###'] = $prodColorText;
		$markerArray['###PRODUCT_SIZE###'] = $prodSizeText;
		//$markerArray['###PRODUCT_ACCESSORY###'] = $prodAccessoryText;
		$markerArray['###PRODUCT_GRADINGS###'] = $prodGradingsText;
	//	$markerArray['###PRICE_ACCESSORY_TAX###'] = $pibase->price->printPrice($pibase->price->priceFormat($pibase->price->getPrice($row['accessory'.$config['priceNoReseller']],1,$row['tax'])));
	//	$markerArray['###PRICE_ACCESSORY_NO_TAX###'] = $pibase->price->printPrice($pibase->price->priceFormat($pibase->price->getPrice($row['accessory'.$config['priceNoReseller']],0,$row['tax'])));
	//	$markerArray['###PRICE_WITH_ACCESSORY_TAX###'] = $pibase->price->printPrice($pibase->price->priceFormat($pibase->price->getPrice($row['accessory'.$conf['priceNoReseller']]+$row['price'.$tconfig['priceNoReseller']],1,$row['tax'])));
	//	$markerArray['###PRICE_WITH_ACCESSORY_NO_TAX###'] = $pibase->price->printPrice($pibase->price->priceFormat($pibase->price->getPrice($row['accessory'.$conf['priceNoReseller']]+$row['price'.$config['priceNoReseller']],0,$row['tax'])));

		if ($row['special_preparation'])
			$markerArray['###PRODUCT_SPECIAL_PREP###'] = $pibase->cObj->substituteMarkerArray($conf['specialPreparation'],$markerArray);
		else
			$markerArray['###PRODUCT_SPECIAL_PREP###'] = '';

/* 		Added els4: cur_sym moved to above (after product_id)*/
			// Fill the Currency Symbol or not

		if ($conf['itemMarkerArrayFunc'])	{
			$markerArray = $this->userProcess('itemMarkerArrayFunc',$markerArray);
		}
		return $markerArray;
	} // getItemMarkerArray



	/**
	 * Returns a url for use in forms and links
	 */
	function getLinkParams($excludeList='',$addQueryString=array()) {
		global $TSFE;
		$queryString=array();
		$queryString['backPID']= $TSFE->id;
		$temp = t3lib_div::GPvar('C') ? t3lib_div::GPvar('C') : $this->currency;
		if ($temp)	{
			$queryString['C'] = $temp;
		}
		$temp =   t3lib_div::_GP('begin_at');
		if ($temp) {
			$queryString['begin_at'] = $temp;
		}
		$temp = t3lib_div::_GP('swords') ? rawurlencode(t3lib_div::_GP('swords')) : '';
		if ($temp) {
			$queryString['swords'] = $temp;
		}
		$temp = t3lib_div::GPvar('newitemdays') ? rawurlencode(stripslashes(t3lib_div::GPvar('newitemdays'))) : '';
		if ($temp) {
			$queryString['newitemdays'] = $temp;
		}
		foreach ($addQueryString as $param => $value){
			$queryString[$param] = $value;
		}
		reset($queryString);
		while(list($key,$val)=each($queryString))	{
			if (!$val || ($excludeList && t3lib_div::inList($excludeList,$key)))	{
				unset($queryString[$key]);
			}
		}

		return $queryString;
	}



}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_view_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_view_div.php']);
}


?>
