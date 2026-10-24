<?php

use App\Enums\PengaduanStatus;
use App\Models\Jurnal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('admin melihat daftar pengaduan masuk dengan tab terkirim sebagai default', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatAdmin();
    $transaksi = buatTransaksi();
    $pengaduan = buatPengaduan($cs, $transaksi);

    $response = $this->actingAs($admin)->get(route('admin.pengaduan.index'));

    $response->assertStatus(200);
    $response->assertSee($pengaduan->nomor_tiket);
    $response->assertSee('Verifikasi');
});

test('admin dapat menerima pengaduan sehingga statusnya menjadi diterima', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatAdmin();
    $pengaduan = buatPengaduan($cs, buatTransaksi());

    $this->actingAs($admin)->post(route('admin.pengaduan.terima', $pengaduan))
        ->assertRedirect(route('admin.pengaduan.show', $pengaduan));

    $pengaduan->refresh();
    expect($pengaduan->status)->toBe(PengaduanStatus::Diterima)
        ->and($pengaduan->diterima_oleh)->toBe($admin->id)
        ->and($pengaduan->diterima_at)->not->toBeNull();

    // Tidak bisa diterima dua kali
    $this->actingAs($admin)->post(route('admin.pengaduan.terima', $pengaduan))->assertForbidden();
});

test('menolak pengaduan wajib disertai catatan untuk cs', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatAdmin();
    $pengaduan = buatPengaduan($cs, buatTransaksi());

    $this->actingAs($admin)->from(route('admin.pengaduan.show', $pengaduan))
        ->post(route('admin.pengaduan.tolak', $pengaduan), ['catatan_pusat' => ''])
        ->assertSessionHasErrors('catatan_pusat');

    expect($pengaduan->fresh()->status)->toBe(PengaduanStatus::Terkirim);

    $this->actingAs($admin)
        ->post(route('admin.pengaduan.tolak', $pengaduan), ['catatan_pusat' => 'Nomor resi tidak sesuai struk, mohon dicek ulang.'])
        ->assertRedirect(route('admin.pengaduan.show', $pengaduan));

    $pengaduan->refresh();
    expect($pengaduan->status)->toBe(PengaduanStatus::Ditolak)
        ->and($pengaduan->ditolak_at)->not->toBeNull()
        ->and($pengaduan->catatan_pusat)->toBe('Nomor resi tidak sesuai struk, mohon dicek ulang.');
});

test('form jurnal terisi otomatis dari pengaduan', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatAdmin();
    $pengaduan = buatPengaduan($cs, buatTransaksi());

    $response = $this->actingAs($admin)->get(route('jurnal.create', ['pengaduan' => $pengaduan->id]));

    $response->assertStatus(200);
    $response->assertSee($pengaduan->nomor_tiket);
    $response->assertSee('BUDI SANTOSO');
    $response->assertSee('name="pengaduan_id"', false);
    $response->assertSee('TRANSAKSI ATM / TARIK TUNAI / UANG TIDAK KELUAR');
});

