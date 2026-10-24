@extends('layouts.app')

@section('title', 'Form Jurnal Keluhan')
@section('page_title', 'Input Jurnal Keluhan Nasabah')
@section('page_subtitle', 'Pencatatan, validasi anti-duplikat, dan penanganan keluhan transaksi nasabah Bank Sulteng')

@section('topbar_action')
    <a href="{{ route('jurnal.index') }}" class="inline-flex items-center gap-2 h-[2.375rem] px-3.5 rounded-lg border border-slate-300 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 text-xs font-semibold transition-colors">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="3" y1="9" x2="21" y2="9"></line>
            <line x1="9" y1="21" x2="9" y2="9"></line>
        </svg>
        <span>Lihat Data Keluhan</span>
    </a>
@endsection

@section('content')
@php
    $prefill = $prefill ?? [];
    $pengaduan = $pengaduan ?? null;
    $nominalAwal = (int) preg_replace('/[^\d]/', '', (string) old('nominal_transaksi', $prefill['nominal_transaksi'] ?? ''));
@endphp
<div class="max-w-[59.375rem] mx-auto">
    @if($pengaduan)
        <div class="bg-sky-50 border border-sky-200 text-sky-900 rounded-xl p-4 mb-5 text-[0.8125rem] flex items-start gap-3 shadow-xs">
            <span class="text-lg leading-none">📨</span>
            <div class="grow min-w-0">
                <div class="font-bold">Diisi otomatis dari pengaduan <span class="font-mono">{{ $pengaduan->nomor_tiket }}</span> — {{ $pengaduan->labelCabang() }}</div>
                <div class="text-[0.75rem] text-sky-800 mt-0.5">Pelapor: {{ $pengaduan->nama_pelapor }} &bull; Kategori: {{ $pengaduan->kategoriLengkap() }}. Periksa kembali seluruh isian dan lengkapi biaya admin, lalu simpan. Nomor tiket keluhan dibawa dari pengaduan ini, dan status di sisi CS otomatis menjadi <strong>Dalam Proses</strong>.</div>
                @if($pengaduan->lampirans->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach($pengaduan->lampirans as $l)
                            <a href="{{ route('pengaduan.lampiran.show', [$pengaduan, $l]) }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-white border border-sky-200 text-[0.71875rem] font-semibold text-navy hover:border-brand-blue" title="{{ $l->nama_asli }}">{{ $l->ikon() }} {{ $l->label() }} <span class="text-slate-500 font-normal">{{ $l->labelFormat() }}</span></a>
                        @endforeach
                    </div>
                @endif
                <a href="{{ route('admin.pengaduan.show', $pengaduan) }}" class="inline-block mt-2 text-[0.75rem] font-bold text-brand-blue hover:underline">Lihat detail pengaduan & kronologi →</a>
            </div>
        </div>
    @endif
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs mb-6 overflow-hidden">
        <div class="px-6 py-4.5 border-b border-slate-200 flex items-center justify-between bg-white flex-wrap gap-2">
            <div class="text-base font-bold text-navy flex items-center gap-2.5">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="12" y1="18" x2="12" y2="12"></line>
                    <line x1="9" y1="15" x2="15" y2="15"></line>
                </svg>
                <span>Formulir Pengaduan & Jurnal Transaksi</span>
            </div>
            <span class="text-xs text-slate-500">
                Semua Input Bertanda (<span class="text-rose-600 font-bold">*</span>) Wajib Diisi
            </span>
        </div>

        <div class="p-6">
            <form action="{{ route('jurnal.store') }}" method="POST">
                @csrf
                @if(!empty($prefill['pengaduan_id']))
                    <input type="hidden" name="pengaduan_id" value="{{ $prefill['pengaduan_id'] }}">
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- SECTION 1: IDENTITAS NASABAH -->
                    <div class="md:col-span-2 flex items-center gap-2.5 my-2.5 text-brand-blue text-[0.8125rem] font-bold uppercase tracking-wider after:content-[''] after:grow after:h-px after:bg-slate-200">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>1. Identitas Nasabah & Rekening</span>
                    </div>

                    <!-- Nama Nasabah -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">Nama Nasabah <span class="text-rose-600 font-bold">*</span></label>
                        <input type="text" name="nama_nasabah" id="nama_nasabah" value="{{ old('nama_nasabah', $prefill['nama_nasabah'] ?? '') }}" required placeholder="Contoh: BUDI SANTOSO" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 placeholder-slate-400 uppercase transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none" oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <!-- No. Rekening -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">No. Rekening <span class="text-rose-600 font-bold">*</span></label>
                        <input type="text" name="no_rekening" value="{{ old('no_rekening', $prefill['no_rekening'] ?? '') }}" required placeholder="Contoh: 00900000" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 placeholder-slate-400 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                    </div>

                    <!-- No. Resi / Trace Number -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">No. Resi / Trace Number <span class="text-rose-600 font-bold">*</span></label>
                        <input type="text" name="no_resi" id="no_resi" value="{{ old('no_resi', $prefill['no_resi'] ?? '') }}" required placeholder="Contoh: 00000000" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 placeholder-slate-400 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                    </div>

                    {{-- Peringatan keluhan berulang (Nama Nasabah + No. Resi). Diisi server-side
                         saat kembali dari validasi gagal, lalu diperbarui lewat AJAX saat mengetik. --}}
                    <div class="md:col-span-2" id="panelDuplikat">
                        @include('partials.panel_duplikat', ['duplikat' => session('duplikat')])
                    </div>
                    {{-- Penanda persetujuan petugas atas kombinasi Nama + Resi yang berulang.
                         Diisi otomatis oleh skrip; nilai dari session dipakai sebagai cadangan
                         bila JavaScript tidak aktif, sehingga simpan kedua kali tetap bisa lanjut. --}}
                    <input type="hidden" name="konfirmasi_duplikat" id="konfirmasi_duplikat" value="{{ old('konfirmasi_duplikat') ?: (session('duplikat')['token'] ?? '') }}">

                    <!-- Nomor Kartu -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">Nomor Kartu ATM/Debit <span class="text-rose-600 font-bold">*</span></label>
                        <input type="text" name="no_kartu" value="{{ old('no_kartu', $prefill['no_kartu'] ?? '') }}" required placeholder="Contoh: 6019xxxxxxxxxxxx" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 placeholder-slate-400 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                    </div>

                    <!-- Nomor Tiket: terkunci bila dari pengaduan CS, diketik manual bila jurnal langsung -->
                    @php $tiketDariPengaduan = $prefill['no_tiket'] ?? null; @endphp
                    <div class="md:col-span-2 flex flex-col gap-1.5">
                        <label for="no_tiket" class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">
                            Nomor Tiket Keluhan
                            @if($tiketDariPengaduan)
                                <span class="inline-flex items-center gap-1 ml-1 px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-[0.6875rem] font-semibold text-slate-600">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                    Dari pengaduan CS
                                </span>
                            @else
                                <span class="text-rose-600 font-bold">*</span>
                            @endif
                        </label>

                        @if($tiketDariPengaduan)
                            <div class="flex items-center rounded-xl border border-slate-300 bg-slate-50 overflow-hidden">
                                <span class="inline-flex items-center px-3.5 py-2.5 bg-slate-100 text-slate-500 font-semibold text-[0.75rem] border-r border-slate-200 select-none">Nomor</span>
                                <output class="block w-full px-3.5 py-2.5 text-sm font-mono font-bold text-navy tracking-wide">{{ $tiketDariPengaduan }}</output>
                            </div>
                            <span class="text-[0.71875rem] text-slate-500 leading-snug">
                                Nomor ini dibuat saat CS cabang mengirim keluhan dan tetap dipakai pada jurnal, sehingga satu keluhan bernomor sama dari awal sampai selesai.
                            </span>
                        @else
                            <input type="text" name="no_tiket" id="no_tiket" value="{{ old('no_tiket') }}" required maxlength="255" placeholder="Contoh: BS-2026090412345" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 placeholder-slate-400 font-mono transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                            <span class="text-[0.71875rem] text-slate-500 leading-snug">
                                Ketik nomor tiket sesuai berkas yang diterima. Nomor ini berasal dari proses sebelumnya (cabang, penyelia, lalu Divisi Literasi), jadi tidak dibuat dari Tanggal Terima Keluhan pada form ini.
                            </span>
                        @endif
                    </div>

                    <!-- SECTION 2: DETAIL TRANSAKSI & KANTOR CABANG -->
                    <div class="md:col-span-2 flex items-center gap-2.5 my-2.5 text-brand-blue text-[0.8125rem] font-bold uppercase tracking-wider after:content-[''] after:grow after:h-px after:bg-slate-200">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <span>2. Detail Transaksi & Lokasi</span>
                    </div>

                    <!-- Cabang Transaksi / Pelapor -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">Cabang Transaksi / Pelapor <span class="text-rose-600 font-bold">*</span></label>
                        <select name="master_cabang_id" id="cabang_id" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                            <option value="">-- Pilih Kantor Cabang --</option>
                            @foreach($cabangs as $c)
                                <option value="{{ $c->id }}" data-kode="{{ $c->kode_cabang ?? '' }}" data-nama="{{ $c->nama_cabang }}" {{ old('master_cabang_id', $prefill['master_cabang_id'] ?? '') == $c->id ? 'selected' : '' }}>
                                    {{ !empty($c->kode_cabang) && strtoupper(trim($c->nama_cabang)) !== 'CALL CENTER' ? $c->kode_cabang . ' - ' : '' }}{{ $c->nama_cabang }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Jenis Transaksi -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">Jenis Transaksi <span class="text-rose-600 font-bold">*</span></label>
                        <select name="master_transaksi_id" id="transaksi_id" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                            <option value="">-- Pilih Jenis Transaksi --</option>
                            @foreach($transaksis as $t)
                                <option value="{{ $t->id }}" {{ old('master_transaksi_id', $prefill['master_transaksi_id'] ?? '') == $t->id ? 'selected' : '' }}>
                                    {{ $t->jenis_transaksi }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Channel Transaksi (Dropdown) -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">Channel Transaksi <span class="text-rose-600 font-bold">*</span></label>
                        <select name="channel" id="channel" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                            <option value="">-- Pilih Channel Transaksi --</option>
                            @php
                                $channelsList = [
                                    'ATM LOKAL',
                                    'ATM BERSAMA',
                                    'ATM LINK',
                                    'FINNET',
                                    'MOBILE BANKING',
                                    'SMS BANKING',
                                    'DEBIT',
                                    'EDC BANK LAIN',
                                    'LAKU PANDAI',
                                    'QRIS',
                                    'CCTV',
                                    'ATM'
                                ];
                                $selectedChannel = old('channel', $prefill['channel'] ?? '');
                            @endphp
                            @foreach($channelsList as $ch)
                                <option value="{{ $ch }}" {{ $selectedChannel == $ch ? 'selected' : '' }}>{{ $ch }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Biaya Admin (Combobox Lengkap 7-Fase: Input Bebas + Auto-Format Rupiah + Suggestion + Keyboard Nav + Auto-Flip) -->
                    <div class="flex flex-col gap-1.5 relative" id="admin_fee_combobox_container">
                        <div class="flex items-center justify-between">
                            <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">
                                Biaya Admin (Rp) <span class="text-rose-600 font-bold">*</span>
                            </label>
                            <span id="admin_fee_hint" class="text-[0.71875rem] text-slate-500 font-normal">Ketik bebas / pilih preset</span>
                        </div>
                        
                        <div id="admin_fee_input_wrapper" class="flex items-center rounded-xl border border-slate-300 bg-white overflow-hidden focus-within:border-brand-blue focus-within:ring-3 focus-within:ring-brand-blue/15 transition-all duration-200">
                            <!-- Prefix Rp Badge -->
                            <span class="inline-flex items-center px-3.5 py-2.5 bg-slate-50 text-slate-500 font-semibold text-xs border-r border-slate-200 select-none">
                                Rp
                            </span>
                            
                            <!-- Input Tampilan Interaktif (Formatted, No Stepper) -->
                            @php
                                $initialAdminRaw = (float) old('biaya_admin', 0);
                                $initialAdminFormatted = number_format($initialAdminRaw, 0, ',', '.');
                            @endphp
                            <input 
                                type="text" 
                                id="biaya_admin_display" 
                                value="{{ $initialAdminFormatted }}" 
                                placeholder="0" 
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-haspopup="listbox"
                                aria-autocomplete="list"
                                aria-controls="admin_suggestion_dropdown"
                                class="block w-full px-3.5 py-2.5 text-sm bg-white text-slate-800 placeholder-slate-400 border-0 focus:outline-none focus:ring-0"
                            >

                            <!-- Hidden Input (Nilai numerik integer murni yang dikirim ke backend) -->
                            <input 
                                type="hidden" 
                                name="biaya_admin" 
                                id="biaya_admin" 
                                value="{{ $initialAdminRaw }}"
                            >

                            <!-- Tombol Trigger Dropdown Saran (Affordance) -->
                            <button 
                                type="button" 
                                id="btn_toggle_admin_dropdown" 
                                class="inline-flex items-center px-3 py-2.5 text-slate-500 hover:text-brand-blue transition-colors focus:outline-none cursor-pointer"
                                title="Buka pilihan preset biaya admin"
                                aria-label="Buka pilihan preset biaya admin"
                                tabindex="-1"
                            >
                                <svg id="icon_admin_chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200 pointer-events-none">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                        </div>

                        <!-- Pesan Error Validasi -->
                        <span id="admin_fee_error" class="hidden text-[0.71875rem] text-rose-600 font-medium mt-0.5">
                            * Biaya admin wajib diisi (minimal Rp 0).
                        </span>

                        <!-- Dropdown Menu Saran Melayang (Floating Popover dengan Auto-Flip) -->
                        <div 
                            id="admin_suggestion_dropdown" 
                            role="listbox"
                            class="hidden absolute left-0 right-0 bg-white border border-slate-200 rounded-xl shadow-xl shadow-slate-200/60 z-50 overflow-hidden py-1 divide-y divide-slate-100 transition-all"
                        >
                            <div class="px-3.5 py-1.5 text-[0.71875rem] font-bold tracking-wider text-slate-500 uppercase bg-slate-50/80 flex items-center justify-between">
                                <span>Nilai Umum / Preset Standar</span>
                                <span class="text-[0.71875rem] text-slate-500 font-normal lowercase">Gunakan ↑↓ Enter</span>
                            </div>
                            <div class="max-h-56 overflow-y-auto py-1" id="admin_suggestion_list">
                                @php
                                    $adminPresets = [
                                        0 => 'Rp 0 (Bebas Biaya / Gratis)',
                                        1000 => 'Rp 1.000',
                                        1500 => 'Rp 1.500',
                                        1750 => 'Rp 1.750',
                                        2000 => 'Rp 2.000',
                                        2500 => 'Rp 2.500',
                                        2750 => 'Rp 2.750',
                                        3000 => 'Rp 3.000',
                                        6500 => 'Rp 6.500',
                                        7500 => 'Rp 7.500',
                                    ];
                                @endphp
                                @foreach($adminPresets as $val => $lbl)
                                    <div 
                                        role="option"
                                        id="admin_opt_{{ $val }}"
                                        aria-selected="false"
                                        class="admin-suggestion-item min-h-[2.75rem] px-3.5 py-2.5 text-sm text-slate-700 hover:bg-sky-50 hover:text-brand-blue flex items-center justify-between cursor-pointer transition-colors"
                                        data-value="{{ $val }}"
                                        data-label="{{ $lbl }}"
                                    >
                                        <span class="font-medium text-[0.84375rem]">{{ $lbl }}</span>
                                        <span class="admin-check hidden text-brand-blue font-bold text-sm">✓</span>
                                    </div>
                                @endforeach
                                <div id="admin_no_match" class="hidden px-3.5 py-3 text-xs text-slate-500 text-center italic bg-slate-50/50">
                                    Tidak ada preset yang cocok. Tekan Enter atau klik di luar untuk menyimpan nominal kustom.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nominal Transaksi -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">Biaya / Nominal Transaksi (Rp) <span class="text-rose-600 font-bold">*</span></label>
                        <div class="flex items-center rounded-xl border border-slate-300 bg-white overflow-hidden focus-within:border-brand-blue focus-within:ring-3 focus-within:ring-brand-blue/15 transition-all duration-200">
                            <span class="inline-flex items-center px-3.5 py-2.5 bg-slate-50 text-slate-500 font-semibold text-xs border-r border-slate-200 select-none">
                                Rp
                            </span>
                            <input 
                                type="text" 
                                id="nominal_display" 
                                value="{{ $nominalAwal > 0 ? number_format($nominalAwal, 0, ',', '.') : '' }}" 
                                required 
                                inputmode="numeric" 
                                placeholder="Contoh: 1.000.000" 
                                autocomplete="off" 
                                class="block w-full px-3.5 py-2.5 text-sm bg-white text-slate-800 placeholder-slate-400 border-0 focus:outline-none focus:ring-0"
                            >
                            <input 
                                type="hidden" 
                                name="nominal_transaksi" 
                                id="nominal_transaksi" 
                                value="{{ $nominalAwal > 0 ? $nominalAwal : '' }}"
                            >
                        </div>
                    </div>

                    <!-- Terminal Transaksi / Mesin ATM -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">
                            Terminal Transaksi / Mesin ATM <span class="text-rose-600 font-bold">*</span>
                        </label>
                        <select name="terminal_transaksi" id="terminal_transaksi" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                            <option value="">-- Pilih Mesin ATM / Terminal --</option>
                        </select>
                        <span id="terminalHelperText" class="text-[0.71875rem] text-slate-500">
                            Pilih mesin ATM langsung atau pilih kantor cabang terlebih dahulu.
                        </span>
                    </div>

                    <!-- SECTION 3: WAKTU, STATUS & KRONOLOGI -->
                    <div class="md:col-span-2 flex items-center gap-2.5 my-2.5 text-brand-blue text-[0.8125rem] font-bold uppercase tracking-wider after:content-[''] after:grow after:h-px after:bg-slate-200">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>3. Waktu & Status Penanganan</span>
                    </div>

                    <!-- Tanggal Transaksi -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">Tanggal Transaksi Bermasalah <span class="text-rose-600 font-bold">*</span></label>
                        <input type="date" name="tgl_transaksi" id="tgl_transaksi" value="{{ old('tgl_transaksi', $prefill['tgl_transaksi'] ?? '') }}" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                    </div>

                    <!-- Tanggal Terima -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">Tanggal Terima Keluhan <span class="text-rose-600 font-bold">*</span></label>
                        <input type="date" name="tgl_terima" id="tgl_terima" value="{{ old('tgl_terima', $prefill['tgl_terima'] ?? date('Y-m-d')) }}" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                    </div>

                    <!-- Tanggal Selesai -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1">
                            Tanggal Selesai Penanganan 
                             <span class ="text-rose-600 font-bold">*</span>
                        </label>
                        <input type="date" name="tgl_selesai" value="{{ old('tgl_selesai') }}" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                    </div>

                    <!-- Status (bisa diketik manual atau pilih dari daftar standar) -->
                    @include('partials.status_combobox', ['nilai' => old('status', 'Menunggu')])

                    <!-- Keterangan Keluhan -->
                    <div class="md:col-span-2 flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700">Keterangan Keluhan</label>
                        <input type="text" name="permasalahan" value="{{ old('permasalahan', $prefill['permasalahan'] ?? '') }}" placeholder="Contoh: TARIK TUNAI ATM LOKAL GAGAL, SALDO TERDEBET" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 placeholder-slate-400 uppercase transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none" oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <!-- Keterangan Log -->
                    <div class="md:col-span-2 flex flex-col gap-1.5">
                        <label class="font-semibold text-[0.8125rem] text-slate-700">Keterangan Log / Catatan Kronologi Keluhan</label>
                        <textarea name="keterangan_log" placeholder="Tuliskan catatan, hasil pemeriksaan log, atau tindak lanjut petugas di sini..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 placeholder-slate-400 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none resize-y min-h-[5.625rem]">{{ old('keterangan_log') }}</textarea>
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-slate-200 flex items-center justify-end gap-3">
                    <button type="reset" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-[0.84375rem] font-semibold bg-slate-100 text-slate-700 border border-slate-300 hover:bg-slate-200 hover:text-slate-900 transition-colors cursor-pointer">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                            <path d="M3 3v5h5"></path>
                        </svg>
                        <span>Reset Form</span>
                    </button>
                    <button type="submit" id="btnSimpanJurnal" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-[0.84375rem] font-semibold bg-gradient-to-r from-brand-blue to-navy text-white hover:opacity-95 hover:shadow-lg shadow-brand-blue/25 transition-all cursor-pointer">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <span>Simpan Jurnal Keluhan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

{{-- Pengaduan sumber dikecualikan supaya jurnal yang sedang dibuat dari pengaduan CS
     tidak dilaporkan sebagai kembaran pengaduannya sendiri. --}}
@include('partials.skrip_duplikat', ['abaikanPengaduanId' => $prefill['pengaduan_id'] ?? null])
@include('partials.skrip_nomor_tiket')

@push('scripts')
<script>
    // Logic Auto-fill AJAX saat Jenis Transaksi dipilih (Channel)
    let currentChannel = '';
    let currentJenisTransaksiText = '';

    // Inisialisasi Data & Elemen
    const transaksiEl = document.getElementById('transaksi_id');
    const channelSelect = document.getElementById('channel');
    const terminalSelect = document.getElementById('terminal_transaksi');
    const helperText = document.getElementById('terminalHelperText');
    const atmsGrouped = @json($atmsGrouped);
    const initialTerminalVal = @json(old('terminal_transaksi', $prefill['terminal_transaksi'] ?? ''));

    // ==========================================
    // BIAYA ADMIN COMBOBOX COMPONENT (7-PHASE)
    // ==========================================
    const adminDisplayInput = document.getElementById('biaya_admin_display');
    const adminHiddenInput = document.getElementById('biaya_admin');
    const adminDropdown = document.getElementById('admin_suggestion_dropdown');
    const adminChevron = document.getElementById('icon_admin_chevron');
    const adminContainer = document.getElementById('admin_fee_combobox_container');
    const adminErrorEl = document.getElementById('admin_fee_error');
    const adminNoMatch = document.getElementById('admin_no_match');
    const adminSuggestionItems = document.querySelectorAll('.admin-suggestion-item');
    const btnToggleAdmin = document.getElementById('btn_toggle_admin_dropdown');

    const numberFormatter = new Intl.NumberFormat('id-ID');
    let currentActiveIndex = -1;

    function formatNumberID(num) {
        if (isNaN(num) || num === null || num === '') return '0';
        return numberFormatter.format(Number(num));
    }

    function parseCleanDigits(str) {
        if (str === null || str === undefined) return 0;
        const cleaned = String(str).replace(/[^\d]/g, '');
        return cleaned === '' ? 0 : parseInt(cleaned, 10);
    }

    function setAdminFeeValue(val, syncDisplay = true) {
        const num = isNaN(val) ? 0 : Number(val);
        if (adminHiddenInput) adminHiddenInput.value = num;
        if (syncDisplay && adminDisplayInput) {
            adminDisplayInput.value = formatNumberID(num);
        }
        hideAdminError();
        highlightActiveAdminPreset();
    }

    function calculateDropdownPosition() {
        if (!adminContainer || !adminDropdown) return;
        const rect = adminContainer.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const dropdownHeight = 250;

        if (spaceBelow < dropdownHeight && rect.top > dropdownHeight) {
            // Auto-Flip ke atas jika ruang bawah tidak cukup
            adminDropdown.style.top = 'auto';
            adminDropdown.style.bottom = 'calc(100% + 6px)';
        } else {
            // Normal ke bawah
            adminDropdown.style.bottom = 'auto';
            adminDropdown.style.top = 'calc(100% + 6px)';
        }
    }

    function openAdminDropdown() {
        if (!adminDropdown) return;
        calculateDropdownPosition();
        adminDropdown.classList.remove('hidden');
        if (adminDisplayInput) adminDisplayInput.setAttribute('aria-expanded', 'true');
        if (adminChevron) adminChevron.classList.add('rotate-180');
        filterAdminSuggestions(adminDisplayInput ? adminDisplayInput.value : '');
    }

    function closeAdminDropdown() {
        if (!adminDropdown) return;
        adminDropdown.classList.add('hidden');
        if (adminDisplayInput) adminDisplayInput.setAttribute('aria-expanded', 'false');
        if (adminChevron) adminChevron.classList.remove('rotate-180');
        currentActiveIndex = -1;
        updateItemKeyboardHighlight();
    }

    function toggleAdminDropdown() {
        if (adminDropdown && adminDropdown.classList.contains('hidden')) {
            openAdminDropdown();
            if (adminDisplayInput) adminDisplayInput.focus();
        } else {
            closeAdminDropdown();
        }
    }

    function highlightActiveAdminPreset() {
        const currentVal = adminHiddenInput ? parseInt(adminHiddenInput.value, 10) : 0;
        adminSuggestionItems.forEach(item => {
            const itemVal = parseInt(item.getAttribute('data-value'), 10);
            const check = item.querySelector('.admin-check');
            if (itemVal === currentVal) {
                item.classList.add('bg-sky-50', 'text-brand-blue');
                item.setAttribute('aria-selected', 'true');
                if (check) check.classList.remove('hidden');
            } else {
                item.classList.remove('bg-sky-50', 'text-brand-blue');
                item.setAttribute('aria-selected', 'false');
                if (check) check.classList.add('hidden');
            }
        });
    }

    function filterAdminSuggestions(query) {
        const rawDigits = String(query).replace(/[^\d]/g, '');
        let visibleCount = 0;

        adminSuggestionItems.forEach(item => {
            const itemVal = item.getAttribute('data-value');
            const itemLabel = item.getAttribute('data-label') || '';
            const match = rawDigits === '' || itemVal.includes(rawDigits) || itemLabel.toLowerCase().includes(String(query).toLowerCase());
            
            if (match) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        if (adminNoMatch) {
            if (visibleCount === 0) {
                adminNoMatch.classList.remove('hidden');
            } else {
                adminNoMatch.classList.add('hidden');
            }
        }
        highlightActiveAdminPreset();
    }

    function getVisibleItems() {
        return Array.from(adminSuggestionItems).filter(item => !item.classList.contains('hidden'));
    }

    function updateItemKeyboardHighlight() {
        const visibleItems = getVisibleItems();
        visibleItems.forEach((item, idx) => {
            if (idx === currentActiveIndex) {
                item.classList.add('bg-slate-100', 'ring-1', 'ring-brand-blue/30');
                item.scrollIntoView({ block: 'nearest' });
                if (adminDisplayInput) adminDisplayInput.setAttribute('aria-activedescendant', item.id);
            } else {
                item.classList.remove('bg-slate-100', 'ring-1', 'ring-brand-blue/30');
            }
        });
    }

    const adminWrapper = document.getElementById('admin_fee_input_wrapper');

    function showAdminError(msg) {
        if (adminWrapper) {
            adminWrapper.classList.add('border-rose-500', 'ring-2', 'ring-rose-500/20');
            adminWrapper.classList.remove('border-slate-300');
        }
        if (adminErrorEl) {
            if (msg) adminErrorEl.textContent = msg;
            adminErrorEl.classList.remove('hidden');
        }
    }

    function hideAdminError() {
        if (adminWrapper) {
            adminWrapper.classList.remove('border-rose-500', 'ring-2', 'ring-rose-500/20');
            adminWrapper.classList.add('border-slate-300');
        }
        if (adminErrorEl) {
            adminErrorEl.classList.add('hidden');
        }
    }

    // Event Listeners Input
    if (adminDisplayInput) {
        adminDisplayInput.addEventListener('focus', function() {
            const num = parseCleanDigits(this.value);
            this.value = num === 0 ? '' : num;
            openAdminDropdown();
        });

        adminDisplayInput.addEventListener('input', function() {
            const digits = this.value.replace(/[^\d]/g, '');
            this.value = digits;
            const parsed = digits === '' ? 0 : parseInt(digits, 10);
            if (adminHiddenInput) adminHiddenInput.value = parsed;
            filterAdminSuggestions(digits);
            hideAdminError();
        });

        adminDisplayInput.addEventListener('blur', function() {
            const parsed = parseCleanDigits(this.value);
            setAdminFeeValue(parsed, true);
            setTimeout(closeAdminDropdown, 180);
        });

        adminDisplayInput.addEventListener('keydown', function(e) {
            const visibleItems = getVisibleItems();

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (adminDropdown && adminDropdown.classList.contains('hidden')) {
                    openAdminDropdown();
                } else if (visibleItems.length > 0) {
                    currentActiveIndex = (currentActiveIndex + 1) % visibleItems.length;
                    updateItemKeyboardHighlight();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (visibleItems.length > 0) {
                    currentActiveIndex = (currentActiveIndex - 1 + visibleItems.length) % visibleItems.length;
                    updateItemKeyboardHighlight();
                }
            } else if (e.key === 'Enter') {
                if (currentActiveIndex >= 0 && visibleItems[currentActiveIndex]) {
                    e.preventDefault();
                    const val = visibleItems[currentActiveIndex].getAttribute('data-value');
                    setAdminFeeValue(val, true);
                    closeAdminDropdown();
                } else {
                    const parsed = parseCleanDigits(this.value);
                    setAdminFeeValue(parsed, true);
                    closeAdminDropdown();
                }
            } else if (e.key === 'Escape') {
                closeAdminDropdown();
            } else if (e.key === 'Tab') {
                closeAdminDropdown();
            }
        });
    }

    // Klik Item Suggestion
    adminSuggestionItems.forEach(item => {
        item.addEventListener('mousedown', function(e) {
            e.preventDefault();
            const val = this.getAttribute('data-value');
            setAdminFeeValue(val, true);
            closeAdminDropdown();
        });
    });

    if (btnToggleAdmin) {
        btnToggleAdmin.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleAdminDropdown();
        });
    }

    // Dismiss saat klik di luar
    document.addEventListener('click', function(e) {
        if (adminContainer && !adminContainer.contains(e.target)) {
            closeAdminDropdown();
        }
    });

    // ==========================================
    // AUTO-SAVE & RESTORE DRAFT FORMULIR (SESSIONSTORAGE)
    // ==========================================
    const DRAFT_KEY = 'jurnal_input_draft' + (@json($prefill['pengaduan_id'] ?? null) ? '_' + @json($prefill['pengaduan_id'] ?? '') : '');
    const draftInputs = [
        'nama_nasabah',
        'no_rekening',
        'no_resi',
        'no_kartu',
        'no_tiket',
        'master_cabang_id',
        'master_transaksi_id',
        'channel',
        'nominal_transaksi',
        'biaya_admin',
        'terminal_transaksi',
        'tgl_transaksi',
        'tgl_terima',
        'tgl_selesai',
        'status',
        'permasalahan',
        'keterangan_log'
    ];

    function isPageReload() {
        try {
            const navEntries = window.performance && window.performance.getEntriesByType ? window.performance.getEntriesByType('navigation') : null;
            if (navEntries && navEntries.length > 0) {
                return navEntries[0].type === 'reload';
            }
            if (window.performance && window.performance.navigation) {
                return window.performance.navigation.type === 1;
            }
        } catch (e) {}
        return false;
    }

    let saveDraftTimer = null;
    let isSubmitting = false;

    function scheduleSaveDraft() {
        if (isSubmitting) return;
        clearTimeout(saveDraftTimer);
        saveDraftTimer = setTimeout(saveDraft, 250);
    }

    function saveDraft() {
        if (!formEl || isSubmitting) return;
        const draft = {};
        let hasAnyData = false;

        draftInputs.forEach(name => {
            const el = formEl.elements[name];
            if (el) {
                const val = (el.value || '').trim();
                draft[name] = el.value;
                if (val !== '' && !['status'].includes(name)) {
                    hasAnyData = true;
                }
            }
        });

        if (hasAnyData) {
            try {
                sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
            } catch (e) {
                console.warn('Gagal menyimpan draft jurnal:', e);
            }
        } else {
            clearDraft();
        }
    }

    function clearDraft() {
        try {
            sessionStorage.removeItem(DRAFT_KEY);
            localStorage.removeItem(DRAFT_KEY);
            ['sessionStorage', 'localStorage'].forEach(storage => {
                const s = window[storage];
                if (s) {
                    Object.keys(s).forEach(key => {
                        if (key.startsWith('jurnal_input_draft')) s.removeItem(key);
                    });
                }
            });
        } catch (e) {}
    }

    function restoreDraft() {
        try {
            // Jika halaman di-refresh (F5 / tombol reload browser), bersihkan draft dan jangan pulihkan
            if (isPageReload()) {
                clearDraft();
                return false;
            }

            const hasServerErrors = @json($errors->any());
            if (hasServerErrors) return false;

            const raw = sessionStorage.getItem(DRAFT_KEY) || localStorage.getItem(DRAFT_KEY);
            if (!raw) return false;
            const draft = JSON.parse(raw);
            if (!draft || typeof draft !== 'object') return false;

            let restoredCount = 0;

            draftInputs.forEach(name => {
                const el = formEl.elements[name];
                if (el && draft[name] !== undefined && draft[name] !== null) {
                    if (el.disabled || el.readOnly) return;
                    const draftVal = draft[name];
                    if (draftVal !== '') {
                        el.value = draftVal;
                        restoredCount++;
                    }
                }
            });

            if (restoredCount > 0) {
                // Pulihkan format tampilan nominal
                if (draft.nominal_transaksi !== undefined && draft.nominal_transaksi !== '') {
                    if (nominalHidden) nominalHidden.value = draft.nominal_transaksi;
                    if (nominalDisplay) {
                        const num = Number(draft.nominal_transaksi);
                        nominalDisplay.value = !isNaN(num) && num > 0 ? fmtNominal.format(num) : draft.nominal_transaksi;
                    }
                }

                // Pulihkan biaya admin
                if (draft.biaya_admin !== undefined && draft.biaya_admin !== '') {
                    setAdminFeeValue(draft.biaya_admin, true);
                }

                // Pulihkan terminal transaksi
                if (draft.terminal_transaksi) {
                    populateAtmDropdown(draft.terminal_transaksi);
                }

                // Picu pengecekan duplikat jika nama atau no resi terisi
                const elNama = document.getElementById('nama_nasabah');
                if (elNama && elNama.value) {
                    elNama.dispatchEvent(new Event('input', { bubbles: true }));
                }

                return true;
            }
        } catch (e) {
            console.warn('Gagal memulihkan draft:', e);
        }
        return false;
    }

    // Validasi Form saat submit & Auto-Save
    const formEl = document.querySelector('form');
    if (formEl) {
        formEl.addEventListener('input', scheduleSaveDraft);
        formEl.addEventListener('change', scheduleSaveDraft);

        const btnSimpan = document.getElementById('btnSimpanJurnal') || formEl.querySelector('button[type="submit"]');
        if (btnSimpan) {
            btnSimpan.addEventListener('click', function() {
                clearTimeout(saveDraftTimer);
            });
        }

        formEl.addEventListener('submit', function(e) {
            if (adminHiddenInput && (adminHiddenInput.value === '' || isNaN(adminHiddenInput.value) || Number(adminHiddenInput.value) < 0)) {
                e.preventDefault();
                showAdminError('* Biaya admin wajib diisi (minimal Rp 0).');
                if (adminDisplayInput) adminDisplayInput.focus();
                return;
            }
            clearTimeout(saveDraftTimer);
            isSubmitting = true;
            clearDraft();
        });

        // Tombol Reset Form di bagian bawah
        const btnReset = formEl.querySelector('button[type="reset"]');
        if (btnReset) {
            btnReset.addEventListener('click', function() {
                clearTimeout(saveDraftTimer);
                clearDraft();
                setTimeout(() => {
                    setAdminFeeValue(0, true);
                    if (nominalHidden) nominalHidden.value = '';
                    if (nominalDisplay) nominalDisplay.value = '';
                    populateAtmDropdown('');
                    const elNama = document.getElementById('nama_nasabah');
                    if (elNama) elNama.dispatchEvent(new Event('input', { bubbles: true }));
                }, 50);
            });
        }
    }

    function loadDetailTransaksi(id) {
        if(id) {
            fetch('/api/transaksi/' + id)
                .then(response => response.json())
                .then(data => {
                    if (channelSelect && data.channel) {
                        channelSelect.value = data.channel.toUpperCase().trim();
                    }
                    if (data && data.biaya_admin !== undefined) {
                        setAdminFeeValue(data.biaya_admin, true);
                    }
                })
                .catch(err => {
                    console.error('Gagal memuat detail transaksi:', err);
                });
        }
    }

    function populateAtmDropdown(targetValue = '') {
        const currentSelected = targetValue || (terminalSelect ? terminalSelect.value : '');
        terminalSelect.innerHTML = '';

        // Default Placeholder
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = '-- Pilih Mesin ATM / Terminal --';
        terminalSelect.appendChild(defaultOpt);

        // Selalu tampilkan seluruh mesin ATM terkelompok per cabang
        Object.keys(atmsGrouped).forEach(kode => {
            const atms = atmsGrouped[kode];
            if (atms && atms.length > 0) {
                const optGroup = document.createElement('optgroup');
                const namaCabang = atms[0].cabang || '';
                optGroup.label = (namaCabang && namaCabang.toUpperCase() !== 'CALL CENTER') ? `${kode} - ${namaCabang}` : (namaCabang || `Cabang ${kode}`);
                atms.forEach(atm => {
                    const opt = document.createElement('option');
                    opt.value = atm.value;
                    opt.textContent = atm.label;
                    opt.setAttribute('data-luno', atm.id_luno || '');
                    opt.setAttribute('data-profil', atm.profil || '');
                    opt.setAttribute('data-lokasi', atm.lokasi || '');
                    opt.setAttribute('data-kode-cabang', kode || '');
                    if (currentSelected && (currentSelected === atm.value || currentSelected.trim() === atm.value.trim() || currentSelected.includes(atm.profil))) {
                        opt.selected = true;
                    }
                    optGroup.appendChild(opt);
                });
                terminalSelect.appendChild(optGroup);
            }
        });

        // Channel non-ATM
        const generalGroup = document.createElement('optgroup');
        generalGroup.label = 'Channel Non-ATM';
        ['MOBILE BANKING', 'ATM BANK LAIN', 'SMS BANKING', 'EDC', 'EDC BANK LAIN'].forEach(ch => {
            const opt = document.createElement('option');
            opt.value = ch;
            opt.textContent = ch;
            if (currentSelected && currentSelected.toUpperCase().trim() === ch) {
                opt.selected = true;
            }
            generalGroup.appendChild(opt);
        });
        terminalSelect.appendChild(generalGroup);

        if (helperText) helperText.textContent = 'Daftar seluruh mesin ATM Bank Sulteng lengkap (Terkelompok berdasarkan cabang).';
    }

    if (transaksiEl) {
        transaksiEl.addEventListener('change', function() {
            loadDetailTransaksi(this.value);
        });
    }

    // ---------- Format Titik Ribuan Otomatis untuk Nominal Transaksi ----------
    const nominalDisplay = document.getElementById('nominal_display');
    const nominalHidden = document.getElementById('nominal_transaksi');
    const fmtNominal = new Intl.NumberFormat('id-ID');

    function syncNominal() {
        if (!nominalDisplay || !nominalHidden) return;
        const digits = (nominalDisplay.value || '').replace(/[^\d]/g, '');
        nominalHidden.value = digits;
        nominalDisplay.value = digits ? fmtNominal.format(Number(digits)) : '';
    }

    if (nominalDisplay) {
        nominalDisplay.addEventListener('input', syncNominal);
        nominalDisplay.addEventListener('blur', syncNominal);
    }

    // Jalankan saat load pertama kali
    window.addEventListener('DOMContentLoaded', function() {
        populateAtmDropdown(initialTerminalVal);
        highlightActiveAdminPreset();
        syncNominal();
        if (transaksiEl && transaksiEl.value && channelSelect && !channelSelect.value) {
            loadDetailTransaksi(transaksiEl.value);
        }
        // Pulihkan draft jika ada data yang tersimpan sebelumnya
        restoreDraft();
    });

    window.addEventListener('pageshow', function(e) {
        isSubmitting = false;
        const raw = sessionStorage.getItem(DRAFT_KEY) || localStorage.getItem(DRAFT_KEY);
        const hasServerErrors = @json($errors->any());
        // Jika kembali dari cache navigasi (bfcache) dan tidak ada draft, pastikan isian bersih
        if (!raw && !hasServerErrors && e.persisted) {
            if (formEl) formEl.reset();
            setAdminFeeValue(0, true);
            if (nominalHidden) nominalHidden.value = '';
            if (nominalDisplay) nominalDisplay.value = '';
            populateAtmDropdown('');
        }
    });

    window.addEventListener('modal-konfirmasi-ditutup', function() {
        isSubmitting = false;
    });
</script>
@endpush