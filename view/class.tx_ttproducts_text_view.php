<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Franz Holzinger <contact@fholzinger.com>
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
 * functions for additional texts
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_text_view extends tx_ttproducts_table_base_view {
	public $marker = 'TEXT';

	function &getTagMarkerArray(&$tagArray, $parentMarker)	{
		$rcArray = array();
		$search = $parentMarker.'_'.$this->marker.'_';

		$searchLen = strlen($search);
		foreach ($tagArray as $marker => $k)	{
			if (substr($marker, 0, $searchLen) == $search)	{
				$tmp = substr($marker, $searchLen, strlen($marker) - $searchLen);
				$rcArray[] = $tmp;
			}
		}
		return $rcArray;
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	array		Returns a markerArray ready for substitution with information
	 * @param	[type]		$parentMarker: ...
	 * @param	[type]		$tagArray: ...
	 * @return	[type]		...
	 * @access private
	 */
	function getItemMarkerArray ($itemArray, &$markerArray, $parentMarker, $tagArray)	{
		$bFoundTagArray = array();

		if (isset($itemArray) && is_array($itemArray) && count($itemArray)) {
			foreach ($itemArray as $k => $row) {
				$tag = strtoupper($row['marker']);
				$bFoundTagArray[$tag] = TRUE;
				$marker = $parentMarker . '_' . $this->getMarker() . '_' . $tag;
				$value = $row['note'];
				$value = ($this->conf['nl2brNote'] ? nl2br($value) : $value);

					// Extension CSS styled content
				if (t3lib_extMgm::isLoaded('css_styled_content')) {
					$value = tx_div2007_alpha5::RTEcssText($this->getCObj(), $value);
				} else if (is_array($this->conf['parseFunc.'])) {
					$value = $this->getCObj()->parseFunc($value, $this->conf['parseFunc.']);
				}
				$markerArray['###' . $marker . '###'] = $value;
				$markerTitle = $marker . '_' . strtoupper('title');
				$markerArray['###' . $markerTitle . '###'] = $row['title'];
			}
		}


		if (isset($tagArray) && is_array($tagArray)) {
			foreach ($tagArray as $tag) {
				if (!$bFoundTagArray[$tag]) {
					$marker = $parentMarker . '_' . $this->getMarker() . '_' . $tag;
					$markerArray['###' . $marker . '###'] = '';
				}
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_text_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_text_view.php']);
}


?>