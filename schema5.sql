-- FIFTH VERSION
-- no circular dependency
-- small changes to data and types
-- =================================

CREATE DATABASE chateaubriand;
USE chateaubriand;
SET default_storage_engine = InnoDB; 


-- ADMIN
-- no change
-- ===========================================================================================
CREATE TABLE admin (
  adminId            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  email              VARCHAR(255)     NOT NULL,
  passwordHash       VARBINARY(60)    NOT NULL,
  twoFactorCode      CHAR(6)          NOT NULL,
  codeExpiration     DATETIME         NOT NULL,

  PRIMARY KEY (adminId),
  UNIQUE KEY uq_admin_email (email),
  CONSTRAINT chk_admin_2fa CHECK (twoFactorCode REGEXP '^[0-9]{6}$')
);

-- CLIENT
-- one row per client. No paymentId FK here 
-- payment is reached via client -> event -> payment to avoid the circular dependency in v4.
-- ===========================================================================================
CREATE TABLE client (
  clientId           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  firstName          VARCHAR(255)     NOT NULL,
  lastName           VARCHAR(255)     NOT NULL,
  email              VARCHAR(255)     NOT NULL,
  phoneNumber        VARCHAR(20)      NOT NULL,

  PRIMARY KEY (clientId),
  UNIQUE KEY uq_client_email (email)
);

-- BALLROOM
-- images kept as file paths
-- ===========================================================================================
CREATE TABLE ballroom (
  ballroomId         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  roomName           VARCHAR(255)     NOT NULL,
  minCapacity        SMALLINT UNSIGNED NOT NULL,
  maxCapacity        SMALLINT UNSIGNED NOT NULL,
  sizeSqFt           SMALLINT UNSIGNED NOT NULL,
  picturesPath       VARCHAR(500)     NOT NULL,
  arrangementPath    VARCHAR(500),

  PRIMARY KEY (ballroomId),
  UNIQUE KEY uq_ballroom_name (roomName),
  CONSTRAINT chk_ballroom_capacity CHECK (maxCapacity >= minCapacity)
);


-- FOOD CATEGORY
-- no change
-- ===========================================================================================
CREATE TABLE foodCategory (
  categoryId         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  categoryName       VARCHAR(255)     NOT NULL,

  PRIMARY KEY (categoryId),
  UNIQUE KEY uq_category_name (categoryName)
);


-- FOOD ITEM
-- no change
-- ===========================================================================================
CREATE TABLE foodItem (
  itemId             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  itemName           VARCHAR(255)     NOT NULL,
  itemPrice          DECIMAL(10,2)    NOT NULL,
  extraPrice         DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
  categoryId         INT UNSIGNED     NOT NULL,

  PRIMARY KEY (itemId),
  UNIQUE KEY uq_item_name (itemName),
  CONSTRAINT fk_fooditem_category
    FOREIGN KEY (categoryId) REFERENCES foodCategory (categoryId)
);


-- MENU
-- no change
-- ===========================================================================================
CREATE TABLE menu (
  menuId             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  menuName           VARCHAR(100)     NOT NULL,
  pricePerPerson     DECIMAL(10,2)    NOT NULL DEFAULT 0.00,

  PRIMARY KEY (menuId),
  UNIQUE KEY uq_menu_name (menuName)
);



-- MENU FOOD ITEM  (junction)
-- no change
-- ===========================================================================================
CREATE TABLE menuFoodItem (
  menuId             INT UNSIGNED     NOT NULL,
  itemId             INT UNSIGNED     NOT NULL,

  PRIMARY KEY (menuId, itemId),
  CONSTRAINT fk_mfi_menu
    FOREIGN KEY (menuId)   REFERENCES menu     (menuId),
  CONSTRAINT fk_mfi_item
    FOREIGN KEY (itemId)   REFERENCES foodItem (itemId)
);



-- BAR
-- ENUM enforces the three allowed types at the DB level.
-- ===========================================================================================
CREATE TABLE bar (
  barId              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  barType            ENUM('Free','Paid','Premium') NOT NULL,
  pricePerPerson     DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
  openTime           TIME,
  closeTime          TIME,

  PRIMARY KEY (barId),
  CONSTRAINT chk_bar_hours CHECK (
    closeTime IS NULL OR openTime IS NULL OR closeTime > openTime
  )
);


-- SERVICES
-- no change
-- ===========================================================================================
CREATE TABLE services (
  serviceId          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  serviceName        VARCHAR(100)     NOT NULL,
  serviceEmail       VARCHAR(255)     NOT NULL,
  servicePhone       VARCHAR(20)      NOT NULL,
  serviceDescription VARCHAR(500)     NOT NULL,
  serviceType        VARCHAR(50)      NOT NULL,

  PRIMARY KEY (serviceId)
);


-- EVENT
-- Central entity. menuId and barId are nullable, a client may decline both.
-- Status is an ENUM for DB-level integrity.
-- ===========================================================================================
CREATE TABLE event (
  eventId            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  clientId           INT UNSIGNED     NOT NULL,
  ballroomId         INT UNSIGNED     NOT NULL,
  menuId             INT UNSIGNED,
  barId              INT UNSIGNED,
  eventDate          DATE             NOT NULL,
  eventTime          TIME             NOT NULL,
  guestCount         SMALLINT UNSIGNED NOT NULL,
  eventType          VARCHAR(50)      NOT NULL,
  description        VARCHAR(500)     NOT NULL,
  status             ENUM(
                       'Pending',
                       'Confirmed',
                       'Cancelled',
                       'Completed',
                       'Declined'
                     )                NOT NULL DEFAULT 'Pending',

  PRIMARY KEY (eventId),
  CONSTRAINT fk_event_client
    FOREIGN KEY (clientId)    REFERENCES client   (clientId),
  CONSTRAINT fk_event_ballroom
    FOREIGN KEY (ballroomId)  REFERENCES ballroom (ballroomId),
  CONSTRAINT fk_event_menu
    FOREIGN KEY (menuId)      REFERENCES menu     (menuId),
  CONSTRAINT fk_event_bar
    FOREIGN KEY (barId)       REFERENCES bar      (barId)
);


-- EVENT SERVICE  (junction)
-- no change
-- ===========================================================================================
CREATE TABLE eventService (
  eventId            INT UNSIGNED     NOT NULL,
  serviceId          INT UNSIGNED     NOT NULL,

  PRIMARY KEY (eventId, serviceId),
  CONSTRAINT fk_es_event
    FOREIGN KEY (eventId)    REFERENCES event    (eventId),
  CONSTRAINT fk_es_service
    FOREIGN KEY (serviceId)  REFERENCES services (serviceId)
);


-- PAYMENT
-- amountLeft is derived (totalPrice - amountPaid) so we don't store it 
-- ===========================================================================================
CREATE TABLE payment (
  paymentId          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  eventId            INT UNSIGNED     NOT NULL UNIQUE,
  totalPrice         DECIMAL(10,2)    NOT NULL,
  depositRequired    DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
  amountPaid         DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
  paymentPlan        ENUM('Full','Installments') NOT NULL DEFAULT 'Full',
  paymentMethod      VARCHAR(50)      NOT NULL,
  nextPaymentDue     DATE,

  PRIMARY KEY (paymentId),
  CONSTRAINT fk_payment_event
    FOREIGN KEY (eventId) REFERENCES event (eventId),
  CONSTRAINT chk_payment_deposit
    CHECK (depositRequired <= totalPrice),
  CONSTRAINT chk_payment_paid
    CHECK (amountPaid <= totalPrice)
);