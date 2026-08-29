<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PeriodoAcademico;
use Illuminate\Http\Request;

class PeriodoController extends Controller
{
    public function index() { return PeriodoAcademico::orderBy('fecha_inicio','desc')->get(); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'       => 'required',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after:fecha_inicio',
            'activo'       => 'boolean',
        ]);
        if (!empty($data['activo']) && $data['activo']) {
            PeriodoAcademico::query()->update(['activo' => false]);
        }
        return PeriodoAcademico::create($data);
    }

    public function show(PeriodoAcademico $periodo) { return $periodo; }

    public function update(Request $request, PeriodoAcademico $periodo)
    {
        $data = $request->validate([
            'nombre'       => 'required',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after:fecha_inicio',
            'activo'       => 'boolean',
        ]);
        if (!empty($data['activo']) && $data['activo']) {
            PeriodoAcademico::where('id','!=',$periodo->id)->update(['activo' => false]);
        }
        $periodo->update($data);
        return $periodo;
    }

    public function destroy(PeriodoAcademico $periodo) { $periodo->delete(); return response()->noContent(); }
}
