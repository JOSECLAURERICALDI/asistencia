<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImportController extends Controller
{
    /**
     * Importa desde un archivo Excel/CSV las materias, docentes y horarios.
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $file = $request->file('archivo');
        $filePath = $file->getRealPath();

        try {
            $extension = strtolower($file->getClientOriginalExtension());
            if (in_array($extension, ['csv', 'txt'])) {
                $reader = IOFactory::createReader('Csv');
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($filePath);
            } else {
                $spreadsheet = IOFactory::load($filePath);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo leer el archivo Excel/CSV. Verifica que el formato sea válido.',
                'error'   => $e->getMessage(),
            ], 422);
        }

        if (count($rows) < 2) {
            return response()->json([
                'message' => 'El archivo no contiene filas de datos.',
            ], 422);
        }

        // Obtener o crear carrera y periodo académico por defecto
        if ($request->carrera_id) {
            $carrera = Carrera::find($request->carrera_id);
        }

        if (empty($carrera)) {
            $carrera = Carrera::firstOrCreate(
                ['sigla' => 'CARSIS'],
                [
                    'nombre'      => 'Ingeniería de Sistemas',
                    'descripcion' => 'Carrera de Ingeniería de Sistemas',
                ]
            );
        }

        $periodo = PeriodoAcademico::firstOrCreate(
            ['nombre' => '2-2026'],
            [
                'fecha_inicio' => '2026-08-03',
                'fecha_fin'    => '2026-12-30',
                'activo'       => true,
            ]
        );

        // Detectar cabecera (primera fila)
        $headerRow = array_shift($rows);
        $headerMap = $this->mapHeaders($headerRow);

        $docentesCreados = 0;
        $materiasCreadas = 0;
        $horariosCreados = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                // Omitir filas vacías
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $sigla = trim($this->getVal($row, $headerMap, ['sigla', 'codigo', 'materia_codigo', 'a']));
                $nombreMateria = trim($this->getVal($row, $headerMap, ['materia', 'nombre_materia', 'materia_nombre', 'b']));
                $tipo = strtolower(trim($this->getVal($row, $headerMap, ['tipo', 'grupo', 'tipo_clase', 'c'])));
                $diaRaw = strtolower(trim($this->getVal($row, $headerMap, ['dia', 'dia_semana', 'd'])));
                $horaRaw = trim($this->getVal($row, $headerMap, ['hora', 'horario', 'hora_inicio', 'e']));
                $horaFinRaw = trim($this->getVal($row, $headerMap, ['hora_fin', 'fin', 'f']));
                $aula = trim($this->getVal($row, $headerMap, ['aula', 'lab', 'ambiente', 'g']));
                $docenteNombreCompleto = trim($this->getVal($row, $headerMap, ['docente', 'nombre_docente', 'docente_nombre', 'h']));
                $docenteCi = trim($this->getVal($row, $headerMap, ['ci', 'docente_ci', 'ci_docente', 'i']));

                if (empty($sigla) && empty($nombreMateria) && empty($docenteCi)) {
                    continue; // Fila sin identificadores mínimos
                }

                // Normalizar tipo (teorica / practica)
                $tipoNorm = str_contains($tipo, 'prac') ? 'practica' : 'teorica';

                // Parsear Día y Horas
                $diaNorm = $this->parseDia($diaRaw);
                [$horaInicio, $horaFin] = $this->parseHoras($horaRaw, $horaFinRaw);

                // 1. Docente (por CI)
                if (!empty($docenteCi)) {
                    $docente = Docente::where('ci', $docenteCi)->first();
                    if (!$docente) {
                        [$nombre, $apellido] = $this->splitNombreApellido($docenteNombreCompleto);
                        $docente = Docente::create([
                            'ci'       => $docenteCi,
                            'nombre'   => $nombre ?: 'DOCENTE',
                            'apellido' => $apellido ?: 'DOCENTE',
                            'password' => Hash::make('12345678'),
                        ]);
                        $docentesCreados++;
                    }
                } else {
                    $docente = Docente::firstOrCreate(
                        ['ci' => '0000000'],
                        [
                            'nombre'   => 'SIN',
                            'apellido' => 'DOCENTE',
                            'password' => Hash::make('12345678'),
                        ]
                    );
                }

                // 2. Materia (por código/sigla)
                if (!empty($sigla)) {
                    $materia = Materia::where('codigo', $sigla)->first();
                    if (!$materia) {
                        $materia = Materia::create([
                            'codigo'               => $sigla,
                            'nombre'               => $nombreMateria ?: $sigla,
                            'carrera_id'           => $carrera->id,
                            'periodo_academico_id' => $periodo->id,
                            'horas_teoricas'       => 4,
                            'horas_practicas'      => 2,
                        ]);
                        $materiasCreadas++;
                    }
                } else {
                    continue;
                }

                // 3. Horario
                $horarioExistente = Horario::where('materia_id', $materia->id)
                    ->where('docente_id', $docente->id)
                    ->where('dia_semana', $diaNorm)
                    ->where('hora_inicio', $horaInicio)
                    ->first();

                if (!$horarioExistente) {
                    Horario::create([
                        'materia_id'  => $materia->id,
                        'docente_id'  => $docente->id,
                        'dia_semana'  => $diaNorm,
                        'hora_inicio' => $horaInicio,
                        'hora_fin'    => $horaFin,
                        'aula'        => $aula ?: 'AULA-1',
                        'tipo'        => $tipoNorm,
                    ]);
                    $horariosCreados++;
                }
            }

            DB::commit();

            return response()->json([
                'message'          => 'Importación completada con éxito.',
                'docentes_creados' => $docentesCreados,
                'materias_creadas' => $materiasCreadas,
                'horarios_creados' => $horariosCreados,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error durante la importación de datos.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mapea nombres de columna a sus claves en array
     */
    private function mapHeaders(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $colKey => $colVal) {
            if ($colVal) {
                $clean = strtolower(trim((string)$colVal));
                $clean = str_replace([' ', '_', '-'], '', $clean);
                $map[$clean] = $colKey;
            }
        }
        return $map;
    }

    private function getVal(array $row, array $map, array $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            if (isset($map[$key]) && isset($row[$map[$key]])) {
                return $row[$map[$key]];
            }
        }
        // Fallback si la clave es la letra de columna directamente
        foreach ($possibleKeys as $key) {
            $upper = strtoupper($key);
            if (isset($row[$upper])) {
                return $row[$upper];
            }
        }
        return '';
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $val) {
            if (!empty(trim((string)$val))) {
                return false;
            }
        }
        return true;
    }

    private function parseDia(string $raw): string
    {
        if (str_contains($raw, 'lun')) return 'lunes';
        if (str_contains($raw, 'mar')) return 'martes';
        if (str_contains($raw, 'mie') || str_contains($raw, 'mié')) return 'miercoles';
        if (str_contains($raw, 'jue')) return 'jueves';
        if (str_contains($raw, 'vie')) return 'viernes';
        if (str_contains($raw, 'sab') || str_contains($raw, 'sáb')) return 'sabado';
        if (str_contains($raw, 'dom')) return 'domingo';
        return 'lunes';
    }

    private function parseHoras(string $raw1, string $raw2): array
    {
        // Ej: "07:30 - 09:45" o "07:30-09:45" o separado
        if (str_contains($raw1, '-')) {
            $parts = explode('-', $raw1);
            return [
                trim($parts[0]),
                trim($parts[1] ?? '09:00')
            ];
        }

        $inicio = !empty($raw1) ? $raw1 : '07:00';
        $fin = !empty($raw2) ? $raw2 : '09:00';
        return [$inicio, $fin];
    }

    /**
     * Genera y descarga la plantilla base en formato CSV para trabajar sobre ella.
     */
    public function descargarPlantilla()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Plantilla_Importacion_Materias_Horarios.csv"',
        ];

        $csvData = "\xEF\xBB\xBF"; // UTF-8 BOM para Excel
        $csvData .= "Sigla,Materia,Tipo,Dia,Hora Inicio,Hora Fin,Aula,Nombre Docente,CI Docente\n";
        $csvData .= "SIS-111,Cálculo 1,Teórica,Lunes,07:00,09:00,A-101,JUAN CARLOS MAMANI QUISPE,3456789\n";
        $csvData .= "SIS-111,Cálculo 1,Práctica,Viernes,07:00,09:00,LAB-1,JUAN CARLOS MAMANI QUISPE,3456789\n";
        $csvData .= "FIS-100,Física Básica I,Teórica,Lunes,07:30,09:45,LAB-2,MARIO ANANIAS FLORES,2468013\n";
        $csvData .= "MAT-100,Álgebra I,Teórica,Martes,07:30,09:45,A-101,FELIX RIVERA ROJAS,3579124\n";
        $csvData .= "SIS-112,Programación I,Práctica,Jueves,09:00,11:00,LAB-2,MARIA FLORES GUTIERREZ,4567890\n";

        return response($csvData, 200, $headers);
    }

    private function splitNombreApellido(string $fullName): array
    {
        $parts = explode(' ', trim($fullName));
        if (count($parts) >= 3) {
            // Asumir APELLIDO_P APELLIDO_M NOMBRES
            $apellido = $parts[0] . ' ' . $parts[1];
            $nombre = implode(' ', array_slice($parts, 2));
            return [$nombre, $apellido];
        } elseif (count($parts) == 2) {
            return [$parts[1], $parts[0]];
        }
        return [$fullName, ''];
    }
}
