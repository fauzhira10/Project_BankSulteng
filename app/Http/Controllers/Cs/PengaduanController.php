<?php

namespace App\Http\Controllers\Cs;

use App\Enums\PengaduanStatus;
use App\Http\Controllers\Controller;
use App\Models\MasterAtm;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use App\Models\Pengaduan;
use App\Models\PengaduanLampiran;
use App\Models\User;
use App\Services\LampiranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Pengaduan nasabah oleh Customer Service cabang.
 * CS hanya melihat pengaduan cabangnya sendiri; edit/hapus hanya saat status masih Terkirim.
 */
class PengaduanController extends Controller
{
    public function __construct(protected LampiranService $lampiranService) {}

    /**
     * Daftar pengaduan cabang + filter status & pencarian
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Pengaduan::with(['transaksi', 'jurnal', 'cabang'])
            ->untukCs($user)
            ->cari($request->q);

        if ($request->filled('status') && in_array($request->status, PengaduanStatus::values(), true)) {
            $query->status($request->status);
        }

        $pengaduans = $query->latest('id')->paginate(10)->withQueryString();
        $stats = $this->hitungStatistik($user);

        return view('cs.pengaduan_index', compact('pengaduans', 'stats', 'user'));
    }

    /**
     * Formulir input pengaduan baru
     */
    public function create(Request $request)
    {
        Gate::authorize('create', Pengaduan::class);

        return view('cs.pengaduan_form', $this->dataForm($request->user()));
    }

    /**
     * Simpan pengaduan baru beserta lampirannya (disimpan sesuai format asli)
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Pengaduan::class);

        $user = $request->user();
        $data = $this->validasi($request, null);

        $pengaduan = DB::transaction(function () use ($data, $user) {
            return Pengaduan::create(array_merge($data, [
                'user_id' => $user->id,
                'status' => PengaduanStatus::Terkirim,
            ]));
        });

        try {
            $this->simpanSemuaLampiran($pengaduan, $request);
        } catch (Throwable $e) {
            // Gagal memproses lampiran → batalkan pengaduan agar tidak ada data tanpa lampiran wajib
            $this->lampiranService->hapusSemua($pengaduan);
            $pengaduan->delete();

            return back()->withInput()->withErrors([
                'lampiran' => 'Pengaduan belum tersimpan karena lampiran gagal diunggah: '.$e->getMessage(),
            ]);
        }

        return redirect()
            ->route('cs.pengaduan.show', $pengaduan)
            ->with('success', "Pengaduan {$pengaduan->nomor_tiket} berhasil dikirim ke Admin Pusat. Silakan pantau statusnya di halaman ini.");
    }

    /**
     * Detail pengaduan (hanya baca) + status dari pusat
     */
    public function show(Pengaduan $pengaduan)
    {
        Gate::authorize('view', $pengaduan);

        $pengaduan->load(['cabang', 'transaksi', 'lampirans', 'user', 'penerima', 'jurnal.masterTransaksi']);

        return view('cs.pengaduan_show', compact('pengaduan'));
    }

    /**
     * Formulir edit (hanya saat status Terkirim)
     */
    public function edit(Request $request, Pengaduan $pengaduan)
    {
        Gate::authorize('update', $pengaduan);

        $pengaduan->load(['lampirans', 'cabang']);

        return view('cs.pengaduan_form', $this->dataForm($request->user(), $pengaduan));
    }

    /**
     * Perbarui pengaduan (lampiran baru ditambahkan, lampiran lama dihapus lewat route terpisah)
     */
    public function update(Request $request, Pengaduan $pengaduan)
    {
        Gate::authorize('update', $pengaduan);

        $data = $this->validasi($request, $pengaduan);

        $pengaduan->update($data);

        try {
            $this->simpanSemuaLampiran($pengaduan, $request);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors([
                'lampiran' => 'Data tersimpan, namun lampiran baru gagal diunggah: '.$e->getMessage(),
            ]);
        }

        return redirect()
            ->route('cs.pengaduan.show', $pengaduan)
            ->with('success', "Pengaduan {$pengaduan->nomor_tiket} berhasil diperbarui.");
    }

    /**
     * Hapus pengaduan (hanya saat status Terkirim)
     */
    public function destroy(Pengaduan $pengaduan)
    {
        Gate::authorize('delete', $pengaduan);

        $nomor = $pengaduan->nomor_tiket;

        $this->lampiranService->hapusSemua($pengaduan);
        $pengaduan->delete();

        return redirect()
            ->route('cs.pengaduan.index')
            ->with('success', "Pengaduan {$nomor} berhasil dihapus.");
    }

