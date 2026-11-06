<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LinkPreviewStatus;
use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Link>
 */
final class LinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $host = fake()->unique()->domainName();

        return [
            'user_id' => User::factory(),
            'url' => 'https://'.$host.'/article',
            'title' => fake()->sentence(6),
            'site_name' => fake()->company(),
            'description' => fake()->sentence(),
            'image_url' => null,
            'preview_status' => LinkPreviewStatus::Ready,
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (): array => [
            'title' => null,
            'site_name' => null,
            'description' => null,
            'image_url' => null,
            'preview_status' => LinkPreviewStatus::Pending,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (): array => [
            'title' => null,
            'site_name' => null,
            'description' => null,
            'image_url' => null,
            'preview_status' => LinkPreviewStatus::Failed,
        ]);
    }
}
