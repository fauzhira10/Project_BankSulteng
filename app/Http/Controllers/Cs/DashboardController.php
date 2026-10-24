<?php

namespace App\Http\Controllers\Cs;

use App\Enums\PengaduanStatus;
use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\Request;

/**
 * Beranda Customer Service Cabang: ringkasan status pengaduan cabang sendiri.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $base = Pengaduan::query()->untukCs($user);

        $perStatus = (clone $base)
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $stats = ['total' => (int) $perStatus->sum()];
        foreach (PengaduanStatus::cases() as $status) {
            $stats[$status->value] = (int) ($perStatus[$status->value] ?? 0);
        }

        $bulanIni = (clone $base)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $terbaru = (clone $base)
            ->with(['transaksi', 'jurnal'])
            ->latest('id')
            ->limit(6)
            ->get();

        // Pengaduan yang baru saja berubah status di pusat (7 hari terakhir)
        $perluPerhatian = (clone $base)
            ->whereIn('status', [PengaduanStatus::Selesai->value, PengaduanStatus::Ditolak->value])
            ->where('updated_at', '>=', now()->subDays(7))
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return view('cs.dashboard', compact('user', 'stats', 'bulanIni', 'terbaru', 'perluPerhatian'));
    }
}
