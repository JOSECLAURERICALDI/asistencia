<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inscripcion extends Model
{
    protected $table = 'inscripciones';

    protected $fillable = ['estudiante_id', 'materia_id', 'periodo_academico_id'];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    /**
     * Cuenta las faltas (estado=ausente) de esta inscripción
     */
    public function contarFaltas(): int
    {
        return $this->asistencias()->where('estado', 'ausente')->count();
    }
}
