<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PlaySession;
use Illuminate\Foundation\Http\FormRequest;

final class FinishPlaySessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('play_session');

        return $session instanceof PlaySession && ($this->user()?->can('update', $session) ?? false);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function body(): ?string
    {
        return $this->filled('body') ? $this->string('body')->value() : null;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.string' => 'The body must be a string.',
            'body.max' => 'The body may not be greater than 10000 characters.',
        ];
    }
}
