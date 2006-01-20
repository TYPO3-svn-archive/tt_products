<?php

// ******************************************************************
// This is the standard TypoScript products table, tt_products
// ******************************************************************
$TCA['tt_products'] = Array (
	'ctrl' => $TCA['tt_products']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,title,subtitle,itemnumber,price,price2,note,category,inStock,tax,weight,bulkily,offer,highlight,directcost,color,size,description,gradings,additional,unit,unit_factor,www,datasheet,special_preparation,image,hidden,starttime,endtime'
	),
	'columns' => Array (
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check'
			)
		),
		'starttime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'endtime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => Array (
					'upper' => mktime(0,0,0,12,31,2020),
					'lower' => mktime(0,0,0,date('m')-1,date('d'),date('Y'))
				)
			)
		),
		'fe_group' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
					Array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
					Array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'title' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'subtitle' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.subtitle',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '256'
			)
		),
		'itemnumber' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.itemnumber',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '40'
			)
		),
		'note' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.note',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			)
		),
		'price' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.price',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'price2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.price2',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'unit_factor' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.unit_factor',
			'config' => Array (
				'type' => 'input',
				'size' => '6',
				'eval' => 'int',
				'default' => '1',
				'max' => '6'
			)
		),
		'unit' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.unit',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '20'
			)
		),
		'www' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.www',
			'config' => Array (
				'type' => 'input',
				'eval' => 'trim',
				'size' => '20',
				'max' => '80'
			)
		),
		'category' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.category',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0)
				),
				'foreign_table' => 'tt_products_cat'
			)
		),
		'inStock' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.inStock',
			'config' => Array (
				'type' => 'input',
				'size' => '6',
				'max' => '6',
				'eval' => 'int',
				'default' => '1'
			)
		),
		'datasheet' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.datasheet',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'doc,htm,html,pdf,sxw,txt,xls,gif,jpg,png',
				'max_size' => '1000',
				'uploadfolder' => 'uploads/tx_ttproducts/datasheet',
				'show_thumbs' => '1',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '0'
			)
		),
		'tax' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.tax',
			'config' => Array (
				'type' => 'input',
				'size' => '6',
				'max' => '2',
				'eval' => 'int',
				'range' => Array (
					'lower' => 0
				)
			)
		),
		'weight' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.weight',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim',
			)
		),
		'bulkily' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.bulkily',
			'config' => Array (
				'type' => 'check',
			)
		),
		'offer' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.offer',
			'config' => Array (
				'type' => 'check',
			)
		),
		'highlight' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.highlight',
			'config' => Array (
				'type' => 'check',
			)
		),
		'directcost' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.directcost',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'color' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.color',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '255'
			)
		),
		'size' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.size',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '255'
			)
		),
		'description' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.description',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			)
		),
		'additional_type' => Array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:list_type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('','')
				),
				'default' => '',
				'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
			)
		),
		'additional' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.additional',
			'config' => Array (
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
										<label>LLL:EXT:tt_products/locallang_tca.xml:tt_products.additional.once</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</isSingle>
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
		'gradings' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.gradings',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '255'
			)
		),
		'special_preparation' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.special_preparation',
			'config' => Array (
				'type' => 'check'
			)
		),
		'image' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.image',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => '1000',
				'uploadfolder' => 'uploads/pics',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '6',
				'minitems' => '0'
			)
		)
	),
/*
	'types' => Array (
//		'1' => Array('showitem' => 'hidden;;;;1-1-1, title;;3;;3-3-3, subtitle, www, category, price;;2, price2, color, size, special_preparation, unit_factor, weight;;4, accessory;;5,unit;;6, note;;;richtext[*]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], image;;;;5-5-5,datasheet')
		'1' => Array('showitem' => 'hidden;;;;1-1-1, starttime, endtime, title;;;;2-2-2, subtitle, inStock, itemnumber, www, category, price;;;;3-3-3, price2, tax, weight, bulkily, offer, highlight, directcost, color, size, gradings, special_preparation, accessory, accessory2, unit, unit_factor, note;;;richtext[*]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], image, datasheet')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
*/
	'types' => Array (
		'0' => Array('showitem' => ';;;;1-1-1,hidden;;1, title;;4;;3-3-3, itemnumber;;2, category, price;;3,weight;;6, note;;;richtext[*]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/],image;;;;4-4-4,datasheet,' .
				'--div--;LLL:EXT:tt_products/locallang_tca.xml:tt_products.variants,  color;;7,description,additional')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime, fe_group'),
		'2' => Array('showitem' => 'inStock'),
		'3' => Array('showitem' => 'price2, directcost, tax, offer, highlight'),
		'4' => Array('showitem' => 'subtitle, www'),
		'5' => Array('showitem' => 'bulkily, special_preparation, unit, unit_factor'),
		'6' => Array('showitem' => ''),
		'7' => Array('showitem' => 'size, gradings'),
	)

);



