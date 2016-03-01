<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$imageFolder = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['imageFolder'];
if (!$imageFolder) {
	$imageFolder = 'uploads/pics';
}


// ******************************************************************
// This is the standard TypoScript products article table, tt_products_articles
// ******************************************************************

$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_articles',
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
		'cruser_id' => 'cruser_id',
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_articles.gif',
		'dividers2tabs' => '1',
		'transForeignTable' => 'tt_products_articles_language',
		'searchFields' => 'title,subtitle,itemnumber,note,note2',
	),
	'interface' => array (
		'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,title,subtitle,itemnumber,price,price2,weight,inStock,basketminquantity,color,color2,color3,size,size2,size3,description,gradings,material,quality,note,note2,image'
	),
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
					'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['endtimeYear']),
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'note2' => array (
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
		'basketmaxquantity' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.basketmaxquantity',
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
					array('', '')
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
		'1' => array('showitem' => 'hidden;;1, title;;3;;1-1-1, itemnumber, inStock;;;;1-1-1, basketminquantity, price;;2;;, weight, color, color2, color3, size, size2, size3, description, gradings, material, quality, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/],note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/],image;;;;3-3-3')
	),
	'palettes' => array (
		'1' => array('showitem' => 'starttime, endtime, fe_group'),
		'2' => array('showitem' => 'price2, config'),
		'3' => array('showitem' => 'subtitle'),
	)
);


switch ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['articleMode']) {
	case '0':
		$result['interface']['showRecordFieldList'] = str_replace(',subtitle,', ',subtitle,uid_product,',$result['interface']['showRecordFieldList']);

		$result['columns']['uid_product'] = array (
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

		$result['types']['1'] = str_replace('title;', 'uid_product;;;;1-1-1,title;', $result['types']['1']);

		break;
}


$table = 'tt_products_articles';

$orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);
if (
	!empty($orderBySortingTablesArray) &&
	in_array($table, $orderBySortingTablesArray)
) {
	$result['ctrl']['sortby'] = 'sorting';
}


if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('addons_em')) {
	$excludeArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.'];

	if (
		is_array($excludeArray) &&
		isset($excludeArray[$table])
	) {
		$fieldArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $excludeArray[$table], 1);
		if (!class_exists('tx_addonsem_tca_div')) {
			include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('addons_em') . 'lib/class.tx_addonsem_tca_div.php');
		}
		tx_addonsem_tca_div::removeFieldsFromTCA($result, $fieldArray);
	}
}

return $result;

