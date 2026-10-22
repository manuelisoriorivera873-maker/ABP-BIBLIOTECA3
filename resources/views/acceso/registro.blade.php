
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Crear cuenta | SAIDE Biblioteca</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 35px 16px;
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #e8efff, #f8fafc);
            color: #1e293b;
        }

        .registro-container {
            max-width: 830px;
            margin: auto;
            overflow: hidden;
            border-radius: 22px;
            background: white;
            box-shadow: 0 20px 55px rgba(15, 23, 42, .12);
        }

        .registro-header {
            padding: 32px 38px;
            color: white;
            background: linear-gradient(120deg, #10264b, #2563eb);
        }

        .registro-header h1 {
            margin: 0 0 10px;
            font-size: 30px;
        }

        .registro-header p {
            margin: 0;
            line-height: 1.6;
            color: #dbeafe;
        }

        .registro-body {
            padding: 35px 38px;
        }

        .section-title {
            margin: 0 0 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 18px;
            color: #1d4ed8;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .field {
            min-width: 0;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .field input {
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            font: inherit;
        }

        .field input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .btn {
            display: inline-block;
            border: none;
            border-radius: 10px;
            padding: 14px 22px;
            background: #2563eb;
            color: white;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        .volver {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }

        .error-box {
            margin-bottom: 24px;
            padding: 16px;
            border-radius: 10px;
            background: #fef2f2;
            color: #b91c1c;
        }

        .note {
            font-size: 13px;
            color: #64748b;
            line-height: 1.6;
        }

        @media(max-width: 650px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .registro-body,
            .registro-header {
                padding: 25px 22px;
            }
        }
    </style>
</head>

<body>

    <main class="registro-container">

        <header class="registro-header">
            <h1>Crear cuenta de lector</h1>

            <p>
                Regístrate en SAIDE Biblioteca para consultar
                nuestro catálogo de libros.
            </p>
        </header>

        <section class="registro-body">

            @if ($errors->any())
                <div class="error-box">
                    <strong>Revisa los siguientes datos:</strong>

                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('registro.guardar') }}">

                @csrf

                <h2 class="section-title">
                    Datos personales
                </h2>

                <div class="form-grid">

                    <div class="field">
                        <label for="nombre">Nombre(s)</label>
                        <input
                            id="nombre"
                            name="nombre"
                            value="{{ old('nombre') }}"
                            maxlength="80"
                            required>
                    </div>

                    <div class="field">
                        <label for="apellido_paterno">
                            Apellido paterno
                        </label>
                        <input
                            id="apellido_paterno"
                            name="apellido_paterno"
                            value="{{ old('apellido_paterno') }}"
                            maxlength="80"
                            required>
                    </div>

                    <div class="field">
                        <label for="apellido_materno">
                            Apellido materno
                        </label>
                        <input
                            id="apellido_materno"
                            name="apellido_materno"
                            value="{{ old('apellido_materno') }}"
                            maxlength="80"
                            required>
                    </div>

                    <div class="field">
                        <label for="telefono">Teléfono</label>
                        <input
                            type="tel"
                            id="telefono"
                            name="telefono"
                            pattern="[0-9 +\(\)\-]+"
                            value="{{ old('telefono') }}"
                            minlength="8"
                            maxlength="20"
                            required>
                    </div>

                </div>

                <h2 class="section-title">
                    Credenciales de acceso
                </h2>

                <div class="form-grid">

                    <div class="field">
                        <label for="nombre_usuario">
                            Nombre de usuario
                        </label>
                        <input
                            id="nombre_usuario"
                            name="nombre_usuario"
                            pattern="[A-Za-z0-9._\-]+"
                            value="{{ old('nombre_usuario') }}"
                            minlength="4"
                            maxlength="60"
                            autocomplete="username"
                            required>
                    </div>

                    <div class="field">
                        <label for="password">
                            Contraseña (mínimo 12 caracteres)
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            minlength="12"
                            autocomplete="new-password"
                            required>
                    </div>

                    <div class="field">
                        <label for="password_confirmation">
                            Confirmar contraseña
                        </label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            minlength="12"
                            autocomplete="new-password"
                            required>
                    </div>

                </div>

                <p class="note">
                    Tu cuenta se creará con el rol USUARIO lector.
                    Los permisos de bibliotecario y administrador
                    solamente pueden ser asignados por el SUPERADMIN.
                    Si ya tienes un registro en la biblioteca,
                    solicita vincularlo para evitar duplicados.
                </p>

                <div class="actions">

                    <a class="volver" href="{{ route('login') }}">
                        ← Volver al inicio de sesión
                    </a>

                    <button class="btn" type="submit">
                        Crear mi cuenta
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>
</html>
