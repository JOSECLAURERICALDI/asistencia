<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Materia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    /**
     * Docentes que NO marcaron asistencia (en fecha única o en rango de fechas pasadas).
     */
    public function docentesSinMarcar(Request $request)
    {
        $fechaInicioStr = $request->query('fecha_inicio');
        $fechaFinStr    = $request->query('fecha_fin');
        $fechaUnicaStr  = $request->query('fecha');

        if (!empty($fechaInicioStr) && !empty($fechaFinStr)) {
            $inicio = Carbon::parse($fechaInicioStr);
            $fin    = Carbon::parse($fechaFinStr);
        } elseif (!empty($fechaUnicaStr)) {
            $inicio = Carbon::parse($fechaUnicaStr);
            $fin    = Carbon::parse($fechaUnicaStr);
        } else {
            $inicio = Carbon::today();
            $fin    = Carbon::today();
        }

        $diasSemanaMap = [
            0 => 'domingo', 1 => 'lunes', 2 => 'martes',
            3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado',
        ];

        $admin = $request->user();
        $isSuperAdmin = $admin ? (bool)$admin->super_admin : true;
        $carreraId = $request->query('carrera_id') ?: ($isSuperAdmin ? null : $admin?->carrera_id);

        $horarios = Horario::with(['docente', 'materia.carrera'])
            ->when($carreraId, function ($q) use ($carreraId) {
                $q->whereHas('materia', fn($m) => $m->where('carrera_id', $carreraId));
            })
            ->get();

        $sinMarcarList   = [];
        $parcialList     = [];
        $omitidasList    = [];
        $marcadasList    = [];
        $todasLasClases  = [];
        $totalClasesEnRango = 0;

        $cursor = $inicio->copy();
        while ($cursor->lessThanOrEqualTo($fin)) {
            $fechaActual = $cursor->toDateString();
            $diaSemanaActual = $diasSemanaMap[$cursor->dayOfWeek];

            $horariosDelDia = $horarios->where('dia_semana', $diaSemanaActual);

            foreach ($horariosDelDia as $horario) {
                if (!$horario->docente || !$horario->materia) continue;

                $totalEstudiantes = Inscripcion::where('materia_id', $horario->materia_id)->count();
                
                $asistencias = Asistencia::where('horario_id', $horario->id)
                    ->whereDate('fecha', $fechaActual)
                    ->get();

                $marcados = $asistencias->count();
                $firstAsistencia = $asistencias->first();

                $claseOmitida = \App\Models\ClaseOmitida::where('horario_id', $horario->id)
                    ->whereDate('fecha', $fechaActual)
                    ->first();

                $totalClasesEnRango++;

                $fechaRegistroStr = null;
                $modoRegistro = 'sin_marcar';
                $observacion  = null;

                if ($claseOmitida) {
                    $modoRegistro = 'omitida';
                    $fechaRegistroStr = $claseOmitida->created_at ? $claseOmitida->created_at->format('d/m/Y H:i:s') : null;
                    $observacion = $claseOmitida->motivo;
                } elseif ($firstAsistencia) {
                    $fechaRegistroStr = $firstAsistencia->created_at ? $firstAsistencia->created_at->format('d/m/Y H:i:s') : null;
                    $esPosterior = (bool)$firstAsistencia->es_retroactiva || ($firstAsistencia->created_at && $firstAsistencia->created_at->format('Y-m-d') > $fechaActual);
                    $modoRegistro = $esPosterior ? 'retroactivo' : 'en_fecha';
                    $observacion = $firstAsistencia->justificacion_retroactiva;
                }

                $item = [
                    'fecha'                 => $cursor->format('d/m/Y'),
                    'fecha_iso'             => $fechaActual,
                    'dia_semana'            => ucfirst($diaSemanaActual),
                    'docente_id'            => $horario->docente->id,
                    'docente_ci'            => $horario->docente->ci,
                    'docente_nombre'        => "{$horario->docente->apellido} {$horario->docente->nombre}",
                    'materia_codigo'        => $horario->materia->codigo,
                    'materia_nombre'        => $horario->materia->nombre,
                    'carrera'               => $horario->materia->carrera->nombre ?? '',
                    'hora_inicio'           => $horario->hora_inicio,
                    'hora_fin'              => $horario->hora_fin,
                    'aula'                  => $horario->aula,
                    'tipo'                  => ucfirst($horario->tipo),
                    'total_estudiantes'     => $totalEstudiantes,
                    'marcados'              => $marcados,
                    'sin_marcar'            => $marcados === 0 && !$claseOmitida,
                    'parcialmente_marcado'  => $marcados > 0 && $marcados < $totalEstudiantes && !$claseOmitida,
                    'omitida'               => (bool)$claseOmitida,
                    'fecha_registro'        => $fechaRegistroStr,
                    'modo_registro'         => $modoRegistro,
                    'observacion'           => $observacion,
                ];

                $todasLasClases[] = $item;

                if ($claseOmitida) {
                    $omitidasList[] = $item;
                } elseif ($marcados === 0) {
                    $sinMarcarList[] = $item;
                } elseif ($marcados < $totalEstudiantes) {
                    $parcialList[] = $item;
                } else {
                    $marcadasList[] = $item;
                }
            }

            $cursor->addDay();
        }

        return response()->json([
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin'    => $fin->toDateString(),
            'sin_marcar'   => $sinMarcarList,
            'marcadas'     => $marcadasList,
            'parcialmente' => $parcialList,
            'omitidas'     => $omitidasList,
            'todas'        => $todasLasClases,
            'total_clases' => $totalClasesEnRango,
        ]);
    }

    /**
     * Estudiantes con más de N faltas ACTIVAS acumuladas (después de la última notificación/acción tomada).
     */
    public function estudiantesConFaltas(Request $request)
    {
        $limite = (int) $request->query('limite', 2);
        $materiaId = $request->query('materia_id');
        $admin = $request->user();
        $isSuperAdmin = $admin ? (bool)$admin->super_admin : true;
        $carreraId = $request->query('carrera_id') ?: ($isSuperAdmin ? null : $admin?->carrera_id);

        $query = Inscripcion::with(['estudiante.carrera', 'materia.carrera', 'accionesFaltas.admin'])
            ->select('inscripciones.*')
            ->join('materias', 'materias.id', '=', 'inscripciones.materia_id');

        if ($materiaId) {
            $query->where('inscripciones.materia_id', $materiaId);
        }

        if ($carreraId) {
            $query->where('materias.carrera_id', $carreraId);
        }

        $inscripciones = $query->get();

        $resultado = $inscripciones->map(function ($insc) {
            $ultimaAccion = $insc->ultimaAccionFalta();
            $totalFaltasActivas = $insc->contarFaltasActivas();
            $totalFaltasHistoricas = $insc->asistencias()->where('estado', 'ausente')->count();

            $fechasFaltas = $insc->asistencias()
                ->where('estado', 'ausente')
                ->with(['horario.docente'])
                ->orderBy('fecha', 'desc')
                ->get()
                ->map(function ($a) use ($ultimaAccion) {
                    $fechaObj = $a->fecha ? Carbon::parse($a->fecha) : null;
                    $esNotificada = false;
                    if ($ultimaAccion && $fechaObj) {
                        $esNotificada = $fechaObj->toDateString() <= $ultimaAccion->fecha_accion->toDateString();
                    }

                    return [
                        'id'             => $a->id,
                        'fecha'          => $fechaObj ? $fechaObj->format('d/m/Y') : '',
                        'fecha_iso'      => $fechaObj ? $fechaObj->toDateString() : '',
                        'docente_nombre' => $a->docente ? "{$a->docente->apellido} {$a->docente->nombre}" : 'Docente',
                        'es_retroactiva' => (bool)$a->es_retroactiva,
                        'justificacion'  => $a->justificacion_retroactiva,
                        'es_notificada'  => $esNotificada,
                    ];
                });

            $accionesTomadas = $insc->accionesFaltas
                ->sortByDesc('fecha_accion')
                ->map(function ($acc) {
                    return [
                        'id'           => $acc->id,
                        'fecha_accion' => $acc->fecha_accion ? $acc->fecha_accion->format('d/m/Y H:i') : '',
                        'observacion'  => $acc->observacion,
                        'admin_nombre' => $acc->admin ? "{$acc->admin->nombre} {$acc->admin->apellido}" : 'Director de Carrera',
                    ];
                })->values();

            return [
                'inscripcion_id'         => $insc->id,
                'estudiante_id'          => $insc->estudiante_id,
                'estudiante_carnet'      => $insc->estudiante->carnet ?? '',
                'estudiante_nombre'      => trim(($insc->estudiante->primer_apellido ?? '') . ' ' . ($insc->estudiante->segundo_apellido ?? '') . ' ' . ($insc->estudiante->nombres ?? '')),
                'carrera'                => $insc->estudiante->carrera->nombre ?? '',
                'materia_id'             => $insc->materia_id,
                'materia_codigo'         => $insc->materia->codigo ?? '',
                'materia_nombre'         => $insc->materia->nombre ?? '',
                'total_faltas'           => $totalFaltasActivas,
                'total_faltas_historicas' => $totalFaltasHistoricas,
                'estado_inscripcion'     => $insc->estado,
                'fecha_abandono'         => $insc->fecha_abandono ? $insc->fecha_abandono->format('d/m/Y H:i') : null,
                'motivo_abandono'        => $insc->motivo_abandono,
                'fechas_faltas'          => $fechasFaltas,
                'acciones_tomadas'       => $accionesTomadas,
                'tiene_notificacion'     => $accionesTomadas->count() > 0,
            ];
        });

        // Filtrar por umbral de faltas activas
        $filtrado = $resultado->filter(fn($item) => $item['total_faltas'] >= $limite)->values();

        return response()->json([
            'limite'      => $limite,
            'total'       => $filtrado->count(),
            'estudiantes' => $filtrado,
        ]);
    }

    /**
     * Registra una Acción Tomada (Notificación) por el Director de Carrera para reiniciar faltas activas.
     */
    public function registrarAccionTomada(Request $request, $inscripcionId)
    {
        $request->validate([
            'observacion' => 'required|string|min:5|max:2000',
        ]);

        $inscripcion = Inscripcion::findOrFail($inscripcionId);
        $admin = $request->user();

        $accion = \App\Models\AccionFalta::create([
            'inscripcion_id' => $inscripcion->id,
            'fecha_accion'   => Carbon::now(),
            'observacion'    => $request->observacion,
            'admin_id'       => $admin?->id,
        ]);

        return response()->json([
            'message' => '✅ Acción tomada / Notificación registrada exitosamente. El contador de faltas para este estudiante se ha actualizado.',
            'accion'  => $accion,
        ]);
    }

    /**
     * Retorna todas las materias inscritas por un estudiante con su estado de abandono.
     */
    public function inscripcionesAbandono(Request $request, $estudianteId)
    {
        $estudiante = \App\Models\Estudiante::with('carrera')->findOrFail($estudianteId);
        $inscripciones = Inscripcion::with('materia')
            ->where('estudiante_id', $estudiante->id)
            ->get()
            ->map(function ($i) {
                return [
                    'inscripcion_id'  => $i->id,
                    'materia_id'      => $i->materia_id,
                    'materia_codigo'  => $i->materia->codigo,
                    'materia_nombre'  => $i->materia->nombre,
                    'estado'          => $i->estado,
                    'es_abandono'     => $i->estado === 'abandono',
                    'motivo_abandono' => $i->motivo_abandono,
                ];
            });

        $totalInscritas = $inscripciones->count();
        $totalAbandono  = $inscripciones->where('es_abandono', true)->count();

        return response()->json([
            'estudiante' => [
                'id'              => $estudiante->id,
                'carnet'          => $estudiante->carnet,
                'nombre_completo' => trim("{$estudiante->primer_apellido} {$estudiante->segundo_apellido} {$estudiante->nombres}"),
                'carrera'         => $estudiante->carrera->nombre ?? '',
            ],
            'inscripciones'   => $inscripciones,
            'es_abandono_total' => $totalInscritas > 0 && $totalInscritas === $totalAbandono,
            'es_abandono_parcial' => $totalAbandono > 0 && $totalAbandono < $totalInscritas,
        ]);
    }

    /**
     * Permite al Director de Carrera cambiar el estado de un estudiante a Abandono (Total o Parcial).
     */
    public function cambiarAbandono(Request $request, $estudianteId)
    {
        $request->validate([
            'tipo_abandono' => 'required|in:total,parcial,restablecer',
            'materia_ids'   => 'nullable|array',
            'motivo'        => 'nullable|string|max:500',
        ]);

        $estudiante = \App\Models\Estudiante::findOrFail($estudianteId);
        $inscripciones = Inscripcion::where('estudiante_id', $estudiante->id)->get();

        DB::beginTransaction();
        try {
            if ($request->tipo_abandono === 'total') {
                foreach ($inscripciones as $i) {
                    $i->update([
                        'estado'          => 'abandono',
                        'fecha_abandono'  => Carbon::now(),
                        'motivo_abandono' => $request->motivo ?: 'Abandono Total de Carrera',
                    ]);
                }
                $msg = '✅ El estudiante ha sido registrado en ABANDONO TOTAL para todas sus materias.';
            } elseif ($request->tipo_abandono === 'parcial') {
                $materiaIdsAbandono = $request->materia_ids ?: [];
                foreach ($inscripciones as $i) {
                    if (in_array($i->materia_id, $materiaIdsAbandono)) {
                        $i->update([
                            'estado'          => 'abandono',
                            'fecha_abandono'  => Carbon::now(),
                            'motivo_abandono' => $request->motivo ?: 'Abandono Parcial de Materia',
                        ]);
                    } else {
                        $i->update([
                            'estado'          => 'activo',
                            'fecha_abandono'  => null,
                            'motivo_abandono' => null,
                        ]);
                    }
                }
                $msg = '✅ El estado de ABANDONO PARCIAL ha sido actualizado para las materias seleccionadas.';
            } else {
                // Restablecer a activo
                foreach ($inscripciones as $i) {
                    $i->update([
                        'estado'          => 'activo',
                        'fecha_abandono'  => null,
                        'motivo_abandono' => null,
                    ]);
                }
                $msg = '✅ Se ha restablecido al estudiante a estado ACTIVO en todas sus materias.';
            }

            DB::commit();

            return response()->json([
                'message' => $msg,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el estado de abandono del estudiante.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reporte de asistencias por rango de fechas, opcionalmente filtrado por materia.
     */
    public function reportePorFechas(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'materia_id'   => 'nullable|exists:materias,id',
        ]);

        $admin = $request->user();
        $isSuperAdmin = $admin ? (bool)$admin->super_admin : true;
        $carreraId = $request->query('carrera_id') ?: ($isSuperAdmin ? null : $admin?->carrera_id);

        $query = Asistencia::with([
            'inscripcion.estudiante',
            'inscripcion.materia.carrera',
            'horario',
            'docente',
        ])
        ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);

        if ($request->materia_id) {
            $query->whereHas('inscripcion', function ($q) use ($request) {
                $q->where('materia_id', $request->materia_id);
            });
        }

        if ($carreraId) {
            $query->whereHas('inscripcion.materia', function ($q) use ($carreraId) {
                $q->where('carrera_id', $carreraId);
            });
        }

        $asistencias = $query->orderBy('fecha')->orderBy('inscripcion_id')->get();

        $resultado = $asistencias->map(function ($a) {
            return [
                'fecha'              => $a->fecha ? $a->fecha->toDateString() : '',
                'materia_codigo'     => $a->inscripcion->materia->codigo ?? '',
                'materia_nombre'     => $a->inscripcion->materia->nombre ?? '',
                'carrera'            => $a->inscripcion->materia->carrera->nombre ?? '',
                'estudiante_carnet'  => $a->inscripcion->estudiante->carnet ?? '',
                'estudiante_nombre'  => trim(($a->inscripcion->estudiante->primer_apellido ?? '') . ' ' . ($a->inscripcion->estudiante->segundo_apellido ?? '') . ' ' . ($a->inscripcion->estudiante->nombres ?? '')),
                'estado'             => $a->estado,
                'es_retroactiva'     => $a->es_retroactiva,
                'justificacion'      => $a->justificacion_retroactiva,
                'docente'            => $a->docente ? "{$a->docente->apellido} {$a->docente->nombre}" : 'DOCENTE',
                'hora_inicio'        => $a->horario->hora_inicio ?? '',
                'tipo'               => $a->horario->tipo ?? 'teorica',
            ];
        });

        // Resumen por materia
        $resumen = $resultado->groupBy('materia_codigo')->map(function ($items, $codigo) {
            return [
                'materia_codigo' => $codigo,
                'materia_nombre' => $items->first()['materia_nombre'],
                'total_presentes'=> $items->where('estado', 'presente')->count(),
                'total_ausentes' => $items->where('estado', 'ausente')->count(),
                'total_permisos' => $items->where('estado', 'permiso')->count(),
                'total'          => $items->count(),
            ];
        })->values();

        return response()->json([
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'detalle'      => $resultado,
            'resumen'      => $resumen,
        ]);
    }

    /**
     * Dashboard: resumen general del día actual.
     */
    public function dashboard(Request $request)
    {
        $fecha = Carbon::today()->toDateString();
        $diasSemana = [
            0 => 'domingo', 1 => 'lunes', 2 => 'martes',
            3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado',
        ];
        $diaSemana = $diasSemana[Carbon::today()->dayOfWeek];

        $admin = $request->user();
        $isSuperAdmin = $admin ? (bool)$admin->super_admin : true;
        $carreraId = $request->query('carrera_id') ?: ($isSuperAdmin ? null : $admin?->carrera_id);

        $horariosHoy = Horario::where('dia_semana', $diaSemana)
            ->with('materia')
            ->when($carreraId, function ($q) use ($carreraId) {
                $q->whereHas('materia', fn($m) => $m->where('carrera_id', $carreraId));
            })
            ->get();

        $totalClases = $horariosHoy->count();
        $clasesConAsistencia = 0;

        foreach ($horariosHoy as $horario) {
            $marcados = Asistencia::where('horario_id', $horario->id)
                ->whereDate('fecha', $fecha)->count();
            if ($marcados > 0) $clasesConAsistencia++;
        }

        $estudiantesConFaltas = Inscripcion::select('inscripciones.*')
            ->join('materias', 'materias.id', '=', 'inscripciones.materia_id')
            ->withCount(['asistencias as total_faltas' => fn($q) => $q->where('estado', 'ausente')])
            ->having('total_faltas', '>', 2)
            ->when($carreraId, fn($q) => $q->where('materias.carrera_id', $carreraId))
            ->count();

        return response()->json([
            'fecha'                   => $fecha,
            'total_clases_hoy'        => $totalClases,
            'clases_con_asistencia'   => $clasesConAsistencia,
            'clases_sin_asistencia'   => $totalClases - $clasesConAsistencia,
            'estudiantes_con_faltas'  => $estudiantesConFaltas,
        ]);
    }

    /**
     * Retorna todos los horarios agrupados por Carrera y por Día de la semana.
     */
    public function horariosPorCarrera(Request $request)
    {
        $admin = $request->user();
        $isSuperAdmin = $admin ? (bool)$admin->super_admin : true;
        $carreraId = $request->query('carrera_id') ?: ($isSuperAdmin ? null : $admin?->carrera_id);
        $diaSemana = $request->query('dia_semana');

        $query = Horario::with(['materia.carrera', 'docente'])
            ->when($carreraId, function ($q) use ($carreraId) {
                $q->whereHas('materia', fn($m) => $m->where('carrera_id', $carreraId));
            })
            ->when($diaSemana, function ($q) use ($diaSemana) {
                $q->where('dia_semana', strtolower($diaSemana));
            })
            ->orderBy('hora_inicio');

        $horarios = $query->get();
        $carreras = \App\Models\Carrera::all();

        $mapped = $horarios->map(function ($h) {
            return [
                'id'             => $h->id,
                'carrera_id'     => $h->materia->carrera_id ?? null,
                'carrera_nombre' => $h->materia->carrera->nombre ?? 'Ingeniería de Sistemas',
                'carrera_sigla'  => $h->materia->carrera->sigla ?? 'CARSIS',
                'materia_codigo' => $h->materia->codigo ?? '',
                'materia_nombre' => $h->materia->nombre ?? '',
                'dia_semana'     => ucfirst($h->dia_semana),
                'hora_inicio'    => $h->hora_inicio,
                'hora_fin'       => $h->hora_fin,
                'aula'           => $h->aula,
                'tipo'           => ucfirst($h->tipo),
                'docente_nombre' => $h->docente ? "{$h->docente->apellido} {$h->docente->nombre}" : 'SIN DOCENTE',
                'docente_ci'     => $h->docente->ci ?? '',
            ];
        });

        return response()->json([
            'carreras' => $carreras,
            'total'    => $mapped->count(),
            'horarios' => $mapped,
        ]);
    }

    /**
     * Reporte detallado de asistencias e inasistencias por estudiante.
     */
    public function historialEstudiante(Request $request)
    {
        $buscar = $request->query('buscar');
        $estudianteId = $request->query('estudiante_id');
        $materiaId = $request->query('materia_id');
        $fechaInicio = $request->query('fecha_inicio', '2026-08-03');
        $fechaFin    = $request->query('fecha_fin', Carbon::today()->toDateString());

        $estudiante = null;

        if (!empty($estudianteId)) {
            $estudiante = \App\Models\Estudiante::with('carrera')->find($estudianteId);
        } elseif (!empty($buscar)) {
            $estudiante = \App\Models\Estudiante::with('carrera')
                ->where('carnet', 'like', "%{$buscar}%")
                ->orWhere('nombres', 'like', "%{$buscar}%")
                ->orWhere('primer_apellido', 'like', "%{$buscar}%")
                ->orWhere('segundo_apellido', 'like', "%{$buscar}%")
                ->first();
        }

        if (!$estudiante) {
            return response()->json([
                'estudiante'   => null,
                'asistencias'  => [],
                'estadisticas' => [
                    'total_clases'    => 0,
                    'presentes'       => 0,
                    'ausentes'        => 0,
                    'permisos'        => 0,
                    'porcentaje'      => 0,
                ],
                'materias'     => [],
            ]);
        }

        // Obtener las materias a las que está inscrito el estudiante
        $inscripciones = Inscripcion::with('materia')
            ->where('estudiante_id', $estudiante->id)
            ->get();

        $materiasInscritas = $inscripciones->map(fn($i) => [
            'id'     => $i->materia->id,
            'codigo' => $i->materia->codigo,
            'nombre' => $i->materia->nombre,
        ])->values();

        $materiasIds = $inscripciones->pluck('materia_id');
        $inscripcionesIds = $inscripciones->pluck('id');

        $query = Asistencia::with(['inscripcion.materia', 'horario.docente'])
            ->whereIn('inscripcion_id', $inscripcionesIds)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if (!empty($materiaId)) {
            $query->whereHas('inscripcion', function ($q) use ($materiaId) {
                $q->where('materia_id', $materiaId);
            });
        }

        $asistencias = $query->orderBy('fecha', 'desc')->get();

        $asistenciasMapped = $asistencias->map(function ($a) {
            $fechaObj = $a->fecha ? Carbon::parse($a->fecha) : null;
            return [
                'id'             => $a->id,
                'fecha'          => $fechaObj ? $fechaObj->format('d/m/Y') : '',
                'fecha_iso'      => $fechaObj ? $fechaObj->toDateString() : '',
                'materia_codigo' => $a->inscripcion->materia->codigo ?? '',
                'materia_nombre' => $a->inscripcion->materia->nombre ?? '',
                'estado'         => $a->estado,
                'es_retroactiva' => (bool)$a->es_retroactiva,
                'justificacion'  => $a->justificacion_retroactiva,
                'docente_nombre' => $a->docente ? "{$a->docente->apellido} {$a->docente->nombre}" : 'Docente',
                'hora_inicio'    => $a->horario->hora_inicio ?? '',
                'aula'           => $a->horario->aula ?? '',
            ];
        });

        // Consultar también las clases omitidas/no marcadas con motivo por los docentes
        $queryOmitidas = \App\Models\ClaseOmitida::with(['horario.materia', 'docente'])
            ->whereHas('horario', function ($q) use ($materiasIds, $materiaId) {
                $q->whereIn('materia_id', $materiasIds);
                if (!empty($materiaId)) {
                    $q->where('materia_id', $materiaId);
                }
            })
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        $clasesOmitidas = $queryOmitidas->get();

        $omitidasMapped = $clasesOmitidas->map(function ($co) {
            $fechaObj = $co->fecha ? Carbon::parse($co->fecha) : null;
            return [
                'id'             => 'omitida_' . $co->id,
                'fecha'          => $fechaObj ? $fechaObj->format('d/m/Y') : '',
                'fecha_iso'      => $fechaObj ? $fechaObj->toDateString() : '',
                'materia_codigo' => $co->horario->materia->codigo ?? '',
                'materia_nombre' => $co->horario->materia->nombre ?? '',
                'estado'         => 'omitida',
                'es_retroactiva' => false,
                'justificacion'  => $co->motivo,
                'docente_nombre' => $co->docente ? "{$co->docente->apellido} {$co->docente->nombre}" : 'Docente',
                'hora_inicio'    => $co->horario->hora_inicio ?? '',
                'aula'           => $co->horario->aula ?? '',
            ];
        });

        // Combinar ambas y ordenar por fecha descendente
        $todosLosRegistros = $asistenciasMapped->concat($omitidasMapped)->sortByDesc('fecha_iso')->values();

        $totalClases = $asistenciasMapped->count();
        $presentes   = $asistenciasMapped->where('estado', 'presente')->count();
        $ausentes    = $asistenciasMapped->where('estado', 'ausente')->count();
        $permisos    = $asistenciasMapped->where('estado', 'permiso')->count();
        $omitidasCount = $omitidasMapped->count();
        $porcentaje  = $totalClases > 0 ? round(($presentes / $totalClases) * 100, 1) : 0;

        return response()->json([
            'estudiante' => [
                'id'              => $estudiante->id,
                'carnet'          => $estudiante->carnet,
                'nombre_completo' => trim("{$estudiante->primer_apellido} {$estudiante->segundo_apellido} {$estudiante->nombres}"),
                'carrera'         => $estudiante->carrera->nombre ?? '',
            ],
            'estadisticas' => [
                'total_clases' => $totalClases,
                'presentes'    => $presentes,
                'ausentes'     => $ausentes,
                'permisos'     => $permisos,
                'omitidas'     => $omitidasCount,
                'porcentaje'   => $porcentaje,
            ],
            'materias'    => $materiasInscritas,
            'asistencias' => $todosLosRegistros,
        ]);
    }
}
