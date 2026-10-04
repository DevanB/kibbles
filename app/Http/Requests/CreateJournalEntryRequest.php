<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\User;
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
        $owner = $this->user();
        assert($owner instanceof User);

        $game = $this->route('game');
        assert($game instanceof Game);

        return [
            'body' => ['required', 'string', 'max:10000'],
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
        ];
    }
}
