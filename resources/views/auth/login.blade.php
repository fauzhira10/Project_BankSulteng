<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Petugas - E-JURNAL KELUHAN Bank Sulteng</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-[radial-gradient(ellipse_at_top_right,_#13467b_0%,_#0b2f54_45%,_#071f38_100%)] min-h-screen flex items-center justify-center p-5 relative overflow-x-hidden antialiased text-slate-800">

<!-- Subtle Background Decorative Elements -->
<div class="absolute inset-0 bg-pattern-dots pointer-events-none"></div>

<div class="bg-white w-full max-w-[27.5rem] rounded-2xl shadow-2xl overflow-hidden relative z-10 animate-modal-in">
    <!-- Header -->
    <div class="bg-gradient-to-br from-navy to-navy-dark text-white pt-8 px-6 pb-6 text-center border-b-[3px] border-brand-gold relative">
        <div class="inline-flex items-center justify-center bg-white px-5 py-2 rounded-xl mb-3.5 shadow-md border border-white/30">
            <img src="{{ asset('images/logo_bank_sulteng.png') }}" alt="Logo Bank Sulteng" class="h-9 w-auto max-w-[175px] object-contain block">
        </div>
        <h1 class="text-[1.1875rem] font-extrabold tracking-wider mb-1 text-white uppercase">PORTAL E-JURNAL</h1>
        <p class="text-[0.78125rem] text-blue-100/90 font-medium tracking-normal">PT Bank Pembangunan Daerah Sulawesi Tengah</p>
    </div>

    <!-- Body -->
    <div class="p-7 sm:p-8">
        <!-- Notifikasi Sukses Logout -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl p-3.5 text-[0.8125rem] mb-4.5 flex items-center gap-2.5 shadow-xs">
                <svg class="w-4.5 h-4.5 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Notifikasi Error Login -->
        @if (isset($errors) && $errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-900 rounded-xl p-3.5 text-[0.8125rem] mb-4.5 flex items-center gap-2.5 shadow-xs">
                <svg class="w-4.5 h-4.5 text-rose-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf

            <!-- Username Input -->
            <div class="mb-5 flex flex-col gap-1.5">
                <label for="username" class="text-[0.8125rem] font-semibold text-slate-700">Username Petugas</label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-slate-500 pointer-events-none flex items-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                    <input type="text" name="username" id="username" class="w-full py-2.5 pl-11 pr-3.5 border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none" value="{{ old('username') }}" required autofocus placeholder="Masukkan username">
                </div>
            </div>

            <!-- Password Input -->
            <div class="mb-5 flex flex-col gap-1.5">
                <label for="password" class="text-[0.8125rem] font-semibold text-slate-700">Kata Sandi</label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-slate-500 pointer-events-none flex items-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
                    <input type="password" name="password" id="password" class="w-full py-2.5 pl-11 pr-11 border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 transition-all duration-200 focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none" required placeholder="Masukkan kata sandi">
                    <button type="button" class="absolute right-3 bg-transparent border-0 text-slate-500 hover:text-slate-600 cursor-pointer p-1 flex items-center" id="togglePwd" title="Tampilkan/Sembunyikan Sandi">
                        <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full py-3 px-5 bg-gradient-to-r from-brand-blue to-navy text-white rounded-xl text-[0.9375rem] font-bold cursor-pointer shadow-lg shadow-brand-blue/30 hover:opacity-95 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-brand-blue/40 transition-all duration-200 flex items-center justify-center gap-2 mt-6.5">
                <span>Masuk ke Sistem</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
            </button>
        </form>
    </div>

    <!-- Footer -->
    <div class="px-7 py-4 text-center text-[0.71875rem] text-slate-500 border-t border-slate-200 bg-slate-50/80">
        &copy; {{ date('Y') }} PT Bank Sulteng &bull; Layanan Pengaduan & Jurnal Keluhan
    </div>
</div>

<script>
    // Toggle Password Visibility
    const togglePwd = document.getElementById('togglePwd');
    const pwdInput = document.getElementById('password');

    if(togglePwd && pwdInput) {
        togglePwd.addEventListener('click', function() {
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                togglePwd.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>`;
            } else {
                pwdInput.type = 'password';
                togglePwd.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>`;
            }
        });
    }
</script>

</body>
</html>

