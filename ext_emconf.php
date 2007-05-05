<?php

########################################################################
# Extension Manager/Repository config file for ext: "tt_products"
#
# Auto generated 31-03-2007 21:26
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Shop System',
	'description' => 'Open Source Shop in multiple languages, photo gallery using DAM, product variants, payment gateways, bill, creditpoint, voucher system and gift certificates. Requires table v0.1.10 and fh_library v0.0.16! Tutorial at typo3.org/documentation',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '2.5.2',
	'dependencies' => 'cms,table,fh_library',
	'conflicts' => 'zk_products,mkl_products,ast_rteproducts,onet_ttproducts_rte,shopsort,c3bi_cookie_at_login',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'alpha',
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_ttproducts/datasheet,fileadmin/data/bill,fileadmin/data/delivery,fileadmin/img',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Franz Holzinger',
	'author_email' => 'kontakt@fholzinger.com',
	'author_company' => 'Freelancer',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'table' => '0.1.10-',
			'fh_library' => '0.0.16-',
			'php' => '4.2.3-0.0.0',
			'typo3' => '3.8.0-4.2',
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
			'mbi_products_categories' => '',
			'sr_feuser_register' => '',
			'static_info_tables' => '2.0.1',
			'tt_address' => '',
			'xajax' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:135:{s:9:"ChangeLog";s:4:"dacc";s:31:"class.tx_ttproducts_wizicon.php";s:4:"cf9d";s:16:"contributors.txt";s:4:"83ce";s:21:"ext_conf_template.txt";s:4:"52c1";s:12:"ext_icon.gif";s:4:"eb61";s:17:"ext_localconf.php";s:4:"78fc";s:14:"ext_tables.php";s:4:"be51";s:14:"ext_tables.sql";s:4:"48f9";s:19:"flexform_ds_pi1.xml";s:4:"e49e";s:13:"locallang.xml";s:4:"b56a";s:24:"locallang_csh_ttprod.php";s:4:"a2c6";s:25:"locallang_csh_ttproda.php";s:4:"026a";s:25:"locallang_csh_ttprodc.php";s:4:"cfa4";s:26:"locallang_csh_ttprodca.php";s:4:"aa36";s:25:"locallang_csh_ttprode.php";s:4:"013d";s:25:"locallang_csh_ttprodo.php";s:4:"12e9";s:16:"locallang_db.xml";s:4:"1517";s:7:"tca.php";s:4:"f08a";s:14:"doc/manual.sxw";s:4:"e414";s:40:"lib/class.tx_ttproducts_billdelivery.php";s:4:"b649";s:34:"lib/class.tx_ttproducts_config.php";s:4:"69c6";s:44:"lib/class.tx_ttproducts_creditpoints_div.php";s:4:"6df1";s:31:"lib/class.tx_ttproducts_csv.php";s:4:"c548";s:33:"lib/class.tx_ttproducts_email.php";s:4:"2f8d";s:37:"lib/class.tx_ttproducts_email_div.php";s:4:"9035";s:36:"lib/class.tx_ttproducts_form_div.php";s:4:"805c";s:37:"lib/class.tx_ttproducts_gifts_div.php";s:4:"49a9";s:33:"lib/class.tx_ttproducts_image.php";s:4:"b986";s:32:"lib/class.tx_ttproducts_info.php";s:4:"f186";s:38:"lib/class.tx_ttproducts_order_view.php";s:4:"81a3";s:38:"lib/class.tx_ttproducts_paymentlib.php";s:4:"0c6e";s:43:"lib/class.tx_ttproducts_paymentshipping.php";s:4:"1899";s:33:"lib/class.tx_ttproducts_price.php";s:4:"128d";s:37:"lib/class.tx_ttproducts_pricecalc.php";s:4:"d988";s:36:"lib/class.tx_ttproducts_tracking.php";s:4:"8a4a";s:35:"lib/class.tx_ttproducts_variant.php";s:4:"f694";s:31:"pi1/class.tx_ttproducts_pi1.php";s:4:"463a";s:13:"pi1/clear.gif";s:4:"cc11";s:17:"pi1/locallang.xml";s:4:"5af9";s:20:"pi1/payment_DIBS.php";s:4:"eb94";s:32:"pi1/products_comp_calcScript.inc";s:4:"218c";s:23:"res/icons/be/ce_wiz.gif";s:4:"a6c1";s:28:"res/icons/be/productlist.gif";s:4:"a6c1";s:24:"res/icons/fe/AddItem.gif";s:4:"287d";s:40:"res/icons/fe/Cart Icon-AddRemoveItem.psd";s:4:"857a";s:34:"res/icons/fe/Cart-Icon-AddItem.gif";s:4:"e76c";s:37:"res/icons/fe/Cart-Icon-RemoveItem.gif";s:4:"b9cc";s:26:"res/icons/fe/Cart-Icon.gif";s:4:"988a";s:27:"res/icons/fe/RemoveItem.gif";s:4:"e28f";s:24:"res/icons/fe/addmemo.png";s:4:"c76f";s:21:"res/icons/fe/amex.gif";s:4:"22e1";s:32:"res/icons/fe/availableDemand.gif";s:4:"bf3a";s:35:"res/icons/fe/availableImmediate.gif";s:4:"7f1d";s:31:"res/icons/fe/availableShort.gif";s:4:"1737";s:23:"res/icons/fe/basket.gif";s:4:"ca3d";s:24:"res/icons/fe/delmemo.png";s:4:"b1da";s:25:"res/icons/fe/discover.gif";s:4:"91c4";s:27:"res/icons/fe/mastercard.gif";s:4:"2fe1";s:28:"res/icons/fe/minibasket1.gif";s:4:"a960";s:35:"res/icons/fe/ttproducts_help_en.png";s:4:"5326";s:21:"res/icons/fe/visa.gif";s:4:"28c6";s:41:"res/icons/table/sys_products_accounts.gif";s:4:"cab5";s:38:"res/icons/table/sys_products_cards.gif";s:4:"f9d0";s:39:"res/icons/table/sys_products_orders.gif";s:4:"b279";s:31:"res/icons/table/tt_products.gif";s:4:"1ebd";s:40:"res/icons/table/tt_products_articles.gif";s:4:"365b";s:35:"res/icons/table/tt_products_cat.gif";s:4:"f852";s:44:"res/icons/table/tt_products_cat_language.gif";s:4:"d4fe";s:38:"res/icons/table/tt_products_emails.gif";s:4:"7972";s:40:"res/icons/table/tt_products_language.gif";s:4:"19e8";s:40:"view/class.tx_ttproducts_basket_view.php";s:4:"2fe2";s:44:"view/class.tx_ttproducts_basketitem_view.php";s:4:"bb48";s:41:"view/class.tx_ttproducts_catlist_view.php";s:4:"af6c";s:42:"view/class.tx_ttproducts_currency_view.php";s:4:"01d2";s:38:"view/class.tx_ttproducts_list_view.php";s:4:"9a5d";s:38:"view/class.tx_ttproducts_memo_view.php";s:4:"c94f";s:39:"view/class.tx_ttproducts_order_view.php";s:4:"9e02";s:43:"view/class.tx_ttproducts_selectcat_view.php";s:4:"baef";s:40:"view/class.tx_ttproducts_single_view.php";s:4:"aa5b";s:45:"control/class.tx_ttproducts_activity_base.php";s:4:"488b";s:49:"control/class.tx_ttproducts_activity_finalize.php";s:4:"3ee1";s:39:"control/class.tx_ttproducts_control.php";s:4:"eea4";s:42:"control/class.tx_ttproducts_javascript.php";s:4:"b82b";s:37:"model/class.tx_ttproducts_address.php";s:4:"ffa8";s:37:"model/class.tx_ttproducts_article.php";s:4:"9fc4";s:42:"model/class.tx_ttproducts_article_base.php";s:4:"e1b1";s:39:"model/class.tx_ttproducts_attribute.php";s:4:"c41d";s:36:"model/class.tx_ttproducts_basket.php";s:4:"fcb0";s:34:"model/class.tx_ttproducts_card.php";s:4:"b9b9";s:38:"model/class.tx_ttproducts_category.php";s:4:"9867";s:43:"model/class.tx_ttproducts_category_base.php";s:4:"02bb";s:37:"model/class.tx_ttproducts_content.php";s:4:"7d6f";s:37:"model/class.tx_ttproducts_country.php";s:4:"96d7";s:33:"model/class.tx_ttproducts_dam.php";s:4:"8bc4";s:36:"model/class.tx_ttproducts_feuser.php";s:4:"fab5";s:35:"model/class.tx_ttproducts_order.php";s:4:"c344";s:34:"model/class.tx_ttproducts_page.php";s:4:"345b";s:37:"model/class.tx_ttproducts_product.php";s:4:"a8be";s:16:"template/agb.txt";s:4:"5a56";s:38:"template/example_template_bill_de.tmpl";s:4:"d3ca";s:35:"template/payment_DIBS_template.tmpl";s:4:"f1d8";s:38:"template/payment_DIBS_template_uk.tmpl";s:4:"9f48";s:24:"template/paymentlib.tmpl";s:4:"ca13";s:29:"template/products_css_de.html";s:4:"049e";s:29:"template/products_css_en.html";s:4:"f16d";s:27:"template/products_help.tmpl";s:4:"ef96";s:31:"template/products_template.tmpl";s:4:"bbcd";s:34:"template/products_template_dk.tmpl";s:4:"d892";s:34:"template/products_template_fi.tmpl";s:4:"11a5";s:34:"template/products_template_fr.tmpl";s:4:"b134";s:40:"template/products_template_htmlmail.tmpl";s:4:"aa8a";s:34:"template/products_template_it.html";s:4:"462f";s:34:"template/products_template_se.tmpl";s:4:"07c9";s:39:"template/meerwijn/detail_cadeaubon.tmpl";s:4:"c263";s:40:"template/meerwijn/detail_geschenken.tmpl";s:4:"b695";s:40:"template/meerwijn/detail_kurkenshop.tmpl";s:4:"0fad";s:38:"template/meerwijn/detail_shopabox.tmpl";s:4:"21a3";s:36:"template/meerwijn/detail_wijnen.tmpl";s:4:"63be";s:37:"template/meerwijn/product_detail.tmpl";s:4:"9e4a";s:45:"template/meerwijn/product_proefpakketten.tmpl";s:4:"9afd";s:32:"template/meerwijn/producten.tmpl";s:4:"103a";s:33:"template/meerwijn/shop-a-box.tmpl";s:4:"f580";s:40:"template/meerwijn/totaal_geschenken.tmpl";s:4:"15ca";s:40:"template/meerwijn/totaal_kurkenshop.tmpl";s:4:"1306";s:38:"template/meerwijn/totaal_shopabox.tmpl";s:4:"f87b";s:36:"template/meerwijn/totaal_wijnen.tmpl";s:4:"5ee1";s:31:"template/meerwijn/tracking.tmpl";s:4:"aadb";s:34:"template/meerwijn/winkelwagen.tmpl";s:4:"ff1b";s:35:"template/meerwijn/js/FormManager.js";s:4:"3ccc";s:48:"marker/class.tx_ttproducts_javascript_marker.php";s:4:"cf53";s:37:"marker/class.tx_ttproducts_marker.php";s:4:"b1f1";s:20:"static/editorcfg.txt";s:4:"4dd7";s:21:"static/test/setup.txt";s:4:"fa5c";s:30:"static/old_style/constants.txt";s:4:"cebf";s:26:"static/old_style/setup.txt";s:4:"3a5f";}',
	'suggests' => array(
	),
);

?>