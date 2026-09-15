<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\UserIpAddress;
use Auth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TracksUserIpAddress
{
    /**
     * A user/IP pair is written at most once per interval, so `count` is the number of intervals the pair was
     * seen in, not the number of requests. Only the set of IP addresses is ever read back.
     */
    private const int TRACK_INTERVAL_SECONDS = 3600;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->ajax() && Auth::check()) {
            /** @var User $user */
            $user      = Auth::user();
            $ipAddress = $request->ip();

            if (Cache::add(sprintf('user-ip-tracked:%d:%s', $user->id, $ipAddress), 1, self::TRACK_INTERVAL_SECONDS)) {
                UserIpAddress::upsert(
                    [
                        'user_id'    => $user->id,
                        'ip_address' => $ipAddress,
                        'count'      => 1,
                        'updated_at' => now(),
                    ],
                    [
                        'user_id',
                        'ip_address',
                    ],
                    [
                        'count' => DB::raw('count + 1'),
                        'updated_at',
                    ],
                );
            }
        }

        return $next($request);
    }
}
