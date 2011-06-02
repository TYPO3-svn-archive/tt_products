<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2009 Franz Holzinger <franz@ttproducts.de>
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
 * JavaScript functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

global $TYPO3_CONF_VARS;

class tx_ttproducts_javascript {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $page;
	var $xajax;
	var $bXajaxAdded;
	var $bCopyrightShown;
	var $copyright;


	function init(&$pibase, &$cnf, &$page, &$xajax) {
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;
		$this->page = &$page;
		$this->xajax = &$xajax;
		$this->bXajaxAdded = false;
		$this->bCopyrightShown = false;
		$this->copyright = '
/***************************************************************
*
*  javascript functions for the tt_products Shop System
*  relies on the javascript library "xajax"
*
*
*  Copyright notice
*
*  (c) 2006-2009 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the TYPO3 t3lib/ library provided by
*  Kasper Skaarhoj <kasper@typo3.com> together with TYPO3
*
*  Released under GNU/GPL (see license file in tslib/)
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
*  This copyright notice MUST APPEAR in all copies of this script
***************************************************************/
';


	}


	/*
	 * Escapes strings to be included in javascript
	 */
	function jsspecialchars($s) {
	   return preg_replace('/([\x09-\x2f\x3a-\x40\x5b-\x60\x7b-\x7e])/e',
	       "'\\x'.(ord('\\1')<16? '0': '').dechex(ord('\\1'))",$s);
	}

