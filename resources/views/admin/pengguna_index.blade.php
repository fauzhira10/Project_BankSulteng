@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('page_title', 'Manajemen Pengguna')
@section('page_subtitle', 'Kelola akun Admin Pusat dan Customer Service cabang')

@section('topbar_action')
    <a href="{{ route('admin.pengguna.create') }}" class="inline-flex items-center gap-2 h-[2.375rem] px-3.5 rounded-lg bg-gradient-to-r from-brand-blue to-navy text-white hover:opacity-95 text-xs font-semibold shadow-sm transition-opacity">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Pengguna</span>
    </a>
@endsection

@section('content')
<div class="max-w-[71.875rem] mx-auto">

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 mb-5">
        <div class="stat-card" style="padding: 14px 16px;">
            <div class="stat-icon bg-brand-blue-light text-brand-blue" style="width:40px;height:40px;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg></div>
            <div class="stat-info"><div class="stat-count" style="font-size:1.25rem;">{{ $ringkasan['admin'] }}</div><div class="stat-label" style="font-size:0.71875rem;">Admin Pusat</div></div>
        </div>
        <div class="stat-card" style="padding: 14px 16px;">
            <div class="stat-icon bg-emerald-100 text-emerald-600" style="width:40px;height:40px;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg></div>
            <div class="stat-info"><div class="stat-count" style="font-size:1.25rem;">{{ $ringkasan['cs'] }}</div><div class="stat-label" style="font-size:0.71875rem;">Akun CS Cabang</div></div>
        </div>
        <div class="stat-card" style="padding: 14px 16px;">
            <div class="stat-icon bg-rose-100 text-rose-600" style="width:40px;height:40px;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg></div>
            <div class="stat-info"><div class="stat-count" style="font-size:1.25rem;">{{ $ringkasan['nonaktif'] }}</div><div class="stat-label" style="font-size:0.71875rem;">Akun Nonaktif</div></div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs mb-5 p-4">
        <form method="GET" action="{{ route('admin.pengguna.index') }}" class="grid grid-cols-1 md:grid-cols-[1fr_200px_auto] gap-3 items-end">
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-700">Cari Pengguna</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nama, username, email, atau cabang..." class="w-full h-10 px-3.5 border border-slate-300 rounded-xl text-sm bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-700">Peran</label>
                <select name="role" class="w-full h-10 px-3 border border-slate-300 rounded-xl text-sm bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                    <option value="">-- Semua Peran --</option>
                    @foreach(\App\Enums\UserRole::cases() as $r)
                        <option value="{{ $r->value }}" {{ request('role') === $r->value ? 'selected' : '' }}>{{ $r->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="h-10 px-4 rounded-xl bg-navy text-white text-xs font-bold hover:opacity-90 cursor-pointer">Terapkan</button>
                <a href="{{ route('admin.pengguna.index') }}" class="h-10 px-4 rounded-xl border border-slate-300 bg-slate-50 text-slate-700 text-xs font-bold hover:bg-slate-100 inline-flex items-center">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 text-[0.9375rem] font-bold text-navy">Daftar Akun Pengguna</div>
        <div class="p-4">
            <div style="overflow-x:auto;">
                <table class="custom-table" style="width:100%; min-width: 860px;">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Peran</th>
                            <th>Cabang Penempatan</th>
                            <th style="text-align:center;">Pengaduan</th>
                            <th>Status</th>
                            <th style="text-align:center; width: 220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr class="{{ !$u->is_active ? 'opacity-60' : '' }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-gold to-amber-600 flex items-center justify-center text-white font-bold text-[0.75rem] shrink-0">{{ strtoupper(substr($u->name, 0, 2)) }}</div>
                                        <div>
                                            <div class="font-bold text-slate-800">{{ $u->name }} @if($u->is(auth()->user()))<span class="text-[0.71875rem] bg-slate-100 border border-slate-200 text-slate-500 px-1.5 py-0.5 rounded ml-1">Anda</span>@endif</div>
                                            <div class="text-[0.71875rem] text-slate-500">&#64;{{ $u->username }} &bull; {{ $u->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($u->isSuperAdmin())
                                        <span class="badge" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a;">Admin Utama</span>
                                    @else
                                        <span class="badge {{ $u->isAdmin() ? 'badge-diproses' : 'badge-selesai' }}">{{ $u->labelRole() }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($u->cabang)
                                        <div class="font-semibold text-slate-800">{{ $u->cabang->nama_cabang }}</div>
                                        @if($u->cabang->kode_cabang)<div class="text-[0.71875rem] text-slate-500">Kode: {{ $u->cabang->kode_cabang }}</div>@endif
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </td>
                                <td style="text-align:center;" class="font-bold text-navy">{{ number_format($u->pengaduans_count, 0, ',', '.') }}</td>
                                <td style="white-space:nowrap;">
                                    <div class="inline-flex items-center gap-1.5">
                                        @if($u->is_active)
                                            <span class="badge badge-done">Aktif</span>
                                            @if(in_array($u->id, $sesiAktifUserIds ?? []))
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[0.6875rem] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 whitespace-nowrap shadow-2xs" title="Akun sedang aktif login di perangkat">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span> Sesi Aktif
                                                </span>
                                            @endif
                                        @else
                                            <span class="badge badge-rejected">Nonaktif</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="text-align:center; white-space:nowrap;">
                                    <a href="{{ route('admin.pengguna.edit', $u) }}" class="btn btn-sm" style="padding:5px 11px; font-size:0.75rem; background:#fef3c7; color:#b45309; border:1px solid #fde68a;">Edit</a>

                                    @if(in_array($u->id, $sesiAktifUserIds ?? []) && ! $u->is(auth()->user()))
                                        <form action="{{ route('admin.pengguna.reset_sesi', $u) }}" method="POST" class="inline"
                                              data-konfirmasi="Reset Sesi Login Pengguna?"
                                              data-ikon="🔄"
                                              data-warna="amber"
                                              data-aksi="Ya, Putus Sesi"
                                              data-pesan="Sesi aktif untuk {{ $u->name }} (@{{ $u->username }}) akan diputus. Pengguna tersebut (atau staf di komputer lain) dapat langsung login kembali ke sistem.">
                                            @csrf
                                            <button type="submit" class="btn btn-sm cursor-pointer" style="padding:5px 11px; font-size:0.75rem; margin-left:4px; background:#fed7aa; color:#9a3412; border:1px solid #fdba74;" title="Putus sesi aktif jika staf terkunci di perangkat lain">
                                                Reset Sesi
                                            </button>
                                        </form>
                                    @endif

                                    @unless($u->is(auth()->user()) || $u->isSuperAdmin())
                                        <form action="{{ route('admin.pengguna.toggle_aktif', $u) }}" method="POST" class="inline"
                                              data-konfirmasi="{{ $u->is_active ? 'Nonaktifkan Akun Ini?' : 'Aktifkan Akun Ini?' }}"
                                              data-ikon="{{ $u->is_active ? '🚫' : '✅' }}"
                                              data-warna="{{ $u->is_active ? 'rose' : 'emerald' }}"
                                              data-aksi="{{ $u->is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}"
                                              data-pesan="{{ $u->is_active ? "Akun {$u->name} (@{$u->username}) tidak akan bisa login lagi sampai diaktifkan kembali. Riwayat pengaduannya tetap tersimpan." : "Akun {$u->name} (@{$u->username}) akan dapat login kembali ke sistem." }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm cursor-pointer" style="padding:5px 11px; font-size:0.75rem; margin-left:4px; {{ $u->is_active ? 'background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5;' : 'background:#d1fae5; color:#047857; border:1px solid #a7f3d0;' }}">
                                                {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-slate-500 py-8">Tidak ada pengguna yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination_sederhana', ['paginator' => $users])
        </div>
    </div>
</div>
@endsection
