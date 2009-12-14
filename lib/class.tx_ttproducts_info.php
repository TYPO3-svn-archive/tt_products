<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2009 Franz Holzinger <franz@ttproducts.de>
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
 * functions for the info addresses
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


class tx_ttproducts_info {
	var $pibase; // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $infoArray; // elements: 'billing' and 'delivery' addresses
			// contains former basket $personInfo and $deliveryInfo

	var $feuserextrafields;			// exension with additional fe_users fields
	var $fe_users;				// object of the type tx_table_db
	var $country;				// object of the type tx_table_db
	var $password;		// automatically generated random password for a new frontend user
	var $staticInfo; 	// object for the static_info_tables extension
	var $paymentshipping; // paymentshipping object
	var $feuserfields;
	var $creditpointfields;
	var $bDeliveryAddress = FALSE;	// normally the delivery is copied from the bill data. But also another table can be used for it.


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init (&$pibase, &$cnf, $formerBasket, &$fe_users, &$paymentshipping, $bProductsPayment=FALSE)  {
		global $TYPO3_DB,$TSFE,$TCA,$TYPO3_CONF_VARS;;

		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;
		$this->fe_users = &$fe_users;
		$this->paymentshipping = &$paymentshipping;
		$this->infoArray = array();
		$this->infoArray['billing'] = $formerBasket['personinfo'];
		$this->infoArray['delivery'] = $formerBasket['delivery'];
		$shippingType = $this->paymentshipping->get('shipping', 'type');
		if ($shippingType == 'pick_store')	{
			$this->bDeliveryAddress = TRUE;
		}

		$allowedTags = '<br><a><b><td><tr><div>';
		foreach ($this->infoArray as $type => $infoRowArray)	{
			if (is_array($infoRowArray))	{
				foreach ($infoRowArray as $k => $infoRow)	{
					$this->infoArray[$type][$k] = strip_tags ($infoRow, $allowedTags);
				}
			} else {
				$this->infoArray[$type] = strip_tags ($infoRowArray, $allowedTags);
			}
		}

		$this->feuserfields = 'name,cnum,first_name,last_name,username,email,telephone,salutation,title,address,fax,email,company,city,zip,state,country,tt_products_vat,date_of_birth';
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

		if (t3lib_extMgm::isLoaded('static_info_tables')) {
			$path = t3lib_extMgm::extPath('static_info_tables');
			$eInfo = tx_div2007_alpha::getExtensionInfo_fh001('static_info_tables');

			if (is_array($eInfo))	{
				$sitVersion = $eInfo['version'];

				if (version_compare($sitVersion, '2.0.0', '>='))	{
					include_once($path.'pi1/class.tx_staticinfotables_pi1.php');
					// Initialise static info library
					$this->staticInfo = t3lib_div::makeInstance('tx_staticinfotables_pi1');
					$this->staticInfo->init();
				} else if (t3lib_extMgm::isLoaded('sr_static_info')) {
					include_once(t3lib_extMgm::extPath('sr_static_info').'pi1/class.tx_srstaticinfo_pi1.php');
					// Initialise static info library
					$this->staticInfo = t3lib_div::makeInstance('tx_srstaticinfo_pi1');
					$this->staticInfo->init();
				}
			} else	{
				echo $eInfo;
			}

			if (is_object($this->staticInfo))	{
				include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_country.php');

					// Country
				$this->country = t3lib_div::makeInstance('tx_ttproducts_country');
				$this->country->init(
					$this->pibase,
					$this->cnf,
					$this->pibase->LLkey,
					'static_countries'
				);
			}
		}

		if ($this->conf['useStaticInfoCountry'] && $this->infoArray['billing']['country_code'] && is_object($this->staticInfo))	{
			$this->infoArray['billing']['country'] = $this->staticInfo->getStaticInfoName('COUNTRIES', $this->infoArray['billing']['country_code'],'','');
			if ($this->infoArray['delivery']['name'] && !$this->bDeliveryAddress)	{
				$this->infoArray['delivery']['country'] = $this->staticInfo->getStaticInfoName('COUNTRIES', $this->infoArray['delivery']['country_code'],'','');
			}
		}

		if ($TSFE->loginUser && (!$this->infoArray['billing'] || !$this->infoArray['billing']['name'] || $this->conf['editLockedLoginInfo']) && $this->conf['lockLoginUserInfo'])	{
			$address = '';

			$this->infoArray['billing']['feusers_uid'] = $TSFE->fe_user->user['uid'];

			if ($this->conf['useStaticInfoCountry'] && !$this->infoArray['billing']['country_code'])	{
				$this->infoArray['billing']['country_code'] = $TSFE->fe_user->user['static_info_country'];
				if (!$this->bDeliveryAddress)	{
					$this->infoArray['delivery']['country_code'] = $TSFE->fe_user->user['static_info_country'];
				}
			}
			if ($this->conf['loginUserInfoAddress']) {
				$address = implode(chr(10),
					t3lib_div::trimExplode(chr(10),
						$TSFE->fe_user->user['address'].chr(10).
						$TSFE->fe_user->user['zip'].' '.$TSFE->fe_user->user['city'].chr(10).
						($this->conf['useStaticInfoCountry'] ? $TSFE->fe_user->user['static_info_country']:$TSFE->fe_user->user['country'])
						,1)
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
			$this->infoArray['billing']['country'] = ($this->infoArray['billing']['country'] ? $this->infoArray['billing']['country'] : ($this->conf['useStaticInfoCountry'] ? $TSFE->fe_user->user['static_info_country']:$TSFE->fe_user->user['country']));
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
					$this->infoArray['billing']['date_of_birth'] = date( 'd-m-Y', ($TSFE->fe_user->user['date_of_birth']));
				}
			}
		}
		if ($bProductsPayment && !$_REQUEST['recs']['personinfo']['agb'])	{
			$this->infoArray['billing']['agb'] = FALSE;
		}
	} // init

