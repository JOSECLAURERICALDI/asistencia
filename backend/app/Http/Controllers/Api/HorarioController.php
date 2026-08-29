<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClaseOmitida;
use App\Models\Horario;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    /**
     * Retorna las materias (horarios) del docente autenticado para el día actual
     * o para una fecha específica (solo fechas pasadas o presente, no futuras).
     */
    public function materiasDelDia(Request $request)
    {
        $fechaParam = $request->query('fecha');

        if ($fechaParam) {
            $fecha = Carbon::parse($fechaParam);
        } else {
            $fecha = Carbon::today();
        }

        $hoy = Carbon::today();

        // Bloquear fechas futuras
        if ($fecha->greaterThan($hoy)) {
            return response()->json([
                'message' => 'No se pueden consultar fechas futuras.',
            ], 403);
        }

        $diasSemana = [
            0 => 'domingo', 1 => 'lunes', 2 => 'martes',
            3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado',
        ];

        $diaSemana = $diasSemana[$fecha->dayOfWeek];
        $esRetroactiva = $fecha->lessThan($hoy);
        $docente = $this->resolveDocente($request);

        if (!$docente) {
            return response()->json(['message' => 'Docente no autenticado.'], 401);
        }

        $horarios = Horario::where('docente_id', $docente->id)
            ->where('dia_semana', $diaSemana)
            ->with(['materia.carrera'])
            ->orderBy('hora_inicio')
            ->get();

        $mapped = $horarios->map(function ($h) use ($fecha) {
            $fechaStr = $fecha->toDateString();
            $asistenciaExists = $h->asistencias()->whereDate('fecha', $fechaStr)->exists();
            $omitidaExists    = ClaseOmitida::where('horario_id', $h->id)->whereDate('fecha', $fechaStr)->exists();

            return [
                'id'             => $h->id,
                'materia_id'     => $h->materia_id,
                'materia_nombre' => $h->materia->nombre,
                'materia_codigo' => $h->materia->codigo,
                'carrera'        => $h->materia->carrera->nombre ?? '',
                'dia_semana'     => $h->dia_semana,
                'hora_inicio'    => $h->hora_inicio,
                'hora_fin'       => $h->hora_fin,
                'aula'           => $h->aula,
                'tipo'           => $h->tipo,
                'ya_registrado'  => $asistenciaExists || $omitidaExists,
            ];
        });

        // Filtrar clases pendientes (las que aún no fueron registradas u omitidas)
        $pendientes = $mapped->filter(fn ($item) => !$item['ya_registrado'])->values();

        return response()->json([
            'fecha'          => $fecha->toDateString(),
            'dia_semana'     => $diaSemana,
            'es_retroactiva' => $esRetroactiva,
            'horarios'       => $pendientes,
        ]);
    }

    /**
     * Retorna todas las clases pasadas sin marcar asistencia desde el inicio de la gestión (3 de Agosto de 2026).
     */
    public function clasesPendientesPasadas(Request $request)
    {
        $docente = $this->resolveDocente($request);

        if (!$docente) {
            return response()->json(['message' => 'Docente no autenticado.'], 401);
        }

        $periodo = \App\Models\PeriodoAcademico::where('activo', true)->first();
        $fechaInicio = $periodo ? Carbon::parse($periodo->fecha_inicio) : Carbon::parse('2026-08-03');
        $ayer = Carbon::yesterday();

        if ($fechaInicio->greaterThan($ayer)) {
            return response()->json([
                'fecha_inicio'       => $fechaInicio->toDateString(),
                'total_pendientes'   => 0,
                'pendientes_pasadas' => [],
            ]);
        }

        $diasSemana = [
            0 => 'domingo', 1 => 'lunes', 2 => 'martes',
            3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado',
        ];

        $horariosDocente = Horario::where('docente_id', $docente->id)
            ->with(['materia.carrera'])
            ->get();

        if ($horariosDocente->isEmpty()) {
            return response()->json([
                'fecha_inicio'       => $fechaInicio->toDateString(),
                'total_pendientes'   => 0,
                'pendientes_pasadas' => [],
            ]);
        }

        $pendientesPasadas = [];
        $cursor = $fechaInicio->copy();

        while ($cursor->lessThanOrEqualTo($ayer)) {
            $fechaStr = $cursor->toDateString();
            $diaSemanaNombre = $diasSemana[$cursor->dayOfWeek];

            $horariosDelDia = $horariosDocente->where('dia_semana', $diaSemanaNombre);

            foreach ($horariosDelDia as $h) {
                $asistenciaExists = $h->asistencias()->whereDate('fecha', $fechaStr)->exists();
                $omitidaExists    = ClaseOmitida::where('horario_id', $h->id)->whereDate('fecha', $fechaStr)->exists();

                if (!$asistenciaExists && !$omitidaExists) {
                    $pendientesPasadas[] = [
                        'id'             => $h->id,
                        'fecha'          => $fechaStr,
                        'materia_id'     => $h->materia_id,
                        'materia_nombre' => $h->materia->nombre,
                        'materia_codigo' => $h->materia->codigo,
                        'carrera'        => $h->materia->carrera->nombre ?? '',
                        'dia_semana'     => ucfirst($h->dia_semana),
                        'hora_inicio'    => $h->hora_inicio,
                        'hora_fin'       => $h->hora_fin,
                        'aula'           => $h->aula,
                        'tipo'           => ucfirst($h->tipo),
                    ];
                }
            }

            $cursor->addDay();
        }

        return response()->json([
            'fecha_inicio'       => $fechaInicio->toDateString(),
            'total_pendientes'   => count($pendientesPasadas),
            'pendientes_pasadas' => array_reverse($pendientesPasadas),
        ]);
    }

    /**
     * Registra una clase como omitida / no marcada con observación o motivo justificado por el docente.
     */
    public function omitirClase(Request $request)
    {
        $data = $request->validate([
            'horario_id' => 'required|exists:horarios,id',
            'fecha'      => 'required|date',
            'motivo'     => 'required|string|max:500',
        ]);

        $docente = $this->resolveDocente($request);

        $claseOmitida = ClaseOmitida::updateOrCreate(
            [
                'horario_id' => $data['horario_id'],
                'fecha'      => $data['fecha'],
            ],
            [
                'motivo'     => $data['motivo'],
                'docente_id' => $docente?->id,
            ]
        );

        return response()->json([
            'message'       => '✅ Clase registrada como no marcada con justificación exitosamente.',
            'clase_omitida' => $claseOmitida,
        ]);
    }

    private function resolveDocente(Request $request): ?\App\Models\Docente
    {
        $token = $request->bearerToken();
        if ($token) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable instanceof \App\Models\Docente) {
                return $accessToken->tokenable;
            }
        }

        if ($request->query('ci')) {
            return \App\Models\Docente::where('ci', trim($request->query('ci')))->first();
        }

        $user = $request->user();
        if ($user instanceof \App\Models\Docente) {
            return $user;
        }

        return null;
    }
}
