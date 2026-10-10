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

    public function longText(): self
    {
        return $this->state(fn (): array => [
            'text' => str_repeat('Saved from the timeline. ', 20),
        ]);
    }

    public function withoutMedia(): self
    {
        return $this->state(fn (): array => [
            'media' => [],
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

    public function quotedWithMedia(): self
    {
        return $this->quoted()->state(fn (): array => [
            'quoted_post' => new XPost(
                authorName: 'Quoted Author',
                authorUsername: 'quoted',
                authorAvatarUrl: 'https://avatars.githubusercontent.com/u/499550?s=96&v=4',
                text: 'The quoted post with a photo.',
                postedAt: now()->subDays(2),
                media: [
                    new XMedia(
                        type: XMediaType::Photo,
                        url: 'https://placehold.co/560x560/9a3412/ffffff/jpeg',
                        previewUrl: null,
                        width: 560,
                        height: 560,
                        mp4Url: null,
                    ),
                ],
                url: 'https://x.com/quoted/status/99',
            ),
        ]);
    }

    public function hotlinkedPhoto(string $url, int $width, int $height): self
    {
        return $this->state(fn (): array => [
            'media' => [
                new XMedia(
                    type: XMediaType::Photo,
                    url: $url,
                    previewUrl: null,
                    width: $width,
                    height: $height,
                    mp4Url: null,
                ),
            ],
        ]);
    }

    public function hotlinkedVideo(): self
    {
        return $this->state(fn (): array => [
            'media' => [
                new XMedia(
                    type: XMediaType::Video,
                    url: 'https://placehold.co/1280x720/111827/ffffff/jpeg',
                    previewUrl: 'https://placehold.co/1280x720/111827/ffffff/jpeg',
                    width: 1280,
                    height: 720,
                    mp4Url: 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
                ),
            ],
        ]);
    }
}
