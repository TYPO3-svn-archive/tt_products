<?php
if (!defined ('TYPO3_MODE'))	die ('Access denied.');

$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it here:

$typoVersion = 0;
if (class_exists('tx_div2007_core')) {
	$typoVersion = tx_div2007_core::getTypoVersion();
} else { // workaround for bug #55727
	$result = FALSE;
	$callingClassName = '\\TYPO3\\CMS\\Core\\Utility\\VersionNumberUtility';
	if (
		class_exists($callingClassName) &&
		method_exists($callingClassName, 'convertVersionNumberToInteger')
	) {
		$result = call_user_func($callingClassName . '::convertVersionNumberToInteger', TYPO3_version);
	} else if (
		class_exists('t3lib_utility_VersionNumber') &&
		method_exists('t3lib_utility_VersionNumber', 'convertVersionNumberToInteger')
	) {
		$result = t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version);
	} else if (
		class_exists('t3lib_div') &&
		method_exists('t3lib_div', 'int_from_ver')
	) {
		$result = t3lib_div::int_from_ver(TYPO3_version);
	}

	$typoVersion = $result;
}


if (!defined ('TT_PRODUCTS_EXTkey')) {
	define('TT_PRODUCTS_EXTkey',$_EXTKEY);
}

if (!defined ('TT_PRODUCTS_EXT')) {
	define('TT_PRODUCTS_EXT', $_EXTKEY);
}

if (!defined ('PATH_BE_ttproducts')) {
	define('PATH_BE_ttproducts', t3lib_extMgm::extPath(TT_PRODUCTS_EXT));
}

if (!defined ('PATH_BE_ttproducts_rel')) {
	define('PATH_BE_ttproducts_rel', t3lib_extMgm::extRelPath(TT_PRODUCTS_EXT));
}

if (!defined ('PATH_FE_ttproducts_rel')) {
	define('PATH_FE_ttproducts_rel', t3lib_extMgm::siteRelPath(TT_PRODUCTS_EXT));
}

if (!defined ('PATH_ttproducts_icon_table_rel')) {
	define('PATH_ttproducts_icon_table_rel', PATH_BE_ttproducts_rel.'res/icons/table/');
}

if (!defined ('TABLE_EXTkey')) {
	define('TABLE_EXTkey','table');
}

if (t3lib_extMgm::isLoaded(TABLE_EXTkey)) {
	if (!defined ('PATH_BE_table')) {
		define('PATH_BE_table', t3lib_extMgm::extPath(TABLE_EXTkey));
	}
}

if (!defined ('ADDONS_EXTkey')) {
	define('ADDONS_EXTkey','addons_tt_products');
}

if (!defined ('TT_ADDRESS_EXTkey')) {
	define('TT_ADDRESS_EXTkey','tt_address');
}

if (!defined ('PARTNER_EXTkey')) {
	define('PARTNER_EXTkey','partner');
}

if (!defined ('PARTY_EXTkey')) {
	define('PARTY_EXTkey','party');
}

if (!defined ('DIV2007_EXTkey')) {
	define('DIV2007_EXTkey','div2007');
}

if (t3lib_extMgm::isLoaded(DIV2007_EXTkey)) {
	if (!defined ('PATH_BE_div2007')) {
		define('PATH_BE_div2007', t3lib_extMgm::extPath(DIV2007_EXTkey));
	}
}

if (!defined ('TT_PRODUCTS_DIV_DLOG')) {
	define('TT_PRODUCTS_DIV_DLOG', '0');	// for development error logging
}

if (!defined ('TAXAJAX_EXT')) {
	define('TAXAJAX_EXT','taxajax');
}

if (!defined ('DAM_EXTkey')) {
	define('DAM_EXTkey','dam');
}

