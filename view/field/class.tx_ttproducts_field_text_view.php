<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Franz Holzinger <franz@ttproducts.de>
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
 * functions for the title field
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_field_text_view extends tx_ttproducts_field_base_view {

	function getItemMarkerArray ($functablename, $fieldname, &$row, $markerKey, &$markerArray, $tagArray, $theCode, $id, &$bSkip, $bHtml=true, $charset='', $prefix='', $imageRenderObj='')	{

		$htmlentitiesArray = array();
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$tableconf = $cnf->getTableConf($functablename, $theCode);

		if (is_array($tableconf['functions.']) && isset($tableconf['functions.']['htmlentities']))	{
			$htmlentitiesArray = t3lib_div::trimExplode(',', $tableconf['functions.']['htmlentities']);
		}

		$value = $this->getModelObj()->getFieldValue($row, $fieldname);

		if ($bHtml && $charset != '' && in_array($fieldname, $htmlentitiesArray))	{
			$bConvertNewlines = $this->conf['nl2brNote'];
			if (
				$bConvertNewlines &&
				(
					$theCode != 'EMAIL' || $this->conf['orderEmail_htmlmail']
				)
			) {
				$value = nl2br($value);
			} else {
				$value = htmlentities($value, ENT_QUOTES, $charset);
			}
		}

		return $value;
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_text_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_text_view.php']);
}


?>