<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ListRecentJournalEntries;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function __invoke(#[CurrentUser] User $user, ListRecentJournalEntries $list): Response
    {
        return Inertia::render('dashboard', [
            'recentJournalEntries' => $list->handle($user),
        ]);
    }
}
