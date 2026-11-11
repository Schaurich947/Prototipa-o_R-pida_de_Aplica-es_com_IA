-- database/seed.sql
-- Dados de demonstração relativos à data atual. Senha de todas as contas: Demo@1234
-- O arquivo storage/receipts/exemplo.pdf é criado pelo bin/setup-database.php.

INSERT INTO hotels (id, name, city, check_in_hour, check_out_hour, earliest_check_in_hour, latest_check_out_hour, early_free_hours, cleaning_buffer_hours, pix_key, whatsapp_phone, payment_deadline_hours, free_cancellation_hours, breakfast_price_cents, breakfast_start_hour, breakfast_end_hour, breakfast_daily_capacity, breakfast_walk_in_price_cents, breakfast_seats_per_room) VALUES
    (1, 'HotelFlex Centro', 'São Paulo',     14, 12, 8, 18, 1, 1, 'financeiro@hotelflexcentro.com.br', '5511940001000', 24, 48, 4500, 6, 10, 40, 5500, 2),
    (2, 'Pousada Mar Azul', 'Florianópolis', 15, 11, 9, 17, 0, 1, '12.345.678/0001-90',               '5548991002000', 12, 72, 3800, 7, 10, 20, 4800, 2);

INSERT INTO users (id, name, email, password_hash, role, hotel_id, whatsapp_phone, whatsapp_opt_in_at) VALUES
    (1, 'Administrador',      'admin@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'admin', NULL, NULL, NULL),
    (2, 'Carlos (Recepção)',  'recepcao.centro@hotelflex.test',  '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'staff', 1,    NULL, NULL),
    (3, 'Marina (Recepção)',  'recepcao.marazul@hotelflex.test', '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'staff', 2,    NULL, NULL),
    (4, 'Ana Souza',          'ana@hotelflex.test',              '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL, '5511987654321', NOW() - INTERVAL 30 DAY),
    (5, 'Bruno Lima',         'bruno@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL, '5521998765432', NOW() - INTERVAL 5 DAY),
    (6, 'Carla Mendes',       'carla@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL, NULL, NULL),
    (7, 'Diego Rocha',        'diego@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL, '5531991234567', NOW() - INTERVAL 2 DAY),
    (8, 'Elisa Prado',        'elisa@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL, NULL, NULL);

INSERT INTO rooms (id, hotel_id, number, category, daily_rate_cents, hourly_rate_cents, status, cleaned_at) VALUES
    (1, 1, '101', 'Standard',  32000, 5000, 'clean',    TIMESTAMP(CURDATE(), '10:30:00')),
    (2, 1, '102', 'Luxo',      45000, 7000, 'cleaning', NULL),
    (3, 1, '103', 'Standard',  32000, 5000, 'released', TIMESTAMP(CURDATE(), '09:15:00')),
    (4, 1, '104', 'Suíte',     68000, 9000, 'occupied', NULL),
    (5, 1, '105', 'Standard',  32000, 5000, 'dirty',    NULL),
    (6, 1, '106', 'Luxo',      45000, 7000, 'clean',    TIMESTAMP(CURDATE(), '11:45:00')),
    (7, 2, '01',  'Vista Mar', 38000, 6000, 'occupied', NULL),
    (8, 2, '02',  'Chalé',     52000, 8000, 'clean',    TIMESTAMP(CURDATE(), '09:00:00'));

INSERT INTO reservations (id, code, user_id, room_id, check_in_date, nights, early_check_in_hours, late_checkout_hours, status) VALUES
    (1,  'HF-1001', 4, 1, CURDATE(),                   2, 0, 0, 'confirmed'),
    (2,  'HF-1002', 5, 2, CURDATE(),                   2, 0, 0, 'pending_payment'),
    (3,  'HF-1003', 6, 3, CURDATE(),                   2, 0, 0, 'confirmed'),
    (4,  'HF-1004', 7, 4, CURDATE() - INTERVAL 1 DAY,  3, 0, 2, 'checked_in'),
    (5,  'HF-1005', 8, 5, CURDATE(),                   2, 0, 0, 'pending_payment'),
    (6,  'HF-1006', 4, 7, CURDATE() + INTERVAL 20 DAY, 3, 0, 0, 'confirmed'),
    (7,  'HF-1007', 4, 8, CURDATE() - INTERVAL 60 DAY, 2, 1, 0, 'checked_out'),
    (8,  'HF-1008', 4, 6, CURDATE() - INTERVAL 30 DAY, 1, 0, 0, 'cancelled'),
    (9,  'HF-1009', 6, 2, CURDATE() - INTERVAL 3 DAY,  3, 0, 0, 'checked_out'),
    (10, 'HF-1010', 5, 7, CURDATE() - INTERVAL 1 DAY,  3, 0, 0, 'checked_in'),
    (11, 'HF-1011', 8, 8, CURDATE() + INTERVAL 10 DAY, 2, 0, 0, 'expired');

INSERT INTO charges (reservation_id, kind, description, amount_cents, status, due_at, receipt_path, receipt_original_name, receipt_mime, rejection_reason, created_at, reviewed_at) VALUES
    (1,  'booking', 'Diárias da reserva',            64000,  'paid',         NOW() + INTERVAL 1 DAY,  'exemplo.pdf', 'pix-ana.pdf',    'application/pdf', NULL, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
    (2,  'booking', 'Diárias da reserva',            90000,  'under_review', NOW() + INTERVAL 20 HOUR, 'exemplo.pdf', 'pix-bruno.pdf', 'application/pdf', NULL, NOW() - INTERVAL 4 HOUR, NULL),
    (3,  'booking', 'Diárias da reserva',            64000,  'paid',         NOW(),                    'exemplo.pdf', 'pix-carla.pdf', 'application/pdf', NULL, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
    (4,  'booking', 'Diárias da reserva',            204000, 'paid',         NOW(),                    'exemplo.pdf', 'pix-diego.pdf', 'application/pdf', NULL, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
    (4,  'extra',   'Saída tardia: +2 horas',        18000,  'pending',      NOW() + INTERVAL 1 DAY,  NULL, NULL, NULL, NULL, NOW() - INTERVAL 1 DAY, NULL),
    (5,  'booking', 'Diárias da reserva',            64000,  'pending',      NOW() + INTERVAL 20 HOUR, NULL, NULL, NULL, 'O valor do comprovante não confere com o da reserva.', NOW() - INTERVAL 4 HOUR, NULL),
    (6,  'booking', 'Diárias da reserva',            114000, 'paid',         NOW(),                    'exemplo.pdf', 'pix-ana-2.pdf', 'application/pdf', NULL, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY),
    (7,  'booking', 'Diárias e entrada antecipada',  112000, 'paid',         NOW(),                    'exemplo.pdf', 'pix-ana-3.pdf', 'application/pdf', NULL, NOW() - INTERVAL 62 DAY, NOW() - INTERVAL 62 DAY),
    (8,  'booking', 'Diárias da reserva',            45000,  'cancelled',    NOW(),                    NULL, NULL, NULL, NULL, NOW() - INTERVAL 35 DAY, NULL),
    (9,  'booking', 'Diárias da reserva',            135000, 'paid',         NOW(),                    'exemplo.pdf', 'pix-carla-2.pdf', 'application/pdf', NULL, NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY),
    (10, 'booking', 'Diárias da reserva',            114000, 'paid',         NOW(),                    'exemplo.pdf', 'pix-bruno-2.pdf', 'application/pdf', NULL, NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY),
    (11, 'booking', 'Diárias da reserva',            104000, 'cancelled',    NOW() - INTERVAL 1 DAY,  NULL, NULL, NULL, NULL, NOW() - INTERVAL 2 DAY, NULL);

INSERT INTO service_requests (reservation_id, type, extra_hours, status, created_at, resolved_at) VALUES
    (1,  'early_checkin', 2, 'pending',  TIMESTAMP(CURDATE(), '10:40:00'), NULL),
    (4,  'late_checkout', 4, 'pending',  TIMESTAMP(CURDATE(), '09:05:00'), NULL),
    (10, 'late_checkout', 3, 'pending',  TIMESTAMP(CURDATE(), '08:30:00'), NULL),
    (3,  'early_checkin', 3, 'rejected', TIMESTAMP(CURDATE(), '08:50:00'), TIMESTAMP(CURDATE(), '09:20:00'));

INSERT INTO notifications (reservation_id, user_id, channel, recipient, subject, body, status, attempts, sent_at, created_at) VALUES
    (1, 4, 'email',    'ana@hotelflex.test', 'Reserva HF-1001 criada · aguardando pagamento', 'Sua reserva foi criada. Pague via Pix e envie o comprovante.', 'sent', 1, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
    (1, 4, 'whatsapp', '5511987654321',      'Reserva HF-1001 criada · aguardando pagamento', 'Sua reserva foi criada. Pague via Pix e envie o comprovante.', 'sent', 1, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
    (1, 4, 'email',    'ana@hotelflex.test', 'Reserva HF-1001 confirmada', 'Recebemos seu pagamento. Sua reserva está confirmada.', 'sent', 1, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
    (1, 4, 'whatsapp', '5511987654321',      'Reserva HF-1001 confirmada', 'Recebemos seu pagamento. Sua reserva está confirmada.', 'sent', 1, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY);

INSERT INTO breakfast_bookings (code, user_id, hotel_id, breakfast_date, people, unit_price_cents, discount_percent, total_cents, payment_option, status, is_paid, paid_method, due_at, receipt_path, receipt_original_name, receipt_mime, created_at) VALUES
    ('CF-2001', 6, 1, CURDATE() + INTERVAL 1 DAY, 2, 4500, 5, 8550, 'pix',  'under_review', 0, NULL, NOW() + INTERVAL 12 HOUR, 'exemplo.pdf', 'pix-cafe-carla.pdf', 'application/pdf', NOW() - INTERVAL 1 HOUR),
    ('CF-2002', 8, 1, CURDATE() + INTERVAL 1 DAY, 3, 4500, 0, 13500, 'desk', 'confirmed',    0, NULL, NULL, NULL, NULL, NULL, NOW() - INTERVAL 2 HOUR),
    ('CF-2003', 4, 2, CURDATE() - INTERVAL 7 DAY, 1, 3800, 5, 3610,  'pix',  'served',       1, 'pix_receipt', NULL, 'exemplo.pdf', 'pix-cafe-ana.pdf', 'application/pdf', NOW() - INTERVAL 8 DAY);
