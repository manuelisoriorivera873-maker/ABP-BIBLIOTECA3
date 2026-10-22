<?php

namespace App\Http\Controllers;

use App\Models\CuentaAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AccesoController extends Controller
{
    // Mostrar el formulario de inicio de sesion
    public function formulario()
    {
        // Si ya inicio sesion, enviarlo segun su rol
        if (Auth::check()) {
            /** @var CuentaAcceso $cuenta */
            $cuenta = Auth::user();

            if ($cuenta->rol === 'USUARIO') {
                return redirect()->route('libros.index');
            }

            return redirect()->route('dashboard');
        }

        return view('acceso.login');
    }

    // Iniciar sesion
    public function iniciar(Request $request)
    {
        // Validar los datos ingresados
        $credenciales = $request->validate([
            'nombre_usuario' => [
                'required',
                'string',
            ],
            'password' => [
                'required',
                'string',
            ],
        ]);

        // Comprobar usuario, contraseña y estado activo
        if (! Auth::attempt([
            'nombre_usuario' => $credenciales['nombre_usuario'],
            'password' => $credenciales['password'],
            'activo' => 1,
        ])) {
            throw ValidationException::withMessages([
                'nombre_usuario' => 'Usuario, contraseña o estado de la cuenta incorrectos.',
            ]);
        }

        // Proteger la sesion
        $request->session()->regenerate();

        // Obtener la cuenta autenticada
        /** @var CuentaAcceso $cuenta */
        $cuenta = Auth::user();

        // Registrar el ultimo acceso
        $cuenta->forceFill([
            'ultimo_acceso' => now(),
        ])->save();

        // Redirigir segun el rol asignado
        switch ($cuenta->rol) {

            case 'SUPERADMIN':
                return redirect()->route('dashboard');

            case 'BIBLIOTECARIO':
                return redirect()->route('dashboard');

            case 'USUARIO':
                return redirect()->route('libros.index');

            default:
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'nombre_usuario' => 'La cuenta no tiene un rol valido.',
                ]);
        }
    }

    // Cerrar sesion
    public function salir(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
