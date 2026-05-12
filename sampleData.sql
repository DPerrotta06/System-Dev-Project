-- SAMPLE DATA 
-- for testing
-- ============================================================

USE chateaubriand; 

INSERT INTO foodCategory (categoryName) VALUES
    ('Hot Fish'),
    ('Hot Meats'),
    ('Hot Vegetarian');

INSERT INTO foodItem (itemName, itemPrice, extraPrice, categoryId) VALUES
    ('Fried Calamari',      3.99, 0.00, 1),
    ('Won Ton Shrimp',      3.99, 0.00, 1),
    ('Crispy Salmon',       5.49, 0.00, 1),
    ('General Tao',         4.99, 0.00, 2),
    ('Orange Beef',         4.99, 0.00, 2),
    ('Honey Garlic Ribs',   6.99, 0.00, 2),
    ('Spring Rolls',        5.99, 0.00, 3),
    ('Egg Rolls',           5.99, 0.00, 3),
    ('Vegetable Dumplings', 4.49, 0.00, 3);

INSERT INTO menu (menuName, pricePerPerson) VALUES
    ('Classic Banquet',  35.99),
    ('Seafood Deluxe',   49.99),
    ('Vegetarian Feast', 29.99);

INSERT INTO menuFoodItem (menuId, itemId) VALUES
    (1, 4),
    (1, 5),
    (1, 7),
    (1, 8),
    (2, 1),
    (2, 2),
    (2, 3),
    (3, 7),
    (3, 8),
    (3, 9);

INSERT INTO bar (barType, openTime, closeTime, pricePerPerson) VALUES
    ('Free',  '17:00:00', '18:00:00', 0.00),
    ('Paid',  '16:00:00', '19:00:00',  12.99),
    ('Premium', '15:00:00', '20:00:00', 24.99);

INSERT INTO admin (email, passwordHash, twoFactorCode, codeExpiration) VALUES
    ('admin@lechateaubriand.com',
     UNHEX(SHA2('AdminPassword123!', 256)),
     '482910',
     '2026-04-01 09:15:00'),
    ('manager@lechateaubriand.com',
     UNHEX(SHA2('ManagerPassword456!', 256)),
     '371628',
     '2026-04-01 10:30:00');

INSERT INTO client (firstName, lastName, email, phoneNumber) VALUES
    ('Alice', 'Martin',   'alice.martin@email.com',   '613-555-0101'),
    ('Bob',   'Tremblay', 'bob.tremblay@email.com',   '613-555-0202'),
    ('Carla', 'Singh',    'carla.singh@email.com',    '613-555-0303'),
    ('David', 'Nguyen',   'david.nguyen@email.com',   '613-555-0404'),
    ('Eva',   'Okafor',   'eva.okafor@email.com',     '613-555-0505'),
    ('Jia-Yu Joy', 'Ho', 'jooywho@gmail.com', '514-971-2768'),
    ('Fairouz', 'Aly', 'fairouzaly73@gmail.com', '514-662-2687');
    
INSERT INTO ballroom (roomName, minCapacity, maxCapacity, sizeSqFt, picturesPath, arrangementPath) VALUES
    ('Grand Salon', 50,  300, 4500, '/images/ballrooms/grandsalon/', '/images/arrangements/grandsalon.jpg'),
    ('Princesse',   20,  100, 1800, '/images/ballrooms/princesse/', '/images/arrangements/princesse.jpg' ),
    ('Royal',       30,  150, 2500, '/images/ballrooms/royal/', '/images/arrangements/royal.jpg');

INSERT INTO services (serviceName, serviceEmail, servicePhone, serviceDescription, serviceType) VALUES
    ('Elite Catering',   'elite@catering.com',     '613-555-1001', 'Full-service catering for all event sizes.',    'Catering'),
    ('Bloom Florals',    'hello@bloomflorals.com', '613-555-1002', 'Custom floral arrangements and centrepieces.',  'Decoration'),
    ('SoundWave DJ',     'book@soundwavedj.com',   '613-555-1003', 'Professional DJ and sound system rental.',     'Entertainment'),
    ('Crystal AV',       'info@crystalav.com',     '613-555-1004', 'Audio/visual equipment and technical support.', 'AV'),
    ('Pure Photography', 'shoot@purephoto.com',    '613-555-1005', 'Event photography and same-day photo booth.',  'Photography');

INSERT INTO event (
	clientId, ballroomId, menuId, barId, 
    eventDate, eventTime, guestCount, 
    eventType, description, status
) VALUES
    (1, 1, 2, 3, 
     '2026-06-14', '18:00:00', 200, 
     'Wedding', 'Wedding reception with seafood menu and premium bar.', 'Pending'),
    (2, 2, 1, 2, 
     '2026-07-22', '19:00:00', 80, 
     'Gala', 'Annual corporate gala with AV presentation.', 'Pending'),
    (3, 3, 3, 1, 
     '2026-05-10', '17:00:00', 60, 
     'Birthday', 'Milestone birthday party with DJ and vegetarian menu.', 'Pending'),
    (4, 1, 1, 2,
     '2026-04-05', '18:30:00', 120, 
     'Fundraiser', 'Spring fundraiser dinner — cancelled by client.', 'Cancelled'),
    (5, 2, 3, 1,
     '2026-08-30', '14:00:00', 40, 
     'Baby shower', 'Baby shower with photo booth and vegetarian menu.', 'Pending'),
	 (6, 2, 3, 1,
     '2026-08-30', '14:00:00', 40, 'Lion Dance', 'Lion Dance for Joy wedding', 'Pending'),
     (7, 2, 3, 1,
     '2026-08-30', '14:00:00', 40, 
     'Birthday', 'Baby birthday with photo booth and vegetarian menu.', 'Pending');

INSERT INTO eventService (eventId, serviceId) VALUES
    (1, 2),
    (1, 5),
    (2, 4),
    (3, 3),
    (4, 1),
    (5, 5);
    
INSERT INTO payment (eventId, totalPrice, depositRequired, amountPaid, paymentPlan, paymentMethod,
nextPaymentDue) VALUES
	(1, 70000.00, 1000.00, 1950.00, 'Installments', 'Debit', '2024-05-23'),
	(2, 60000.00, 2000.00, 1850.00, 'Full', 'Cash', '2024-06-23'),
	(3, 50000.00, 3000.00, 1750.00, 'Installments', 'e-transfer', '2024-07-23'),
	(4, 40000.00, 4000.00, 1650.00, 'Full', 'Cash', '2024-08-23'),
	(5, 30000.00, 5000.00, 1550.00, 'Installments', 'Debit', '2024-09-23'),
	(6, 20000.00, 6000.00, 1450.00, 'Full', 'Cash', '2024-10-23'),
	(7, 10000.00, 7000.00, 1350.00, 'Installments', 'Debit', '2024-11-23');