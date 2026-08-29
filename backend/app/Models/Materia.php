<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materia extends Model
{
    protected $fillable = [
        'codigo', 'nombre', 'carrera_id', 'periodo_academico_id',
        'horas_teoricas', 'horas_practicas',
    ];

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'inscripciones');
    }
}
