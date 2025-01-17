<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Assuming you have a method to check if the user is admin
        // if (!$request->user() || !$request->user()->isAdmin()) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }
        if(Auth::user()->role == 'admin'){
            return $next($request);
        }
        abort(401);

    }
}
