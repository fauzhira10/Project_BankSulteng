@extends('layouts.app')

@section('title', 'Serah Terima Penyelesaian Nasabah')
@section('page_title', 'Serah Terima Penyelesaian Pengaduan Nasabah')
@section('page_subtitle', 'Pilih tiket yang telah selesai untuk dicetak lembar serah terima pembukuan resmi.')

@push('styles')
<style>
/* ============================================================
   SERAH TERIMA — Halaman Pemilihan Tiket
   Bank Sulteng E-Jurnal Keluhan | Frontend
============================================================ */
@keyframes slideUpFade {
    from { transform: translateY(20px) translateX(-50%); opacity: 0; }
    to   { transform: translateY(0)    translateX(-50%); opacity: 1; }
}
@keyframes pulseGreen {
    0%,100% { box-shadow: 0 0 0 0 rgba(16,185,129,.5); }
    50%     { box-shadow: 0 0 0 8px rgba(16,185,129,0); }
}

#floatingSummary {
    position: fixed;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%) translateY(24px);
    z-index: 9999;
    min-width: 520px;
    max-width: 94vw;
    display: flex;
    align-items: center;
    gap: 14px;
    background: linear-gradient(135deg, #0f2a5e 0%, #1a4090 100%);
    color: #fff;
    border-radius: 18px;
    padding: 14px 22px;
    box-shadow: 0 10px 36px rgba(15,42,94,.5), 0 2px 8px rgba(0,0,0,.18);
    border: 1px solid rgba(255,255,255,.13);
    backdrop-filter: blur(6px);
    opacity: 0;
    pointer-events: none;
    transition: opacity .26s cubic-bezier(.4,0,.2,1),
                transform .26s cubic-bezier(.4,0,.2,1);
}
#floatingSummary.visible {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
    pointer-events: auto;
}
#fsBadgeCount {
    background: #10b981;
    color: #fff;
    font-weight: 800;
    font-size: .88rem;
    padding: 3px 13px;
    border-radius: 999px;
    min-width: 36px;
    text-align: center;
    animation: pulseGreen 2s ease-in-out infinite;
}
#fsBadgeTotal {
    font-size: .98rem;
    font-weight: 800;
    color: #6ee7b7;
    white-space: nowrap;
}
.fs-label { font-size: .8rem; color: rgba(255,255,255,.75); }
.fs-sep   { color: rgba(255,255,255,.3); margin: 0 4px; }
.btn-cetak-float {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    border: none;
    border-radius: 11px;
    padding: 10px 20px;
    font-size: .8rem;
    font-weight: 700;
    cursor: pointer;
    transition: filter .17s, transform .15s;
    white-space: nowrap;
    box-shadow: 0 3px 12px rgba(16,185,129,.4);
}
.btn-cetak-float:hover { filter: brightness(1.1); transform: scale(1.03); }

