<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tt_products".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Shop System',
	'description' => 'New versions at ttproducts.de. Documented in E-Book "Der TYPO3-Webshop" - Shop with listing in multiple languages, with order tracking, photo gallery, DAM, product variants, credit card payment and bank accounts, bill, creditpoint, voucher system and gift certificates. Latest updates at ttproducts.de.',
	'category' => 'plugin',
	'shy' => 0,
	'dependencies' => 'cms,table,div2007,func_wizards',
	'conflicts' => 'su_products,zk_products,mkl_products,ast_rteproducts,onet_ttproducts_rte,shopsort,c3bi_cookie_at_login',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_ttproducts/datasheet,uploads/tx_ttproducts/rte,fileadmin/data/bill,fileadmin/data/delivery,fileadmin/img',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@ttproducts.de',
	'author_company' => 'jambage.com',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '2.8.4',
	'_md5_values_when_last_written' => 'a:161:{s:9:"ChangeLog";s:4:"4a0c";s:31:"class.tx_ttproducts_wizicon.php";s:4:"8e88";s:16:"contributors.txt";s:4:"83ce";s:21:"ext_conf_template.txt";s:4:"bf8c";s:12:"ext_icon.gif";s:4:"eb61";s:17:"ext_localconf.php";s:4:"5686";s:14:"ext_tables.php";s:4:"5d3b";s:14:"ext_tables.sql";s:4:"3087";s:19:"flexform_ds_pi1.xml";s:4:"20f0";s:13:"locallang.xml";s:4:"ea36";s:24:"locallang_csh_ttprod.php";s:4:"a2c6";s:25:"locallang_csh_ttproda.php";s:4:"026a";s:26:"locallang_csh_ttprodac.php";s:4:"c02a";s:25:"locallang_csh_ttprodc.php";s:4:"cfa4";s:26:"locallang_csh_ttprodca.php";s:4:"aa36";s:25:"locallang_csh_ttprode.php";s:4:"013d";s:25:"locallang_csh_ttprodo.php";s:4:"12e9";s:16:"locallang_db.xml";s:4:"5d6c";s:7:"tca.php";s:4:"8ac6";s:14:"doc/manual.sxw";s:4:"24ea";s:40:"lib/class.tx_ttproducts_billdelivery.php";s:4:"5e1e";s:34:"lib/class.tx_ttproducts_config.php";s:4:"93b6";s:44:"lib/class.tx_ttproducts_creditpoints_div.php";s:4:"9544";s:31:"lib/class.tx_ttproducts_csv.php";s:4:"7e90";s:33:"lib/class.tx_ttproducts_email.php";s:4:"5766";s:37:"lib/class.tx_ttproducts_email_div.php";s:4:"ff9e";s:36:"lib/class.tx_ttproducts_form_div.php";s:4:"562f";s:37:"lib/class.tx_ttproducts_gifts_div.php";s:4:"e8e7";s:33:"lib/class.tx_ttproducts_image.php";s:4:"fc39";s:32:"lib/class.tx_ttproducts_info.php";s:4:"dde3";s:38:"lib/class.tx_ttproducts_order_view.php";s:4:"c844";s:38:"lib/class.tx_ttproducts_paymentlib.php";s:4:"7ab4";s:43:"lib/class.tx_ttproducts_paymentshipping.php";s:4:"1e9a";s:33:"lib/class.tx_ttproducts_price.php";s:4:"9ee6";s:37:"lib/class.tx_ttproducts_pricecalc.php";s:4:"fea0";s:36:"lib/class.tx_ttproducts_tracking.php";s:4:"cd46";s:35:"lib/class.tx_ttproducts_variant.php";s:4:"0df4";s:31:"pi1/class.tx_ttproducts_pi1.php";s:4:"e1ec";s:13:"pi1/clear.gif";s:4:"cc11";s:17:"pi1/locallang.xml";s:4:"882a";s:20:"pi1/payment_DIBS.php";s:4:"6ebb";s:32:"pi1/products_comp_calcScript.inc";s:4:"a85e";s:23:"res/icons/be/ce_wiz.gif";s:4:"a6c1";s:28:"res/icons/be/productlist.gif";s:4:"a6c1";s:24:"res/icons/fe/AddItem.gif";s:4:"287d";s:40:"res/icons/fe/Cart Icon-AddRemoveItem.psd";s:4:"857a";s:34:"res/icons/fe/Cart-Icon-AddItem.gif";s:4:"e76c";s:37:"res/icons/fe/Cart-Icon-RemoveItem.gif";s:4:"b9cc";s:26:"res/icons/fe/Cart-Icon.gif";s:4:"988a";s:27:"res/icons/fe/RemoveItem.gif";s:4:"e28f";s:24:"res/icons/fe/addmemo.png";s:4:"c76f";s:21:"res/icons/fe/amex.gif";s:4:"22e1";s:32:"res/icons/fe/availableDemand.gif";s:4:"bf3a";s:35:"res/icons/fe/availableImmediate.gif";s:4:"7f1d";s:31:"res/icons/fe/availableShort.gif";s:4:"1737";s:23:"res/icons/fe/basket.gif";s:4:"ca3d";s:24:"res/icons/fe/delmemo.png";s:4:"b1da";s:25:"res/icons/fe/discover.gif";s:4:"91c4";s:27:"res/icons/fe/mastercard.gif";s:4:"2fe1";s:28:"res/icons/fe/meerwijn.tar.gz";s:4:"c5f9";s:28:"res/icons/fe/minibasket1.gif";s:4:"a960";s:35:"res/icons/fe/ttproducts_help_en.png";s:4:"5326";s:21:"res/icons/fe/visa.gif";s:4:"28c6";s:27:"res/icons/fe/meerwijn/+.gif";s:4:"36a3";s:27:"res/icons/fe/meerwijn/_.gif";s:4:"209f";s:32:"res/icons/fe/meerwijn/basket.gif";s:4:"508c";s:41:"res/icons/fe/meerwijn/help-icon-hover.gif";s:4:"05b8";s:35:"res/icons/fe/meerwijn/help-icon.gif";s:4:"8f01";s:41:"res/icons/fe/meerwijn/info-icon-hover.gif";s:4:"fdbf";s:35:"res/icons/fe/meerwijn/info-icon.gif";s:4:"271c";s:30:"res/icons/fe/meerwijn/kurk.gif";s:4:"c7fb";s:30:"res/icons/fe/meerwijn/lock.gif";s:4:"80ca";s:37:"res/icons/fe/meerwijn/recycle-bin.gif";s:4:"b8a7";s:32:"res/icons/fe/meerwijn/unlock.gif";s:4:"12c9";s:27:"res/icons/fe/meerwijn/x.gif";s:4:"1ccf";s:41:"res/icons/table/sys_products_accounts.gif";s:4:"cab5";s:38:"res/icons/table/sys_products_cards.gif";s:4:"f9d0";s:39:"res/icons/table/sys_products_orders.gif";s:4:"b279";s:31:"res/icons/table/tt_products.gif";s:4:"1ebd";s:40:"res/icons/table/tt_products_articles.gif";s:4:"e779";s:49:"res/icons/table/tt_products_articles_language.gif";s:4:"20e5";s:35:"res/icons/table/tt_products_cat.gif";s:4:"f852";s:44:"res/icons/table/tt_products_cat_language.gif";s:4:"d4fe";s:38:"res/icons/table/tt_products_emails.gif";s:4:"7972";s:40:"res/icons/table/tt_products_language.gif";s:4:"19e8";s:40:"view/class.tx_ttproducts_basket_view.php";s:4:"3581";s:44:"view/class.tx_ttproducts_basketitem_view.php";s:4:"6425";s:41:"view/class.tx_ttproducts_catlist_view.php";s:4:"7ee2";s:42:"view/class.tx_ttproducts_currency_view.php";s:4:"6cfd";s:38:"view/class.tx_ttproducts_list_view.php";s:4:"86c6";s:38:"view/class.tx_ttproducts_memo_view.php";s:4:"ffa6";s:39:"view/class.tx_ttproducts_order_view.php";s:4:"f244";s:43:"view/class.tx_ttproducts_selectcat_view.php";s:4:"01af";s:40:"view/class.tx_ttproducts_single_view.php";s:4:"b781";s:45:"control/class.tx_ttproducts_activity_base.php";s:4:"98ec";s:49:"control/class.tx_ttproducts_activity_finalize.php";s:4:"4cde";s:39:"control/class.tx_ttproducts_control.php";s:4:"18ff";s:42:"control/class.tx_ttproducts_javascript.php";s:4:"1892";s:33:"hooks/class.tx_ttproducts_cms.php";s:4:"9c77";s:37:"model/class.tx_ttproducts_account.php";s:4:"0203";s:37:"model/class.tx_ttproducts_address.php";s:4:"0827";s:37:"model/class.tx_ttproducts_article.php";s:4:"8f10";s:42:"model/class.tx_ttproducts_article_base.php";s:4:"bd38";s:39:"model/class.tx_ttproducts_attribute.php";s:4:"ac46";s:36:"model/class.tx_ttproducts_basket.php";s:4:"9685";s:34:"model/class.tx_ttproducts_card.php";s:4:"ebb4";s:38:"model/class.tx_ttproducts_category.php";s:4:"bf7f";s:43:"model/class.tx_ttproducts_category_base.php";s:4:"1d60";s:37:"model/class.tx_ttproducts_content.php";s:4:"439d";s:37:"model/class.tx_ttproducts_country.php";s:4:"1008";s:33:"model/class.tx_ttproducts_dam.php";s:4:"67e1";s:36:"model/class.tx_ttproducts_feuser.php";s:4:"cb31";s:35:"model/class.tx_ttproducts_order.php";s:4:"062c";s:34:"model/class.tx_ttproducts_page.php";s:4:"4386";s:37:"model/class.tx_ttproducts_product.php";s:4:"81e7";s:16:"template/agb.txt";s:4:"d76f";s:38:"template/example_template_bill_de.tmpl";s:4:"c422";s:35:"template/payment_DIBS_template.tmpl";s:4:"f1d8";s:38:"template/payment_DIBS_template_uk.tmpl";s:4:"9f48";s:24:"template/paymentlib.tmpl";s:4:"ca13";s:29:"template/products_css_de.html";s:4:"049e";s:29:"template/products_css_en.html";s:4:"babb";s:38:"template/products_css_variants_de.html";s:4:"b8c9";s:27:"template/products_help.tmpl";s:4:"7486";s:31:"template/products_template.tmpl";s:4:"6ad3";s:34:"template/products_template_dk.tmpl";s:4:"1838";s:34:"template/products_template_fi.tmpl";s:4:"51e9";s:34:"template/products_template_fr.tmpl";s:4:"d77a";s:40:"template/products_template_htmlmail.tmpl";s:4:"aa8a";s:34:"template/products_template_it.html";s:4:"30e6";s:34:"template/products_template_se.tmpl";s:4:"3104";s:35:"template/shop_template-css-eng.html";s:4:"41bb";s:31:"template/shop_template-css.html";s:4:"d3f7";s:24:"template/tt_products.css";s:4:"54ea";s:37:"template/tt_products_example_css.html";s:4:"92cd";s:39:"template/meerwijn/detail_cadeaubon.tmpl";s:4:"c263";s:40:"template/meerwijn/detail_geschenken.tmpl";s:4:"b695";s:40:"template/meerwijn/detail_kurkenshop.tmpl";s:4:"0fad";s:38:"template/meerwijn/detail_shopabox.tmpl";s:4:"21a3";s:36:"template/meerwijn/detail_wijnen.tmpl";s:4:"63be";s:37:"template/meerwijn/product_detail.tmpl";s:4:"9e4a";s:45:"template/meerwijn/product_proefpakketten.tmpl";s:4:"9afd";s:32:"template/meerwijn/producten.tmpl";s:4:"103a";s:33:"template/meerwijn/shop-a-box.tmpl";s:4:"f580";s:40:"template/meerwijn/totaal_geschenken.tmpl";s:4:"15ca";s:40:"template/meerwijn/totaal_kurkenshop.tmpl";s:4:"1306";s:38:"template/meerwijn/totaal_shopabox.tmpl";s:4:"f87b";s:36:"template/meerwijn/totaal_wijnen.tmpl";s:4:"5ee1";s:31:"template/meerwijn/tracking.tmpl";s:4:"aadb";s:34:"template/meerwijn/winkelwagen.tmpl";s:4:"ff1b";s:35:"template/meerwijn/js/FormManager.js";s:4:"3ccc";s:48:"marker/class.tx_ttproducts_javascript_marker.php";s:4:"f96d";s:37:"marker/class.tx_ttproducts_marker.php";s:4:"f59c";s:20:"static/editorcfg.txt";s:4:"4dd7";s:31:"static/css_styled/constants.txt";s:4:"ebb5";s:27:"static/css_styled/setup.txt";s:4:"e77f";s:21:"static/test/setup.txt";s:4:"fa5c";s:30:"static/old_style/constants.txt";s:4:"cb67";s:26:"static/old_style/setup.txt";s:4:"93d5";s:38:"widgets/class.tx_ttproducts_latest.php";s:4:"2f92";s:18:"widgets/labels.xml";s:4:"35d9";}',
	'constraints' => array(
		'depends' => array(
			'div2007' => '1.5.1-0.0.0',
			'php' => '5.1.2-5.6.99',
			'table' => '0.3.0-0.0.0',
			'tsparser' => '',
			'typo3' => '4.5.0-7.7.99',
		),
		'conflicts' => array(
			'mkl_products' => '',
			'su_products' => '',
			'zk_products' => '',
			'ast_rteproducts' => '',
			'onet_ttproducts_rte' => '',
			'shopsort' => '',
			'c3bi_cookie_at_login' => '',
		),
		'suggests' => array(
			'func_wizards' => '',
		),
	),
);

?>