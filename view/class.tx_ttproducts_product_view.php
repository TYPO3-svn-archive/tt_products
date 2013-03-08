<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2008 Franz Holzinger <franz@ttproducts.de>
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
 * functions for the product
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

require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_article_base_view.php');


class tx_ttproducts_product_view extends tx_ttproducts_article_base_view {
	public $relatedArray = array(); // array of related products
	public $marker = 'PRODUCT';
	public $type = 'product';
	public $piVar = 'product';
	public $articleArray = array();
	public $datafield;


	public function getItemMarkerSubpartArrays (
		&$templateCode,
		&$row,
		$markerArray,
		&$subpartArray,
		&$wrappedSubpartArray,
		&$tagArray,
		$code=''
	)	{
		parent::getItemMarkerSubpartArrays ($templateCode, $row, $markerArray, $subpartArray, $wrappedSubpartArray, $tagArray, $code);
		$extArray = $row['ext'];

		if (is_array($extArray) && is_array($extArray['tt_products']))	{
			$variant = $extArray['tt_products'][0]['vars'];
		} else if (is_array($extArray) && is_array($extArray['tx_dam']))	{
			$variant = $extArray['tx_dam'][0]['vars'];
		}
		$bGiftService = true;
		if ($this->getModelObj()->hasAdditional($row,'noGiftService'))	{
			$bGiftService = false;
		}

		$this->variant->getVariantSubpartMarkerArray (
			$subpartArray,
			$markerArray,
			$row,
			$templateCode,
			$bSelectVariants,
			$this->conf,
			$this->getModelObj()->hasAdditional($row,'isSingle'),
			$bGiftService
		);

		$modelObj = $this->getModelObj();
		$datafieldViewObj = $this->getFieldObj('datasheet');
		$datafieldViewObj->getItemSubpartArrays(
				$templateCode,
				$this->marker,
				$modelObj->getFuncTablename(),
				$row,
				'datasheet',
				$modelObj->getTableConf($code),
				$subpartArray,
				$wrappedSubpartArray,
				$tagArray,
				$code
		);
	}

	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	public function getItemMarkerArray (
		&$item,
		&$markerArray,
		$catTitle,
		$imageNum=0,
		$imageRenderObj='image',
		&$tagArray,
		$forminfoArray=array(),
		$theCode='',
		$id='1',
		$prefix='',
		$linkWrap='',
		$bHtml=true,
		$charset=''
	)	{

			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		$row = &$item['rec'];
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$modelObj = $this->getModelObj ();

		parent::getItemMarkerArray($item, $markerArray, $catTitle, $imageNum, $imageRenderObj, $tagArray, $forminfoArray, $theCode, $id, '', $linkWrap, $bHtml, $charset);
		$datafieldViewObj = $this->getFieldObj('datasheet');
		if (isset($datafieldViewObj) && is_object($datafieldViewObj))	{
			$datafieldViewObj->getItemMarkerArray($modelObj->getTablename(), 'datasheet', $row, $this->marker, $markerArray, $tagArray, $theCode, $id, $tmp, FALSE,'','', 'datasheetIcon');
		}

			// Subst. fields
		$markerArray['###'.$this->marker.'_UNIT###'] = $row['unit'];
		$markerArray['###'.$this->marker.'_UNIT_FACTOR###'] = $row['unit_factor'];
		$markerArray['###'.$this->marker.'_WWW###'] = $row['www'];
		$markerArray['###BULKILY_WARNING###'] = $row['bulkily'] ? $this->conf['bulkilyWarning'] : '';

		if ($this->conf['itemMarkerArrayFunc'])	{
			$markerArray = tx_div2007_alpha::userProcess_fh001($this, $this->conf, 'itemMarkerArrayFunc', $markerArray);
		}

		if ($theCode == 'SINGLE')	{
			if ($row['note_uid']) {
				$pageObj = $tablesObj->get('pages');

				$notePageArray = $pageObj->getNotes ($row['uid']);
				$confObj = t3lib_div::getUserObj('&tx_ttproducts_config');
				$contentConf = $confObj->getTableConf('tt_content', $code);

				foreach ($notePageArray as $k => $pid)	{
					$pageRow = $pageObj->get($pid);
					$pageMarkerKey = 'PRODUCT_NOTE_UID_'.($k + 1);
					$contentArray = $tablesObj->get('tt_content')->getFromPid($pid);
					$countArray = array();
					foreach ($contentArray as $k2 => $contentEl)	{
						$cType = $contentEl['CType'];
						$countArray[$cType] = intval($countArray[$cType]) + 1;
						$markerKey = $pageMarkerKey.'_'.$countArray[$cType].'_'.strtoupper($cType);
						foreach ($tagArray as $index => $v)	{
							$pageFoundPos = strpos($index, $pageMarkerKey);
							if ($pageFoundPos == 0 && $pageFoundPos !== FALSE)	{
								$fieldName = str_replace($pageMarkerKey.'_','',$index);
								if (isset($pageRow[$fieldName]))	{
									$markerArray['###'.$index.'###'] = $pageRow[$fieldName];
								}
							}
							if (strstr($index, $markerKey) === FALSE)	{
								continue;
							}
							$fieldPos = strrpos($index, '_');
							$fieldName = substr($index, $fieldPos+1);
							$markerArray['###'.$index.'###'] = $contentEl[$fieldName];
							if (isset ($contentConf['displayFields.']) && is_array ($contentConf['displayFields.']) && $contentConf['displayFields.'][$fieldName] == 'RTEcssText')	{
									// Extension CSS styled content
								if (t3lib_extMgm::isLoaded('css_styled_content')) {
									$markerArray['###'.$index.'###'] =
										$this->pibase->pi_RTEcssText($contentEl[$fieldName]);
								} else if (is_array($this->conf['parseFunc.']))	{
									$markerArray['###'.$index.'###'] =
										$this->cObj->parseFunc($contentEl[$fieldName],$this->conf['parseFunc.']);
								}
							}
						}
					}
				}
			}

			foreach ($tagArray as $key => $val)	{
				if (strstr($key,'PRODUCT_NOTE_UID')!==FALSE)	{
					if (!isset($markerArray['###'.$key.'###']))	{
						$markerArray['###'.$key.'###'] = '';
					}
				}
			}
		}

		if ($row['special_preparation'])	{
			$markerArray['###'.$this->marker.'_SPECIAL_PREP###'] = $this->cObj->substituteMarkerArray($this->conf['specialPreparation'], $markerArray);
		} else	{
			$markerArray['###'.$this->marker.'_SPECIAL_PREP###'] = '';
		}
	} // getItemMarkerArray
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_product_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_product_view.php']);
}


?>