<?php

declare(strict_types=1);

use App\Exceptions\LinkPreviewFailed;
use App\Services\PublicIpResolver;

it('treats only routable public addresses as fetchable', function (string $ip, bool $public): void {
    expect(PublicIpResolver::isPublic($ip))->toBe($public);
})->with([
    'cloudflare' => ['1.1.1.1', true],
    'loopback' => ['127.0.0.1', false],
    'rfc1918-10' => ['10.0.0.5', false],
    'rfc1918-172' => ['172.16.0.1', false],
    'rfc1918-192' => ['192.168.0.1', false],
    'link-local' => ['169.254.10.1', false],
    'unspecified' => ['0.0.0.0', false],
    'multicast' => ['224.0.0.1', false],
    'ipv6-loopback' => ['::1', false],
    'ipv6-link-local' => ['fe80::1', false],
    'ipv6-public' => ['2001:4860:4860::8888', true],
]);

it('aborts curl when the connected peer is not public', function (): void {
    expect(PublicIpResolver::curlPrereq(null, '1.1.1.1'))->toBe(CURL_PREREQFUNC_OK)
        ->and(PublicIpResolver::curlPrereq(null, '127.0.0.1'))->toBe(CURL_PREREQFUNC_ABORT);
});

it('refuses a hostname that resolves to loopback', function (): void {
    expect(fn (): array => resolve(PublicIpResolver::class)->resolve('localhost'))
        ->toThrow(LinkPreviewFailed::class);
});

it('refuses a bracketed IPv6 loopback without sending a lookup as a hostname', function (): void {
    expect(fn (): array => resolve(PublicIpResolver::class)->resolve('[::1]'))
        ->toThrow(LinkPreviewFailed::class);
});

it('accepts a public literal and refuses a private literal', function (): void {
    expect(resolve(PublicIpResolver::class)->resolve('1.1.1.1'))->toBe(['1.1.1.1'])
        ->and(fn (): array => resolve(PublicIpResolver::class)->resolve('127.0.0.1'))
        ->toThrow(LinkPreviewFailed::class);
});

it('refuses a decimal IPv4 loopback', function (): void {
    expect(fn (): array => resolve(PublicIpResolver::class)->resolve('2130706433'))
        ->toThrow(LinkPreviewFailed::class);
});

it('accepts a public decimal IPv4 address', function (): void {
    expect(resolve(PublicIpResolver::class)->resolve('16843009'))->toBe(['1.1.1.1']);
});

it('resolves a public hostname to public addresses', function (): void {
    $ips = resolve(PublicIpResolver::class)->resolve('one.one.one.one');

    expect($ips)->not->toBeEmpty();

    foreach ($ips as $ip) {
        expect(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
            ->not->toBeFalse();
    }
});

it('refuses a hostname that does not resolve', function (): void {
    expect(fn (): array => resolve(PublicIpResolver::class)->resolve('no-such-host.invalid'))
        ->toThrow(LinkPreviewFailed::class);
});
