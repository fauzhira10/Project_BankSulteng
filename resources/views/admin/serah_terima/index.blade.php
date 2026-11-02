@extends('layouts.app')

@section('title', 'Serah Terima Penyelesaian Nasabah')
@section('page_title', 'Serah Terima Penyelesaian Nasabah')
@section('page_subtitle', 'Pilih pengaduan yang telah selesai untuk dicetak lembar serah terima pembukuan resmi.')

@section('content')
<div class="max-w-[78.125rem] mx-auto">
    {{-- Ringkasan Global --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Tiket Selesai (CLOSED)</div>
                <div class="text-2xl font-bold text-navy mt-0.5">{{ number_format($ringkasan['total_tiket'] ?? 0, 0, ',', '.') }} Tiket</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-brand-blue flex items-center justify-center shrink-0">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Akumulasi Nominal Selesai</div>
                <div class="text-2xl font-bold text-emerald-600 mt-0.5">{{ $ringkasan['total_nominal_rupiah'] ?? 'Rp 0' }}</div>
            </div>
        </div>
    </div>

    {{-- Form Serah Terima & Tabel Pemilihan --}}
    <form action="{{ route('admin.serah-terima.cetak') }}" method="POST" target="_blank" id="formSerahTerima">
        @csrf
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden mb-6">
            <div class="px-6 py-4.5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4 bg-slate-50">
                <div class="flex items-center gap-3">
                    <label for="tanggal_serah_terima" class="text-xs font-bold text-slate-700">Per Tanggal:</label>
                    <input type="date" id="tanggal_serah_terima" name="tanggal_serah_terima" value="{{ date('Y-m-d') }}" class="px-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-blue">
                </div>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white font-bold text-xs shadow-md hover:opacity-95 transition-all">
                    <span>🖨️ Cetak Lembar Serah Terima</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4 w-10 text-center">Pilih</th>
                            <th class="py-3 px-4">No. Tiket</th>
                            <th class="py-3 px-4">Nama Nasabah</th>
                            <th class="py-3 px-4">No. Rekening</th>
                            <th class="py-3 px-4 text-right">Nominal</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($tikets as $t)
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-3 px-4 text-center">
                                <input type="checkbox" name="ids[]" value="{{ $t->id }}" checked class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue">
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-navy">{{ $t->no_tiket ?: '-' }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $t->nama_nasabah }}</td>
                            <td class="py-3 px-4 font-mono text-slate-600">{{ $t->no_rekening ?: '-' }}</td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-600">Rp {{ number_format($t->nominal_transaksi, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[0.6875rem] font-bold uppercase bg-emerald-100 text-emerald-800">CLOSED</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 italic">Belum ada pengaduan berstatus selesai yang siap diserahterimakan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tikets->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $tikets->links() }}
                </div>
            @endif
        </div>
    </form>
</div>
@endsection
