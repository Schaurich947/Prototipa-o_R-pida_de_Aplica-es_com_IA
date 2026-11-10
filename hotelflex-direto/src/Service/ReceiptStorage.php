<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\DomainException;
use finfo;
use RuntimeException;

/**
 * Receipts live outside public/ with random names. The real file type is checked
 * from its content (not the extension), so a renamed script is rejected.
 */
final class ReceiptStorage
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    private const ALLOWED_TYPES = ['application/pdf' => 'pdf', 'image/png' => 'png', 'image/jpeg' => 'jpg'];

    public function __construct(private readonly string $storagePath)
    {
    }

    /** @param array<string, mixed> $uploadedFile one entry of $_FILES */
    public function storeUpload(array $uploadedFile): StoredReceipt
    {
        if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $uploadedFile['tmp_name'])) {
            throw new DomainException('Anexe o comprovante (PDF, JPG ou PNG de até 5 MB).');
        }
        $content = (string) file_get_contents((string) $uploadedFile['tmp_name']);

        return $this->store($content, (string) ($uploadedFile['name'] ?? 'comprovante'));
    }

    public function storeBase64(string $base64Content, string $originalName): StoredReceipt
    {
        $content = base64_decode($base64Content, true);
        if ($content === false) {
            throw new DomainException('O comprovante enviado não está em base64 válido.');
        }

        return $this->store($content, $originalName);
    }

    public function getAbsolutePath(string $storedName): string
    {
        if (!preg_match('/^[a-z0-9-]+\.(pdf|png|jpg)$/', $storedName)) {
            throw new RuntimeException('Nome de comprovante inválido.');
        }

        return "{$this->storagePath}/{$storedName}";
    }

    public function delete(string $storedName): void
    {
        $path = $this->getAbsolutePath($storedName);
        if (is_file($path) && !unlink($path)) {
            error_log("[hotelflex] não foi possível remover o comprovante {$storedName}");
        }
    }

    private function store(string $content, string $originalName): StoredReceipt
    {
        if ($content === '' || strlen($content) > self::MAX_BYTES) {
            throw new DomainException('O comprovante deve ter até 5 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($content) ?: '';
        $extension = self::ALLOWED_TYPES[$mime] ?? throw new DomainException('Formato não aceito. Envie PDF, JPG ou PNG.');
        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        if (file_put_contents($this->getAbsolutePath($storedName), $content) === false) {
            throw new RuntimeException('Não foi possível salvar o comprovante.');
        }

        return new StoredReceipt($storedName, $this->sanitizeName($originalName, $extension), $mime);
    }

    private function sanitizeName(string $originalName, string $extension): string
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = trim((string) preg_replace('/[^\p{L}\p{N}._-]+/u', '-', $baseName), '-') ?: 'comprovante';

        return mb_substr($safeName, 0, 100) . '.' . $extension;
    }
}