    /**
     * Hapus satu lampiran (hanya saat status Terkirim)
     */
    public function destroyLampiran(Pengaduan $pengaduan, PengaduanLampiran $lampiran)
    {
        Gate::authorize('update', $pengaduan);

        abort_unless((int) $lampiran->pengaduan_id === (int) $pengaduan->id, 404);

        $label = $lampiran->label();
        $this->lampiranService->hapus($lampiran);

        return back()->with('success', "Lampiran {$label} berhasil dihapus.");
    }

    // ==================== HELPER ====================

    /**
     * Data master yang dibutuhkan formulir (create & edit).
     */
    protected function dataForm(User $user, ?Pengaduan $pengaduan = null): array
    {
        return [
            'pengaduan' => $pengaduan,
            'cabang' => $user->cabang,
            'cabangs' => MasterCabang::orderBy('kode_cabang')->get(),
            'transaksis' => MasterTransaksi::whereIn('id', range(1, 33))->orderBy('id')->get(),
            'atmsGrouped' => MasterAtm::getAtmsGroupedByCabang(),
            'channels' => config('pengaduan.channels', []),
            'terminalNonAtm' => config('pengaduan.terminal_non_atm', []),
            'jenisLampiran' => config('pengaduan.jenis_lampiran', []),
            'maxFile' => (int) config('pengaduan.max_file_per_jenis', 5),
            'maxKb' => (int) config('pengaduan.max_ukuran_file_kb', 5120),
            'ekstensi' => config('pengaduan.ekstensi_diizinkan', []),
        ];
    }

    /**
     * Validasi & normalisasi input formulir pengaduan.
     * `master_cabang_id` sengaja tidak diambil dari request — selalu dari akun CS.
     */
    protected function validasi(Request $request, ?Pengaduan $pengaduan): array
    {
        // Normalisasi nominal: "1.500.000" → 1500000
        $request->merge([
            'nominal_transaksi' => preg_replace('/[^\d]/', '', (string) $request->input('nominal_transaksi')),
            'no_ktp' => preg_replace('/[^\d]/', '', (string) $request->input('no_ktp')),
        ]);

        $channels = config('pengaduan.channels', []);
        $ekstensi = implode(',', config('pengaduan.ekstensi_diizinkan', ['jpg', 'jpeg', 'png', 'webp', 'pdf']));
        $maxKb = (int) config('pengaduan.max_ukuran_file_kb', 5120);
        $maxFile = (int) config('pengaduan.max_file_per_jenis', 5);

        $rules = [
            'nama_pelapor' => 'required|string|max:255',
            'master_cabang_id' => 'required|exists:master_cabangs,id',
            'kategori' => 'required|string|max:255',
            'sub_kategori' => 'nullable|string|max:255',
            'sub_kategori_2' => 'nullable|string|max:255',
            'nama_nasabah' => 'required|string|max:255',
            'no_hp' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'no_ktp' => ['required', 'digits:16'],
            'no_rekening' => 'required|string|max:50',
            'no_kartu' => 'nullable|string|max:50',
            'master_transaksi_id' => 'required|exists:master_transaksis,id',
            'no_resi' => 'required|string|max:100',
            'terminal_transaksi' => 'nullable|string|max:255',
            'channel' => ['required', Rule::in($channels)],
            'nominal_transaksi' => 'required|numeric|min:0',
            'tgl_transaksi' => 'required|date|before_or_equal:today',
            'kronologi' => 'required|string|min:20',
        ];

        $lampiranAda = $pengaduan ? $pengaduan->lampirans()->pluck('jenis')->unique()->all() : [];

        foreach (config('pengaduan.jenis_lampiran', []) as $jenis => $cfg) {
            $wajib = ! empty($cfg['wajib']) && ! in_array($jenis, $lampiranAda, true);
            $rules["lampiran.{$jenis}"] = [$wajib ? 'required' : 'nullable', 'array', "max:{$maxFile}"];
            $rules["lampiran.{$jenis}.*"] = ['file', "mimes:{$ekstensi}", "max:{$maxKb}"];
        }

        $validated = $request->validate($rules, $this->pesanValidasi(), $this->namaAtribut());

        unset($validated['lampiran']);

        $validated['nama_pelapor'] = trim($validated['nama_pelapor']);
        $validated['nama_nasabah'] = strtoupper(trim($validated['nama_nasabah']));
        $validated['kategori'] = strtoupper(trim($validated['kategori']));
        $validated['sub_kategori'] = filled($validated['sub_kategori'] ?? null) ? strtoupper(trim($validated['sub_kategori'])) : null;
        $validated['sub_kategori_2'] = filled($validated['sub_kategori_2'] ?? null) ? strtoupper(trim($validated['sub_kategori_2'])) : null;
        $validated['no_kartu'] = filled($validated['no_kartu'] ?? null) ? trim($validated['no_kartu']) : null;
        $validated['terminal_transaksi'] = filled($validated['terminal_transaksi'] ?? null) ? trim($validated['terminal_transaksi']) : null;
        $validated['channel'] = strtoupper(trim($validated['channel']));
        $validated['kronologi'] = trim($validated['kronologi']);

        return $validated;
    }

