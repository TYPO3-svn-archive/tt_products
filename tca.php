<?php

$addressTable = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['addressTable'];

if (!$addressTable)	{
	if (t3lib_extMgm::isLoaded(PARTY_EXTkey))	{
		$addressTable = 'tx_party_addresses';
	} else if (t3lib_extMgm::isLoaded(PARTNER_EXTkey))	{
		$addressTable = 'tx_partner_main';
	} else if (t3lib_extMgm::isLoaded(TT_ADDRESS_EXTkey)) {
		$addressTable = 'tt_address';
	} else {
		$addressTable = 'fe_users';
	}
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['addressTable'] = $addressTable;

$imageFolder = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['imageFolder'];
if (!$imageFolder)	{
	$imageFolder = 'uploads/pics';
}

if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['where.']) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['where.']) && isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['where.']['category']))	{
	$whereCategory = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['where.']['category'];
}

// ******************************************************************
// This is the standard TypoScript products table, tt_products
// ******************************************************************
$TCA['tt_products'] = Array (
	'ctrl' => $TCA['tt_products']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,title,subtitle,accessory_uid,related_uid,itemnumber,ean,price,price2,creditpoints,graduated_price_uid,note,note2,note_uid,category,address,inStock,tax,weight,usebydate,bulkily,offer,highlight,directcost,color,color2,color3,size,size2,size3,description,gradings,material,quality,additional,unit,unit_factor,www,datasheet,special_preparation,image,hidden,sellstarttime,sellendtime,shipping,shipping2,handling,dam_uid'
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
			'exclude' => 0,
			'label' => 'LLL:EXT:tt_products/locallang_db.xml:tt_products.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'subtitle' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.subtitle',
			'config' => Array (
				'type' => 'text',
				'rows' => '3',
				'cols' => '20',
				'max' => '512'
			)
		),
		'prod_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.prod_uid',
			'config' => Array (
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
		'itemnumber' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.itemnumber',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '40'
			)
		),
		'ean' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_db.xml:tt_products.ean',
			'config' => Array (
				'type' => 'input',
				'size' => '48',
				'eval' => 'trim',
				'max' => '48'
			)
		),
		'price' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.price',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'price2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.price2',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'creditpoints' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.creditpoints',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'int',
				'max' => '12'
			)
		),
		'graduated_price_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.graduated_price_uid',
			'config' => Array (
				'type' => 'inline',
				'appearance' => Array ('collapseAll' => TRUE, 'useCombination' => TRUE),
				'foreign_table' => 'tt_products_mm_graduated_price',
				'foreign_field' => 'product_uid',
				'foreign_sortby' => 'productsort',
				'foreign_label' => 'graduated_price_uid',
				'foreign_selector' => 'graduated_price_uid',
				'maxitems' => 10
			),
		),
		'note' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_db.xml:tt_products.note',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			)
		),
		'note2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_products/locallang_db.xml:tt_products.note2',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '2'
			)
		),
		'note_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.note_uid',
			'config' => Array (
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
		'text_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.text_uid',
			'config' => Array (
				'type' => 'inline',
				'foreign_table' => 'tt_products_texts',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'maxitems' => 20
			),
		),
		'unit_factor' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.unit_factor',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'eval' => 'double',
				'default' => '1',
				'max' => '6'
			)
		),
		'unit' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.unit',
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
				'size' => '30',
				'max' => '160'
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
				'foreign_table' => 'tt_products_cat',
				'foreign_table_where' => $whereCategory
			)
		),
		'address' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.address',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0)
				),
				'foreign_table' => $addressTable
			)
		),
		'inStock' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.inStock',
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
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.datasheet',
			'config' => Array (
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
		'dam_uid' => Array (
			'displayCond' => 'EXT:'.DAM_EXTkey.':LOADED:true',
			'exclude' => 1,
			'label' => 'LLL:EXT:'.DAM_EXTkey.'/locallang_db.xml:tx_dam_item',
			'config' => Array (
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
		'tax' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.tax',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'max' => '19',
				'eval' => 'trim,double2',
			)
		),
		'weight' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.weight',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim,tx_double6',
			)
		),
		'usebydate' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.usebydate',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'bulkily' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.bulkily',
			'config' => Array (
				'type' => 'check',
			)
		),
		'offer' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.offer',
			'config' => Array (
				'type' => 'check',
			)
		),
		'highlight' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.highlight',
			'config' => Array (
				'type' => 'check',
			)
		),
		'directcost' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.directcost',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'accessory_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.accessory_uid',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'MM' => 'tt_products_accessory_products_products_mm',
				'foreign_table' => 'tt_products',
				'foreign_table_where' => ' ORDER BY tt_products.uid',
				'size' => 3,
				'minitems' => 0,
				'maxitems' => 12,
			),
		),
		'related_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.related_uid',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'MM' => 'tt_products_related_products_products_mm',
				'foreign_table' => 'tt_products',
				'foreign_table_where' => ' ORDER BY tt_products.uid',
				'size' => 3,
				'minitems' => 0,
				'maxitems' => 50,
			),
		),
		'color' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.color',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'color2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.color2',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'color3' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.color3',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'size' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.size',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'size2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.size2',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'size3' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.size3',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'description' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.description',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'gradings' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.gradings',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'material' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.material',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'quality' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.quality',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
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
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.additional',
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
										<label>LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.additional.issingle</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</isSingle>
								<isImage>
									<TCEforms>
										<label>LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.additional.isimage</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</isImage>
								<alwaysInStock>
									<TCEforms>
										<label>LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.additional.alwaysInStock</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</alwaysInStock>
								<noMinPrice>
									<TCEforms>
										<label>LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.additional.noMinPrice</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</noMinPrice>
								<noGiftService>
									<TCEforms>
										<label>LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.additional.noGiftService</label>
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
		'special_preparation' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.special_preparation',
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
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => $imageFolder,
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '30',
				'minitems' => '0'
			)
		),
		'shipping' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.shipping',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim,double2',
			)
		),
		'shipping2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.shipping2',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim,double2',
			)
		),
		'handling' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.handling',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim,double2',
			)
		),
		'delivery' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.delivery',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array ('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.delivery.availableDemand', '0'),
					Array ('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.delivery.availableImmediate', '1'),
					Array ('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.delivery.availableShort', '2')
				),
				'size' => '6',
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'sellstarttime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.sellstarttime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'sellendtime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.sellendtime',
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
	),
	'types' => Array (
		'0' => Array('showitem' => 'title;;5;;3-3-3, itemnumber;;3, category, address, price;;4,graduated_price_uid,weight;;6,hidden;;1,' .
			'--div--;LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.descriptions,note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/],note_uid;;;;1-1-1,text_uid;;;;1-1-1,image;;;;4-4-4,datasheet;;;;1-1-1,dam_uid;;;;1-1-1,'.
			'--div--;LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.variants,color;;;;4-4-4,color2;;7;;,size,size2;;8,description,gradings,material,quality;;9,additional;;10,'.
			'--div--;LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.relations,accessory_uid,related_uid,'.
			'--div--;LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.shippingdiv,shipping,shipping2,handling,delivery,')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'sellstarttime,sellendtime,starttime, endtime, fe_group'),
		'3' => Array('showitem' => 'inStock,ean'),
		'4' => Array('showitem' => 'price2, directcost, tax, creditpoints, offer, highlight'),
		'5' => Array('showitem' => 'subtitle, www'),
		'6' => Array('showitem' => 'bulkily, special_preparation, unit, unit_factor'),
		'7' => Array('showitem' => 'color3'),
		'8' => Array('showitem' => 'size3'),
		'10' => Array('showitem' => 'usebydate')
	)
);


