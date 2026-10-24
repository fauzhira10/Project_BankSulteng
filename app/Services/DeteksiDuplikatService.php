<?php

namespace App\Services;

use App\Models\Jurnal;
use App\Models\Pengaduan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Deteksi keluhan berulang berdasarkan kombinasi Nama Nasabah + No. Resi.
 *
 * Aturannya bertingkat, bukan blokir mentah:
 *
 *   KEMBAR   Nama + Resi + Tanggal Transaksi sama persis. Ditolak dan tidak bisa
 *            dilanjutkan, sebab index unik `jurnal_unique_kombinasi` di database
 *            memang melarang barisnya masuk.
 *   BERULANG Nama + Resi sama, tetapi tanggal transaksinya berbeda. Hanya diberi
 *            peringatan: nomor resi (trace number) mesin ATM berputar, jadi satu
 *            nasabah bisa sah memakai nomor yang sama pada tanggal berbeda.
 *            Petugas boleh melanjutkan setelah konfirmasi.
 *   AMAN     Selain itu.
 *
 * Pencocokan juga melihat tabel pengaduan CS yang belum dijurnal, supaya keluhan
 * yang sedang berjalan di cabang ikut ketahuan sebelum jurnal baru dibuat.
 */
class DeteksiDuplikatService
{
    public const AMAN = 'aman';

    public const BERULANG = 'berulang';

    public const KEMBAR = 'kembar';

    /**
     * Banyaknya data pembanding yang ditampilkan pada panel peringatan.
     */
    public const BATAS_TAMPIL = 5;

    /**
     * Nama nasabah selalu disimpan dalam huruf besar tanpa spasi di ujung
     * (lihat JurnalController::store), jadi pembandingnya dirapikan sama persis.
     * Sengaja tidak memakai UPPER()/TRIM() di sisi SQL agar index tetap terpakai.
     */
    public static function normalNama(?string $nilai): string
    {
        return strtoupper(trim((string) $nilai));
    }

    public static function normalResi(?string $nilai): string
    {
        return trim((string) $nilai);
    }

    /**
     * Penanda bahwa petugas sudah menyetujui satu kombinasi tertentu. Karena
     * isinya nama + resi yang sedang diperiksa, penanda dari isian sebelumnya
     * tidak bisa dipakai ulang untuk nasabah atau resi yang lain.
     */
    public static function token(?string $nama, ?string $resi): string
    {
        return self::normalNama($nama).'|'.self::normalResi($resi);
    }

    /**
     * Periksa satu kombinasi nama + resi terhadap jurnal dan pengaduan yang ada.
     *
     * @param  string|null  $tglTransaksi  Tanggal transaksi (Y-m-d) untuk membedakan KEMBAR dari BERULANG.
     * @param  int|null  $abaikanJurnalId  Jurnal yang sedang diedit, supaya tidak melaporkan dirinya sendiri.
     * @param  int|null  $abaikanPengaduanId  Pengaduan yang sedang dibuka, dengan alasan yang sama.
     * @return array{tingkat:string, jurnals:Collection, pengaduans:Collection, jumlah:int, token:string, nama:string, resi:string}
     */
    public static function periksa(
        ?string $nama,
        ?string $resi,
        ?string $tglTransaksi = null,
        ?int $abaikanJurnalId = null,
        ?int $abaikanPengaduanId = null
    ): array {
        $nama = self::normalNama($nama);
        $resi = self::normalResi($resi);

        if ($nama === '' || $resi === '') {
            return self::hasilKosong($nama, $resi);
        }

        $jurnals = Jurnal::with([
            'masterCabang:id,kode_cabang,nama_cabang',
            'masterTransaksi:id,jenis_transaksi,channel',
        ])
            ->where('nama_nasabah', $nama)
            ->where('no_resi', $resi)
            ->when($abaikanJurnalId, fn ($q) => $q->whereKeyNot($abaikanJurnalId))
            ->orderByDesc('tgl_transaksi')
            ->limit(self::BATAS_TAMPIL)
            ->get();

        // Pengaduan CS yang belum tertaut ke jurnal mana pun.
        $pengaduans = Pengaduan::with(['cabang:id,kode_cabang,nama_cabang'])
            ->where('nama_nasabah', $nama)
            ->where('no_resi', $resi)
            ->whereNull('jurnal_id')
            ->when($abaikanPengaduanId, fn ($q) => $q->whereKeyNot($abaikanPengaduanId))
            ->orderByDesc('tgl_transaksi')
            ->limit(self::BATAS_TAMPIL)
            ->get();

        $tingkat = self::AMAN;

        if ($jurnals->isNotEmpty() || $pengaduans->isNotEmpty()) {
            $tingkat = self::BERULANG;
        }

        // Hanya jurnal yang bisa menjadi KEMBAR — index unik ada di tabel jurnals.
        $tglNormal = self::normalTanggal($tglTransaksi);

        if ($tglNormal !== null && $jurnals->contains(fn ($j) => self::normalTanggal($j->tgl_transaksi) === $tglNormal)) {
            $tingkat = self::KEMBAR;
        }

        return [
            'tingkat' => $tingkat,
            'jurnals' => $jurnals,
            'pengaduans' => $pengaduans,
            'jumlah' => $jurnals->count() + $pengaduans->count(),
            'token' => self::token($nama, $resi),
            'nama' => $nama,
            'resi' => $resi,
        ];
    }

    /**
     * Tanggal dari database bisa berupa string (model Jurnal tanpa $casts) atau
     * Carbon (model Pengaduan), jadi diseragamkan dulu sebelum dibandingkan.
     */
    private static function normalTanggal(mixed $tanggal): ?string
    {
        if (blank($tanggal)) {
            return null;
        }

        if ($tanggal instanceof \DateTimeInterface) {
            return $tanggal->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $tanggal)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{tingkat:string, jurnals:Collection, pengaduans:Collection, jumlah:int, token:string, nama:string, resi:string}
     */
    private static function hasilKosong(string $nama, string $resi): array
    {
        return [
            'tingkat' => self::AMAN,
            'jurnals' => new Collection,
            'pengaduans' => new Collection,
            'jumlah' => 0,
            'token' => self::token($nama, $resi),
            'nama' => $nama,
            'resi' => $resi,
        ];
    }
}
