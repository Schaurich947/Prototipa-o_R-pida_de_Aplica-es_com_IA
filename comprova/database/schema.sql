-- database/schema.sql
-- Recria as tabelas do zero (ambiente de desenvolvimento/demonstração).
-- Valores monetários em centavos (INT) para evitar erros de arredondamento.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notifications, payments, beneficiaries, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recebedores. O consentimento de WhatsApp fica registrado com data (LGPD / política do WhatsApp).
CREATE TABLE beneficiaries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    whatsapp_phone VARCHAR(20) NULL,
    whatsapp_opt_in_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id INT UNSIGNED NOT NULL,
    -- Identificador do sistema pagador: impede registrar o mesmo pagamento duas vezes.
    external_id VARCHAR(80) NULL UNIQUE,
    source ENUM('manual', 'webhook') NOT NULL,
    amount_cents INT UNSIGNED NOT NULL,
    description VARCHAR(160) NULL,
    paid_at DATETIME NOT NULL,
    receipt_path VARCHAR(120) NOT NULL,
    receipt_original_name VARCHAR(160) NOT NULL,
    receipt_mime VARCHAR(40) NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_beneficiary FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries (id),
    CONSTRAINT fk_payments_user FOREIGN KEY (created_by) REFERENCES users (id),
    INDEX idx_payments_paid_at (paid_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fila de envios (outbox). O WhatsApp espera o e-mail ser entregue ("waiting").
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id INT UNSIGNED NOT NULL,
    channel ENUM('email', 'whatsapp') NOT NULL,
    recipient VARCHAR(190) NOT NULL,
    status ENUM('waiting', 'pending', 'sending', 'sent', 'failed', 'skipped') NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    next_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_error VARCHAR(255) NULL,
    provider_message_id VARCHAR(120) NULL,
    sent_at DATETIME NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT fk_notifications_payment FOREIGN KEY (payment_id) REFERENCES payments (id),
    UNIQUE KEY uq_notifications_payment_channel (payment_id, channel),
    INDEX idx_notifications_queue (status, next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
