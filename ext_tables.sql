#
# Table structure for table 'tt_products'
#
CREATE TABLE tt_products (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	subtitle mediumtext NOT NULL,
	prod_uid int(11) DEFAULT '0' NOT NULL,
	accessory_uid int(11) DEFAULT '0' NOT NULL,
	related_uid int(11) DEFAULT '0' NOT NULL,
	dam_uid int(11) DEFAULT '0' NOT NULL,
	itemnumber varchar(40) DEFAULT '' NOT NULL,
	ean varchar(48) DEFAULT '' NOT NULL,
	directcost decimal(19,2) DEFAULT '0.00' NOT NULL,
	price decimal(19,2) DEFAULT '0.00' NOT NULL,
	price2 decimal(19,2) DEFAULT '0.00' NOT NULL,
	creditpoints int(11) DEFAULT '0' NOT NULL,
	graduated_price_uid int(11) DEFAULT '0' NOT NULL,
	note text NOT NULL,
	note2 text NOT NULL,
	note_uid int(11) DEFAULT '0' NOT NULL,
	text_uid int(11) DEFAULT '0' NOT NULL,
	unit varchar(20) DEFAULT '' NOT NULL,
	unit_factor varchar(6) DEFAULT '' NOT NULL,
	image text NOT NULL,
	datasheet text NOT NULL,
	www varchar(160) DEFAULT '' NOT NULL,
	category int(11) unsigned DEFAULT '0' NOT NULL,
	address int(11) unsigned DEFAULT '0' NOT NULL,
	inStock int(11) DEFAULT '1' NOT NULL,
	tax decimal(19,2) DEFAULT '0.00' NOT NULL,
	weight decimal(19,6) DEFAULT '0.000000' NOT NULL,
	usebydate int(11) unsigned DEFAULT '0' NOT NULL,
	bulkily int(11) DEFAULT '0' NOT NULL,
	offer int(11) DEFAULT '0' NOT NULL,
	highlight int(11) DEFAULT '0' NOT NULL,
	color mediumtext NOT NULL,
	color2 mediumtext NOT NULL,
	color3 mediumtext NOT NULL,
	size mediumtext NOT NULL,
	size2 mediumtext NOT NULL,
	size3 mediumtext NOT NULL,
	description mediumtext NOT NULL,
	gradings mediumtext NOT NULL,
	material mediumtext NOT NULL,
	quality mediumtext NOT NULL,
	additional_type varchar(36) DEFAULT '' NOT NULL,
	additional mediumtext NOT NULL,
	special_preparation int(11) DEFAULT '0' NOT NULL,
	shipping decimal(19,2) DEFAULT '0.00' NOT NULL,
	shipping2 decimal(19,2) DEFAULT '0.00' NOT NULL,
	handling decimal(19,2) DEFAULT '0.00' NOT NULL,
	delivery int(11) DEFAULT '0' NOT NULL,
	sellstarttime int(11) unsigned DEFAULT '0' NOT NULL,
	sellendtime int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_language'
#
CREATE TABLE tt_products_language (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	subtitle mediumtext NOT NULL,
	itemnumber varchar(40) DEFAULT '' NOT NULL,
	prod_uid int(11) DEFAULT '0' NOT NULL,
	text_uid int(11) DEFAULT '0' NOT NULL,
	note text NOT NULL,
	note2 text NOT NULL,
	unit varchar(20) DEFAULT '' NOT NULL,
	image text NOT NULL,
	datasheet text NOT NULL,
	www varchar(160) DEFAULT '' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_related_products_products_mm'
#
#
CREATE TABLE tt_products_related_products_products_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
);


#
# Table structure for table 'tt_products_accessory_products_products_mm'
#
#
CREATE TABLE tt_products_accessory_products_products_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
);


#
# Table structure for table 'tt_products_products_dam_mm'
#
#
CREATE TABLE tt_products_products_dam_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
);


#
# Table structure for table 'tt_products_products_note_pages_mm'
#
#
CREATE TABLE tt_products_products_note_pages_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
);


