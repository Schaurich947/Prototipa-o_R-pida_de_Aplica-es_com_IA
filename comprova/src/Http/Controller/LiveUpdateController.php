<?php

declare(strict_types=1);

namespace Comprova\Http\Controller;

use Comprova\Http\Response;
use Comprova\Repository\ChangeTrackerRepository;

/** Polled by the screens to refresh delivery statuses without manual reloads. */
final class LiveUpdateController
{
    public function __construct(private readonly ChangeTrackerRepository $changeTracker)
    {
    }

    public function showVersion(): never
    {
        Response::json(['version' => $this->changeTracker->getDataVersion()]);
    }
}
