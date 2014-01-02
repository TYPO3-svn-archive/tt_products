<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2010 Franz Holzinger <franz@ttproducts.de>
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
 * functions for the info addresses view
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_form_div.php');


class tx_ttproducts_info_view {
	public $pibase; // reference to object of pibase
	public $conf;
	public $config;
	public $infoArray; // elements: 'billing' and 'delivery' addresses
			// contains former basket $personInfo and $deliveryInfo

	public $feuserextrafields;		// exension with additional fe_users fields
	public $country;			// object of the type tx_table_db
	public $password;	// automatically generated random password for a new frontend user
	public $staticInfo; 	// object for the static_info_tables extension
	public $feuserfields;
	public $creditpointfields;
	public $overwriteMode = 0;
	public $bDeliveryAddress = FALSE;	// normally the delivery is copied from the bill data. But also another table than fe_users could be used for the billing data.
	public $bHasBeenInitialised = FALSE;


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init (&$pibase, $formerBasket, $bProductsPayment)  {
		global $TYPO3_DB,$TSFE, $TCA;

		$this->pibase = &$pibase;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$paymentshippingObj = &t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');

		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		$this->infoArray = array();
		$this->infoArray['billing'] = $formerBasket['personinfo'];
		$this->infoArray['delivery'] = $formerBasket['delivery'];

		$shippingType = $paymentshippingObj->get('shipping', 'type');
		if ($shippingType == 'pick_store')	{
			$this->bDeliveryAddress = TRUE;
		}

		$allowedTags = '<br><a><b><td><tr><div>';
		foreach ($this->infoArray as $type => $infoRowArray)	{
			if (is_array($infoRowArray))	{
				foreach ($infoRowArray as $k => $infoRow)	{
					$this->infoArray[$type][$k] = strip_tags($infoRow, $allowedTags);
				}
			} else {
				$this->infoArray[$type] = strip_tags($infoRowArray, $allowedTags);
			}
		}

		$this->feuserfields = 'name,cnum,first_name,last_name,username,email,telephone,title,salutation,address,telephone,fax,email,company,city,zip,state,country,country_code,tt_products_vat,date_of_birth';
		$this->creditpointfields = 'tt_products_creditpoints,tt_products_vouchercode';

		// if feuserextrafields is loaded use also these extra fields
		if (t3lib_extMgm::isLoaded('feuserextrafields')) {
			$this->feuserextrafields = ',tx_feuserextrafields_initials_name, tx_feuserextrafields_prefix_name, tx_feuserextrafields_gsm_tel,'.
					'tx_feuserextrafields_company_deliv, tx_feuserextrafields_address_deliv, tx_feuserextrafields_housenumber,'.
					'tx_feuserextrafields_housenumber_deliv, tx_feuserextrafields_housenumberadd, tx_feuserextrafields_housenumberadd_deliv,'.
					'tx_feuserextrafields_pobox, tx_feuserextrafields_pobox_deliv, tx_feuserextrafields_zip_deliv, tx_feuserextrafields_city_deliv,'.
					'tx_feuserextrafields_country, tx_feuserextrafields_country_deliv';
			$this->feuserfields .= ','.$this->feuserextrafields;
		}

		if (isset($TCA['fe_users']['columns']) && is_array(($TCA['fe_users']['columns'])))	{
			foreach (($TCA['fe_users']['columns']) as $field => $fieldTCA)	{
				if (!t3lib_div::inList($this->feuserfields, $field))	{
					$this->feuserfields .= ',' . $field;
				}
			}
		}

		if (t3lib_extMgm::isLoaded('static_info_tables')) {
			$eInfo = tx_div2007_alpha::getExtensionInfo_fh001('static_info_tables');

			if (is_array($eInfo))	{
				$sitVersion = $eInfo['version'];

				if (version_compare($sitVersion, '2.0.0', '>='))	{
					// Initialise static info library
					$this->staticInfo = &t3lib_div::getUserObj('&tx_staticinfotables_pi1');
					if (!method_exists($this->staticInfo, 'needsInit') || $this->staticInfo->needsInit())	{
						$this->staticInfo->init();
					}
				} else if (t3lib_extMgm::isLoaded('sr_static_info')) {
					include_once(t3lib_extMgm::extPath('sr_static_info').'pi1/class.tx_srstaticinfo_pi1.php');
					// Initialise static info library
					$this->staticInfo = t3lib_div::makeInstance('tx_srstaticinfo_pi1');
					$this->staticInfo->init();
				}
			} else {
				// TODO: inform the user about wrong version of static_info_tables
			}
		}

		if ($this->conf['useStaticInfoCountry'] && $this->infoArray['billing']['country_code'] && is_object($this->staticInfo))	{
			$this->infoArray['billing']['country'] = $this->staticInfo->getStaticInfoName('COUNTRIES', $this->infoArray['billing']['country_code'],'','');
			if ($this->infoArray['delivery']['name'] && !$this->bDeliveryAddress)	{
				$this->infoArray['delivery']['country'] = $this->staticInfo->getStaticInfoName('COUNTRIES', $this->infoArray['delivery']['country_code'],'','');
			}

			$bFixCountries = self::fixCountries($this->infoArray);
			if (
				!$bFixCountries &&
				$this->infoArray['delivery']['name'] && !$this->bDeliveryAddress
			)	{
				$this->infoArray['delivery']['country'] = $this->staticInfo->getStaticInfoName('COUNTRIES', $this->infoArray['delivery']['country_code'],'','');
			}
		}

		if (
			$TSFE->loginUser &&
			(!$this->infoArray['billing'] || !$this->infoArray['billing']['name'] || $this->conf['editLockedLoginInfo'] || $this->infoArray['billing']['error']) &&
			$this->conf['lockLoginUserInfo']
		)	{
			$address = '';
			$this->infoArray['billing']['feusers_uid'] = $TSFE->fe_user->user['uid'];

			if ($this->conf['useStaticInfoCountry'] && !$this->infoArray['billing']['country_code'])	{
				$this->infoArray['billing']['country_code'] = $TSFE->fe_user->user['static_info_country'];
// 				if (!$this->bDeliveryAddress)	{
// 					$this->infoArray['delivery']['country_code'] = $TSFE->fe_user->user['static_info_country'];
// 				}
			}

			if ($this->conf['loginUserInfoAddress']) {
				$address = implode(chr(10),
					t3lib_div::trimExplode(
						chr(10),
						$TSFE->fe_user->user['address'].chr(10).
						$TSFE->fe_user->user['zip'].' '.$TSFE->fe_user->user['city'].chr(10).
						($this->conf['useStaticInfoCountry'] ? $TSFE->fe_user->user['static_info_country']:$TSFE->fe_user->user['country']),
						1
					)
				);
			} else {
				$address = $TSFE->fe_user->user['address'];
			}
			$this->infoArray['billing']['address'] = $address;

			$fields = $this->feuserfields.','.$this->creditpointfields;
			$fieldArray = t3lib_div::trimExplode(',',$fields);
			foreach ($fieldArray as $k => $field)	{
				$this->infoArray['billing'][$field] = ($this->infoArray['billing'][$field] ? $this->infoArray['billing'][$field]: $TSFE->fe_user->user[$field]);
			}
			$this->infoArray['billing']['country'] = ($this->infoArray['billing']['country'] ? $this->infoArray['billing']['country'] : (($this->conf['useStaticInfoCountry'] || !$TSFE->fe_user->user['country']) ? $TSFE->fe_user->user['static_info_country']:$TSFE->fe_user->user['country']));
			$this->infoArray['billing']['agb'] = (isset($this->infoArray['billing']['agb']) ? $this->infoArray['billing']['agb'] : $TSFE->fe_user->user['agb']);

			$dateBirth = $this->infoArray['billing']['date_of_birth'];
			$tmpPos =  strpos($dateBirth,'-');
			if (!$dateBirth || $tmpPos === FALSE || $tmpPos == 0)	{
				if (t3lib_extMgm::isLoaded('sr_feuser_register')) {
					require_once(PATH_BE_srfeuserregister.'pi1/class.tx_srfeuserregister_pi1_adodb_time.php');

					// prepare for handling dates before 1970
					$adodbTime = &t3lib_div::getUserObj('&tx_srfeuserregister_pi1_adodb_time');
					$this->infoArray['billing']['date_of_birth'] = $adodbTime->adodb_date('d-m-Y', $TSFE->fe_user->user['date_of_birth']);
				} else {
					$this->infoArray['billing']['date_of_birth'] = date('d-m-Y', ($TSFE->fe_user->user['date_of_birth']));
				}
			}
			unset ($this->infoArray['billing']['error']);
			$this->overwriteMode = 1;
		}

		if ($bProductsPayment && isset($_REQUEST['recs']) && is_array($_REQUEST['recs']) &&
			isset($_REQUEST['recs']['personinfo']) && is_array($_REQUEST['recs']['personinfo']) && !$_REQUEST['recs']['personinfo']['agb'])	{
			$this->infoArray['billing']['agb'] = FALSE;
		}

		$this->bHasBeenInitialised = TRUE;
	} // init


