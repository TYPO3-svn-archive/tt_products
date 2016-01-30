<?php
if (!defined ('TYPO3_MODE'))	die ('Access denied.');

$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
	class_exists($emClass) &&
	method_exists($emClass, 'extPath')
) {
	// nothing
} else {
	$emClass = 't3lib_extMgm';
}

$tempColumns = array (
	'tt_products_memoItems' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:fe_users.tt_products_memoItems',
		'config' => array (
			'type' => 'input',
			'size' => '10',
			'max' => '256'
		)
	),
	'tt_products_discount' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:fe_users.tt_products_discount',
		'config' => array (
			'type' => 'input',
			'size' => '4',
			'max' => '8',
			'eval' => 'trim,double2',
			'checkbox' => '0',
			'range' => array (
				'upper' => '1000',
				'lower' => '1'
			),
			'default' => 0
		)
	),
	'tt_products_creditpoints' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:fe_users.tt_products_creditpoints',
		'config' => array (
			'type' => 'input',
			'size' => '5',
			'max' => '20',
			'eval' => 'trim,integer',
		)
	),
	'tt_products_vouchercode' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:fe_users.tt_products_vouchercode',
		'config' => array (
			'type' => 'input',
			'size' => '20',
			'max' => '256'
		)
	),
	'tt_products_vat' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:fe_users.tt_products_vat',
		'config' => array (
			'type' => 'input',
			'size' => '15',
			'max' => '15'
		)
	),
	'tt_products_business_partner' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_business_partner',
		'config' => array (
			'type' => 'select',
			'renderType' => 'selectSingle',
			'items' => array (
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_business_partner.I.0', '0'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_business_partner.I.1', '1'),
			),
			'size' => 1,
			'maxitems' => 1,
		)
	),
	'tt_products_organisation_form' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form',
		'config' => array (
			'type' => 'select',
			'renderType' => 'selectSingle',
			'items' => array (
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.A1', 'A1'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.A2', 'A2'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.A3', 'A3'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.BH', 'BH'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.E1', 'E1'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.E2', 'E2'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.E3', 'E3'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.E4', 'E4'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G1', 'G1'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G2', 'G2'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G3', 'G3'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G4', 'G4'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G5', 'G5'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G6', 'G6'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G7', 'G7'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.K2', 'K2'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.K3', 'K3'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.KG', 'KG'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.KO', 'KO'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.O1', 'O1'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.P', 'P'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.S1', 'S1'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.S2', 'S2'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.S3', 'S3'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.U', 'U'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.V1', 'V1'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.Z1', 'Z1'),
			),
			'size' => 1,
			'maxitems' => 1,
		)
	),
);

if (
	version_compare(TYPO3_version, '6.1.0', '<')
) {
	t3lib_div::loadTCA('fe_users');
}

call_user_func($emClass . '::addTCAcolumns', 'fe_users', $tempColumns);
call_user_func($emClass . '::addToAllTCAtypes', 'fe_users',  'tt_products_creditpoints;;;;1-1-1,tt_products_vouchercode;;;;1-1-1,tt_products_memoItems;;;;1-1-1,tt_products_discount;;;;1-1-1,tt_products_vat;;;;1-1-1,tt_products_business_partner;;;;1-1-1,tt_products_organisation_form;;;;1-1-1');


