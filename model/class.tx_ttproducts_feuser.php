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
 * functions for the frontend users
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_feuser {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;

	var $dataArray; // array of read in frontend users
	var $table;		 // object of the type tx_table_db
	var $fields = array();
	var $tableconf;

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, &$cnf, $tablename)  {
		global $TYPO3_DB,$TSFE,$TCA;

		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;

		$tablename = ($tablename ? $tablename : 'fe_users');
		$this->tableconf = $this->cnf->getTableConf('fe_users');
		
		$this->table = t3lib_div::makeInstance('tx_table_db');
		$this->table->setTCAFieldArray($tablename);
		$this->fields['payment'] = ($this->tableconf['payment'] ? $this->tableconf['payment'] : '');
		$requiredFields = 'uid,pid,email'.($this->fields['payment'] ? ','.$this->fields['payment'] : '');
		if (is_array($this->tableconf['ALL.']))	{
			$tmp = $this->tableconf['ALL.']['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}
		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$this->table->setRequiredFieldArray($requiredListArray);
	} // init


	function get ($uid) {
		global $TYPO3_DB;

		$rc = $this->dataArray[$uid];
		if (!$rc && $uid) {
			$where = '1=1 '.$this->table->enableFields();
			$res = $this->table->exec_SELECTquery('*',$where.' AND uid = '.intval($uid));
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$rc = $this->dataArray[$row['uid']] = $row;
		}
		return $rc;
	}


	function getFieldName ($field)	{
		$rc = $field;
		if (is_array($this->fields) && $this->fields[$field])	{
			$rc = $this->fields[$field];
		}

		return $rc;
	}


	function isUserInGroup($feuser, $group)	{
		$groups = explode(',', $feuser['usergroup']);
		foreach ($groups as $singlegroup)
			if ($singlegroup == $group)
				return true;
		return false;
	} // isUserInGroup


	function getWrappedSubpartArray(&$subpartArray, &$wrappedSubpartArray)	{
		global $TSFE;

		if ($TSFE->fe_user->user)	{
			$wrappedSubpartArray['###FE_GROUP_1_TEMPLATE###'] = array('','');
		} else {
			$subpartArray['###FE_GROUP_1_TEMPLATE###'] = '';
		}
		return;		
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	function getItemMarkerArray (&$row, &$markerArray, $bSelect, $type)	{
		global $TCA;
		global $TYPO3_CONF_VARS;

		if ($bSelect)	{
			include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_form_div.php');

			$typeSalutationText = tx_ttproducts_form_div::createSelect ($this->pibase, $TCA['sys_products_orders']['columns']['salutation']['config']['items'], 'recs['.$type.'info][salutation]');

		} else if (is_numeric($row['salutation'])) {
			$salutation = $TCA['sys_products_orders']['columns']['salutation']['config']['items'][$row['salutation']];
			$tmp = tx_fhlibrary_language::sL($salutation[0]);
			$typeSalutationText = $this->pibase->pi_getLL($tmp);
		} else {
			$typeSalutationText = '';
		}

		$markerArray['###'.strtoupper($type).'_SALUTATION###'] = $typeSalutationText;
		
	} // getMarkerArray

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_feuser.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_feuser.php']);
}


?>
