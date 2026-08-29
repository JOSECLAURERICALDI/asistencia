<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    protected $fillable = [
        'materia_id', 'docente_id', 'dia_semana',
        'hora_inicio', 'hora_fin', 'aula', 'tipo',
    ];

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    /**
     * Devuelve el nombre del día en español capitalizado
     */
    public function getDiaFormateadoAttribute(): string
    {
        $dias = [
            'lunes'     => 'Lunes',
            'martes'    => 'Martes',
            'miercoles' => 'Miércoles',
            'jueves'    => 'Jueves',
            'viernes'   => 'Viernes',
            'sabado'    => 'Sábado',
        ];
        return $dias[$this->dia_semana] ?? $this->dia_semana;
    }
}
