<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Horario;
use Illuminate\Http\Request;

class HorarioAdminController extends Controller
{
    public function index(Request $request)
    {
        return Horario::with('materia.carrera','docente')
            ->when($request->materia_id, fn($q,$v) => $q->where('materia_id',$v))
            ->when($request->docente_id, fn($q,$v) => $q->where('docente_id',$v))
            ->orderBy('dia_semana')->orderBy('hora_inicio')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'materia_id'  => 'required|exists:materias,id',
            'docente_id'  => 'required|exists:docentes,id',
            'dia_semana'  => 'required|in:lunes,martes,miercoles,jueves,viernes,sabado',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin'    => 'required|date_format:H:i|after:hora_inicio',
            'aula'        => 'nullable|string',
            'tipo'        => 'required|in:teorica,practica',
        ]);
        return Horario::create($data)->load('materia.carrera','docente');
    }

    public function show(Horario $horario) { return $horario->load('materia.carrera','docente'); }

    public function update(Request $request, Horario $horario)
    {
        $horario->update($request->validate([
            'materia_id'  => 'required|exists:materias,id',
            'docente_id'  => 'required|exists:docentes,id',
            'dia_semana'  => 'required|in:lunes,martes,miercoles,jueves,viernes,sabado',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin'    => 'required|date_format:H:i|after:hora_inicio',
            'aula'        => 'nullable|string',
            'tipo'        => 'required|in:teorica,practica',
        ]));
        return $horario->load('materia.carrera','docente');
    }

    public function destroy(Horario $horario) { $horario->delete(); return response()->noContent(); }
}