    /**
     * Simpan seluruh input file `lampiran[jenis][]` sesuai format aslinya.
     */
    protected function simpanSemuaLampiran(Pengaduan $pengaduan, Request $request): void
    {
        foreach (array_keys(config('pengaduan.jenis_lampiran', [])) as $jenis) {
            $files = $request->file("lampiran.{$jenis}", []);
            if (! is_array($files)) {
                $files = [$files];
            }
            $files = array_values(array_filter($files));

            if (! empty($files)) {
                $this->lampiranService->simpan($pengaduan, $jenis, $files);
            }
        }
    }

    protected function hitungStatistik(User $user): array
    {
        $perStatus = Pengaduan::query()
            ->untukCs($user)
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $stats = ['total' => (int) $perStatus->sum()];
        foreach (PengaduanStatus::cases() as $status) {
            $stats[$status->value] = (int) ($perStatus[$status->value] ?? 0);
        }

        return $stats;
    }

    protected function pesanValidasi(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
            'lampiran.*.max' => 'Jumlah berkas :attribute maksimal :max file.',
            'lampiran.*.*.max' => 'Ukuran berkas :attribute maksimal :max KB per file.',
            'lampiran.*.*.mimes' => 'Berkas :attribute harus berformat :values.',
            'lampiran.*.*.file' => 'Berkas :attribute tidak valid.',
            'lampiran.*.required' => ':attribute wajib diunggah.',
            'lampiran.*.array' => 'Format unggahan :attribute tidak valid.',
            'master_cabang_id.required' => 'Asal cabang wajib dipilih.',
            'master_cabang_id.exists' => 'Asal cabang yang dipilih tidak valid.',
            'no_hp.regex' => 'Nomor HP hanya boleh berisi angka, spasi, +, - atau tanda kurung.',
            'no_ktp.digits' => 'Nomor KTP (NIK) harus terdiri dari 16 digit angka.',
            'master_transaksi_id.exists' => 'Jenis transaksi yang dipilih tidak valid.',
            'channel.in' => 'Prinsipal / channel yang dipilih tidak valid.',
            'nominal_transaksi.numeric' => 'Nominal transaksi harus berupa angka.',
            'nominal_transaksi.min' => 'Nominal transaksi tidak boleh negatif.',
            'tgl_transaksi.date' => 'Tanggal transaksi tidak valid.',
            'tgl_transaksi.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            'kronologi.min' => 'Kronologi terlalu singkat. Tuliskan minimal :min karakter secara detail.',
        ];
    }

    protected function namaAtribut(): array
    {
        $atribut = [
            'nama_pelapor' => 'Nama Pelapor (CS)',
            'master_cabang_id' => 'Asal Cabang',
            'kategori' => 'Kategori',
            'sub_kategori' => 'Sub Kategori',
            'sub_kategori_2' => 'Sub Kategori 2',
            'nama_nasabah' => 'Nama Nasabah',
            'no_hp' => 'Nomor HP',
            'no_ktp' => 'Nomor KTP (NIK)',
            'no_rekening' => 'Nomor Rekening',
            'no_kartu' => 'Nomor Kartu ATM',
            'master_transaksi_id' => 'Jenis Transaksi',
            'no_resi' => 'Nomor Resi',
            'terminal_transaksi' => 'Terminal Transaksi',
            'channel' => 'Prinsipal / Channel',
            'nominal_transaksi' => 'Nominal Transaksi',
            'tgl_transaksi' => 'Tanggal Transaksi',
            'kronologi' => 'Kronologi',
        ];

        foreach (config('pengaduan.jenis_lampiran', []) as $jenis => $cfg) {
            $label = 'Lampiran '.($cfg['label'] ?? $jenis);
            $atribut["lampiran.{$jenis}"] = $label;
            $atribut["lampiran.{$jenis}.*"] = $label;
        }

        return $atribut;
    }
}
