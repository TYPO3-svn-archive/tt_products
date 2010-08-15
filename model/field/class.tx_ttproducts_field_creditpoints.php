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
 * functions for the title field
 *
 * $Id $
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_field_creditpoints {


	public function getBasketTotal ()	{
		$rc = 0;
		$basketObj = &t3lib_div::getUserObj('&tx_ttproducts_basket');
		$itemArray = &$basketObj->getItemArray();

		if (count($itemArray))	{
			$creditpointsTotal = 0;
			// loop over all items in the basket indexed by a sort string
			foreach ($itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {
					$row = &$actItem['rec'];
					if ($row['creditpoints'] > 0)	{
						$count = $actItem['count'];
						$creditpointsTotal += $row['creditpoints'] * $count;
					}
				}
			}
			$rc = $creditpointsTotal;
		}
		return $rc;
	}


	/* reduces the amount of creditpoints of the FE user by the total amount of creditpoints from the products. */
	/* It returns the number of credipoints by which the account of the FE user has been reduced. FALSE is if no FE user is logged in. */
	public function pay ()	{
		global $TSFE, $TYPO3_DB;

		$rc = FALSE;
		if ($TSFE->loginUser)	{
//			$whereGeneral = '(fe_users_uid="'.$TSFE->fe_user->user['uid'].'" OR fe_users_uid=0) ';

			$creditpointsTotal = $this->getBasketTotal();

			if ($creditpointsTotal)	{
				$fieldsArrayFeUsers = array();
				$fieldsArrayFeUsers['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'] - $creditpointsTotal;
				if ($fieldsArrayFeUsers['tt_products_creditpoints'] < 0)	{
					$fieldsArrayFeUsers['tt_products_creditpoints'] = 0;
					$rc = $TSFE->fe_user->user['tt_products_creditpoints'];
				}
				if ($TSFE->fe_user->user['tt_products_creditpoints'] != $fieldsArrayFeUsers['tt_products_creditpoints'])	{
					$TSFE->fe_user->user['tt_products_creditpoints'] = $fieldsArrayFeUsers['tt_products_creditpoints']; // store it also for the global FE user data
					$TYPO3_DB->exec_UPDATEquery('fe_users', 'uid='.intval($TSFE->fe_user->user['uid']), $fieldsArrayFeUsers);
					$rc = $creditpointsTotal;
				}
			}
		}
		return $rc;
	}
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/field/class.tx_ttproducts_field_creditpoints.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/field/class.tx_ttproducts_field_creditpoints.php']);
}


?>
