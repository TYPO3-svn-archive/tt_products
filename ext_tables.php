<?php
if (!defined ('TYPO3_MODE'))	die ('Access denied.');

t3lib_extMgm::addStaticFile(TT_PRODUCTS_EXTkey, 'static/old_style/', 'Shop System Old Style');
t3lib_extMgm::addStaticFile(TT_PRODUCTS_EXTkey, 'static/test/', 'Shop System Test');
$typoVersion = t3lib_div::int_from_ver($GLOBALS['TYPO_VERSION']); 
		
$TCA['tt_products'] = Array (
	'ctrl' => Array (
		'title' =>'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products',
		'label' => 'title',
		'label_alt' => 'subtitle',
		'default_sortby' => 'ORDER BY title',
		'tstamp' => 'tstamp',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'crdate' => 'crdate',
//		'sortby' => 'sorting',
		'delete' => 'deleted',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'thumbnail' => 'image',
		'useColumnsForDefaultValues' => 'category',
		'mainpalette' => 1,
		'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
		'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products.gif',
		'dividers2tabs' => '1',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden,title,subtitle,itemnumber,price,price2,note,category,address,inStock,tax,weight,bulkily,offer,highlight,directcost,color,size,description,gradings,unit,unit_factor,www,datasheet,special_preparation,image,hidden,starttime,endtime',
	)
);

$TCA['tt_products_cat'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_cat',
		'label' => 'title',
		'label_alt' => 'subtitle',
		'default_sortby' => 'ORDER BY title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'crdate' => 'crdate',
//		'sortby' => 'sorting',
		'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_cat.gif',
		'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php'
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden,title,note,image,email',
	)
);


$TCA['tt_products_language'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_language',
		'label' => 'title',
		'default_sortby' => 'ORDER BY title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'crdate' => 'crdate',
//		'sortby' => 'sorting',
		'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_language.gif',
		'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'title,subtitle,prod_uid,note,unit,www,datasheet,hidden,starttime,endtime',
	)
);


$TCA['tt_products_cat_language'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_cat_language',
		'label' => 'title',
		'default_sortby' => 'ORDER BY title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'crdate' => 'crdate',
//		'sortby' => 'sorting',
		'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_cat_language.gif',
		'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden,cat_uid, sys_language_uid,title',
	)
);

$TCA['tt_products_articles'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_articles',
		'label' => 'title',
		'default_sortby' => 'ORDER BY title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'crdate' => 'crdate',
//		'sortby' => 'sorting',
		'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_articles.gif',
		'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden,cat_uid, sys_language_uid,title',
	)
);


$TCA['tt_products_emails'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_emails',
		'label' => 'name',
		'default_sortby' => 'ORDER BY name',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'crdate' => 'crdate',
//		'sortby' => 'sorting',
		'mainpalette' => 1,
		'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_emails.gif',
		'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden',
	)
);


$TCA['sys_products_cards'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_cards',
		'label' => 'cc_number',
		'default_sortby' => 'ORDER BY cc_number',
		'tstamp' => 'tstamp',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'crdate' => 'crdate',
		'iconfile' => PATH_ttproducts_icon_table_rel.'sys_products_cards.gif',
		'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => '',
	)
);


$TCA['sys_products_orders'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders',
		'label' => 'name',
		'default_sortby' => 'ORDER BY name',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'crdate' => 'crdate',
		'mainpalette' => 1,
		'iconfile' => PATH_ttproducts_icon_table_rel.'sys_products_orders.gif',
		'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden',
	)
);


t3lib_div::loadTCA('tt_content');

if ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['useFlexforms']==1)	{
	$TCA['tt_content']['types']['list']['subtypes_excludelist']['5']='layout,select_key';
	$TCA['tt_content']['types']['list']['subtypes_addlist']['5']='pi_flexform';
	t3lib_extMgm::addPiFlexFormValue('5', 'FILE:EXT:'.TT_PRODUCTS_EXTkey.'/flexform_ds_pi1.xml');
} else if ($TYPO3_CONF_VARS['EXTCONF'][TT_PRODUCTS_EXTkey]['pageAsCategory'] == 1) {
	$tempStr = 'LLL:EXT:tt_products/locallang_db.xml:tt_content.tt_products_code.I.';
	if ($typoVersion < 3008000)	{
		$tempStr = 'LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code.I.';
	}
	$tempColumns = Array (
		'tt_products_code' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_content.tt_products_code',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array($tempStr.'0',  'LIST'),
					Array($tempStr.'1',  'LISTOFFERS'),
					Array($tempStr.'2',  'LISTHIGHLIGHTS'),
					Array($tempStr.'3',  'LISTNEWITEMS'),
					Array($tempStr.'4',  'SINGLE'),
					Array($tempStr.'5',  'SEARCH'),
					Array($tempStr.'6',  'MEMO'),
					Array($tempStr.'7',  'BASKET'),
					Array($tempStr.'8',  'INFO'),
					Array($tempStr.'9',  'PAYMENT'),
					Array($tempStr.'10', 'FINALIZE'),
					Array($tempStr.'11', 'OVERVIEW'),
					Array($tempStr.'12', 'TRACKING'),
					Array($tempStr.'13', 'BILL'),
					Array($tempStr.'14', 'DELIVERY'),
					Array($tempStr.'15', 'HELP'),
					Array($tempStr.'16', 'CURRENCY'),
					Array($tempStr.'17', 'ORDERS'),
					Array($tempStr.'18', 'LISTGIFTS'),
					Array($tempStr.'19', 'LISTCAT'),
					Array($tempStr.'20', 'LISTARTICLES')
				),
			)
		)
	);

	if ($typoVersion < 3008000)	{
		$tempColumns['tt_products_code']['label'] = 'LLL:EXT:tt_products/locallang_tca.php:tt_content.tt_products_code';
	}
	t3lib_extMgm::addTCAcolumns('tt_content',$tempColumns,1);
	$TCA['tt_content']['types']['list']['subtypes_excludelist']['5']='layout,select_key';
	$TCA['tt_content']['types']['list']['subtypes_addlist']['5']='tt_products_code;;;;1-1-1';
}

