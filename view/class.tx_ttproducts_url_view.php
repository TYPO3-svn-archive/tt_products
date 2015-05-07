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
 * $Id:$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_url_view {
	var $pibase; // reference to object of pibase
	var $cObj;
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
		$this->cObj = $this->pibase->cObj;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

 		$this->conf = &$cnf->conf;
 		$this->config = &$cnf->config;
	}


	public function getSingleExcludeList ($excludeList) {
		$excludeListArray = t3lib_div::trimExplode(',', $excludeList);
		$singleExcludeListArray =
			array(
				'article',
				'product',
				'variants',
				'dam'
			);
		$singleExcludeListArray = array_merge($excludeListArray, $singleExcludeListArray);

		if (!$singleExcludeListArray[0]) {
			unset($singleExcludeListArray[0]);
		}
		$singleExcludeList = implode(',', $singleExcludeListArray);
		return $singleExcludeList;
	}


	public function setUrlArray ($urlArray)	{
		$this->urlArray = $urlArray;
	}


	/**
	 * Adds link markers to a wrapped subpart array
	 */
	public function getWrappedSubpartArray (&$wrappedSubpartArray,$addQueryString=array(),$css_current='',$bUseBackPid=TRUE)	{
		global $TSFE;

		$pidBasket = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);
		$pageLink = tx_div2007_alpha::getPageLink_fh002(
			$this->cObj,
			$pidBasket,
			'',
			$this->getLinkParams(
				'',
				$addQueryString,
				TRUE,
				$bUseBackPid
			)
		) ;
		$wrappedSubpartArray['###LINK_BASKET###'] = array('<a href="' . $pageLink . '"' . $css_current . '>','</a>');
	}


	/**
	 * Adds URL markers to a markerArray
	 */
	public function addURLMarkers (
		$pidNext,
		$markerArray,
		$addQueryString=array(),
		$excludeList='',
		$bUseBackPid=TRUE,
		$bExcludeSingleVar=TRUE
	)	{
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
			$newExcludeListArray =
				array(
					'tt_products[article]',
					'tt_products[product]',
					'tt_products[dam]'
				);
			$excludeListArray = t3lib_div::trimExplode(',', $excludeList);
			$excludeListArray = array_merge($excludeListArray, $newExcludeListArray);
			if (!$excludeListArray[0])	{
				unset($excludeListArray[0]);
			}
			$excludeList = implode(',', $excludeListArray);
		}
		$bUseBackPid = ($bUseBackPid && $pidNext != $TSFE->id);

		$url = tx_div2007_alpha::getTypoLink_URL_fh002($this->cObj,$pidFormUrl,$this->getLinkParams($excludeList,$addQueryString,TRUE,$bUseBackPid),$target,$conf);

		$urlMarkerArray['###FORM_URL###'] = htmlspecialchars($url, ENT_NOQUOTES, $charset);
		$pid = ( $this->conf['PIDinfo'] ? $this->conf['PIDinfo'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh002($this->cObj,$pid,$this->getLinkParams($excludeList,$addQueryString,TRUE,$bUseBackPid),$target,$conf);

		$commandArray = array('basket', 'info', 'payment', 'finalize', 'thanks', 'search', 'memo', 'tracking', 'billing', 'delivery', 'agb');

		foreach ($commandArray as $command) {
			$pid = ($this->conf['PID' . $command] ? $this->conf['PID' . $command] : $pidBasket);
			$url = tx_div2007_alpha::getTypoLink_URL_fh002(
				$this->cObj,
				$pid,
				$this->getLinkParams(
					$excludeList,
					$addQueryString,
					TRUE,
					$bUseBackPid
				),
				$target,
				$conf
			);
			$urlMarkerArray['###FORM_URL_' . strtoupper($command) . '###'] =
				htmlspecialchars(
					$url,
					ENT_NOQUOTES,
					$charset
				);
		}

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
	public function addQueryStringParam (&$queryString, $param, $bUsePrefix=FALSE) {
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
	public function getLinkParams ($excludeList='',$addQueryString=array(),$bUsePrefix=FALSE,$bUseBackPid=TRUE,$piVarSingle='product',$piVarCat='cat') {
		global $TSFE;

		$prefixId = $this->pibase->prefixId;
		$queryString=array();
		if ($bUseBackPid)	{
			if ($bUsePrefix && !$addQueryString[$prefixId . '[backPID]'])	{
				$queryString[$prefixId . '[backPID]'] = $TSFE->id;
			} else if (!$addQueryString['backPID'])	{
				$queryString['backPID'] = $TSFE->id;
			}
		}
		if ($excludeList != '' && $bUsePrefix) {
			$excludeArray = explode(',', $excludeList);
			foreach ($excludeArray as $k => $v) {
				$excludeArray[$k] = $this->pibase->prefixId.'[' . $v . ']';
			}
			$excludeList = implode(',', $excludeArray);
		}

		$this->addQueryStringParam($queryString, 'C', $bUsePrefix);
		$this->addQueryStringParam($queryString, 'article', $bUsePrefix);
		$this->addQueryStringParam($queryString, 'product', $bUsePrefix);
		if ($piVarSingle != '') {
			$this->addQueryStringParam($queryString, $piVarSingle, $bUsePrefix);
		}
		if ($piVarCat)	{
			$this->addQueryStringParam($queryString, $piVarCat, $bUsePrefix);
		}

		$listPointerParam = tx_ttproducts_model_control::getPointerPiVar('LIST');
		$this->addQueryStringParam($queryString, $listPointerParam, $bUsePrefix);
		$catlistPointerParam = tx_ttproducts_model_control::getPointerPiVar('CATLIST');
		$this->addQueryStringParam($queryString, $listPointerParam, $bUsePrefix);
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

		if ($bUsePrefix)	{
			$excludeListArray = array();
			$tmpArray = t3lib_div::trimExplode(',',$excludeList);
			if (count($tmpArray))	{
				foreach($tmpArray as $param)	{
					if (strpos($param, $prefixId) === FALSE)	{
						$excludeListArray[] = $prefixId . '[' . $param . ']';
					}
				}
				if (count($excludeListArray))	{
					$excludeList .= ',' . implode(',', $excludeListArray);
				}
			}
		}

		foreach($queryString as $key => $val)	{
			if ($val=='' || (strlen($excludeList) && t3lib_div::inList($excludeList,$key)))	{
				unset($queryString[$key]);
			}
		}

		if (is_array($addQueryString))	{
			foreach ($addQueryString as $param => $value){
				if ($bUsePrefix)	{
					$queryString[$prefixId . '[' . $param . ']'] = $value;
				} else {
					$queryString[$param] = $value;
				}
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
