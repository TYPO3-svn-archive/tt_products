<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Kasper Skårhøj <kasperYYYY@typo3.com>
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
 * main class for eID AJAX
 *
 * $Id$
 *
 * @author  Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

define ('CRLF', "\r\n");
define ('DQT', '"');
define ('QT', "'");


// *********************
// Libraries included
// *********************
$TT->push('Include Frontend libraries','');
    require_once(PATH_tslib.'class.tslib_fe.php');
    require_once(PATH_t3lib.'class.t3lib_page.php');
    require_once(PATH_t3lib.'class.t3lib_userauth.php');
    require_once(PATH_tslib.'class.tslib_feuserauth.php');
    require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
    require_once(PATH_t3lib.'class.t3lib_cs.php');
$TT->pull();

$typoVersion = t3lib_div::int_from_ver($GLOBALS['TYPO_VERSION']);
if ($typoVersion >= 4003000)    {

	// ***********************************
	// Initializing the Caching System
	// ***********************************

	$TT->push('Initializing the Caching System','');
			// TODO implement autoloading so that we only require stuff we really need
		require_once(PATH_t3lib . 'class.t3lib_cache.php');

		require_once(PATH_t3lib . 'cache/class.t3lib_cache_abstractbackend.php');
		require_once(PATH_t3lib . 'cache/class.t3lib_cache_abstractcache.php');
		require_once(PATH_t3lib . 'cache/class.t3lib_cache_exception.php');
		require_once(PATH_t3lib . 'cache/class.t3lib_cache_factory.php');
		require_once(PATH_t3lib . 'cache/class.t3lib_cache_manager.php');
		require_once(PATH_t3lib . 'cache/class.t3lib_cache_variablecache.php');

		require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_classalreadyloaded.php');
		require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_duplicateidentifier.php');
		require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invalidbackend.php');
		require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invalidcache.php');
		require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invaliddata.php');
		require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_nosuchcache.php');

		$typo3CacheManager = t3lib_div::makeInstance('t3lib_cache_Manager');
		$cacheFactoryClass = t3lib_div::makeInstanceClassName('t3lib_cache_Factory');
		$typo3CacheFactory = new $cacheFactoryClass($typo3CacheManager);

		unset($cacheFactoryClass);
	$TT->pull();
}

// ***********************************
// Create $TSFE object (TSFE = TypoScript Front End)
// Connecting to database
// ***********************************
$temp_TSFEclassName=t3lib_div::makeInstanceClassName('tslib_fe');
$TSFE = new $temp_TSFEclassName(
        $GLOBALS['TYPO3_CONF_VARS'],
        t3lib_div::_GP('id'),
        t3lib_div::_GP('type'),
        t3lib_div::_GP('no_cache'),
        t3lib_div::_GP('cHash'),
        t3lib_div::_GP('jumpurl'),
        t3lib_div::_GP('MP'),
        t3lib_div::_GP('RDCT')
    );

$TSFE->connectToMySQL();
if ($TSFE->RDCT)    {
	$TSFE->sendRedirect();
}

// *******************
// output compression
// *******************
if ($GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel'])    {
    ob_start();
    require_once(PATH_t3lib.'class.gzip_encode.php');
}

// *********
// FE_USER
// *********
$TT->push('Front End user initialized','');
$TSFE->initFEuser();
$TT->pull();

// *****************************************
// Proces the ID, type and other parameters
// After this point we have an array, $page in TSFE, which is the
// page-record of the current page, $id
// *****************************************
$TT->push('Process ID','');
    // not needed and doesnot work with realurl //
$TSFE->checkAlternativeIdMethods();
    $TSFE->clear_preview();
    $TSFE->determineId();

        // Now, if there is a backend user logged in and he has NO access to
	// this page, then re-evaluate the id shown!
    if ($TSFE->beUserLogin && !$BE_USER->extPageReadAccess($TSFE->page))    {

            // Remove user
        unset($BE_USER);
        $TSFE->beUserLogin = 0;

            // Re-evaluate the page-id.
        $TSFE->checkAlternativeIdMethods();
        $TSFE->clear_preview();
        $TSFE->determineId();
    }
    $TSFE->makeCacheHash();
$TT->pull();


// *******************************************
// Get compressed $TCA-Array();
// After this, we should now have a valid $TCA, though minimized
// *******************************************
$TSFE->getCompressedTCarray();


// ********************************
// Starts the template
// *******************************
$TT->push('Start Template','');
$TSFE->initTemplate();
$TSFE->tmpl->getFileName_backPath = PATH_site;
$TT->pull();

// ******************************************************
// Get config if not already gotten
// After this, we should have a valid config-array ready
// ******************************************************
$TSFE->getConfigArray();

$conf = $TSFE->tmpl->setup['plugin.'][TT_PRODUCTS_EXTkey.'.'];
$config = array();
$config['LLkey'] = '';

// tt_products specific parts

require_once(PATH_BE_ttproducts.'eid/class.tx_ttproducts_ajax.php');
require_once(PATH_BE_ttproducts.'eid/class.tx_ttproducts_db.php');


// Make instance:
$ajax = &t3lib_div::makeInstance('tx_ttproducts_ajax');
$ajax->init();

$SOBE = &t3lib_div::makeInstance('tx_ttproducts_db');
$SOBE->init($conf, $config, $ajax);
if($_POST['xajax']){
	global $trans;
	$trans = $this;
	$ajax->taxajax->processRequests();
	exit();
}
$SOBE->main();
$SOBE->printContent();

?>
