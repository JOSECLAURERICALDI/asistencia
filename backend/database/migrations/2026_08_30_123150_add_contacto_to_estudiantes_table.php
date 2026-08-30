<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('contacto_nombre', 255)->nullable()->after('email');
            $table->string('contacto_parentesco', 100)->nullable()->after('contacto_nombre');
            $table->string('contacto_telefono', 50)->nullable()->after('contacto_parentesco');
        });
    }

    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn(['contacto_nombre', 'contacto_parentesco', 'contacto_telefono']);
        });
    }
};
