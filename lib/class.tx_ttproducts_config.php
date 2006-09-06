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



class tx_ttproducts_config {
	var $conf;
	var $config;

	/**
	 * Getting the configurations
	 */
	function init(&$conf, &$config) {
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


	function &getTableConf ($tablename, $theCode='')	{

		$tableConf = array();
		if (is_array($this->conf['conf.']) &&
			is_array($this->conf['conf.'][$tablename.'.'])
			)	{
			if (is_array($this->conf['conf.'][$tablename.'.']['ALL.']))	{
				$tableConf = $this->conf['conf.'][$tablename.'.']['ALL.'];
			}
			if ($theCode &&
				is_array($this->conf['conf.'][$tablename.'.'][$theCode.'.']))	{
				$tempConf = $this->conf['conf.'][$tablename.'.'][$theCode.'.'];
				$tableConf = array_merge($tableConf, $tempConf);
			}
			if ($tableConf['orderBy'] == '{$plugin.tt_products.orderBy}')	{
				$tableConf['orderBy'] = '';
			}
		}
		
		return $tableConf;
	}


	function bUseLanguageTable ($tableConf) 	{
		global $TSFE;

		$rc = false;
		if ($TSFE->config['config']['sys_language_uid'])	{

			if ((!$tableConf['language.'] || !$tableConf['language.']['type']))	{
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
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_config.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_config.php']);
}


?>
