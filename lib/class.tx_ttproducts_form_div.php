<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2008 Franz Holzinger <contact@fholzinger.com>
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
 * functions for the display of forms
 *
 * $Id$
 *
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_form_div {

	static public function createSelect (&$langObj, &$valueArray, $name, $selectedKey, $bSelectTags=true, $bTranslateText=true, $allowedArray = array(), $type = 'select') {
		global $TYPO3_DB, $TSFE;

		$flags = ENT_QUOTES;

		if (is_array($valueArray))	{
			$totaltext = '';

			foreach ($valueArray as $key => $parts) {
				if (is_array($parts))	{
					$selectKey = $parts[1];
					$selectValue = $parts[0];
				} else {
					$selectKey = $key;
					$selectValue = $parts;
				}
				if ($bTranslateText)	{
					$tmp = tx_div2007_alpha5::sL_fh002($selectValue);
					$text = tx_div2007_alpha5::getLL_fh002($langObj, $tmp);
				} else {
					$text = '';
				}
				if ($text == '')	{
					if (strpos($selectValue,'LLL:EXT') === 0)	{
						continue;
					}
					$text = $selectValue;
				}

				if (!count($allowedArray) || in_array($selectKey, $allowedArray))	{
					$nameText = trim($text);
					$valueText = $selectKey;
					$selectedText = '';
					if ($selectKey == $selectedKey)	{
						switch ($type)	{
							case 'select':
								$selectedText = ' selected="selected"';
								break;
							case 'radio':
								$selectedText = ' checked="checked"';
								break;
						}
					}
					switch ($type)	{
						case 'select':
							$totaltext .= '<option value="' . $valueText . '"' . htmlspecialchars($selectedText, $flags) . '>' . $nameText . '</option>';
							break;
						case 'radio':
							$totaltext .= '<input type="radio" name="' . $name . '" value="' . htmlspecialchars($valueText, $flags) . '" ' . $selectedText . '> ' . $nameText;
							break;
					}
				}
			}
			if ($bSelectTags && $type == 'select')	{
				$text = '<select name="'.$name.'">' . $totaltext .'</select>';
			} else {
				$text = $totaltext;
			}
		} else {
			$text = FALSE;
		}

		return $text;
	}


	// fetches the valueArray needed for the functions of this class form a valueArray setup
	static public function fetchValueArray($confArray)	{
		$rcArray = array();
		if (is_array($confArray))	{
			foreach ($confArray as $k => $vArray)	{
				$rcArray [] = array(0 => $vArray['label'], 1 => $vArray['value']);
			}
		}
		return $rcArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$valueArray: ...
	 * @return	[type]		...
	 */
	static public function getKeyValueArray($valueArray)	{
		$rc = array();

		foreach ($valueArray as $k => $row)	{
			$rc[$row[1]] = $row[0];
		}
		return $rc;
	}
}


?>