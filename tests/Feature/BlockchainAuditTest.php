<?php

use App\Models\AuditTrail;
use App\Models\BlokAudit;
use App\Services\AuditTrailService;
use App\Services\BlockchainAuditService;
use App\Services\JangkarEthereumService;
use App\Support\MerkleTree;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Kesulitan rendah agar test cepat; aturannya sama dengan kesulitan 4.
    config([
        'blockchain.ukuran_blok' => 3,
        'blockchain.tingkat_kesulitan' => 2,
        'blockchain.ethereum.aktif' => false,
    ]);
});

function catatAudit(int $jumlah): void
{
    for ($i = 1; $i <= $jumlah; $i++) {
        AuditTrailService::catat('uji.kejadian', ['keterangan' => "kejadian ke-{$i}"]);
    }
}

function blockchain(): BlockchainAuditService
{
    return app(BlockchainAuditService::class);
}

function aktifkanJangkar(): void
{
    config([
        'blockchain.ethereum.aktif' => true,
        'blockchain.ethereum.rpc_url' => 'http://127.0.0.1:8545',
        'blockchain.ethereum.alamat_kontrak' => '0x'.str_repeat('ab', 20),
    ]);
}

/**
 * Tiru ulang tindakan pemalsu yang paham blockchain: ubah isi catatan, hitung
 * ulang rantai hash audit, bangun ulang merkle root, lalu tambang ulang blok itu
 * dan SEMUA blok sesudahnya. Hasilnya lolos pemeriksaan lokal.
 */
function palsukanDanTambangUlang(int $auditId, string $keteranganBaru): void
{
    DB::table('audit_trails')->where('id', $auditId)->update(['keterangan' => $keteranganBaru]);

    $hashSebelumnya = null;
    foreach (AuditTrail::query()->orderBy('id')->get() as $baris) {
        $baris->hash_sebelumnya = $hashSebelumnya;
        $baris->hash_sekarang = $baris->hitungHash($hashSebelumnya);
        DB::table('audit_trails')->where('id', $baris->id)->update([
            'hash_sebelumnya' => $baris->hash_sebelumnya,
            'hash_sekarang' => $baris->hash_sekarang,
        ]);
        $hashSebelumnya = $baris->hash_sekarang;
    }

    $induk = null;
    foreach (BlokAudit::query()->orderBy('nomor')->get() as $blok) {
        $daftar = AuditTrail::query()
            ->where('id', '>', $induk?->audit_id_akhir ?? 0)
            ->where('id', '<=', $blok->audit_id_akhir)
            ->orderBy('id')->get()
            ->map(fn ($b) => [(int) $b->id, $b->hitungHash($b->hash_sebelumnya)])->all();

        $blok->daftar_transaksi = $daftar;
        $blok->merkle_root = MerkleTree::akar(array_column($daftar, 1));
        $blok->hash_sebelumnya = $induk?->hash_blok ?? BlokAudit::HASH_NOL;

        $kepala = $blok->kepala();
        $awalan = str_repeat('0', $blok->tingkat_kesulitan);
        for ($n = 0; ! str_starts_with(BlokAudit::hashKepala($kepala, $n), $awalan); $n++);

        $blok->nonce = $n;
        $blok->hash_blok = BlokAudit::hashKepala($kepala, $n);
        // Lepas sementara indeks unik hash_sebelumnya: tulis lewat query builder.
        DB::table('blok_audits')->where('id', $blok->id)->update([
            'daftar_transaksi' => json_encode($daftar),
            'merkle_root' => $blok->merkle_root,
            'hash_sebelumnya' => $blok->hash_sebelumnya,
            'nonce' => $n,
            'hash_blok' => $blok->hash_blok,
        ]);

        $induk = $blok;
    }
}

// ---------------------------------------------------------------------------
// Pohon Merkle
// ---------------------------------------------------------------------------

