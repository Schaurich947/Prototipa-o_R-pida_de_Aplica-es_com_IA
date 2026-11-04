<?php

declare(strict_types=1);

use Comprova\Domain\Money;
use Comprova\Domain\NotificationStatus;
use Comprova\Http\Url;

/** Escapes any dynamic value for HTML output (anti-XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Builds an app URL that also works when the project lives in a subfolder. */
function url(string $path): string
{
    return Url::to($path);
}

function formatMoney(int $cents): string
{
    return Money::format($cents);
}

function formatDateTime(DateTimeInterface $moment): string
{
    return $moment->format('d/m/Y H:i');
}

function initials(string $name): string
{
    $parts = array_slice(preg_split('/\s+/u', trim($name)) ?: [], 0, 2);

    return mb_strtoupper(implode('', array_map(static fn (string $part) => mb_substr($part, 0, 1), $parts)));
}

function statusClass(NotificationStatus $status): string
{
    return match (true) {
        $status === NotificationStatus::Sent => 'status',
        $status->isProblem() => 'status failed',
        default => 'status pending',
    };
}
