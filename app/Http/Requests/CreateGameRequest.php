<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Game;
use App\Models\User;
use App\Rules\UniqueOwnedGameTitle;
use App\Rules\UniqueOwnedRawgId;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

final class CreateGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Game::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $owner = $this->user();

        throw_unless($owner instanceof User, LogicException::class, 'The authenticated user must be an '.User::class.' instance.');

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                new UniqueOwnedGameTitle($owner),
            ],
            'rawg_id' => [
                'nullable',
                'integer',
                'min:1',
                new UniqueOwnedRawgId($owner),
            ],
        ];
    }

    public function rawgId(): ?int
    {
        return $this->filled('rawg_id') ? $this->integer('rawg_id') : null;
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
            'rawg_id.integer' => 'The RAWG id must be an integer.',
            'rawg_id.min' => 'The RAWG id must be an integer.',
            'rawg_id.unique' => UniqueOwnedRawgId::message(),
        ];
    }
}
