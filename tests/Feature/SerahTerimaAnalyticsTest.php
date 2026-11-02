<?php

use App\Models\Jurnal;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use App\Models\User;
use App\Services\SerahTerimaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ekspor csv pembukuan menghasilkan format operasional bank sulteng yang presisi', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cabang = MasterCabang::create(['kode_cabang' => '001', 'nama_cabang' => 'Cabang Utama Palu']);
    $transaksi = MasterTransaksi::create(['jenis_transaksi' => 'ATM_TARIK TUNAI', 'channel' => 'ATM LOKAL']);

    $jurnal1 = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'no_tiket' => 'BS-202609030021',
        'no_resi' => 'RESI001',
        'nama_nasabah' => 'Musdalifah',
        'no_rekening' => '0010205293011',
        'nominal_transaksi' => 3750000,
        'status' => 'CLOSED',
        'tgl_transaksi' => '2026-09-03',
        'tgl_terima' => '2026-09-03',
        'tgl_selesai' => '2026-09-05',
    ]);

    $jurnal2 = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'no_tiket' => 'BS-202609030022',
        'no_resi' => 'RESI002',
        'nama_nasabah' => 'Andi Saputra',
        'no_rekening' => '0010205293022',
        'nominal_transaksi' => 1250000,
        'status' => 'Done',
        'tgl_transaksi' => '2026-09-03',
        'tgl_terima' => '2026-09-03',
        'tgl_selesai' => '2026-09-06',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.export_csv'), [
        'ids' => [$jurnal1->id, $jurnal2->id],
        'tanggal_serah_terima' => '2026-09-18',
    ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('attachment; filename="Serah_Terima_Pembukuan_');

    $csv = $response->getContent();
    $lines = explode("\n", trim($csv));

    expect($lines[0])->toBe('SERAH TERIMA PENYELESAIAN NASABAH,,,');
    expect($lines[1])->toBe('PER TANGGAL 18 SEPTEMBER 2026,,,');
    expect($lines[2])->toBe('No.,Nomor Tiket,Nama,Nomor Rekening,Nominal,Keterangan');
    expect($lines[3])->toBe('1,BS-202609030021,MUSDALIFAH,0010205293011,3750000,CLOSED');
    expect($lines[4])->toBe('2,BS-202609030022,ANDI SAPUTRA,0010205293022,1250000,CLOSED');
    expect($lines[5])->toBe('MOHON UNTUK DIBUKUKAN HARI INI 18 SEPTEMBER 2026,,,,,,');
    expect($lines[6])->toBe(',,,,"Yang Menerima,",,,,');
});

test('ekspor csv gagal validasi apabila tidak ada tiket yang dipilih', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.export_csv'), [
        'ids' => [],
    ]);

    $response->assertSessionHasErrors('ids');
});

test('ekspor excel pembukuan menghasilkan file xlsx resmi dengan tabel bergaris', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cabang = MasterCabang::create(['kode_cabang' => '001', 'nama_cabang' => 'Cabang Utama Palu']);
    $transaksi = MasterTransaksi::create(['jenis_transaksi' => 'ATM_TARIK TUNAI', 'channel' => 'ATM LOKAL']);

    $jurnal = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'no_tiket' => 'BS-202609030021',
        'no_resi' => 'RESI001',
        'nama_nasabah' => 'Musdalifah',
        'no_rekening' => '0010205293011',
        'nominal_transaksi' => 3750000,
        'status' => 'CLOSED',
        'tgl_transaksi' => '2026-09-03',
        'tgl_terima' => '2026-09-03',
        'tgl_selesai' => '2026-09-05',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.serah-terima.export_excel'), [
        'ids' => [$jurnal->id],
        'tanggal_serah_terima' => '2026-09-18',
    ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('attachment; filename="Serah_Terima_Pembukuan_');
});

