<?php

use App\Enums\PengaduanStatus;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use App\Models\Pengaduan;
use App\Models\PengaduanLampiran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| Helper bersama untuk membuat data uji (cabang, transaksi, akun admin/CS, pengaduan).
|
*/

function buatCabang(string $kode = '001', string $nama = 'CABANG UTAMA'): MasterCabang
{
    return MasterCabang::create(['kode_cabang' => $kode, 'nama_cabang' => $nama]);
}

function buatTransaksi(string $jenis = 'ATM_TARIK TUNAI ATM BANK SULTENG', string $channel = 'ATM LOKAL', float $biaya = 0): MasterTransaksi
{
    return MasterTransaksi::create(['jenis_transaksi' => $jenis, 'channel' => $channel, 'biaya_admin' => $biaya]);
}

function buatAdmin(array $atribut = []): User
{
    return User::factory()->admin()->create(array_merge([
        'username' => 'admin.test',
        'password' => 'admin123',
    ], $atribut));
}

function buatSuperAdmin(array $atribut = []): User
{
    return User::factory()->superadmin()->create(array_merge([
        'username' => 'admin',
        'password' => 'admin123',
    ], $atribut));
}

function buatCs(MasterCabang $cabang, array $atribut = []): User
{
    return User::factory()->cs($cabang->id)->create(array_merge([
        'username' => 'cs.'.strtolower(str_replace(' ', '', $cabang->nama_cabang)).'.'.$cabang->id,
        'password' => 'cs12345',
    ], $atribut));
}

function buatPengaduan(User $cs, MasterTransaksi $transaksi, array $atribut = []): Pengaduan
{
    return Pengaduan::create(array_merge([
        'user_id' => $cs->id,
        'nama_pelapor' => $cs->name,
        'master_cabang_id' => $cs->master_cabang_id,
        'kategori' => 'TRANSAKSI ATM',
        'sub_kategori' => 'TARIK TUNAI',
        'sub_kategori_2' => 'UANG TIDAK KELUAR',
        'nama_nasabah' => 'BUDI SANTOSO',
        'no_hp' => '081234567890',
        'no_ktp' => '7271012345670001',
        'no_rekening' => '00900001234',
        'no_kartu' => '6019001234567890',
        'master_transaksi_id' => $transaksi->id,
        'no_resi' => '123456',
        'terminal_transaksi' => '180 - CRM.PALUBARAT',
        'channel' => 'ATM LOKAL',
        'nominal_transaksi' => 500000,
        'tgl_transaksi' => '2026-09-01',
        'kronologi' => 'Nasabah melakukan tarik tunai Rp 500.000, uang tidak keluar namun saldo terdebet.',
        'status' => PengaduanStatus::Terkirim,
    ], $atribut));
}

/**
 * Buat berkas gambar uji yang berisi konten nyata (bukan kanvas kosong),
 * sehingga perilaku kompresi menyerupai foto asli dari lapangan.
 */
