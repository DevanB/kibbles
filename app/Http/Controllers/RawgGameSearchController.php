<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchRawgGamesRequest;
use App\Services\RawgClient;
use App\Services\RawgSearchResult;
use Illuminate\Http\JsonResponse;

final readonly class RawgGameSearchController
{
    public function __invoke(SearchRawgGamesRequest $request, RawgClient $rawg): JsonResponse
    {
        return response()->json([
            'results' => array_map(
                static fn (RawgSearchResult $result): array => $result->toArray(),
                $rawg->search($request->string('query')->value()),
            ),
        ]);
    }
}
