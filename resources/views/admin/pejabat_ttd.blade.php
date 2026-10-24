@extends('layouts.app')

@section('title', 'Daftar Tanda Tangan')
@section('page_title', 'Daftar Tanda Tangan')
@section('page_subtitle', 'Daftar pejabat dan tanda tangan digital untuk 4 slot pengesahan dokumen keluhan nasabah')

@section('content')
<div class="max-w-[75rem] mx-auto">

    @if(session('success'))
    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg mb-6 shadow-sm flex items-start gap-3">
        <svg class="text-emerald-500 mt-0.5 shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>
            <h4 class="text-emerald-800 font-bold text-sm">{{ session('success') }}</h4>
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
            <h4 class="text-rose-800 font-bold text-sm">{{ session('error') }}</h4>
        </div>
    </div>
    @endif

    {{-- 4 Grid Slot Pejabat --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach(\App\Models\MasterPejabatTtd::DAFTAR_SLOT as $slotNum => $slotInfo)
            @php
                $pejabats = $pejabatsGrouped->get($slotNum, collect());
                $pejabatUtama = $pejabats->firstWhere('is_default', true) ?? $pejabats->first();
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden flex flex-col">
                {{-- Header Slot --}}
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-2">
                    <div>
                        <div class="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">{{ $slotInfo['kelompok'] }}</div>
                        <h3 class="text-[0.9375rem] font-extrabold text-navy flex items-center gap-2">
                            <span>{{ $slotInfo['label'] }}</span>
                        </h3>
                    </div>
                    <button type="button" onclick="bukaModalTambah({{ $slotNum }}, '{{ addslashes($slotInfo['label']) }}', '{{ addslashes($slotInfo['default_jabatan']) }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 hover:text-blue-900 border border-blue-200 hover:border-blue-300 transition-all cursor-pointer shadow-2xs group" title="Tambah staff baru untuk slot ini">
                        <svg class="w-3.5 h-3.5 text-blue-600 group-hover:text-blue-900 group-hover:rotate-90 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Tambah Staff</span>
                    </button>
                </div>

                {{-- Daftar Pejabat di Slot Ini --}}
                <div class="p-5 flex-1 flex flex-col gap-4">
                    @if($pejabats->isEmpty())
                        <div class="text-center py-8 text-slate-400 text-sm">
                            Belum ada pejabat terdaftar untuk slot ini.<br>
                            <span class="text-xs text-slate-500">Nilai default: <strong>{{ $slotInfo['default_nama'] }}</strong></span>
                        </div>
                    @else
                        @foreach($pejabats as $p)
                            <div class="p-3.5 rounded-xl border {{ $p->is_default ? 'border-brand-blue/40 bg-sky-50/40 ring-1 ring-brand-blue/20' : 'border-slate-200 bg-slate-50/50' }} transition-all">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0 pr-2">
                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                            <span class="font-extrabold text-[0.875rem] text-navy tracking-tight">{{ $p->nama }}</span>
                                            @if($p->is_default)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[0.6875rem] font-extrabold bg-emerald-500 text-white shadow-xs">
                                                    ★ UTAMA (DEFAULT)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[0.6875rem] font-bold bg-slate-200 text-slate-700">
                                                    Cadangan / PLT
                                                </span>
                                            @endif

                                            @if(!$p->is_aktif)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[0.6875rem] font-bold bg-rose-100 text-rose-700">
                                                    Nonaktif
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-[0.75rem] text-slate-600 line-clamp-2">{{ $p->jabatan }}</div>
                                    </div>

                                    {{-- Pratinjau Tanda Tangan --}}
                                    <div class="shrink-0" style="width: 140px; height: 65px; min-width: 140px; max-width: 140px; border: 1px solid #cbd5e1; border-radius: 10px; background-color: #ffffff; display: flex; align-items: center; justify-content: center; padding: 4px 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04);" title="Pratinjau Tanda Tangan {{ $p->nama }}">
                                        @if($p->ttd_image)
                                            <img src="{{ $p->ttd_image }}" alt="TTD {{ $p->nama }}" style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; display: block; margin: auto; pointer-events: none;">
                                        @else
                                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;">
                                                <svg style="width: 16px; height: 16px; color: #cbd5e1;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
                                                    <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
                                                </svg>
                                                <span style="font-size: 10px; color: #94a3b8; font-style: italic;">Belum ada TTD</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Tombol Aksi --}}
                                <div class="mt-3 pt-2.5 border-t border-slate-200/80 flex items-center justify-between gap-2 text-[0.71875rem]">
                                    <div>
                                        @if(!$p->is_default)
                                            <form action="{{ route('admin.pejabat-ttd.set_default', $p->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.71875rem] font-bold text-amber-800 bg-amber-50/80 hover:bg-amber-100 hover:text-amber-900 border border-amber-200/80 shadow-xs transition-all cursor-pointer group" title="Jadikan pejabat utama (default) untuk slot ini">
                                                    <svg class="w-3.5 h-3.5 text-amber-500 fill-amber-400 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                                    </svg>
                                                    <span>Jadikan Utama</span>
                                                </button>
                                            </form>
                                        @else
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.71875rem] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-xs">
                                                <span class="flex h-2 w-2 relative">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                                </span>
                                                <span>Pejabat Utama</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick='bukaModalEdit(@json($p))' class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.71875rem] font-bold text-slate-700 bg-white hover:bg-slate-50 hover:text-brand-blue border border-slate-300 hover:border-brand-blue/50 shadow-xs transition-all cursor-pointer group" title="{{ $p->isDefaultMaster() ? 'Perbarui berkas tanda tangan digital' : 'Edit pejabat atau perbarui tanda tangan' }}">
                                            <svg class="w-3.5 h-3.5 text-slate-500 group-hover:text-brand-blue transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                            </svg>
                                            <span>{{ $p->isDefaultMaster() ? 'Edit TTD' : 'Edit' }}</span>
                                        </button>
                                        @if($p->isDeletable())
                                            <button type="button" onclick="bukaModalKonfirmasiHapus({{ $p->id }}, '{{ addslashes($p->nama) }}', '{{ addslashes($slotInfo['label']) }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[0.71875rem] font-bold text-rose-700 bg-rose-50/70 hover:bg-rose-100 hover:text-rose-800 border border-rose-200/80 shadow-xs transition-all cursor-pointer group" title="Hapus staff ini">
                                                <svg class="w-3.5 h-3.5 text-rose-500 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endforeach
    </div>

