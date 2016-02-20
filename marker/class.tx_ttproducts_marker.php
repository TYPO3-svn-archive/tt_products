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
 * marker functions
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


// require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_language.php');


class tx_ttproducts_marker {
	public $cObj;
	public $conf;
	public $config;
	public $markerArray;
	public $globalMarkerArray;
	public $urlArray;
	private $langArray;
	private $errorCode = array();
	private $specialArray = array('eq', 'ne', 'lt', 'le', 'gt', 'ge', 'id');

	/**
	 * Initialized the marker object
	 * $basket is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param	string		$fieldname is the field in the table you want to create a JavaScript for
	 * @param	array		array urls which should be overridden with marker key as index
	 * @return	  void
	 */
	public function init ($cObj, $piVars)	{
		$this->cObj = $cObj;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->markerArray = array('CATEGORY', 'PRODUCT', 'ARTICLE');
		$langObj = t3lib_div::getUserObj('tx_ttproducts_language');
		$langObj->init($this, $this->cObj, $this->conf['marks.'], 'marker/class.tx_ttproducts_marker.php');

		$markerFile = $this->conf['markerFile'];
		$language = $langObj->getLanguage();
		$defaultMarkerFile = 'EXT:'.TT_PRODUCTS_EXT.'/marker/locallang.xml';
		tx_div2007_alpha5::loadLL_fh002($langObj, $defaultMarkerFile);

		if ($language == '' || $language == 'default' || $language == 'en')	{
			if ($markerFile)	{
				tx_div2007_alpha5::loadLL_fh002($langObj,$markerFile);
			}
		} else	{
			if (!$markerFile)	{
				if ($language == 'de')	{
					$markerFile = $language . '.locallang.xml';
				} else if (t3lib_extMgm::isLoaded(ADDONS_EXTkey))	{
					$markerFile = 'EXT:' . ADDONS_EXTkey . '/' . $language . '.locallang.xml';
				}
			} else if (substr($markerFile, 0, 4) == 'EXT:')	{	// extension
				list($extKey,$local) = explode('/', substr($markerFile, 4), 2);
				$filename='';
				if (strcmp($extKey, '') && !t3lib_extMgm::isLoaded($extKey) && strcmp($local, ''))	{
					$error_code = array();
					$error_code[0] = 'extension_missing';
					$error_code[1] = $extKey;
					$error_code[2] = $markerFile;
					$this->setErrorCode($error_code);
				}
			}
			tx_div2007_alpha5::loadLL_fh002($langObj, $markerFile);
		}
		$locallang = $langObj->getLocallang();
		$LLkey = $langObj->getLLkey();

		$this->setGlobalMarkerArray($piVars, $locallang, $LLkey);
		$error_code = $this->getErrorCode();
		return (count($error_code) == 0 ? TRUE : FALSE);
	}

	public function getErrorCode ()	{
		return $this->errorCode;
	}

	public function setErrorCode ($errorCode)	{
		$this->errorCode = $errorCode;
	}

	public function setLangArray (&$langArray)	{
		$this->langArray = $langArray;
	}

	public function &getLangArray ()	{
		return $this->langArray;
	}

	public function &getGlobalMarkerArray ()	{
		return $this->globalMarkerArray;
	}

	public function &replaceGlobalMarkers (&$content)	{
		$markerArray = &$this->getGlobalMarkerArray();
		$rc = &$this->cObj->substituteMarkerArrayCached($content,$markerArray);
		return $rc;
	}

