<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('game');

        return $game instanceof Game && ($this->user()?->can('delete', $game) ?? false);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
