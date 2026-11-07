<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ResolvesPublicIps;
use App\Exceptions\InvalidLinkUrl;
use App\Exceptions\LinkPreviewFailed;
use App\ValueObjects\LinkPreview;
use App\ValueObjects\LinkUrl;
use DOMDocument;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final readonly class LinkPreviewClient
{
    public const int MAX_REDIRECTS = 3;

    public const int MAX_BYTES = 1_048_576;

    public const int CONNECT_TIMEOUT = 3;

    public const int TOTAL_TIMEOUT = 5;

    public function __construct(private ResolvesPublicIps $ips) {}

    public static function abortIfTooLarge(mixed $downloadTotal, mixed $downloaded): void
    {
        unset($downloadTotal);

        if ((is_int($downloaded) ? $downloaded : 0) > self::MAX_BYTES) {
            throw LinkPreviewFailed::tooLarge();
        }
    }

    public function fetch(string $url): LinkPreview
    {
        $current = LinkUrl::parse($url)->value;

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $response = $this->get($current);
            $status = $response->status();

            if ($status >= 300 && $status < 400) {
                $location = $response->header('Location');

                if ($location === '') {
                    throw LinkPreviewFailed::http($status);
                }

                try {
                    $current = $this->absoluteUrl($current, $location);
                } catch (InvalidLinkUrl) {
                    throw LinkPreviewFailed::http($status);
                }

                continue;
            }

            if ($response->failed()) {
                throw LinkPreviewFailed::http($status);
            }

            $body = $response->body();

            if (mb_strlen($body, '8bit') > self::MAX_BYTES) {
                throw LinkPreviewFailed::tooLarge();
            }

            return $this->parse($body, $current);
        }

        throw LinkPreviewFailed::tooManyRedirects();
    }

    private function curlResolve(string $host, int $port, string $ip): string
    {
        return $this->bracketIp($host).':'.$port.':'.$this->bracketIp($ip);
    }

    private function bracketIp(string $value): string
    {
        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return '['.$value.']';
        }

        return $value;
    }

    private function get(string $url): Response
    {
        $parsed = LinkUrl::parse($url);
        $parts = parse_url($parsed->value);
        $scheme = mb_strtolower((string) ($parts['scheme'] ?? 'https'));
        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'http' ? 80 : 443);
        $ip = $this->ips->resolve($parsed->host)[0];

        try {
            return Http::timeout(self::TOTAL_TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->withHeaders([
                    'Accept' => 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8',
                ])
                ->withOptions([
                    'allow_redirects' => false,
                    'protocols' => ['http', 'https'],
                    'curl' => [
                        CURLOPT_RESOLVE => [$this->curlResolve($parsed->host, $port, $ip)],
                        CURLOPT_PREREQFUNCTION => $this->ips->curlPrereq(...),
                    ],
                    'progress' => self::abortIfTooLarge(...),
                ])
                ->get($url);
        } catch (LinkPreviewFailed $failed) {
            throw $failed;
        } catch (Throwable $e) {
            throw LinkPreviewFailed::http(0, $e);
        }
    }

    private function absoluteUrl(string $current, string $location): string
    {
        if (parse_url($location, PHP_URL_SCHEME) !== null) {
            return LinkUrl::parse($location)->value;
        }

        $parts = parse_url($current);
        $scheme = (string) ($parts['scheme'] ?? 'https');
        $host = (string) ($parts['host'] ?? '');
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $base = $scheme.'://'.$host.$port;
        $path = str_starts_with($location, '/')
            ? $location
            : mb_rtrim((string) ($parts['path'] ?? '/'), '/').'/'.$location;

        return LinkUrl::parse($base.$path)->value;
    }

    private function parse(string $html, string $finalUrl): LinkPreview
    {
        if ($html === '') {
            return new LinkPreview(title: null, siteName: null, description: null, imageUrl: null);
        }

        $document = new DOMDocument;
        @$document->loadHTML($html);

        $og = [];

        foreach ($document->getElementsByTagName('meta') as $meta) {
            $property = $meta->getAttribute('property');
            $name = $meta->getAttribute('name');
            $content = mb_trim($meta->getAttribute('content'));

            if ($content === '') {
                continue;
            }

            $key = $property !== '' ? $property : $name;

            if ($key !== '') {
                $og[$key] = $content;
            }
        }

        $title = $og['og:title'] ?? $this->documentTitle($document);
        $image = $og['og:image'] ?? null;

        return new LinkPreview(
            title: $this->nullableString($title),
            siteName: $this->nullableString($og['og:site_name'] ?? null),
            description: $this->nullableString($og['og:description'] ?? $og['description'] ?? null),
            imageUrl: $this->publicImageUrl($image, $finalUrl),
        );
    }

    private function documentTitle(DOMDocument $document): ?string
    {
        $nodes = $document->getElementsByTagName('title');
        $title = $nodes->item(0)?->textContent;

        return is_string($title) ? mb_trim($title) : null;
    }

    private function publicImageUrl(?string $image, string $finalUrl): ?string
    {
        if ($image === null || $image === '') {
            return null;
        }

        try {
            $url = str_starts_with($image, 'http://') || str_starts_with($image, 'https://')
                ? LinkUrl::parse($image)
                : LinkUrl::parse($this->absoluteUrl($finalUrl, $image));
        } catch (Throwable) {
            return null;
        }

        return $url->fetchable ? $url->value : null;
    }

    private function nullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
