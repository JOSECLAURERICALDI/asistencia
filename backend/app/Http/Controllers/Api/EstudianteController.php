<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        return Estudiante::with('carrera')
            ->when($request->carrera_id, fn($q,$v) => $q->where('carrera_id',$v))
            ->orderBy('primer_apellido')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'carnet'           => 'required|unique:estudiantes',
            'primer_apellido'  => 'required',
            'segundo_apellido' => 'nullable',
            'nombres'          => 'required',
            'email'            => 'nullable|email',
            'carrera_id'       => 'required|exists:carreras,id',
        ]);
        return Estudiante::create($data)->load('carrera');
    }

    public function show(Estudiante $estudiante) { return $estudiante->load('carrera'); }

    public function update(Request $request, Estudiante $estudiante)
    {
        $estudiante->update($request->validate([
            'primer_apellido'  => 'required',
            'segundo_apellido' => 'nullable',
            'nombres'          => 'required',
            'email'            => 'nullable|email',
            'activo'           => 'boolean',
        ]));
        return $estudiante->load('carrera');
    }

    public function destroy(Estudiante $estudiante) { $estudiante->delete(); return response()->noContent(); }

    /**
     * Inscribe a un estudiante a una materia en el periodo activo.
     */
    public function inscribir(Request $request, int $estudianteId)
    {
        $data = $request->validate([
            'materia_id' => 'required|exists:materias,id',
        ]);

        $periodo = \App\Models\PeriodoAcademico::where('activo', true)->first()
            ?? \App\Models\PeriodoAcademico::first();

        $inscripcion = \App\Models\Inscripcion::firstOrCreate([
            'estudiante_id'        => $estudianteId,
            'materia_id'           => $data['materia_id'],
            'periodo_academico_id' => $periodo->id,
        ]);

        return response()->json([
            'message'     => 'Estudiante inscrito en la materia exitosamente.',
            'inscripcion' => $inscripcion->load('materia'),
        ]);
    }

    /**
     * Desinscribe a un estudiante de una materia.
     */
    public function desinscribir(int $estudianteId, int $materiaId)
    {
        \App\Models\Inscripcion::where('estudiante_id', $estudianteId)
            ->where('materia_id', $materiaId)
            ->delete();

        return response()->json([
            'message' => 'Inscripción eliminada correctamente.',
        ]);
    }
}
