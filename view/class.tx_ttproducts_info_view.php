<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2014 Franz Holzinger <franz@ttproducts.de>
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


class tx_ttproducts_info_view {
	var $pibase; // reference to object of pibase
	var $conf;
	var $config;
	var $infoArray; // elements: 'billing' and 'delivery' addresses
			// contains former basket $personInfo and $deliveryInfo

	var $feuserextrafields;		// exension with additional fe_users fields
	var $country;			// object of the type tx_table_db
	var $password;	// automatically generated random password for a new frontend user
	var $feuserfields;
	var $creditpointfields;
	var $overwriteMode = 0;
	var $bDeliveryAddress = FALSE;	// normally the delivery is copied from the bill data. But also another table can be used for it.

	/**
	 * Getting all tt_products_cat categories into internal array
	 *
	 * @param	[type]		$$pibase: ...
	 * @param	[type]		$formerBasket: ...
	 * @param	[type]		$bProductsPayment: ...
	 * @return	[type]		...
	 */
	function init ($pibase, $formerBasket, $bProductsPayment, $fixCountry)  {
		global $TYPO3_DB,$TSFE,$TCA;

		$this->pibase = $pibase;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$paymentshippingObj = t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');

		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		$this->infoArray = array();

		if (isset($formerBasket) && is_array($formerBasket)) {
			$this->infoArray['billing'] = $formerBasket['personinfo'];
			$this->infoArray['delivery'] = $formerBasket['delivery'];
		}
		if (!$this->infoArray['billing']) {
			$this->infoArray['billing'] = array();
		}
		if (!$this->infoArray['delivery']) {
			$this->infoArray['delivery'] = array();
		}

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

		$this->feuserfields = 'name,cnum,first_name,last_name,username,email,telephone,title,salutation,address,telephone,fax,email,company,city,zip,state,country,country_code,tt_products_vat,date_of_birth,tt_products_business_partner,tt_products_organisation_form';
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

		$requiredInfoFields = $this->getRequiredInfoFields();
		$checkField = '';
		$possibleCheckFieldArray = array('name', 'last_name', 'email', 'telephone');
		foreach ($possibleCheckFieldArray as $possibleCheckField) {
			if (t3lib_div::inList($requiredInfoFields, $possibleCheckField)) {
				$checkField = $possibleCheckField;
				break;
			}
		}

		tx_ttproducts_static_info::init();
		$staticInfo = tx_ttproducts_static_info::getStaticInfo();

		if ($this->conf['useStaticInfoCountry'] && $this->infoArray['billing']['country_code'] && is_object($staticInfo))	{
			$this->infoArray['billing']['country'] = $staticInfo->getStaticInfoName('COUNTRIES', $this->infoArray['billing']['country_code'],'','');
			if ($this->infoArray['delivery'][$checkField] && !$this->bDeliveryAddress)	{
				$this->infoArray['delivery']['country'] = $staticInfo->getStaticInfoName('COUNTRIES', $this->infoArray['delivery']['country_code'],'','');
			}

			if ($fixCountry) {
				$bFixCountries = self::fixCountries($this->infoArray);
			}
			if (
				!$bFixCountries &&
				$this->infoArray['delivery'][$checkField] &&
				!$this->bDeliveryAddress
			)	{
				$this->infoArray['delivery']['country'] = $staticInfo->getStaticInfoName('COUNTRIES', $this->infoArray['delivery']['country_code'],'','');
			}
		}
		if (isset($this->infoArray) && is_array($this->infoArray) && is_array($this->infoArray['delivery']) && !$this->infoArray['delivery'][$checkField])	{
			unset($this->infoArray['delivery']['country_code']);
			unset($this->infoArray['delivery']['salutation']);
			unset($this->infoArray['delivery']['tt_products_business_partner']);
			unset($this->infoArray['delivery']['tt_products_organisation_form']);
		}

		if (
			$TSFE->loginUser &&
			(!$this->infoArray['billing'] || !$this->infoArray['billing'][$checkField] || $this->conf['editLockedLoginInfo'] || $this->infoArray['billing']['error']) &&
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
			$this->infoArray['billing']['country'] = ($this->infoArray['billing']['country'] ? $this->infoArray['billing']['country'] : (($this->conf['useStaticInfoCountry'] || !$TSFE->fe_user->user['country']) ? $TSFE->fe_user->user['static_info_country']:$TSFE->fe_user->user['country']));
			$this->infoArray['billing']['agb'] = (isset($this->infoArray['billing']['agb']) ? $this->infoArray['billing']['agb'] : $TSFE->fe_user->user['agb']);

			$dateBirth = $this->infoArray['billing']['date_of_birth'];
			$tmpPos =  strpos($dateBirth,'-');

			if (!$dateBirth || $tmpPos === FALSE || $tmpPos == 0)	{
				$this->infoArray['billing']['date_of_birth'] = date( 'd-m-Y', ($TSFE->fe_user->user['date_of_birth']));
			}

			unset ($this->infoArray['billing']['error']);
			$this->overwriteMode = 1;
		}

		if ($bProductsPayment && !$_REQUEST['recs']['personinfo']['agb'])	{
			$this->infoArray['billing']['agb'] = FALSE;
		}
	} // init


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
	 *
	 * @return	[type]		...
	 */
	function mapPersonIntoDelivery ()	{
		global $TCA;

		$requiredInfoFields = $this->getRequiredInfoFields();

			// all of the delivery address will be overwritten when one of the required fields have not been filled in
		$bMissingField = FALSE;
		$requiredInfoFieldArray = t3lib_div::trimExplode(',', $requiredInfoFields);

		if (!$this->bDeliveryAddress)	{
			foreach ($requiredInfoFieldArray as $field)	{
				if (
					is_array($this->infoArray['delivery']) &&
					!trim($this->infoArray['delivery'][$field])
				) {
					$bMissingField = TRUE;
					break;
				}
			}
		}

		if ($bMissingField || $this->overwriteMode && !$this->bDeliveryAddress) {
			$fieldArray = t3lib_div::trimExplode(',',$this->feuserfields);

			foreach($fieldArray as $k => $fName) {
				if (
					isset($this->infoArray['billing'][$fName]) &&
					(
						$this->infoArray['delivery'][$fName] == '' ||
						(
							$this->infoArray['delivery'][$fName] == '0' &&
							!trim($this->infoArray['delivery']['address'])
						)
					)
				) {
					$this->infoArray['delivery'][$fName] = $this->infoArray['billing'][$fName];
				}
			}
		}
	} // mapPersonIntoDelivery


