<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class Docente extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'ci', 'nombre', 'apellido', 'email', 'password', 'activo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class);
    }

    public function asistenciasRegistradas(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'registrado_por');
    }

    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'horarios');
    }

    /**
     * Retorna horarios del docente para un día de semana dado (ej: 'lunes').
     */
    public function horariosDelDia(string $dia): HasMany
    {
        return $this->horarios()->where('dia_semana', $dia)->with('materia.carrera');
    }
}
