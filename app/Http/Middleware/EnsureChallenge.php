<?php

namespace App\Http\Middleware;


use App\Support\AuthChallenge;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\OtpCode;

class EnsureChallenge
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type)
    {
        if (Auth::check()) return redirect()->intended(route('dashboard'));

        $c = AuthChallenge::get($request);
        if (! $c || ($c['type'] ?? null) !== $type) {
            return redirect()->route('login');
        }

        if ($type === 'otp' && $request->isMethod('get')) {
            $hasActive = OtpCode::for($c['user_unique'], $c['purpose'] ?? 'login', $c['channel'] ?? null)
                ->active()
                ->exists();
            if (! $hasActive) {
                AuthChallenge::clear($request);
                return redirect()->route('login');
            }
        }

        if ($type === 'totp') {
            $until = (int)($c['until'] ?? 0);
            if ($until && time() > $until) {
                AuthChallenge::clear($request);
                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
