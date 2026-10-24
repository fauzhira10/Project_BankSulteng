{{-- Modal Pop-up Cepat untuk Melihat Rincian Jurnal / Pengaduan Duplikat --}}
<div class="modal-backdrop fixed inset-0 bg-slate-900/65 z-50 backdrop-blur-xs hidden items-center justify-center p-4" id="modalDuplikatDetail" role="dialog" aria-modal="true" aria-labelledby="mdd_judul">
    <div class="modal-content bg-white rounded-2xl w-full max-w-[42rem] max-h-[90vh] shadow-2xl overflow-hidden flex flex-col animate-modal-in">
        <!-- Header Modal -->
        <div class="modal-header px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <h3 id="mdd_judul" class="text-base font-bold text-navy flex items-center gap-2 m-0">
                <svg class="w-5 h-5 text-brand-blue shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span id="mdd_header_title">Rincian Riwayat Jurnal Keluhan</span>
            </h3>
            <button type="button" class="btn-close-modal p-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors cursor-pointer" onclick="tutupModalDuplikatDetail()" title="Tutup Modal (Esc)">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Body Modal (Scrollable) -->
        <div class="modal-body p-6 overflow-y-auto grow text-[0.8125rem]">
            <!-- Card Ringkasan Singkat -->
            <div class="mb-5 p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <span class="text-[0.6875rem] font-bold text-slate-500 uppercase tracking-wider block">Nomor Tiket</span>
                    <span id="mdd_no_tiket" class="font-mono font-bold text-sm text-navy">-</span>
                </div>
                <div class="text-right">
                    <span class="text-[0.6875rem] font-bold text-slate-500 uppercase tracking-wider block mb-1">Status</span>
                    <span id="mdd_status_badge" class="badge badge-strip">-</span>
                </div>
            </div>

            <!-- Section 1: Informasi Nasabah -->
            <div class="detail-section mb-5">
                <div class="detail-section-title text-[0.75rem] font-bold text-brand-blue uppercase tracking-wider border-b border-slate-200 pb-1.5 mb-3 flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Informasi Nasabah & Identitas</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Nama Nasabah</span>
                        <span id="mdd_nama_nasabah" class="font-bold text-slate-800 text-sm">-</span>
                    </div>
                    <div>
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Nomor Rekening</span>
                        <span id="mdd_no_rekening" class="font-mono font-bold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Nomor Resi / Trace Number</span>
                        <span id="mdd_no_resi" class="font-mono font-bold text-brand-blue">-</span>
                    </div>
                    <div>
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Nomor Kartu ATM/Debit</span>
                        <span id="mdd_no_kartu" class="font-mono font-bold text-slate-800">-</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Kantor Cabang Pelapor</span>
                        <span id="mdd_cabang" class="font-semibold text-slate-800">-</span>
                    </div>
                </div>
            </div>

            <!-- Section 2: Informasi Transaksi & Finansial -->
            <div class="detail-section mb-5">
                <div class="detail-section-title text-[0.75rem] font-bold text-brand-blue uppercase tracking-wider border-b border-slate-200 pb-1.5 mb-3 flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <span>Informasi Transaksi & Finansial</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Jenis Transaksi</span>
                        <span id="mdd_jenis_transaksi" class="font-bold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Channel Transaksi</span>
                        <span id="mdd_channel" class="font-semibold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Nominal Transaksi</span>
                        <span id="mdd_nominal" class="font-bold text-emerald-700 text-sm">-</span>
                    </div>
                    <div>
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Biaya Admin</span>
                        <span id="mdd_biaya_admin" class="font-semibold text-slate-800">-</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Terminal / Mesin ATM</span>
                        <span id="mdd_terminal" class="font-semibold text-slate-800">-</span>
                    </div>
                </div>
            </div>

            <!-- Section 3: Waktu & Tanggal Proses -->
            <div class="detail-section mb-5">
                <div class="detail-section-title text-[0.75rem] font-bold text-brand-blue uppercase tracking-wider border-b border-slate-200 pb-1.5 mb-3 flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Waktu &amp; Tanggal Proses</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Tanggal Transaksi</span>
                        <span id="mdd_tgl_transaksi" class="font-semibold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Tanggal Terima</span>
                        <span id="mdd_tgl_terima" class="font-semibold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="text-[0.6875rem] font-semibold text-slate-500 block">Tanggal Selesai</span>
                        <span id="mdd_tgl_selesai" class="font-semibold text-slate-800">-</span>
                    </div>
                </div>
            </div>

            <!-- Section 4: Keterangan (Opsional) -->
            <div class="detail-section" id="mdd_keterangan_wrap">
                <div class="detail-section-title text-[0.75rem] font-bold text-brand-blue uppercase tracking-wider border-b border-slate-200 pb-1.5 mb-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span>Keterangan Keluhan</span>
                </div>
                <div id="mdd_keterangan" class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-700 leading-relaxed italic">
                    -
                </div>
            </div>
        </div>

        <!-- Footer Modal -->
        <div class="modal-footer px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-end items-center gap-2.5">
            <button type="button" class="px-5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-semibold text-xs transition-colors cursor-pointer" onclick="tutupModalDuplikatDetail()">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    function formatTglIndo(str) {
        if (!str || str === '-' || str === 'null') return '-';
        const d = new Date(str);
        if (isNaN(d.getTime())) return str;
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function formatRupiah(num) {
        const val = Number(num) || 0;
        return 'Rp ' + val.toLocaleString('id-ID');
    }

    /**
     * Ambil rincian satu baris lewat AJAX, lalu buka modalnya.
     *
     * Tombol pada panel keluhan berulang hanya membawa id. Rinciannya —
     * termasuk nomor rekening, nomor kartu, NIK, dan nomor HP — tidak lagi
     * tercetak di sumber halaman untuk seluruh baris sekaligus.
     */
    // Hasilnya disimpan per alamat, dan permintaannya dimulai sejak kursor
    // menyentuh tombol, supaya modalnya tidak terasa menunggu saat diklik.
    const simpananRincian = new Map();
    const rincianDimuat = new Map();

    function ambilRincian(url) {
        if (simpananRincian.has(url)) {
            return Promise.resolve(simpananRincian.get(url));
        }

        if (rincianDimuat.has(url)) {
            return rincianDimuat.get(url);
        }

        const permintaan = fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.ok ? r.json() : Promise.reject(new Error('Gagal memuat rincian')))
            .then(data => {
                if (data && data.id) simpananRincian.set(url, data);
                rincianDimuat.delete(url);

                return data;
            })
            .catch(err => {
                rincianDimuat.delete(url);
                throw err;
            });

        rincianDimuat.set(url, permintaan);

        return permintaan;
    }

    function alamatRincian(el) {
        if (el.dataset.jurnalId) {
            return '{{ url('/api/jurnal') }}/' + el.dataset.jurnalId;
        }

        if (el.dataset.pengaduanId) {
            return '{{ url('/api/pengaduan') }}/' + el.dataset.pengaduanId;
        }

        return null;
    }

    function ambilLaluBuka(url, lanjut) {
        ambilRincian(url)
            .then(data => {
                if (data && data.id) lanjut(data);
            })
            .catch(err => console.error('Gagal memuat rincian:', err));
    }

    // Pramuat. Panel keluhan berulang dirender ulang lewat AJAX sambil petugas
    // mengetik, jadi pendengarnya dipasang dengan delegasi pada document.
    ['mouseover', 'focusin', 'touchstart'].forEach(function (peristiwa) {
        document.addEventListener(peristiwa, function (e) {
            const tombol = e.target.closest ? e.target.closest('.js-rincian-duplikat') : null;
            if (!tombol) return;

            const url = alamatRincian(tombol);
            if (url) ambilRincian(url).catch(() => {});
        }, { passive: true });
    });

    window.bukaModalJurnalDuplikat = function(el) {
        let jurnal = null;
        try {
            if (typeof el === 'string') {
                jurnal = JSON.parse(el);
            } else if (el && el.dataset && el.dataset.jurnalId) {
                ambilLaluBuka('{{ url('/api/jurnal') }}/' + el.dataset.jurnalId, window.bukaModalJurnalDuplikat);
                return;
            } else if (typeof el === 'object') {
                jurnal = el;
            }
        } catch(e) {
            console.error('Gagal membaca data jurnal:', e);
            return;
        }

        if (!jurnal) return;

        const modal = document.getElementById('modalDuplikatDetail');
        if (!modal) return;

        document.getElementById('mdd_header_title').textContent = 'Rincian Riwayat Jurnal Keluhan';
        document.getElementById('mdd_no_tiket').textContent = jurnal.no_tiket || '-';

        // Badge Status
        const st = (jurnal.status || '-').toLowerCase().trim();
        let badgeClass = 'badge-strip';
        if (st === 'menunggu') badgeClass = 'badge-menunggu';
        else if (st === 'success') badgeClass = 'badge-success';
        else if (st === 'done') badgeClass = 'badge-done';
        else if (st === 'rejected') badgeClass = 'badge-rejected';

        const badgeEl = document.getElementById('mdd_status_badge');
        badgeEl.className = 'badge ' + badgeClass;
        badgeEl.textContent = jurnal.status || '-';

        // Informasi Nasabah
        document.getElementById('mdd_nama_nasabah').textContent = jurnal.nama_nasabah || '-';
        document.getElementById('mdd_no_rekening').textContent = jurnal.no_rekening || '-';
        document.getElementById('mdd_no_resi').textContent = jurnal.no_resi || '-';
        document.getElementById('mdd_no_kartu').textContent = jurnal.no_kartu || '-';

        let namaCabang = '-';
        if (jurnal.master_cabang) {
            const code = jurnal.master_cabang.kode_cabang;
            const name = jurnal.master_cabang.nama_cabang || '-';
            namaCabang = (code && name.toUpperCase().trim() !== 'CALL CENTER') ? `${code} - ${name}` : name;
        }
        document.getElementById('mdd_cabang').textContent = namaCabang;

        // Informasi Transaksi & Finansial
        document.getElementById('mdd_jenis_transaksi').textContent = jurnal.master_transaksi?.jenis_transaksi || '-';
        document.getElementById('mdd_channel').textContent = jurnal.master_transaksi?.channel || '-';
        document.getElementById('mdd_nominal').textContent = formatRupiah(jurnal.nominal_transaksi);
        
        const fee = Number(jurnal.biaya_admin ?? jurnal.master_transaksi?.biaya_admin) || 0;
        document.getElementById('mdd_biaya_admin').textContent = fee > 0 ? formatRupiah(fee) : '- (Rp 0)';
        document.getElementById('mdd_terminal').textContent = jurnal.terminal_transaksi || '-';

        // Waktu
        document.getElementById('mdd_tgl_transaksi').textContent = formatTglIndo(jurnal.tgl_transaksi);
        document.getElementById('mdd_tgl_terima').textContent = formatTglIndo(jurnal.tgl_terima);
        document.getElementById('mdd_tgl_selesai').textContent = formatTglIndo(jurnal.tgl_selesai);

        // Keterangan
        const ketWrap = document.getElementById('mdd_keterangan_wrap');
        const ketEl = document.getElementById('mdd_keterangan');
        if (jurnal.keterangan && jurnal.keterangan.trim() !== '' && jurnal.keterangan.trim() !== '-') {
            ketEl.textContent = jurnal.keterangan;
            ketWrap.style.display = '';
        } else {
            ketWrap.style.display = 'none';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex', 'show');
    };

    window.bukaModalPengaduanDuplikat = function(el) {
        let adu = null;
        try {
            if (typeof el === 'string') {
                adu = JSON.parse(el);
            } else if (el && el.dataset && el.dataset.pengaduanId) {
                ambilLaluBuka('{{ url('/api/pengaduan') }}/' + el.dataset.pengaduanId, window.bukaModalPengaduanDuplikat);
                return;
            } else if (typeof el === 'object') {
                adu = el;
            }
        } catch(e) {
            console.error('Gagal membaca data pengaduan:', e);
            return;
        }

        if (!adu) return;

        const modal = document.getElementById('modalDuplikatDetail');
        if (!modal) return;

        document.getElementById('mdd_header_title').textContent = 'Rincian Pengaduan CS Cabang';
        document.getElementById('mdd_no_tiket').textContent = adu.nomor_tiket || '-';

        // Badge Status Pengaduan
        const st = (adu.status || '-').toLowerCase().trim();
        let badgeClass = 'badge-strip';
        if (st === 'terkirim') badgeClass = 'badge-terkirim';
        else if (st === 'diterima') badgeClass = 'badge-diterima';
        else if (st === 'diproses') badgeClass = 'badge-diproses';
        else if (st === 'selesai') badgeClass = 'badge-selesai';
        else if (st === 'ditolak') badgeClass = 'badge-ditolak';

        const badgeEl = document.getElementById('mdd_status_badge');
        badgeEl.className = 'badge ' + badgeClass;
        badgeEl.textContent = adu.status || '-';

        // Informasi Nasabah
        document.getElementById('mdd_nama_nasabah').textContent = adu.nama_nasabah || '-';
        document.getElementById('mdd_no_rekening').textContent = adu.no_rekening || '-';
        document.getElementById('mdd_no_resi').textContent = adu.no_resi || '-';
        document.getElementById('mdd_no_kartu').textContent = adu.no_kartu || '-';

        let namaCabang = '-';
        if (adu.cabang) {
            namaCabang = (adu.cabang.kode_cabang ? adu.cabang.kode_cabang + ' - ' : '') + (adu.cabang.nama_cabang || '-');
        }
        document.getElementById('mdd_cabang').textContent = namaCabang;

        // Informasi Transaksi & Finansial
        document.getElementById('mdd_jenis_transaksi').textContent = adu.jenis_transaksi || '-';
        document.getElementById('mdd_channel').textContent = adu.channel || '-';
        document.getElementById('mdd_nominal').textContent = formatRupiah(adu.nominal_transaksi);
        document.getElementById('mdd_biaya_admin').textContent = '-';
        document.getElementById('mdd_terminal').textContent = adu.terminal || '-';

        // Waktu
        document.getElementById('mdd_tgl_transaksi').textContent = formatTglIndo(adu.tgl_transaksi);
        document.getElementById('mdd_tgl_terima').textContent = formatTglIndo(adu.created_at);
        document.getElementById('mdd_tgl_selesai').textContent = formatTglIndo(adu.tgl_selesai || '-');

        // Keterangan / Deskripsi
        const ketWrap = document.getElementById('mdd_keterangan_wrap');
        const ketEl = document.getElementById('mdd_keterangan');
        const ket = adu.deskripsi_keluhan || adu.keterangan || '';
        if (ket.trim() !== '' && ket.trim() !== '-') {
            ketEl.textContent = ket;
            ketWrap.style.display = '';
        } else {
            ketWrap.style.display = 'none';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex', 'show');
    };

    window.tutupModalDuplikatDetail = function() {
        const modal = document.getElementById('modalDuplikatDetail');
        if (modal) {
            modal.classList.remove('show', 'flex');
            modal.classList.add('hidden');
        }
    };

    // Close on Escape or click outside
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            tutupModalDuplikatDetail();
        }
    });

    document.addEventListener('click', function(e) {
        const modal = document.getElementById('modalDuplikatDetail');
        if (modal && e.target === modal) {
            tutupModalDuplikatDetail();
        }
    });
})();
</script>
