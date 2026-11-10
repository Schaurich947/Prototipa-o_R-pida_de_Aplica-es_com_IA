<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Http\Response;
use HotelFlex\Repository\ChangeTrackerRepository;

/** Polled by the screens to refresh when housekeeping or guests change data. */
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
