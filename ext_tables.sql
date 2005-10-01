#
# Table structure for table 'tt_products'
#
CREATE TABLE tt_products (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	subtitle tinytext NOT NULL,
	itemnumber varchar(40) DEFAULT '' NOT NULL,
	price varchar(20) DEFAULT '' NOT NULL,
	price2 varchar(20) DEFAULT '' NOT NULL,
	note text NOT NULL,
	unit varchar(20) DEFAULT '' NOT NULL,  
	unit_factor varchar(6) DEFAULT '' NOT NULL,   
	image tinyblob NOT NULL,
	datasheet tinyblob NOT NULL,
	www varchar(80) DEFAULT '' NOT NULL,
	category int(10) unsigned DEFAULT '0' NOT NULL,
	inStock int(11) DEFAULT '1' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	tax int(11) DEFAULT '0' NOT NULL,
	weight varchar(20) DEFAULT '' NOT NULL,
	bulkily int(11) DEFAULT '0' NOT NULL,
	offer int(11) DEFAULT '0' NOT NULL,
	highlight int(11) DEFAULT '0' NOT NULL,
	directcost varchar(20) DEFAULT '' NOT NULL,
	accessory varchar(10) DEFAULT '' NOT NULL,
	accessory2 varchar(10) DEFAULT '' NOT NULL,
	color varchar(255) DEFAULT '' NOT NULL,
	size varchar(255) DEFAULT '' NOT NULL,
	gradings varchar(255) DEFAULT '' NOT NULL,
	special_preparation int(11) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tt_products_cat'
#
CREATE TABLE tt_products_cat (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	note text NOT NULL,
	image tinyblob NOT NULL,
	email_uid int(11) DEFAULT '0' NOT NULL,
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
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT "" NOT NULL,
	subtitle varchar(80) DEFAULT "" NOT NULL,
	prod_uid int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	subtitle tinytext NOT NULL,
	note text NOT NULL,
	unit varchar(20) DEFAULT '' NOT NULL,  
	datasheet tinyblob NOT NULL,  
	www varchar(80) DEFAULT '' NOT NULL,
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
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	note text NOT NULL,  
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
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	subtitle varchar(80) DEFAULT '' NOT NULL,
	itemnumber varchar(40) DEFAULT '' NOT NULL,
	price varchar(20) DEFAULT '' NOT NULL,
	price2 varchar(20) DEFAULT '' NOT NULL,
	inStock int(11) DEFAULT '1' NOT NULL,

	color varchar(20) DEFAULT '' NOT NULL,
	size varchar(20) DEFAULT '' NOT NULL,
	gradings varchar(20) DEFAULT '' NOT NULL,
	
	uid_product int(11) DEFAULT '0' NOT NULL,

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
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
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
# Table structure for table 'tt_products_card_payments'
#
#CREATE TABLE tt_products_card_payments (
#  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
#  pid int(11) unsigned DEFAULT '0' NOT NULL,
#  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
#  crdate int(11) unsigned DEFAULT '0' NOT NULL,
#  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
#  ord_uid int(11) unsigned DEFAULT '0' NOT NULL,
#  order_id varchar(20) DEFAULT '' NOT NULL,
#  session_id varchar(30) DEFAULT '' NOT NULL,  
#  amount_num int(10) DEFAULT '0' NOT NULL,
#  response_code char(3) DEFAULT '' NOT NULL,   
#  cc_number_hash1 varchar(255) DEFAULT '' NOT NULL,
#  cc_number_hash2 varchar(255) DEFAULT '' NOT NULL,  
#  card_type varchar(20) DEFAULT '' NOT NULL,
#  address_ok char(1) DEFAULT '' NOT NULL, 
#  test char(1) DEFAULT '' NOT NULL,   
#  auth_code varchar(16) DEFAULT '' NOT NULL,
#  bin int(6) unsigned DEFAULT '0' NOT NULL,
#  fraud tinyint(1) unsigned DEFAULT '0' NOT NULL,  
#  sequence int(6) unsigned DEFAULT '0' NOT NULL,                   
#  PRIMARY KEY (uid),
#  KEY parent (ord_uid)
#);


#
# Table structure for table 'sys_products_orders'
#
CREATE TABLE sys_products_orders (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	note text NOT NULL,
	feusers_uid int(11) DEFAULT '0' NOT NULL,
# forename varchar(80) DEFAULT '' NOT NULL,
	name varchar(80) DEFAULT '' NOT NULL,
# company varchar(80) DEFAULT '' NOT NULL,  
# vat_id varchar(20) DEFAULT '' NOT NULL,  
	telephone varchar(20) DEFAULT '' NOT NULL,
	fax varchar(20) DEFAULT '' NOT NULL,
	email varchar(80) DEFAULT '' NOT NULL,
	payment varchar(80) DEFAULT '' NOT NULL,
	shipping varchar(80) DEFAULT '' NOT NULL,
	amount varchar(20) DEFAULT '' NOT NULL,
	email_notify tinyint(4) unsigned DEFAULT '0' NOT NULL,
	tracking_code varchar(20) DEFAULT '' NOT NULL,
	status tinyint(4) unsigned DEFAULT '0' NOT NULL,
	status_log blob NOT NULL,
	orderData mediumblob NOT NULL,
#  session_id varchar(30) DEFAULT '' NOT NULL,
#  amount_num int(10) unsigned DEFAULT '0' NOT NULL,
#  street varchar(40) DEFAULT '' NOT NULL,
#  street_n1 varchar(40) DEFAULT '' NOT NULL,
#  street_n2 varchar(10) DEFAULT '' NOT NULL,
#  city varchar(40) DEFAULT '' NOT NULL,
#  zip varchar(10) DEFAULT '' NOT NULL,
#  country_code char(3) DEFAULT '' NOT NULL,
#  client_ip varchar(15) DEFAULT '' NOT NULL,
	creditpoints decimal(10,4) default '0.0000' NOT NULL,
# added els4: creditpoints_spended
	creditpoints_spended decimal(10,0) default '0' NOT NULL,
# added els4: creditpoints_saved
	creditpoints_saved decimal(10,4) default '0.0000' NOT NULL,
	agb char(2) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY tracking (tracking_code),
	KEY status (status),
	KEY uid (uid,amount)
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
	KEY tt_products_uid (tt_products_articles_uid),
	KEY sys_products_orders_uid (sys_products_orders_uid)
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tt_products_memoItems tinytext NOT NULL,
	tt_products_discount int(11) DEFAULT '0' NOT NULL
	tt_products_creditpoints decimal(10,4) DEFAULT '0.0000' NOT NULL,
	tt_products_vouchercode varchar(50) DEFAULT ''
);

#
# Extension of table 'tt_content' for zk_products compatibility where pages are used as categories
#
CREATE TABLE tt_content (
	tt_products_code varchar(30) DEFAULT 'HELP' NOT NULL
);
