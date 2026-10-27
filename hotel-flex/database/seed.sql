-- database/seed.sql
-- Dados de demonstração relativos à data atual, para o painel sempre ter casos de teste.

DELETE FROM service_requests;
DELETE FROM reservations;
DELETE FROM rooms;

INSERT INTO rooms (id, number, category, daily_rate_cents, hourly_rate_cents, checkout_limit_hour, status, cleaned_at) VALUES
    (1, '101', 'Standard', 32000, 5000, 18, 'clean',    TIMESTAMP(CURDATE(), '10:30:00')),
    (2, '102', 'Luxo',     45000, 7000, 15, 'cleaning', NULL),
    (3, '103', 'Standard', 32000, 5000, 18, 'released', TIMESTAMP(CURDATE(), '09:15:00')),
    (4, '104', 'Suíte',    68000, 9000, 18, 'occupied', NULL),
    (5, '105', 'Standard', 32000, 5000, 18, 'dirty',    NULL),
    (6, '106', 'Luxo',     45000, 7000, 18, 'clean',    TIMESTAMP(CURDATE(), '11:45:00'));

INSERT INTO reservations (id, code, guest_name, room_id, check_in_date, nights, late_checkout_hours) VALUES
    (1, 'HF-1001', 'Ana Souza',    1, CURDATE(), 2, 0),
    (2, 'HF-1002', 'Bruno Lima',   2, CURDATE(), 2, 0),
    (3, 'HF-1003', 'Carla Mendes', 3, CURDATE(), 2, 0),
    (4, 'HF-1004', 'Diego Rocha',  4, CURDATE() - INTERVAL 1 DAY, 3, 2),
    (5, 'HF-1005', 'Elisa Prado',  5, CURDATE(), 2, 0);

INSERT INTO service_requests (reservation_id, type, extra_hours, status, created_at, resolved_at) VALUES
    (1, 'early_checkin', 0, 'pending',  TIMESTAMP(CURDATE(), '10:40:00'), NULL),
    (4, 'late_checkout', 4, 'pending',  TIMESTAMP(CURDATE(), '09:05:00'), NULL),
    (3, 'early_checkin', 0, 'approved', TIMESTAMP(CURDATE(), '08:50:00'), TIMESTAMP(CURDATE(), '09:20:00'));
