/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

CREATE TABLE IF NOT EXISTS `_tricho_failed_queries` (
    `ID` int(10) unsigned NOT NULL auto_increment,
    `DateOccurred` datetime NOT NULL,
    `Query` text NOT NULL,
    `Error` text NOT NULL,
    `MailSent` tinyint(1) unsigned NOT NULL default 0,
    PRIMARY KEY (`ID`),
    KEY `DateOccurred` (`DateOccurred`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `_tricho_log` (
    `ID` int(10) unsigned NOT NULL auto_increment,
    `DateLogged` datetime NOT NULL,
    `User` varchar(30) NOT NULL,
    `Action` varchar(255) NOT NULL,
    `SQL` text NOT NULL,
    PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `_tricho_login_failures` (
    `ID` int(10) unsigned NOT NULL auto_increment,
    `Time` datetime NOT NULL,
    `User` varchar(255) NOT NULL,
    `IP` varchar(15) NOT NULL,
    `Active` tinyint(1) unsigned NOT NULL default 1,
    `LockedUntil` datetime default NULL,
    PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `_tricho_slow_queries` (
    `ID` int(10) unsigned NOT NULL auto_increment,
    `DateOccurred` datetime NOT NULL,
    `Query` text NOT NULL,
    `TimeTaken` double unsigned NOT NULL,
    `MailSent` tinyint(1) unsigned NOT NULL default 0,
    PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `_tricho_tlds` (
    `Domain` varchar(6) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    PRIMARY KEY (`Domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `_tricho_users` (
    `User` varchar(30) NOT NULL,
    `Pass` char(106) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    `AccessLevel` tinyint(3) unsigned NOT NULL,
    PRIMARY KEY (`User`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `help_data` (
    `HelpTable` varchar(100) NOT NULL,
    `HelpColumn` varchar(100) NOT NULL,
    `HelpText` text NOT NULL,
    `QuickHelp` text NOT NULL,
    PRIMARY KEY (`HelpTable`,`HelpColumn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