.cb-tiket {
    width: 1.1rem;
    height: 1.1rem;
    accent-color: #1e40af;
    cursor: pointer;
    transition: transform .13s;
}
.cb-tiket:hover { transform: scale(1.18); }
.tr-tiket       { transition: background .12s; cursor: pointer; }
.tr-tiket:hover { background: #f0f7ff !important; }
.tr-tiket.row-selected { background: #eff6ff !important; }
.tr-tiket.row-selected td:first-child { border-left: 3px solid #2563eb; }

.sla-ok   { background: #d1fae5; color: #065f46; }
.sla-warn { background: #fef3c7; color: #92400e; }
.sla-late { background: #fee2e2; color: #991b1b; }

.filter-inp {
    padding: .4rem .85rem;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    font-size: .8rem;
    transition: border-color .18s, box-shadow .18s;
    outline: none;
    background: #fff;
}
.filter-inp:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}

#btnCheckAll {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 11px;
    border-radius: 7px;
    border: 1.5px solid #94a3b8;
    background: #fff;
    font-size: .71rem;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    transition: border-color .17s, background .17s, color .17s;
    white-space: nowrap;
}
#btnCheckAll:hover { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; }
#btnCheckAll.all-checked { border-color: #10b981; background: #d1fae5; color: #065f46; }

.tbl-serah thead th { position: sticky; top: 0; z-index: 4; }
</style>
@endpush

@section('content')
<div class="max-w-[78.125rem] mx-auto space-y-5">

    {{-- === RINGKASAN GLOBAL === --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center gap-4 hover:shadow-md hover:-translate-y-0.5 transition-all">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Tiket Selesai</div>
                <div class="text-2xl font-extrabold text-navy mt-0.5">{{ number_format($ringkasan['total_tiket'] ?? 0, 0, ',', '.') }} Tiket</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center gap-4 hover:shadow-md hover:-translate-y-0.5 transition-all">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-brand-blue flex items-center justify-center shrink-0">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Akumulasi Nominal</div>
                <div class="text-2xl font-extrabold text-emerald-600 mt-0.5">{{ $ringkasan['total_nominal_rupiah'] ?? 'Rp 0' }}</div>
            </div>
        </div>
    </div>

    {{-- === FORM + TABEL === --}}
    <form action="{{ route('admin.serah-terima.cetak') }}" method="POST" target="_blank" id="formSerahTerima">
        @csrf
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">

            {{-- Filter Bar --}}
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <label for="tanggal_serah_terima" class="text-xs font-bold text-slate-600 whitespace-nowrap">📅 Per Tanggal:</label>
                            <input type="date" id="tanggal_serah_terima" name="tanggal_serah_terima" value="{{ date('Y-m-d') }}" class="filter-inp w-40">
                        </div>
                        <div class="hidden sm:block w-px h-5 bg-slate-200"></div>
                        <div class="relative">
                            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="inputSearch" placeholder="Cari nama / tiket / rekening…" class="filter-inp pl-8 w-56" autocomplete="off">
                        </div>
                    </div>
                    <button type="submit" id="btnCetakHeader"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-navy to-brand-blue text-white font-bold text-xs shadow-md hover:opacity-95 hover:scale-[1.02] transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                            disabled>
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        🖨️ Cetak Lembar Serah Terima
                    </button>
                </div>
            </div>

            {{-- Tabel --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs tbl-serah" id="tabelTiket">
                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4 w-10 text-center">
                                <button type="button" id="btnCheckAll" title="Pilih Semua / Batalkan Semua">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span id="lblCheckAll">Pilih Semua</span>
                                </button>
                            </th>
                            <th class="py-3 px-4">No. Tiket</th>
                            <th class="py-3 px-4">Nama Nasabah</th>
                            <th class="py-3 px-4">No. Rekening</th>
                            <th class="py-3 px-4 text-right">Nominal</th>
                            <th class="py-3 px-4 text-center">Status SLA</th>
                            <th class="py-3 px-4 text-center">Tgl. Selesai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="tbodyTiket">
                        @forelse($tikets as $t)
                        @php
                            $slaHari  = null;
                            if ($t->tgl_terima && $t->tgl_selesai) {
                                $slaHari = \Carbon\Carbon::parse($t->tgl_terima)->diffInDays(\Carbon\Carbon::parse($t->tgl_selesai));
                            }
                            $slaClass = 'sla-ok';  $slaLabel = 'Tepat Waktu';
                            if ($slaHari > 14)     { $slaClass = 'sla-late'; $slaLabel = 'Melewati SLA'; }
                            elseif ($slaHari > 7)  { $slaClass = 'sla-warn'; $slaLabel = 'Mendekati SLA'; }
                        @endphp
                        <tr class="tr-tiket"
                            data-id="{{ $t->id }}"
                            data-nominal="{{ $t->nominal_transaksi }}"
                            data-nama="{{ strtolower($t->nama_nasabah) }}"
                            data-tiket="{{ strtolower($t->no_tiket ?? '') }}"
                            data-rekening="{{ $t->no_rekening ?? '' }}">
                            <td class="py-3 px-4 text-center">
                                <input type="checkbox" name="ids[]" value="{{ $t->id }}" class="cb-tiket" id="cb_{{ $t->id }}" checked>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-navy text-[0.7rem]">{{ $t->no_tiket ?: '—' }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $t->nama_nasabah }}</td>
                            <td class="py-3 px-4 font-mono text-slate-600">{{ $t->no_rekening ?: '—' }}</td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-700 whitespace-nowrap">Rp {{ number_format($t->nominal_transaksi, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[0.65rem] font-bold uppercase {{ $slaClass }}">
                                    {{ $slaLabel }}@if($slaHari !== null) ({{ $slaHari }}h)@endif
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center text-slate-500">
                                {{ $t->tgl_selesai ? \Carbon\Carbon::parse($t->tgl_selesai)->locale('id')->translatedFormat('d M Y') : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-slate-400">
                                    <svg class="w-10 h-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                    <span class="text-sm font-medium">Belum ada tiket selesai yang siap diserahterimakan.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tikets->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $tikets->links() }}</div>
            @endif
        </div>
    </form>
</div>

{{-- ===================== FLOATING LIVE SUMMARY BAR ===================== --}}
<div id="floatingSummary" role="status" aria-live="polite">
    <span id="fsBadgeCount">0</span>
    <span class="fs-label">tiket dipilih</span>
    <span class="fs-sep">•</span>
    <span class="fs-label">Total:</span>
    <span id="fsBadgeTotal">Rp 0</span>
    <button type="button" class="btn-cetak-float ml-2" id="btnCetakFloat"
            onclick="document.getElementById('formSerahTerima').submit()">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        🖨️ Cetak Lembar Serah Terima
    </button>
</div>
@endsection

@push('scripts')
<script>
/* ============================================================
   SERAH TERIMA — Interactive JS
   Live Floating Summary + Check-All + Live Search
============================================================ */
(function () {
    'use strict';

    const tbody      = document.getElementById('tbodyTiket');
    const btnAll     = document.getElementById('btnCheckAll');
    const lblAll     = document.getElementById('lblCheckAll');
    const floatBar   = document.getElementById('floatingSummary');
    const badgeCount = document.getElementById('fsBadgeCount');
    const badgeTotal = document.getElementById('fsBadgeTotal');
    const btnHdr     = document.getElementById('btnCetakHeader');
    const srch       = document.getElementById('inputSearch');

    function cbs()      { return Array.from(tbody.querySelectorAll('.cb-tiket')); }
    function visible(c) { const r = c.closest('tr'); return r && r.style.display !== 'none'; }

    function rupiah(n)  { return 'Rp ' + new Intl.NumberFormat('id-ID').format(n); }

    function update() {
        const all      = cbs();
        const shown    = all.filter(visible);
        const checked  = shown.filter(c => c.checked);
        const count    = checked.length;
        const total    = checked.reduce((s, c) => s + parseFloat(c.closest('tr')?.dataset?.nominal ?? 0), 0);

        badgeCount.textContent = count;
        badgeTotal.textContent = rupiah(total);
        floatBar.classList.toggle('visible', count > 0);
        if (btnHdr) btnHdr.disabled = count === 0;

        const allChk = shown.length > 0 && shown.every(c => c.checked);
        btnAll.classList.toggle('all-checked', allChk);
        lblAll.textContent = allChk ? 'Batalkan Semua' : 'Pilih Semua';

        all.forEach(c => {
            const row = c.closest('tr');
            if (row) row.classList.toggle('row-selected', c.checked && visible(c));
        });
    }

    /* Checkbox change */
    tbody.addEventListener('change', e => { if (e.target.classList.contains('cb-tiket')) update(); });

    /* Klik baris = toggle checkbox */
    tbody.addEventListener('click', e => {
        if (e.target.classList.contains('cb-tiket')) return;
        const row = e.target.closest('tr.tr-tiket');
        if (!row) return;
        const cb = row.querySelector('.cb-tiket');
        if (cb) { cb.checked = !cb.checked; update(); }
    });

    /* Check-All */
    btnAll.addEventListener('click', () => {
        const shown   = cbs().filter(visible);
        const allChkd = shown.every(c => c.checked);
        shown.forEach(c => c.checked = !allChkd);
        update();
    });

    /* Live Search */
    srch.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        tbody.querySelectorAll('tr.tr-tiket').forEach(row => {
            const match = !q
                || (row.dataset.nama    ?? '').includes(q)
                || (row.dataset.tiket   ?? '').includes(q)
                || (row.dataset.rekening ?? '').includes(q);
            row.style.display = match ? '' : 'none';
        });
        update();
    });

    update(); // inisialisasi awal
    window.serahTerimaUpdate = update;
})();
</script>
@endpush
