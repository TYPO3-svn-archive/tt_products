<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012-2012 Franz Holzinger <franz@ttproducts.de>
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
 * data functions for the customer.
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */



class tx_ttproducts_control_session {

	static public function filterExtensionData ($session) {

		$result = '';
		if (is_array($session) && isset($session['tt_products'])) {
			$result = $session['tt_products'];
		}
		return $result;
	}

	/*************************************
	* FE USER SESSION DATA HANDLING
	*************************************/
	/**
	* Retrieves session data
	*
	* @param	boolean	$readAll: whether to retrieve all session data or only data for this extension key
	* @return	array	session data
	*/
	static public function readSessionData ($readAll = FALSE) {
		$sessionData = array();
		$extKey = TT_PRODUCTS_EXT;
		$allSessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'feuser');
		if (isset($allSessionData) && is_array($allSessionData)) {
			if ($readAll) {
				$sessionData = $allSessionData;
			} else if (isset($allSessionData[$extKey])) {
				$sessionData = $allSessionData[$extKey];
			}
		}
		return $sessionData;
	}

	/**
	* Writes data to FE user session data
	*
	* @param	array	$data: the data to be written to FE user session data
	* @param	boolean	$keepToken: whether to keep any token
	* @param	boolean	$keepRedirectUrl: whether to keep any redirectUrl
	* @return	array	session data
	*/
	static public function writeSessionData (
		array $data
	) {
		$clearSession = empty($data);
		$extKey = TT_PRODUCTS_EXT;
			// Read all session data
		$allSessionData = self::readSessionData(TRUE);

		if (is_array($allSessionData[$extKey])) {
			$keys = array_keys($allSessionData[$extKey]);
			if ($clearSession) {
				foreach ($keys as $key) {
					unset($allSessionData[$extKey][$key]);
				}
			} else {
				$typo3Version = class_exists('t3lib_utility_VersionNumber') ? t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) : t3lib_div::int_from_ver(TYPO3_version);
				if ($typo3Version < 4007000) {
					foreach ($keys as $key) {
						if ($data[$key] == '__UNSET') {
							unset($data[$key]);
							unset($allSessionData[$extKey][$key]);
						}
					}
				}
			}

			$allSessionData[$extKey] =
				t3lib_div::array_merge_recursive_overrule($allSessionData[$extKey], $data);
		} else {
			$allSessionData[$extKey] = $data;
		}
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'feuser', $allSessionData);
			// The feuser session data shall not get lost when coming back from external scripts
		$GLOBALS['TSFE']->fe_user->storeSessionData();
	}
}


?>