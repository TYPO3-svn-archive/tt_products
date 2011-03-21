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
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_config {
	public $conf;
	public $config;
	private $bHasBeenInitialised = false;

	/**
	 * Getting the configurations
	 */
	public function init (&$conf, &$config) {
		$this->conf = &$conf;
		$this->config = &$config;
		$this->bHasBeenInitialised = true;
	} // init

	public function needsInit ()	{
		return !$this->bHasBeenInitialised;
	}

	public function &getConf ()	{
		return $this->conf;
	}

	public function &getConfig ()	{
		return $this->config;
	}

	public function &getTableDesc ($functablename, $type='')	{
		$tableDesc = array();
		if (is_array($this->conf['table.']) &&
			is_array($this->conf['table.'][$functablename.'.'])
			)	{
			$tableDesc = $this->conf['table.'][$functablename.'.'];
		}

		if ($type)	{
			$rc = $tableDesc[$type];
		} else {
			$rc = $tableDesc;
		}
		return $rc;
	}

	public function getTableName ($functablename)	{
		return $this->conf['table.'][$functablename];
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

	public function &getTableConf ($functablename, $theCode='')	{
		$tableConf = $this->getSpecialConf('conf', $functablename, $theCode);
		return $tableConf;
	}

	public function &getCSSConf ($functablename, $theCode='')	{
		$cssConf = $this->getSpecialConf('CSS', $functablename, $theCode);

		return $cssConf;
	}

	public function &getViewControlConf ($theCode)	{
		$viewConf = $this->getSpecialConf('control', '', $theCode);

		return $viewConf;
	}

	public function getBasketConf ($feature)	{
		$rc = array();
		if (is_array($this->conf['basket.']))	{
			$rc = $this->conf['basket.'][$feature.'.'];
		}
		return $rc;
	}

	public function bUseLanguageTable ($tableConf) 	{
		global $TSFE;

		$rc = FALSE;
		$sys_language_uid = $TSFE->config['config']['sys_language_uid'];
		if (is_numeric($sys_language_uid))	{

			if ((is_array($tableConf['language.']) && $tableConf['language.']['type'] == 'table' && $sys_language_uid > 0))	{
				$rc = TRUE;
			}
		}
		return $rc;
	}

	public function getTranslationFields ($tableConf)	{
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

	public function getImageFields ($tableConf)	{
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

	public function getAJAXConf ()	{
		$rc = array();
		if (isset($this->conf['ajax.']) && is_array($this->conf['ajax.']['conf.']))	{
			$rc = $this->conf['ajax.']['conf.'];
		}
		return $rc;
	}

	public function getTemplateFile ($theCode) {
		$rc = '';
		if (is_array($this->conf['templateFile.']) && ($this->conf['templateFile.'][$theCode]))	{
			$rc = $this->conf['templateFile.'][$theCode];
		} else {
			$rc = $this->conf['templateFile'];
		}

		return $rc;
	}

	public function mergeAJAX ($ajaxconf)	{
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


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_config.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_config.php']);
}


?>
