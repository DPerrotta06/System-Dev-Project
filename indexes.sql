-- INDEXES
-- for the most common dashboard filter/search patterns.
-- ============================================================

-- Upcoming events ordered by date
CREATE INDEX idx_event_date       ON event (eventDate);

-- Filter events by status (Pending, Confirmed, etc.)
CREATE INDEX idx_event_status     ON event (status);

-- Look up all events for a client
CREATE INDEX idx_event_client     ON event (clientId);

-- Search clients by name
CREATE INDEX idx_client_name      ON client (lastName, firstName);

-- Payments overdue (filter by nextPaymentDue)
CREATE INDEX idx_payment_due      ON payment (nextPaymentDue);