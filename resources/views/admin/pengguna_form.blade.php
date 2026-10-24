@extends('layouts.app')

@php
    $edit = isset($user) && $user;
    $inputCls = 'w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 placeholder-slate-400 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none';
    $labelCls = 'font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1';
    $roleAwal = old('role', $edit ? $user->role->value : \App\Enums\UserRole::Cs->value);
    // Satu sumber kebijakan kata sandi, sama dengan yang divalidasi di server
    // dan dipakai perintah admin:buat.
    $minSandi = \App\Rules\KataSandi::PANJANG_MIN;
    $ketSandi = \App\Rules\KataSandi::keterangan();
@endphp

@section('title', $edit ? 'Edit Pengguna' : 'Tambah Pengguna')
@section('page_title', $edit ? 'Edit Akun Pengguna' : 'Tambah Akun Pengguna')
@section('page_subtitle', 'Akun CS cabang wajib terikat ke satu kantor cabang penempatan')

@section('topbar_action')
    <a href="{{ route('admin.pengguna.index') }}" class="inline-flex items-center gap-2 h-[2.375rem] px-3.5 rounded-lg border border-slate-300 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 text-xs font-semibold transition-colors">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        <span>Kembali ke Daftar</span>
    </a>
@endsection

@section('content')
<div class="max-w-[47.5rem] mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4.5 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2">
            <div class="text-base font-bold text-navy">{{ $edit ? 'Edit: ' . $user->name : 'Formulir Akun Baru' }}</div>
            <span class="text-xs text-slate-500">Input bertanda (<span class="text-rose-600 font-bold">*</span>) wajib diisi</span>
        </div>
        <div class="p-6">
            <form action="{{ $edit ? route('admin.pengguna.update', $user) : route('admin.pengguna.store') }}" method="POST">
                @csrf
                @if($edit) @method('PUT') @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2 flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Nama Lengkap <span class="text-rose-600 font-bold">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $edit ? $user->name : '') }}" required maxlength="255" placeholder="Contoh: Siti Rahma (CS Cabang Poso)" class="{{ $inputCls }}">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Username <span class="text-rose-600 font-bold">*</span></label>
                        <input type="text" name="username" value="{{ old('username', $edit ? $user->username : '') }}" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9._\-]+" placeholder="Contoh: cs.poso" class="{{ $inputCls }} font-mono" autocomplete="off">
                        <span class="text-[0.71875rem] text-slate-500">Huruf, angka, titik, garis bawah, strip. Dipakai untuk login.</span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Email <span class="text-rose-600 font-bold">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $edit ? $user->email : '') }}" required maxlength="255" placeholder="nama@banksulteng.co.id" class="{{ $inputCls }}" autocomplete="off">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Peran <span class="text-rose-600 font-bold">*</span></label>
                        <select name="role" id="role" required class="{{ $inputCls }}" {{ $edit && $user->is(auth()->user()) ? 'disabled' : '' }}>
                            @foreach(\App\Enums\UserRole::cases() as $r)
                                <option value="{{ $r->value }}" {{ $roleAwal === $r->value ? 'selected' : '' }}>{{ $r->label() }}</option>
                            @endforeach
                        </select>
                        @if($edit && $user->is(auth()->user()))
                            <input type="hidden" name="role" value="{{ $user->role->value }}">
                            <span class="text-[0.71875rem] text-amber-700">Peran akun Anda sendiri tidak dapat diubah.</span>
                        @endif
                    </div>

                    <div class="flex flex-col gap-1.5" id="wrapCabang">
                        <label class="{{ $labelCls }}">Cabang Penempatan <span class="text-rose-600 font-bold" id="tandaCabang">*</span></label>
                        <select name="master_cabang_id" id="master_cabang_id" class="{{ $inputCls }}">
                            <option value="">-- Pilih Kantor Cabang --</option>
                            @foreach($cabangs as $c)
                                <option value="{{ $c->id }}" {{ (string) old('master_cabang_id', $edit ? $user->master_cabang_id : '') === (string) $c->id ? 'selected' : '' }}>{{ !empty($c->kode_cabang) && strtoupper(trim($c->nama_cabang)) !== 'CALL CENTER' ? $c->kode_cabang . ' - ' : '' }}{{ $c->nama_cabang }}</option>
                            @endforeach
                        </select>
                        <span class="text-[0.71875rem] text-slate-500" id="ketCabang">CS hanya dapat melihat pengaduan dari cabang ini.</span>
                    </div>

                    <div class="md:col-span-2 border-t border-slate-100 pt-4 -mb-1 text-[0.78125rem] font-bold text-brand-blue uppercase tracking-wider">
                        {{ $edit ? 'Ganti Kata Sandi (kosongkan jika tidak diubah)' : 'Kata Sandi' }}
                    </div>

                    <div class="md:col-span-2 -mt-2 text-[0.71875rem] text-slate-500 normal-case font-normal tracking-normal">
                        {{ $ketSandi }}. Akun ini membuka data pribadi nasabah, jadi hindari kata sandi
                        yang dipakai di layanan lain.
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Kata Sandi @unless($edit)<span class="text-rose-600 font-bold">*</span>@endunless</label>
                        <input type="password" name="password" {{ $edit ? '' : 'required' }} minlength="{{ $minSandi }}" placeholder="{{ $ketSandi }}" class="{{ $inputCls }}" autocomplete="new-password">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelCls }}">Konfirmasi Kata Sandi @unless($edit)<span class="text-rose-600 font-bold">*</span>@endunless</label>
                        <input type="password" name="password_confirmation" {{ $edit ? '' : 'required' }} minlength="{{ $minSandi }}" placeholder="Ulangi kata sandi" class="{{ $inputCls }}" autocomplete="new-password">
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-slate-200 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.pengguna.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-[0.84375rem] font-semibold bg-slate-100 text-slate-700 border border-slate-300 hover:bg-slate-200 transition-colors">Batal</a>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-[0.84375rem] font-semibold bg-gradient-to-r from-brand-blue to-navy text-white hover:opacity-95 hover:shadow-lg shadow-brand-blue/25 transition-all cursor-pointer">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <span>{{ $edit ? 'Simpan Perubahan' : 'Buat Akun' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const roleEl = document.getElementById('role');
    const cabangEl = document.getElementById('master_cabang_id');
    const tandaCabang = document.getElementById('tandaCabang');
    const ketCabang = document.getElementById('ketCabang');

    function sesuaikanCabang() {
        const isCs = roleEl.value === 'cs';
        cabangEl.required = isCs;
        cabangEl.disabled = !isCs;
        if (!isCs) cabangEl.value = '';
        tandaCabang.style.display = isCs ? '' : 'none';
        ketCabang.textContent = isCs ? 'CS hanya dapat melihat pengaduan dari cabang ini.' : 'Admin Pusat tidak terikat ke cabang tertentu.';
    }
    if (roleEl && cabangEl) {
        roleEl.addEventListener('change', sesuaikanCabang);
        sesuaikanCabang();
    }
</script>
@endpush
