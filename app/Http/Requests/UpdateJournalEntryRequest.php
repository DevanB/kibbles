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
        return [
            'body' => ['required', 'string', 'max:10000'],
            'next' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'A body is required.',
            'body.string' => 'The body must be a string.',
            'body.max' => 'The body may not be greater than 10000 characters.',
            'next.string' => 'The next plan must be a string.',
            'next.max' => 'The next plan may not be greater than 2000 characters.',
        ];
    }
}
