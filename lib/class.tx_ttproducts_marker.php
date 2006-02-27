<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_marker {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $basket;


	/**
	 * Initialized the marker object
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
 	 */

	function init(&$pibase, &$conf, &$config, &$basket)	{
 		$this->pibase = &$pibase;
 		$this->conf = &$conf;
 		$this->config = &$config;
 		$this->basket = &$basket;
	}


	/**
	 * Adds URL markers to a markerArray
	 */
	function addURLMarkers($pidNext,$markerArray,$addQueryString=array())	{
		global $TSFE;

			// Add's URL-markers to the $markerArray and returns it
		$pidBasket = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);
		$pidFormUrl = ($pidNext ? $pidNext : $pidBasket);
		$markerArray['###FORM_URL###'] = $this->pibase->pi_getPageLink($pidFormUrl,'',$this->getLinkParams('',$addQueryString)) ;
		$pid = ( $this->conf['PIDinfo'] ? $this->conf['PIDinfo'] : $pidBasket);
		$markerArray['###FORM_URL_INFO###'] = $this->pibase->pi_getPageLink($pid,'',$this->getLinkParams('',$addQueryString)) ;
		$pid = ( $this->conf['PIDfinalize'] ? $this->conf['PIDfinalize'] : $pidBasket);
		$markerArray['###FORM_URL_FINALIZE###'] = $this->pibase->pi_getPageLink($pid,'',$this->getLinkParams('',$addQueryString)) ;
		$pid = ( $this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : $pidBasket);
		$markerArray['###FORM_URL_THANKS###'] = $this->pibase->pi_getPageLink($pid,'',$this->getLinkParams('',$addQueryString)) ;
		$markerArray['###FORM_URL_TARGET###'] = '_self';

		// This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
		if ($this->basket->basketExtra['payment.']['handleURL'])	{
			$markerArray['###FORM_URL_THANKS###'] = $this->basket->basketExtra['payment.']['handleURL'];
		}
		if ($this->basket->basketExtra['payment.']['handleTarget'])	{	// Alternative target
			$markerArray['###FORM_URL_TARGET###'] = $this->basket->basketExtra['payment.']['handleTarget'];
		}
		
			// Call all addURLMarkers hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['addURLMarkers'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['addURLMarkers'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addURLMarkers')) {
					$hookObj->addURLMarkers($pidNext,$markerArray,$addQueryString);
				}
			}
		}

		return $markerArray;
	} // addURLMarkers



	/**
	 * Returning template subpart marker
	 */
	function spMarker($subpartMarker)	{
		$sPBody = substr($subpartMarker,3,-3);
		$altSPM = '';
		if (isset($this->conf['altMainMarkers.']))	{
			$altSPM = trim($this->pibase->cObj->stdWrap($this->conf['altMainMarkers.'][$sPBody],$this->conf['altMainMarkers.'][$sPBody.'.']));
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
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	function getItemMarkerArray (&$item, $catTitle, &$tt_products, &$tt_content, $imageNum=0, $imageRenderObj='image', $forminfoArray=array())	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row

		$row = &$item['rec'];
		if ($this->conf['useArticles'])	{
			$variantRow = &$row;
		} else {
			$variantRow = array();
		}
		$variants = $tt_products->variant->getVariantFromRow ($variantRow);
		$markerArray=array();
		
			// Get image
		$theImgCode=array();

		$imgs = array();

		if ($this->conf['usePageContentImage']) {
			$pageContent = $tt_content->getFromPid($row['pid']);
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
				$this->conf[$imageRenderObj.'.']['file'] = 'uploads/pics/'.$val;
			} else {
				$this->conf[$imageRenderObj.'.']['file'] = $this->conf['noImageAvailable'];
			}
			$i = $c;
			if (!$this->conf['separateImage'])
			{
				$i = 0;  // show all images together as one image
			}
			$theImgCode[$i] .= $this->pibase->cObj->IMAGE($this->conf[$imageRenderObj.'.']);
		}

		$iconImgCode = $this->pibase->cObj->IMAGE($this->conf['datasheetIcon.']);

			// Subst. fields
		$markerArray['###PRODUCT_UNIT###'] = $row['unit'];
		$markerArray['###PRODUCT_UNIT_FACTOR###'] = $row['unit_factor'];

		$markerArray['###ICON_DATASHEET###']=$iconImgCode;

		$markerArray['###PRODUCT_TITLE###'] = $row['title'];
		$markerArray['###PRODUCT_NOTE###'] = ($this->conf['nl2brNote'] ? nl2br($row['note']) : $row['note']);

			// Extension CSS styled content
		if (t3lib_extMgm::isLoaded('css_styled_content')) {
			$markerArray['###PRODUCT_NOTE###'] = $this->pibase->pi_RTEcssText($markerArray['###PRODUCT_NOTE###']);
		} else if (is_array($this->conf['parseFunc.']))	{
			$markerArray['###PRODUCT_NOTE###'] = $this->pibase->cObj->parseFunc($markerArray['###PRODUCT_NOTE###'],$this->conf['parseFunc.']);
		}
		$markerArray['###PRODUCT_ITEMNUMBER###'] = $row[$this->pibase->tt_products->fields['itemnumber']];

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
		$markerArray['###CUR_SYM###'] = ' '.($this->conf['currencySymbol'] ? $this->conf['currencySymbol'] : '');

		$markerArray['###PRICE_TAX###'] = $this->pibase->price->printPrice($this->pibase->price->priceFormat($item['priceTax']));
		$markerArray['###PRICE_NO_TAX###'] = $this->pibase->price->printPrice($this->pibase->price->priceFormat($item['priceNoTax']));
		$markerArray['###PRICE_ONLY_TAX###'] = $this->pibase->price->printPrice($this->pibase->price->priceFormat($item['priceTax']-$item['priceNoTax']));

/* Added els4: printing of pric_no_tax with currency symbol (used in totaal-_.tmpl and winkelwagen.tmpl) */
		if ($row['category'] == $this->conf['creditsCategory']) {
			$markerArray['###PRICE_NO_TAX_CUR_SYM###'] = $this->pibase->price->printPrice($item['priceNoTax']);
		} else {
			$markerArray['###PRICE_NO_TAX_CUR_SYM###'] = $markerArray['###CUR_SYM###'].'&nbsp;'.$this->pibase->price->printPrice($this->pibase->price->priceFormat($item['priceNoTax']));
		}

		$oldPrice = $this->pibase->price->printPrice($this->pibase->price->priceFormat($this->pibase->price->getPrice($row['price'],1,$row['tax'])));
		$oldPriceNoTax = $this->pibase->price->printPrice($this->pibase->price->priceFormat($this->pibase->price->getPrice($row['price'],0,$row['tax'])));
		$price2 = $this->pibase->price->printPrice($this->pibase->price->priceFormat($this->pibase->price->getPrice($row['price2'],1,$row['tax'])));
		$price2NoTax = $this->pibase->price->printPrice($this->pibase->price->priceFormat($this->pibase->price->getPrice($row['price2'],0,$row['tax'])));
		$priceNo = intval($this->config['priceNoReseller']);
		if ($priceNo == 0) {	// no old price will be shown when the new price has not been reducted
			$oldPrice = $oldPriceNoTax = '';
		}

		$markerArray['###OLD_PRICE_TAX###'] = $oldPrice;
		$markerArray['###OLD_PRICE_NO_TAX###'] = $oldPriceNoTax;
		$markerArray['###PRICE2_TAX###'] = $price2;
		$markerArray['###PRICE2_NO_TAX###'] = $price2NoTax;

		$markerArray['###PRODUCT_INSTOCK_UNIT###'] = '';
		if ($row['inStock'] <> 0) {
			$markerArray['###PRODUCT_INSTOCK###'] = $row['inStock'];
			$markerArray['###PRODUCT_INSTOCK_UNIT###'] = $this->conf['inStockPieces'];
		} else {
			$markerArray['###PRODUCT_INSTOCK###'] = $this->conf['notInStockMessage'];
		}

		$markerArray['###CATEGORY_TITLE###'] = $catTitle;

		$basketQuantityName = 'ttp_basket['.$row['uid'].'][quantity]';
		
		$markerArray['###FIELD_NAME###']=$basketQuantityName;

//		$markerArray["###FIELD_NAME###"]="recs[tt_products][".$row["uid"]."]";

		$quantity = $this->basket->basketExt[$row['uid']][$variants];

		$markerArray['###FIELD_QTY###']= $quantity ? $quantity : '';

		$markerArray['###FIELD_NAME_BASKET###']='ttp_basket['.$row['uid'].']['.md5($row['extVars']).']';

		$markerArray['###FIELD_SIZE_NAME###']='ttp_basket['.$row['uid'].'][size]';
		$markerArray['###FIELD_SIZE_VALUE###']=$row['size'];
		$markerArray['###FIELD_SIZE_ONCHANGE']= ''; // TODO:  use $forminfoArray['###FORM_NAME###' in something like onChange="Go(this.form.Auswahl.options[this.form.Auswahl.options.selectedIndex].value)"

		$markerArray['###FIELD_COLOR_NAME###']='ttp_basket['.$row['uid'].'][color]';
		$markerArray['###FIELD_COLOR_VALUE###']=$row['color'];

		$markerArray['###FIELD_DESCRIPTION_NAME###']='ttp_basket['.$row['uid'].'][description]';
		$markerArray['###FIELD_DESCRIPTION_VALUE###']=$row['description'];

		$markerArray['###FIELD_GRADINGS_NAME###']='ttp_basket['.$row['uid'].'][gradings]';
		$markerArray['###FIELD_GRADINGS_VALUE###']=$row['gradings'];

		$markerArray['###FIELD_ADDITIONAL_NAME###']='ttp_basket['.$row['uid'].'][additional]';
//		$markerArray['###FIELD_ADDITIONAL_VALUE###']=$row['additional'];

/* Added Els4: total price is quantity multiplied with pricenottax mulitplied with unit_factor (exception for kurkenshop), _credits is necessary for "kurkenshop", without decimal and currency symbol */
		if ($row['category'] == $this->conf['creditsCategory']) {
			$markerArray['###PRICE_ITEM_X_QTY###'] = $this->pibase->price->printPrice($markerArray['###FIELD_QTY###']*$item['priceNoTax']*$row['unit_factor']);
		} else {
/* Added Els8: &nbsp; -> space */
			$markerArray['###PRICE_ITEM_X_QTY###'] = $markerArray['###CUR_SYM###'].' '.$this->pibase->price->printPrice($this->pibase->price->priceFormat($markerArray['###FIELD_QTY###']*$item['priceNoTax']*$row['unit_factor']));
		}

		$prodColorText = '';
		$prodTmp = explode(';', $row['color']);
		if ($this->conf['selectColor']) {
			foreach ($prodTmp as $prodCol)
				$prodColorText = $prodColorText . '<OPTION value="'.$prodCol.'">'.$prodCol.'</OPTION>';
		} else {
			$prodColorText = $prodTmp[0];
		}

		$prodSizeText = '';
		$prodTmp = explode(';', $row['size']);
		if ($this->conf['selectSize']) {
			foreach ($prodTmp as $prodSize) {
				$prodSizeText = $prodSizeText . '<OPTION value="'.$prodSize.'">'.$prodSize.'</OPTION>';
			}
		} else {
			$prodSizeText = $prodTmp[0];
		}

		$prodDescriptionText = '';
		$prodTmp = explode(';', $row['size']);
		if ($this->conf['selectDescription']) {
			foreach ($prodTmp as $prodDescription) {
				$prodDescriptionText = $prodDescriptionText . '<OPTION value="'.$prodDescription.'">'.$prodDescription.'</OPTION>';
			}
		} else {
			$prodDescriptionText = $prodTmp[0];
		}

		$prodGradingsText = '';
		$prodTmp = explode(';', $row['gradings']);
		if ($this->conf['selectGradings']) {
			foreach ($prodTmp as $prodGradings) {
				$prodGradingsText = $prodGradingsText . '<OPTION value="'.$prodGradings.'">'.$prodGradings.'</OPTION>';
			}
		} else {
			$prodGradingsText = $prodTmp[0];
		}

		$prodAdditionalText['single'] = '';
		
		if ($this->conf['selectAdditional']) {
			$isSingleProduct = $this->pibase->tt_products->isSingle($row);
			if ($isSingleProduct)	{
				$message = $this->pibase->pi_getLL('additional_single');
				// $basketSingleName = 'ttp_basket['.$row['uid'].'][quantity]';
				$prodAdditionalText['single'] = $message.'<input type="checkbox" name="'.$basketQuantityName.'" '.($quantity ? 'checked="checked"':'').'onchange = "this.form[this.name+\'[1]\'].value=(this.checked ? 1 : 0);"'.' value="1">';
				//$prodAdditionalText['single'] = $message.'<input type="checkbox" name="'.$basketQuantityName.'[0]" '.($quantity ? 'checked="checked"':'').' value="1">';
				
				$prodAdditionalText['single'] .= '<input type="hidden" name="'.$basketQuantityName.'[1]" value="'.($quantity ? '1' : '0') .'">';
			}
 		}

		$markerArray['###PRODUCT_WEIGHT###'] = doubleval($row['weight']);
		$markerArray['###BULKILY_WARNING###'] = $row['bulkily'] ? $this->conf['bulkilyWarning'] : '';
		$markerArray['###PRODUCT_COLOR###'] = $prodColorText;
		$markerArray['###PRODUCT_SIZE###'] = $prodSizeText;
		$markerArray['###PRODUCT_DESCRIPTION###'] = $prodDescriptionText;
		$markerArray['###PRODUCT_GRADINGS###'] = $prodGradingsText;
		$markerArray['###PRODUCT_ADDITIONAL_SINGLE###'] = $prodAdditionalText['single'];

		if ($row['special_preparation'])
			$markerArray['###PRODUCT_SPECIAL_PREP###'] = $this->pibase->cObj->substituteMarkerArray($this->conf['specialPreparation'],$markerArray);
		else
			$markerArray['###PRODUCT_SPECIAL_PREP###'] = '';

		// Fill the Currency Symbol or not
		if ($this->conf['itemMarkerArrayFunc'])	{
			$markerArray = $this->pibase->userProcess('itemMarkerArrayFunc',$markerArray);
		}

			// Call all getItemMarkerArray hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['getItemMarkerArray'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['getItemMarkerArray'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getItemMarkerArray')) {
					$hookObj->getItemMarkerArray ($this, $markerArray, $item, $catTitle, $tt_products, $tt_content, $imageNum, $imageRenderObj, $forminfoArray);
				}
			}
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
		$temp =	t3lib_div::_GP('begin_at');
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

			// Call all getLinkParams hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['getLinkParams'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['getLinkParams'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getLinkParams')) {
					$hookObj->getLinkParams($this,$queryString,$excludeList,$addQueryString);
				}
			}
		}

		return $queryString;
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	function &getMarkerFields (&$templateCode, &$tableFieldArray, &$requiredFieldArray, $prefix)	{
//		while ($temp = strstr($templateCode, '###'))	{
//			$marker = substr ($temp, 3);
//			$end = strstr ($marker, '###');
//		}
		$retArray = $requiredFieldArray;
		// obligatory fields uid and pid

		$prefixLen = strlen($prefix);
		$tagArray = explode ('###', $templateCode);
		if (is_array($tagArray))	{
			foreach ($tagArray as $k => $tag)	{
				$temp = strstr($tag, $prefix);
				if ($temp)	{
					$field = substr ($temp, $prefixLen);
					$field = strtolower($field);
					if (is_array ($tableFieldArray[$field]))	{
						$retArray[] = $field;
					}
				}
			}
		}
		
		return $retArray;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_marker.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_marker.php']);
}

?>
