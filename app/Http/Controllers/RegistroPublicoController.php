<?php

namespace App\Http\Controllers;

use App\Models\CuentaAcceso;
use App\Models\EstadoUsuario;
use App\Models\Persona;
use App\Models\Usuario;
use App\Rules\LimiteBcrypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistroPublicoController extends Controller
{
    // Mostrar formulario de registro
    public function formulario()
    {
        if (Auth::check()) {
            $cuenta = Auth::user();

            if ($cuenta->rol === 'USUARIO') {
                return redirect()->route('libros.index');
            }

            return redirect()->route('dashboard');
        }

        return view('acceso.registro');
    }

    // Registrar un nuevo usuario lector
    public function registrar(Request $request)
    {
        // Validar los datos del formulario
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:80',
            ],
            'apellido_paterno' => [
                'required',
                'string',
                'max:80',
            ],
            'apellido_materno' => [
                'required',
                'string',
                'max:80',
            ],
            'telefono' => [
                'required',
                'string',
                'min:8',
                'max:20',
                'regex:/^[0-9 +()\-]+$/',
                'unique:usuarios,telefono',
            ],
            'nombre_usuario' => [
                'required',
                'string',
                'min:4',
                'max:60',
                'regex:/^[A-Za-z0-9._-]+$/',
                'unique:cuentas_acceso,nombre_usuario',
            ],
            'password' => [
                'required',
                'string',
                'min:12',
                new LimiteBcrypt,
                'confirmed',
            ],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute admite como máximo :max caracteres.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'unique' => 'El campo :attribute ya está registrado. Solicita al personal revisar tu cuenta.',
            'telefono.regex' => 'El teléfono solo admite números, espacios, +, paréntesis y guiones.',
            'nombre_usuario.regex' => 'El usuario solo admite letras, números, puntos, guiones y guiones bajos.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ], [
            'nombre' => 'nombre',
            'apellido_paterno' => 'apellido paterno',
            'apellido_materno' => 'apellido materno',
            'telefono' => 'teléfono',
            'nombre_usuario' => 'nombre de usuario',
            'password' => 'contraseña',
        ]);

        // Buscar el estado ACTIVO
        $estadoActivo = EstadoUsuario::whereRaw(
            'UPPER(TRIM(nombre_estado_usuario)) = ?',
            ['ACTIVO']
        )->value('id_estado_usuario');

        if ($estadoActivo === null) {
            throw ValidationException::withMessages([
                'nombre_usuario' => 'No existe el estado ACTIVO para los lectores.',
            ]);
        }

        // Registrar todos los datos en una transaccion
        $cuenta = DB::transaction(function () use (
            $datos,
            $estadoActivo
        ) {
            $coincide = Persona::query();
            foreach (['nombre', 'apellido_paterno', 'apellido_materno'] as $campo) {
                $coincide->whereRaw('LOWER(TRIM('.$campo.')) = ?', [mb_strtolower(trim($datos[$campo]))]);
            }
            if ($coincide->exists()) {
                throw ValidationException::withMessages([
                    'nombre' => 'No fue posible completar el registro. Solicita al personal verificar y vincular tus datos.',
                ]);
            }
            // 1. Crear la persona
            $persona = Persona::create([
                'nombre' => trim($datos['nombre']),
                'apellido_paterno' => trim($datos['apellido_paterno']),
                'apellido_materno' => trim($datos['apellido_materno']),
            ]);

            // 2. Crear el usuario lector
            $usuario = Usuario::create([
                'id_persona' => $persona->id_persona,
                'telefono' => trim($datos['telefono']),
                'id_estado_usuario' => $estadoActivo,
            ]);

            // 3. Crear su cuenta de acceso
            $cuenta = CuentaAcceso::create([
                'nombre_usuario' => $datos['nombre_usuario'],
                'password' => $datos['password'],
                'rol' => 'USUARIO',
                'activo' => true,
                'id_usuario' => $usuario->id_usuario,
                'id_bibliotecario' => null,
                'ultimo_acceso' => now(),
            ]);

            // CuentaAcceso tiene un cast 'hashed'
            // que protege la contraseña automaticamente.

            // Los permisos se heredan dinámicamente del rol USUARIO.

            // 5. Guardar el historial del registro
            DB::table('historial_permisos')->insert([
                'actor_id' => null,
                'cuenta_id' => $cuenta->id_cuenta,
                'evento' => 'AUTO_REGISTRO',
                'detalle' => json_encode([
                    'rol' => 'USUARIO',
                    'id_persona' => $persona->id_persona,
                    'id_usuario' => $usuario->id_usuario,
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);

            return $cuenta;
        });

        // 6. Iniciar sesion automaticamente
        Auth::login($cuenta);

        // Regenerar la sesion por seguridad
        $request->session()->regenerate();

        // 7. Enviar al nuevo lector al catalogo
        return redirect()
            ->route('libros.index')
            ->with(
                'success',
                'Tu cuenta de lector fue creada correctamente.'
            );
    }
}
