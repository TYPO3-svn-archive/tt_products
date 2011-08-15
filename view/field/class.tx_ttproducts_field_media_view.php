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
 * functions for digital medias view
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_field_media_view extends tx_ttproducts_field_base_view {


	function getImageCode ($imageConf, $theCode) {
		$imageCode = $this->cObj->IMAGE($imageConf);

		if ($theCode == 'EMAIL' && $GLOBALS['TSFE']->absRefPrefix == '') {
			$absRefPrefix = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
			$fixImgCode = str_replace('index.php', $absRefPrefix . 'index.php', $imageCode);
			$fixImgCode = str_replace('src="', 'src="' . $absRefPrefix, $fixImgCode);
			$fixImgCode = str_replace('"uploads/', '"' . $absRefPrefix . 'uploads/', $fixImgCode);
			$imageCode = $fixImgCode;
		}
		return $imageCode;
	}


	/**
	 * replaces a text string with its markers
	 * used for JavaScript functions
	 *
	 * @param	[type]		$$markerArray: ...
	 * @param	[type]		$imageConf: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 * @access private
	 */
	function replaceMarkerArray (&$markerArray, &$imageConf, &$row)	{
		if ($imageConf['params'])	{
			$text = $imageConf['params'];
			if (!count($markerArray))	{
				$this->getExtItemMarkerArray($markerArray, $imageConf, $row);
			}
			$text = $this->cObj->substituteMarkerArray($text, $markerArray);
			$imageConf['params'] = $text;
		}
	}

	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * 				for the tt_producst record, $row
	 *
	 * @param	[type]		$$markerArray: ...
	 * @param	[type]		$imageConf: ...
	 * @param	[type]		$row: ...
	 * @return	array		Returns a markerArray ready for substitution with information
	 * @access private
	 */
	function getExtItemMarkerArray (&$markerArray, $imageConf, &$row)	{
		$markerArray['###IMAGE_FILE###'] = $imageConf['file'];

		foreach ($row as $field => $val)	{
			$key = '###IMAGE_'.strtoupper($field).'###';
			$markerArray[$key] = $val;
		}
	}


	/* returns the key for the tag array and marker array without leading and ending '###' */
	function getMarkerkey(&$imageMarkerArray, $markerKey, $imageName, $c = 1, $suffix='')	{
		$keyArray = array();
		$keyArray[] = $markerKey;
		if ($suffix)	{
			$keyArray[] = $suffix;
		}
		if (is_array($imageMarkerArray))	{
			$imageNameArray = t3lib_div::trimExplode('_', $imageName);
			$partsArray = t3lib_div::trimExplode(',', $imageMarkerArray['parts']);
			foreach ($partsArray as $k2 => $part)	{
				$keyArray[] = $imageNameArray[$part-1];
			}
		}
		$tmp = implode('_', $keyArray);
		$tmpArray = t3lib_div::trimExplode('.',$tmp);
		reset($tmpArray);
		$key = current($tmpArray);

		if (!is_array($imageMarkerArray))	{
			$key .= $c;
		}
		return $key;
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$functablename: ...
	 * @param	[type]		$markerKey: ...
	 * @param	[type]		$theCode: ...
	 * @param	[type]		$imageRow: ...
	 * @param	[type]		$imageArray: ...
	 * @param	[type]		$dirname: ...
	 * @param	[type]		$mediaNum: ...
	 * @param	[type]		$imageRenderObj: ...
	 * @param	[type]		$linkWrap: ...
	 * @param	[type]		$markerArray: ...
	 * @param	[type]		$theImgDAM: ...
	 * @param	[type]		$specialConf: ...
	 * @return	[type]		...
	 */
	function &getCodeMarkerArray($functablename, $markerKey, $theCode, &$imageRow, &$imageArray, $dirname, $mediaNum=0, $imageRenderObj, $linkWrap, &$markerArray, &$theImgDAM, &$specialConf)	{

		$imgCodeArray = array();
		$markerArray['###'.$markerKey.'_PATH###'] = $dirname;

		if (count($imageArray))	{
			$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
			$tableConf = $cnf->getTableConf($functablename, $theCode);

			if (is_array($tableConf))	{
				$imageMarkerArray = $tableConf['imageMarker.'];
			}
			$imageConfStart = $this->conf[$imageRenderObj.'.'];
			if ($linkWrap && $imageConfStart['imageLinkWrap'])	{
				$imageConfStart['imageLinkWrap'] = 0;
				unset($imageConfStart['imageLinkWrap.']);
				$imageConfStart['wrap'] = $linkWrap;
			}
			if ($linkWrap === FALSE)	{
				$imageConfStart['imageLinkWrap'] = 0;
			}

			// first loop to get the general markers used also for replacement inside of JavaScript in the setup
			foreach($imageArray as $c => $val)	{
				if ($c == $mediaNum)	{
					break;
				}
				if (!$this->conf['separateImage']) {
					$key = 0;  // show all images together as one image
				} else {
					$key = ($val ? $val : $c);
				}
				$tagkey = '';

				if ($val)	{
					$tagkey = $this->getMarkerkey($imageMarkerArray, $markerKey, $key, $c + 1);
					$filetagkey = $this->getMarkerkey($imageMarkerArray, $markerKey, $key, $c + 1, 'FILE');
					$markerArray['###'.$filetagkey.'###'] = $val;
				}
			}

			foreach($imageArray as $c => $val)	{
//				$confMarkerArray = array();

				$imageConf = $imageConfStart;
				if ($c == $mediaNum)	{
					break;
				}
				$bUseImage = false;
				$meta = false;
				if ($val)	{
					$imageConf['file'] = $dirname.$val;
					$bUseImage = true;
				}
				if (t3lib_extMgm::isLoaded('dam') && $bUseImage && $bImages) {
					$damObj = &t3lib_div::getUserObj('tx_dam');
					if(method_exists($damObj,'meta_getDataForFile')) {
						$fieldList = 'uid,pid,tstamp,crdate,active,media_type,title,category,index_type,file_mime_type,file_mime_subtype,
							file_type,file_type_version,file_name,file_path,file_size,file_mtime,file_inode,file_ctime,file_hash,file_status,
							file_orig_location,file_orig_loc_desc,file_creator,file_dl_name,file_usage,meta,ident,creator,
							keywords,description,alt_text,caption,abstract,search_content,language,pages,publisher,copyright,
							instructions,date_cr,date_mod,loc_desc,loc_country,loc_city,hres,vres,hpixels,vpixels,color_space,
							width,height,height_unit';
						$meta = $damObj->meta_getDataForFile($imageConf['file'], $fieldList);
					}
				}

				if (!$this->conf['separateImage']) {
					$key = 0;  // show all images together as one image
				} else {
					$key = ($val ? $val : $c);

				}
				$tagkey = '';
				if ($val)	{
					$tagkey = $this->getMarkerkey($imageMarkerArray, $markerKey, $key, $c + 1);
				}
				$this->cObj->alternativeData = ($meta ? $meta : $imageRow);
				$imageConf['params'] = preg_replace('/\s+/',' ',$imageConf['params']);
				$this->replaceMarkerArray($markerArray, $imageConf, $this->cObj->alternativeData);
				$tmpImgCode = $this->getImageCode($imageConf, $theCode);
				if ($tmpImgCode != '')	{
					$imgCodeArray[$key] .= $tmpImgCode;
				}

				if ($meta)	{
					$theImgDAM[$key] = $meta;
				}

				if ($tagkey && is_array($specialConf[$tagkey]))	{
					foreach ($specialConf[$tagkey] as $specialConfType => $specialImageConf)	{
						$theImageConf = array_merge($imageConf, $specialImageConf);
						$this->cObj->alternativeData = ($meta ? $meta : $imageRow); // has to be redone here
						$this->replaceMarkerArray($markerArray, $theImageConf, $this->cObj->alternativeData);
						$tmpImgCode = $this->getImageCode($theImageConf, $theCode);
						$key1 = $key.':'.$specialConfType;
						$imgCodeArray[$key1] .= $tmpImgCode;
					}
				}
			}	// foreach
		} else if ($this->conf['noImageAvailable']!='') {	// if (count($imageArray))
			$imageConf = $this->conf[$imageRenderObj.'.'];

			$imageConf['file'] = $this->conf['noImageAvailable'];
			$tmpImgCode = $this->getImageCode($imageConf, $theCode);
			$imgCodeArray[0] = $tmpImgCode;
		}
		return $imgCodeArray;
	}


	private function getMediaMarkerArray ($functablename, $fieldname, &$row, $mediaNum, $markerKey, &$markerArray, $tagArray, $theCode, $id, &$bSkip, $bHtml=TRUE, $charset='', $prefix='', $imageRenderObj='image')	{

		$imageRow = $row;
		$bImages = false;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$tableConf = $cnf->getTableConf($functablename, $theCode);
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

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
					$res = $TYPO3_DB->exec_SELECTquery('*',$conftable,$where_clause,'',$foreignfield,1);
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

		$theImgCode = $this->getCodeMarkerArray(
			$functablename,
			$markerKey,
			$theCode,
			$imageRow,
			$imgs,
			$dirname,
			$mediaNum,
			$imageRenderObj,
			$linkWrap,
			$markerArray,
			$theImgDAM,
			$specialConf
		);
		$actImgCode = current($theImgCode);
		$markerArray['###'.$markerKey.'###'] = $actImgCode ? $actImgCode : ''; // for compatibility only
		$c = 1;
		$countArray = array();

		foreach($theImgCode as $k1 => $val) {

			$bIsSpecial = true;
			if (strstr($k1, ':') === FALSE)	{
				$bIsSpecial = false;
			}
			$key = $markerKey. intval($c);

			if (isset($tagArray[$key]))	{
				$markerArray['###'.$key.'###'] = $val;
			}
			if (!$bIsSpecial)	{
				$countArray[$k1] = $c;
			}

			if ($bIsSpecial)	{
				$keyArray = t3lib_div::trimExplode(':', $k1);
				$count = $countArray[$keyArray[0]];
				$key =  $markerKey . intval($count);

				if (isset($count) && is_array($specialConf[$key]) && isset($specialConf[$key][$keyArray[1]]) && is_array($specialConf[$key][$keyArray[1]]))	{
					$combkey = $key.':'.strtoupper($keyArray[1]);
					if (isset($tagArray[$combkey]))	{
						$markerArray['###'.$combkey.'###'] = $val;
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
				$tagkey = $this->getMarkerkey($imageMarkerArray, $markerKey, $imageName).strtoupper($suffix);
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
		$bSkip = TRUE;

		if ($bHtml) {

			$mediaMarkerKeyArray = array();
			$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
			$tableConf = $cnf->getTableConf($functablename, $theCode);

			foreach ($tagArray as $value => $k1)	{
				if (strpos($value, $markerKey) !== false)	{
					$keyMarker = '###'.$value.'###';
					$foundPos = strpos($value, $markerKey.'_ID');
					if ($foundPos !== FALSE)	{
						$c = substr ($value, strlen($markerKey.'_ID'));
						$markerArray[$keyMarker] = $id.'-'.$c;
					} else {
						$mediaMarkerKeyArray[] = $keyMarker;
					}

					// empty all image fields with no available image
					if (!isset($markerArray[$keyMarker]))	{
						$markerArray[$keyMarker] = '';
					}
				}
			}

	// plugin.tt_products.conf.tt_products.ALL.limitImage = 10

			if (count ($mediaMarkerKeyArray))	{
				$mediaNum = $tableConf['limitImage'];
				if (!$mediaNum)	{
					$codeTypeArray = array(	// Todo: make this configurable
						'list' => array('real' => array('SEARCH', 'MEMO'), 'part' => array('LIST', 'MENU'), 'num' => $this->conf['limitImage']),
						'basket' => array('real' => array('OVERVIEW', 'BASKET', 'FINALIZE', 'INFO', 'PAYMENT', 'EMAIL'),
							'part' => array() , 'num' => 1),
						'single' => array('real' => array(), 'part' => array('SINGLE'), 'num' => $this->conf['limitImageSingle'])
					);
					foreach ($codeTypeArray as $type => $codeArray)	{
						$realArray = $codeArray['real'];
						if (count ($realArray))	{
							if (in_array($theCode, $realArray))	{
								$mediaNum = $codeArray['num'];
								break;
							}
						}
						$partArray = $codeArray['part'];
						if (count ($partArray))	{
							foreach ($partArray as $k => $part)	{
								if (strpos($theCode, $part) !== FALSE)	{
									$mediaNum = $codeArray['num'];
									break;
								}
							}
						}
					}
				}

				if ($mediaNum)	{
					$this->getMediaMarkerArray(
						$functablename,
						$fieldname,
						$row,
						$mediaNum,
						$markerKey,
						$markerArray,
						$tagArray,
						$theCode,
						$id,
						$bSkip,
						$bHtml,
						$charset,
						$prefix,
						$imageRenderObj
					);
				}
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_media_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_media_view.php']);
}


?>
