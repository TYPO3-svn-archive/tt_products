<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Part of the tt_products (Shop System) extension.
 *
 * view functions
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

global $TYPO3_CONF_VARS;

class tx_ttproducts_marker {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $basket;
	var $markerArray;
	var $cObj;


	/**
	 * Initialized the marker object
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
 	 */

	function init (&$pibase, &$cnf, &$basket)	{
 		$this->pibase = &$pibase;
		$this->cObj = &$pibase->cObj;
 		$this->cnf = &$cnf;
 		$this->conf = &$this->cnf->conf;
 		$this->config = &$this->cnf->config;
 		$this->basket = &$basket;
 		$this->markerArray = array('CATEGORY', 'PRODUCT', 'ARTICLE', 'DAM');
	}

	/**
	 * Adds link markers to a wrapped subpart array
	 */
	function getWrappedSubpartArray (&$wrappedSubpartArray,$addQueryString=array(),$css_current='')	{
		global $TSFE;

		$pidBasket = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);

		$pageLink = $this->pibase->pi_getPageLink($pidBasket,'',$this->getLinkParams('',$addQueryString,true)) ;
		$wrappedSubpartArray['###LINK_BASKET###'] = array('<a href="'. $pageLink .'"'.$css_current.'>','</a>');
	}

	/**
	 * Adds URL markers to a markerArray
	 */
	function addURLMarkers ($pidNext,$markerArray,$addQueryString=array(),$excludeList='')	{
		global $TSFE;
		global $TYPO3_CONF_VARS;
		$conf = array('useCacheHash' => true);
		$target = '';

			// Add's URL-markers to the $markerArray and returns it
		$pidBasket = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $TSFE->id);
		$pidFormUrl = ($pidNext ? $pidNext : $pidBasket);
		$bUseBackPid = ($pidNext != $TSFE->id);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pidFormUrl,$this->getLinkParams($excludeList,$addQueryString,TRUE,$bUseBackPid),$target,$conf);
		$markerArray['###FORM_URL###'] = htmlspecialchars($url);
		$pid = ( $this->conf['PIDinfo'] ? $this->conf['PIDinfo'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->getLinkParams($excludeList,$addQueryString,TRUE,$bUseBackPid),$target,$conf);
		$markerArray['###FORM_URL_INFO###'] = htmlspecialchars($url);
		$pid = ( $this->conf['PIDpayment'] ? $this->conf['PIDpayment'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->getLinkParams($excludeList,$addQueryString,TRUE,$bUseBackPid),$target,$conf);
		$markerArray['###FORM_URL_PAYMENT###'] = htmlspecialchars($url);
		$pid = ( $this->conf['PIDfinalize'] ? $this->conf['PIDfinalize'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->getLinkParams($excludeList,$addQueryString,TRUE,$bUseBackPid),$target,$conf);
		$markerArray['###FORM_URL_FINALIZE###'] = htmlspecialchars($url);
		$pid = ( $this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : $pidBasket);
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($this->pibase,$pid,$this->getLinkParams($excludeList,$addQueryString,TRUE,$bUseBackPid),$target,$conf);
		$markerArray['###FORM_URL_THANKS###'] = htmlspecialchars($url);
		$markerArray['###FORM_URL_TARGET###'] = '_self';

		// This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
		if ($this->basket->basketExtra['payment.']['handleURL'])	{
			$markerArray['###FORM_URL_THANKS###'] = $this->basket->basketExtra['payment.']['handleURL'];
		}
		if ($this->basket->basketExtra['payment.']['handleTarget'])	{	// Alternative target
			$markerArray['###FORM_URL_TARGET###'] = $this->basket->basketExtra['payment.']['handleTarget'];
		}

			// Call all addURLMarkers hooks at the end of this method
		if (is_array ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['addURLMarkers'])) {
			foreach  ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['addURLMarkers'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addURLMarkers')) {
					$hookObj->addURLMarkers($pidNext,$markerArray,$addQueryString,$excludeList);
				}
			}
		}
		return $markerArray;
	} // addURLMarkers

	/**
	 * getting the global markers
	 */
	function &getGlobalMarkers ()	{
		global $TYPO3_CONF_VARS;
		$markerArray = array();

			// globally substituted markers, fonts and colors.
		$splitMark = md5(microtime());
		list($markerArray['###GW1B###'],$markerArray['###GW1E###']) = explode($splitMark,$this->pibase->cObj->stdWrap($splitMark,$this->conf['wrap1.']));
		list($markerArray['###GW2B###'],$markerArray['###GW2E###']) = explode($splitMark,$this->pibase->cObj->stdWrap($splitMark,$this->conf['wrap2.']));
		list($markerArray['###GW3B###'],$markerArray['###GW3E###']) = explode($splitMark,$this->pibase->cObj->stdWrap($splitMark,$this->conf['wrap3.']));
		$markerArray['###GC1###'] = $this->pibase->cObj->stdWrap($this->conf['color1'],$this->conf['color1.']);
		$markerArray['###GC2###'] = $this->pibase->cObj->stdWrap($this->conf['color2'],$this->conf['color2.']);
		$markerArray['###GC3###'] = $this->pibase->cObj->stdWrap($this->conf['color3'],$this->conf['color3.']);
		$markerArray['###DOMAIN###'] = $this->conf['domain'];
		$markerArray['###PATH_FE_REL###'] = PATH_FE_ttproducts_rel;
		if (t3lib_extMgm::isLoaded(ADDONS_EXTkey)) {
			$markerArray['###PATH_FE_REL###'] = PATH_FE_addons_rel;
			$markerArray['###PATH_FE_ICONS###'] = PATH_FE_addons_icon_rel;
		}

		$pidMarkerArray = array('agb','basket','info','finalize','payment', 'thanks','itemDisplay','listDisplay','search','storeRoot',
								'memo','tracking','billing','delivery');
		foreach ($pidMarkerArray as $k => $function)	{
			$markerArray['###PID_'.strtoupper($function).'###'] = $this->conf['PID'.$function];
		}

		$markerArray['###SHOPADMIN_EMAIL###'] = $this->conf['orderEmail_from'];

		if (is_array($this->conf['marks.']))	{
				// Substitute Marker Array from TypoScript Setup
			foreach ($this->conf['marks.'] as $key => $value)	{

				if (is_array($value))	{
					switch($key)	{
						case 'image.':
							foreach ($value as $k2 => $v2)	{
								$fileresource = $this->cObj->fileResource($v2);
								$markerArray['###IMAGE'.strtoupper($k2).'###'] = $fileresource;
							}
						break;
					}
				} else {
					if(isset($this->conf['marks.'][$key.'.']) && is_array($this->conf['marks.'][$key.'.']))	{
						$out = $this->cObj->cObjGetSingle($this->conf['marks.'][$key],$this->conf['marks.'][$key.'.']);
					} else {
						$out = $value;
					}
					$markerArray['###'.strtoupper($key).'###'] = $out;
				}
			}
		}
			// Call all addURLMarkers hooks at the end of this method
		if (is_array ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['addGlobalMarkers'])) {
			foreach  ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['addGlobalMarkers'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addGlobalMarkers')) {
					$hookObj->addGlobalMarkers($markerArray);
				}
			}
		}
		return $markerArray;
	} // getGlobalMarkers

	/**
	 * Returning template subpart marker
	 */
	function spMarker ($subpartMarker)	{
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
	function addQueryStringParam (&$queryString, $param, $bUsePrefix=false) {
		$temp = $this->pibase->piVars[$param];
		$temp = ($temp ? $temp : (t3lib_div::_GP($param) ? t3lib_div::_GP($param) : 0));
		if ($temp)	{
			if ($bUsePrefix)	{
				$queryString[$this->pibase->prefixId.'['.$param.']'] = $temp;
			} else {
				$queryString[$param] = $temp;
			}
		}
	}

	function getSearchParams (&$queryString) {

		$sword = t3lib_div::_GP('sword') ? rawurlencode(t3lib_div::_GP('sword')) : '';
		if (!$sword)	{
			$sword = t3lib_div::_GP('swords') ? rawurlencode(t3lib_div::_GP('swords')) : '';
		}

		if (!$sword)	{
			$sword = $this->pibase->piVars['sword'];
		}

		if ($sword) {
			$queryString['sword'] = $sword;
		}
	}


	/**
	 * Returns a url for use in forms and links
	 */
	function getLinkParams ($excludeList='',$addQueryString=array(),$bUsePrefix=FALSE,$bUseBackPid=TRUE) {
		global $TSFE;
		global $TYPO3_CONF_VARS;

		$typoVersion = t3lib_div::int_from_ver($GLOBALS['TYPO_VERSION']);
		$queryString=array();
		if ($bUseBackPid)	{
			if ($bUsePrefix && !$addQueryString[$this->pibase->prefixId.'[backPID]'])	{
				$queryString[$this->pibase->prefixId.'[backPID]'] = $TSFE->id; // $queryString['backPID']= $TSFE->id;
			} else if (!$addQueryString['backPID'])	{
				$queryString['backPID'] = $TSFE->id;
			}
		}
		$this->addQueryStringParam($queryString, 'C', $bUsePrefix);
		$this->addQueryStringParam($queryString, 'cat', $bUsePrefix);
		$this->addQueryStringParam($queryString, 'begin_at', $bUsePrefix);
		$this->addQueryStringParam($queryString, 'newitemdays', $bUsePrefix);

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

			preg_match('/'.$this->pibase->prefixId.'\[(.*)\]/',$key, $matches);
			$shortkey = $matches[1];
			if ($val=='' || ($excludeList && (t3lib_div::inList($excludeList,$key) || t3lib_div::inList($excludeList,$shortkey))))	{
				unset($queryString[$key]);
			}
		}

			// Call all getLinkParams hooks at the end of this method
		if (is_array ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['getLinkParams'])) {
			foreach  ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['getLinkParams'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getLinkParams')) {
					$hookObj->getLinkParams($this,$queryString,$excludeList,$addQueryString);
				}
			}
		}

		return $queryString;
	}

	/**
	 * finds all the markers for a product
	 * This helps to reduce the data transfer from the database
	 *
	 * @access private
	 */
	function &getMarkerFields (&$templateCode, &$tableFieldArray, &$requiredFieldArray, &$addCheckArray, $prefixParam, &$tagArray, &$parentArray)	{
		$retArray = $requiredFieldArray;
		// obligatory fields uid and pid

		$prefix = $prefixParam.'_';
		$prefixLen = strlen($prefix);
		// $tagArray = explode ('###', $templateCode);
		$treffer = array();
		// preg_match_all('/\###([ \w]+)\###/', $templateCode, $treffer);
		preg_match_all('/###([\w:]+)###/', $templateCode, $treffer);
		$tagArray = $treffer[1];

		if (is_array($tagArray))	{
			$tagArray = array_flip($tagArray);
			$retTagArray = $tagArray;
			foreach ($tagArray as $tag => $k1)	{
				$prefixFound = strstr($tag, $prefix);
				$bFieldadded = FALSE;
				if ($prefixFound)	{
					$field = substr ($prefixFound, $prefixLen);
					$field = strtolower($field);
					if (strstr($field,'image'))	{	// IMAGE markers can contain following number
						$field = 'image';
					}
					if (is_array ($tableFieldArray[$field]))	{
						$retArray[] = $field;
						$bFieldadded = true;
					}
					$parentFound = strpos($tag, 'PARENT');
					if	($parentFound !== FALSE)	{
						$parentEnd = strpos($tag, '_');
						$parentLen = strlen('PARENT');
						$temp = substr($tag, $parentLen, ($parentEnd - $parentFound) - $parentLen);
						$parentArray[] = $temp;
					}
				} else {
					// unset the tags of different tables
					foreach ($this->markerArray as $k => $marker)	{
						if ($marker != $prefixParam) 	{
							$bMarkerFound = strpos($tag, $marker);
							if ($bMarkerFound == 0 && $bMarkerFound !== FALSE)	{
								unset($retTagArray[$tag]);
							}
						}
					}
				}
				if (!$bFieldadded && is_array($addCheckArray))	{
					foreach ($addCheckArray as $marker => $field)	{
						$temp = strstr($tag, $marker);
						if ($temp)	{
							$retArray[] = $field;
							break;
						}
					}
				}
			}
			$tagArray = $retTagArray;
		}
		sort($parentArray);
		if (is_array($retArray))	{
			$retArray = array_unique($retArray);
		}

		return $retArray;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/marker/class.tx_ttproducts_marker.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/marker/class.tx_ttproducts_marker.php']);
}

?>
