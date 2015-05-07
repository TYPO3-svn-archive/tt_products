<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2008 Franz Holzinger <contact@fholzinger.com>
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
 * class for control initialization
 *
 * $Id$
 *
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_config.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_tables.php');

require_once (PATH_BE_ttproducts.'control/class.tx_ttproducts_control_product.php');
require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_language.php');


class tx_ttproducts_control_creator {

	public function init (&$conf, &$config, &$pObj, $cObj)  {

		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$cnf->init(
			$conf,
			$config
		);

		$langObj = &t3lib_div::getUserObj('&tx_ttproducts_language');
		if (is_object($pObj))	{
			$pLangObj = &$pObj;
		} else {
			$pLangObj = &$this;
		}
		$langObj->init($pLangObj, $cObj, $conf, 'pi1/class.tx_ttproducts_pi1.php');

		tx_div2007_alpha5::loadLL_fh002($langObj, 'EXT:' . TT_PRODUCTS_EXTkey . '/locallang_db.xml');
		tx_div2007_alpha5::loadLL_fh002($langObj, 'EXT:' . TT_PRODUCTS_EXTkey . '/pi_search/locallang_db.xml');
		tx_div2007_alpha5::loadLL_fh002($langObj, 'EXT:' . TT_PRODUCTS_EXTkey . '/pi1/locallang.xml');

		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$tablesObj->init($langObj);
			// Call all init hooks
		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['init']) &&
			is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['init'])
		) {
			$tableClassArray = $tablesObj->getTableClassArray();

			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['init'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($langObj, $tableClassArray);
				}
			}
			$tablesObj->setTableClassArray($tableClassArray);
		}
	}

	public function destruct () {
		tx_ttproducts_control_basket::destruct();
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_creator.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_creator.php']);
}


?>