	/**
	 * Fills in all empty fields in the delivery info array
	 */
	function mapPersonIntoDelivery ()	{
		global $TCA;

			// all of the delivery address will be overwritten when no address and no email address have been filled in
		if (!trim($this->infoArray['delivery']['address']) && !trim($this->infoArray['delivery']['email']) && !$this->bDeliveryAddress) {
			$fieldArray = t3lib_div::trimExplode(',',$this->feuserfields);
			foreach($fieldArray as $fName)	{
				$this->infoArray['delivery'][$fName] = $this->infoArray['billing'][$fName];
			}
		}
	} // mapPersonIntoDelivery

	/**
	 * Checks if required fields are filled in
	 */
	function checkRequired ($type='')	{
		$rc = '';
		$requiredInfoFields = trim($this->conf['requiredInfoFields']);
		$addRequiredInfoFields = $this->paymentshipping->getAddRequiredInfoFields();
		if ($addRequiredInfoFields != '')
			$requiredInfoFields .= ','.$addRequiredInfoFields;

		if ($requiredInfoFields)	{
			$infoFields = t3lib_div::trimExplode(',',$requiredInfoFields);
			// Check if the required billing fields ar filled.
			$bBillingTo = true;
			foreach($infoFields as $fName)    {
				$bBillingTo &= !empty($this->infoArray['billing'][$fName]);
			}
			foreach($infoFields as $fName)	{
				if (trim($this->infoArray['billing'][$fName])=='' || ($type != 'billing') && !$this->bDeliveryAddress && trim($this->infoArray['delivery'][$fName])=='' && $bBillingTo)	{
					$rc=$fName;
					break;
				}
			}
		}
		return $rc;
	} // checkRequired

	/**
	 * Checks if the filled in fields are allowed
	 */
	function checkAllowed ()	{
		$rc = '';

		$where = $this->getWhereAllowed();
		if ($where && $this->conf['useStaticInfoCountry'])	{
			$type = ($this->bDeliveryAddress ? 'billing' : 'delivery');
			$row = $this->country->get($this->infoArray[$type]['country_code'], $where);
			if (!$row)	{
				$rc = 'country';
			}
		}
		return $rc;
	} // checkAllowed

