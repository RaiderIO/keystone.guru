<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\UserIpAddress;
use Auth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TracksUserIpAddress
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Maybe this should be handled differently? Idk how heavy these queries will be
        if (!$request->ajax() && Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            UserIpAddress::upsert(
                [
                    'user_id'    => $user->id,
                    'ip_address' => $request->ip(),
                    'count'      => 1,
                    // Default value for new rows
                    'updated_at' => now(),
                    // Example of tracking when a row is updated
                ],
                [
                    'user_id',
                    'ip_address',
                ],
                [
                    'count' => DB::raw('count + 1'),
                    'updated_at',
                ] // Update these columns if a conflict occurs
            );
        }

        return $next($request);
    }
}
