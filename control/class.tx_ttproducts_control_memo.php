<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2010 Franz Holzinger <franz@ttproducts.de>
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
 * functions for the control of the single view
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


class tx_ttproducts_control_memo {

	/**
	 * Adds link markers to a wrapped subpart array
	 */
	public function getWrappedSubpartArray (
		&$wrappedSubpartArray,
		$pidMemo,
		$uid,
		$cObj,
		$urlObj,
		$excludeList='',
		$addQueryString=array(),
		$css_current='',
		$bUseBackPid=TRUE
	)	{
		global $TSFE;

		$pidMemo = ($this->conf['PIDmemo'] ? $this->conf['PIDmemo'] : $TSFE->id);
		$cmdArray = array('add','del');

		foreach ($cmdArray as $cmd)	{
			$addQueryString[$cmd . 'memo'] = $uid;

			$pageLink = tx_div2007_alpha::getPageLink_fh002(
				$cObj,
				$pidMemo,
				'',
				$urlObj->getLinkParams(
					$excludeList,
					$addQueryString,
					TRUE,
					$bUseBackPid
				)
			);
			$wrappedSubpartArray['###LINK_MEMO_' . strtoupper($cmd) . '###'] = array('<a href="' . $pageLink . '"' . $css_current . '>','</a>');
			unset($addQueryString[$cmd . 'memo']);
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_memo.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_memo.php']);
}


?>
