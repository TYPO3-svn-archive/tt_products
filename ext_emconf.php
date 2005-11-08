<?php

########################################################################
# Extension Manager/Repository config file for ext: "tt_products"
# 
# Auto generated 05-11-2005 17:22
# 
# Manual updates:
# Only the data in the array - anything else is removed by next write
########################################################################

$EM_CONF[$_EXTKEY] = Array (
	'title' => 'Shop system',
	'description' => 'Simple shop system with categories.',
	'category' => 'plugin',
	'shy' => 0,
	'dependencies' => 'cms,table,fh_library',
	'conflicts' => 'zk_products,mkl_products,ast_rteproducts,onet_ttproducts_rte,shopsort',
	'priority' => '',
	'TYPO3_version' => '3.7.1-3.8.1',
	'PHP_version' => '4.2.3-5.0.5',
	'module' => '',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_ttproducts/datasheet',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@fholzinger.com',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'private' => 0,
	'download_password' => '',
	'version' => '2.3.5',	// Don't modify this! Managed automatically during upload to repository.
	'_md5_values_when_last_written' => 'a:81:{s:9:"ChangeLog";s:4:"e33f";s:31:"class.tx_ttproducts_wizicon.php";s:4:"8694";s:12:"ext_icon.gif";s:4:"eb61";s:17:"ext_localconf.php";s:4:"f2f5";s:14:"ext_tables.php";s:4:"37fb";s:14:"ext_tables.sql";s:4:"b0ea";s:28:"ext_typoscript_constants.txt";s:4:"b76a";s:24:"ext_typoscript_setup.txt";s:4:"9b44";s:18:"flexform_ds_pi.xml";s:4:"070d";s:13:"locallang.php";s:4:"1c12";s:24:"locallang_csh_ttprod.php";s:4:"24a3";s:25:"locallang_csh_ttproda.php";s:4:"4847";s:25:"locallang_csh_ttprodc.php";s:4:"4d89";s:25:"locallang_csh_ttprode.php";s:4:"b948";s:17:"locallang_tca.php";s:4:"477b";s:7:"tca.php";s:4:"be71";s:14:"doc/manual.sxw";s:4:"4f3c";s:39:"lib/class.tx_ttproducts_article_div.php";s:4:"3644";s:37:"lib/class.tx_ttproducts_attribute.php";s:4:"0701";s:34:"lib/class.tx_ttproducts_basket.php";s:4:"d8c2";s:44:"lib/class.tx_ttproducts_billdelivery_div.php";s:4:"bb1d";s:36:"lib/class.tx_ttproducts_category.php";s:4:"010e";s:35:"lib/class.tx_ttproducts_content.php";s:4:"d2d6";s:44:"lib/class.tx_ttproducts_creditpoints_div.php";s:4:"0ad1";s:31:"lib/class.tx_ttproducts_csv.php";s:4:"54d9";s:40:"lib/class.tx_ttproducts_currency_div.php";s:4:"69d1";s:30:"lib/class.tx_ttproducts_db.php";s:4:"446a";s:31:"lib/class.tx_ttproducts_div.php";s:4:"06bb";s:33:"lib/class.tx_ttproducts_email.php";s:4:"f61f";s:37:"lib/class.tx_ttproducts_email_div.php";s:4:"d47c";s:40:"lib/class.tx_ttproducts_finalize_div.php";s:4:"016d";s:37:"lib/class.tx_ttproducts_gifts_div.php";s:4:"9748";s:37:"lib/class.tx_ttproducts_list_view.php";s:4:"5111";s:36:"lib/class.tx_ttproducts_memo_div.php";s:4:"f137";s:37:"lib/class.tx_ttproducts_order_div.php";s:4:"6c13";s:32:"lib/class.tx_ttproducts_page.php";s:4:"f203";s:47:"lib/class.tx_ttproducts_paymentshipping_div.php";s:4:"f158";s:37:"lib/class.tx_ttproducts_price_div.php";s:4:"efca";s:41:"lib/class.tx_ttproducts_pricecalc_div.php";s:4:"4994";s:35:"lib/class.tx_ttproducts_product.php";s:4:"9ae1";s:39:"lib/class.tx_ttproducts_single_view.php";s:4:"cdce";s:40:"lib/class.tx_ttproducts_tracking_div.php";s:4:"968f";s:36:"lib/class.tx_ttproducts_view_div.php";s:4:"781f";s:26:"pi/class.tx_ttproducts.php";s:4:"e4ca";s:35:"pi/class.tx_ttproducts_htmlmail.php";s:4:"5154";s:12:"pi/clear.gif";s:4:"cc11";s:16:"pi/locallang.php";s:4:"07cf";s:19:"pi/payment_DIBS.php";s:4:"26a2";s:31:"pi/products_comp_calcScript.inc";s:4:"3f75";s:23:"pi/static/editorcfg.txt";s:4:"ee72";s:23:"res/icons/be/ce_wiz.gif";s:4:"a6c1";s:28:"res/icons/be/productlist.gif";s:4:"a6c1";s:35:"res/icons/fe/ttproducts_help_en.png";s:4:"5326";s:31:"res/icons/table/tt_products.gif";s:4:"1ebd";s:40:"res/icons/table/tt_products_articles.gif";s:4:"1ebd";s:35:"res/icons/table/tt_products_cat.gif";s:4:"f852";s:44:"res/icons/table/tt_products_cat_language.gif";s:4:"d4fe";s:38:"res/icons/table/tt_products_emails.gif";s:4:"1ebd";s:40:"res/icons/table/tt_products_language.gif";s:4:"9d4e";s:38:"template/example_template_bill_de.tmpl";s:4:"fdc8";s:35:"template/payment_DIBS_template.tmpl";s:4:"4684";s:38:"template/payment_DIBS_template_uk.tmpl";s:4:"96f9";s:27:"template/products_help.tmpl";s:4:"351f";s:31:"template/products_template.tmpl";s:4:"5e86";s:34:"template/products_template_dk.tmpl";s:4:"0d73";s:40:"template/products_template_htmlmail.tmpl";s:4:"aa8a";s:34:"template/products_template_se.tmpl";s:4:"bbf6";s:39:"template/meerwijn/detail_cadeaubon.tmpl";s:4:"c263";s:40:"template/meerwijn/detail_geschenken.tmpl";s:4:"b695";s:40:"template/meerwijn/detail_kurkenshop.tmpl";s:4:"0fad";s:38:"template/meerwijn/detail_shopabox.tmpl";s:4:"21a3";s:36:"template/meerwijn/detail_wijnen.tmpl";s:4:"63be";s:37:"template/meerwijn/product_detail.tmpl";s:4:"7b3c";s:45:"template/meerwijn/product_proefpakketten.tmpl";s:4:"c6c8";s:32:"template/meerwijn/producten.tmpl";s:4:"e2cb";s:33:"template/meerwijn/shop-a-box.tmpl";s:4:"81c3";s:40:"template/meerwijn/totaal_geschenken.tmpl";s:4:"41ec";s:40:"template/meerwijn/totaal_kurkenshop.tmpl";s:4:"5c51";s:38:"template/meerwijn/totaal_shopabox.tmpl";s:4:"8945";s:36:"template/meerwijn/totaal_wijnen.tmpl";s:4:"7625";s:34:"template/meerwijn/winkelwagen.tmpl";s:4:"d009";}',
);

?>