$TCA['tt_products_language'] = Array (
	'ctrl' => $TCA['tt_products_language']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_diffsource,hidden,starttime,endtime,prod_uid,title,subtitle,unit,note,datasheet,www'
	),
	'feInterface' => $TCA['tt_products_language']['feInterface'],
	'columns' => Array (
		'sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'starttime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => Array (
					'upper' => mktime(0,0,0,12,31,2020),
					'lower' => mktime(0,0,0,date('m')-1,date('d'),date('Y'))
				)
			)
		),
		'fe_group' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
					Array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
					Array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'prod_uid' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products_language.prod_uid',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tt_products',
				'foreign_table_where' => 'AND tt_products.pid=###CURRENT_PID### ORDER BY tt_products.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'title' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'subtitle' => Array (
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.subtitle',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '256'
			)
		),
		'unit' => Array (
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.unit',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '20'
			)
		),
		'note' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.note',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			)
		),
		'datasheet' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products_language.datasheet',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'doc,htm,html,pdf,sxw,txt,xls,gif,jpg,png',
				'max_size' => '1000',
				'uploadfolder' => 'uploads/tx_ttproducts/datasheet',
				'show_thumbs' => '1',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '0'
			)
		),
		'www' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.www',
			'config' => Array (
				'type' => 'input',
				'eval' => 'trim',
				'size' => '20',
				'max' => '80'
			)
		),
	),
	'types' => Array (
		'1' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_diffsource, hidden;;1, prod_uid,title;;2;;3-3-3, note;;;richtext[*]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], image;;;;4-4-4,datasheet')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime,endtime,fe_group'),
		'2' => Array('showitem' => 'subtitle, www'),
	)
);





// ******************************************************************
// This is the standard TypoScript products category table, tt_products_cat
// ******************************************************************
$TCA['tt_products_cat'] = Array (
	'ctrl' => $TCA['tt_products_cat']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,title, image, email_uid'
	),
	'feInterface' => $TCA['tt_products_cat']['feInterface'],
	'columns' => Array (
		'title' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'image' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.image',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => '1000',
				'uploadfolder' => 'uploads/pics',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '6',
				'minitems' => '0'
			)
		),
		'email_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products_cat.email_uid',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
				),
				'foreign_table' => 'tt_products_emails',
				'foreign_table_where' => 'AND tt_products_emails.pid=###CURRENT_PID### ORDER BY tt_products_emails.uid',
				'size' => 6,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'hidden;;;;1-1-1, title, email_uid, image;;;;3-3-3')
	)
);



// ******************************************************************
// This is the language overlay for  products category table, tt_products_cat
// ******************************************************************
$TCA['tt_products_cat_language'] = Array (
	'ctrl' => $TCA['tt_products_cat_language']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_diffsource,hidden,starttime,endtime,fe_group,title,note,cat_uid'
	),
	'feInterface' => $TCA['tt_products_cat_language']['feInterface'],
	'columns' => Array (
		'sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'starttime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => Array (
					'upper' => mktime(0,0,0,12,31,2020),
					'lower' => mktime(0,0,0,date('m')-1,date('d'),date('Y'))
				)
			)
		),
		'title' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'note' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.note',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			)
		),
		'cat_uid' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products_cat_language.cat_uid',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tt_products_cat',
				'foreign_table_where' => 'AND tt_products_cat.pid=###CURRENT_PID### ORDER BY tt_products_cat.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => '
		sys_language_uid;;;;1-1-1, l18n_diffsource, hidden;;1,  cat_uid;;;;2-2-2, sys_language_uid, title, note;;;;3-3-3')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime, fe_group')
	)
);

