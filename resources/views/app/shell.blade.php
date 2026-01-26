<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#118EEA">
  <meta name="theme-color" content="#118EEA">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>Arbicon</title>
  <link rel="manifest" href="/manifest.webmanifest">

  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-[#F5F7FB] text-slate-900">
  <div id="appSplash" class="fixed inset-0 z-[999] flex items-center justify-center bg-[#118EEA]">
    <div class="text-center text-white">
      <div class="mx-auto h-16 w-16 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" class="opacity-95">
          <path d="M12 2l3 7 7 3-7 3-3 7-3-7-7-3 7-3 3-7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="mt-4 text-xl font-bold tracking-wide">Arbicon</div>
      <div class="mt-1 text-xs text-white/80">Loading…</div>
    </div>
  </div>

  <div id="app" class="min-h-dvh flex flex-col">
    {{-- Top bar (DANA-ish) --}}
    <header class="sticky top-0 z-20 overflow-hidden">
      <div class="bg-[#118EEA] px-4 pt-3 pb-5 rounded-b-[30px] shadow-lg">
        <div class="flex items-center justify-between text-white">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-white/15 flex items-center justify-center border border-white/25 text-white">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" class="opacity-95">
                <path d="M12 2l3 7 7 3-7 3-3 7-3-7-7-3 7-3 3-7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                <path d="M5 21c2.4-1.4 4.6-1.4 7 0 2.4 1.4 4.6 1.4 7 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
              </svg>
            </div>

            <div>
              <div class="text-xxl font-bold" id="greeting">Selamat ...</div>
              <div class="font-bold text-xl leading-tight">Pak Bos</div>
            </div>
          </div>

          <div class="flex gap-2">
            <button id="btnMenu"
              class="h-10 w-10 rounded-full bg-white/10 flex items-center justify-center text-white font-bold"
              aria-label="Menu">
              ⋮
            </button>
          </div>
        </div>
      </div>
    </header>
    <div id="menuOverlay" class="hidden fixed inset-0 z-30">
      <div class="absolute inset-0 bg-black/20"></div>
      <div class="absolute right-3 top-16 w-44 rounded-2xl bg-white border border-slate-200 shadow-lg overflow-hidden">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="w-full px-4 py-3 text-left text-sm font-semibold text-rose-700 hover:bg-slate-50">
            Keluar
          </button>
        </form>
      </div>
    </div>

    <div id="pjaxLoading" class="hidden h-1 w-full bg-[#118EEA]/20">
      <div class="h-1 w-1/3 bg-[#118EEA] animate-pulse"></div>
    </div>

    {{-- Main --}}
    <main id="appMain" class="flex-1 px-4 py-4">
      {!! $initialHtml ?? '' !!}
    </main>

    {{-- Bottom nav --}}
    <nav class="sticky bottom-0 z-20 bg-[#118EEA] rounded-t-[32px] shadow-[0_-8px_30px_rgba(0,0,0,0.1)]">
      <div id="nav-container" class="grid grid-cols-5 gap-1 pt-3 pb-5 px-2 text-[10px] font-medium text-white/70">

        <a href="/app/home" data-nav="/app/home" class="flex flex-col items-center justify-center transition-all duration-200">
          <svg viewBox="0 0 24 24" fill="currentColor" class="w-7 h-7">
            <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z"/>
          </svg>
          <div class="mt-1 text-sm text-200 color:white">Beranda</div>
        </a>

        <a href="/app/stock" data-nav="/app/stock" class="flex flex-col items-center justify-center transition-all duration-200">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-7 h-7">
            <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/>
          </svg>
          <div class="mt-1 text-sm text-200 color:white">Stok</div>
        </a>

        <a href="/app/input" data-nav="/app/input" class="relative flex flex-col items-center justify-center">
          <div class="btn-input -mt-12 h-14 w-14 bg-white rounded-2xl shadow-xl flex items-center justify-center text-[#118EEA] border-[4px] border-[#118EEA] active:scale-90 transition-transform">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" class="w-7 h-7">
              <path d="M12 5v14M5 12h14" stroke-linecap="round"/>
            </svg>
          </div>
          <div class="mt-1 text-sm text-200 color:white">Input</div>
        </a>

        <a href="/app/history" data-nav="/app/history" class="flex flex-col items-center justify-center transition-all duration-200">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-7 h-7">
            <path d="M12 8v5l3 2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3 12a9 9 0 1 0 3-6.7" stroke-linecap="round"/>
          </svg>
          <div class="mt-1 text-sm text-200 color:white">Riwayat</div>
        </a>

        <a href="/app/reports" data-nav="/app/reports" class="flex flex-col items-center justify-center transition-all duration-200">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-7 h-7">
            <path d="M7 3h7l3 3v15a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z" stroke-linejoin="round"/>
          </svg>
          <div class="mt-1 text-sm text-200 color:white">Laporan</div>
        </a>

      </div>
    </nav>

  </div>
  <div id="exitModal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/30"></div>
    <div class="absolute inset-x-0 bottom-0 p-4">
      <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-lg">
        <div class="text-sm font-semibold">Keluar aplikasi?</div>
        <div class="mt-1 text-sm text-slate-500">Tekan “Keluar” untuk kembali.</div>

        <div class="mt-4 grid grid-cols-2 gap-3">
          <button id="exitNo" class="rounded-xl border border-slate-200 py-2 font-semibold">
            Batal
          </button>
          <button id="exitYes" class="rounded-xl bg-[#118EEA] py-2 font-semibold text-white">
            Keluar
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <script>
    (function () {
      if (!('serviceWorker' in navigator)) return;

      window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js').catch(function () {
        });
      });
    })();
  </script>
</body>
</html>
