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
 */
class tx_ttproducts_field_datafield_view extends tx_ttproducts_field_base_view {


	function getLinkArray (
		&$wrappedSubpartArray,
		$tagArray,
		$marker,
		$dirname,
		$dataFile,
		$fieldname,
		$tableConf
	) {
		if ($tagArray[$marker])	{

			// $wrappedSubpartArray['###' . $marker . '###'] = array('<a href="' . $dirname . '/' . $dataFile . '">','</a>');

			if (
				isset($tableConf['fieldLink.']) &&
				is_array($tableConf['fieldLink.']) &&
				isset($tableConf['fieldLink.'][$fieldname.'.'])
			)	{
				$typolinkConf = $tableConf['fieldLink.'][$fieldname.'.'];
			} else {
				$typolinkConf = array();
			}
			$typolinkConf['parameter'] = $dirname . '/' . $dataFile;
			$linkTxt = microtime();
			$typoLink = $this->cObj->typoLink($linkTxt, $typolinkConf);
			$wrappedSubpartArray['###' . $marker . '###'] = t3lib_div::trimExplode($linkTxt, $typoLink);
		}
	}



	public function getRepeatedRowSubpartArrays (
		&$subpartArray,
		&$wrappedSubpartArray,
		$markerKey,
		$row,
		$fieldname,
		$key,
		$value,
		$fieldname,
		$tableConf,
		$tagArray
	) {
		$dirname = $this->modelObj->getDirname($row, $fieldname);
		$upperField = strtoupper($fieldname);
		$marker = $markerKey . '_LINK_' . $upperField;

		$this->getLinkArray(
			$wrappedSubpartArray,
			$tagArray,
			$marker,
			$dirname,
			$value,
			$fieldname,
			$tableConf
		);
	}

	public function &getItemSubpartArrays (
		&$templateCode,
		$markerKey,
		$functablename,
		&$row,
		$fieldname,
		$tableConf,
		&$subpartArray,
		&$wrappedSubpartArray,
		&$tagArray,
		$theCode = '',
		$id = '1'
	) {
		$this->getRepeatedSubpartArrays (
			$subpartArray,
			$wrappedSubpartArray,
			$templateCode,
			$markerKey,
			$functablename,
			$row,
			$fieldname,
			$tableConf,
			$tagArray,
			$theCode,
			$id
		);

		$dirname = $this->getModelObj()->getDirname($row, $fieldname);
		$dataFileArray = t3lib_div::trimExplode(',', $row[$fieldname]);
		$upperField = strtoupper($fieldname);

		if (count($dataFileArray) && $dataFileArray[0])	{
			foreach ($dataFileArray as $k => $dataFile)	{

				$marker = $markerKey . '_LINK_' . $upperField . ($k+1);
				$this->getLinkArray(
					$wrappedSubpartArray,
					$tagArray,
					$marker,
					$dirname,
					$dataFile,
					$fieldname,
					$tableConf
				);
			}

			$marker = $markerKey.'_LINK_'.$upperField;
			$this->getLinkArray(
				$wrappedSubpartArray,
				$tagArray,
				$marker,
				$dirname,
				$dataFileArray[0],
				$fieldname,
				$tableConf
			);

// 			$wrappedSubpartArray[$marker] = array('<a href="'.$dirname.'/'.$dataFileArray[0].'">','</a>');
		}

		// empty all image fields with no available image
		foreach ($tagArray as $value => $k1)	{
			$keyMarker = '###'.$value.'###';
			if (strpos($value, $markerKey . '_LINK_' . $upperField) !== FALSE && !$wrappedSubpartArray[$keyMarker])	{

				$wrappedSubpartArray[$keyMarker] =  array('<!--','-->');
			}
		}
	}


	function getRepeatedRowMarkerArray (
		&$markerArray,
		$markerKey,
		$functablename,
		$row,
		$fieldname,
		$key,
		$value,
		$tableConf,
		$tagArray,
		$theCode='',
		$id='1'
	)	{
// ++++
		$dirname = $this->modelObj->getDirname($row, $fieldname);
		$upperField = strtoupper($fieldname);
		$marker = $markerKey . '_' . $upperField;

		$this->getSingleValueArray(
			$markerArray,
			$marker,
			$tagArray,
			$imageConf,
			$dirname,
			$value
		);
		$marker1 = 'ICON_' . strtoupper($fieldname);
		$this->getIconMarker (
			$markerArray,
			$marker1,
			$tagArray,
			'datasheetIcon'
		);

		return TRUE;
	}


	function getSingleValueArray (
		&$markerArray,
		$marker,
		$tagArray,
		$imageConf,
		$dirname,
		$dataFile
	) {
		$imageConf['file'] = $dirname . '/' . $dataFile;
		$iconImgCode = $this->cObj->IMAGE($imageConf);

		if (isset($tagArray[$marker])) {
			$markerArray['###' . $marker . '###'] = $iconImgCode; // new marker now
		}

		if (isset($tagArray[$marker . '_FILE'])) {
			$markerArray['###' . $marker . '_FILE###'] = basename($imageConf['file']);
		}
	}


	function getIconMarker (
		&$markerArray,
		$marker,
		$tagArray,
		$imageRenderObj
	) {

		if (isset($imageRenderObj)) {

			$imageConf = $this->conf[$imageRenderObj . '.'];

			if (isset($tagArray[$marker]) && isset($this->conf['datasheetIcon.']))	{
				if ($this->conf['datasheetIcon.']['file'] != '{$plugin.tt_products.file.datasheetIcon}')	{
					$imageConf['file'] = $this->conf['datasheetIcon.']['file'];
					$iconImgCode = $this->cObj->IMAGE($imageConf);

					$markerArray['###' . $marker . '###'] = $iconImgCode;
				} else {
					$markerArray['###' . $marker . '###'] = '';
				}
			} else {
				$markerArray['###' . $marker . '###'] = '';
			}
		} else if (isset($tagArray[$marker]))	{
			$markerArray['###' . $marker . '###'] = '';
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

		$value = $row[$fieldname];
		$marker1 = 'ICON_' . strtoupper($fieldname);
		$dirname = $this->modelObj->getDirname($row, $fieldname);
		$dataFileArray = t3lib_div::trimExplode(',', $value);

		$this->getIconMarker (
			$markerArray,
			$marker1,
			$tagArray,
			$imageRenderObj
		);

		if (count($dataFileArray) && $dataFileArray[0])	{
			foreach ($dataFileArray as $k => $dataFile)	{
				$marker = $markerKey . '_' . $upperField . ($k+1);

				$this->getSingleValueArray(
					$markerArray,
					$marker,
					$tagArray,
					$imageConf,
					$dirname,
					$dataFile
				);
			}
		}

		for ($i = 1; $i <= 50; ++$i) {
			$marker2 = $markerKey . $i;

			if (isset($tagArray[$marker2]) && !isset($markerArray['###' . $marker2 . '###'])) {
				$markerArray['###' . $marker2 . '###'] = '';
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_datafield_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_datafield_view.php']);
}

?>
