<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Franz Holzinger <franz@ttproducts.de>
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
 * Part of the tt_products (Shop System) extension.
 *
 * url marker functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_url_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $urlArray;

	/**
	 * Initialized the marker object
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		$fieldname is the field in the table you want to create a JavaScript for
	* @param		array		array urls which should be overridden with marker key as index
	 * @return	  void
 	 */

	public function init (&$pibase)	{
 		$this->pibase = &$pibase;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

 		$this->conf = &$cnf->conf;
 		$this->config = &$cnf->config;
	}

	public function setUrlArray ($urlArray)	{
		$this->urlArray = $urlArray;
	}

	/**
	 * Adds link markers to a wrapped subpart array
	 */
	public function getWrappedSubpartArray (&$wrappedSubpartArray,$addQueryString=array(),$css_current='')	{
		global $TSFE;

		$pidBasket = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);
		$pageLink = $this->pibase->pi_getPageLink($pidBasket,'',$this->getLinkParams('',$addQueryString,true)) ;
		$wrappedSubpartArray['###LINK_BASKET###'] = array('<a href="'. $pageLink .'"'.$css_current.'>','</a>');
	}

	/**
	 * Adds URL markers to a markerArray
	 */
	public function addURLMarkers ($pidNext,$markerArray,$addQueryString=array(),$excludeList='',$bUseBackPid=TRUE,$bExcludeSingleVar=TRUE)	{
		global $TSFE;

		$charset = $TSFE->renderCharset;
		$urlMarkerArray = array();
		$conf = array('useCacheHash' => TRUE);
		$target = '';

		// disable caching as soon as someone enters products into the basket, enters user data etc.
		// $addQueryString['no_cache'] = 1;
			// Add's URL-markers to the $markerArray and returns it
		$pidBasket = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);
		$pidFormUrl = ($pidNext ? $pidNext : $pidBasket);
		if ($pidFormUrl != $TSFE->id && $bExcludeSingleVar)	{
			$newExcludeListArray = array('tt_products[article]', 'tt_products[product]');
			$excludeListArray = t3lib_div::trimExplode(',', $excludeList);
			$excludeListArray = array_merge ($excludeListArray, $newExcludeListArray);
			if (!$excludeListArray[0])	{
				unset($excludeListArray[0]);
			}
			$excludeList = implode(',', $excludeListArray);
		}
		$bUseBackPid = ($bUseBackPid && $pidNext != $TSFE->id);

		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pidFormUrl,$this->getLinkParams($excludeList,$addQueryString,TRUE,$bUseBackPid),$target,$conf);
		$urlMarkerArray['###FORM_URL###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
		$pid = ( $this->conf['PIDinfo'] ? $this->conf['PIDinfo'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->getLinkParams($excludeList,$addQueryString,TRUE,$bUseBackPid),$target,$conf);
		$urlMarkerArray['###FORM_URL_INFO###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
		$pid = ( $this->conf['PIDpayment'] ? $this->conf['PIDpayment'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->getLinkParams($excludeList,$addQueryString,TRUE,$bUseBackPid),$target,$conf);
		$urlMarkerArray['###FORM_URL_PAYMENT###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
		$pid = ( $this->conf['PIDfinalize'] ? $this->conf['PIDfinalize'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->getLinkParams($excludeList,$addQueryString,TRUE,$bUseBackPid),$target,$conf);
		$urlMarkerArray['###FORM_URL_FINALIZE###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
		$pid = ( $this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->getLinkParams($excludeList,$addQueryString,TRUE,$bUseBackPid),$target,$conf);
		$urlMarkerArray['###FORM_URL_THANKS###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
		$urlMarkerArray['###FORM_URL_TARGET###'] = '_self';

		if (isset($this->urlArray) && is_array($this->urlArray))	{
			foreach ($this->urlArray as $k => $urlValue)	{
				$urlMarkerArray['###'.strtoupper($k).'###'] = $urlValue;
			}
		}

			// Call all addURLMarkers hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['addURLMarkers'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['addURLMarkers'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addURLMarkers')) {
					$hookObj->addURLMarkers($this->pibase,$pidNext,$urlMarkerArray,$addQueryString,$excludeList,$bUseBackPid,$bExcludeSingleVar);
				}
			}
		}

		if (isset($markerArray) && is_array($markerArray))	{
			$markerArray = array_merge($markerArray, $urlMarkerArray);
		} else {
			$markerArray = $urlMarkerArray;
		}
		return $markerArray;
	} // addURLMarkers

	/**
	 * Returns a url for use in forms and links
	 */
	public function addQueryStringParam (&$queryString, $param, $bUsePrefix=false) {
		$temp = $this->pibase->piVars[$param];
		$temp = ($temp ? $temp : (t3lib_div::_GP($param) && ($param!='pid') ? t3lib_div::_GP($param) : 0));
		if ($temp)	{
			if ($bUsePrefix)	{
				$queryString[$this->pibase->prefixId.'['.$param.']'] = $temp;
			} else {
				$queryString[$param] = $temp;
			}
		}
	}

	/**
	 * Returns a url for use in forms and links
	 */
	public function getLinkParams ($excludeList='',$addQueryString=array(),$bUsePrefix=FALSE,$bUseBackPid=TRUE,$piVarCat='cat') {
		global $TSFE;

		$queryString=array();
		if ($bUseBackPid)	{
			if ($bUsePrefix && !$addQueryString[$this->pibase->prefixId.'[backPID]'])	{
				$queryString[$this->pibase->prefixId . '[backPID]'] = $TSFE->id;
			} else if (!$addQueryString['backPID'])	{
				$queryString['backPID'] = $TSFE->id;
			}
		}

		$this->addQueryStringParam($queryString, 'C', $bUsePrefix);
		if ($piVarCat)	{
			$this->addQueryStringParam($queryString, $piVarCat, $bUsePrefix);
		}

		$this->addQueryStringParam($queryString, 'begin_at', $bUsePrefix);
		$this->addQueryStringParam($queryString, 'newitemdays', $bUsePrefix);
		$this->addQueryStringParam($queryString, 'searchbox', $bUsePrefix);
		$this->addQueryStringParam($queryString, 'sword', $bUsePrefix);

		$temp = t3lib_div::_GP('sword') ? rawurlencode(t3lib_div::_GP('sword')) : '';
		if (!$temp)	{
			$temp = t3lib_div::_GP('swords') ? rawurlencode(t3lib_div::_GP('swords')) : '';
		}
		if ($temp) {
			$queryString['sword'] = $temp;
		}

		if (is_array($addQueryString))	{
			foreach ($addQueryString as $param => $value){
				if ($bUsePrefix)	{
					$queryString[$this->pibase->prefixId.'['.$param.']'] = $value;
				} else {
					$queryString[$param] = $value;
				}
			}
		}

		foreach($queryString as $key => $val)	{
			if ($val=='' || (strlen($excludeList) && t3lib_div::inList($excludeList,$key)))	{
				unset($queryString[$key]);
			}
		}

			// Call all getLinkParams hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['getLinkParams'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['getLinkParams'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getLinkParams')) {
					$hookObj->getLinkParams($this,$queryString,$excludeList,$addQueryString,$bUsePrefix,$bUseBackPid,$piVarCat);
				}
			}
		}
		return $queryString;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_url_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_url_view.php']);
}

?>
