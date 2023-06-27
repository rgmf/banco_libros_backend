<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');

        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = substr($token, 7);
        $user = User::where('gdc_token', $token)->first();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (Carbon::parse($user->gdc_token_expiration)->isPast()) {
            $user->delete();
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
