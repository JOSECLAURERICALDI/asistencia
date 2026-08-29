<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AsistenciaSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. CARRERA ──────────────────────────────────────────────────
        $carrera = Carrera::firstOrCreate(
            ['sigla' => 'CARSIS'],
            [
                'nombre'      => 'Ingeniería de Sistemas',
                'descripcion' => 'Carrera de Ingeniería de Sistemas',
            ]
        );

        // ── 2. PERIODO ACADÉMICO ─────────────────────────────────────────
        $periodo = PeriodoAcademico::firstOrCreate(
            ['nombre' => '2026-I'],
            [
                'fecha_inicio' => '2026-03-01',
                'fecha_fin'    => '2026-07-31',
                'activo'       => true,
            ]
        );

        // ── 3. ADMINS ─────────────────────────────────────────────────────
        Admin::firstOrCreate(
            ['email' => 'director@carsis.edu.bo'],
            [
                'nombre'      => 'Director de Carrera',
                'password'    => Hash::make('director123'),
                'carrera_id'  => $carrera->id,
                'super_admin' => false,
            ]
        );

        Admin::firstOrCreate(
            ['email' => 'admin@sistema.edu.bo'],
            [
                'nombre'      => 'Super Administrador',
                'password'    => Hash::make('admin123'),
                'carrera_id'  => null,
                'super_admin' => true,
            ]
        );

        // ── 4. DOCENTES (de la planilla de materias) ─────────────────────
        $docentes = [
            ['ci' => '3456789',  'nombre' => 'JUAN CARLOS',  'apellido' => 'MAMANI QUISPE',   'password' => Hash::make('12345678')],
            ['ci' => '4567890',  'nombre' => 'MARIA',        'apellido' => 'FLORES GUTIERREZ','password' => Hash::make('12345678')],
            ['ci' => '5678901',  'nombre' => 'PEDRO',        'apellido' => 'CONDORI LIMA',    'password' => Hash::make('12345678')],
            ['ci' => '6789012',  'nombre' => 'ANA',          'apellido' => 'RODRIGUEZ VELA',  'password' => Hash::make('12345678')],
            ['ci' => '7890123',  'nombre' => 'CARLOS',       'apellido' => 'TICONA MAMANI',   'password' => Hash::make('12345678')],
            ['ci' => '2468013',  'nombre' => 'MARIO',        'apellido' => 'ANANIAS FLORES',  'password' => Hash::make('12345678')],
            ['ci' => '3579124',  'nombre' => 'FELIX',        'apellido' => 'RIVERA ROJAS',    'password' => Hash::make('12345678')],
            ['ci' => '4680235',  'nombre' => 'ELENA',        'apellido' => 'VARGAS TERRAZAS', 'password' => Hash::make('12345678')],
            ['ci' => '8901234',  'nombre' => 'PATRICIA',     'apellido' => 'CLAROS MONTAÑO',  'password' => Hash::make('12345678')],
        ];

        $docenteModels = [];
        foreach ($docentes as $d) {
            $docenteModels[$d['ci']] = Docente::firstOrCreate(
                ['ci' => $d['ci']],
                [
                    'nombre'   => $d['nombre'],
                    'apellido' => $d['apellido'],
                    'password' => $d['password'],
                ]
            );
        }

        // ── 5. MATERIAS ───────────────────────────────────────────────────
        $materias = [
            ['codigo' => 'FIS-100', 'nombre' => 'Física Básica I',             'horas_teoricas' => 4, 'horas_practicas' => 2],
            ['codigo' => 'MAT-100', 'nombre' => 'Álgebra I',                   'horas_teoricas' => 4, 'horas_practicas' => 2],
            ['codigo' => 'QMC-100', 'nombre' => 'Química General',             'horas_teoricas' => 4, 'horas_practicas' => 2],
            ['codigo' => 'SIS-111', 'nombre' => 'Cálculo 1',                   'horas_teoricas' => 4, 'horas_practicas' => 2],
            ['codigo' => 'SIS-112', 'nombre' => 'Programación I',              'horas_teoricas' => 3, 'horas_practicas' => 3],
            ['codigo' => 'SIS-213', 'nombre' => 'Estructura de Datos',         'horas_teoricas' => 4, 'horas_practicas' => 2],
            ['codigo' => 'SIS-314', 'nombre' => 'Base de Datos I',             'horas_teoricas' => 3, 'horas_practicas' => 3],
            ['codigo' => 'SIS-415', 'nombre' => 'Redes de Computadoras',       'horas_teoricas' => 4, 'horas_practicas' => 2],
            ['codigo' => 'SIS-516', 'nombre' => 'Ingeniería de Software I',    'horas_teoricas' => 4, 'horas_practicas' => 2],
            ['codigo' => 'LIN-101', 'nombre' => 'Inglés Técnico I',            'horas_teoricas' => 4, 'horas_practicas' => 0],
            ['codigo' => 'MAT-202', 'nombre' => 'Cálculo II',                  'horas_teoricas' => 4, 'horas_practicas' => 2],
            ['codigo' => 'SIS-221', 'nombre' => 'Arquitectura de Computadoras','horas_teoricas' => 4, 'horas_practicas' => 2],
            ['codigo' => 'SIS-322', 'nombre' => 'Sistemas Operativos',         'horas_teoricas' => 3, 'horas_practicas' => 3],
            ['codigo' => 'SIS-423', 'nombre' => 'Inteligencia Artificial',     'horas_teoricas' => 4, 'horas_practicas' => 2],
            ['codigo' => 'SIS-524', 'nombre' => 'Seguridad Informática',       'horas_teoricas' => 4, 'horas_practicas' => 2],
        ];

        $materiaModels = [];
        foreach ($materias as $m) {
            $materiaModels[$m['codigo']] = Materia::firstOrCreate(
                ['codigo' => $m['codigo']],
                [
                    'nombre'               => $m['nombre'],
                    'carrera_id'           => $carrera->id,
                    'periodo_academico_id' => $periodo->id,
                    'horas_teoricas'       => $m['horas_teoricas'],
                    'horas_practicas'      => $m['horas_practicas'],
                ]
            );
        }

        // ── 6. HORARIOS ───────────────────────────────────────────────────
        $horarios = [
            // FIS-100 Física Básica I
            ['materia' => 'FIS-100', 'docente' => '2468013', 'dia' => 'lunes',     'inicio' => '07:30', 'fin' => '09:45', 'aula' => 'LAB-2',    'tipo' => 'teorica'],
            ['materia' => 'FIS-100', 'docente' => '2468013', 'dia' => 'miercoles', 'inicio' => '07:30', 'fin' => '09:45', 'aula' => 'LAB-2',    'tipo' => 'practica'],
            // MAT-100 Álgebra I
            ['materia' => 'MAT-100', 'docente' => '3579124', 'dia' => 'martes',    'inicio' => '07:30', 'fin' => '09:45', 'aula' => 'AULA-101', 'tipo' => 'teorica'],
            ['materia' => 'MAT-100', 'docente' => '3579124', 'dia' => 'jueves',    'inicio' => '07:30', 'fin' => '09:45', 'aula' => 'AULA-101', 'tipo' => 'practica'],
            // QMC-100 Química General
            ['materia' => 'QMC-100', 'docente' => '4680235', 'dia' => 'lunes',     'inicio' => '10:00', 'fin' => '12:15', 'aula' => 'AULA-102', 'tipo' => 'teorica'],
            ['materia' => 'QMC-100', 'docente' => '4680235', 'dia' => 'miercoles', 'inicio' => '10:00', 'fin' => '12:15', 'aula' => 'LAB-3',    'tipo' => 'practica'],
            // SIS-111 Cálculo 1
            ['materia' => 'SIS-111', 'docente' => '3456789', 'dia' => 'lunes',     'inicio' => '07:00', 'fin' => '09:00', 'aula' => 'AULA-101', 'tipo' => 'teorica'],
            ['materia' => 'SIS-111', 'docente' => '3456789', 'dia' => 'miercoles', 'inicio' => '07:00', 'fin' => '09:00', 'aula' => 'AULA-101', 'tipo' => 'teorica'],
            ['materia' => 'SIS-111', 'docente' => '3456789', 'dia' => 'viernes',   'inicio' => '07:00', 'fin' => '09:00', 'aula' => 'LAB-1',    'tipo' => 'practica'],
            // SIS-112 Programación I
            ['materia' => 'SIS-112', 'docente' => '4567890', 'dia' => 'lunes',     'inicio' => '09:00', 'fin' => '11:00', 'aula' => 'LAB-2',    'tipo' => 'practica'],
            ['materia' => 'SIS-112', 'docente' => '4567890', 'dia' => 'jueves',    'inicio' => '09:00', 'fin' => '11:00', 'aula' => 'AULA-102', 'tipo' => 'teorica'],
            // SIS-213 Estructura de Datos
            ['materia' => 'SIS-213', 'docente' => '5678901', 'dia' => 'martes',    'inicio' => '11:00', 'fin' => '13:00', 'aula' => 'AULA-201', 'tipo' => 'teorica'],
            ['materia' => 'SIS-213', 'docente' => '5678901', 'dia' => 'viernes',   'inicio' => '11:00', 'fin' => '13:00', 'aula' => 'LAB-1',    'tipo' => 'practica'],
            // SIS-314 Base de Datos I
            ['materia' => 'SIS-314', 'docente' => '6789012', 'dia' => 'lunes',     'inicio' => '14:00', 'fin' => '16:00', 'aula' => 'AULA-301', 'tipo' => 'teorica'],
            ['materia' => 'SIS-314', 'docente' => '6789012', 'dia' => 'miercoles', 'inicio' => '14:00', 'fin' => '16:00', 'aula' => 'LAB-3',    'tipo' => 'practica'],
            // SIS-415 Redes de Computadoras
            ['materia' => 'SIS-415', 'docente' => '7890123', 'dia' => 'martes',    'inicio' => '16:00', 'fin' => '18:00', 'aula' => 'AULA-401', 'tipo' => 'teorica'],
            ['materia' => 'SIS-415', 'docente' => '7890123', 'dia' => 'jueves',    'inicio' => '16:00', 'fin' => '18:00', 'aula' => 'LAB-4',    'tipo' => 'practica'],
            // SIS-516 Ingeniería de Software I
            ['materia' => 'SIS-516', 'docente' => '3456789', 'dia' => 'jueves',    'inicio' => '07:00', 'fin' => '09:00', 'aula' => 'AULA-501', 'tipo' => 'teorica'],
            ['materia' => 'SIS-516', 'docente' => '3456789', 'dia' => 'viernes',   'inicio' => '14:00', 'fin' => '16:00', 'aula' => 'LAB-2',    'tipo' => 'practica'],
            // LIN-101 Inglés Técnico I
            ['materia' => 'LIN-101', 'docente' => '8901234', 'dia' => 'sabado',    'inicio' => '08:00', 'fin' => '12:00', 'aula' => 'AULA-103', 'tipo' => 'teorica'],
            // MAT-202 Cálculo II
            ['materia' => 'MAT-202', 'docente' => '3579124', 'dia' => 'lunes',     'inicio' => '11:00', 'fin' => '13:00', 'aula' => 'AULA-202', 'tipo' => 'teorica'],
            ['materia' => 'MAT-202', 'docente' => '3579124', 'dia' => 'miercoles', 'inicio' => '11:00', 'fin' => '13:00', 'aula' => 'AULA-202', 'tipo' => 'practica'],
            // SIS-221 Arquitectura de Computadoras
            ['materia' => 'SIS-221', 'docente' => '2468013', 'dia' => 'martes',    'inicio' => '09:00', 'fin' => '11:00', 'aula' => 'AULA-203', 'tipo' => 'teorica'],
            ['materia' => 'SIS-221', 'docente' => '2468013', 'dia' => 'jueves',    'inicio' => '11:00', 'fin' => '13:00', 'aula' => 'LAB-1',    'tipo' => 'practica'],
            // SIS-322 Sistemas Operativos
            ['materia' => 'SIS-322', 'docente' => '4567890', 'dia' => 'lunes',     'inicio' => '16:00', 'fin' => '18:00', 'aula' => 'AULA-302', 'tipo' => 'teorica'],
            ['materia' => 'SIS-322', 'docente' => '4567890', 'dia' => 'miercoles', 'inicio' => '16:00', 'fin' => '18:00', 'aula' => 'LAB-3',    'tipo' => 'practica'],
            // SIS-423 Inteligencia Artificial
            ['materia' => 'SIS-423', 'docente' => '5678901', 'dia' => 'martes',    'inicio' => '14:00', 'fin' => '16:00', 'aula' => 'AULA-402', 'tipo' => 'teorica'],
            ['materia' => 'SIS-423', 'docente' => '5678901', 'dia' => 'jueves',    'inicio' => '14:00', 'fin' => '16:00', 'aula' => 'LAB-4',    'tipo' => 'practica'],
            // SIS-524 Seguridad Informática
            ['materia' => 'SIS-524', 'docente' => '6789012', 'dia' => 'viernes',   'inicio' => '16:00', 'fin' => '18:00', 'aula' => 'AULA-502', 'tipo' => 'teorica'],
            ['materia' => 'SIS-524', 'docente' => '6789012', 'dia' => 'sabado',    'inicio' => '14:00', 'fin' => '16:00', 'aula' => 'LAB-1',    'tipo' => 'practica'],
        ];

        foreach ($horarios as $h) {
            Horario::firstOrCreate([
                'materia_id'  => $materiaModels[$h['materia']]->id,
                'docente_id'  => $docenteModels[$h['docente']]->id,
                'dia_semana'  => $h['dia'],
                'hora_inicio' => $h['inicio'],
                'hora_fin'    => $h['fin'],
                'tipo'        => $h['tipo'],
            ], [
                'aula'        => $h['aula'],
            ]);
        }

        // ── 7. ESTUDIANTES NÓMINA ─────────────────────────────────────────
        $estudiantesData = [
            ['carnet' => '1113004', 'primer_apellido' => 'CRUZ',    'segundo_apellido' => 'TICONA',   'nombres' => 'PAOLA ANDREA'],
            ['carnet' => '1112944', 'primer_apellido' => 'NINA',    'segundo_apellido' => 'EUGENIO',  'nombres' => 'JHON JAIRO'],
            ['carnet' => '1113072', 'primer_apellido' => 'NOGALES', 'segundo_apellido' => 'ROJAS',    'nombres' => 'MATEO ALBERTO'],
            ['carnet' => '1112915', 'primer_apellido' => 'NOLASCO', 'segundo_apellido' => 'ESCOBAR',  'nombres' => 'JESUS'],
            ['carnet' => '1112475', 'primer_apellido' => 'SAAVEDRA','segundo_apellido' => 'ARNEZ',    'nombres' => 'DIEGO JAVIER'],
            ['carnet' => '1112983', 'primer_apellido' => 'TAPIA',   'segundo_apellido' => 'CALIZAYA', 'nombres' => 'MADISON NURIA'],
            ['carnet' => '1113100', 'primer_apellido' => 'QUISPE',  'segundo_apellido' => 'MAMANI',   'nombres' => 'LUIS ANTONIO'],
            ['carnet' => '1113101', 'primer_apellido' => 'CHOQUE',  'segundo_apellido' => 'APAZA',    'nombres' => 'MARIA FERNANDA'],
            ['carnet' => '1113102', 'primer_apellido' => 'CONDORI', 'segundo_apellido' => 'HUANCA',   'nombres' => 'EDGAR ROLANDO'],
            ['carnet' => '1113103', 'primer_apellido' => 'LAURA',   'segundo_apellido' => 'PONCE',    'nombres' => 'RODRIGO'],
        ];

        $estudianteModels = [];
        foreach ($estudiantesData as $e) {
            $estudianteModels[$e['carnet']] = Estudiante::firstOrCreate(
                ['carnet' => $e['carnet']],
                [
                    'primer_apellido'  => $e['primer_apellido'],
                    'segundo_apellido' => $e['segundo_apellido'],
                    'nombres'          => $e['nombres'],
                    'carrera_id'       => $carrera->id,
                ]
            );
        }

        // ── 8. INSCRIPCIONES (Inscribir estudiantes en todas las materias) ─
        foreach ($materiaModels as $codigo => $matObj) {
            foreach ($estudianteModels as $estObj) {
                Inscripcion::firstOrCreate([
                    'estudiante_id'        => $estObj->id,
                    'materia_id'           => $matObj->id,
                    'periodo_academico_id' => $periodo->id,
                ]);
            }
        }
    }
}
