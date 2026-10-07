<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final readonly class RawgClient
{
    /**
     * @return list<RawgSearchResult>
     */
    public function search(string $query): array
    {
        $response = $this->send('/games', [
            'search' => $query,
            'page_size' => 8,
        ]);

        if ($response === null) {
            return [];
        }

        return array_values(
            $response->collect('results')
                ->map(RawgSearchResult::from(...))
                ->all(),
        );
    }

    public function find(int $id): ?RawgGameDetail
    {
        $response = $this->send('/games/'.$id);

        return $response === null ? null : RawgGameDetail::from($response->json());
    }

    /**
     * @param  array<string, int|string>  $query
     */
    private function send(string $path, array $query = []): ?Response
    {
        $key = $this->key();

        if ($key === null) {
            return null;
        }

        try {
            $response = $this->request()->get($path, [
                'key' => $key,
                ...$query,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        return $response->successful() ? $response : null;
    }

    private function key(): ?string
    {
        $key = config('services.rawg.key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl('https://api.rawg.io/api')
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(5);
    }
}
