<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
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
 * functions for the data sheets
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_field_datafield_view {

	function getItemSubpartArrays ($itemTableConf, &$row, &$wrappedSubpartArray, &$markerArray, &$cObj)	{
		global $TCA;

		$datasheetFile = $row['datasheet'];
		if($datasheetFile == '')  {
			$wrappedSubpartArray['###LINK_DATASHEET###'] = array('<!--','-->');
		}  else  {
			if (
				isset($itemTableConf['fieldLink.']) &&
				is_array($itemTableConf['fieldLink.']) &&
				isset($itemTableConf['fieldLink.']['datasheet.'])
			)	{
				$typolinkConf = $itemTableConf['fieldLink.']['datasheet.'];
			} else {
				$typolinkConf = array();
			}
			$typolinkConf['parameter'] = 'uploads/tx_ttproducts/datasheet/'.$datasheetFile;
			$linkTxt = '9999999999';
			$typoLink = $cObj->typoLink($linkTxt, $typolinkConf);
			$wrappedSubpartArray['###LINK_DATASHEET###'] = t3lib_div::trimExplode($linkTxt,$typoLink);
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_datafield_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_datafield_view.php']);
}

?>
