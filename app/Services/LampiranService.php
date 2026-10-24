<?php

namespace App\Services;

use App\Models\Pengaduan;
use App\Models\PengaduanLampiran;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Menyimpan lampiran pengaduan di disk privat dengan **format yang sama persis
 * seperti kiriman CS** (JPG tetap JPG, PNG tetap PNG, WEBP tetap WEBP, PDF tetap PDF).
 *
 * Khusus gambar, berkas dioptimalkan agar penyimpanan tidak membengkak:
 * orientasi EXIF dikoreksi dan gambar diperkecil bila sisi terpanjangnya melebihi
 * `config('pengaduan.lebar_maks_gambar')`. Bila gambar sudah kecil dan orientasinya
 * benar, berkas asli disimpan tanpa dikodekan ulang. Berkas PDF tidak pernah diubah.
 */
class LampiranService
{
    /**
     * Peta ekstensi → tipe MIME. Ekstensi sudah divalidasi lebih dulu oleh aturan
     * `mimes:` pada controller, sehingga pemetaan ini aman dan deterministik.
     */
    protected const PETA_MIME = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
    ];

    protected const EKSTENSI_GAMBAR = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Simpan seluruh berkas untuk satu jenis lampiran (satu baris per berkas).
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, PengaduanLampiran>
     */
    public function simpan(Pengaduan $pengaduan, string $jenis, array $files): array
    {
        $hasil = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $hasil[] = $this->simpanBerkas($pengaduan, $jenis, $file);
        }

        return $hasil;
    }

    /**
     * Hapus lampiran beserta berkasnya di disk.
     */
    public function hapus(PengaduanLampiran $lampiran): void
    {
        $disk = Storage::disk($this->disk());

        if ($lampiran->path && $disk->exists($lampiran->path)) {
            $disk->delete($lampiran->path);
        }

        $lampiran->delete();
    }

    /**
     * Hapus seluruh folder lampiran milik satu pengaduan.
     */
    public function hapusSemua(Pengaduan $pengaduan): void
    {
        Storage::disk($this->disk())->deleteDirectory($this->folder($pengaduan));
    }

    // ==================== PENYIMPANAN ====================

    protected function disk(): string
    {
        return config('pengaduan.disk', 'local');
    }

    protected function folder(Pengaduan $pengaduan): string
    {
        return 'pengaduan/'.$pengaduan->id;
    }

    /**
     * Nama berkas di disk: jenis-tanggal-acak.ekstensi (ekstensi asli dipertahankan).
     */
    protected function namaFile(string $jenis, string $ekstensi): string
    {
        return $jenis.'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(6)).'.'.$ekstensi;
    }

    /**
     * Ekstensi asli berkas (huruf kecil), dengan cadangan dari tipe MIME.
     */
    protected function ekstensi(UploadedFile $file): string
    {
        $ekstensi = strtolower(trim((string) $file->getClientOriginalExtension()));

        if ($ekstensi === '' || ! array_key_exists($ekstensi, self::PETA_MIME)) {
            $tebakan = strtolower((string) $file->guessExtension());
            $ekstensi = array_key_exists($tebakan, self::PETA_MIME) ? $tebakan : $ekstensi;
        }

        if ($ekstensi === '') {
            throw new RuntimeException('Ekstensi berkas "'.$file->getClientOriginalName().'" tidak dapat dikenali.');
        }

        return $ekstensi;
    }

    protected function simpanBerkas(Pengaduan $pengaduan, string $jenis, UploadedFile $file): PengaduanLampiran
    {
        $ekstensi = $this->ekstensi($file);
        $mime = self::PETA_MIME[$ekstensi] ?? ($file->getMimeType() ?: 'application/octet-stream');
        $namaAsli = $file->getClientOriginalName();
        $ukuran = (int) $file->getSize();

        // Ukuran berkas harus dibaca sebelum storeAs memindahkan berkas sementara
        $optimal = in_array($ekstensi, self::EKSTENSI_GAMBAR, true)
            ? $this->optimalkanGambar($file, $ekstensi)
            : null;

        if ($optimal !== null) {
            $path = $this->folder($pengaduan).'/'.$this->namaFile($jenis, $ekstensi);
            Storage::disk($this->disk())->put($path, $optimal);
            $ukuran = strlen($optimal);
        } else {
            // storeAs memindahkan berkas tanpa mengubah isinya
            $path = $file->storeAs($this->folder($pengaduan), $this->namaFile($jenis, $ekstensi), $this->disk());
        }

        if ($path === false || $path === '') {
            throw new RuntimeException('Berkas "'.$namaAsli.'" gagal disimpan ke penyimpanan.');
        }

        return $pengaduan->lampirans()->create([
            'jenis' => $jenis,
            'nama_asli' => Str::limit($namaAsli, 240, '…'),
            'path' => $path,
            'sumber' => str_starts_with($mime, 'image/') ? 'gambar' : 'pdf',
            'mime' => $mime,
            'ukuran' => $ukuran,
        ]);
    }

    // ==================== OPTIMALISASI GAMBAR ====================

    /**
     * Kembalikan isi gambar yang sudah dikoreksi orientasi & diperkecil,
     * atau null bila berkas asli sebaiknya disimpan apa adanya.
     */
    protected function optimalkanGambar(UploadedFile $file, string $ekstensi): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $isi = @file_get_contents($file->getRealPath());
        if ($isi === false || $isi === '') {
            return null;
        }

        $info = @getimagesizefromstring($isi);
        if ($info === false) {
            return null;
        }

        [$lebar, $tinggi] = $info;

        // Gambar sangat besar dilewati agar tidak menghabiskan memori PHP
        if ($lebar * $tinggi > (int) config('pengaduan.batas_piksel_proses', 40000000)) {
            return null;
        }

        $maks = (int) config('pengaduan.lebar_maks_gambar', 2000);
        $sudut = $this->sudutExif($file, $ekstensi);

        // Sudah ringan dan orientasinya benar → simpan berkas asli tanpa dikodekan ulang
        if ($sudut === 0 && max($lebar, $tinggi) <= $maks) {
            return null;
        }

        $img = @imagecreatefromstring($isi);
        if (! $img) {
            return null;
        }

        try {
            if ($sudut !== 0) {
                $diputar = @imagerotate($img, $sudut, 0);
                if ($diputar !== false) {
                    imagedestroy($img);
                    $img = $diputar;
                }
            }

            $img = $this->perkecil($img, $maks, $this->punyaAlpha($isi, $ekstensi));
            $hasil = $this->encode($img, $ekstensi);
        } finally {
            if ($img instanceof \GdImage) {
                imagedestroy($img);
            }
        }

        if ($hasil === '') {
            return null;
        }

        // Jangan sampai hasil olahan justru lebih besar dari berkas asli.
        // Bila rotasi EXIF diterapkan, hasil olahan tetap dipakai agar orientasi benar.
        if ($sudut === 0 && strlen($hasil) >= strlen($isi)) {
            return null;
        }

        return $hasil;
    }

    /**
     * Sudut putar berdasarkan metadata EXIF (hanya relevan untuk foto JPEG dari ponsel).
     */
    protected function sudutExif(UploadedFile $file, string $ekstensi): int
    {
        if (! in_array($ekstensi, ['jpg', 'jpeg'], true) || ! function_exists('exif_read_data')) {
            return 0;
        }

        $exif = @exif_read_data($file->getRealPath());
        if ($exif === false) {
            return 0;
        }

        return match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
    }

    /**
     * Apakah berkas sumber benar-benar memiliki kanal transparansi.
     * PNG tanpa alpha sengaja tidak diberi kanal alpha saat disimpan ulang,
     * karena menambah kanal membuat berkas membengkak tanpa manfaat.
     */
    protected function punyaAlpha(string $isi, string $ekstensi): bool
    {
        return match ($ekstensi) {
            // Byte ke-25 pada header PNG (IHDR) adalah color type; 4 & 6 mengandung alpha
            'png' => strlen($isi) > 25 && in_array(ord($isi[25]), [4, 6], true),
            'webp' => true,
            default => false,
        };
    }

    /**
     * Perkecil gambar sampai sisi terpanjang maksimal $maks piksel (rasio dipertahankan).
     */
    protected function perkecil(\GdImage $img, int $maks, bool $alpha): \GdImage
    {
        $lebar = imagesx($img);
        $tinggi = imagesy($img);
        $terpanjang = max($lebar, $tinggi);

        if ($terpanjang <= $maks) {
            return $img;
        }

        $skala = $maks / $terpanjang;
        $lebarBaru = max(1, (int) round($lebar * $skala));
        $tinggiBaru = max(1, (int) round($tinggi * $skala));

        $kanvas = imagecreatetruecolor($lebarBaru, $tinggiBaru);

        if ($alpha) {
            // Pertahankan transparansi
            imagealphablending($kanvas, false);
            imagesavealpha($kanvas, true);
            $transparan = imagecolorallocatealpha($kanvas, 0, 0, 0, 127);
            imagefilledrectangle($kanvas, 0, 0, $lebarBaru, $tinggiBaru, $transparan);
        } else {
            // Tanpa alpha, ratakan ke latar putih agar tidak ada kanal tambahan
            $putih = imagecolorallocate($kanvas, 255, 255, 255);
            imagefilledrectangle($kanvas, 0, 0, $lebarBaru, $tinggiBaru, $putih);
        }

        imagecopyresampled($kanvas, $img, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);
        imagedestroy($img);

        return $kanvas;
    }

    /**
     * Encode ulang gambar ke format yang sama dengan berkas asli.
     */
    protected function encode(\GdImage $img, string $ekstensi): string
    {
        $kualitas = (int) config('pengaduan.kualitas_gambar', 85);

        ob_start();

        match ($ekstensi) {
            'png' => imagepng($img, null, (int) config('pengaduan.kompresi_png', 6)),
            'webp' => function_exists('imagewebp') ? imagewebp($img, null, $kualitas) : imagejpeg($img, null, $kualitas),
            default => imagejpeg($img, null, $kualitas),
        };

        return (string) ob_get_clean();
    }
}
