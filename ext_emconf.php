<?php

########################################################################
# Extension Manager/Repository config file for ext: "tt_products"
#
# Auto generated 10-02-2006 19:20
#
# Manual updates:
# Only the data in the array - anything else is removed by next write
########################################################################

$EM_CONF[$_EXTKEY] = Array (
	'title' => 'Shop System',
	'description' => 'Shop system in multiple languages with photo gallery, articles, order tracking, bill creation, creditpoint and voucher system, gift certificates, confirmation emails and search facility. Install the \'Table Library\' table v0.0.9 and the fh_library v0.0.8 extensions before you make an update!',
	'category' => 'plugin',
	'shy' => 0,
	'dependencies' => 'cms,table,fh_library',
	'conflicts' => 'zk_products,mkl_products,ast_rteproducts,onet_ttproducts_rte,shopsort,c3bi_cookie_at_login',
	'priority' => '',
	'loadOrder' => '',
	'TYPO3_version' => '3.7.1-4.0.20',
	'PHP_version' => '4.2.3-',
	'module' => '',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_ttproducts/datasheet,fileadmin/data/bill,fileadmin/data/delivery',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@fholzinger.com',
	'author_company' => 'Freelancer',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'private' => 0,
	'download_password' => '',
	'version' => '2.4.6',	// Don't modify this! Managed automatically during upload to repository.
	'_md5_values_when_last_written' => 'a:89:{s:9:"ChangeLog";s:4:"e6bf";s:31:"class.tx_ttproducts_wizicon.php";s:4:"51ef";s:12:"ext_icon.gif";s:4:"eb61";s:17:"ext_localconf.php";s:4:"4ec1";s:14:"ext_tables.php";s:4:"695b";s:14:"ext_tables.sql";s:4:"6fbe";s:28:"ext_typoscript_constants.txt";s:4:"081a";s:24:"ext_typoscript_setup.txt";s:4:"8f6d";s:19:"flexform_ds_pi1.xml";s:4:"0e64";s:13:"locallang.xml";s:4:"5fb2";s:24:"locallang_csh_ttprod.php";s:4:"2c56";s:25:"locallang_csh_ttproda.php";s:4:"0b59";s:25:"locallang_csh_ttprodc.php";s:4:"b042";s:25:"locallang_csh_ttprode.php";s:4:"392b";s:25:"locallang_csh_ttprodo.php";s:4:"6930";s:16:"locallang_db.xml";s:4:"f2d7";s:7:"tca.php";s:4:"58c4";s:14:"doc/manual.sxw";s:4:"5b1d";s:39:"lib/class.tx_ttproducts_article_div.php";s:4:"76dd";s:37:"lib/class.tx_ttproducts_attribute.php";s:4:"dfa2";s:34:"lib/class.tx_ttproducts_basket.php";s:4:"38e8";s:39:"lib/class.tx_ttproducts_basket_view.php";s:4:"5ca6";s:40:"lib/class.tx_ttproducts_billdelivery.php";s:4:"975a";s:36:"lib/class.tx_ttproducts_category.php";s:4:"ba00";s:35:"lib/class.tx_ttproducts_content.php";s:4:"3f2b";s:44:"lib/class.tx_ttproducts_creditpoints_div.php";s:4:"0b55";s:31:"lib/class.tx_ttproducts_csv.php";s:4:"f4b7";s:41:"lib/class.tx_ttproducts_currency_view.php";s:4:"a1bf";s:31:"lib/class.tx_ttproducts_div.php";s:4:"097f";s:33:"lib/class.tx_ttproducts_email.php";s:4:"eb65";s:37:"lib/class.tx_ttproducts_email_div.php";s:4:"5d21";s:40:"lib/class.tx_ttproducts_finalize_div.php";s:4:"46cd";s:37:"lib/class.tx_ttproducts_gifts_div.php";s:4:"0cd1";s:37:"lib/class.tx_ttproducts_list_view.php";s:4:"d928";s:34:"lib/class.tx_ttproducts_marker.php";s:4:"4a07";s:37:"lib/class.tx_ttproducts_memo_view.php";s:4:"202d";s:33:"lib/class.tx_ttproducts_order.php";s:4:"1e70";s:38:"lib/class.tx_ttproducts_order_view.php";s:4:"b81e";s:32:"lib/class.tx_ttproducts_page.php";s:4:"f8d5";s:43:"lib/class.tx_ttproducts_paymentshipping.php";s:4:"4720";s:33:"lib/class.tx_ttproducts_price.php";s:4:"327f";s:37:"lib/class.tx_ttproducts_pricecalc.php";s:4:"a2a1";s:35:"lib/class.tx_ttproducts_product.php";s:4:"ec2e";s:39:"lib/class.tx_ttproducts_single_view.php";s:4:"5a37";s:36:"lib/class.tx_ttproducts_tracking.php";s:4:"4aae";s:36:"pi1/class.tx_ttproducts_htmlmail.php";s:4:"5b6b";s:31:"pi1/class.tx_ttproducts_pi1.php";s:4:"d1d1";s:13:"pi1/clear.gif";s:4:"cc11";s:17:"pi1/locallang.xml";s:4:"5756";s:20:"pi1/payment_DIBS.php";s:4:"7929";s:32:"pi1/products_comp_calcScript.inc";s:4:"3f75";s:24:"pi1/static/editorcfg.txt";s:4:"4dd7";s:34:"pi1/static/old_style/constants.txt";s:4:"a932";s:30:"pi1/static/old_style/setup.txt";s:4:"686f";s:23:"res/icons/be/ce_wiz.gif";s:4:"a6c1";s:28:"res/icons/be/productlist.gif";s:4:"a6c1";s:28:"res/icons/fe/minibasket1.gif";s:4:"a960";s:35:"res/icons/fe/ttproducts_help_en.png";s:4:"5326";s:39:"res/icons/table/sys_products_orders.gif";s:4:"9d4e";s:31:"res/icons/table/tt_products.gif";s:4:"1ebd";s:40:"res/icons/table/tt_products_articles.gif";s:4:"1ebd";s:35:"res/icons/table/tt_products_cat.gif";s:4:"f852";s:44:"res/icons/table/tt_products_cat_language.gif";s:4:"d4fe";s:38:"res/icons/table/tt_products_emails.gif";s:4:"1ebd";s:40:"res/icons/table/tt_products_language.gif";s:4:"9d4e";s:16:"template/agb.txt";s:4:"5a56";s:38:"template/example_template_bill_de.tmpl";s:4:"1c38";s:35:"template/payment_DIBS_template.tmpl";s:4:"4684";s:38:"template/payment_DIBS_template_uk.tmpl";s:4:"96f9";s:27:"template/products_help.tmpl";s:4:"d2d6";s:31:"template/products_template.tmpl";s:4:"ad42";s:34:"template/products_template_dk.tmpl";s:4:"7665";s:34:"template/products_template_fr.tmpl";s:4:"a233";s:40:"template/products_template_htmlmail.tmpl";s:4:"aa8a";s:34:"template/products_template_se.tmpl";s:4:"7161";s:39:"template/meerwijn/detail_cadeaubon.tmpl";s:4:"c263";s:40:"template/meerwijn/detail_geschenken.tmpl";s:4:"b695";s:40:"template/meerwijn/detail_kurkenshop.tmpl";s:4:"0fad";s:38:"template/meerwijn/detail_shopabox.tmpl";s:4:"21a3";s:36:"template/meerwijn/detail_wijnen.tmpl";s:4:"63be";s:37:"template/meerwijn/product_detail.tmpl";s:4:"9e4a";s:45:"template/meerwijn/product_proefpakketten.tmpl";s:4:"9afd";s:32:"template/meerwijn/producten.tmpl";s:4:"95a0";s:33:"template/meerwijn/shop-a-box.tmpl";s:4:"5606";s:40:"template/meerwijn/totaal_geschenken.tmpl";s:4:"15ca";s:40:"template/meerwijn/totaal_kurkenshop.tmpl";s:4:"1306";s:38:"template/meerwijn/totaal_shopabox.tmpl";s:4:"f87b";s:36:"template/meerwijn/totaal_wijnen.tmpl";s:4:"5ee1";s:34:"template/meerwijn/winkelwagen.tmpl";s:4:"1ac5";}',
);

?>
