@extends('layouts.app')

@php
    $edit = isset($pengaduan) && $pengaduan;
    $nilai = function (string $field, $default = '') use ($edit, $pengaduan) {
        return old($field, $edit ? ($pengaduan->{$field} ?? $default) : $default);
    };
    $inputCls = 'w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 placeholder-slate-400 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none';
    $labelCls = 'font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1';
    $sectionCls = "md:col-span-2 flex items-center gap-2.5 my-2.5 text-brand-blue text-[0.8125rem] font-bold uppercase tracking-wider after:content-[''] after:grow after:h-px after:bg-slate-200";
    $lampiranAda = $edit ? $pengaduan->lampirans->groupBy('jenis') : collect();
    $nominalAwal = (int) preg_replace('/[^\d]/', '', (string) old('nominal_transaksi', $edit ? (int) round((float) $pengaduan->nominal_transaksi) : ''));
    $tglTransaksiAwal = old('tgl_transaksi', $edit && $pengaduan->tgl_transaksi ? $pengaduan->tgl_transaksi->format('Y-m-d') : '');
@endphp

@section('title', $edit ? 'Edit Pengaduan' : 'Input Pengaduan Nasabah')
@section('page_title', $edit ? 'Edit Pengaduan Nasabah' : 'Input Pengaduan Nasabah')
@section('page_subtitle', 'Formulir pengaduan keluhan transaksi nasabah untuk dikirim ke Admin Pusat (Divisi IT)')

@section('topbar_action')
    <a href="{{ $edit ? route('cs.pengaduan.show', $pengaduan) : route('cs.pengaduan.index') }}" class="inline-flex items-center gap-2 h-[2.375rem] px-3.5 rounded-lg border border-slate-300 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 text-xs font-semibold transition-colors">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        <span>{{ $edit ? 'Kembali ke Detail' : 'Lihat Data Pengaduan' }}</span>
    </a>
@endsection

