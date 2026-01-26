@php $rows = $rows ?? []; @endphp

<div class="space-y-3">

  @if(empty($rows))
    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm text-sm text-slate-500">
      Belum ada log.
    </div>
  @else
    <div class="space-y-2">
      @foreach($rows as $r)
        @php
          $ok = ($r['status'] ?? '') === 'SENT';
          $badge = $ok ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                       : 'bg-rose-50 text-rose-700 border-rose-200';
        @endphp

        <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full border {{ $badge }}">
                {{ $r['status'] }}
              </span>

              <div class="mt-2 text-sm font-semibold truncate">{{ $r['subject'] }}</div>
              <div class="mt-1 text-xs text-slate-400">
                Mode: {{ $r['mode'] }} • Base: {{ $r['base_date'] }} • To: {{ $r['to_email'] }}
              </div>

              @if(!$ok && !empty($r['error_message']))
                <div class="mt-2 text-xs text-rose-700">
                  {{ $r['error_message'] }}
                </div>
              @endif
            </div>

            <div class="text-right shrink-0">
              <div class="text-xs text-slate-500">Sent at</div>
              <div class="text-sm font-semibold">{{ $r['sent_at'] ?? '-' }}</div>

              <form method="POST" action="{{ route('app.reports.resend') }}" class="mt-2">
                @csrf
                <input type="hidden" name="mode" value="{{ $r['mode'] }}">
                <input type="hidden" name="date" value="{{ $r['base_date'] }}">
                <button class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700">
                  Resend
                </button>
              </form>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
