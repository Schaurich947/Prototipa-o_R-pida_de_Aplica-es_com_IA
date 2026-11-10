<?php

declare(strict_types=1);

namespace HotelFlex\Database;

use RuntimeException;

/** Minimal valid one-page PDF used by the demo payments and the webhook test script. */
final class SampleReceipt
{
    public static function build(string $text = 'Comprovante de pagamento (exemplo)'): string
    {
        $stream = 'BT /F1 18 Tf 72 720 Td (' . addcslashes($text, '()\\') . ') Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n{$object}\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf . 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";
    }

    public static function write(string $path): void
    {
        if (file_put_contents($path, self::build()) === false) {
            throw new RuntimeException("Não foi possível criar {$path}.");
        }
    }
}
