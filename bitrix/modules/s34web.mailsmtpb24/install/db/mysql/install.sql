CREATE TABLE IF NOT EXISTS `mail_smtp_b24_smtp_accounts` (
    `ID` int(18) NOT NULL AUTO_INCREMENT,
    `ACTIVE` char(1) NOT NULL DEFAULT 'Y',
    `NAME` varchar(255) DEFAULT NULL,
    `EMAIL` varchar(255) DEFAULT NULL,
    `SERVER` varchar(255) NOT NULL,
    `PORT` int(5) NOT NULL,
    `SECURE` char(1) NOT NULL DEFAULT 'N',
    `AUTH` char(1) NOT NULL DEFAULT 'N',
    `LOGIN` varchar(255) DEFAULT NULL,
    `PASSWORD` varchar(255),
    `DATE_CREATE_LOG` date NOT NULL,
    PRIMARY KEY (`ID`)
);

CREATE TABLE IF NOT EXISTS `mail_smtp_b24_users_smtp_accounts` (
    `ID` int(18) NOT NULL AUTO_INCREMENT,
    `SMTP_ID` int(18) unsigned NOT NULL,
    `DATE_CREATE_LOG` date NOT NULL,
    PRIMARY KEY (`ID`)
);