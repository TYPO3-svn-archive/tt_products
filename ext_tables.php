<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tt_products'] = Array (
	'ctrl' => Array (
		'label' => 'title',
		'default_sortby' => 'ORDER BY title',
		'tstamp' => 'tstamp',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'delete' => 'deleted',
		'title' => 'LLL:EXT:tt_products/locallang_tca.php:tt_products',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'thumbnail' => 'image',
		'useColumnsForDefaultValues' => 'category',
		'mainpalette' => 1,
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'ext_icon.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php'
	)
);
$TCA['tt_products_cat'] = Array (
	'ctrl' => Array (
		'label' => 'title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'crdate' => 'crdate',
		'title' => 'LLL:EXT:tt_products/locallang_tca.php:tt_products_cat',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php'
	)
);
t3lib_extMgm::addPlugin(Array('LLL:EXT:tt_products/locallang_tca.php:tt_products', '5'));
t3lib_extMgm::allowTableOnStandardPages('tt_products');
t3lib_extMgm::addToInsertRecords('tt_products');
if (TYPO3_MODE=='BE')	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_ttproducts_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'class.tx_ttproducts_wizicon.php';

t3lib_extMgm::addLLrefForTCAdescr('tt_products','EXT:tt_products/locallang_csh_ttprod.php');
t3lib_extMgm::addLLrefForTCAdescr('tt_products_cat','EXT:tt_products/locallang_csh_ttprodc.php');

?>