@section('content')
<div class="max-w-[62.5rem] mx-auto">

    @if($edit)
        <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-xl p-4 mb-5 text-[0.8125rem] flex items-start gap-3">
            <span class="text-lg leading-none">✏️</span>
            <div>
                Anda mengubah pengaduan <strong class="font-mono">{{ $pengaduan->nomor_tiket }}</strong>. Perubahan hanya dapat dilakukan selama status masih <strong>Menunggu Verifikasi Pusat</strong>. Lampiran baru akan <strong>ditambahkan</strong>; lampiran lama bisa dihapus lewat tombol di sampingnya.
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
                <span>Formulir Pengaduan Keluhan Nasabah</span>
            </div>
            <span class="text-xs text-slate-500">Input bertanda (<span class="text-rose-600 font-bold">*</span>) wajib diisi</span>
        </div>

        <div class="p-6">
            <form id="formPengaduan" action="{{ $edit ? route('cs.pengaduan.update', $pengaduan) : route('cs.pengaduan.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if($edit) @method('PUT') @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- ================= 1. DATA PELAPOR ================= --}}
                    <div class="{{ $sectionCls }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span>1. Data Pelapor (Customer Service)</span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Nama Pelapor (Nama CS) <span class="text-rose-600 font-bold">*</span></label>
                        <input type="text" name="nama_pelapor" value="{{ $nilai('nama_pelapor', auth()->user()->name) }}" required maxlength="255" placeholder="Nama petugas CS yang menerima pengaduan" class="{{ $inputCls }}">
                    </div>

                    @php $cabangAwal = old('master_cabang_id', $edit ? $pengaduan->master_cabang_id : ($cabang?->id ?? '')); @endphp
                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Asal Cabang <span class="text-rose-600 font-bold">*</span></label>
                        <select name="master_cabang_id" id="cabang_id" required class="{{ $inputCls }}">
                            <option value="">-- Pilih Kantor Cabang --</option>
                            @foreach($cabangs as $c)
                                <option value="{{ $c->id }}" data-kode="{{ $c->kode_cabang ?? '' }}" {{ (string) $cabangAwal === (string) $c->id ? 'selected' : '' }}>
                                    {{ !empty($c->kode_cabang) && strtoupper(trim($c->nama_cabang)) !== 'CALL CENTER' ? $c->kode_cabang . ' - ' : '' }}{{ $c->nama_cabang }}
                                </option>
                            @endforeach
                        </select>
                        <span class="text-[0.71875rem] text-slate-500">Pilih kantor cabang asal pengaduan{{ $cabang ? ' (default: cabang penempatan akun Anda)' : '' }}.</span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Kategori <span class="text-rose-600 font-bold">*</span></label>
                        <input type="text" name="kategori" value="{{ $nilai('kategori') }}" required maxlength="255" placeholder="Contoh: TRANSAKSI ATM" class="{{ $inputCls }} uppercase" oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Sub Kategori</label>
                        <input type="text" name="sub_kategori" value="{{ $nilai('sub_kategori') }}" maxlength="255" placeholder="Contoh: TARIK TUNAI" class="{{ $inputCls }} uppercase" oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <div class="md:col-span-2 flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Sub Kategori 2</label>
                        <input type="text" name="sub_kategori_2" value="{{ $nilai('sub_kategori_2') }}" maxlength="255" placeholder="Contoh: UANG TIDAK KELUAR, SALDO TERDEBET" class="{{ $inputCls }} uppercase" oninput="this.value = this.value.toUpperCase()">
                    </div>

                    {{-- ================= 2. DATA NASABAH ================= --}}
                    <div class="{{ $sectionCls }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><circle cx="8" cy="11" r="2.5"></circle><path d="M14 10h5M14 14h5"></path></svg>
                        <span>2. Data Nasabah</span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Nama Nasabah <span class="text-rose-600 font-bold">*</span></label>
                        <input type="text" name="nama_nasabah" id="nama_nasabah" value="{{ $nilai('nama_nasabah') }}" required maxlength="255" placeholder="Sesuai KTP, contoh: BUDI SANTOSO" class="{{ $inputCls }} uppercase" oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Nomor HP Nasabah <span class="text-rose-600 font-bold">*</span></label>
                        <input type="tel" name="no_hp" value="{{ $nilai('no_hp') }}" required maxlength="30" inputmode="tel" placeholder="Contoh: 081234567890" class="{{ $inputCls }}">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Nomor KTP (NIK) <span class="text-rose-600 font-bold">*</span></label>
                        <input type="text" name="no_ktp" value="{{ $nilai('no_ktp') }}" required minlength="16" maxlength="16" inputmode="numeric" pattern="[0-9]{16}" placeholder="16 digit angka" class="{{ $inputCls }} font-mono" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 16)">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Nomor Rekening <span class="text-rose-600 font-bold">*</span></label>
                        <input type="text" name="no_rekening" value="{{ $nilai('no_rekening') }}" required maxlength="50" inputmode="numeric" placeholder="Contoh: 00900000123" class="{{ $inputCls }} font-mono">
                    </div>

                    <div class="md:col-span-2 flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Nomor Kartu ATM / Debit</label>
                        <input type="text" name="no_kartu" value="{{ $nilai('no_kartu') }}" maxlength="50" inputmode="numeric" placeholder="Contoh: 6019xxxxxxxxxxxx (kosongkan jika bukan transaksi kartu)" class="{{ $inputCls }} font-mono">
                    </div>

                    {{-- ================= 3. DATA TRANSAKSI ================= --}}
                    <div class="{{ $sectionCls }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        <span>3. Data Transaksi Bermasalah</span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Jenis Transaksi <span class="text-rose-600 font-bold">*</span></label>
                        <select name="master_transaksi_id" id="transaksi_id" required class="{{ $inputCls }}">
                            <option value="">-- Pilih Jenis Transaksi --</option>
                            @foreach($transaksis as $t)
                                <option value="{{ $t->id }}" {{ (string) $nilai('master_transaksi_id') === (string) $t->id ? 'selected' : '' }}>{{ $t->jenis_transaksi }}</option>
                            @endforeach
                        </select>
                        <span class="text-[0.71875rem] text-slate-500">Channel akan terisi otomatis setelah jenis transaksi dipilih.</span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Prinsipal / Channel <span class="text-rose-600 font-bold">*</span></label>
                        <select name="channel" id="channel" required class="{{ $inputCls }}">
                            <option value="">-- Pilih Channel --</option>
                            @foreach($channels as $ch)
                                <option value="{{ $ch }}" {{ strtoupper((string) $nilai('channel')) === $ch ? 'selected' : '' }}>{{ $ch }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Nomor Resi / Trace Number <span class="text-rose-600 font-bold">*</span></label>
                        <input type="text" name="no_resi" id="no_resi" value="{{ $nilai('no_resi') }}" required maxlength="100" placeholder="Nomor resi / trace pada struk" class="{{ $inputCls }} font-mono">
                    </div>

                    {{-- Pemberitahuan bila keluhan nasabah ini sudah pernah tercatat di pusat.
                         Sifatnya hanya informasi — pengiriman pengaduan tidak pernah dihalangi. --}}
                    <div class="md:col-span-2" id="panelDuplikat"></div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Terminal Transaksi / Mesin ATM</label>
                        <select name="terminal_transaksi" id="terminal_transaksi" class="{{ $inputCls }}">
                            <option value="">-- Pilih Mesin ATM / Terminal --</option>
                        </select>
                        <span class="text-[0.71875rem] text-slate-500" id="terminalHelperText">Mesin ATM dari cabang asal yang dipilih ditampilkan paling atas.</span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Nominal Transaksi (Rp) <span class="text-rose-600 font-bold">*</span></label>
                        <div class="flex items-center rounded-xl border border-slate-300 bg-white overflow-hidden focus-within:border-brand-blue focus-within:ring-3 focus-within:ring-brand-blue/15 transition-all duration-200">
                            <span class="inline-flex items-center px-3.5 py-2.5 bg-slate-50 text-slate-500 font-semibold text-xs border-r border-slate-200 select-none">Rp</span>
                            <input type="text" id="nominal_display" value="{{ $nominalAwal > 0 ? number_format($nominalAwal, 0, ',', '.') : '' }}" required inputmode="numeric" placeholder="Contoh: 1.000.000" autocomplete="off" class="block w-full px-3.5 py-2.5 text-sm bg-white text-slate-800 placeholder-slate-400 border-0 focus:outline-none focus:ring-0">
                            <input type="hidden" name="nominal_transaksi" id="nominal_transaksi" value="{{ $nominalAwal > 0 ? $nominalAwal : '' }}">
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Tanggal Transaksi <span class="text-rose-600 font-bold">*</span></label>
                        <input type="date" name="tgl_transaksi" id="tgl_transaksi" value="{{ $tglTransaksiAwal }}" required max="{{ date('Y-m-d') }}" class="{{ $inputCls }}">
                    </div>

                    {{-- ================= 4. LAMPIRAN ================= --}}
                    <div class="{{ $sectionCls }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                        <span>4. Lampiran Dokumen</span>
                    </div>

                    <div class="md:col-span-2 bg-sky-50 border border-sky-200 text-sky-900 rounded-xl p-3.5 text-[0.78125rem] flex items-start gap-2.5">
                        <span class="text-base leading-none">📎</span>
                        <div>
                            Unggah <strong>foto (JPG/PNG/WEBP)</strong> atau <strong>PDF</strong>, maksimal <strong>{{ $maxFile }} berkas</strong> per jenis dan <strong>{{ round($maxKb / 1024) }} MB</strong> per berkas.
                            Berkas disimpan <strong>sesuai format aslinya</strong> tanpa dikonversi. Foto beresolusi sangat besar diperkecil otomatis agar ringan, formatnya tetap sama dan dokumen tetap terbaca jelas.
                        </div>
                    </div>

                    @foreach($jenisLampiran as $jenis => $cfg)
                        @php
                            $sudahAda = $lampiranAda->get($jenis, collect());
                            $wajib = !empty($cfg['wajib']) && $sudahAda->isEmpty();
                        @endphp
                        <div class="flex flex-col gap-1.5 {{ $jenis === 'lainnya' ? 'md:col-span-2' : '' }}">
                            <label class="{{ $labelCls }}" for="lampiran_{{ $jenis }}">
                                {{ $cfg['label'] }}
                                @if($wajib)<span class="text-rose-600 font-bold">*</span>@endif
                            </label>

                            @if($sudahAda->isNotEmpty())
                                <div class="flex flex-col gap-1.5 mb-1">
                                    @foreach($sudahAda as $l)
                                        <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg border border-emerald-200 bg-emerald-50/60 text-[0.75rem]">
                                            <span class="w-8 h-8 rounded-md flex items-center justify-center font-extrabold text-[0.71875rem] shrink-0 {{ $l->adalahGambar() ? 'bg-sky-100 text-sky-700' : 'bg-rose-100 text-rose-600' }}">{{ $l->labelFormat() }}</span>
                                            <a href="{{ route('pengaduan.lampiran.show', [$pengaduan, $l]) }}" target="_blank" class="grow min-w-0 truncate font-semibold text-navy hover:text-brand-blue" title="Buka {{ $l->nama_asli }}">{{ $l->nama_asli }} <span class="font-normal text-slate-500">&bull; {{ $l->ukuranTerbaca() }}</span></a>
                                            <button type="submit" form="hapusLampiran{{ $l->id }}" class="text-rose-600 hover:text-rose-800 font-bold text-[0.71875rem] cursor-pointer shrink-0">Hapus</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <input type="file" name="lampiran[{{ $jenis }}][]" id="lampiran_{{ $jenis }}" data-lampiran="{{ $jenis }}" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" {{ $wajib ? 'required' : '' }}
                                class="block w-full text-[0.78125rem] text-slate-600 border border-dashed border-slate-300 rounded-xl bg-slate-50/60 cursor-pointer file:mr-3 file:py-2.5 file:px-4 file:rounded-l-xl file:border-0 file:text-[0.75rem] file:font-bold file:bg-navy file:text-white hover:file:bg-brand-blue hover:border-brand-blue/50 transition-colors">
                            <span class="text-[0.71875rem] text-slate-500">{{ $cfg['keterangan'] ?? '' }}</span>
                            <div id="preview_{{ $jenis }}" class="grid grid-cols-2 sm:grid-cols-3 gap-2 empty:hidden"></div>
                            <span id="err_{{ $jenis }}" class="hidden text-[0.71875rem] text-rose-600 font-medium"></span>
                        </div>
                    @endforeach

                    {{-- ================= 5. KRONOLOGI ================= --}}
                    <div class="{{ $sectionCls }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <span>5. Keterangan Detail / Kronologi</span>
                    </div>

                    <div class="md:col-span-2 flex flex-col gap-1.5">
                        <div class="flex items-center justify-between">
                            <label class="{{ $labelCls }}">Kronologi Kejadian <span class="text-rose-600 font-bold">*</span></label>
                            <span class="text-[0.71875rem] text-slate-500"><span id="kronologiCount">0</span> karakter (min. 20)</span>
                        </div>
                        <textarea name="kronologi" id="kronologi" required minlength="20" rows="7" placeholder="Tuliskan kronologi selengkap mungkin: waktu kejadian, lokasi/mesin, langkah yang dilakukan nasabah, pesan error yang muncul, apakah struk keluar, saldo terdebet atau tidak, dan tindakan yang sudah dilakukan CS." class="{{ $inputCls }} resize-y min-h-[8.75rem] leading-relaxed">{{ $nilai('kronologi') }}</textarea>
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-slate-200 flex items-center justify-between gap-3 flex-wrap">
                    <span class="text-[0.71875rem] text-slate-500">Dengan mengirim, Anda menyatakan data & lampiran sudah diverifikasi bersama nasabah.</span>
                    <div class="flex items-center gap-3">
                        <a href="{{ $edit ? route('cs.pengaduan.show', $pengaduan) : route('cs.pengaduan.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-[0.84375rem] font-semibold bg-slate-100 text-slate-700 border border-slate-300 hover:bg-slate-200 transition-colors">Batal</a>
                        <button type="submit" id="btnSubmit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-[0.84375rem] font-semibold bg-gradient-to-r from-brand-blue to-navy text-white hover:opacity-95 hover:shadow-lg shadow-brand-blue/25 transition-all cursor-pointer">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                            <span>{{ $edit ? 'Simpan Perubahan' : 'Kirim Pengaduan ke Pusat' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Form hapus lampiran (di luar form utama agar valid HTML) --}}
    @if($edit)
        @foreach($pengaduan->lampirans as $l)
            <form id="hapusLampiran{{ $l->id }}" action="{{ route('cs.pengaduan.lampiran.destroy', [$pengaduan, $l]) }}" method="POST" class="hidden"
                  data-konfirmasi="Hapus Lampiran Ini?"
                  data-ikon="🗑️"
                  data-warna="rose"
                  data-aksi="Ya, Hapus"
                  data-pesan="Berkas {{ $l->nama_asli }} pada {{ $l->label() }} akan dihapus permanen dari pengaduan ini.">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif
</div>
@endsection

@include('partials.skrip_duplikat', [
    'informatif' => true,
    'abaikanPengaduanId' => $edit ? $pengaduan->id : null,
])

@push('scripts')
<script>
    const atmsGrouped = @json($atmsGrouped);
    const kodeCabangCs = @json($cabang?->kode_cabang);
    const terminalNonAtm = @json($terminalNonAtm);
    const initialTerminal = @json(old('terminal_transaksi', $edit ? ($pengaduan->terminal_transaksi ?? '') : ''));
    const MAX_FILE = {{ (int) $maxFile }};
    const MAX_KB = {{ (int) $maxKb }};

    const transaksiEl = document.getElementById('transaksi_id');
    const channelSelect = document.getElementById('channel');
    const terminalSelect = document.getElementById('terminal_transaksi');
    const cabangSelect = document.getElementById('cabang_id');

    // Kode cabang asal yang sedang dipilih (fallback: cabang akun CS)
    function kodeCabangTerpilih() {
        if (!cabangSelect) return kodeCabangCs || '';
        const opt = cabangSelect.options[cabangSelect.selectedIndex];
        const kode = opt ? (opt.getAttribute('data-kode') || '') : '';
        return kode || kodeCabangCs || '';
    }

    // ---------- Auto-fill channel dari jenis transaksi ----------
    function loadDetailTransaksi(id) {
        if (!id) return;
        fetch('/api/transaksi/' + id)
            .then(r => r.json())
            .then(data => {
                if (channelSelect && data && data.channel) {
                    const val = String(data.channel).toUpperCase().trim();
                    const ada = Array.from(channelSelect.options).some(o => o.value === val);
                    if (ada) channelSelect.value = val;
                }
            })
            .catch(err => console.error('Gagal memuat detail transaksi:', err));
    }

    // ---------- Dropdown terminal: cabang CS paling atas ----------
    function populateAtmDropdown(targetValue = '') {
        const current = targetValue || (terminalSelect ? terminalSelect.value : '');
        terminalSelect.innerHTML = '';

        const def = document.createElement('option');
        def.value = '';
        def.textContent = '-- Pilih Mesin ATM / Terminal --';
        terminalSelect.appendChild(def);

        const kodePrioritas = kodeCabangTerpilih();
        const kodeUrut = Object.keys(atmsGrouped).sort((a, b) => {
            if (a === kodePrioritas) return -1;
            if (b === kodePrioritas) return 1;
            return a.localeCompare(b);
        });

        kodeUrut.forEach(kode => {
            const atms = atmsGrouped[kode];
            if (!atms || atms.length === 0) return;
            const grp = document.createElement('optgroup');
            const nama = atms[0].cabang || '';
            let label = (nama && nama.toUpperCase() !== 'CALL CENTER') ? `${kode} - ${nama}` : (nama || `Cabang ${kode}`);
            if (kode === kodePrioritas) label += '  ★ (Cabang Asal)';
            grp.label = label;
            atms.forEach(atm => {
                const opt = document.createElement('option');
                opt.value = atm.value;
                opt.textContent = atm.label;
                if (current && (current === atm.value || current.trim() === atm.value.trim())) opt.selected = true;
                grp.appendChild(opt);
            });
            terminalSelect.appendChild(grp);
        });

        const umum = document.createElement('optgroup');
        umum.label = 'Channel Non-ATM';
        terminalNonAtm.forEach(ch => {
            const opt = document.createElement('option');
            opt.value = ch;
            opt.textContent = ch;
            if (current && current.toUpperCase().trim() === ch) opt.selected = true;
            umum.appendChild(opt);
        });
        terminalSelect.appendChild(umum);
    }

    // ---------- Nominal: format ribuan ----------
    const nominalDisplay = document.getElementById('nominal_display');
    const nominalHidden = document.getElementById('nominal_transaksi');
    const fmtID = new Intl.NumberFormat('id-ID');
    function syncNominal() {
        const digits = (nominalDisplay.value || '').replace(/[^\d]/g, '');
        nominalHidden.value = digits;
        nominalDisplay.value = digits ? fmtID.format(Number(digits)) : '';
    }
    if (nominalDisplay) {
        nominalDisplay.addEventListener('input', syncNominal);
        nominalDisplay.addEventListener('blur', syncNominal);
    }

    // ---------- Pratinjau & validasi lampiran ----------
    function ukuranTerbaca(b) {
        if (b >= 1048576) return (b / 1048576).toFixed(2) + ' MB';
        if (b >= 1024) return Math.round(b / 1024) + ' KB';
        return b + ' B';
    }

    document.querySelectorAll('input[type=file][data-lampiran]').forEach(input => {
        const jenis = input.dataset.lampiran;
        const preview = document.getElementById('preview_' + jenis);
        const errEl = document.getElementById('err_' + jenis);

        input.addEventListener('change', () => {
            preview.innerHTML = '';
            errEl.classList.add('hidden');
            const files = Array.from(input.files || []);

            if (files.length > MAX_FILE) {
                errEl.textContent = `Maksimal ${MAX_FILE} berkas untuk jenis lampiran ini.`;
                errEl.classList.remove('hidden');
                input.value = '';
                return;
            }

            for (const f of files) {
                if (f.size > MAX_KB * 1024) {
                    errEl.textContent = `Berkas "${f.name}" melebihi batas ${Math.round(MAX_KB / 1024)} MB.`;
                    errEl.classList.remove('hidden');
                    input.value = '';
                    preview.innerHTML = '';
                    return;
                }

                const item = document.createElement('div');
                item.className = 'flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-white text-[0.71875rem] min-w-0';

                if (f.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.className = 'w-10 h-10 rounded object-cover shrink-0 border border-slate-200';
                    img.src = URL.createObjectURL(f);
                    img.onload = () => URL.revokeObjectURL(img.src);
                    item.appendChild(img);
                } else {
                    const ic = document.createElement('span');
                    ic.className = 'w-10 h-10 rounded bg-rose-100 text-rose-600 flex items-center justify-center font-extrabold text-[0.71875rem] shrink-0';
                    ic.textContent = 'PDF';
                    item.appendChild(ic);
                }

                const info = document.createElement('div');
                info.className = 'min-w-0';
                info.innerHTML = `<div class="font-semibold text-slate-800 truncate" title="${f.name.replace(/"/g, '&quot;')}">${f.name.replace(/</g, '&lt;')}</div><div class="text-slate-500">${ukuranTerbaca(f.size)}</div>`;
                item.appendChild(info);
                preview.appendChild(item);
            }
        });
    });

    // ---------- Penghitung kronologi ----------
    const kronologiEl = document.getElementById('kronologi');
    const kronologiCount = document.getElementById('kronologiCount');
    function hitungKronologi() { if (kronologiEl && kronologiCount) kronologiCount.textContent = kronologiEl.value.trim().length; }
    if (kronologiEl) kronologiEl.addEventListener('input', hitungKronologi);

    // ---------- Cegah klik ganda ----------
    const formEl = document.getElementById('formPengaduan');
    const btnSubmit = document.getElementById('btnSubmit');
    if (formEl && btnSubmit) {
        formEl.addEventListener('submit', () => {
            syncNominal();
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-70', 'cursor-wait');
            btnSubmit.querySelector('span').textContent = 'Memproses lampiran...';
        });
    }

    if (transaksiEl) transaksiEl.addEventListener('change', function () { loadDetailTransaksi(this.value); });
    if (cabangSelect) cabangSelect.addEventListener('change', () => populateAtmDropdown(terminalSelect ? terminalSelect.value : ''));

    window.addEventListener('DOMContentLoaded', () => {
        populateAtmDropdown(initialTerminal);
        hitungKronologi();
        if (transaksiEl && transaksiEl.value && channelSelect && !channelSelect.value) loadDetailTransaksi(transaksiEl.value);
    });
</script>
@endpush
