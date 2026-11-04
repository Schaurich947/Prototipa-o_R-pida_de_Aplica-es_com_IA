-- database/seed.sql
-- Dados de demonstração. Login: admin@comprova.test / Demo@1234
-- O arquivo storage/receipts/exemplo.pdf é criado pelo bin/setup-database.php.

INSERT INTO users (id, name, email, password_hash) VALUES
    (1, 'Operador Acme', 'admin@comprova.test', '$2y$10$WV4szLelhmPJiw9sbbEd9.AmzLMpygaEnZqyhJzqDPxLeclwqfF.W');

INSERT INTO beneficiaries (id, name, email, whatsapp_phone, whatsapp_opt_in_at) VALUES
    (1, 'Mariana Lima',    'mariana.lima@email.com',    '5511987654321', NOW() - INTERVAL 40 DAY),
    (2, 'Rafael Souza',    'rafael.souza@email.com',    NULL,            NULL),
    (3, 'Fernanda Costa',  'fernanda.costa@email.com',  '5521998765432', NOW() - INTERVAL 10 DAY),
    (4, 'Pedro Oliveira',  'pedro.oliveira@email.com',  '5531991234567', NOW() - INTERVAL 90 DAY);

INSERT INTO payments (id, beneficiary_id, external_id, source, amount_cents, description, paid_at, receipt_path, receipt_original_name, receipt_mime, created_by) VALUES
    (1, 1, 'PAG-2026-0001', 'webhook', 245000, 'Serviços de design · outubro', NOW() - INTERVAL 3 HOUR, 'exemplo.pdf', 'comprovante-mariana.pdf', 'application/pdf', NULL),
    (2, 2, NULL,            'manual',   89000, 'Reembolso de despesas',        NOW() - INTERVAL 6 HOUR, 'exemplo.pdf', 'comprovante-rafael.pdf',  'application/pdf', 1),
    (3, 3, 'PAG-2026-0003', 'webhook', 120000, 'Consultoria',                  NOW() - INTERVAL 8 HOUR, 'exemplo.pdf', 'comprovante-fernanda.pdf','application/pdf', NULL),
    (4, 4, 'PAG-2026-0004', 'webhook', 378000, 'Fornecimento de materiais',    NOW() - INTERVAL 1 DAY,  'exemplo.pdf', 'comprovante-pedro.pdf',   'application/pdf', NULL);

INSERT INTO notifications (payment_id, channel, recipient, status, attempts, last_error, sent_at) VALUES
    (1, 'email',    'mariana.lima@email.com',   'sent',    1, NULL, NOW() - INTERVAL 3 HOUR),
    (1, 'whatsapp', '5511987654321',            'sent',    1, NULL, NOW() - INTERVAL 3 HOUR),
    (2, 'email',    'rafael.souza@email.com',   'sent',    1, NULL, NOW() - INTERVAL 6 HOUR),
    (3, 'email',    'fernanda.costa@email.com', 'pending', 0, NULL, NULL),
    (3, 'whatsapp', '5521998765432',            'waiting', 0, NULL, NULL),
    (4, 'email',    'pedro.oliveira@email.com', 'sent',    1, NULL, NOW() - INTERVAL 1 DAY),
    (4, 'whatsapp', '5531991234567',            'failed',  5, 'Número não possui conta no WhatsApp.', NULL);
