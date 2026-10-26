<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Models\XBookmark;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class StoreBookmarkReviewRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $user): bool
    {
        return $user->can('update', $this->bookmark());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'bookmark_id' => ['required', 'uuid'],
        ];
    }

    public function bookmark(): XBookmark
    {
        return XBookmark::query()->findOrFail($this->string('bookmark_id')->value());
    }
}
