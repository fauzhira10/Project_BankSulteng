<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memasang header keamanan HTTP pada seluruh respons aplikasi.
 *
 * Yang paling penting bagi sistem ini adalah `X-Content-Type-Options: nosniff`.
 * Lampiran pengaduan (foto KTP, PDF) disajikan `inline` lewat
 * PengaduanLampiranController; tanpa nosniff, peramban boleh menebak ulang tipe
 * berkas dan berpeluang menjalankannya sebagai HTML alih-alih menampilkannya.
 */
class HeaderKeamanan
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // HSTS hanya bermakna (dan hanya dihormati peramban) di atas HTTPS.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Content-Security-Policy', $this->csp());

        return $response;
    }

    /**
     * Kebijakan Content-Security-Policy.
     *
     * `'unsafe-inline'` masih diperlukan karena hampir seluruh view memakai
     * <script> dan style sebaris. Meski begitu kebijakan ini tetap berguna:
     * skrip dari domain luar, <object>/<embed>, penyematan halaman dalam frame,
     * dan pengiriman form ke domain lain semuanya ditutup. Menghapus
     * 'unsafe-inline' menuntut nonce di setiap blok skrip — pekerjaan tersendiri.
     */
    protected function csp(): string
    {
        $skrip = ["'self'", "'unsafe-inline'", 'https://cdn.jsdelivr.net'];
        $gaya = ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'];
        $koneksi = ["'self'"];

        // Server pengembangan Vite (npm run dev) memuat aset dan membuka
        // WebSocket dari porta 5173, yang bukan origin aplikasi.
        if (app()->environment('local')) {
            foreach (['http://localhost:5173', 'http://127.0.0.1:5173'] as $origin) {
                $skrip[] = $origin;
                $gaya[] = $origin;
                $koneksi[] = $origin;
            }
            $koneksi[] = 'ws://localhost:5173';
            $koneksi[] = 'ws://127.0.0.1:5173';
        }

        $arahan = [
            "default-src 'self'",
            'script-src '.implode(' ', $skrip),
            'style-src '.implode(' ', $gaya),
            'connect-src '.implode(' ', $koneksi),
            "img-src 'self' data:",
            "font-src 'self' data: https://fonts.gstatic.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        return implode('; ', $arahan);
    }
}
