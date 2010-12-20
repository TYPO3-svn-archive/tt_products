<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
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
	 * @param	string		$fieldname is the field in the table you want to create a JavaScript for
	 * @param	array		array urls which should be overridden with marker key as index
	 * @return	void
	 */
	function init(&$pibase)	{
 		$this->pibase = &$pibase;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

 		$this->conf = &$cnf->conf;
 		$this->config = &$cnf->config;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$urlArray: ...
	 * @return	[type]		...
	 */
	function setUrlArray ($urlArray)	{
		$this->urlArray = $urlArray;
	}

	/**
	 * Adds link markers to a wrapped subpart array
	 *
	 * @param	[type]		$$wrappedSubpartArray: ...
	 * @param	[type]		$addQueryString: ...
	 * @param	[type]		$css_current: ...
	 * @return	[type]		...
	 */
	function getWrappedSubpartArray(&$wrappedSubpartArray,$addQueryString=array(),$css_current='')	{
		global $TSFE;

		$pidBasket = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);
		$pageLink = $this->pibase->pi_getPageLink($pidBasket,'',$this->getLinkParams('',$addQueryString,true)) ;
		$wrappedSubpartArray['###LINK_BASKET###'] = array('<a href="'. $pageLink .'"'.$css_current.'>','</a>');
	}

	/**
	 * Adds URL markers to a markerArray
	 *
	 * @param	[type]		$pidNext: ...
	 * @param	[type]		$markerArray: ...
	 * @param	[type]		$addQueryString: ...
	 * @param	[type]		$excludeList: ...
	 * @param	[type]		$bExcludeSingleVar: ...
	 * @return	[type]		...
	 */
	function addURLMarkers($pidNext,$markerArray,$addQueryString=array(),$excludeList='',$bExcludeSingleVar=TRUE)	{
		global $TSFE;

		$charset = $TSFE->renderCharset;
		$conf = array('useCacheHash' => true);
		$target = '';

		// disable caching as soon as someone enters products into the basket, enters user data etc.
		// $addQueryString['no_cache'] = 1;
			// Add's URL-markers to the $markerArray and returns it
		$pidBasket = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);
		$pidFormUrl = ($pidNext ? $pidNext : $pidBasket);
		if ($pidFormUrl != $TSFE->id && $bExcludeSingleVar)	{
			$newExcludeListArray = array('tx_ttproducts_pi1[article]', 'tx_ttproducts_pi1[product]');
			$excludeListArray = t3lib_div::trimExplode(',', $excludeList);
			$excludeListArray = array_merge ($excludeListArray, $newExcludeListArray);
			if (!$excludeListArray[0])	{
				unset ($excludeListArray[0]);
			}
			$excludeList = implode(',', $excludeListArray);
		}

		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pidFormUrl,$this->getLinkParams($excludeList,$addQueryString,true),$target,$conf);

		$markerArray['###FORM_URL###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
		$pid = ( $this->conf['PIDinfo'] ? $this->conf['PIDinfo'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->getLinkParams($excludeList,$addQueryString,true),$target,$conf);
		$markerArray['###FORM_URL_INFO###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
		$pid = ( $this->conf['PIDpayment'] ? $this->conf['PIDpayment'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->getLinkParams($excludeList,$addQueryString,true),$target,$conf);
		$markerArray['###FORM_URL_PAYMENT###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
		$pid = ( $this->conf['PIDfinalize'] ? $this->conf['PIDfinalize'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->getLinkParams($excludeList,$addQueryString,true),$target,$conf);
		$markerArray['###FORM_URL_FINALIZE###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
		$pid = ( $this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->getLinkParams($excludeList,$addQueryString,true),$target,$conf);
		$markerArray['###FORM_URL_THANKS###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
		$markerArray['###FORM_URL_TARGET###'] = '_self';

		if (isset($this->urlArray) && is_array($this->urlArray))	{
			foreach ($this->urlArray as $k => $urlValue)	{
				$markerArray['###'.strtoupper($k).'###'] = $urlValue;
			}
		}

			// Call all addURLMarkers hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['addURLMarkers'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['addURLMarkers'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addURLMarkers')) {
					$hookObj->addURLMarkers($this->pibase,$pidNext,$markerArray,$addQueryString);
				}
			}
		}
		return $markerArray;
	} // addURLMarkers


	/**
	 * Returns a url for use in forms and links
	 *
	 * @param	[type]		$$queryString: ...
	 * @param	[type]		$param: ...
	 * @param	[type]		$bUsePrefix: ...
	 * @return	[type]		...
	 */
	function addQueryStringParam(&$queryString, $param, $bUsePrefix=false) {
		$temp = $this->pibase->piVars[$param];
		$temp = ($temp ? $temp : (t3lib_div::GPvar($param) && ($param!='pid') ? t3lib_div::GPvar($param) : 0));
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
	 *
	 * @param	[type]		$excludeList: ...
	 * @param	[type]		$addQueryString: ...
	 * @param	[type]		$bUsePrefix: ...
	 * @param	[type]		$bUseBackPid: ...
	 * @param	[type]		$piVarCat: ...
	 * @return	[type]		...
	 */
	function getLinkParams($excludeList='',$addQueryString=array(),$bUsePrefix=false,$bUseBackPid=true,$piVarCat='cat') {
		global $TSFE;

		$typoVersion = t3lib_div::int_from_ver($GLOBALS['TYPO_VERSION']);
		$queryString=array();
//		$fe_user = (is_array($TSFE->fe_user->user) ? 1 : 0);
		if ($bUseBackPid)	{
			if ($bUsePrefix)	{
				$queryString[$this->pibase->prefixId.'[backPID]'] = $TSFE->id; // $queryString['backPID']= $TSFE->id;
	//			if ($fe_user)	{
	//				$queryString[$this->pibase->prefixId.'[fegroup]'] = 1;
	//			}
			} else {
				$queryString['backPID'] = $TSFE->id;
	//			if ($fe_user)	{
	//				$queryString['fegroup'] = 1;
	//			}
			}
		}

		$this->addQueryStringParam($queryString, 'C', $bUsePrefix);
		if ($piVarCat)	{
			$this->addQueryStringParam($queryString, $piVarCat, $bUsePrefix);
		}

		$this->addQueryStringParam($queryString, 'begin_at', $bUsePrefix);
		$this->addQueryStringParam($queryString, 'newitemdays', $bUsePrefix);

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
			if ($val=='' || ($excludeList && t3lib_div::inList($excludeList,$key)))	{
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
