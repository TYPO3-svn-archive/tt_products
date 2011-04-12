<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2011 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

global $TYPO3_CONF_VARS;


class tx_ttproducts_image {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $marker;
	var $parenttable;


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, &$cnf, &$tt_content, &$parenttable, $marker)  {
		global $TYPO3_DB,$TSFE,$TCA,$TYPO3_CONF_VARS;;

		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;
		$this->tt_content = &$tt_content;
		$this->parenttable = &$parenttable;
		$this->marker = $marker;

		if ($this->conf['noImageAvailable'] == '{$plugin.tt_products.file.noImageAvailable}')	{
			$this->conf['noImageAvailable'] = '';
		}

		// DAM support
		if (t3lib_extMgm::isLoaded('dam')) {
			include_once(t3lib_extMgm::extPath('dam').'lib/class.tx_dam.php');
		}
	} // init


	function getImageCode ($imageConf, $theCode) {
		$imageCode = $this->pibase->cObj->IMAGE($imageConf);

		if ($theCode == 'EMAIL' && $GLOBALS['TSFE']->absRefPrefix == '') {
			$absRefPrefix = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
			$fixImgCode = str_replace('index.php', $absRefPrefix . 'index.php', $imageCode);
			$fixImgCode = str_replace('src="', 'src="' . $absRefPrefix, $fixImgCode);
			$imageCode = $fixImgCode;
		}
		return $imageCode;
	}


	/* returns the key for the tag array and marker array without leading and ending '###' */
	function getMarkerkey(&$imageMarkerArray, $imageName, $c = 1, $suffix='')	{

		$keyArray = array();
		$keyArray[] = $this->marker;
		$keyArray[] = 'IMAGE'.($suffix ? '_'.$suffix : '');
		if (is_array($imageMarkerArray))	{
			$imageNameArray = t3lib_div::trimExplode('_', $imageName);
			$partsArray = t3lib_div::trimExplode(',', $imageMarkerArray['parts']);
			foreach ($partsArray as $k2 => $part)	{
				$keyArray[] = $imageNameArray[$part-1];
			}
		}
		$tmp = implode('_', $keyArray);
		$tmpArray = t3lib_div::trimExplode('.',$tmp);
		reset ($tmpArray);
		$key = current($tmpArray);

		if (!is_array($imageMarkerArray))	{
			$key .= $c;
		}

		return $key;
	}

	/**
	 * Template marker substitution
	 * Fills in the markerArray with external data for an image
	 * Only for markers inside of TypoScript texts
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array		Returns a markerArray ready for substitution with information
	 * 			 	for the tt_producst record, $row
	 * @access private
	 */
	function getExtItemMarkerArray (&$row, &$markerArray, $imageConf)	{
		$markerArray['###IMAGE_FILE###'] = $imageConf['file'];

		foreach ($row as $field => $val)	{
			$key = '###IMAGE_'.strtoupper($field).'###';
			$markerArray[$key] = $val;
		}
	}


	/**
	 * replaces a text string with its markers
	 * used for JavaScript functions
	 * Only for markers inside of TypoScript texts
	 *
	 * @param	string		text to replace the markers
	 * 				for the tt_producst record, $row
	 * @access private
	 */
	function replaceMarkerArray (&$markerArray, &$imageConf, &$row)	{
		if ($imageConf['params'])	{
			$text = $imageConf['params'];
			if (!count($markerArray))	{
				$this->getExtItemMarkerArray($row, $markerArray, $imageConf);
			}
			$text = $this->pibase->cObj->substituteMarkerArray($text, $markerArray);
			$imageConf['params'] = $text;
		}
	}


	function getSingleImageMarkerArray ($markerKey, &$markerArray, &$imageConf, $theCode)	{
		$tmpImgCode = $this->getImageCode($imageConf, $theCode);
		$markerArray['###'.$markerKey.'###'] = $tmpImgCode;
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	string		name of the marker prefix
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array		Returns a markerArray ready for substitution with information
	 * 			 	for the tt_producst record, $row
	 * @access private
	 */
	function getItemMarkerArray ($row, &$markerArray, $pid, $imageNum=0, $imageRenderObj='image', &$tagArray, $theCode, $id='', $prefix='', $linkWrap = '')	{
		global $TYPO3_DB;

		$imageRow = $row;
		$bImages = false;
		$tableConf = $this->cnf->getTableConf($this->parenttable->name, $theCode);

			// Get image
		$theImgCode = array();
		$theImgDAM = array();
		$specialImgCode = array();
		if (is_array($tableConf))	{
			$imageMarkerArray = $tableConf['imageMarker.'];
		}
		$imgs = array();

		if (is_array($tableConf['fetchImage.']) &&
			$tableConf['fetchImage.']['type'] == 'foreigntable'  &&
			$tableConf['fetchImage.']['table'] == 'tt_content') {
			$pageContent = $this->tt_content->getFromPid($pid);
			foreach ($pageContent as $pid => $contentRow) {
				if ($contentRow['image']) {
					$imgs[] = $contentRow['image'];
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
					$where_clause .= $this->pibase->cObj->enableFields($conftable);
					$res = $TYPO3_DB->exec_SELECTquery('*',$conftable,$where_clause,'',$foreignfield,1);
						// only first found row will be used
					$imageRow = $TYPO3_DB->sql_fetch_assoc($res);
				}
			}

			// $confParentTableConf = $this->getTableConf($conftable, $theCode);
			$conftable = ($conftable ? $conftable : $this->parenttable->name);
			$generateArray = array('generateImage', 'generatePath');
			$nameArray = array();
			$conftableConf = $this->cnf->getTableConf($conftable, $theCode);

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
									if ($generate == 'generateImage')
										$bImages = true;
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
				if (count($imgs))
					$bImages = true;
			}
		}

		if (!$bImages)	{
			$imgs = ($imageRow['image'] ? explode(',',$imageRow['image']) : array());
			$tmp = count($imgs);
			if (!$tmp && $imageRow['file_mime_type'] == 'image')	{
				$imgs = array($imageRow['file_name']);
			}
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
		} else if($imageRow['file_mime_type'] == 'image' && isset($imageRow['file_path']))	{
			$dirname = $imageRow['file_path'];
		} else {
			$dirname = 'uploads/pics/';
		}
		$markerArray['###'.$this->marker.'_IMAGE_PATH###'] = $dirname.'/';

		$imageConfStart = $this->conf[$imageRenderObj.'.'];
		if ($linkWrap && $imageConfStart['imageLinkWrap'])	{
			$imageConfStart['imageLinkWrap'] = 0;
			unset($imageConfStart['imageLinkWrap.']);
			$imageConfStart['wrap'] = $linkWrap;
		}
		if ($linkWrap === false)	{
			$imageConfStart['imageLinkWrap'] = 0;
		}

		foreach($imgs as $c => $val)	{
			$confMarkerArray = array();
			$imageConf = $imageConfStart;
			if ($c==$imageNum)	break;
			$bUseImage = false;
			$meta = false;
			if ($val)	{

### GERNOT start
				$dataArray = array(
					'marker' => 'row:image',
					'imagename' => $dirname.$val
				);
				array_walk_recursive($imageConf, array($this, 'replace_field_image'), $dataArray);
				$imageConf['file'] = ($imageConf['file'] != '' ? $imageConf['file'] : $dataArray['imagename']);
### GERNOT end
				$bUseImage = true;
			}
			if (t3lib_extMgm::isLoaded('dam') && $bUseImage && $bImages) {
				$damObj = &t3lib_div::getUserObj('tx_dam');
				if(method_exists($damObj,'meta_getDataForFile')) {
					$damTableConf = $this->cnf->getTableConf('tx_dam', $theCode);
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

			$this->pibase->cObj->alternativeData = ($meta ? $meta : $imageRow);
			$this->replaceMarkerArray($confMarkerArray, $imageConf, $this->pibase->cObj->alternativeData);
			$tmpImgCode = $this->getImageCode($imageConf, $theCode);
			$theImgCode[$key] .= $tmpImgCode;
			if ($meta)	{
				$theImgDAM[$key] = $meta;
			}
			$tagkey = '';
			if ($val)	{
				$tagkey = $this->getMarkerkey($imageMarkerArray, $key, $c + 1);
				$filetagkey = $this->getMarkerkey($imageMarkerArray, $key, $c + 1, 'FILE');
			}
			$markerArray['###'.$filetagkey.'###'] = $val;

			if (is_array($specialConf[$tagkey]))	{
				foreach ($specialConf[$tagkey] as $specialConfType => $specialImageConf)	{
	### GERNOT start
					$dataArray = array(
						'marker' => 'row:image',
						'imagename' => $dirname.$val
					);
					array_walk_recursive($specialImageConf, array($this, 'replace_field_image'), $dataArray);
	### GERNOT end
					$theImageConf = array_merge($imageConf, $specialImageConf);
					$this->pibase->cObj->alternativeData = ($meta ? $meta : $imageRow); // has to be redone here
					$this->replaceMarkerArray($confMarkerArray, $theImageConf, $this->pibase->cObj->alternativeData);
					$tmpImgCode = $this->getImageCode($theImageConf, $theCode);
					$key1 = $key.':'.$specialConfType;
					$theImgCode[$key1] .= $tmpImgCode;
				}
			}
		}

		if (!count($imgs))	{
			$imageConf = $this->conf[$imageRenderObj.'.'];
			$imageConf['file'] = $this->conf['noImageAvailable'];
			$tmpImgCode = $this->getImageCode($imageConf, $theCode);
			$theImgCode[0] = $tmpImgCode;
		}
		reset ($theImgCode);
		$actImgCode = current($theImgCode);
		$markerArray['###'.$this->marker.'_IMAGE###'] = $actImgCode ? $actImgCode : ''; // for compatibility only

		$c = 1;
		$countArray = array();

		while ((list($k1,$val)=each($theImgCode))) {
			$bIsSpecial = true;
			if (strstr($k1, ':') === FALSE)	{
				$bIsSpecial = false;
			}
			$key = $this->marker.'_IMAGE' . intval($c);

			if (isset($tagArray[$key]))	{
				$markerArray['###'.$key.'###'] = $val;
			}
			if (!$bIsSpecial)	{
				$countArray[$k1] = $c;
			}

			if ($bIsSpecial)	{
				$keyArray = t3lib_div::trimExplode(':', $k1);
				$count = $countArray[$keyArray[0]];
				$key = $this->marker.'_IMAGE' . intval($count);
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
				$tagkey = $this->getMarkerkey($imageMarkerArray, $imageName).strtoupper($suffix);
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


	### GERNOT start
	function replace_field_image(&$item, $key, $userdata) {
		if ($item == $userdata['marker']) {
			$item = $userdata['imagename'];
		}
	}
	### GERNOT end
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_image.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_image.php']);
}


?>
