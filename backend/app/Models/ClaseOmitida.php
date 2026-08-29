<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaseOmitida extends Model
{
    protected $table = 'clases_omitidas';

    protected $fillable = [
        'horario_id',
        'fecha',
        'motivo',
        'docente_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }
}
