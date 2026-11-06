<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Link;
use App\ValueObjects\LinkUrl;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Link::class);
    }

    /**
     * @return array<string, array<int, Closure|string>>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    unset($attribute);

                    if (! is_string($value) || ! LinkUrl::isValid($value)) {
                        $fail('Enter a valid http or https URL.');
                    }
                },
            ],
        ];
    }

    public function linkUrl(): string
    {
        return $this->string('url')->value();
    }
}
