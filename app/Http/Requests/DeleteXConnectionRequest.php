<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Models\XConnection;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteXConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $connection = $user instanceof User ? $user->xConnection : null;

        return $connection instanceof XConnection && $user->can('delete', $connection);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
