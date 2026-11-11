-- database/schema.sql
-- Recria as tabelas do zero (ambiente de desenvolvimento/demonstração).
-- Valores monetários em centavos (INT) para evitar erros de arredondamento.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS breakfast_bookings, notifications, charges, service_requests, reservations, rooms, users, hotels;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE hotels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    city VARCHAR(80) NOT NULL,
    check_in_hour TINYINT UNSIGNED NOT NULL DEFAULT 14,
    check_out_hour TINYINT UNSIGNED NOT NULL DEFAULT 12,
    earliest_check_in_hour TINYINT UNSIGNED NOT NULL DEFAULT 8,
    latest_check_out_hour TINYINT UNSIGNED NOT NULL DEFAULT 18,
    early_free_hours TINYINT UNSIGNED NOT NULL DEFAULT 1,
    cleaning_buffer_hours TINYINT UNSIGNED NOT NULL DEFAULT 1,
    -- Pagamento direto ao hotel, sem intermediário.
    pix_key VARCHAR(140) NOT NULL,
    whatsapp_phone VARCHAR(20) NULL,
    payment_deadline_hours SMALLINT UNSIGNED NOT NULL DEFAULT 24,
    free_cancellation_hours SMALLINT UNSIGNED NOT NULL DEFAULT 48,
    -- Café da manhã vendido também para quem não está hospedado (0 = o hotel não vende avulso).
    breakfast_price_cents INT UNSIGNED NOT NULL DEFAULT 0,
    breakfast_start_hour TINYINT UNSIGNED NOT NULL DEFAULT 6,
    breakfast_end_hour TINYINT UNSIGNED NOT NULL DEFAULT 10,
    breakfast_daily_capacity SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    -- Preço para quem chega sem reserva (maior que o normal) e lugares estimados por quarto ocupado.
    breakfast_walk_in_price_cents INT UNSIGNED NOT NULL DEFAULT 0,
    breakfast_seats_per_room TINYINT UNSIGNED NOT NULL DEFAULT 2,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('guest', 'staff', 'admin') NOT NULL DEFAULT 'guest',
    hotel_id INT UNSIGNED NULL,
    -- Consentimento para mensagens automáticas por WhatsApp (LGPD / política do WhatsApp).
    whatsapp_phone VARCHAR(20) NULL,
    whatsapp_opt_in_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT UNSIGNED NOT NULL,
    number VARCHAR(10) NOT NULL,
    category VARCHAR(40) NOT NULL,
    daily_rate_cents INT UNSIGNED NOT NULL,
    hourly_rate_cents INT UNSIGNED NOT NULL,
    status ENUM('occupied', 'dirty', 'cleaning', 'clean', 'released') NOT NULL DEFAULT 'clean',
    cleaned_at DATETIME NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT fk_rooms_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id),
    UNIQUE KEY uq_rooms_hotel_number (hotel_id, number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    check_in_date DATE NOT NULL,
    nights SMALLINT UNSIGNED NOT NULL,
    early_check_in_hours TINYINT UNSIGNED NOT NULL DEFAULT 0,
    late_checkout_hours TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending_payment', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'expired') NOT NULL DEFAULT 'pending_payment',
    -- Forma de pagamento escolhida e o desconto que ela dá nas diárias.
    payment_plan ENUM('integral', 'sinal', 'sinal-balcao') NULL,
    discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT fk_reservations_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_reservations_room FOREIGN KEY (room_id) REFERENCES rooms (id),
    INDEX idx_reservations_room_dates (room_id, check_in_date),
    INDEX idx_reservations_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT UNSIGNED NOT NULL,
    type ENUM('early_checkin', 'late_checkout') NOT NULL,
    extra_hours TINYINT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    resolved_at DATETIME NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT fk_requests_reservation FOREIGN KEY (reservation_id) REFERENCES reservations (id),
    INDEX idx_requests_reservation_status (reservation_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cobranças da reserva: diárias, horas extras e diferenças de valor. Créditos a devolver ficam como "refund".
CREATE TABLE charges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT UNSIGNED NOT NULL,
    kind ENUM('booking', 'extra', 'refund') NOT NULL,
    description VARCHAR(160) NOT NULL,
    amount_cents INT UNSIGNED NOT NULL,
    status ENUM('pending', 'under_review', 'paid', 'rejected', 'cancelled', 'refund_pending', 'refunded') NOT NULL,
    due_at DATETIME NULL,
    receipt_path VARCHAR(120) NULL,
    receipt_original_name VARCHAR(160) NULL,
    receipt_mime VARCHAR(40) NULL,
    rejection_reason VARCHAR(255) NULL,
    -- Cobranças combinadas para pagar no hotel não pedem comprovante; a recepção registra o recebimento.
    pay_at_desk TINYINT(1) NOT NULL DEFAULT 0,
    paid_method ENUM('pix_receipt', 'cash', 'debit_card', 'credit_card', 'pix_desk') NULL,
    received_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    reviewed_at DATETIME NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT fk_charges_reservation FOREIGN KEY (reservation_id) REFERENCES reservations (id),
    INDEX idx_charges_reservation (reservation_id),
    INDEX idx_charges_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fila de mensagens (outbox). O texto já fica pronto no momento do evento e serve de histórico.
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NOT NULL,
    channel ENUM('email', 'whatsapp') NOT NULL,
    recipient VARCHAR(190) NOT NULL,
    subject VARCHAR(190) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending', 'sending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    next_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_error VARCHAR(255) NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT fk_notifications_reservation FOREIGN KEY (reservation_id) REFERENCES reservations (id),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id),
    INDEX idx_notifications_queue (status, next_attempt_at),
    INDEX idx_notifications_reservation (reservation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Café da manhã avulso. Pagamento via Pix com comprovante (com desconto) ou no balcão.
CREATE TABLE breakfast_bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    hotel_id INT UNSIGNED NOT NULL,
    breakfast_date DATE NOT NULL,
    people TINYINT UNSIGNED NOT NULL,
    unit_price_cents INT UNSIGNED NOT NULL,
    discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
    total_cents INT UNSIGNED NOT NULL,
    payment_option ENUM('pix', 'desk', 'walk_in') NOT NULL,
    walk_in_name VARCHAR(120) NULL,
    status ENUM('pending_payment', 'under_review', 'confirmed', 'served', 'cancelled', 'expired') NOT NULL,
    is_paid TINYINT(1) NOT NULL DEFAULT 0,
    paid_method ENUM('pix_receipt', 'cash', 'debit_card', 'credit_card', 'pix_desk') NULL,
    due_at DATETIME NULL,
    receipt_path VARCHAR(120) NULL,
    receipt_original_name VARCHAR(160) NULL,
    receipt_mime VARCHAR(40) NULL,
    rejection_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT fk_breakfast_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_breakfast_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id),
    INDEX idx_breakfast_hotel_date (hotel_id, breakfast_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
