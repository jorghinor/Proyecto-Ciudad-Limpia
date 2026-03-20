<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $user = Auth::user();

        foreach ($roles as $role) {
            if ($user->rol === $role) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
    }
}
