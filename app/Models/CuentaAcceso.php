<?php

namespace App\Models;

use App\Support\PermisosBiblioteca;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class CuentaAcceso extends Authenticatable
{
    protected $table = 'cuentas_acceso';

    protected $primaryKey = 'id_cuenta';

    protected $fillable = [
        'nombre_usuario', 'password', 'rol', 'activo', 'id_usuario', 'id_bibliotecario', 'ultimo_acceso',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'activo' => 'boolean', 'ultimo_acceso' => 'datetime'];
    }

    public function lector(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Bibliotecario::class, 'id_bibliotecario', 'id_bibliotecario');
    }

    public function permisos(): HasMany
    {
        return $this->hasMany(CuentaPermiso::class, 'id_cuenta', 'id_cuenta');
    }

    public function scopeDePersona(Builder $query, int $idPersona): Builder
    {
        return $query->where(function (Builder $consulta) use ($idPersona): void {
            $consulta->whereHas('lector', fn (Builder $lector) => $lector->where('id_persona', $idPersona))
                ->orWhereHas('empleado', fn (Builder $empleado) => $empleado->where('id_persona', $idPersona));
        });
    }

    public function puede(string $modulo, string $accion = 'ver'): bool
    {
        if (! $this->activo || ! PermisosBiblioteca::permiteRol($this->rol, $modulo, $accion)) {
            return false;
        }
        if ($this->rol === 'SUPERADMIN') {
            return true;
        }

        // El espacio del lector es bibliográfico y de registros propios.
        // Una casilla histórica no lo convierte en operador administrativo.
        if ($this->rol === 'USUARIO') {
            $accionesLector = match ($modulo) {
                'libros', 'autores', 'categorias', 'editoriales', 'prestamos', 'devoluciones' => ['ver'],
                'solicitudes' => ['ver', 'crear'],
                default => [],
            };
            if (! in_array($accion, $accionesLector, true)) {
                return false;
            }
        }

        return PermisosBiblioteca::concede($this->rol, $modulo, $accion);
    }

    public function destinoInicio(): array
    {
        return [$this->rol === 'USUARIO' ? 'lector.inicio' : 'dashboard', []];
    }

    public function getNombreVisibleAttribute(): string
    {
        if ($this->rol === 'BIBLIOTECARIO') {
            return $this->empleado?->persona?->nombre_completo ?? $this->nombre_usuario;
        }
        if ($this->rol === 'USUARIO') {
            return $this->lector?->persona?->nombre_completo ?? $this->nombre_usuario;
        }

        return $this->empleado?->persona?->nombre_completo
            ?? $this->lector?->persona?->nombre_completo
            ?? $this->nombre_usuario;
    }
}
