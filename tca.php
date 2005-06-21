<?php

// ******************************************************************
// This is the standard TypoScript products table, tt_products
// ******************************************************************
$TCA["tt_products"] = Array (
	"ctrl" => $TCA["tt_products"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "title,itemnumber,price,price2,note,category,inStock,tax,weight,bulkily,offer,highlight,directcost,color,size,accessory,accessory2,special_preparation,gradings,subtitle,image,hidden,starttime,endtime"
	),
	"columns" => Array (
		"starttime" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.starttime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"endtime" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.endtime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"hidden" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check"
			)
		),
		"fe_group" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.fe_group",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.php:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.php:LGL.any_login", -2),
					Array("LLL:EXT:lang/locallang_general.php:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"title" => Array (
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.title",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"note" => Array (
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.note",
			"config" => Array (
				"type" => "text",
				"cols" => "48",
				"rows" => "5"
			)
		),
		"price" => Array (
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.price",
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"price2" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.price2",
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"www" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.www",
			"config" => Array (
				"type" => "input",
				"eval" => "trim",
				"size" => "20",
				"max" => "80"
			)
		),
		"itemnumber" => Array (
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.itemnumber",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "40"
			)
		),
		"category" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.category",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", 0)
				),
				"foreign_table" => "tt_products_cat"
			)
		),
		"inStock" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.inStock",
			"config" => Array (
				"type" => "input",
				"size" => "6",
				"max" => "6",
				"eval" => "int",
				"default" => "-1"
			)
		),
		"tax" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.tax",
			"config" => Array (
				"type" => "input",
				"size" => "6",
				"max" => "2",
				"eval" => "int",
				"range" => Array (
					"lower" => 0
				)
			)
		),
		"weight" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.weight",
			"config" => Array (
				"type" => "input",
				"size" => "6",
				"max" => "20",
				"eval" => "trim,double2",
			)
		),
		"bulkily" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.bulkily",
			"config" => Array (
				"type" => "check",
			)
		),
		"offer" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.offer",
			"config" => Array (
				"type" => "check",
			)
		),
		"highlight" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.highlight",
			"config" => Array (
				"type" => "check",
			)
		),
		"directcost" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.directcost",
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"color" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.color",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "255"
			)
		),
		"accessory" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.accessory",
			"config" => Array (
				"type" => "input",
				"size" => "6",
				"eval" => "trim,double2",
				"max" => "10"
			)
		),
		"accessory2" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.accessory2",
			"config" => Array (
				"type" => "input",
				"size" => "6",
				"eval" => "trim,double2",
				"max" => "10"
			)
		),
		"size" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.size",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "255"
			)
		),
		"special_preparation" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.special_preparation",
			"config" => Array (
				"type" => "check"
			)
		),
		"gradings" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.gradings",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"eval" => "trim",
				"max" => "30"
			)
		),
		"subtitle" => Array (
			"label" => "LLL:EXT:tt_products/locallang_tca.php:tt_products.subtitle",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"max" => "256"
			)
		),
		"image" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.image",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],
				"max_size" => "1000",
				"uploadfolder" => "uploads/pics",
				"show_thumbs" => "1",
				"size" => "3",
				"maxitems" => "6",
				"minitems" => "0"
			)
		)
	),
	"types" => Array (
		"1" => Array("showitem" => "hidden;;;;1-1-1, title;;3;;3-3-3, itemnumber, category, price;;2, weight;;4, accessory;;5, note;;;richtext[cut|copy|paste|formatblock|textcolor|bold|italic|underline|left|center|right|orderedlist|unorderedlist|outdent|indent|link|table|image|line|chMode]:rte_transform[mode=ts_css|imgpath=uploads/tx_djshop/rte/], image;;;;5-5-5")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "starttime, endtime, fe_group"),
		"2" => Array("showitem" => "price2, inStock, directcost, tax, offer, highlight"),
		"3" => Array("showitem" => "www, subtitle"),
		"4" => Array("showitem" => "bulkily, color, size, gradings, special_preparation"),
		"5" => Array("showitem" => "accessory2")
	)
);





// ******************************************************************
// This is the standard TypoScript products category table, tt_products_cat
// ******************************************************************
$TCA["tt_products_cat"] = Array (
	"ctrl" => $TCA["tt_products_cat"]["ctrl"],
	"columns" => Array (
		"title" => Array (
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.title",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		)
	),
	"types" => Array (
		"0" => Array("showitem" => "title;;;;3-3-3")
	)
);
?>
