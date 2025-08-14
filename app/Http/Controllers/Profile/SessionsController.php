<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SessionsController extends Controller
{
    public function index(Request $request)
    {
        $now = now()->timestamp;

        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity']);

        $items = $sessions->map(function ($s) use ($request, $now) {
            $ua = strtolower($s->user_agent ?? '');

            // Device type
            $isBot    = str_contains($ua, 'bot');
            $isTablet = str_contains($ua, 'ipad') || str_contains($ua, 'tablet');
            $isMobile = str_contains($ua, 'android') || str_contains($ua, 'iphone') || str_contains($ua, 'mobile');
            $device   = $isBot ? 'bot' : ($isTablet ? 'tablet' : ($isMobile ? 'mobile' : 'desktop'));

            // Browser
            $browser = str_contains($ua, 'edg') ? 'Edge'
                : (str_contains($ua, 'chrome') && !str_contains($ua, 'edg') ? 'Chrome'
                    : (str_contains($ua, 'firefox') ? 'Firefox'
                        : (str_contains($ua, 'safari') && !str_contains($ua, 'chrome') ? 'Safari'
                            : 'Browser')));

            // OS
            $os = str_contains($ua, 'windows') ? 'Windows'
                : (str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') ? 'macOS'
                    : (str_contains($ua, 'android') ? 'Android'
                        : (str_contains($ua, 'iphone') || str_contains($ua, 'ios') ? 'iOS'
                            : (str_contains($ua, 'linux') ? 'Linux' : 'OS'))));

            return [
                'id'          => $s->id,
                'ip'          => $s->ip_address,
                'agent'       => "{$browser} on {$os}",
                'device'      => $device, // 'desktop' | 'mobile' | 'tablet' | 'bot'
                'active'      => ($now - (int) $s->last_activity) <= 90, // <= 90s => online
                'is_current'  => $s->id === $request->session()->getId(),
                'last_seen'   => Carbon::createFromTimestamp($s->last_activity)->diffForHumans(),
                'last_activity_unix' => (int) $s->last_activity,
            ];
        });

        return inertia('settings/Sessions', ['sessions' => $items]);
    }

    public function destroyOthers(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('status', 'Sesi lain berhasil dilogout.');
    }

    private function shortAgent(?string $ua): string
    {
        if (!$ua) return 'Unknown';
        $ua = strtolower($ua);
        $browser = str_contains($ua, 'chrome') ? 'Chrome' : (str_contains($ua, 'firefox') ? 'Firefox' : (str_contains($ua, 'safari') ? 'Safari' : (str_contains($ua, 'edge') ? 'Edge' : 'Browser')));
        $os = str_contains($ua, 'windows') ? 'Windows' : (str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') ? 'macOS' : (str_contains($ua, 'linux') ? 'Linux' : (str_contains($ua, 'android') ? 'Android' : (str_contains($ua, 'iphone') || str_contains($ua, 'ios') ? 'iOS' : 'OS'))));
        return "{$browser} on {$os}";
    }
}