	/**
	 * Checks if required fields are filled in
	 *
	 * @return	[type]		...
	 */
	function getRequiredInfoFields ()	{
		$paymentshippingObj = t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
		$rc = '';
		$requiredInfoFields = trim($this->conf['requiredInfoFields']);
		$addRequiredInfoFields = $paymentshippingObj->getAddRequiredInfoFields();
		if ($addRequiredInfoFields != '')	{
			$requiredInfoFields .= ','.$addRequiredInfoFields;
		}
		return $requiredInfoFields;
	}


	/**
	 * Checks if required fields are filled in
	 *
	 * @param	[type]		$type: ...
	 * @return	[type]		...
	 */
	function checkRequired ($type='')	{

		$requiredInfoFields = $this->getRequiredInfoFields();

		if ($requiredInfoFields)	{
			$bBillingTo = true;
			$infoFields = t3lib_div::trimExplode(',',$requiredInfoFields);

			foreach($infoFields as $k => $fName)	{
				$bBillingTo &= !empty($this->infoArray['billing'][$fName]);
			}
			foreach($infoFields as $k => $fName)	{
				if (
					(
						is_array($this->infoArray['billing']) &&
						trim($this->infoArray['billing'][$fName]) == ''
					) ||
					(
						$type != 'billing' &&
						$bBillingTo &&
						is_array($this->infoArray['delivery']) &&
						trim($this->infoArray['delivery'][$fName]) == ''
					)
				) {
					$rc = $fName;
					break;
				}
			}
		}

		return $rc;
	} // checkRequired


