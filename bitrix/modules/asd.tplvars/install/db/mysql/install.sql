create table if not exists b_asd_option_descr
(
	ID INT not null auto_increment,
	NAME VARCHAR(50) not null,
	DESCRIPTION VARCHAR(255),
	SITE_ID CHAR(2),
	PRIMARY KEY (ID),
	INDEX ASD_OPTION_NAME (NAME),
	INDEX ASD_OPTION_SITE (SITE_ID)
);