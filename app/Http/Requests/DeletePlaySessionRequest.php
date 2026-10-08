<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PlaySession;
use Illuminate\Foundation\Http\FormRequest;

final class DeletePlaySessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('play_session');

        return $session instanceof PlaySession && ($this->user()?->can('delete', $session) ?? false);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
