<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTemplateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $limit = $user->templateLimit();

        if ($limit !== null && $user->templates()->count() >= $limit) {
            return response()->json([
                'message' => "Limite del plan gratuito alcanzado ({$limit} plantillas). Pasate al plan pro para plantillas ilimitadas.",
                'code' => 'PLAN_LIMIT',
            ], 403);
        }

        return $next($request);
    }
}
