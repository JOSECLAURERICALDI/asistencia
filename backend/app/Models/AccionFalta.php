<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccionFalta extends Model
{
    protected $table = 'acciones_faltas';

    protected $fillable = [
        'inscripcion_id',
        'fecha_accion',
        'observacion',
        'admin_id',
    ];

    protected $casts = [
        'fecha_accion' => 'datetime',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
