<?php
if (!defined ('TYPO3_MODE'))	die ('Access denied.');

$typoVersion = t3lib_div::int_from_ver($GLOBALS['TYPO_VERSION']);
$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it here:

if (!defined ('TT_PRODUCTS_EXTkey')) {
	define('TT_PRODUCTS_EXTkey',$_EXTKEY);
}

if (!defined ('PATH_BE_ttproducts')) {
	define('PATH_BE_ttproducts', t3lib_extMgm::extPath(TT_PRODUCTS_EXTkey));
}

if (!defined ('PATH_BE_ttproducts_rel')) {
	define('PATH_BE_ttproducts_rel', t3lib_extMgm::extRelPath(TT_PRODUCTS_EXTkey));
}

if (!defined ('PATH_FE_ttproducts_rel')) {
	define('PATH_FE_ttproducts_rel', t3lib_extMgm::siteRelPath(TT_PRODUCTS_EXTkey));
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

if (!defined ('TT_ADDRESS_EXTkey')) {
	define('TT_ADDRESS_EXTkey','tt_address');
}

if (!defined ('PARTNER_EXTkey')) {
	define('PARTNER_EXTkey','partner');
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

if (!defined ('TAXAJAX_EXTkey')) {
	define('TAXAJAX_EXTkey','taxajax');
}

if (!defined ('DAM_EXTkey')) {
	define('DAM_EXTkey','dam');
}

if (t3lib_extMgm::isLoaded(TAXAJAX_EXTkey)) {
	if (!defined ('PATH_BE_taxajax')) {
		define('PATH_BE_taxajax', t3lib_extMgm::extPath(TAXAJAX_EXTkey));
	}
	if (!defined ('PATH_FE_taxajax_rel')) {
		define('PATH_FE_taxajax_rel', t3lib_extMgm::siteRelPath(TAXAJAX_EXTkey));
	}
	$GLOBALS['TYPO3_CONF_VARS'] ['FE']['eID_include'][TT_PRODUCTS_EXTkey] =  'EXT:'.TT_PRODUCTS_EXTkey.'/eid/class.tx_ttproducts_eid.php' ;
}

t3lib_extMgm::addUserTSConfig('options.saveDocNew.tt_products=1');
t3lib_extMgm::addUserTSConfig('options.saveDocNew.tt_products_cat=1');
t3lib_extMgm::addUserTSConfig('options.saveDocNew.tt_products_articles=1');

if (!defined($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'] = $_EXTCONF['pageAsCategory'];
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['useFlexforms'] = $_EXTCONF['useFlexforms'];
if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['useFlexforms'] = '1';
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['addressTable'] = $_EXTCONF['addressTable'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['imageFolder']  = $_EXTCONF['imageFolder'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['orderBySortingTables'] = t3lib_div::trimExplode(',',$_EXTCONF['orderBySortingTables']);

if (!t3lib_extMgm::isLoaded(DIV2007_EXTkey)) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['useFlexforms'] = 0;
}

if (!defined($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['alternativeProducts'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['alternativeProducts'] = '';
}

if (($_EXTCONF['usePatch1822'] || $typoVersion >= 4004000) &&
!defined($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products']['MENU'])) {
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
}

if (isset($_EXTCONF['where.']) && is_array($_EXTCONF['where.']))	{
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['where.'] = $_EXTCONF['where.'];
}

if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['useFlexforms'])	{
	// replace the output of the former CODE field with the flexform
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][5][] = 'EXT:'.TT_PRODUCTS_EXTkey.'/hooks/class.tx_ttproducts_cms.php:&tx_ttproducts_cms->pmDrawItem';
}

  // Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','tt_content.CSS_editor.ch.tt_products = < plugin.tt_products.CSS_editor ',43);

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_ttproducts_pi1.php','_pi1','list_type',1 );

$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/mydashboard/class.tx_mydashboard_widgetmgm.php']['addWidget']['tt_products_latest'] = 'EXT:tt_products/widgets/class.tx_ttproducts_latest.php:tx_ttproducts_latest';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['tx_double6'] = 'EXT:'.DIV2007_EXTkey.'/hooks/class.tx_div2007_hooks_eval.php';

?>
