<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class JournalEntryRules
{
    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
            'next' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'body.required' => 'A body is required.',
            'body.string' => 'The body must be a string.',
            'body.max' => 'The body may not be greater than 10000 characters.',
            'next.string' => 'The next plan must be a string.',
            'next.max' => 'The next plan may not be greater than 2000 characters.',
        ];
    }
}
