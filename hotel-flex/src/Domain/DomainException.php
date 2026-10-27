<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use RuntimeException;

/** Business rule violation whose message is safe to show to the user. */
final class DomainException extends RuntimeException
{
}
