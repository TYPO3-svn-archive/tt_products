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


// require_once (PATH_BE_ttproducts.'model/field/class.tx_ttproducts_field_base.php');


abstract class tx_ttproducts_table_base	{
	public $bHasBeenInitialised = false;
	public $cObj;
	public $conf;
	public $config;
	public $fieldArray = array(); // field replacements

	private $functablename;
	private $tablename;
	public $tableObj;	// object of the type tx_table_db
	private $theCode;
	private $orderBy;
	protected $dataArray;
	private $fieldClassArray = array (
		'datasheet' => 'tx_ttproducts_field_datafield',
		'graduated_price_uid' => 'tx_ttproducts_field_graduated_price',
		'image' => 'tx_ttproducts_field_image',
		'itemnumber' => 'tx_ttproducts_field_text',
		'note' => 'tx_ttproducts_field_note',
		'note2' => 'tx_ttproducts_field_note',
		'price' => 'tx_ttproducts_field_price',
		'price2' => 'tx_ttproducts_field_price',
		'subtitle' => 'tx_ttproducts_field_text',
		'title' => 'tx_ttproducts_field_text',
	);


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


	public function init ($cObj, $functablename)	{

		$this->cObj = $cObj;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		$this->tableObj = t3lib_div::makeInstance('tx_table_db');

		$this->setFuncTablename($functablename);
		$tablename = $cnf->getTableName($functablename);
		$tablename = ($tablename ? $tablename : $functablename);
		$this->setTablename($tablename);
		$this->tableObj->setName($tablename);

		$this->tableDesc = $cnf->getTableDesc($functablename);
		if (isset($this->tableDesc) && is_array($this->tableDesc))	{
			$this->fieldArray = array_merge($this->fieldArray, $this->tableDesc);
		}
		$this->bHasBeenInitialised = TRUE;
	}


	/* uid can be a string. Add a blank character to your uid integer if you want to have muliple rows as a result
	*
	* @param	[type]		$uid: ...
	* @param	[type]		$pid: ...
	* @param	[type]		$bStore: ...
	* @param	[type]		$where_clause: ...
	* @param	[type]		$limit: ...
	* @param	[type]		$fields: ...
	* @param	[type]		$bCount: ...
	* @return	[type]		...
	* @author  Franz Holzinger <contact@fholzinger.com>
	* @maintainer	Franz Holzinger <contact@fholzinger.com>
	* @package TYPO3
	* @subpackage tt_products
	*/
	function get ($uid = '0', $pid = 0, $bStore = TRUE, $where_clause = '', $limit = '', $fields = '', $bCount = FALSE, $groupBy = '', $orderBy = '') {
		global $TYPO3_DB;

		$tableObj = $this->getTableObj();
		$alias = $this->getAlias() . $aliasPostfix;

		if (
			tx_div2007_core::testInt($uid) &&
			isset($this->dataArray[$uid]) &&
			is_array($this->dataArray[$uid]) &&
			!$where_clause &&
			!$fields
		)	{
			if (!$pid || ($pid && $this->dataArray[$uid]['pid'] == $pid))	{
				$rc = $this->dataArray[$uid];
			} else {
				$rc = array();
			}
		}

		if (!$rc) {
			$enableFields = $tableObj->enableFields($aliasPostfix);
			$where = '1=1 '.$enableFields;

			if (is_int($uid))	{
				$where .= ' AND '.$alias.'.uid = '.intval($uid);
			} else if($uid)	{
				$uidArray = t3lib_div::trimExplode(',',$uid);
				foreach ($uidArray as $k => $v)	{
					$uidArray[$k] = intval($v);
				}
				$where .= ' AND ' . $alias . '.uid IN (' . implode(',',$uidArray) . ')';
			}
			if ($pid)	{
				$pidArray = t3lib_div::trimExplode(',',$pid);
				foreach ($pidArray as $k => $v)	{
					$pidArray[$k] = intval($v);
				}
				$where .= ' AND ' . $alias . '.pid IN (' . implode(',',$pidArray) . ')';
			}
			if ($where_clause)	{
				$where .= ' AND ( ' . $where_clause . ' )';
			}
			if (!$fields)	{
				if ($bCount)	{
					$fields = 'count(*)';
				} else {
					$fields = '*';
				}
			}

			// Fetching the records
			$res = $tableObj->exec_SELECTquery($fields, $where, $groupBy, $orderBy, $limit, '', $aliasPostfix);

			if ($res !== FALSE)	{

				$rc = array();

				while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{

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


	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getCobj ()	{
		return $this->cObj;
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$cObj: ...
	 * @return	[type]		...
	 */
	function setCobj ($cObj)	{
		$this->cObj = $cObj;
	}


	function needsInit ()	{
		return !$this->bHasBeenInitialised;
	}


	public function getfieldClass ($fieldname)	{
		$rc = '';
		if ($fieldname)	{
			$rc = $this->fieldClassArray[$fieldname];
		}
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


	public function getCode ()	{
		return $this->theCode;
	}


	public function setCode ($theCode)	{
		$this->theCode = $theCode;
	}


	/* initalisation for code dependant configuration */
	public function initCodeConf ($theCode)	{
		$tableConf = $this->getTableConf($theCode);

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
				$tableObj->setTCAFieldArray($tableObj->getLangName(), $tableObj->getAlias() . 'lang', FALSE);
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


	public function getTableConf ($theCode='')	{
		if (!$theCode)	{
			$theCode = $this->theCode;
		}
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');

		$rc = $cnf->getTableConf($this->getFuncTablename(), $theCode);
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


	public function getLanguageFieldArray ($theCode='')	{

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
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_table_base.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_table_base.php']);
}


?>