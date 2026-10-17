<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\JournalEntry;
use App\Rules\LinkablePlaySession;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

final class UpdateJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entry = $this->route('journal_entry');

        return $entry instanceof JournalEntry && ($this->user()?->can('update', $entry) ?? false);
    }

    /**
     * @return array<string, list<string|LinkablePlaySession>>
     */
    public function rules(): array
    {
        $entry = $this->route('journal_entry');

        throw_unless($entry instanceof JournalEntry, LogicException::class, 'The route journal entry must be a '.JournalEntry::class.' instance.');

        return [
            'body' => ['required', 'string', 'max:10000'],
            'play_session_id' => LinkablePlaySession::rules($entry->game, $entry),
        ];
    }

    public function playSessionId(): ?string
    {
        $value = $this->validated('play_session_id');

        return is_string($value) ? $value : null;
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
            ...LinkablePlaySession::messages(),
        ];
    }

    public function prepareForValidation(): void
    {
        if (in_array($this->input('play_session_id'), ['', 'none'], true)) {
            $this->merge(['play_session_id' => null]);
        }
    }
}
