@extends('layouts.app')
@section('title','Reportes')
@section('page-title','Reportes Estadísticos')

@section('content')

{{-- Header --}}
<div class="page-header">
    <div>
        <h1>Reportes del Tópico</h1>
        <p>Análisis estadístico de atenciones médicas — {{ now()->locale('es')->isoFormat('MMMM YYYY') }}</p>
    </div>
</div>

{{-- Stats rápidos --}}
<div class="stats-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:22px;">
    <div class="stat-card" style="border-left:4px solid var(--accent);">
        <div class="stat-icon" style="background:var(--accent-lt);">📅</div>
        <div>
            <div class="value counter" data-val="{{ $atencionsMes }}">0</div>
            <div class="label">Atenciones este mes</div>
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--warn);">
        <div class="stat-icon" style="background:var(--warn-lt);">⚠️</div>
        <div>
            <div class="value counter" data-val="{{ $casosRecurrentes->count() }}" style="color:#c2410c;">0</div>
            <div class="label">Casos recurrentes</div>
        </div>
    </div>
</div>

{{-- Grid: Motivos + Casos recurrentes ARRIBA --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">

    {{-- Motivos frecuentes --}}
    <div class="card">
        <div style="font-size:15px;font-weight:700;margin-bottom:4px;">🏆 Motivos más frecuentes</div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:18px;">Top 10 motivos de consulta</div>
        <div style="display:flex;flex-direction:column;gap:14px;" id="motivos-list">
            @forelse($motivosFrecuentes as $i => $m)
            @php
                $pct = $motivosFrecuentes->first()->atenciones_count > 0
                    ? round($m->atenciones_count / $motivosFrecuentes->first()->atenciones_count * 100)
                    : 0;
                $colors = ['var(--accent)','var(--primary)','#8b5cf6','#f59e0b','#ec4899','#06b6d4','#10b981','#f97316','#6366f1','#14b8a6'];
                $color = $colors[$i] ?? 'var(--muted)';
            @endphp
            <div class="motivo-row"
                style="opacity:0;transform:translateX(-24px);
                       transition:opacity .7s ease {{ $i * 0.18 }}s,
                                  transform .7s cubic-bezier(.34,1.2,.64,1) {{ $i * 0.18 }}s;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="width:22px;height:22px;border-radius:50%;background:{{ $color }};
                               display:flex;align-items:center;justify-content:center;
                               font-size:11px;font-weight:700;color:#fff;flex-shrink:0;
                               box-shadow:0 2px 6px {{ $color }}66;">{{ $i + 1 }}</span>
                        <span style="font-size:13px;font-weight:500;">{{ $m->nombre }}</span>
                    </div>
                    <span style="font-size:13px;font-weight:700;color:{{ $color }};">{{ $m->atenciones_count }}</span>
                </div>
                <div style="background:var(--border);border-radius:4px;height:7px;overflow:hidden;">
                    <div class="bar-motivo"
                         style="height:100%;background:{{ $color }};width:0;border-radius:4px;
                                transition:width 1.1s cubic-bezier(.34,1.1,.64,1) {{ $i * 0.18 }}s;"
                         data-pct="{{ $pct }}">
                    </div>
                </div>
            </div>
            @empty
            <p style="color:var(--muted);font-size:13px;">Sin datos.</p>
            @endforelse
        </div>
    </div>

    {{-- Casos recurrentes --}}
    <div class="card">
        <div style="font-size:15px;font-weight:700;margin-bottom:4px;">⚠️ Casos recurrentes</div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:16px;">Pacientes con atenciones repetidas</div>
        @forelse($casosRecurrentes as $i => $at)
        <div class="rec-item"
             style="display:flex;align-items:center;gap:12px;padding:12px 0;
                    border-bottom:1px solid var(--border);
                    opacity:0;transform:translateY(12px);
                    transition:opacity .65s ease {{ $i * 0.2 }}s,
                               transform .65s cubic-bezier(.34,1.2,.64,1) {{ $i * 0.2 }}s;">
            <div style="width:38px;height:38px;border-radius:50%;
                        background:var(--warn-lt);border:2px solid var(--warn);
                        display:flex;align-items:center;justify-content:center;
                        font-size:14px;font-weight:700;color:#c2410c;flex-shrink:0;">
                {{ strtoupper(substr($at->estudiante->nombres, 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;
                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $at->estudiante->nombre_completo }}
                </div>
                <div style="font-size:11.5px;color:var(--muted);">{{ $at->motivo->nombre }}</div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:11px;font-family:'DM Mono',monospace;color:var(--muted);">
                    {{ $at->fecha->format('d/m/Y') }}
                </div>
                <span class="badge badge-warning" style="font-size:10px;">⚠ Recurrente</span>
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:40px;color:var(--muted);">
            <div style="font-size:36px;margin-bottom:10px;">✅</div>
            <div style="font-size:13px;">Sin casos recurrentes recientes</div>
        </div>
        @endforelse
    </div>

</div>

{{-- Gráfico de barras por mes ABAJO --}}
<div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <div>
            <div style="font-size:15px;font-weight:700;">📈 Atenciones por mes</div>
            <div style="font-size:12px;color:var(--muted);">Últimos 6 meses</div>
        </div>
        <div style="font-size:12px;color:var(--muted);background:var(--card2);
                    padding:5px 14px;border-radius:20px;border:1px solid var(--border);">
            Total: <strong>{{ $atencionsPorMes->sum('total') }}</strong> atenciones
        </div>
    </div>

    @php $maxVal = $atencionsPorMes->max('total') ?: 1; @endphp

    <div style="display:flex;align-items:flex-end;gap:10px;height:200px;padding:0 8px;">
        @forelse($atencionsPorMes as $i => $mes)
        @php
            $pct     = ($mes->total / $maxVal) * 100;
            $mesNombre = \Carbon\Carbon::create($mes->anio, $mes->mes)->locale('es')->monthName;
            $isMax   = $mes->total == $maxVal;
        @endphp
        <div class="bar-col"
             style="flex:1;display:flex;flex-direction:column;
                    align-items:center;gap:8px;height:100%;justify-content:flex-end;">

            {{-- Número encima --}}
            <div class="bar-value"
                 style="font-size:13px;font-weight:700;
                        color:{{ $isMax ? 'var(--accent)' : 'var(--text)' }};
                        opacity:0;transform:translateY(14px);
                        transition:opacity .8s ease {{ $i * 0.22 }}s,
                                   transform .8s cubic-bezier(.34,1.3,.64,1) {{ $i * 0.22 }}s;">
                {{ $mes->total }}
            </div>

            {{-- Barra --}}
            <div class="bar-fill"
                 style="width:100%;border-radius:8px 8px 0 0;
                        background:{{ $isMax
                            ? 'linear-gradient(to top,var(--primary),var(--accent))'
                            : 'linear-gradient(to top,var(--border2),var(--muted))' }};
                        height:0;max-height:{{ $pct }}%;
                        transition:height 1.2s cubic-bezier(.34,1.3,.64,1) {{ $i * 0.22 }}s;
                        cursor:pointer;position:relative;"
                 data-total="{{ $mes->total }}"
                 title="{{ $mes->total }} atenciones en {{ $mesNombre }}">
                @if($isMax)
                <div style="position:absolute;top:-9px;left:50%;transform:translateX(-50%);
                            width:9px;height:9px;background:var(--accent);border-radius:50%;
                            box-shadow:0 0 0 3px var(--accent-lt);
                            animation:ping 2s ease infinite;"></div>
                @endif
            </div>

            {{-- Etiqueta mes --}}
            <div style="text-align:center;line-height:1.3;">
                <div style="font-size:11.5px;font-weight:600;
                            color:var(--text);text-transform:capitalize;">
                    {{ mb_substr($mesNombre, 0, 3) }}
                </div>
                <div style="font-size:10px;color:var(--muted);">{{ $mes->anio }}</div>
            </div>
        </div>
        @empty
        <div style="flex:1;display:flex;align-items:center;justify-content:center;
                    color:var(--muted);font-size:13px;">
            Sin datos disponibles.
        </div>
        @endforelse
    </div>
</div>

<style>
@keyframes ping {
    0%, 100% { box-shadow: 0 0 0 0 rgba(42,157,143,.5); }
    50%       { box-shadow: 0 0 0 7px rgba(42,157,143,0); }
}
</style>

<script>
// ── Contadores suaves ──
document.querySelectorAll('.counter').forEach(el => {
    const target = parseInt(el.dataset.val) || 0;
    let c = 0;
    const step = Math.max(1, Math.floor(target / 40));
    const t = setInterval(() => {
        c = Math.min(c + step, target);
        el.textContent = c;
        if (c >= target) clearInterval(t);
    }, 60); // más lento → 60ms por tick
});

// ── Disparar animaciones con IntersectionObserver ──
const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (!entry.isIntersecting) return;

        // Barras de meses
        document.querySelectorAll('.bar-fill').forEach(b => {
            b.style.height = (parseFloat(b.style.maxHeight) || 0) + '%';
        });
        document.querySelectorAll('.bar-value').forEach(v => {
            v.style.opacity = '1';
            v.style.transform = 'translateY(0)';
        });

        // Barras de motivos
        document.querySelectorAll('.bar-motivo').forEach(b => {
            b.style.width = b.dataset.pct + '%';
        });
        document.querySelectorAll('.motivo-row').forEach(r => {
            r.style.opacity = '1';
            r.style.transform = 'translateX(0)';
        });

        // Casos recurrentes
        document.querySelectorAll('.rec-item').forEach(r => {
            r.style.opacity = '1';
            r.style.transform = 'translateY(0)';
        });

        observer.disconnect();
    });
}, { threshold: 0.05 });

observer.observe(document.querySelector('.stats-grid') || document.body);

// ── Hover en barras de meses ──
document.querySelectorAll('.bar-fill').forEach(b => {
    b.addEventListener('mouseenter', function () {
        this.style.filter = 'brightness(1.15)';
        this.style.transform = 'scaleY(1.04)';
        this.style.transformOrigin = 'bottom';
        this.style.transition += ',filter .2s,transform .2s';
    });
    b.addEventListener('mouseleave', function () {
        this.style.filter = '';
        this.style.transform = '';
    });
});
</script>
@endsection