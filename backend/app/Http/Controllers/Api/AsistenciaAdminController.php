<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Horario;
use App\Models\Inscripcion;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AsistenciaAdminController extends Controller
{
    /**
     * Lista y filtra asistencias registradas para supervisión y edición administrativa.
     */
    public function index(Request $request)
    {
        $fecha = $request->query('fecha');
        $carreraId = $request->query('carrera_id');
        $materiaId = $request->query('materia_id');
        $docenteId = $request->query('docente_id');
        $buscar = $request->query('buscar');

        $query = Asistencia::with([
            'inscripcion.estudiante',
            'inscripcion.materia.carrera',
            'horario.docente',
        ]);

        if (!empty($fecha)) {
            $query->whereDate('fecha', $fecha);
        }

        if (!empty($carreraId)) {
            $query->whereHas('inscripcion.materia', function ($q) use ($carreraId) {
                $q->where('carrera_id', $carreraId);
            });
        }

        if (!empty($materiaId)) {
            $query->whereHas('horario', function ($q) use ($materiaId) {
                $q->where('materia_id', $materiaId);
            });
        }

        if (!empty($docenteId)) {
            $query->whereHas('horario', function ($q) use ($docenteId) {
                $q->where('docente_id', $docenteId);
            });
        }

        if (!empty($buscar)) {
            $query->whereHas('inscripcion.estudiante', function ($q) use ($buscar) {
                $q->where('carnet', 'like', "%{$buscar}%")
                  ->orWhere('nombres', 'like', "%{$buscar}%")
                  ->orWhere('primer_apellido', 'like', "%{$buscar}%")
                  ->orWhere('segundo_apellido', 'like', "%{$buscar}%");
            });
        }

        $asistencias = $query->orderBy('fecha', 'desc')->paginate(30);

        return response()->json($asistencias);
    }

    /**
     * Permite al administrador editar cualquier asistencia ya marcada.
     */
    public function update(Request $request, int $id)
    {
        $asistencia = Asistencia::findOrFail($id);

        $data = $request->validate([
            'estado'                   => 'required|in:presente,permiso,ausente',
            'justificacion_retroactiva'=> 'nullable|string|max:500',
        ]);

        $asistencia->update([
            'estado'                    => $data['estado'],
            'justificacion_retroactiva' => $data['justificacion_retroactiva'] ?? $asistencia->justificacion_retroactiva,
        ]);

        return response()->json([
            'message'    => 'Asistencia actualizada correctamente por el administrador.',
            'asistencia' => $asistencia->load(['inscripcion.estudiante', 'horario.docente']),
        ]);
    }

    /**
     * Permite al administrador crear o modificar asistencia de un estudiante directamente.
     */
    public function storeOrUpdate(Request $request)
    {
        $data = $request->validate([
            'inscripcion_id'           => 'required|exists:inscripciones,id',
            'horario_id'               => 'required|exists:horarios,id',
            'fecha'                    => 'required|date',
            'estado'                   => 'required|in:presente,permiso,ausente',
            'justificacion_retroactiva'=> 'nullable|string|max:500',
        ]);

        $horario = Horario::findOrFail($data['horario_id']);

        $asistencia = Asistencia::updateOrCreate(
            [
                'inscripcion_id' => $data['inscripcion_id'],
                'horario_id'     => $data['horario_id'],
                'fecha'          => $data['fecha'],
            ],
            [
                'estado'                    => $data['estado'],
                'es_retroactiva'            => true,
                'justificacion_retroactiva' => $data['justificacion_retroactiva'] ?? 'Modificado por Administrador',
                'registrado_por'            => $horario->docente_id,
            ]
        );

        return response()->json([
            'message'    => 'Registro de asistencia actualizado con éxito.',
            'asistencia' => $asistencia,
        ]);
    }
}