	public function needsInit ()	{
		return !$this->bHasBeenInitialised;
	}


	public static function fixCountries (&$infoArray)	{
		$rc = FALSE;

		if (
			$infoArray['billing']['country_code'] != '' &&
			(
				$infoArray['delivery']['zip'] == '' ||
				($infoArray['delivery']['zip'] != '' && $infoArray['delivery']['zip'] == $infoArray['billing']['zip'])
			)
		)	{
			// a country change in the select box shall be copied
			$infoArray['delivery']['country_code'] = $infoArray['billing']['country_code'];
			$rc = TRUE;
		}
		return $rc;
	}


	/**
	 * Fills in all empty fields in the delivery info array
	 */
	public function mapPersonIntoDelivery ()	{

			// all of the delivery address will be overwritten when no address and no email address have been filled in
		if ((!trim($this->infoArray['delivery']['address']) && !trim($this->infoArray['delivery']['email']) || $this->overwriteMode) && !$this->bDeliveryAddress) {
			$fieldArray = t3lib_div::trimExplode(',',$this->feuserfields . ',feusers_uid');
			foreach($fieldArray as $k => $fName)	{
				if (
					isset($this->infoArray['billing'][$fName]) &&
					($this->infoArray['delivery'][$fName] == '')
				)	{
					$this->infoArray['delivery'][$fName] = $this->infoArray['billing'][$fName];
				}
			}
		}

			// Call info hooks
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['info'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['info'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'mapPersonIntoDelivery')) {
					$hookObj->mapPersonIntoDelivery($this);
				}
			}
		}
	} // mapPersonIntoDelivery


	/**
	 * Checks if required fields are filled in
	 */
	public function getRequiredInfoFields ($type)	{
		$paymentshippingObj = &t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
		$rc = '';
		$requiredInfoFieldArray = $this->conf['requiredInfoFields.'];
		if (isset($requiredInfoFieldArray) && is_array($requiredInfoFieldArray) && isset($requiredInfoFieldArray[$type]))	{
			$requiredInfoFields = $requiredInfoFieldArray[$type];
		} else {
			$requiredInfoFields = trim($this->conf['requiredInfoFields']);
		}
		$addRequiredInfoFields = $paymentshippingObj->getAddRequiredInfoFields();
		if ($addRequiredInfoFields != '')	{
			$requiredInfoFields .= ','.$addRequiredInfoFields;
		}
		return $requiredInfoFields;
	}


	/**
	 * Checks if required fields are filled in
	 */
	public function checkRequired ($type)	{

		if (!$this->bDeliveryAddress || $type == 'billing')	{
			$requiredInfoFields = $this->getRequiredInfoFields($type);

			if ($requiredInfoFields)	{

				$infoFields = t3lib_div::trimExplode(',',$requiredInfoFields);
				foreach($infoFields as $fName)	{

					if (trim($this->infoArray[$type][$fName]) == '')	{
						$rc = $fName;
						break;
					}
				}
			}
		}
		return $rc;
	} // checkRequired


	/**
	 * Checks if the filled in fields are allowed
	 */
	public function checkAllowed ()	{
		$rc = '';

		$where = $this->getWhereAllowedCountries();
		if ($where && $this->conf['useStaticInfoCountry'] && is_object($this->staticInfo))	{
			$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
			$countryObj = &$tablesObj->get('static_countries');
			if (is_object($countryObj))	{
				$type = ($this->bDeliveryAddress ? 'billing' : 'delivery');
				$row = $countryObj->isoGet($this->infoArray[$type]['country_code'], $where);
				if (!$row)	{
					$rc = 'country';
				}
			}
		}
		return $rc;
	} // checkAllowed


	/**
	 * gets the WHERE clause for the allowed static_countries
	 */
	public function getWhereAllowedCountries ()	{
		$where = '';

		if (is_object($this->staticInfo))	{
			$paymentshippingObj = &t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
			$where = $paymentshippingObj->getWhere('static_countries');
		}
		return $where;
	} // checkAllowed


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
	public function getRowMarkerArray (&$markerArray, $bSelectSalutation)	{
		global $TCA, $TSFE;

		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$infoFields = t3lib_div::trimExplode(',',$this->feuserfields); // Fields...
		foreach ($infoFields as $k => $fName)	{
			$fieldMarker = strtoupper($fName);
			if ($fName != 'salutation' && $fieldMarker != '')	{
				$markerArray['###PERSON_' . $fieldMarker . '###'] =
				$TSFE->csConv($this->infoArray['billing'][$fName],$TSFE->metaCharset);
				$markerArray['###DELIVERY_' . $fieldMarker . '###'] =
				$TSFE->csConv($this->infoArray['delivery'][$fName],$TSFE->metaCharset);
			}
		}

		if ($this->conf['useStaticInfoCountry'] && is_object($this->staticInfo))	{
			$bReady = FALSE;
			$whereCountries = $this->getWhereAllowedCountries();
			$countryCodeArray = array();
			$countryCodeArray['billing'] = ($this->infoArray['billing']['country_code'] ? $this->infoArray['billing']['country_code'] : $TSFE->fe_user->user['static_info_country']);
			$countryCodeArray['delivery'] = ($this->infoArray['delivery']['country_code'] ? $this->infoArray['delivery']['country_code'] : $TSFE->fe_user->user['static_info_country']);
			$zoneCodeArray = array();
			$zoneCodeArray['billing'] = ($this->infoArray['billing']['zone'] ? $this->infoArray['billing']['zone'] : $TSFE->fe_user->user['zone']);
			$zoneCodeArray['delivery'] = ($this->infoArray['delivery']['zone'] ? $this->infoArray['delivery']['zone'] : $TSFE->fe_user->user['zone']);
// 'zn_code', 'zn_country_iso_##'

			if (t3lib_extMgm::isLoaded('static_info_tables')) {
				$eInfo = tx_div2007_alpha::getExtensionInfo_fh001('static_info_tables');
				$sitVersion = $eInfo['version'];

				if (version_compare($sitVersion, '2.0.1', '>='))	{
					$markerArray['###PERSON_COUNTRY_CODE###'] =
						$this->staticInfo->buildStaticInfoSelector(
							'COUNTRIES',
							'recs[personinfo][country_code]',
							'',
							$countryCodeArray['billing'],
							'',
							$this->conf['onChangeCountryAttribute'],
							'',
							'',
							$whereCountries,
							'',
							FALSE,
							array(),
							1,
							$outSelectedArray
						);

					if (isset($outSelectedArray) && is_array($outSelectedArray))	{
						$markerArray['###PERSON_ZONE###'] =
							$this->staticInfo->buildStaticInfoSelector(
								'SUBDIVISIONS',
								'recs[personinfo][zone]',
								'',
								$zoneCodeArray['billing'],
								current($outSelectedArray),
								0,
								'',
								''
							);
					} else {
						$markerArray['###PERSON_ZONE###'] = '';
					}
					$countryArray = $this->staticInfo->initCountries('ALL','',FALSE,$whereCountries);
					$markerArray['###PERSON_COUNTRY_FIRST###'] = current($countryArray);
					$markerArray['###PERSON_COUNTRY_FIRST_HIDDEN###'] = '<input type="hidden" name="recs[personinfo][country_code]" size="3" value="'.current(array_keys($countryArray)).'">';

					$markerArray['###PERSON_COUNTRY###'] =
						$this->staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['billing'],'','');
					unset($outSelectedArray);
					$markerArray['###DELIVERY_COUNTRY_CODE###'] =
						$this->staticInfo->buildStaticInfoSelector(
							'COUNTRIES',
							'recs[delivery][country_code]',
							'',
							$countryCodeArray['delivery'],
							'',
							$this->conf['onChangeCountryAttribute'],
							'',
							'',
							$whereCountries,
							'',
							FALSE,
							array(),
							1,
							$outSelectedArray
						);

					if (isset($outSelectedArray) && is_array($outSelectedArray))	{
						$markerArray['###DELIVERY_ZONE###'] =
							$this->staticInfo->buildStaticInfoSelector(
								'SUBDIVISIONS',
								'recs[delivery][zone]',
								'',
								$zoneCodeArray['billing'],
								current($outSelectedArray),
								0,
								'',
								''
							);
					} else {
						$markerArray['###DELIVERY_ZONE###'] = '';
					}

					$markerArray['###DELIVERY_COUNTRY_FIRST###'] = $markerArray['###PERSON_COUNTRY_FIRST###'];
					$markerArray['###DELIVERY_COUNTRY###'] =
						$this->staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['delivery'],'','');
					$bReady = TRUE;
				}

				$markerArray['###PERSON_ZONE_DISPLAY###'] = tx_staticinfotables_div::getTitleFromIsoCode('static_country_zones', array($zoneCodeArray['billing'],$countryCodeArray['billing']));
				$markerArray['###DELIVERY_ZONE_DISPLAY###'] = tx_staticinfotables_div::getTitleFromIsoCode('static_country_zones', array($zoneCodeArray['delivery'],$countryCodeArray['delivery']));
			}

			if (!$bReady)	{
				$markerArray['###PERSON_COUNTRY_CODE###'] =
					$this->staticInfo->buildStaticInfoSelector(
						'COUNTRIES',
						'recs[personinfo][country_code]',
						'',
						$countryCodeArray['billing'],
						''
					);
				$markerArray['###PERSON_COUNTRY###'] =
					$this->staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['billing'],'','');
				$markerArray['###DELIVERY_COUNTRY_CODE###'] =
					$this->staticInfo->buildStaticInfoSelector(
						'COUNTRIES',
						'recs[delivery][country_code]',
						'',
						$countryCodeArray['delivery'],
						''
					);
				$markerArray['###DELIVERY_COUNTRY###'] =
					$this->staticInfo->getStaticInfoName(
						'COUNTRIES',
						$countryCodeArray['delivery'],
						'',
						''
					);
			}
		}

			// Markers for use if you want to output line-broken address information
		$markerArray['###PERSON_ADDRESS_DISPLAY###'] = nl2br($markerArray['###PERSON_ADDRESS###']);
		$markerArray['###DELIVERY_ADDRESS_DISPLAY###'] = nl2br($markerArray['###DELIVERY_ADDRESS###']);

		$orderAddressViewObj = &$tablesObj->get('fe_users',TRUE);
		$orderAddressViewObj->getRowMarkerArray($this->infoArray['billing'], $markerArray, $bSelectSalutation,'personinfo');
		$orderAddressViewObj->getRowMarkerArray($this->infoArray['delivery'], $markerArray, $bSelectSalutation,'delivery');

		$text = $TSFE->csConv($this->infoArray['delivery']['note'],$TSFE->metaCharset);
		$markerArray['###DELIVERY_NOTE###'] = $text;
		$markerArray['###DELIVERY_NOTE_DISPLAY###'] = nl2br($text);
		$markerArray['###DELIVERY_GIFT_SERVICE###'] = $this->infoArray['delivery']['giftservice'];
		$markerArray['###DELIVERY_GIFT_SERVICE_DISPLAY###'] = nl2br($this->infoArray['delivery']['giftservice']);
		$markerArray['###DELIVERY_RADIO1_1###'] = ($this->infoArray['delivery']['radio1'] == '1' ? 'checked ' : '');
		$markerArray['###DELIVERY_RADIO1_2###'] = ($this->infoArray['delivery']['radio1'] == '2' ? 'checked ' : '');
		$markerArray['###DELIVERY_RADIO1_DISPLAY###'] = $this->infoArray['delivery']['radio1'];

			// Desired delivery date.
		$markerArray['###DELIVERY_DESIRED_DATE###'] = $this->infoArray['delivery']['desired_date'];
		$markerArray['###DELIVERY_DESIRED_TIME###'] = $this->infoArray['delivery']['desired_time'];
		$markerArray['###DELIVERY_STORE_SELECT###'] = '';
		if ($this->bDeliveryAddress)	{
			$addressObj = &$tablesObj->get('address',FALSE);
			$tablename = $addressObj->getTablename();
			$tableconf = $cnf->getTableConf('address', 'INFO');
			$orderBy = $tableconf['orderBy'];
			$addressArray = $addressObj->get('',0,FALSE,'','','',FALSE,'',$orderBy);

			if (isset($this->conf['UIDstore']))	{
				$uidStoreArray = t3lib_div::trimExplode(',',$this->conf['UIDstore']);
				if (is_array($uidStoreArray))	{
					$actUidStore = $this->infoArray['delivery']['store'];
					$tableFieldArray = array(
						'tx_party_addresses' => array('post_code','locality','remarks'),
						'tt_address' => array('zip','city','name','address'),
						'fe_users' => array('zip','city','name','address')
					);
					$valueArray = array();
					if (isset($tableFieldArray[$tablename]) && is_array($tableFieldArray[$tablename]))	{
						foreach ($addressArray as $uid => $row)	{

							if (in_array($uid, $uidStoreArray))	{
								$partRow = array();
								foreach ($tableFieldArray[$tablename] as $field)	{
									$partRow[$field] = $row[$field];
								}
								$valueArray[$uid] = implode(',',$partRow);
							}
						}
					}
					include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_form_div.php');
					$markerArray['###DELIVERY_STORE_SELECT###'] =
						tx_ttproducts_form_div::createSelect($this->pibase, $valueArray, 'recs[delivery][store]', $actUidStore);

					if ($actUidStore)	{
						$row = $addressArray[$actUidStore];
						foreach ($row as $field => $value)	{
							$markerArray['###DELIVERY_' . strtoupper($field) . '###'] = $value;
						}
					}
				}
			}
		}

			// Fe users:
		$markerArray['###FE_USER_TT_PRODUCTS_DISCOUNT###'] = $TSFE->fe_user->user['tt_products_discount'];
		$markerArray['###FE_USER_USERNAME###'] = $TSFE->fe_user->user['username'];
		$markerArray['###FE_USER_UID###'] = $TSFE->fe_user->user['uid'];
		$bAgb = ($this->infoArray['billing']['agb'] && (!isset($this->pibase->piVars['agb']) || $this->pibase->piVars['agb']>0));

		$markerArray['###PERSON_AGB###'] = 'value="1" '. ($bAgb ? 'checked="checked"' : '');
		$markerArray['###USERNAME###'] = $this->infoArray['billing']['email'];
		$markerArray['###PASSWORD###'] = $this->password;
		$valueArray = $TCA['sys_products_orders']['columns']['foundby']['config']['items'];

		unset($valueArray[0]);
		$foundbyText = tx_ttproducts_form_div::createSelect (
			$this->pibase,
			$valueArray,
			'recs[delivery][foundby]',
			$this->infoArray['delivery']['foundby'],
			TRUE,
			TRUE,
			array(),
			'radio'
		);

		$foundbyKey = $this->infoArray['delivery']['foundby'];
		if (is_array($valueArray[$foundbyKey]))	{
			$tmp = tx_div2007_alpha::sL_fh001($valueArray[$foundbyKey][0]);
			$text = tx_div2007_alpha::getLL($this->pibase, $tmp);
		}

		$markerArray['###DELIVERY_FOUNDBY###'] = $text;
		$markerArray['###DELIVERY_FOUNDBY_SELECTOR###'] = $foundbyText;
		$markerArray['###DELIVERY_FOUNDBY_OTHERS###'] = $this->infoArray['delivery']['foundby_others'];
	} // getMarkerArray
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_info_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_info_view.php']);
}


?>
