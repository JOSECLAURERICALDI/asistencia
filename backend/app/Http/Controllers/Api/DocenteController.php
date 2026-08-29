<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DocenteController extends Controller
{
    public function index() { return Docente::select('id','ci','nombre','apellido','email','activo')->get(); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ci'       => 'required|unique:docentes',
            'nombre'   => 'required',
            'apellido' => 'required',
            'email'    => 'nullable|email|unique:docentes',
            'password' => 'required|min:6',
        ]);
        $data['password'] = Hash::make($data['password']);
        return Docente::create($data);
    }

    public function show(Docente $docente) { return $docente->makeHidden('password'); }

    public function update(Request $request, Docente $docente)
    {
        $data = $request->validate([
            'nombre'   => 'required',
            'apellido' => 'required',
            'email'    => 'nullable|email|unique:docentes,email,'.$docente->id,
            'activo'   => 'boolean',
            'password' => 'nullable|min:6',
        ]);
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $docente->update($data);
        return $docente->makeHidden('password');
    }

    public function destroy(Docente $docente) { $docente->delete(); return response()->noContent(); }
}
