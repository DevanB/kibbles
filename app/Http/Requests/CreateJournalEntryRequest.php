<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Game;
use App\Models\JournalEntry;
use App\Rules\LinkablePlaySession;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

final class CreateJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('game');

        return $game instanceof Game && ($this->user()?->can('create', [JournalEntry::class, $game]) ?? false);
    }

    /**
     * @return array<string, list<string|LinkablePlaySession>>
     */
    public function rules(): array
    {
        $game = $this->route('game');

        throw_unless($game instanceof Game, LogicException::class, 'The route game must be a '.Game::class.' instance.');

        return [
            'body' => ['required', 'string', 'max:10000'],
            'play_session_id' => LinkablePlaySession::rules($game),
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

    protected function prepareForValidation(): void
    {
        if (in_array($this->input('play_session_id'), ['', 'none'], true)) {
            $this->merge(['play_session_id' => null]);
        }
    }
}
