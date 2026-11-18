<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Fiscal\Service\InvoicePdfService;
use HotelFlex\Http\Auth;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Http\Url;

final class InvoicePdfController
{
    public function __construct(
        private readonly Auth $auth,
        private readonly InvoicePdfService $pdfs,
        private readonly string $appUrl,
    ) {
    }

    /** @param array<string, int> $params */
    public function download(HttpRequest $request, array $params): never
    {
        $consultUrl = Url::toAbsolute("/notas/{$params['id']}", $this->appUrl, $_SERVER);
        $pdf = $this->pdfs->generate($this->auth->requireUser(), $params['id'], $consultUrl);
        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($pdf['content']));
        header('Content-Disposition: inline; filename="' . $pdf['fileName'] . '"');
        header('Cache-Control: private, no-store');
        echo $pdf['content'];
        exit;
    }
}
