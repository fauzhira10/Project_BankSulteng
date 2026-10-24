{{-- Lencana status penjangkaran satu blok ke Ethereum. Butuh $blok (BlokAudit). --}}
@switch($blok->jangkar_status)
    @case('terkirim')
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[0.71875rem] font-bold bg-sky-100 text-sky-800" title="Tercatat di {{ $blok->jangkar_jaringan }}">⛓ Terjangkar</span>
        @break
    @case('konflik')
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[0.71875rem] font-bold bg-rose-500 text-white" title="{{ $blok->jangkar_galat }}">⚠ Konflik</span>
        @break
    @case('gagal')
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[0.71875rem] font-bold bg-amber-100 text-amber-800" title="{{ $blok->jangkar_galat }}">Gagal, dicoba ulang</span>
        @break
    @default
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[0.71875rem] font-bold bg-slate-100 text-slate-500">Belum dijangkarkan</span>
@endswitch
