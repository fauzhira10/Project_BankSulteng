{{-- Badge status pengaduan. Pemakaian: @include('partials.pengaduan_status_badge', ['status' => $pengaduan->status, 'singkat' => false]) --}}
@php
    $st = $status instanceof \App\Enums\PengaduanStatus ? $status : \App\Enums\PengaduanStatus::tryFrom((string) $status);
    $singkat = $singkat ?? false;
@endphp
<span class="badge {{ $st ? $st->badgeClass() : 'badge-strip' }}" title="{{ $st ? $st->label() : '' }}">
    {{ $st ? ($singkat ? $st->labelSingkat() : $st->label()) : ($status ?: '-') }}
</span>
