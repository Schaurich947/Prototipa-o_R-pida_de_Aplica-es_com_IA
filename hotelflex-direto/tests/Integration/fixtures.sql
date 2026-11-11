-- tests/Integration/fixtures.sql
-- Base fixa dos testes de integração: hotéis, quartos e contas. Sem reservas (cada teste cria as suas).
-- Senha de todas as contas: Demo@1234

INSERT INTO hotels (id, name, city, check_in_hour, check_out_hour, earliest_check_in_hour, latest_check_out_hour, early_free_hours, cleaning_buffer_hours, pix_key, whatsapp_phone, payment_deadline_hours, free_cancellation_hours, breakfast_price_cents, breakfast_start_hour, breakfast_end_hour, breakfast_daily_capacity, breakfast_walk_in_price_cents, breakfast_seats_per_room) VALUES
    (1, 'HotelFlex Centro', 'São Paulo',     14, 12, 8, 18, 1, 1, 'pix@centro.test', '5511940001000', 24, 48, 4500, 6, 10, 10, 5500, 2),
    (2, 'Pousada Mar Azul', 'Florianópolis', 15, 11, 9, 17, 0, 1, 'pix@marazul.test', '5548991002000', 12, 72, 3800, 7, 10, 20, 4800, 2);

INSERT INTO users (id, name, email, password_hash, role, hotel_id, whatsapp_phone, whatsapp_opt_in_at) VALUES
    (1, 'Administrador',     'admin@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'admin', NULL, NULL, NULL),
    (2, 'Carlos (Recepção)', 'recepcao.centro@hotelflex.test',  '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'staff', 1,    NULL, NULL),
    (3, 'Marina (Recepção)', 'recepcao.marazul@hotelflex.test', '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'staff', 2,    NULL, NULL),
    (4, 'Ana Souza',         'ana@hotelflex.test',              '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL, '5511987654321', '2026-11-01 10:00:00'),
    (5, 'Bruno Lima',        'bruno@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL, NULL, NULL),
    (6, 'Carla Mendes',      'carla@hotelflex.test',            '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W', 'guest', NULL, NULL, NULL);

-- Centro: 101 e 102 Standard (R$ 320/diária, R$ 50/h); 103 Luxo (R$ 450, R$ 70/h). Mar Azul: 01 Vista Mar.
INSERT INTO rooms (id, hotel_id, number, category, daily_rate_cents, hourly_rate_cents, status, cleaned_at) VALUES
    (1, 1, '101', 'Standard',  32000, 5000, 'clean', NULL),
    (2, 1, '102', 'Standard',  32000, 5000, 'clean', NULL),
    (3, 1, '103', 'Luxo',      45000, 7000, 'clean', NULL),
    (4, 2, '01',  'Vista Mar', 38000, 6000, 'clean', NULL);
