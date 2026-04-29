-- fourth version

-- DROP DATABASE sysdev;
-- CREATE DATABASE sysdev;
-- USE sysdev;

SET default_storage_engine = InnoDB; 
	
CREATE TABLE admin (
    adminId              INT            AUTO_INCREMENT PRIMARY KEY,
    email                VARCHAR(255)   NOT NULL UNIQUE,
    password             VARBINARY(60)  NOT NULL,
    twoFactorCode        CHAR(6)        NOT NULL 
									    CHECK (twoFactorCode REGEXP '^[0-9]{6}$'),
    factorCodeExpiration DATETIME       NOT NULL
);

CREATE TABLE ballroom (
    ballroomId   INT            AUTO_INCREMENT PRIMARY KEY,
    roomName     VARCHAR(255)   NOT NULL UNIQUE,
    minCapacity  SMALLINT       NOT NULL,
    maxCapacity  SMALLINT       NOT NULL,
    sizeSqFt     SMALLINT       NOT NULL,
    roomPictures LONGBLOB       NOT NULL, 
	tableArrangement LONGBLOB   NOT NULL
);

CREATE TABLE menu (
    menuId         INT           AUTO_INCREMENT PRIMARY KEY,
    menuName       VARCHAR(100)  NOT NULL UNIQUE,
    pricePerPerson DECIMAL(10,2) NOT NULL DEFAULT 0.00
);

CREATE TABLE bar (
    barId            INT           AUTO_INCREMENT PRIMARY KEY,
    barType          VARCHAR(50)   NOT NULL CHECK (barType IN ('Free', 'Paid', 'Premium')),
    pricePerPerson   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    barOpenTime 	 TIME,		  
    barCloseTime     TIME
);

CREATE TABLE event (
    eventId          INT           AUTO_INCREMENT PRIMARY KEY,
    ballroomId       INT           NOT NULL,
    menuId           INT           NOT NULL,  
    barId            INT           NOT NULL,	
    eventDate        DATE          NOT NULL,
    eventTime        TIME          NOT NULL,
    guestQuantity    SMALLINT      NOT NULL,
    eventType 		 VARCHAR(50)   NOT NULL,
    eventDescription VARCHAR(255)  NOT NULL,
    eventStatus      VARCHAR(50)   NOT NULL DEFAULT 'Pending'
                                   CHECK (eventStatus IN ('Confirmed', 'Pending', 'Done', 'Cancelled')),
    FOREIGN KEY (ballroomId) REFERENCES ballroom(ballroomId),
    FOREIGN KEY (menuId)     REFERENCES menu(menuId),
    FOREIGN KEY (barId)      REFERENCES bar(barId)
);

CREATE TABLE payment (
	paymentId        INT              AUTO_INCREMENT PRIMARY KEY,
    eventId          INT              NOT NULL, 
    amountPaid       DECIMAL(10,2)    NOT NULL,
    amountLeft       DECIMAL(10,2)    NOT NULL,
    paymentDate      DATE             NOT NULL,
	totalPrice       DECIMAL(10,2)    NOT NULL,
	depositAmount    DECIMAL(10,2)    NOT NULL,
    depositPaid      DECIMAL(10,2)    NOT NULL,
    paymentPlan      VARCHAR(50)      NOT NULL
								      CHECK (paymentPlan IN ('Full', 'Installments')),		
    paymentMethod    VARCHAR(50)      NOT NULL 						 
								      CHECK (paymentMethod IN ('Cash' ,'E-transfer', 'Debit', 'Cheque')),
    FOREIGN KEY (eventId) REFERENCES event(eventId)
);

CREATE TABLE client (
    clientId    INT           AUTO_INCREMENT PRIMARY KEY,
    firstName   VARCHAR(255)  NOT NULL,
    lastName    VARCHAR(255)  NOT NULL,
    email       VARCHAR(255)  NOT NULL UNIQUE,
    phoneNumber VARCHAR(20)   NOT NULL,
    paymentId   INT  		  NOT NULL,
    FOREIGN KEY (paymentId)  REFERENCES payment(paymentId)
);

CREATE TABLE foodCategory (
    categoryId   INT          	AUTO_INCREMENT PRIMARY KEY,
    categoryName VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE foodItem (
    itemId       INT            AUTO_INCREMENT PRIMARY KEY,
    itemName     VARCHAR(255)   NOT NULL UNIQUE,
    itemPrice    DECIMAL(10,2)  NOT NULL,			
    extraPrice   DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    itemCategory SMALLINT            NOT NULL,
    FOREIGN KEY (itemCategory) REFERENCES foodCategory(categoryId)
);

CREATE TABLE menuFoodItem (
    menuId INT  NOT NULL,
    itemId INT  NOT NULL,
    PRIMARY KEY (menuId, itemId),
    FOREIGN KEY (menuId) REFERENCES menu(menuId),
    FOREIGN KEY (itemId) REFERENCES foodItem(itemId)
);

CREATE TABLE services (
    serviceId          INT          AUTO_INCREMENT PRIMARY KEY,
    serviceName        VARCHAR(50)  NOT NULL,
    serviceEmail       VARCHAR(255) NOT NULL,
    servicePhoneNumber VARCHAR(20)  NOT NULL,
    serviceDescription VARCHAR(255) NOT NULL,
    serviceType        VARCHAR(50)  NOT NULL
);

CREATE TABLE eventService (
    eventId   INT  NOT NULL,
    serviceId INT  NOT NULL,
    PRIMARY KEY (eventId, serviceId),
    FOREIGN KEY (eventId)   REFERENCES event(eventId),
    FOREIGN KEY (serviceId) REFERENCES services(serviceId)
);

ALTER TABLE event
ADD COLUMN clientId INT,
ADD CONSTRAINT client_fk
FOREIGN KEY (clientId)
REFERENCES client(clientId);

SELECT * FROM event;
