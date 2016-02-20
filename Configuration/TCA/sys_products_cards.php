<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// ******************************************************************
// These are the credit cards data used for orders
// ******************************************************************
$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_cards',
		'label' => 'cc_number',
		'default_sortby' => 'ORDER BY cc_number',
		'tstamp' => 'tstamp',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'crdate' => 'crdate',
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'sys_products_cards.gif',
		'searchFields' => 'owner_name,cc_number',
	),
	'interface' => array (
		'showRecordFieldList' => 'cc_number,owner_name,cc_type,cvv2,endtime'
	),
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
					'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['endtimeYear']),
					'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
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



$table = 'sys_products_cards';
$orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);
if (
	!empty($orderBySortingTablesArray) &&
	in_array($table, $orderBySortingTablesArray)
) {
	$result['ctrl']['sortby'] = 'sorting';
}


return $result;

