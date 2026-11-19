<?php

namespace App\Services;

use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Libro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogoLector
{
    public function consulta(): Builder
    {
        return Libro::with(['ficha', 'autores.persona', 'autores.ficha', 'categorias', 'editoriales', 'recursosDigitales'])
            ->whereHas('ficha')
            ->addSelect(['es_favorito' => DB::table('usuario_favoritos')->selectRaw('1')->whereColumn('usuario_favoritos.id_libro', 'libros.id_libro')->where('id_usuario', auth()->user()?->id_usuario ?? 0)->limit(1)])
            ->withCount(['ejemplares as disponibles' => fn ($q) => $q->whereIn('id_ejemplar', app(SolicitarPrestamo::class)->disponibles()->select('id_ejemplar'))]);
    }

    public function filtrar(Request $request): Builder
    {
        $datos = $request->validate([
            'buscar' => ['nullable', 'string', 'max:200'], 'categoria' => ['nullable', 'string', 'max:100'],
            'editorial' => ['nullable', 'string', 'max:100'], 'disponibilidad' => ['nullable', 'in:disponible,agotado'],
            'idioma' => ['nullable', 'in:spa,eng,pendiente'],
            'orden' => ['nullable', 'in:titulo,autor,disponibilidad'],
        ]);
        $q = $this->consulta();
        if (! empty($datos['idioma'])) {
            $q->whereHas('ficha', fn ($f) => $datos['idioma'] === 'pendiente' ? $f->whereNull('idioma') : $f->where('idioma', $datos['idioma']));
        }
        $buscar = trim($datos['buscar'] ?? '');
        foreach (array_slice(preg_split('/\s+/', $buscar, -1, PREG_SPLIT_NO_EMPTY), 0, 12) as $buscar) {
            $q->where(fn ($q) => $q->where('titulo', 'like', '%'.$buscar.'%')
                ->orWhereHas('ficha', fn ($f) => $f->where('titulo_verificado', 'like', '%'.$buscar.'%')->orWhere('isbn_verificado', 'like', '%'.$buscar.'%'))
                ->orWhereHas('autores.persona', fn ($p) => $p->where('nombre', 'like', '%'.$buscar.'%')->orWhere('apellido_paterno', 'like', '%'.$buscar.'%')->orWhere('apellido_materno', 'like', '%'.$buscar.'%'))
                ->orWhereHas('autores.ficha', fn ($a) => $a->where('nombre_publico', 'like', '%'.$buscar.'%'))
                ->orWhereHas('categorias', fn ($c) => $c->where('nombre', 'like', '%'.$buscar.'%'))
                ->orWhereHas('editoriales', fn ($e) => $e->where('nombre', 'like', '%'.$buscar.'%')));
        }
        foreach (['categoria' => 'categorias', 'editorial' => 'editoriales'] as $filtro => $relacion) {
            if (! empty($datos[$filtro])) {
                if ($filtro === 'categoria') {
                    $nombre = Categoria::EQUIVALENCIAS[$datos[$filtro]] ?? $datos[$filtro];
                    $variantes = [$nombre, ...array_keys(array_filter(Categoria::EQUIVALENCIAS, fn ($valor) => $valor === $nombre))];
                    $q->whereHas($relacion, fn ($r) => $r->whereIn('nombre', $variantes));
                } else {
                    $q->whereHas($relacion, fn ($r) => $r->where('nombre', $datos[$filtro]));
                }
            }
        }
        if (! empty($datos['disponibilidad'])) {
            $disponibles = app(SolicitarPrestamo::class)->disponibles()->select('id_libro');
            $datos['disponibilidad'] === 'disponible' ? $q->whereIn('id_libro', $disponibles) : $q->whereNotIn('id_libro', $disponibles);
        }
        if (($datos['orden'] ?? '') === 'autor') {
            $q->orderBy(Autor::select('personas.apellido_paterno')->join('personas', 'personas.id_persona', '=', 'autores.id_persona')
                ->join('asigna_autores', 'asigna_autores.id_autor', '=', 'autores.id_autor')->whereColumn('asigna_autores.id_libro', 'libros.id_libro')->orderBy('personas.apellido_paterno')->limit(1));
        } elseif (($datos['orden'] ?? '') === 'disponibilidad') {
            $q->orderByDesc('disponibles');
        }

        return $q->orderByRaw("CASE WHEN (SELECT idioma FROM libro_fichas WHERE libro_fichas.id_libro = libros.id_libro) = 'spa' THEN 0 ELSE 1 END")->orderBy('titulo')->orderBy('id_libro');
    }
}
