CREATE TABLE tx_blogsync_config (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,

	connection_id varchar(255) DEFAULT '' NOT NULL,
	account_email varchar(255) DEFAULT '' NOT NULL,
	api_key varchar(255) DEFAULT '' NOT NULL,
	site_url varchar(255) DEFAULT '' NOT NULL,
	blog_storage_folder int(11) unsigned DEFAULT '0' NOT NULL,
	sync_enabled tinyint(1) unsigned DEFAULT '1' NOT NULL,
	render_h1_title tinyint(1) DEFAULT '0' NOT NULL,
	last_sync int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	UNIQUE KEY connection_id (connection_id),
	KEY parent (pid)
);

CREATE TABLE tx_blogsync_log (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,

	config_id int(11) unsigned DEFAULT '0' NOT NULL,
	sync_type varchar(50) DEFAULT '' NOT NULL,
	status varchar(50) DEFAULT '' NOT NULL,
	imported_count int(11) DEFAULT '0' NOT NULL,
	failed_count int(11) DEFAULT '0' NOT NULL,
	message text,
	details text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE pages (
	external_id varchar(255) DEFAULT '' NOT NULL,
	KEY blogsync_external_id (external_id)
);
