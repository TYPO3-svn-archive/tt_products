<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2009 Franz Holzinger <franz@ttproducts.de>
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
 * hook functions for TYPO3 FE extensions
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


class tx_ttproducts_hooks_fe {

	public function login_confirmed ($params, $pObj) {

		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][TT_PRODUCTS_EXT . '.'];

		tx_ttproducts_control_memo::copySession2Feuser($params, $pObj, $conf);
		$this->resetAdresses($params, $pObj);
	}


	public function resetAdresses ($params, $pObj) {
		global $TSFE;

		$recs = $TSFE->fe_user->getKey('ses','recs');
		if (isset($recs) && is_array($recs)) {
			unset($recs['personinfo']);
			unset($recs['delivery']);
			$TSFE->fe_user->setKey('ses','recs',$recs);
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_hooks_fe.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_hooks_fe.php']);
}

?>