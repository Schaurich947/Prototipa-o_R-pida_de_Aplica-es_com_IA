-- database/schema.sql
-- Recria as tabelas do zero (ambiente de desenvolvimento/demonstração).
-- Valores monetários em centavos (INT) para evitar erros de arredondamento.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS service_requests, reservations, rooms, users, hotels;
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
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('guest', 'staff', 'admin') NOT NULL DEFAULT 'guest',
    hotel_id INT UNSIGNED NULL,
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
    status ENUM('confirmed', 'checked_in', 'checked_out', 'cancelled') NOT NULL DEFAULT 'confirmed',
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
