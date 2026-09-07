<?php
/**
 * Seed de datos de prueba para vista EstudiantesFaltas
 * Crea escenarios variados: faltas leves, graves, con notificación, con abandono
 */

use App\Models\Asistencia;
use App\Models\Inscripcion;
use App\Models\AccionFalta;
use App\Models\Admin;
use Carbon\Carbon;

// Limpiar faltas previas de prueba (las que fueron marcadas retroactiva=false y son recientes)
echo "Limpiando faltas de prueba anteriores..." . PHP_EOL;
Asistencia::where('es_retroactiva', false)
    ->where('created_at', '>=', Carbon::now()->subDays(2))
    ->delete();

$adminId = Admin::first()->id;

// Función auxiliar: obtener inscripciones de un estudiante con sus horarios
function getInscHorario($estId, $materiaIds = null) {
    $q = Inscripcion::where('estudiante_id', $estId)->with(['materia']);
    if ($materiaIds) $q->whereIn('materia_id', $materiaIds);
    return $q->get()->map(function($insc) {
        $horario = \App\Models\Horario::where('materia_id', $insc->materia_id)->first()
            ?? \App\Models\Horario::first();
        return ['inscripcion' => $insc, 'horario' => $horario];
    })->filter(fn($r) => $r['horario'] !== null);
}

function crearFalta($inscId, $horarioId, $daysAgo, $docId) {
    $fecha = Carbon::now()->subDays($daysAgo)->toDateString();
    if (!Asistencia::where('inscripcion_id', $inscId)->where('fecha', $fecha)->exists()) {
        Asistencia::create([
            'inscripcion_id' => $inscId,
            'horario_id'     => $horarioId,
            'fecha'          => $fecha,
            'estado'         => 'ausente',
            'es_retroactiva' => false,
            'registrado_por' => $docId,
        ]);
        return true;
    }
    return false;
}

$total = 0;

// ======================================================
// ESCENARIO 1: MENDOZA MANUEL (ID 27) - 8 materias
// CRÍTICO: 5+ faltas activas en varias materias - SIN notificación
// ======================================================
echo PHP_EOL . "📌 Escenario 1: MENDOZA MANUEL - Caso crítico (muchas faltas, sin notificación)..." . PHP_EOL;
$items = getInscHorario(27);
foreach ($items->take(4) as $r) {
    $insc = $r['inscripcion'];
    $hor  = $r['horario'];
    $doc  = $hor->docente_id ?? 1;
    foreach ([25, 20, 15, 10, 5] as $d) {
        if (crearFalta($insc->id, $hor->id, $d, $doc)) $total++;
    }
}
// Las otras 4 materias: 2-3 faltas
foreach ($items->slice(4) as $r) {
    $insc = $r['inscripcion'];
    $hor  = $r['horario'];
    $doc  = $hor->docente_id ?? 1;
    foreach ([18, 12, 6] as $d) {
        if (crearFalta($insc->id, $hor->id, $d, $doc)) $total++;
    }
}
echo "   ✓ Faltas creadas para MENDOZA" . PHP_EOL;

// ======================================================
// ESCENARIO 2: VEIZAGA CARLOS JAVIER (ID 26) - 8 materias
// NOTIFICADO: Tenía faltas, ya fue llamado, pero acumuló más
// ======================================================
echo "📌 Escenario 2: VEIZAGA CARLOS JAVIER - Ya notificado pero reincidió..." . PHP_EOL;
$items = getInscHorario(26);
// Primero crear AccionFalta antigua (hace 30 días)
foreach ($items->take(3) as $r) {
    $insc = $r['inscripcion'];
    if (!AccionFalta::where('inscripcion_id', $insc->id)->exists()) {
        AccionFalta::create([
            'inscripcion_id' => $insc->id,
            'admin_id'       => $adminId,
            'fecha_accion'   => Carbon::now()->subDays(30),
            'observacion'    => 'Se realizó llamada telefónica al estudiante. Indicó problemas económicos y se comprometió a regularizar asistencia.',
        ]);
    }
    // Nuevas faltas DESPUÉS de la notificación (activas)
    $hor = $r['horario'];
    $doc = $hor->docente_id ?? 1;
    foreach ([22, 16, 9] as $d) {
        if (crearFalta($insc->id, $hor->id, $d, $doc)) $total++;
    }
}
foreach ($items->slice(3, 3) as $r) {
    $insc = $r['inscripcion'];
    $hor  = $r['horario'];
    $doc  = $hor->docente_id ?? 1;
    foreach ([20, 13] as $d) {
        if (crearFalta($insc->id, $hor->id, $d, $doc)) $total++;
    }
}
echo "   ✓ Faltas + acción anterior creadas para VEIZAGA" . PHP_EOL;