// ******************************************************************
// These are the articles for some of the products where variants exist
// ******************************************************************
$TCA['tt_products_articles'] = Array (
	'ctrl' => $TCA['tt_products_articles']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,title,subtitle,itemnumber,price,price2,inStock,color,size,gradings,uid_product'
	),
	'feInterface' => $TCA['tt_products_articles']['feInterface'],
	'columns' => Array (
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'starttime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => Array (
					'upper' => mktime(0,0,0,12,31,2020),
					'lower' => mktime(0,0,0,date('m')-1,date('d'),date('Y'))
				)
			)
		),
		'fe_group' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
					Array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
					Array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'title' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'subtitle' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.subtitle',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'itemnumber' => Array (
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.itemnumber',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '40'
			)
		),
		'price' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.price',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'price2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.price2',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'inStock' => Array (
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.inStock',
			'config' => Array (
				'type' => 'input',
				'size' => '6',
				'max' => '6',
				'eval' => 'int',
				'default' => '1'
			)
		),
		'color' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.color',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '60'
			)
		),
		'size' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.size',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '60'
			)
		),
		'gradings' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products.gradings',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '60'
			)
		),
		'uid_product' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:tt_products_articles.uid_product',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		)
	),
	'types' => Array (
		'1' => Array('showitem' => 'hidden;;;;1-1-1, title;;3;;3-3-3, itemnumber, inStock, price;;2, price2, color, size, gradings')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime, fe_group'),
		'2' => Array('showitem' => 'uid_product', 'price2'),
		'3' => Array('showitem' => 'subtitle'),
	)

);


// ******************************************************************
// These are the email addresses which are used for sending notification emails
// ******************************************************************
$TCA['tt_products_emails'] = Array (
	'ctrl' => $TCA['tt_products_emails']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'name,email,hidden,starttime,endtime,fe_group'
	),
	'feInterface' => $TCA['tt_products_emails']['feInterface'],
	'columns' => Array (
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'starttime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => Array (
					'upper' => mktime(0,0,0,12,31,2020),
					'lower' => mktime(0,0,0,date('m')-1,date('d'),date('Y'))
				)
			)
		),
		'fe_group' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
					Array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
					Array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'name' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.name',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80'
			)
		),
		'email' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.email',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80'
			)
		),
	),
	'types' => Array (
		'1' => Array('showitem' => 'hidden;;;;1-1-1, name, email')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime, fe_group')
	)
);




// ******************************************************************
// These are the orders
// ******************************************************************
$TCA['sys_products_orders'] = Array (
	'ctrl' => $TCA['sys_products_orders']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'name,note,telephone,email,payment,shipping,amount,email_notify,tracking_code,status,status_log,orderData,fax,agb,feusers_id,creditpoints,creditpoints_spended,creditpoints_saved,creditpoints_gifts,desired_date'
	),
	'feInterface' => $TCA['tt_products_articles']['feInterface'],
	'columns' => Array (
		'name' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.name',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'note' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.note',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			)
		),
		'telephone' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.phone',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '20',
			)
		),
		'email' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.email',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '80',
			)
		),
		'payment' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.payment',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
			)
		),
		'shipping' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.shipping',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
			)
		),
		'amount' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.amount',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '20',
			)
		),
		'email_notify' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.email_notify',
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
			)
		),
		'tracking_code' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.tracking_code',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '20',
			)
		),
		'status' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.status',
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
			)
		),
		'status_log' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.status_log',
			'config' => Array (
				'type' => 'text',
				'cols' => '80',
				'rows' => '4',
			)
		),
		'orderData' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.orderData',
			'config' => Array (
				'type' => 'text',
				'cols' => '80',
				'rows' => '30',
			)
		),
		'fax' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fax',
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
			)
		),
		'agb' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.agb',
			'config' => Array (
				'type' => 'input',
				'size' => '2',
				'max' => '2',
			)
		),
		'feusers_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.feusers_uid',
			'config' => Array (
				'type' => 'input',
				'size' => '11',
				'max' => '11',
			)
		),
		'creditpoints' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.creditpoints',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
			)
		),
		'creditpoints_spended' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.creditpoints_spended',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
			)
		),
		'creditpoints_saved' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.creditpoints_saved',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
			)
		),
		'creditpoints_gifts' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.creditpoints_gifts',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
			)
		),
		'desired_date' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_tca.xml:sys_products_orders.desired_date',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
			)
		),
	),
	'types' => Array (
		'1' => Array('showitem' => 'hidden;;;;1-1-1, name;;3;;3-3-3, note,telephone,email,payment,shipping,amount,email_notify,tracking_code,status,status_log,orderData,fax,agb,feusers_id,creditpoints,creditpoints_spended,creditpoints_saved,creditpoints_gifts,desired_date')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime, fe_group'),
	)

);


?>
