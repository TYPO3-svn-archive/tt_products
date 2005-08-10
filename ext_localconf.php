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

t3lib_extMgm::addUserTSConfig('
        options.saveDocNew.tt_products=1
');

t3lib_extMgm::addUserTSConfig('
        options.saveDocNew.tt_products_cat=1
');

$TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'] = 0; //for page as categories:  1

?>