if (t3lib_extMgm::isLoaded(TAXAJAX_EXT)) {
	if (!defined ('PATH_BE_taxajax')) {
		define('PATH_BE_taxajax', t3lib_extMgm::extPath(TAXAJAX_EXT));
	}
	$GLOBALS['TYPO3_CONF_VARS'] ['FE']['eID_include'][TT_PRODUCTS_EXT] =  'EXT:'.TT_PRODUCTS_EXT.'/eid/class.tx_ttproducts_eid.php' ;
}

t3lib_extMgm::addUserTSConfig('options.saveDocNew.tt_products=1');
t3lib_extMgm::addUserTSConfig('options.saveDocNew.tt_products_cat=1');
t3lib_extMgm::addUserTSConfig('options.saveDocNew.tt_products_articles=1');

if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]))	{
	$tmpArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT];
} else {
	unset($tmpArray);
}

if (isset($_EXTCONF) && is_array($_EXTCONF))	{
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT] = $_EXTCONF;

	if (isset($tmpArray) && is_array($tmpArray))	{
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT] = array_merge($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT], $tmpArray);
	}
} else if (!isset($tmpArray)) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT] = array();
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['useFlexforms'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['useFlexforms'] = '1';
}


if (!defined($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products']['MENU'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products'] = array (
		'default' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_default',
			'fList' =>  'title,itemnumber,price,image',
			'icon' => TRUE
		),
		'ext' => array (
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_ext',
			'fList' =>  'title,price2,category;inStock;weight;tax',
			'icon' => TRUE
		),
		'variants' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_variants',
			'fList' =>  'title,color;size;gradings,description',
			'icon' => TRUE
		)
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products_language'] = array (
		'default' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_default',
			'fList' => 'sys_language_uid,prod_uid,title,subtitle,datasheet,www',
			'icon' => TRUE
		),
		'ext' => array (
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_ext',
			'fList' => 'sys_language_uid,prod_uid,note,note2',
			'icon' => TRUE
		),
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products_articles'] = array (
		'default' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_default',
			'fList' =>  'title,itemnumber,price,inStock',
			'icon' => TRUE
		),
		'ext' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_ext',
			'fList' =>  'title;price2,color;size;gradings',
			'icon' => TRUE
		)
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products_cat'] = array (
		'default' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_default',
			'fList' =>  'title,image',
			'icon' => TRUE
		)
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products_cat_language'] = array (
		'default' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_default',
			'fList' => 'sys_language_uid,title,subtitle,cat_uid',
			'icon' => TRUE
		),
		'ext' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_ext',
			'fList' => 'sys_language_uid,title,note',
			'icon' => TRUE
		)
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['sys_products_orders'] = array (
		'default' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_default',
			'fList' => 'name,first_name,last_name,zip,city,country,email,amount',
			'icon' => TRUE
		),
		'ext' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_ext',
			'fList' => 'name,feusers_uid,address,telephone,status,note',
			'icon' => TRUE
		)
	);
}

if (isset($_EXTCONF['where.']) && is_array($_EXTCONF['where.']))	{
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where.'] = $_EXTCONF['where.'];
}

if (
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['useFlexforms']
)	{
	// replace the output of the former CODE field with the flexform
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][5][] = 'EXT:'.TT_PRODUCTS_EXT.'/hooks/class.tx_ttproducts_cms.php:&tx_ttproducts_cms->pmDrawItem';
}


if (TYPO3_MODE=='FE')	{ // hooks for FE extensions

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed'][TT_PRODUCTS_EXT] = 'EXT:'.TT_PRODUCTS_EXT.'/hooks/class.tx_ttproducts_hooks_fe.php:&tx_ttproducts_hooks_fe->login_confirmed';

	// add the table enhancements to the FE
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_products']['extendingTCA'][] = TT_PRODUCTS_EXT;
}

  // Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','tt_content.CSS_editor.ch.tt_products = < plugin.tt_products.CSS_editor ',43);

