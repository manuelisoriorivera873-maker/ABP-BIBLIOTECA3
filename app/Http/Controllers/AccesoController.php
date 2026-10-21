<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AccesoController extends Controller
{
    public function formulario()
    {
        if (Auth::check()) {
            return redirect()->route(...Auth::user()->destinoInicio());
        }
        return view('acceso.login');
    }

    public function iniciar(Request $request)
    {
        $credenciales = $request->validate([
            'nombre_usuario' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt([...$credenciales, 'activo' => 1])) {
            throw ValidationException::withMessages([
                'nombre_usuario' => 'Usuario, contraseña o estado de la cuenta incorrectos.',
            ]);
        }

        $request->session()->regenerate();
        $cuenta = Auth::user();
        $cuenta->forceFill(['ultimo_acceso' => now()])->save();
        return redirect()->intended(route(...$cuenta->destinoInicio()));
    }

    public function salir(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