// ******************************************************************
// This is the language overlay for the products table, tt_products
// ******************************************************************
$TCA['tt_products_language'] = Array (
	'ctrl' => $TCA['tt_products_language']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,hidden,starttime,endtime,prod_uid,title,subtitle,unit,note,datasheet,www'
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
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_language.prod_uid',
			'config' => Array (
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
		'title' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'subtitle' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.subtitle',
			'config' => Array (
				'type' => 'text',
				'rows' => '3',
				'cols' => '20',
				'max' => '512'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'unit' => Array (
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.unit',
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
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'note2' => Array (
			'label' => '2:',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '2'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'datasheet' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_language.datasheet',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'doc,htm,html,pdf,sxw,txt,xls,gif,jpg,png',
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
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
				'size' => '30',
				'max' => '160'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
	),
	'types' => Array (
		'1' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_diffsource, hidden;;1, prod_uid,title;;2;;3-3-3, unit, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], image;;;;4-4-4,datasheet')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime,endtime,fe_group'),
		'2' => Array('showitem' => 'subtitle, www'),
	)
);



// ******************************************************************
// products to graduated price relation table, tt_products_mm_graduated_price
// ******************************************************************
$TCA['tt_products_mm_graduated_price'] = Array (
	'ctrl' => $TCA['tt_products_mm_graduated_price']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'product_uid,graduated_price_uid'
	),
	'feInterface' => $TCA['tt_products_mm_graduated_price']['feInterface'],
	'columns' => Array (
		'product_uid' => Array (
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_mm_graduated_price.product_uid',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tt_products',
				'maxitems' => 1
			)
		),
		'graduated_price_uid' => Array (
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_mm_graduated_price.graduated_price_uid',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tt_products_graduated_price',
				'maxitems' => 1
			)
		),
		'productsort' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
		'graduatedsort' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
	),
);



