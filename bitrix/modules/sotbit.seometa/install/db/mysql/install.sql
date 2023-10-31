CREATE TABLE IF NOT EXISTS `b_sotbit_seometa`
(
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `NAME` text,
    `ACTIVE` char(1) DEFAULT 'Y',
    `SEARCH` char(1) DEFAULT 'Y',
    `SORT` int(18) NOT NULL DEFAULT '100',
    `DATE_CHANGE` datetime DEFAULT NULL,
    `SITES` text,
    `TYPE_OF_CONDITION` varchar(255) DEFAULT NULL,
    `TYPE_OF_INFOBLOCK` varchar(255) DEFAULT NULL,
    `INFOBLOCK` varchar(255) DEFAULT NULL,
    `SECTIONS` text,
    `RULE` text,
    `META` text,
    `NO_INDEX` char(1) DEFAULT 'N',
    `STRONG` char(1) DEFAULT 'Y',
    `PRIORITY` float(3) NOT NULL DEFAULT 0.5,
    `CHANGEFREQ` varchar(10) NOT NULL DEFAULT 'monthly',
    `CATEGORY_ID` int(11) NOT NULL DEFAULT 0,
    `FILTER_TYPE` text,
    `TAG` text,
    `HIDE_IN_SECTION` char(1) DEFAULT 'N',
    `CONDITION_TAG` text,
    `STRICT_RELINKING` char(1) DEFAULT 'N',
    `GENERATE_AJAX` char(1) DEFAULT 'Y',
    PRIMARY KEY (`ID`)
);
CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_sitemaps`
(
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `TIMESTAMP_CHANGE` timestamp,
    `SITE_ID` char(3) NOT NULL,
    `NAME` varchar(255) NOT NULL,
    `DATE_RUN` datetime DEFAULT NULL,
    `SETTINGS` longtext DEFAULT NULL,
    PRIMARY KEY (`ID`)
);
CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_section`
(
    ID int(11) NOT NULL auto_increment,
    DATE_CHANGE DATETIME not null,
    DATE_CREATE DATETIME NULL,
    ACTIVE CHAR(1) DEFAULT 'Y' NOT NULL,
    SORT int(11) DEFAULT 500 NOT NULL,
    NAME VARCHAR(255) NULL,
    DESCRIPTION TEXT NULL,
    PARENT_CATEGORY_ID INT(11) DEFAULT 0 NOT NULL,
    PRIMARY KEY (ID)
);
CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_chpu`
(
    `ID` int(11) NOT NULL auto_increment,
    `ACTIVE` char(1) DEFAULT 'N',
    `SORT` int(11) DEFAULT 100,
    `NAME` VARCHAR(255) NULL,
    `REAL_URL` text,
    `NEW_URL` text,
    `CATEGORY_ID` int(11) NOT NULL DEFAULT 0,
    `DATE_CHANGE` DATETIME not null,
    `CONDITION_ID` int(11) DEFAULT 0,
    `iblock_id` int(11) DEFAULT 0,
    `section_id` int(11) DEFAULT 0,
    `PROPERTIES` text,
    `PRODUCT_COUNT` int(11) DEFAULT 0,
    `IN_SITEMAP` char(1) DEFAULT 'N',
    `STATUS` varchar(255),
    `DESCRIPTION` varchar(255),
    `KEYWORDS` varchar(255),
    `TITLE` varchar(255),
    `DATE_SCAN` datetime,
    `SEOMETA_DATA` text,
    `ELEMENT_FILE` varchar(255),
    `SITE_ID` varchar(255),
    PRIMARY KEY (ID)
);
CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_chpu_links`
(
    `ID` int(11) NOT NULL auto_increment,
    `MAIN_CHPU_ID` int(11) DEFAULT 0,
    `LINK_CHPU_ID` int(11) DEFAULT 0,
    `SEOMETA_DATA_CHPU_LINK` text,
    PRIMARY KEY (ID)
);
CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_chpu_seo_data`
(
    `ID` int(11) NOT NULL auto_increment,
    `BITRIX_URL` text,
    `CONDITION_ID` int(11),
    `SEOMETA_DATA` text,
    `SITE_ID` varchar(255),
    PRIMARY KEY (ID)
);
# CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_chpu_bottom_tag`
# (
#     `ID` int(11) NOT NULL auto_increment,
#     `CHPU_ID` int(11),
#     `TAG_OVERRIDE_TYPE` varchar(1),
#     `TAG_DATA` text,
#     PRIMARY KEY (ID)
# );
CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_section_chpu`
(
    ID int(11) NOT NULL auto_increment,
    DATE_CHANGE DATETIME not null,
    DATE_CREATE DATETIME NULL,
    ACTIVE CHAR(1) DEFAULT 'Y' NOT NULL,
    SORT int(11) DEFAULT 500 NOT NULL,
    NAME VARCHAR(255) NULL,
    DESCRIPTION TEXT NULL,
    PARENT_CATEGORY_ID INT(11) DEFAULT 0 NOT NULL,
    PRIMARY KEY (ID)
);
CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_statistics`
(
    ID int(11) NOT NULL auto_increment,
    DATE_CREATE DATETIME NULL,
    SORT int(11) DEFAULT 500 NOT NULL,
    URL text,
    IN_SITEMAP char(1) DEFAULT 'N',
    NO_INDEX char(1) DEFAULT 'N',
    CONDITION_ID int,
    META_TITLE text,
    META_KEYWORDS text,
    META_DESCRIPTION text,
    ROBOTS_INFO VARCHAR(1000) NULL,
    LAST_DATE_CHECK DATETIME NULL,
    PAGE_STATUS char(5) NULL,
    SITE_ID VARCHAR(255),
    PRIMARY KEY (ID)
);
CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_autogeneration`
(
    `ID` int(11) NOT NULL auto_increment,
    `NAME` varchar(255) NOT NULL,
    `SITES` varchar(255),
    `TYPE_OF_INFOBLOCK` varchar(255),
    `INFOBLOCK` varchar(255),
    `SECTIONS` text,
    `RULE` text,
    `LOGIC` varchar(10) DEFAULT 'AND',
    `FILTER_TYPE` varchar(255),
    `NAME_TEMPLATE` varchar(255),
    `ACTIVE` char(1) DEFAULT 'Y',
    `SEARCH` char(1) DEFAULT 'Y',
    `NO_INDEX` char(1) DEFAULT 'N',
    `STRICT` char(1) DEFAULT 'Y',
    `CATEGORY` int(11) DEFAULT 0,
    `META` text,
    `TAGS` text,
    `NEW_URL_TEMPLATE` varchar(255),
    `GENERATE_CHPU` char(1) DEFAULT 'N',
    `DATE_CHANGE` datetime,
    PRIMARY KEY (`ID`)
);
CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_og`
(
    `ID` int(11) NOT NULL auto_increment,
    `CONDITION_ID` int(11),
    `ACTIVE` char(1) DEFAULT 'N',
    `SETTINGS` text,
    PRIMARY KEY (`ID`)
);
CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_tw`
(
    `ID` int(11) NOT NULL auto_increment,
    `CONDITION_ID` int(11),
    `ACTIVE` char(1) DEFAULT 'N',
    `SETTINGS` text,
    PRIMARY KEY (`ID`)
);

CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_not_configured_pages`
(
    `ID` int(11) NOT NULL auto_increment,
    `SITE_ID` char(255),
    `ACTIVE` char(1) DEFAULT 'N',
    `BEHAVIOR_FILTERED_PAGES` char(10) DEFAULT '0',
    `BEHAVIOR_PAGINATION_PAGES` char(10) DEFAULT '0',
    `METAINFO_SETTINGS` text,
    `MAIN_SETTINGS` text,
    PRIMARY KEY (`ID`)
);

CREATE TABLE IF NOT EXISTS `b_sotbit_seometa_parse_result`
(
    `ID` int(11) NOT NULL auto_increment,
    `FILE_ID` int(11) NOT NULL,
    `ENTITY_ROW`  int(11) NOT NULL,
    `ENTITY_NAME` VARCHAR(255) NULL,
    `MESSAGE` text,
    PRIMARY KEY (`ID`)
);