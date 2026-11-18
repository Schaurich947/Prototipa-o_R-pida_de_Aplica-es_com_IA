<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Pdf;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/** QR code as an SVG data URI: no GD extension needed and nothing fetched from the network. */
final class QrCodeImage
{
    public static function toDataUri(string $content): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => true,
            'addQuietzone' => true,
            'quietzoneSize' => 1,
        ]);

        return (new QRCode($options))->render($content);
    }
}