test('kalkulasi analitik menghitung metrik kepatuhan sla dan distribusi channel dengan akurat', function () {
    $cabang = MasterCabang::create(['kode_cabang' => '001', 'nama_cabang' => 'Cabang Utama Palu']);

    $trxAtm = MasterTransaksi::create(['jenis_transaksi' => 'ATM_TARIK TUNAI', 'channel' => 'ATM LOKAL']);
    $trxMbanking = MasterTransaksi::create(['jenis_transaksi' => 'MBANKING_TRANSFER', 'channel' => 'MOBILE BANKING']);
    $trxEdc = MasterTransaksi::create(['jenis_transaksi' => 'EDC', 'channel' => 'DEBIT']);
    $trxQris = MasterTransaksi::create(['jenis_transaksi' => 'QRIS', 'channel' => 'MOBILE BANKING']);

    // 1. ATM Tunai — Tepat Waktu (3 hari <= 14 hari) — Rp 4.000.000 (40%)
    Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $trxAtm->id,
        'no_tiket' => 'BS-101',
        'no_resi' => 'R101',
        'nama_nasabah' => 'Nasabah ATM',
        'no_rekening' => '1001',
        'nominal_transaksi' => 4000000,
        'status' => 'Done',
        'tgl_transaksi' => '2026-10-01',
        'tgl_terima' => '2026-10-01',
        'tgl_selesai' => '2026-10-04',
    ]);

    // 2. Mobile Banking — Tepat Waktu (5 hari <= 14 hari) — Rp 3.000.000 (30%)
    Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $trxMbanking->id,
        'no_tiket' => 'BS-102',
        'no_resi' => 'R102',
        'nama_nasabah' => 'Nasabah MBanking',
        'no_rekening' => '1002',
        'nominal_transaksi' => 3000000,
        'status' => 'Success',
        'tgl_transaksi' => '2026-10-01',
        'tgl_terima' => '2026-10-01',
        'tgl_selesai' => '2026-10-06',
    ]);

    // 3. EDC — Tepat Waktu (10 hari <= 14 hari) — Rp 2.000.000 (20%)
    Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $trxEdc->id,
        'no_tiket' => 'BS-103',
        'no_resi' => 'R103',
        'nama_nasabah' => 'Nasabah EDC',
        'no_rekening' => '1003',
        'nominal_transaksi' => 2000000,
        'status' => 'CLOSED',
        'tgl_transaksi' => '2026-10-01',
        'tgl_terima' => '2026-10-01',
        'tgl_selesai' => '2026-10-11',
    ]);

    // 4. QRIS — Terlambat / Overdue (20 hari > 14 hari) — Rp 1.000.000 (10%)
    Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $trxQris->id,
        'no_tiket' => 'BS-104',
        'no_resi' => 'R104',
        'nama_nasabah' => 'Nasabah QRIS',
        'no_rekening' => '1004',
        'nominal_transaksi' => 1000000,
        'status' => 'Done',
        'tgl_transaksi' => '2026-10-01',
        'tgl_terima' => '2026-10-01',
        'tgl_selesai' => '2026-10-21',
    ]);

    $jurnals = SerahTerimaService::ambilTiketSelesai()->get();
    $analitik = SerahTerimaService::kalkulasiAnalitik($jurnals);

    // Uji Volume & Nilai Pembukuan
    expect($analitik['volume']['total_tiket'])->toBe(4);
    expect($analitik['volume']['total_nominal'])->toBe(10000000.0);
    expect($analitik['volume']['total_nominal_rupiah'])->toBe('Rp 10.000.000');

    // Uji Kepatuhan SLA (3 Tepat Waktu = 75%, 1 Overdue = 25%)
    expect($analitik['sla']['tepat_waktu'])->toBe(3);
    expect($analitik['sla']['terlambat'])->toBe(1);
    expect($analitik['sla']['persentase_tepat_waktu'])->toBe(75.0);
    expect($analitik['sla']['persentase_terlambat'])->toBe(25.0);

    // Uji Distribusi Channel Transaksi
    expect($analitik['channels']['ATM Tunai']['jumlah_tiket'])->toBe(1);
    expect($analitik['channels']['ATM Tunai']['nominal'])->toBe(4000000.0);
    expect($analitik['channels']['ATM Tunai']['persentase'])->toBe(40.0);

    expect($analitik['channels']['Mobile Banking']['jumlah_tiket'])->toBe(1);
    expect($analitik['channels']['Mobile Banking']['nominal'])->toBe(3000000.0);
    expect($analitik['channels']['Mobile Banking']['persentase'])->toBe(30.0);

    expect($analitik['channels']['EDC']['jumlah_tiket'])->toBe(1);
    expect($analitik['channels']['EDC']['nominal'])->toBe(2000000.0);
    expect($analitik['channels']['EDC']['persentase'])->toBe(20.0);

    expect($analitik['channels']['QRIS']['jumlah_tiket'])->toBe(1);
    expect($analitik['channels']['QRIS']['nominal'])->toBe(1000000.0);
    expect($analitik['channels']['QRIS']['persentase'])->toBe(10.0);
});

