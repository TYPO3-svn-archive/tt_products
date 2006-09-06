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
 * JavaScript marker functions
 *
 * $Id:$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_javascript_marker {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $needXajax;
	var $javaScript; // reference to JavaScript object
	var $marker = 'JAVASCRIPT';


	function init(&$pibase, &$cnf, &$javaScript) {
		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;
		$this->bneedXajax = false;
		$this->javaScript = &$javaScript;
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a JavaScript
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	function getMarkerArray (&$markerArray)	{
		if (is_array($this->conf['javaScript.']))	{
			$paramsArray = array();
			foreach ($this->conf['javaScript.'] as $key => $confJS)	{
				$marker = rtrim($key,'.');
				$paramsArray[$marker] = $confJS['value'];
				$this->javaScript->set('direct', $paramsArray);
				$marker = '###'.$this->marker.'_'.strtoupper($marker).'###';
				$markerArray[$marker] = '';
			}
		}
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/marker/class.tx_ttproducts_javascript_marker.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/marker/class.tx_ttproducts_javascript_marker.php']);
}


?>