test('menyimpan jurnal dari pengaduan menautkan keduanya dan status menjadi diproses', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $admin = buatAdmin();
    $transaksi = buatTransaksi();
    $pengaduan = buatPengaduan($cs, $transaksi);

    $response = $this->actingAs($admin)->post(route('jurnal.store'), [
        'pengaduan_id' => $pengaduan->id,
        'nama_nasabah' => $pengaduan->nama_nasabah,
        'no_resi' => $pengaduan->no_resi,
        'no_rekening' => $pengaduan->no_rekening,
        'no_kartu' => $pengaduan->no_kartu,
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'channel' => 'ATM LOKAL',
        'terminal_transaksi' => $pengaduan->terminal_transaksi,
        'nominal_transaksi' => 500000,
        'biaya_admin' => 0,
        'tgl_transaksi' => '2026-09-01',
        'tgl_terima' => '2026-09-04',
        'tgl_selesai' => '2026-09-04',
        'status' => 'Menunggu',
        'permasalahan' => 'TRANSAKSI ATM / TARIK TUNAI',
        'keterangan_log' => '',
    ]);

    $response->assertRedirect(route('admin.pengaduan.show', $pengaduan));

    $pengaduan->refresh();
    $jurnal = Jurnal::first();

    expect($jurnal)->not->toBeNull()
        ->and($pengaduan->jurnal_id)->toBe($jurnal->id)
        ->and($pengaduan->status)->toBe(PengaduanStatus::Diproses)
        ->and($pengaduan->diproses_at)->not->toBeNull()
        ->and($pengaduan->diterima_oleh)->toBe($admin->id)
        // Jurnal membawa nomor tiket milik pengaduan, tidak membuat nomor baru,
        // sehingga satu keluhan bernomor sama dari CS sampai selesai.
        ->and($jurnal->no_tiket)->toBe($pengaduan->nomor_tiket)
        ->and(Jurnal::formatTiketValid($jurnal->no_tiket))->toBeTrue();

    // Pengaduan yang sudah tertaut tidak bisa dijurnal ulang
    $this->actingAs($admin)->get(route('jurnal.create', ['pengaduan' => $pengaduan->id]))
        ->assertRedirect(route('admin.pengaduan.show', $pengaduan));
});

test('perubahan status jurnal disinkronkan ke status pengaduan cs', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    $transaksi = buatTransaksi();
    $pengaduan = buatPengaduan($cs, $transaksi);

    $jurnal = Jurnal::create([
        'nama_nasabah' => 'BUDI SANTOSO', 'no_resi' => '123456', 'no_rekening' => '00900001234', 'no_kartu' => '-',
        'no_tiket' => $pengaduan->nomor_tiket, 'master_cabang_id' => $cabang->id, 'master_transaksi_id' => $transaksi->id,
        'terminal_transaksi' => '-', 'nominal_transaksi' => 500000, 'biaya_admin' => 0,
        'tgl_transaksi' => '2026-09-01', 'tgl_terima' => '2026-09-04', 'tgl_selesai' => null,
        'status' => 'Menunggu', 'permasalahan' => '-', 'keterangan_log' => '-',
    ]);

    $pengaduan->update(['jurnal_id' => $jurnal->id, 'status' => PengaduanStatus::Diproses, 'diproses_at' => now()]);

    $jurnal->update(['status' => 'Done', 'tgl_selesai' => '2026-09-05']);
    $pengaduan->refresh();
    expect($pengaduan->status)->toBe(PengaduanStatus::Selesai)
        ->and($pengaduan->selesai_at)->not->toBeNull();

    $jurnal->update(['status' => 'Rejected']);
    $pengaduan->refresh();
    expect($pengaduan->status)->toBe(PengaduanStatus::Ditolak)
        ->and($pengaduan->ditolak_at)->not->toBeNull()
        ->and($pengaduan->selesai_at)->toBeNull()
        ->and($pengaduan->catatan_pusat)->not->toBeEmpty();

    $jurnal->update(['status' => 'Menunggu']);
    expect($pengaduan->fresh()->status)->toBe(PengaduanStatus::Diproses);

    // Jurnal dihapus → pengaduan kembali ke Diterima tanpa tautan
    $jurnal->delete();
    $pengaduan->refresh();
    expect($pengaduan->status)->toBe(PengaduanStatus::Diterima)
        ->and($pengaduan->jurnal_id)->toBeNull()
        ->and($pengaduan->diproses_at)->toBeNull();
});

