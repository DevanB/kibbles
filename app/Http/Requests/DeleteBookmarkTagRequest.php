<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteBookmarkTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $bookmark = $this->route('bookmark');
        $tag = $this->route('tag');

        return $user instanceof User
            && $bookmark instanceof XBookmark
            && $tag instanceof Tag
            && $user->can('update', $bookmark)
            && $user->can('view', $tag);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
