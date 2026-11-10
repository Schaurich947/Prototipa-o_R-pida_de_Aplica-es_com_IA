<?php

declare(strict_types=1);

namespace HotelFlex\Notification;

use RuntimeException;

/** Builds a multipart e-mail with the receipt attached, with headers safe against injection. */
final class MimeEmailBuilder
{
    /** @return array{to: string, subject: string, headers: string, body: string} */
    public function build(EmailMessage $message, string $fromEmail, string $fromName): array
    {
        $boundary = 'hotelflex-' . bin2hex(random_bytes(12));
        $headers = implode("\r\n", [
            'From: ' . $this->encodeAddress($fromEmail, $fromName),
            'MIME-Version: 1.0',
            "Content-Type: multipart/mixed; boundary=\"{$boundary}\"",
        ]);

        return [
            'to' => $this->encodeAddress($message->toEmail, $message->toName),
            'subject' => $this->encodeHeader($message->subject),
            'headers' => $headers,
            'body' => $this->buildBody($message, $boundary),
        ];
    }

    private function buildBody(EmailMessage $message, string $boundary): string
    {
        $textPart = ["--{$boundary}", 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64', '', chunk_split(base64_encode($message->textBody))];
        if ($message->attachmentPath === null) {
            return implode("\r\n", [...$textPart, "--{$boundary}--"]);
        }
        $attachment = file_get_contents($message->attachmentPath);
        if ($attachment === false) {
            throw new RuntimeException('Arquivo do comprovante não encontrado.');
        }
        $fileName = $this->encodeHeader((string) $message->attachmentName);

        return implode("\r\n", [
            ...$textPart,
            "--{$boundary}",
            "Content-Type: {$message->attachmentMime}; name=\"{$fileName}\"",
            'Content-Transfer-Encoding: base64',
            "Content-Disposition: attachment; filename=\"{$fileName}\"",
            '',
            chunk_split(base64_encode($attachment)),
            "--{$boundary}--",
        ]);
    }

    private function encodeAddress(string $email, string $name): string
    {
        $cleanEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($cleanEmail === false) {
            throw new RuntimeException('Endereço de e-mail inválido.');
        }

        return $this->encodeHeader($name) . " <{$cleanEmail}>";
    }

    /** RFC 2047 encoding also strips line breaks, which blocks header injection. */
    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode(str_replace(["\r", "\n"], ' ', $value)) . '?=';
    }
}
