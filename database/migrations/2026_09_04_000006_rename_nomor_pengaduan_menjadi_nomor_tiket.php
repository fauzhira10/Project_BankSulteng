<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor pengaduan kini menjadi nomor tiket keluhan resmi.
     *
     * Nomor dibuat saat CS cabang mengirim keluhan, lalu dibawa terus ke Jurnal
     * Keluhan, sehingga satu keluhan hanya memiliki satu nomor dari awal sampai
     * selesai. Formatnya berubah dari PGD-{kode cabang}-{tanggal}-{urut} menjadi
     * BS-{YYYYMMDD}{5 angka acak}.
     */
    public function up(): void
    {
        Schema::table('pengaduans', function (Blueprint $table) {
            $table->renameColumn('nomor_pengaduan', 'nomor_tiket');
        });

        // Ubah nomor lama berformat PGD-... menjadi format tiket BS-...
        $baris = DB::table('pengaduans')
            ->where('nomor_tiket', 'LIKE', 'PGD-%')
            ->get(['id', 'created_at']);

        foreach ($baris as $row) {
            $awalan = 'BS-'.Carbon::parse($row->created_at)->format('Ymd');

            do {
                $kandidat = $awalan.str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
                $bentrok = DB::table('pengaduans')->where('nomor_tiket', $kandidat)->exists()
                    || DB::table('jurnals')->where('no_tiket', $kandidat)->exists();
            } while ($bentrok);

            DB::table('pengaduans')->where('id', $row->id)->update(['nomor_tiket' => $kandidat]);
        }
    }

    /**
     * Catatan: nomor lama berformat PGD-... tidak dapat dipulihkan,
     * kolomnya hanya dikembalikan ke nama semula.
     */
    public function down(): void
    {
        Schema::table('pengaduans', function (Blueprint $table) {
            $table->renameColumn('nomor_tiket', 'nomor_pengaduan');
        });
    }
};
