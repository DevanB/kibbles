<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Game;
use App\Models\PlaySession;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;

final class StorePlaySessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('game');

        return $game instanceof Game && ($this->user()?->can('create', [PlaySession::class, $game]) ?? false);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date', 'after_or_equal:started_at'],
            'timezone' => ['required', 'timezone:all'],
        ];
    }

    public function startedAt(): CarbonInterface
    {
        return PlaySession::fromBrowserLocal(
            $this->string('started_at')->value(),
            $this->string('timezone')->value(),
        );
    }

    public function endedAt(): CarbonInterface
    {
        return PlaySession::fromBrowserLocal(
            $this->string('ended_at')->value(),
            $this->string('timezone')->value(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'started_at.required' => 'A start time is required.',
            'started_at.date' => 'The start time must be a valid date.',
            'ended_at.required' => 'An end time is required.',
            'ended_at.date' => 'The end time must be a valid date.',
            'ended_at.after_or_equal' => 'The end time must be at or after the start time.',
            'timezone.required' => 'A timezone is required.',
            'timezone.timezone' => 'The timezone must be a valid timezone.',
        ];
    }
}
