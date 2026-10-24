{{-- Perapian nomor tiket saat petugas selesai mengetik.

     Pemakaian: @include('partials.skrip_nomor_tiket')

     Menyalin aturan App\Services\NomorTiketService::rapikan() supaya petugas
     langsung melihat bentuk akhirnya, bukan baru tahu setelah menyimpan.
     Hanya kenyamanan — server tetap penjaga terakhir, jadi tidak menjadi soal
     bila JavaScript mati.

     Elemen #no_tiket hanya ada bila nomornya memang diketik manual (jurnal input
     langsung). Bila jurnal berasal dari pengaduan CS, nomornya tampil sebagai
     <output> terkunci sehingga skrip ini tidak melakukan apa-apa.
--}}
@push('scripts')
<script>
(function () {
    const elTiket = document.getElementById('no_tiket');
    if (!elTiket) return;

    elTiket.addEventListener('blur', function () {
        const bersih = (this.value || '').trim();

        // "BS - 2026081347674" / "bs 2026081347674" → "BS-2026081347674".
        // Nomor manual berupa teks bebas seperti "PRO AKTIF" tidak tersentuh.
        const cocok = bersih.match(/^BS\s*-?\s*([\d\s]+)$/i);

        this.value = cocok ? 'BS-' + cocok[1].replace(/\s+/g, '') : bersih;
    });
})();
</script>
@endpush
