<?php

declare(strict_types=1);

use App\Exceptions\LinkPreviewFailed;
use App\Services\LinkPreviewClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('parses open graph tags from a public address', function (): void {
    Http::fake([
        'http://1.1.1.1/*' => Http::response(
            '<html><head><title>Ignore</title><meta property="og:title" content="OG Title"><meta property="og:site_name" content="Example"><meta property="og:description" content="A post."><meta property="og:image" content="https://example.com/og.png"></head></html>',
            200,
        ),
    ]);

    $preview = resolve(LinkPreviewClient::class)->fetch('http://1.1.1.1/article');

    expect($preview->title)->toBe('OG Title')
        ->and($preview->siteName)->toBe('Example')
        ->and($preview->description)->toBe('A post.')
        ->and($preview->imageUrl)->toBe('https://example.com/og.png');
});

it('falls back to the document title and drops a private image', function (): void {
    Http::fake([
        'http://1.1.1.1/*' => Http::response(
            '<html><head><title> Page Title </title><meta name="description" content="Meta desc."><meta property="og:image" content="http://127.0.0.1/secret.png"><meta property="og:title" content=""><meta name="empty" content=""></head></html>',
            200,
        ),
    ]);

    $preview = resolve(LinkPreviewClient::class)->fetch('http://1.1.1.1/article');

    expect($preview->title)->toBe('Page Title')
        ->and($preview->description)->toBe('Meta desc.')
        ->and($preview->imageUrl)->toBeNull();
});

it('resolves a relative image against the final URL', function (): void {
    Http::fake([
        'http://1.1.1.1/*' => Http::response(
            '<html><head><meta property="og:image" content="/cover.png"></head></html>',
            200,
        ),
    ]);

    $preview = resolve(LinkPreviewClient::class)->fetch('http://1.1.1.1/story');

    expect($preview->imageUrl)->toBe('http://1.1.1.1/cover.png');
});

it('ignores an unusable image and an empty document', function (): void {
    Http::fake([
        'http://1.1.1.1/bad-image' => Http::response(
            '<html><head><meta property="og:image" content="javascript:alert(1)"></head></html>',
            200,
        ),
        'http://1.1.1.1/empty' => Http::response('', 200),
        'http://1.1.1.1/relative-image' => Http::response(
            '<html><head><meta property="og:image" content="cover.png"></head></html>',
            200,
        ),
    ]);

    $client = resolve(LinkPreviewClient::class);

    expect($client->fetch('http://1.1.1.1/bad-image')->imageUrl)->toBeNull()
        ->and($client->fetch('http://1.1.1.1/empty')->title)->toBeNull()
        ->and($client->fetch('http://1.1.1.1/relative-image')->imageUrl)->toBe('http://1.1.1.1/relative-image/cover.png');
});

it('fetches a public IPv6 literal', function (): void {
    Http::fake([
        'http://[2001:4860:4860::8888]/*' => Http::response(
            '<html><head><title>v6</title></head></html>',
            200,
        ),
    ]);

    $preview = resolve(LinkPreviewClient::class)->fetch('http://[2001:4860:4860::8888]/');

    expect($preview->title)->toBe('v6');
});

it('does not send a request to a private, loopback, or link-local address', function (string $url): void {
    Http::fake();

    expect(fn () => resolve(LinkPreviewClient::class)->fetch($url))
        ->toThrow(LinkPreviewFailed::class);

    Http::assertNothingSent();
})->with([
    'loopback' => ['http://127.0.0.1/secret'],
    'rfc1918' => ['http://10.0.0.8/admin'],
    'link-local' => ['http://169.254.1.1/setup'],
    'ipv6-loopback' => ['http://[::1]/'],
    'decimal loopback' => ['http://2130706433/'],
]);

