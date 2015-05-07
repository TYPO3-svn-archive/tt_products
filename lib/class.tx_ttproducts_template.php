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
 * functions for the template file
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



class tx_ttproducts_template {
	private $templateFile;

	public function getTemplateFile ()	{
		return $this->templateFile;
	}


	public function getTemplateSuffix ()	{
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$config = &$cnf->getConfig();
		return $config['templateSuffix'];
	}


	public function &get ($theCode, $langObj, $cObj, &$templateFile, &$errorMessage) {

		$templateCode = '';
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$conf = &$cnf->getConf();
		$templateFile = $cnf->getTemplateFile($theCode);

		if ($templateFile) {

			// template file is fetched. The whole template file from which the various subpart are extracted.
			$templateCode = $cObj->fileResource($templateFile);
		}

		if (!$templateFile || empty($templateCode)) {
			if ($conf['templateFile.'][$theCode])	{
				$tmplText = $theCode.'.';
			}
			$tmplText .= 'templateFile';
			$errorMessage .= tx_div2007_alpha5::getLL_fh002($langObj, 'no_template') . ' plugin.' . TT_PRODUCTS_EXTkey . '.' . $tmplText . ' = ';
			$errorMessage .= ($conf['templateFile'] ? "'".$conf['templateFile']."'" : '""');
		}

		$this->templateFile = $templateFile;
		return $templateCode;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_template.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_template.php']);
}


?>
