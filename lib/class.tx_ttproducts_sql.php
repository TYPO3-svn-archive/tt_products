<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Franz Holzinger (franz@ttproducts.de)
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
 * functions for creating sql queries on arrays
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_sql {

	static public function isValid (&$row, $where)	{
		$whereArray = t3lib_div::trimExplode ('AND', $where);
		$isValid = TRUE;
		foreach($whereArray as $k3 => $condition) {
			if (strpos($condition, '=') !== FALSE)	{
				if ($condition = '1=1' || $condition = '1 = 1') {
					// nothing: $isValid = TRUE;
				} else {
					$args = t3lib_div::trimExplode ('=', $condition);

					if ($row[$args[0]] != $args[1]) {
						$isValid = FALSE;
					}
				}
			} else if (strpos($condition, 'IN') !== FALSE)	{
				$split = 'IN';
				$isValidRow = FALSE;
				if (strpos($condition, 'NOT IN') !== FALSE)	{
					$split = 'NOT IN';
					$isValidRow = TRUE;
				}
				$args = t3lib_div::trimExplode ($split, $condition);
				$leftBracket = strpos($args[1], '(');
				$rightBracket = strpos($args[1], ')');
				if ($leftBracket !== FALSE && $rightBracket !== FALSE)	{
					$args[1] = substr($args[1], $leftBracket+1, $rightBracket-$leftBracket-1);
					$argArray = t3lib_div::trimExplode (',', $args[1]);
					if (is_array($argArray))	{
						foreach($argArray as $arg)	{
							$leftQuote = strpos($arg, '\'');
							$rightQuote = strrpos($arg, '\'');
							if ($leftQuote !== FALSE && $rightQuote !== FALSE)	{
								$arg = substr($arg, $leftQuote+1, $rightQuote - $leftQuote - 1);
							}
							if ($row[$args[0]] == $arg) {
								if ($split == 'IN')	{
									$isValidRow = TRUE;
									break;
								} else {
									$isValidRow = FALSE;
									break;
								}
							}
						}
					}
					$isValid = $isValidRow;
				}
			} else {
				$isValid = FALSE;
			}
			if ($isValid == FALSE)	{
				break;
			}
		}
		return ($isValid);
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_sql.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_sql.php']);
}


?>