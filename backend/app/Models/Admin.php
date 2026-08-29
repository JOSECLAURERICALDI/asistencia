<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'nombre', 'email', 'password', 'carrera_id', 'super_admin',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'super_admin' => 'boolean',
    ];

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }
}
