<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2007 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * subpart marker functions
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_subpartmarker {
	var $cObj; // reference to object
	var $conf;


	/**
	 * Initialized the marker object
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param	string		$fieldname is the field in the table you want to create a JavaScript for
	 * @param	array		array urls which should be overridden with marker key as index
	 * @return	void
	 */
	function init($cObj)	{
 		$this->cObj = $cObj;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

 		$this->conf = &$cnf->conf;
	}


	/**
	 * Returning template subpart marker
	 *
	 * @param	[type]		$subpartMarker: ...
	 * @return	[type]		...
	 */
	function spMarker($subpartMarker)	{
		$sPBody = substr($subpartMarker,3,-3);
		$altSPM = '';
		if (isset($this->conf['altMainMarkers.']))	{
			$altSPM = trim($this->cObj->stdWrap($this->conf['altMainMarkers.'][$sPBody], $this->conf['altMainMarkers.'][$sPBody.'.']));
			$GLOBALS['TT']->setTSlogMessage('Using alternative subpart marker for "' . $subpartMarker . '": ' . $altSPM, 1);
		}
		$rc = $altSPM ? $altSPM : $subpartMarker;
		return $rc;
	} // spMarker


	/**
	 * Returning template subpart array
	 *
	 * @param	[type]		$templateCode: ...
	 * @param	[type]		$subItemMarkerArray: ...
	 * @return	[type]		...
	 */
	function getTemplateSubParts($templateCode, $subItemMarkerArray)	{
		$rc = array();
		foreach ($subItemMarkerArray as $key => $subItemMarker)	{
			$rc[$subItemMarker] = substr($this->spMarker('###' . $subItemMarker . '_TEMPLATE###'), 3, -3);
		}
		return $rc;
	} // getTemplate


}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/marker/class.tx_ttproducts_subpartmarker.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/marker/class.tx_ttproducts_subpartmarker.php']);
}

?>