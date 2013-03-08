<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Franz Holzinger <franz@ttproducts.de>
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
 * functions for additional texts
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_text extends tx_ttproducts_table_base {
	var $dataArray; // array of read in categories
	var $marker = 'TEXT';
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $tt_products_texts; // element of class tx_table_db


	/**
	 * text elements
	 *
	 * @param	[type]		$$pibase: ...
	 * @param	[type]		$functablename: ...
	 * @return	[type]		...
	 */
	function init ($cObj, $functablename='tt_products_texts')  {
		global $TYPO3_DB,$TSFE,$TCA;

		parent::init($cObj, $functablename);
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$tableconf = $cnf->getTableConf($functablename,$theCode);
		$tableObj = $this->getTableObj();

		$tablename = $cnf->getTableName($functablename);
		$tableObj->setTCAFieldArray($tablename, 'texts');

		if ($cnf->bUseLanguageTable($tableconf))	{
			$tableObj->setLanguage ($this->config['LLkey']);
			$tableObj->setLangName($tableconf['language.']['table']);
			$tableObj->setTCAFieldArray($tableObj->langname, 'textslang', FALSE);
		}
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$tagArray: ...
	 * @param	[type]		$parentMarker: ...
	 * @return	[type]		...
	 */
	function &getTagMarkerArray (&$tagArray, $parentMarker)	{
		$rcArray = array();
		$search = $parentMarker.'_'.$this->marker.'_';
		$searchLen = strlen($search);
		foreach ($tagArray as $marker => $k)	{
			if (substr($marker, 0, $searchLen) == $search)	{
				$tmp = substr($marker, $searchLen, strlen($marker) - $searchLen);
				$rcArray[] = $tmp;
			}
		}
		return $rcArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$uid: ...
	 * @param	[type]		$tagMarkerArray: ...
	 * @param	[type]		$parenttable: ...
	 * @return	[type]		...
	 */
	function getChildUidArray ($uid, $tagMarkerArray, $parenttable='tt_products')	{
		global $TYPO3_DB;

		$rcArray = array();
		$tagWhere = '';
		if (count($tagMarkerArray))	{
			$tagMarkerArray = $TYPO3_DB->fullQuoteArray ($tagMarkerArray,$this->getTableObj()->name);
			$tags = implode(',',$tagMarkerArray);
			$tagWhere = ' AND marker IN ('.$tags.')';
		}
		$where_clause = 'parentid = '.intval($uid).' AND parenttable='.$TYPO3_DB->fullQuoteStr($parenttable, $this->getTableObj()->name).$tagWhere.$this->getTableObj()->enableFields();

		$rcArray = $this->get('', '', FALSE, $where_clause);

		return $rcArray;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_text.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_text.php']);
}


?>