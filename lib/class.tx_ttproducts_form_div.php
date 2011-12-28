<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Franz Holzinger <franz@ttproducts.de>
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
 * functions for the display of forms
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

global $TYPO3_CONF_VARS;


class tx_ttproducts_form_div {

	function createSelect ($langObj, &$valueArray, $name, $indexSelected, $allowedArray = array()) {
		global $TYPO3_DB, $TSFE;

		$text = '';
		foreach ($valueArray as $key => $parts) {
			if (is_array($parts))	{
				$selectKey = $parts[1];
				$selectValue = $parts[0];
			} else {
				$selectKey = $key;
				$selectValue = $parts;
			}
			$tmp = tx_div2007_alpha::sL_fh001($selectValue);
			$text = tx_div2007_alpha5::getLL_fh002($langObj, $tmp);

			if ($text == '')	{
				$text = htmlentities($selectValue,ENT_QUOTES,$TSFE->renderCharset);
			}
			if (!count($allowedArray) || in_array($selectKey, $allowedArray))	{
				$selectedText = '';
				if (intval($selectKey) == intval($indexSelected))	{
					$selectedText = ' selected="selected"';
				}
				$totaltext .= '<option value="'.htmlentities($selectKey,ENT_QUOTES,$TSFE->renderCharset).'"'.$selectedText.'>'.$text.'</option>';
			}
		}
		$text = '<select name="'.$name.'">' . $totaltext .'</select>';
		return $text;
	}

	// fetches the valueArray needed for the functions of this class form a valueArray setup
	function fetchValueArray($confArray)	{
		$rcArray = array();
		if (is_array($confArray))	{
			foreach ($confArray as $k => $vArray)	{
				$rcArray [] = array(0 => $vArray['label'], 1 => $vArray['value']);
			}
		}
		return $rcArray;
	}

	function getKeyValueArray($valueArray)	{
		$rc = array();

		foreach ($valueArray as $k => $row)	{
			$rc[$row[1]] = $row[0];
		}
		return $rc;
	}
}

?>
