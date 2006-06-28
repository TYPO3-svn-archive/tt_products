<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2006 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * Part of the tt_products (Shopping System) extension.
 *
 * functions for the category
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_image.php');

class tx_ttproducts_category_base {
	var $table;		 // object of the type tx_table_db
	var $pibase; 	 // reference to object of pibase
	var $cnf;
	var $conf;
	var $config;
	var $dataArray;  // array of read in categories
	var $image;
	var $marker = 'CATEGORY';	// must be overridden
	var $piVar = ''; // must be overridden


	/**
	 * initialization with table object and language table
	 */
	function init(&$pibase, &$cnf, &$tt_content)	{
		global $TYPO3_DB,$TSFE;

		$this->pibase = &$pibase;
		$this->cnf = &$cnf;
		$this->conf = &$this->cnf->conf;
		$this->config = &$this->cnf->config;

			// image
		$this->image = t3lib_div::makeInstance('tx_ttproducts_image');
		$this->image->init($this->pibase, $cnf, $tt_content, $this->table, $this->marker);
	} // init


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array		Returns a markerArray ready for substitution with information
	 * 			 			for the tt_producst record, $row
	 * @access private
	 */
	function getMarkerArray (&$markerArray, &$page, $category, $pid, $imageNum=0, $imageRenderObj='image', $viewCatTagArray, $forminfoArray=array(), $pageAsCategory=0, $code)	{
	}


	function getParamDefault ()	{
		$rc = '';
		return $rc;
	}


	function &getRelationArray ($excludePid=0) {
		
	}


	function setMarkerArrayCatTitle (&$markerArray, $catTitle)	{
		$this->pibase->cObj->setCurrentVal($catTitle);
		$markerArray['###CATEGORY_TITLE###']=$this->pibase->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
	}


	function getMarkerArrayCatTitle(&$markerArray)	{
		return ($markerArray['###CATEGORY_TITLE###']);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_category_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_category_base.php']);
}


?>