if (!$loadTcaAdditions) {

	call_user_func($emClass . '::addStaticFile', TT_PRODUCTS_EXT, 'static/old_style/', 'Shop System Old Style');
	call_user_func($emClass . '::addStaticFile', TT_PRODUCTS_EXT, 'static/css_styled/', 'Shop System CSS Styled');

	$GLOBALS['TCA']['tt_products'] = array (
		'ctrl' => array (
			'title' =>'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products',
			'label' => 'title',
			'label_alt' => 'subtitle',
			'default_sortby' => 'ORDER BY title',
			'tstamp' => 'tstamp',
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
			'delete' => 'deleted',
			'enablecolumns' => array (
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
			'transForeignTable' => 'tt_products_language',
			'searchFields' => 'title,subtitle,itemnumber,ean,note,note2,www',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'title,subtitle,itemnumber,price,price2,note,category,address,inStock,tax,weight,bulkily,offer,highlight,directcost,color,size,description,gradings,unit,unit_factor,www,datasheet,special_preparation,image,hidden,starttime,endtime',
		)
	);

	$GLOBALS['TCA']['tt_products_language'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products_language',
			'label' => 'title',
			'label_alt' => 'subtitle',
			'default_sortby' => 'ORDER BY title',
			'tstamp' => 'tstamp',
			'delete' => 'deleted',
			'enablecolumns' => array (
				'disabled' => 'hidden',
				'starttime' => 'starttime',
				'endtime' => 'endtime',
				'fe_group' => 'fe_group',
			),
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
	//		'sortby' => 'sorting',
			'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_language.gif',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
			'transOrigPointerField' => 'prod_uid',
			'transOrigPointerTable' => 'tt_products',
			'languageField' => 'sys_language_uid',
			'mainpalette' => 1,
			'searchFields' => 'title,subtitle,itemnumber,ean,note,note2,www',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'title,subtitle,prod_uid,note,unit,www,datasheet,sys_language_uid,hidden,starttime,endtime',
		)
	);

	$GLOBALS['TCA']['tt_products_mm_graduated_price'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products_mm_graduated_price',
			'label' => 'title',
			'tstamp' => 'tstamp',
			'delete' => 'deleted',
			'enablecolumns' => array (
				'disabled' => 'hidden'
			),
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
	//		'sortby' => 'sorting',
			'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_cat.gif',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php'
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'hidden,title',
		)
	);


	$GLOBALS['TCA']['tt_products_graduated_price'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products_graduated_price',
			'label' => 'title',
			'default_sortby' => 'ORDER BY title',
			'tstamp' => 'tstamp',
			'delete' => 'deleted',
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
	//		'sortby' => 'sorting',
			'delete' => 'deleted',
			'enablecolumns' => array (
				'disabled' => 'hidden',
				'starttime' => 'starttime',
				'endtime' => 'endtime',
				'fe_group' => 'fe_group',
			),
			'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_cat.gif',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
			'searchFields' => 'title,note',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'hidden,starttime,endtime,title',
		)
	);


	$GLOBALS['TCA']['tt_products_cat'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products_cat',
			'label' => 'title',
			'label_alt' => 'subtitle',
			'default_sortby' => 'ORDER BY title',
			'tstamp' => 'tstamp',
			'delete' => 'deleted',
			'enablecolumns' => array (
				'disabled' => 'hidden',
				'starttime' => 'starttime',
				'endtime' => 'endtime',
				'fe_group' => 'fe_group',
			),
			'thumbnail' => 'image',
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
	//		'sortby' => 'sorting',
			'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_cat.gif',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
			'transForeignTable' => 'tt_products_cat_language',
			'searchFields' => 'title,subtitle,note,note2',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'hidden,starttime,endtime,title,note,image,email',
		)
	);


	$GLOBALS['TCA']['tt_products_cat_language'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products_cat_language',
			'label' => 'title',
			'label_alt' => 'subtitle',
			'default_sortby' => 'ORDER BY title',
			'tstamp' => 'tstamp',
			'delete' => 'deleted',
			'enablecolumns' => array (
				'disabled' => 'hidden',
				'starttime' => 'starttime',
				'endtime' => 'endtime',
				'fe_group' => 'fe_group',
			),
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
	//		'sortby' => 'sorting',
			'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_cat_language.gif',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
			'transOrigPointerField' => 'cat_uid',
			'transOrigPointerTable' => 'tt_products_cat',
			'languageField' => 'sys_language_uid',
			'mainpalette' => 1,
			'searchFields' => 'title,subtitle,note,note2',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'hidden,starttime,endtime,cat_uid,sys_language_uid,title',
		)
	);


	$GLOBALS['TCA']['tt_products_texts'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products_texts',
			'label' => 'title',
			'label_alt' => 'marker',
			'label_alt_force' => '1',
			'default_sortby' => 'ORDER BY title',
			'tstamp' => 'tstamp',
			'delete' => 'deleted',
			'enablecolumns' => array (
				'disabled' => 'hidden',
				'starttime' => 'starttime',
				'endtime' => 'endtime',
				'fe_group' => 'fe_group',
			),
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
	//		'sortby' => 'sorting',
			'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_texts.gif',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
			'transForeignTable' => 'tt_products_texts_language',
			'searchFields' => 'title,marker,note',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'hidden,starttime,endtime,title,note',
		)
	);


	$GLOBALS['TCA']['tt_products_texts_language'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products_texts_language',
			'label' => 'title',
			'default_sortby' => 'ORDER BY title',
			'tstamp' => 'tstamp',
			'delete' => 'deleted',
			'enablecolumns' => array (
				'disabled' => 'hidden',
				'starttime' => 'starttime',
				'endtime' => 'endtime',
				'fe_group' => 'fe_group',
			),
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
	//		'sortby' => 'sorting',
			'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_texts_language.gif',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
			'transOrigPointerField' => 'text_uid',
			'transOrigPointerTable' => 'tt_products_texts',
			'languageField' => 'sys_language_uid',
			'mainpalette' => 1,
			'searchFields' => 'title,note',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'hidden,starttime,endtime,cat_uid, sys_language_uid,title',
		)
	);


	$GLOBALS['TCA']['tt_products_articles'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products_articles',
			'label' => 'title',
			'label_alt' => 'subtitle',
			'default_sortby' => 'ORDER BY title',
			'tstamp' => 'tstamp',
			'delete' => 'deleted',
			'enablecolumns' => array (
				'disabled' => 'hidden',
				'starttime' => 'starttime',
				'endtime' => 'endtime',
				'fe_group' => 'fe_group',
			),
			'thumbnail' => 'image',
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
	//		'sortby' => 'sorting',
			'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_articles.gif',
			'dividers2tabs' => '1',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
			'transForeignTable' => 'tt_products_articles_language',
			'searchFields' => 'title,subtitle,itemnumber,keyword,note,note2',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'hidden,starttime,endtime,cat_uid, title',
		)
	);


	$GLOBALS['TCA']['tt_products_articles_language'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products_articles_language',
			'label' => 'title',
			'label_alt' => 'subtitle',
			'default_sortby' => 'ORDER BY title',
			'tstamp' => 'tstamp',
			'delete' => 'deleted',
			'enablecolumns' => array (
				'disabled' => 'hidden',
				'starttime' => 'starttime',
				'endtime' => 'endtime',
				'fe_group' => 'fe_group',
			),
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
	//		'sortby' => 'sorting',
			'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_articles_language.gif',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
			'transOrigPointerField' => 'article_uid',
			'transOrigPointerTable' => 'tt_products_articles',
			'languageField' => 'sys_language_uid',
			'mainpalette' => 1,
			'searchFields' => 'title,subtitle,itemnumber,keyword,note,note2',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'hidden,starttime,endtime,article_uid, title',
		)
	);


	$GLOBALS['TCA']['tt_products_emails'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products_emails',
			'label' => 'name',
			'default_sortby' => 'ORDER BY name',
			'tstamp' => 'tstamp',
			'delete' => 'deleted',
			'enablecolumns' => array (
				'disabled' => 'hidden',
				'starttime' => 'starttime',
				'endtime' => 'endtime',
				'fe_group' => 'fe_group',
			),
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
	//		'sortby' => 'sorting',
			'mainpalette' => 1,
			'iconfile' => PATH_ttproducts_icon_table_rel.'tt_products_emails.gif',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
			'searchFields' => 'name,email',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'hidden,starttime,endtime',
		)
	);


	$GLOBALS['TCA']['sys_products_accounts'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:sys_products_accounts',
			'label' => 'ac_number',
			'label_userFunc' => 'tx_ttproducts_table_label->getLabel',
			'default_sortby' => 'ORDER BY ac_number',
			'tstamp' => 'tstamp',
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
			'iconfile' => PATH_ttproducts_icon_table_rel.'sys_products_accounts.gif',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
			'searchFields' => 'owner_name,ac_number',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => '',
		)
	);


	$GLOBALS['TCA']['sys_products_cards'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:sys_products_cards',
			'label' => 'cc_number',
			'default_sortby' => 'ORDER BY cc_number',
			'tstamp' => 'tstamp',
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
			'iconfile' => PATH_ttproducts_icon_table_rel.'sys_products_cards.gif',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
			'searchFields' => 'owner_name,cc_number',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => '',
		)
	);


	$GLOBALS['TCA']['sys_products_orders'] = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:sys_products_orders',
			'label' => 'name',
			'label_alt' => 'last_name',
			'default_sortby' => 'ORDER BY name',
			'tstamp' => 'tstamp',
			'delete' => 'deleted',
			'enablecolumns' => array (
				'disabled' => 'hidden',
			),
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'crdate' => 'crdate',
			'mainpalette' => 1,
			'iconfile' => PATH_ttproducts_icon_table_rel.'sys_products_orders.gif',
			'dynamicConfigFile' => PATH_BE_ttproducts.'tca.php',
			'dividers2tabs' => '1',
			'searchFields' => 'uid,name,first_name,last_name,vat_id,zip,city,telephone,email,giftcode,bill_no',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'hidden',
		)
	);

	if (
		version_compare(TYPO3_version, '6.1.0', '<')
	) {
		t3lib_div::loadTCA('tt_content');
	}

	if (
		!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['useFlexforms']) ||
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['useFlexforms']==1
	)	{
		$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['5']='layout,select_key';
		$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['5']='pi_flexform';
		call_user_func($emClass . '::addPiFlexFormValue', '5', 'FILE:EXT:' . TT_PRODUCTS_EXT . '/flexform_ds_pi1.xml');
	}

	call_user_func($emClass . '::addPlugin', array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_content.list_type_pi1', '5'), 'list_type');
	call_user_func($emClass . '::addToInsertRecords', 'tt_products');
	call_user_func($emClass . '::addToInsertRecords', 'tt_products_articles');
	call_user_func($emClass . '::addToInsertRecords', 'tt_products_articles_language');
	call_user_func($emClass . '::addToInsertRecords', 'tt_products_cat');
	call_user_func($emClass . '::addToInsertRecords', 'tt_products_cat_language');
	call_user_func($emClass . '::addToInsertRecords', 'tt_products_emails');
	call_user_func($emClass . '::addToInsertRecords', 'tt_products_graduated_price');
	call_user_func($emClass . '::addToInsertRecords', 'tt_products_language');
	call_user_func($emClass . '::addToInsertRecords', 'tt_products_mm_graduated_price');
	call_user_func($emClass . '::addToInsertRecords', 'tt_products_texts');
	call_user_func($emClass . '::addToInsertRecords', 'tt_products_texts_language');

	call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products');
	call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_articles');
	call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_articles_language');
	call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_cat');
	call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_cat_language');
	call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_graduated_price');
	call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_emails');
	call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_language');
	call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_mm_graduated_price');
	call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_texts');
	call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_texts_language');
	call_user_func($emClass . '::allowTableOnStandardPages', 'sys_products_accounts');
	call_user_func($emClass . '::allowTableOnStandardPages', 'sys_products_cards');
	call_user_func($emClass . '::allowTableOnStandardPages', 'sys_products_orders');

	//t3lib_extMgm::addToInsertRecords('tt_products');

	if (TYPO3_MODE=='BE')	{
		$GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']['tx_ttproducts_wizicon'] = PATH_BE_ttproducts.'class.tx_ttproducts_wizicon.php';

		call_user_func($emClass . '::insertModuleFunction',
			'web_func',
			'tx_ttproducts_modfunc1',
			PATH_BE_ttproducts . 'modfunc1/class.tx_ttproducts_modfunc1.php',
			'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xml:moduleFunction.tx_ttproducts_modfunc1',
			'wiz'
		);

		call_user_func($emClass . '::insertModuleFunction',
			'web_func',
			'tx_ttproducts_modfunc2',
			PATH_BE_ttproducts . 'modfunc2/class.tx_ttproducts_modfunc2.php',
			'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xml:moduleFunction.tx_ttproducts_modfunc2',
			'wiz'
		);

		// add folder icon
		$pageType = 'ttpproduct';
		$imageFile = PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products.gif';

		$spriteManagerClass = '\\TYPO3\\CMS\\Backend\\Sprite\\SpriteManager';
		if (
			class_exists($spriteManagerClass) &&
			method_exists($spriteManagerClass, 'addTcaTypeIcon')
		) {
			call_user_func(
				$spriteManagerClass . '::addTcaTypeIcon',
				'pages',
				'contains-' . $pageType,
				$imageFile
			);
		} else {
			t3lib_SpriteManager::addTcaTypeIcon(
				'pages',
				'contains-' . $pageType,
				$imageFile
			);
		}

		$addToModuleSelection = TRUE;
		foreach ($GLOBALS['TCA']['pages']['columns']['module']['config']['items'] as $item) {
			if ($item['1'] == $pageType) {
				$addToModuleSelection = FALSE;
				continue;
			}
		}

		if ($addToModuleSelection) {
			$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
				0 => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xml:pageModule.plugin',
				1 => $pageType,
				2 => 'EXT:' . TT_PRODUCTS_EXT . '/res/icons/table/tt_products.gif'
			);
		}
	}

	call_user_func($emClass . '::addLLrefForTCAdescr', 'tt_products', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprod.xml');
	call_user_func($emClass . '::addLLrefForTCAdescr', 'tt_products_cat', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprodc.xml');
	call_user_func($emClass . '::addLLrefForTCAdescr', 'tt_products_articles','EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttproda.xml');
	call_user_func($emClass . '::addLLrefForTCAdescr', 'tt_products_emails', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprode.xml');
	call_user_func($emClass . '::addLLrefForTCAdescr', 'tt_products_texts', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprodt.xml');
	call_user_func($emClass . '::addLLrefForTCAdescr', 'sys_products_accounts', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprodac.xml');
	call_user_func($emClass . '::addLLrefForTCAdescr',  'sys_products_cards', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprodca.xml');
	call_user_func($emClass . '::addLLrefForTCAdescr', 'sys_products_orders', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprodo.xml');

	$productsTableArray = array('tt_products', 'tt_products_language', 'tt_products_related_products_products_mm', 'tt_products_accessory_products_products_mm', 'tt_products_products_dam_mm', 'tt_products_products_note_pages_mm', 'tt_products_cat', 'tt_products_cat_language', 'tt_products_articles', 'tt_products_articles_language', 'tt_products_gifts', 'tt_products_gifts_articles_mm', 'tt_products_emails', 'tt_products_texts', 'tt_products_texts_language', 'tt_products_mm_graduated_price', 'tt_products_graduated_price', 'sys_products_accounts', 'sys_products_cards', 'sys_products_orders', 'sys_products_orders_mm_tt_products', 'fe_users', 'pages_language_overlay');

	$divClass = '\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility';

	if (
		class_exists($divClass)
	) {
		// nothing
	} else {
		$divClass = 't3lib_div';
	}

	$orderBySortingTablesArray = call_user_func($divClass . '::trimExplode', ',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);

	if (isset($orderBySortingTablesArray) && is_array($orderBySortingTablesArray))	{
		foreach ($orderBySortingTablesArray as $k => $productTable)	{
			if (in_array($productTable, $productsTableArray))	{
				$GLOBALS['TCA'][$productTable]['ctrl']['sortby'] = 'sorting';
			}
		}
	}
}

?>