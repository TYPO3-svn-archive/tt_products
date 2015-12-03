<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Franz Holzinger <franz@ttproducts.de>
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
 * functions for the static_taxes table
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


// require_once(PATH_BE_table.'lib/class.tx_table_db.php');


class tx_ttproducts_static_tax extends tx_ttproducts_table_base {
	protected $uidStore;
	private $allTaxesArray;
	private $taxArray;
	private $countryArray = array();
	private $taxIdArray = array();

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init (&$pibase, $functablename)	{
		if ($this->isInstalled())	{
			parent::init($pibase, $functablename);
			$tablename = $this->getTablename();
			$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
			$tableconf = $cnf->getTableConf('static_taxes');
			$this->getTableObj()->setDefaultFieldArray(array('uid' => 'uid', 'pid' => 'pid'));
			$this->getTableObj()->setTCAFieldArray('static_taxes');

			$requiredFields = 'uid,pid';
			if ($this->tableconf['requiredFields'])	{
				$tmp = $tableconf['requiredFields'];
				$requiredFields = ($tmp ? $tmp : $requiredFields);
			}
			$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
			$this->getTableObj()->setRequiredFieldArray($requiredListArray);
		}
	} // init

	public function isInstalled () {
		$rc = FALSE;

		if (t3lib_extMgm::isLoaded('static_info_tables_taxes')) {
			$eInfo = tx_div2007_alpha5::getExtensionInfo_fh003('static_info_tables_taxes');

			if (is_array($eInfo)) {
				$sittVersion = $eInfo['version'];
				if (version_compare($sittVersion, '0.1.0', '>=')) {
					$rc = TRUE;
				}
			}
		}
		return $rc;
	}

	public function isValid ()	{
		$rc = $this->isInstalled() && !$this->needsInit() && $this->getUidStore();
		return $rc;
	}

	public function getUidStore ()	{
		return $this->uidStore;
	}

	public function setUidStore ($uid)	{
		$this->uidStore = $uid;
	}

	public function setStoreData ($uidStore)	{
		global $TYPO3_DB;

		if ($this->isInstalled())	{
			$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
			$orderAdressObj = $tablesObj->get('address', FALSE);
			$storeRow = $orderAdressObj->get($uidStore);
			$theCountryCode = '';

			if ($storeRow)	{
				$staticInfoCountryField = $orderAdressObj->getField('static_info_country');
				$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
				$tableconf = $cnf->getTableConf('address');

				if (
					$tableconf['countryReference'] == 'uid' &&
					tx_div2007_core::testInt($storeRow[$staticInfoCountryField])
				) {
					$countryObj = $tablesObj->get('static_countries');
					if (is_object($countryObj)) {
						$countryRow = $countryObj->get($storeRow[$staticInfoCountryField]);
						$theCountryCode = $countryRow['cn_iso_3'];
					}
				} else {
					$theCountryCode = $storeRow[$staticInfoCountryField];
				}

				$zoneField = $orderAdressObj->getField('zone');
				if ($tableconf['zoneReference'] == 'uid')	{
					$zoneArray = $TYPO3_DB->exec_SELECTgetRows('zn_code', 'static_country_zones', 'uid=' . intval($storeRow[$zoneField]));
					if (isset($zoneArray) && is_array($zoneArray) && isset($zoneArray[0]))	{
						$theZoneCode = $zoneArray[0]['zn_code'];
					}
				} else {
					$theZoneCode = $storeRow[$zoneField];
				}
				$this->countryArray['shop'] = array();
				$this->countryArray['customer'] = array();
				$this->countryArray['shop']['country_code'] = $theCountryCode;
				$this->countryArray['shop']['zone'] = $theZoneCode;
				$this->setUidStore($uidStore); // this must be done at the end of successful processing
			}
/*			$allTaxesArray = array();
			$this->getStaticTax($row,$tax,$allTaxesArray); // call it to set the member variables*/
		}
	}

	protected function didValuesChange ($countryArray)	{
		if (count($this->countryArray['customer']))	{
			$rc = (count(array_diff_assoc($this->countryArray['customer'], $countryArray['customer'])) > 0);
		} else {
			$rc = (count($countryArray['customer']) > 0);
		}
		return $rc;
	}

	public function setAllTaxesArray ($taxArray,$taxId='')	{
		if (strlen($taxId))	{
			$this->allTaxesArray[$taxId] = $taxArray;
		} else {
			$this->allTaxesArray = $taxArray;
		}
	}

	public function getAllTaxesArray ($taxId = '')	{
		if (strlen($taxId))	{
				$rc = $this->allTaxesArray[$taxId];
		} else {
				$rc = $this->allTaxesArray;
		}
		return $rc;
	}

	public function setTax ($tax, $taxId)	{

		if (strlen($taxId))	{
			$this->taxArray[$taxId] = $tax;
		}
	}

	public function getTaxArray ($taxId)	{
		return $this->taxArray[$taxId];
	}

	public function setTaxId ($taxId)	{
		$this->taxIdArray[] = $taxId;
		$this->taxIdArray = array_unique($this->taxIdArray);
	}

	public function storeValues ($countryArray)	{
		$this->countryArray = $countryArray;
	}

	public function getStaticTax (&$row, &$tax, &$taxArray)	{

		if ($this->getUidStore() && $this->isInstalled())	{
			$basketObj = t3lib_div::getUserObj('&tx_ttproducts_basket');

			if (isset($basketObj->recs) && is_array($basketObj->recs) && count($basketObj->recs))	{
				$deliveryInfo = $basketObj->recs['delivery'];
			}

			if (isset($this->countryArray['shop']['country_code']) && strlen($row['tax_id']))	{
				$taxId = $row['tax_id'];
				$staticInfoObj = tx_ttproducts_static_info::getStaticInfo();

				$countryArray = $this->countryArray;

				if (isset($deliveryInfo) && is_array($deliveryInfo))	{
					$countryArray['customer']['country_code'] =  $deliveryInfo['country_code'];
					$countryArray['customer']['zone'] = $deliveryInfo['zone'];
				} else {	// use the tax from the location and zone of the store
					$countryArray['customer']['country_code'] =  $countryArray['shop']['country_code'];
					$countryArray['customer']['zone'] = $countryArray['shop']['zone'];
				}
				$taxArray = $this->getAllTaxesArray($taxId);

				if ($this->didValuesChange(
					$countryArray
				) || !$taxArray)	{
					$taxArray = tx_staticinfotablestaxes_div::fetchCountryTaxes(
						$staticInfoObj,
						3,
						$taxId,
						$countryArray['shop']['country_code'],
						$countryArray['shop']['zone'],
						$countryArray['customer']['country_code'],
						$countryArray['customer']['zone']
					);
					$this->storeValues(
						$countryArray
					);
					$this->setAllTaxesArray($taxArray,$taxId);
					$tax = 0.0;

					if (isset($taxArray) && is_array($taxArray) && count($taxArray))	{
						$priceOne = tx_staticinfotablestaxes_div::applyConsumerTaxes (
							$staticInfoObj,
							1,
							$taxArray
						);
						$tax = floatval($priceOne - 1);
					}
					$this->setTax($tax,$taxId);
				} else {
					$tax = floatval($this->getTaxArray($taxId));
				}
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_static_tax.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_static_tax.php']);
}


?>