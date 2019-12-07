CREATE TABLE IF NOT EXISTS `b_sotbit_schema_page_meta` (
    `ID` int(11) not null auto_increment,
    `NAME` VARCHAR(255) not null,
    `ACTIVE` char(1) DEFAULT 'Y',
    `URL` VARCHAR(255) null,
    `ENTITIES` TEXT null,
    `CATEGORY_ID` INT NULL,
    `SITE_ID` VARCHAR(255) null,
    `TIMESTAMP_X` datetime not null,
    `DATE_CREATE` datetime not null,
    `SORT` VARCHAR(255) null,
    `ENTITY_TYPE` VARCHAR(255) null,
    primary key (ID)
);

CREATE TABLE IF NOT EXISTS `b_sotbit_schema_category` (
    `ID` INT(11) not null auto_increment,
    `PARENT_ID` INT(11) NULL,
    `NAME` VARCHAR(255) not null,
    `SITE_ID` VARCHAR(255) null,
    `TIMESTAMP_X` datetime not null,
    `DATE_CREATE` datetime not null,
    `SORT` VARCHAR(255) null,
    primary key (ID)
);