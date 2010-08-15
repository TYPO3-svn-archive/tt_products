<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2010 Franz Holzinger <franz@ttproducts.de>
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
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

global $TYPO3_CONF_VARS;

class tx_ttproducts_feuser {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $dataArray; // array of read in frontend users
	var $table;		 // object of the type tx_table_db
	var $fields = array();
	var $tableconf;
	var $bCondition = FALSE;
	var $bConditionRecord = FALSE;


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init (&$pibase, &$cnf, $tablename)  {
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

	function isUserInGroup ($feuser, $group)	{
		$groups = explode(',', $feuser['usergroup']);
		foreach ($groups as $singlegroup)
			if ($singlegroup == $group)
				return true;
		return false;
	} // isUserInGroup

	function setCondition ($row, $funcTablename)	{
		global $TSFE;

		$bCondition = FALSE;

		if (isset($this->conf['conf.'][$funcTablename.'.']['ALL.']['fe_users.']['date_of_birth.']['period.']['y']))	{
			$year = $this->conf['conf.'][$funcTablename.'.']['ALL.']['fe_users.']['date_of_birth.']['period.']['y'];
			$infoObj = &t3lib_div::getUserObj('&tx_ttproducts_info');

			if ($infoObj->infoArray['billing']['date_of_birth'])	{
				$timeTemp = $infoObj->infoArray['billing']['date_of_birth'];
				$bAge = TRUE;
			} else if ($TSFE->fe_user->user)	{
				if (t3lib_extMgm::isLoaded('sr_feuser_register')) {
					require_once(PATH_BE_srfeuserregister.'pi1/class.tx_srfeuserregister_pi1_adodb_time.php');

					// prepare for handling dates before 1970
					$adodbTime = &t3lib_div::getUserObj('&tx_srfeuserregister_pi1_adodb_time');
					$timeTemp = $adodbTime->adodb_date('d-m-Y', $TSFE->fe_user->user['date_of_birth']);
				} else {
					$timeTemp = date('d-m-Y', ($TSFE->fe_user->user['date_of_birth']));
				}
				$bAge = TRUE;
			} else {
				$bAge = FALSE;
			}

			if ($bAge)	{
				$feDateArray = t3lib_div::trimExplode('-', $timeTemp);
				$date = getdate();
				$offset = 0;
				if ($date['mon'] < $feDateArray[1])	{
					$offset = 1;
				}
				if ($date['year'] - $feDateArray[2] - $offset >= $year)	{
					$bCondition = TRUE;
				}
			}
		} else {
			$bCondition = TRUE;
		}

		$whereConf = $this->conf['conf.'][$funcTablename.'.']['ALL.']['fe_users.']['where'];
		$whereArray = t3lib_div::trimExplode('IN', $whereConf);
		$pos1 = strpos ($whereArray[1], '(');
		$pos2 = strpos ($whereArray[1], ')');
		$inString = substr ($whereArray[1], $pos1+1, $pos2-$pos1-1);

		$valueArray = t3lib_div::trimExplode(',', $inString);
		foreach ($valueArray as $value)	{
			if ($row[$whereArray[0]] == $value)	{
				$this->bConditionRecord = TRUE;
			}
		}

		if ($bCondition)	{
			$this->bCondition = TRUE;
		}
	}

	function getCondition ()	{
		return $this->bCondition;
	}

	function getWrappedSubpartArray (&$subpartArray, &$wrappedSubpartArray, $funcTablename)	{
		global $TSFE;

		if ($TSFE->fe_user->user)	{
			$wrappedSubpartArray['###FE_GROUP_1_TEMPLATE###'] = array('','');
			$subpartArray['###FE_GROUP_0_TEMPLATE###'] = '';
		} else {
			$wrappedSubpartArray['###FE_GROUP_0_TEMPLATE###'] = array('','');
			$subpartArray['###FE_GROUP_1_TEMPLATE###'] = '';
		}

		if ($this->getCondition() || !$this->bConditionRecord)	{
			$wrappedSubpartArray['###FE_CONDITION1_TRUE_TEMPLATE###'] = array('','');
			$subpartArray['###FE_CONDITION1_FALSE_TEMPLATE###'] = '';
		} else {
			$wrappedSubpartArray['###FE_CONDITION1_FALSE_TEMPLATE###'] = array('','');
			$subpartArray['###FE_CONDITION1_TRUE_TEMPLATE###'] = '';
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
			$typeSalutationText = tx_ttproducts_form_div::createSelect ($this->pibase, $TCA['sys_products_orders']['columns']['salutation']['config']['items'], 'recs['.$type.'][salutation]', $row['salutation'], array());

		} else if (is_numeric($row['salutation'])) {
			$salutation = $TCA['sys_products_orders']['columns']['salutation']['config']['items'][$row['salutation']];
			$tmp = tx_div2007_alpha::sL_fh001($salutation[0]);
			$typeSalutationText = htmlspecialchars(tx_div2007_alpha::getLL($this->pibase, $tmp));
		} else {
			$typeSalutationText = '';
		}

		$markerArray['###'.($type=='personinfo' ? 'PERSON' : 'DELIVERY').'_SALUTATION###'] = $typeSalutationText;
	} // getMarkerArray
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_feuser.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_feuser.php']);
}


?>
