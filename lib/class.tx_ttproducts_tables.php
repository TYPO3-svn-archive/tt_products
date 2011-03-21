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
 * table class for creation of database table classes and table view classes
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_table_base.php');
require_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_table_base_view.php');


class tx_ttproducts_tables	{
	var $tableArray = array();
	protected $tableClassArray = array(
		'address' => 'tx_ttproducts_address',
		'fe_users' => 'tx_ttproducts_orderaddress',
		'pages' => 'tx_ttproducts_page',
		'static_banks_de' => 'tx_ttproducts_bank_de',
		'static_countries' => 'tx_ttproducts_country',
		'sys_products_orders' => 'tx_ttproducts_order',
		'sys_products_accounts' => 'tx_ttproducts_account',
		'sys_products_cards' => 'tx_ttproducts_card',
		'address' => 'tx_ttproducts_address',
		'tt_content' => 'tx_ttproducts_content',
		'tt_products' => 'tx_ttproducts_product',
		'tt_products_articles' => 'tx_ttproducts_article',
		'tt_products_cat' => 'tx_ttproducts_category',
		'tt_products_emails' => 'tx_ttproducts_email',
		'tt_products_texts' => 'tx_ttproducts_text',
		'voucher' => 'tx_ttproducts_voucher',
		'tx_dam' => 'tx_ttproducts_dam',
		'tx_dam_cat' => 'tx_ttproducts_damcategory'
	);
	var $needExtensionArray = array(
		'static_banks_de' => 'static_info_tables_banks_de',
		'static_countries' => 'static_info_tables',
		'tx_dam' => 'dam',
		'tx_dam_cat' => 'dam'
	);
	var $pibase;
	var $cnf;
	var $conf;


	function init (&$pibase)	{

		$this->pibase = &$pibase;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

		$this->cnf = &$cnf;
		$this->conf = &$cnf->conf;
	}	// init

	function gettableClass ($functablename, $bView=false)	{
		$rc = '';
		if ($functablename)	{
			$neededExtension = $this->needExtensionArray[$functablename];
			if (!$neededExtension || t3lib_extMgm::isLoaded($neededExtension))	{
				$rc = $this->tableClassArray[$functablename].($bView ? '_view': '');
			} else {
				$rc = 'skip';
			}
		}
		return $rc;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$functablename: ...
	 * @param	[type]		$bView: ...
	 * @return	[type]		...
	 */
	function &get ($functablename, $bView=false)	{
		$classNameArray = array();
		$tableObjArray = array();

		$classNameArray['model'] = $this->gettableClass($functablename, false);
		if ($bView)	{
			$classNameArray['view'] = $this->gettableClass($functablename, true);
		}

		if (!$classNameArray['model'] || $bView && !$classNameArray['view'])	{
			debug('Error in '.TT_PRODUCTS_EXTkey.'. No class found after calling function tx_ttproducts_tables::get with parameters "'.$functablename.'", '.$bView.'.');
			return 'ERROR';
		}

		foreach ($classNameArray as $k => $className)	{
			if ($className != 'skip')	{
				include_once (PATH_BE_ttproducts.$k.'/class.'.$className.'.php');
				$tableObj[$k] = &t3lib_div::getUserObj('&'.$className);	// fetch and store it as persistent object
			}
		}

		if (isset($tableObj['model']) && is_object($tableObj['model']))	{
			if ($tableObj['model']->needsInit())	{
				$tableObj['model']->init(
					$this->pibase->cObj,
					$functablename
				);
			}
		} else {
			debug ('Object for \''.$functablename.'\' has not been found.');
		}

		if (isset($tableObj['view']) && is_object($tableObj['model']))	{
			if ($tableObj['view']->needsInit())	{
				$tableObj['view']->init(
					$this->pibase,
					$tableObj['model']
				);
			}
		}

		return ($bView ? $tableObj['view'] : $tableObj['model']);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_tables.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_tables.php']);
}


?>