test('merkle root berubah bila satu daun berubah, dan bukti merkle memverifikasi setiap daun', function () {
    $daun = array_map(fn ($i) => hash('sha256', "catatan-{$i}"), range(1, 7));
    $akar = MerkleTree::akar($daun);

    foreach ($daun as $i => $hash) {
        $bukti = MerkleTree::bukti($daun, $i);
        expect(MerkleTree::verifikasi($hash, $bukti, $akar))->toBeTrue();
        // Daun palsu dengan bukti yang sama tidak sampai ke akar.
        expect(MerkleTree::verifikasi(hash('sha256', 'palsu'), $bukti, $akar))->toBeFalse();
    }

    $diubah = $daun;
    $diubah[3] = hash('sha256', 'catatan-4-diubah');
    expect(MerkleTree::akar($diubah))->not->toBe($akar);
});

test('pohon merkle menangani satu daun dan tanpa daun', function () {
    $satu = hash('sha256', 'tunggal');

    expect(MerkleTree::akar([$satu]))->toBe($satu)
        ->and(MerkleTree::bukti([$satu], 0))->toBe([])
        ->and(MerkleTree::akar([]))->toBe(hash('sha256', ''));
});

// ---------------------------------------------------------------------------
// Penambangan
// ---------------------------------------------------------------------------

test('menambang membuat genesis lalu mengelompokkan catatan audit ke blok berantai', function () {
    catatAudit(7);

    $baru = blockchain()->tambang();

    // 7 catatan, 3 per blok → blok #1 (3), #2 (3), #3 (1), ditambah genesis #0.
    expect($baru)->toHaveCount(3)
        ->and(BlokAudit::count())->toBe(4)
        ->and(array_map(fn ($b) => $b->jumlah_transaksi, $baru))->toBe([3, 3, 1]);

    $blok = BlokAudit::query()->orderBy('nomor')->get();
    expect($blok[0]->hash_sebelumnya)->toBe(BlokAudit::HASH_NOL);

    foreach ($blok as $i => $b) {
        expect(BlokAudit::memenuhiKesulitan($b->hash_blok, 2))->toBeTrue()
            ->and($b->hash_blok)->toBe($b->hitungHash());

        if ($i > 0) {
            expect($b->hash_sebelumnya)->toBe($blok[$i - 1]->hash_blok);
        }
    }

    expect(blockchain()->periksa())
        ->utuh->toBeTrue()
        ->transaksi_tercatat->toBe(7)
        ->transaksi_tertunda->toBe(0);
});

test('opsi --penuh menunda catatan yang belum cukup untuk satu blok', function () {
    catatAudit(4);

    $this->artisan('blockchain:tambang --penuh')->assertSuccessful();

    expect(BlokAudit::where('nomor', '>', 0)->count())->toBe(1)
        ->and(blockchain()->periksa()['transaksi_tertunda'])->toBe(1);

    // Putaran berikutnya tanpa --penuh menyegel sisanya ke blok baru yang menyambung.
    blockchain()->tambang();
    expect(BlokAudit::max('nomor'))->toBe(2)
        ->and(blockchain()->periksa()['utuh'])->toBeTrue();
});

test('menambang tanpa catatan baru tidak membuat blok', function () {
    catatAudit(2);
    blockchain()->tambang();
    $jumlah = BlokAudit::count();

    expect(blockchain()->tambang())->toBe([])
        ->and(BlokAudit::count())->toBe($jumlah);
});

