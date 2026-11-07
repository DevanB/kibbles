<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class LinkPreviewFailed extends RuntimeException
{
    public static function privateAddress(string $ip): self
    {
        return new self('Refusing to fetch a private address: '.$ip);
    }

    public static function unresolvedHost(string $host): self
    {
        return new self('Could not resolve host: '.$host);
    }

    public static function tooManyRedirects(): self
    {
        return new self('Too many redirects while fetching the preview.');
    }

    public static function http(int $status, ?Throwable $previous = null): self
    {
        return new self('Preview fetch failed with HTTP '.$status.'.', previous: $previous);
    }

    public static function tooLarge(): self
    {
        return new self('Preview response exceeded 1 MiB.');
    }
}
