<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Franz Holzinger <contact@fholzinger.com>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * Class for updating the db
 *
 * $Id$
 *
 * @author	 Franz Holzinger <contact@fholzinger.com>
 */
class ext_update  {

	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main()	{

		$content = '';
		$content.= '<br />Integrate the products articles table into the multirelational table.';
		$content.= '<br />Missing records will be generated.';
		$content.= '<br /><b>Create a backup of your TYPO3 database before you execute this script!</b>';

		if(t3lib_div::_GP('integrate')) {
// 			require_once(t3lib_extMgm::extPath(TT_PRODUCTS_EXT).'lib/class.tx_ttproducts_integrate.php');
			$count = tx_ttproducts_integrate::generateArticleMM();

			$content .= '<p>You have integrated '.$count.' articles into the table tt_products_products_mm_articles</p>';
			$content .= '<p>Done</p>';
		} else {
			$linkScript = t3lib_div::slashJS(t3lib_div::linkThisScript());

			$content .= '<br /><br />';
			$content .= 'This integration works only once. When you added entries in the articles table <br />'.
				'and you want to do it again you have to select \'UPDATE!\' in the EM.';
			$content .= '<br /><br />';
			$content .= '<input type="submit" name="integrate" value="Integrate the product articles table into the relational mm table" onclick="this.form.action=\'' . $linkScript . '\';submit();" />';
		}

		return $content;
	}

	/**
	 * access is always allowed
	 *
	 * @return	boolean		Always returns TRUE
	 */
	function access() {
		return TRUE;
	}

}

// Include extension?
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/class.ext_update.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/class.ext_update.php']);
}


?>