// ******************************************************************
// graduated price calculation table, tt_products_graduated_price
// ******************************************************************
$TCA['tt_products_graduated_price'] = Array (
	'ctrl' => $TCA['tt_products_graduated_price']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'title, formula, startamount, note, parentid'
	),
	'feInterface' => $TCA['tt_products_cat']['feInterface'],
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
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_graduated_price.starttime',
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
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_graduated_price.endtime',
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
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'formula' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_graduated_price.formula',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '1'
			)
		),
		'startamount' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_graduated_price.startamount',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'note' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.note',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '2'
			)
		),
		'items' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_graduated_price.items',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
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
	'types' => Array (
		'0' => Array('showitem' => 'hidden;;1;;1-1-1, title, formula, startamount, note, items')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime')
	)
);



// ******************************************************************
// This is the standard TypoScript products category table, tt_products_cat
// ******************************************************************
$TCA['tt_products_cat'] = Array (
	'ctrl' => $TCA['tt_products_cat']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,title, subtitle, note, note2, image, email_uid'
	),
	'feInterface' => $TCA['tt_products_cat']['feInterface'],
	'columns' => Array (
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check'
			)
		),
		'tstamp' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXTkey . '/locallang_db.xml:tstamp',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0'
			)
		),
		'crdate' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXTkey . '/locallang_db.xml:crdate',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
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
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'subtitle' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.subtitle',
			'config' => Array (
				'type' => 'text',
				'rows' => '3',
				'cols' => '20',
				'max' => '512'
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
		'note2' => Array (
			'exclude' => 1,
			'label' => '2:',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			)
		),
		'image' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.image',
			'config' => Array (
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
		'email_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_cat.email_uid',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
				),
				'foreign_table' => 'tt_products_emails',
				'foreign_table_where' => 'AND tt_products_emails.pid IN (###CURRENT_PID###,###STORAGE_PID###) ORDER BY tt_products_emails.uid',
				'size' => 6,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'title, subtitle, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], email_uid, image;;;;3-3-3,hidden;;1;;')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime, fe_group')
	)
);



// ******************************************************************
// This is the language overlay for  products category table, tt_products_cat
// ******************************************************************
$TCA['tt_products_cat_language'] = Array (
	'ctrl' => $TCA['tt_products_cat_language']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_diffsource,hidden,starttime,endtime,fe_group,title,subtitle,note,note2,cat_uid'
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
		'tstamp' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXTkey . '/locallang_db.xml:tstamp',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0'
			)
		),
		'crdate' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXTkey . '/locallang_db.xml:crdate',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0'
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
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'subtitle' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.subtitle',
			'config' => Array (
				'type' => 'text',
				'rows' => '3',
				'cols' => '20',
				'max' => '512'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'note' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.note',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'note2' => Array (
			'label' => '2',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'cat_uid' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_cat_language.cat_uid',
			'config' => Array (
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
	'types' => Array (
		'0' => Array('showitem' => '
		sys_language_uid;;;;1-1-1, l18n_diffsource, cat_uid;;;;2-2-2, title, subtitle, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/],hidden;;1;;')
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
		'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,title,subtitle,itemnumber,price,price2,weight,inStock,color,color2,color3,size,size2,size3,description,gradings,material,quality,note,image,uid_product'
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
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
			)
		),
		'subtitle' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.subtitle',
			'config' => Array (
				'type' => 'text',
				'rows' => '3',
				'cols' => '20',
				'max' => '512'
			)
		),
		'itemnumber' => Array (
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.itemnumber',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '40'
			)
		),
		'price' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.price',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'price2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.price2',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20'
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
		'inStock' => Array (
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.inStock',
			'config' => Array (
				'type' => 'input',
				'size' => '6',
				'max' => '6',
				'eval' => 'int',
				'default' => '1'
			)
		),
		'weight' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.weight',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim,tx_double6',
			)
		),
		'color' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.color',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'color2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.color2',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'color3' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.color3',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'size' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.size',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'size2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.size2',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'size3' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.size3',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'description' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.description',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'gradings' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.gradings',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'material' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.material',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'quality' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.quality',
			'config' => Array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5'
			)
		),
		'uid_product' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_articles.uid_product',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'image' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.image',
			'config' => Array (
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
		)
	),
	'types' => Array (
		'1' => Array('showitem' => 'hidden;;1, title;;3;;3-3-3, uid_product, itemnumber, inStock, price;;2, weight, color, color2, color3, size, size2, size3, description, gradings, material, quality, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/],image;;;;3-3-3')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime, fe_group'),
		'2' => Array('showitem' => 'price2'),
		'3' => Array('showitem' => 'subtitle'),
	)

);


