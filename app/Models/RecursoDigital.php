<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecursoDigital extends Model
{
    protected $table = 'recursos_digitales';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['disponible' => 'boolean', 'verificado_en' => 'datetime'];

    public function scopeAutorizados($query)
    {
        return $query->where('disponible', true)->whereNotNull('verificado_en')->where('licencia', '<>', '')->where('licencia_url', 'like', 'https://%')
            ->whereIn('tipo', ['integrado', 'externo', 'previa']);
    }

    public function rutaVerificada(): ?string
    {
        if ($this->tipo !== 'integrado' || ! in_array($this->formato, ['epub', 'pdf'], true)) {
            return null;
        }
        $base = realpath(storage_path('app/private/lecturas'));
        $ruta = $base ? realpath($base.DIRECTORY_SEPARATOR.$this->ruta_privada) : false;

        return $ruta && str_starts_with($ruta, $base.DIRECTORY_SEPARATOR) && is_file($ruta) && $this->sha256 && hash_equals($this->sha256, hash_file('sha256', $ruta)) ? $ruta : null;
    }

    public function getAccesibleAttribute(): bool
    {
        if (! $this->disponible || ! $this->verificado_en || ! $this->licencia || ! filter_var($this->fuente, FILTER_VALIDATE_URL) || parse_url($this->fuente, PHP_URL_SCHEME) !== 'https') {
            return false;
        }
        if ($this->tipo === 'integrado') {
            return $this->rutaVerificada() !== null;
        }

        return in_array($this->tipo, ['externo', 'previa'], true) && filter_var($this->url, FILTER_VALIDATE_URL) && parse_url($this->url, PHP_URL_SCHEME) === 'https';
    }

    public function libro()
    {
        return $this->belongsTo(Libro::class, 'id_libro');
    }
}
