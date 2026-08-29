<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Asistencia;
use App\Models\ClaseOmitida;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LlenarAsistenciasAgostoSeeder extends Seeder
{
    public function run(): void
    {
        $fechaInicio = Carbon::parse('2026-08-03');
        $fechaFin    = Carbon::parse('2026-08-22');

        $feriados = [
            '2026-08-06',
            '2026-08-07',
            '2026-08-14',
        ];

        $materiasPrimerSemestre = [
            'SIS-111',
            'SIS-112',
            'SIS-113',
            'SIS-114',
            'SIS-115',
            'SIS-116',
        ];

        $diasSemanaMap = [
            0 => 'domingo', 1 => 'lunes', 2 => 'martes',
            3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado',
        ];

        // 1. Limpiar asistencias y clases omitidas en el rango 2026-08-03 al 2026-08-22
        Asistencia::whereBetween('fecha', ['2026-08-03', '2026-08-22'])->delete();
        ClaseOmitida::whereBetween('fecha', ['2026-08-03', '2026-08-22'])->delete();

        $horarios = Horario::with(['materia', 'docente'])->get();

        $totalAsistenciasCreadas = 0;
        $totalClasesOmitidasCreadas = 0;

        DB::beginTransaction();
        try {
            $cursor = $fechaInicio->copy();
            while ($cursor->lessThanOrEqualTo($fechaFin)) {
                $fechaStr = $cursor->toDateString();
                $diaSemanaNombre = $diasSemanaMap[$cursor->dayOfWeek];

                // Horarios que le corresponden a este día de la semana
                $horariosDelDia = $horarios->where('dia_semana', $diaSemanaNombre);

                foreach ($horariosDelDia as $horario) {
                    if (!$horario->materia) continue;

                    $codigoMateria = strtoupper(trim($horario->materia->codigo));

                    // CONDICION 1: Feriados (6, 7 y 14 de Agosto)
                    if (in_array($fechaStr, $feriados)) {
                        ClaseOmitida::create([
                            'horario_id' => $horario->id,
                            'fecha'      => $fechaStr,
                            'motivo'     => 'feriado nacional-departamental',
                            'docente_id' => $horario->docente_id,
                        ]);
                        $totalClasesOmitidasCreadas++;
                        continue;
                    }

                    // CONDICION 2: Materias de 1er Semestre antes del 17 de Agosto (del 3 al 16)
                    $esPrimerSemestre = in_array($codigoMateria, $materiasPrimerSemestre);
                    if ($esPrimerSemestre && $cursor->lessThan(Carbon::parse('2026-08-17'))) {
                        ClaseOmitida::create([
                            'horario_id' => $horario->id,
                            'fecha'      => $fechaStr,
                            'motivo'     => 'SEMESTRE NO INICIO CLASES',
                            'docente_id' => $horario->docente_id,
                        ]);
                        $totalClasesOmitidasCreadas++;
                        continue;
                    }

                    // CONDICION 3: Clases normales con todos los estudiantes PRESENTES
                    $inscripciones = Inscripcion::where('materia_id', $horario->materia_id)->get();

                    foreach ($inscripciones as $insc) {
                        Asistencia::create([
                            'inscripcion_id'            => $insc->id,
                            'horario_id'                => $horario->id,
                            'fecha'                     => $fechaStr,
                            'estado'                    => 'presente',
                            'es_retroactiva'            => true,
                            'justificacion_retroactiva' => null,
                            'registrado_por'            => $horario->docente_id,
                        ]);
                        $totalAsistenciasCreadas++;
                    }
                }

                $cursor->addDay();
            }

            DB::commit();

            echo "✅ Llenado completado exitosamente.\n";
            echo " - Registros de asistencia (Presente): {$totalAsistenciasCreadas}\n";
            echo " - Clases omitidas / no marcadas por feriado/retraso 1er sem: {$totalClasesOmitidasCreadas}\n";
        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ Error al llenar la base de datos: " . $e->getMessage() . "\n";
        }
    }
}