</div>

{{-- MODAL EDIT PEJABAT --}}
{{-- MODAL EDIT / TAMBAH PEJABAT --}}
<div id="modalPejabat" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col" style="width: 100%; max-width: 500px; max-height: 90vh; margin: auto;">
        {{-- Header Modal --}}
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span id="modalIcon" class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 flex items-center justify-center font-bold text-sm">✏️</span>
                <h3 id="modalTitle" class="text-[0.9375rem] font-bold text-slate-900">
                    Edit Pejabat & Tanda Tangan
                </h3>
            </div>
            <button type="button" onclick="tutupModalPejabat()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition-colors cursor-pointer" title="Tutup (Esc)">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <form id="formPejabat" method="POST" action="{{ route('admin.pejabat-ttd.store') }}" enctype="multipart/form-data" class="overflow-y-auto p-6 flex flex-col gap-4">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            {{-- Alert Khusus Pejabat Default Terkunci --}}
            <div id="alertPejabatLocked" class="hidden p-3 rounded-xl bg-amber-50 border border-amber-200/90 text-amber-900 text-xs flex items-start gap-2.5 shadow-2xs">
                <span class="text-base shrink-0 mt-0.5 leading-none">🔒</span>
                <div class="leading-relaxed">
                    <strong class="font-bold">Nama & Jabatan Terkunci:</strong> Pejabat ini merupakan pejabat default sistem. Hanya berkas tanda tangan digital yang dapat diperbarui.
                </div>
            </div>

            {{-- Pilihan Slot --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Peruntukan Slot Dokumen <span class="text-rose-500">*</span></label>
                <select name="slot" id="inputSlot" required class="w-full h-10 px-3 border border-slate-300 rounded-xl text-sm bg-white focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 focus:outline-none transition-colors">
                    @foreach(\App\Models\MasterPejabatTtd::DAFTAR_SLOT as $num => $info)
                        <option value="{{ $num }}">{{ $info['label'] }} — {{ $info['role'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Nama Pejabat --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-700">Nama Lengkap & Gelar Pejabat <span id="reqNama" class="text-rose-500">*</span></label>
                    <span id="badgeLockedNama" class="hidden text-[0.6875rem] font-bold text-amber-700 bg-amber-100/90 border border-amber-300/80 px-2 py-0.5 rounded-md">🔒 Terkunci</span>
                </div>
                <input type="text" name="nama" id="inputNama" required placeholder="Contoh: DIANA, ST atau H. AHMAD, SE" class="w-full h-10 px-3.5 border border-slate-300 rounded-xl text-sm uppercase bg-white focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 focus:outline-none font-medium transition-colors">
                <p id="hintLockedNama" class="hidden text-[0.6875rem] text-slate-500 mt-1">Nama pejabat default sistem dilindungi dan tidak dapat diubah.</p>
            </div>

            {{-- Jabatan --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-700">Jabatan Resmi Pejabat <span id="reqJabatan" class="text-rose-500">*</span></label>
                    <span id="badgeLockedJabatan" class="hidden text-[0.6875rem] font-bold text-amber-700 bg-amber-100/90 border border-amber-300/80 px-2 py-0.5 rounded-md">🔒 Terkunci</span>
                </div>
                <input type="text" name="jabatan" id="inputJabatan" required placeholder="Contoh: Pemimpin Divisi IT atau Plt. Pemimpin Divisi IT" class="w-full h-10 px-3.5 border border-slate-300 rounded-xl text-sm bg-white focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 focus:outline-none transition-colors">
                <p id="hintLockedJabatan" class="hidden text-[0.6875rem] text-slate-500 mt-1">Jabatan pejabat default sistem dilindungi dan tidak dapat diubah.</p>
            </div>

            {{-- Input Unggah Berkas Tanda Tangan --}}
            <div class="pt-2 border-t border-slate-200">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-bold text-slate-700">Berkas Tanda Tangan Digital</label>
                    <span class="text-[0.6875rem] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded">PNG / JPG / WEBP (Maks. 2 MB)</span>
                </div>
                
                <div class="border-2 border-dashed border-slate-300 hover:border-brand-blue/60 rounded-xl p-4 bg-slate-50/70 hover:bg-blue-50/30 transition-all text-center">
                    <input type="file" name="ttd_file" id="inputTtdFile" accept="image/png,image/jpeg,image/webp" onchange="handleFileSelected(event)" class="hidden">
                    
                    <div id="uploadPlaceholder" class="cursor-pointer py-1" onclick="document.getElementById('inputTtdFile').click()">
                        <div class="w-10 h-10 rounded-full bg-brand-blue/10 text-brand-blue flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                        </div>
                        <div class="text-xs font-bold text-navy hover:text-brand-blue">Pilih Berkas Gambar TTD</div>
                        <p class="text-[0.6875rem] text-slate-500 mt-0.5">Disarankan berkas PNG dengan latar belakang transparan</p>
                    </div>

                    {{-- Pratinjau Berkas Baru Terpilih --}}
                    <div id="previewNewUpload" class="hidden flex-col items-center gap-2 pt-1">
                        <div class="text-[0.6875rem] font-bold text-emerald-700 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Berkas TTD baru terpilih
                        </div>
                        <div style="width: 180px; height: 75px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin: auto;">
                            <img id="imgNewUpload" src="" alt="TTD Baru" style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;">
                        </div>
                        <button type="button" onclick="resetSelectedFile()" class="inline-flex items-center gap-1 text-[0.6875rem] font-bold text-rose-600 hover:underline cursor-pointer">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18m-2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            Ganti Berkas Lain
                        </button>
                    </div>
                </div>

                {{-- Pratinjau TTD Lama saat Edit --}}
                <div id="previewTtdLama" class="hidden mt-3 p-2.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="text-xs text-slate-500 font-medium shrink-0">TTD Saat Ini:</div>
                        <div style="width: 120px; height: 50px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; display: flex; align-items: center; justify-content: center; padding: 4px; flex-shrink: 0;">
                            <img id="imgTtdLama" src="" alt="TTD Lama" style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;">
                        </div>
                    </div>
                    <span class="text-[0.6875rem] text-slate-400 italic text-right leading-tight">Tetap dipakai jika tidak unggah berkas baru</span>
                </div>
            </div>

            {{-- Checkbox Default & Aktif --}}
            <div class="pt-2 border-t border-slate-200 flex flex-col gap-2">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_default" id="inputIsDefault" value="1" class="w-4 h-4 rounded text-brand-blue focus:ring-brand-blue border-slate-300">
                    <span class="text-xs font-bold text-slate-700">Jadikan Pejabat Utama (Otomatis dipakai default saat cetak)</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_aktif" id="inputIsAktif" value="1" checked class="w-4 h-4 rounded text-brand-blue focus:ring-brand-blue border-slate-300">
                    <span class="text-xs font-bold text-slate-700">Status Aktif</span>
                </label>
            </div>

            {{-- Tombol Submit --}}
            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2.5">
                <button type="button" onclick="tutupModalPejabat()" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 cursor-pointer transition-colors shadow-2xs">
                    Batal
                </button>
                <button type="submit" id="btnSimpanPejabat" class="px-5 py-2 rounded-xl bg-gradient-to-r from-brand-blue to-navy text-white text-xs font-bold shadow-md hover:opacity-95 cursor-pointer transition-all">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL KONFIRMASI HAPUS STAFF (UI Khusus Bank Sulteng) --}}
<div id="modalKonfirmasiHapus" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden" style="width: 100%; max-width: 380px; margin: auto;">
        <div class="p-6 text-center">
            <div class="w-12 h-12 rounded-full bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3.5 shadow-2xs">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
            </div>
            
            <h3 class="text-base font-extrabold text-slate-900 mb-1">
                Hapus Data Staff?
            </h3>
            
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; margin: 14px 0; text-align: left;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Staff yang akan dihapus:</div>
                <div id="hapusNamaPejabat" style="font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 3px; word-break: break-word;"></div>
                <div style="font-size: 12px; color: #475569; margin-top: 2px;">Peruntukan: <span id="hapusSlotPejabat" style="font-weight: 700; color: #1e40af;"></span></div>
            </div>

            <p class="text-xs text-slate-500 leading-relaxed m-0">
                Data staff dan tanda tangan terkait akan dihapus permanen dari sistem. Tindakan ini tidak dapat dibatalkan.
            </p>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
            <button type="button" onclick="tutupModalKonfirmasiHapus()" class="flex-1 py-2.5 px-4 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs cursor-pointer transition-colors shadow-2xs">
                Batal
            </button>
            <form id="formHapusPejabat" method="POST" action="" class="m-0 flex-1">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md cursor-pointer transition-colors">
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function handleFileSelected(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('imgNewUpload').src = evt.target.result;
            document.getElementById('uploadPlaceholder').classList.add('hidden');
            document.getElementById('previewNewUpload').classList.remove('hidden');
            document.getElementById('previewNewUpload').classList.add('flex');
        };
        reader.readAsDataURL(file);
    }

    function resetSelectedFile() {
        const fileInput = document.getElementById('inputTtdFile');
        if (fileInput) fileInput.value = '';
        const imgNew = document.getElementById('imgNewUpload');
        if (imgNew) imgNew.src = '';
        const prevBox = document.getElementById('previewNewUpload');
        if (prevBox) {
            prevBox.classList.add('hidden');
            prevBox.classList.remove('flex');
        }
        const holder = document.getElementById('uploadPlaceholder');
        if (holder) holder.classList.remove('hidden');
    }

    function setFormLock(isLocked) {
        const inputNama = document.getElementById('inputNama');
        const inputJabatan = document.getElementById('inputJabatan');
        const inputSlot = document.getElementById('inputSlot');
        const alertBox = document.getElementById('alertPejabatLocked');
        const badgeNama = document.getElementById('badgeLockedNama');
        const badgeJabatan = document.getElementById('badgeLockedJabatan');
        const hintNama = document.getElementById('hintLockedNama');
        const hintJabatan = document.getElementById('hintLockedJabatan');

        if (isLocked) {
            // Kunci Nama
            inputNama.readOnly = true;
            inputNama.classList.add('bg-slate-100', 'text-slate-500', 'cursor-not-allowed', 'border-slate-200');
            inputNama.classList.remove('bg-white', 'text-slate-800');
            if (badgeNama) badgeNama.classList.remove('hidden');
            if (hintNama) hintNama.classList.remove('hidden');

            // Kunci Jabatan
            inputJabatan.readOnly = true;
            inputJabatan.classList.add('bg-slate-100', 'text-slate-500', 'cursor-not-allowed', 'border-slate-200');
            inputJabatan.classList.remove('bg-white', 'text-slate-800');
            if (badgeJabatan) badgeJabatan.classList.remove('hidden');
            if (hintJabatan) hintJabatan.classList.remove('hidden');

            // Kunci Slot (pejabat default tidak pindah slot)
            inputSlot.style.pointerEvents = 'none';
            inputSlot.classList.add('bg-slate-100', 'text-slate-500', 'cursor-not-allowed');
            inputSlot.classList.remove('bg-white');

            if (alertBox) {
                alertBox.classList.remove('hidden');
                alertBox.classList.add('flex');
            }
        } else {
            // Buka Kunci Nama
            inputNama.readOnly = false;
            inputNama.classList.remove('bg-slate-100', 'text-slate-500', 'cursor-not-allowed', 'border-slate-200');
            inputNama.classList.add('bg-white', 'text-slate-800');
            if (badgeNama) badgeNama.classList.add('hidden');
            if (hintNama) hintNama.classList.add('hidden');

            // Buka Kunci Jabatan
            inputJabatan.readOnly = false;
            inputJabatan.classList.remove('bg-slate-100', 'text-slate-500', 'cursor-not-allowed', 'border-slate-200');
            inputJabatan.classList.add('bg-white', 'text-slate-800');
            if (badgeJabatan) badgeJabatan.classList.add('hidden');
            if (hintJabatan) hintJabatan.classList.add('hidden');

            // Buka Kunci Slot
            inputSlot.style.pointerEvents = 'auto';
            inputSlot.classList.remove('bg-slate-100', 'text-slate-500', 'cursor-not-allowed');
            inputSlot.classList.add('bg-white');

            if (alertBox) {
                alertBox.classList.add('hidden');
                alertBox.classList.remove('flex');
            }
        }
    }

    function bukaModalTambah(slot, label, defaultJabatan) {
        setFormLock(false);
        const form = document.getElementById('formPejabat');
        form.reset();
        form.action = "{{ route('admin.pejabat-ttd.store') }}";
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('modalIcon').innerText = '➕';
        document.getElementById('modalTitle').innerText = `Tambah Staff: ${label || 'Slot ' + slot}`;
        document.getElementById('btnSimpanPejabat').innerText = 'Simpan Staff Baru';
        document.getElementById('inputSlot').value = slot;
        document.getElementById('inputNama').value = '';
        document.getElementById('inputJabatan').value = defaultJabatan || '';
        document.getElementById('inputIsDefault').checked = false;
        document.getElementById('inputIsAktif').checked = true;

        resetSelectedFile();

        const previewBox = document.getElementById('previewTtdLama');
        if (previewBox) previewBox.classList.add('hidden');

        const modal = document.getElementById('modalPejabat');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function bukaModalEdit(pejabat) {
        const defaultNames = ['MUJADID', 'AYU FEBRIANTI', 'WACHYUNI MADARAYU', 'DIANA, ST'];
        const cleanName = (pejabat.nama || '').toUpperCase().replace(/[^A-Z]/g, '');
        const isLocked = Boolean(pejabat.is_locked) || defaultNames.some(d => d.replace(/[^A-Z]/g, '') === cleanName);

        setFormLock(isLocked);

        const form = document.getElementById('formPejabat');
        form.reset();
        form.action = `/pejabat-ttd/${pejabat.id}`;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalIcon').innerText = isLocked ? '🔒' : '✏️';
        document.getElementById('modalTitle').innerText = isLocked 
            ? `Perbarui Tanda Tangan: ${pejabat.nama}`
            : `Edit Pejabat: ${pejabat.nama}`;
        document.getElementById('btnSimpanPejabat').innerText = isLocked
            ? 'Simpan Tanda Tangan'
            : 'Simpan Perubahan';
        document.getElementById('inputSlot').value = pejabat.slot;
        document.getElementById('inputNama').value = pejabat.nama;
        document.getElementById('inputJabatan').value = pejabat.jabatan;
        document.getElementById('inputIsDefault').checked = Boolean(pejabat.is_default);
        document.getElementById('inputIsAktif').checked = Boolean(pejabat.is_aktif);

        resetSelectedFile();

        const previewBox = document.getElementById('previewTtdLama');
        const previewImg = document.getElementById('imgTtdLama');
        if (pejabat.ttd_image) {
            previewImg.src = pejabat.ttd_image;
            previewBox.classList.remove('hidden');
        } else {
            previewBox.classList.add('hidden');
        }

        const modal = document.getElementById('modalPejabat');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function tutupModalPejabat() {
        const modal = document.getElementById('modalPejabat');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        resetSelectedFile();
    }

    function bukaModalKonfirmasiHapus(id, nama, slotLabel) {
        const form = document.getElementById('formHapusPejabat');
        form.action = `/pejabat-ttd/${id}`;
        document.getElementById('hapusNamaPejabat').innerText = nama;
        document.getElementById('hapusSlotPejabat').innerText = slotLabel || 'Slot';

        const modal = document.getElementById('modalKonfirmasiHapus');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function tutupModalKonfirmasiHapus() {
        const modal = document.getElementById('modalKonfirmasiHapus');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            tutupModalPejabat();
            tutupModalKonfirmasiHapus();
        }
    });

    const modalHapusEl = document.getElementById('modalKonfirmasiHapus');
    if (modalHapusEl) {
        modalHapusEl.addEventListener('click', function(e) {
            if (e.target === this) tutupModalKonfirmasiHapus();
        });
    }

    const modalPejabatEl = document.getElementById('modalPejabat');
    if (modalPejabatEl) {
        modalPejabatEl.addEventListener('click', function(e) {
            if (e.target === this) tutupModalPejabat();
        });
    }
</script>
@endsection
