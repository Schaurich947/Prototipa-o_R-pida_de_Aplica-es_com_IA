<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\DomainException;

/** Streams a receipt only to signed-in operators, always as a download (never rendered inline). */
final class ReceiptDownload
{
    public static function send(string $absolutePath, string $fileName, string $mime): never
    {
        if (!is_file($absolutePath)) {
            throw new DomainException('Arquivo do comprovante não encontrado.');
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($absolutePath));
        header('Content-Disposition: attachment; filename="' . addcslashes($fileName, "\"\\") . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($absolutePath);
        exit;
    }
}