test('catatan yang sudah dirusak sebelum ditambang tidak disegel ke blok', function () {
    catatAudit(3);
    $id = AuditTrail::orderBy('id')->skip(1)->value('id');
    DB::table('audit_trails')->where('id', $id)->update(['keterangan' => 'diubah diam-diam']);

    expect(fn () => blockchain()->tambang())->toThrow(RuntimeException::class, "#{$id}");
    expect(BlokAudit::where('nomor', '>', 0)->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Deteksi pemalsuan
// ---------------------------------------------------------------------------

test('mengubah isi catatan audit terdeteksi dan menunjuk catatan yang diubah', function () {
    catatAudit(5);
    blockchain()->tambang();
    $id = AuditTrail::orderBy('id')->skip(3)->value('id');

    DB::table('audit_trails')->where('id', $id)->update(['username' => 'bukan.pelakunya']);

    $hasil = blockchain()->periksa();
    $rusak = collect($hasil['blok'])->reject(fn ($b) => $b['valid']);

    expect($hasil['utuh'])->toBeFalse()
        ->and($rusak)->toHaveCount(1)
        ->and($rusak->first()['nomor'])->toBe(2)
        ->and(implode(' ', $rusak->first()['masalah']))->toContain("Catatan audit #{$id} diubah");
});

test('mengubah isi sekaligus memperbarui hash catatannya tetap terdeteksi di tingkat blok', function () {
    catatAudit(3);
    blockchain()->tambang();
    $baris = AuditTrail::orderBy('id')->first();

    $baris->keterangan = 'dipalsukan';
    DB::table('audit_trails')->where('id', $baris->id)->update([
        'keterangan' => 'dipalsukan',
        'hash_sekarang' => $baris->hitungHash($baris->hash_sebelumnya),
    ]);

    expect(blockchain()->periksa()['utuh'])->toBeFalse();
});

test('menghapus dan menyisipkan catatan audit terdeteksi', function () {
    catatAudit(6);
    blockchain()->tambang();
    [$pertama, $kedua] = AuditTrail::orderBy('id')->limit(2)->pluck('id')->all();

    DB::table('audit_trails')->where('id', $kedua)->delete();

    $masalah = implode(' ', collect(blockchain()->periksa()['blok'])->pluck('masalah')->flatten()->all());
    expect($masalah)->toContain("Catatan audit #{$kedua} dihapus");

    // Sisipkan kembali baris palsu dengan id yang sama.
    DB::table('audit_trails')->insert([
        'id' => $kedua, 'aksi' => 'uji.palsu', 'hash_sekarang' => str_repeat('f', 64),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $masalah = implode(' ', collect(blockchain()->periksa()['blok'])->pluck('masalah')->flatten()->all());
    expect($masalah)->toContain("Catatan audit #{$kedua} diubah");
});

test('mengubah kepala blok atau memutus tautan antarblok terdeteksi', function () {
    catatAudit(6);
    blockchain()->tambang();

    DB::table('blok_audits')->where('nomor', 1)->update(['nonce' => 999999]);
    $masalah = blockchain()->periksa()['blok'];
    $blok1 = collect($masalah)->firstWhere('nomor', 1);

    expect($blok1['valid'])->toBeFalse()
        ->and(implode(' ', $blok1['masalah']))->toContain('Kepala blok diubah');
});

test('menghapus satu blok terdeteksi dari nomor yang melompat', function () {
    catatAudit(9);
    blockchain()->tambang();

    DB::table('blok_audits')->where('nomor', 2)->delete();
    $blok3 = collect(blockchain()->periksa()['blok'])->firstWhere('nomor', 3);

    expect($blok3['valid'])->toBeFalse()
        ->and(implode(' ', $blok3['masalah']))->toContain('Nomor blok melompat');
});

test('pemalsuan yang menambang ulang seluruh rantai lolos pemeriksaan lokal tetapi tertangkap jangkar Ethereum', function () {
    catatAudit(6);
    blockchain()->tambang();

    // Rekam hash asli seolah sudah tercatat di Ethereum.
    $asli = BlokAudit::orderBy('nomor')->pluck('hash_blok', 'nomor')->all();

    palsukanDanTambangUlang(AuditTrail::orderBy('id')->value('id'), 'jejak yang disamarkan');

    // Lapis 1 saja tidak cukup: rantai palsu terlihat utuh.
    expect(blockchain()->periksa()['utuh'])->toBeTrue();

    aktifkanJangkar();
    Process::fake(fn () => Process::result(json_encode([
        'ok' => true,
        'jaringan' => 'sepolia',
        'blok' => collect($asli)->map(fn ($h) => ['ada' => true, 'hash_blok' => $h, 'merkle_root' => null, 'waktu' => 1])->all(),
    ])));

    $banding = app(JangkarEthereumService::class)->bandingkan(BlokAudit::orderBy('nomor')->get());

    // Genesis tidak berubah; blok #1 dan sesudahnya berbeda dengan Ethereum.
    expect($banding[0]['status'])->toBe('cocok')
        ->and($banding[1]['status'])->toBe('berbeda')
        ->and($banding[2]['status'])->toBe('berbeda');
});

// ---------------------------------------------------------------------------
// Jangkar Ethereum
// ---------------------------------------------------------------------------

test('menjangkarkan blok mengirim hanya nomor, hash blok, dan merkle root', function () {
    catatAudit(2);
    blockchain()->tambang();
    aktifkanJangkar();

    Process::fake(fn () => Process::result(json_encode([
        'ok' => true, 'status' => 'terkirim', 'jaringan' => 'sepolia',
        'tx' => '0x'.str_repeat('1', 64), 'blok_eth' => 7001234,
    ])));

    $hasil = app(JangkarEthereumService::class)->jangkarkanTertunda();

    expect($hasil)->toMatchArray(['terkirim' => 2, 'gagal' => 0]);

    $blok = BlokAudit::where('nomor', 1)->first();
    expect($blok->jangkar_status)->toBe('terkirim')
        ->and($blok->jangkar_tx)->toBe('0x'.str_repeat('1', 64))
        ->and($blok->jangkar_blok_eth)->toBe(7001234)
        ->and($blok->urlTransaksiEthereum())->toContain('sepolia.etherscan.io/tx/0x');

    Process::assertRan(function (PendingProcess $proses) use ($blok) {
        return $proses->command === ['node', 'jangkar.mjs', 'kirim', '1', $blok->hash_blok, $blok->merkle_root];
    });
    // Tidak ada isi catatan audit yang ikut terkirim.
    Process::assertDidntRun(fn (PendingProcess $p) => str_contains(json_encode($p->command), 'kejadian'));
});

test('kegagalan jaringan menandai blok gagal dan menghentikan antrean', function () {
    catatAudit(4);
    blockchain()->tambang();
    aktifkanJangkar();

    Process::fake(fn () => Process::result(json_encode(['ok' => false, 'galat' => 'could not detect network']), exitCode: 1));

    $hasil = app(JangkarEthereumService::class)->jangkarkanTertunda();

    expect($hasil['terkirim'])->toBe(0)
        ->and($hasil['galat'])->toContain('could not detect network')
        ->and(BlokAudit::where('jangkar_status', 'gagal')->count())->toBe(1)
        ->and(BlokAudit::whereNull('jangkar_status')->count())->toBe(2);

    // Putaran berikutnya mencoba ulang blok yang gagal.
    Process::fake(fn () => Process::result(json_encode(['ok' => true, 'status' => 'terkirim', 'jaringan' => 'sepolia', 'tx' => '0xab', 'blok_eth' => 1])));
    app(JangkarEthereumService::class)->jangkarkanTertunda();

    expect(BlokAudit::where('jangkar_status', 'terkirim')->count())->toBe(3);
});

test('konflik hash di Ethereum dicatat sebagai konflik dan tidak dicoba ulang', function () {
    catatAudit(1);
    blockchain()->tambang();
    aktifkanJangkar();

    Process::fake(fn () => Process::result(json_encode([
        'ok' => false, 'status' => 'konflik', 'jaringan' => 'sepolia',
        'galat' => 'Blok #0 sudah tercatat di Ethereum dengan hash berbeda',
    ]), exitCode: 2));

    app(JangkarEthereumService::class)->jangkarkanTertunda();

    expect(BlokAudit::where('nomor', 0)->value('jangkar_status'))->toBe('konflik');

    Process::fake(fn () => Process::result(json_encode(['ok' => true, 'status' => 'terkirim', 'jaringan' => 'sepolia', 'tx' => '0xab', 'blok_eth' => 1])));
    app(JangkarEthereumService::class)->jangkarkanTertunda();

    Process::assertRan(fn (PendingProcess $p) => $p->command[3] === '1');
    // Riwayat proses menumpuk lintas fake(): blok #0 hanya tercatat dari percobaan pertama.
    Process::assertRanTimes(fn (PendingProcess $p) => $p->command[3] === '0', 1);
    expect(BlokAudit::where('nomor', 0)->value('jangkar_status'))->toBe('konflik');
});

test('perintah tambang tidak menyentuh Ethereum bila penjangkaran nonaktif', function () {
    Process::fake();
    catatAudit(2);

    $this->artisan('blockchain:tambang')->assertSuccessful();

    Process::assertNothingRan();
});

test('perintah periksa gagal bila blockchain dirusak', function () {
    catatAudit(3);
    blockchain()->tambang();

    $this->artisan('blockchain:periksa')->assertSuccessful();

    DB::table('audit_trails')->where('id', AuditTrail::min('id'))->update(['aksi' => 'jurnal.dibuat']);

    $this->artisan('blockchain:periksa')->assertFailed();
});

test('penambangan dan pemeriksaan blockchain terjadwal', function () {
    $jadwal = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command)->implode(' ');

    expect($jadwal)->toContain('blockchain:tambang')
        ->and($jadwal)->toContain('blockchain:periksa --jangkar');
});

// ---------------------------------------------------------------------------
// Block Explorer
// ---------------------------------------------------------------------------

test('admin dapat membuka block explorer dan menambang dari halaman', function () {
    $admin = buatAdmin();
    catatAudit(4);

    $this->actingAs($admin)->get(route('admin.blockchain.index'))
        ->assertOk()
        ->assertSee('Blockchain belum dimulai');

    $this->actingAs($admin)->post(route('admin.blockchain.tambang'))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($admin)->get(route('admin.blockchain.index'))
        ->assertOk()
        ->assertSee('Rantai blok UTUH');

    $this->actingAs($admin)->get(route('admin.blockchain.show', 1))
        ->assertOk()
        ->assertSee('Pohon Merkle')
        ->assertSee('Bukti Merkle');
});

test('rincian blok tidak menampilkan nilai data yang dicatat', function () {
    $admin = buatAdmin();
    AuditTrailService::catat('jurnal.diubah', [
        'lama' => ['no_rekening' => '1234567890123'],
        'baru' => ['no_rekening' => '9876543210987'],
    ]);
    blockchain()->tambang();

    $this->actingAs($admin)->get(route('admin.blockchain.show', 1))
        ->assertOk()
        ->assertSee('jurnal.diubah')
        ->assertDontSee('1234567890123')
        ->assertDontSee('9876543210987');
});

test('halaman blok yang dirusak menandai catatan yang berubah', function () {
    $admin = buatAdmin();
    catatAudit(3);
    blockchain()->tambang();
    DB::table('audit_trails')->where('id', AuditTrail::min('id'))->update(['keterangan' => 'diubah']);

    $this->actingAs($admin)->get(route('admin.blockchain.index'))->assertSee('Rantai blok RUSAK');
    $this->actingAs($admin)->get(route('admin.blockchain.show', 1))
        ->assertSee('Blok rusak')
        ->assertSee('diubah setelah masuk blok');
});

test('customer service tidak dapat membuka block explorer', function () {
    $cs = buatCs(buatCabang());

    $this->actingAs($cs)->get(route('admin.blockchain.index'))->assertRedirect();
    $this->actingAs($cs)->post(route('admin.blockchain.tambang'))->assertRedirect();

    expect(BlokAudit::count())->toBe(0);
});

test('tombol jangkarkan menolak bila ethereum belum dikonfigurasi', function () {
    $this->actingAs(buatAdmin())->post(route('admin.blockchain.jangkarkan'))
        ->assertSessionHas('error');
});
