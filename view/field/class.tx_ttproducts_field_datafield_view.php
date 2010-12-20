<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_field_datafield_view extends tx_ttproducts_field_base_view {

	function &getItemSubpartArrays (&$templateCode, &$row, $fieldname, &$subpartArray, &$wrappedSubpartArray, &$tagArray, $theCode='', $id='1')	{
		global $TCA;

		$dirname = $this->getModelObj()->getDirname ($row, $fieldname);
		$dataFileArray = t3lib_div::trimExplode(',',$row[$fieldname]);
		$upperField = strtoupper($fieldname);

		if (count($dataFileArray) && $dataFileArray[0])	{
			foreach ($dataFileArray as $k => $dataFile)	{
				$marker = '###LINK_'.$upperField.($k+1).'###';
				if ($tagArray[$marker])	{
					$wrappedSubpartArray[$marker] = array('<a href="'.$dirname.'/'.$dataFile.'">','</a>');
				}
			}
			$marker = '###LINK_'.$upperField.'###';
			$wrappedSubpartArray[$marker] = array('<a href="'.$dirname.'/'.$dataFileArray[0].'">','</a>');
		}


		// empty all image fields with no available image
		foreach ($tagArray as $value => $k1)	{
			$keyMarker = '###'.$value.'###';
			if (strstr($value, '_'.$upperField) && strstr($value, 'LINK') && !$wrappedSubpartArray[$keyMarker])	{
				$wrappedSubpartArray[$keyMarker] =  array('<!--','-->');
			}
		}
	}

	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * 				for the tt_producst record, $row
	 *
	 * @param	string		name of the marker prefix
	 * @param	array		reference to an item array with all the data of the item
	 * @param	[type]		$row: ...
	 * @param	[type]		$markerKey: ...
	 * @param	[type]		$markerArray: ...
	 * @param	[type]		$tagArray: ...
	 * @param	[type]		$theCode: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$bSkip: ...
	 * @param	[type]		$bHtml: ...
	 * @param	[type]		$charset: ...
	 * @param	[type]		$prefix: ...
	 * @param	[type]		$imageRenderObj: ...
	 * @return	[type]		...
	 * @access private
	 */
	function getItemMarkerArray ($functablename, $fieldname, &$row, $markerKey, &$markerArray, $tagArray, $theCode, $id, &$bSkip, $bHtml=true, $charset='', $prefix='', $imageRenderObj='')	{

		$val = $row[$fieldname];
		if (isset($imageRenderObj) && $val)	{
			$imageConf = $this->conf[$imageRenderObj.'.'];
			$dirname = $this->modelObj->getDirname ($row, $fieldname);
			$iconImgCode = $this->cObj->IMAGE($imageConf);
			$markerArray['###ICON_'.strtoupper($fieldname).'###'] = $iconImgCode;
			$imageConf['file'] = $dirname.'/'.$val;
			$iconImgCode = $this->cObj->IMAGE($imageConf);
			$markerArray['###'.$markerKey.'_'.strtoupper($fieldname).'1###'] = $iconImgCode; // new marker now
		} else {
			$markerArray['###ICON_'.strtoupper($fieldname).'###'] = '';
			$markerArray['###'.$markerKey.'_'.strtoupper($fieldname).'1###'] = '';
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_datafield_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_datafield_view.php']);
}

?>
