@extends('layouts.app')

@section('title', 'Escritorio | SAIDE Biblioteca')

@section('content')
@php
    $libros = (int) ($estadisticas['libros'] ?? 0);
    $ejemplares = (int) ($estadisticas['ejemplares'] ?? 0);
    $usuarios = (int) ($estadisticas['usuarios'] ?? 0);
    $prestamos = (int) ($estadisticas['prestamos'] ?? 0);
    $activos = (int) ($estadisticas['activos'] ?? 0);
    $atrasados = (int) ($estadisticas['atrasados'] ?? 0);
    $devueltos = (int) ($estadisticas['devueltos'] ?? 0);
    $personas = (int) ($estadisticas['personas'] ?? 0);
    $bibliotecarios = (int) ($estadisticas['bibliotecarios'] ?? 0);

    $totalEstados = $activos + $atrasados + $devueltos;
    $base = max(1, $totalEstados);
    $porcentajeActivos = round($activos * 100 / $base, 1);
    $porcentajeAtrasados = round($atrasados * 100 / $base, 1);
    $porcentajeDevueltos = round($devueltos * 100 / $base, 1);
    $finAtrasados = $porcentajeActivos + $porcentajeAtrasados;
@endphp

<style>
    /* Diseño autónomo del escritorio: no depende de la cuadrícula de Bootstrap. */
    .saide-dashboard { font-family: 'Segoe UI', Arial, sans-serif; color: #20304a; }
    .saide-dashboard * { box-sizing: border-box; }
    .saide-dashboard a { text-decoration: none; }
    .saide-dash-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin: 0 0 25px; }
    .saide-dash-head h1 { margin: 0 0 7px; font-size: clamp(26px, 3vw, 35px); font-weight: 800; letter-spacing: -.045em; }
    .saide-dash-head p { margin: 0; color: #74839b; font-size: 14px; }
    .saide-dash-date { display: inline-flex; align-items: center; padding: 10px 15px; border: 1px solid #e3eaf3; border-radius: 30px; background: #fff; color: #63758b; font-size: 13px; font-weight: 600; }

    .saide-primary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; margin-bottom: 18px; }
    .saide-kpi { position: relative; overflow: hidden; min-height: 185px; border-radius: 21px; padding: 23px 23px 0; color: #fff; box-shadow: 0 14px 30px rgba(25, 43, 78, .13); transition: transform .18s ease, box-shadow .18s ease; }
    .saide-kpi:hover { transform: translateY(-3px); box-shadow: 0 18px 35px rgba(25, 43, 78, .18); }
    .saide-kpi--cyan { background: linear-gradient(135deg, #0e8db6, #21c6d9); }
    .saide-kpi--green { background: linear-gradient(135deg, #158853, #29bb89); }
    .saide-kpi--orange { background: linear-gradient(135deg, #db7c17, #f5ae39); }
    .saide-kpi--blue { background: linear-gradient(135deg, #244fd2, #5f91fb); }
    .saide-kpi::before { content: ''; position: absolute; width: 190px; height: 190px; right: -80px; top: -80px; border-radius: 50%; border: 30px solid rgba(255,255,255,.09); }
    .saide-kpi-number { position: relative; font-size: 39px; line-height: 1.15; font-weight: 800; letter-spacing: -.05em; }
    .saide-kpi-label { position: relative; margin: 10px 0 30px; font-size: 14px; font-weight: 600; }
    .saide-kpi-icon { position: absolute; right: 21px; top: 27px; font-size: 40px; line-height: 1; opacity: .22; }
    .saide-kpi-link { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 0 -23px; padding: 12px 23px; background: rgba(0,0,0,.14); color: #fff !important; font-size: 13px; font-weight: 700; }
    .saide-kpi-link:hover { background: rgba(0,0,0,.22); }
    .saide-kpi-link--disabled { opacity: .75; cursor: default; }

    .saide-secondary-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 15px; margin-bottom: 23px; }
    .saide-mini { display: flex; align-items: center; gap: 13px; padding: 18px 16px; min-height: 107px; border: 1px solid #e3eaf3; background: #fff; border-radius: 19px; box-shadow: 0 8px 24px rgba(20,40,80,.045); }
    .saide-mini-icon { display: grid; flex: 0 0 44px; height: 44px; place-items: center; border-radius: 13px; font-weight: 800; font-size: 22px; }
    .saide-mini-icon--blue { background: #e6efff; color: #2563eb; }
    .saide-mini-icon--red { background: #fee9e9; color: #e44848; }
    .saide-mini-icon--green { background: #def8ed; color: #0b9c68; }
    .saide-mini-icon--orange { background: #fff0dc; color: #e18a22; }
    .saide-mini-icon--purple { background: #efe8ff; color: #7c56d6; }
    .saide-mini-label { display: block; color: #718199; font-size: 12px; line-height: 1.3; }
    .saide-mini-number { display: block; margin-top: 4px; font-size: 24px; font-weight: 800; line-height: 1.1; }

    .saide-chart-grid { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(260px, 1fr); gap: 18px; margin-bottom: 24px; }
    .saide-panel { padding: 24px; background: #fff; border: 1px solid #e4eaf2; border-radius: 22px; box-shadow: 0 10px 25px rgba(20,40,80,.045); min-width: 0; }
    .saide-panel-title { margin: 0; font-size: 19px; font-weight: 800; letter-spacing: -.03em; }
    .saide-panel-subtitle { margin: 7px 0 0; color: #76859a; font-size: 13px; }
    .saide-bars { margin-top: 24px; display: grid; gap: 20px; }
    .saide-bar-label { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 9px; color: #4b5d76; font-size: 13px; }
    .saide-bar-label strong { color: #263853; }
    .saide-track { height: 13px; border-radius: 50px; background: #eaf0f7; overflow: hidden; }
    .saide-fill { display: block; height: 100%; border-radius: inherit; }
    .saide-fill--blue { background: linear-gradient(90deg, #2563eb, #78a5ff); }
    .saide-fill--red { background: linear-gradient(90deg, #ee5362, #ff9a89); }
    .saide-fill--green { background: linear-gradient(90deg, #10a979, #5de0b1); }
    .saide-donut-wrap { display: grid; place-items: center; padding: 20px 0 8px; }
    .saide-donut { width: 190px; aspect-ratio: 1; border-radius: 50%; display: grid; place-items: center; background: conic-gradient(#2563eb 0 var(--p-activos), #ef5362 var(--p-activos) var(--p-atrasados), #10a979 var(--p-atrasados) 100%); }
    .saide-donut--empty { background: #e9edf4; }
    .saide-donut-center { display: grid; place-items: center; align-content: center; width: 140px; aspect-ratio: 1; border-radius: 50%; background: #fff; }
    .saide-donut-center strong { font-size: 33px; font-weight: 800; line-height: 1; }
    .saide-donut-center span { font-size: 12px; color: #8390a4; margin-top: 5px; }
    .saide-legend { display: flex; flex-wrap: wrap; justify-content: center; gap: 14px; font-size: 12px; color: #64748b; }
    .saide-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 5px; }
    .saide-dot--blue { background: #2563eb; } .saide-dot--red { background: #ef5362; } .saide-dot--green { background: #10a979; }
    .saide-notice { margin: 14px 0 0; color: #8692a4; font-size: 12px; line-height: 1.5; }

    .saide-section-head { display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 17px; }
    .saide-button { display: inline-flex; align-items: center; justify-content: center; border: 1px solid #cfe0ff; background: #eff5ff; color: #245bd9 !important; padding: 9px 14px; border-radius: 12px; font-size: 13px; font-weight: 700; }
    .saide-button:hover { background: #e3eeff; }
    .saide-table-wrap { overflow-x: auto; width: 100%; }
    .saide-table { width: 100%; border-collapse: collapse; text-align: left; }
    .saide-table th { padding: 14px 12px; background: #f6f8fc; color: #6d7d94; font-size: 11px; font-weight: 800; letter-spacing: .055em; text-transform: uppercase; white-space: nowrap; }
    .saide-table td { padding: 15px 12px; border-bottom: 1px solid #edf1f6; font-size: 13px; color: #33445f; }
    .saide-table tbody tr:hover { background: #fafcff; }
    .saide-table tbody tr:last-child td { border-bottom: none; }
    .saide-status { display: inline-block; padding: 7px 10px; border-radius: 30px; font-size: 11px; font-weight: 800; white-space: nowrap; }
    .saide-status--blue { background: #e7efff; color: #225dd5; }
    .saide-status--red { background: #fff0ee; color: #d94b43; }
    .saide-status--green { background: #e1f8ed; color: #13935c; }
    .saide-empty { text-align: center; color: #8190a3 !important; padding: 34px 12px !important; }

    @media (max-width: 1280px) { .saide-primary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .saide-secondary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 860px) { .saide-chart-grid { grid-template-columns: 1fr; } .saide-secondary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 540px) { .saide-primary-grid, .saide-secondary-grid { grid-template-columns: 1fr; } .saide-panel { padding: 17px; } .saide-dash-head { margin-bottom: 18px; } }
</style>

<div class="saide-dashboard">
    <div class="saide-dash-head">
        <div>
            <h1>Escritorio</h1>
            <p>Panorama general de la Biblioteca Escolar · SAIDE</p>
        </div>
        <span class="saide-dash-date">Actualizado: {{ now()->format('d/m/Y') }}</span>
    </div>

    <div class="saide-primary-grid">
        <article class="saide-kpi saide-kpi--cyan">
            <span class="saide-kpi-icon" aria-hidden="true">▤</span>
            <div class="saide-kpi-number">{{ number_format($libros) }}</div>
            <div class="saide-kpi-label">Libros registrados</div>
            @if (auth()->user()->puede('libros', 'ver'))
                <a class="saide-kpi-link" href="{{ route('libros.index') }}">Ver catálogo <span aria-hidden="true">→</span></a>
            @else
                <span class="saide-kpi-link saide-kpi-link--disabled">Sin acceso al módulo</span>
            @endif
        </article>

        <article class="saide-kpi saide-kpi--green">
            <span class="saide-kpi-icon" aria-hidden="true">▣</span>
            <div class="saide-kpi-number">{{ number_format($ejemplares) }}</div>
            <div class="saide-kpi-label">Ejemplares físicos</div>
            @if (auth()->user()->puede('ejemplares', 'ver'))
                <a class="saide-kpi-link" href="{{ route('ejemplares.index') }}">Ver inventario <span aria-hidden="true">→</span></a>
            @else
                <span class="saide-kpi-link saide-kpi-link--disabled">Sin acceso al módulo</span>
            @endif
        </article>

        <article class="saide-kpi saide-kpi--orange">
            <span class="saide-kpi-icon" aria-hidden="true">♟</span>
            <div class="saide-kpi-number">{{ number_format($usuarios) }}</div>
            <div class="saide-kpi-label">Usuarios registrados</div>
            @if (auth()->user()->puede('usuarios', 'ver'))
                <a class="saide-kpi-link" href="{{ route('usuarios.index') }}">Ver usuarios <span aria-hidden="true">→</span></a>
            @else
                <span class="saide-kpi-link saide-kpi-link--disabled">Sin acceso al módulo</span>
            @endif
        </article>

        <article class="saide-kpi saide-kpi--blue">
            <span class="saide-kpi-icon" aria-hidden="true">↔</span>
            <div class="saide-kpi-number">{{ number_format($prestamos) }}</div>
            <div class="saide-kpi-label">Préstamos registrados</div>
            @if (auth()->user()->puede('prestamos', 'ver'))
                <a class="saide-kpi-link" href="{{ route('prestamos.index') }}">Ver préstamos <span aria-hidden="true">→</span></a>
            @else
                <span class="saide-kpi-link saide-kpi-link--disabled">Sin acceso al módulo</span>
            @endif
        </article>
    </div>

    <div class="saide-secondary-grid">
        <article class="saide-mini">
            <span class="saide-mini-icon saide-mini-icon--blue" aria-hidden="true">↔</span>
            <div><span class="saide-mini-label">Préstamos activos</span><strong class="saide-mini-number">{{ $activos }}</strong></div>
        </article>
        <article class="saide-mini">
            <span class="saide-mini-icon saide-mini-icon--red" aria-hidden="true">!</span>
            <div><span class="saide-mini-label">Préstamos atrasados</span><strong class="saide-mini-number">{{ $atrasados }}</strong></div>
        </article>
        <article class="saide-mini">
            <span class="saide-mini-icon saide-mini-icon--green" aria-hidden="true">✓</span>
            <div><span class="saide-mini-label">Préstamos devueltos</span><strong class="saide-mini-number">{{ $devueltos }}</strong></div>
        </article>
        <article class="saide-mini">
            <span class="saide-mini-icon saide-mini-icon--orange" aria-hidden="true">♣</span>
            <div><span class="saide-mini-label">Personas</span><strong class="saide-mini-number">{{ $personas }}</strong></div>
        </article>
        <article class="saide-mini">
            <span class="saide-mini-icon saide-mini-icon--purple" aria-hidden="true">◆</span>
            <div><span class="saide-mini-label">Bibliotecarios</span><strong class="saide-mini-number">{{ $bibliotecarios }}</strong></div>
        </article>
    </div>

    <div class="saide-chart-grid">
        <section class="saide-panel" aria-labelledby="estado-prestamos-titulo">
            <h2 class="saide-panel-title" id="estado-prestamos-titulo">Estado de los préstamos</h2>
            <p class="saide-panel-subtitle">Distribución de activos, atrasados y devueltos.</p>
            <div class="saide-bars">
                <div>
                    <div class="saide-bar-label"><span>Activos</span><strong>{{ $activos }} · {{ $porcentajeActivos }}%</strong></div>
                    <div class="saide-track"><span class="saide-fill saide-fill--blue" style="width: {{ $porcentajeActivos }}%"></span></div>
                </div>
                <div>
                    <div class="saide-bar-label"><span>Atrasados</span><strong>{{ $atrasados }} · {{ $porcentajeAtrasados }}%</strong></div>
                    <div class="saide-track"><span class="saide-fill saide-fill--red" style="width: {{ $porcentajeAtrasados }}%"></span></div>
                </div>
                <div>
                    <div class="saide-bar-label"><span>Devueltos</span><strong>{{ $devueltos }} · {{ $porcentajeDevueltos }}%</strong></div>
                    <div class="saide-track"><span class="saide-fill saide-fill--green" style="width: {{ $porcentajeDevueltos }}%"></span></div>
                </div>
            </div>
            <p class="saide-notice">Los valores se obtienen de las estadísticas que envía DashboardController.</p>
        </section>

        <section class="saide-panel" aria-labelledby="resumen-visual-titulo">
            <h2 class="saide-panel-title" id="resumen-visual-titulo">Resumen visual</h2>
            <p class="saide-panel-subtitle">Proporción de cada estado dentro del total clasificado.</p>
            <div class="saide-donut-wrap">
                <div class="saide-donut {{ $totalEstados === 0 ? 'saide-donut--empty' : '' }}" style="--p-activos: {{ $porcentajeActivos }}%; --p-atrasados: {{ $finAtrasados }}%;" role="img" aria-label="{{ $activos }} activos, {{ $atrasados }} atrasados, {{ $devueltos }} devueltos">
                    <div class="saide-donut-center"><strong>{{ $totalEstados }}</strong><span>préstamos</span></div>
                </div>
            </div>
            <div class="saide-legend">
                <span><i class="saide-dot saide-dot--blue"></i>Activos</span>
                <span><i class="saide-dot saide-dot--red"></i>Atrasados</span>
                <span><i class="saide-dot saide-dot--green"></i>Devueltos</span>
            </div>
        </section>
    </div>

    <section class="saide-panel" aria-labelledby="prestamos-recientes-titulo">
        <div class="saide-section-head">
            <div>
                <h2 class="saide-panel-title" id="prestamos-recientes-titulo">Préstamos recientes</h2>
                <p class="saide-panel-subtitle">Últimas operaciones registradas en el sistema.</p>
            </div>
            @if (auth()->user()->puede('prestamos', 'ver'))
                <a class="saide-button" href="{{ route('prestamos.index') }}">Ver todos →</a>
            @endif
        </div>

        <div class="saide-table-wrap">
            <table class="saide-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Ejemplares</th>
                        <th>Fecha de préstamo</th>
                        <th>Fecha límite</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prestamosRecientes as $prestamo)
                        @php
                            $devuelto = (bool) ($prestamo->esta_devuelto ?? false);
                            $atrasado = (bool) ($prestamo->esta_atrasado ?? false);
                        @endphp
                        <tr>
                            <td><strong>#{{ $prestamo->id_prestamo }}</strong></td>
                            <td>{{ $prestamo->usuario?->persona?->nombre_completo ?? 'Sin usuario' }}</td>
                            <td>{{ $prestamo->detalles->count() }}</td>
                            <td>{{ $prestamo->fecha_prestamo?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $prestamo->fecha_limite?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                @if ($devuelto)
                                    <span class="saide-status saide-status--green">DEVUELTO</span>
                                @elseif ($atrasado)
                                    <span class="saide-status saide-status--red">ATRASADO</span>
                                @else
                                    <span class="saide-status saide-status--blue">ACTIVO</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="saide-empty" colspan="6">No hay préstamos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
