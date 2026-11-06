<?php

declare(strict_types=1);

use App\Exceptions\InvalidLinkUrl;
use App\ValueObjects\LinkUrl;

it('strips tracking parameters, fragments, and lowercases the host', function (): void {
    $url = LinkUrl::parse('HTTPS://News.Example.COM/path?utm_source=x&fbclid=1&keep=yes#section');

    expect($url->value)->toBe('https://news.example.com/path?keep=yes')
        ->and($url->host)->toBe('news.example.com')
        ->and($url->fetchable)->toBeTrue();
});

it('refuses schemes other than http and https', function (string $raw): void {
    expect(fn (): LinkUrl => LinkUrl::parse($raw))
        ->toThrow(InvalidLinkUrl::class)
        ->and(LinkUrl::isValid($raw))->toBeFalse();
})->with([
    'javascript' => ['javascript:alert(1)'],
    'ftp' => ['ftp://example.com/file'],
    'empty' => ['  '],
    'userinfo' => ['https://user:pass@example.com/secret'],
    'malformed' => ['http://:'],
]);

it('drops empty query pairs while keeping real parameters', function (): void {
    $url = LinkUrl::parse('https://example.com/path?keep=yes&&utm_source=x');

    expect($url->value)->toBe('https://example.com/path?keep=yes');
});

it('saves private and LAN hosts without fetching a preview', function (string $raw): void {
    $url = LinkUrl::parse($raw);

    expect($url->fetchable)->toBeFalse();
})->with([
    'loopback' => ['http://127.0.0.1/admin'],
    'rfc1918' => ['http://192.168.1.20/nas'],
    'link-local' => ['http://169.254.1.1/printer'],
    'localhost' => ['http://localhost:3000'],
    'mdns' => ['http://nas.local/queue'],
    'decimal loopback' => ['http://2130706433/'],
    'ipv6-loopback' => ['http://[::1]/'],
]);
