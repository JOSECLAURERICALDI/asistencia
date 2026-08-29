<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    protected $fillable = ['nombre', 'sigla', 'sede', 'descripcion'];

    public function materias(): HasMany
    {
        return $this->hasMany(Materia::class);
    }

    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class);
    }

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }
}
