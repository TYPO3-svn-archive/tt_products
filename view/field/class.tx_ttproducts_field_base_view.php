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
 * base class for all database table fields view classes
 *
 * $Id$
 *
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

// require_once (PATH_BE_ttproducts.'view/field/interface.tx_ttproducts_field_view_int.php');


abstract class tx_ttproducts_field_base_view implements tx_ttproducts_field_view_int	{
	private $bHasBeenInitialised = FALSE;
	public $modelObj;
	public $cObj;
	public $conf;		// original configuration
	public $config;		// modified configuration
	public $langObj;

	public function init ($langObj, $modelObj)	{
		$this->langObj = $langObj;
		$this->modelObj = $modelObj;
		$this->cObj = $modelObj->cObj;
		$this->conf = &$modelObj->conf;
		$this->config = &$modelObj->config;

		$this->bHasBeenInitialised = TRUE;
	}

	public function needsInit ()	{
		return !$this->bHasBeenInitialised;
	}

	public function getModelObj ()	{
		return $this->modelObj;
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
		// overwrite this!
		return FALSE;
	}

	public function getRepeatedRowMarkerArray (
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
		// overwrite this!
		return FALSE;
	}

	public function getRepeatedSubpartArrays (
		&$subpartArray,
		&$wrappedSubpartArray,
		$templateCode,
		$markerKey,
		$functablename,
		$row,
		$fieldname,
		$tableConf,
		$tagArray,
		$theCode='',
		$id='1'
	)	{
		$result = FALSE;
		$newContent = '';
		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$upperField = strtoupper($fieldname);
		$templateAreaList = $markerKey . '_' . $upperField . '_LIST';
		$t = array();
		$t['listFrameWork'] = $this->cObj->getSubpart($templateCode, '###' . $templateAreaList . '###');
// 			$t['listFrameWork'] = $this->cObj->substituteMarkerArrayCached(
// 				$t['listFrameWork'],
// 				$markerArray,
// 				$subpartArray,
// 				$wrappedSubpartArray
// 			);

		$templateAreaSingle = $markerKey . '_' . $upperField . '_SINGLE';
		$t['singleFrameWork'] = $this->cObj->getSubpart($t['listFrameWork'], '###' . $templateAreaSingle . '###');

		if ($t['singleFrameWork'] != '') {
			$repeatedTagArray = $markerObj->getAllMarkers($t['singleFrameWork']);

			$value = $row[$fieldname];
			$valueArray = t3lib_div::trimExplode(',', $value);

			if (isset($valueArray) && is_array($valueArray) && $valueArray['0'] != '') {

				$content = '';
				foreach ($valueArray as $key => $value) {
					$repeatedMarkerArray = array();
					$repeatedSubpartArray = array();
					$repeatedWrappedSubpartArray = array();

					$resultRowMarker = $this->getRepeatedRowMarkerArray (
						$repeatedMarkerArray,
						$markerKey,
						$functablename,
						$row,
						$fieldname,
						$key,
						$value,
						$tableConf,
						$tagArray,
						$theCode,
						$id
					);

					$this->getRepeatedRowSubpartArrays (
						$repeatedSubpartArray,
						$repeatedWrappedSubpartArray,
						$markerKey,
						$row,
						$fieldname,
						$key,
						$value,
						$fieldname,
						$tableConf,
						$tagArray
					);

					$newContent = $this->cObj->substituteMarkerArrayCached(
						$t['singleFrameWork'],
						$repeatedMarkerArray,
						$repeatedSubpartArray,
						$repeatedWrappedSubpartArray
					);

					$result = $resultRowMarker;
					if ($result) {
						$content .= $newContent;
					}
				}
				$newContent = $this->cObj->substituteMarkerArrayCached(
					$t['listFrameWork'],
					array(),
					array('###' . $templateAreaSingle . '###' => $content),
					array()
				);
			}
		}
		$subpartArray['###' . $templateAreaList . '###'] = $newContent;
		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_base_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_base_view.php']);
}


?>