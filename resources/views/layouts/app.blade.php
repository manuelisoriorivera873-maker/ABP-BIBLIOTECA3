<!doctype html>
<html lang="es">
<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Biblioteca 3')</title>

    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}"><
    <link rel="stylesheet" href="{{ asset('assets/css/escritorio.css') }}?v=1">

    <link rel="stylesheet" href="{{ asset('assets/css/acceso.css') }}">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-mark">B</div>
                <div>
                    <strong>SAIDE Biblioteca</strong>
                    <small>Control bibliotecario</small>
                </div>
            </div>

            <div class="sidebar-user">
                <div class="avatar">{{ mb_substr(auth()->user()->nombre_usuario,0,2) }}</div>
                <div>
                    <strong>{{ auth()->user()->nombre_visible }}</strong>
                    <span>
                        <i></i>
                        {{ auth()->user()->rol }}
                    </span>
                </div>
            </div>

            <nav class="nav-menu">
                @if (auth()->user()->rol !== 'USUARIO')
                <a
                    class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}"
                >
                    <span class="nav-icon">⌂</span>
                    Escritorio
                </a>
                @endif

                @if (auth()->user()->puede('libros', 'ver'))
                <a
                    class="nav-link {{ request()->routeIs('libros.*') ? 'active' : '' }}"
                    href="{{ route('libros.index') }}"
                >
                    <span class="nav-icon">▤</span>
                    Libros
                </a>
                @endif

                @if (auth()->user()->puede('ejemplares', 'ver'))
                <a
                    class="nav-link {{ request()->routeIs('ejemplares.*') ? 'active' : '' }}"
                    href="{{ route('ejemplares.index') }}"
                >
                    <span class="nav-icon">▣</span>
                    Ejemplares
                </a>
                @endif

                @if (auth()->user()->puede('autores', 'ver'))
                <a
                    class="nav-link {{ request()->routeIs('autores.*') ? 'active' : '' }}"
                    href="{{ route('autores.index') }}"
                >
                    <span class="nav-icon">✎</span>
                    Autores
                </a>
                @endif

                @if (auth()->user()->puede('personas', 'ver'))
                <a
                    class="nav-link {{ request()->routeIs('personas.*') ? 'active' : '' }}"
                    href="{{ route('personas.index') }}"
                >
                    <span class="nav-icon">♣</span>
                    Personas
                </a>
                @endif

                @if (auth()->user()->puede('usuarios', 'ver'))
                <a
                    class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}"
                    href="{{ route('usuarios.index') }}"
                >
                    <span class="nav-icon">♟</span>
                    Usuarios
                </a>
                @endif

                @if (auth()->user()->puede('bibliotecarios', 'ver'))
                <a
                    class="nav-link {{ request()->routeIs('bibliotecarios.*') ? 'active' : '' }}"
                    href="{{ route('bibliotecarios.index') }}"
                >
                    <span class="nav-icon">◆</span>
                    Bibliotecarios
                </a>
                @endif

                @if (auth()->user()->puede('prestamos', 'ver'))
                <a
                    class="nav-link {{ request()->routeIs('prestamos.*') ? 'active' : '' }}"
                    href="{{ route('prestamos.index') }}"
                >
                    <span class="nav-icon">↔</span>
                    Préstamos
                </a>
                @endif

                @if (auth()->user()->puede('devoluciones', 'ver'))
                <a
                    class="nav-link {{ request()->routeIs('devoluciones.*') ? 'active' : '' }}"
                    href="{{ route('devoluciones.index') }}"
                >
                    <span class="nav-icon">✓</span>
                    Devoluciones
                </a>
                @endif

                @if (collect(['categorias','editoriales','ubicaciones','estados-ejemplar','estados-usuario'])->contains(fn ($m) => auth()->user()->puede($m, 'ver')))
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
                    @if (auth()->user()->puede('categorias', 'ver'))
                    <a href="{{ route('catalogos.index', 'categorias') }}">Categorías</a>
                    @endif
                    @if (auth()->user()->puede('editoriales', 'ver'))
                    <a href="{{ route('catalogos.index', 'editoriales') }}">Editoriales</a>
                    @endif
                    @if (auth()->user()->puede('ubicaciones', 'ver'))
                    <a href="{{ route('catalogos.index', 'ubicaciones') }}">Ubicaciones</a>
                    @endif
                    @if (auth()->user()->puede('estados-ejemplar', 'ver'))
                    <a href="{{ route('catalogos.index', 'estados-ejemplar') }}">Estados de ejemplar</a>
                    @endif
                    @if (auth()->user()->puede('estados-usuario', 'ver'))
                    <a href="{{ route('catalogos.index', 'estados-usuario') }}">Estados de usuario</a>
                    @endif
                </div>
                @endif
                @if (auth()->user()->rol === 'SUPERADMIN')
                <a class="nav-link {{ request()->routeIs('cuentas.*') ? 'active' : '' }}" href="{{ route('cuentas.index') }}">
                    <span class="nav-icon">🔐</span> Cuentas y permisos
                </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                Laravel + Bootstrap local
            </div>
        </aside>

        <main class="main-area">
            <header class="topbar">
                <button
                    class="sidebar-button"
                    type="button"
                    id="sidebarButton"
                    aria-label="Abrir menú"
                >
                    ☰
                </button>

                <div class="topbar-title">Sistema de Biblioteca</div>
                <div class="d-flex align-items-center gap-3"><span class="topbar-date">{{ now()->format('d/m/Y') }}</span><form action="{{ route('logout') }}" method="POST" class="m-0">@csrf<button type="submit" class="btn btn-sm btn-outline-secondary">Cerrar sesión</button></form></div>
            </header>

            <div class="content-wrap">
                @include('partials.alerts')
                @yield('content')
            </div>

            <footer class="app-footer">
                Biblioteca 3 · Gestión escolar · {{ auth()->user()->rol }}
            </footer>
        </main>
    </div>

    <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
