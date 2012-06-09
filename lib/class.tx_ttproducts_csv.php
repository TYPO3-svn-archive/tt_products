<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Klaus Zierer <zierer@pz-systeme.de>
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
 * functions for the creation of CSV files
 *
 * $Id$
 *
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_csv {
	var $pibase; // reference to object of pibase
	var $conf;
	var $calculatedArray; // reference to calculated basket array
	var $itemArray; // reference to the bakset item array
	var $accountUid;

	/**
	 * Getting all tt_products_cat categories into internal array
	 *
	 * @param	[type]		$$pibase: ...
	 * @param	[type]		$itemArray: ...
	 * @param	[type]		$calculatedArray: ...
	 * @param	[type]		$accountUid: ...
	 * @return	[type]		...
	 */
	function init (&$pibase, &$itemArray, &$calculatedArray, $accountUid)	{
		global $TYPO3_DB;
 		$this->pibase = &$pibase;
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

 		$this->conf = &$cnf->conf;
 		$this->calculatedArray = &$calculatedArray;
 		$this->itemArray = &$itemArray;
 		$this->accountUid = $accountUid;
	} // init

	function create ($functablename, &$address, $csvorderuid, &$csvfilepath, &$error_message) {

		$basket = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$orderObj = &$tablesObj->get('sys_products_orders');
		$accountObj = &$tablesObj->get('sys_products_accounts');
		$itemTable = &$tablesObj->get($functablename, FALSE);

		$csvfilepath = trim($csvfilepath);
		if ($csvfilepath{strlen($csvfilepath)-1} != '/') {
			$csvfilepath .= '/';
		}
		$csvfilepath .= $orderObj->getNumber($csvorderuid).'.csv';
		$csvfile = fopen($csvfilepath, 'w');
		if ($csvfile !== FALSE)	{
			// Generate invoice and delivery address
			$csvlinehead = '';
			$csvlineperson = '';
			$csvlinedelivery = '';

			$infoFields = explode(',','feusers_uid,name,cnum,first_name,last_name,salutation,address,telephone,fax,email,company,city,zip,state,country,agb,business_partner,organisation_form');
			foreach($infoFields as $fName) {
				if ($csvlinehead != '') {
					$csvlinehead .= ';';
					$csvlineperson .= ';';
					$csvlinedelivery .= ';';
				}
				$csvlinehead .= '"' . $fName . '"';
				$csvlineperson .= '"' . str_replace(chr(13).chr(10), '|', $address->infoArray['billing'][$fName]) . '"';
				$csvlinedelivery .= '"' . $address->infoArray['delivery'][$fName] . '"';
			}

			// Generate shipping/payment information and delivery note
			$csvlineshipping = '"' . $basket->basketExtra['shipping.']['title'] . '";"' .
				$priceViewObj->priceFormat($this->calculatedArray['priceTax']['shipping']) . '";"' .
				$priceViewObj->priceFormat($this->calculatedArray['priceNoTax']['shipping']) . '"';

			$accountRow = array();
			if ($this->accountUid)	{
				$accountRow = $accountObj->get($this->accountUid,0,true);
			}

			$csvlinepayment = '"' . $basket->basketExtra['payment.']['title'] . '";"' .
				$priceViewObj->priceFormat($this->calculatedArray['priceTax']['payment']) . '";"' .
				$priceViewObj->priceFormat($this->calculatedArray['priceNoTax']['payment']) . '";"' .
				implode('";"', $accountRow).'"';

			$csvlinegift = '"' . $basket->recs['tt_products']['giftcode'] . '"';
			$csvlinedeliverynote = '"'.$address->infoArray['delivery']['note'].'"';
			$csvlinedeliverydesireddate = '"'.$address->infoArray['delivery']['desired_date'].'"';

			// Build field list
			$csvfields = explode(',', $this->conf['CSVfields']);
			$csvfieldcount = count($csvfields);
			for ($a = 0; $a < $csvfieldcount; $a++)	{
				$csvfields[$a] = trim($csvfields[$a]);
			}

			// Write description header
			$csvdescr = '"uid";"count";"';
			$variantFieldArray = $itemTable->variant->getSelectableFieldArray();
			if (count($variantFieldArray))	{
				$csvdescr .= ';"'.implode('";"',$variantFieldArray).'"';
			}
			foreach($csvfields as $csvfield)	{
				$csvdescr .= ';"'.$csvfield.'"';
			}

			if ($this->conf['CSVinOneLine'])	{
				$csvdescr .= ';"deliverynote";"desired date";"shipping method";"shipping_price";"shipping_no_tax";"payment method";"payment_price";"payment_no_tax"';
				$csvdescr .= ';"giftcode"';
				$csvdescr .= ';'.$csvlinehead.';'.$csvlinehead;
			}
			$csvdescr .= chr(13);
			fwrite($csvfile, $csvdescr);

			// Write ordered product list
			$infoWritten = false;
			// loop over all items in the basket indexed by a sorting text
			foreach ($this->itemArray as $sort=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					$row = &$actItem['rec'];
					$pid = intval($row['pid']);
					if (!$basket->getPidListObj()->getPageArray($pid)) {
						// product belongs to another basket
						continue;
					}
					$variants = explode(';', $itemTable->variant->getVariantFromRow($row));
					// $variants = explode(';', $actItem['rec']['extVars']);
					$csvdata = '"' . intval($row['uid']) . '";"' .
						intval($actItem['count']) . '";"' . implode('";"',$variants) . '"';
					foreach($csvfields as $csvfield) {
						$csvdata .= ';"' . $actItem['rec'][$csvfield] . '"';
					}
					if ($this->conf['CSVinOneLine'] && (!$infoWritten))	{
						$infoWritten = true;
						$csvdata .= ';' . $csvlinedeliverynote . ';' . $csvlinedeliverydesireddate . ';' . $csvlineshipping . ';' . $csvlinepayment . ';' . $csvlinegift . ';' . $csvlineperson . ';' . $csvlinedelivery;
					}
					$csvdata .= chr(13);
					fwrite($csvfile, $csvdata);
				}
			}

			if (!$this->conf['CSVinOneLine']) {
				fwrite($csvfile, chr(13));
				fwrite($csvfile, $csvlinehead . chr(13));
				fwrite($csvfile, $csvlineperson . chr(13));
				fwrite($csvfile, $csvlinedelivery . chr(13));
				fwrite($csvfile, chr(13));
				fwrite($csvfile, $csvlinedeliverynote . chr(13));
				fwrite($csvfile, $csvlinedeliverydesireddate . chr(13));
				fwrite($csvfile, $csvlineshipping . chr(13));
				fwrite($csvfile, $csvlinepayment . chr(13));
				fwrite($csvfile, $csvlinegift . chr(13));
			}

			fclose($csvfile);
		} else {
			$langObj = &t3lib_div::getUserObj('&tx_ttproducts_language');
			$message = tx_div2007_alpha5::getLL_fh002($langObj,'no_csv_creation');
			$messageArr =  explode('|', $message);
			$error_message=$messageArr[0] . $csvfilepath . $messageArr[1];
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_csv.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_csv.php']);
}


?>
