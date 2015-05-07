<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Tim Lochmueller <webmaster@fruit-lab.de>
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
 * dashboard functions
 *
 * $Id: class.tx_ttproducts_latest.php 9057 2008-05-01 20:00:10Z franzholz $
 *
 * @author	Tim Lochmueller <webmaster@fruit-lab.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once(t3lib_extMgm::extPath('mydashboard', 'templates/class.tx_mydashboard_template.php'));
require_once(t3lib_extMgm::extPath('mydashboard', 'templates/interface.tx_mydashboard_widgetinterface.php'));

class tx_ttproducts_latest extends tx_mydashboard_template implements tx_mydashboard_widgetinterface {


	/*
	 * initial  the Widget
	 */
	function init(){

		// Init Parent
		parent::init();

		// Build config
		$config = array(
			'item_limit' => array(
				'default' => 10,
				'type' => 'int',
			),
		);

		// Add Language File
		$this->addLanguageFile(t3lib_div::getFileAbsFileName('EXT:tt_products/widgets/labels.xml'));

		// Set the Default config
		$this->setDefaultConfig($config);

		// Set title & icon
		$this->setTitle('Shop System Lists');
		$this->setIcon(PATH_BE_ttproducts_rel.'ext_icon.gif');

		// required
		return true;
	} # function - init


	/*
	 * Print the Content
	 */
	public function getContent(){

		// Build the Option Menu
		$options = array(
			'order' => 'Orders',
			'products' => 'Newest products',
			'outstock' => 'Out of stock',
			'fewstock' => 'Few on stock'
		);

		// Get the Menu
		$c = $this->buildSelectMenu($options);

		$limit = (int)$this->getConfigVar('item_limit');

		// run the database queries
		switch($_REQUEST['value']){
			case 'order':

				// Show Database List
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','sys_products_orders','deleted=0','','uid DESC',$limit);
		
				// Render List
				$c .= $this->showDatabaseList('Orders:',$res,'uid,name,amount,crdate,note');
			break;

			case 'products':

				// Show Database List
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tt_products','deleted=0 AND hidden=0','','crdate DESC',$limit);
		
				// Render List
				$c .= $this->showDatabaseList('Products:',$res,'uid,itemnumber,title,price');
			break;

			case 'outstock':

				// Show Database List
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tt_products','deleted=0 AND hidden=0 AND inStock<=0','','inStock',$limit);
		
				// Render List
				$c .= $this->showDatabaseList('Products:',$res,'uid,itemnumber,title,inStock');
			break;

			case 'fewstock':

				// Show Database List
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tt_products','deleted=0 AND hidden=0 AND inStock>0','','inStock',$limit);
		
				// Render List
				$c .= $this->showDatabaseList('Products:',$res,'uid,itemnumber,title,inStock');
			break;
		}

		return $c;
	} # function - getContent


} # class - tx_ttproducts_latest

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/widgets/class.tx_ttproducts_latest.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/widgets/class.tx_ttproducts_latest.php']);
} # if
?>