// ******************************************************************
// This is the language overlay for the articles table, tt_products_articles
// ******************************************************************
$TCA['tt_products_articles_language'] = Array (
	'ctrl' => $TCA['tt_products_articles_language']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,hidden,starttime,endtime,fe_group,article_uid,title,subtitle,note'
	),
	'feInterface' => $TCA['tt_products_articles_language']['feInterface'],
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
		'article_uid' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_articles_language.article_uid',
			'config' => Array (
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
		'title' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'subtitle' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products.subtitle',
			'config' => Array (
				'type' => 'text',
				'rows' => '3',
				'cols' => '20',
				'max' => '512'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'note' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.note',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
	),
	'types' => Array (
		'1' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_diffsource, hidden;;1, article_uid,title;;2;;3-3-3, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/]')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime,endtime,fe_group'),
		'2' => Array('showitem' => 'subtitle'),
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
			'exclude' => 0,
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
// This is the standard TypoScript products texts table, tt_products_texts
// ******************************************************************
$TCA['tt_products_texts'] = Array (
	'ctrl' => $TCA['tt_products_texts']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,title,marker,note'
	),
	'feInterface' => $TCA['tt_products_texts']['feInterface'],
	'columns' => Array (
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'title' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'marker' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_texts.marker',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
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
		'parentid' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_texts.parentid',
			'config' => Array (
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
		'parenttable' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_texts.parenttable',
			'config' => Array (
				'type' => 'select',
				'items' => Array(
					Array('tt_products', 'tt_products')
				)
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'hidden;;;;1-1-1, title, marker, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], parentid;;;;2-2-2, parenttable')
	)
);



// ******************************************************************
// This is the language overlay for  products texts table, tt_products_texts
// ******************************************************************
$TCA['tt_products_texts_language'] = Array (
	'ctrl' => $TCA['tt_products_texts_language']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,hidden,starttime,endtime,fe_group,title,marker,note'
	),
	'feInterface' => $TCA['tt_products_texts_language']['feInterface'],
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
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'note' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.note',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'text_uid' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:tt_products_texts_language.text_uid',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products_texts',
				'foreign_table' => 'tt_products_texts',
				'foreign_table_where' => 'AND tt_products_texts.pid IN (###CURRENT_PID###,###STORAGE_PID###) ORDER BY tt_products_texts.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
	),
	'types' => Array (
		'0' => Array('showitem' => '
		sys_language_uid;;;;1-1-1, l18n_diffsource, hidden;;1, text_uid;;;;2-2-2, title, note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/], parenttable')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime, fe_group')
	)
);




// ******************************************************************
// These are the bank account data used for orders
// ******************************************************************
$TCA['sys_products_accounts'] = Array (
	'ctrl' => $TCA['sys_products_accounts']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'ac_number,owner_name,bic'
	),
	'feInterface' => $TCA['sys_products_accounts']['feInterface'],
	'columns' => Array (
		'ac_number' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_accounts.ac_number',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'owner_name' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.name',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80'
			)
		),
		'bic' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_accounts.bic',
			'config' => Array (
				'type' => 'input',
				'size' => '11',
				'max' => '11'
			)
		),
	),
	'types' => Array (
		'1' => Array('showitem' => 'ac_number;;;;1-1-1, owner_name, bic')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



// ******************************************************************
// These are the credit cards data used for orders
// ******************************************************************
$TCA['sys_products_cards'] = Array (
	'ctrl' => $TCA['sys_products_cards']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'cc_number,owner_name,cc_type,cvv2,endtime'
	),
	'feInterface' => $TCA['sys_products_cards']['feInterface'],
	'columns' => Array (
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
		'cc_number' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_cards.cc_number',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'owner_name' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.name',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80'
			)
		),
		'cc_type' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_cards.cc_type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_cards.cc_type.I.0', '0'),
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_cards.cc_type.I.1', '1'),
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_cards.cc_type.I.2', '2'),
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_cards.cc_type.I.3', '3'),
				),
				'size' => 1,
				'maxitems' => 1,
			)
		),
		'cvv2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_cards.cvv2',
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'eval' => 'int',
				'max' => '4'
			)
		),
	),
	'types' => Array (
		'1' => Array('showitem' => 'cc_number;;;;1-1-1 , owner_name, cc_type, cvv2, endtime')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);




