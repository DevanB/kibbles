<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use LogicException;

final class StoreBookmarkTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $bookmark = $this->route('bookmark');

        if (! $user instanceof User || ! $bookmark instanceof XBookmark || ! $user->can('update', $bookmark)) {
            return false;
        }

        if ($this->filled('tag_id')) {
            $tagId = $this->string('tag_id')->value();

            if (! Str::isUuid($tagId)) {
                return true;
            }

            $tag = Tag::query()->find($tagId);

            return $tag instanceof Tag && $user->can('view', $tag);
        }

        return $user->can('create', Tag::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tag_id' => ['required_without:name', 'nullable', 'uuid'],
            'name' => ['required_without:tag_id', 'nullable', 'string', 'max:255'],
        ];
    }

    public function tag(): Tag|string
    {
        if ($this->filled('tag_id')) {
            $tag = Tag::query()->find($this->string('tag_id')->value());

            throw_unless($tag instanceof Tag, LogicException::class, 'The tag_id must resolve to a '.Tag::class.' instance.');

            return $tag;
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
            'name.required_without' => 'A tag name is required.',
            'name.string' => 'The tag name must be a string.',
            'name.max' => 'The tag name may not be greater than 255 characters.',
        ];
    }
}
