<?php

namespace App\Http\Controllers;

use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Editorial;
use App\Models\LibroFicha;
use App\Models\Prestamo;
use App\Models\SolicitudPrestamo;
use App\Models\Usuario;
use App\Services\CatalogoLector;
use App\Services\SesionesOperativas;
use App\Services\SolicitarPrestamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LectorController extends Controller
{
    public function inicio(Request $request, CatalogoLector $catalogo)
    {
        $nuevos = $catalogo->consulta()->orderByDesc('libros.id_libro')->limit(4)->get();
        $idsVariados = DB::table('asigna_categorias')->selectRaw('MIN(id_libro) as id_libro')->groupBy('id_categoria')->pluck('id_libro')->unique()->all();
        $descubrir = $catalogo->consulta()->whereIn('libros.id_libro', $idsVariados)->whereNotIn('libros.id_libro', $nuevos->modelKeys())->orderBy('titulo')->limit(4)->get();
        $autoresDestacados = $request->user()->puede('autores', 'ver')
            ? Autor::with(['persona', 'ficha'])->withCount('libros')->whereHas('ficha')->has('libros')->orderByDesc('libros_count')->orderBy('id_autor')->limit(3)->get()
            : collect();
        $disponibles = $catalogo->consulta()->whereIn('id_libro', app(SolicitarPrestamo::class)->disponibles()->select('id_libro'))->orderBy('titulo')->limit(4)->get();
        $categorias = Categoria::sinAlias()->withCount('libros')->has('libros')->orderByDesc('libros_count')->orderBy('nombre')->limit(6)->get();
        $puedeSeguimiento = $request->user()->puede('solicitudes', 'ver');
        $solicitudes = $puedeSeguimiento ? SolicitudPrestamo::where('id_usuario', $request->user()->id_usuario ?? 0)->count() : 0;
        $prestamos = $puedeSeguimiento ? Prestamo::visiblesPara($request->user())->whereHas('detalles', fn ($q) => $q->whereDoesntHave('devolucionesDetalle'))->count() : 0;
        $puedeSolicitar = $request->user()->puede('solicitudes', 'crear');

        return view('lector.inicio', compact('nuevos', 'descubrir', 'autoresDestacados', 'disponibles', 'categorias', 'solicitudes', 'prestamos', 'puedeSolicitar', 'puedeSeguimiento'));
    }

    public function explorar(Request $request, CatalogoLector $catalogo)
    {
        $libros = $catalogo->filtrar($request)->paginate(12)->withQueryString();
        $categorias = Categoria::sinAlias()->orderBy('nombre')->get(['nombre']);
        $editoriales = Editorial::orderBy('nombre')->get(['nombre']);
        $puedeSolicitar = $request->user()->puede('solicitudes', 'crear');

        return view('lector.explorar', compact('libros', 'categorias', 'editoriales', 'puedeSolicitar'));
    }

    public function ficha(string $publico, CatalogoLector $catalogo)
    {
        $libro = $catalogo->consulta()->whereHas('ficha', fn ($q) => $q->where('publico', $publico))->firstOrFail();

        return view('lector.ficha', compact('libro'));
    }

    public function bibliografia(Request $request, string $tipo)
    {
        abort_unless(in_array($tipo, ['autores', 'categorias', 'editoriales'], true), 404);
        $buscar = trim((string) $request->query('buscar', ''));
        abort_if(mb_strlen($buscar) > 200, 422);
        $consulta = match ($tipo) {
            'autores' => Autor::with(['persona', 'ficha'])->withCount('libros')->where(fn ($q) => $q->whereHas('persona', fn ($p) => $p->where('nombre', 'like', '%'.$buscar.'%')->orWhere('apellido_paterno', 'like', '%'.$buscar.'%')->orWhere('apellido_materno', 'like', '%'.$buscar.'%'))->orWhereHas('ficha', fn ($f) => $f->where('nombre_publico', 'like', '%'.$buscar.'%')))->orderBy('id_autor'),
            'categorias' => Categoria::sinAlias()->with('ficha')->withCount('libros')->where('nombre', 'like', '%'.$buscar.'%')->orderBy('nombre'),
            'editoriales' => Editorial::with('ficha')->withCount('libros')->where('nombre', 'like', '%'.$buscar.'%')->orderBy('nombre'),
        };
        $registros = $consulta->paginate(18)->withQueryString();

        return view('lector.bibliografia', compact('registros', 'tipo', 'buscar'));
    }

    public function autor(string $publico, CatalogoLector $catalogo)
    {
        $autor = Autor::with(['persona', 'ficha'])->whereHas('ficha', fn ($q) => $q->where('publico', $publico))->firstOrFail();
        $libros = $catalogo->consulta()->whereHas('autores', fn ($q) => $q->where('autores.id_autor', $autor->getKey()))->orderBy('titulo')->paginate(12);
        $puedeSolicitar = auth()->user()->puede('solicitudes', 'crear');

        return view('lector.autor', compact('autor', 'libros', 'puedeSolicitar'));
    }

    public function bandeja(Request $request)
    {
        $seleccion = $request->session()->get('lector.bandeja', []);
        $libros = app(CatalogoLector::class)->consulta()->whereHas('ficha', fn ($q) => $q->whereIn('publico', array_keys($seleccion)))->orderBy('titulo')->get();
        $token = $request->session()->get('lector.envio') ?? (string) Str::uuid();
        $request->session()->put('lector.envio', $token);

        return view('lector.bandeja', compact('seleccion', 'libros', 'token'));
    }

    public function seleccionar(Request $request, string $publico)
    {
        $ficha = LibroFicha::where('publico', $publico)->firstOrFail();
        $datos = $request->validate(['cantidad' => ['required', 'integer', 'min:0', 'max:'.config('prestamos.maximo_por_titulo')]]);
        $seleccion = $request->session()->get('lector.bandeja', []);
        $cantidad = (int) $datos['cantidad'];
        if ($cantidad === 0) {
            unset($seleccion[$publico]);
        } else {
            $seleccion[$publico] = $cantidad;
        }
        if (array_sum($seleccion) > config('prestamos.maximo_ejemplares')) {
            throw ValidationException::withMessages(['cantidad' => 'La solicitud admite como máximo cinco ejemplares.']);
        }
        if ($cantidad > app(SolicitarPrestamo::class)->disponibles()->where('id_libro', $ficha->id_libro)->count()) {
            throw ValidationException::withMessages(['cantidad' => 'No hay suficientes ejemplares disponibles.']);
        }
        $request->session()->put('lector.bandeja', $seleccion);

        return back()->with('success', $cantidad ? 'Libro agregado a tu solicitud.' : 'Libro retirado de tu solicitud.');
    }

    public function enviar(Request $request, SolicitarPrestamo $reglas, SesionesOperativas $sesiones)
    {
        abort_unless($request->user()->id_usuario, 403);
        $datos = $request->validate(['confirmar' => ['accepted'], 'envio' => ['required', 'uuid']]);
        $token = $request->session()->get('lector.envio');
        if (! $token || ! hash_equals($token, $datos['envio'])) {
            return redirect()->route('lector.solicitudes')->with('info', 'Esta solicitud ya se envió o la confirmación caducó.');
        }
        DB::transaction(function () use ($request, $reglas, $sesiones, $datos): void {
            // Serializar al lector y comprobar nuevamente el identificador de envío.
            Usuario::whereKey($request->user()->id_usuario)->lockForUpdate()->firstOrFail();
            if (DB::table('solicitud_envios')->where('token', $datos['envio'])->exists()) {
                return;
            }
            $seleccion = $request->session()->get('lector.bandeja', []);
            $fichas = LibroFicha::whereIn('publico', array_keys($seleccion))->get();
            abort_unless($fichas->count() === count($seleccion), 422, 'La selección cambió; vuelve al catálogo.');
            $cantidades = $fichas->mapWithKeys(fn ($f) => [$f->id_libro => $seleccion[$f->publico]])->all();
            $reglas->validar($request->user()->id_usuario, $cantidades);
            $solicitud = SolicitudPrestamo::create(['id_usuario' => $request->user()->id_usuario]);
            DB::table('solicitud_envios')->insert(['token' => $datos['envio'], 'id_solicitud' => $solicitud->getKey()]);
            foreach ($cantidades as $id => $cantidad) {
                $solicitud->detalles()->create(['id_libro' => $id, 'cantidad' => $cantidad]);
            }
            $sesiones->asignarPendientes();
        }, 3);
        $request->session()->forget(['lector.bandeja', 'lector.envio']);

        return redirect()->route('lector.solicitudes')->with('success', 'Tu solicitud fue enviada a la biblioteca.');
    }

    public function solicitudes(Request $request)
    {
        $solicitudes = SolicitudPrestamo::with(['detalles.libro.ficha'])->where('id_usuario', $request->user()->id_usuario ?? 0)->orderByDesc('id_solicitud')->paginate(12);
        // Aviso por cambios desde la última consulta, sin mostrar claves internas.
        $version = $solicitudes->map(fn ($s) => $s->estado.'|'.$s->updated_at)->implode(';');
        $clave = 'lector.estados_pagina.'.$solicitudes->currentPage();
        $anterior = $request->session()->get($clave);
        $cambios = $anterior !== null && $anterior !== $version;
        $request->session()->put($clave, $version);

        return view('lector.solicitudes', compact('solicitudes', 'cambios'));
    }

    public function prestamos(Request $request)
    {
        $prestamos = Prestamo::with(['detalles.ejemplar.libro.ficha', 'detalles.devolucionesDetalle'])->visiblesPara($request->user())->orderByDesc('fecha_prestamo')->paginate(12);

        return view('lector.prestamos', compact('prestamos'));
    }
}
