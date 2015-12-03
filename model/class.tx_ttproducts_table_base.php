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
 * base class for all database table classes
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


abstract class tx_ttproducts_table_base	{
	public $bHasBeenInitialised = FALSE;
	public $cObj;
	public $conf;
	public $config;
	public $tableObj;	// object of the type tx_table_db
	public $defaultFieldArray = array('uid' => 'uid', 'pid' => 'pid'); // fields which must always be read in
	public $relatedFromTableArray = array();
	protected $insertRowArray;	// array of stored insert records
	protected $insertKey;		// array for insertion
	public $fieldArray = array(); // field replacements

	protected $tableAlias;	// must be overridden
	protected $dataArray;

	private $functablename;
	private $tablename;
	private $tableConf;
	private $tableDesc;
	private $theCode;
	private $orderBy;

	private $fieldClassArray = array (
			'ac_uid' => 'tx_ttproducts_field_foreign_table',
			'crdate' => 'tx_ttproducts_field_datetime',
// 			'creditpoints' => 'tx_ttproducts_field_creditpoints',
			'datasheet' => 'tx_ttproducts_field_datafield',
// 			'delivery' => 'tx_ttproducts_field_delivery',
			'directcost' => 'tx_ttproducts_field_price',
			'endtime' => 'tx_ttproducts_field_datetime',
			'graduated_price_uid' => 'tx_ttproducts_field_graduated_price',
			'image' => 'tx_ttproducts_field_image',
			'smallimage' => 'tx_ttproducts_field_image',
			'itemnumber' => 'tx_ttproducts_field_text',
			'note' => 'tx_ttproducts_field_note',
			'note2' => 'tx_ttproducts_field_note',
			'price' => 'tx_ttproducts_field_price',
			'price2' => 'tx_ttproducts_field_price',
			'sellendtime' => 'tx_ttproducts_field_datetime',
			'sellstarttime' => 'tx_ttproducts_field_datetime',
			'starttime' => 'tx_ttproducts_field_datetime',
			'subtitle' => 'tx_ttproducts_field_text',
			'tax' => 'tx_ttproducts_field_tax',
			'title' => 'tx_ttproducts_field_text',
			'tstamp' => 'tx_ttproducts_field_datetime',
			'usebydate' => 'tx_ttproducts_field_datetime',
		);

	public function init ($cObj, $functablename)	{
		global $TCA;

		$this->cObj = $cObj;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->tableObj = t3lib_div::makeInstance('tx_table_db');
		$this->insertKey = 0;

		$this->setFuncTablename($functablename);
		$tablename = $cnf->getTableName($functablename);
		$tablename = ($tablename ? $tablename : $functablename);
		$this->setTablename($tablename);
		$this->tableDesc = $cnf->getTableDesc($functablename);

		$checkDefaultFieldArray = array('tstamp' => 'tstamp', 'crdate' => 'crdate', 'hidden' => 'hidden', 'deleted' => 'deleted');

		if (isset($TCA[$tablename]['ctrl']) && is_array($TCA[$tablename]['ctrl']))	{

			foreach ($checkDefaultFieldArray as $theField)	{
				if (
					isset($TCA[$tablename]['ctrl'][$theField]) && is_array($TCA[$tablename]['ctrl'][$theField]) ||
					in_array($theField,$TCA[$tablename]['ctrl'],TRUE) ||
					isset($TCA[$tablename]['ctrl']['enablecolumns']) && is_array($TCA[$tablename]['ctrl']['enablecolumns']) && in_array($theField,$TCA[$tablename]['ctrl']['enablecolumns'],TRUE)
				)	{
					$this->defaultFieldArray[$theField] = $theField;
				}
			}
		}

		if (isset($this->tableDesc) && is_array($this->tableDesc))	{
			$this->fieldArray = array_merge($this->fieldArray, $this->tableDesc);
		}

		$this->fieldArray['name'] = ($this->tableDesc['name'] && is_array($TCA[$this->tableDesc['name']]['ctrl']) ? $this->tableDesc['name'] : ($TCA[$tablename]['ctrl']['label'] ? $TCA[$tablename]['ctrl']['label'] : 'name'));
		$this->defaultFieldArray[$this->fieldArray['name']] = $this->fieldArray['name'];

		if (isset($this->defaultFieldArray) && is_array($this->defaultFieldArray) && count($this->defaultFieldArray))	{
			$this->tableObj->setDefaultFieldArray($this->defaultFieldArray);
		}

		$this->tableObj->setName($tablename);
		$this->tableObj->setTCAFieldArray($tablename, $this->tableAlias);
		$this->tableObj->setNewFieldArray();
		$this->bHasBeenInitialised = TRUE;
		$this->tableConf = $this->getTableConf('');
		$this->initCodeConf('ALL', $this->tableConf);
	}


	public function clear ()	{
		$this->dataArray = array();
	}


	public function getField ($theField)	{
		$rc = $theField;
		if (isset($this->fieldArray[$theField]))	{
			$rc = $this->fieldArray[$theField];
		}
		return $rc;
	}


