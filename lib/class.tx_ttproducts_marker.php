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

		// disable caching as soon as someone enters products into the basket, enters user data etc.
		$addQueryString['no_cache'] = 1; 
			// Add's URL-markers to the $markerArray and returns it
		$pidBasket = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);
		$pidFormUrl = ($pidNext ? $pidNext : $pidBasket);
		$markerArray['###FORM_URL###'] = $this->pibase->pi_getPageLink($pidFormUrl,'',$this->getLinkParams('',$addQueryString)) ;
		$pid = ( $this->conf['PIDinfo'] ? $this->conf['PIDinfo'] : $pidBasket);
		$markerArray['###FORM_URL_INFO###'] = $this->pibase->pi_getPageLink($pid,'',$this->getLinkParams('',$addQueryString)) ;
		$pid = ( $this->conf['PIDpayment'] ? $this->conf['PIDpayment'] : $pidBasket);
		$markerArray['###FORM_URL_PAYMENT###'] = $this->pibase->pi_getPageLink($pid,'',$this->getLinkParams('',$addQueryString)) ;
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
	 * Returns a url for use in forms and links
	 */
	function addQueryStringParam(&$queryString, $param) {
		$temp = $this->pibase->piVars[$param];
		$temp = ($temp ? $temp : (t3lib_div::GPvar($param) ? t3lib_div::GPvar($param) : $this->pibase->currency)); 
		if ($temp)	{
			$queryString[$this->pibase->prefixId.'['.$param.']'] = $temp;
		}
	}


	/**
	 * Returns a url for use in forms and links
	 */
	function getLinkParams($excludeList='',$addQueryString=array()) {
		global $TSFE;
		$queryString=array();
		$queryString[$this->pibase->prefixId.'[backPID]']= $TSFE->id;
		
		$this->addQueryStringParam($queryString, 'C');
		$this->addQueryStringParam($queryString, 'begin_at');
		$this->addQueryStringParam($queryString, 'newitemdays');

		$temp = t3lib_div::_GP('swords') ? rawurlencode(t3lib_div::_GP('swords')) : '';
		if ($temp) {
			$queryString['swords'] = $temp;
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
	 * This helps to reduce the data transfer from the database
	 *
	 * @access private
	 */
	function &getMarkerFields (&$templateCode, &$tableFieldArray, &$requiredFieldArray, &$addCheckArray, $prefix)	{
//		while ($temp = strstr($templateCode, '###'))	{
//			$marker = substr ($temp, 3);
//			$end = strstr ($marker, '###');
//		}
		$retArray = $requiredFieldArray;
		// obligatory fields uid and pid

		$prefixLen = strlen($prefix);
		// $tagArray = explode ('###', $templateCode);
		$treffer = array();
		// preg_match_all('/\###([ \w]+)\###/', $templateCode, $treffer);
		preg_match_all('/###([\w]+)###/', $templateCode, $treffer);
		$tagArray = $treffer[1];
		
		if (is_array($tagArray))	{
			foreach ($tagArray as $k1 => $tag)	{
				$temp = strstr($tag, $prefix);
				if ($temp)	{
					$field = substr ($temp, $prefixLen);
					$field = strtolower($field);
					if (is_array ($tableFieldArray[$field]))	{
						$retArray[] = $field;
					}
				} else if (is_array($addCheckArray)){
					foreach ($addCheckArray as $marker => $field)	{
						$temp = strstr($tag, $marker);
						if ($temp)	{
							$retArray[] = $field;
							break;
						}
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
