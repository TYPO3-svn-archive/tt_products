<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2005 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * Part of the tt_products (Shopping System) extension.
 *
 * functions for the creation of CSV files
 *
 * $Id$
 *
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_order.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_view_div.php');


class tx_ttproducts_csv {
	var $pibase; // reference to object of pibase
	var $calculatedArray; // reference to calculated basket array
	var $itemArray; // reference to the bakset item array
	var $price;	 					// object for price functions
	var $conf;

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, &$conf, &$itemArray, &$calculatedArray, &$price)	{
		global $TYPO3_DB;
 		$this->pibase = &$pibase;
 		$this->conf = &$conf;
 		$this->calculatedArray = &$calculatedArray;
 		$this->itemArray = &$itemArray;
 		$this->price = &$price;
	} // init


	function create(&$basket, $csvorderuid, $csvfilepath, &$error_message) {
		if ($csvfilepath[strlen($csvfilepath)-1] != '/') {
			$csvfilepath .= '/';
		}
		$csvfilepath .= $basket->order->getOrderNumber($csvorderuid).'.csv';
		
		$csvfile = fopen($csvfilepath, 'w');
		if ($csvfile !== FALSE)
		{
			// Generate invoice and delivery address
			$csvlinehead = '';
			$csvlineperson = '';
			$csvlinedelivery = '';
/* Added Els: 'feusers_uid,' */
			$infoFields = explode(',','feusers_uid,name,first_name,last_name,address,telephone,fax,email,company,city,zip,state,country,agb');
			while(list(,$fName)=each($infoFields)) {
				if ($csvlinehead != '') {
					$csvlinehead .= ';';
					$csvlineperson .= ';';
					$csvlinedelivery .= ';';
				}
				$csvlinehead .= '"' . $fName . '"';
				$csvlineperson .= '"' . str_replace('\r\n', '|', $basket->personInfo[$fName]) . '"';
				$csvlinedelivery .= '"' . $basket->deliveryInfo[$fName] . '"';
			}

			// Generate shipping/payment information and delivery note
			$csvlineshipping = '"' . $basket->basketExtra['shipping.']['title'] . '";"' .
				$this->price->priceFormat($this->calculatedArray['priceTax']['shipping']) . '";"' .
				$this->price->priceFormat($this->calculatedArray['priceNoTax']['shipping']) . '"';

			$csvlinepayment = '"' . $basket->basketExtra['payment.']['title'] . '";"' .
				$this->price->priceFormat($this->calculatedArray['priceTax']['payment']) . '";"' .
				$this->price->priceFormat($this->calculatedArray['priceNoTax']['payment']) . '"';

			$csvlinedeliverynote = '"'.$basket->deliveryInfo['note'].'"';
			
			$csvlinedeliverydesireddate = '"'.$basket->deliveryInfo['desired_date'].'"';

			// Build field list
			$csvfields = explode(',', $this->conf['CSVfields']);
			$csvfieldcount = count($csvfields);
			for ($a=0;$a<$csvfieldcount;$a++)
				$csvfields[$a] = trim($csvfields[$a]);

			// Write description header
			$csvdescr = '"uid";"count";"color";"size";"description";"gradings"';
			reset($csvfields);
			foreach($csvfields as $csvfield)
				$csvdescr .= ';"'.$csvfield.'"';
			if ($this->conf['CSVinOneLine'])
			{
				$csvdescr .= '"deliverynote";"desired date";"shipping method";"shipping_price";"shipping_no_tax";"payment method";"payment_price";"payment_no_tax"';
				$csvdescr .= ';'.$csvlinehead.';'.$csvlinehead;
			}
			$csvdescr .= '\n';
			fwrite($csvfile, $csvdescr);

			// Write ordered product list
			reset($this->itemArray);

			$infoWritten = false;
			// loop over all items in the basket indexed by page and itemnumber
			foreach ($this->itemArray as $pid=>$pidItem) {
				foreach ($pidItem as $itemnumber=>$actItemArray) {
					foreach ($actItemArray as $k1=>$actItem) {
						$variants = explode(';', $actItem['rec']['extVars']);
						$csvdata = '"'.intval($actItem['rec']['uid']).'";"'.
									intval($actItem['count']).'";"'.
									$variants[0].'";"'.
									$variants[1].'";"'.
									$variants[2].'";"'.
									$variants[3].'"';
									$variants[4].'"';
						reset($csvfields);
						foreach($csvfields as $csvfield) {
							$csvdata .= ';"'.$actItem['rec'][$csvfield].'"';
						}
						if ($this->conf['CSVinOneLine'] && (!$infoWritten))	{
							$infoWritten = true;
							$csvdata .= ';'.$csvlinedeliverynote.';'.$csvlinedeliverydesireddate.';'.$csvlineshipping.';'.$csvlinepayment.';'.$csvlineperson.';'.$csvlinedelivery;
						}
						$csvdata .= '\n';
						fwrite($csvfile, $csvdata);
					}
				}
			}

			if (!$this->conf['CSVinOneLine']) {
				fwrite($csvfile, '\n');
				fwrite($csvfile, $csvlinehead . '\n');
				fwrite($csvfile, $csvlineperson . '\n');
				fwrite($csvfile, $csvlinedelivery . '\n');
				fwrite($csvfile, '\n');
				fwrite($csvfile, $csvlinedeliverynote . '\n');
				fwrite($csvfile, $csvlinedeliverydesireddate . '\n');
				fwrite($csvfile, $csvlineshipping . '\n');
				fwrite($csvfile, $csvlinepayment . '\n');
			}

			fclose($csvfile);
		}
		else {
			$message = $this->pibase->pi_getLL('no csv creation');
			$messageArr =  explode('|', $message);
			$error_message=$messageArr[0]. $csvfilepath .$messageArr[1];
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_csv.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_csv.php']);
}


?>
