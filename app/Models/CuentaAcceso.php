<?php

namespace App\Models;

use App\Support\PermisosBiblioteca;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function puede(string $modulo, string $accion = 'ver'): bool
    {
        if (!$this->activo || !PermisosBiblioteca::permiteRol($this->rol, $modulo, $accion)) {
            return false;
        }
        if ($this->rol === 'SUPERADMIN') {
            return true;
        }
        return $this->permisos->contains(fn ($permiso) => $permiso->modulo === $modulo && $permiso->accion === $accion);
    }

    public function destinoInicio(): array
    {
        if ($this->rol !== 'USUARIO') {
            return ['dashboard', []];
        }
        foreach (['libros', 'autores', 'categorias', 'editoriales'] as $modulo) {
            if ($this->puede($modulo, 'ver')) {
                return in_array($modulo, ['libros', 'autores'], true)
                    ? [$modulo . '.index', []]
                    : ['catalogos.index', [$modulo]];
            }
        }
        return ['sin-permisos', []];
    }

    public function getNombreVisibleAttribute(): string
    {
        if ($this->rol === 'BIBLIOTECARIO') {
            return $this->empleado?->persona?->nombre_completo ?? $this->nombre_usuario;
        }
        if ($this->rol === 'USUARIO') {
            return $this->lector?->persona?->nombre_completo ?? $this->nombre_usuario;
        }
        return $this->nombre_usuario;
    }
}
