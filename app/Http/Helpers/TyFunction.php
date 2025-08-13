<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TyFunction
{
    /**
     * Cegah spam/aksi berulang dalam window tertentu (detik).
     * Menggunakan RateLimiter Laravel (atomik & aman untuk concurrency).
     */
    public static function isSpam(string|int $userId, string $action, int $limitSeconds = 20): bool
    {
        $ip = request()?->ip() ?? 'cli';
        $key = 'ty:spam:' . sha1($userId . '|' . $action . '|' . $ip);

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return true;
        }
        RateLimiter::hit($key, $limitSeconds);
        return false;
    }

    public static function generateUserUnique(): string
    {
        $prefix = 'DT';
        $timeSegment = substr((string) now()->timestamp, -6);
        $randomLetters = Str::upper(Str::random(4));
        $randomNumbers = (string) random_int(1000, 9999);
        return $prefix . $timeSegment . $randomLetters . $randomNumbers;
    }

    public static function generateRandomString(int $length = 10): string
    {
        return Str::random($length);
    }

    public static function generateNumberTrx(int $length = 30): string
    {
        $date = date('Ymd');                   // 8 digit
        $timestamp = (string) time();          // ~10 digit
        $random = bin2hex(random_bytes(10));   // 20 hex chars
        $raw = preg_replace('/\D+/', '', $date . $timestamp . $random) ?? '';
        return substr($raw, 0, $length);
    }

    public static function generateRefId(string $from = 'topup'): string
    {
        $prefix = match ($from) {
            'topup'    => 'DTTRX-',
            'order'    => 'DTORD-',
            'transfer' => 'DTTRF-',
            default    => 'DTREF-',
        };

        $date = date('dm');
        $micro = substr((string) microtime(true), -3);
        $number = (string) random_int(1_000_000_000, 9_999_999_999);
        return $prefix . $date . $number . $micro;
    }

    public static function generateMerchantId(): string
    {
        $prefix = 'DTMC';
        $random = Str::upper(Str::random(2));
        $date = (string) now()->timestamp;
        return $prefix . $random . $date;
    }

    public static function generateApiUnique(int $length = 20, string $prefix = ''): string
    {
        return $prefix . Str::random($length);
    }

    public static function generateAvatar(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9]+/', '', $name) ?: 'default';
        $hash = md5($name);
        return 'https://www.gravatar.com/avatar/' . $hash . '?d=mp&s=200';
    }

    /**
     * Simulasi pengukuran waktu proses (dev only).
     * Hindari pemakaian di request produksi karena ada usleep().
     */
    public static function generatePageLoad(): string
    {
        $start = microtime(true);
        usleep(random_int(100_000, 500_000));
        $end = microtime(true);
        return number_format(($end - $start) * 1000, 2) . ' ms';
    }

    public static function timer(string $start, string $end): string
    {
        $s = strtotime($start);
        $e = strtotime($end);
        $diff = max(0, $e - $s);

        return match (true) {
            $diff < 60      => $diff . ' detik',
            $diff < 3600    => floor($diff / 60) . ' menit',
            $diff < 86400   => floor($diff / 3600) . ' jam',
            default         => floor($diff / 86400) . ' hari',
        };
    }

    public static function formatRupiah(float|int $number): string
    {
        return 'Rp ' . number_format((float) $number, 0, ',', '.');
    }

    public static function formatSingkat(float|int $number): string
    {
        $n = (float) $number;
        return match (true) {
            $n >= 1_000_000_000 => self::formatRupiah($n / 1_000_000_000) . ' B',
            $n >= 1_000_000     => self::formatRupiah($n / 1_000_000) . ' M',
            $n >= 1_000         => self::formatRupiah($n / 1_000) . ' K',
            default             => self::formatRupiah($n),
        };
    }

    public static function detectProvider(string $phoneNumber): string
    {
        $number = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if (str_starts_with($number, '62')) {
            $number = '0' . substr($number, 2);
        }

        $byuPrefixes = ['085155', '085156', '085157', '085158'];
        $providers = [
            'By.U'      => $byuPrefixes,
            'Telkomsel' => ['0811', '0812', '0813', '0821', '0822', '0851', '0852', '0853'],
            'Indosat'   => ['0814', '0815', '0816', '0855', '0856', '0857', '0858'],
            'Tri'       => ['0895', '0896', '0897', '0898', '0899'],
            'XL'        => ['0817', '0818', '0819', '0859', '0877', '0878'],
            'Axis'      => ['0831', '0832', '0833', '0838'],
            'Smartfren' => ['0881', '0882', '0883', '0884', '0885', '0886', '0887', '0888', '0889'],
        ];

        $prefix6 = substr($number, 0, 6);
        if (in_array($prefix6, $byuPrefixes, true)) {
            return 'By.U';
        }

        $prefix4 = substr($number, 0, 4);
        foreach ($providers as $provider => $prefixes) {
            if ($provider === 'By.U') continue;
            if (in_array($prefix4, $prefixes, true)) {
                return $provider;
            }
        }

        return 'Unknown';
    }

    public static function formatPhoneNumber(string $phoneNumber): string
    {
        $number = preg_replace('/\D+/', '', $phoneNumber) ?? '';
        if (str_starts_with($number, '62')) {
            $number = '0' . substr($number, 2);
        }
        return $number;
    }

    public static function getProviderLogo(string $provider): string
    {
        $logos = [
            'byu'       => 'byu.png',
            'telkomsel' => 'telkomsel.png',
            'indosat'   => 'indosat.png',
            'tri'       => 'tri.png',
            'xl'        => 'xl.png',
            'axis'      => 'axis.png',
            'smartfren' => 'smartfren.png',
            'pln'       => 'pln.png',
        ];

        $key = preg_replace('/[^a-z0-9]+/', '', strtolower(trim($provider))) ?? 'product';
        return asset('build/images/cards/' . ($logos[$key] ?? 'product.png'));
    }

    public static function urlSlug(string $text): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', trim($text)) ?? '';
        return strtolower(trim($slug, '-'));
    }

    public static function normalSlug(string $text): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', ' ', trim($text)) ?? '';
        return strtolower(trim($slug));
    }

    public static function iconByCategory(string $category): string
    {
        $icons = [
            'games'       => 'fa-solid fa-gamepad-modern',
            'pulsa'       => 'fa-solid fa-mobile-screen-button',
            'pln'         => 'fa-solid fa-bolt',
            'data'        => 'fa-solid fa-signal-stream',
            'e-money'     => 'fa-solid fa-credit-card',
            'masa aktif'  => 'fa-solid fa-hourglass-half',
            'voucher'     => 'fa-solid fa-tickets-perforated',
            'tv'          => 'fa-solid fa-tv',
            'sosial media' => 'fa-solid fa-thumbs-up',
            'streaming'   => 'fa-solid fa-film',
        ];

        return $icons[strtolower($category)] ?? 'fa-solid fa-rectangles-mixed';
    }

    /** AES-256-CBC + HMAC-SHA256 (encrypt string) */
    public static function encryptString(string $plaintext, string $key): string
    {
        $cipher = 'aes-256-cbc';
        $ivLen = openssl_cipher_iv_length($cipher);
        $iv = random_bytes($ivLen);
        $k = hash('sha256', $key, true);

        $ciphertext = openssl_encrypt($plaintext, $cipher, $k, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $iv . $ciphertext, $k, true);

        return base64_encode($iv . $ciphertext . $hmac);
    }

    public static function decryptString(string $encoded, string $key): string|false
    {
        $cipher = 'aes-256-cbc';
        $ivLen = openssl_cipher_iv_length($cipher);
        $k = hash('sha256', $key, true);

        $data = base64_decode($encoded, true);
        if ($data === false || strlen($data) <= ($ivLen + 32)) {
            return false;
        }

        $iv = substr($data, 0, $ivLen);
        $hmac = substr($data, -32);
        $ciphertext = substr($data, $ivLen, -32);

        $calcHmac = hash_hmac('sha256', $iv . $ciphertext, $k, true);
        if (!hash_equals($hmac, $calcHmac)) {
            return false;
        }

        return openssl_decrypt($ciphertext, $cipher, $k, OPENSSL_RAW_DATA, $iv);
    }

    /** AES-256-CBC + HMAC untuk JSON */
    public static function encryptJson(array $data, string $key): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new InvalidArgumentException('Data JSON tidak valid untuk dienkripsi.');
        }

        $cipher = 'aes-256-cbc';
        $ivLen = openssl_cipher_iv_length($cipher);
        $iv = random_bytes($ivLen);
        $k = hash('sha256', $key, true);

        $ciphertext = openssl_encrypt($json, $cipher, $k, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $iv . $ciphertext, $k, true);

        return base64_encode($iv . $ciphertext . $hmac);
    }

    public static function decryptJson(string $encoded, string $key): array|false
    {
        $cipher = 'aes-256-cbc';
        $ivLen = openssl_cipher_iv_length($cipher);
        $k = hash('sha256', $key, true);

        $data = base64_decode($encoded, true);
        if ($data === false || strlen($data) <= ($ivLen + 32)) {
            return false;
        }

        $iv = substr($data, 0, $ivLen);
        $hmac = substr($data, -32);
        $ciphertext = substr($data, $ivLen, -32);

        $calcHmac = hash_hmac('sha256', $iv . $ciphertext, $k, true);
        if (!hash_equals($hmac, $calcHmac)) {
            return false;
        }

        $json = openssl_decrypt($ciphertext, $cipher, $k, OPENSSL_RAW_DATA, $iv);
        if ($json === false) return false;

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : false;
    }

    /** AES-256-GCM base64url (ringkas untuk URL) */
    public static function encryptUrl(string $plaintext, string $key): string
    {
        $cipher = 'aes-256-gcm';
        $iv = random_bytes(12);
        $k = hash('sha256', $key, true);
        $tag = '';

        $ciphertext = openssl_encrypt($plaintext, $cipher, $k, OPENSSL_RAW_DATA, $iv, $tag);
        $raw = $iv . $tag . $ciphertext;

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public static function decryptUrl(string $encoded, string $key): string|false
    {
        $cipher = 'aes-256-gcm';
        $k = hash('sha256', $key, true);

        $encoded = strtr($encoded, '-_', '+/');
        $data = base64_decode($encoded, true);
        if (!$data || strlen($data) < 28) return false;

        $iv  = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        return openssl_decrypt($ciphertext, $cipher, $k, OPENSSL_RAW_DATA, $iv, $tag);
    }

    /** AES-128-CBC + deflate, base64url (ringkas untuk JSON di URL) */
    public static function encryptJsonUrl(array $data, string $key): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new InvalidArgumentException('Data JSON tidak valid untuk dienkripsi.');
        }

        $compressed = gzdeflate($json);
        $cipher = 'aes-128-cbc';
        $iv = random_bytes(openssl_cipher_iv_length($cipher));
        $k = substr(hash('sha256', $key, true), 0, 16);

        $ciphertext = openssl_encrypt($compressed, $cipher, $k, OPENSSL_RAW_DATA, $iv);
        $raw = $iv . $ciphertext;

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public static function decryptJsonUrl(string $encoded, string $key): array|false
    {
        $cipher = 'aes-128-cbc';
        $k = substr(hash('sha256', $key, true), 0, 16);

        $encoded = strtr($encoded, '-_', '+/');
        $padding = strlen($encoded) % 4;
        if ($padding) $encoded .= str_repeat('=', 4 - $padding);

        $raw = base64_decode($encoded, true);
        if ($raw === false) return false;

        $ivLen = openssl_cipher_iv_length($cipher);
        if (strlen($raw) <= $ivLen) return false;

        $iv = substr($raw, 0, $ivLen);
        $ciphertext = substr($raw, $ivLen);

        $decrypted = openssl_decrypt($ciphertext, $cipher, $k, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) return false;

        $json = @gzinflate($decrypted);
        if ($json === false) return false;

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : false;
    }
}
