-- database/schema-fiscal.sql
-- Módulo fiscal (fase 1: emissão simulada). Roda depois do schema.sql, que já apaga estas tabelas.

-- O hotel como emissor ("prestador"). Alíquotas e regras vêm do contador do hotel.
CREATE TABLE hotel_fiscal_profiles (
    hotel_id INT UNSIGNED PRIMARY KEY,
    legal_name VARCHAR(150) NOT NULL,
    cnpj CHAR(14) NOT NULL,
    municipal_registration VARCHAR(20) NOT NULL,
    state_registration VARCHAR(20) NULL,
    tax_regime ENUM('simples', 'presumido', 'real', 'mei') NOT NULL,
    municipality_code CHAR(7) NOT NULL,
    address VARCHAR(255) NOT NULL,
    service_code VARCHAR(10) NOT NULL DEFAULT '09.01',
    -- Pontos-base: 500 = 5,00%.
    iss_rate_bp SMALLINT UNSIGNED NOT NULL,
    icms_rate_bp SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    series VARCHAR(5) NOT NULL DEFAULT '1',
    next_number INT UNSIGNED NOT NULL DEFAULT 1,
    iss_withholding ENUM('nunca', 'empresa-mesma-cidade') NOT NULL DEFAULT 'nunca',
    allows_anonymous_consumer TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT fk_fiscal_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache das consultas de CNPJ (dados públicos de empresas).
CREATE TABLE company_lookups (
    cnpj CHAR(14) PRIMARY KEY,
    payload JSON NOT NULL,
    looked_up_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Regras de CBS/IBS por período de vigência: lei nova = linha nova, sem mudar o código.
CREATE TABLE tax_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tax ENUM('cbs', 'ibs') NOT NULL,
    service_type ENUM('hospedagem', 'alimentacao') NOT NULL,
    rate_bp SMALLINT UNSIGNED NOT NULL,
    reduction_bp SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    valid_from DATE NOT NULL,
    valid_until DATE NULL,
    is_informative TINYINT(1) NOT NULL,
    allows_acquirer_credit TINYINT(1) NOT NULL,
    legal_basis VARCHAR(255) NOT NULL,
    INDEX idx_tax_rules_lookup (service_type, valid_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notas fiscais. Os dados do tomador e os valores são uma cópia do momento da emissão.
CREATE TABLE invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    kind ENUM('nfse', 'nfce') NOT NULL,
    service_type ENUM('hospedagem', 'alimentacao') NOT NULL,
    source_type ENUM('reserva', 'cafe') NOT NULL,
    source_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'processing', 'authorized', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    series VARCHAR(5) NULL,
    number INT UNSIGNED NULL,
    dps_id VARCHAR(60) NULL UNIQUE,
    access_key VARCHAR(60) NULL,
    issuer VARCHAR(30) NULL,
    taker_name VARCHAR(150) NOT NULL,
    taker_document_type ENUM('cpf', 'cnpj', 'estrangeiro') NULL,
    taker_document VARCHAR(20) NULL,
    taker_email VARCHAR(190) NULL,
    taker_address VARCHAR(255) NULL,
    taker_municipality_code CHAR(7) NULL,
    taker_is_simples TINYINT(1) NULL,
    description VARCHAR(500) NOT NULL,
    amount_cents INT UNSIGNED NOT NULL,
    iss_cents INT UNSIGNED NOT NULL DEFAULT 0,
    iss_withheld TINYINT(1) NOT NULL DEFAULT 0,
    icms_cents INT UNSIGNED NOT NULL DEFAULT 0,
    cbs_cents INT UNSIGNED NOT NULL DEFAULT 0,
    ibs_cents INT UNSIGNED NOT NULL DEFAULT 0,
    reform_informative TINYINT(1) NOT NULL DEFAULT 1,
    acquirer_credit TINYINT(1) NOT NULL DEFAULT 0,
    competence_date DATE NOT NULL,
    issued_at DATETIME NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    next_attempt_at DATETIME NOT NULL,
    last_error VARCHAR(255) NULL,
    cancel_reason VARCHAR(255) NULL,
    cancelled_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT fk_invoices_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id),
    CONSTRAINT fk_invoices_user FOREIGN KEY (user_id) REFERENCES users (id),
    UNIQUE KEY uq_invoices_number (hotel_id, kind, series, number),
    INDEX idx_invoices_source (source_type, source_id),
    INDEX idx_invoices_queue (status, next_attempt_at),
    INDEX idx_invoices_hotel_month (hotel_id, competence_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Razão de tributos (pré-controle): só inserções. Um cancelamento lança o estorno com valor negativo.
CREATE TABLE tax_ledger (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED NOT NULL,
    tax ENUM('iss', 'icms', 'cbs', 'ibs') NOT NULL,
    nature ENUM('proprio', 'retido', 'informativo') NOT NULL,
    amount_cents INT NOT NULL,
    competence_month CHAR(7) NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_ledger_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id),
    CONSTRAINT fk_ledger_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id),
    INDEX idx_ledger_hotel_month (hotel_id, competence_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ano de teste da reforma (LC 214/2025): CBS 0,9% e IBS 0,1% destacados na nota, sem recolhimento.
-- Hospedagem e alimentação em hotel: regimes específicos que vedam o crédito ao adquirente.
-- Confirmar com o contador; as alíquotas de 2027 em diante entram como novas linhas quando publicadas.
INSERT INTO tax_rules (tax, service_type, rate_bp, reduction_bp, valid_from, valid_until, is_informative, allows_acquirer_credit, legal_basis) VALUES
    ('cbs', 'hospedagem',  90, 0, '2026-01-01', '2026-12-31', 1, 0, 'LC 214/2025: ano de teste (CBS 0,9%); hotelaria em regime específico, sem crédito ao adquirente'),
    ('ibs', 'hospedagem',  10, 0, '2026-01-01', '2026-12-31', 1, 0, 'LC 214/2025: ano de teste (IBS 0,1%); hotelaria em regime específico, sem crédito ao adquirente'),
    ('cbs', 'alimentacao', 90, 0, '2026-01-01', '2026-12-31', 1, 0, 'LC 214/2025: ano de teste (CBS 0,9%); bares e restaurantes, sem crédito ao adquirente'),
    ('ibs', 'alimentacao', 10, 0, '2026-01-01', '2026-12-31', 1, 0, 'LC 214/2025: ano de teste (IBS 0,1%); bares e restaurantes, sem crédito ao adquirente');
