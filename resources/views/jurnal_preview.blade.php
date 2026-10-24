@extends('layouts.app')

@section('title', 'Preview Jurnal Keluhan')
@section('page_title', 'Ringkasan Input Jurnal')
@section('page_subtitle', 'Data jurnal keluhan baru saja ditambahkan dan siap untuk dicetak')

@section('topbar_action')
    <a href="{{ route('jurnal.index') }}" class="inline-flex items-center gap-2 h-[2.375rem] px-3.5 rounded-lg border border-slate-300 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 text-xs font-semibold transition-colors">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="3" y1="9" x2="21" y2="9"></line>
            <line x1="9" y1="21" x2="9" y2="9"></line>
        </svg>
        <span>Ke Tabel Data Jurnal</span>
    </a>
    <a href="{{ route('jurnal.create') }}" class="inline-flex items-center gap-2 h-[2.375rem] px-3.5 rounded-lg bg-gradient-to-r from-brand-blue to-navy text-white hover:opacity-95 text-xs font-semibold shadow-sm transition-opacity">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Input Jurnal Baru</span>
    </a>
@endsection

@section('content')
<div class="max-w-[53.125rem] mx-auto">
    
    @if(session('success'))
    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg mb-6 shadow-sm flex items-start gap-3">
        <svg class="text-emerald-500 mt-0.5" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>
            <h4 class="text-emerald-800 font-bold text-sm">{{ session('success') }}</h4>
            <p class="text-emerald-700 text-xs mt-0.5">Berikut adalah ringkasan data yang baru saja Anda masukkan ke dalam sistem.</p>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-lg mb-6 shadow-sm flex items-start gap-3">
        <svg class="text-rose-500 mt-0.5 shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <h4 class="text-rose-800 font-bold text-sm">Pemberitahuan</h4>
            <p class="text-rose-700 text-xs mt-0.5">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    @php
        $isMenunggu = strtolower(trim($jurnal->status ?? '')) === 'menunggu';
    @endphp

    @if($isMenunggu)
    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg mb-6 shadow-sm flex items-start gap-3">
        <svg class="text-amber-600 mt-0.5 shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <h4 class="text-amber-800 font-bold text-sm">Status Masih Menunggu</h4>
            <p class="text-amber-700 text-xs mt-0.5">Formulir keluhan nasabah belum dapat dicetak karena status saat ini masih <strong>Menunggu</strong>. Silakan perbarui status penyelesaian keluhan (misalnya menjadi <em>Done / Selesai</em>, <em>Success</em>, atau <em>Rejected</em>) agar formulir cetak dapat diterbitkan.</p>
        </div>
    </div>
    @endif

    @if($jurnal->pengaduan)
        <div class="bg-sky-50 border border-sky-200 text-sky-900 rounded-xl p-4 mb-5 text-[0.8125rem] flex items-start gap-3 shadow-xs">
            <span class="text-lg leading-none">📨</span>
            <div class="grow min-w-0">
                <div class="font-bold">Sumber: Pengaduan CS <span class="font-mono">{{ $jurnal->pengaduan->nomor_tiket }}</span> — {{ $jurnal->pengaduan->labelCabang() }}</div>
                <div class="text-[0.75rem] text-sky-800 mt-0.5">Status di sisi CS saat ini: <strong>{{ $jurnal->pengaduan->status->label() }}</strong>.</div>
                @if($jurnal->pengaduan->lampirans->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach($jurnal->pengaduan->lampirans as $l)
                            <a href="{{ route('pengaduan.lampiran.show', [$jurnal->pengaduan, $l]) }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-white border border-sky-200 text-[0.71875rem] font-semibold text-navy hover:border-brand-blue" title="{{ $l->nama_asli }}">{{ $l->ikon() }} {{ $l->label() }} <span class="text-slate-500 font-normal">{{ $l->labelFormat() }}</span></a>
                        @endforeach
                    </div>
                @endif
                <a href="{{ route('admin.pengaduan.show', $jurnal->pengaduan) }}" class="inline-block mt-2 text-[0.75rem] font-bold text-brand-blue hover:underline">Lihat detail pengaduan & kronologi →</a>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs mb-6 overflow-hidden">
        <div class="px-6 py-4.5 border-b border-slate-200 flex items-center justify-between bg-slate-50 flex-wrap gap-2">
            <div class="text-[0.9375rem] font-bold text-navy flex items-center gap-2.5">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-brand-blue">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span>Rincian Jurnal Transaksi</span>
            </div>
            
            @php
                $statusClass = 'bg-slate-100 text-slate-700 border-slate-200';
                $st = strtolower(trim($jurnal->status));
                if($st === 'menunggu') $statusClass = 'bg-amber-50 text-amber-700 border-amber-200';
                if($st === 'success') $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                if($st === 'done') $statusClass = 'bg-blue-50 text-blue-700 border-blue-200';
                if($st === 'rejected') $statusClass = 'bg-rose-50 text-rose-700 border-rose-200';
            @endphp
            <div class="px-3 py-1 text-xs font-bold uppercase rounded border {{ $statusClass }}">
                Status: {{ $jurnal->status }}
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <!-- Data Nasabah -->
                <div>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Informasi Nasabah</h3>
                    <div class="space-y-3.5">
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-500 mb-0.5">Nama Nasabah</span>
                            <span class="text-sm font-semibold text-slate-800">{{ $jurnal->nama_nasabah }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-500 mb-0.5">No. Rekening</span>
                            <span class="text-sm font-semibold text-slate-800">{{ $jurnal->no_rekening }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-500 mb-0.5">No. Kartu ATM / HP</span>
                            <span class="text-sm font-semibold text-slate-800">{{ $jurnal->no_kartu }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-500 mb-0.5">No. Trace / Resi</span>
                            <span class="text-sm font-bold text-brand-blue">{{ $jurnal->no_resi }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-500 mb-0.5">No. Tiket CS</span>
                            <span class="text-sm font-semibold text-slate-800">{{ $jurnal->no_tiket }}</span>
                        </div>
                    </div>
                </div>

                <!-- Data Transaksi -->
                <div>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Data Transaksi</h3>
                    <div class="space-y-3.5">
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-500 mb-0.5">Jenis Transaksi (Channel)</span>
                            <span class="text-sm font-semibold text-slate-800">
                                {{ $jurnal->masterTransaksi->jenis_transaksi ?? '-' }} 
                                <span class="text-slate-500 font-normal">({{ $jurnal->masterTransaksi->channel ?? '-' }})</span>
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-500 mb-0.5">Kantor Cabang</span>
                            <span class="text-sm font-semibold text-slate-800">
                                @if($jurnal->masterCabang)
                                    {{ $jurnal->masterCabang->kode_cabang }} - {{ $jurnal->masterCabang->nama_cabang }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-500 mb-0.5">Terminal / Mesin</span>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-800">{{ $atmInfo['profil'] ?? $jurnal->terminal_transaksi }}</span>
                                @if(isset($atmInfo['id_luno']))
                                    <span class="text-[0.71875rem] font-mono text-slate-500 mt-0.5">ID: {{ $atmInfo['id_luno'] }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-500 mb-0.5">Nominal Transaksi</span>
                            <span class="text-[0.9375rem] font-bold text-emerald-600">Rp {{ number_format($jurnal->nominal_transaksi, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-500 mb-0.5">Biaya Admin</span>
                            <span class="text-sm font-semibold text-slate-800">
                                {{ $jurnal->biaya_admin > 0 ? 'Rp ' . number_format($jurnal->biaya_admin, 0, ',', '.') : '- (Rp 0)' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 border-t border-slate-100 pt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <div>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Timeline</h3>
                        <div class="space-y-3.5">
                            <div class="flex flex-col">
                                <span class="text-xs text-slate-500 mb-0.5">Tanggal Transaksi</span>
                                <span class="text-sm font-semibold text-slate-800">{{ \Carbon\Carbon::parse($jurnal->tgl_transaksi)->translatedFormat('d F Y') }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs text-slate-500 mb-0.5">Tanggal Terima Keluhan</span>
                                <span class="text-sm font-semibold text-slate-800">{{ \Carbon\Carbon::parse($jurnal->tgl_terima)->translatedFormat('d F Y') }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs text-slate-500 mb-0.5">Tanggal Selesai</span>
                                <span class="text-sm font-semibold text-slate-800">{{ $jurnal->tgl_selesai ? \Carbon\Carbon::parse($jurnal->tgl_selesai)->translatedFormat('d F Y') : '-' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Catatan Petugas</h3>
                        <div class="space-y-4">
                            <div class="flex flex-col">
                                <span class="text-xs text-slate-500 mb-1">Keterangan Keluhan</span>
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-sm text-slate-700 min-h-[2.5rem]">
                                    {{ $jurnal->permasalahan !== '-' && $jurnal->permasalahan ? $jurnal->permasalahan : 'Tidak ada keterangan permasalahan.' }}
                                </div>
                            </div>
                            
                            @php
                                $hasLog = !empty($jurnal->keterangan_log) && trim($jurnal->keterangan_log) !== '-' && trim($jurnal->keterangan_log) !== '' && trim(strtolower($jurnal->keterangan_log)) !== 'tidak ada keterangan tambahan.' && trim(strtolower($jurnal->keterangan_log)) !== 'tidak ada catatan log tambahan.';
                            @endphp
                            <div class="flex flex-col">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs text-slate-500">Keterangan Log / Kronologi</span>
                                    @if(!$hasLog)
                                        <span class="text-[0.71875rem] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded border border-amber-300">Log Belum Diisi</span>
                                    @endif
                                </div>
                                @if($hasLog)
                                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-sm text-slate-700 min-h-[3.75rem] leading-relaxed">
                                        {{ $jurnal->keterangan_log }}
                                    </div>
                                @else
                                    <div class="bg-amber-50/70 border border-dashed border-amber-300 rounded-lg p-3.5 text-xs text-amber-900 flex flex-col gap-2">
                                        <div class="flex items-start gap-2">
                                            <span class="text-amber-600 text-sm">⚠️</span>
                                            <span>Keterangan log hasil pemeriksaan mesin atau switching masih kosong. Lengkapi data ini agar dokumen dapat dicetak.</span>
                                        </div>
                                        <div>
                                            <a href="{{ route('jurnal.edit', $jurnal->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition-colors">
                                                <span>⚡ Edit & Isi Keterangan Log</span>
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 border-t border-slate-200 p-5 px-6 flex items-center justify-between flex-wrap gap-3">
            <a href="{{ route('jurnal.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                ← Kembali ke Data
            </a>
            
            <div class="flex items-center gap-2.5 flex-wrap">
                @if($isMenunggu)
                    <div class="inline-flex items-center gap-2 h-11 px-4 rounded-xl bg-amber-100/80 border border-amber-300 text-amber-900 text-xs font-semibold">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-amber-700">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Status Menunggu — Belum Dapat Dicetak 🔒</span>
                    </div>
                    <a href="{{ route('jurnal.edit', $jurnal->id) }}" class="inline-flex items-center gap-1.5 h-11 px-5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-md shadow-amber-600/20 transition-all hover:-translate-y-0.5">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        <span>⚡ Ubah Status Keluhan</span>
                    </a>
                @elseif($hasLog)
                    <a href="{{ route('jurnal.download', $jurnal->id) }}?format=normal" target="_blank" class="inline-flex items-center gap-2 h-11 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition-all hover:-translate-y-0.5">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        <span>Cetak Form Normal (Klaim Diterima)</span>
                    </a>
                    <a href="{{ route('jurnal.download', $jurnal->id) }}?format=penolakan" target="_blank" class="inline-flex items-center gap-2 h-11 px-5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md shadow-rose-600/20 transition-all hover:-translate-y-0.5">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                        </svg>
                        <span>Cetak Form Penolakan Klaim</span>
                    </a>
                @else
                    <a href="{{ route('jurnal.edit', $jurnal->id) }}" class="inline-flex items-center gap-2 h-11 px-6 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-lg shadow-amber-500/20 transition-all hover:-translate-y-0.5" title="Keterangan log masih kosong. Lengkapi catatan log terlebih dahulu untuk mencetak formulir.">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Lengkapi Log untuk Cetak 🔒</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
