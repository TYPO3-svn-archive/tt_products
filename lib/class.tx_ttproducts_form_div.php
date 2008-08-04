<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com> 
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

global $TYPO3_CONF_VARS;


class tx_ttproducts_form_div {

	function createSelect (&$pibase, &$valueArray, $name, $indexSelected, $allowedArray = array()) {
		global $TYPO3_DB, $TSFE;

		$text = '';
		foreach ($valueArray as $key => $parts) {
			$tmp = tx_fhlibrary_language::sL($parts[0]);
			$text = $pibase->pi_getLL($tmp);
			if ($text == '')	{
				$text = htmlentities($parts[0],ENT_QUOTES,$TSFE->renderCharset);
			}
			if (!count($allowedArray) || in_array($parts[1], $allowedArray))	{
				$selectedText = '';
				if (intval($parts[1]) == intval($indexSelected))	{
					$selectedText = ' selected';
				}
				$totaltext .= '<OPTION value="'.htmlentities($parts[1],ENT_QUOTES,$TSFE->renderCharset).'"'.$selectedText.'>'.htmlentities($text,ENT_QUOTES,$TSFE->renderCharset).'</OPTION>';
			}
		}
		$text = '<SELECT name="'.$name.'">' . $totaltext .'</SELECT>';
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
