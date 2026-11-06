<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthenticateSoleOwner
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $owner = User::query()->sole();
        } catch (ModelNotFoundException|MultipleRecordsFoundException) {
            abort(401);
        }

        auth()->setUser($owner);
        $request->setUserResolver(fn (): User => $owner);

        return $next($request);
    }
}
