-- VIEWS
-- replace repetitive multi-join queries in the app and dashboard.
-- =================================================================

-- Full event summary — one row per event, everything the
-- admin needs at a glance.
CREATE OR REPLACE VIEW v_event_summary AS
SELECT
  e.eventId,
  e.eventDate,
  e.eventTime,
  e.status,
  e.eventType,
  e.guestCount,
  e.description,

  CONCAT(c.firstName, ' ', c.lastName)  AS clientName,
  c.email                               AS clientEmail,
  c.phoneNumber                         AS clientPhone,

  b.roomName                            AS ballroom,
  b.minCapacity,
  b.maxCapacity,

  m.menuName,
  m.pricePerPerson                      AS menuPricePerPerson,

  ba.barType,
  ba.pricePerPerson                     AS barPricePerPerson,
  ba.openTime                           AS barOpen,
  ba.closeTime                          AS barClose,

  p.totalPrice,
  p.depositRequired,
  p.amountPaid,
  (p.totalPrice - p.amountPaid)         AS amountLeft,
  p.paymentPlan,
  p.paymentMethod,
  p.nextPaymentDue

FROM event e
JOIN client   c  ON c.clientId   = e.clientId
JOIN ballroom b  ON b.ballroomId = e.ballroomId
LEFT JOIN menu   m  ON m.menuId  = e.menuId
LEFT JOIN bar    ba ON ba.barId  = e.barId
LEFT JOIN payment p ON p.eventId = e.eventId;


-- Per-event services — used on the event detail page.
CREATE OR REPLACE VIEW v_event_services AS
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


-- Menu with full item breakdown — useful for menu management.
CREATE OR REPLACE VIEW v_menu_items AS
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
JOIN menuFoodItem mfi ON mfi.menuId   = m.menuId
JOIN foodItem    fi  ON fi.itemId    = mfi.itemId
JOIN foodCategory fc  ON fc.categoryId = fi.categoryId
ORDER BY m.menuName, fc.categoryName, fi.itemName;

-- SELECT * FROM v_event_summary;
-- SELECT * FROM v_event_services;
-- SELECT * FROM v_menu_items;