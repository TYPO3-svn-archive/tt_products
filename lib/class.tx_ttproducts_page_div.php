<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * functions for the pages
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_page_div {


	/**
	 * Returning the pid out from the row using the where clause
	 */
	function getPID($conf, $confExt, $row) {
		$rc = 0;
		if ($confExt) {
			foreach ($confExt as $k1 => $param) {
				$type  = $param['type'];
				$where = $param['where'];
				$isValid = false;
				if ($where) {
					$wherelist = explode ('AND', $where);
					$isValid = true;
					foreach ($wherelist as $k2 => $condition) {
						$args = explode ('=', $condition);
						if ($row[$args[0]] != $args[1]) {
							$isValid = false;
						}
					}
				} else {
					$isValid = true;
				}

				if ($isValid == true) {
					switch ($type) {
						case 'sql':
							$rc = $param['pid'];
							break;
						case 'pid':
							$rc = intval ($this->pageArray[$row['pid']]['pid']);
							break;
					}
					break;  //ready with the foreach loop
				}
			}
		} else
		{
			if ($conf) {
				$rc = $conf;
			} else {
				$rc = intval ($this->pageArray[$row['pid']]['pid']);
			}
		}
		return $rc;
	} // getPID


	/**
	 * Sets the pid_list internal var
	 */
	function setPidlist($pid_list)	{
		$this->pid_list = $pid_list;
	}


	/**
	 * Extends the internal pid_list by the levels given by $recursive
	 */
	function initRecursive($recursive)	{
		global $TSFE;
		
		if (!$this->pid_list) {
			$this->pid_list = $TSFE->id;
		}
		if ($recursive)	{		// get pid-list if recursivity is enabled
			$pid_list_arr = explode(',',$this->pid_list);
			$this->pid_list='';
			while(list(,$val)=each($pid_list_arr))	{
				$this->pid_list.=$val.','.$this->cObj->getTreeList($val,intval($recursive));
			}
			$this->pid_list = ereg_replace(',$','',$this->pid_list);
			$pid_list_arr = explode(',',$this->pid_list);
			$pid_list_arr = array_unique ($pid_list_arr);
			$this->pid_list = implode(',', $pid_list_arr);
		}
	}



	/**
	 * Generates an array, ->pageArray of the pagerecords from ->pid_list
	 */
	function generatePageArray()	{
			// Get pages (for category titles)
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid,pid', 'pages', 'uid IN ('.$this->pid_list.')');
		$this->pageArray = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
			$this->pageArray[$row['uid']] = $row;
		}
	} // generatePageArray

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_page_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_page_div.php']);
}


?>
