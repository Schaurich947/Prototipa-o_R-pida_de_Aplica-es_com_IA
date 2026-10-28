-- database/seed.sql
-- Dados de demonstração relativos à data atual. Senha de todas as contas: Demo@1234

INSERT INTO hotels (id, name, city, check_in_hour, check_out_hour, earliest_check_in_hour, latest_check_out_hour, early_free_hours, cleaning_buffer_hours) VALUES
    (1, 'HotelFlex Centro', 'São Paulo',     14, 12, 8, 18, 1, 1),
    (2, 'Pousada Mar Azul', 'Florianópolis', 15, 11, 9, 17, 0, 1);

INSERT INTO users (id, name, email, password_hash, role, hotel_id) VALUES
    (1, 'Administrador',      'admin@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'admin', NULL),
    (2, 'Carlos (Recepção)',  'recepcao.centro@hotelflex.test',  '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'staff', 1),
    (3, 'Marina (Recepção)',  'recepcao.marazul@hotelflex.test', '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'staff', 2),
    (4, 'Ana Souza',          'ana@hotelflex.test',              '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL),
    (5, 'Bruno Lima',         'bruno@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL),
    (6, 'Carla Mendes',       'carla@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL),
    (7, 'Diego Rocha',        'diego@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL),
    (8, 'Elisa Prado',        'elisa@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL);

INSERT INTO rooms (id, hotel_id, number, category, daily_rate_cents, hourly_rate_cents, status, cleaned_at) VALUES
    (1, 1, '101', 'Standard',        32000, 5000, 'clean',    TIMESTAMP(CURDATE(), '10:30:00')),
    (2, 1, '102', 'Luxo',            45000, 7000, 'cleaning', NULL),
    (3, 1, '103', 'Standard',        32000, 5000, 'released', TIMESTAMP(CURDATE(), '09:15:00')),
    (4, 1, '104', 'Suíte',           68000, 9000, 'occupied', NULL),
    (5, 1, '105', 'Standard',        32000, 5000, 'dirty',    NULL),
    (6, 1, '106', 'Luxo',            45000, 7000, 'clean',    TIMESTAMP(CURDATE(), '11:45:00')),
    (7, 2, '01',  'Vista Mar',       38000, 6000, 'occupied', NULL),
    (8, 2, '02',  'Chalé',           52000, 8000, 'clean',    TIMESTAMP(CURDATE(), '09:00:00'));

INSERT INTO reservations (id, code, user_id, room_id, check_in_date, nights, early_check_in_hours, late_checkout_hours, status) VALUES
    (1,  'HF-1001', 4, 1, CURDATE(),                     2, 0, 0, 'confirmed'),
    (2,  'HF-1002', 5, 2, CURDATE(),                     2, 0, 0, 'confirmed'),
    (3,  'HF-1003', 6, 3, CURDATE(),                     2, 0, 0, 'confirmed'),
    (4,  'HF-1004', 7, 4, CURDATE() - INTERVAL 1 DAY,    3, 0, 2, 'checked_in'),
    (5,  'HF-1005', 8, 5, CURDATE(),                     2, 0, 0, 'confirmed'),
    (6,  'HF-1006', 4, 7, CURDATE() + INTERVAL 20 DAY,   3, 0, 0, 'confirmed'),
    (7,  'HF-1007', 4, 8, CURDATE() - INTERVAL 60 DAY,   2, 1, 0, 'checked_out'),
    (8,  'HF-1008', 4, 6, CURDATE() - INTERVAL 30 DAY,   1, 0, 0, 'cancelled'),
    (9,  'HF-1009', 6, 2, CURDATE() - INTERVAL 3 DAY,    3, 0, 0, 'checked_out'),
    (10, 'HF-1010', 5, 7, CURDATE() - INTERVAL 1 DAY,    3, 0, 0, 'checked_in');

INSERT INTO service_requests (reservation_id, type, extra_hours, status, created_at, resolved_at) VALUES
    (1,  'early_checkin', 2, 'pending',  TIMESTAMP(CURDATE(), '10:40:00'), NULL),
    (4,  'late_checkout', 4, 'pending',  TIMESTAMP(CURDATE(), '09:05:00'), NULL),
    (10, 'late_checkout', 3, 'pending',  TIMESTAMP(CURDATE(), '08:30:00'), NULL),
    (3,  'early_checkin', 3, 'rejected', TIMESTAMP(CURDATE(), '08:50:00'), TIMESTAMP(CURDATE(), '09:20:00'));