$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/mydashboard/class.tx_mydashboard_widgetmgm.php']['addWidget']['tt_products_latest'] = 'EXT:tt_products/widgets/class.tx_ttproducts_latest.php:tx_ttproducts_latest';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['tx_double6'] = 'EXT:'.DIV2007_EXTkey.'/hooks/class.tx_div2007_hooks_eval.php';



// support for new Caching Framework

$optionsArray = array();
$backendCache = 't3lib_cache_backend_NullBackend';

// Register cache 'tt_products_cache'
if (
	isset($_EXTCONF['cache.']) &&
	$_EXTCONF['cache.']['backend'] &&
	!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache'])
) {
	if (
		isset($_EXTCONF['cache.']['options.']) &&
		$_EXTCONF['cache.']['options.']['servers'] != ''
	) {
		$optionsArray['servers'] = array($_EXTCONF['cache.']['options.']['servers']);
	}

	if (
		extension_loaded('memcache') &&
		isset($optionsArray['servers']) &&
		is_array($optionsArray['servers'])
	) {
		$backendCache = 't3lib_cache_backend_MemcachedBackend';
	} else if (extension_loaded('apc') || extension_loaded('apcu')) {
		$backendCache = 't3lib_cache_backend_ApcBackend';
	} else if (extension_loaded('redis')) {
		$backendCache = 't3lib_cache_backend_RedisBackend';
	}
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache'] = array();
}

// Define string frontend as default frontend, this must be set with TYPO3 4.5 and below
// and overrides the default variable frontend of 4.6
if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['frontend'])) {
    $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['frontend'] = 't3lib_cache_frontend_StringFrontend';
}

if ($typoVersion >= '4006000') {

	if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['backend'])) {
		$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['backend'] = $backendCache;
	}

	if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options'])) {
		$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options'] = $optionsArray;
	}
}


if (t3lib_extMgm::isLoaded('searchbox')) {
	t3lib_extMgm::addPItoST43($_EXTKEY, 'pi_search/class.tx_ttproducts_pi_search.php', '_pi_search', 'list_type', 1 );
}

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi_int/class.tx_ttproducts_pi_int.php', '_pi_int', 'list_type', 1 );

if ($typoVersion < '4006000') {

	t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_ttproducts_pi1.php', '_pi1', 'list_type', 1);

	// Define database backend as backend for 4.5 and below (default in 4.6)
	if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['backend'])) {
        $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['backend'] = 't3lib_cache_backend_DbBackend';
    }
	// Define data and tags table for 4.5 and below (obsolete in 4.6)
	if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options'])) {
        $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options'] = array();
    }
	if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options']['cacheTable'])) {
        $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options']['cacheTable'] = 'tt_products_cache';
    }
	if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options']['tagsTable'])) {
        $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options']['tagsTable'] = 'tt_products_cache_tags';
    }

	t3lib_cache::initializeCachingFramework();
} else {
	// add missing setup for the tt_content "list_type = 5" which is used by tt_products
	$addLine = 'tt_content.list.20.5 = < plugin.tt_products';
	t3lib_extMgm::addTypoScript(TT_PRODUCTS_EXT, 'setup', '
	# Setting ' . TT_PRODUCTS_EXT . ' plugin TypoScript
	' . $addLine . '
	', 43);
}


if (
	isset($GLOBALS['typo3CacheFactory']) &&
	is_object($GLOBALS['typo3CacheFactory']) &&
	($typoVersion >= '4006000' || TYPO3_UseCachingFramework)
) {
    // register the cache in BE so it will be cleared with "clear all caches"
    try {
		$cacheName = 'tt_products_cache';
		if (!$GLOBALS['typo3CacheManager']->hasCache($cacheName)) {
			$GLOBALS['typo3CacheFactory']->create(
				'tt_products_cache',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['frontend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['options']
			);
		}
    } catch (t3lib_cache_exception_DuplicateIdentifier $e) {
        // do nothing, a tt_products_cache cache already exists
    }
}



?>