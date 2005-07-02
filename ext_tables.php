<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

/* mkl:
if (TYPO3_MODE=="BE")   include_once(t3lib_extMgm::extPath("mkl_products")."class.tx_ttproducts_language.php");

t3lib_div::loadTCA("tt_content");
$TCA["tt_content"]["types"]["list"]["subtypes_excludelist"]["5"]="layout,select_key";
$TCA["tt_content"]["types"]["list"]["subtypes_addlist"]["5"]="pi_flexform";
t3lib_extMgm::addPiFlexFormValue('5', 'FILE:EXT:mkl_products/flexform_ds_pi.xml');
*/

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
/* mkl:
$TCA["tt_products_language_overlay"] = Array (
	"ctrl" => Array (
		"label" => "title",
		"default_sortby" => "ORDER BY title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"prependAtCopy" => "LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy",
		"crdate" => "crdate",
		"title" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_language_overlay",
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_tt_products_language_overlay.gif",
	)
);

$TCA["tt_products_cat_language_overlay"] = Array (
	"ctrl" => Array (
		"label" => "title",
		"default_sortby" => "ORDER BY title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"prependAtCopy" => "LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy",
		"crdate" => "crdate",
		"title" => "LLL:EXT:mkl_products/locallang_tca.php:tt_products_cat_language_overlay",
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_tt_products_cat_language_overlay.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden,cat_uid, sys_language_uid,title",
	)
);

t3lib_extMgm::addPlugin(Array("LLL:EXT:mkl_products/locallang_tca.php:tt_content.list_type_pi1","5"),"list_type");

*/
t3lib_extMgm::addPlugin(Array('LLL:EXT:tt_products/locallang_tca.php:tt_products', '5'));
t3lib_extMgm::allowTableOnStandardPages('tt_products');
/* mkl:
t3lib_extMgm::allowTableOnStandardPages("tt_products_language_overlay");
t3lib_extMgm::allowTableOnStandardPages("tt_products_cat");
t3lib_extMgm::allowTableOnStandardPages("tt_products_cat_language_overlay");
*/
t3lib_extMgm::addToInsertRecords('tt_products');
if (TYPO3_MODE=='BE')	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_ttproducts_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'class.tx_ttproducts_wizicon.php';


$tempColumns = Array (
	'tt_products_code' => Array (
		'exclude' => 0,
		'label' => 'LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code',
		'config' => Array (
			'type' => 'select',
			'items' => Array (
				Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.0', 'LIST'),
				Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.1', 'LISTOFFERS'),
                Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.2', 'LISTHIGHLIGHTS'),
                Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.3', 'LISTNEWITEMS'),
                Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.4', 'SINGLE'),
                Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.5', 'SEARCH'),
                Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.6', 'MEMO'),
                Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.7', 'BASKET'),
                Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.8', 'INFO'),
                Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.9', 'PAYMENT'),
                Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.10', 'FINALIZE'),
				Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.11', 'OVERVIEW'),
				Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.12', 'TRACKING'),
				Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.13', 'BILL'),
				Array('LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.14', 'DELIVERY'),
			),
		)
	)
);

if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_products']['pageAsCategory'] == 1) {
	t3lib_div::loadTCA('tt_content');
	t3lib_extMgm::addTCAcolumns('tt_content',$tempColumns,1);
	$TCA['tt_content']['types']['list']['subtypes_excludelist']['5']='layout,select_key';
	$TCA['tt_content']['types']['list']['subtypes_addlist']['5']='tt_products_code;;;;1-1-1';
}

t3lib_extMgm::addLLrefForTCAdescr('tt_products','EXT:tt_products/locallang_csh_ttprod.php');
t3lib_extMgm::addLLrefForTCAdescr('tt_products_cat','EXT:tt_products/locallang_csh_ttprodc.php');

$tempColumns = Array (
	'tt_products_memoItems' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:tt_products/locallang_tca.php:fe_users.tt_products_memoItems',
		'config' => Array (
			'type' => 'input',
			'size' => '10',
			'max' => '256'
		)
	),
	'tt_products_discount' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:tt_products/locallang_tca.php:fe_users.tt_products_discount',
		'config' => Array (
			'type' => 'input',
			'size' => '4',
			'max' => '4',
			'eval' => 'int',
			'checkbox' => '0',
			'range' => Array (
				'upper' => '1000',
				'lower' => '10'
			),
			'default' => 0
		)
	),
);
t3lib_div::loadTCA('fe_users');
t3lib_extMgm::addTCAcolumns('fe_users',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('fe_users', 'tt_products_memoItems;;;;1-1-1,tt_products_discount;;;;1-1-1');
?>
