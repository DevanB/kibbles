<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\XMediaType;
use App\Models\User;
use App\Models\XBookmark;
use App\ValueObjects\XMedia;
use App\ValueObjects\XPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<XBookmark>
 */
final class XBookmarkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $username = fake()->unique()->userName();

        return [
            'user_id' => User::factory(),
            'x_post_id' => (string) fake()->unique()->numerify('################'),
            'author_name' => fake()->name(),
            'author_username' => $username,
            'author_avatar_url' => 'https://pbs.twimg.com/profile_images/1/avatar.jpg',
            'text' => fake()->sentence(),
            'posted_at' => now()->subDay(),
            'first_seen_at' => now()->subHour(),
            'media' => [
                new XMedia(
                    type: XMediaType::Photo,
                    url: 'https://pbs.twimg.com/media/demo.jpg',
                    previewUrl: null,
                    width: 1200,
                    height: 800,
                    mp4Url: null,
                ),
            ],
            'quoted_post' => null,
        ];
    }

    public function video(): self
    {
        return $this->state(fn (): array => [
            'media' => [
                new XMedia(
                    type: XMediaType::Video,
                    url: 'https://pbs.twimg.com/ext_tw_video_thumb/demo.jpg',
                    previewUrl: 'https://pbs.twimg.com/ext_tw_video_thumb/demo.jpg',
                    width: 1280,
                    height: 720,
                    mp4Url: 'https://video.twimg.com/high.mp4',
                ),
            ],
        ]);
    }

    public function quoted(): self
    {
        return $this->state(fn (): array => [
            'quoted_post' => new XPost(
                authorName: 'Quoted Author',
                authorUsername: 'quoted',
                authorAvatarUrl: 'https://pbs.twimg.com/profile_images/2/avatar.jpg',
                text: 'The quoted post.',
                postedAt: now()->subDays(2),
                media: [],
                url: 'https://x.com/quoted/status/99',
            ),
        ]);
    }
}