// ======================================================
// ESCENARIO 3: LOPEZ FRANCO RONALDO (ID 24) - 8 materias
// MODERADO: 2-3 faltas por materia, sin notificación
// ======================================================
echo "📌 Escenario 3: LOPEZ FRANCO RONALDO - Caso moderado (2-3 faltas)..." . PHP_EOL;
$items = getInscHorario(24);
foreach ($items->take(5) as $r) {
    $insc = $r['inscripcion'];
    $hor  = $r['horario'];
    $doc  = $hor->docente_id ?? 1;
    foreach ([14, 8] as $d) {
        if (crearFalta($insc->id, $hor->id, $d, $doc)) $total++;
    }
}
echo "   ✓ Faltas creadas para LOPEZ" . PHP_EOL;

// ======================================================
// ESCENARIO 4: IRIARTE GEUNER MARCELO (ID 14) - 7 materias
// LIMITES: Exactamente 2 faltas en 3 materias (justo en el límite)
// ======================================================
echo "📌 Escenario 4: IRIARTE GEUNER MARCELO - En el límite (2 faltas/materia)..." . PHP_EOL;
$items = getInscHorario(14);
foreach ($items->take(3) as $r) {
    $insc = $r['inscripcion'];
    $hor  = $r['horario'];
    $doc  = $hor->docente_id ?? 1;
    foreach ([11, 4] as $d) {
        if (crearFalta($insc->id, $hor->id, $d, $doc)) $total++;
    }
}
echo "   ✓ Faltas creadas para IRIARTE" . PHP_EOL;

// ======================================================
// ESCENARIO 5: CARDOZO JHOEL EDDY (ID 15) - 7 materias
// CON CONTACTO + FALTAS GRAVES (para probar botón "Llamar Familiar")
// Agregar teléfono de contacto
// ======================================================
echo "📌 Escenario 5: CARDOZO JHOEL EDDY - Con contacto familiar y faltas graves..." . PHP_EOL;
\App\Models\Estudiante::where('id', 15)->update([
    'contacto_nombre'      => 'María Eddy Vda. de Cardozo',
    'contacto_parentesco'  => 'Madre',
    'contacto_telefono'    => '77812345',
]);
$items = getInscHorario(15);
foreach ($items->take(6) as $r) {
    $insc = $r['inscripcion'];
    $hor  = $r['horario'];
    $doc  = $hor->docente_id ?? 1;
    foreach ([28, 21, 14, 7] as $d) {
        if (crearFalta($insc->id, $hor->id, $d, $doc)) $total++;
    }
}
echo "   ✓ Faltas + contacto familiar creados para CARDOZO" . PHP_EOL;

// ======================================================
// RESUMEN FINAL
// ======================================================
echo PHP_EOL . "========================================" . PHP_EOL;
echo "TOTAL FALTAS CREADAS: {$total}" . PHP_EOL;
echo "TOTAL AUSENTES EN BD: " . Asistencia::where('estado','ausente')->count() . PHP_EOL;
echo "AccionesFaltas: " . AccionFalta::count() . PHP_EOL;
echo PHP_EOL . "Escenarios listos:" . PHP_EOL;
echo "  🔴 MENDOZA MANUEL      - CRÍTICO: ~35 faltas activas, sin notificación" . PHP_EOL;
echo "  🟠 VEIZAGA CARLOS      - REINCIDENTE: notificado hace 30d, acumuló 9+ más" . PHP_EOL;
echo "  🟡 LOPEZ FRANCO        - MODERADO: ~10 faltas activas, sin notificación" . PHP_EOL;
echo "  🟡 IRIARTE GEUNER      - LÍMITE: exactamente 6 faltas en 3 materias" . PHP_EOL;
echo "  🔴 CARDOZO JHOEL       - GRAVE + CONTACTO: 24 faltas, con teléfono familiar" . PHP_EOL;
