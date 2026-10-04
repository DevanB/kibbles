<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
function boostConfig(): array
{
    $config = json_decode((string) file_get_contents(base_path('boost.json')), true);

    expect($config)->toBeArray();

    /** @var array<string, mixed> $config */
    return $config;
}

/**
 * @return array<string, mixed>
 */
function composerConfig(): array
{
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);

    expect($composer)->toBeArray();

    /** @var array<string, mixed> $composer */
    return $composer;
}

it('keeps existing composer post-update scripts and runs boost:update', function (): void {
    $scripts = composerConfig()['scripts'] ?? [];

    expect($scripts)->toBeArray();

    /** @var array<string, mixed> $scripts */
    $postUpdate = $scripts['post-update-cmd'] ?? [];

    expect($postUpdate)->toBeArray()
        ->toContain('@php artisan vendor:publish --tag=laravel-assets --ansi --force')
        ->toContain('@php artisan clear')
        ->toContain('@php artisan boost:update --ansi')
        ->toContain('@update:requirements');
});

it('tracks package-aligned boost skills', function (): void {
    $skills = boostConfig()['skills'] ?? [];

    expect($skills)->toBeArray()
        ->toContain('inertia-react-development')
        ->toContain('tailwindcss-development')
        ->toContain('testing-best-practices')
        ->toContain('fortify-development')
        ->toContain('wayfinder-development')
        ->toContain('laravel-best-practices')
        ->toContain('infer-conventions')
        ->not->toContain('verify-kibbles')
        ->not->toContain('pest-testing');
});

it('publishes boost skills for cursor and github agents', function (string $skill): void {
    expect(base_path('.cursor/skills/'.$skill.'/SKILL.md'))->toBeFile()
        ->and(base_path('.github/skills/'.$skill.'/SKILL.md'))->toBeFile();
})->with([
    'inertia-react-development',
    'tailwindcss-development',
    'testing-best-practices',
    'fortify-development',
    'wayfinder-development',
    'laravel-best-practices',
    'infer-conventions',
]);

it('preserves the project-local verify-kibbles skill', function (): void {
    $skill = base_path('.cursor/skills/verify-kibbles/SKILL.md');

    expect($skill)->toBeFile()
        ->and((string) file_get_contents($skill))->toContain('Verify kibbles')
        ->and(base_path('.cursor/skills/verify-kibbles/bin/doctor'))->toBeFile();
});

it('refreshes agent guidelines for the published skills', function (): void {
    $guidelines = (string) file_get_contents(base_path('AGENTS.md'));

    expect($guidelines)
        ->toContain('inertia-react-development')
        ->toContain('testing-best-practices')
        ->toContain('Activate `inertia-react-development`');
});
