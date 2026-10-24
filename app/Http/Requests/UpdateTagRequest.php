<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tag;
use App\Models\User;
use App\Rules\UniqueOwnedTagName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

final class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tag = $this->route('tag');

        return $tag instanceof Tag && ($this->user()?->can('update', $tag) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $owner = $this->user();

        throw_unless($owner instanceof User, LogicException::class, 'The authenticated user must be an '.User::class.' instance.');

        $tag = $this->route('tag');

        throw_unless($tag instanceof Tag, LogicException::class, 'The tag route parameter must be a '.Tag::class.' instance.');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueOwnedTagName($owner, $tag),
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
