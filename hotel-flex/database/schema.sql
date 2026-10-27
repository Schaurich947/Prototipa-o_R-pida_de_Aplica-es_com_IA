-- database/schema.sql
-- Valores monetários em centavos (INT) para evitar erros de arredondamento.

CREATE TABLE IF NOT EXISTS rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    number VARCHAR(10) NOT NULL UNIQUE,
    category VARCHAR(40) NOT NULL,
    daily_rate_cents INT UNSIGNED NOT NULL,
    hourly_rate_cents INT UNSIGNED NOT NULL,
    checkout_limit_hour TINYINT UNSIGNED NOT NULL DEFAULT 18,
    status ENUM('occupied', 'dirty', 'cleaning', 'clean', 'released') NOT NULL DEFAULT 'dirty',
    cleaned_at DATETIME NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    guest_name VARCHAR(120) NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    check_in_date DATE NOT NULL,
    nights SMALLINT UNSIGNED NOT NULL,
    late_checkout_hours TINYINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT fk_reservations_room FOREIGN KEY (room_id) REFERENCES rooms (id),
    INDEX idx_reservations_room (room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT UNSIGNED NOT NULL,
    type ENUM('early_checkin', 'late_checkout') NOT NULL,
    extra_hours TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    resolved_at DATETIME NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT fk_requests_reservation FOREIGN KEY (reservation_id) REFERENCES reservations (id),
    INDEX idx_requests_reservation_status (reservation_id, status),
    INDEX idx_requests_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
