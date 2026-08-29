<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        'inscripcion_id', 'horario_id', 'fecha', 'estado',
        'es_retroactiva', 'justificacion_retroactiva', 'registrado_por',
    ];

    protected $casts = [
        'fecha'           => 'date',
        'es_retroactiva'  => 'boolean',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'registrado_por');
    }
}