		/**
		 * Sets JavaScript code in the additionalJavaScript array
		 *
		 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
		 * @param		array		  category array
		 * @param		integer		  counter
		 * @return	  	void
		 * @see
		 */
	function set($fieldname, $params='', $count=0, $catid='cat', $parentFieldArray=array(), $piVarArray=array(), $fieldArray=array(), $method='clickShow') {
		global $TSFE;

		$bDirectHTML = false;
		$code = '';
		$bError = false;
		$emailArr =  explode('|', $message = $this->pibase->pi_getLL('invalid_email'));

		if (!$this->bCopyrightShown && $fieldname != 'xajax')	{
			$code = $this->copyright;
			$this->bCopyrightShown = TRUE;
		}

		$JSfieldname = $fieldname;
		switch ($fieldname) {
			case 'email' :
				$code .=
				'function test(eing) {
					var reg = /@/;
					var rc = true;
					if (!reg.exec(eing)) {
				 		rc = false;
				 	}
				 	return rc;
				}

				function checkEmail(element) {
					if (test(element.value)){
						return (true);
					}
					alert("'.$emailArr[0].'\'"+element.value+"\''.$emailArr[1].'");
					return (false);
				}

				function checkParams(formObj) {
					var rc = true;
					for (var i = 0; i < formObj.length; i++) {
						if (formObj[i].type == "text") {
							var email = /email/i;
							if (email.exec(formObj[i].name)) {
								rc = checkEmail (formObj[i]);
							}
						}
						if (!rc) {
							break;
						}
					}
					return rc;
				}
				';
				break;
			case 'selectcat':
				if (is_array($params))	{
					$funcs = count ($params);
					$ajaxConf = $this->cnf->getAJAXConf();
					if (is_array($ajaxConf))	{
						// TODO: make it possible that AJAX gets all the necessary configuration
						$code .= 'var conf = new Array();
		';
						foreach ($ajaxConf as $k => $actConf)	{
							$pVar = t3lib_div::_GP($k);
							if (isset($pVar) && is_array($actConf[$pVar.'.']))	{
								foreach ($actConf[$pVar.'.'] as $k2 => $v2)	{
									$code .= 'conf['.$k2.'] = '.$v2.'; ';
								}
							}
						}
						$code .= '
		';
					}
					$code .= 'var c = new Array(); // categories
		var boxCount = '.$count.'; // number of select boxes
		var pi = new Array(); // names of select boxes;
		var inAction = false; // is the script still running?
		var maxFunc = '.$funcs.';
		';
					foreach ($piVarArray as $fnr => $pivar)	{
						$code .= 'pi['.$fnr.'] = "'.$pivar.'";';
					}
					$code .= '
		';
					foreach ($params as $fnr => $catArray)	{
						$code .= 'c['.$fnr.'] = new Array('.count($catArray).');';
						foreach ($catArray as $k => $row)	{
							$code .= 'c['.$fnr.']['.$k.'] = new Array(3);';
							$code .= 'c['.$fnr.']['.$k.'][0] = "'.$this->jsspecialchars($row['title']).'"; ' ;
							$parentField = $parentFieldArray[$fnr];
							$code .= 'c['.$fnr.']['.$k.'][1] = "'.intval($row[$parentField]).'"; ' ;
							$child_category = $row['child_category'];
							if (is_array($child_category))	{
								$code .= 'c['.$fnr.']['.$k.'][2] = new Array('.count($child_category).');';
								$count = 0;
								foreach ($child_category as $k1 => $childCat)	{
									$newCode = 'c['.$fnr.']['.$k.'][2]['.$count.'] = "'.$childCat.'"; ';
									$code .= $newCode;
									$count++;
								}
							} else {
								$code .= 'c['.$fnr.']['.$k.'][2] = "0"; ' ;
							}
							$code .= '
		';
						}
					}
				}
				$code .=
		'
		' .
	'function fillSelect (select,id,showSubCategories) {
		var sb;
		var sbt;
		var index;
		var selOption;
		var subcategories;
		var bShowArticle = 0;
		var len;
		var idel;
		var category;

		if (inAction == true)	{
			return false;
		}
		inAction = true;
		index = select.selectedIndex;
		selOption = select.options[index];
		category = selOption.value;

		if (id > 0) {
			var func;
			var bRootFunctions = (maxFunc > 1) && (id == 2);

			sb = document.getElementById("'.$catid.'"+1);
			func = sb.selectedIndex - 1;
			len = sb.options.length;
			if (maxFunc == 1 || func < 0 || func > maxFunc)	{
				func = 0;
				bRootFunctions = false;
			}
			// sb.options[len] = new Option("1. +++ func = "+func+"len = "+len+" id = "+id+" index = "+index+" category = "+category+" bRootFunctions = "+bRootFunctions, "B");
			for (var l = boxCount; l >= id+1; l--)	{
				idel = "'.$catid.'" + l;
				sbt = document.getElementById(idel);
				sbt.options.length = 0;
				sbt.selectedIndex = 0;
			}
			idel = "'.$catid.'" + id;
			sbt = document.getElementById(idel);
			sbt.options.length = 0;
			if (sb.selectedIndex == 0) {
				// nothing
			} else if (bRootFunctions) {
				// lens = c[func].length;
				subcategories = new Array ();
				var count = 0;
				for (k in c[func]) {
					if (c[func][k][1] == 0)	{
						subcategories[count] = k;
						count++;
					}
				}
			} else if (category > 0) {
				subcategories = c[func][category][2];
			}
			if ((typeof(subcategories) == "object") && (showSubCategories == 1)) {
				var newOption = new Option("", 0);
				sbt.options[0] = newOption; // sbt.options.add(newOption);
		        len = subcategories.length;';
	        	$code .= '
				// sb.options[len] = new Option("2. +++ func = "+func+"len = "+len+" id = "+id+" category = "+category+" subcategories = "+subcategories, "B");
				for (k = 0; k < len; k++)	{
					var cat = subcategories[k];
					var text = c[func][cat][0];
					newOption = new Option(text, cat);
					sbt.options[k+1] = newOption; // sbt.options.add(newOption);
				}
				sbt.name = pi[func];
			} else {
				bShowArticle = 1;
			}
		} else {
			bShowArticle = 1;
		}
	    if (bShowArticle)	{
	        /* sb.options[0] = new Option(len, "keine Unterkategorie");*/
			var data = new Array();

	        data["'.$this->pibase->prefixId.'"] = new Array();
	        data["'.$this->pibase->prefixId.'"]["'.$catid.'"] = category;
	        ';
		        if ($method == 'clickShow')	{
		        	$code .= $this->pibase->extKey.'_showArticle(data);';
		        }
				$code .= '
	    } else {
			/* nothing */
	    }
		';
				$code .= '
		inAction = false;
		return true;
	}
		';
				$code .= '
		function doFetchData() {
			var data = new Array();
			var func;

			sb = document.getElementById("'.$catid.'"+1);
			func = sb.selectedIndex - 1;
			for (var k = 2; k <= boxCount; k++) {
				sb = document.getElementById("'.$catid.'"+k);
				index = sb.selectedIndex;
				if (index > 0)	{
					value = sb.options[index].value;
					if (value)	{
						data["'.$this->pibase->prefixId.'"] = new Array();
						data["'.$this->pibase->prefixId.'"][pi[func]] = value;
					}
				}
			}
			var sub = document.getElementsByName("'.$this->pibase->prefixId.'[submit]")[0];
			for (k in sub.form.elements)	{
				var el = sub.form.elements[k];
				var elname;
				if (el)	{
					elname = String(el.name);
				}
				if (elname && elname.indexOf("function") == -1 && elname.indexOf("'.$this->pibase->prefixId.'") == 0)	{
					var start = elname.indexOf("[");
					var end = elname.indexOf("]");
					var element = elname.substring(start+1,end);
					data["'.$this->pibase->prefixId.'"][element] = el.value;
				}
			}

			';
		        if ($method == 'submitShow')	{
		        	$code .= $this->pibase->extKey.'_showList(data);';
		        }
				$code .= '
			return true;
		}
		';
				break;

			case 'direct':
				if (is_array($params))	{
					$code .= current($params);
					$JSfieldname = $fieldname .'-'. key($params);
				}
				break;

			case 'xajax':
				// XAJAX part
				if (!$this->bXajaxAdded && is_object($this->xajax))	{
					$code = $this->xajax->getJavascript(t3lib_extMgm::siteRelPath('xajax'));
					$this->bXajaxAdded = true;
				}
				$bDirectHTML = true;
				break;

			default:
				$bError = true;
				break;
		} // switch

		if (!$bError)	{
			if ($code)	{
				if ($bDirectHTML)	{
					// $TSFE->setHeaderHTML ($fieldname, $code);
					$TSFE->additionalHeaderData['tx_ttproducts-xajax'] = $code;
				} else {
					$TSFE->setJS ($JSfieldname, $code);
				}
			}
		}
	} // setJS

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_javascript.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_javascript.php']);
}


?>
