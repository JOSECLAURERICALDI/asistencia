<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    protected $fillable = [
        'carnet', 'primer_apellido', 'segundo_apellido', 'nombres', 'email',
        'carrera_id', 'activo',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function materias()
    {
        return $this->belongsToMany(Materia::class, 'inscripciones');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'inscripcion_id', 'id')
                    ->join('inscripciones', 'inscripciones.id', '=', 'asistencias.inscripcion_id')
                    ->where('inscripciones.estudiante_id', $this->id);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->primer_apellido} {$this->segundo_apellido} {$this->nombres}";
    }
}