	/**
	 * Checks if the filled in fields are allowed
	 *
	 * @return	[type]		...
	 */
	function checkAllowed ()	{
		$rc = '';

		$staticInfo = tx_ttproducts_static_info::getStaticInfo();

		$where = $this->getWhereAllowed();
		if ($where && $this->conf['useStaticInfoCountry'] && is_object($staticInfo))	{
			$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
			$countryObj = $tablesObj->get('static_countries');
			if (is_object($countryObj))	{
				$type = ($this->bDeliveryAddress ? 'billing' : 'delivery');
				$row = $countryObj->get($this->infoArray[$type]['country_code'], $where);
				if (!$row)	{
					$rc = 'country';
				}
			}
		}
		return $rc;
	} // checkAllowed


	/**
	 * gets the WHERE clause for the allowed static_countries
	 *
	 * @return	[type]		...
	 */
	function getWhereAllowed ()	{
		$where = '';
		$staticInfo = tx_ttproducts_static_info::getStaticInfo();

		if (is_object($staticInfo))	{
			$paymentshippingObj = t3lib_div::getUserObj('&tx_ttproducts_paymentshipping');
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
	function getItemMarkerArray (&$markerArray, $bSelectSalutation)	{
		global $TCA, $TSFE;

		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$infoFields = t3lib_div::trimExplode(',',$this->feuserfields); // Fields...
		$orderAddressViewObj = $tablesObj->get('fe_users',true);
		$orderAddressObj = $orderAddressViewObj->getModelObj();
		$selectInfoFields = $orderAddressObj->getSelectInfoFields();
		$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');
		$staticInfo = tx_ttproducts_static_info::getStaticInfo();

		foreach ($infoFields as $k => $fName) {
			if (!in_array($fName, $selectInfoFields)) {
				$fieldMarker = strtoupper($fName);
				$markerArray['###PERSON_' . $fieldMarker . '###'] =
					$TSFE->csConv($this->infoArray['billing'][$fName], $TSFE->metaCharset);
				$markerArray['###DELIVERY_' . $fieldMarker . '###'] =
					$TSFE->csConv($this->infoArray['delivery'][$fName], $TSFE->metaCharset);
			}
		}

		if ($this->conf['useStaticInfoCountry'] && is_object($staticInfo))	{
			$bReady = FALSE;
			$whereCountries = $this->getWhereAllowed();
			$countryCodeArray = array();
			$countryCodeArray['billing'] = ($this->infoArray['billing']['country_code'] ? $this->infoArray['billing']['country_code'] : $TSFE->fe_user->user['static_info_country']);
			$countryCodeArray['delivery'] = ($this->infoArray['delivery']['country_code'] ? $this->infoArray['delivery']['country_code'] : $TSFE->fe_user->user['static_info_country']);

			if (t3lib_extMgm::isLoaded('static_info_tables')) {
				$eInfo = tx_div2007_alpha5::getExtensionInfo_fh003('static_info_tables');
				$sitVersion = $eInfo['version'];

				if (version_compare($sitVersion, '2.0.1', '>='))	{
					$markerArray['###PERSON_COUNTRY_CODE###'] =
						$staticInfo->buildStaticInfoSelector('COUNTRIES', 'recs[personinfo][country_code]', '', $countryCodeArray['billing'], '', 0, '', '', $whereCountries);
					$countryArray = $staticInfo->initCountries('ALL','',FALSE,$whereCountries);
					$markerArray['###PERSON_COUNTRY_FIRST###'] = current($countryArray);
					$markerArray['###PERSON_COUNTRY_FIRST_HIDDEN###'] = '<input type="hidden" name="recs[personinfo][country_code]" size="3" value="'.current(array_keys($countryArray)).'">';
					$markerArray['###PERSON_COUNTRY###'] =
						$staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['billing'], '', '');
					$markerArray['###DELIVERY_COUNTRY_CODE###'] =
						$staticInfo->buildStaticInfoSelector('COUNTRIES', 'recs[delivery][country_code]', '', $countryCodeArray['delivery'], '', 0, '', '', $whereCountries);
					$markerArray['###DELIVERY_COUNTRY_FIRST###'] = $markerArray['###PERSON_COUNTRY_FIRST###'];
					$markerArray['###DELIVERY_COUNTRY###'] =
						$staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['delivery'],'','');
					$bReady = TRUE;
				}
			}

			if (!$bReady)	{
				$markerArray['###PERSON_COUNTRY_CODE###'] =
					$staticInfo->buildStaticInfoSelector('COUNTRIES', 'recs[personinfo][country_code]', '', $countryCodeArray['billing'],'');
				$markerArray['###PERSON_COUNTRY###'] =
					$staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['billing'],'','');
				$markerArray['###DELIVERY_COUNTRY_CODE###'] =
					$staticInfo->buildStaticInfoSelector('COUNTRIES', 'recs[delivery][country_code]', '', $countryCodeArray['delivery'],'');
				$markerArray['###DELIVERY_COUNTRY###'] =
					$staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['delivery'],'','');
			}
		}

			// Markers for use if you want to output line-broken address information
		$markerArray['###PERSON_ADDRESS_DISPLAY###'] = nl2br($markerArray['###PERSON_ADDRESS###']);
		$markerArray['###DELIVERY_ADDRESS_DISPLAY###'] = nl2br($markerArray['###DELIVERY_ADDRESS###']);

		$orderAddressViewObj->getItemMarkerArray($this->infoArray['billing'], $markerArray, $bSelectSalutation,'personinfo');
		$orderAddressViewObj->getItemMarkerArray($this->infoArray['delivery'], $markerArray, $bSelectSalutation,'delivery');

		$text = $TSFE->csConv($this->infoArray['delivery']['note'], $TSFE->metaCharset);
		$markerArray['###DELIVERY_NOTE###'] = $text;
		$markerArray['###DELIVERY_NOTE_DISPLAY###'] = nl2br($text);

		$markerArray['###DELIVERY_GIFT_SERVICE###'] = $this->infoArray['delivery']['giftservice'];
		$markerArray['###DELIVERY_GIFT_SERVICE_DISPLAY###'] = nl2br($this->infoArray['delivery']['giftservice']);

		$markerArray['###DELIVERY_RADIO1_1###'] = ($this->infoArray['delivery']['radio1'] == '1' ? 'checked ' : '');
		$markerArray['###DELIVERY_RADIO1_2###'] = ($this->infoArray['delivery']['radio1'] == '2' ? 'checked ' : '');
		$markerArray['###DELIVERY_RADIO1_DISPLAY###'] = $this->infoArray['delivery']['radio1'];

			// Desired delivery date.
		$markerArray['###DELIVERY_DESIRED_DATE###'] = $this->infoArray['delivery']['desired_date'];
		$markerArray['###DELIVERY_STORE_SELECT###'] = '';

		if ($this->bDeliveryAddress)	{
			$addressObj = $tablesObj->get('address',FALSE);
			if (is_object($addressObj)) {
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
							'tt_address' => array('zip','city','name','address')
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
		}

			// Fe users:
		$markerArray['###FE_USER_TT_PRODUCTS_DISCOUNT###'] = $TSFE->fe_user->user['tt_products_discount'];
		$markerArray['###FE_USER_USERNAME###'] = $TSFE->fe_user->user['username'];
		$markerArray['###FE_USER_UID###'] = $TSFE->fe_user->user['uid'];
		$markerArray['###FE_USER_CNUM###'] = $TSFE->fe_user->user['cnum'];
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
				true,
				true,
				array(),
				'radio'
			);

		$foundbyKey = $this->infoArray['delivery']['foundby'];
		if (is_array($valueArray[$foundbyKey]))	{
			$tmp = tx_div2007_alpha5::sL_fh002($valueArray[$foundbyKey][0]);
			$text = tx_div2007_alpha5::getLL_fh002($langObj, $tmp);
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