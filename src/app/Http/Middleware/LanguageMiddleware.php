<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     * 
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = ['pt-BR', 'pt', 'en'];
        
        $locale = $request->query('lang');
        if ($locale && in_array($locale, $supportedLocales)) {
            App::setLocale($locale);
            return $next($request);
        }

        $preferredLanguage = $request->getPreferredLanguage($supportedLocales);
        
        if ($preferredLanguage) {
            // Convert pt_BR to pt-BR format if needed
            $locale = str_replace('_', '-', $preferredLanguage);
            
            // Map pt to pt-BR
            if ($locale === 'pt') {
                $locale = 'pt-BR';
            }
            
            App::setLocale($locale);
        } else {
            // Fallback to English
            App::setLocale('en');
        }

        return $next($request);
    }
}
