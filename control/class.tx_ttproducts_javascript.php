<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2010 Franz Holzinger <franz@ttproducts.de>
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
 * JavaScript functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_javascript {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $ajax;
	var $bAjaxAdded;
	var $bCopyrightShown;
	var $copyright;


	function init($pibase, $ajax) {
		$this->pibase = $pibase;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->ajax = $ajax;
		$this->bAjaxAdded = false;
		$this->bCopyrightShown = false;
		$this->copyright = '
/***************************************************************
*
*  javascript functions for the TYPO3 Shop System tt_products
*  relies on the javascript library "xajax"
*
*  Copyright notice
*
*  (c) 2006-2014 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  Released under GNU/GPL (http://typo3.com/License.1625.0.html)
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
 *
 * @param	[type]		$s: ...
 * @return	[type]		...
 */
	function jsspecialchars($s) {
	   return preg_replace('/([\x09-\x2f\x3a-\x40\x5b-\x60\x7b-\x7e])/e',
	       "'\\x'.(ord('\\1')<16? '0': '').dechex(ord('\\1'))",$s);
	}

		/**
 * Sets JavaScript code in the additionalJavaScript array
 *
 * @param	string		$fieldname is the field in the table you want to create a JavaScript for
 * @param	array		category array
 * @param	integer		counter
 * @param	string		$catid: id for the category
 * @param	array		$parentFieldArray: function table name as key, variantFieldArray as value
 * @param	array		$piVarArray: pivars to be set
 * @param	array		$fieldArray: array of fields
 * @param	string		$method: Ajax method
 * @return	void
 * @see
 */
	function set($fieldname, $params='', $count=0, $catid='cat', $parentFieldArray=array(), $piVarArray=array(), $fieldArray=array(), $method='clickShow') {
		global $TSFE;

		$bDirectHTML = false;
		$code = '';
		$bError = false;
		$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');
		$message = tx_div2007_alpha5::getLL_fh002($langObj, 'invalid_email');
		$emailArr =  explode('|', $message);

		if (!$this->bCopyrightShown && $fieldname != 'xajax')	{
			$code = $this->copyright;
			$this->bCopyrightShown = TRUE;
		}
		if (!is_object($this->ajax) && in_array($fieldname, array('fetchdata')))	{
			$fieldname = 'error';
		}

		$JSfieldname = $fieldname;
		switch ($fieldname) {
			case 'email' :
				$code .= '
	function test(eing) {
		var reg = /@/;
		var rc = true;
		if (!reg.exec(eing)) {
	 		rc = false;
	 	}
	 	return rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$element: ...
	 * @return	[type]		...
	 */
	function checkEmail(element) {
		if (test(element.value)){
			return (true);
		}
		alert("'.$emailArr[0].'\'"+element.value+"\''.$emailArr[1].'");
		return (false);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$formObj: ...
	 * @return	[type]		...
	 */
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
					$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

					$ajaxConf = $cnf->getAJAXConf();
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
			if (maxFunc == 1 || func < 0 || func > maxFunc)	{
				func = 0;
				bRootFunctions = false;
			}

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
			var data = new Array();

		data["'.$this->pibase->extKey.'"] = new Array();
		data["'.$this->pibase->extKey.'"]["'.$catid.'"] = category;
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
					data["'.$this->pibase->extKey.'"] = new Array();
					data["'.$this->pibase->extKey.'"][pi[func]] = value;
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
				data["'.$this->pibase->extKey.'"][element] = el.value;
			}
		}

			';
				if ($method == 'submitShow')	{
		        		$code .= $this->pibase->extKey.'_showList(data);';
				}
				$code .= '
		return true;
	}
	'		;
				break;

			case 'fetchdata':
				$code .= 'var vBoxCount = new Array('.count($params).'); // number of select boxes'.chr(13);
				$code .= 'var v = new Array(); // variants'.chr(13).chr(13);
				foreach ($params as $tablename => $variantFieldArray)	{
					if (is_array($variantFieldArray))	{
						$code .= 'vBoxCount["'.$tablename.'"] = '.(count($variantFieldArray)).';'.chr(13);
						$code .= 'v["'.$tablename.'"] = new Array('.count($variantFieldArray).');'.chr(13);
						$k = 0;
						foreach ($variantFieldArray as $variant => $field)	{
							$code .= 'v["'.$tablename.'"]['.$k.'] = "'.$field.'";'.chr(13);
							$k++;
						}
					}
				}
				$tableName = str_replace('_','-',$this->conftablename);
				$code .= '

	function doFetchRow(table, view, uid) {
		var data = new Array();
		var sb;
		var temp = table.split("_");
		var feTable = temp.join("-");

		data["view"] = view;
		data[table] = new Array();
		data[table]["uid"] = uid;
		for (var k = 0; k < vBoxCount[table]; k++) {
			var field = v[table][k];
			var id = feTable+"-"+view+"-"+uid+"-"+field;
			sb = document.getElementById(id);
			if (typeof sb == "object")	{
				try {
					var index = sb.selectedIndex;
					if (typeof index != "undefined")	{
						var value = sb.options[index].value;
						data[table][field] = value;
					}
				}
				catch (e)	{
					// nothing
				}
			}
		}
	';
				$code .= '	'.$this->pibase->extKey.'_fetchRow(data);
		return true;
	}';
				break;

			case 'direct':
				if (is_array($params))	{
					reset ($params);
					$code .= current($params);
					$JSfieldname = $fieldname .'-'. key($params);
				}
				break;

			case 'xajax':
				// XAJAX part
				if (!$this->bAjaxAdded && is_object($this->ajax) && is_object($this->ajax->taxajax))	{
					$code = $this->ajax->taxajax->getJavascript(t3lib_extMgm::siteRelPath(TAXAJAX_EXT));
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
					$TSFE->setJS($JSfieldname, $code);
				}
			}
		}
	} // setJS
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_javascript.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_javascript.php']);
}


?>