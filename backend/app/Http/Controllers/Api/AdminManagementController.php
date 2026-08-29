<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    public function index(Request $request)
    {
        $admins = Admin::with('carrera')->orderBy('nombre')->get();
        return response()->json($admins);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:255',
            'email'       => 'required|email|unique:admins,email',
            'password'    => 'required|string|min:6',
            'carrera_id'  => 'nullable|exists:carreras,id',
            'super_admin' => 'boolean',
        ]);

        $admin = Admin::create([
            'nombre'      => $data['nombre'],
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),
            'carrera_id'  => $data['carrera_id'] ?? null,
            'super_admin' => $data['super_admin'] ?? false,
        ]);

        return response()->json([
            'message' => 'Director / Administrador creado exitosamente.',
            'admin'   => $admin->load('carrera'),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $admin = Admin::findOrFail($id);

        $data = $request->validate([
            'nombre'      => 'required|string|max:255',
            'email'       => 'required|email|unique:admins,email,' . $id,
            'password'    => 'nullable|string|min:6',
            'carrera_id'  => 'nullable|exists:carreras,id',
            'super_admin' => 'boolean',
        ]);

        $updateData = [
            'nombre'      => $data['nombre'],
            'email'       => $data['email'],
            'carrera_id'  => $data['carrera_id'] ?? null,
            'super_admin' => $data['super_admin'] ?? false,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $admin->update($updateData);

        return response()->json([
            'message' => 'Usuario administrativo actualizado exitosamente.',
            'admin'   => $admin->load('carrera'),
        ]);
    }

    public function destroy(int $id)
    {
        $admin = Admin::findOrFail($id);
        $admin->delete();

        return response()->json([
            'message' => 'Usuario administrativo eliminado.',
        ]);
    }
}
