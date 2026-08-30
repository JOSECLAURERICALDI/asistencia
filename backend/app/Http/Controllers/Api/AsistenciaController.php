<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Horario;
use App\Models\Inscripcion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsistenciaController extends Controller
{
    /**
     * Retorna la nómina de estudiantes de un horario para una fecha dada,
     * con su estado de asistencia si ya fue registrado.
     */
    public function nomina(Request $request, int $horarioId)
    {
        $horario = Horario::with('materia')->findOrFail($horarioId);
        $userId = $request->user()?->id ?? $horario->docente_id;

        // Solo el docente asignado puede ver la nómina
        if ($horario->docente_id !== $userId) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $fecha = $request->query('fecha', Carbon::today()->toDateString());
        $fechaCarbon = Carbon::parse($fecha);
        $hoy = Carbon::today();

        // Bloquear fechas futuras
        if ($fechaCarbon->greaterThan($hoy)) {
            return response()->json(['message' => 'No se pueden consultar fechas futuras.'], 403);
        }

        $inscripciones = Inscripcion::where('materia_id', $horario->materia_id)
            ->with(['estudiante', 'asistencias' => function ($q) use ($fecha, $horarioId) {
                $q->where('horario_id', $horarioId)->whereDate('fecha', $fecha);
            }])
            ->get();

        $nomina = $inscripciones->map(function ($insc) use ($fecha, $horarioId) {
            $asistencia = $insc->asistencias->first();
            $esAbandono = $insc->estado === 'abandono';
            return [
                'inscripcion_id'    => $insc->id,
                'estudiante_id'     => $insc->estudiante->id,
                'carnet'            => $insc->estudiante->carnet,
                'nombre_completo'   => trim("{$insc->estudiante->primer_apellido} {$insc->estudiante->segundo_apellido} {$insc->estudiante->nombres}"),
                'primer_apellido'   => $insc->estudiante->primer_apellido,
                'segundo_apellido'  => $insc->estudiante->segundo_apellido,
                'nombres'           => $insc->estudiante->nombres,
                'estado'            => $asistencia?->estado ?? null,
                'asistencia_id'     => $asistencia?->id,
                'es_retroactiva'    => $asistencia?->es_retroactiva ?? false,
                'justificacion'     => $asistencia?->justificacion_retroactiva,
                'estado_inscripcion'=> $insc->estado,
                'es_abandono'       => $esAbandono,
                'motivo_abandono'   => $insc->motivo_abandono,
            ];
        });

        return response()->json([
            'horario' => [
                'id'             => $horario->id,
                'materia_nombre' => $horario->materia->nombre,
                'materia_codigo' => $horario->materia->codigo,
                'hora_inicio'    => $horario->hora_inicio,
                'hora_fin'       => $horario->hora_fin,
                'aula'           => $horario->aula,
                'tipo'           => $horario->tipo,
            ],
            'fecha'   => $fecha,
            'nomina'  => $nomina->sortBy('primer_apellido')->values(),
        ]);
    }

    /**
     * Guarda o actualiza la asistencia de un estudiante en una fecha.
     */
    public function registrar(Request $request)
    {
        $data = $request->validate([
            'horario_id'               => 'required|exists:horarios,id',
            'inscripcion_id'           => 'required|exists:inscripciones,id',
            'fecha'                    => 'required|date',
            'estado'                   => 'required|in:presente,permiso,ausente',
            'justificacion_retroactiva'=> 'nullable|string|max:500',
        ]);

        $inscripcion = Inscripcion::findOrFail($data['inscripcion_id']);
        if ($inscripcion->estado === 'abandono') {
            return response()->json([
                'message' => 'El estudiante se encuentra en estado de ABANDONO de esta materia. No se puede registrar asistencia.',
            ], 422);
        }

        $horario = Horario::findOrFail($data['horario_id']);
        $userId = $request->user()?->id ?? $horario->docente_id;

        if ($horario->docente_id !== $userId) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $fecha = Carbon::parse($data['fecha']);
        $hoy = Carbon::today();

        // Bloquear fechas futuras
        if ($fecha->greaterThan($hoy)) {
            return response()->json(['message' => 'No se pueden registrar fechas futuras.'], 403);
        }

        $esRetroactiva = $fecha->lessThan($hoy);

        // Si es retroactiva, requiere justificación
        if ($esRetroactiva && empty($data['justificacion_retroactiva'])) {
            return response()->json([
                'message' => 'Debe ingresar una justificación para registrar asistencia en fecha anterior.',
            ], 422);
        }

        $asistencia = Asistencia::updateOrCreate(
            [
                'inscripcion_id' => $data['inscripcion_id'],
                'horario_id'     => $data['horario_id'],
                'fecha'          => $data['fecha'],
            ],
            [
                'estado'                     => $data['estado'],
                'es_retroactiva'             => $esRetroactiva,
                'justificacion_retroactiva'  => $esRetroactiva ? $data['justificacion_retroactiva'] : null,
                'registrado_por'             => $userId,
            ]
        );

        return response()->json([
            'message'    => 'Asistencia registrada correctamente.',
            'asistencia' => $asistencia,
        ]);
    }

    /**
     * Guardar asistencia masiva (toda la nómina de una vez).
     */
    public function registrarMasivo(Request $request)
    {
        $data = $request->validate([
            'horario_id'               => 'required|exists:horarios,id',
            'fecha'                    => 'required|date',
            'justificacion_retroactiva'=> 'nullable|string|max:500',
            'asistencias'              => 'required|array',
            'asistencias.*.inscripcion_id' => 'required|exists:inscripciones,id',
            'asistencias.*.estado'         => 'required|in:presente,permiso,ausente',
        ]);

        $horario = Horario::findOrFail($data['horario_id']);
        $userId = $request->user()?->id ?? $horario->docente_id;

        if ($horario->docente_id !== $userId) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $fecha = Carbon::parse($data['fecha']);
        $hoy   = Carbon::today();

        if ($fecha->greaterThan($hoy)) {
            return response()->json(['message' => 'No se pueden registrar fechas futuras.'], 403);
        }

        $esRetroactiva = $fecha->lessThan($hoy);

        if ($esRetroactiva && empty($data['justificacion_retroactiva'])) {
            return response()->json([
                'message' => 'Debe ingresar una justificación para registrar asistencia en fecha anterior.',
            ], 422);
        }

        DB::transaction(function () use ($data, $esRetroactiva, $userId) {
            foreach ($data['asistencias'] as $item) {
                $inscripcion = Inscripcion::find($item['inscripcion_id']);
                // Omitir registro para inscripciones en abandono
                if ($inscripcion && $inscripcion->estado === 'abandono') {
                    continue;
                }

                Asistencia::updateOrCreate(
                    [
                        'inscripcion_id' => $item['inscripcion_id'],
                        'horario_id'     => $data['horario_id'],
                        'fecha'          => $data['fecha'],
                    ],
                    [
                        'estado'                    => $item['estado'],
                        'es_retroactiva'            => $esRetroactiva,
                        'justificacion_retroactiva' => $esRetroactiva ? $data['justificacion_retroactiva'] : null,
                        'registrado_por'            => $userId,
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Asistencia registrada correctamente para todos los estudiantes.',
        ]);
    }
}