if ($typoVersion < 3008000)	{
	// overwrite the values for former language files
	$TCA['tt_products']['ctrl']['title'] = 'LLL:EXT:tt_products/locallang_tca.php:tt_products';
	$TCA['tt_products_cat']['ctrl']['title'] = 'LLL:EXT:tt_products/locallang_tca.php:tt_products_cat';
	$TCA['tt_products_language']['ctrl']['title'] = 'LLL:EXT:tt_products/locallang_tca.php:tt_products_language';
	$TCA['tt_products_cat_language']['ctrl']['title'] = 'LLL:EXT:tt_products/locallang_tca.php:tt_products_cat_language';
	$TCA['tt_products_articles']['ctrl']['title'] =  'LLL:EXT:tt_products/locallang_tca.php:tt_products_articles';
	$TCA['tt_products_emails']['ctrl']['title'] =  'LLL:EXT:tt_products/locallang_tca.php:tt_products_emails';
	$TCA['sys_products_orders']['ctrl']['title'] = 'LLL:EXT:tt_products/locallang_tca.php:sys_products_orders';
	$TCA['sys_products_cards']['ctrl']['title'] = 'LLL:EXT:tt_products/locallang_tca.php:sys_products_cards';
	t3lib_extMgm::addPlugin(Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_tca.php:tt_content.list_type_pi1','5'),'list_type');
} else {
	t3lib_extMgm::addPlugin(Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_content.list_type_pi1','5'),'list_type');
}

t3lib_extMgm::allowTableOnStandardPages('tt_products');
t3lib_extMgm::allowTableOnStandardPages('tt_products_language');
t3lib_extMgm::allowTableOnStandardPages('tt_products_cat');
t3lib_extMgm::allowTableOnStandardPages('tt_products_cat_language');
t3lib_extMgm::allowTableOnStandardPages('tt_products_articles');
t3lib_extMgm::allowTableOnStandardPages('tt_products_emails');
t3lib_extMgm::allowTableOnStandardPages('sys_products_cards');
t3lib_extMgm::allowTableOnStandardPages('sys_products_orders');


//t3lib_extMgm::addToInsertRecords('tt_products');

if (TYPO3_MODE=='BE')  
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_ttproducts_wizicon'] = PATH_BE_ttproducts.'class.tx_ttproducts_wizicon.php';

t3lib_extMgm::addLLrefForTCAdescr('tt_products','EXT:'.TT_PRODUCTS_EXTkey.'//locallang_csh_ttprod.php');
t3lib_extMgm::addLLrefForTCAdescr('tt_products_cat','EXT:'.TT_PRODUCTS_EXTkey.'//locallang_csh_ttprodc.php');
t3lib_extMgm::addLLrefForTCAdescr('tt_products_articles','EXT:'.TT_PRODUCTS_EXTkey.'//locallang_csh_ttproda.php');
t3lib_extMgm::addLLrefForTCAdescr('tt_products_emails','EXT:'.TT_PRODUCTS_EXTkey.'//locallang_csh_ttprode.php');
t3lib_extMgm::addLLrefForTCAdescr('sys_products_cards','EXT:'.TT_PRODUCTS_EXTkey.'//locallang_csh_ttprodca.php');
t3lib_extMgm::addLLrefForTCAdescr('sys_products_orders','EXT:'.TT_PRODUCTS_EXTkey.'//locallang_csh_ttprodo.php');


$tempColumns = Array (
	'tt_products_memoItems' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:fe_users.tt_products_memoItems',
		'config' => Array (
			'type' => 'input',
			'size' => '10',
			'max' => '256'
		)
	),
	'tt_products_discount' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:fe_users.tt_products_discount',
		'config' => Array (
			'type' => 'input',
			'size' => '4',
			'max' => '4',
			'eval' => 'int',
			'checkbox' => '0',
			'range' => Array (
				'upper' => '1000',
				'lower' => '1'
			),
			'default' => 0
		)
	),
	'tt_products_creditpoints' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:fe_users.tt_products_creditpoints',
		'config' => Array (
			'type' => 'input',
			'size' => '5',
			'max' => '20',
			'eval' => 'trim,integer',
		)
	),
	'tt_products_vouchercode' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:fe_users.tt_products_vouchercode',
		'config' => Array (
			'type' => 'input',
			'size' => '20',
			'max' => '256'
		)
	),
	'tt_products_vat' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:fe_users.tt_products_vat',
		'config' => Array (
			'type' => 'input',
			'size' => '15',
			'max' => '15'
		)
	),
);

if ($typoVersion < 3008000)	{
	$tempColumns['tt_products_memoItems']['label'] = 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_tca.php:fe_users.tt_products_memoItems';
	$tempColumns['tt_products_discount']['label'] = 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_tca.php:fe_users.tt_products_discount';
	$tempColumns['tt_products_creditpoints']['label'] = 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_tca.php:fe_users.tt_products_creditpoints';
	$tempColumns['tt_products_vouchercode']['label'] =  'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_tca.php:fe_users.tt_products_vouchercode';
}

t3lib_div::loadTCA('fe_users');

t3lib_extMgm::addTCAcolumns('fe_users',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('fe_users','tt_products_creditpoints;;;;1-1-1,tt_products_vouchercode;;;;1-1-1,tt_products_memoItems;;;;1-1-1,tt_products_discount;;;;1-1-1,tt_products_vat;;;;1-1-1');


?>
