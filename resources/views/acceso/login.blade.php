
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Iniciar sesión | SAIDE Biblioteca</title>

    <link rel="stylesheet"
          href="{{ asset('assets/css/acceso.css') }}?v=3">
</head>

<body class="biblioteca-login">

    <main class="login-container">

        <section class="login-info">

            <div class="brand">
                <div class="brand-icon">B</div>
                <span>SAIDE Biblioteca</span>
            </div>

            <div class="info-content">
                <span class="tag">SISTEMA DE GESTIÓN ESCOLAR</span>

                <h1>Biblioteca<br>Escolar</h1>

                <p>
                    Administración inteligente de libros,
                    ejemplares, préstamos y devoluciones
                    en una plataforma moderna y segura.
                </p>

                <div class="features">
                    <div>✓ Gestión bibliotecaria</div>
                    <div>✓ Control de acceso por roles</div>
                    <div>✓ Administración de permisos</div>
                </div>
            </div>

            <div class="info-footer">
                Sistema de Administración de Biblioteca
            </div>

        </section>

        <section class="login-form-panel">

            <div class="form-content">

                <div class="welcome-icon">B</div>

                <span class="welcome-label">BIENVENIDO</span>

                <h2>Iniciar sesión</h2>

                <p class="subtitle">
                    Ingresa tus credenciales para acceder
                    al sistema.
                </p>

                @if ($errors->any())
                    <div class="error-message">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('login.iniciar') }}">

                    @csrf

                    <div class="form-group">
                        <label for="nombre_usuario">
                            Nombre de usuario
                        </label>

                        <input
                            type="text"
                            id="nombre_usuario"
                            name="nombre_usuario"
                            placeholder="Ingresa tu usuario"
                            value="{{ old('nombre_usuario') }}"
                            autocomplete="username"
                            maxlength="60"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">
                            Contraseña
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingresa tu contraseña"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <button type="submit" class="login-button">
                        Iniciar sesión →
                    </button>

                </form>

                <div class="roles">
                    <span>SUPERADMIN</span>
                    <span>BIBLIOTECARIO</span>
                    <span>USUARIO</span>
                </div>

            </div>

            <div class="copyright">
                © 2026 SAIDE Biblioteca
            </div>

        </section>

    </main>

</body>
</html>
