<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2006 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * configuration
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


global $TYPO3_CONF_VARS;


class tx_ttproducts_config {
	var $conf;
	var $config;

	/**
	 * Getting the configurations
	 */
	function init (&$conf, &$config) {
		$this->conf = &$conf;
		$this->config = &$config;
	} // init


	function &getTableDesc ($tablename)	{

		$tableDesc = array();
		if (is_array($this->conf['table.']) &&
			is_array($this->conf['table.'][$tablename.'.'])
			)	{
			$tableDesc = $this->conf['table.'][$tablename.'.'];
		}

		return $tableDesc;
	}


	public function &getSpecialConf ($type, $tablename='', $theCode='')	{
		$specialConf = array();

		if (is_array($this->conf[$type.'.']))	{

			if ($tablename != '' && is_array($this->conf[$type.'.'][$tablename.'.']))	{
				if (is_array($this->conf[$type.'.'][$tablename.'.']['ALL.']))	{
					$specialConf = $this->conf[$type.'.'][$tablename.'.']['ALL.'];
				}
				if ($theCode &&
					is_array($this->conf[$type.'.'][$tablename.'.'][$theCode.'.']))	{
					$tempConf = $this->conf[$type.'.'][$tablename.'.'][$theCode.'.'];
					$specialConf = array_merge($specialConf, $tempConf);
				}
				if ($specialConf['orderBy'] == '{$plugin.'.TT_PRODUCTS_EXTkey.'.orderBy}')	{
					$specialConf['orderBy'] = '';
				}
			} else {
				if (is_array($this->conf[$type.'.']['ALL.']))	{
					$specialConf = $this->conf[$type.'.']['ALL.'];
				}
				if ($theCode &&
					is_array($this->conf[$type.'.'][$theCode.'.']))	{
					$tempConf = $this->conf[$type.'.'][$theCode.'.'];
					$specialConf = t3lib_div::array_merge_recursive_overrule($specialConf, $tempConf);
				}
			}
		}
		return $specialConf;
	}


	function &getTableConf ($tablename, $theCode='')	{
		$tableConf = $this->getSpecialConf('conf', $tablename, $theCode);
		return $tableConf;
	}


	function &getCSSConf ($tablename, $theCode='')	{
		$cssConf = $this->getSpecialConf('CSS', $tablename, $theCode);

		return $cssConf;
	}


	function &getViewControlConf ($theCode)	{
		$viewConf = $this->getSpecialConf('control', '', $theCode);

		return $viewConf;
	}


	function getBasketConf ($feature)	{
		$rc = array();
		if (is_array($this->conf['basket.']))	{
			$rc = $this->conf['basket.'][$feature.'.'];
		}
		return $rc;
	}


	function bUseLanguageTable ($tableConf) 	{
		global $TSFE;

		$rc = false;
		$sys_language_uid = $TSFE->config['config']['sys_language_uid'];
		if (is_numeric($sys_language_uid))	{

			if ((is_array($tableConf['language.']) && $tableConf['language.']['type'] == 'table' && $sys_language_uid > 0))	{
				$rc = true;
			}
		}

		return $rc;
	}


	function getTranslationFields($tableConf)	{
		$fieldArray = array();
		if (is_array($tableConf['language.']) && $tableConf['language.']['type'] == 'field')	{
			$langConf = $tableConf['language.']['field.'];
			if (is_array($langConf))	{
				foreach ($langConf as $field => $langfield)	{
					$fieldArray[$field] = $langfield;
				}
			}
		}
		return $fieldArray;
	}


	function getImageFields($tableConf)	{
		$retArray = array();

		$generateArray = array('generateImage', 'generatePath');
		foreach ($generateArray as $k => $generate)	{
			if (is_array($tableConf) && is_array($tableConf[$generate.'.'])) {
				$genPartArray = $tableConf[$generate.'.'];
				if ($genPartArray['type'] == 'tablefields')	{
					$fieldArray = $genPartArray['field.'];
					if (is_array($fieldArray))	{
						foreach ($fieldArray as $field => $count)	{
							$retArray[] = $field;
						}
					}
				}
			}
		}
		return $retArray;
	}


	function getAJAXConf()	{
		$rc = array();
		if (isset($this->conf['ajax.']) && is_array($this->conf['ajax.']['conf.']))	{
			$rc = $this->conf['ajax.']['conf.'];
		}
		return $rc;
	}


	function getTemplateFile($theCode) {
		$rc = '';
		if (is_array($this->conf['templateFile.']) && ($this->conf['templateFile.'][$theCode]))	{
			$rc = $this->conf['templateFile.'][$theCode];
		} else {
			$rc = $this->conf['templateFile'];
		}

		return $rc;
	}


	function mergeAJAX($ajaxconf)	{
		global $TYPO3_DB;

//		if (is_array($ajaxconf) && isset($this->conf['ajax.']) && is_array($this->conf['ajax.']['conf.']))	{
//			foreach ($ajaxconf as $k => $v){
//				$newVal = $this->conf['ajax.']['conf.'][$k];
//				if (isset($newVal))	{
//					if (is_array($newVal))	{
//						if ($k == 'TSFE.')	{
//							$TSFE->config['config'] = array_merge ($TSFE->config['config'], $newVal);
//						} else {
//							$this->conf[$k] = array_merge($this->conf[$k], $newVal);
//						}
//					} else {
//						$this->conf[$k] = $newVal;
//					}
//				}
//			}
//		}
	}


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_config.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_config.php']);
}


?>