#
# Table structure for table 'tt_products_cat'
#
CREATE TABLE tt_products_cat (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	subtitle mediumtext NOT NULL,
	note text NOT NULL,
	note2 text NOT NULL,
	image text NOT NULL,
	email_uid int(11) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_cat_language'
#
CREATE TABLE tt_products_cat_language (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	subtitle mediumtext NOT NULL,
	note text NOT NULL,
	note2 text NOT NULL,
	cat_uid int(11) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_articles'
#
CREATE TABLE tt_products_articles (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	subtitle varchar(80) DEFAULT '' NOT NULL,
	itemnumber varchar(40) DEFAULT '' NOT NULL,
	price decimal(19,2) DEFAULT '0.00' NOT NULL,
	price2 decimal(19,2) DEFAULT '0.00' NOT NULL,
	note text NOT NULL,
	note2 text NOT NULL,
	image text NOT NULL,
	inStock int(11) DEFAULT '1' NOT NULL,
	weight decimal(19,6) DEFAULT '0.000000' NOT NULL,
	color varchar(60) DEFAULT '' NOT NULL,
	color2 varchar(60) DEFAULT '' NOT NULL,
	color3 varchar(60) DEFAULT '' NOT NULL,
	size varchar(60) DEFAULT '' NOT NULL,
	size2 varchar(60) DEFAULT '' NOT NULL,
	size3 varchar(60) DEFAULT '' NOT NULL,
	description varchar(60) DEFAULT '' NOT NULL,
	gradings varchar(60) DEFAULT '' NOT NULL,
	material varchar(60) DEFAULT '' NOT NULL,
	quality varchar(60) DEFAULT '' NOT NULL,
	uid_product int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_articles_language'
#
CREATE TABLE tt_products_articles_language (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	subtitle varchar(80) DEFAULT '' NOT NULL,
	article_uid int(11) DEFAULT '0' NOT NULL,
	note text NOT NULL,
	note2 text NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_gifts'
#
CREATE TABLE tt_products_gifts (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,

	ordernumber int(11) DEFAULT '0' NOT NULL,
	personname varchar(80) DEFAULT '' NOT NULL,
	personemail varchar(80) DEFAULT '' NOT NULL,
	deliveryname varchar(80) DEFAULT '' NOT NULL,
	deliveryemail varchar(80) DEFAULT '' NOT NULL,
	note text NOT NULL,
	amount decimal(19,2) DEFAULT '0.00' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_gifts_articles_mm'
#
#
CREATE TABLE tt_products_gifts_articles_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	count int(11) DEFAULT '0' NOT NULL,
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
);


#
# Table structure for table 'tt_products_emails'
#
CREATE TABLE tt_products_emails (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	name varchar(80) DEFAULT '' NOT NULL,
	email varchar(80) DEFAULT '' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_texts'
#
CREATE TABLE tt_products_texts (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	marker varchar(30) DEFAULT '' NOT NULL,
	note text NOT NULL,
	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable varchar(30) DEFAULT '' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_texts_language'
#
CREATE TABLE tt_products_texts_language (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	text_uid int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	note text NOT NULL,
	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable varchar(30) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_products_mm_graduated_price'
#
CREATE TABLE tt_products_mm_graduated_price (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	product_uid int(11) DEFAULT '0' NOT NULL,
	graduated_price_uid int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(30) DEFAULT '' NOT NULL,
	productsort int(10) DEFAULT '0' NOT NULL,
	graduatedsort int(10) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY product_uid (product_uid),
	KEY graduated_price_uid (graduated_price_uid),
	KEY parent (pid)
);


#
# Table structure for table 'tt_products_graduated_price'
#
CREATE TABLE tt_products_graduated_price (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	formula text NOT NULL,
	startamount decimal(19,2) DEFAULT '0.00' NOT NULL,
	note text NOT NULL,
	items int(11) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'sys_products_accounts'
#
CREATE TABLE sys_products_accounts (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	owner_name varchar(255) DEFAULT '' NOT NULL,
	ac_number varchar(255) DEFAULT '' NOT NULL,
#  cc_number_hash1 varchar(255) DEFAULT '' NOT NULL,
#  cc_number_hash2 varchar(255) DEFAULT '' NOT NULL,
	bic varchar(11) DEFAULT '' NOT NULL,
#  address_ok char(1) DEFAULT '' NOT NULL,
#  test char(1) DEFAULT '' NOT NULL,
#  auth_code varchar(16) DEFAULT '' NOT NULL,
#  bin int(6) unsigned DEFAULT '0' NOT NULL,
#  fraud tinyint(1) unsigned DEFAULT '0' NOT NULL,
#  sequence int(6) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'sys_products_cards'
#
CREATE TABLE sys_products_cards (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
#	order_uid int(11) unsigned DEFAULT '0' NOT NULL,
#  session_id varchar(30) DEFAULT '' NOT NULL,
#  amount_num int(10) DEFAULT '0' NOT NULL,
#  response_code char(3) DEFAULT '' NOT NULL,
	owner_name varchar(255) DEFAULT '' NOT NULL,
	cc_number varchar(255) DEFAULT '' NOT NULL,
#  cc_number_hash1 varchar(255) DEFAULT '' NOT NULL,
#  cc_number_hash2 varchar(255) DEFAULT '' NOT NULL,
	cc_type varchar(20) DEFAULT '' NOT NULL,
	cvv2 int(11) unsigned DEFAULT '0' NOT NULL,
#  address_ok char(1) DEFAULT '' NOT NULL,
#  test char(1) DEFAULT '' NOT NULL,
#  auth_code varchar(16) DEFAULT '' NOT NULL,
#  bin int(6) unsigned DEFAULT '0' NOT NULL,
#  fraud tinyint(1) unsigned DEFAULT '0' NOT NULL,
#  sequence int(6) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'sys_products_orders'
#
CREATE TABLE sys_products_orders (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	feusers_uid int(11) DEFAULT '0' NOT NULL,
	name varchar(80) DEFAULT '' NOT NULL,
	first_name varchar(50) DEFAULT '' NOT NULL,
	last_name varchar(50) DEFAULT '' NOT NULL,
	salutation int(11) DEFAULT '0' NOT NULL,
# company varchar(80) DEFAULT '' NOT NULL,
# vat_id varchar(20) DEFAULT '' NOT NULL,
	address tinytext NOT NULL,
	zip varchar(20) DEFAULT '' NOT NULL,
	city varchar(50) DEFAULT '' NOT NULL,
	country varchar(60) DEFAULT '' NOT NULL,
#	country_code char(3) DEFAULT '' NOT NULL,
	telephone varchar(20) DEFAULT '' NOT NULL,
	email varchar(80) DEFAULT '' NOT NULL,
	fax varchar(20) DEFAULT '' NOT NULL,
	business_partner int(11) DEFAULT '0' NOT NULL,
	organisation_form varchar(2) DEFAULT 'U' NOT NULL,
	payment varchar(80) DEFAULT '' NOT NULL,
	shipping varchar(80) DEFAULT '' NOT NULL,
	amount decimal(19,2) DEFAULT '0.00' NOT NULL,
	email_notify tinyint(4) unsigned DEFAULT '0' NOT NULL,
	tracking_code varchar(64) DEFAULT '' NOT NULL,
	status tinyint(4) unsigned DEFAULT '0' NOT NULL,
	status_log blob NOT NULL,
	orderData mediumblob NOT NULL,
#  session_id varchar(30) DEFAULT '' NOT NULL,
#  amount_num int(10) unsigned DEFAULT '0' NOT NULL,
	creditpoints decimal(10,0) default '0' NOT NULL,
	creditpoints_spended decimal(10,0) default '0' NOT NULL,
	creditpoints_saved decimal(10,0) default '0' NOT NULL,
	creditpoints_gifts decimal(10,0) default '0' NOT NULL,
	agb tinyint(1) DEFAULT '0' NOT NULL,
	desired_date varchar(30) DEFAULT '' NOT NULL,
	client_ip varchar(15) DEFAULT '' NOT NULL,
	note text NOT NULL,
	giftservice text NOT NULL,
	cc_uid int(11) unsigned DEFAULT '0' NOT NULL,
	ac_uid int(11) unsigned DEFAULT '0' NOT NULL,
	foundby int(11) DEFAULT '0' NOT NULL,
	giftcode varchar(80) DEFAULT '' NOT NULL,
	date_of_birth int(11) DEFAULT '0' NOT NULL,
	radio1 int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY tracking (tracking_code),
	KEY status (status)
);


#
# Table structure for table 'sys_products_orders_mm_tt_products'
#
CREATE TABLE sys_products_orders_mm_tt_products (
	sys_products_orders_uid int(11) unsigned DEFAULT '0' NOT NULL,
	sys_products_orders_qty int(11) unsigned DEFAULT '0' NOT NULL,
	tt_products_uid int(11) unsigned DEFAULT '0' NOT NULL,
	tt_products_articles_uid int(11) unsigned DEFAULT '0' NOT NULL,
	KEY tt_products_uid (tt_products_uid),
	KEY tt_products_articles_uid (tt_products_articles_uid),
	KEY sys_products_orders_uid (sys_products_orders_uid)
);


#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tt_products_memoItems tinytext NOT NULL,
	tt_products_discount decimal(19,2) DEFAULT '0.00' NOT NULL,
	tt_products_creditpoints decimal(10,0) DEFAULT '0' NOT NULL,
	tt_products_vouchercode varchar(50) DEFAULT '',
	tt_products_vat varchar(15) DEFAULT '' NOT NULL,
	tt_products_business_partner int(11) DEFAULT '0' NOT NULL,
	tt_products_organisation_form varchar(2) DEFAULT 'U' NOT NULL
);


#
# Table structure for table 'pages_language_overlay'
#
CREATE TABLE pages_language_overlay (
	sorting int(11) DEFAULT '0' NOT NULL,
);



### cache tables needed only for TYPO3 4.3 - 4.5

#
# TABLE structure FOR TABLE 'tt_products_cache'
#
CREATE TABLE tt_products_cache (
    id int(11) unsigned NOT NULL auto_increment,
    identifier varchar(250) DEFAULT '' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    content mediumblob,
    lifetime int(11) unsigned DEFAULT '0' NOT NULL,
    PRIMARY KEY (id),
    KEY cache_id (identifier)
) ENGINE=InnoDB;


#
# TABLE structure FOR TABLE 'tt_products_cache_tags'
#
CREATE TABLE tt_products_cache_tags (
    id int(11) unsigned NOT NULL auto_increment,
    identifier varchar(250) DEFAULT '' NOT NULL,
    tag varchar(250) DEFAULT '' NOT NULL,
    PRIMARY KEY (id),
    KEY cache_id (identifier),
    KEY cache_tag (tag)
) ENGINE=InnoDB;


