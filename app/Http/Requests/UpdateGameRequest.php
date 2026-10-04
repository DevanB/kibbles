<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Game;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateGameRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        assert($user instanceof User);

        $game = $this->route('game');
        assert($game instanceof Game);

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Game::class)->where('user_id', $user->id)->ignore($game->id),
            ],
        ];
    }
}
