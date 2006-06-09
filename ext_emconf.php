<?php

########################################################################
# Extension Manager/Repository config file for ext: "tt_products"
#
# Auto generated 07-06-2006 13:07
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Shop System',
	'description' => 'Open Source Shop in multiple languages, photo gallery using DAM, product variants, payment gateways, bill, creditpoint, voucher system and gift certificates. Requires table v0.1.3 and fh_library v0.0.9!',
	'category' => 'plugin',
	'shy' => 0,
	'dependencies' => 'cms,table,fh_library',
	'conflicts' => 'zk_products,mkl_products,ast_rteproducts,onet_ttproducts_rte,shopsort,c3bi_cookie_at_login',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_ttproducts/datasheet,fileadmin/data/bill,fileadmin/data/delivery,fileadmin/img',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Franz Holzinger',
	'author_email' => 'kontakt@fholzinger.com',
	'author_company' => 'Freelancer',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '2.4.11',
	'_md5_values_when_last_written' => 'a:109:{s:9:"ChangeLog";s:4:"a572";s:31:"class.tx_ttproducts_wizicon.php";s:4:"7816";s:21:"ext_conf_template.txt";s:4:"d528";s:12:"ext_icon.gif";s:4:"eb61";s:17:"ext_localconf.php";s:4:"1511";s:14:"ext_tables.php";s:4:"a54d";s:14:"ext_tables.sql";s:4:"f5e9";s:28:"ext_typoscript_constants.txt";s:4:"1476";s:24:"ext_typoscript_setup.txt";s:4:"6312";s:19:"flexform_ds_pi1.xml";s:4:"c708";s:13:"locallang.xml";s:4:"ba8c";s:24:"locallang_csh_ttprod.php";s:4:"a2c6";s:25:"locallang_csh_ttproda.php";s:4:"026a";s:25:"locallang_csh_ttprodc.php";s:4:"cfa4";s:25:"locallang_csh_ttprode.php";s:4:"013d";s:25:"locallang_csh_ttprodo.php";s:4:"12e9";s:16:"locallang_db.xml";s:4:"20e5";s:7:"tca.php";s:4:"cac7";s:14:"doc/manual.sxw";s:4:"a05c";s:35:"lib/class.tx_ttproducts_article.php";s:4:"a8fd";s:40:"lib/class.tx_ttproducts_article_base.php";s:4:"a954";s:37:"lib/class.tx_ttproducts_attribute.php";s:4:"7dec";s:34:"lib/class.tx_ttproducts_basket.php";s:4:"3dd3";s:39:"lib/class.tx_ttproducts_basket_view.php";s:4:"48ce";s:40:"lib/class.tx_ttproducts_billdelivery.php";s:4:"392c";s:36:"lib/class.tx_ttproducts_category.php";s:4:"e984";s:41:"lib/class.tx_ttproducts_category_base.php";s:4:"660f";s:40:"lib/class.tx_ttproducts_catlist_view.php";s:4:"2b63";s:35:"lib/class.tx_ttproducts_content.php";s:4:"c015";s:44:"lib/class.tx_ttproducts_creditpoints_div.php";s:4:"5887";s:31:"lib/class.tx_ttproducts_csv.php";s:4:"37b5";s:41:"lib/class.tx_ttproducts_currency_view.php";s:4:"6358";s:33:"lib/class.tx_ttproducts_email.php";s:4:"c8b1";s:37:"lib/class.tx_ttproducts_email_div.php";s:4:"7d18";s:36:"lib/class.tx_ttproducts_fe_users.php";s:4:"ba60";s:37:"lib/class.tx_ttproducts_gifts_div.php";s:4:"7055";s:33:"lib/class.tx_ttproducts_image.php";s:4:"dc26";s:38:"lib/class.tx_ttproducts_javascript.php";s:4:"d2a5";s:37:"lib/class.tx_ttproducts_list_view.php";s:4:"29ec";s:34:"lib/class.tx_ttproducts_marker.php";s:4:"24f9";s:37:"lib/class.tx_ttproducts_memo_view.php";s:4:"abf3";s:33:"lib/class.tx_ttproducts_order.php";s:4:"b7fe";s:38:"lib/class.tx_ttproducts_order_view.php";s:4:"225e";s:32:"lib/class.tx_ttproducts_page.php";s:4:"3625";s:43:"lib/class.tx_ttproducts_paymentshipping.php";s:4:"6ed6";s:33:"lib/class.tx_ttproducts_price.php";s:4:"267b";s:37:"lib/class.tx_ttproducts_pricecalc.php";s:4:"dec9";s:35:"lib/class.tx_ttproducts_product.php";s:4:"9545";s:39:"lib/class.tx_ttproducts_single_view.php";s:4:"fd53";s:36:"lib/class.tx_ttproducts_tracking.php";s:4:"bd22";s:35:"lib/class.tx_ttproducts_variant.php";s:4:"f6f6";s:31:"pi1/class.tx_ttproducts_pi1.php";s:4:"164c";s:13:"pi1/clear.gif";s:4:"cc11";s:17:"pi1/locallang.xml";s:4:"30f4";s:20:"pi1/payment_DIBS.php";s:4:"48fa";s:32:"pi1/products_comp_calcScript.inc";s:4:"218c";s:24:"pi1/static/editorcfg.txt";s:4:"4dd7";s:34:"pi1/static/old_style/constants.txt";s:4:"a932";s:30:"pi1/static/old_style/setup.txt";s:4:"686f";s:23:"res/icons/be/ce_wiz.gif";s:4:"a6c1";s:28:"res/icons/be/productlist.gif";s:4:"a6c1";s:24:"res/icons/fe/AddItem.gif";s:4:"287d";s:40:"res/icons/fe/Cart Icon-AddRemoveItem.psd";s:4:"857a";s:34:"res/icons/fe/Cart-Icon-AddItem.gif";s:4:"e76c";s:37:"res/icons/fe/Cart-Icon-RemoveItem.gif";s:4:"b9cc";s:26:"res/icons/fe/Cart-Icon.gif";s:4:"988a";s:27:"res/icons/fe/RemoveItem.gif";s:4:"e28f";s:24:"res/icons/fe/addmemo.png";s:4:"c76f";s:21:"res/icons/fe/amex.gif";s:4:"22e1";s:23:"res/icons/fe/basket.gif";s:4:"ca3d";s:24:"res/icons/fe/delmemo.png";s:4:"b1da";s:25:"res/icons/fe/discover.gif";s:4:"91c4";s:27:"res/icons/fe/mastercard.gif";s:4:"2fe1";s:28:"res/icons/fe/minibasket1.gif";s:4:"a960";s:35:"res/icons/fe/ttproducts_help_en.png";s:4:"5326";s:21:"res/icons/fe/visa.gif";s:4:"28c6";s:39:"res/icons/table/sys_products_orders.gif";s:4:"9d4e";s:31:"res/icons/table/tt_products.gif";s:4:"1ebd";s:40:"res/icons/table/tt_products_articles.gif";s:4:"1ebd";s:35:"res/icons/table/tt_products_cat.gif";s:4:"f852";s:44:"res/icons/table/tt_products_cat_language.gif";s:4:"d4fe";s:38:"res/icons/table/tt_products_emails.gif";s:4:"1ebd";s:40:"res/icons/table/tt_products_language.gif";s:4:"9d4e";s:16:"template/agb.txt";s:4:"5a56";s:38:"template/example_template_bill_de.tmpl";s:4:"1202";s:35:"template/payment_DIBS_template.tmpl";s:4:"f1d8";s:38:"template/payment_DIBS_template_uk.tmpl";s:4:"9f48";s:24:"template/paymentlib.tmpl";s:4:"056a";s:27:"template/products_help.tmpl";s:4:"d2d6";s:31:"template/products_template.tmpl";s:4:"ad42";s:34:"template/products_template_dk.tmpl";s:4:"7665";s:34:"template/products_template_fi.tmpl";s:4:"0f9f";s:34:"template/products_template_fr.tmpl";s:4:"a233";s:40:"template/products_template_htmlmail.tmpl";s:4:"aa8a";s:34:"template/products_template_se.tmpl";s:4:"7161";s:39:"template/meerwijn/detail_cadeaubon.tmpl";s:4:"c263";s:40:"template/meerwijn/detail_geschenken.tmpl";s:4:"b695";s:40:"template/meerwijn/detail_kurkenshop.tmpl";s:4:"0fad";s:38:"template/meerwijn/detail_shopabox.tmpl";s:4:"21a3";s:36:"template/meerwijn/detail_wijnen.tmpl";s:4:"63be";s:37:"template/meerwijn/product_detail.tmpl";s:4:"9e4a";s:45:"template/meerwijn/product_proefpakketten.tmpl";s:4:"9afd";s:32:"template/meerwijn/producten.tmpl";s:4:"95a0";s:33:"template/meerwijn/shop-a-box.tmpl";s:4:"5606";s:40:"template/meerwijn/totaal_geschenken.tmpl";s:4:"15ca";s:40:"template/meerwijn/totaal_kurkenshop.tmpl";s:4:"1306";s:38:"template/meerwijn/totaal_shopabox.tmpl";s:4:"f87b";s:36:"template/meerwijn/totaal_wijnen.tmpl";s:4:"5ee1";s:34:"template/meerwijn/winkelwagen.tmpl";s:4:"1ac5";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'table' => '0.1.3-',
			'fh_library' => '0.0.9-',
			'php' => '4.2.3-',
			'typo3' => '3.8.0-4.0.20',
		),
		'conflicts' => array(
			'zk_products' => '',
			'mkl_products' => '',
			'ast_rteproducts' => '',
			'onet_ttproducts_rte' => '',
			'shopsort' => '',
			'c3bi_cookie_at_login' => '',
		),
		'suggests' => array(
			'sr_static_info' => '',
			'sr_feuser_register' => '',
			'mbi_products_categories' => '',
		),
	),
	'suggests' => array(
	),
);

?>