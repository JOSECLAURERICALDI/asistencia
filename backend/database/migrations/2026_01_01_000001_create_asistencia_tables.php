<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Carreras
        Schema::create('carreras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('sigla', 20)->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        // 2. Periodos academicos
        Schema::create('periodos_academicos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: "2026-I"
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });

        // 3. Docentes
        Schema::create('docentes', function (Blueprint $table) {
            $table->id();
            $table->string('ci', 20)->unique();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // 4. Materias
        Schema::create('materias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20); // ej: SIS-111
            $table->string('nombre');
            $table->foreignId('carrera_id')->constrained('carreras')->onDelete('cascade');
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos')->onDelete('cascade');
            $table->integer('horas_teoricas')->default(0);
            $table->integer('horas_practicas')->default(0);
            $table->timestamps();
        });

        // 5. Estudiantes
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('carnet', 20)->unique(); // "Codigo" en el excel
            $table->string('primer_apellido');
            $table->string('segundo_apellido')->nullable();
            $table->string('nombres');
            $table->string('email')->nullable();
            $table->foreignId('carrera_id')->constrained('carreras')->onDelete('cascade');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 6. Horarios
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->onDelete('cascade');
            $table->foreignId('docente_id')->constrained('docentes')->onDelete('cascade');
            $table->enum('dia_semana', ['lunes','martes','miercoles','jueves','viernes','sabado']);
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('aula', 50)->nullable();
            $table->enum('tipo', ['teorica', 'practica'])->default('teorica');
            $table->timestamps();
        });

        // 7. Inscripciones (estudiante en materia)
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->onDelete('cascade');
            $table->foreignId('materia_id')->constrained('materias')->onDelete('cascade');
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos')->onDelete('cascade');
            $table->unique(['estudiante_id', 'materia_id', 'periodo_academico_id'], 'uq_inscripcion');
            $table->timestamps();
        });

        // 8. Asistencias
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->onDelete('cascade');
            $table->foreignId('horario_id')->constrained('horarios')->onDelete('cascade');
            $table->date('fecha');
            $table->enum('estado', ['presente', 'permiso', 'ausente'])->default('ausente');
            $table->boolean('es_retroactiva')->default(false);
            $table->text('justificacion_retroactiva')->nullable();
            $table->foreignId('registrado_por')->constrained('docentes')->onDelete('cascade');
            $table->timestamps();

            // Un estudiante solo puede tener un registro por horario+fecha
            $table->unique(['inscripcion_id', 'horario_id', 'fecha'], 'uq_asistencia');
        });

        // 9. Admins (Dirección de Carrera)
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('carrera_id')->nullable()->constrained('carreras')->onDelete('set null');
            $table->boolean('super_admin')->default(false); // puede ver todas las carreras
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
        Schema::dropIfExists('inscripciones');
        Schema::dropIfExists('horarios');
        Schema::dropIfExists('estudiantes');
        Schema::dropIfExists('materias');
        Schema::dropIfExists('docentes');
        Schema::dropIfExists('periodos_academicos');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('carreras');
    }
};