test('halaman serah terima tampil bersih tanpa kartu analitik berlebihan dan memuat tombol ekspor excel', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.serah-terima.index'));

    $response->assertOk();
    $response->assertDontSee('Kepatuhan SLA (Resolution Performance)');
    $response->assertDontSee('Distribusi Channel Transaksi');
    $response->assertSee('Ekspor Excel Pembukuan');
    $response->assertSee('Cetak Lembar Serah Terima');
});

test('deteksi status pembukuan membedakan tiket yang sudah dibukukan dan belum dibukukan', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cabang = MasterCabang::create(['kode_cabang' => '001', 'nama_cabang' => 'Cabang Utama Palu']);
    $transaksi = MasterTransaksi::create(['jenis_transaksi' => 'ATM_TARIK TUNAI', 'channel' => 'ATM LOKAL']);

    $jurnalSudah = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'no_tiket' => 'BS-202611020001',
        'no_resi' => 'R-SUDAH',
        'nama_nasabah' => 'Nasabah Sudah Dibukukan',
        'no_rekening' => '001001',
        'nominal_transaksi' => 1500000,
        'status' => 'Done',
        'tgl_transaksi' => '2026-11-01',
        'tgl_terima' => '2026-11-01',
        'tgl_selesai' => '2026-11-02',
    ]);

    $jurnalBelum = Jurnal::create([
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'no_tiket' => 'BS-202611020002',
        'no_resi' => 'R-BELUM',
        'nama_nasabah' => 'Nasabah Belum Dibukukan',
        'no_rekening' => '001002',
        'nominal_transaksi' => 2500000,
        'status' => 'CLOSED',
        'tgl_transaksi' => '2026-11-01',
        'tgl_terima' => '2026-11-01',
        'tgl_selesai' => '2026-11-02',
    ]);

    // Buat dokumen serah terima untuk $jurnalSudah
    $serahTerima = SerahTerimaService::buatSerahTerima([$jurnalSudah->id], \Carbon\Carbon::parse('2026-11-02'), $admin);

    $peta = SerahTerimaService::petaStatusPembukuan();
    expect($peta)->toHaveKey($jurnalSudah->id);
    expect($peta[$jurnalSudah->id]['nomor_dokumen'])->toBe($serahTerima->nomor_dokumen);
    expect($peta)->not->toHaveKey($jurnalBelum->id);

    // Filter hanya yang belum dibukukan
    $belumList = SerahTerimaService::ambilTiketSelesai(['status_pembukuan' => 'belum'])->pluck('id');
    expect($belumList)->toContain($jurnalBelum->id);
    expect($belumList)->not->toContain($jurnalSudah->id);

    // Filter hanya yang sudah dibukukan
    $sudahList = SerahTerimaService::ambilTiketSelesai(['status_pembukuan' => 'sudah'])->pluck('id');
    expect($sudahList)->toContain($jurnalSudah->id);
    expect($sudahList)->not->toContain($jurnalBelum->id);

    // Pastikan badge tampil di halaman index
    $response = $this->actingAs($admin)->get(route('admin.serah-terima.index'));
    $response->assertOk();
    $response->assertSee('Sudah Dibukukan');
    $response->assertSee($serahTerima->nomor_dokumen);
    $response->assertSee('Belum Dibukukan');
    $response->assertSee('filterPembukuan', false);
    $response->assertSee('filterSla', false);
    $response->assertSee('filterCabang', false);
});
