<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * functions for digital medias
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_field_media extends tx_ttproducts_field_base {

	/**
	 * @param	[type]		$$cObj: ...
	 * @return	[type]		...
	 */
	function init(&$cObj)	{
		global $TYPO3_DB,$TSFE,$TCA;

 		parent::init($cObj);

		// DAM support
		if (t3lib_extMgm::isLoaded('dam')) {
			include_once(t3lib_extMgm::extPath('dam').'lib/class.tx_dam.php');
		}
	} // init


	function getDirname(&$imageRow)	{

		if($imageRow['file_mime_type'] == 'image' && isset($imageRow['file_path']))	{
			$dirname = $imageRow['file_path'];
		} else {
			$dirname = ($this->conf['defaultImageDir'] ? $this->conf['defaultImageDir'] : ( $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['imageFolder'] ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['imageFolder'] . '/' : 'uploads/pics/'));
		}
		return $dirname;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/field/class.tx_ttproducts_field_media.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/field/class.tx_ttproducts_field_media.php']);
}


?>
