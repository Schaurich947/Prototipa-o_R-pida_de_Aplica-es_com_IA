<?php

declare(strict_types=1);

namespace HotelFlex\Notification;

/**
 * WhatsApp Business Cloud API (Meta). Requires a business phone number id,
 * a permanent access token and an approved message template.
 */
final class CloudApiWhatsAppSender implements WhatsAppSender
{
    private const API_URL = 'https://graph.facebook.com/v21.0/%s/messages';
    private const TIMEOUT_SECONDS = 15;
    /** Meta error codes that will not succeed on retry (invalid recipient, template not approved...). */
    private const PERMANENT_ERROR_CODES = [131026, 131051, 132000, 132001, 132015, 132016];

    public function __construct(
        private readonly string $phoneNumberId,
        private readonly string $accessToken,
    ) {
    }

    /** @return array<string, mixed> */
    public static function buildPayload(WhatsAppMessage $message): array
    {
        $parameters = array_map(static fn (string $value) => ['type' => 'text', 'text' => $value], $message->templateParameters);

        return [
            'messaging_product' => 'whatsapp',
            'to' => $message->toDigits,
            'type' => 'template',
            'template' => [
                'name' => $message->templateName,
                'language' => ['code' => $message->languageCode],
                'components' => [['type' => 'body', 'parameters' => $parameters]],
            ],
        ];
    }

    public function send(WhatsAppMessage $message): DeliveryResult
    {
        [$statusCode, $responseBody] = $this->post(json_encode(self::buildPayload($message), JSON_THROW_ON_ERROR));
        $response = json_decode($responseBody, true);
        if ($statusCode >= 200 && $statusCode < 300 && isset($response['messages'][0]['id'])) {
            return DeliveryResult::success((string) $response['messages'][0]['id']);
        }
        $errorCode = (int) ($response['error']['code'] ?? 0);
        $errorMessage = (string) ($response['error']['message'] ?? "HTTP {$statusCode}");

        return DeliveryResult::failure("WhatsApp: {$errorMessage}", !in_array($errorCode, self::PERMANENT_ERROR_CODES, true));
    }

    public function describe(): string
    {
        return 'Envio real via WhatsApp Business Cloud API';
    }

    /** @return array{0: int, 1: string} */
    private function post(string $jsonBody): array
    {
        $curl = curl_init(sprintf(self::API_URL, rawurlencode($this->phoneNumberId)));
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $this->accessToken],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
        ]);
        $responseBody = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        return $responseBody === false ? [0, json_encode(['error' => ['message' => "falha de conexão ({$curlError})"]])] : [$statusCode, (string) $responseBody];
    }
}
