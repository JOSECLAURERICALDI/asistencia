<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoAcademico extends Model
{
    protected $table = 'periodos_academicos';

    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_fin', 'activo'];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activo'       => 'boolean',
    ];

    public function materias(): HasMany
    {
        return $this->hasMany(Materia::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public static function activo(): ?self
    {
        return static::where('activo', true)->first();
    }
}
