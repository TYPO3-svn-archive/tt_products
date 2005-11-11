<?php
if (!defined ("TYPO3_MODE"))    die ("Access denied.");

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

if (!defined ('PATH_BE_table')) {
	define('PATH_BE_table', t3lib_extMgm::extPath(TABLE_EXTkey));
}

if (!defined ('FH_LIBRARY_EXTkey')) {
	define('FH_LIBRARY_EXTkey','fh_library');
}

if (!defined ('PATH_BE_fh_library')) {
	define('PATH_BE_fh_library', t3lib_extMgm::extPath(FH_LIBRARY_EXTkey));
}


if (!defined ('TT_PRODUCTS_DIV_DLOG')) {
	define('TT_PRODUCTS_DIV_DLOG', '0');	// for development error logging
}

t3lib_extMgm::addUserTSConfig('
        options.saveDocNew.tt_products=1
');

t3lib_extMgm::addUserTSConfig('
        options.saveDocNew.tt_products_cat=1
');

t3lib_extMgm::addUserTSConfig('
        options.saveDocNew.tt_products_articles=1
');

if (!defined($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'])) {
	$TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'] = 0; //for page as categories:  1
}
if (!defined($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['useFlexforms'])) {
	$TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['useFlexforms'] = 1;  // set this to 1 !
}
if (!defined($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['alternativeProducts'])) {
	$TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['alternativeProducts'] = '';
}


//$TYPO3_CONF_VARS['EXTCONF']['cms']['db_layout']['addTables']['tt_products'][0] = array(
//    'fList' => 'title,subtitle,price,inStock',
//    'icon' => TRUE
//);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_ttproducts_pi1 = < plugin.tx_ttproducts_pi1.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_ttproducts_pi1.php','_pi1','list_type',0);

?>
