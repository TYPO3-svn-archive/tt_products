<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Franz Holzinger <franz@ttproducts.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


require_once(PATH_t3lib.'class.t3lib_extobjbase.php');



/**
 * Module extension (addition to function menu) 'Move Items' for the 'tt_products' extension.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package	TYPO3
 * @subpackage	tx_ttproducts
 */
class tx_ttproducts_modfunc1 extends t3lib_extobjbase {

	/**
		* Returns the module menu
		*
		* @return	Array with menuitems
		*/
	function modMenu()	{
		global $LANG;

		return Array (
			'tx_ttproducts_modfunc1_pid_dest' => '',
			'tx_ttproducts_modfunc1_age' => '',
		);
	}

	/**
		* Main method of the module
		*
		* @return	HTML
		*/
	function main()	{
			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS,$TYPO3_DB;

		$theOutput.=$this->pObj->doc->spacer(5);
		if ($BE_USER->user['admin'])	{
			$currId = $this->pObj->id;
			$content = sprintf($LANG->getLL('pid_src'),$currId);
		} else {
			$content = $LANG->getLL('only_admin');
		}
		$theOutput.=$this->pObj->doc->section($LANG->getLL('title'),$content,0,1);
		$destId = $this->pObj->MOD_SETTINGS['tx_ttproducts_modfunc1_pid_dest'];
		$age = $this->pObj->MOD_SETTINGS['tx_ttproducts_modfunc1_age'];

		if($currId && $destId && $age)	{
			$fieldsArray = array();
			$fieldsArray['pid'] = intval($destId);
			$day = 24 * 60 * 60;
			$time = time();
			$tstamplimit = $time - $age * $day;
			$TYPO3_DB->exec_UPDATEquery('tt_products', 'pid='.intval($currId).' AND tstamp<'.$tstamplimit, $fieldsArray);
			$TYPO3_DB->exec_UPDATEquery('tt_products_articles', 'pid='.intval($currId).' AND tstamp<'.$tstamplimit, $fieldsArray);
			$content .= '<br />'.$LANG->getLL('moved');
		}

		if ($BE_USER->user['admin'])	{
			$menu=array();
// 			$menu[]=t3lib_BEfunc::getFuncCheck(
// 				$this->wizard->pObj->id,
// 				'SET[tx_ttproducts_modfunc1_check]',
// 				$this->wizard->pObj->MOD_SETTINGS['tx_ttproducts_modfunc1_check']
// 			).$LANG->getLL('checklabel');

			$content = '';
			$content.='<br />'.$LANG->getLL('pid_dest').t3lib_BEfunc::getFuncInput(
				$this->pObj->id,
				'SET[tx_ttproducts_modfunc1_pid_dest]',
				$destId
			);
			$content.='<br />'.$LANG->getLL('age').t3lib_BEfunc::getFuncInput(
				$this->pObj->id,
				'SET[tx_ttproducts_modfunc1_age]',
				$age
			);
			$content .= '<br /><input type="submit" name="create" value="'.$LANG->getLL('start').'">';
			$menu[]=$content;
			$theOutput.=$this->pObj->doc->spacer(5);
			$theOutput.=$this->pObj->doc->section('Menu',implode(' - ',$menu),0,1);
		}

		return $theOutput;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/modfunc1/class.tx_ttproducts_modfunc1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/modfunc1/class.tx_ttproducts_modfunc1.php']);
}

?>