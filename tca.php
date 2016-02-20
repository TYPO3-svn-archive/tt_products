<?php

/* $Id$ */


$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
	class_exists($emClass) &&
	method_exists($emClass, 'extPath')
) {
	// nothing
} else {
	$emClass = 't3lib_extMgm';
}

$divClass = '\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility';

if (
	class_exists($divClass)
) {
	// nothing
} else {
	$divClass = 't3lib_div';
}


if (
	version_compare(TYPO3_version, '6.2.0', '<')
) {
	if (
		isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.'])
		&& is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.'])
	) {
		$excludeArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.'];
	}

	$imageFolder = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['imageFolder'];
	if (!$imageFolder) {
		$imageFolder = 'uploads/pics';
	}

	$whereCategory = '';
	if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where.']) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where.']) && isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where.']['category'])) {
		$whereCategory = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where.']['category'];
	}

	$endtimeYear = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['endtimeYear'];

	// ******************************************************************
	// This is the standard TypoScript products table, tt_products
	// ******************************************************************
	$GLOBALS['TCA']['tt_products'] = array (
		'ctrl' => $GLOBALS['TCA']['tt_products']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,title,subtitle,accessory_uid,related_uid,itemnumber,ean,price,price2,creditpoints,deposit,graduated_price_uid,article_uid,note,note2,note_uid,text_uid,download_type,download_info,download_uid,category,address,inStock,basketminquantity,tax_id,weight,usebydate,bulkily,offer,highlight,bargain,directcost,color,color2,color3,size,size2,size3,description,gradings,material,quality,additional,damcat,unit,unit_factor,www,datasheet,special_preparation,image,sellstarttime,sellendtime,shipping,shipping2,handling,dam_uid'
		),
		'columns' => array (
			't3ver_label' => array (
				'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
				'config' => array (
					'type' => 'input',
					'size' => '30',
					'max'  => '30',
				)
			),
			'sorting' => Array (
				'config' => Array (
					'type' => 'passthrough',
				)
			),
			'hidden' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
				'config' => array (
					'type' => 'check'
				)
			),
			'tstamp' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'crdate' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'starttime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0'
				)
			),
			'endtime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0',
					'range' => array (
						'upper' => mktime(0, 0, 0, 12, 31, $endtimeYear),
						'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
					)
				)
			),
			'fe_group' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('', 0),
						array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
						array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
					),
					'foreign_table' => 'fe_groups'
				)
			),
			'title' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.title',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '256'
				)
			),
			'subtitle' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.subtitle',
				'config' => array (
					'type' => 'text',
					'rows' => '3',
					'cols' => '20',
					'max' => '512'
				)
			),
			'prod_uid' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.prod_uid',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tt_products',
					'foreign_table' => 'tt_products',
					'foreign_table_where' => ' ORDER BY tt_products.uid',
					'size' => 3,
					'minitems' => 0,
					'maxitems' => 3,
				)
			),
			'itemnumber' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.itemnumber',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'eval' => 'trim',
					'max' => '120'
				)
			),
			'ean' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.ean',
				'config' => array (
					'type' => 'input',
					'size' => '48',
					'eval' => 'trim',
					'max' => '48'
				)
			),
			'price' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.price',
				'config' => array (
					'type' => 'input',
					'size' => '20',
					'eval' => 'trim,double2',
					'max' => '20'
				)
			),
			'price2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.price2',
				'config' => array (
					'type' => 'input',
					'size' => '20',
					'eval' => 'trim,double2',
					'max' => '20'
				)
			),
			'creditpoints' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.creditpoints',
				'config' => array (
					'type' => 'input',
					'size' => '12',
					'eval' => 'int',
					'max' => '12'
				)
			),
			'deposit' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.deposit',
				'config' => array (
					'type' => 'input',
					'size' => '20',
					'eval' => 'trim,double2',
					'max' => '20'
				)
			),
			'graduated_price_uid' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.graduated_price_uid',
				'config' => array (
					'type' => 'inline',
					'appearance' => array ('collapseAll' => TRUE, 'newRecordLinkAddTitle' => TRUE, 'useCombination' => TRUE),
					'foreign_table' => 'tt_products_mm_graduated_price',
					'foreign_field' => 'product_uid',
					'foreign_sortby' => 'productsort',
					'foreign_label' => 'graduated_price_uid',
					'foreign_selector' => 'graduated_price_uid',
					'foreign_unique' => 'graduated_price_uid',
					'maxitems' => 10
				),
			),
			'article_uid' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.article_uid',
				'config' => array (
					'type' => 'inline',
					'appearance' => array ('collapseAll' => TRUE, 'newRecordLinkAddTitle' => TRUE, 'useCombination' => TRUE),
					'foreign_table' => 'tt_products_products_mm_articles',
					'foreign_field' => 'uid_local',
					'foreign_sortby' => 'sorting',
					'foreign_label' => 'uid_foreign',
					'foreign_selector' => 'uid_foreign',
					'foreign_unique' => 'uid_foreign',
					'maxitems' => 1000
				),
			),
			'note' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				)
			),
			'note2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note2',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '2'
				)
			),
			'note_uid' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note_uid',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'pages',
					'MM' => 'tt_products_products_note_pages_mm',
					'size' => '2',
					'autoSizeMax' => '12',
					'minitems' => '0',
					'maxitems' => '30',
					'show_thumbs' => '1',
				),
			),
			'text_uid' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.text_uid',
				'config' => array (
					'type' => 'inline',
					'foreign_table' => 'tt_products_texts',
					'foreign_field' => 'parentid',
					'foreign_table_field' => 'parenttable',
					'maxitems' => 20
				),
			),
			'download_type' => array (
				'label' => 'LLL:EXT:cms/locallang_ttc.php:list_type',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('','')
					),
					'default' => '',
					'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
				)
			),
			'download_info' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.download_info',
				'config' => array (
					'type' => 'flex',
					'ds_pointerField' => 'download_type',
					'ds' => array (
						'default' => '
							<T3DataStructure>
								<ROOT>
									<type>array</type>
									<el>
									<limitedToDomain>
										<TCEforms>
											<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.download_info.limitedToDomain</label>
											<config>
												<type>check</type>
											</config>
										</TCEforms>
									</limitedToDomain>
									</el>
								</ROOT>
								<meta>
									<langDisable>1</langDisable>
								</meta>
							</T3DataStructure>
							',
					)
				)
			),
			'unit_factor' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.unit_factor',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'eval' => 'double',
					'default' => '1',
					'max' => '6'
				)
			),
			'unit' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.unit',
				'config' => array (
					'type' => 'input',
					'size' => '20',
					'eval' => 'trim',
					'max' => '20'
				)
			),
			'www' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.www',
				'config' => array (
					'type' => 'input',
					'eval' => 'trim',
					'size' => '30',
					'max' => '160'
				)
			),
			'category' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.category',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('', 0)
					),
					'foreign_table' => 'tt_products_cat',
					'foreign_table_where' => $whereCategory
				)
			),
			'damcat' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.damcat',
				'displayCond' => 'EXT:' . DAM_EXT . ':LOADED:true',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tx_dam_cat',
					'MM' => 'tt_products_products_mm_damcat',
					'size' => '12',
					'autoSizeMax' => '30',
					'minitems' => '0',
					'maxitems' => '30',
					'show_thumbs' => '1',
				),
			),
			'address' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.address',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addressTable'],
					'size' => 1,
					'minitems' => 0,
					'maxitems' => 1,
				),
			),
			'inStock' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.inStock',
				'config' => array (
					'type' => 'input',
					'size' => '6',
					'max' => '6',
					'eval' => 'int',
					'default' => '1'
				)
			),
			'basketminquantity' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.basketminquantity',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'eval' => 'trim,double2',
					'max' => '10'
				)
			),
			'datasheet' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.datasheet',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'file',
					'allowed' => 'doc,htm,html,pdf,sxw,txt,xls,gif,jpg,png',
					'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
					'uploadfolder' => 'uploads/tx_ttproducts/datasheet',
					'show_thumbs' => '1',
					'size' => '3',
					'maxitems' => '20',
					'minitems' => '0'
				)
			),
			'dam_uid' => array (
				'exclude' => 1,
				'displayCond' => 'EXT:' . DAM_EXT . ':LOADED:true',
				'label' => 'LLL:EXT:' . DAM_EXT . '/locallang_db.xml:tx_dam_item',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tx_dam',
					'MM' => 'tt_products_products_dam_mm',
					'foreign_table' => 'tx_dam',
					'foreign_table_where' => ' ORDER BY tx_dam.title',
					'size' => 10,
					'minitems' => 0,
					'maxitems' => 20,
				)
			),
			'tax_id' => array (
				'exclude' => 1,
				'displayCond' => 'EXT:' . STATIC_INFO_TABLES_TAXES_EXT . ':LOADED:true',
				'label' => 'LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id.I.0', '0'),
						array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id.I.1', '1'),
						array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id.I.2', '2'),
						array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id.I.3', '3'),
						array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id.I.4', '4'),
						array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xml:static_taxes.tx_rate_id.I.5', '5'),
					),
				)
			),
			'weight' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.weight',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'max' => '20',
					'eval' => 'trim,tx_double6',
				)
			),
			'usebydate' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.usebydate',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0'
				)
			),
			'bulkily' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.bulkily',
				'config' => array (
					'type' => 'check',
				)
			),
			'offer' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.offer',
				'config' => array (
					'type' => 'check',
				)
			),
			'highlight' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.highlight',
				'config' => array (
					'type' => 'check',
				)
			),
			'bargain' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.bargain',
				'config' => array (
					'type' => 'check',
				)
			),
			'directcost' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.directcost',
				'config' => array (
					'type' => 'input',
					'size' => '12',
					'eval' => 'trim,double2',
					'max' => '20'
				)
			),
			'accessory_uid' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.accessory_uid',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tt_products',
					'MM' => 'tt_products_accessory_products_products_mm',
					'foreign_table' => 'tt_products',
					'foreign_table_where' => ' ORDER BY tt_products.uid',
					'size' => 10,
					'selectedListStyle' => 'width:450px',
					'minitems' => 0,
					'maxitems' => 12,
				),
			),
			'related_uid' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.related_uid',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tt_products',
					'MM' => 'tt_products_related_products_products_mm',
					'foreign_table' => 'tt_products',
					'foreign_table_where' => ' ORDER BY tt_products.uid',
					'size' => 10,
					'selectedListStyle' => 'width:450px',
					'minitems' => 0,
					'maxitems' => 50,
				),
			),
			'color' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.color',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'color2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.color2',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'color3' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.color3',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'size' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.size',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'size2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.size2',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'size3' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.size3',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'description' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.description',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'gradings' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.gradings',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'material' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.material',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'quality' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.quality',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'additional_type' => array (
				'label' => 'LLL:EXT:cms/locallang_ttc.php:list_type',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('', '')
					),
					'default' => '',
					'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
				)
			),
			'additional' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional',
				'config' => array (
					'type' => 'flex',
					'ds_pointerField' => 'additional_type',
					'ds' => array (
						'default' => '
							<T3DataStructure>
								<ROOT>
									<type>array</type>
									<el>
									<isSingle>
										<TCEforms>
											<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional.isSingle</label>
											<config>
												<type>check</type>
											</config>
										</TCEforms>
									</isSingle>
									<isImage>
										<TCEforms>
											<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional.isImage</label>
											<config>
												<type>check</type>
											</config>
										</TCEforms>
									</isImage>
									<alwaysInStock>
										<TCEforms>
											<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional.alwaysInStock</label>
											<config>
												<type>check</type>
											</config>
										</TCEforms>
									</alwaysInStock>
									<noMinPrice>
										<TCEforms>
											<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional.noMinPrice</label>
											<config>
												<type>check</type>
											</config>
										</TCEforms>
									</noMinPrice>
									<noGiftService>
										<TCEforms>
											<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional.noGiftService</label>
											<config>
												<type>check</type>
											</config>
										</TCEforms>
									</noGiftService>
									</el>
								</ROOT>
								<meta>
									<langDisable>1</langDisable>
								</meta>
							</T3DataStructure>
							',
					)
				)
			),
			'special_preparation' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.special_preparation',
				'config' => array (
					'type' => 'check'
				)
			),
			'image' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.image',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'file',
					'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
					'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
					'uploadfolder' => $imageFolder,
					'show_thumbs' => '1',
					'size' => '5',
					'maxitems' => '30',
					'minitems' => '0'
				)
			),
			'shipping' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.shipping',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'max' => '20',
					'eval' => 'trim,double2',
				)
			),
			'shipping2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.shipping2',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'max' => '20',
					'eval' => 'trim,double2',
				)
			),
			'handling' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.handling',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'max' => '20',
					'eval' => 'trim,double2',
				)
			),
			'delivery' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.delivery',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.delivery.availableNot', '-1'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.delivery.availableDemand', '0'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.delivery.availableImmediate', '1'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.delivery.availableShort', '2')
					),
					'size' => '6',
					'minitems' => 0,
					'maxitems' => 1,
				)
			),
			'sellstarttime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.sellstarttime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0'
				)
			),
			'sellendtime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.sellendtime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0',
					'range' => array (
						'upper' => mktime(0, 0, 0, 12, 31, $endtimeYear),
						'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
					)
				)
			),
		),
		'types' => array (
			'0' => array('showitem' => 'title;;7;;3-3-3, itemnumber;;2, category, address, price;;3,tax_id,deposit;;4,,offer;;6;;3-3-3,weight;;8,hidden;;1,' .
				'--div--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.descriptions,note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/],note_uid;;;;1-1-1,text_uid;;;;1-1-1,image;;;;4-4-4,datasheet;;;;1-1-1,dam_uid;;;;1-1-1,'.
				'--div--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.variants,color;;;;4-4-4,color2;;9;;,size,size2;;10,description,gradings,material,quality;;,additional;;11,damcat,'.
				'--div--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.graduated,graduated_price_uid;;;;4-4-4,'.
				'--div--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.relations,article_uid,related_uid,accessory_uid,download_info,download_uid,'.
				'--div--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.shippingdiv,shipping,shipping2,handling,delivery,')
		),
		'palettes' => array (
			'1' => array('showitem' => 'sellstarttime,sellendtime,starttime,endtime,fe_group'),
			'2' => array('showitem' => 'inStock,basketminquantity,ean'),
			'3' => array('showitem' => 'price2,directcost'),
			'4' => array('showitem' => 'creditpoints'),
			'6' => array('showitem' => 'highlight,bargain'),
			'7' => array('showitem' => 'subtitle,www'),
			'8' => array('showitem' => 'bulkily,special_preparation,unit,unit_factor'),
			'9' => array('showitem' => 'color3'),
			'10' => array('showitem' => 'size3'),
			'11' => array('showitem' => 'usebydate')
		)
	);

	$bSelectTaxMode = FALSE;

	if (
		call_user_func($emClass . '::isLoaded', 'static_info_tables_taxes') &&
		call_user_func($emClass . '::isLoaded', 'div2007')
	) {
		call_user_func($divClass . '::requireOnce', PATH_BE_div2007 . 'class.tx_div2007_alpha5.php');

		$eInfo = tx_div2007_alpha5::getExtensionInfo_fh003('static_info_tables_taxes');

		if (is_array($eInfo)) {
			$sittVersion = $eInfo['version'];
			if (version_compare($sittVersion, '0.1.0', '>=')) {
				$bSelectTaxMode = TRUE;
			}
		}
	}

	if (!$bSelectTaxMode) {
		$GLOBALS['TCA']['tt_products']['interface']['showRecordFieldList'] = str_replace(',tax_id,', ',tax,', $GLOBALS['TCA']['tt_products']['interface']['showRecordFieldList']);
		unset($GLOBALS['TCA']['tt_products']['columns']['tax_id']);
		$GLOBALS['TCA']['tt_products']['columns']['tax'] =
			array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.tax',
				'config' => array (
					'type' => 'input',
					'size' => '12',
					'max' => '19',
					'eval' => 'trim,double2'
				)
			);

		$GLOBALS['TCA']['tt_products']['types']['0']['showitem'] = str_replace(',tax_id,', ',tax,', $GLOBALS['TCA']['tt_products']['types']['0']['showitem']);
	}


	if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['articleMode'] == '1') {

		$GLOBALS['TCA']['tt_products']['columns']['article_uid'] = array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.article_uid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products_articles',
				'MM' => 'tt_products_products_mm_articles',
				'foreign_table' => 'tt_products_articles',
				'foreign_table_where' => ' ORDER BY tt_products_articles.title',
				'size' => 10,
				'selectedListStyle' => 'width:450px',
				'minitems' => 0,
				'maxitems' => 1000,
			)
		);
	}


	// ******************************************************************
	// This is the language overlay for the products table, tt_products
	// ******************************************************************
	$GLOBALS['TCA']['tt_products_language'] = array (
		'ctrl' => $GLOBALS['TCA']['tt_products_language']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'sys_language_uid,hidden,starttime,endtime,prod_uid,title,subtitle,unit,note,note2,text_uid,datasheet,www,image'
		),
		'feInterface' => $GLOBALS['TCA']['tt_products_language']['feInterface'],
		'columns' => array (
			't3ver_label' => array (
				'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
				'config' => array (
					'type' => 'input',
					'size' => '30',
					'max'  => '30',
				)
			),
			'sys_language_uid' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'foreign_table' => 'sys_language',
					'foreign_table_where' => 'ORDER BY sys_language.title',
					'items' => array(
						array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0)
					)
				)
			),
			'sorting' => Array (
				'config' => Array (
					'type' => 'passthrough',
				)
			),
			'hidden' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
				'config' => array (
					'type' => 'check',
					'default' => '0'
				)
			),
			'tstamp' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'crdate' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'starttime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0',
					'checkbox' => '0'
				)
			),
			'endtime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0',
					'range' => array (
						'upper' => mktime(0, 0, 0, 12, 31, $endtimeYear),
						'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
					)
				)
			),
			'fe_group' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('', 0),
						array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
						array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
					),
					'foreign_table' => 'fe_groups'
				)
			),
			'prod_uid' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_language.prod_uid',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tt_products',
					'foreign_table' => 'tt_products',
					'foreign_table_where' => 'AND tt_products.pid IN (###CURRENT_PID###,###STORAGE_PID###) ORDER BY tt_products.uid',
					'size' => 1,
					'minitems' => 0,
					'maxitems' => 1,
				),
			),
			'title' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '256'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'subtitle' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.subtitle',
				'config' => array (
					'type' => 'text',
					'rows' => '3',
					'cols' => '20',
					'max' => '512'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'itemnumber' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.itemnumber',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'eval' => 'trim',
					'max' => '120'
				)
			),
			'unit' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.unit',
				'config' => array (
					'type' => 'input',
					'size' => '20',
					'eval' => 'trim',
					'max' => '20'
				)
			),
			'note' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'note2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note2',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '2'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'datasheet' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_language.datasheet',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'file',
					'allowed' => 'doc,htm,html,pdf,sxw,txt,xls,gif,jpg,png',
					'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
					'uploadfolder' => 'uploads/tx_ttproducts/datasheet',
					'show_thumbs' => '1',
					'size' => '1',
					'maxitems' => '20',
					'minitems' => '0'
				)
			),
			'text_uid' => Array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_language.text_uid',
				'config' => Array (
					'type' => 'inline',
					'foreign_table' => 'tt_products_texts_language',
					'foreign_field' => 'parentid',
					'foreign_table_field' => 'parenttable',
					'foreign_unique' => 'parentid',
					'maxitems' => 20,
					'behaviour' => array('localizationMode' => 'select'), // vorher keep
				),
			),
			'www' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.www',
				'config' => array (
					'type' => 'input',
					'eval' => 'trim',
					'size' => '30',
					'max' => '160'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'image' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.image',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'file',
					'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
					'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
					'uploadfolder' => $imageFolder,
					'show_thumbs' => '1',
					'size' => '3',
					'maxitems' => '10',
					'minitems' => '0'
				)
			),
		),
		'types' => array (
			'1' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_diffsource, hidden;;1, prod_uid,title;;2;;3-3-3, unit, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], text_uid;;;;1-1-1, image;;;;4-4-4, datasheet')
		),
		'palettes' => array (
			'1' => array('showitem' => 'starttime,endtime,fe_group'),
			'2' => array('showitem' => 'subtitle, itemnumber, www'),
		)
	);



	// ******************************************************************
	// products to graduated price relation table, tt_products_mm_graduated_price
	// ******************************************************************
	$GLOBALS['TCA']['tt_products_mm_graduated_price'] = array (
		'ctrl' => $GLOBALS['TCA']['tt_products_mm_graduated_price']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'product_uid,graduated_price_uid'
		),
		'feInterface' => $GLOBALS['TCA']['tt_products_mm_graduated_price']['feInterface'],
		'columns' => array (
			'product_uid' => array (
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_mm_graduated_price.product_uid',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'foreign_table' => 'tt_products',
					'maxitems' => 1
				)
			),
			'graduated_price_uid' => array (
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_mm_graduated_price.graduated_price_uid',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'foreign_table' => 'tt_products_graduated_price',
					'maxitems' => 1
				)
			),
			'productsort' => array (
				'config' => array (
					'type' => 'passthrough',
				)
			),
			'graduatedsort' => array (
				'config' => array (
					'type' => 'passthrough',
				)
			),
		),
	);



	// ******************************************************************
	// graduated price calculation table, tt_products_graduated_price
	// ******************************************************************
	$GLOBALS['TCA']['tt_products_graduated_price'] = array (
		'ctrl' => $GLOBALS['TCA']['tt_products_graduated_price']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'title,formula,startamount,note,parentid,items'
		),
		'feInterface' => $GLOBALS['TCA']['tt_products_graduated_price']['feInterface'],
		'columns' => array (
			'tstamp' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'crdate' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'sorting' => Array (
				'config' => Array (
					'type' => 'passthrough',
				)
			),
			'hidden' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
				'config' => array (
					'type' => 'check'
				)
			),
			'starttime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_graduated_price.starttime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0'
				)
			),
			'endtime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_graduated_price.endtime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0',
					'range' => array (
						'upper' => mktime(0, 0, 0, 12, 31, $endtimeYear),
						'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
					)
				)
			),
			'fe_group' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('', 0),
						array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
						array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
					),
					'foreign_table' => 'fe_groups'
				)
			),
			'title' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '256'
				)
			),
			'formula' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_graduated_price.formula',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '1'
				)
			),
			'startamount' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products_graduated_price.startamount',
				'config' => array (
					'type' => 'input',
					'size' => '12',
					'eval' => 'trim,double2',
					'max' => '20'
				)
			),
			'note' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.note',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '2'
				)
			),
			'items' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_graduated_price.items',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectMultipleSideBySide',
					'items' => array (
						array('', 0),
					),
					'foreign_table' => 'tt_products_mm_graduated_price',
					'foreign_field' => 'graduated_price_uid',
					'foreign_sortby' => 'graduatedsort',
					'foreign_label' => 'product_uid',
					'size' => 6,
					'minitems' => 0,
					'maxitems' => 100,
				)
			),
		),
		'types' => array (
			'0' => array('showitem' => 'hidden;;1;;1-1-1, title, formula, startamount, note, items')
		),
		'palettes' => array (
			'1' => array('showitem' => 'starttime, endtime, fe_group')
		)
	);



	// ******************************************************************
	// This is the standard TypoScript products category table, tt_products_cat
	// ******************************************************************
	$GLOBALS['TCA']['tt_products_cat'] = array (
		'ctrl' => $GLOBALS['TCA']['tt_products_cat']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,title, subtitle, note, note2, image, email_uid'
		),
		'feInterface' => $GLOBALS['TCA']['tt_products_cat']['feInterface'],
		'columns' => array (
			't3ver_label' => array (
				'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
				'config' => array (
					'type' => 'input',
					'size' => '30',
					'max'  => '30',
				)
			),
			'sorting' => Array (
				'config' => Array (
					'type' => 'passthrough',
				)
			),
			'hidden' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
				'config' => array (
					'type' => 'check'
				)
			),
			'tstamp' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'crdate' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'starttime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0'
				)
			),
			'endtime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0',
					'range' => array (
						'upper' => mktime(0, 0, 0, 12, 31, $endtimeYear),
						'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
					)
				)
			),
			'fe_group' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('', 0),
						array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
						array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
					),
					'foreign_table' => 'fe_groups'
				)
			),
			'title' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '256'
				)
			),
			'subtitle' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.subtitle',
				'config' => array (
					'type' => 'text',
					'rows' => '3',
					'cols' => '20',
					'max' => '512'
				)
			),
			'note' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				)
			),
			'note2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note2',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				)
			),
			'image' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.image',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'file',
					'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
					'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
					'uploadfolder' => $imageFolder,
					'show_thumbs' => '1',
					'size' => '3',
					'maxitems' => '10',
					'minitems' => '0'
				)
			),
			'email_uid' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_cat.email_uid',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tt_products_emails',
					'foreign_table' => 'tt_products_emails',
					'foreign_table_where' => ' ORDER BY tt_products_emails.name',
					'size' => 1,
					'autoSizeMax' => 1,
					'minitems' => 0,
					'maxitems' => 1,
				)
			),
		),
		'types' => array (
			'0' => array('showitem' => 'title, subtitle, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], email_uid, image;;;;3-3-3, hidden;;1;;')
		),
		'palettes' => array (
			'1' => array('showitem' => 'starttime, endtime, fe_group')
		)
	);



	// ******************************************************************
	// This is the language overlay for  products category table, tt_products_cat
	// ******************************************************************
	$GLOBALS['TCA']['tt_products_cat_language'] = array (
		'ctrl' => $GLOBALS['TCA']['tt_products_cat_language']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'sys_language_uid,l18n_diffsource,hidden,starttime,endtime,fe_group,title,subtitle,note,note2,cat_uid'
		),
		'feInterface' => $GLOBALS['TCA']['tt_products_cat_language']['feInterface'],
		'columns' => array (
			't3ver_label' => array (
				'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
				'config' => array (
					'type' => 'input',
					'size' => '30',
					'max'  => '30',
				)
			),
			'sys_language_uid' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'foreign_table' => 'sys_language',
					'foreign_table_where' => 'ORDER BY sys_language.title',
					'items' => array(
						array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0)
					)
				)
			),
			'tstamp' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'crdate' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'sorting' => Array (
				'config' => Array (
					'type' => 'passthrough',
				)
			),
			'hidden' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
				'config' => array (
					'type' => 'check',
					'default' => '0'
				)
			),
			'starttime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0',
					'checkbox' => '0'
				)
			),
			'endtime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0',
					'range' => array (
						'upper' => mktime(0, 0, 0, 12, 31, $endtimeYear),
						'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
					)
				)
			),
			'title' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '256'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'subtitle' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.subtitle',
				'config' => array (
					'type' => 'text',
					'rows' => '3',
					'cols' => '20',
					'max' => '512'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'note' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'note2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note2',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'cat_uid' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_cat_language.cat_uid',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tt_products_cat',
					'foreign_table' => 'tt_products_cat',
					'foreign_table_where' => 'AND tt_products_cat.pid IN (###CURRENT_PID###,###STORAGE_PID###) ORDER BY tt_products_cat.uid',
					'size' => 1,
					'minitems' => 0,
					'maxitems' => 1,
				),
			),
		),
		'types' => array (
			'0' => array('showitem' => '
			sys_language_uid;;;;1-1-1, l18n_diffsource, hidden;;1, cat_uid;;;;2-2-2, title, subtitle, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/],hidden;;1;;')
		),
		'palettes' => array (
			'1' => array('showitem' => 'starttime, endtime, fe_group')
		)
	);


	// ******************************************************************
	// These are the articles for some of the products where variants exist
	// ******************************************************************
	$GLOBALS['TCA']['tt_products_articles'] = array (
		'ctrl' => $GLOBALS['TCA']['tt_products_articles']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,title,subtitle,itemnumber,price,price2,weight,inStock,basketminquantity,color,color2,color3,size,size2,size3,description,gradings,material,quality,note,note2,image'
		),
		'feInterface' => $GLOBALS['TCA']['tt_products_articles']['feInterface'],
		'columns' => array (
			't3ver_label' => array (
				'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
				'config' => array (
					'type' => 'input',
					'size' => '30',
					'max'  => '30',
				)
			),
			'tstamp' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'crdate' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'sorting' => Array (
				'config' => Array (
					'type' => 'passthrough',
				)
			),
			'hidden' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
				'config' => array (
					'type' => 'check',
					'default' => '0'
				)
			),
			'starttime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0',
					'checkbox' => '0'
				)
			),
			'endtime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0',
					'range' => array (
						'upper' => mktime(0, 0, 0, 12, 31, $endtimeYear),
						'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
					)
				)
			),
			'fe_group' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('', 0),
						array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
						array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
					),
					'foreign_table' => 'fe_groups'
				)
			),
			'title' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '80',
					'eval' => 'trim',
				)
			),
			'subtitle' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.subtitle',
				'config' => array (
					'type' => 'text',
					'rows' => '3',
					'cols' => '20',
					'max' => '512'
				)
			),
			'itemnumber' => array (
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.itemnumber',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'eval' => 'trim',
					'max' => '120'
				)
			),
			'price' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.price',
				'config' => array (
					'type' => 'input',
					'size' => '12',
					'eval' => 'trim,double2',
					'max' => '20'
				)
			),
			'price2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.price2',
				'config' => array (
					'type' => 'input',
					'size' => '12',
					'eval' => 'trim,double2',
					'max' => '20'
				)
			),
			'note' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'note2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note2',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'inStock' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.inStock',
				'config' => array (
					'type' => 'input',
					'size' => '6',
					'max' => '6',
					'eval' => 'int',
					'default' => '1'
				)
			),
			'basketminquantity' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.basketminquantity',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'eval' => 'trim,double2',
					'max' => '10'
				)
			),
			'weight' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.weight',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'max' => '20',
					'eval' => 'trim,tx_double6',
				)
			),
			'color' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.color',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5',
					'default' => ''
				)
			),
			'color2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.color2',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'color3' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.color3',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'size' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.size',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'size2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.size2',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'size3' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.size3',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'description' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.description',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'gradings' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.gradings',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'material' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.material',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'quality' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.quality',
				'config' => array (
					'type' => 'text',
					'cols' => '46',
					'rows' => '5'
				)
			),
			'config_type' => array (
				'label' => 'LLL:EXT:cms/locallang_ttc.php:list_type',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('','')
					),
					'default' => '',
					'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
				)
			),
			'config' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_articles.config',
				'config' => array (
					'type' => 'flex',
					'ds_pointerField' => 'config_type',
					'ds' => array (
						'default' => '
							<T3DataStructure>
								<ROOT>
									<type>array</type>
									<el>
									<isAddedPrice>
										<TCEforms>
											<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_articles.config.isaddedprice</label>
											<config>
												<type>check</type>
											</config>
										</TCEforms>
									</isAddedPrice>
									</el>
								</ROOT>
								<meta>
									<langDisable>1</langDisable>
								</meta>
							</T3DataStructure>
							',
					)
				)
			),
			'image' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.image',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'file',
					'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
					'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
					'uploadfolder' => $imageFolder,
					'show_thumbs' => '1',
					'size' => '3',
					'maxitems' => '10',
					'minitems' => '0'
				)
			),
		),
		'types' => array (
			'1' => array('showitem' => 'hidden;;1, title;;3;;1-1-1, itemnumber, inStock;;;;1-1-1, basketminquantity,price;;2;;, weight, color, color2, color3, size, size2, size3, description, gradings, material, quality, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/],note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/],image;;;;3-3-3')
		),
		'palettes' => array (
			'1' => array('showitem' => 'starttime, endtime, fe_group'),
			'2' => array('showitem' => 'price2, config'),
			'3' => array('showitem' => 'subtitle'),
		)
	);

	if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['articleMode'] == '0') {
		$GLOBALS['TCA']['tt_products_articles']['interface']['showRecordFieldList'] = str_replace(',subtitle,', ',subtitle,uid_product,',$GLOBALS['TCA']['tt_products_articles']['interface']['showRecordFieldList']);

		$GLOBALS['TCA']['tt_products_articles']['columns']['uid_product'] = array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_articles.uid_product',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		);

		$GLOBALS['TCA']['tt_products_articles']['types']['1'] = str_replace('title;', 'uid_product;;;;1-1-1,title;', $GLOBALS['TCA']['tt_products_articles']['types']['1']);

		unset($GLOBALS['TCA']['tt_products']['columns']['article_uid']);
				// neu Anfang
		$GLOBALS['TCA']['tt_products']['types']['0'] = str_replace(',article_uid,', '', $GLOBALS['TCA']['tt_products']['types']['0']);
		// neu Ende

	}

	if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['articleMode'] == '2') {

		// ******************************************************************
		// products to article relation table, tt_products_products_mm_articles
		// ******************************************************************
		$GLOBALS['TCA']['tt_products_products_mm_articles'] = array (
			'ctrl' => $GLOBALS['TCA']['tt_products_products_mm_articles']['ctrl'],
			'interface' => array (
				'showRecordFieldList' => 'uid_local,uid_foreign'
			),
			'feInterface' => $GLOBALS['TCA']['tt_products_products_mm_articles']['feInterface'],
			'columns' => array (
		// 		'title' => array (
		// 			'exclude' => 1,
		// 			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
		// 			'config' => array (
		// 				'type' => 'input',
		// 				'size' => '40',
		// 				'max' => '80',
		// 				'eval' => 'required,trim',
		// 			)
		// 		),
				'uid_local' => array (
					'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_products_mm_articles.uid_local',
					'config' => array (
						'type' => 'select',
						'renderType' => 'selectSingle',
						'foreign_table' => 'tt_products',
						'maxitems' => 1
					)
				),
				'uid_foreign' => array (
					'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_products_mm_articles.uid_foreign',
					'config' => array (
						'type' => 'select',
						'renderType' => 'selectSingle',
						'foreign_table' => 'tt_products_articles',
						'maxitems' => 1
					)
				),
				'sorting' => array (
					'config' => array (
						'type' => 'passthrough',
					)
				),
				'articlesort' => array (
					'config' => array (
						'type' => 'passthrough',
					)
				),
			),
		// 	'types' => array (
		// 		'1' => array('showitem' => 'title;;3;;3-3-3')
		// 	),
		// 	'palettes' => array (
		// 		'1' => array('showitem' => ''),
		// 	)
		);
	}

	// ******************************************************************
	// This is the language overlay for the articles table, tt_products_articles
	// ******************************************************************
	$GLOBALS['TCA']['tt_products_articles_language'] = array (
		'ctrl' => $GLOBALS['TCA']['tt_products_articles_language']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'sys_language_uid,hidden,starttime,endtime,fe_group,article_uid,title,subtitle,note,note2'
		),
		'feInterface' => $GLOBALS['TCA']['tt_products_articles_language']['feInterface'],
		'columns' => array (
			't3ver_label' => array (
				'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
				'config' => array (
					'type' => 'input',
					'size' => '30',
					'max'  => '30',
				)
			),
			'sys_language_uid' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'foreign_table' => 'sys_language',
					'foreign_table_where' => 'ORDER BY sys_language.title',
					'items' => array(
						array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
						array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
					)
				)
			),
			'tstamp' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'crdate' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'sorting' => Array (
				'config' => Array (
					'type' => 'passthrough',
				)
			),
			'hidden' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
				'config' => array (
					'type' => 'check',
					'default' => '0'
				)
			),
			'starttime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0',
					'checkbox' => '0'
				)
			),
			'endtime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0',
					'range' => array (
						'upper' => mktime(0, 0, 0, 12, 31, $endtimeYear),
						'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
					)
				)
			),
			'fe_group' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('', 0),
						array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
						array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
					),
					'foreign_table' => 'fe_groups'
				)
			),
			'article_uid' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_articles_language.article_uid',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tt_products_articles',
					'foreign_table' => 'tt_products_articles',
					'foreign_table_where' => 'AND tt_products_articles.pid IN (###CURRENT_PID###,###STORAGE_PID###) ORDER BY tt_products_articles.title',
					'size' => 1,
					'minitems' => 0,
					'maxitems' => 1,
				),
			),
			'title' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '256'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'subtitle' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.subtitle',
				'config' => array (
					'type' => 'text',
					'rows' => '3',
					'cols' => '20',
					'max' => '512'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'note' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'note2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note2',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
		),
		'types' => array (
			'1' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_diffsource, hidden;;1, article_uid,title;;2;;3-3-3, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/],hidden;;1;;')
		),
		'palettes' => array (
			'1' => array('showitem' => 'starttime,endtime,fe_group'),
			'2' => array('showitem' => 'subtitle'),
		)
	);

	// ******************************************************************
	// These are the email addresses which are used for sending notification emails
	// ******************************************************************
	$GLOBALS['TCA']['tt_products_emails'] = array (
		'ctrl' => $GLOBALS['TCA']['tt_products_emails']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'name,email,hidden,starttime,endtime,fe_group'
		),
		'feInterface' => $GLOBALS['TCA']['tt_products_emails']['feInterface'],
		'columns' => array (
			't3ver_label' => array (
				'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
				'config' => array (
					'type' => 'input',
					'size' => '30',
					'max'  => '30',
				)
			),
			'tstamp' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'crdate' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'hidden' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
				'config' => array (
					'type' => 'check',
					'default' => '0'
				)
			),
			'starttime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0',
					'checkbox' => '0'
				)
			),
			'endtime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0',
					'range' => array (
						'upper' => mktime(0, 0, 0, 12, 31, $endtimeYear),
						'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
					)
				)
			),
			'fe_group' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('', 0),
						array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
						array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
					),
					'foreign_table' => 'fe_groups'
				)
			),
			'name' => array (
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.name',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '80'
				)
			),
			'email' => array (
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.email',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '80'
				)
			),
		),
		'types' => array (
			'1' => array('showitem' => 'hidden;;;;1-1-1, name, email')
		),
		'palettes' => array (
			'1' => array('showitem' => 'starttime, endtime, fe_group')
		)
	);




	// ******************************************************************
	// This is the standard TypoScript products texts table, tt_products_texts
	// ******************************************************************
	$GLOBALS['TCA']['tt_products_texts'] = array (
		'ctrl' => $GLOBALS['TCA']['tt_products_texts']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'hidden,title,marker,note'
		),
		'feInterface' => $GLOBALS['TCA']['tt_products_texts']['feInterface'],
		'columns' => array (
			't3ver_label' => array (
				'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
				'config' => array (
					'type' => 'input',
					'size' => '30',
					'max'  => '30',
				)
			),
			'tstamp' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'crdate' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'sorting' => Array (
				'config' => Array (
					'type' => 'passthrough',
				)
			),
			'hidden' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
				'config' => array (
					'type' => 'check',
					'default' => '0'
				)
			),
			'starttime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0',
					'checkbox' => '0'
				)
			),
			'endtime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0',
					'range' => array (
						'upper' => mktime(0, 0, 0, 12, 31, $endtimeYear),
						'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
					)
				)
			),
			'fe_group' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('', 0),
						array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
						array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
					),
					'foreign_table' => 'fe_groups'
				)
			),
			'title' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '256'
				)
			),
			'marker' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_texts.marker',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '256'
				)
			),
			'note' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				)
			),
			'parentid' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_texts.parentid',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tt_products',
					'prepend_tname' => FALSE,
					'foreign_table_where' => 'AND tt_products.pid IN (###CURRENT_PID###,###STORAGE_PID###) ORDER BY tt_products.title',
					'size' => 1,
					'minitems' => 0,
					'maxitems' => 1,
				)
			),
			'parenttable' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_texts.parenttable',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array(
						array('tt_products', 'tt_products')
					)
				)
			),
		),
		'types' => array (
			'0' => array('showitem' => 'hidden;;;;1-1-1, title, marker, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], parentid;;;;2-2-2, parenttable')
		),
		'palettes' => array (
			'1' => array('showitem' => 'starttime,endtime,fe_group'),
		)
	);



	// ******************************************************************
	// This is the language overlay for  products texts table, tt_products_texts
	// ******************************************************************
	$GLOBALS['TCA']['tt_products_texts_language'] = array (
		'ctrl' => $GLOBALS['TCA']['tt_products_texts_language']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'sys_language_uid,hidden,starttime,endtime,fe_group,title,note'
		),
		'feInterface' => $GLOBALS['TCA']['tt_products_texts_language']['feInterface'],
		'columns' => array (
			't3ver_label' => array (
				'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
				'config' => array (
					'type' => 'input',
					'size' => '30',
					'max'  => '30',
				)
			),
			'tstamp' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'crdate' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'sys_language_uid' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'foreign_table' => 'sys_language',
					'foreign_table_where' => 'ORDER BY sys_language.title',
					'items' => array(
						array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0)
					)
				)
			),
			'sorting' => Array (
				'config' => Array (
					'type' => 'passthrough',
				)
			),
			'hidden' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
				'config' => array (
					'type' => 'check',
					'default' => '0'
				)
			),
			'starttime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0',
					'checkbox' => '0'
				)
			),
			'endtime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0',
					'range' => array (
						'upper' => mktime(0, 0, 0, 12, 31, $endtimeYear),
						'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
					)
				)
			),
			'fe_group' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('', 0),
						array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
						array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
					),
					'foreign_table' => 'fe_groups'
				)
			),
			'title' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '256'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'note' => array (
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				),
				'l10n_mode' => 'prefixLangTitle',
			),
			'text_uid' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_texts_language.text_uid',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'allowed' => 'tt_products_texts',
					'foreign_table' => 'tt_products_texts',
					'itemsProcFunc' => 'tx_ttproducts_hooks_be->extendedItemList',
					'foreign_table_where' => 'AND tt_products_texts.pid IN (###CURRENT_PID###,###STORAGE_PID###) ORDER BY tt_products_texts.marker',
					'size' => 1,
					'minitems' => 1, // FHO
					'maxitems' => 1,
				),
			),
			'parentid' => Array (
				'exclude' => 0,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_texts_language.parentid',
				'config' => Array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tt_products_language',
					'prepend_tname' => FALSE,
					'foreign_table_where' => 'AND tt_products_language.pid IN (###CURRENT_PID###,###STORAGE_PID###) ORDER BY tt_products_language.title',
					'size' => 1,
					'minitems' => 0,
					'maxitems' => 1,
					'db' => 'passthrough',
				),
			),
			'parenttable' => Array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_texts_language.parenttable',
				'config' => Array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => Array(
						Array('tt_products_language', 'tt_products_language')
					),
					'db' => 'passthrough',
				)
			),
		),
		'types' => array (
			'0' => array('showitem' => '
			sys_language_uid;;;;1-1-1, l18n_diffsource, hidden;;1, text_uid;;;;2-2-2, title, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], parentid;;;;2-2-2, parenttable')
		),
		'palettes' => array (
			'1' => array('showitem' => 'starttime, endtime, fe_group')
		)
	);



	// ******************************************************************
	// These are the bank account data used for orders
	// ******************************************************************
	$GLOBALS['TCA']['sys_products_accounts'] = array (
		'ctrl' => $GLOBALS['TCA']['sys_products_accounts']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'iban,ac_number,owner_name,bic'
		),
		'feInterface' => $GLOBALS['TCA']['sys_products_accounts']['feInterface'],
		'columns' => array (
			'iban' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_accounts.iban',
				'config' => array (
					'type' => 'input',
					'size' => '24',
					'max' => '24',
					'eval' => 'required,trim',
				)
			),
			'ac_number' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_accounts.ac_number',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '80',
				)
			),
			'owner_name' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.name',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '80'
				)
			),
			'bic' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_accounts.bic',
				'config' => array (
					'type' => 'input',
					'size' => '11',
					'max' => '11'
				)
			),
		),
		'types' => array (
			'1' => array('showitem' => 'hidden;;;;1-1-1, iban, ac_number, owner_name, bic')
		),
		'palettes' => array (
			'1' => array('showitem' => '')
		)
	);

	if (!$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['sepa']) {
		unset($GLOBALS['TCA']['sys_products_accounts']['columns']['iban']['config']['eval']);
		$GLOBALS['TCA']['sys_products_accounts']['columns']['ac_number']['config']['eval'] = 'required,trim';
	}


	// ******************************************************************
	// These are the credit cards data used for orders
	// ******************************************************************
	$GLOBALS['TCA']['sys_products_cards'] = array (
		'ctrl' => $GLOBALS['TCA']['sys_products_cards']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'cc_number,owner_name,cc_type,cvv2,endtime'
		),
		'feInterface' => $GLOBALS['TCA']['sys_products_cards']['feInterface'],
		'columns' => array (
			'endtime' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0',
					'range' => array (
						'upper' => mktime(0, 0, 0, 12, 31, $endtimeYear),
						'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
					)
				)
			),
			'cc_number' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_cards.cc_number',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '80',
					'eval' => 'required,trim',
				)
			),
			'owner_name' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.name',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '80'
				)
			),
			'cc_type' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_cards.cc_type',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_cards.cc_type.I.0', '0'),
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_cards.cc_type.I.1', '1'),
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_cards.cc_type.I.2', '2'),
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_cards.cc_type.I.3', '3'),
					),
					'size' => 1,
					'maxitems' => 1,
				)
			),
			'cvv2' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_cards.cvv2',
				'config' => array (
					'type' => 'input',
					'size' => '4',
					'eval' => 'int',
					'max' => '4'
				)
			),
		),
		'types' => array (
			'1' => array('showitem' => 'hidden;;;;1-1-1, cc_number, owner_name, cc_type, cvv2, endtime')
		),
		'palettes' => array (
			'1' => array('showitem' => '')
		)
	);



	// *****************************************************************
	// These are the orders
	// ******************************************************************
	$GLOBALS['TCA']['sys_products_orders'] = array (
		'ctrl' => $GLOBALS['TCA']['sys_products_orders']['ctrl'],
		'interface' => array (
			'showRecordFieldList' => 'hidden,sys_language_uid,name,first_name,last_name,company,vat_id,salutation,address,zip,city,country,telephone,email,fax,business_partner,organisation_form,payment,shipping,amount,tax_mode,pay_mode,email_notify,tracking_code,status,agb,feusers_id,creditpoints,creditpoints_spended,creditpoints_saved,creditpoints_gifts,desired_date,desired_time,client_ip,note,giftservice,cc_uid,ac_uid,date_of_birth,date_of_payment,date_of_delivery,bill_no,radio1,'
		),
		'feInterface' => $GLOBALS['TCA']['sys_products_orders']['feInterface'],
		'columns' => array (
			'hidden' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
				'config' => array (
					'type' => 'check',
					'default' => '0'
				)
			),
			'tstamp' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'crdate' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'default' => '0'
				)
			),
			'sys_language_uid' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'foreign_table' => 'sys_language',
					'foreign_table_where' => 'ORDER BY sys_language.title',
					'items' => array(
						array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
						array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0)
					)
				)
			),
			'name' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.name',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '80',
					'eval' => 'required,trim',
				)
			),
			'first_name' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.first_name',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '50',
					'eval' => 'trim',
				)
			),
			'last_name' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.last_name',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '50',
					'eval' => 'trim',
				)
			),
			'company' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.company',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '80',
					'eval' => 'trim',
				)
			),
			'vat_id' => Array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.vat_id',
				'config' => Array (
					'type' => 'input',
					'size' => '15',
					'max' => '15'
				)
			),
			'salutation' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.salutation',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.salutation.I.0', '0'),
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.salutation.I.1', '1'),
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.salutation.I.2', '2'),
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.salutation.I.3', '3'),
					),
					'size' => 1,
					'maxitems' => 1,
				)
			),
			'address' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.address',
				'config' => array (
					'type' => 'input',
					'size' => '50',
					'max' => '256'
				)
			),
			'zip' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.zip',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'max' => '20',
					'eval' => 'trim',
				)
			),
			'city' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.city',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '50',
					'eval' => 'trim',
				)
			),
			'country' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.country',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '60',
					'eval' => 'trim',
				)
			),
			'telephone' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.phone',
				'config' => array (
					'type' => 'input',
					'size' => '20',
					'max' => '20',
				)
			),
			'email' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.email',
				'config' => array (
					'type' => 'input',
					'size' => '20',
					'max' => '80',
				)
			),
			'fax' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fax',
				'config' => array (
					'type' => 'input',
					'size' => '4',
					'max' => '4',
				)
			),
			'business_partner' => array (
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
			'organisation_form' => array (
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
			'payment' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.payment',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '80',
				)
			),
			'shipping' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.shipping',
				'config' => array (
					'type' => 'input',
					'size' => '40',
					'max' => '80',
				)
			),
			'amount' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.amount',
				'config' => array (
					'type' => 'input',
					'size' => '20',
					'max' => '20',
					'eval' => 'trim,double2',
				)
			),
			'tax_mode' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.tax_mode',
				'config' => array (
					'type' => 'radio',
					'items' => array (
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.tax_mode.I.0', '0'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.tax_mode.I.1', '1'),
					),
					'default' => '0'
				)
			),
			'pay_mode' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode',
				'config' => array (
					'type' => 'radio',
					'items' => array (
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.0', '0'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.1', '1'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.2', '2'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.3', '3'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.4', '4'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.5', '5'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.6', '6'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.7', '7'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.8', '8'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.9', '9'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.10', '10'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.11', '11')
					),
					'default' => '0'
				)
			),
			'email_notify' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.email_notify',
				'config' => array (
					'type' => 'input',
					'size' => '4',
					'max' => '4',
				)
			),
			'tracking_code' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.tracking_code',
				'config' => array (
					'type' => 'input',
					'size' => '32',
					'max' => '64',
				)
			),
			'status' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.status',
				'config' => array (
					'type' => 'input',
					'size' => '4',
					'max' => '4',
				)
			),
			'status_log' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.status_log',
				'config' => array (
					'type' => 'text',
					'cols' => '80',
					'rows' => '4',
				)
			),
			'orderData' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.orderData',
				'config' => array (
					'type' => 'text',
					'cols' => '160',
					'rows' => '160',
					'wrap' => 'off',
				)
			),
			'orderHtml' => Array (
				'exclude' => 1,
				'label' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:sys_products_orders.orderHtml',
				'config' => Array (
					'type' => 'user',
					'size' => '30',
					'db' => 'passthrough',
					'userFunc' => 'EXT:' . TT_PRODUCTS_EXT . '/hooks/class.tx_ttproducts_hooks_be.php:tx_ttproducts_hooks_be->displayOrderHtml',
					'parameters' => array(
						'format' => 'html'
					),
				)
			),
			'agb' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.agb',
				'config' => array (
					'type' => 'input',
					'size' => '2',
					'max' => '2',
					'readOnly' => '1',
				)
			),
			'feusers_uid' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.feusers_uid',
				'config' => array (
					'type' => 'input',
					'size' => '11',
					'max' => '11',
				)
			),
			'creditpoints' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.creditpoints',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'max' => '10',
				)
			),
			'creditpoints_spended' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.creditpoints_spended',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'max' => '10',
				)
			),
			'creditpoints_saved' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.creditpoints_saved',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'max' => '10',
				)
			),
			'creditpoints_gifts' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.creditpoints_gifts',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'max' => '10',
				)
			),
			'desired_date' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.desired_date',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'max' => '10',
				)
			),
			'desired_time' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.desired_time',
				'config' => array (
					'type' => 'input',
					'size' => '10',
					'max' => '10',
				)
			),
			'client_ip' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.client_ip',
				'config' => array (
					'type' => 'input',
					'size' => '15',
					'max' => '15',
				)
			),
			'note' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.note',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				)
			),
			'giftservice' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.giftservice',
				'config' => array (
					'type' => 'text',
					'cols' => '48',
					'rows' => '5'
				)
			),
			'cc_uid' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_cards.cc_number',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'sys_products_cards',
					'size' => 1,
					'minitems' => 0,
					'maxitems' => 1,
				)
			),
			'ac_uid' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_accounts.iban',
				'config' => array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'sys_products_accounts',
					'size' => 1,
					'minitems' => 0,
					'maxitems' => 1,
				)
			),
			'foundby' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => array (
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.0', '0'),
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.1', '1'),
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.2', '2'),
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.3', '3'),
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.4', '4'),
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.5', '5'),
						array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.6', '6'),
					),
					'size' => 1,
					'maxitems' => 1,
				)
			),
			'giftcode' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.order_code',
				'config' => array (
					'type' => 'input',
					'size' => '30',
					'max' => '80',
				)
			),
			'date_of_birth' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.date_of_birth',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0'
				)
			),
			'date_of_payment' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.date_of_payment',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0'
				)
			),
			'date_of_delivery' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.date_of_delivery',
				'config' => array (
					'type' => 'input',
					'size' => '8',
					'max' => '20',
					'eval' => 'date',
					'checkbox' => '0',
					'default' => '0'
				)
			),
			'bill_no' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.bill_no',
				'config' => array (
					'type' => 'input',
					'size' => '30',
					'max' => '80',
				)
			),
			'radio1' => array (
				'exclude' => 1,
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.radio1',
				'config' => array (
					'type' => 'radio',
					'items' => array (
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.radio1.I.0', '0'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.radio1.I.1', '1'),
						array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.radio1.I.2', '2'),
					),
					'default' => '0'
				)
			),
		),
		'types' => array (
			'1' => array
				(
					'showitem' => 'hidden;;;;1-1-1, name;;3;;3-3-3, sys_language_uid,first_name,last_name,company,vat_id,salutation,address,zip,city,country,telephone,email,payment,shipping,amount,tax_mode,pay_mode,email_notify,tracking_code,status,fax,business_partner,organisation_form,agb,feusers_uid,creditpoints,creditpoints_spended,creditpoints_saved,creditpoints_gifts,desired_date,desired_time,client_ip,note,giftservice,foundby,giftcode,cc_uid,ac_uid,date_of_birth,date_of_payment,date_of_delivery,bill_no,radio1,' .
					'--div--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.orderHtmlDiv,orderHtml;;3;;3-3-3,'
				)
		),
		'palettes' => array (
			'1' => array('showitem' => 'tstamp, crdate'),
		)
	);

	if (!$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['sepa']) {
		$GLOBALS['TCA']['sys_products_orders']['columns']['ac_uid']['label'] = 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_accounts.ac_number';
	}


	if (isset($excludeArray) && is_array($excludeArray)) {
		foreach ($excludeArray as $tablename => $excludeFields) {
			$excludeFieldArray = call_user_func($divClass . '::trimExplode', ',', $excludeFields, 1);

			if (
				isset($GLOBALS['TCA'][$tablename]) &&
				isset($excludeFieldArray) &&
				is_array($excludeFieldArray) &&
				count($excludeFieldArray)
			) {
				$tmpArray = explode(',', $GLOBALS['TCA'][$tablename]['interface']['showRecordFieldList']);
				$tmpArray = array_diff($tmpArray, $excludeFieldArray);
				$GLOBALS['TCA'][$tablename]['interface']['showRecordFieldList'] = implode(',', $tmpArray);

				foreach ($excludeFieldArray as $excludeField) {
					if (isset($GLOBALS['TCA'][$tablename]['columns'][$excludeField])) {
						unset($GLOBALS['TCA'][$tablename]['columns'][$excludeField]);
					}
				}

				$conigTypeArray = array('types', 'palettes');

				foreach ($conigTypeArray as $configType) {
					if (
						isset($GLOBALS['TCA'][$tablename][$configType])
						&& is_array($GLOBALS['TCA'][$tablename][$configType])
					) {
						foreach ($GLOBALS['TCA'][$tablename][$configType] as $k => $config) {
							if (isset($config) && is_array($config)) {
								$showItemArray = explode(',', $config['showitem']);
								if (isset($showItemArray) && is_array($showItemArray)) {
									foreach ($showItemArray as $k2 => $showItem) {
										$showItem = trim($showItem);
										foreach ($excludeFieldArray as $excludeField) {
											if (strpos($showItem, $excludeField) === 0) {
												$length = strlen($excludeField);
												if (
													strlen($showItem) == $length
													|| substr($showItem, $length, 1) == ';'
												) {
													unset($showItemArray[$k2]);
												}
											}
										}
									}
									$GLOBALS['TCA'][$tablename][$configType][$k]['showitem'] = implode(',', $showItemArray);
								}
							}
						}
					}
				}
			}
		}
	}
} // if (version_compare(TYPO3_version, '6.2.0', '<'))