it('does not follow a redirect onto a private address', function (): void {
    Http::fake(function (Request $request) {
        if (str_starts_with($request->url(), 'http://1.1.1.1')) {
            return Http::response('', 302, ['Location' => 'http://127.0.0.1/secret']);
        }

        return Http::response('should not run', 200);
    });

    expect(fn () => resolve(LinkPreviewClient::class)->fetch('http://1.1.1.1/start'))
        ->toThrow(LinkPreviewFailed::class);

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'http://1.1.1.1'));
    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '127.0.0.1'));
});

it('follows a relative redirect on the same public host', function (): void {
    Http::fake(function (Request $request) {
        if ($request->url() === 'http://1.1.1.1/dir/start') {
            return Http::response('', 302, ['Location' => '/finish']);
        }

        return Http::response('<html><head><title>Landed</title></head></html>', 200);
    });

    $preview = resolve(LinkPreviewClient::class)->fetch('http://1.1.1.1/dir/start');

    expect($preview->title)->toBe('Landed');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://1.1.1.1/finish');
});

it('refuses an invalid redirect target', function (): void {
    Http::fake([
        'http://1.1.1.1/*' => Http::response('', 302, ['Location' => 'javascript:alert(1)']),
    ]);

    expect(fn () => resolve(LinkPreviewClient::class)->fetch('http://1.1.1.1/start'))
        ->toThrow(LinkPreviewFailed::class);
});

it('refuses a redirect without a location', function (): void {
    Http::fake([
        'http://1.1.1.1/*' => Http::response('', 302),
    ]);

    expect(fn () => resolve(LinkPreviewClient::class)->fetch('http://1.1.1.1/start'))
        ->toThrow(LinkPreviewFailed::class);
});

it('stops after three redirects', function (): void {
    Http::fake([
        'http://1.1.1.1/*' => Http::response('', 302, ['Location' => 'http://1.1.1.1/next']),
    ]);

    expect(fn () => resolve(LinkPreviewClient::class)->fetch('http://1.1.1.1/start'))
        ->toThrow(LinkPreviewFailed::class);

    Http::assertSentCount(4);
});

it('refuses a non-success response', function (): void {
    Http::fake([
        'http://1.1.1.1/*' => Http::response('nope', 500),
    ]);

    expect(fn () => resolve(LinkPreviewClient::class)->fetch('http://1.1.1.1/start'))
        ->toThrow(LinkPreviewFailed::class);
});

it('refuses a body larger than one mebibyte', function (): void {
    Http::fake([
        'http://1.1.1.1/*' => Http::response(str_repeat('a', LinkPreviewClient::MAX_BYTES + 1), 200),
    ]);

    expect(fn () => resolve(LinkPreviewClient::class)->fetch('http://1.1.1.1/start'))
        ->toThrow(LinkPreviewFailed::class);
});

it('wraps a connection failure', function (): void {
    Http::fake([
        'http://1.1.1.1/*' => Http::failedConnection(),
    ]);

    expect(fn () => resolve(LinkPreviewClient::class)->fetch('http://1.1.1.1/start'))
        ->toThrow(LinkPreviewFailed::class);
});

it('rethrows a preview failure raised during the request', function (): void {
    Http::fake(function (): never {
        throw LinkPreviewFailed::tooLarge();
    });

    expect(fn () => resolve(LinkPreviewClient::class)->fetch('http://1.1.1.1/start'))
        ->toThrow(LinkPreviewFailed::class);
});

it('does not fetch when DNS resolves to a private address', function (): void {
    Http::fake();

    expect(fn () => resolve(LinkPreviewClient::class)->fetch('http://localhost/'))
        ->toThrow(LinkPreviewFailed::class);

    Http::assertNothingSent();
});

it('aborts a download that exceeds the size cap', function (): void {
    expect(fn () => LinkPreviewClient::abortIfTooLarge(0, LinkPreviewClient::MAX_BYTES + 1))
        ->toThrow(LinkPreviewFailed::class);

    LinkPreviewClient::abortIfTooLarge(0, 'chunk');
});
