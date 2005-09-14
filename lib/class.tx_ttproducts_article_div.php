<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005-2005 Franz Holzinger <kontakt@fholzinger.com>
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
 * Part of the tt_products (Shopping System) extension.
 *
 * article functions without object instance
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *  
 */


class tx_ttproducts_article_div {

       /**
         * Sets JavaScript code in the additionalJavaScript array
         *
         * @param       string          $fieldname is the field in the table you want to create a JavaScript for
         * @return      void
         * @see 
         */

	/**
	 * fills in the row fields from the variant extVar string
	 *
	 * @param	array		the row
	 * @param	string		variants separated by ';'
	 * @return	void
	 * @access private
	 * @see getVariantFromRow
	 */
	 function getRowFromVariant (&$row, $variant) {
		$variantArray = explode(';', $variant);
		$row['color'] = $variantArray[0];
		$row['size'] = $variantArray[1];
		$row['accessory'] = floatval($variantArray[2]);
		$row['gradings'] = $variantArray[3];
	 }


	/**
	 * Returns the variant extVar string from the variant values in the row
	 *
	 * @param	array		the row
	 * @param	string		variants separated by ';'
	 * @return	void
	 * @access private
	 * @see getRowFromVariant
	 */
	 function getVariantFromRow (&$row) {
	 	// take only the first color, size and gradings, if there are more entries from the products table
	 	$tmpArr = explode(';', $row['color']);
	 	$color = $tmpArr[0]; 
	 	$tmpArr = explode(';', $row['size']);
	 	$size = $tmpArr[0]; 
	 	$tmpArr = explode(';', $row['gradings']);
	 	$gradings = $tmpArr[0]; 
	 	
	 	$rc = $color.';'.$size.';'.intval(100*$row['accessory']).';'.$gradings;
	 	return $rc; 
	 }


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_article_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_article_div.php']);
}


?>