	/* uid can be a string. Add a blank character to your uid integer if you want to have muliple rows as a result
	*/
	public function get ($uid = '0', $pid = 0, $bStore = TRUE, $where_clause = '', $groupBy = '', $orderBy = '', $limit = '', $fields = '', $bCount = FALSE, $aliasPostfix = '') {
		global $TYPO3_DB;

		$tableObj = $this->getTableObj();
		$alias = $this->getAlias() . $aliasPostfix;

		if (
			tx_div2007_core::testInt($uid) &&
			isset($this->dataArray[$uid]) &&
			is_array($this->dataArray[$uid]) &&
			!$where_clause &&
			!$fields
		) {
			if (!$pid || ($pid && $this->dataArray[$uid]['pid'] == $pid))	{
				$rc = $this->dataArray[$uid];
			} else {
				$rc = array();
			}
		}

		if (!$rc) {
			$needsEnableFields = TRUE;
			$enableFields = $tableObj->enableFields($aliasPostfix);
			$where = '1=1';

			if (is_int($uid))	{
				$where .= ' AND ' . $alias . '.uid = ' . intval($uid);
			} else if($uid)	{
				$uidArray = t3lib_div::trimExplode(',', $uid);
				foreach ($uidArray as $k => $v)	{
					$uidArray[$k] = intval($v);
				}
				$where .= ' AND ' . $alias . '.uid IN (' . implode(',', $uidArray) . ')';
			}
			if ($pid)	{
				$pidArray = t3lib_div::trimExplode(',', $pid);
				foreach ($pidArray as $k => $v)	{
					$pidArray[$k] = intval($v);
				}
				$where .= ' AND ' . $alias . '.pid IN (' . implode(',', $pidArray) . ')';
			}
			if ($where_clause)	{
				if (strpos($where_clause, $enableFields) !== FALSE) {
					$needsEnableFields = FALSE;
				}
				$where .= ' AND ( ' . $where_clause . ' )';
			}
			if ($needsEnableFields) {
				$where .= $enableFields;
			}

			if (!$fields)	{
				if ($bCount)	{
					$fields = 'count(*)';
				} else {
					$fields = '*';
				}
			}
// 			$tableConf = $this->tableConf;
// 			$orderBy = $TYPO3_DB->stripOrderBy($tableConf['orderBy']);

			// Fetching the records
			$res = $tableObj->exec_SELECTquery($fields, $where, $groupBy, $orderBy, $limit, '', $aliasPostfix);

			if ($res !== FALSE)	{

				$rc = array();

				while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
// +++ hook +++
					if (is_array($tableObj->langArray) && $tableObj->langArray[$row['title']])	{
						$row['title'] = $tableObj->langArray[$row['title']];
					}
					if ($row)	{
						$rc[$row['uid']] = $row;
						if($bStore && $fields == '*')	{
							$this->dataArray[$row['uid']] = $row;
						}
					} else {
						break;
					}
				}

				$TYPO3_DB->sql_free_result($res);
				if (
					tx_div2007_core::testInt($uid)
				) {
					reset($rc);
					$rc = current ($rc);
				}

				if ($bCount)	{
					reset($rc[0]);
					$rc = intval(current($rc[0]));
				}

				if (!$rc) {
					$rc = array();
				}
			} else {
				$rc = FALSE;
			}
		}
		return $rc;
	}


	/**
	 * Returns the label of the record, Usage in the following format:
	 *
	 * @param	array		$row: current record
	 * @return	string		Label of the record
	 */
	public function getLabel ($row) {

		return $row['title'];
	}


	public function getCobj ()	{
		return $this->cObj;
	}


	public function setCobj ($cObj)	{
		$this->cObj = $cObj;
	}


	public function needsInit ()	{
		return !$this->bHasBeenInitialised;
	}


	public function getDefaultFieldArray ()	{
		return $this->defaultFieldArray;
	}


	public function getFieldClassAndPath ($fieldname)	{
		global $TCA;

		$class = '';
		$path = '';
		$tablename = $this->getTablename();

		if ($fieldname && isset($TCA[$tablename]['columns'][$fieldname]) && is_array($TCA[$tablename]['columns'][$fieldname]))	{

			$funcTablename = $this->getFuncTablename();
			if (
				isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass']) &&
				is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass']) &&
				isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass'][$funcTablename]) &&
				is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass'][$funcTablename])
			)	{
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass'][$funcTablename] as $extKey => $hookArray)	{
					if (t3lib_extMgm::isLoaded($extKey)) {
						$class = $hookArray[$fieldname];
						if ($class)	{
							$path = t3lib_extMgm::extPath($extKey);
							break;
						}
					}
				}
			}

			if (!$class)	{
				$class = $this->fieldClassArray[$fieldname];
				if ($class)	{
					$path = PATH_BE_ttproducts;
				}
			}
		}
		$rc = array('class' => $class, 'path' => $path);
		return $rc;
	}


	public function getAlias ()	{
		$tableObj = $this->getTableObj();
		return $tableObj->getAlias();
	}


	public function getFuncTablename ()	{
		return $this->functablename;
	}


	private function setFuncTablename ($tablename)	{
		$this->functablename = $tablename;
	}


	public function getTablename ()	{
		return $this->tablename;
	}


	private function setTablename ($tablename)	{
		$this->tablename = $tablename;
	}


	public function getLangName () {
		$tableObj = $this->getTableObj();
		return $tableObj->getLangName();
	}


	public function getCode ()	{
		return $this->theCode;
	}


	public function setCode ($theCode)	{
		$this->theCode = $theCode;
	}


	public function getOrderBy ()	{
		return $this->orderBy;
	}


	/* initalisation for code dependant configuration */
	public function initCodeConf ($theCode, $tableConf)	{
		if ($theCode != $this->getCode())	{
			$this->setCode($theCode);
			if ($this->orderBy != $tableConf['orderBy'])	{
				$this->orderBy = $tableConf['orderBy'];
				$this->dataArray = array();
			}

			$requiredFields = $this->getRequiredFields($theCode);
			$requiredFieldArray = t3lib_div::trimExplode(',', $requiredFields);
			$this->getTableObj()->setRequiredFieldArray($requiredFieldArray);

			if (is_array($tableConf['language.']) &&
				$tableConf['language.']['type'] == 'field' &&
				is_array($tableConf['language.']['field.'])
				)	{
				$addRequiredFields = array();
				$addRequiredFields = $tableConf['language.']['field.'];
				$this->getTableObj()->addRequiredFieldArray($addRequiredFields);
			}
			$tableObj = $this->getTableObj();
			if ($this->bUseLanguageTable($tableConf))	{
				$tableObj->setLanguage($this->config['LLkey']);
				$tableObj->setLangName($tableConf['language.']['table']);
				$tableObj->setTCAFieldArray($tableObj->getLangName(), $tableObj->getAlias().'lang', FALSE);
			}
			if ($tableConf['language.'] && $tableConf['language.']['type'] == 'csv')	{
				$tableObj->initLanguageFile($tableConf['language.']['file']);
			}
			if ($tableConf['language.'] && is_array($tableConf['language.']['marker.']))	{
				$tableObj->initMarkerFile($tableConf['language.']['marker.']['file']);
			}
		}
	}


	public function translateByFields ($theCode)	{
		$langFieldArray = $this->getLanguageFieldArray($theCode);

		if (is_array($this->dataArray))	{
			foreach ($this->dataArray as $uid => $row)	{
				foreach ($row as $field => $value)	{
					$realField = $langFieldArray[$field];

					if (isset($realField) && $realField != $field)	{
						$newValue = $this->dataArray[$uid][$realField];
						if ($newValue != '')	{
							$this->dataArray[$uid][$field] = $newValue;
						}
					}
				}
			}
		}
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


	public function fixTableConf (&$tableConf)	{
		// nothing. Override thisn for your table if needed
	}


	public function getTableConf ($theCode = '')	{
		if ($theCode == '' && $this->getCode() != '')	{
			$rc = $this->tableConf;
		} else {
			$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
			$rc = &$cnf->getTableConf($this->getFuncTablename(), $theCode);
		}
		$this->fixTableConf($rc);
		return $rc;
	}


	public function setTableConf ($tableConf)	{
		$this->tableConf = $tableConf;
	}


	public function getTableDesc ()	{
		return $this->tableDesc;
	}


	public function setTableDesc ($tableDesc)	{
		$this->tableDesc = tableDesc;
	}


	public function getKeyFieldArray ($theCode = '')	{
		$tableConf = $this->getTableConf($theCod);
		$rc = array();
		if (isset($tableConf['keyfield.']) && is_array($tableConf['keyfield.']))	{
			$rc = $tableConf['keyfield.'];
		}
		return $rc;
	}


	public function getRequiredFields ($theCode='')	{
		$tableConf = $this->getTableConf($theCode);
		$rc = '';
		if (isset($tableConf['requiredFields']))	{
			$rc = $tableConf['requiredFields'];
		} else {
			$rc = 'uid,pid';
		}
		return $rc;
	}


	public function getLanguageFieldArray ($theCode = '')	{

		$tableConf = $this->getTableConf($theCode);
		if (is_array($tableConf['language.']) &&
			$tableConf['language.']['type'] == 'field' &&
			is_array($tableConf['language.']['field.'])
		)	{
			$rc = $tableConf['language.']['field.'];
		} else {
			$rc = array();
		}
		return $rc;
	}


	public function getTableObj ()	{
		return $this->tableObj;
	}


	public function reset ()	{
		$this->insertRowArray = array();
		$this->setInsertKey(0);
	}


	public function setInsertKey ($k)	{
		$this->insertKey = $k;
	}


	public function getInsertKey ()	{
		return $this->insertKey;
	}


	public function &addInsertRow ($row, &$k='')	{
		$bUseInsertKey = FALSE;

		if ($k == '')	{
			$k = $this->getInsertKey();
			$bUseInsertKey = TRUE;
		}
		$this->insertRowArray[$k++] = $row;
		if ($bUseInsertKey)	{
			$this->setInsertKey($k);
		}
	}


	public function &getInsertRowArray ()	{
		return $this->insertRowArray;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_table_base.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_table_base.php']);
}

?>