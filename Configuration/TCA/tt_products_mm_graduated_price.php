<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// ******************************************************************
// products to graduated price relation table, tt_products_mm_graduated_price
// ******************************************************************
$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_mm_graduated_price',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden'
		),
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'crdate' => 'crdate',
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_cat.gif',
		'hideTable' => TRUE,
	),
	'interface' => array (
		'showRecordFieldList' => 'product_uid,graduated_price_uid'
	),
	'feInterface' => $TCA['tt_products_mm_graduated_price']['feInterface'],
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

return $result;

