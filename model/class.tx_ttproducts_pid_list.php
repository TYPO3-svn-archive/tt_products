<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2007 Franz Holzinger <kontakt@fholzinger.com>
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
 * functions for the page id list
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_pid_list {
	var $pid_list;				// list of page ids
	var $recursive;
	var $pageArray = array();		// pid_list as array
	var $cObj;

	/**
	 * Getting all tt_products_cat categories into internal array
	 *
	 * @param	[type]		$$cObj: ...
	 * @return	[type]		...
	 */
	function init($cObj)	{
		$this->cObj = $cObj;
	} // init


	/**
	 * Sets the pid_list internal var
	 *
	 * @param	[type]		$pid_list: ...
	 * @return	[type]		...
	 */
	function setPidlist($pid_list)	{
		$this->pid_list = $pid_list;
	}


	/**
	 * gets the latest applied recursive
	 *
	 * @return	[type]		...
	 */
	function getRecursive()	{
		return $this->recursive;
	}


	/**
	 * Gets the pid_list internal var or the child pid_list of the page id as parameter
	 *
	 * @param	[type]		$pid: ...
	 * @return	[type]		...
	 */
	function getPidlist($pid='')	{
		$rc = '';
		if ($pid)	{
			$this->applyRecursive(1,$pid,FALSE);
			$rc = $pid;
		} else {
			$rc = $this->pid_list;
		}
		return $rc;
	}


	/**
	 * Sets the pid_list internal var
	 *
	 * @return	[type]		...
	 */
	function setPageArray()	{
		$this->pageArray = t3lib_div::trimExplode (',', $this->pid_list);
		$this->pageArray = array_flip($this->pageArray);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$pid: ...
	 * @return	[type]		...
	 */
	function getPageArray($pid=0)	{
		if ($pid)	{
			$rc = isset($this->pageArray[$pid]);
		} else {
			$rc = $this->pageArray;
		}

		return $rc;
	}


	/**
	 * Extends the internal pid_list by the levels given by $recursive
	 *
	 * @param	[type]		$recursive: ...
	 * @param	[type]		$pids: ...
	 * @param	[type]		$bStore: ...
	 * @return	[type]		...
	 */
	function applyRecursive($recursive, &$pids, $bStore=FALSE)	{
		global $TSFE;

		if ($pids)	{
			if ($bStore)	{
				$this->pid_list = $pids;
				$pid_list = &$this->pid_list;
			} else {
				$pid_list = &$pids;
			}
		} else {
			$pid_list = &$this->pid_list;
		}
		if (!$pid_list) {
			$pid_list = $TSFE->id;
		}
		if ($recursive)	{		// get pid-list if recursivity is enabled
			$recursive = intval($recursive);
			$this->recursive = $recursive;
			$pid_list_arr = explode(',',$pid_list);
			$pid_list = '';
			while(list(,$val) = each($pid_list_arr))	{
				$pid_list .= $val.','.$this->cObj->getTreeList($val,$recursive);
			}
			$pid_list = preg_replace('/,$/','',$pid_list);
			$pid_list_arr = explode(',',$pid_list);
			$pid_list_arr = array_unique ($pid_list_arr);
			$pid_list = implode(',', $pid_list_arr);
		}
	}

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_pid_list.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_pid_list.php']);
}


?>