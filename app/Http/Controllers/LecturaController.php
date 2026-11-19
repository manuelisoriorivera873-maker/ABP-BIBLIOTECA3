<?php

namespace App\Http\Controllers;

use App\Models\LibroFicha;
use App\Models\RecursoDigital;
use App\Services\CatalogoLector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LecturaController extends Controller
{
    private function lector(Request $request): int
    {
        abort_unless($request->user()?->rol === 'USUARIO' && $request->user()->id_usuario && $request->user()->puede('libros', 'ver'), 403);

        return (int) $request->user()->id_usuario;
    }

    public function favoritos(Request $request, CatalogoLector $catalogo)
    {
        $id = $this->lector($request);
        $libros = $catalogo->consulta()->whereIn('libros.id_libro', DB::table('usuario_favoritos')->where('id_usuario', $id)->select('id_libro'))->orderBy('titulo')->paginate(12);
        $puedeSolicitar = $request->user()->puede('solicitudes', 'crear');

        return response()->view('lector.favoritos', compact('libros', 'puedeSolicitar'))->header('Cache-Control', 'private, no-store');
    }

    public function favorito(Request $request, string $publico)
    {
        $id = $this->lector($request);
        $request->validate(['id_usuario' => ['prohibited'], 'id_libro' => ['prohibited']]);
        $ficha = LibroFicha::where('publico', $publico)->firstOrFail();
        $clave = ['id_usuario' => $id, 'id_libro' => $ficha->id_libro];
        if ($request->isMethod('put')) {
            DB::table('usuario_favoritos')->insertOrIgnore($clave + ['creado_en' => now()]);
        } else {
            DB::table('usuario_favoritos')->where($clave)->delete();
        }

        return response()->json(['favorito' => $request->isMethod('put')])->header('Cache-Control', 'no-store');
    }

    private function recurso(Request $request, string $publico): RecursoDigital
    {
        $this->lector($request);

        $recurso = RecursoDigital::autorizados()->with('libro.ficha')->where('publico', $publico)->firstOrFail();
        abort_unless($recurso->accesible, 404);

        return $recurso;
    }

    public function leer(Request $request, string $publico)
    {
        $recurso = $this->recurso($request, $publico);
        if ($recurso->tipo !== 'integrado') {
            abort_unless(filter_var($recurso->url, FILTER_VALIDATE_URL) && parse_url($recurso->url, PHP_URL_SCHEME) === 'https', 404);

            return redirect()->away($recurso->url);
        }
        $this->archivoVerificado($recurso);
        $progreso = DB::table('lectura_progresos')->where('id_usuario', $request->user()->id_usuario)->where('id_recurso', $recurso->getKey())->first();

        return response()->view('lector.leer', compact('recurso', 'progreso'))->header('Cache-Control', 'private, no-store');
    }

    private function archivoVerificado(RecursoDigital $recurso): string
    {
        $ruta = $recurso->rutaVerificada();
        abort_unless($ruta, 404);

        return $ruta;
    }

    public function archivo(Request $request, string $publico)
    {
        $recurso = $this->recurso($request, $publico);
        $ruta = $this->archivoVerificado($recurso);

        return response()->file($ruta, ['Content-Type' => $recurso->formato === 'pdf' ? 'application/pdf' : 'application/epub+zip', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function progreso(Request $request, string $publico)
    {
        $recurso = $this->recurso($request, $publico);
        $this->archivoVerificado($recurso);
        $datos = $request->validate(['posicion' => ['required', 'string', 'max:500'], 'tamano' => ['required', 'integer', 'min:75', 'max:200'], 'id_usuario' => ['prohibited']]);
        abort_unless($recurso->formato === 'pdf' ? preg_match('/^[1-9][0-9]{0,5}$/D', $datos['posicion']) : preg_match('/^epubcfi\([0-9\/,\[\]\!:;=a-zA-Z_.~@%+^-]+\)$/D', $datos['posicion']), 422);
        DB::table('lectura_progresos')->updateOrInsert(['id_usuario' => $request->user()->id_usuario, 'id_recurso' => $recurso->getKey()], $datos + ['actualizado_en' => now()]);

        return response()->json(['guardado' => true])->header('Cache-Control', 'no-store');
    }
}
