<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#118EEA">
  <title>Login</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-dvh bg-[#F5F7FB] text-slate-900 flex items-center justify-center p-4">
  <div class="w-full max-w-sm">
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
      <div class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-2xl bg-[#118EEA] flex items-center justify-center text-white font-bold">
          K
        </div>
        <div>
          <div class="text-xl font-semibold leading-tight">Masuk</div>
          <div class="text-sm text-slate-500">Akses dibatasi. Bukan buat umum.</div>
        </div>
      </div>

      <form class="mt-5 space-y-3" method="POST" action="{{ route('login.store') }}">
        @csrf

        <div>
          <label class="text-sm text-slate-600">Nama</label>
          <input name="name" value="{{ old('name') }}"
                 class="mt-1 w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                        focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20"
                 autocomplete="username" required>
          @error('name')
            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
          @enderror
        </div>

        <div>
          <label class="text-sm text-slate-600">Password</label>
          <input type="password" name="password"
                 class="mt-1 w-full rounded-xl bg-white border border-slate-200 px-3 py-2 outline-none
                        focus:border-[#118EEA] focus:ring-2 focus:ring-[#118EEA]/20"
                 autocomplete="current-password" required>
          @error('password')
            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
          @enderror
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-[#118EEA] py-2.5 font-semibold text-white shadow-sm
                       active:scale-[0.99]">
          Login
        </button>
      </form>

      <div class="mt-4 text-xs text-slate-400">
        Tip: pasang ke Home Screen biar kerasa kayak app beneran.
      </div>
    </div>
  </div>
</body>
</html>
