{{-- Panel peringatan keluhan berulang.

     Pemakaian:
     @include('partials.panel_duplikat', ['duplikat' => $cek])
     @include('partials.panel_duplikat', ['duplikat' => $cek, 'ringkas' => true])   (versi CS cabang)

     $duplikat berisi hasil App\Services\DeteksiDuplikatService::periksa().

     Mode ringkas (CS cabang) hanya memberi tahu BAHWA keluhannya sudah pernah
     ditangani: tanpa jumlah catatan, tanpa kartu rincian, dan tanpa membedakan
     KEMBAR dari BERULANG. Nomor tiket, cabang asal, tanggal, dan nominal adalah
     data cabang lain — lihat alasan lengkapnya di JurnalController::cekDuplikat.
--}}
@php
    use App\Services\DeteksiDuplikatService;

    $duplikat = $duplikat ?? null;
    $ringkas = $ringkas ?? false;
    $tingkat = $duplikat['tingkat'] ?? DeteksiDuplikatService::AMAN;
@endphp

@if($duplikat && $tingkat !== DeteksiDuplikatService::AMAN)
    @php
        // Mode ringkas tidak pernah menampilkan tingkat KEMBAR: membedakannya
        // sama saja dengan membenarkan tanggal transaksi yang sedang diketik.
        $kembar = ! $ringkas && $tingkat === DeteksiDuplikatService::KEMBAR;
        $gaya = $kembar
            ? ['kotak' => 'bg-rose-50 border-rose-200 text-rose-900', 'ikon' => 'text-rose-600', 'kartu' => 'border-rose-200', 'jejak' => 'text-rose-800']
            : ['kotak' => 'bg-amber-50 border-amber-200 text-amber-900', 'ikon' => 'text-amber-600', 'kartu' => 'border-amber-200', 'jejak' => 'text-amber-800'];

        $jurnals = $duplikat['jurnals'] ?? collect();
        $pengaduans = $duplikat['pengaduans'] ?? collect();

        $judul = $kembar
            ? 'Keluhan ini sudah pernah dijurnal dengan tanggal yang sama'
            : 'Keluhan ini sudah pernah ditangani';
    @endphp

    <div class="{{ $gaya['kotak'] }} border rounded-xl p-4 text-[0.8125rem] flex items-start gap-3 shadow-xs"
         data-tingkat="{{ $ringkas ? DeteksiDuplikatService::BERULANG : $tingkat }}"
         @unless($ringkas) data-jumlah="{{ $duplikat['jumlah'] ?? 0 }}" @endunless>
        <svg class="w-5 h-5 {{ $gaya['ikon'] }} shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>

        <div class="grow min-w-0">
            <div class="font-bold">{{ $judul }}</div>
            <div class="text-[0.75rem] {{ $gaya['jejak'] }} mt-0.5">
                @if($ringkas)
                    Keluhan atas nama <strong>{{ $duplikat['nama'] ?? '-' }}</strong> dengan No. Resi
                    <strong class="font-mono">{{ $duplikat['resi'] ?? '-' }}</strong> sudah pernah
                    dicatat di Kantor Pusat.
                @else
                    Ditemukan {{ $duplikat['jumlah'] ?? 0 }} catatan atas nama
                    <strong>{{ $duplikat['nama'] ?? '-' }}</strong> dengan No. Resi
                    <strong class="font-mono">{{ $duplikat['resi'] ?? '-' }}</strong>.
                @endif
            </div>

            @unless($ringkas)
            <div class="flex flex-col gap-2 mt-2.5">
                @foreach($jurnals as $lama)
                    @php
                        $st = strtolower(trim($lama->status ?? '-'));
                        $kelasStatus = match($st) {
                            'menunggu' => 'badge-menunggu',
                            'success' => 'badge-success',
                            'done' => 'badge-done',
                            'rejected' => 'badge-rejected',
                            default => 'badge-strip',
                        };
                    @endphp
                    <div class="bg-white border {{ $gaya['kartu'] }} rounded-lg px-3 py-2.5">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <span class="font-mono font-bold text-[0.8125rem] text-navy">{{ $lama->no_tiket ?: '-' }}</span>
                            <span class="badge {{ $kelasStatus }}">{{ $lama->status ?: '-' }}</span>
                        </div>
                        <div class="text-[0.75rem] text-slate-600 mt-1 leading-relaxed">
                            Transaksi {{ \Carbon\Carbon::parse($lama->tgl_transaksi)->translatedFormat('d M Y') }}
                            &bull; {{ $lama->masterCabang->nama_cabang ?? '-' }}
                            &bull; Rp {{ number_format((float) $lama->nominal_transaksi, 0, ',', '.') }}
                            <br>
                            {{ $lama->masterTransaksi->jenis_transaksi ?? '-' }}
                            &bull; Diterima {{ \Carbon\Carbon::parse($lama->tgl_terima)->translatedFormat('d M Y') }}
                            @if(!empty($lama->tgl_selesai))
                                &bull; Selesai {{ \Carbon\Carbon::parse($lama->tgl_selesai)->translatedFormat('d M Y') }}
                            @endif
                        </div>
                        {{-- Hanya id: menanam seluruh baris di sini membuat nomor rekening,
                             nomor kartu, NIK, dan nomor HP sampai lima nasabah tercetak di
                             sumber halaman tiap kali panelnya muncul. Modalnya mengambil
                             rincian lewat AJAX untuk baris yang benar-benar dibuka. --}}
                        <button type="button" onclick="bukaModalJurnalDuplikat(this)" data-jurnal-id="{{ $lama->id }}" class="js-rincian-duplikat inline-flex items-center gap-1 mt-1.5 text-[0.75rem] font-bold text-brand-blue hover:text-blue-700 hover:underline cursor-pointer bg-transparent border-0 p-0 text-left">
                            Lihat jurnal ini &rarr;
                        </button>
                    </div>
                @endforeach

                @foreach($pengaduans as $adu)
                    <div class="bg-white border {{ $gaya['kartu'] }} rounded-lg px-3 py-2.5">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <span class="font-mono font-bold text-[0.8125rem] text-navy">{{ $adu->nomor_tiket }}</span>
                            @include('partials.pengaduan_status_badge', ['status' => $adu->status, 'singkat' => true])
                        </div>
                        <div class="text-[0.75rem] text-slate-600 mt-1 leading-relaxed">
                            Pengaduan cabang &bull; Transaksi {{ \Carbon\Carbon::parse($adu->tgl_transaksi)->translatedFormat('d M Y') }}
                            &bull; {{ $adu->labelCabang() }}
                            &bull; Rp {{ number_format((float) $adu->nominal_transaksi, 0, ',', '.') }}
                        </div>
                        <button type="button" onclick="bukaModalPengaduanDuplikat(this)" data-pengaduan-id="{{ $adu->id }}" class="js-rincian-duplikat inline-flex items-center gap-1 mt-1.5 text-[0.75rem] font-bold text-brand-blue hover:text-blue-700 hover:underline cursor-pointer bg-transparent border-0 p-0 text-left">
                            Lihat pengaduan ini &rarr;
                        </button>
                    </div>
                @endforeach
            </div>
            @endunless

            <div class="text-[0.75rem] {{ $gaya['jejak'] }} mt-2.5 leading-relaxed">
                @if($ringkas)
                    Sampaikan ke nasabah bahwa keluhan ini sudah tercatat di pusat. Bila yang dilaporkan
                    memang transaksi yang berbeda, pengaduan tetap dapat dikirim seperti biasa.
                @elseif($kembar)
                    Data dengan nama nasabah, No. Resi, <strong>dan tanggal transaksi</strong> yang sama tidak dapat
                    disimpan dua kali. Buka jurnal yang sudah ada di atas untuk memperbaruinya, atau perbaiki
                    tanggal transaksi bila memang keluhan yang berbeda.
                @else
                    Bila ini memang keluhan baru yang berbeda, silakan lanjutkan &mdash;
                    sistem akan meminta konfirmasi sekali lagi saat Anda menyimpan.
                @endif
            </div>
        </div>
    </div>
@endif
