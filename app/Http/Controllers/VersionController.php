<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

final readonly class VersionController
{
    public function __invoke(): Response
    {
        return response($this->revision(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function revision(): string
    {
        $revision = config('app.revision');

        if (! is_string($revision) || $revision === '') {
            return 'dev';
        }

        return $revision;
    }
}
