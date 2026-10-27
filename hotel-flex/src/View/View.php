<?php

declare(strict_types=1);

namespace HotelFlex\View;

use RuntimeException;

/** Renders PHP/HTML templates from /views. Every dynamic value must pass through e(). */
final class View
{
    /** @param array<string, mixed> $shared values available to every template (user, csrf, flashes) */
    public function __construct(
        private readonly string $viewsPath,
        private array $shared = [],
    ) {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /** @param array<string, mixed> $data */
    public function render(string $page, array $data = [], string $layout = 'app'): void
    {
        $content = $this->capture("pages/{$page}", $data);
        echo $this->capture("layouts/{$layout}", [...$data, 'content' => $content]);
    }

    /** @param array<string, mixed> $data */
    public function partial(string $name, array $data = []): string
    {
        return $this->capture("partials/{$name}", $data);
    }

    /** @param array<string, mixed> $data */
    private function capture(string $template, array $data): string
    {
        $file = "{$this->viewsPath}/{$template}.php";
        if (!is_file($file)) {
            throw new RuntimeException("Template não encontrado: {$template}");
        }
        $view = $this;
        extract([...$this->shared, ...$data], EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }
}
