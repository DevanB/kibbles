<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\XConnection;
use Illuminate\Foundation\Http\FormRequest;

final class StoreXConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', XConnection::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