function berkasGambarUji(string $nama, int $lebar, int $tinggi): UploadedFile
{
    $ekstensi = strtolower(pathinfo($nama, PATHINFO_EXTENSION)) ?: 'jpg';

    $img = imagecreatetruecolor($lebar, $tinggi);
    imagefilledrectangle($img, 0, 0, $lebar - 1, $tinggi - 1, imagecolorallocate($img, 248, 248, 248));

    mt_srand(20260904);
    for ($i = 0; $i < 600; $i++) {
        $warna = imagecolorallocate($img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        $x = mt_rand(0, $lebar - 1);
        $y = mt_rand(0, $tinggi - 1);
        imagefilledrectangle($img, $x, $y, min($lebar - 1, $x + mt_rand(20, 140)), min($tinggi - 1, $y + mt_rand(20, 140)), $warna);
    }

    $path = tempnam(sys_get_temp_dir(), 'ujigambar').'.'.$ekstensi;

    match ($ekstensi) {
        'png' => imagepng($img, $path, 6),
        'webp' => imagewebp($img, $path, 85),
        default => imagejpeg($img, $path, 90),
    };
    imagedestroy($img);

    $mime = match ($ekstensi) {
        'png' => 'image/png',
        'webp' => 'image/webp',
        default => 'image/jpeg',
    };

    return new UploadedFile($path, $nama, $mime, null, true);
}

/**
 * Buat satu lampiran tersimpan (berkas asli di disk privat) untuk pengaduan.
 * Pakai Storage::fake('local') pada test yang memanggilnya.
 */
function buatLampiran(Pengaduan $pengaduan, string $jenis = 'foto_ktp', string $nama = 'ktp-depan.jpg', string $mime = 'image/jpeg'): PengaduanLampiran
{
    $ekstensi = pathinfo($nama, PATHINFO_EXTENSION) ?: 'jpg';
    $path = "pengaduan/{$pengaduan->id}/{$jenis}-".Str::lower(Str::random(6)).".{$ekstensi}";
    $isi = $mime === 'application/pdf' ? "%PDF-1.4\n%dummy" : 'isi-berkas-gambar-dummy';

    Storage::disk('local')->put($path, $isi);

    return $pengaduan->lampirans()->create([
        'jenis' => $jenis,
        'nama_asli' => $nama,
        'path' => $path,
        'sumber' => str_starts_with($mime, 'image/') ? 'gambar' : 'pdf',
        'mime' => $mime,
        'ukuran' => strlen($isi),
    ]);
}

/**
 * Data POST minimal untuk menyimpan jurnal keluhan lewat form.
 */
function dataJurnal(MasterCabang $cabang, MasterTransaksi $transaksi, array $override = []): array
{
    return array_merge([
        'nama_nasabah' => 'Ahmad Rifai',
        'no_resi' => '12345678',
        'no_rekening' => '001099887766',
        'no_kartu' => '6019001234567890',
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'channel' => 'ATM LOKAL',
        'terminal_transaksi' => '-',
        'nominal_transaksi' => 500000,
        'biaya_admin' => 0,
        'tgl_transaksi' => '2026-09-01',
        'tgl_terima' => '2026-09-04',
        'tgl_selesai' => '2026-09-05',
        'status' => 'Menunggu',
        'permasalahan' => '-',
        'keterangan_log' => '-',
    ], $override);
}

/**
 * Atribut lengkap untuk membuat baris jurnal langsung lewat model.
 */
function dataJurnalDb(MasterCabang $cabang, MasterTransaksi $transaksi, array $override = []): array
{
    return array_merge([
        'nama_nasabah' => 'BUDI SANTOSO',
        'no_resi' => '123456',
        'no_rekening' => '00900001234',
        'no_kartu' => '-',
        'no_tiket' => '-',
        'master_cabang_id' => $cabang->id,
        'master_transaksi_id' => $transaksi->id,
        'terminal_transaksi' => '-',
        'nominal_transaksi' => 500000,
        'biaya_admin' => 0,
        'tgl_transaksi' => '2026-09-01',
        'tgl_terima' => '2026-09-04',
        'tgl_selesai' => null,
        'status' => 'Menunggu',
        'permasalahan' => '-',
        'keterangan_log' => '-',
    ], $override);
}

function dataFormPengaduan(MasterTransaksi $transaksi, ?MasterCabang $cabang = null, array $override = []): array
{
    return array_merge([
        'nama_pelapor' => 'Siti CS',
        'master_cabang_id' => $cabang?->id,
        'kategori' => 'Transaksi ATM',
        'sub_kategori' => 'Tarik Tunai',
        'sub_kategori_2' => 'Uang tidak keluar',
        'nama_nasabah' => 'Ahmad Rifai',
        'no_hp' => '081234567890',
        'no_ktp' => '7271012345670001',
        'no_rekening' => '00900001234',
        'no_kartu' => '6019001234567890',
        'master_transaksi_id' => $transaksi->id,
        'no_resi' => '998877',
        'terminal_transaksi' => '180 - CRM.PALUBARAT',
        'channel' => 'ATM LOKAL',
        'nominal_transaksi' => '1.500.000',
        'tgl_transaksi' => '2026-09-01',
        'kronologi' => 'Nasabah tarik tunai Rp 1.500.000 di ATM Palu Barat, uang tidak keluar tetapi saldo terdebet dan struk tidak tercetak.',
    ], $override);
}
