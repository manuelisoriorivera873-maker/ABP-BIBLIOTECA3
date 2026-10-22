
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>@yield('title', 'SAIDE Biblioteca')</title>

    <!-- Bootstrap -->
    <link rel="stylesheet"
          href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">

    <!-- Estilos generales -->
    <link rel="stylesheet"
          href="{{ asset('assets/css/app.css') }}?v=2">

    <!-- Estilos del escritorio -->
    <link rel="stylesheet"
          href="{{ asset('assets/css/escritorio.css') }}?v=2">

    <style>
        /* Identificacion de la cuenta conectada */

        .sesion-actual {
            margin-left: auto;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .sesion-identidad {
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding: 9px 16px;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 12px;
        }

        .sesion-etiqueta {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .sesion-rol {
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 800;
        }

        .sesion-nombre {
            color: #475569;
            font-size: 12px;
        }

        .sesion-fecha {
            color: #64748b;
            font-weight: 600;
            white-space: nowrap;
        }

        .sesion-salir {
            margin: 0;
        }

        .sesion-salir button {
            padding: 9px 14px;
            border-radius: 11px;
            white-space: nowrap;
        }

        @media (max-width: 850px) {
            .sesion-actual {
                gap: 8px;
            }

            .sesion-fecha {
                display: none;
            }

            .sesion-identidad {
                padding: 7px 10px;
            }

            .sesion-etiqueta {
                display: none;
            }
        }

        @media (max-width: 540px) {
            .sesion-nombre {
                display: none;
            }

            .sesion-rol {
                font-size: 11px;
            }

            .sesion-salir button {
                font-size: 11px;
                padding: 7px 10px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    @php
        $cuentaActual = auth()->user();

        $rolActual = $cuentaActual->rol;

        $nombreActual = $cuentaActual->nombre_visible;

        $rolVisible = match ($rolActual) {
            'SUPERADMIN' => 'Superadministrador',
            'BIBLIOTECARIO' => 'Bibliotecario',
            'USUARIO' => 'Usuario lector',
            default => 'Cuenta de acceso',
        };

        $catalogos = [
            'categorias',
            'editoriales',
            'ubicaciones',
            'estados-ejemplar',
            'estados-usuario',
        ];

        $tieneCatalogos = collect($catalogos)->contains(
            fn ($modulo) => $cuentaActual->puede($modulo, 'ver')
        );
    @endphp

    <div class="app-shell">

        <!-- MENU LATERAL -->

        <aside class="sidebar" id="sidebar">

            <div class="brand">
                <div class="brand-mark">
                    B
                </div>

                <div>
                    <strong>SAIDE Biblioteca</strong>
                    <small>Control bibliotecario</small>
                </div>
            </div>

            <!-- PERSONA CONECTADA -->

            <div class="sidebar-user">

                <div class="avatar">
                    {{ mb_substr($nombreActual, 0, 2) }}
                </div>

                <div>
                    <strong>{{ $nombreActual }}</strong>

                    <span>
                        <i></i>
                        {{ $rolVisible }}
                    </span>

                    <small style="display:block; opacity:.75; margin-top:4px;">
                        {{ '@' . $cuentaActual->nombre_usuario }}
                    </small>
                </div>

            </div>

            <!-- NAVEGACION -->

            <nav class="nav-menu">

                @if ($rolActual !== 'USUARIO')
                    <a
                        class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}"
                    >
                        <span class="nav-icon">⌂</span>
                        Escritorio
                    </a>
                @endif

                <!-- LIBROS -->

                @if ($cuentaActual->puede('libros', 'ver'))
                    <a
                        class="nav-link {{ request()->routeIs('libros.*') ? 'active' : '' }}"
                        href="{{ route('libros.index') }}"
                    >
                        <span class="nav-icon">▤</span>
                        {{ $rolActual === 'USUARIO' ? 'Catálogo de libros' : 'Libros' }}
                    </a>
                @endif

                <!-- EJEMPLARES -->

                @if ($cuentaActual->puede('ejemplares', 'ver'))
                    <a
                        class="nav-link {{ request()->routeIs('ejemplares.*') ? 'active' : '' }}"
                        href="{{ route('ejemplares.index') }}"
                    >
                        <span class="nav-icon">▣</span>
                        Ejemplares
                    </a>
                @endif

                <!-- AUTORES -->

                @if ($cuentaActual->puede('autores', 'ver'))
                    <a
                        class="nav-link {{ request()->routeIs('autores.*') ? 'active' : '' }}"
                        href="{{ route('autores.index') }}"
                    >
                        <span class="nav-icon">✎</span>
                        Autores
                    </a>
                @endif

                <!-- PERSONAS -->

                @if ($cuentaActual->puede('personas', 'ver'))
                    <a
                        class="nav-link {{ request()->routeIs('personas.*') ? 'active' : '' }}"
                        href="{{ route('personas.index') }}"
                    >
                        <span class="nav-icon">♣</span>
                        Personas
                    </a>
                @endif

                <!-- USUARIOS LECTORES -->

                @if ($cuentaActual->puede('usuarios', 'ver'))
                    <a
                        class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}"
                        href="{{ route('usuarios.index') }}"
                    >
                        <span class="nav-icon">♟</span>
                        Usuarios
                    </a>
                @endif

                <!-- BIBLIOTECARIOS -->

                @if ($cuentaActual->puede('bibliotecarios', 'ver'))
                    <a
                        class="nav-link {{ request()->routeIs('bibliotecarios.*') ? 'active' : '' }}"
                        href="{{ route('bibliotecarios.index') }}"
                    >
                        <span class="nav-icon">◆</span>
                        Bibliotecarios
                    </a>
                @endif

                <!-- PRESTAMOS -->

                @if ($rolActual === 'USUARIO' && $cuentaActual->puede('solicitudes', 'crear'))
                    <a class="nav-link {{ request()->routeIs('solicitudes.create') ? 'active' : '' }}" href="{{ route('solicitudes.create') }}"><span class="nav-icon">＋</span>Solicitar préstamo</a>
                @endif
                @if ($cuentaActual->puede('solicitudes', 'ver'))
                    <a class="nav-link {{ request()->routeIs('solicitudes.index') ? 'active' : '' }}" href="{{ route('solicitudes.index') }}"><span class="nav-icon">▤</span>{{ $rolActual === 'USUARIO' ? 'Mis solicitudes' : 'Solicitudes de préstamo' }}</a>
                    @if ($rolActual === 'USUARIO')
                        <a class="nav-link {{ request()->routeIs('solicitudes.prestamos') ? 'active' : '' }}" href="{{ route('solicitudes.prestamos') }}"><span class="nav-icon">↔</span>Mis préstamos</a>
                    @endif
                @endif

                @if ($cuentaActual->puede('prestamos', 'ver') && ! ($rolActual === 'USUARIO' && $cuentaActual->puede('solicitudes', 'ver')))
                    <a
                        class="nav-link {{ request()->routeIs('prestamos.*') ? 'active' : '' }}"
                        href="{{ route('prestamos.index') }}"
                    >
                        <span class="nav-icon">↔</span>
                        Préstamos
                    </a>
                @endif

                <!-- DEVOLUCIONES -->

                @if ($cuentaActual->puede('devoluciones', 'ver'))
                    <a
                        class="nav-link {{ request()->routeIs('devoluciones.*') ? 'active' : '' }}"
                        href="{{ route('devoluciones.index') }}"
                    >
                        <span class="nav-icon">✓</span>
                        Devoluciones
                    </a>
                @endif

                <!-- CATALOGOS -->

                @if ($tieneCatalogos)

                    <button
                        class="nav-link nav-toggle {{ request()->routeIs('catalogos.*') ? 'active' : '' }}"
                        type="button"
                        data-menu-toggle="catalogosMenu"
                    >
                        <span>
                            <span class="nav-icon">⚙</span>
                            Catálogos
                        </span>

                        <span>⌄</span>
                    </button>

                    <div
                        class="submenu {{ request()->routeIs('catalogos.*') ? 'show' : '' }}"
                        id="catalogosMenu"
                    >

                        @if ($cuentaActual->puede('categorias', 'ver'))
                            <a href="{{ route('catalogos.index', 'categorias') }}">
                                Categorías
                            </a>
                        @endif

                        @if ($cuentaActual->puede('editoriales', 'ver'))
                            <a href="{{ route('catalogos.index', 'editoriales') }}">
                                Editoriales
                            </a>
                        @endif

                        @if ($cuentaActual->puede('ubicaciones', 'ver'))
                            <a href="{{ route('catalogos.index', 'ubicaciones') }}">
                                Ubicaciones
                            </a>
                        @endif

                        @if ($cuentaActual->puede('estados-ejemplar', 'ver'))
                            <a href="{{ route('catalogos.index', 'estados-ejemplar') }}">
                                Estados de ejemplar
                            </a>
                        @endif

                        @if ($cuentaActual->puede('estados-usuario', 'ver'))
                            <a href="{{ route('catalogos.index', 'estados-usuario') }}">
                                Estados de usuario
                            </a>
                        @endif

                    </div>

                @endif

                <!-- ADMINISTRACION DE ACCESOS -->

                @if ($rolActual === 'SUPERADMIN')
                    <a
                        class="nav-link {{ request()->routeIs('cuentas.*') ? 'active' : '' }}"
                        href="{{ route('cuentas.index') }}"
                    >
                        <span class="nav-icon">🔐</span>
                        Cuentas y permisos
                    </a>
                @endif

            </nav>

            <div class="sidebar-footer">
                SAIDE Biblioteca · {{ $rolVisible }}
            </div>

        </aside>

        <!-- CONTENIDO PRINCIPAL -->

        <main class="main-area">

            <!-- BARRA SUPERIOR -->

            <header class="topbar">

                <button
                    class="sidebar-button"
                    type="button"
                    id="sidebarButton"
                    aria-label="Abrir menú"
                    aria-controls="sidebar"
                >
                    ☰
                </button>

                <div class="topbar-title">
                    Sistema de Biblioteca
                </div>

                <!-- IDENTIFICACION DEL TRABAJADOR -->

                <div class="sesion-actual">

                    <div class="sesion-identidad">

                        <span class="sesion-etiqueta">
                            SESIÓN ACTIVA
                        </span>

                        <strong class="sesion-rol">
                            {{ $rolVisible }}
                        </strong>

                        <span class="sesion-nombre">
                            {{ $nombreActual }}
                        </span>

                    </div>

                    <span class="sesion-fecha">
                        {{ now()->format('d/m/Y') }}
                    </span>

                    <form
                        class="sesion-salir"
                        action="{{ route('logout') }}"
                        method="POST"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="btn btn-sm btn-outline-secondary"
                        >
                            Cerrar sesión
                        </button>
                    </form>

                </div>

            </header>

            <!-- CONTENIDO DE CADA MODULO -->

            <div class="content-wrap">

                @include('partials.alerts')

                @yield('content')

            </div>

            <!-- PIE DE PAGINA -->

            <footer class="app-footer">
                SAIDE Biblioteca ·
                {{ $rolVisible }} ·
                {{ $nombreActual }}
            </footer>

        </main>

    </div>

    <!-- JAVASCRIPT -->

    @stack('modals')

    <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>

    <script src="{{ asset('assets/js/app.js') }}?v=3"></script>

    @stack('scripts')

</body>
</html>
