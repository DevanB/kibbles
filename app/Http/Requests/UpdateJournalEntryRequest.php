<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\JournalEntry;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entry = $this->route('journal_entry');

        return $entry instanceof JournalEntry && ($this->user()?->can('update', $entry) ?? false);
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
