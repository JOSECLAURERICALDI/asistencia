<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inscripcion extends Model
{
    protected $table = 'inscripciones';

    protected $fillable = [
        'estudiante_id',
        'materia_id',
        'periodo_academico_id',
        'estado',
        'fecha_abandono',
        'motivo_abandono',
    ];

    protected $casts = [
        'fecha_abandono' => 'datetime',
    ];

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

    public function accionesFaltas(): HasMany
    {
        return $this->hasMany(AccionFalta::class);
    }

    /**
     * Retorna la última acción tomada (notificación) registrada por la dirección
     */
    public function ultimaAccionFalta(): ?AccionFalta
    {
        return $this->accionesFaltas()->orderBy('fecha_accion', 'desc')->first();
    }

    /**
     * Cuenta las faltas activas acumuladas DESPUÉS de la última acción tomada / notificación.
     */
    public function contarFaltasActivas(): int
    {
        $ultimaAccion = $this->ultimaAccionFalta();
        $query = $this->asistencias()->where('estado', 'ausente');

        if ($ultimaAccion) {
            $fechaCorte = $ultimaAccion->fecha_accion->toDateString();
            $query->where('fecha', '>', $fechaCorte);
        }

        return $query->count();
    }
}
