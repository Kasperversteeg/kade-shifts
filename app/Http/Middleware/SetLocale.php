<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the application locale based on user preference, browser language, or default.
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);

        return $next($request);
    }

    /**
     * Resolve the locale from user preferences, browser header, or fallback.
     */
    private function resolveLocale(Request $request): string
    {
        $supportedLocales = ['en', 'nl'];

        // 1. Authenticated user's preference
        $user = $request->user();
        if ($user) {
            $preference = $user->preferences['language'] ?? null;
            if ($preference && in_array($preference, $supportedLocales)) {
                return $preference;
            }
        }

        // 2. Browser's Accept-Language header
        $browserLocale = $request->getPreferredLanguage($supportedLocales);
        if ($browserLocale) {
            return $browserLocale;
        }

        // 3. Fallback
        return 'en';
    }
}
