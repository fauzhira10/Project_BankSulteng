<?php

namespace App\Http\Controllers;

use App\Enums\PengaduanStatus;
use App\Models\Jurnal;
use App\Models\MasterAtm;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use App\Models\Pengaduan;
use App\Observers\JurnalObserver;
use App\Services\AuditTrailService;
use App\Services\DeteksiDuplikatService;
use App\Services\NomorTiketService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JurnalController extends Controller
{
    /**
     * Status baku yang ikut dihitung pada kartu ringkasan & rekap laporan.
     * Petugas tetap boleh mengetik status lain secara manual pada form jurnal.
     */
    public const STATUS_STANDAR = ['-', 'Menunggu', 'Success', 'Done', 'Rejected'];

    /**
     * Merapikan status yang diketik manual: spasi berlebih dibuang dan penulisan
     * yang sama dengan status baku diseragamkan (mis. "done" menjadi "Done"),
     * agar tidak muncul duplikat yang membuat perhitungan statistik meleset.
     */
    private function normalisasiStatus(?string $status): string
    {
        $bersih = trim(preg_replace('/\s+/', ' ', (string) $status));

        if ($bersih === '') {
            return '-';
        }

        foreach (self::STATUS_STANDAR as $baku) {
            if (strcasecmp($bersih, $baku) === 0) {
                return $baku;
            }
        }

        return mb_substr($bersih, 0, 50);
    }

    /**
     * Menampilkan daftar data jurnal keluhan dengan fitur pencarian, filter, dan statistik
     */
    public function index(Request $request)
    {
        $query = Jurnal::with(['masterCabang', 'masterTransaksi', 'pengaduan:id,jurnal_id,nomor_tiket,status']);

        // 1. Filter Pencarian Keyword Multi-Field (Case-Insensitive & Multi-Word)
        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $rawTerms = explode(' ', $keyword);
            $terms = array_values(array_filter($rawTerms, fn ($t) => trim($t) !== '' && trim($t) !== '-'));
            if (empty($terms)) {
                $terms = [$keyword];
            }

            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('nama_nasabah', 'LIKE', "%{$term}%")
                            ->orWhere('no_resi', 'LIKE', "%{$term}%")
                            ->orWhere('no_rekening', 'LIKE', "%{$term}%")
                            ->orWhere('no_kartu', 'LIKE', "%{$term}%")
                            ->orWhere('no_tiket', 'LIKE', "%{$term}%")
                            ->orWhere('terminal_transaksi', 'LIKE', "%{$term}%")
                            ->orWhereHas('masterCabang', function ($cQ) use ($term) {
                                $cQ->where('nama_cabang', 'LIKE', "%{$term}%")
                                    ->orWhere('kode_cabang', 'LIKE', "%{$term}%");
                            })
                            ->orWhereHas('masterTransaksi', function ($tQ) use ($term) {
                                $tQ->where('jenis_transaksi', 'LIKE', "%{$term}%")
                                    ->orWhere('channel', 'LIKE', "%{$term}%");
                            });

                        $atmMatch = MasterAtm::findAtmInfo($term);
                        if ($atmMatch && ! empty($atmMatch['profil'])) {
                            $subQ->orWhere('terminal_transaksi', 'LIKE', "%{$atmMatch['profil']}%");
                        }
                    });
                }
            });
        }

        // 2. Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filter Cabang
        if ($request->filled('master_cabang_id')) {
            $query->where('master_cabang_id', $request->master_cabang_id);
        }

        // 4. Filter Terminal Transaksi / Mesin ATM
        if ($request->filled('terminal_transaksi')) {
            $termVal = trim($request->terminal_transaksi);
            $query->where(function ($tq) use ($termVal) {
                $clean = strtoupper($termVal);

                // 1. Channel non-mesin (Bank Lain, Mobile Banking)
                if (str_contains($clean, 'BANK LAIN') || str_contains($clean, 'MOBILE') || str_contains($clean, 'SMS')) {
                    $tq->where('terminal_transaksi', 'LIKE', "%{$termVal}%");

                    return;
                }

                // 2. Format gabungan "ID - PROFIL" (misal: "452 - GRG.KCU1")
                if (str_contains($clean, '-')) {
                    $parts = explode('-', $clean, 2);
                    $idPart = trim($parts[0] ?? '');
                    $profilPart = trim($parts[1] ?? '');

                    $tq->where('terminal_transaksi', '=', $clean)
                        ->orWhere('terminal_transaksi', '=', $profilPart)
                        ->orWhere('terminal_transaksi', '=', "{$idPart} - {$profilPart}")
                        ->orWhere('terminal_transaksi', 'LIKE', "% - {$profilPart}")
                        ->orWhere('terminal_transaksi', 'LIKE', "{$profilPart} %");

                    return;
                }

                // 3. Single term profil atau ID
                $atmMatch = MasterAtm::findAtmInfo($clean);
                $profil = $atmMatch['profil'] ?? $clean;
                $idLuno = $atmMatch['id_luno'] ?? null;

                $tq->where('terminal_transaksi', '=', $clean)
                    ->orWhere('terminal_transaksi', '=', $profil)
                    ->orWhere('terminal_transaksi', 'LIKE', "% - {$profil}")
                    ->orWhere('terminal_transaksi', 'LIKE', "{$profil} %");

                if ($idLuno) {
                    $tq->orWhere('terminal_transaksi', '=', (string) $idLuno)
                        ->orWhere('terminal_transaksi', '=', "{$idLuno} - {$profil}");
                }
            });
        }

        // 5. Filter Hanya Data Berulang (nama nasabah + no resi yang sama muncul lebih dari sekali).
        // Subquery berkorelasi dipakai agar tetap jalan di MySQL maupun SQLite dan
        // memanfaatkan prefix kiri index jurnal_unique_kombinasi.
        if ($request->boolean('duplikat')) {
            $query->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('jurnals as j2')
                    ->whereColumn('j2.nama_nasabah', 'jurnals.nama_nasabah')
                    ->whereColumn('j2.no_resi', 'jurnals.no_resi')
                    ->whereColumn('j2.id', '!=', 'jurnals.id');
            });
        }

        // 6. Filter Rentang Tanggal Transaksi
        if ($request->filled('tgl_dari')) {
            $query->whereDate('tgl_transaksi', '>=', $request->tgl_dari);
        }
        if ($request->filled('tgl_sampai')) {
            $query->whereDate('tgl_transaksi', '<=', $request->tgl_sampai);
        }

        // Jumlah baris per halaman (Pilihan: 10, 50, 100)
        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 50, 100])) {
            $perPage = 10;
        }

        // Logika Pengurutan:
        // Jika filter tanggal (tgl_dari / tgl_sampai) digunakan, urutkan kronologis dari tanggal terlama ke tanggal terbaru (ASC)
        // Jika tidak ada filter tanggal, tampilkan data terbaru terlebih dahulu (DESC)
        if ($request->filled('tgl_dari') || $request->filled('tgl_sampai')) {
            $query->orderBy('tgl_transaksi', 'asc')->orderBy('id', 'asc');
        } else {
            $query->latest('id');
        }

        $jurnals = $query->paginate($perPage)->withQueryString();

        // Jumlah kembaran (nama nasabah + no resi sama) untuk baris di halaman ini saja,
        // dipakai menandai baris dengan badge "Berulang" di tabel Data Keluhan.
        $jumlahBerulang = $this->hitungBerulang($jurnals->getCollection());

        // Ambil data Master untuk pilihan filter
        $cabangs = MasterCabang::orderBy('kode_cabang')->get();
        $transaksis = MasterTransaksi::whereIn('id', range(1, 33))->orderBy('id')->get();
        $atmsGrouped = MasterAtm::getAtmsGroupedByCabang();

        // Hitung Ringkasan Statistik
        $stats = [
            'total' => Jurnal::count(),
            'menunggu' => Jurnal::where('status', 'Menunggu')->count(),
            'success' => Jurnal::where('status', 'Success')->count(),
            'done' => Jurnal::where('status', 'Done')->count(),
            'rejected' => Jurnal::where('status', 'Rejected')->count(),
        ];

        // Status kustom yang pernah diketik manual pada form jurnal, agar tetap dapat difilter
        $statusKustom = Jurnal::query()
            ->select('status')
            ->distinct()
            ->whereNotNull('status')
            ->whereNotIn('status', self::STATUS_STANDAR)
            ->orderBy('status')
            ->pluck('status')
            ->filter(fn ($s) => trim((string) $s) !== '')
            ->values();

        return view('jurnal_data', compact('jurnals', 'cabangs', 'transaksis', 'stats', 'atmsGrouped', 'statusKustom', 'jumlahBerulang'));
    }

    /**
     * Hitung berapa kali tiap kombinasi "NAMA|RESI" muncul di tabel jurnal,
     * khusus untuk baris yang sedang ditampilkan. Cukup satu query tambahan
     * per halaman, bukan satu query per baris.
     *
     * @param  Collection<int, Jurnal>  $barisHalaman
     * @return array<string, int>
     */
    private function hitungBerulang($barisHalaman): array
    {
        $namaHalaman = $barisHalaman->pluck('nama_nasabah')->filter()->unique()->values();

        if ($namaHalaman->isEmpty()) {
            return [];
        }

        return Jurnal::query()
            ->selectRaw('nama_nasabah, no_resi, COUNT(*) as jml')
            ->whereIn('nama_nasabah', $namaHalaman)
            ->groupBy('nama_nasabah', 'no_resi')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->mapWithKeys(fn ($b) => [$b->nama_nasabah.'|'.$b->no_resi => (int) $b->jml])
            ->all();
    }

    /**
     * Menampilkan formulir input jurnal keluhan.
     * Mendukung ?pengaduan={id} untuk mengisi otomatis dari pengaduan CS cabang.
     */
    public function create(Request $request)
    {
        $cabangs = MasterCabang::orderBy('kode_cabang')->get();
        $transaksis = MasterTransaksi::whereIn('id', range(1, 33))->orderBy('id')->get();
        $atmsGrouped = MasterAtm::getAtmsGroupedByCabang();

        $prefill = [];
        $pengaduan = null;

        if ($request->filled('pengaduan')) {
            $pengaduan = Pengaduan::with(['cabang', 'transaksi', 'lampirans'])->find((int) $request->input('pengaduan'));

            if ($pengaduan && $pengaduan->jurnal_id !== null) {
                return redirect()
                    ->route('admin.pengaduan.show', $pengaduan)
                    ->with('error', "Pengaduan {$pengaduan->nomor_tiket} sudah tertaut ke jurnal keluhan.");
            }

            if ($pengaduan) {
                $prefill = [
                    'pengaduan_id' => $pengaduan->id,
                    'nama_nasabah' => $pengaduan->nama_nasabah,
                    'no_rekening' => $pengaduan->no_rekening,
                    'no_resi' => $pengaduan->no_resi,
                    'no_kartu' => $pengaduan->no_kartu ?? '',
                    'no_tiket' => $pengaduan->nomor_tiket,
                    'master_cabang_id' => $pengaduan->master_cabang_id,
                    'master_transaksi_id' => $pengaduan->master_transaksi_id,
                    'channel' => $pengaduan->channel,
                    'terminal_transaksi' => $pengaduan->terminal_transaksi ?? '',
                    'nominal_transaksi' => (float) $pengaduan->nominal_transaksi,
                    'tgl_transaksi' => optional($pengaduan->tgl_transaksi)->format('Y-m-d'),
                    'tgl_terima' => now()->format('Y-m-d'),
                    'permasalahan' => strtoupper($pengaduan->kategoriLengkap()),
                ];
            }
        }

        return view('jurnal_form', compact('cabangs', 'transaksis', 'atmsGrouped', 'prefill', 'pengaduan'));
    }

    /**
     * Endpoint AJAX pemeriksaan keluhan berulang, dipanggil dari form jurnal
     * (admin) dan form pengaduan (CS) sambil petugas mengetik.
     *
     * Panel peringatannya dirender di sini sebagai HTML jadi, supaya tampilannya
     * hanya ditulis sekali di partials/panel_duplikat.blade.php.
     *
     * Jawaban untuk CS cabang sengaja dipangkas menjadi ADA atau TIDAK ADA.
     * Endpoint ini menerima nama nasabah dan nomor resi apa pun, jadi jawaban
     * yang rinci membuatnya dapat dipakai satu akun cabang untuk memetakan
     * keluhan cabang lain — nomor tiket, cabang asal, tanggal, dan nominalnya —
     * cukup dengan mencoba banyak kombinasi. Yang benar-benar dibutuhkan CS
     * hanyalah tahu bahwa keluhan itu sudah tercatat di pusat; rinciannya ada
     * pada Admin Pusat yang memang berwenang melihat lintas cabang.
     */
    public function cekDuplikat(Request $request)
    {
        $cek = DeteksiDuplikatService::periksa(
            $request->input('nama'),
            $request->input('resi'),
            $request->input('tgl'),
            $request->filled('abaikan') ? (int) $request->input('abaikan') : null,
            $request->filled('abaikan_pengaduan') ? (int) $request->input('abaikan_pengaduan') : null
        );

        if (! ($request->user()?->isAdmin() ?? false)) {
            $ada = $cek['tingkat'] !== DeteksiDuplikatService::AMAN;

            // Tingkat KEMBAR ikut diratakan menjadi BERULANG: membedakannya sama
            // saja dengan memberi tahu bahwa ada catatan bertanggal transaksi
            // persis seperti yang sedang diketik. Jumlah dan token juga tidak
            // dikirim — form CS memakai mode informatif yang tidak memerlukannya.
            return response()->json([
                'tingkat' => $ada ? DeteksiDuplikatService::BERULANG : DeteksiDuplikatService::AMAN,
                'html' => $ada
                    ? view('partials.panel_duplikat', ['duplikat' => $cek, 'ringkas' => true])->render()
                    : '',
            ]);
        }

        return response()->json([
            'tingkat' => $cek['tingkat'],
            'jumlah' => $cek['jumlah'],
            'token' => $cek['token'],
            'html' => view('partials.panel_duplikat', ['duplikat' => $cek])->render(),
        ]);
    }

    /**
     * Penjaga sisi server untuk keluhan berulang. Dipakai store() dan update().
     *
     * KEMBAR   selalu ditolak — index unik jurnal_unique_kombinasi memang melarangnya.
     * BERULANG ditolak hanya bila petugas belum menyetujui kombinasi ini; penanda
     *          persetujuannya berisi nama + resi yang diperiksa, jadi tidak bisa
     *          dipakai ulang untuk nasabah atau resi yang lain.
     *
     * @param  int|null  $abaikanJurnalId  Jurnal yang sedang diedit, supaya tidak melaporkan dirinya sendiri.
     * @param  int|null  $abaikanPengaduanId  Pengaduan CS yang menjadi sumber jurnal ini.
     * @return RedirectResponse|null null bila aman untuk disimpan.
     */
    private function tolakBilaBerulang(
        Request $request,
        string $namaNorm,
        string $resiNorm,
        ?string $tglTransaksi,
        ?int $abaikanJurnalId = null,
        ?int $abaikanPengaduanId = null
    ) {
        $cek = DeteksiDuplikatService::periksa($namaNorm, $resiNorm, $tglTransaksi, $abaikanJurnalId, $abaikanPengaduanId);

        if ($cek['tingkat'] === DeteksiDuplikatService::KEMBAR) {
            return back()->withInput()->with('duplikat', $cek)->withErrors([
                'no_resi' => 'Keluhan atas nasabah ini dengan No. Resi dan Tanggal Transaksi yang sama sudah pernah dijurnal. Periksa rincian di bawah kolom No. Resi.',
            ]);
        }

        if ($cek['tingkat'] === DeteksiDuplikatService::BERULANG
            && $request->input('konfirmasi_duplikat') !== $cek['token']) {
            return back()->withInput()->with('duplikat', $cek)->withErrors([
                'no_resi' => 'Keluhan atas nasabah ini dengan No. Resi yang sama sudah pernah ditangani. Periksa rincian di bawah kolom No. Resi, lalu simpan sekali lagi untuk melanjutkan.',
            ]);
        }

        return null;
    }

    /**
     * Menyimpan data jurnal dengan deteksi keluhan berulang (Nama + No. Resi).
     *
     * @see DeteksiDuplikatService untuk aturan dua tingkat KEMBAR / BERULANG.
     */
    public function store(Request $request)
    {
        if ($request->has('nominal_transaksi')) {
            $cleanNominal = preg_replace('/[^\d]/', '', (string) $request->input('nominal_transaksi'));
            $request->merge(['nominal_transaksi' => $cleanNominal !== '' ? (float) $cleanNominal : null]);
        }
        if ($request->has('biaya_admin')) {
            $cleanBiaya = preg_replace('/[^\d]/', '', (string) $request->input('biaya_admin'));
            $request->merge(['biaya_admin' => $cleanBiaya !== '' ? (float) $cleanBiaya : null]);
        }

        $request->validate([
            'nama_nasabah' => 'required|string|max:255',
            'no_resi' => 'required|string|max:255',
            'no_rekening' => 'required|string|max:255',
            'no_kartu' => 'nullable|string|max:255',
            'no_tiket' => 'nullable|string|max:255',
            'master_cabang_id' => 'required|exists:master_cabangs,id',
            'master_transaksi_id' => 'required|exists:master_transaksis,id',
            'terminal_transaksi' => 'nullable|string|max:255',
            'nominal_transaksi' => 'required|numeric|min:0',
            'biaya_admin' => 'nullable|numeric|min:0',
            'tgl_transaksi' => 'required|date',
            'tgl_terima' => 'required|date',
            'tgl_selesai' => 'required|date',
            'status' => 'required|string|max:50',
            'permasalahan' => 'nullable|string|max:255',
            'keterangan_log' => 'nullable|string',
            'pengaduan_id' => 'nullable|integer|exists:pengaduans,id',
            'konfirmasi_duplikat' => 'nullable|string|max:512',
        ]);

        // Deteksi keluhan berulang. Nilainya dirapikan lebih dulu agar yang
        // dibandingkan sama persis dengan yang nanti disimpan ke database.
        $namaNorm = DeteksiDuplikatService::normalNama($request->nama_nasabah);
        $resiNorm = DeteksiDuplikatService::normalResi($request->no_resi);

        // Pengaduan yang menjadi sumber jurnal ini dikecualikan: tautannya baru dibuat
        // setelah jurnal tersimpan, jadi tanpa ini setiap jurnal dari CS akan dianggap
        // berulang terhadap pengaduannya sendiri.
        $gagal = $this->tolakBilaBerulang(
            $request,
            $namaNorm,
            $resiNorm,
            $request->tgl_transaksi,
            null,
            $request->filled('pengaduan_id') ? (int) $request->pengaduan_id : null
        );

        if ($gagal) {
            return $gagal;
        }

        // Pengaduan CS cabang yang menjadi sumber (opsional)
        $pengaduanSumber = null;
        if ($request->filled('pengaduan_id')) {
            $pengaduanSumber = Pengaduan::find($request->pengaduan_id);
            if ($pengaduanSumber && $pengaduanSumber->jurnal_id !== null) {
                return back()->withInput()->withErrors([
                    'pengaduan_id' => "Pengaduan {$pengaduanSumber->nomor_tiket} sudah tertaut ke jurnal lain.",
                ]);
            }
        }

        // Kolom disusun satu per satu, bukan dari $request->except(). Menyalin
        // seluruh isi request membuat setiap kolom tabel dapat ditulis hanya
        // dengan menambah input bernama sama pada permintaan.
        $data = [
            'nama_nasabah' => $namaNorm,
            'no_resi' => $resiNorm,
            'no_rekening' => $request->no_rekening,
            'no_kartu' => $request->filled('no_kartu') ? $request->no_kartu : '-',
            'master_cabang_id' => $request->master_cabang_id,
            'master_transaksi_id' => $request->master_transaksi_id,
            'terminal_transaksi' => $request->filled('terminal_transaksi') ? $request->terminal_transaksi : '-',
            'nominal_transaksi' => $request->nominal_transaksi,
            'biaya_admin' => $request->filled('biaya_admin') ? (float) $request->biaya_admin : 0,
            'tgl_transaksi' => $request->tgl_transaksi,
            'tgl_terima' => $request->tgl_terima,
            'tgl_selesai' => $request->tgl_selesai,
            'status' => $this->normalisasiStatus($request->status),
            'permasalahan' => $request->filled('permasalahan') ? strtoupper(trim($request->permasalahan)) : '-',
            'keterangan_log' => $request->filled('keterangan_log') ? trim($request->keterangan_log) : '-',
        ];

        // Sinkronisasi channel yang dipilih dengan master transaksi
        if ($request->filled('channel') && $request->filled('master_transaksi_id')) {
            $currentMT = MasterTransaksi::find($request->master_transaksi_id);
            if ($currentMT && strtoupper(trim($currentMT->channel)) !== strtoupper(trim($request->channel))) {
                $matchedMT = MasterTransaksi::firstOrCreate(
                    [
                        'jenis_transaksi' => $currentMT->jenis_transaksi,
                        'channel' => strtoupper(trim($request->channel)),
                    ],
                    [
                        'biaya_admin' => $currentMT->biaya_admin ?? 0,
                    ]
                );
                $data['master_transaksi_id'] = $matchedMT->id;
            }
        }

        // Jurnal yang berasal dari pengaduan cabang memakai nomor tiket milik
        // pengaduan tersebut, sehingga satu keluhan bernomor sama dari CS sampai
        // selesai. Jurnal yang diinput langsung nomor tiketnya diketik petugas,
        // sebab nomor itu berasal dari proses sebelum berkas sampai ke Divisi IT
        // (cabang → penyelia → Divisi Literasi), bukan dari tanggal terima di form ini.
        $data['no_tiket'] = $pengaduanSumber
            ? $pengaduanSumber->nomor_tiket
            : ($request->filled('no_tiket') ? NomorTiketService::rapikan($request->no_tiket) : '-');

        $jurnal = Jurnal::create($data);

        // Tautkan ke pengaduan CS cabang → status pengaduan menjadi "Diproses"
        if ($pengaduanSumber) {
            $pengaduanSumber->fill([
                'diterima_oleh' => $pengaduanSumber->diterima_oleh ?? $request->user()->id,
                'diterima_at' => $pengaduanSumber->diterima_at ?? now(),
            ])->save();

            JurnalObserver::sinkronkan($pengaduanSumber, $jurnal);

            return redirect()->route('admin.pengaduan.show', $pengaduanSumber)
                ->with('success', "Jurnal keluhan atas nama {$jurnal->nama_nasabah} berhasil disimpan dan pengaduan {$pengaduanSumber->nomor_tiket} kini berstatus Dalam Proses.");
        }

        return redirect()->route('jurnal.index')
            ->with('success', "Data jurnal keluhan atas nama {$jurnal->nama_nasabah} berhasil disimpan!");
    }

    /**
     * Halaman Pratinjau (Preview) setelah User berhasil Input Jurnal
     */
    public function preview($id)
    {
        $jurnal = Jurnal::with(['masterCabang', 'masterTransaksi', 'pengaduan.cabang', 'pengaduan.lampirans'])->findOrFail($id);

        // Memanfaatkan logic penamaan ATM
        $atmsGrouped = MasterAtm::getAtmsGroupedByCabang();
        $term = trim($jurnal->terminal_transaksi ?? '-');

        $atmInfo = null;
        if ($term !== '-' && strtoupper($term) !== 'BANK LAIN' && strtoupper($term) !== 'MOBILE BANKING' && strtoupper($term) !== 'SMS BANKING') {
            $termClean = strtoupper($term);
            foreach ($atmsGrouped as $kode => $atms) {
                foreach ($atms as $atm) {
                    $val = strtoupper(trim($atm['value'] ?? ''));
                    $prof = strtoupper(trim($atm['profil'] ?? ''));
                    $idLuno = trim((string) ($atm['id_luno'] ?? ''));
                    if ($termClean === $val || $termClean === $prof || str_contains($termClean, $prof) || str_contains($val, $termClean) || ($idLuno && $termClean === $idLuno)) {
                        $atmInfo = $atm;
                        break 2;
                    }
                }
            }
        }

        return view('jurnal_preview', compact('jurnal', 'atmInfo'));
    }

    /**
     * API AJAX untuk mengambil data master transaksi (Biaya admin & channel)
     */
    public function getDetailTransaksi($id)
    {
        $transaksi = MasterTransaksi::find($id);

        return response()->json($transaksi);
    }

    /**
     * API AJAX untuk mengambil daftar mesin ATM berdasarkan Kantor Cabang
     */
    public function getAtmsByCabang($id)
    {
        $cabang = MasterCabang::find($id);
        $grouped = MasterAtm::getAtmsGroupedByCabang();
        $kode = $cabang ? $cabang->kode_cabang : null;
        $atms = ($kode && isset($grouped[$kode])) ? $grouped[$kode] : [];

        return response()->json([
            'kode_cabang' => $kode,
            'nama_cabang' => $cabang->nama_cabang ?? '',
            'atms' => $atms,
        ]);
    }

    /**
     * API AJAX untuk mengambil rincian 1 data jurnal keluhan
     */
    public function getDetailJurnal($id)
    {
        $jurnal = Jurnal::with(['masterCabang', 'masterTransaksi', 'pengaduan:id,jurnal_id,nomor_tiket,status'])->find($id);

        return response()->json($jurnal);
    }

    /**
     * Menampilkan formulir edit data jurnal keluhan
     */
    public function edit($id)
    {
        $jurnal = Jurnal::with(['masterCabang', 'masterTransaksi', 'pengaduan.cabang', 'pengaduan.lampirans'])->findOrFail($id);
        $cabangs = MasterCabang::orderBy('kode_cabang')->get();
        $transaksis = MasterTransaksi::whereIn('id', range(1, 33))->orderBy('id')->get();
        $atmsGrouped = MasterAtm::getAtmsGroupedByCabang();

        return view('jurnal_edit', compact('jurnal', 'cabangs', 'transaksis', 'atmsGrouped'));
    }

    /**
     * Memperbarui data jurnal keluhan nasabah
     */
    public function update(Request $request, $id)
    {
        $jurnal = Jurnal::with('pengaduan')->findOrFail($id);

        if ($request->has('nominal_transaksi')) {
            $cleanNominal = preg_replace('/[^\d]/', '', (string) $request->input('nominal_transaksi'));
            $request->merge(['nominal_transaksi' => $cleanNominal !== '' ? (float) $cleanNominal : null]);
        }
        if ($request->has('biaya_admin')) {
            $cleanBiaya = preg_replace('/[^\d]/', '', (string) $request->input('biaya_admin'));
            $request->merge(['biaya_admin' => $cleanBiaya !== '' ? (float) $cleanBiaya : null]);
        }

        $request->validate([
            'nama_nasabah' => 'required|string|max:255',
            'no_resi' => 'required|string|max:255',
            'no_rekening' => 'required|string|max:255',
            'no_kartu' => 'nullable|string|max:255',
            'no_tiket' => 'nullable|string|max:255',
            'master_cabang_id' => 'required|exists:master_cabangs,id',
            'master_transaksi_id' => 'required|exists:master_transaksis,id',
            'terminal_transaksi' => 'nullable|string|max:255',
            'nominal_transaksi' => 'required|numeric|min:0',
            'biaya_admin' => 'nullable|numeric|min:0',
            'tgl_transaksi' => 'required|date',
            'tgl_terima' => 'required|date',
            'tgl_selesai' => 'required|date',
            'status' => 'required|string|max:50',
            'permasalahan' => 'nullable|string|max:255',
            'keterangan_log' => 'nullable|string',
            'konfirmasi_duplikat' => 'nullable|string|max:512',
        ]);

        $namaNorm = DeteksiDuplikatService::normalNama($request->nama_nasabah);
        $resiNorm = DeteksiDuplikatService::normalResi($request->no_resi);

        // Jurnal ini sendiri diabaikan supaya tidak melaporkan dirinya sebagai kembaran.
        if ($gagal = $this->tolakBilaBerulang($request, $namaNorm, $resiNorm, $request->tgl_transaksi, (int) $id)) {
            return $gagal;
        }

        // Sama seperti store(): daftar kolom ditulis eksplisit agar tidak ada
        // kolom yang ikut terbawa dari isi request.
        $data = [
            'nama_nasabah' => $namaNorm,
            'no_resi' => $resiNorm,
            'no_rekening' => $request->no_rekening,
            'no_kartu' => $request->filled('no_kartu') ? $request->no_kartu : '-',
            'master_cabang_id' => $request->master_cabang_id,
            'master_transaksi_id' => $request->master_transaksi_id,
            'nominal_transaksi' => $request->nominal_transaksi,
            'biaya_admin' => $request->filled('biaya_admin') ? (float) $request->biaya_admin : 0,
            'tgl_transaksi' => $request->tgl_transaksi,
            'tgl_terima' => $request->tgl_terima,
            'tgl_selesai' => $request->tgl_selesai,
            'status' => $this->normalisasiStatus($request->status),
        ];

        // Jurnal yang berasal dari pengaduan CS memakai nomor tiket pengaduan dan
        // tidak dapat diubah. Jurnal yang diinput langsung tetap diketik petugas,
        // karena nomor tiketnya berasal dari proses sebelum masuk ke Divisi IT.
        $data['no_tiket'] = $jurnal->pengaduan
            ? $jurnal->no_tiket
            : ($request->filled('no_tiket') ? NomorTiketService::rapikan($request->no_tiket) : ($jurnal->no_tiket ?: '-'));
        $data['terminal_transaksi'] = $request->filled('terminal_transaksi') ? $request->terminal_transaksi : '-';
        $data['permasalahan'] = $request->filled('permasalahan') ? strtoupper(trim($request->permasalahan)) : '-';
        $data['keterangan_log'] = $request->filled('keterangan_log') ? trim($request->keterangan_log) : '-';

        // Sinkronisasi channel yang dipilih dengan master transaksi
        if ($request->filled('channel') && $request->filled('master_transaksi_id')) {
            $currentMT = MasterTransaksi::find($request->master_transaksi_id);
            if ($currentMT && strtoupper(trim($currentMT->channel)) !== strtoupper(trim($request->channel))) {
                $matchedMT = MasterTransaksi::firstOrCreate(
                    [
                        'jenis_transaksi' => $currentMT->jenis_transaksi,
                        'channel' => strtoupper(trim($request->channel)),
                    ],
                    [
                        'biaya_admin' => $currentMT->biaya_admin ?? 0,
                    ]
                );
                $data['master_transaksi_id'] = $matchedMT->id;
            }
        }

        $jurnal->update($data);

        return redirect()->route('jurnal.index')->with('success', "Perubahan data jurnal keluhan {$jurnal->nama_nasabah} berhasil disimpan!");
    }

    /**
     * Menghapus data jurnal keluhan
     */
    public function destroy($id)
    {
        $jurnal = Jurnal::findOrFail($id);

        // Jejak audit milik jurnal ini sengaja TIDAK ikut dihapus. Justru
        // penghapusan itulah kejadian yang paling perlu terekam. Kolom
        // audit_trails.jurnal_id bersifat nullOnDelete, sehingga barisnya
        // bertahan dengan tautan terlepas, sedangkan auditable_id tetap
        // menyimpan id jurnal yang dihapus. JurnalObserver::deleted() juga
        // merekam seluruh isi baris sebelum hilang.

        $namaNasabah = $jurnal->nama_nasabah;
        $noResi = $jurnal->no_resi;

        $jurnal->delete();

        return redirect()->route('jurnal.index')->with('success', "Data jurnal keluhan {$namaNasabah} (No. Resi: {$noResi}) berhasil dihapus.");
    }

    /**
     * Mengekspor data jurnal keluhan ke Master Template Multi-Sheet Excel (.xlsx)
     */
    public function exportExcel(Request $request)
    {
        $query = Jurnal::with(['masterCabang', 'masterTransaksi']);

        // 1. Filter Pencarian Keyword Multi-Field
        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $terms = array_filter(explode(' ', $keyword));

            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('nama_nasabah', 'LIKE', "%{$term}%")
                            ->orWhere('no_resi', 'LIKE', "%{$term}%")
                            ->orWhere('no_rekening', 'LIKE', "%{$term}%")
                            ->orWhere('no_kartu', 'LIKE', "%{$term}%")
                            ->orWhere('no_tiket', 'LIKE', "%{$term}%")
                            ->orWhere('terminal_transaksi', 'LIKE', "%{$term}%")
                            ->orWhereHas('masterCabang', function ($cQ) use ($term) {
                                $cQ->where('nama_cabang', 'LIKE', "%{$term}%")
                                    ->orWhere('kode_cabang', 'LIKE', "%{$term}%");
                            })
                            ->orWhereHas('masterTransaksi', function ($tQ) use ($term) {
                                $tQ->where('jenis_transaksi', 'LIKE', "%{$term}%")
                                    ->orWhere('channel', 'LIKE', "%{$term}%");
                            });
                    });
                }
            });
        }

        // 2. Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filter Cabang
        if ($request->filled('master_cabang_id')) {
            $query->where('master_cabang_id', $request->master_cabang_id);
        }

        // 4. Filter Rentang Tanggal Transaksi
        if ($request->filled('tgl_dari')) {
            $query->whereDate('tgl_transaksi', '>=', $request->tgl_dari);
        }
        if ($request->filled('tgl_sampai')) {
            $query->whereDate('tgl_transaksi', '<=', $request->tgl_sampai);
        }

        if ($request->filled('tgl_dari') || $request->filled('tgl_sampai')) {
            $query->orderBy('tgl_transaksi', 'asc')->orderBy('id', 'asc');
        } else {
            $query->orderBy('id', 'asc');
        }

        $jurnals = $query->with(['masterCabang', 'masterTransaksi'])->get();

        $spreadsheet = $this->buildCleanExportSpreadsheet($jurnals);

        $fileName = 'Jurnal_Keluhan_BankSulteng_'.date('Ymd_His').'.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Access-Control-Expose-Headers' => 'Content-Disposition, X-Total-Count',
            'X-Total-Count' => (string) count($jurnals),
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Membangun berkas Master Spreadsheet Multi-Sheet secara in-memory dengan format tabel identik
     */
    private function buildCleanExportSpreadsheet($jurnals)
    {
        $spreadsheet = new Spreadsheet;

        // 1. SHEET 1: KELUHAN (Identik dengan format tabel berkas Master Bank Sulteng)
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('KELUHAN');

        // Header Baris 2
        $headers = [
            'A2' => 'No',
            'B2' => 'Nama Nasabah',
            'C2' => 'Tgl Terima',
            'D2' => 'Tgl Selesai',
            'E2' => 'Tgl Transaksi Keluhan',
            'F2' => 'No Tiket',
            'G2' => 'No. Kartu',
            'H2' => 'No. Rekening',
            'I2' => 'No. Resi',
            'J2' => 'JENIS TRANSAKSI',
            'K2' => 'BIAYA ADMIN',
            'L2' => 'Principal/ Chanel',
            'M2' => 'Cabang  Transaksi',
            'N2' => 'Terminal transaksi',
            'O2' => 'Nominal Transaksi',
            'P2' => 'KETERANGAN',
            'Q2' => 'STATUS',
        ];

        foreach ($headers as $cell => $val) {
            $sheet1->setCellValue($cell, $val);
        }

        // Sub-Header Baris 3 (Penomoran Kolom 1 s/d 17)
        $colNumbers = [
            'A3' => 1, 'B3' => 2, 'C3' => 3, 'D3' => 4, 'E3' => 5,
            'F3' => 6, 'G3' => 7, 'H3' => 8, 'I3' => 9, 'J3' => 10,
            'K3' => 11, 'L3' => 12, 'M3' => 13, 'N3' => 14, 'O3' => 15,
            'P3' => 16, 'Q3' => 17,
        ];
        foreach ($colNumbers as $cell => $val) {
            $sheet1->setCellValue($cell, $val);
        }

        // Styling Header Baris 2 & 3
        $headerStyle = [
            'font' => ['name' => 'Calibri', 'bold' => true, 'color' => ['rgb' => '000000'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
        ];
        $sheet1->getStyle('A2:Q2')->applyFromArray($headerStyle);
        $sheet1->getStyle('A3:Q3')->applyFromArray($headerStyle);
        $sheet1->getStyle('A3:Q3')->getFont()->setSize(9)->setBold(false)->getColor()->setRGB('64748B');

        $sheet1->getRowDimension(2)->setRowHeight(28);
        $sheet1->getRowDimension(3)->setRowHeight(18);

        // Lebar Kolom Sesuai Format File Master Asli
        $columnWidths = [
            'A' => 6,  'B' => 32, 'C' => 18, 'D' => 18, 'E' => 22,
            'F' => 22, 'G' => 22, 'H' => 20, 'I' => 18, 'J' => 35,
            'K' => 15, 'L' => 18, 'M' => 28, 'N' => 26, 'O' => 20,
            'P' => 50, 'Q' => 16,
        ];
        foreach ($columnWidths as $col => $w) {
            $sheet1->getColumnDimension($col)->setWidth($w);
        }
        $sheet1->freezePane('A4');

        // Format Tanggal Indonesia Helper
        $formatTgl = function ($dateVal) {
            if (empty($dateVal)) {
                return '';
            }
            try {
                $c = Carbon::parse($dateVal);
                $bulanIndo = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                ];

                return $c->format('d').' '.($bulanIndo[(int) $c->format('n')] ?? $c->format('F')).' '.$c->format('Y');
            } catch (\Exception $e) {
                return (string) $dateVal;
            }
        };

        $startRow = 4;
        $currentRow = $startRow;

        foreach ($jurnals as $index => $item) {
            $sheet1->setCellValue("A{$currentRow}", $index + 1);
            $sheet1->setCellValue("B{$currentRow}", $item->nama_nasabah ?? '');
            $sheet1->setCellValue("C{$currentRow}", $formatTgl($item->tgl_terima));
            $sheet1->setCellValue("D{$currentRow}", $formatTgl($item->tgl_selesai));
            $sheet1->setCellValue("E{$currentRow}", $formatTgl($item->tgl_transaksi));

            // PROTEKSI NOMOR: Format teks eksplisit (DataType::TYPE_STRING) agar awalan nol tidak hilang dan TIDAK BERUBAH MENJADI 5.000E+12
            $sheet1->setCellValueExplicit("F{$currentRow}", (string) ($item->no_tiket ?? ''), DataType::TYPE_STRING);
            $sheet1->setCellValueExplicit("G{$currentRow}", (string) ($item->no_kartu ?? ''), DataType::TYPE_STRING);
            $sheet1->setCellValueExplicit("H{$currentRow}", (string) ($item->no_rekening ?? ''), DataType::TYPE_STRING);
            $sheet1->setCellValueExplicit("I{$currentRow}", (string) ($item->no_resi ?? ''), DataType::TYPE_STRING);

            $sheet1->setCellValue("J{$currentRow}", $item->masterTransaksi->jenis_transaksi ?? '');
            $sheet1->setCellValue("K{$currentRow}", (float) ($item->biaya_admin ?? $item->masterTransaksi->biaya_admin ?? 0));
            $sheet1->setCellValue("L{$currentRow}", $item->masterTransaksi->channel ?? '');

            // Format Cabang: "001 - CABANG UTAMA"
            $cabangText = '';
            if ($item->masterCabang) {
                $kode = str_pad($item->masterCabang->kode_cabang ?? '', 3, '0', STR_PAD_LEFT);
                $cabangText = $kode.' - '.($item->masterCabang->nama_cabang ?? '');
            }
            $sheet1->setCellValue("M{$currentRow}", $cabangText);

            $sheet1->setCellValue("N{$currentRow}", $item->terminal_transaksi ?? '');
            $sheet1->setCellValue("O{$currentRow}", (float) ($item->nominal_transaksi ?? 0));
            $sheet1->setCellValue("P{$currentRow}", $item->permasalahan ?: ($item->keterangan_log ?? ''));
            $sheet1->setCellValue("Q{$currentRow}", $item->status ?? '');

            $currentRow++;
        }

        $endRow = $currentRow - 1;
        if ($endRow >= $startRow) {
            // Terapkan Range Alignment & Number Formatting
            $sheet1->getStyle("A4:A{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("C4:E{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Format Text eksplisit pada Kolom Nomor
            $sheet1->getStyle("F4:I{$endRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            $sheet1->getStyle("F4:I{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet1->getStyle("K4:K{$endRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet1->getStyle("O4:O{$endRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet1->getStyle("L4:L{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("Q4:Q{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet1->getStyle("A4:Q{$endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            $sheet1->getStyle("A4:Q{$endRow}")->getFont()->setName('Calibri')->setSize(10);
        }

        // 2. SHEET 2: FORMAT_SLIP_JURNAL
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('FORMAT_SLIP_JURNAL');

        $sheet2->setCellValue('B2', 'PT BANK SULTENG');
        $sheet2->setCellValue('B3', 'LEMBAR SLIP JURNAL PENANGANAN KELUHAN NASABAH');
        $sheet2->setCellValue('B4', 'Pilih No. Urut Transaksi (Diinput pada Sel C6 di bawah):');
        $sheet2->mergeCells('B2:G2');
        $sheet2->mergeCells('B3:G3');
        $sheet2->mergeCells('B4:G4');

        $sheet2->getStyle('B2')->getFont()->setName('Segoe UI')->setSize(16)->setBold(true)->getColor()->setRGB('0B2F64');
        $sheet2->getStyle('B3')->getFont()->setName('Segoe UI')->setSize(11)->setBold(true)->getColor()->setRGB('0066CC');
        $sheet2->getStyle('B4')->getFont()->setName('Segoe UI')->setSize(9.5)->setItalic(true)->getColor()->setRGB('64748B');

        $sheet2->setCellValue('B6', 'PILIH NO. URUT:');
        $sheet2->setCellValue('C6', 1);
        $sheet2->setCellValue('D6', '<-- (Ganti nomor urut ini untuk memilih nasabah)');
        $sheet2->getStyle('B6')->getFont()->setName('Segoe UI')->setBold(true)->setSize(11)->getColor()->setRGB('0B2F64');
        $sheet2->getStyle('C6')->getFont()->setName('Segoe UI')->setBold(true)->setSize(13)->getColor()->setRGB('B45309');
        $sheet2->getStyle('C6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('C6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
        $sheet2->getStyle('C6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('F59E0B');
        $sheet2->getStyle('D6')->getFont()->setName('Segoe UI')->setItalic(true)->setSize(9)->getColor()->setRGB('94A3B8');

        $sheet2->setCellValue('B8', 'I. INFORMASI NASABAH & IDENTITAS');
        $sheet2->mergeCells('B8:G8');
        $sheet2->getStyle('B8')->getFont()->setName('Segoe UI')->setBold(true)->setSize(10.5)->getColor()->setRGB('FFFFFF');
        $sheet2->getStyle('B8:G8')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B2F64');

        $maxLookup = max(1000, count($jurnals) + 50);

        $slipFields1 = [
            9 => ['Nama Nasabah',        '=IFERROR(INDEX(KELUHAN!$B$4:$B$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
            10 => ['Nomor Rekening',      '=IFERROR(INDEX(KELUHAN!$H$4:$H$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
            11 => ['Nomor Resi / Trace',  '=IFERROR(INDEX(KELUHAN!$I$4:$I$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
            12 => ['Nomor Kartu ATM',     '=IFERROR(INDEX(KELUHAN!$G$4:$G$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
            13 => ['Nomor Tiket CS',      '=IFERROR(INDEX(KELUHAN!$F$4:$F$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
            14 => ['Kantor Cabang',       '=IFERROR(INDEX(KELUHAN!$M$4:$M$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
        ];

        foreach ($slipFields1 as $row => [$label, $formula]) {
            $sheet2->setCellValue("B{$row}", $label);
            $sheet2->setCellValue("D{$row}", $formula);
            $sheet2->mergeCells("B{$row}:C{$row}");
            $sheet2->mergeCells("D{$row}:G{$row}");
            $sheet2->getStyle("B{$row}")->getFont()->setName('Segoe UI')->setBold(true)->setSize(10)->getColor()->setRGB('334155');
            $sheet2->getStyle("D{$row}")->getFont()->setName('Segoe UI')->setSize(10)->getColor()->setRGB('0F172A');
        }

        $sheet2->setCellValue('B16', 'II. RINCIAN TRANSAKSI & FINANSIAL');
        $sheet2->mergeCells('B16:G16');
        $sheet2->getStyle('B16')->getFont()->setName('Segoe UI')->setBold(true)->setSize(10.5)->getColor()->setRGB('FFFFFF');
        $sheet2->getStyle('B16:G16')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B2F64');

        $slipFields2 = [
            17 => ['Jenis Transaksi',     '=IFERROR(INDEX(KELUHAN!$J$4:$J$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
            18 => ['Channel Pembayaran',  '=IFERROR(INDEX(KELUHAN!$L$4:$L$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
            19 => ['Terminal / ATM',      '=IFERROR(INDEX(KELUHAN!$N$4:$N$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
            20 => ['Nominal Transaksi',   '=IFERROR(INDEX(KELUHAN!$O$4:$O$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), 0)'],
            21 => ['Biaya Admin',         '=IFERROR(INDEX(KELUHAN!$K$4:$K$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), 0)'],
            22 => ['Status Penyelesaian', '=IFERROR(INDEX(KELUHAN!$Q$4:$Q$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
        ];

        foreach ($slipFields2 as $row => [$label, $formula]) {
            $sheet2->setCellValue("B{$row}", $label);
            $sheet2->setCellValue("D{$row}", $formula);
            $sheet2->mergeCells("B{$row}:C{$row}");
            $sheet2->mergeCells("D{$row}:G{$row}");
            $sheet2->getStyle("B{$row}")->getFont()->setName('Segoe UI')->setBold(true)->setSize(10)->getColor()->setRGB('334155');
            $sheet2->getStyle("D{$row}")->getFont()->setName('Segoe UI')->setSize(10)->getColor()->setRGB('0F172A');
        }
        $sheet2->getStyle('D20:D21')->getNumberFormat()->setFormatCode('"Rp " #,##0');
        $sheet2->getStyle('D20')->getFont()->setBold(true)->getColor()->setRGB('047857');

        $sheet2->setCellValue('B24', 'III. HISTORI TANGGAL & KETERANGAN');
        $sheet2->mergeCells('B24:G24');
        $sheet2->getStyle('B24')->getFont()->setName('Segoe UI')->setBold(true)->setSize(10.5)->getColor()->setRGB('FFFFFF');
        $sheet2->getStyle('B24:G24')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B2F64');

        $slipFields3 = [
            25 => ['Tgl Transaksi',  '=IFERROR(INDEX(KELUHAN!$E$4:$E$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
            26 => ['Tgl Terima CS',  '=IFERROR(INDEX(KELUHAN!$C$4:$C$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
            27 => ['Tgl Selesai',    '=IFERROR(INDEX(KELUHAN!$D$4:$D$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
            28 => ['Keterangan Log', '=IFERROR(INDEX(KELUHAN!$P$4:$P$'.$maxLookup.', MATCH($C$6, KELUHAN!$A$4:$A$'.$maxLookup.', 0)), "-")'],
        ];

        foreach ($slipFields3 as $row => [$label, $formula]) {
            $sheet2->setCellValue("B{$row}", $label);
            $sheet2->setCellValue("D{$row}", $formula);
            $sheet2->mergeCells("B{$row}:C{$row}");
            $sheet2->mergeCells("D{$row}:G{$row}");
            $sheet2->getStyle("B{$row}")->getFont()->setName('Segoe UI')->setBold(true)->setSize(10)->getColor()->setRGB('334155');
            $sheet2->getStyle("D{$row}")->getFont()->setName('Segoe UI')->setSize(10)->getColor()->setRGB('0F172A');
        }
        $sheet2->getRowDimension(28)->setRowHeight(35);
        $sheet2->getStyle('D28')->getAlignment()->setWrapText(true);

        $boxBorder = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
        $sheet2->getStyle('B8:G14')->applyFromArray($boxBorder);
        $sheet2->getStyle('B16:G22')->applyFromArray($boxBorder);
        $sheet2->getStyle('B24:G28')->applyFromArray($boxBorder);

        $sheet2->setCellValue('B31', 'Dibuat Oleh,');
        $sheet2->setCellValue('B32', 'Petugas CS / Teller');
        $sheet2->setCellValue('B36', '( ........................................ )');
        $sheet2->mergeCells('B31:C31');
        $sheet2->mergeCells('B32:C32');
        $sheet2->mergeCells('B36:C36');
        $sheet2->getStyle('B31:C36')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet2->setCellValue('D31', 'Diverifikasi Oleh,');
        $sheet2->setCellValue('D32', 'Penyelia / Supervisor');
        $sheet2->setCellValue('D36', '( ........................................ )');
        $sheet2->mergeCells('D31:E31');
        $sheet2->mergeCells('D32:E32');
        $sheet2->mergeCells('D36:E36');
        $sheet2->getStyle('D31:E36')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet2->setCellValue('F31', 'Disetujui Oleh,');
        $sheet2->setCellValue('F32', 'Pimpinan Seksi / Cabang');
        $sheet2->setCellValue('F36', '( ........................................ )');
        $sheet2->mergeCells('F31:G31');
        $sheet2->mergeCells('F32:G32');
        $sheet2->mergeCells('F36:G36');
        $sheet2->getStyle('F31:G36')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('B31:G36')->getFont()->setName('Segoe UI')->setSize(9.5)->getColor()->setRGB('334155');

        $sheet2->getColumnDimension('A')->setWidth(4);
        $sheet2->getColumnDimension('B')->setWidth(18);
        $sheet2->getColumnDimension('C')->setWidth(18);
        $sheet2->getColumnDimension('D')->setWidth(18);
        $sheet2->getColumnDimension('E')->setWidth(18);
        $sheet2->getColumnDimension('F')->setWidth(18);
        $sheet2->getColumnDimension('G')->setWidth(18);

        // 3. SHEET 3: REKAP_STATUS_CABANG
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('REKAP_STATUS_CABANG');

        $sheet3->setCellValue('A1', 'PT BANK SULTENG - REKAPITULASI STATUS & AGREGASI KELUHAN');
        $sheet3->setCellValue('A2', 'Ringkasan Eksekutif & Statistik Otomatis Berdasarkan Data Master');
        $sheet3->mergeCells('A1:F1');
        $sheet3->mergeCells('A2:F2');

        $sheet3->getStyle('A1')->getFont()->setName('Segoe UI')->setSize(14)->setBold(true)->getColor()->setRGB('0B2F64');
        $sheet3->getStyle('A2')->getFont()->setName('Segoe UI')->setSize(10)->setItalic(true)->getColor()->setRGB('64748B');

        $sheet3->setCellValue('A4', 'I. REKAPITULASI BERDASARKAN STATUS PENYELESAIAN');
        $sheet3->mergeCells('A4:D4');
        $sheet3->getStyle('A4')->getFont()->setName('Segoe UI')->setBold(true)->setSize(11)->getColor()->setRGB('0B2F64');

        $sheet3->setCellValue('A5', 'Status Keluhan');
        $sheet3->setCellValue('B5', 'Jumlah Transaksi');
        $sheet3->setCellValue('C5', 'Total Nominal (Rp)');
        $sheet3->setCellValue('D5', 'Persentase (%)');
        $sheet3->getStyle('A5:D5')->applyFromArray($headerStyle);

        $statusList = [
            ['Menunggu', 6],
            ['Success',  7],
            ['Done',     8],
            ['Rejected', 9],
        ];

        foreach ($statusList as [$st, $r]) {
            $sheet3->setCellValue("A{$r}", $st);
            $sheet3->setCellValue("B{$r}", '=COUNTIF(KELUHAN!$Q$4:$Q$'.$maxLookup.', "'.$st.'")');
            $sheet3->setCellValue("C{$r}", '=SUMIFS(KELUHAN!$O$4:$O$'.$maxLookup.', KELUHAN!$Q$4:$Q$'.$maxLookup.', "'.$st.'")');
            $sheet3->setCellValue("D{$r}", "=IF(B10>0, B{$r}/B10, 0)");
            $sheet3->getStyle("A{$r}")->getFont()->setName('Segoe UI')->setBold(true);
            $sheet3->getStyle("B{$r}:D{$r}")->getFont()->setName('Segoe UI');
            $sheet3->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet3->getStyle("C{$r}")->getNumberFormat()->setFormatCode('"Rp " #,##0');
            $sheet3->getStyle("D{$r}")->getNumberFormat()->setFormatCode('0.0%');
        }

        $sheet3->setCellValue('A10', 'TOTAL KESELURUHAN');
        $sheet3->setCellValue('B10', '=SUM(B6:B9)');
        $sheet3->setCellValue('C10', '=SUM(C6:C9)');
        $sheet3->setCellValue('D10', '=SUM(D6:D9)');

        $totalStyle = [
            'font' => ['name' => 'Segoe UI', 'bold' => true, 'color' => ['rgb' => '0B2F64']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']]],
        ];
        $sheet3->getStyle('A10:D10')->applyFromArray($totalStyle);
        $sheet3->getStyle('B10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet3->getStyle('C10')->getNumberFormat()->setFormatCode('"Rp " #,##0');
        $sheet3->getStyle('D10')->getNumberFormat()->setFormatCode('0.0%');
        $sheet3->getStyle('A5:D10')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        $sheet3->getStyle('A10:D10')->getBorders()->getTop()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setRGB('0B2F64');

        $sheet3->setCellValue('A13', 'II. REKAPITULASI PER KANTOR CABANG');
        $sheet3->mergeCells('A13:F13');
        $sheet3->getStyle('A13')->getFont()->setName('Segoe UI')->setBold(true)->setSize(11)->getColor()->setRGB('0B2F64');

        $sheet3->setCellValue('A14', 'Kode');
        $sheet3->setCellValue('B14', 'Nama Kantor Cabang');
        $sheet3->setCellValue('C14', 'Total Transaksi');
        $sheet3->setCellValue('D14', 'Menunggu');
        $sheet3->setCellValue('E14', 'Selesai (Success/Done)');
        $sheet3->setCellValue('F14', 'Total Nominal (Rp)');
        $sheet3->getStyle('A14:F14')->applyFromArray($headerStyle);

        $cabangData = [
            ['001', 'Kantor Cabang Utama Palu'],
            ['002', 'Kantor Cabang Parigi'],
            ['003', 'Kantor Cabang Poso'],
            ['004', 'Kantor Cabang Tolitoli'],
            ['005', 'Kantor Cabang Luwuk'],
            ['006', 'Kantor Cabang Buol'],
            ['007', 'Kantor Cabang Donggala'],
            ['008', 'Kantor Cabang Banggai'],
            ['009', 'Kantor Cabang Tinombo'],
            ['010', 'Kantor Cabang Morowali'],
            ['011', 'Kantor Cabang Ampana'],
            ['012', 'Kantor Cabang Sigi'],
            ['013', 'Kantor Cabang Kolonodale'],
            ['014', 'Kantor Cabang Salakan'],
            ['015', 'Kantor Cabang Jakarta'],
        ];

        $rowCabang = 15;
        foreach ($cabangData as [$kode, $nama]) {
            $sheet3->setCellValue("A{$rowCabang}", $kode);
            $sheet3->setCellValue("B{$rowCabang}", $nama);
            $sheet3->setCellValue("C{$rowCabang}", '=COUNTIF(KELUHAN!$M$4:$M$'.$maxLookup.', "*'.$kode.'*")');
            $sheet3->setCellValue("D{$rowCabang}", '=COUNTIFS(KELUHAN!$M$4:$M$'.$maxLookup.', "*'.$kode.'*", KELUHAN!$Q$4:$Q$'.$maxLookup.', "Menunggu")');
            $sheet3->setCellValue("E{$rowCabang}", '=COUNTIFS(KELUHAN!$M$4:$M$'.$maxLookup.', "*'.$kode.'*", KELUHAN!$Q$4:$Q$'.$maxLookup.', "Success") + COUNTIFS(KELUHAN!$M$4:$M$'.$maxLookup.', "*'.$kode.'*", KELUHAN!$Q$4:$Q$'.$maxLookup.', "Done")');
            $sheet3->setCellValue("F{$rowCabang}", '=SUMIFS(KELUHAN!$O$4:$O$'.$maxLookup.', KELUHAN!$M$4:$M$'.$maxLookup.', "*'.$kode.'*")');

            $sheet3->getStyle("A{$rowCabang}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet3->getStyle("C{$rowCabang}:E{$rowCabang}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet3->getStyle("F{$rowCabang}")->getNumberFormat()->setFormatCode('"Rp " #,##0');
            $sheet3->getStyle("A{$rowCabang}:F{$rowCabang}")->getFont()->setName('Segoe UI')->setSize(9.5);

            $rowCabang++;
        }

        $sheet3->setCellValue("A{$rowCabang}", 'TOTAL');
        $sheet3->setCellValue("B{$rowCabang}", 'Seluruh Cabang');
        $sheet3->setCellValue("C{$rowCabang}", '=SUM(C15:C'.($rowCabang - 1).')');
        $sheet3->setCellValue("D{$rowCabang}", '=SUM(D15:D'.($rowCabang - 1).')');
        $sheet3->setCellValue("E{$rowCabang}", '=SUM(E15:E'.($rowCabang - 1).')');
        $sheet3->setCellValue("F{$rowCabang}", '=SUM(F15:F'.($rowCabang - 1).')');

        $sheet3->getStyle("A{$rowCabang}:F{$rowCabang}")->applyFromArray($totalStyle);
        $sheet3->getStyle("C{$rowCabang}:E{$rowCabang}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet3->getStyle("F{$rowCabang}")->getNumberFormat()->setFormatCode('"Rp " #,##0');
        $sheet3->getStyle("A14:F{$rowCabang}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

        $sheet3->getColumnDimension('A')->setWidth(10);
        $sheet3->getColumnDimension('B')->setWidth(30);
        $sheet3->getColumnDimension('C')->setWidth(16);
        $sheet3->getColumnDimension('D')->setWidth(16);
        $sheet3->getColumnDimension('E')->setWidth(24);
        $sheet3->getColumnDimension('F')->setWidth(24);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Membangun Master Template Multi-Sheet default jika berkas belum ada
     */
    private function generateDefaultMasterTemplate($targetFile)
    {
        $dir = dirname($targetFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $spreadsheet = new Spreadsheet;

        // 1. SHEET 1: DATA_KELUHAN
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('DATA_KELUHAN');

        $sheet1->setCellValue('A1', 'PT BANK SULTENG - DATA MASTER JURNAL KELUHAN NASABAH');
        $sheet1->setCellValue('A2', 'Data Transaksi Keluhan Terintegrasi Sistem & Rekapitulasi Otomatis');
        $sheet1->mergeCells('A1:R1');
        $sheet1->mergeCells('A2:R2');

        $sheet1->getStyle('A1')->getFont()->setName('Segoe UI')->setSize(14)->setBold(true)->getColor()->setRGB('0B2F64');
        $sheet1->getStyle('A2')->getFont()->setName('Segoe UI')->setSize(10)->setItalic(true)->getColor()->setRGB('64748B');

        $headers = [
            'A4' => 'No', 'B4' => 'Tgl Transaksi', 'C4' => 'Tgl Terima', 'D4' => 'Tgl Selesai',
            'E4' => 'Nama Nasabah', 'F4' => 'No. Rekening', 'G4' => 'No. Resi / Trace',
            'H4' => 'No. Kartu ATM', 'I4' => 'No. Tiket CS', 'J4' => 'Kode Cabang',
            'K4' => 'Nama Cabang', 'L4' => 'Jenis Transaksi', 'M4' => 'Channel',
            'N4' => 'Biaya Admin (Rp)', 'O4' => 'Nominal Transaksi (Rp)', 'P4' => 'Terminal / Mesin',
            'Q4' => 'Status', 'R4' => 'Keterangan Log',
        ];

        foreach ($headers as $cell => $val) {
            $sheet1->setCellValue($cell, $val);
        }

        $headerStyle = [
            'font' => ['name' => 'Segoe UI', 'bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0B2F64']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
        $sheet1->getStyle('A4:R4')->applyFromArray($headerStyle);
        $sheet1->getRowDimension(4)->setRowHeight(28);

        $columnWidths = [
            'A' => 6, 'B' => 14, 'C' => 14, 'D' => 14, 'E' => 24, 'F' => 18,
            'G' => 18, 'H' => 20, 'I' => 16, 'J' => 14, 'K' => 22, 'L' => 22,
            'M' => 14, 'N' => 16, 'O' => 20, 'P' => 18, 'Q' => 14, 'R' => 35,
        ];
        foreach ($columnWidths as $col => $w) {
            $sheet1->getColumnDimension($col)->setWidth($w);
        }
        $sheet1->freezePane('A5');

        // 2. SHEET 2: FORMAT_SLIP_JURNAL
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('FORMAT_SLIP_JURNAL');

        $sheet2->setCellValue('B2', 'PT BANK SULTENG');
        $sheet2->setCellValue('B3', 'LEMBAR SLIP JURNAL PENANGANAN KELUHAN NASABAH');
        $sheet2->setCellValue('B4', 'Pilih No. Urut Transaksi (Diinput pada Sel C6 di bawah):');
        $sheet2->mergeCells('B2:G2');
        $sheet2->mergeCells('B3:G3');
        $sheet2->mergeCells('B4:G4');

        $sheet2->getStyle('B2')->getFont()->setName('Segoe UI')->setSize(16)->setBold(true)->getColor()->setRGB('0B2F64');
        $sheet2->getStyle('B3')->getFont()->setName('Segoe UI')->setSize(11)->setBold(true)->getColor()->setRGB('0066CC');
        $sheet2->getStyle('B4')->getFont()->setName('Segoe UI')->setSize(9.5)->setItalic(true)->getColor()->setRGB('64748B');

        $sheet2->setCellValue('B6', 'PILIH NO. URUT:');
        $sheet2->setCellValue('C6', 1);
        $sheet2->setCellValue('D6', '<-- (Ganti nomor urut ini untuk memilih nasabah)');
        $sheet2->getStyle('B6')->getFont()->setName('Segoe UI')->setBold(true)->setSize(11)->getColor()->setRGB('0B2F64');
        $sheet2->getStyle('C6')->getFont()->setName('Segoe UI')->setBold(true)->setSize(13)->getColor()->setRGB('B45309');
        $sheet2->getStyle('C6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('C6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
        $sheet2->getStyle('C6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('F59E0B');
        $sheet2->getStyle('D6')->getFont()->setName('Segoe UI')->setItalic(true)->setSize(9)->getColor()->setRGB('94A3B8');

        $sheet2->setCellValue('B8', 'I. INFORMASI NASABAH & IDENTITAS');
        $sheet2->mergeCells('B8:G8');
        $sheet2->getStyle('B8')->getFont()->setName('Segoe UI')->setBold(true)->setSize(10.5)->getColor()->setRGB('FFFFFF');
        $sheet2->getStyle('B8:G8')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B2F64');

        $slipFields1 = [
            9 => ['Nama Nasabah',        '=IFERROR(INDEX(DATA_KELUHAN!$E$5:$E$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
            10 => ['Nomor Rekening',      '=IFERROR(INDEX(DATA_KELUHAN!$F$5:$F$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
            11 => ['Nomor Resi / Trace',  '=IFERROR(INDEX(DATA_KELUHAN!$G$5:$G$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
            12 => ['Nomor Kartu ATM',     '=IFERROR(INDEX(DATA_KELUHAN!$H$5:$H$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
            13 => ['Nomor Tiket CS',      '=IFERROR(INDEX(DATA_KELUHAN!$I$5:$I$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
            14 => ['Kantor Cabang',       '=IFERROR(INDEX(DATA_KELUHAN!$J$5:$J$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)) & " - " & INDEX(DATA_KELUHAN!$K$5:$K$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
        ];

        foreach ($slipFields1 as $row => [$label, $formula]) {
            $sheet2->setCellValue("B{$row}", $label);
            $sheet2->setCellValue("D{$row}", $formula);
            $sheet2->mergeCells("B{$row}:C{$row}");
            $sheet2->mergeCells("D{$row}:G{$row}");
            $sheet2->getStyle("B{$row}")->getFont()->setName('Segoe UI')->setBold(true)->setSize(10)->getColor()->setRGB('334155');
            $sheet2->getStyle("D{$row}")->getFont()->setName('Segoe UI')->setSize(10)->getColor()->setRGB('0F172A');
        }

        $sheet2->setCellValue('B16', 'II. RINCIAN TRANSAKSI & FINANSIAL');
        $sheet2->mergeCells('B16:G16');
        $sheet2->getStyle('B16')->getFont()->setName('Segoe UI')->setBold(true)->setSize(10.5)->getColor()->setRGB('FFFFFF');
        $sheet2->getStyle('B16:G16')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B2F64');

        $slipFields2 = [
            17 => ['Jenis Transaksi',     '=IFERROR(INDEX(DATA_KELUHAN!$L$5:$L$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
            18 => ['Channel Pembayaran',  '=IFERROR(INDEX(DATA_KELUHAN!$M$5:$M$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
            19 => ['Terminal / ATM',      '=IFERROR(INDEX(DATA_KELUHAN!$P$5:$P$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
            20 => ['Nominal Transaksi',   '=IFERROR(INDEX(DATA_KELUHAN!$O$5:$O$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), 0)'],
            21 => ['Biaya Admin',         '=IFERROR(INDEX(DATA_KELUHAN!$N$5:$N$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), 0)'],
            22 => ['Status Penyelesaian', '=IFERROR(INDEX(DATA_KELUHAN!$Q$5:$Q$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
        ];

        foreach ($slipFields2 as $row => [$label, $formula]) {
            $sheet2->setCellValue("B{$row}", $label);
            $sheet2->setCellValue("D{$row}", $formula);
            $sheet2->mergeCells("B{$row}:C{$row}");
            $sheet2->mergeCells("D{$row}:G{$row}");
            $sheet2->getStyle("B{$row}")->getFont()->setName('Segoe UI')->setBold(true)->setSize(10)->getColor()->setRGB('334155');
            $sheet2->getStyle("D{$row}")->getFont()->setName('Segoe UI')->setSize(10)->getColor()->setRGB('0F172A');
        }
        $sheet2->getStyle('D20:D21')->getNumberFormat()->setFormatCode('"Rp " #,##0');
        $sheet2->getStyle('D20')->getFont()->setBold(true)->getColor()->setRGB('047857');

        $sheet2->setCellValue('B24', 'III. HISTORI TANGGAL & KETERANGAN');
        $sheet2->mergeCells('B24:G24');
        $sheet2->getStyle('B24')->getFont()->setName('Segoe UI')->setBold(true)->setSize(10.5)->getColor()->setRGB('FFFFFF');
        $sheet2->getStyle('B24:G24')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B2F64');

        $slipFields3 = [
            25 => ['Tgl Transaksi',  '=IFERROR(INDEX(DATA_KELUHAN!$B$5:$B$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
            26 => ['Tgl Terima CS',  '=IFERROR(INDEX(DATA_KELUHAN!$C$5:$C$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
            27 => ['Tgl Selesai',    '=IFERROR(INDEX(DATA_KELUHAN!$D$5:$D$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
            28 => ['Keterangan Log', '=IFERROR(INDEX(DATA_KELUHAN!$R$5:$R$1000, MATCH($C$6, DATA_KELUHAN!$A$5:$A$1000, 0)), "-")'],
        ];

        foreach ($slipFields3 as $row => [$label, $formula]) {
            $sheet2->setCellValue("B{$row}", $label);
            $sheet2->setCellValue("D{$row}", $formula);
            $sheet2->mergeCells("B{$row}:C{$row}");
            $sheet2->mergeCells("D{$row}:G{$row}");
            $sheet2->getStyle("B{$row}")->getFont()->setName('Segoe UI')->setBold(true)->setSize(10)->getColor()->setRGB('334155');
            $sheet2->getStyle("D{$row}")->getFont()->setName('Segoe UI')->setSize(10)->getColor()->setRGB('0F172A');
        }
        $sheet2->getRowDimension(28)->setRowHeight(35);
        $sheet2->getStyle('D28')->getAlignment()->setWrapText(true);

        $boxBorder = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
        $sheet2->getStyle('B8:G14')->applyFromArray($boxBorder);
        $sheet2->getStyle('B16:G22')->applyFromArray($boxBorder);
        $sheet2->getStyle('B24:G28')->applyFromArray($boxBorder);

        $sheet2->setCellValue('B31', 'Dibuat Oleh,');
        $sheet2->setCellValue('B32', 'Petugas CS / Teller');
        $sheet2->setCellValue('B36', '( ........................................ )');
        $sheet2->mergeCells('B31:C31');
        $sheet2->mergeCells('B32:C32');
        $sheet2->mergeCells('B36:C36');
        $sheet2->getStyle('B31:C36')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet2->setCellValue('D31', 'Diverifikasi Oleh,');
        $sheet2->setCellValue('D32', 'Penyelia / Supervisor');
        $sheet2->setCellValue('D36', '( ........................................ )');
        $sheet2->mergeCells('D31:E31');
        $sheet2->mergeCells('D32:E32');
        $sheet2->mergeCells('D36:E36');
        $sheet2->getStyle('D31:E36')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet2->setCellValue('F31', 'Disetujui Oleh,');
        $sheet2->setCellValue('F32', 'Pimpinan Seksi / Cabang');
        $sheet2->setCellValue('F36', '( ........................................ )');
        $sheet2->mergeCells('F31:G31');
        $sheet2->mergeCells('F32:G32');
        $sheet2->mergeCells('F36:G36');
        $sheet2->getStyle('F31:G36')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('B31:G36')->getFont()->setName('Segoe UI')->setSize(9.5)->getColor()->setRGB('334155');

        $sheet2->getColumnDimension('A')->setWidth(4);
        $sheet2->getColumnDimension('B')->setWidth(18);
        $sheet2->getColumnDimension('C')->setWidth(18);
        $sheet2->getColumnDimension('D')->setWidth(18);
        $sheet2->getColumnDimension('E')->setWidth(18);
        $sheet2->getColumnDimension('F')->setWidth(18);
        $sheet2->getColumnDimension('G')->setWidth(18);

        // 3. SHEET 3: REKAP_STATUS_CABANG
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('REKAP_STATUS_CABANG');

        $sheet3->setCellValue('A1', 'PT BANK SULTENG - REKAPITULASI STATUS & AGREGASI KELUHAN');
        $sheet3->setCellValue('A2', 'Ringkasan Eksekutif & Statistik Otomatis Berdasarkan Data Master');
        $sheet3->mergeCells('A1:F1');
        $sheet3->mergeCells('A2:F2');

        $sheet3->getStyle('A1')->getFont()->setName('Segoe UI')->setSize(14)->setBold(true)->getColor()->setRGB('0B2F64');
        $sheet3->getStyle('A2')->getFont()->setName('Segoe UI')->setSize(10)->setItalic(true)->getColor()->setRGB('64748B');

        $sheet3->setCellValue('A4', 'I. REKAPITULASI BERDASARKAN STATUS PENYELESAIAN');
        $sheet3->mergeCells('A4:D4');
        $sheet3->getStyle('A4')->getFont()->setName('Segoe UI')->setBold(true)->setSize(11)->getColor()->setRGB('0B2F64');

        $sheet3->setCellValue('A5', 'Status Keluhan');
        $sheet3->setCellValue('B5', 'Jumlah Transaksi');
        $sheet3->setCellValue('C5', 'Total Nominal (Rp)');
        $sheet3->setCellValue('D5', 'Persentase (%)');
        $sheet3->getStyle('A5:D5')->applyFromArray($headerStyle);

        $statusList = [
            ['Menunggu', 6],
            ['Success',  7],
            ['Done',     8],
            ['Rejected', 9],
        ];

        foreach ($statusList as [$st, $r]) {
            $sheet3->setCellValue("A{$r}", $st);
            $sheet3->setCellValue("B{$r}", "=COUNTIF(DATA_KELUHAN!\$Q\$5:\$Q\$1000, \"{$st}\")");
            $sheet3->setCellValue("C{$r}", "=SUMIFS(DATA_KELUHAN!\$O\$5:\$O\$1000, DATA_KELUHAN!\$Q\$5:\$Q\$1000, \"{$st}\")");
            $sheet3->setCellValue("D{$r}", "=IF(B10>0, B{$r}/B10, 0)");
            $sheet3->getStyle("A{$r}")->getFont()->setName('Segoe UI')->setBold(true);
            $sheet3->getStyle("B{$r}:D{$r}")->getFont()->setName('Segoe UI');
            $sheet3->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet3->getStyle("C{$r}")->getNumberFormat()->setFormatCode('"Rp " #,##0');
            $sheet3->getStyle("D{$r}")->getNumberFormat()->setFormatCode('0.0%');
        }

        $sheet3->setCellValue('A10', 'TOTAL KESELURUHAN');
        $sheet3->setCellValue('B10', '=SUM(B6:B9)');
        $sheet3->setCellValue('C10', '=SUM(C6:C9)');
        $sheet3->setCellValue('D10', '=SUM(D6:D9)');

        $totalStyle = [
            'font' => ['name' => 'Segoe UI', 'bold' => true, 'color' => ['rgb' => '0B2F64']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']]],
        ];
        $sheet3->getStyle('A10:D10')->applyFromArray($totalStyle);
        $sheet3->getStyle('B10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet3->getStyle('C10')->getNumberFormat()->setFormatCode('"Rp " #,##0');
        $sheet3->getStyle('D10')->getNumberFormat()->setFormatCode('0.0%');
        $sheet3->getStyle('A5:D10')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        $sheet3->getStyle('A10:D10')->getBorders()->getTop()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setRGB('0B2F64');

        $sheet3->setCellValue('A13', 'II. REKAPITULASI PER KANTOR CABANG');
        $sheet3->mergeCells('A13:F13');
        $sheet3->getStyle('A13')->getFont()->setName('Segoe UI')->setBold(true)->setSize(11)->getColor()->setRGB('0B2F64');

        $sheet3->setCellValue('A14', 'Kode');
        $sheet3->setCellValue('B14', 'Nama Kantor Cabang');
        $sheet3->setCellValue('C14', 'Total Transaksi');
        $sheet3->setCellValue('D14', 'Menunggu');
        $sheet3->setCellValue('E14', 'Selesai (Success/Done)');
        $sheet3->setCellValue('F14', 'Total Nominal (Rp)');
        $sheet3->getStyle('A14:F14')->applyFromArray($headerStyle);

        $cabangData = [
            ['001', 'Kantor Cabang Utama Palu'],
            ['002', 'Kantor Cabang Parigi'],
            ['003', 'Kantor Cabang Poso'],
            ['004', 'Kantor Cabang Tolitoli'],
            ['005', 'Kantor Cabang Luwuk'],
            ['006', 'Kantor Cabang Buol'],
            ['007', 'Kantor Cabang Donggala'],
            ['008', 'Kantor Cabang Banggai'],
            ['009', 'Kantor Cabang Tinombo'],
            ['010', 'Kantor Cabang Morowali'],
            ['011', 'Kantor Cabang Ampana'],
            ['012', 'Kantor Cabang Sigi'],
            ['013', 'Kantor Cabang Kolonodale'],
            ['014', 'Kantor Cabang Salakan'],
            ['015', 'Kantor Cabang Jakarta'],
        ];

        $rowCabang = 15;
        foreach ($cabangData as [$kode, $nama]) {
            $sheet3->setCellValue("A{$rowCabang}", $kode);
            $sheet3->setCellValue("B{$rowCabang}", $nama);
            $sheet3->setCellValue("C{$rowCabang}", "=COUNTIF(DATA_KELUHAN!\$J\$5:\$J\$1000, A{$rowCabang})");
            $sheet3->setCellValue("D{$rowCabang}", "=COUNTIFS(DATA_KELUHAN!\$J\$5:\$J\$1000, A{$rowCabang}, DATA_KELUHAN!\$Q\$5:\$Q\$1000, \"Menunggu\")");
            $sheet3->setCellValue("E{$rowCabang}", "=COUNTIFS(DATA_KELUHAN!\$J\$5:\$J\$1000, A{$rowCabang}, DATA_KELUHAN!\$Q\$5:\$Q\$1000, \"Success\") + COUNTIFS(DATA_KELUHAN!\$J\$5:\$J\$1000, A{$rowCabang}, DATA_KELUHAN!\$Q\$5:\$Q\$1000, \"Done\")");
            $sheet3->setCellValue("F{$rowCabang}", "=SUMIFS(DATA_KELUHAN!\$O\$5:\$O\$1000, DATA_KELUHAN!\$J\$5:\$J\$1000, A{$rowCabang})");

            $sheet3->getStyle("A{$rowCabang}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet3->getStyle("C{$rowCabang}:E{$rowCabang}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet3->getStyle("F{$rowCabang}")->getNumberFormat()->setFormatCode('"Rp " #,##0');
            $sheet3->getStyle("A{$rowCabang}:F{$rowCabang}")->getFont()->setName('Segoe UI')->setSize(9.5);

            $rowCabang++;
        }

        $sheet3->setCellValue("A{$rowCabang}", 'TOTAL');
        $sheet3->setCellValue("B{$rowCabang}", 'Seluruh Cabang');
        $sheet3->setCellValue("C{$rowCabang}", '=SUM(C15:C'.($rowCabang - 1).')');
        $sheet3->setCellValue("D{$rowCabang}", '=SUM(D15:D'.($rowCabang - 1).')');
        $sheet3->setCellValue("E{$rowCabang}", '=SUM(E15:E'.($rowCabang - 1).')');
        $sheet3->setCellValue("F{$rowCabang}", '=SUM(F15:F'.($rowCabang - 1).')');

        $sheet3->getStyle("A{$rowCabang}:F{$rowCabang}")->applyFromArray($totalStyle);
        $sheet3->getStyle("C{$rowCabang}:E{$rowCabang}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet3->getStyle("F{$rowCabang}")->getNumberFormat()->setFormatCode('"Rp " #,##0');
        $sheet3->getStyle("A14:F{$rowCabang}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

        $sheet3->getColumnDimension('A')->setWidth(10);
        $sheet3->getColumnDimension('B')->setWidth(30);
        $sheet3->getColumnDimension('C')->setWidth(16);
        $sheet3->getColumnDimension('D')->setWidth(16);
        $sheet3->getColumnDimension('E')->setWidth(24);
        $sheet3->getColumnDimension('F')->setWidth(24);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $writer->save($targetFile);
    }

    /**
     * Mengimpor ribuan data transaksi dari file Excel Master ke dalam database
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv,txt|max:51200',
        ], [
            'file_excel.required' => 'Silakan pilih file Excel Master (.xlsx / .xls) yang ingin diimpor.',
            'file_excel.mimes' => 'Format berkas harus berupa Excel (.xlsx, .xls) atau CSV.',
            'file_excel.max' => 'Ukuran berkas maksimal adalah 50MB.',
        ]);

        $file = $request->file('file_excel');
        $uploadedPath = $file->getRealPath();
        $originalName = $file->getClientOriginalName();

        // 1. Simpan sebagai Master Template Aktif jika opsi dipilih
        if ($request->boolean('set_as_template', true)) {
            $templateTarget = storage_path('app/templates/Master_Jurnal_BankSulteng_Template.xlsx');
            $dir = dirname($templateTarget);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            copy($uploadedPath, $templateTarget);
        }

        try {
            $spreadsheet = IOFactory::load($uploadedPath);
        } catch (\Exception $e) {
            return back()->withErrors(['file_excel' => 'Gagal membaca berkas Excel: '.$e->getMessage()]);
        }

        // 2. Tentukan Sheet KELUHAN / Sheet Data Transaksi Utama yang Tepat
        $sheet = null;
        $allSheets = $spreadsheet->getAllSheets();

        // Prioritas 1: Cari Sheet yang memuat kata 'keluhan' di judulnya (misal: "KELUHAN", "DATA KELUHAN", "JURNAL KELUHAN")
        foreach ($allSheets as $sh) {
            $title = strtolower(trim($sh->getTitle()));
            if (str_contains($title, 'keluhan')) {
                $sheet = $sh;
                break;
            }
        }

        // Prioritas 2: Cari Sheet dengan nama data / transaksi / master
        if (! $sheet) {
            $candidateNames = ['data', 'transaksi', 'jurnal', 'master', 'trx'];
            foreach ($allSheets as $sh) {
                $title = strtolower(trim($sh->getTitle()));
                foreach ($candidateNames as $cand) {
                    if (str_contains($title, $cand)) {
                        $sheet = $sh;
                        break 2;
                    }
                }
            }
        }

        // Prioritas 3: Cari Sheet dengan baris terbanyak (yang memuat ribuan data transaksi)
        if (! $sheet) {
            $maxRows = 0;
            $bestSheet = null;
            foreach ($allSheets as $sh) {
                $hRow = $sh->getHighestRow();
                if ($hRow > $maxRows) {
                    $maxRows = $hRow;
                    $bestSheet = $sh;
                }
            }
            $sheet = $bestSheet ?? $spreadsheet->getSheet(0);
        }

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        // 3. Deteksi baris header secara pintar pada Sheet Keluhan tersebut (mencari dalam 15 baris pertama)
        $headerRow = null;
        $columnMap = [];

        for ($row = 1; $row <= min(15, $highestRow); $row++) {
            $rowValues = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $val = trim((string) $sheet->getCell([$col, $row])->getValue());
                $rowValues[$col] = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $val));
            }

            $matchedKeywords = 0;
            foreach ($rowValues as $normVal) {
                if (str_contains($normVal, 'nama') || str_contains($normVal, 'nasabah') ||
                    str_contains($normVal, 'resi') || str_contains($normVal, 'rekening') ||
                    str_contains($normVal, 'nominal') || str_contains($normVal, 'transaksi') ||
                    str_contains($normVal, 'cabang') || str_contains($normVal, 'status')) {
                    $matchedKeywords++;
                }
            }

            if ($matchedKeywords >= 2) {
                $headerRow = $row;
                foreach ($rowValues as $colIdx => $normVal) {
                    if (! $normVal) {
                        continue;
                    }
                    if (empty($columnMap['no_urut']) && ($normVal === 'no' || $normVal === 'nomor' || $normVal === 'nourut' || $normVal === 'number' || $normVal === 'num' || $normVal === 'no.')) {
                        $columnMap['no_urut'] = $colIdx;
                    } elseif (empty($columnMap['nama_nasabah']) && (str_contains($normVal, 'namanasabah') || $normVal === 'nama' || str_contains($normVal, 'nasabah') || str_contains($normVal, 'customer') || str_contains($normVal, 'pelapor') || str_contains($normVal, 'namapelanggan'))) {
                        $columnMap['nama_nasabah'] = $colIdx;
                    } elseif (empty($columnMap['status']) && (str_contains($normVal, 'statustransaksi') || str_contains($normVal, 'statuskeluhan') || str_contains($normVal, 'statuslaporan') || str_contains($normVal, 'status') || str_contains($normVal, 'kondisi') || str_contains($normVal, 'keteranganstatus') || str_contains($normVal, 'progres') || str_contains($normVal, 'progress') || str_contains($normVal, 'hasil') || str_contains($normVal, 'stat') || str_contains($normVal, 'statusakhir') || str_contains($normVal, 'penyelesaian'))) {
                        $columnMap['status'] = $colIdx;
                    } elseif (empty($columnMap['kode_cabang']) && (str_contains($normVal, 'kodecabang') || str_contains($normVal, 'kdcabang') || str_contains($normVal, 'kodekantor') || str_contains($normVal, 'branchcode') || str_contains($normVal, 'kdcbg') || str_contains($normVal, 'kodeunit'))) {
                        $columnMap['kode_cabang'] = $colIdx;
                    } elseif (empty($columnMap['nama_cabang']) && (str_contains($normVal, 'cabangtransaksi') || str_contains($normVal, 'namacabang') || str_contains($normVal, 'kantorcabang') || str_contains($normVal, 'kantor') || str_contains($normVal, 'cabang') || str_contains($normVal, 'unitkerja') || str_contains($normVal, 'branchname') || str_contains($normVal, 'branch') || str_contains($normVal, 'lokasicabang') || str_contains($normVal, 'cbg') || str_contains($normVal, 'unit'))) {
                        $columnMap['nama_cabang'] = $colIdx;
                    } elseif (empty($columnMap['terminal_transaksi']) && (str_contains($normVal, 'terminaltransaksi') || str_contains($normVal, 'terminal') || str_contains($normVal, 'mesin') || str_contains($normVal, 'lokasimesin') || str_contains($normVal, 'tid') || str_contains($normVal, 'mid') || str_contains($normVal, 'atm') || str_contains($normVal, 'edc'))) {
                        $columnMap['terminal_transaksi'] = $colIdx;
                    } elseif (empty($columnMap['nominal_transaksi']) && (str_contains($normVal, 'nominaltransaksi') || str_contains($normVal, 'nominal') || str_contains($normVal, 'jumlah') || str_contains($normVal, 'amount') || str_contains($normVal, 'nilai') || str_contains($normVal, 'totalnominal') || str_contains($normVal, 'total'))) {
                        $columnMap['nominal_transaksi'] = $colIdx;
                    } elseif (empty($columnMap['biaya_admin']) && (str_contains($normVal, 'biayaadmin') || $normVal === 'admin' || str_contains($normVal, 'biayaadm') || str_contains($normVal, 'biayadmin') || str_contains($normVal, 'fee') || (str_contains($normVal, 'biaya') && ! str_contains($normVal, 'nominal')) || str_contains($normVal, 'adm'))) {
                        $columnMap['biaya_admin'] = $colIdx;
                    } elseif (empty($columnMap['channel']) && (str_contains($normVal, 'channel') || str_contains($normVal, 'chanel') || str_contains($normVal, 'kanal') || str_contains($normVal, 'media') || str_contains($normVal, 'via') || str_contains($normVal, 'jalur') || str_contains($normVal, 'tipechannel'))) {
                        $columnMap['channel'] = $colIdx;
                    } elseif (empty($columnMap['tgl_terima']) && (str_contains($normVal, 'tglterima') || str_contains($normVal, 'tanggalterima') || str_contains($normVal, 'tglmasuk') || str_contains($normVal, 'tanggalmasuk') || str_contains($normVal, 'tglpengaduan') || str_contains($normVal, 'penerimaan') || str_contains($normVal, 'tglterimalaporan'))) {
                        $columnMap['tgl_terima'] = $colIdx;
                    } elseif (empty($columnMap['tgl_selesai']) && (str_contains($normVal, 'tglselesai') || str_contains($normVal, 'tanggalselesai') || str_contains($normVal, 'tglclose') || str_contains($normVal, 'tanggalclose') || str_contains($normVal, 'tglpenyelesaian') || str_contains($normVal, 'tglproses'))) {
                        $columnMap['tgl_selesai'] = $colIdx;
                    } elseif (empty($columnMap['tgl_transaksi']) && (str_contains($normVal, 'tgltransaksi') || str_contains($normVal, 'tanggaltransaksi') || str_contains($normVal, 'trxdate') || str_contains($normVal, 'tgltrx') || str_contains($normVal, 'tglkejadian') || str_contains($normVal, 'tanggalkejadian') || str_contains($normVal, 'tgllapor') || str_contains($normVal, 'tanggallapor') || str_contains($normVal, 'tanggal') || str_contains($normVal, 'tgl') || str_contains($normVal, 'date'))) {
                        $columnMap['tgl_transaksi'] = $colIdx;
                    } elseif (empty($columnMap['no_rekening']) && (str_contains($normVal, 'norekening') || str_contains($normVal, 'nomorrekening') || str_contains($normVal, 'rekening') || str_contains($normVal, 'norek') || str_contains($normVal, 'accountnumber') || str_contains($normVal, 'account') || str_contains($normVal, 'acct'))) {
                        $columnMap['no_rekening'] = $colIdx;
                    } elseif (empty($columnMap['no_resi']) && (str_contains($normVal, 'noresi') || str_contains($normVal, 'nomorresi') || str_contains($normVal, 'resi') || str_contains($normVal, 'notrace') || str_contains($normVal, 'trace') || str_contains($normVal, 'rrn') || str_contains($normVal, 'ref') || str_contains($normVal, 'struk'))) {
                        $columnMap['no_resi'] = $colIdx;
                    } elseif (empty($columnMap['no_kartu']) && (str_contains($normVal, 'nokartu') || str_contains($normVal, 'nomorkartu') || str_contains($normVal, 'kartu') || str_contains($normVal, 'pan') || str_contains($normVal, 'atmdebit') || str_contains($normVal, 'card'))) {
                        $columnMap['no_kartu'] = $colIdx;
                    } elseif (empty($columnMap['no_tiket']) && (str_contains($normVal, 'notiket') || str_contains($normVal, 'nomortiket') || str_contains($normVal, 'tiket') || str_contains($normVal, 'ticket'))) {
                        $columnMap['no_tiket'] = $colIdx;
                    } elseif (empty($columnMap['jenis_transaksi']) && (str_contains($normVal, 'jenistransaksi') || str_contains($normVal, 'transaksi') || str_contains($normVal, 'kategori') || str_contains($normVal, 'keperluan'))) {
                        $columnMap['jenis_transaksi'] = $colIdx;
                    } elseif (empty($columnMap['permasalahan']) && (str_contains($normVal, 'keluhan') || str_contains($normVal, 'permasalahan') || str_contains($normVal, 'keterangankeluhan') || str_contains($normVal, 'keterangan') || str_contains($normVal, 'uraian') || str_contains($normVal, 'kasus') || str_contains($normVal, 'masalah'))) {
                        $columnMap['permasalahan'] = $colIdx;
                    } elseif (empty($columnMap['keterangan_log']) && (str_contains($normVal, 'log') || str_contains($normVal, 'catatan') || str_contains($normVal, 'remark') || str_contains($normVal, 'notes'))) {
                        $columnMap['keterangan_log'] = $colIdx;
                    }
                }
                break;
            }
        }

        // Fallback default index jika deteksi otomatis belum menemukan header
        if (! $headerRow) {
            $headerRow = 4;
            $columnMap = [
                'no_urut' => 1,
                'nama_nasabah' => 5,
                'tgl_transaksi' => 2,
                'tgl_terima' => 3,
                'tgl_selesai' => 4,
                'no_rekening' => 6,
                'no_resi' => 7,
                'no_kartu' => 8,
                'no_tiket' => 9,
                'kode_cabang' => 10,
                'nama_cabang' => 11,
                'jenis_transaksi' => 12,
                'channel' => 13,
                'biaya_admin' => 14,
                'nominal_transaksi' => 15,
                'terminal_transaksi' => 16,
                'permasalahan' => 17,
                'status' => 18,
                'keterangan_log' => 19,
            ];
        }

        // 4. Siapkan Cache Master Data di Memori (High-Speed Lookup)
        $allCabangs = MasterCabang::all();
        $cabangByKode = [];
        $cabangByNama = [];
        foreach ($allCabangs as $c) {
            $cabangByKode[trim(strtoupper($c->kode_cabang))] = $c->id;
            $cabangByNama[strtolower(trim($c->nama_cabang))] = $c->id;
        }

        $allTransaksis = MasterTransaksi::all();
        $transaksiByKey = [];
        foreach ($allTransaksis as $t) {
            $key = strtolower(trim($t->jenis_transaksi)).'___'.strtolower(trim($t->channel));
            $transaksiByKey[$key] = $t->id;
        }

        $defaultCabangId = $allCabangs->first()?->id ?? 1;
        $defaultTransaksiId = $allTransaksis->first()?->id ?? 1;

        $totalRead = 0;
        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $berulangCount = 0;

        // 5. Cari baris mulai data di mana nomor urut dimulai dari angka "1" (bukan satu Romawi)
        $startRow = $headerRow + 1;
        $noCol = $columnMap['no_urut'] ?? 1;
        for ($r = $headerRow + 1; $r <= min($headerRow + 15, $highestRow); $r++) {
            $rawNo = trim((string) $sheet->getCell([$noCol, $r])->getValue());
            if ($rawNo === '1' || $rawNo === '1.0' || $rawNo === '01' || $rawNo === '1.') {
                $startRow = $r;
                break;
            }
        }

        // 6. Proses baris data transaksi dari baris angka 1 sampai baris terakhir
        for ($row = $startRow; $row <= $highestRow; $row++) {
            $noUrut = $this->getCellValue($sheet, $columnMap, 'no_urut', $row);
            $namaNasabah = $this->getCellValue($sheet, $columnMap, 'nama_nasabah', $row);
            $noResi = $this->getCellValue($sheet, $columnMap, 'no_resi', $row);
            $noRekening = $this->getCellValue($sheet, $columnMap, 'no_rekening', $row);

            // Lewati baris kosong
            if (empty($namaNasabah) && empty($noResi) && empty($noRekening)) {
                continue;
            }

            // Lewati baris Romawi (I, II, III, dst) atau baris sub-header penomoran
            if ($this->isRomanOrHeaderRow($noUrut, $namaNasabah, $noResi)) {
                continue;
            }

            $totalRead++;

            // Jika nama nasabah kosong tapi ada resi
            if (empty($namaNasabah)) {
                $namaNasabah = 'Nasabah '.($noResi ?: 'Tanpa Nama');
            }

            // No Resi fallback
            if (empty($noResi)) {
                $noResi = 'TRX-'.date('Ymd').'-'.$row;
            }

            // Parse Tanggal dengan Resolusi Cerdas & Preservasi Tanggal Excel
            $parsedTrx = $this->parseExcelDate($sheet, $columnMap, 'tgl_transaksi', $row);
            $parsedTerima = $this->parseExcelDate($sheet, $columnMap, 'tgl_terima', $row);
            $parsedSelesai = $this->parseExcelDate($sheet, $columnMap, 'tgl_selesai', $row);

            $tglTransaksi = $parsedTrx ?? $parsedTerima ?? now()->format('Y-m-d');
            $tglTerima = $parsedTerima ?? $tglTransaksi;
            $tglSelesai = $parsedSelesai; // Tetap null jika belum ada tanggal selesai di Excel

            // Parse Nominal
            $rawNominal = $this->getCellValue($sheet, $columnMap, 'nominal_transaksi', $row);
            $nominal = $this->parseNominal($rawNominal);

            // Parse Biaya Admin dari Excel
            $rawBiayaAdmin = $this->getCellValue($sheet, $columnMap, 'biaya_admin', $row);
            $biayaAdmin = $rawBiayaAdmin !== null ? $this->parseNominal($rawBiayaAdmin) : 0;

            // Parse Cabang Pintar
            $kodeCabang = trim((string) $this->getCellValue($sheet, $columnMap, 'kode_cabang', $row));
            $namaCabang = trim((string) $this->getCellValue($sheet, $columnMap, 'nama_cabang', $row));
            $cabangId = $this->resolveCabangId($kodeCabang, $namaCabang, $cabangByKode, $cabangByNama, $defaultCabangId);

            // Parse Transaksi & Channel Presisi
            $jenisTransaksi = trim((string) $this->getCellValue($sheet, $columnMap, 'jenis_transaksi', $row));
            $channel = trim((string) $this->getCellValue($sheet, $columnMap, 'channel', $row));
            $transaksiId = $this->resolveTransaksiId($jenisTransaksi, $channel, $transaksiByKey, $defaultTransaksiId);

            // Parse Status Fleksibel (Kosong -> '-')
            $rawStatus = trim((string) $this->getCellValue($sheet, $columnMap, 'status', $row));
            $status = $this->parseStatus($rawStatus);

            $noKartu = $this->getCellValue($sheet, $columnMap, 'no_kartu', $row) ?: '-';
            // Berkas Excel lama bisa memuat nomor berspasi ("BS - 2026…"), jadi ikut dirapikan.
            $noTiket = NomorTiketService::rapikan($this->getCellValue($sheet, $columnMap, 'no_tiket', $row)) ?: '-';
            $terminal = $this->getCellValue($sheet, $columnMap, 'terminal_transaksi', $row) ?: '-';
            $permasalahan = $this->getCellValue($sheet, $columnMap, 'permasalahan', $row) ?: '-';
            $keteranganLog = $this->getCellValue($sheet, $columnMap, 'keterangan_log', $row) ?: '-';

            // Simpan / Perbarui data ke Database dengan Anti-Duplikat
            $existing = Jurnal::where('nama_nasabah', $namaNasabah)
                ->where('no_resi', $noResi)
                ->where('tgl_transaksi', $tglTransaksi)
                ->first();

            $dataPayload = [
                'nama_nasabah' => $namaNasabah,
                'no_resi' => $noResi,
                'no_rekening' => $noRekening ?: '-',
                'no_kartu' => $noKartu,
                'no_tiket' => $noTiket,
                'tgl_transaksi' => $tglTransaksi,
                'tgl_terima' => $tglTerima,
                'tgl_selesai' => $tglSelesai,
                'master_cabang_id' => $cabangId,
                'master_transaksi_id' => $transaksiId,
                'terminal_transaksi' => $terminal,
                'nominal_transaksi' => $nominal,
                'biaya_admin' => $biayaAdmin,
                'status' => $status,
                'permasalahan' => $permasalahan,
                'keterangan_log' => $keteranganLog,
            ];

            if ($existing) {
                $existing->update($dataPayload);
                $updatedCount++;
            } else {
                // Baris baru, tetapi nama nasabah + no resinya sudah pernah ada dengan
                // tanggal transaksi berbeda. Tetap disimpan (bisa jadi klaim yang sah),
                // hanya dilaporkan supaya petugas dapat menelitinya kembali.
                if (Jurnal::where('nama_nasabah', $namaNasabah)->where('no_resi', $noResi)->exists()) {
                    $berulangCount++;
                }

                Jurnal::create($dataPayload);
                $insertedCount++;
            }
        }

        $message = "Proses import selesai! Total: {$totalRead} baris data diproses ({$insertedCount} data baru berhasil disimpan, {$updatedCount} data diperbarui).";

        if ($berulangCount > 0) {
            $message .= " Perhatian: {$berulangCount} data baru memiliki Nama Nasabah & No. Resi yang sama dengan keluhan lama (tanggal transaksi berbeda) — periksa lewat filter \"Cek data berulang\".";
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'total' => $totalRead,
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'skipped' => $skippedCount,
                    'berulang' => $berulangCount,
                ],
            ]);
        }

        return redirect()->route('jurnal.index')->with('success', $message);
    }

    /**
     * Mengosongkan / Reset Seluruh Data Jurnal & Menghapus Template Master
     */
    public function resetAllData(Request $request)
    {
        // Konfirmasi ulang kata sandi. Tombolnya berada di halaman yang sudah
        // terbuka sepanjang hari kerja; tanpa ini, satu klik pada layar yang
        // ditinggalkan tanpa terkunci sudah cukup menghapus seluruh jurnal.
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Masukkan kata sandi Anda untuk menghapus seluruh data jurnal.',
            'password.current_password' => 'Kata sandi yang Anda masukkan tidak sesuai. Penghapusan dibatalkan.',
        ]);

        $deletedCount = Jurnal::count();

        // Cadangan dibuat lebih dulu. Penghapusan massal ini tidak dapat
        // dibatalkan, jadi berkasnya harus sudah ada sebelum baris pertama hilang.
        $berkasCadangan = $this->simpanCadanganJurnal();

        AuditTrailService::catat('jurnal.reset_massal', [
            'keterangan' => "Menghapus seluruh data jurnal keluhan ({$deletedCount} baris). Cadangan disimpan di: {$berkasCadangan}.",
            'lama' => ['jumlah_baris' => $deletedCount, 'cadangan' => $berkasCadangan],
        ]);

        // 1. Hapus seluruh data transaksi di tabel jurnals
        Jurnal::query()->delete();

        // 1b. Mass delete tidak memicu observer → kembalikan pengaduan CS yang tertaut ke status "Diterima"
        Pengaduan::whereIn('status', [
            PengaduanStatus::Diproses->value,
            PengaduanStatus::Selesai->value,
        ])->update([
            'jurnal_id' => null,
            'status' => PengaduanStatus::Diterima->value,
            'diproses_at' => null,
            'selesai_at' => null,
            'ditolak_at' => null,
        ]);

        // 2. Hapus file master template jika dipilih
        $resetTemplate = $request->boolean('delete_template', true);
        if ($resetTemplate) {
            $templateTarget = storage_path('app/templates/Master_Jurnal_BankSulteng_Template.xlsx');
            if (file_exists($templateTarget)) {
                @unlink($templateTarget);
            }
        }

        // 3. Bersihkan Master Transaksi dari data tanggal / log palsu yang tidak valid
        $allMaster = MasterTransaksi::all();
        foreach ($allMaster as $mt) {
            if ($this->isInvalidJenisTransaksi($mt->jenis_transaksi)) {
                $mt->delete();
            }
        }

        $message = "Berhasil! Seluruh data jurnal keluhan ({$deletedCount} data) telah dibersihkan. Cadangan otomatis tersimpan sebagai {$berkasCadangan}.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
            ]);
        }

        return redirect()->route('jurnal.index')->with('success', $message);
    }

    /**
     * Simpan seluruh jurnal ke berkas Excel di disk privat sebagai cadangan
     * sebelum penghapusan massal. Mengembalikan nama berkasnya.
     */
    private function simpanCadanganJurnal(): string
    {
        $jurnals = Jurnal::with(['masterCabang', 'masterTransaksi'])->orderBy('id')->get();

        $namaBerkas = 'cadangan-jurnal-'.now()->format('Ymd-His').'.xlsx';
        $folder = storage_path('app/private/cadangan');

        if (! is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $spreadsheet = $this->buildCleanExportSpreadsheet($jurnals);
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setPreCalculateFormulas(false);
        $writer->save($folder.DIRECTORY_SEPARATOR.$namaBerkas);
        $spreadsheet->disconnectWorksheets();

        return $namaBerkas;
    }

    /**
     * Helper mengambil nilai cell berdasarkan pemetaan nama kolom
     */
    private function getCellValue($sheet, $columnMap, $field, $row)
    {
        if (empty($columnMap[$field])) {
            return null;
        }
        $colIdx = $columnMap[$field];
        $colLetter = Coordinate::stringFromColumnIndex($colIdx);
        $cell = $sheet->getCell("{$colLetter}{$row}");
        $val = $cell->getValue();
        if ($val === null || $val === '') {
            return null;
        }
        if (str_starts_with((string) $val, '=')) {
            try {
                $val = $cell->getCalculatedValue();
            } catch (\Exception $e) {
                $val = $cell->getFormattedValue();
            }
        }

        return trim((string) $val);
    }

    /**
     * Helper mendeteksi apakah suatu baris adalah baris Romawi (I, II, III) atau baris subheader/penomoran kolom
     */
    private function isRomanOrHeaderRow($noVal, $namaVal, $resiVal)
    {
        $n = strtoupper(trim((string) $noVal));
        $nama = strtoupper(trim((string) $namaVal));
        $resi = strtoupper(trim((string) $resiVal));

        // Angka Romawi I s/d L
        $romans = [
            'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X',
            'XI', 'XII', 'XIII', 'XIV', 'XV', 'XVI', 'XVII', 'XVIII', 'XIX', 'XX',
            'XXI', 'XXII', 'XXIII', 'XXIV', 'XXV', 'XXX', 'XL', 'L',
        ];

        $cleanN = preg_replace('/[^A-Z0-9]/', '', $n);
        $cleanNama = preg_replace('/[^A-Z0-9]/', '', $nama);

        // 1. Cek apakah kolom No atau Nama adalah angka Romawi murni (tanpa no resi yang valid)
        if (in_array($cleanN, $romans) && empty($resi)) {
            return true;
        }
        if (in_array($cleanNama, $romans) && empty($resi)) {
            return true;
        }

        // 2. Cek format penomoran kolom (1), (2), (3) atau (I), (II)
        if (preg_match('/^\([0-9]+\)$/', trim((string) $noVal)) || preg_match('/^\([IVXLCDM]+\)$/i', trim((string) $noVal))) {
            return true;
        }

        // 3. Cek judul kategori berhuruf Romawi (misal: "I. TRANSAKSI ATM", "II. TRANSAKSI FINNET")
        if (preg_match('/^[IVXLCDM]+\.?\s+/i', $nama) && empty($resi)) {
            return true;
        }

        // 4. Cek baris header yang berulang
        if ($cleanNama === 'NAMANASABAH' || $cleanNama === 'NAMA' || $cleanNama === 'NASABAH' || $cleanNama === 'NORESEN' || $cleanNama === 'NOREKENING' || $cleanNama === 'TANGGALTRANSAKSI') {
            return true;
        }

        return false;
    }

    /**
     * Helper konversi status teks Excel ke status standar sistem (Success, Done, Rejected, Menunggu, atau '-')
     */
    private function parseStatus($rawStatus)
    {
        $s = strtolower(trim((string) $rawStatus));
        if (empty($s) || $s === '-' || $s === 'null' || $s === 'none' || $s === 'undefined') {
            return '-';
        }

        // 1. Status Selesai / Done / Closed
        if (str_contains($s, 'done') || str_contains($s, 'selesai') || str_contains($s, 'close') || str_contains($s, 'closed') || str_contains($s, 'lunas') || str_contains($s, 'finish')) {
            return 'Done';
        }

        // 2. Status Sukses / Berhasil
        if (str_contains($s, 'sukses') || str_contains($s, 'success') || str_contains($s, 'berhasil') || str_contains($s, 'kredit') || str_contains($s, 'debet') || str_contains($s, 'ok') || $s === 's') {
            return 'Success';
        }

        // 3. Status Ditolak / Gagal / Rejected
        if (str_contains($s, 'reject') || str_contains($s, 'tolak') || str_contains($s, 'ditolak') || str_contains($s, 'gagal') || str_contains($s, 'batal') || str_contains($s, 'cancel') || str_contains($s, 'unsuccess') || str_contains($s, 'tidak')) {
            return 'Rejected';
        }

        // 4. Status Menunggu / Pending / On Progress
        if (str_contains($s, 'menunggu') || str_contains($s, 'pending') || str_contains($s, 'progress') || str_contains($s, 'proses')) {
            return 'Menunggu';
        }

        // 5. Default jika ada teks lain di excel, simpan nilai aslinya atau '-'
        return trim((string) $rawStatus) ?: '-';
    }

    /**
     * Helper konversi tanggal Excel (Serial Date, ISO, d/m/Y, Format Indonesia, Jam/Menit) ke YYYY-MM-DD
     */
    private function parseExcelDate($sheet, $columnMap, $field, $row)
    {
        if (empty($columnMap[$field])) {
            return null;
        }
        $colIdx = $columnMap[$field];
        $colLetter = Coordinate::stringFromColumnIndex($colIdx);
        $cell = $sheet->getCell("{$colLetter}{$row}");
        $val = $cell->getValue();

        if ($val === null || $val === '') {
            return null;
        }

        // 1. Cek formula Excel
        if (is_string($val) && str_starts_with($val, '=')) {
            try {
                $val = $cell->getCalculatedValue();
            } catch (\Exception $e) {
            }
        }

        // 2. Numeric Serial Date Excel (misal: 45525)
        if (is_numeric($val) && $val > 1000 && $val < 100000) {
            try {
                return Date::excelToDateTimeObject((float) $val)->format('Y-m-d');
            } catch (\Exception $e) {
            }
        }

        // 3. String Tanggal
        $strVal = trim((string) $cell->getFormattedValue());
        if (empty($strVal)) {
            $strVal = trim((string) $val);
        }

        // Hapus jam / waktu jika ada (misal: 21/08/2024 14:30:00 atau 2024-08-21T10:00)
        $strVal = preg_replace('/[T\s]\d{1,2}:\d{1,2}(:\d{1,2})?.*$/', '', $strVal);
        $strVal = trim($strVal);

        if (empty($strVal)) {
            return null;
        }

        // 4. Normalisasi nama bulan bahasa Indonesia ke bahasa Inggris
        $indoMonths = [
            'januari' => 'January', 'februari' => 'February', 'maret' => 'March',
            'april' => 'April', 'mei' => 'May', 'juni' => 'June',
            'juli' => 'July', 'agustus' => 'August', 'september' => 'September',
            'oktober' => 'October', 'november' => 'November', 'desember' => 'December',
            'jan' => 'Jan', 'feb' => 'Feb', 'mar' => 'Mar', 'apr' => 'Apr',
            'jun' => 'Jun', 'jul' => 'Jul', 'agu' => 'Aug', 'agt' => 'Aug',
            'sep' => 'Sep', 'okt' => 'Oct', 'nov' => 'Nov', 'des' => 'Dec',
        ];

        $normalizedStr = str_ireplace(array_keys($indoMonths), array_values($indoMonths), $strVal);

        // 5. Regex parsing: d/m/Y, d-m-Y, d.m.Y
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $normalizedStr, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        // 6. Regex parsing 2-digit year: d/m/y (misal 21/08/24)
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2})$/', $normalizedStr, $m)) {
            $year = (int) $m[3] + 2000;

            return sprintf('%04d-%02d-%02d', $year, (int) $m[2], (int) $m[1]);
        }

        // 7. Regex parsing: Y-m-d atau Y/m/d
        if (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $normalizedStr, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        // 8. Coba parse dengan Carbon
        try {
            return Carbon::parse($normalizedStr)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Helper membersihkan format uang (Rp, titik, koma) menjadi angka murni
     */
    private function parseNominal($raw)
    {
        if (empty($raw)) {
            return 0;
        }
        // Bersihkan Rp, spasi, karakter non-angka kecuali titik/koma
        $cleaned = preg_replace('/[^\d,\.]/', '', (string) $raw);
        if (empty($cleaned)) {
            return 0;
        }

        // Jika format Indonesia: 1.500.000,00 -> 1500000.00
        if (str_contains($cleaned, ',') && str_contains($cleaned, '.')) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (str_contains($cleaned, '.')) {
            // Cek apakah titik merupakan pemisah ribuan (misal 1.500.000)
            if (substr_count($cleaned, '.') > 1 || preg_match('/\.\d{3}$/', $cleaned)) {
                $cleaned = str_replace('.', '', $cleaned);
            }
        } elseif (str_contains($cleaned, ',')) {
            $cleaned = str_replace(',', '', $cleaned);
        }

        return (float) $cleaned;
    }

    /**
     * Helper resolve ID Kantor Cabang yang sangat pintar (mendukung Kode, Nama, Gabungan '001 - PALU', dsb)
     */
    private function resolveCabangId($kode, $nama, &$byKode, &$byNama, $defaultId)
    {
        $rawKode = trim((string) $kode);
        $rawNama = trim((string) $nama);

        // 1. Jika nama berisi gabungan "001 - CABANG UTAMA" atau "[001] PALU"
        if (empty($rawKode) && preg_match('/^\[?(\d{1,4})\]?\s*[\-\:\/]\s*(.*)$/', $rawNama, $matches)) {
            $rawKode = $matches[1];
            $rawNama = trim($matches[2]);
        } elseif (empty($rawKode) && is_numeric($rawNama)) {
            $rawKode = $rawNama;
            $rawNama = '';
        }

        // 2. Cek kecocokan berdasarkan Kode Cabang (misal "001", "1" -> "001")
        if (! empty($rawKode)) {
            $k = strtoupper($rawKode);
            if (isset($byKode[$k])) {
                return $byKode[$k];
            }

            if (is_numeric($k)) {
                $padded = str_pad($k, 3, '0', STR_PAD_LEFT);
                if (isset($byKode[$padded])) {
                    return $byKode[$padded];
                }
            }
        }

        // 3. Cek kecocokan berdasarkan Nama Cabang
        if (! empty($rawNama)) {
            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawNama));

            // Exact normalized match
            foreach ($byNama as $cName => $cId) {
                $normDbName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cName));
                if ($cleanName === $normDbName) {
                    return $cId;
                }
            }

            // Keyword match (misal: "Poso" mencocokkan "CABANG POSO", "Bahodopi" mencocokkan "KCP BAHODOPI")
            $keywords = ['utama', 'palu', 'toli', 'poso', 'luwuk', 'bungku', 'salakan', 'sigi', 'jakarta', 'donggala', 'parigi', 'lambunu', 'labean', 'tolai', 'tinombo', 'tinombala', 'buol', 'soni', 'paleleh', 'ampana', 'wakai', 'tentena', 'pendolo', 'napu', 'kolonodale', 'banggai', 'beteleme', 'batui', 'toili', 'mamosalato', 'tomata', 'baturube', 'bahomotefe', 'bahodopi', 'kulawi', 'tawaeli', 'masama', 'tambarana', 'bunta', 'kotaraya'];

            foreach ($keywords as $kw) {
                if (str_contains($cleanName, $kw)) {
                    foreach ($byNama as $cName => $cId) {
                        $normDbName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cName));
                        if (str_contains($normDbName, $kw)) {
                            return $cId;
                        }
                    }
                }
            }

            // Fuzzy substring match
            foreach ($byNama as $cName => $cId) {
                $normDbName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cName));
                if (strlen($cleanName) >= 3 && (str_contains($normDbName, $cleanName) || str_contains($cleanName, $normDbName))) {
                    return $cId;
                }
            }

            // Buat cabang baru HANYA jika benar-benar nama kantor valid (bukan murni angka / strip / tanggal)
            if (! empty($rawNama) && strlen($rawNama) >= 4 && ! is_numeric($rawNama) && $rawNama !== '-' && preg_match('/[a-zA-Z]/', $rawNama)) {
                $newKode = ! empty($rawKode) && strlen($rawKode) === 3 ? strtoupper($rawKode) : 'CBG-'.rand(100, 999);
                $newCabang = MasterCabang::firstOrCreate(
                    ['nama_cabang' => strtoupper($rawNama)],
                    ['kode_cabang' => $newKode]
                );
                $byKode[strtoupper($newCabang->kode_cabang)] = $newCabang->id;
                $byNama[strtolower($newCabang->nama_cabang)] = $newCabang->id;

                return $newCabang->id;
            }
        }

        return $defaultId;
    }

    /**
     * Helper validasi apakah suatu string BUKAN jenis transaksi (misal: Tanggal, Log Pemeriksaan, No Rekening, dsb)
     */
    private function isInvalidJenisTransaksi($str)
    {
        $s = trim((string) $str);
        if (empty($s)) {
            return true;
        }

        // A. Terlalu panjang (log pemeriksaan / uraian masalah)
        if (strlen($s) > 50) {
            return true;
        }

        // B. Cek apakah ini Tanggal (Format Indonesia, ISO, d/m/Y, dsb)
        $indoMonths = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember', 'jan', 'feb', 'mar', 'apr', 'jun', 'jul', 'agu', 'agt', 'sep', 'okt', 'nov', 'des'];
        $low = strtolower($s);
        foreach ($indoMonths as $m) {
            if (str_contains($low, $m) && preg_match('/\d{1,4}/', $low)) {
                return true;
            }
        }

        // Tanggal regex numerik: 2024-08-21, 21/08/2024, 21.08.2024
        if (preg_match('/^\d{1,4}[\/\-\.]\d{1,2}[\/\-\.]\d{1,4}$/', $s)) {
            return true;
        }

        // C. Cek apakah ini Format Rekening (contoh: 000.00.2310711.001.360) atau murni angka
        if (preg_match('/^\d+(\.\d+)+$/', $s) || is_numeric($s)) {
            return true;
        }

        // D. Cek apakah ini kalimat keterangan (Berdasarkan hasil, force credit, klaim, dsb)
        if (str_starts_with($low, 'berdasarkan') || str_starts_with($low, 'telah') || str_contains($low, 'klaim') || str_contains($low, 'hasil') || str_contains($low, 'log switching') || str_contains($low, 'log mesin')) {
            return true;
        }

        return false;
    }

    /**
     * Helper resolve ID Master Transaksi yang valid (mencocokkan jenis transaksi & channel resmi Bank Sulteng)
     */
    private function resolveTransaksiId($jenis, $channel, &$byKey, $defaultId)
    {
        $rawJenis = trim((string) $jenis);
        $rawChannel = trim((string) $channel);

        // 1. Cek apakah $rawJenis adalah Tanggal, Log Keterangan, Nomor Rekening, atau Karakter Tak Valid
        if ($this->isInvalidJenisTransaksi($rawJenis)) {
            $rawJenis = 'ATM_TARIK TUNAI ATM BANK SULTENG';
        }

        $jenisClean = $rawJenis ?: 'ATM_TARIK TUNAI ATM BANK SULTENG';
        $normJenis = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $jenisClean));

        // 2. Jika channel diisi di Excel, coba cocokkan kombinasi jenis___channel
        if (! empty($rawChannel) && $rawChannel !== '-' && strtolower($rawChannel) !== 'umum') {
            $key = strtolower($jenisClean).'___'.strtolower($rawChannel);
            if (isset($byKey[$key])) {
                return $byKey[$key];
            }
        }

        // 3. Cek pencocokan eksak jenis_transaksi dengan master data resmi
        foreach ($byKey as $k => $id) {
            $parts = explode('___', $k);
            $dbJenis = $parts[0] ?? '';
            $normDb = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $dbJenis));
            if ($normJenis === $normDb) {
                return $id;
            }
        }

        // 4. Cek pencocokan substring / fuzzy dengan master data resmi
        foreach ($byKey as $k => $id) {
            $parts = explode('___', $k);
            $dbJenis = $parts[0] ?? '';
            $normDb = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $dbJenis));
            if (strlen($normJenis) >= 5 && (str_contains($normDb, $normJenis) || str_contains($normJenis, $normDb))) {
                return $id;
            }
        }

        // 5. Cerdas tentukan channel dari nama transaksi jika belum ada channel spesifik
        $autoChannel = $this->detectChannelFromJenis($jenisClean);
        if (! empty($rawChannel) && $rawChannel !== '-' && strtolower($rawChannel) !== 'umum') {
            $autoChannel = strtoupper($rawChannel);
        }

        // 6. Buat master transaksi baru hanya jika nama valid
        if (strlen($jenisClean) <= 50 && ! $this->isInvalidJenisTransaksi($jenisClean)) {
            $transaksi = MasterTransaksi::firstOrCreate(
                ['jenis_transaksi' => strtoupper($jenisClean)],
                ['channel' => $autoChannel, 'biaya_admin' => 0]
            );
            $key = strtolower($transaksi->jenis_transaksi).'___'.strtolower($transaksi->channel);
            $byKey[$key] = $transaksi->id;

            return $transaksi->id;
        }

        return $defaultId;
    }

    /**
     * Helper deteksi channel perbankan otomatis berdasarkan pola nama jenis transaksi
     */
    private function detectChannelFromJenis($jenis)
    {
        $j = strtolower($jenis);
        if (str_contains($j, 'mbanking') || str_contains($j, 'm-banking') || str_contains($j, 'mobile') || str_contains($j, 'qris')) {
            return 'MOBILE BANKING';
        }
        if (str_contains($j, 'sms')) {
            return 'SMS BANKING';
        }
        if (str_contains($j, 'edc')) {
            return str_contains($j, 'lain') ? 'EDC BANK LAIN' : 'DEBIT';
        }
        if (str_contains($j, 'laku pandai')) {
            return 'LAKU PANDAI';
        }
        if (str_contains($j, 'cctv')) {
            return 'CCTV';
        }
        if (str_contains($j, 'telkom') || str_contains($j, 'pulsa') || str_contains($j, 'pln') || str_contains($j, 'dana') || str_contains($j, 'gopay') || str_contains($j, 'bpjs') || str_contains($j, 'halo') || str_contains($j, 'pembayaran') || str_contains($j, 'pembelian') || str_contains($j, 'finnet')) {
            return 'FINNET';
        }
        if (str_contains($j, 'bank lain') || str_contains($j, 'bersama')) {
            return 'ATM BERSAMA';
        }
        if (str_contains($j, 'link')) {
            return 'ATM LINK';
        }
        if (str_contains($j, 'atm') || str_contains($j, 'crm')) {
            return 'ATM LOKAL';
        }

        return '-';
    }

    /**
     * Rute Cerdas: Merender Formulir Penyelesaian Keluhan Nasabah (Blade View Cetak)
     * berdasarkan jenis channel transaksi secara dinamis langsung dari Database,
     * atau format penolakan klaim (?format=penolakan).
     */
    public function downloadDokumen(Request $request, $id)
    {
        $jurnal = Jurnal::with(['masterCabang', 'masterTransaksi'])->findOrFail($id);

        // Validasi: Status 'Menunggu' belum dapat dicetak untuk keluhan nasabah
        if (strtolower(trim($jurnal->status ?? '')) === 'menunggu') {
            return redirect()->route('jurnal.preview', $jurnal->id)
                ->with('error', "Dokumen keluhan nasabah {$jurnal->nama_nasabah} (Tiket: {$jurnal->no_tiket}) belum dapat dicetak karena status saat ini masih Menunggu. Silakan perbarui status penyelesaian keluhan terlebih dahulu.");
        }

        $format = strtolower(trim($request->query('format', '')));

        if ($format === 'penolakan' || $format === 'tolak') {
            return view('cetak.penolakan', compact('jurnal'));
        }

        $logOptions = config('keluhan_logs.atm_lokal', []);

        if ($format === 'form' || $format === 'keluhan') {
            return view('cetak.lokal', compact('jurnal', 'logOptions'));
        }

        $channel = strtoupper(trim($jurnal->masterTransaksi->channel ?? ''));
        $jenis = strtoupper(trim($jurnal->masterTransaksi->jenis_transaksi ?? ''));
        $permasalahan = strtoupper(trim($jurnal->permasalahan ?? ''));
        $jurnalJenis = strtoupper(trim($jurnal->jenis_transaksi ?? ''));

        // 1. QRIS (Prioritas Utama: Khusus transaksi QRIS, selalu gunakan slip QRIS walaupun channelnya Mobile Banking)
        $isQris = str_contains($jenis, 'QRIS') ||
                  str_contains($channel, 'QRIS') ||
                  str_contains($permasalahan, 'QRIS') ||
                  str_contains($jurnalJenis, 'QRIS');

        if ($isQris) {
            return view('cetak.qris', compact('jurnal'));
        }

        // 2. MOBILE BANKING / DIGI / BPJS / DANA (M-Banking Pembayaran, Transfer, Pulsa, PLN, BPJS, DANA, Top Up, dll. - Kecuali QRIS)
        if (
            str_contains($channel, 'MOBILE') || str_contains($channel, 'MBANKING') || str_contains($channel, 'M-BANKING') || str_contains($channel, 'DIGI') ||
            str_contains($jenis, 'MBANKING') || str_contains($jenis, 'M-BANKING') || str_contains($jenis, 'MOBILE BANKING') || str_contains($jenis, 'DIGI') ||
            str_contains($jenis, 'BPJS') || str_contains($channel, 'BPJS') || str_contains($permasalahan, 'BPJS') ||
            str_contains($jenis, 'DANA') || str_contains($channel, 'DANA') || str_contains($permasalahan, 'DANA') ||
            str_contains($permasalahan, 'MBANKING') || str_contains($permasalahan, 'M-BANKING') || str_contains($permasalahan, 'MOBILE BANKING') || str_contains($permasalahan, 'DIGI')
        ) {
            return view('cetak.mbanking', compact('jurnal'));
        }

        // 3. EDC (Dua Cabang: EDC Bank Lain vs EDC Bank Sulteng Langsung)
        $isEdc = str_contains($jenis, 'EDC') ||
                 str_contains($channel, 'EDC') ||
                 str_contains($permasalahan, 'EDC') ||
                 str_contains($jurnalJenis, 'EDC') ||
                 $channel === 'DEBIT' ||
                 $channel === 'EDC BANK LAIN';

        if ($isEdc) {
            $isEdcBankLain = $channel === 'EDC BANK LAIN' ||
                             str_contains($channel, 'BANK LAIN') ||
                             str_contains($channel, 'LAIN') ||
                             str_contains($jenis, 'EDC BANK LAIN') ||
                             str_contains($jenis, 'BANK LAIN') ||
                             str_contains($jurnalJenis, 'EDC BANK LAIN') ||
                             str_contains($jurnalJenis, 'BANK LAIN') ||
                             (str_contains($permasalahan, 'EDC') && (str_contains($permasalahan, 'BANK LAIN') || str_contains($permasalahan, 'LAIN')));

            if ($isEdcBankLain) {
                // 3a. EDC Bank Lain: Menggunakan Form Penyelesaian & Slip ATM Bersama
                return view('cetak.atmb', compact('jurnal'));
            }

            // 3b. EDC Bank Sulteng Langsung (bukan bank lain): Hanya menggunakan slip EDC biasa yang dikhususkan untuk Bank Sulteng saja
            return view('cetak.edc', compact('jurnal'));
        }

        // 4. ATM LINK
        if (str_contains($jenis, 'LINK') || $channel === 'ATM LINK') {
            return view('cetak.link', compact('jurnal'));
        }

        // 5. ATM BERSAMA
        if (str_contains($jenis, 'BERSAMA') || str_contains($jenis, 'BANK LAIN') || $channel === 'ATM BERSAMA') {
            return view('cetak.atmb', compact('jurnal'));
        }

        // 6. SMS BANKING
        if (str_contains($jenis, 'SMS') || $channel === 'SMS BANKING') {
            return view('cetak.sms', compact('jurnal'));
        }

        // 7. CCTV
        if (str_contains($jenis, 'CCTV') || $channel === 'CCTV') {
            return view('cetak.cctv', compact('jurnal'));
        }

        // 8. FINNET / PULSA ATM / PLN PREPAID
        if (str_contains($jenis, 'FINNET') || str_contains($jenis, 'PULSA') || str_contains($jenis, 'PLN') || str_contains($jenis, 'PREPAID') || str_contains($jenis, 'LISTRIK') || $channel === 'FINNET' || $channel === 'PULSA' || $channel === 'PLN') {
            return view('cetak.pulsa', compact('jurnal'));
        }

        // 9. ATM LOKAL & DEFAULT
        if (str_contains($jenis, 'ATM') || str_contains($jenis, 'CRM') || $channel === 'ATM LOKAL' || $channel === 'LOKAL' || $channel === 'ATM') {
            return view('cetak.lokal', compact('jurnal', 'logOptions'));
        }

        return view('cetak.lokal', compact('jurnal', 'logOptions'));
    }

    public function updateLog(Request $request, $id)
    {
        $request->validate([
            'keterangan_log' => 'required|string',
        ]);

        $jurnal = Jurnal::findOrFail($id);
        $jurnal->keterangan_log = trim($request->input('keterangan_log'));
        $jurnal->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Keterangan log berhasil diperbarui',
            'keterangan_log' => $jurnal->keterangan_log,
        ]);
    }
}
