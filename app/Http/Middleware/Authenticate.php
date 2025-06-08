<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // For API routes, we'll handle the response in the unauthenticated exception handler
        // Only handle web routes with a redirect here
        if (! $request->expectsJson() && !str_contains($request->path(), 'api')) {
            return route('api.v1.login'); // This route doesn't need to exist for API-only apps
        }
        
        return null; // Return null for API routes to trigger a JSON response
    }
}
