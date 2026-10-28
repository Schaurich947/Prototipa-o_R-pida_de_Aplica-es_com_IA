<?php

declare(strict_types=1);

use HotelFlex\Domain\Availability;
use HotelFlex\Http\Url;

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
    static $formatter = null;
    $formatter ??= new NumberFormatter('pt_BR', NumberFormatter::CURRENCY);

    return (string) $formatter->formatCurrency($cents / 100, 'BRL');
}

function formatHour(int $hour): string
{
    return sprintf('%02d:00', $hour);
}

function formatHours(int $hours): string
{
    return $hours === 1 ? '1 hora' : "{$hours} horas";
}

function formatDate(DateTimeInterface $date): string
{
    return $date->format('d/m/Y');
}

function formatDateTime(DateTimeInterface $date): string
{
    return $date->format('d/m H:i');
}

function formatCheckoutHour(int $lateCheckoutHours): string
{
    return formatHour(Availability::getCheckoutHour($lateCheckoutHours));
}
