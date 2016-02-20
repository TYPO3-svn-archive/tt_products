<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$accountField = 'ac_number';

if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['sepa']) {
	$accountField = 'iban';
}

// ******************************************************************
// These are the bank account data used for orders
// ******************************************************************
$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_accounts',
		'label' => $accountField,
		'label_userFunc' => 'tx_ttproducts_table_label->getLabel',
		'default_sortby' => 'ORDER BY ' . $accountField,
		'tstamp' => 'tstamp',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'crdate' => 'crdate',
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'sys_products_accounts.gif',
		'searchFields' => 'owner_name,' . $accountField,
	),
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


if ($accountField != 'iban') {
	unset($result['columns']['iban']['config']['eval']);
	$result['columns'][$accountField]['config']['eval'] = 'required,trim';
}


$table = 'sys_products_accounts';

$orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);
if (
	!empty($orderBySortingTablesArray) &&
	in_array($table, $orderBySortingTablesArray)
) {
	$result['ctrl']['sortby'] = 'sorting';
}



return $result;

