<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\XBookmark;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteXBookmarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bookmark = $this->route('bookmark');

        return $bookmark instanceof XBookmark && ($this->user()?->can('delete', $bookmark) ?? false);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