test('reset seluruh data jurnal mengembalikan pengaduan tertaut ke status diterima', function () {
    $cabang = buatCabang();
    $cs = buatCs($cabang);
    // Reset massal kini dibatasi Admin Utama dan meminta konfirmasi kata sandi
    $admin = buatSuperAdmin();
    $transaksi = buatTransaksi();
    $pengaduan = buatPengaduan($cs, $transaksi);

    $jurnal = Jurnal::create([
        'nama_nasabah' => 'BUDI SANTOSO', 'no_resi' => '123456', 'no_rekening' => '00900001234', 'no_kartu' => '-',
        'no_tiket' => '-', 'master_cabang_id' => $cabang->id, 'master_transaksi_id' => $transaksi->id,
        'terminal_transaksi' => '-', 'nominal_transaksi' => 500000, 'biaya_admin' => 0,
        'tgl_transaksi' => '2026-09-01', 'tgl_terima' => '2026-09-04', 'tgl_selesai' => null,
        'status' => 'Done', 'permasalahan' => '-', 'keterangan_log' => '-',
    ]);
    $pengaduan->update(['jurnal_id' => $jurnal->id, 'status' => PengaduanStatus::Selesai, 'diproses_at' => now(), 'selesai_at' => now()]);

    $this->actingAs($admin)->delete(route('jurnal.reset_all'), [
        'delete_template' => false,
        'password' => 'admin123',
    ])->assertRedirect(route('jurnal.index'));

    $pengaduan->refresh();
    expect(Jurnal::count())->toBe(0)
        ->and($pengaduan->status)->toBe(PengaduanStatus::Diterima)
        ->and($pengaduan->jurnal_id)->toBeNull();
});

test('lampiran pengaduan dapat dibuka admin sesuai format aslinya namun tidak oleh cs cabang lain', function () {
    Storage::fake('local');
    $cabangA = buatCabang('001', 'CABANG UTAMA');
    $cabangB = buatCabang('003', 'CABANG POSO');
    $csA = buatCs($cabangA);
    $csB = buatCs($cabangB);
    $admin = buatAdmin();
    $pengaduan = buatPengaduan($csA, buatTransaksi());

    $gambar = buatLampiran($pengaduan, 'foto_ktp', 'ktp.jpg', 'image/jpeg');
    $pdf = buatLampiran($pengaduan, 'form_keluhan', 'form.pdf', 'application/pdf');

    $urlGambar = route('pengaduan.lampiran.show', [$pengaduan, $gambar]);
    $urlPdf = route('pengaduan.lampiran.show', [$pengaduan, $pdf]);

    $this->actingAs($admin)->get($urlGambar)->assertStatus(200)->assertHeader('content-type', 'image/jpeg');
    $this->actingAs($admin)->get($urlPdf)->assertStatus(200)->assertHeader('content-type', 'application/pdf');

    $this->actingAs($csA)->get($urlGambar)->assertStatus(200);
    $this->actingAs($csB)->get($urlGambar)->assertForbidden();
});

test('admin dapat membuat akun cs dan menonaktifkannya', function () {
    $cabang = buatCabang('003', 'CABANG POSO');
    $admin = buatSuperAdmin();

    $this->actingAs($admin)->post(route('admin.pengguna.store'), [
        'name' => 'CS Poso',
        'username' => 'cs.poso',
        'email' => 'cs.poso@banksulteng.co.id',
        'role' => 'cs',
        'master_cabang_id' => $cabang->id,
        'password' => 'rahasiaPoso2026',
        'password_confirmation' => 'rahasiaPoso2026',
    ])->assertRedirect(route('admin.pengguna.index'));

    $this->assertDatabaseHas('users', ['username' => 'cs.poso', 'role' => 'cs', 'master_cabang_id' => $cabang->id, 'is_active' => true]);

    $csBaru = User::where('username', 'cs.poso')->first();

    $this->actingAs($admin)->patch(route('admin.pengguna.toggle_aktif', $csBaru))->assertRedirect();
    expect($csBaru->fresh()->is_active)->toBeFalse();

    // Tidak bisa menonaktifkan diri sendiri
    $this->actingAs($admin)->patch(route('admin.pengguna.toggle_aktif', $admin))->assertSessionHas('error');
    expect($admin->fresh()->is_active)->toBeTrue();

    // Akun CS tanpa cabang ditolak
    $this->actingAs($admin)->from(route('admin.pengguna.create'))->post(route('admin.pengguna.store'), [
        'name' => 'CS Tanpa Cabang', 'username' => 'cs.tanpa', 'email' => 'cs.tanpa@banksulteng.co.id',
        'role' => 'cs', 'master_cabang_id' => '', 'password' => 'rahasiaPoso2026', 'password_confirmation' => 'rahasiaPoso2026',
    ])->assertSessionHasErrors('master_cabang_id');
});
