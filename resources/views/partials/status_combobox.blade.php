{{-- Input Status Keluhan: bisa diketik manual atau dipilih dari daftar standar.
     Pemakaian: @include('partials.status_combobox', ['nilai' => old('status', $jurnal->status ?? 'Menunggu')]) --}}
@php
    $nilaiStatus = trim((string) ($nilai ?? ''));
    $statusStandar = [
        '-'        => ['label' => '- (Belum Ditentukan)', 'kelas' => 'badge-strip',    'titik' => '#94a3b8'],
        'Menunggu' => ['label' => 'Menunggu',             'kelas' => 'badge-menunggu', 'titik' => '#d97706'],
        'Success'  => ['label' => 'Success',              'kelas' => 'badge-success',  'titik' => '#0284c7'],
        'Done'     => ['label' => 'Done',                 'kelas' => 'badge-done',     'titik' => '#059669'],
        'Rejected' => ['label' => 'Rejected',             'kelas' => 'badge-rejected', 'titik' => '#dc2626'],
    ];
@endphp

<div class="flex flex-col gap-1.5 relative" id="status_combobox">
    <div class="flex items-center justify-between">
        <label for="status_input" class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">
            Status Keluhan <span class="text-rose-600 font-bold">*</span>
        </label>
        <span class="text-[0.71875rem] text-slate-500 font-normal">Ketik bebas / pilih daftar</span>
    </div>

    <div class="flex items-center rounded-xl border border-slate-300 bg-white overflow-hidden focus-within:border-brand-blue focus-within:ring-3 focus-within:ring-brand-blue/15 transition-all duration-200" id="status_wrapper">
        <span class="pl-3.5 pr-0.5 flex items-center shrink-0" title="Warna label status">
            <span id="status_titik" class="w-2.5 h-2.5 rounded-full bg-slate-400 transition-colors"></span>
        </span>
        <input
            type="text"
            name="status"
            id="status_input"
            value="{{ $nilaiStatus }}"
            required
            maxlength="50"
            autocomplete="off"
            role="combobox"
            aria-expanded="false"
            aria-haspopup="listbox"
            aria-autocomplete="list"
            aria-controls="status_dropdown"
            placeholder="Contoh: Menunggu Konfirmasi Bank Lain"
            class="block w-full px-3 py-2.5 text-sm bg-white text-slate-800 placeholder-slate-400 border-0 focus:outline-none focus:ring-0"
        >
        <button
            type="button"
            id="status_toggle"
            tabindex="-1"
            title="Buka daftar status standar"
            aria-label="Buka daftar status standar"
            class="inline-flex items-center px-3 py-2.5 text-slate-500 hover:text-brand-blue transition-colors focus:outline-none cursor-pointer"
        >
            <svg id="status_chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200 pointer-events-none">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </button>
    </div>

    <span id="status_info" class="text-[0.71875rem] text-slate-500 leading-snug"></span>

    <div
        id="status_dropdown"
        role="listbox"
        class="hidden absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl shadow-slate-200/60 z-50 overflow-hidden py-1"
    >
        <div class="px-3.5 py-1.5 text-[0.71875rem] font-bold tracking-wider text-slate-500 uppercase bg-slate-50/80 flex items-center justify-between">
            <span>Status Standar</span>
            <span class="text-[0.71875rem] text-slate-500 font-normal lowercase">Gunakan ↑↓ Enter</span>
        </div>
        <div class="max-h-56 overflow-y-auto py-1" id="status_list">
            @foreach($statusStandar as $kode => $meta)
                <div
                    role="option"
                    id="status_opt_{{ $loop->index }}"
                    aria-selected="false"
                    class="status-item min-h-[2.625rem] px-3.5 py-2.5 text-sm text-slate-700 hover:bg-sky-50 hover:text-brand-blue flex items-center justify-between gap-2 cursor-pointer transition-colors"
                    data-value="{{ $kode }}"
                >
                    <span class="flex items-center gap-2.5 font-medium text-[0.84375rem]">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $meta['titik'] }};"></span>
                        {{ $meta['label'] }}
                    </span>
                    <span class="status-check hidden text-brand-blue font-bold text-sm">✓</span>
                </div>
            @endforeach
            <div id="status_no_match" class="hidden px-3.5 py-3 text-xs text-slate-500 text-center italic bg-slate-50/50">
                Tidak ada status standar yang cocok. Tekan Enter untuk memakai status yang Anda ketik.
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const wadah = document.getElementById('status_combobox');
    const wrapper = document.getElementById('status_wrapper');
    const input = document.getElementById('status_input');
    const dropdown = document.getElementById('status_dropdown');
    const chevron = document.getElementById('status_chevron');
    const tombol = document.getElementById('status_toggle');
    const titik = document.getElementById('status_titik');
    const info = document.getElementById('status_info');
    const kosong = document.getElementById('status_no_match');
    const daftar = Array.from((wadah || document).querySelectorAll('.status-item'));
    if (!wadah || !input) return;

    const warna = {
        '-': '#94a3b8',
        'menunggu': '#d97706',
        'success': '#0284c7',
        'done': '#059669',
        'rejected': '#dc2626',
    };
    let indeksAktif = -1;

    function nilaiBaku(teks) {
        return String(teks || '').trim().toLowerCase();
    }

    function perbaruiPenanda() {
        const baku = nilaiBaku(input.value);
        const standar = Object.prototype.hasOwnProperty.call(warna, baku);

        titik.style.backgroundColor = standar ? warna[baku] : '#7c3aed';

        if (input.value.trim() === '') {
            info.textContent = 'Status wajib diisi.';
            info.className = 'text-[0.71875rem] text-slate-500 leading-snug';
        } else if (standar) {
            info.textContent = 'Status standar. Ikut dihitung pada ringkasan statistik dan rekap laporan.';
            info.className = 'text-[0.71875rem] text-slate-500 leading-snug';
        } else {
            info.textContent = 'Status kustom akan disimpan apa adanya, namun tidak ikut dihitung pada kartu ringkasan statistik.';
            info.className = 'text-[0.71875rem] text-violet-700 leading-snug';
        }

        daftar.forEach(item => {
            const cocok = nilaiBaku(item.dataset.value) === baku;
            const centang = item.querySelector('.status-check');
            item.setAttribute('aria-selected', cocok ? 'true' : 'false');
            item.classList.toggle('bg-sky-50', cocok);
            item.classList.toggle('text-brand-blue', cocok);
            if (centang) centang.classList.toggle('hidden', !cocok);
        });
    }

    function terlihat() {
        return daftar.filter(item => !item.classList.contains('hidden'));
    }

    function saring(tampilkanSemua = false) {
        const kunci = tampilkanSemua ? '' : nilaiBaku(input.value);
        let jumlah = 0;

        daftar.forEach(item => {
            const cocok = kunci === '' || nilaiBaku(item.dataset.value).includes(kunci) || nilaiBaku(item.textContent).includes(kunci);
            item.classList.toggle('hidden', !cocok);
            if (cocok) jumlah++;
        });

        kosong.classList.toggle('hidden', jumlah > 0);
        indeksAktif = -1;
        sorotKeyboard();
    }

    function sorotKeyboard() {
        terlihat().forEach((item, i) => {
            const aktif = i === indeksAktif;
            item.classList.toggle('bg-slate-100', aktif);
            item.classList.toggle('ring-1', aktif);
            item.classList.toggle('ring-brand-blue/30', aktif);
            if (aktif) {
                item.scrollIntoView({ block: 'nearest' });
                input.setAttribute('aria-activedescendant', item.id);
            }
        });
    }

    function buka(tampilkanSemua = true) {
        dropdown.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
        chevron.classList.add('rotate-180');
        saring(tampilkanSemua);
        perbaruiPenanda();

        // Inisialisasi sorotan keyboard awal ke opsi yang saat ini aktif
        const opsi = terlihat();
        const currentBaku = nilaiBaku(input.value);
        const idx = opsi.findIndex(item => nilaiBaku(item.dataset.value) === currentBaku);
        if (idx >= 0) {
            indeksAktif = idx;
            sorotKeyboard();
        }
    }

    function tutup() {
        dropdown.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
        chevron.classList.remove('rotate-180');
        indeksAktif = -1;
        sorotKeyboard();
    }

    function pilih(nilai) {
        input.value = nilai;
        perbaruiPenanda();
        tutup();
    }

    input.addEventListener('focus', function () {
        buka(true);
        setTimeout(() => {
            try { input.select(); } catch (e) {}
        }, 30);
    });

    input.addEventListener('click', function () {
        if (dropdown.classList.contains('hidden')) {
            buka(true);
        }
    });

    if (wrapper) {
        wrapper.addEventListener('click', function (e) {
            if (e.target !== tombol && !tombol.contains(e.target)) {
                if (dropdown.classList.contains('hidden')) {
                    buka(true);
                }
                input.focus();
            }
        });
    }

    input.addEventListener('input', function () {
        saring(false);
        perbaruiPenanda();
        if (dropdown.classList.contains('hidden')) {
            dropdown.classList.remove('hidden');
            input.setAttribute('aria-expanded', 'true');
            chevron.classList.add('rotate-180');
        }
    });

    input.addEventListener('keydown', function (e) {
        const opsi = terlihat();

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (dropdown.classList.contains('hidden')) { buka(true); return; }
            if (opsi.length) { indeksAktif = (indeksAktif + 1) % opsi.length; sorotKeyboard(); }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (dropdown.classList.contains('hidden')) { buka(true); return; }
            if (opsi.length) { indeksAktif = (indeksAktif - 1 + opsi.length) % opsi.length; sorotKeyboard(); }
        } else if (e.key === 'Enter') {
            if (!dropdown.classList.contains('hidden')) {
                e.preventDefault();
                if (indeksAktif >= 0 && opsi[indeksAktif]) pilih(opsi[indeksAktif].dataset.value);
                else tutup();
            }
        } else if (e.key === 'Escape') {
            tutup();
        } else if (e.key === 'Tab') {
            tutup();
        }
    });

    daftar.forEach(item => {
        item.addEventListener('mousedown', function (e) {
            e.preventDefault();
            pilih(this.dataset.value);
        });
    });

    tombol.addEventListener('click', function (e) {
        e.stopPropagation();
        if (dropdown.classList.contains('hidden')) {
            buka(true);
            input.focus();
            setTimeout(() => {
                try { input.select(); } catch (e) {}
            }, 30);
        } else {
            tutup();
        }
    });

    document.addEventListener('click', function (e) {
        if (!wadah.contains(e.target)) tutup();
    });

    // Rapikan spasi berlebih sebelum dikirim
    const formStatus = input.closest('form');
    if (formStatus) {
        formStatus.addEventListener('submit', function () {
            input.value = input.value.trim().replace(/\s+/g, ' ');
        });
    }

    perbaruiPenanda();
})();
</script>
@endpush
