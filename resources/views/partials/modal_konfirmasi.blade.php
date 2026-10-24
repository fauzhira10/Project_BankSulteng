{{-- Modal konfirmasi bersama, menggantikan dialog bawaan browser (confirm()).

     Pemakaian: tambahkan atribut data pada <form> yang perlu dikonfirmasi.
     data-konfirmasi = judul (wajib, sekaligus penanda aktif)
     data-pesan      = kalimat penjelasan
     data-aksi       = label tombol lanjut (bawaan: "Ya, Lanjutkan")
     data-warna      = emerald | rose | amber | navy (bawaan: navy)
     data-ikon       = satu emoji (bawaan: ⚠️)
--}}
<div class="modal-backdrop fixed inset-0 bg-slate-900/65 z-50 backdrop-blur-xs hidden items-center justify-center p-4" id="modalKonfirmasi" role="dialog" aria-modal="true" aria-labelledby="mkJudul">
    <div class="bg-white rounded-2xl w-full max-w-[27.5rem] shadow-2xl overflow-hidden animate-modal-in">
        <div class="p-6 text-center">
            <div id="mkIkon" class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 text-[1.625rem] bg-slate-100">⚠️</div>
            <h3 id="mkJudul" class="text-lg font-bold text-navy mb-2">Konfirmasi</h3>
            <p id="mkPesan" class="text-[0.84375rem] text-slate-600 leading-relaxed"></p>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-center gap-3">
            <button type="button" id="mkBatal" class="px-5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-semibold text-[0.84375rem] transition-colors cursor-pointer min-w-[6.25rem]">
                Batal
            </button>
            <button type="button" id="mkLanjut" class="px-5 py-2 rounded-lg text-white font-semibold text-[0.84375rem] shadow-md transition-colors cursor-pointer min-w-[7.5rem] bg-navy hover:opacity-90">
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalKonfirmasi');
    const elIkon = document.getElementById('mkIkon');
    const elJudul = document.getElementById('mkJudul');
    const elPesan = document.getElementById('mkPesan');
    const btnBatal = document.getElementById('mkBatal');
    const btnLanjut = document.getElementById('mkLanjut');
    if (!modal) return;

    const gaya = {
        emerald: { ikon: 'bg-emerald-100', tombol: 'bg-emerald-600 hover:bg-emerald-700' },
        rose:    { ikon: 'bg-rose-100',    tombol: 'bg-rose-600 hover:bg-rose-700' },
        amber:   { ikon: 'bg-amber-100',   tombol: 'bg-amber-500 hover:bg-amber-600' },
        navy:    { ikon: 'bg-slate-100',   tombol: 'bg-navy hover:opacity-90' },
    };
    const semuaKelasIkon = Object.values(gaya).map(g => g.ikon);
    const semuaKelasTombol = Object.values(gaya).flatMap(g => g.tombol.split(' '));

    let formTertunda = null;

    function buka(form) {
        const d = form.dataset;
        const warna = gaya[d.warna] || gaya.navy;

        elIkon.textContent = d.ikon || '⚠️';
        elJudul.textContent = d.konfirmasi || 'Konfirmasi';
        elPesan.textContent = d.pesan || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        btnLanjut.textContent = d.aksi || 'Ya, Lanjutkan';

        elIkon.classList.remove(...semuaKelasIkon);
        elIkon.classList.add(warna.ikon);
        btnLanjut.classList.remove(...semuaKelasTombol);
        btnLanjut.classList.add(...warna.tombol.split(' '));

        formTertunda = form;
        modal.classList.add('show');
        setTimeout(() => btnLanjut.focus(), 60);
    }

    function tutup() {
        modal.classList.remove('show');
        formTertunda = null;
        window.dispatchEvent(new CustomEvent('modal-konfirmasi-ditutup'));
    }

    // Pemantauan lewat document (event delegation), bukan diikat satu per satu saat
    // halaman dimuat. Dengan begitu form yang atribut data-konfirmasi-nya baru
    // dipasang belakangan lewat JS (mis. peringatan keluhan berulang pada form
    // jurnal) tetap ikut terkonfirmasi.
    //
    // Sengaja memakai fase bubble, sehingga listener submit milik form itu sendiri
    // sempat berjalan lebih dulu; bila salah satunya sudah membatalkan pengiriman
    // (validasi lain gagal), dialog ini tidak ikut muncul.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (e.defaultPrevented) return;             // sudah dibatalkan validasi lain
        if (!form.dataset.konfirmasi) return;       // form ini memang tidak perlu konfirmasi
        if (form.dataset.konfirmasiLolos === '1') return; // sudah dikonfirmasi, biarkan terkirim

        e.preventDefault();
        buka(form);
    });

    btnLanjut.addEventListener('click', function () {
        if (!formTertunda) return;
        const form = formTertunda;
        tutup();
        form.dataset.konfirmasiLolos = '1';
        try {
            ['sessionStorage', 'localStorage'].forEach(storage => {
                const s = window[storage];
                if (s) {
                    Object.keys(s).forEach(key => {
                        if (key.startsWith('jurnal_input_draft')) s.removeItem(key);
                    });
                }
            });
        } catch (e) {}
        form.submit();
    });

    btnBatal.addEventListener('click', tutup);
    modal.addEventListener('click', function (e) {
        if (e.target === this) tutup();
    });
    window.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) tutup();
    });
})();
</script>
