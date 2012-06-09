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
 * base class for all database table classes
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

require_once (PATH_BE_ttproducts.'view/field/class.tx_ttproducts_field_base_view.php');


abstract class tx_ttproducts_table_base_view	{
	private $bHasBeenInitialised = false;
	public $cObj;
	public $conf;
	public $config;
	public $piVar;
	public $modelObj;
	public $langObj;
	public $marker;		// must be overridden

	public function init (&$langObj, &$modelObj)	{
		$this->langObj = &$langObj;
		$this->modelObj = &$modelObj;
		$this->cObj = &$langObj->cObj;
		$this->conf = &$modelObj->conf;
		$this->config = &$modelObj->config;

		if ($this->marker)	{
			$this->bHasBeenInitialised = true;
		} else {
			return false;
		}
	}

	public function needsInit ()	{
		return !$this->bHasBeenInitialised;
	}

	public function &getModelObj ()	{
		return $this->modelObj;
	}

	public function &getFieldObj ($field)	{
		$className = $this->getfieldClass($field);
		return $this->getObj($className);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getPivar ()	{
		return $this->piVar;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$piVar: ...
	 * @return	[type]		...
	 */
	function setPivar ($piVar)	{
		$this->piVar = $piVar;
	}

	public function getMarker ()	{
		return $this->marker;
	}

	public function &getObj ($className)	{
		$classNameView = $className.'_view';

		include_once (PATH_BE_ttproducts.'view/field/class.'.$classNameView.'.php');
		$fieldViewObj = &t3lib_div::getUserObj('&'.$classNameView);	// fetch and store it as persistent object
		if ($fieldViewObj->needsInit())	{
			include_once (PATH_BE_ttproducts.'model/field/class.'.$className.'.php');
			$fieldObj = &t3lib_div::getUserObj('&'.$className);	// fetch and store it as persistent object
			if ($fieldObj->needsInit())	{
				$fieldObj->init($this->cObj);
			}
			$fieldViewObj->init($this->langObj, $fieldObj);
		}

		return $fieldViewObj;
	}

	public function getfieldClass ($fieldname)	{
		$rc = $this->getModelObj()->getfieldClass($fieldname);
		return $rc;
	}

	public function &getItemSubpartArrays (&$templateCode, &$row, &$subpartArray, &$wrappedSubpartArray, &$tagArray, $theCode='', $id='1')	{
		global $TCA;

		if (is_array($row))	{
			$modelObj = &$this->getModelObj();
			$tableConf = $modelObj->getTableConf($theCode);

			foreach ($row as $field => $value)	{
				$className = $this->getfieldClass($field);
				if ($className)	{
					$fieldViewObj = $this->getObj($className);
					if (method_exists ($fieldViewObj, 'getItemSubpartArrays'))	{
						$fieldViewObj->getItemSubpartArrays(
							$templateCode,
							$this->marker,
							$functablename,
							$row,
							$field,
							$tableConf,
							$subpartArray,
							$wrappedSubpartArray,
							$tagArray,
							$theCode,
							$id
						);
					}
				}
			}
		}
	}

	public function getItemMarkerArray (
		&$row,
		&$markerArray,
		&$variantFieldArray,
		&$variantMarkerArray,
		&$tagArray,
		$theCode,
		$bHtml=TRUE,
		$charset='',
		$prefix='',
		$imageRenderObj='image'
	)	{
		global $TSFE;

		$rowMarkerArray = array();

		if (is_array($row) && $row['uid'])	{
			$functablename = $this->getModelObj()->getFuncTablename();
			$marker = $prefix.$this->marker;
			$extTableName = str_replace('_','-',$functablename);
			$extId = $extTableName.'-'.str_replace('_','-',strtolower($theCode));
			$id = $extId.'-'.$row['uid'];
			$rowMarkerArray['###'.$marker.'_ID###'] = $id;
			$rowMarkerArray['###'.$marker.'_NAME###'] = $extName.'-'.$row['uid'];
			$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
			$tableconf = $cnf->getTableConf($functablename,$theCode);
			$tabledesc = $cnf->getTableDesc($functablename);

			foreach ($row as $field => $value)	{
				$viewField = $field;
				$bSkip = FALSE;
				$theMarkerArray = &$rowMarkerArray;
				$fieldId = $id.'-'.$viewField;
				$markerKey = $marker.'_'.strtoupper($viewField);

				if (isset($tagArray[$markerKey]))	{
					$rowMarkerArray['###'.$markerKey.'_ID###'] = $fieldId;
				}

				if (is_array($variantFieldArray) && is_array($variantMarkerArray) && in_array($field, $variantFieldArray))	{
					$className = 'tx_ttproducts_field_text';
					$theMarkerArray = &$variantMarkerArray;
				} else {
					$className = $this->getfieldClass($field);
				}

				if ($className)	{
					$fieldViewObj = $this->getObj($className);
					$modifiedValue =
						$fieldViewObj->getItemMarkerArray	(
							$functablename,
							$field,
							$row,
							$markerKey,
							$theMarkerArray,
							$tagArray,
							$theCode,
							$fieldId,
							$bSkip,
							$bHtml,
							$charset,
							$prefix,
							$imageRenderObj
						);

					if (isset($modifiedValue))	{
						$value = $modifiedValue;
					}
				} else {
					switch ($field)	{
						case 'ext':
							$bSkip = true;
							break;
						default:
							// nothing
							break;
					}
				}

				if (!$bSkip)	{
					$tableName = $this->conf['table.'][$functablename];
					$fieldConf = $TCA[$tableName]['columns'][$field];
					if (is_array($fieldConf))	{
						if ($fieldConf['config']['eval'] == 'date')	{
							$value = $this->cObj->stdWrap($value,$this->conf['usebyDate_stdWrap.']);
						}
					}
					$theMarkerArray['###'.$markerKey.'###'] = $value;
				}
			}
		} else {
			$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
			$tablename = $this->getModelObj()->getTablename();
			$tmpMarkerArray = array();
			$tmpMarkerArray[] = $marker;

			if (isset($tagArray) && is_array($tagArray))	{
				foreach ($tagArray as $theTag => $v)	{
					foreach ($tmpMarkerArray as $theMarker)	{
						if (strpos($theTag,$theMarker) === 0)	{
							$rowMarkerArray['###' . $theTag . '###'] = '';
						}
					}
					if (!isset($rowMarkerArray['###' . $theTag . '###']) && strpos($theTag,$markerKey) === 0)	{
// Todo
					}
				}
			}
		}
		$markerArray = array_merge($markerArray, $rowMarkerArray);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_table_base_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_table_base_view.php']);
}


?>