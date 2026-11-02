<?php

use App\Models\Jurnal;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use App\Models\SerahTerima;
use App\Models\User;
use App\Services\SerahTerimaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('service serah terima dapat membuat nomor dokumen resmi berurutan', function () {
    $tgl = Carbon::parse('2026-11-02');
    $nomor1 = SerahTerimaService::buatNomorDokumen($tgl);
    expect($nomor1)->toBe('ST/20261102/0001');

    // Buat record serah terima pertama
    SerahTerima::create([
        'nomor_dokumen' => $nomor1,
        'tanggal_serah_terima' => '2026-11-02',
        'total_tiket' => 1,
        'total_nominal' => 500000,
        'tiket_ids' => [1],
        'blockchain_hash' => hash('sha256', 'dummy'),
    ]);

    // Nomor berikutnya harus berurutan (0002)
    $nomor2 = SerahTerimaService::buatNomorDokumen($tgl);
    expect($nomor2)->toBe('ST/20261102/0002');
});

test('service serah terima menghitung akumulasi total tiket dan nominal secara akurat', function () {
    $data = [
        ['nominal_transaksi' => 1500000],
        ['nominal_transaksi' => 2750000],
        ['nominal_transaksi' => 750000],
    ];

    $kalkulasi = SerahTerimaService::kalkulasiTotal($data);

    expect($kalkulasi['total_tiket'])->toBe(3);
    expect($kalkulasi['total_nominal'])->toBe(5000000.0);
    expect($kalkulasi['total_nominal_rupiah'])->toBe('Rp 5.000.000');
});

test('service serah terima mengunci integritas dokumen ke dalam hash blockchain 64 karakter', function () {
    $hash = SerahTerimaService::kunciKeBlockchain([
        'nomor_dokumen' => 'ST/20261102/0001',
        'tanggal_serah_terima' => '2026-11-02',
        'total_nominal' => 3500000,
        'tiket_ids' => [1, 2, 3],
    ]);

    expect(strlen($hash))->toBe(64);
    expect(ctype_xdigit($hash))->toBeTrue();
});

test('service serah terima hanya mengambil tiket yang statusnya telah selesai', function () {
    $cabang = MasterCabang::create(['kode_cabang' => '001', 'nama_cabang' => 'Cabang Palu']);
    $transaksi = MasterTransaksi::create(['jenis_transaksi' => 'Tarik Tunai', 'channel' => 'ATM']);

    // Jurnal Selesai
    $jurnalDone = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'no_tiket' => 'BS-001',
        'no_resi' => 'RESI001',
        'nama_nasabah' => 'Budi',
        'no_rekening' => '1234567890',
        'nominal_transaksi' => 500000,
        'status' => 'Done',
        'tgl_transaksi' => '2026-11-01',
        'tgl_terima' => '2026-11-01',
        'tgl_selesai' => '2026-11-02',
    ]);

    $jurnalClosed = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'no_tiket' => 'BS-002',
        'no_resi' => 'RESI002',
        'nama_nasabah' => 'Siti',
        'no_rekening' => '1234567891',
        'nominal_transaksi' => 1000000,
        'status' => 'CLOSED',
        'tgl_transaksi' => '2026-11-01',
        'tgl_terima' => '2026-11-01',
        'tgl_selesai' => '2026-11-02',
    ]);

    // Jurnal Belum Selesai (Menunggu)
    $jurnalMenunggu = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'no_tiket' => 'BS-003',
        'no_resi' => 'RESI003',
        'nama_nasabah' => 'Joko',
        'no_rekening' => '1234567892',
        'nominal_transaksi' => 200000,
        'status' => 'Menunggu',
        'tgl_transaksi' => '2026-11-01',
        'tgl_terima' => '2026-11-01',
    ]);

    $hasil = SerahTerimaService::ambilTiketSelesai()->get();

    expect($hasil->count())->toBe(2);
    expect($hasil->pluck('id'))->toContain($jurnalDone->id, $jurnalClosed->id);
    expect($hasil->pluck('id'))->not->toContain($jurnalMenunggu->id);
});

test('admin dapat mengakses halaman index serah terima', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.serah-terima.index'));

    $response->assertOk();
    $response->assertViewIs('admin.serah_terima.index');
    $response->assertViewHas(['tikets', 'ringkasan', 'cabangs']);
});

test('admin dapat mencetak lembar serah terima untuk tiket yang dipilih dan tercatat di database', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cabang = MasterCabang::create(['kode_cabang' => '001', 'nama_cabang' => 'Cabang Palu']);
    $transaksi = MasterTransaksi::create(['jenis_transaksi' => 'Tarik Tunai', 'channel' => 'ATM']);

    $jurnal = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'no_tiket' => 'BS-202609030021',
        'no_resi' => 'RESI889',
        'nama_nasabah' => 'MUSDALIFAH',
        'no_rekening' => '0010205293011',
        'nominal_transaksi' => 3750000,
        'status' => 'CLOSED',
        'tgl_transaksi' => '2026-09-03',
        'tgl_terima' => '2026-09-03',
        'tgl_selesai' => '2026-09-05',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.cetak'), [
        'ids' => [$jurnal->id],
        'tanggal_serah_terima' => '2026-09-18',
    ]);

    $response->assertOk();
    $response->assertViewIs('admin.serah_terima.cetak');
    $response->assertViewHas(['jurnals', 'total', 'nomorDokumen', 'blockchainHash']);

    // Pastikan tersimpan di tabel serah_terimas
    $this->assertDatabaseHas('serah_terimas', [
        'total_tiket' => 1,
        'total_nominal' => 3750000.00,
        'created_by' => $admin->id,
    ]);
});

test('cetak serah terima gagal validasi jika tidak ada tiket yang dipilih', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.cetak'), [
        'ids' => [],
    ]);

    $response->assertSessionHasErrors('ids');
});

test('pengguna bukan admin dialihkan saat mengakses serah terima', function () {
    $cs = User::factory()->create(['role' => 'cs']);

    $response = $this->actingAs($cs)->get(route('admin.serah-terima.index'));

    $response->assertRedirect(route('cs.dashboard'));

    // Akses via JSON menghasilkan 403 Forbidden
    $responseJson = $this->actingAs($cs)->getJson(route('admin.serah-terima.index'));
    $responseJson->assertForbidden();
});
