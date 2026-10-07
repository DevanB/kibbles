<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final readonly class RawgClient
{
    /**
     * @return list<RawgSearchResult>
     */
    public function search(string $query): array
    {
        if ($this->key() === null || mb_trim($query) === '') {
            return [];
        }

        try {
            $response = $this->request()->get('/games', [
                'key' => $this->key(),
                'search' => $query,
                'page_size' => 8,
            ]);
        } catch (ConnectionException) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $results = $response->json('results');

        if (! is_array($results)) {
            return [];
        }

        return array_values(array_filter(array_map(
            RawgSearchResult::tryFrom(...),
            $results,
        )));
    }

    public function find(int $id): ?RawgGameDetail
    {
        if ($this->key() === null) {
            return null;
        }

        try {
            $response = $this->request()->get('/games/'.$id, [
                'key' => $this->key(),
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return RawgGameDetail::tryFrom($response->json());
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
