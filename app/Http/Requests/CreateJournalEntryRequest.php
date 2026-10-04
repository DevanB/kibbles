<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Game;
use App\Models\JournalEntry;
use Illuminate\Foundation\Http\FormRequest;

final class CreateJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('game');

        return $game instanceof Game && ($this->user()?->can('create', [JournalEntry::class, $game]) ?? false);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return JournalEntryRules::rules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return JournalEntryRules::messages();
    }
}
