<?php

declare(strict_types=1);

namespace App\Contracts;

interface ResolvesPublicIps
{
    /**
     * @return list<string>
     */
    public function resolve(string $host): array;

    public function curlPrereq(mixed $handle, string $connectedIp): int;
}
