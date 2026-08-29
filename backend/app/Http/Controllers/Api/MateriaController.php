<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    public function index(Request $request)
    {
        return Materia::with('carrera','periodoAcademico')
            ->when($request->carrera_id, fn($q,$v) => $q->where('carrera_id',$v))
            ->when($request->periodo_id, fn($q,$v) => $q->where('periodo_academico_id',$v))
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo'               => 'required',
            'nombre'               => 'required',
            'carrera_id'           => 'required|exists:carreras,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
            'horas_teoricas'       => 'integer|min:0',
            'horas_practicas'      => 'integer|min:0',
        ]);
        return Materia::create($data)->load('carrera','periodoAcademico');
    }

    public function show(Materia $materia) { return $materia->load('carrera','periodoAcademico','horarios.docente'); }

    public function update(Request $request, Materia $materia)
    {
        $materia->update($request->validate([
            'codigo'               => 'required',
            'nombre'               => 'required',
            'carrera_id'           => 'required|exists:carreras,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
            'horas_teoricas'       => 'integer|min:0',
            'horas_practicas'      => 'integer|min:0',
        ]));
        return $materia->load('carrera','periodoAcademico');
    }

    public function destroy(Materia $materia) { $materia->delete(); return response()->noContent(); }
}
