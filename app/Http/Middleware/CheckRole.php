<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
   public function handle(Request $request, Closure $next, $roles): Response
{
    if (!auth()->check()) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $roles = explode('|', $roles);

    if (!in_array(auth()->user()->role, $roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    return $next($request);
}
}
