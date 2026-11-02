<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthenticateKibblesApi
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.kibbles_api.token');
        $provided = $request->bearerToken();

        abort_if(
            ! is_string($expected) || $expected === '' || ! is_string($provided) || ! hash_equals($expected, $provided),
            401,
        );

        return $next($request);
    }
}
