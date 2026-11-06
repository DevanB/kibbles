<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class InvalidLinkUrl extends RuntimeException
{
    public static function make(): self
    {
        return new self('Enter a valid http or https URL.');
    }
}
