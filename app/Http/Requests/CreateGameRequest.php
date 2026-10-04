<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Game;
use App\Models\User;
use App\Rules\UniqueOwnedGameTitle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
        assert($owner instanceof User);

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                new UniqueOwnedGameTitle($owner),
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
        ];
    }
}
