<?php
// Explorar datos disponibles en la BD local
use App\Models\Carrera;
use App\Models\Materia;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\Horario;
use App\Models\Docente;
use App\Models\Admin;

echo "=== CARRERAS ===" . PHP_EOL;
Carrera::all()->each(function($c) {
    echo "  [{$c->id}] {$c->nombre}" . PHP_EOL;
});

echo PHP_EOL . "=== MATERIAS (primeras 10) ===" . PHP_EOL;
Materia::with('carrera')->take(10)->get()->each(function($m) {
    echo "  [{$m->id}] {$m->codigo} - {$m->nombre} (Carrera: {$m->carrera->nombre})" . PHP_EOL;
});

echo PHP_EOL . "=== DOCENTES (primeros 5) ===" . PHP_EOL;
Docente::take(5)->get()->each(function($d) {
    echo "  [{$d->id}] {$d->nombre} {$d->apellido}" . PHP_EOL;
});

echo PHP_EOL . "=== HORARIOS (primeros 5) ===" . PHP_EOL;
Horario::with(['materia','docente'])->take(5)->get()->each(function($h) {
    echo "  [{$h->id}] Materia: {$h->materia->nombre} | Docente: {$h->docente->apellido}" . PHP_EOL;
});

echo PHP_EOL . "=== ESTUDIANTES CON MÁS INSCRIPCIONES (top 5) ===" . PHP_EOL;
Inscripcion::selectRaw('estudiante_id, COUNT(*) as total')
    ->groupBy('estudiante_id')
    ->orderByDesc('total')
    ->limit(5)
    ->get()
    ->each(function($r) {
        $est = Estudiante::find($r->estudiante_id);
        echo "  [{$est->id}] {$est->primer_apellido} {$est->nombres} - {$r->total} materias" . PHP_EOL;
    });
