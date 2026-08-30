<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EstudianteImportController extends Controller
{
    /**
     * Importa desde un archivo Excel/CSV los estudiantes y sus inscripciones en materias.
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
                'message' => 'No se pudo leer el archivo Excel/CSV. Verifica el formato.',
                'error'   => $e->getMessage(),
            ], 422);
        }

        if (count($rows) < 2) {
            return response()->json([
                'message' => 'El archivo no contiene filas de datos.',
            ], 422);
        }

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

        // Cabecera (primera fila)
        $headerRow = array_shift($rows);
        $headerMap = $this->mapHeaders($headerRow);

        $estudiantesCreados = 0;
        $inscripcionesCreadas = 0;
        $materiaCodigoActual = '';

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $materiaCodigo = trim($this->getVal($row, $headerMap, ['codigomateria', 'codmateria', 'materia_codigo', 'sigla', 'materia', 'a']));
                $carnet = trim($this->getVal($row, $headerMap, ['codigoestudiante', 'codigoestudianti', 'carnet', 'ci', 'estudiante_id', 'b']));
                $nombres = trim($this->getVal($row, $headerMap, ['nombres', 'nombre', 'nombress', 'c']));
                $primerApellido = trim($this->getVal($row, $headerMap, ['1erapellido', 'primerapellido', 'apellido_paterno', 'apellidopaterno', 'd']));
                $segundoApellido = trim($this->getVal($row, $headerMap, ['2doapellido', 'segundoapellido', 'apellido_materno', 'apellidomaterno', 'e']));

                $contactoNombre = trim($this->getVal($row, $headerMap, ['contactonombre', 'nombrecontacto', 'apoderado', 'contacto']));
                $contactoParentesco = trim($this->getVal($row, $headerMap, ['contactoparentesco', 'parentesco', 'relacion']));
                $contactoTelefono = trim($this->getVal($row, $headerMap, ['contactotelefono', 'telefonocontacto', 'celularcontacto', 'telefono', 'celular']));

                // Mantener el código de materia actual en caso de filas agrupadas
                if (!empty($materiaCodigo)) {
                    $materiaCodigoActual = $materiaCodigo;
                } else {
                    $materiaCodigo = $materiaCodigoActual;
                }

                if (empty($carnet) || empty($nombres)) {
                    continue;
                }

                // 1. Crear o buscar Estudiante
                $estudiante = Estudiante::where('carnet', $carnet)->first();
                if (!$estudiante) {
                    $estudiante = Estudiante::create([
                        'carnet'              => $carnet,
                        'primer_apellido'     => $primerApellido ?: 'S/A',
                        'segundo_apellido'    => $segundoApellido ?: '',
                        'nombres'             => $nombres,
                        'carrera_id'          => $carrera->id,
                        'contacto_nombre'     => $contactoNombre ?: null,
                        'contacto_parentesco' => $contactoParentesco ?: null,
                        'contacto_telefono'   => $contactoTelefono ?: null,
                    ]);
                    $estudiantesCreados++;
                } else {
                    // Actualizar apellidos/nombres/contacto si vinieron nuevos
                    $estudiante->update([
                        'primer_apellido'     => $primerApellido ?: $estudiante->primer_apellido,
                        'segundo_apellido'    => $segundoApellido ?: $estudiante->segundo_apellido,
                        'nombres'             => $nombres ?: $estudiante->nombres,
                        'contacto_nombre'     => $contactoNombre ?: $estudiante->contacto_nombre,
                        'contacto_parentesco' => $contactoParentesco ?: $estudiante->contacto_parentesco,
                        'contacto_telefono'   => $contactoTelefono ?: $estudiante->contacto_telefono,
                    ]);
                }

                // 2. Buscar o crear Materia por Código
                if (!empty($materiaCodigo)) {
                    $materia = Materia::where('codigo', $materiaCodigo)->first();
                    if (!$materia) {
                        $materia = Materia::create([
                            'codigo'               => $materiaCodigo,
                            'nombre'               => $materiaCodigo,
                            'carrera_id'           => $carrera->id,
                            'periodo_academico_id' => $periodo->id,
                            'horas_teoricas'       => 4,
                            'horas_practicas'      => 2,
                        ]);
                    }

                    // 3. Crear Inscripción
                    $inscripcionExistente = Inscripcion::where('estudiante_id', $estudiante->id)
                        ->where('materia_id', $materia->id)
                        ->where('periodo_academico_id', $periodo->id)
                        ->first();

                    if (!$inscripcionExistente) {
                        Inscripcion::create([
                            'estudiante_id'        => $estudiante->id,
                            'materia_id'           => $materia->id,
                            'periodo_academico_id' => $periodo->id,
                        ]);
                        $inscripcionesCreadas++;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message'               => 'Importación de estudiantes e inscripciones completada con éxito.',
                'estudiantes_creados'   => $estudiantesCreados,
                'inscripciones_creadas' => $inscripcionesCreadas,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error durante la importación de estudiantes.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera la plantilla base idéntica al modelo solicitado.
     */
    public function descargarPlantilla()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Plantilla_Importacion_Estudiantes_Materias.csv"',
        ];

        $csvData = "\xEF\xBB\xBF"; // UTF-8 BOM para Excel
        $csvData .= "CODIGO MATERIA,CODIGO ESTUDIANTE,NOMBRES,1er. Apellido,2do. Apellido\n";
        $csvData .= "SIS-111,1112475,DIEGO JAVIER,SAAVEDRA,ARNEZ\n";
        $csvData .= "SIS-111,1112915,JESUS,NOLASCO,ESCOBAR\n";
        $csvData .= "SIS-111,1112944,JHON JAIRO,NINA,EUGENIO\n";
        $csvData .= "SIS-111,1112983,MADISON NURIA,TAPIA,CALIZAYA\n";
        $csvData .= "SIS-111,1113072,MATEO ALBERTO,NOGALES,ROJAS\n";
        $csvData .= "SIS-111,1113004,PAOLA ANDREA,CRUZ,TICONA\n";
        $csvData .= ",,,,\n";
        $csvData .= "SIS-112,1112475,DIEGO JAVIER,SAAVEDRA,ARNEZ\n";
        $csvData .= "SIS-112,1112915,JESUS,NOLASCO,ESCOBAR\n";
        $csvData .= "SIS-112,1112944,JHON JAIRO,NINA,EUGENIO\n";
        $csvData .= "SIS-112,1112983,MADISON NURIA,TAPIA,CALIZAYA\n";
        $csvData .= "SIS-112,1113072,MATEO ALBERTO,NOGALES,ROJAS\n";
        $csvData .= "SIS-112,1113004,PAOLA ANDREA,CRUZ,TICONA\n";
        $csvData .= ",,,,\n";
        $csvData .= "SIS-113,1112475,DIEGO JAVIER,SAAVEDRA,ARNEZ\n";
        $csvData .= "SIS-113,1112915,JESUS,NOLASCO,ESCOBAR\n";
        $csvData .= "SIS-113,1112944,JHON JAIRO,NINA,EUGENIO\n";
        $csvData .= "SIS-113,1112983,MADISON NURIA,TAPIA,CALIZAYA\n";
        $csvData .= "SIS-113,1113072,MATEO ALBERTO,NOGALES,ROJAS\n";
        $csvData .= "SIS-113,1113004,PAOLA ANDREA,CRUZ,TICONA\n";

        return response($csvData, 200, $headers);
    }

    private function mapHeaders(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $colKey => $colVal) {
            if ($colVal) {
                $clean = strtolower(trim((string)$colVal));
                $clean = str_replace([' ', '_', '-', '.', '(', ')'], '', $clean);
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
}