	/**
	 * getting the global markers
	 */
	public function setGlobalMarkerArray ($piVars, $locallang, $LLkey)	{
		global $TSFE;

		$markerArray = array();

			// globally substituted markers, fonts and colors.
		$splitMark = md5(microtime());
		list($markerArray['###GW1B###' ], $markerArray['###GW1E###']) = explode($splitMark, $this->cObj->stdWrap($splitMark, $this->conf['wrap1.']));
		list($markerArray['###GW2B###'], $markerArray['###GW2E###']) = explode($splitMark, $this->cObj->stdWrap($splitMark, $this->conf['wrap2.']));
		list($markerArray['###GW3B###'], $markerArray['###GW3E###']) = explode($splitMark, $this->cObj->stdWrap($splitMark, $this->conf['wrap3.']));
		$markerArray['###GC1###'] = $this->cObj->stdWrap($this->conf['color1'], $this->conf['color1.']);
		$markerArray['###GC2###'] = $this->cObj->stdWrap($this->conf['color2'], $this->conf['color2.']);
		$markerArray['###GC3###'] = $this->cObj->stdWrap($this->conf['color3'], $this->conf['color3.']);
		$markerArray['###DOMAIN###'] = $this->conf['domain'];
		$markerArray['###PATH_FE_REL###'] = PATH_FE_ttproducts_rel;
		if (t3lib_extMgm::isLoaded(ADDONS_EXTkey)) {
			$markerArray['###PATH_FE_REL###'] = PATH_FE_addons_rel;
			$markerArray['###PATH_FE_ICONS###'] = PATH_FE_addons_icon_rel;
		}
		$pidMarkerArray = array('agb', 'basket', 'info', 'finalize', 'payment',
			'thanks', 'itemDisplay', 'listDisplay', 'revocation', 'search', 'storeRoot',
			'memo', 'tracking', 'billing', 'delivery'
		);
		foreach ($pidMarkerArray as $k => $function)	{
			$markerArray['###PID_' . strtoupper($function) . '###'] = intval($this->conf['PID' . $function]);
		}
		$markerArray['###SHOPADMIN_EMAIL###'] = $this->conf['orderEmail_from'];
		$lang =  t3lib_div::_GET('L');

		if ($lang!='')	{
			$markerArray['###LANGPARAM###'] = '&amp;L=' . $lang;
		} else {
			$markerArray['###LANGPARAM###'] = '';
		}
		$markerArray['###LANG###'] = $lang;
		$markerArray['###LANGUAGE###'] = $TSFE->config['config']['language'];
		$markerArray['###LOCALE_ALL###'] = $TSFE->config['config']['locale_all'];

		$backPID = $piVars['backPID'];
		$backPID = ($backPID ? $backPID : t3lib_div::_GP('backPID'));
		$backPID = ($backPID  ? $backPID : ($this->conf['PIDlistDisplay'] ? $this->conf['PIDlistDisplay'] : $TSFE->id));
		$markerArray['###BACK_PID###'] = $backPID;

			// Call all addURLMarkers hooks at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addGlobalMarkers'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addGlobalMarkers'] as $classRef) {
				$hookObj= t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addGlobalMarkers')) {
					$hookObj->addGlobalMarkers($markerArray);
				}
			}
		}
		if (isset($locallang[$LLkey]))	{
			$langArray = array_merge($locallang['default'], $locallang[$LLkey]);
		} else {
			$langArray = $locallang['default'];
		}
		if(isset($langArray) && is_array($langArray))	{
			foreach ($langArray as $key => $value)	{
				if (
					version_compare(TYPO3_version, '4.6.0', '>=') &&
					is_array($value)
				) {
					if ($value[0]['target']) {
						$value = $value[0]['target'];
					} else {
						$value = $value[0]['source'];
					}
				}

				$langArray[$key] = $value;
				$markerArray['###' . strtoupper($key) . '###'] = $value;
			}
		} else {
			$langArray = array();
		}

		if (is_array($this->conf['marks.']))	{
				// Substitute Marker Array from TypoScript Setup
			foreach ($this->conf['marks.'] as $key => $value)	{

				if (is_array($value))	{
					switch($key)	{
						case 'image.':
							foreach ($value as $k2 => $v2)	{
								$fileresource = $this->cObj->fileResource($v2);
								$markerArray['###IMAGE' . strtoupper($k2) . '###'] = $fileresource;
							}
						break;
					}
				} else {
					if(isset($this->conf['marks.'][$key . '.']) && is_array($this->conf['marks.'][$key . '.']))	{
						$out = $this->cObj->cObjGetSingle($this->conf['marks.'][$key], $this->conf['marks.'][$key . '.']);
					} else {
						$langArray[$key] = $value;
						$out = $value;
					}
					$markerArray['###'.strtoupper($key).'###'] = $out;
				}
			}
		}
		$this->globalMarkerArray = &$markerArray;
		$this->setLangArray($langArray);
	} // setGlobalMarkerArray

	public function reduceMarkerArray ($templateCode, $markerArray) {
		$result = array();

		$tagArray = $this->getAllMarkers($templateCode);

		foreach ($tagArray as $tag => $v) {
			$marker = '###' . $tag. '###';
			if (isset($markerArray[$marker])) {
				$result[$marker] = $markerArray[$marker];
			}
		}
		return $result;
	}

	public function &getAllMarkers (&$templateCode)	{
		$treffer = array();
		preg_match_all('/###([\w:]+)###/', $templateCode, $treffer);
		$tagArray = $treffer[1];
		$bFieldaddedArray = array();

		if (is_array($tagArray))	{
			$tagArray = array_flip($tagArray);
		}
		return $tagArray;
	}

	/**
	 * finds all the markers for a product
	 * This helps to reduce the data transfer from the database
	 *
	 * @access private
	 */
	public function &getMarkerFields (&$templateCode, &$tableFieldArray, &$requiredFieldArray, &$addCheckArray, $prefixParam, &$tagArray, &$parentArray)	{

		$retArray = (count($requiredFieldArray) ? $requiredFieldArray : array());
		// obligatory fields uid and pid

		$prefix = $prefixParam.'_';
		$prefixLen = strlen($prefix);
		// $tagArray = explode ('###', $templateCode);
// 		$treffer = array();
// 		preg_match_all('/###([\w:]+)###/', $templateCode, $treffer);
// 		$tagArray = $treffer[1];
// 		$bFieldaddedArray = array();

		$tagArray = $this->getAllMarkers($templateCode);

		if (is_array($tagArray))	{
// 			$tagArray = array_flip($tagArray);
			$retTagArray = $tagArray;
			foreach ($tagArray as $tag => $v1)	{
				$prefixFound = strstr($tag, $prefix);

				if ($prefixFound != '')	{
					$fieldTmp = substr($prefixFound, $prefixLen);
					$fieldTmp = strtolower($fieldTmp);

					$fieldPartArray = t3lib_div::trimExplode('_', $fieldTmp);
					$field = $fieldPartArray[0];
					$fieldPartArray = t3lib_div::trimExplode(':', $fieldTmp);
					$field = $fieldPartArray[0];

					if (strstr($field,'image'))	{	// IMAGE markers can contain following number
						$field = 'image';
					} else {
						$newFieldPartArray = array();
						foreach ($fieldPartArray as $k => $v)	{
							if (in_array($v, $this->specialArray))	{
								break;
							} else {
								$newFieldPartArray[] = $v;
							}
						}
						$field = implode('_', $newFieldPartArray);
					}

					if (!is_array($tableFieldArray[$field]))	{	// find similar field names with letters in other cases
						$upperField = strtoupper($field);
						foreach ($tableFieldArray as $k => $v)	{
							if (strtoupper($k) == $upperField)	{
								$field = $k;
								break;
							}
						}
					}
					$field = strtolower($field);

					if (is_array($tableFieldArray[$field]))	{
						$retArray[] = $field;
						$bFieldaddedArray[$field] = TRUE;
					}
					$parentFound = strpos($tag, 'PARENT');
					if ($parentFound !== FALSE)	{
						$parentEnd = strpos($tag, '_');
						$parentLen = strlen('PARENT');
						$temp = substr($tag, $parentLen, ($parentEnd - $parentFound) - $parentLen);
						$parentArray[] = $temp;
					}
				} else {
					// unset the tags of different tables

					foreach ($this->markerArray as $k => $marker)	{
						if ($marker != $prefixParam) 	{
							$bMarkerFound = strpos($tag, $marker);
							if ($bMarkerFound == 0 && $bMarkerFound !== FALSE)	{
								unset($retTagArray[$tag]);
							}
						}
					}
				}
			}
			$tagArray = $retTagArray;
		}
		$parentArray = array_unique($parentArray);
		sort($parentArray);

		if (is_array($addCheckArray))	{
			foreach ($addCheckArray as $marker => $field)	{
				if (!$bFieldaddedArray[$field] && isset($tableFieldArray[$field]))	{ 	// TODO: check also if the marker is in the $tagArray
					$retArray[] = $field;
				}
			}
		}
		if (is_array($retArray))	{
			$retArray = array_unique($retArray);
		}

		return $retArray;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/marker/class.tx_ttproducts_marker.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/marker/class.tx_ttproducts_marker.php']);
}

?>