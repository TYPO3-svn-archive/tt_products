#
# Table structure for table 'tt_products'
#
CREATE TABLE tt_products (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,
  title tinytext NOT NULL,
  note text NOT NULL,
  price varchar(20) DEFAULT '' NOT NULL,
  price2 varchar(20) DEFAULT '' NOT NULL,
  image tinyblob NOT NULL,
  www varchar(80) DEFAULT '' NOT NULL,
  itemnumber varchar(40) DEFAULT '' NOT NULL,
  category int(10) unsigned DEFAULT '0' NOT NULL,
  inStock int(11) DEFAULT '0' NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
  ordered int(10) unsigned DEFAULT '0' NOT NULL,
  fe_group int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'tt_products_cat'
#
CREATE TABLE tt_products_cat (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  title tinytext NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);


#
# Table structure for table 'sys_products_orders'
#
CREATE TABLE sys_products_orders (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  note text NOT NULL,
  name varchar(80) DEFAULT '' NOT NULL,
  telephone varchar(20) DEFAULT '' NOT NULL,
  email varchar(80) DEFAULT '' NOT NULL,
  payment varchar(80) DEFAULT '' NOT NULL,
  shipping varchar(80) DEFAULT '' NOT NULL,
  amount varchar(20) DEFAULT '' NOT NULL,
  email_notify tinyint(4) unsigned DEFAULT '0' NOT NULL,
  tracking_code varchar(20) DEFAULT '' NOT NULL,
  status tinyint(4) unsigned DEFAULT '0' NOT NULL,
  status_log blob NOT NULL,
  orderData mediumblob NOT NULL,
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
  KEY tt_products_uid (tt_products_uid),
  KEY sys_products_orders_uid (sys_products_orders_uid)
);
