<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBookmarkTagRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $user): bool
    {
        return $user->can('update', $this->route('bookmark'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(#[CurrentUser] User $user): array
    {
        return [
            'tag_id' => [
                'required_without:name',
                'nullable',
                'uuid',
                Rule::exists(Tag::class, 'id')->where('user_id', $user->id),
            ],
            'name' => ['required_without:tag_id', 'nullable', 'string', 'max:255'],
        ];
    }

    public function tag(): Tag|string
    {
        if ($this->filled('tag_id')) {
            return Tag::query()->findOrFail($this->string('tag_id')->value());
        }

        return $this->string('name')->value();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tag_id.required_without' => 'A tag is required.',
            'tag_id.uuid' => 'The tag id must be a valid UUID.',
            'tag_id.exists' => 'The selected tag does not exist.',
            'name.required_without' => 'A tag name is required.',
            'name.string' => 'The tag name must be a string.',
            'name.max' => 'The tag name may not be greater than 255 characters.',
        ];
    }
}
