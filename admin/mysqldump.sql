-- MySQL dump 10.14  Distrib 5.5.40-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: freedom_archives
-- ------------------------------------------------------
-- Server version	5.5.40-MariaDB-0ubuntu0.14.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `AUTHORS`
--

DROP TABLE IF EXISTS `AUTHORS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AUTHORS` (
  `AUTHOR_ID` int(11) NOT NULL AUTO_INCREMENT,
  `AUTHOR_NAME` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`AUTHOR_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `COLLECTIONS`
--

DROP TABLE IF EXISTS `COLLECTIONS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `COLLECTIONS` (
  `COLLECTION_ID` int(11) NOT NULL AUTO_INCREMENT,
  `COLLECTION_NAME` varchar(255) DEFAULT NULL,
  `COLLECTION_LABEL` varchar(500) DEFAULT NULL,
  `DESCRIPTION` text,
  `SUMMARY` varchar(255) DEFAULT NULL,
  `PARENT_ID` int(11) DEFAULT '0',
  `IS_HIDDEN` int(1) DEFAULT '0',
  `CALL_NO` text,
  `DATE_CREATED` text,
  `DATE_RANGE` text,
  `ACCESS_LEVEL` text,
  `SUBJECTS` text,
  `KEYWORDS` text,
  `ORGANIZATION` text,
  `INTERNAL_NOTES` text,
  `THUMBNAIL` varchar(50) DEFAULT NULL,
  `DISPLAY_ORDER` int(11) NOT NULL DEFAULT '1000',
  `DATE_MODIFIED` datetime DEFAULT NULL,
  `CREATOR` varchar(45) DEFAULT NULL,
  `CONTRIBUTOR` varchar(45) DEFAULT NULL,
  `NEEDS_REVIEW` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`COLLECTION_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=343 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `COLLECTIONS_LIVE`
--

DROP TABLE IF EXISTS `COLLECTIONS_LIVE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `COLLECTIONS_LIVE` (
  `COLLECTION_ID` int(11) NOT NULL AUTO_INCREMENT,
  `COLLECTION_NAME` varchar(255) DEFAULT NULL,
  `COLLECTION_LABEL` varchar(500) DEFAULT NULL,
  `DESCRIPTION` text,
  `SUMMARY` varchar(255) DEFAULT NULL,
  `PARENT_ID` int(11) DEFAULT '0',
  `IS_HIDDEN` int(1) DEFAULT '0',
  `CALL_NO` text,
  `DATE_CREATED` text,
  `DATE_RANGE` text,
  `ACCESS_LEVEL` text,
  `SUBJECTS` text,
  `KEYWORDS` text,
  `ORGANIZATION` text,
  `INTERNAL_NOTES` text,
  `THUMBNAIL` varchar(50) DEFAULT NULL,
  `DISPLAY_ORDER` int(11) NOT NULL DEFAULT '1000',
  `DATE_MODIFIED` datetime DEFAULT NULL,
  `CREATOR` varchar(45) DEFAULT NULL,
  `CONTRIBUTOR` varchar(45) DEFAULT NULL,
  `IS_REVIEWED` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`COLLECTION_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=343 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CONTROLLED_VOCABULARY`
--

DROP TABLE IF EXISTS `CONTROLLED_VOCABULARY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CONTROLLED_VOCABULARY` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `WORD` varchar(50) DEFAULT NULL,
  `COUNT` int(11) DEFAULT '0',
  `COLLECTION_ID` int(11) DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=624 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOCUMENTS`
--

DROP TABLE IF EXISTS `DOCUMENTS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOCUMENTS` (
  `DOCID` int(11) NOT NULL AUTO_INCREMENT,
  `TITLE` text,
  `CREATOR` varchar(500) DEFAULT NULL,
  `SUBJECTS` text,
  `DESCRIPTION` text,
  `PUBLISHER` varchar(500) DEFAULT NULL,
  `CONTRIBUTOR` varchar(500) DEFAULT NULL,
  `DATE_CREATED` datetime DEFAULT NULL,
  `DATE_AVAILABLE` varchar(20) DEFAULT NULL,
  `DATE_MODIFIED` datetime DEFAULT NULL,
  `IDENTIFIER` varchar(255) DEFAULT NULL,
  `SOURCE` varchar(255) DEFAULT NULL,
  `LANGUAGE` varchar(50) DEFAULT NULL,
  `RELATION` varchar(100) DEFAULT NULL,
  `COVERAGE` varchar(50) DEFAULT NULL,
  `RIGHTS` varchar(50) DEFAULT NULL,
  `AUDIENCE` varchar(255) DEFAULT NULL,
  `FORMAT` text,
  `DIGITIZATION_SPECIFICATION` varchar(100) DEFAULT NULL,
  `KEYWORDS` text,
  `AUTHORS` text,
  `VOL_NUMBER` varchar(50) DEFAULT NULL,
  `NO_COPIES` int(11) DEFAULT '1',
  `FILE_NAME` varchar(1000) DEFAULT NULL,
  `DOC_TEXT` text,
  `FILE_EXTENSION` text,
  `PBCORE_CREATOR` varchar(255) DEFAULT NULL,
  `PBCORE_COVERAGE` varchar(20) DEFAULT NULL,
  `PBCORE_RIGHTS_SUMMARY` varchar(255) DEFAULT NULL,
  `PBCORE_EXTENSION` varchar(255) DEFAULT NULL,
  `COLLECTION_ID` int(11) DEFAULT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `URL_TEXT` varchar(255) DEFAULT NULL,
  `PRODUCERS` text,
  `PROGRAM` text,
  `GENERATION` text,
  `QUALITY` text,
  `YEAR` varchar(10) DEFAULT NULL,
  `LOCATION` varchar(50) DEFAULT NULL,
  `NEEDS_REVIEW` int(1) DEFAULT '0',
  `IS_HIDDEN` int(1) DEFAULT '0',
  `CALL_NUMBER` text,
  `LENGTH` varchar(50) DEFAULT NULL,
  `NOTES` text,
  `THUMBNAIL` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`DOCID`),
  KEY `duplicate` (`TITLE`(100),`DESCRIPTION`(100)),
  FULLTEXT KEY `FA_FULLTEXT` (`TITLE`,`DESCRIPTION`,`SUBJECTS`,`AUTHORS`,`DOC_TEXT`,`KEYWORDS`,`FILE_EXTENSION`)
) ENGINE=MyISAM AUTO_INCREMENT=33987 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DOCUMENTS_LIVE`
--

DROP TABLE IF EXISTS `DOCUMENTS_LIVE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DOCUMENTS_LIVE` (
  `DOCID` int(11) NOT NULL AUTO_INCREMENT,
  `TITLE` text,
  `CREATOR` varchar(500) DEFAULT NULL,
  `SUBJECTS` text,
  `DESCRIPTION` text,
  `PUBLISHER` varchar(500) DEFAULT NULL,
  `CONTRIBUTOR` varchar(500) DEFAULT NULL,
  `DATE_CREATED` datetime DEFAULT NULL,
  `DATE_AVAILABLE` varchar(20) DEFAULT NULL,
  `DATE_MODIFIED` datetime DEFAULT NULL,
  `IDENTIFIER` varchar(255) DEFAULT NULL,
  `SOURCE` varchar(255) DEFAULT NULL,
  `LANGUAGE` varchar(50) DEFAULT NULL,
  `RELATION` varchar(100) DEFAULT NULL,
  `COVERAGE` varchar(50) DEFAULT NULL,
  `RIGHTS` varchar(50) DEFAULT NULL,
  `AUDIENCE` varchar(255) DEFAULT NULL,
  `FORMAT` text,
  `DIGITIZATION_SPECIFICATION` varchar(100) DEFAULT NULL,
  `KEYWORDS` text,
  `AUTHORS` text,
  `VOL_NUMBER` varchar(50) DEFAULT NULL,
  `NO_COPIES` int(11) DEFAULT '1',
  `FILE_NAME` varchar(1000) DEFAULT NULL,
  `DOC_TEXT` text,
  `FILE_EXTENSION` text,
  `PBCORE_CREATOR` varchar(255) DEFAULT NULL,
  `PBCORE_COVERAGE` varchar(20) DEFAULT NULL,
  `PBCORE_RIGHTS_SUMMARY` varchar(255) DEFAULT NULL,
  `PBCORE_EXTENSION` varchar(255) DEFAULT NULL,
  `COLLECTION_ID` int(11) DEFAULT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `URL_TEXT` varchar(255) DEFAULT NULL,
  `PRODUCERS` text,
  `PROGRAM` text,
  `GENERATION` text,
  `QUALITY` text,
  `YEAR` varchar(10) DEFAULT NULL,
  `LOCATION` varchar(50) DEFAULT NULL,
  `IS_REVIEWED` int(11) DEFAULT '0',
  `IS_HIDDEN` int(11) DEFAULT '0',
  `CALL_NUMBER` text,
  `LENGTH` varchar(50) DEFAULT NULL,
  `NOTES` text,
  `THUMBNAIL` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`DOCID`),
  KEY `duplicate` (`TITLE`(100),`DESCRIPTION`(100)),
  FULLTEXT KEY `FA_FULLTEXT` (`TITLE`,`DESCRIPTION`,`SUBJECTS`,`AUTHORS`,`DOC_TEXT`,`KEYWORDS`,`FILE_EXTENSION`)
) ENGINE=MyISAM AUTO_INCREMENT=33987 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FEATURED_DOCS`
--

DROP TABLE IF EXISTS `FEATURED_DOCS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FEATURED_DOCS` (
  `DOCID` int(11) NOT NULL,
  `COLLECTION_ID` int(11) NOT NULL,
  `DOC_ORDER` int(11) DEFAULT NULL,
  `DESCRIPTION` varchar(45) CHARACTER SET latin1 DEFAULT NULL,
  KEY `colid` (`COLLECTION_ID`,`DOCID`),
  KEY `docid` (`DOCID`,`COLLECTION_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FEATURED_DOCS_LIVE`
--

DROP TABLE IF EXISTS `FEATURED_DOCS_LIVE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FEATURED_DOCS_LIVE` (
  `DOCID` int(11) NOT NULL,
  `COLLECTION_ID` int(11) NOT NULL,
  `DOC_ORDER` int(11) DEFAULT NULL,
  `DESCRIPTION` varchar(45) CHARACTER SET latin1 DEFAULT NULL,
  KEY `colid` (`COLLECTION_ID`,`DOCID`),
  KEY `docid` (`DOCID`,`COLLECTION_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FILEMAKER_COLLECTIONS`
--

DROP TABLE IF EXISTS `FILEMAKER_COLLECTIONS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FILEMAKER_COLLECTIONS` (
  `CALL_NUMBER` varchar(5) DEFAULT NULL,
  `Title` varchar(172) DEFAULT NULL,
  `COLLECTION_ID` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `GROUPS`
--

DROP TABLE IF EXISTS `GROUPS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GROUPS` (
  `GROUP_ID` int(11) NOT NULL,
  `GROUP_NAME` varchar(100) DEFAULT NULL,
  `ROLE_ID` int(11) DEFAULT NULL,
  PRIMARY KEY (`GROUP_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LIST_ITEMS`
--

DROP TABLE IF EXISTS `LIST_ITEMS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LIST_ITEMS` (
  `item` varchar(60) NOT NULL,
  `type` varchar(45) NOT NULL,
  PRIMARY KEY (`item`,`type`),
  KEY `type` (`type`,`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LIST_ITEMS_LOOKUP`
--

DROP TABLE IF EXISTS `LIST_ITEMS_LOOKUP`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LIST_ITEMS_LOOKUP` (
  `type` varchar(20) NOT NULL,
  `item` varchar(60) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  `is_doc` int(1) NOT NULL,
  PRIMARY KEY (`type`,`id`,`is_doc`,`order`),
  KEY `value` (`item`,`type`,`is_doc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LIST_ITEMS_LOOKUP_LIVE`
--

DROP TABLE IF EXISTS `LIST_ITEMS_LOOKUP_LIVE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LIST_ITEMS_LOOKUP_LIVE` (
  `type` varchar(20) NOT NULL,
  `item` varchar(60) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  `is_doc` int(1) NOT NULL,
  PRIMARY KEY (`type`,`id`,`is_doc`,`order`),
  KEY `value` (`item`,`type`,`is_doc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ONTOLECTION`
--

DROP TABLE IF EXISTS `ONTOLECTION`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ONTOLECTION` (
  `WORD` varchar(50) DEFAULT NULL,
  `EXPANSION_VALUE` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PRODUCERS`
--

DROP TABLE IF EXISTS `PRODUCERS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PRODUCERS` (
  `PRODUCER_ID` int(11) NOT NULL AUTO_INCREMENT,
  `PRODUCER` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`PRODUCER_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REVIEW_METADATA_FIELDS`
--

DROP TABLE IF EXISTS `REVIEW_METADATA_FIELDS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REVIEW_METADATA_FIELDS` (
  `FIELD_ID` varchar(20) NOT NULL,
  `COLUMN_NAME` varchar(50) DEFAULT NULL,
  `DISPLAY_NAME` varchar(50) DEFAULT NULL,
  `IS_ACTIVE` binary(20) DEFAULT NULL,
  PRIMARY KEY (`FIELD_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `SEARCH_HISTORY`
--

DROP TABLE IF EXISTS `SEARCH_HISTORY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SEARCH_HISTORY` (
  `id` varchar(20) NOT NULL,
  `USER_IP` varchar(20) DEFAULT NULL,
  `SEARCH_TERM` varchar(255) DEFAULT NULL,
  `SEARCH_ELAPSED_TIME` varchar(20) DEFAULT NULL,
  `NUMBER_OF_HITS` bigint(20) DEFAULT NULL,
  `SEARCH_CONTEXT` varchar(100) DEFAULT NULL,
  `USER_ID` int(11) DEFAULT NULL,
  `SEARCH_DATE` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `SUBJECTS`
--

DROP TABLE IF EXISTS `SUBJECTS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SUBJECTS` (
  `SUBJECT_ID` int(11) NOT NULL AUTO_INCREMENT,
  `SUBJECT_HEADER` varchar(2000) DEFAULT NULL,
  PRIMARY KEY (`SUBJECT_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `SUBJECT_HEADERS`
--

DROP TABLE IF EXISTS `SUBJECT_HEADERS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SUBJECT_HEADERS` (
  `SUBJECT_ID` varchar(10) NOT NULL,
  `SUBJECT` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`SUBJECT_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TABLE 15`
--

DROP TABLE IF EXISTS `TABLE 15`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TABLE 15` (
  `Title` varchar(215) DEFAULT NULL,
  `File Name` varchar(68) DEFAULT NULL,
  `URL` varchar(154) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USERS`
--

DROP TABLE IF EXISTS `USERS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USERS` (
  `USER_ID` int(11) NOT NULL AUTO_INCREMENT,
  `USERNAME` varchar(50) DEFAULT NULL,
  `FIRSTNAME` varchar(50) DEFAULT NULL,
  `LASTNAME` varchar(50) DEFAULT NULL,
  `USER_TYPE` varchar(50) DEFAULT NULL,
  `PASSWORD` varchar(10) DEFAULT NULL,
  `id` varchar(20) NOT NULL,
  `STATUS` varchar(20) DEFAULT NULL,
  `EMAIL` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`USER_ID`,`id`),
  KEY `fk_USERSRelationship8` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `USER_GROUP_MAP`
--

DROP TABLE IF EXISTS `USER_GROUP_MAP`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USER_GROUP_MAP` (
  `USER_ID` int(11) NOT NULL,
  `GROUP_ID` int(11) NOT NULL,
  PRIMARY KEY (`USER_ID`,`GROUP_ID`),
  KEY `fk_USER_GROUP_MAPRelationship10` (`GROUP_ID`),
  CONSTRAINT `fk_USER_GROUP_MAPRelationship10` FOREIGN KEY (`GROUP_ID`) REFERENCES `GROUPS` (`GROUP_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id` int(11) NOT NULL,
  `type` varchar(15) NOT NULL,
  `user` varchar(30) NOT NULL,
  `action` varchar(45) DEFAULT NULL,
  `description` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`timestamp`,`id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-12-23  3:22:25
