<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * functions for the images
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_field_image_view extends tx_ttproducts_field_media_view {

	/**
	 * @param	[type]		$$langObj: ...
	 * @param	[type]		$modelObj: ...
	 * @return	[type]		...
	 */
	function init($langObj, $modelObj)	{
		parent::init($langObj, $modelObj);

		if ($this->conf['noImageAvailable'] == '{$plugin.tt_products.file.noImageAvailable}')	{
			$this->conf['noImageAvailable'] = '';
		}
	} // init


	function getSingleImageMarkerArray ($markerKey, &$markerArray, &$imageConf, $theCode)	{
		$tmpImgCode = $this->getImageCode($imageConf, $theCode);
		$markerArray['###'.$markerKey.'###'] = $tmpImgCode;
	}


	/**
	 * deprecated
	 * This function must be replaced by getItemMarkerArray and getMediaMarkerArray
	 *
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * 				for the tt_producst record, $row
	 *
	 * @param	string		name of the marker prefix
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @param	[type]		$imageRenderObj: ...
	 * @param	[type]		$tagArray: ...
	 * @param	[type]		$theCode: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$prefix: ...
	 * @param	[type]		$linkWrap: ...
	 * @return	array		Returns a markerArray ready for substitution with information
	 * @access private
	 */
	function getItemMarkerArrayEnhanced ($functablename, $row, $marker, &$markerArray, $pid, $imageNum=0, $imageRenderObj='image', &$tagArray, $theCode, $id='', $prefix='', $linkWrap = '')	{
		global $TYPO3_DB;
// TODO: use $prefix

		$imageRow = $row;
		$bImages = false;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$tableConf = $cnf->getTableConf($functablename, $theCode);
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');

			// Get image
		$theImgDAM = array();
		$specialImgCode = array();
		if (is_array($tableConf))	{
			$imageMarkerArray = $tableConf['imageMarker.'];
		}
		$imgs = array();
		$imageField = 'image';
		if ($functablename == 'pages')	{
			$imageField = 'media';
		}

		if (is_array($tableConf['fetchImage.']) &&
			$tableConf['fetchImage.']['type'] == 'foreigntable'  &&
			isset($tableConf['fetchImage.']['table'])) {
			$pageContent = $tablesObj->get($tableConf['fetchImage.']['table'])->getFromPid($pid);
			foreach ($pageContent as $pid => $contentRow) {
				if ($contentRow[$imageField]) {
					$imgs[] = $contentRow[$imageField];
				}
			}
			$bImages = true;
		}

		if (!$bImages)	{
			$fieldconfParent = array();
			if (is_array($tableConf))	{
				$tempConf = '';
				if	(
					is_array($tableConf['generateImage.']) &&
					$tableConf['generateImage.']['type'] == 'foreigntable'
				)	{
					$tempConf = &$tableConf['generateImage.'];
				}
				if (is_array($tempConf) && $imageRow)	{
					$conftable = $tempConf['table'];
					$localfield = $tempConf['uid_local'];
					$foreignfield = $tempConf['uid_foreign'];
					$fieldconfParent['generateImage'] = $tempConf['field.'];
					$where_clause = $conftable.'.'.$foreignfield .'='. $imageRow[$localfield];
					$where_clause .= $this->cObj->enableFields($conftable);
					$res = $TYPO3_DB->exec_SELECTquery('*', $conftable, $where_clause, '', $foreignfield, 1);
						// only first found row will be used
					$imageRow = $TYPO3_DB->sql_fetch_assoc($res);
				}
			}

			// $confParentTableConf = $this->getTableConf($conftable, $theCode);
			$conftable = ($conftable ? $conftable : $functablename);
			$generateArray = array('generateImage', 'generatePath');
			$nameArray = array();
			$conftableConf = $cnf->getTableConf($conftable, $theCode);

			foreach ($generateArray as $k => $generate)	{
				if (is_array($conftableConf) &&
				 	is_array($conftableConf[$generate.'.'])) {
				 	$genPartArray = $conftableConf[$generate.'.'];
				 	$tableFieldsCode = '';

				 	if ($genPartArray['type'] == 'tablefields')	{
				 		$nameArray[$generate] = '';
				 		$fieldConf = $genPartArray['field.'];

						if (is_array($fieldConf))	{
							if (is_array($fieldconfParent[$generate]))	{
								$fieldConf = array_merge($fieldConf, $fieldconfParent[$generate]);
							}

							foreach ($fieldConf as $field => $count)	{
								if ($imageRow[$field])	{
									$nameArray[$generate] .= substr($imageRow[$field], 0, $count);
									if ($generate == 'generateImage')	{
										$bImages = true;
									}
								}
							}
				 		}
				 	}
				}
			}

			if ($nameArray['generatePath'])	{
				if (is_array($conftableConf['generatePath.']))	{
					$dirname = $conftableConf['generatePath.']['base'].'/'.$nameArray['generatePath'];
				}
				if ($nameArray['generateImage'] && is_dir($dirname))	{
					$directory = dir($dirname);
					while($entry=$directory->read())	{
						if (strstr($entry, $nameArray['generateImage'].'_') !== FALSE)	{
							$imgs[] = $entry;
						}
					}
					$directory->close();
				}
				if (count($imgs))	{
					$bImages = true;
				}
			}
		}

		if (!$bImages)	{
			$imgs = $this->getModelObj()->getImageArray($imageRow, $imageField);
		}

		$specialConf = array();
		$tempImageConf = '';
		if (is_array($tableConf) &&
			is_array($tableConf['image.']))	{
			$tempImageConf = &$tableConf['image.'];
		}

		if (is_array($tempImageConf))	{
			foreach ($tagArray as $key => $value)	{
				$keyArray = t3lib_div::trimExplode (':', $key);
				$specialConfType = strtolower($keyArray[1]);
				$tagKey = $keyArray[0];

				if ($specialConfType &&
					(!is_array($specialConf[$tagKey]) || !isset($specialConf[$tagKey][$specialConfType]) ) &&
					is_array($tempImageConf[$specialConfType.'.'])) {

					// add the special configuration
					if (!is_array($specialConf[$tagKey]))	{
						$specialConf[$tagKey] = array();
					}
					$specialConf[$tagKey][$specialConfType] = &$tempImageConf[$specialConfType.'.'];
				}
			}
		}

		if (isset($dirname))	{
			$dirname .= '/';
		} else {
			$dirname = $this->getModelObj()->getDirname($imageRow);
		}

//		$markerArray['###'.$marker.'_IMAGE_PATH###'] = $dirname;
		$theImgCode = $this->getCodeMarkerArray($functablename, 'PRODUCT_IMAGE', $theCode, $imageRow, $imgs, $dirname, $imageNum, $imageRenderObj, $linkWrap, $markerArray, $theImgDAM, $specialConf);

		reset ($theImgCode);
		$actImgCode = current($theImgCode);
		$markerArray['###'.$marker.'_IMAGE###'] = $actImgCode ? $actImgCode : ''; // for compatibility only

		$c = 1;
		$countArray = array();
		foreach($theImgCode as $k1 => $val) {
			$bIsSpecial = true;
			if (strstr($k1, ':') === FALSE)	{
				$bIsSpecial = false;
			}
			$key = $marker.'_IMAGE' . intval($c);
			if (isset($tagArray[$key]))	{
				$markerArray['###'.$key.'###'] = $val;
			}
			if (!$bIsSpecial)	{
				$countArray[$k1] = $c;
			}

			if ($bIsSpecial)	{
				$keyArray = t3lib_div::trimExplode(':', $k1);
				$count = $countArray[$keyArray[0]];
				$key = $marker.'_IMAGE' . intval($count);
				if (isset($count) && is_array($specialConf[$key]))	{
					foreach ($specialConf[$key] as $special => $sconf)	{
						$combkey = $key.':'.strtoupper($special);
						if (isset($tagArray[$combkey]))	{
							$markerArray['###'.$combkey.'###'] = $val;
						}
					}
				}
			}

			if (is_array($theImgDAM[$k1]))	{
				foreach ($theImgDAM[$k1] as $field => $val2)	{
					$key1 = '###'.$key.'_'.strtoupper($field).'###';
					if (isset($tagArray[$key1]))	{
						$markerArray[$key1] = $val2;
					}
				}
			}
			if (!$bIsSpecial)	{
				$c++;
			}
		}

		$bImageMarker = false;
		if (is_array($tableConf) &&
			is_array($tableConf['imageMarker.']) &&
			$tableConf['imageMarker.']['type'] == 'imagename' )	{
			$bImageMarker = true;
		}

		if ($bImageMarker)	{
			foreach ($theImgCode as $imageName => $imgValue)	{
				$nameArray = t3lib_div::trimExplode(':', $imageName);
				$suffix = ($nameArray[1] ? ':'.$nameArray[1] : '');
				$tagkey = $this->getMarkerkey($imageMarkerArray, 'PRODUCT_IMAGE', $imageName) . strtoupper($suffix);
				if (isset($tagArray[$tagkey]))	{
					$markerArray['###'.$tagkey.'###'] = $imgValue;
				}
				if (is_array($theImgDAM[$imageName]))	{
					foreach ($theImgDAM[$imageName] as $field => $val2)	{
						$key1 = $tagkey.'_'.strtoupper($field);
						if (isset($tagArray[$key1]))	{
							$markerArray['###'.$key1.'###'] = $val2;
						}
					}
				}
			}
		}

		// empty all image fields with no available image
		foreach ($tagArray as $value => $k1)	{
			$keyMarker = '###'.$value.'###';
			if (strstr($value, '_IMAGE') && !$markerArray[$keyMarker])	{
				$markerArray[$keyMarker] = '';
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$functablename: ...
	 * @param	[type]		$fieldname: ...
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
	 */
	function getItemMarkerArray ($functablename, $fieldname, &$row, $markerKey, &$markerArray, $tagArray, $theCode, $id, &$bSkip, $bHtml=TRUE, $charset='', $prefix='', $imageRenderObj='image')	{

		parent::getItemMarkerArray($functablename, $fieldname, $row, $markerKey, $markerArray, $tagArray, $theCode, $id, $bSkip, $bHtml, $charset, $prefix, $imageRenderObj);
	}

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_image_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_image_view.php']);
}


?>