<?php

declare(strict_types=1);

namespace Comprova\Http\Controller;

use Comprova\Domain\DomainException;
use Comprova\Http\Response;
use Comprova\Http\Session;

/**
 * Runs a POST action with the Post/Redirect/Get pattern: business errors become
 * a flash message; unexpected errors bubble up to the global handler and are logged.
 */
final class ActionRunner
{
    public function __construct(private readonly Session $session)
    {
    }

    /**
     * @param callable(): (string|null) $action may return a different redirect target on success
     * @param array<string, mixed> $formInput kept for re-filling the form after an error
     */
    public function run(callable $action, string $successMessage, string $redirectTo, array $formInput = []): never
    {
        try {
            $successRedirect = $action();
            if ($successMessage !== '') {
                $this->session->flash('success', $successMessage);
            }
            Response::redirect(is_string($successRedirect) ? $successRedirect : $redirectTo);
        } catch (DomainException $error) {
            $this->session->flash('error', $error->getMessage());
            $this->session->flashOldInput($formInput);
            Response::redirect($redirectTo);
        }
    }
}
