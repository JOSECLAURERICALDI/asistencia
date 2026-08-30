<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->string('estado', 50)->default('activo')->after('periodo_academico_id');
            $table->timestamp('fecha_abandono')->nullable()->after('estado');
            $table->string('motivo_abandono', 500)->nullable()->after('fecha_abandono');
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropColumn(['estado', 'fecha_abandono', 'motivo_abandono']);
        });
    }
};
