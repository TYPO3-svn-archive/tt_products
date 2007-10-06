<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2006 Franz Holzinger <kontakt@fholzinger.com>
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
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_javascript {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $page;
	var $xajax;
	var $bXajaxAdded;


	function init(&$pibase, &$cnf, &$page, &$xajax) {
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;
		$this->page = &$page;
		$this->xajax = &$xajax;
		$this->bXajaxAdded = false;
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
	function set($fieldname, $params='', $count=0) {
		global $TSFE;
		$bDirectHTML = false;
		$code = '';
		$bError = false;
		$emailArr =  explode('|', $message = $this->pibase->pi_getLL('invalid_email'));

		$JSfieldname = $fieldname;
		switch ($fieldname) {
			case 'email' :
				$code =
				'function test (eing) {
					var reg = /@/;
					var rc = true;
					if (!reg.exec(eing)) {
				 		rc = false;
				 	}
				 	return rc;
				}
	
				function checkEmail(element) {
					if (test(element.value)){
						return (true)
					}
	/* Added els5: comma after the invalid address */
	//				alert("'.$emailArr[0].'\'"+element.value+"'.$emailArr[1].'")
					alert("'.$emailArr[0].'\'"+element.value+"\''.$emailArr[1].'")
					return (false)
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
				if ($this->pibase->pageAsCategory == 2)	{
					$catIndex = 'pid';
				} else {
					$catIndex = 'cat';
				}
				if (is_object($this->xajax))	{
					$code .= 'var c = new Array(); // categories
		var boxCount = '.$count.'; // number of select boxes
';
					if (is_array($params))	{
						foreach ($params as $k => $row)	{
							$code .= 'c['.$k.'] = new Array(3);';
							$code .= 'c['.$k.'][0] = "'.$row['title'].'"; ' ;
							$code .= 'c['.$k.'][1] = "'.$row['pid'].'"; ' ;
							$child_category = $row['child_category'];
							if (is_array($child_category))	{
								$code .= 'c['.$k.'][2] = new Array('.count($child_category).');';
								$count = 0;
								foreach ($child_category as $k1 => $childCat)	{
									$newCode = 'c['.$k.'][2]['.$count.'] = "'.$childCat.'"; ';
									$code .= $newCode;
									$count++;
								}
							} else {
								$code .= 'c['.$k.'][2] = "0"; ' ;
							}
						$code .= '
	';
						}
					}
					$code .=
		'
		function fillSelect (select,id,showSubCategories) {
		var sb;
		var index = select.selectedIndex;
		var category = select.options[index].value;
		var subcategories;
		var bShowArticle = 0;
		var len;
		var idel;
        var b;
	
	    if (id > 0) {
	        for (var l=boxCount; l>=id+1; l--)	{
	        	idel = "'.$catIndex.'" + l;
	        	sb = document.getElementById(idel);
			    sb.length = 0;
		        sb.selectedIndex = 0;
	        }
			idel = "'.$catIndex.'" + id;
			sb = document.getElementById(idel);
		    sb.length = 0;

		    subcategories = c[category][2]; 
		    if ((typeof(subcategories) == "object") && (showSubCategories == 1)) {
		        sb.options[0] = new Option("", "A");
		        len = subcategories.length;';
		        	$code .= '
		        for (var k = 0;k < len; k++) {
			        sb.options[k+1] = new Option(c[c[category][2][k]][0], c[category][2][k]);
		        }
		    } else {
		    	bShowArticle = 1;
		    }
	    } else {
	    	bShowArticle = 1;
	    }
	    if (bShowArticle)	{
	        /* sb.options[0] = new Option(len, "keine Unterkategorie");*/
	        var data = new Array();
			sb = document.getElementById("'.$catIndex.'"+2);
	        sb.options[0] = new Option("", "B");
	        data["'.$this->pibase->prefixId.'"] = new Array();
	        data["'.$this->pibase->prefixId.'"]["'.$catIndex.'"] = category;
	        tt_products_showArticle(data);
	    } else {
			sb = document.getElementById("'.$catIndex.'"+2);
	        sb.options[0] = new Option("", "C");
	        sb.selectedIndex = 0;
	        select.selectedIndex = index;
	        // sb.options[0] = new Option("keinen Artikel anzeigen \'"+bShowArticle+"\'", "C");
	    }
	    /* sb.options[0] = new Option("Test", "keine Unterkategorie");
		sb.selectedIndex = 0; */
		';
					$code .= '
		}
		';
				}
				break;

			case 'direct':
				if (is_array($params))	{
					$code = current($params);
					$JSfieldname = $fieldname .'-'. key($params);
				}
				break;

			case 'ttpajax':
				// XAJAX part
				if (!$this->bXajaxAdded && is_object($this->xajax))	{
					$code .= $this->xajax->getJavascript(t3lib_extMgm::siteRelPath('xajax')); 
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
