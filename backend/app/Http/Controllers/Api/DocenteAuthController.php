<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DocenteAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'ci' => 'required|string',
        ]);

        $docente = Docente::where('ci', trim($request->ci))
            ->where('activo', true)
            ->first();

        if (!$docente) {
            return response()->json([
                'message' => 'El número de CI introducido no se encuentra registrado como docente activo.',
            ], 401);
        }

        $token = $docente->createToken('docente-token')->plainTextToken;

        return response()->json([
            'token'   => $token,
            'docente' => [
                'id'       => $docente->id,
                'ci'       => $docente->ci,
                'nombre'   => $docente->nombre,
                'apellido' => $docente->apellido,
                'email'    => $docente->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
