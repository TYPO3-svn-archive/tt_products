<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2009 Franz Holzinger <franz@ttproducts.de>
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
 * functions for the static_banks_de table
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



class tx_ttproducts_bank_de extends tx_ttproducts_table_base {

	/**
	 * bank data elements
	 *
	 * @param	[type]		$$cObj: ...
	 * @param	[type]		$functablename: ...
	 * @return	[type]		...
	 */
	function init ($cObj, $functablename)  {
		global $TYPO3_DB,$TSFE,$TCA;

		parent::init($cObj, $functablename);
		$tableObj = $this->getTableObj();
		$tableObj->addDefaultFieldArray(array('sorting' => 'sorting'));
		$tableObj->setDefaultFieldArray(array('uid'=>'uid', 'pid'=>'pid', 'starttime'=>'starttime', 'endtime'=>'endtime'));

		$tablename = 'static_banks_de';
		$this->tableObj->setName($tablename);
		$this->tableObj->setTCAFieldArray($tablename, 'bank');
	} // init
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_bank_de.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_bank_de.php']);
}


?>