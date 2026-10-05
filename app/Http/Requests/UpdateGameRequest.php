<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use App\Rules\UniqueOwnedGameTitle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('game');

        return $game instanceof Game && ($this->user()?->can('update', $game) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $owner = $this->user();
        assert($owner instanceof User);

        $game = $this->route('game');
        assert($game instanceof Game);

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                new UniqueOwnedGameTitle($owner, $game),
            ],
            'status' => [
                'required',
                Rule::enum(GameStatus::class),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'A title is required.',
            'title.string' => 'The title must be a string.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'title.unique' => UniqueOwnedGameTitle::message(),
            'status.required' => 'A status is required.',
            'status.enum' => 'The status must be Backlog, In Progress, Abandoned, or Finished.',
        ];
    }
}
