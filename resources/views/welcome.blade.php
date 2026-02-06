<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#070a12">
  <title>{{ config('app.name', 'KawatApp') }}</title>

  @vite(['resources/css/app.css','resources/js/app.js'])

  <style>
    :root{
      --glass: rgba(10, 14, 28, .58);
      --glass2: rgba(10, 18, 38, .72);
      --stroke: rgba(255,255,255,.16);
      --glow1: rgba(56,189,248,.20);
      --glow2: rgba(109,40,217,.18);
    }
    body{ margin:0; background:#050713; }

    /* mobile-first scene */
    .page{
      min-height: 100svh;
      position: relative;
      overflow: hidden;
      color:#fff;
      background: #050713;
      isolation: isolate; /* keeps blend effects sane */
    }

    /* BACKGROUND (your new one) */
    .bg{
      position:absolute; inset:0;
      background-image: url("{{ asset('landing/bg-mobile.png') }}");
      background-size: cover;
      background-repeat: no-repeat;
      background-position: 28% 50%;
      opacity: .98;
      pointer-events:none;
      transform: translate3d(0,0,0);
      animation: bgDrift 24s ease-in-out infinite alternate;
      filter: saturate(108%) contrast(106%);
    }
    @keyframes bgDrift{
      0%{ transform: scale(1.05) translate(-1.3%, -0.6%); }
      100%{ transform: scale(1.08) translate(1.2%, 0.8%); }
    }

    /* extra readability overlays */
    .topFade{
      position:absolute; inset:0;
      background: linear-gradient(180deg, rgba(0,0,0,.45), transparent 38%);
      pointer-events:none;
      z-index: 2;
    }
    .bottomFade{
      position:absolute; inset:0;
      background: linear-gradient(180deg, transparent 40%, rgba(0,0,0,.60) 86%, rgba(0,0,0,.80));
      pointer-events:none;
      z-index: 2;
    }
    .vignette{
      position:absolute; inset:0;
      background: radial-gradient(circle at 50% 25%, transparent 28%, rgba(0,0,0,.32) 70%, rgba(0,0,0,.62));
      pointer-events:none;
      z-index: 2;
    }

    /* tiny star sparkle layer (biar hidup dikit) */
    .stars{
      position:absolute; inset:-20%;
      background-repeat: repeat;
      background-size: 260px 260px;
      opacity: .33;
      pointer-events:none;
      z-index: 1;
      background-image:
        radial-gradient(1px 1px at 20px 30px, rgba(255,255,255,.75) 1px, transparent 1px),
        radial-gradient(1px 1px at 90px 120px, rgba(255,255,255,.65) 1px, transparent 1px),
        radial-gradient(1px 1px at 160px 60px, rgba(255,255,255,.55) 1px, transparent 1px),
        radial-gradient(1px 1px at 210px 200px, rgba(255,255,255,.70) 1px, transparent 1px);
      animation: starScroll 95s linear infinite;
    }
    @keyframes starScroll{ from{ transform: translateY(0);} to{ transform: translateY(220px);} }

    /* float anims */
    .floaty{ animation: floaty 6.2s ease-in-out infinite; }
    .floaty2{ animation: floaty2 8.2s ease-in-out infinite; }
    @keyframes floaty{
      0%{ transform: translateY(0) rotate(-1deg); }
      50%{ transform: translateY(-14px) rotate(1deg); }
      100%{ transform: translateY(0) rotate(-1deg); }
    }
    @keyframes floaty2{
      0%{ transform: translateY(0) rotate(1deg); }
      50%{ transform: translateY(-18px) rotate(-1deg); }
      100%{ transform: translateY(0) rotate(1deg); }
    }

    /* DECOR layer */
    .deco{
      position:absolute; inset:0;
      pointer-events:none;
      z-index: 5;
    }

    /* portal vortex (center-ish) */
    .portal{
      position:absolute;
      left: 58%;
      top: 44%;
      width: min(72vw, 420px);
      aspect-ratio: 1 / 1;
      transform: translate(-50%,-50%);
      opacity: .52;
      mix-blend-mode: screen;
      filter: blur(.2px) drop-shadow(0 0 26px rgba(56,189,248,.18));
      animation: spin 22s linear infinite;
    }
    @keyframes spin{
      from{ transform: translate(-50%,-50%) rotate(0deg); }
      to{ transform: translate(-50%,-50%) rotate(360deg); }
    }
    @media (max-width: 768px) {
      .portal {
        left: auto;
        right: 0;   /* Menempel tepat di pinggir kanan layar */
        top: 0;     /* Menempel tepat di pinggir atas layar */
        
        /* Reset transform agar tidak ditarik ke tengah */
        transform: translate(0, 0); 
        
        /* Menjalankan animasi baru khusus mobile agar posisinya tidak 'loncat' */
        animation: spinMobile 22s linear infinite;
      }
    }

    /* Animasi khusus mobile tanpa translate(-50%, -50%) */
    @keyframes spinMobile {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    /* astronaut swing (top-left) - remove white bg using multiply */
    .astroSwing{
      position:absolute;
      left: 10px;
      top: 36px;
      width: clamp(120px, 32vw, 190px);
      height: auto;
      opacity: .95;
      mix-blend-mode: multiply; /* makes white background disappear-ish */
      filter: drop-shadow(0 16px 22px rgba(0,0,0,.38)) drop-shadow(0 0 14px var(--glow2));
    }

    /* astronaut realistic (bottom-right) */
    .astroReal{
      position:absolute;
      right: -6px;
      bottom: 78px; /* keep above buttons */
      width: clamp(160px, 44vw, 320px);
      height: auto;
      opacity: .95;
      transform: rotate(8deg);
      filter: drop-shadow(0 24px 34px rgba(0,0,0,.55)) drop-shadow(0 0 18px var(--glow1));
    }

    /* UI buttons pinned bottom */
    .ui{
      position: relative;
      z-index: 10;
      min-height: 100svh;
      display:flex;
      align-items:flex-end;
      justify-content:center;
      padding:
        18px
        18px
        calc(22px + env(safe-area-inset-bottom))
        18px;
    }
    .stack{
      width: min(92vw, 420px);
      display:flex;
      flex-direction:column;
      gap: 12px;
      padding-bottom: 4svh;
    }

    .pill{
      position:relative;
      border-radius: 999px;
      overflow: hidden;
      text-decoration:none;
      display:block;
      transform: translate3d(0,0,0);
    }
    .pill::before{
      content:"";
      position:absolute; inset:-2px;
      background: conic-gradient(from 180deg,
        rgba(56,189,248,.85),
        rgba(109,40,217,.70),
        rgba(34,197,94,.55),
        rgba(56,189,248,.85)
      );
      filter: blur(10px);
      opacity: .55;
      transition: opacity .22s ease;
    }
    .pill:active::before{ opacity: .90; }

    .pill > span{
      position:relative;
      width:100%;
      display:flex;
      align-items:center;
      justify-content:center;
      gap: 10px;
      padding: 14px 22px;
      border-radius: 999px;
      background: var(--glass);
      border: 1px solid var(--stroke);
      backdrop-filter: blur(12px);
      color: rgba(255,255,255,.94);
      font-weight: 800;
      letter-spacing: .2px;
      transition: transform .16s ease, background .16s ease, border-color .16s ease;
      box-shadow: 0 18px 40px rgba(0,0,0,.35);
    }
    .pill:active > span{
      transform: scale(.985);
      background: var(--glass2);
      border-color: rgba(255,255,255,.24);
    }

    .shine{
      position:absolute; inset:0;
      background: linear-gradient(120deg, transparent 35%, rgba(255,255,255,.18), transparent 65%);
      transform: translateX(-120%);
      animation: shine 3.2s ease-in-out infinite;
      pointer-events:none;
      mix-blend-mode: screen;
    }
    @keyframes shine{
      0%{ transform: translateX(-120%); opacity:0; }
      30%{ opacity:.6; }
      60%{ transform: translateX(120%); opacity:0; }
      100%{ transform: translateX(120%); opacity:0; }
    }

    /* glow behind buttons for readability */
    .halo{
      position:absolute;
      left:50%;
      bottom: calc(22px + env(safe-area-inset-bottom));
      width: min(92vw, 420px);
      height: 220px;
      transform: translateX(-50%);
      background: radial-gradient(circle at 50% 30%, rgba(56,189,248,.18), transparent 70%);
      filter: blur(24px);
      opacity: .85;
      pointer-events:none;
      z-index: 9;
    }

    @media (prefers-reduced-motion: reduce){
      .bg,.stars,.portal,.shine,.floaty,.floaty2{ animation:none !important; }
    }
  </style>
</head>

<body>
  <main class="page">
    <div class="bg"></div>
    <div class="stars"></div>

    <div class="deco">
      <img class="portal" src="{{ asset('landing/portal.png') }}" alt="" aria-hidden="true">
      <img class="astroSwing floaty" src="{{ asset('landing/astro-swing.png') }}" alt="" aria-hidden="true">
      <img class="astroReal floaty2" src="{{ asset('landing/astro-real.png') }}" alt="" aria-hidden="true">
    </div>

    <div class="topFade"></div>
    <div class="bottomFade"></div>
    <div class="vignette"></div>

    <div class="halo"></div>

    <div class="ui">
      <div class="stack">
        <a href="{{ route('login') }}" class="pill">
          <span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="opacity-90">
              <path d="M10 17l5-5-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M15 12H3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M21 4v16" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity=".5"/>
            </svg>
            Login
            <i class="shine"></i>
          </span>
        </a>

        <a href="/app/home" class="pill">
          <span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="opacity-90">
              <path d="M4 12l8-8 8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6 10v10h12V10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity=".95"/>
            </svg>
            Buka App
            <i class="shine"></i>
          </span>
        </a>
      </div>
    </div>
  </main>
</body>
</html>
