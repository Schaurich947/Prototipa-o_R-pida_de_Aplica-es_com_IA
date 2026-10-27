<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\DomainException;
use HotelFlex\Http\Response;
use HotelFlex\Http\Session;

/**
 * Runs a POST action with the Post/Redirect/Get pattern: business errors become
 * a flash message; unexpected errors bubble up to the global handler and are logged.
 */
final class ActionRunner
{
    public function __construct(private readonly Session $session)
    {
    }

    public function run(callable $action, string $successMessage, string $redirectTo): never
    {
        try {
            $action();
            $this->session->flash('success', $successMessage);
        } catch (DomainException $error) {
            $this->session->flash('error', $error->getMessage());
        }
        Response::redirect($redirectTo);
    }
}
