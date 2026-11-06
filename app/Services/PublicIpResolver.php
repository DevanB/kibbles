<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\LinkPreviewFailed;

final readonly class PublicIpResolver
{
    public static function curlPrereq(mixed $handle, string $connectedIp): int
    {
        unset($handle);

        return self::isPublic($connectedIp)
            ? CURL_PREREQFUNC_OK
            : CURL_PREREQFUNC_ABORT;
    }

    public static function isPublic(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $long = ip2long($ip);

            if ($long === false || ($long & 0xF0000000) === 0xE0000000) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function resolve(string $host): array
    {
        $host = $this->normalizedHost($host);

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $this->assertPublic($host);

            return [$host];
        }

        if (ctype_digit($host)) {
            $ip = long2ip((int) $host);

            $this->assertPublic($ip);

            return [$ip];
        }

        $ips = $this->lookup($host);

        if ($ips === []) {
            throw LinkPreviewFailed::unresolvedHost($host);
        }

        foreach ($ips as $ip) {
            $this->assertPublic($ip);
        }

        return array_values(array_unique($ips));
    }

    public function assertPublic(string $ip): void
    {
        if (! self::isPublic($ip)) {
            throw LinkPreviewFailed::privateAddress($ip);
        }
    }

    private function normalizedHost(string $host): string
    {
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            return mb_substr($host, 1, -1);
        }

        return $host;
    }

    /**
     * @return list<string>
     */
    private function lookup(string $host): array
    {
        $ips = [];

        foreach (gethostbynamel($host) ?: [] as $ip) {
            if ($ip !== '') {
                $ips[] = $ip;
            }
        }

        foreach (dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            $ip = $record['ipv6'] ?? null;

            if (is_string($ip) && $ip !== '') {
                $ips[] = $ip;
            }
        }

        return $ips;
    }
}
