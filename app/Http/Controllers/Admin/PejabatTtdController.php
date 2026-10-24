<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jurnal;
use App\Models\MasterPejabatTtd;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PejabatTtdController extends Controller
{
    /**
     * Halaman Pengelolaan Master Pejabat & Tanda Tangan Digital
     */
    public function index(): View
    {
        $pejabatsGrouped = MasterPejabatTtd::orderByDesc('is_default')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get()
            ->groupBy('slot');

        return view('admin.pejabat_ttd', compact('pejabatsGrouped'));
    }

    /**
     * Menyimpan data pejabat & tanda tangan baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slot' => 'required|integer|in:1,2,3,4',
            'nama' => 'required|string|max:100',
            'jabatan' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
            'ttd_file' => 'nullable|image|max:2048',
            'ttd_canvas' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        $ttdImage = null;

        if ($request->hasFile('ttd_file')) {
            $file = $request->file('ttd_file');
            $mime = $file->getMimeType();
            $data = file_get_contents($file->getRealPath());
            $ttdImage = 'data:' . $mime . ';base64,' . base64_encode($data);
        } elseif (!empty($request->input('ttd_canvas'))) {
            $ttdImage = $request->input('ttd_canvas');
        }

        $isDefault = $request->boolean('is_default');

        if ($isDefault) {
            MasterPejabatTtd::where('slot', $validated['slot'])->update(['is_default' => false]);
        }

        $pejabat = MasterPejabatTtd::create([
            'slot' => $validated['slot'],
            'nama' => strtoupper(trim($validated['nama'])),
            'jabatan' => trim($validated['jabatan']),
            'nip' => !empty($validated['nip']) ? trim($validated['nip']) : null,
            'ttd_image' => $ttdImage,
            'is_aktif' => true,
            'is_default' => $isDefault,
            'urutan' => MasterPejabatTtd::where('slot', $validated['slot'])->count() + 1,
        ]);

        AuditTrailService::catat('pejabat_ttd.dibuat', [
            'objek' => $pejabat,
            'keterangan' => "Pejabat TTD baru ditambahkan: {$pejabat->nama} ({$pejabat->labelSlot()})",
            'baru' => $pejabat->only(['slot', 'nama', 'jabatan', 'nip', 'is_default']),
        ]);

        return redirect()->route('admin.pejabat-ttd.index')
            ->with('success', "Pejabat {$pejabat->nama} berhasil didaftarkan untuk {$pejabat->labelSlot()}.");
    }

    /**
     * Memperbarui data pejabat & tanda tangan
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $pejabat = MasterPejabatTtd::findOrFail($id);

        if ($pejabat->isDefaultMaster()) {
            // Pejabat default sistem (MUJADID, AYU FEBRIANTI, WACHYUNI MADARAYU, DIANA, ST)
            // Nama dan Jabatan DIKUNCI (tidak dapat diedit), hanya berkas TTD dan status yang dapat diperbarui
            $validated = $request->validate([
                'ttd_file' => 'nullable|image|max:2048',
                'ttd_canvas' => 'nullable|string',
                'is_aktif' => 'nullable|boolean',
                'is_default' => 'nullable|boolean',
            ]);

            $lama = $pejabat->only(['nama', 'jabatan', 'nip', 'is_aktif', 'is_default']);

            $pejabat->is_aktif = $request->boolean('is_aktif');
        } else {
            $validated = $request->validate([
                'nama' => 'required|string|max:100',
                'jabatan' => 'required|string|max:150',
                'nip' => 'nullable|string|max:50',
                'ttd_file' => 'nullable|image|max:2048',
                'ttd_canvas' => 'nullable|string',
                'is_aktif' => 'nullable|boolean',
                'is_default' => 'nullable|boolean',
            ]);

            $lama = $pejabat->only(['nama', 'jabatan', 'nip', 'is_aktif', 'is_default']);

            $pejabat->nama = strtoupper(trim($validated['nama']));
            $pejabat->jabatan = trim($validated['jabatan']);
            $pejabat->nip = !empty($validated['nip']) ? trim($validated['nip']) : null;
            $pejabat->is_aktif = $request->boolean('is_aktif');
        }

        if ($request->hasFile('ttd_file')) {
            $file = $request->file('ttd_file');
            $mime = $file->getMimeType();
            $data = file_get_contents($file->getRealPath());
            $pejabat->ttd_image = 'data:' . $mime . ';base64,' . base64_encode($data);
        } elseif (!empty($request->input('ttd_canvas'))) {
            $pejabat->ttd_image = $request->input('ttd_canvas');
        }

        if ($request->boolean('is_default')) {
            MasterPejabatTtd::where('slot', $pejabat->slot)
                ->where('id', '!=', $pejabat->id)
                ->update(['is_default' => false]);
            $pejabat->is_default = true;
        }

        $pejabat->save();

        AuditTrailService::catat('pejabat_ttd.diubah', [
            'objek' => $pejabat,
            'keterangan' => "Data pejabat TTD diperbarui: {$pejabat->nama}",
            'lama' => $lama,
            'baru' => $pejabat->only(['nama', 'jabatan', 'nip', 'is_aktif', 'is_default']),
        ]);

        $pesanSukses = $pejabat->isDefaultMaster()
            ? "Tanda tangan untuk pejabat default {$pejabat->nama} berhasil diperbarui."
            : "Data pejabat {$pejabat->nama} berhasil diperbarui.";

        return redirect()->route('admin.pejabat-ttd.index')
            ->with('success', $pesanSukses);
    }

    /**
     * Menghapus atau menonaktifkan pejabat
     */
    public function destroy(int $id): RedirectResponse
    {
        $pejabat = MasterPejabatTtd::findOrFail($id);

        if (!$pejabat->isDeletable()) {
            return redirect()->route('admin.pejabat-ttd.index')
                ->with('error', "Pejabat default sistem ({$pejabat->nama}) tidak dapat dihapus.");
        }

        $nama = $pejabat->nama;

        AuditTrailService::catat('pejabat_ttd.dihapus', [
            'objek' => $pejabat,
            'keterangan' => "Pejabat TTD dihapus: {$nama}",
            'lama' => $pejabat->only(['slot', 'nama', 'jabatan', 'nip']),
        ]);

        $pejabat->delete();

        return redirect()->route('admin.pejabat-ttd.index')
            ->with('success', "Pejabat {$nama} berhasil dihapus dari sistem.");
    }

    /**
     * Menjadikan pejabat ini sebagai default di slotnya
     */
    public function setDefault(int $id): RedirectResponse
    {
        $pejabat = MasterPejabatTtd::findOrFail($id);

        MasterPejabatTtd::where('slot', $pejabat->slot)->update(['is_default' => false]);

        $pejabat->is_default = true;
        $pejabat->is_aktif = true;
        $pejabat->save();

        AuditTrailService::catat('pejabat_ttd.default_diubah', [
            'objek' => $pejabat,
            'keterangan' => "Pejabat default {$pejabat->labelSlot()} diubah ke: {$pejabat->nama}",
            'baru' => ['slot' => $pejabat->slot, 'pejabat_id' => $pejabat->id, 'nama' => $pejabat->nama],
        ]);

        return redirect()->route('admin.pejabat-ttd.index')
            ->with('success', "{$pejabat->nama} kini menjadi pejabat default utama untuk {$pejabat->labelSlot()}.");
    }

    /**
     * API AJAX: Mengambil daftar pejabat aktif berdasarkan nomor slot (1-4)
     */
    public function apiBySlot(int $slot): JsonResponse
    {
        $pejabats = MasterPejabatTtd::where('slot', $slot)
            ->where('is_aktif', true)
            ->orderByDesc('is_default')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get(['id', 'slot', 'nama', 'jabatan', 'nip', 'ttd_image', 'is_default']);

        return response()->json([
            'status' => 'success',
            'slot' => $slot,
            'data' => $pejabats,
        ]);
    }

    /**
     * API AJAX: Menyimpan snapshot konfigurasi TTD ke tiket Jurnal tertentu
     */
    public function simpanTtdJurnal(Request $request, int $id): JsonResponse
    {
        $jurnal = Jurnal::findOrFail($id);

        $validated = $request->validate([
            'mode' => 'required|string|in:digital,manual',
            'slots' => 'required|array',
            'slots.*.nama' => 'nullable|string',
            'slots.*.jabatan' => 'nullable|string',
            'slots.*.nip' => 'nullable|string',
            'slots.*.pejabat_id' => 'nullable',
            'slots.*.ttd_image' => 'nullable|string',
            'slots.*.is_kosong' => 'nullable|boolean',
        ]);

        $snapshot = [
            'mode' => $validated['mode'],
            'slots' => $validated['slots'],
            'disimpan_pada' => now()->translatedFormat('d F Y H:i:s') . ' WITA',
            'disimpan_oleh' => auth()->user()?->username ?? 'admin',
        ];

        $jurnal->ttd_data = $snapshot;
        $jurnal->save();

        AuditTrailService::catat('jurnal.ttd_disimpan', [
            'jurnal' => $jurnal,
            'keterangan' => "Konfigurasi tanda tangan digital disimpan untuk tiket {$jurnal->no_tiket}",
            'baru' => [
                'mode' => $validated['mode'],
                'ringkasan_slot' => collect($validated['slots'])->map(fn ($s) => [
                    'nama' => $s['nama'] ?? '',
                    'is_kosong' => (bool) ($s['is_kosong'] ?? false),
                ])->toArray(),
            ],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Konfigurasi tanda tangan untuk tiket {$jurnal->no_tiket} berhasil disimpan permanen.",
            'data' => $snapshot,
        ]);
    }
}
