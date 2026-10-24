{{-- Skrip pemeriksaan keluhan berulang (Nama Nasabah + No. Resi).

     Dipakai bersama oleh jurnal_form, jurnal_edit, dan form pengaduan CS:
     @include('partials.skrip_duplikat')
     @include('partials.skrip_duplikat', ['abaikanJurnalId' => $jurnal->id])
     @include('partials.skrip_duplikat', ['informatif' => true])

     Elemen yang wajib ada di halaman: #nama_nasabah, #no_resi, #panelDuplikat.
     Opsional: #tgl_transaksi, #konfirmasi_duplikat, dan tombol simpan #btnSimpanJurnal.

     Mode informatif (dipakai CS cabang) hanya menampilkan panel: tidak menonaktifkan
     tombol dan tidak meminta konfirmasi, sebab CS harus tetap bisa menerima keluhan
     nasabah yang datang ke cabang. Penyaringnya ada di sisi Admin Pusat.

     Dialog konfirmasinya memakai partials/modal_konfirmasi.blade.php yang sudah
     disertakan sekali di layout.
--}}
@push('scripts')
<script>
(function () {
    const elNama  = document.getElementById('nama_nasabah');
    const elResi  = document.getElementById('no_resi');
    const elTgl   = document.getElementById('tgl_transaksi');
    const panel   = document.getElementById('panelDuplikat');
    const elToken = document.getElementById('konfirmasi_duplikat');
    const btnSimpan = document.getElementById('btnSimpanJurnal');
    const formEl  = elToken ? elToken.closest('form') : null;
    const abaikanJurnalId = @json($abaikanJurnalId ?? null);
    const abaikanPengaduanId = @json($abaikanPengaduanId ?? null);
    const informatif = @json($informatif ?? false);

    if (!elNama || !elResi || !panel) return;
    if (!informatif && (!elToken || !formEl)) return;

    let timer = null;
    let permintaanKe = 0;   // penanda urutan, supaya balasan lama tidak menimpa yang baru

    function aktifkanSimpan(boleh) {
        if (!btnSimpan) return;
        btnSimpan.disabled = !boleh;
        btnSimpan.classList.toggle('opacity-50', !boleh);
        btnSimpan.classList.toggle('cursor-not-allowed', !boleh);
    }

    function pasangKonfirmasi(jumlah) {
        formEl.dataset.konfirmasi = 'Keluhan ini sudah pernah ditangani';
        formEl.dataset.pesan = 'Ditemukan ' + jumlah + ' catatan dengan Nama Nasabah dan No. Resi yang sama. '
            + 'Pastikan ini benar-benar keluhan yang berbeda sebelum disimpan.';
        formEl.dataset.aksi = 'Saya paham, tetap simpan';
        formEl.dataset.warna = 'amber';
        formEl.dataset.ikon = '⚠️';
    }

    function lepasKonfirmasi() {
        delete formEl.dataset.konfirmasi;
        delete formEl.dataset.pesan;
        delete formEl.dataset.aksi;
        delete formEl.dataset.warna;
        delete formEl.dataset.ikon;
        delete formEl.dataset.konfirmasiLolos;
    }

    function terapkan(data) {
        panel.innerHTML = data.html || '';

        // Mode informatif berhenti di sini: panel tampil, alur simpan tidak diganggu.
        if (informatif) return;

        if (data.tingkat === 'kembar') {
            // Kombinasi ini dilarang index unik database, jadi tidak bisa dilanjutkan.
            elToken.value = '';
            lepasKonfirmasi();
            aktifkanSimpan(false);
            return;
        }

        if (data.tingkat === 'berulang') {
            elToken.value = data.token || '';
            pasangKonfirmasi(data.jumlah || 0);
            aktifkanSimpan(true);
            return;
        }

        elToken.value = '';
        lepasKonfirmasi();
        aktifkanSimpan(true);
    }

    function periksaDuplikat() {
        const nama = (elNama.value || '').trim();
        const resi = (elResi.value || '').trim();

        if (nama === '' || resi === '') {
            terapkan({ tingkat: 'aman', html: '' });
            return;
        }

        const params = new URLSearchParams({ nama: nama, resi: resi, tgl: elTgl ? (elTgl.value || '') : '' });
        if (abaikanJurnalId) params.append('abaikan', abaikanJurnalId);
        if (abaikanPengaduanId) params.append('abaikan_pengaduan', abaikanPengaduanId);

        const nomor = ++permintaanKe;

        fetch('{{ route('api.duplikat.periksa') }}?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(data => {
                if (nomor !== permintaanKe) return;   // sudah ada permintaan yang lebih baru
                terapkan(data);
            })
            .catch(err => {
                // Gangguan jaringan tidak boleh menghalangi petugas menyimpan —
                // pemeriksaan yang menentukan tetap dilakukan di sisi server.
                console.error('Gagal memeriksa keluhan berulang:', err);
            });
    }

    function jadwalkan() {
        clearTimeout(timer);
        timer = setTimeout(periksaDuplikat, 400);
    }

    [elNama, elResi, elTgl].forEach(el => {
        if (!el) return;
        el.addEventListener('input', jadwalkan);
        el.addEventListener('change', jadwalkan);
        el.addEventListener('blur', periksaDuplikat);
    });

    // Panel hasil validasi server sudah tercetak saat halaman dimuat; selaraskan
    // status tombol & penanda konfirmasinya dengan panel tersebut.
    window.addEventListener('DOMContentLoaded', function () {
        if (informatif) return;

        if (panel.querySelector('[data-tingkat="kembar"]')) {
            aktifkanSimpan(false);
        } else if (panel.querySelector('[data-tingkat="berulang"]')) {
            pasangKonfirmasi(panel.querySelector('[data-tingkat="berulang"]').dataset.jumlah || 0);
        }
    });
})();
</script>
@endpush