	/**
	 * gets the WHERE clause for the allowed static_countries
	 */
	function getWhereAllowed ()	{
		$where = '';

		if (is_object($this->staticInfo))	{
			$where = $this->paymentshipping->getWhere('static_countries');
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
	function getItemMarkerArray (&$markerArray, $bSelectSalutation)	{
		global $TCA, $TSFE;
		global $TYPO3_CONF_VARS;

		$infoFields = t3lib_div::trimExplode(',',$this->feuserfields); // Fields...
		reset($infoFields);
		while(list(,$fName)=each($infoFields))	{
			if ($fName != 'salutation')	{
				$markerArray['###PERSON_' . strtoupper($fName) . '###'] = $this->infoArray['billing'][$fName];
				$markerArray['###DELIVERY_' . strtoupper($fName) . '###'] = $this->infoArray['delivery'][$fName];
			}
		}

		if ($this->conf['useStaticInfoCountry'] && is_object($this->staticInfo))	{
			$bReady = FALSE;
			$whereCountries = $this->getWhereAllowed();
			$countryCodeArray = array();
			$countryCodeArray['billing'] = ($this->infoArray['billing']['country_code'] ? $this->infoArray['billing']['country_code'] : $TSFE->fe_user->user['static_info_country']);
			$countryCodeArray['delivery'] = ($this->infoArray['delivery']['country_code'] ? $this->infoArray['delivery']['country_code'] : $TSFE->fe_user->user['static_info_country']);
			if (t3lib_extMgm::isLoaded('static_info_tables')) {
				$eInfo = tx_div2007_alpha::getExtensionInfo_fh001('static_info_tables');
				$sitVersion = $eInfo['version'];

				if (version_compare($sitVersion, '2.0.1', '>='))	{
					$markerArray['###PERSON_COUNTRY_CODE###'] =
						$this->staticInfo->buildStaticInfoSelector('COUNTRIES', 'recs[personinfo][country_code]', '', $countryCodeArray['billing'], '', 0, '', '', $whereCountries);
					$countryArray = $this->staticInfo->initCountries('ALL','',false,$whereCountries);
					$markerArray['###PERSON_COUNTRY_FIRST###'] = current($countryArray);
					$markerArray['###PERSON_COUNTRY_FIRST_HIDDEN###'] = '<input type="hidden" name="recs[personinfo][country_code]" size="3" value="'.current(array_keys($countryArray)).'">';
					$markerArray['###PERSON_COUNTRY###'] =
						$this->staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['billing'],'','');
					$markerArray['###DELIVERY_COUNTRY_CODE###'] =
						$this->staticInfo->buildStaticInfoSelector('COUNTRIES', 'recs[delivery][country_code]', '', $countryCodeArray['delivery'], '', 0, '', '', $whereCountries);
					$markerArray['###DELIVERY_COUNTRY_FIRST###'] = $markerArray['###PERSON_COUNTRY_FIRST###'];
					$markerArray['###DELIVERY_COUNTRY###'] =
						$this->staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['delivery'],'','');
					$bReady = TRUE;
				}
			}

			if (!$bReady)	{
				$markerArray['###PERSON_COUNTRY_CODE###'] =
					$this->staticInfo->buildStaticInfoSelector('COUNTRIES', 'recs[personinfo][country_code]', '', $countryCodeArray['billing'],'');
				$markerArray['###PERSON_COUNTRY###'] =
					$this->staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['billing'],'','');
				$markerArray['###DELIVERY_COUNTRY_CODE###'] =
					$this->staticInfo->buildStaticInfoSelector('COUNTRIES', 'recs[delivery][country_code]', '', $countryCodeArray['delivery'],'');
				$markerArray['###DELIVERY_COUNTRY###'] =
					$this->staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['delivery'],'','');
			}
		}

			// Markers for use if you want to output line-broken address information
		$markerArray['###PERSON_ADDRESS_DISPLAY###'] = nl2br($markerArray['###PERSON_ADDRESS###']);
		$markerArray['###DELIVERY_ADDRESS_DISPLAY###'] = nl2br($markerArray['###DELIVERY_ADDRESS###']);

		$this->fe_users->getItemMarkerArray($this->infoArray['billing'], $markerArray, $bSelectSalutation,'person');
		$this->fe_users->getItemMarkerArray($this->infoArray['delivery'], $markerArray, $bSelectSalutation,'delivery');

			// Delivery note.
		$markerArray['###DELIVERY_NOTE###'] = $this->infoArray['delivery']['note'];
		$markerArray['###DELIVERY_NOTE_DISPLAY###'] = nl2br($markerArray['###DELIVERY_NOTE###']);

			// Desired delivery date.
		$markerArray['###DELIVERY_DESIRED_DATE###'] = $this->infoArray['delivery']['desired_date'];
		$markerArray['###DELIVERY_STORE_SELECT###'] = '';
		if ($this->bDeliveryAddress)	{
			include_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_address.php');
			$addressObj = t3lib_div::makeInstance('tx_ttproducts_address');
			$addressObj->init(
				$this->pibase,
				$this->cnf,
				$this->conf['table.']['address']
			);
			$tableconf = $this->cnf->getTableConf('address', 'INFO');
			$orderBy = $tableconf['orderBy'];
			$addressArray = $addressObj->get(0,0,FALSE,$orderBy);
			if (isset($this->conf['UIDstore']))	{
				$uidStoreArray = t3lib_div::trimExplode(',',$this->conf['UIDstore']);
				if (is_array($uidStoreArray))	{
					$actUidStore = $this->infoArray['delivery']['store'];
					$valueArray = array();
					foreach ($addressArray as $uid => $row)	{

						if (in_array($uid, $uidStoreArray))	{
							$valueArray[$uid] = implode(',',array($row['zip'],$row['city'],$row['name'],$row['address']));
						}
					}
					include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_form_div.php');

					$markerArray['###DELIVERY_STORE_SELECT###'] =
						tx_ttproducts_form_div::createSelect($this->pibase, $valueArray, 'recs[delivery][store]', $actUidStore, array());

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
		$markerArray['###PERSON_AGB###'] = 'value="1" ' . ($bAgb ? 'checked="checked"' : '');
		$markerArray['###USERNAME###'] = $this->infoArray['billing']['email'];
		$markerArray['###PASSWORD###'] = $this->password;
	} // getMarkerArray
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_info.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_info.php']);
}


?>
