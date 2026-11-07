<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ResolvesPublicIps;
use App\Services\PublicIpResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ResolvesPublicIps::class, PublicIpResolver::class);
    }

    public function boot(): void
    {
        FormRequest::failOnUnknownFields(! app()->isProduction());
    }
}
