<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\UniqueOwnedTagName;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateTagRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $user): bool
    {
        return $user->can('update', $this->route('tag'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(#[CurrentUser] User $user): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueOwnedTagName($user, (string) $this->route()?->originalParameter('tag')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A name is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'name.unique' => UniqueOwnedTagName::message(),
        ];
    }
}
