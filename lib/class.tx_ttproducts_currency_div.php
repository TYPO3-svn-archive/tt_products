<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2005 Milosz Klosowicz <typo3@miklobit.com>
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
 * currency functions
 *
 * $Id$
 *
 * @author	Milosz Klosowicz <typo3@miklobit.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_view_div.php');


class tx_ttproducts_currency_div {



	/**
	 * currency selector
	 */
	function currency_selector(&$pibase, $theCode)	{
		global $TSFE;

		$currList = $this->exchangeRate->initCurrencies($this->BaseCurrency);
		$jScript =  '	var currlink = new Array(); '.chr(10);
		$index = 0;
		foreach( $currList as $key => $value)	{
			//$url = $this->getLinkUrl('','',array('C' => 'C='.$key));
			$url = $pibase->pi_getPageLink($TSFE->id,'',tx_ttproducts_view_div::getLinkParams('',array('C' => 'C='.$key)));
			$jScript .= '	currlink['.$index.'] = "'.$url.'"; '.chr(10) ;
			$index ++ ;
		}

		$content = $pibase->cObj->getSubpart($this->templateCode,tx_ttproducts_view_div::spMarker($this->pibase, $this->conf, '###CURRENCY_SELECTOR###'));
		$content = $pibase->cObj->substituteMarker( $content, '###CURRENCY_FORM_NAME###', 'tt_products_currsel_form' );
		$onChange = 'if (!document.tt_products_currsel_form.C.options[document.tt_products_currsel_form.C.selectedIndex].value) return; top.location.replace(currlink[document.tt_products_currsel_form.C.selectedIndex] );';
		$selector = $this->exchangeRate->buildCurrSelector($this->BaseCurrency,'C','',$this->currency, $onChange);
		$content = $pibase->cObj->substituteMarker( $content, '###SELECTOR###', $selector );

		// javascript to submit correct get parameters for each currency
		$GLOBALS['TSFE']->additionalHeaderData['tx_ttproducts'] = '<script type="text/javascript">'.chr(10).$jScript.'</script>';
		return $content ;
	}


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_currency_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_currency_div.php']);
}


?>
