<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AsistenciaController;
use App\Http\Controllers\Api\DocenteAuthController;
use App\Http\Controllers\Api\HorarioController;
use App\Http\Controllers\Api\ReporteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema de Asistencia
|--------------------------------------------------------------------------
*/

// ── DOCENTE AUTH & ENDPOINTS ──────────────────────────────────────────────
Route::prefix('docente')->group(function () {
    Route::post('login',  [DocenteAuthController::class, 'login']);

    Route::get('horarios/dia', [HorarioController::class, 'materiasDelDia']);
    Route::get('horarios/clases-pendientes-pasadas', [HorarioController::class, 'clasesPendientesPasadas']);
    Route::post('horarios/omitir-clase', [HorarioController::class, 'omitirClase']);
    Route::get('horarios/{horario}/nomina', [AsistenciaController::class, 'nomina']);
    Route::post('asistencia',         [AsistenciaController::class, 'registrar']);
    Route::post('asistencia/masivo',  [AsistenciaController::class, 'registrarMasivo']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [DocenteAuthController::class, 'logout']);
        Route::get('me',      [DocenteAuthController::class, 'me']);
    });
});

// ── ADMIN (DIRECCIÓN DE CARRERA) AUTH & ENDPOINTS ────────────────────────
Route::prefix('admin')->group(function () {
    Route::post('login',  [AdminAuthController::class, 'login']);

    // Endpoints administrativos
    Route::get('dashboard', [ReporteController::class, 'dashboard']);
    Route::get('reportes/docentes-sin-marcar', [ReporteController::class, 'docentesSinMarcar']);
    Route::get('reportes/estudiantes-faltas',  [ReporteController::class, 'estudiantesConFaltas']);
    Route::get('reportes/por-fechas',          [ReporteController::class, 'reportePorFechas']);
    Route::get('reportes/horarios-carrera',    [ReporteController::class, 'horariosPorCarrera']);
    Route::get('reportes/historial-estudiante', [ReporteController::class, 'historialEstudiante']);
    Route::get('materias',                     [App\Http\Controllers\Api\MateriaController::class, 'index']);
    Route::post('importar-excel',              [App\Http\Controllers\Api\ExcelImportController::class, 'importar']);
    Route::get('descargar-plantilla',          [App\Http\Controllers\Api\ExcelImportController::class, 'descargarPlantilla']);
    Route::post('importar-estudiantes',        [App\Http\Controllers\Api\EstudianteImportController::class, 'importar']);
    Route::get('descargar-plantilla-estudiantes', [App\Http\Controllers\Api\EstudianteImportController::class, 'descargarPlantilla']);

    // Edición de asistencias por administrador
    Route::get('asistencias', [App\Http\Controllers\Api\AsistenciaAdminController::class, 'index']);
    Route::put('asistencias/{id}', [App\Http\Controllers\Api\AsistenciaAdminController::class, 'update']);
    Route::post('asistencias/crear-o-editar', [App\Http\Controllers\Api\AsistenciaAdminController::class, 'storeOrUpdate']);

    // Administración de usuarios directores y superadmins
    Route::get('administradores', [App\Http\Controllers\Api\AdminManagementController::class, 'index']);
    Route::post('administradores', [App\Http\Controllers\Api\AdminManagementController::class, 'store']);
    Route::put('administradores/{id}', [App\Http\Controllers\Api\AdminManagementController::class, 'update']);
    Route::delete('administradores/{id}', [App\Http\Controllers\Api\AdminManagementController::class, 'destroy']);

    // Inscripción manual de estudiantes
    Route::post('estudiantes/{id}/inscribir', [App\Http\Controllers\Api\EstudianteController::class, 'inscribir']);
    Route::delete('estudiantes/{id}/desinscribir/{materiaId}', [App\Http\Controllers\Api\EstudianteController::class, 'desinscribir']);

    // Catálogos (CRUD)
    Route::post('carreras/importar',            [App\Http\Controllers\Api\CarreraController::class, 'importar']);
    Route::get('carreras/descargar-plantilla',  [App\Http\Controllers\Api\CarreraController::class, 'descargarPlantilla']);
    Route::apiResource('carreras',   App\Http\Controllers\Api\CarreraController::class);
    Route::apiResource('periodos',   App\Http\Controllers\Api\PeriodoController::class);
    Route::apiResource('docentes',   App\Http\Controllers\Api\DocenteController::class);
    Route::apiResource('estudiantes',App\Http\Controllers\Api\EstudianteController::class);
    Route::apiResource('horarios',   App\Http\Controllers\Api\HorarioAdminController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('me',      [AdminAuthController::class, 'me']);
    });
});
