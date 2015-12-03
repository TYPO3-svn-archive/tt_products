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

class tx_ttproducts_tables	{
	protected $tableClassArray = array(
		'address' => 'tx_ttproducts_address',
		'fe_users' => 'tx_ttproducts_orderaddress',
		'pages' => 'tx_ttproducts_page',
		'static_banks_de' => 'tx_ttproducts_bank_de',
		'static_countries' => 'tx_ttproducts_country',
		'static_taxes' => 'tx_ttproducts_static_tax',
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
	protected $needExtensionArray = array(
		'static_banks_de' => 'static_info_tables_banks_de',
		'static_countries' => 'static_info_tables',
		'tx_dam' => 'dam',
		'tx_dam_cat' => 'dam'
	);
	public $langObj;
	public $cnf;
	public $conf;


	public function init ($langObj)	{

		$this->langObj = $langObj;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

		$this->cnf = &$cnf;
		$this->conf = &$cnf->conf;
	}	// init

	public function getTableClassArray ()	{
		return $this->tableClassArray;
	}

	public function setTableClassArray ($tableClassArray)	{
		$this->tableClassArray = $tableClassArray;
	}

	public function getTableClass ($functablename, $bView = FALSE)	{

		$rc = '';
		if ($functablename)	{

			$neededExtension = $this->needExtensionArray[$functablename];
			if (!isset($neededExtension) || t3lib_extMgm::isLoaded($neededExtension))	{
				$rc = $this->tableClassArray[$functablename].($bView ? '_view': '');
			} else {
				$rc = 'skip';
			}
		}
		return $rc;
	}

	/* set the $bView to TRUE if you want to get the view class */
	public function &get ($functablename, $bView=FALSE)	{
		$classNameArray = array();
		$tableObjArray = array();

		$classNameArray['model'] = $this->getTableClass($functablename, FALSE);
		if ($bView)	{
			$classNameArray['view'] = $this->getTableClass($functablename, TRUE);
		}

		if (!$classNameArray['model'] || $bView && !$classNameArray['view'])	{
			debug('Error in '.TT_PRODUCTS_EXT.'. No class found after calling function tx_ttproducts_tables::get with parameters "'.$functablename.'", '.$bView.'.','internal error', __LINE__, __FILE__);
			return 'ERROR';
		}

		foreach ($classNameArray as $k => $className)	{
			if ($className != 'skip')	{
				// include_once (PATH_BE_ttproducts.$k.'/class.'.$className.'.php');
				if (strpos($className, ':') === FALSE)	{
					$path = PATH_BE_ttproducts;
				} else {
					list($extKey, $className) = t3lib_div::trimExplode(':', $className, TRUE);

					if (!t3lib_extMgm::isLoaded($extKey))	{
						debug('Error in '.TT_PRODUCTS_EXT.'. No extension "' . $extKey . '" has been loaded to use class class.' . $className . '.','internal error',  __LINE__,  __FILE__);
						continue;
					}
					$path = t3lib_extMgm::extPath($extKey);
				}
				$classRef = 'class.'.$className;
				$classFile = $path . $k . '/' . $classRef . '.php';
				if (file_exists($classFile)) {
					$classRef = $classFile . ':&' . $className;
					$tableObj[$k] = t3lib_div::getUserObj($classRef);	// fetch and store it as persistent object
				} else {
					debug ($classFile, 'File not found: ' . $classFile . ' in file class.tx_ttproducts_tables.php');
				}
			}
		}

		if (isset($tableObj['model']) && is_object($tableObj['model']))	{
			if ($tableObj['model']->needsInit())	{
				$tableObj['model']->init(
					$this->langObj->cObj,
					$functablename
				);
			}
		} else {
			debug ('Object for \''.$functablename.'\' has not been found.','internal error in '.TT_PRODUCTS_EXT, __LINE__, __FILE__);
		}

		if (isset($tableObj['view']) && is_object($tableObj['view']) && isset($tableObj['model']) && is_object($tableObj['model']))	{
			if ($tableObj['view']->needsInit())	{
				$tableObj['view']->init(
					$this->langObj,
					$tableObj['model']
				);
			}
		}

		return ($bView ? $tableObj['view'] : $tableObj['model']);
	}


	public function &getMM ($functablename)	{

		include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_mm_table.php');
		$tableObj = t3lib_div::getUserObj('&tx_ttproducts_mm_table');

		if (isset($tableObj) && is_object($tableObj))	{
			if ($tableObj->needsInit() || $tableObj->getFuncTablename() != $functablename)	{
				$tableObj->init(
					$this->langObj->cObj,
					$functablename
				);
			}
		} else {
			debug ('Object for \''.$functablename.'\' has not been found.','internal error in '.TT_PRODUCTS_EXT, __LINE__, __FILE__);
		}
		return $tableObj;
	}


	/**
	 * Returns informations about the table and foreign table
	 * This is used by various tables.
	 *
	 * @param	string		name of the table
	 * @param	string		field of the table
	 *
	 * @return	array		infos about the table and foreign table:
					table         ... name of the table
					foreign_table ... name of the foreign table
					mmtable       ... name of the mm table
					foreign_field ... name of the field in the mm table which joins with
					                  the foreign table
	 * @access	public
	 *
	 */
	public function getForeignTableInfo ($functablename,$fieldname)	{
		global $TCA, $TYPO3_DB;

		$rc = array();
		if ($fieldname != '')	{
			$tableObj = $this->get($functablename,FALSE);
			$tablename = $tableObj->getTableName ($functablename);
			$rc = tx_div2007_alpha5::getForeignTableInfo_fh003 ($tablename, $fieldname);
		}
		return $rc;
	}


	public function prepareSQL ($foreignTableInfoArray, $tableAliasArray, $aliasPostfix, &$sqlArray)	{

		if ($foreignTableInfoArray['mmtable'] == '' && $foreignTableInfoArray['foreign_table'] != '')	{
			$fieldname = $foreignTableInfoArray['table_field'];

			$tablename = $foreignTableInfoArray['table'];
			if (isset($tableAliasArray[$tablename]))	{
				$tablealiasname = $tableAliasArray[$tablename];
			} else {
				$tablealiasname = $tablename;
			}

			$foreigntablename = $foreignTableInfoArray['foreign_table'];
			if (isset($tableAliasArray[$foreigntablename]))	{
				$foreigntablealiasname = $tableAliasArray[$foreigntablename];
			} else {
				$foreigntablealiasname = $foreigntablename;
			}

			$sqlArray['local'] = $tablename;
			$sqlArray['from'] = $tablename.' '.$tablealiasname.$aliasPostfix.' INNER JOIN '.$foreigntablename.' '.$foreigntablealiasname.$aliasPostfix.' ON '.$tablealiasname.$aliasPostfix.'.'.$fieldname.'='.$foreigntablealiasname.$aliasPostfix.'.uid';
			$sqlArray['where'] = $tablealiasname.'.uid='.$tablealiasname.$aliasPostfix.'.uid';
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_tables.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_tables.php']);
}


?>