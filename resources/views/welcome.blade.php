<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#118EEA">
  <title>{{ config('app.name', 'KawatApp') }}</title>

  {{-- kalau Vite kamu sudah bener di production --}}
  @vite(['resources/css/app.css','resources/js/app.js'])

  <style>
    .r30 { border-radius: 30px; }
    .shadow-soft { box-shadow: 0 18px 45px rgba(2, 8, 23, .14); }
  </style>
</head>
<body class="bg-slate-50 text-slate-900">

  <div class="min-h-[100svh] flex items-stretch justify-center bg-slate-100">
    <div class="w-full max-w-md bg-white rounded-[30px] overflow-hidden shadow-lg">


      {{-- HERO --}}
      <header class="relative overflow-hidden">
        
        <div
            class="bg-[#118EEA] text-white px-5 pt-10 pb-10
                    min-h-[32vh] flex flex-col justify-between
                    rounded-b-[30px]">

            {{-- TOP --}}
            <br>
            <div class="flex items-center gap-3">
            <br>
            <div class="h-12 w-12 bg-white/15 border border-white/25
                        flex items-center justify-center
                        rounded-b-[22px] rounded-t-none">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" class="opacity-95">
                <path d="M12 2l3 7 7 3-7 3-3 7-3-7-7-3 7-3 3-7z"
                        stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
            </div>
            
            <div>
                <div class="text-xs text-white/80">Selamat datang di</div>
                <div class="text-2xl font-extrabold tracking-wide">
                {{ config('app.name', 'KawatApp') }}
                </div>
            </div>
            </div>

            {{-- DESC --}}
            <div class="mt-4 text-sm text-white/90 leading-snug max-w-[26ch]">
            Catat stok kawat, transaksi harian, dan laporan periodik.
            </div>
            <br>
            {{-- CTA --}}
            <div class="mt-6 flex gap-2">
            <a href="{{ route('login') }}"
                class="flex-1 inline-flex items-center justify-center
                        rounded-2xl bg-white text-[#118EEA]
                        font-bold py-3 active:scale-[0.98]">
                Login
            </a>

            <a href="/app/home"
                class="flex-1 inline-flex items-center justify-center
                        rounded-2xl bg-white/10 border border-white/25
                        text-white font-bold py-3 active:scale-[0.98]">
                Buka App
            </a>
            </div>
            <br>
        </div>
      </header>
    </div>
  </div>

</body>
</html>