// *****************************************************************
// These are the orders
// ******************************************************************
$TCA['sys_products_orders'] = Array (
	'ctrl' => $TCA['sys_products_orders']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,name,first_name,last_name,salutation,address,zip,country,telephone,email,fax,payment,shipping,amount,email_notify,tracking_code,status,agb,feusers_id,creditpoints,creditpoints_spended,creditpoints_saved,creditpoints_gifts,desired_date,client_ip,note,giftservice,cc_uid,ac_uid,date_of_birth,radio1'
	),
	'feInterface' => $TCA['sys_products_orders']['feInterface'],
	'columns' => Array (
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check'
			)
		),
		'name' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.name',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
			)
		),
		'first_name' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.first_name',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'eval' => 'trim',
			)
		),
		'last_name' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.last_name',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'eval' => 'trim',
			)
		),
		'salutation' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.salutation',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.salutation.I.0', '0'),
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.salutation.I.1', '1'),
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.salutation.I.2', '2'),
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.salutation.I.3', '3'),
				),
				'size' => 1,
				'maxitems' => 1,
			)
		),
		'address' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.address',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '256'
			)
		),
		'zip' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.zip',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim',
			)
		),
		'city' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.city',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'eval' => 'trim',
			)
		),
		'country' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.country',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '60',
				'eval' => 'trim',
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
		'fax' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fax',
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
			)
		),
		'payment' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.payment',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
			)
		),
		'shipping' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.shipping',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
			)
		),
		'amount' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.amount',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '20',
				'eval' => 'trim,double2',
			)
		),
		'email_notify' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.email_notify',
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
			)
		),
		'tracking_code' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.tracking_code',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '32',
			)
		),
		'status' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.status',
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
			)
		),
		'status_log' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.status_log',
			'config' => Array (
				'type' => 'text',
				'cols' => '80',
				'rows' => '4',
			)
		),
		'orderData' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.orderData',
			'config' => Array (
				'type' => 'text',
				'cols' => '80',
				'rows' => '30',
			)
		),
		'agb' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.agb',
			'config' => Array (
				'type' => 'input',
				'size' => '2',
				'max' => '2',
			)
		),
		'feusers_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.feusers_uid',
			'config' => Array (
				'type' => 'input',
				'size' => '11',
				'max' => '11',
			)
		),
		'creditpoints' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.creditpoints',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
			)
		),
		'creditpoints_spended' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.creditpoints_spended',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
			)
		),
		'creditpoints_saved' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.creditpoints_saved',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
			)
		),
		'creditpoints_gifts' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.creditpoints_gifts',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
			)
		),
		'desired_date' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.desired_date',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
			)
		),
		'client_ip' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.client_ip',
			'config' => Array (
				'type' => 'input',
				'size' => '15',
				'max' => '15',
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
		'giftservice' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.giftservice',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			)
		),
		'cc_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_cards.cc_number',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'sys_products_cards',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'ac_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_accounts.ac_number',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'sys_products_accounts',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'foundby' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.foundby',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.foundby.I.0', '0'),
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.foundby.I.1', '1'),
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.foundby.I.2', '2'),
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.foundby.I.3', '3'),
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.foundby.I.4', '4'),
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.foundby.I.5', '5'),
					Array('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.foundby.I.6', '6'),
				),
				'size' => 1,
				'maxitems' => 1,
			)
		),
		'giftcode' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.order_code',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'max' => '80',
			)
		),
		'date_of_birth' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.date_of_birth',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'radio1' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.radio1',
			'config' => Array (
				'type' => 'radio',
				'items' => Array (
					Array ('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.radio1.I.0', '0'),
					Array ('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.radio1.I.1', '1'),
					Array ('LLL:EXT:'.TT_PRODUCTS_EXTkey.'/locallang_db.xml:sys_products_orders.radio1.I.2', '2'),
				),
				'default' => '0'
			)
		),
	),
	'types' => Array (
		'1' => Array('showitem' => 'hidden;;;;1-1-1, name;;3;;3-3-3, first_name,last_name,salutation,address,zip,country,telephone,email,payment,shipping,amount,email_notify,tracking_code,status,fax,agb,feusers_uid,creditpoints,creditpoints_spended,creditpoints_saved,creditpoints_gifts,desired_date,client_ip,note,giftservice,foundby,giftcode,cc_uid,ac_uid,date_of_birth,radio1')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime, fe_group'),
	)

);


?>
