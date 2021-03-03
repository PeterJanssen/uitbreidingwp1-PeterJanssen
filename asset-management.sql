--
-- Database: 'asset-management'
--
-- --------------------------------------------------------
-- Creating tables and inserting dummy data
-- --------------------------------------------------------

--
-- Drop and create the database again
--

DROP DATABASE `asset-management-tool`;
CREATE DATABASE `asset-management-tool`;
USE `asset-management-tool`;

--
-- Tablestructure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE IF NOT EXISTS `rooms`(
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `happinessScore` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
);

--
-- Inserting dummy data into table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `happinessScore`) VALUES
(443, 'B051', 3445),
(444, 'B052', 1000),
(445, 'B053', 5000)
;

-- --------------------------------------------------------

--
-- Tablestructure for table `renters`
--

DROP TABLE IF EXISTS `renters`;
CREATE TABLE IF NOT EXISTS `renters`(
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(45) DEFAULT NULL,
  `lastName` varchar(45) DEFAULT NULL,
  `fromDate` date DEFAULT NULL,
  `toDate` date DEFAULT NULL,
  `roomId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
);

--
-- Inserting dummy data into table `renters`
--

INSERT INTO `renters` (`id`, `firstName`, `lastName`,`fromDate`,`toDate`,`roomId`) VALUES
(1, 'Peter', 'Janssen','2021-07-20','2021-08-20',443),
(2, 'Ben', 'Merken','2021-02-21','2021-02-24',444),
(3, 'Piet', 'Jan','2021-01-10','2021-02-20',445);

-- --------------------------------------------------------

--
-- Tablestructure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
CREATE TABLE IF NOT EXISTS `assets`(
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roomId` int(11) DEFAULT 0,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
);

--
-- Inserting dummy data into table `assets`
--

INSERT INTO `assets` (`id`, `roomId`, `name`) VALUES
(223, 443, 'beamer'),
(224, 443, 'computer'),
(225, 444, 'computer'),
(226, 445, 'router');

-- --------------------------------------------------------

--
-- Tablestructure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE IF NOT EXISTS `tickets`(
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assetId` int(11) DEFAULT 0,
  `numberOfVotes` int(11) DEFAULT 0,
  `description` varchar(90) DEFAULT NULL,
  PRIMARY KEY (`id`)
);

--
-- Inserting dummy data into table `tickets`
--

INSERT INTO `tickets` (`id`, `assetId`, `numberOfVotes`, `description`) VALUES
(32, 223, 1, 'beamer does not show correct colours'),
(33, 224, 5, 'computer fan does not spin'),
(34, 225, 100, 'router does not want to start'),
(35, 226, 666, 'Somebody smashed the router!');

-- --------------------------------------------------------
-- adding constraints
-- --------------------------------------------------------

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `Asset_Room` FOREIGN KEY (`roomId`) REFERENCES `rooms` (`id`);
COMMIT;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `Ticket_Asset` FOREIGN KEY (`assetId`) REFERENCES `assets` (`id`);
COMMIT;

--
-- Constraints for table `renters`
--
ALTER TABLE `renters`
  ADD CONSTRAINT `Renter_Room` FOREIGN KEY (`roomId`) REFERENCES `rooms` (`id`);
COMMIT;
