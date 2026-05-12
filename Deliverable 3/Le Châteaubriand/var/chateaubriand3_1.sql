-- ============================================================
-- MySQL dump converted from SQLite: chateaubriand3.db
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;
-- DROP DATABASE chateaubriand3_1;
-- CREATE SCHEMA chateaubriand3_1;
USE chateaubriand3_1;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` TEXT NOT NULL,
  `totp_secret` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `client` (
  `clientId` INT NOT NULL AUTO_INCREMENT,
  `firstName` VARCHAR(255) NOT NULL,
  `lastName` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phoneNumber` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`clientId`),
  UNIQUE KEY `uq_client_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ballroom` (
  `ballroomId` INT NOT NULL AUTO_INCREMENT,
  `roomName` VARCHAR(255) NOT NULL,
  `minCapacity` INT NOT NULL,
  `maxCapacity` INT NOT NULL,
  `sizeSqFt` INT NOT NULL,
  `picturesPath` TEXT NOT NULL,
  `arrangementPath` TEXT DEFAULT NULL,
  PRIMARY KEY (`ballroomId`),
  UNIQUE KEY `uq_ballroom_roomName` (`roomName`),
  CONSTRAINT `chk_ballroom_capacity` CHECK (`maxCapacity` >= `minCapacity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bar` (
  `barId` INT NOT NULL AUTO_INCREMENT,
  `barType` ENUM('Free','Paid','Premium') NOT NULL,
  `pricePerPerson` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `openTime` TIME DEFAULT NULL,
  `closeTime` TIME DEFAULT NULL,
  PRIMARY KEY (`barId`),
  CONSTRAINT `chk_bar_hours` CHECK (`closeTime` IS NULL OR `openTime` IS NULL OR `closeTime` > `openTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `services` (
  `serviceId` INT NOT NULL AUTO_INCREMENT,
  `serviceName` VARCHAR(255) NOT NULL,
  `serviceEmail` VARCHAR(255) NOT NULL,
  `servicePhone` VARCHAR(50) NOT NULL,
  `serviceDescription` TEXT NOT NULL,
  `serviceType` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`serviceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `foodCategory` (
  `categoryId` INT NOT NULL AUTO_INCREMENT,
  `categoryName` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`categoryId`),
  UNIQUE KEY `uq_foodCategory_name` (`categoryName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `foodItem` (
  `itemId` INT NOT NULL AUTO_INCREMENT,
  `itemName` VARCHAR(40) NOT NULL,
  `itemCategory` INT NOT NULL,
  `itemPrice` DECIMAL(10,2) NOT NULL,
  `extraPrice` DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (`itemId`),
  CONSTRAINT `fk_foodItem_category` FOREIGN KEY (`itemCategory`) REFERENCES `foodCategory` (`categoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `menu` (
  `menuId` INT NOT NULL AUTO_INCREMENT,
  `menuName` VARCHAR(50) NOT NULL,
  `pricePerPerson` DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (`menuId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `menuFoodItem` (
  `menuId` INT NOT NULL,
  `itemId` INT NOT NULL,
  PRIMARY KEY (`menuId`, `itemId`),
  CONSTRAINT `fk_mfi_menu` FOREIGN KEY (`menuId`) REFERENCES `menu` (`menuId`),
  CONSTRAINT `fk_mfi_item` FOREIGN KEY (`itemId`) REFERENCES `foodItem` (`itemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `event` (
  `eventId` INT NOT NULL AUTO_INCREMENT,
  `clientId` INT NOT NULL,
  `ballroomId` INT NOT NULL,
  `menuId` INT DEFAULT NULL,
  `barId` INT DEFAULT NULL,
  `eventDate` DATE NOT NULL,
  `eventTime` TIME NOT NULL,
  `guestCount` INT NOT NULL,
  `eventType` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL DEFAULT (''),
  `status` ENUM('Pending','Confirmed','Cancelled','Completed','Declined') NOT NULL DEFAULT 'Pending',
  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`eventId`),
  CONSTRAINT `fk_event_client`   FOREIGN KEY (`clientId`)   REFERENCES `client`   (`clientId`),
  CONSTRAINT `fk_event_ballroom` FOREIGN KEY (`ballroomId`) REFERENCES `ballroom` (`ballroomId`),
  CONSTRAINT `fk_event_menu`     FOREIGN KEY (`menuId`)     REFERENCES `menu`     (`menuId`),
  CONSTRAINT `fk_event_bar`      FOREIGN KEY (`barId`)      REFERENCES `bar`      (`barId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `eventService` (
  `eventId` INT NOT NULL,
  `serviceId` INT NOT NULL,
  PRIMARY KEY (`eventId`, `serviceId`),
  CONSTRAINT `fk_es_event`   FOREIGN KEY (`eventId`)   REFERENCES `event`    (`eventId`),
  CONSTRAINT `fk_es_service` FOREIGN KEY (`serviceId`) REFERENCES `services` (`serviceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `payment` (
  `paymentId` INT NOT NULL AUTO_INCREMENT,
  `eventId` INT NOT NULL,
  `totalPrice` DECIMAL(12,2) NOT NULL,
  `depositRequired` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `amountPaid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `paymentPlan` ENUM('Full','Installments') NOT NULL DEFAULT 'Full',
  `paymentMethod` VARCHAR(100) NOT NULL,
  `nextPaymentDue` DATE DEFAULT NULL,
  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`paymentId`),
  UNIQUE KEY `uq_payment_event` (`eventId`),
  CONSTRAINT `chk_payment_deposit` CHECK (`depositRequired` <= `totalPrice`),
  CONSTRAINT `chk_payment_paid`    CHECK (`amountPaid`      <= `totalPrice`),
  CONSTRAINT `fk_payment_event`    FOREIGN KEY (`eventId`) REFERENCES `event` (`eventId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data for `admin`
INSERT INTO `admin` (`id`, `email`, `password_hash`, `totp_secret`) VALUES
  (1, 'admin@test.com', '$2y$12$N5HrwEOjWrz70Ie5AfR12OETiFK4AjcVRS1AzTDOYPGbCOfpDtoAK', 'JBSWY3DPEHPK3PXP');

-- Data for `client`
INSERT INTO `client` (`clientId`, `firstName`, `lastName`, `email`, `phoneNumber`) VALUES
  (1, 'Sophie', 'Martinez', 'sophie.martinez@example.com', '514-555-8124');

-- Data for `ballroom`
INSERT INTO `ballroom` (`ballroomId`, `roomName`, `minCapacity`, `maxCapacity`, `sizeSqFt`, `picturesPath`, `arrangementPath`) VALUES
  (1, 'Princesse', 1, 125, 0, '', NULL),
  (2, 'Royal', 125, 225, 0, '', NULL),
  (3, 'Grand Salon', 225, 600, 0, '', NULL);

-- Data for `bar`
INSERT INTO `bar` (`barId`, `barType`, `pricePerPerson`, `openTime`, `closeTime`) VALUES
  (1, 'Free', 0, '17:00:00', '22:00:00'),
  (2, 'Paid', 15, '17:00:00', '22:00:00'),
  (3, 'Premium', 30, '16:00:00', '23:59:00');

-- Data for `services`
INSERT INTO `services` (`serviceId`, `serviceName`, `serviceEmail`, `servicePhone`, `serviceDescription`, `serviceType`) VALUES
  (1, 'Fleurs Élégance', 'contact@fleurselegance.com', '514-555-0101', 'Floral arrangements and centrepieces for all event types.', 'Florist'),
  (2, 'Photo Prestige', 'info@photoprestige.com', '514-555-0202', 'Professional event photography and videography services.', 'Photography'),
  (3, 'DJ Montréal Pro', 'booking@djmtlpro.com', '514-555-0303', 'DJ services with full sound system and lighting setup.', 'Entertainment'),
  (4, 'Déco Événements', 'hello@decoevenements.com', '514-555-0404', 'Custom event decoration, draping, and lighting design.', 'Decoration'),
  (5, 'Transport VIP', 'reservations@transportvip.com', '514-555-0505', 'Luxury shuttle and limousine service for guests.', 'Transportation');

-- Data for `foodCategory`
INSERT INTO `foodCategory` (`categoryId`, `categoryName`) VALUES
  (1, 'Entrée'),
  (2, 'Station Froide'),
  (3, 'Station Chaude'),
  (4, 'Soupe'),
  (5, 'Pâtes'),
  (6, 'Salade'),
  (7, 'Plat Principal'),
  (8, 'Dessert'),
  (9, 'Options Main'),
  (10, 'Hot Meats'),
  (11, 'Cold Antipasto'),
  (12, 'Hot Fish'),
  (13, 'Cold Fish'),
  (14, 'Options Buffet'),
  (15, 'Hot Vegetarian'),
  (16, 'Included'),
  (17, 'Standard Included'),
  (18, 'Sweet Options'),
  (19, 'Sandwich Options'),
  (20, 'Savoury Options');

-- Data for `foodItem`
INSERT INTO `foodItem` (`itemId`, `itemName`, `itemCategory`, `itemPrice`, `extraPrice`) VALUES
  (1, 'Canapé', 1, 0, NULL),
  (2, 'Antipasto', 1, 0, NULL),
  (3, 'Bar à pains', 2, 0, NULL),
  (4, 'Focaccia', 2, 0, NULL),
  (5, 'Viandes froides assorties', 2, 0, NULL),
  (6, 'Fromages assortis', 2, 0, NULL),
  (7, 'Mozzarella et tomates', 2, 0, NULL),
  (8, 'Olives assortis', 2, 0, NULL),
  (9, 'Saumon fumée / gravlax', 2, 0, NULL),
  (10, 'Tartare de saumon', 2, 0, NULL),
  (11, 'Salade de crabe', 2, 0, NULL),
  (12, 'Bruschetta', 2, 0, NULL),
  (13, 'Mini burger', 3, 0, NULL),
  (14, 'Mini arancini', 3, 0, NULL),
  (15, 'Mini boulettes de viande', 3, 0, NULL),
  (16, 'Ailes de poulet', 3, 0, NULL),
  (17, 'Brochette de poulet', 3, 0, NULL),
  (18, 'Champignon farci', 3, 0, NULL),
  (19, 'Brie chaud et noix', 3, 0, NULL),
  (20, 'Spanakopita', 3, 0, NULL),
  (21, 'Taboule / hummus', 3, 0, NULL),
  (22, 'Station risotto (chef)', 3, 0, NULL),
  (23, 'Poulet general tao', 3, 0, NULL),
  (24, 'Rouleaux de printemps', 3, 0, NULL),
  (25, 'Calmars frits', 3, 0, NULL),
  (26, 'Moules', 3, 0, NULL),
  (27, 'Crevettes wonton', 3, 0, NULL),
  (28, 'Soupe du jour', 4, 0, NULL),
  (29, 'Fazzoletti', 5, 0, NULL),
  (30, 'Manicotti', 5, 0, NULL),
  (31, 'Cannelloni', 5, 0, NULL),
  (32, 'Rotolo', 5, 0, NULL),
  (33, 'Cannelloni sicilienne', 5, 0, NULL),
  (34, 'Tortellone', 5, 0, NULL),
  (35, 'Fusili', 5, 0, NULL),
  (36, 'Cavatelli', 5, 0, NULL),
  (37, 'Penne', 5, 0, NULL),
  (38, 'Strozzapreti', 5, 0, NULL),
  (39, 'Sauce', 5, 0, NULL),
  (40, 'Mixte', 6, 0, NULL),
  (41, 'Roquette', 6, 0, NULL),
  (42, 'César', 6, 0, NULL),
  (43, 'Grecque', 6, 0, NULL),
  (44, 'Boeuf braisé', 7, 0, NULL),
  (45, 'Rib Steak', 7, 0, NULL),
  (46, 'Filet Mignon', 7, 0, NULL),
  (47, 'Scalopini de veau', 7, 0, NULL),
  (48, 'Rôti de veau', 7, 0, NULL),
  (49, 'Chop de veau', 7, 0, NULL),
  (50, 'Chop de porc nagano', 7, 0, NULL),
  (51, 'Poitrine de poulet', 7, 0, NULL),
  (52, 'Saumon', 7, 0, NULL),
  (53, 'Legumes de saison', 7, 0, NULL),
  (54, 'Pomme de terre', 7, 0, NULL),
  (55, 'Crevettes flambées', 9, 0, NULL),
  (56, 'Pieuvre grillée', 9, 0, NULL),
  (57, 'Sushi et sashimi', 9, 0, NULL),
  (58, 'Bar à huîtres crues', 9, 0, NULL),
  (59, 'Carré d agneau et steak tomahawk', 9, 0, NULL),
  (60, 'Italian Sausage', 10, 0, NULL),
  (61, 'Chorizo Sausage', 10, 0, NULL),
  (62, 'Loukanica Sausage', 10, 0, NULL),
  (63, 'Cacciatore Sausage', 10, 0, NULL),
  (64, 'Mini Pork Ribs', 10, 0, NULL),
  (65, 'Mini Burgers', 10, 0, NULL),
  (66, 'Mini Meatballs', 10, 0, NULL),
  (67, 'Chicken Skewers', 10, 0, NULL),
  (68, 'Beef Skewers', 10, 0, NULL),
  (69, 'Chicken Wings', 10, 0, NULL),
  (70, 'General Tao', 10, 0, NULL),
  (71, 'Orange Beef', 10, 0, NULL),
  (72, 'Mini Arangini', 10, 0, NULL),
  (73, 'Mini Quiche', 10, 0, NULL),
  (74, 'Braised Trippe', 10, 0, NULL),
  (75, 'Assorted Coldcuts', 11, 0, NULL),
  (76, 'Prosciutto on Morza', 11, 0, NULL),
  (77, 'Assorted Cheeses', 11, 0, NULL),
  (78, 'Mozzarina & Tomatoe', 11, 0, NULL),
  (79, 'Assorted Bruschetta', 11, 0, NULL),
  (80, 'Assorted Tapenade', 11, 0, NULL),
  (81, 'Assorted Olives', 11, 0, NULL),
  (82, 'Grilled Vegetables', 11, 0, NULL),
  (83, 'Canapes du Chef', 11, 0, 6),
  (84, 'Crudité & Dip', 11, 0, NULL),
  (85, 'Fried Calamari', 12, 0, NULL),
  (86, 'Stuffed Mussels', 12, 0, NULL),
  (87, 'Mussels Marinara', 12, 0, NULL),
  (88, 'Mussels Creamy Pesto', 12, 0, NULL),
  (89, 'Won Ton Shrimp', 12, 0, NULL),
  (90, 'Smoked Salmon', 13, 0, NULL),
  (91, 'Salmon Gravlax', 13, 0, NULL),
  (92, 'Salmon Tartare', 13, 0, NULL),
  (93, 'Crab Salad', 13, 0, NULL),
  (94, 'Flambée Shrimp', 14, 0, 7),
  (95, 'Flambée Scallop', 14, 0, 7),
  (96, 'Grilled Octopus', 14, 0, 10),
  (97, 'Seafood Paella', 14, 0, 6),
  (98, 'Caciocavallo on BBQ', 14, 0, 6),
  (99, 'Sushi and Sashimi', 14, 0, 7),
  (100, 'Seafood Salad', 14, 0, 5),
  (101, 'Shrimp Cocktail', 14, 0, 4),
  (102, 'Alaskan King Crab Legs', 14, 0, NULL),
  (103, 'Raw Oyster Bar', 14, 0, 7),
  (104, 'Rockafellar Oysters', 14, 0, 7),
  (105, 'Shrimp Rissois', 14, 0, 6),
  (106, 'Paste Cod', 14, 0, 6),
  (107, 'Buffla Cheese N Ricotta', 14, 0, 8),
  (108, 'Lamb Racks & Tomahawks', 14, 0, 15),
  (109, 'Stuffed Mushroom', 15, 0, NULL),
  (110, 'Mushroom and Polenta', 15, 0, NULL),
  (111, 'Warm Brie & Roasted Nuts', 15, 0, NULL),
  (112, 'Spring Rolls', 15, 0, NULL),
  (113, 'Eggrolls', 15, 0, NULL),
  (114, 'Peanut Butter Dumplings', 15, 0, NULL),
  (115, 'Parmesan Zucchini Sticks', 15, 0, NULL),
  (116, 'Ratatouille', 15, 0, NULL),
  (117, 'Spanakopita', 15, 0, NULL),
  (118, 'Tiropita', 15, 0, NULL),
  (119, 'Taramosalata / Tzatziki', 15, 0, NULL),
  (120, 'Dolmades', 15, 0, NULL),
  (121, 'Tabbouleh / Humus', 15, 0, NULL),
  (122, 'Mini Samosas', 15, 0, NULL),
  (123, 'Risotto Station (Chef)', 15, 0, NULL),
  (124, 'Assorted Bread Bar', 16, 0, NULL),
  (125, 'Assorted Focaccia', 16, 0, NULL),
  (126, 'Martini or Wine Station', 16, 0, NULL),
  (127, 'Fruits', 17, 0, NULL),
  (128, 'Pastry', 17, 0, NULL),
  (129, 'Pizza', 17, 0, NULL),
  (130, 'Sandwich of Choice', 17, 0, NULL),
  (131, 'Gelato Bar', 18, 0, 5),
  (132, 'Sundae Station', 18, 0, 5),
  (133, 'Nutella Bar', 18, 0, 5),
  (134, 'Cannolli Station', 18, 0, 5),
  (135, 'Assorted Cakes', 18, 0, 5),
  (136, 'Cake Pops', 18, 0, 5),
  (137, 'Cupcakes', 18, 0, 5),
  (138, 'Candy Bar', 18, 0, 5),
  (139, 'Chocolate & Porto Bar', 18, 0, 5),
  (140, 'Chocolate Fountain', 18, 0, 5),
  (141, 'Chocolate Fondue', 18, 0, 5),
  (142, 'Beaver Tale Station', 18, 0, 5),
  (143, 'Pasteis Natas', 18, 0, 5),
  (144, 'Assorted Baklava', 18, 0, 5),
  (145, 'Macaroons', 18, 0, 5),
  (146, 'Donuts', 18, 0, 5),
  (147, 'Assorted Biscotti', 18, 0, 5),
  (148, 'Assorted Pies', 18, 0, 5),
  (149, 'Popcorn Bar', 18, 0, 5),
  (150, 'Smores Bar', 18, 0, 5),
  (151, 'Chocolate Strawberries', 18, 0, 5),
  (152, 'Mr Puffs Bar', 18, 0, 5),
  (153, 'Smoke Meat', 19, 0, 7),
  (154, 'Taco Bar', 19, 0, 7),
  (155, 'Souvlaki', 19, 0, 7),
  (156, 'Sausage', 19, 0, 7),
  (157, 'Deli Meats', 19, 0, 7),
  (158, 'Hot Dogs', 19, 0, 7),
  (159, 'Pulled Pork', 19, 0, 7),
  (160, 'Mini Burgers', 19, 0, 7),
  (161, 'Porchetta', 19, 0, 7),
  (162, 'Sausage Rolls', 19, 0, 7),
  (163, 'Poutine', 20, 0, 5),
  (164, 'Onion Rings', 20, 0, 5),
  (165, 'Pasta Bar', 20, 0, 5),
  (166, 'Braised Trippe', 20, 0, 7),
  (167, 'Mussels', 20, 0, 5),
  (168, 'Shrimp Flambee', 20, 0, 5);

-- Data for `menu`
INSERT INTO `menu` (`menuId`, `menuName`, `pricePerPerson`) VALUES
  (1, 'Main Menu', 0),
  (2, 'Buffet', 0),
  (3, 'Midnight Table', 0);

-- Data for `menuFoodItem`
INSERT INTO `menuFoodItem` (`menuId`, `itemId`) VALUES
  (1, 1),
  (1, 2),
  (1, 3),
  (1, 4),
  (1, 5),
  (1, 6),
  (1, 7),
  (1, 8),
  (1, 9),
  (1, 10),
  (1, 11),
  (1, 12),
  (1, 13),
  (1, 14),
  (1, 15),
  (1, 16),
  (1, 17),
  (1, 18),
  (1, 19),
  (1, 20),
  (1, 21),
  (1, 22),
  (1, 23),
  (1, 24),
  (1, 25),
  (1, 26),
  (1, 27),
  (1, 28),
  (1, 29),
  (1, 30),
  (1, 31),
  (1, 32),
  (1, 33),
  (1, 34),
  (1, 35),
  (1, 36),
  (1, 37),
  (1, 38),
  (1, 39),
  (1, 40),
  (1, 41),
  (1, 42),
  (1, 43),
  (1, 44),
  (1, 45),
  (1, 46),
  (1, 47),
  (1, 48),
  (1, 49),
  (1, 50),
  (1, 51),
  (1, 52),
  (1, 53),
  (1, 54),
  (1, 55),
  (1, 56),
  (1, 57),
  (1, 58),
  (1, 59),
  (2, 60),
  (2, 61),
  (2, 62),
  (2, 63),
  (2, 64),
  (2, 65),
  (2, 66),
  (2, 67),
  (2, 68),
  (2, 69),
  (2, 70),
  (2, 71),
  (2, 72),
  (2, 73),
  (2, 74),
  (2, 75),
  (2, 76),
  (2, 77),
  (2, 78),
  (2, 79),
  (2, 80),
  (2, 81),
  (2, 82),
  (2, 83),
  (2, 84),
  (2, 85),
  (2, 86),
  (2, 87),
  (2, 88),
  (2, 89),
  (2, 90),
  (2, 91),
  (2, 92),
  (2, 93),
  (2, 94),
  (2, 95),
  (2, 96),
  (2, 97),
  (2, 98),
  (2, 99),
  (2, 100),
  (2, 101),
  (2, 102),
  (2, 103),
  (2, 104),
  (2, 105),
  (2, 106),
  (2, 107),
  (2, 108),
  (2, 109),
  (2, 110),
  (2, 111),
  (2, 112),
  (2, 113),
  (2, 114),
  (2, 115),
  (2, 116),
  (2, 117),
  (2, 118),
  (2, 119),
  (2, 120),
  (2, 121),
  (2, 122),
  (2, 123),
  (2, 124),
  (2, 125),
  (2, 126),
  (3, 127),
  (3, 128),
  (3, 129),
  (3, 130),
  (3, 131),
  (3, 132),
  (3, 133),
  (3, 134),
  (3, 135),
  (3, 136),
  (3, 137),
  (3, 138),
  (3, 139),
  (3, 140),
  (3, 141),
  (3, 142),
  (3, 143),
  (3, 144),
  (3, 145),
  (3, 146),
  (3, 147),
  (3, 148),
  (3, 149),
  (3, 150),
  (3, 151),
  (3, 152),
  (3, 153),
  (3, 154),
  (3, 155),
  (3, 156),
  (3, 157),
  (3, 158),
  (3, 159),
  (3, 160),
  (3, 161),
  (3, 162),
  (3, 163),
  (3, 164),
  (3, 165),
  (3, 166),
  (3, 167),
  (3, 168);

-- Data for `event`
INSERT INTO `event` (`eventId`, `clientId`, `ballroomId`, `menuId`, `barId`, `eventDate`, `eventTime`, `guestCount`, `eventType`, `description`, `status`, `createdAt`) VALUES
  (1, 1, 2, 1, 2, '2026-06-15', '18:00:00', 180, 'Wedding', 'Sample wedding reception for testing purposes.', 'Confirmed', '2026-05-11 18:55:26');

-- Data for `eventService`
INSERT INTO `eventService` (`eventId`, `serviceId`) VALUES
  (1, 1),
  (1, 2);

-- Data for `payment`
INSERT INTO `payment` (`paymentId`, `eventId`, `totalPrice`, `depositRequired`, `amountPaid`, `paymentPlan`, `paymentMethod`, `nextPaymentDue`, `createdAt`) VALUES
  (1, 1, 12500, 3125, 3125, 'Installments', 'Credit Card', '2026-09-15', '2026-05-11 18:55:26');

SET FOREIGN_KEY_CHECKS = 1;

CREATE OR REPLACE VIEW `v_event_summary` AS
SELECT
  e.eventId,
  e.eventDate,
  e.eventTime,
  e.status,
  e.eventType,
  e.guestCount,
  e.description,
  e.createdAt,

  CONCAT(c.firstName, ' ', c.lastName) AS clientName,
  c.email                              AS clientEmail,
  c.phoneNumber                        AS clientPhone,

  b.roomName                           AS ballroom,
  b.minCapacity,
  b.maxCapacity,
  b.arrangementPath,

  m.menuName,
  m.pricePerPerson                     AS menuPricePerPerson,

  ba.barType,
  ba.pricePerPerson                    AS barPricePerPerson,
  ba.openTime                          AS barOpen,
  ba.closeTime                         AS barClose,

  p.totalPrice,
  p.depositRequired,
  p.amountPaid,
  (p.totalPrice - p.amountPaid)        AS amountLeft,
  p.paymentPlan,
  p.paymentMethod,
  p.nextPaymentDue

FROM event e
JOIN  client   c  ON c.clientId   = e.clientId
JOIN  ballroom b  ON b.ballroomId = e.ballroomId
LEFT JOIN menu   m  ON m.menuId   = e.menuId
LEFT JOIN bar    ba ON ba.barId   = e.barId
LEFT JOIN payment p ON p.eventId  = e.eventId;


CREATE OR REPLACE VIEW `v_event_services` AS
SELECT
  es.eventId,
  s.serviceId,
  s.serviceName,
  s.serviceType,
  s.serviceEmail,
  s.servicePhone,
  s.serviceDescription
FROM eventService es
JOIN services s ON s.serviceId = es.serviceId;


CREATE OR REPLACE VIEW `v_menu_items` AS
SELECT
  m.menuId,
  m.menuName,
  m.pricePerPerson,
  fc.categoryName,
  fi.itemId,
  fi.itemName,
  fi.itemPrice,
  fi.extraPrice
FROM menu m
JOIN menuFoodItem mfi ON mfi.menuId    = m.menuId
JOIN foodItem     fi  ON fi.itemId     = mfi.itemId
JOIN foodCategory fc  ON fc.categoryId = fi.itemCategory
ORDER BY m.menuName, fc.categoryName, fi.itemName;