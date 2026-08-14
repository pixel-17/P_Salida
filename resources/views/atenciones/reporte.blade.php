<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte — {{ $estudiante->nombre_completo }}</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;color:#1a1a2e;font-size:13px;padding:32px;background:#fff;}
.header{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:16px;margin-bottom:20px;border-bottom:3px solid #1a6b6b;}
.logo-wrap h1{font-size:22px;font-weight:700;color:#1a6b6b;display:flex;align-items:center;gap:8px;}
.logo-dot{width:8px;height:8px;background:#2a9d8f;border-radius:50%;display:inline-block;}
.logo-wrap p{font-size:11px;color:#6b7280;margin-top:2px;}
.fecha-box{text-align:right;font-size:12px;color:#6b7280;}
.fecha-box strong{display:block;font-size:13px;color:#1a1a2e;}
.titulo{text-align:center;font-size:15px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-bottom:20px;color:#1a6b6b;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;}
.section{border:1px solid #e5e7eb;border-radius:10px;padding:14px;}
.section-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#6b7280;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #f3f4f6;}
.row{display:flex;gap:8px;margin-bottom:6px;font-size:12px;align-items:flex-start;}
.row label{color:#6b7280;min-width:110px;flex-shrink:0;font-weight:500;}
.badge-sangre{background:#fde8ea;color:#be123c;padding:2px 8px;border-radius:10px;font-weight:700;font-size:12px;}
.alergias-box{background:#fff3e0;border:1px solid #ffcc80;border-radius:6px;padding:8px;color:#e65100;font-size:12px;margin-top:6px;}

/* IMC card */
.imc-section{border:1px solid #b2dfdb;border-radius:10px;padding:14px;background:linear-gradient(135deg,#e6f4f4,#f0fdf4);margin-bottom:8px;}
.imc-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;text-align:center;}
.imc-item .val{font-size:20px;font-weight:700;color:#1a6b6b;}
.imc-item .lbl{font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;}
.imc-bar-wrap{margin-top:10px;}
.imc-bar-bg{height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;position:relative;}
.imc-bar-fill{height:100%;border-radius:4px;transition:width .5s;}
.imc-labels{display:flex;justify-content:space-between;font-size:9px;color:#9ca3af;margin-top:3px;}
.imc-badge{display:inline-block;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:700;}

table{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;}
th{background:#f3f4f6;padding:8px 10px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;}
td{padding:8px 10px;border-bottom:1px solid #f3f4f6;vertical-align:top;}
.num{font-family:'DM Mono',monospace;font-size:11px;color:#9ca3af;text-align:center;width:30px;}
.recurrente{background:#fef3c7;color:#92400e;padding:2px 7px;border-radius:10px;font-size:10px;font-weight:700;}
.normal{background:#d1fae5;color:#065f46;padding:2px 7px;border-radius:10px;font-size:10px;font-weight:700;}

.firma{margin-top:40px;display:flex;justify-content:flex-end;}
.firma-box{text-align:center;}
.firma-line{width:200px;border-top:1.5px solid #1a1a2e;padding-top:6px;font-size:12px;color:#1a1a2e;font-weight:500;}
.firma-sub{font-size:11px;color:#6b7280;margin-top:2px;}

.print-btn{position:fixed;bottom:24px;right:24px;background:#1a6b6b;color:#fff;border:none;border-radius:50px;padding:12px 22px;font-size:14px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;box-shadow:0 4px 16px rgba(26,107,107,.4);}
.print-btn:hover{background:#134f4f;}

@media print{.print-btn{display:none;}body{padding:20px;}}
</style>
</head>
<body>

<div class="header">
    <div class="logo-wrap">
        <h1><span class="logo-dot"></span> S_Topic</h1>
        <p>IEST Público "Túpac Amaru" — Cusco</p>
        <p>Unidad de Bienestar y Empleabilidad — Tópico</p>
    </div>
    <div class="fecha-box">
        <span>Fecha de emisión:</span>
        <strong>{{ now()->format('d/m/Y H:i') }}</strong>
        <div style="margin-top:6px;font-size:11px;">Emitido por:<br><strong style="color:#1a1a2e;">{{ auth()->user()->nombre_completo }}</strong></div>
    </div>
</div>

<div class="titulo">📋 Reporte de Historial Clínico</div>

<div class="grid2">
    <div class="section">
        <div class="section-title">Datos del estudiante</div>
        <div class="row"><label>Nombre:</label><strong>{{ $estudiante->nombre_completo }}</strong></div>
        <div class="row"><label>DNI:</label><span style="font-family:'DM Mono',monospace;font-weight:600;">{{ $estudiante->dni }}</span></div>
        <div class="row"><label>Género:</label>{{ $estudiante->genero==='M'?'Masculino':'Femenino' }}</div>
        <div class="row"><label>Fecha nac.:</label>{{ $estudiante->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</div>
        <div class="row"><label>Carrera:</label>{{ $estudiante->carrera->nombre ?? '—' }}</div>
        <div class="row"><label>Semestre:</label>{{ $estudiante->semestre ?? '—' }}</div>
        <div class="row"><label>Teléfono:</label>{{ $estudiante->telefono ?? '—' }}</div>
    </div>

    <div>
        <div class="section" style="margin-bottom:10px;">
            <div class="section-title">🩺 Ficha médica</div>
            @if($estudiante->fichaMedica)
                <div class="row">
                    <label>Tipo sangre:</label>
                    @if($estudiante->fichaMedica->tipo_sangre)
                        <span class="badge-sangre">🩸 {{ $estudiante->fichaMedica->tipo_sangre }}</span>
                    @else <span>—</span> @endif
                </div>
                @if($estudiante->fichaMedica->alergias)
                    <div class="alergias-box">⚠️ <strong>Alergias:</strong> {{ $estudiante->fichaMedica->alergias }}</div>
                @endif
                @if($estudiante->fichaMedica->enfermedades_previas)
                    <div class="row" style="margin-top:6px;"><label>Enf. previas:</label>{{ $estudiante->fichaMedica->enfermedades_previas }}</div>
                @endif
                @if($estudiante->fichaMedica->medicamentos_actuales)
                    <div class="row"><label>Medicamentos:</label>{{ $estudiante->fichaMedica->medicamentos_actuales }}</div>
                @endif
                @if($estudiante->fichaMedica->contacto_emergencia)
                    <div class="row"><label>Emergencia:</label>{{ $estudiante->fichaMedica->contacto_emergencia }} — {{ $estudiante->fichaMedica->telefono_emergencia }}</div>
                @endif
            @else
                <p style="color:#9ca3af;font-size:12px;">Sin ficha médica.</p>
            @endif
        </div>

        {{-- IMC Card --}}
        @if($estudiante->fichaMedica?->imc)
        @php
            $imc = $estudiante->fichaMedica->imc;
            $clasificacion = $estudiante->fichaMedica->clasificacion_imc;
            $pct = min(100, max(0, (($imc - 10) / 30) * 100));
            $colorImc = match(true) {
                $imc < 18.5 => '#f59e0b',
                $imc < 25   => '#22c55e',
                $imc < 30   => '#f97316',
                default     => '#ef4444'
            };
            $bgImc = match(true) {
                $imc < 18.5 => '#fef3c7',
                $imc < 25   => '#d1fae5',
                $imc < 30   => '#fff3e0',
                default     => '#fee2e2'
            };
        @endphp
        <div class="imc-section">
            <div class="section-title">📊 Índice de Masa Corporal (IMC)</div>
            <div class="imc-grid">
                <div class="imc-item">
                    <div class="val">{{ $estudiante->fichaMedica->peso }}<span style="font-size:12px;font-weight:400;">kg</span></div>
                    <div class="lbl">Peso</div>
                </div>
                <div class="imc-item">
                    <div class="val">{{ $estudiante->fichaMedica->talla }}<span style="font-size:12px;font-weight:400;">m</span></div>
                    <div class="lbl">Talla</div>
                </div>
                <div class="imc-item">
                    <div class="val" style="color:{{ $colorImc }};">{{ $imc }}</div>
                    <div class="lbl">IMC</div>
                </div>
            </div>
            <div class="imc-bar-wrap">
                <div class="imc-bar-bg">
                    <div class="imc-bar-fill" style="width:{{ $pct }}%;background:{{ $colorImc }};"></div>
                </div>
                <div class="imc-labels"><span>Bajo</span><span>Normal</span><span>Sobrepeso</span><span>Obeso</span></div>
            </div>
            <div style="text-align:center;margin-top:8px;">
                <span class="imc-badge" style="background:{{ $bgImc }};color:{{ $colorImc }};">{{ $clasificacion }}</span>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="section">
    <div class="section-title">Historial de atenciones — Total: {{ $atenciones->count() }} registro(s)</div>
    <table>
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Fecha</th>
                <th>Motivo</th>
                <th>Diagnóstico</th>
                <th>Tratamiento</th>
                <th>Enfermera</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($atenciones as $i => $at)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td style="font-family:'DM Mono',monospace;font-size:11px;white-space:nowrap;">{{ $at->fecha->format('d/m/Y H:i') }}</td>
                <td><strong>{{ $at->motivo->nombre }}</strong><br><span style="font-size:10px;color:#9ca3af;">{{ $at->motivo->categoria->nombre ?? '' }}</span></td>
                <td>{{ $at->diagnostico ?? '—' }}</td>
                <td>{{ $at->tratamiento ?? '—' }}</td>
                <td style="white-space:nowrap;">{{ $at->enfermera->name }}</td>
                <td>
                    @if($at->es_recurrente)<span class="recurrente">⚠ Recurrente</span>
                    @else<span class="normal">✓ Normal</span>@endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;color:#9ca3af;padding:20px;">Sin atenciones registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="firma">
    <div class="firma-box">
        <div class="firma-line">{{ auth()->user()->nombre_completo }}</div>
        <div class="firma-sub">Personal del Tópico — ISTTA</div>
    </div>
</div>

<button class="print-btn no-print" onclick="window.print()">🖨️ Imprimir reporte</button>

</body>
</html>
