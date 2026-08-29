<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CarreraController extends Controller
{
    public function index()
    {
        return Carrera::orderBy('nombre')->orderBy('sede')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:255',
            'sigla'       => 'nullable|string|max:50',
            'sede'        => 'nullable|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        if (empty($data['sede'])) {
            $data['sede'] = 'Cochabamba';
        }

        return Carrera::create($data);
    }

    public function show(Carrera $carrera)
    {
        return $carrera;
    }

    public function update(Request $request, Carrera $carrera)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:255',
            'sigla'       => 'nullable|string|max:50',
            'sede'        => 'nullable|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        if (empty($data['sede'])) {
            $data['sede'] = 'Cochabamba';
        }

        $carrera->update($data);
        return $carrera;
    }

    public function destroy(Carrera $carrera)
    {
        $carrera->delete();
        return response()->noContent();
    }

    /**
     * Importación masiva por lote desde archivo Excel/CSV.
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

        $headerRow = array_shift($rows);
        $headerMap = $this->mapHeaders($headerRow);

        $creadas = 0;
        $actualizadas = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $nombre      = trim($this->getVal($row, $headerMap, ['carrera', 'nombredecarrera', 'nombrecarrera', 'nombre', 'a']));
                $sigla       = trim($this->getVal($row, $headerMap, ['sigla', 'codigo', 'codigocarrera', 'b']));
                $sede        = trim($this->getVal($row, $headerMap, ['sede', 'ciudad', 'campus', 'c']));
                $descripcion = trim($this->getVal($row, $headerMap, ['descripcion', 'detalle', 'observacion', 'd']));

                if (empty($nombre)) {
                    continue;
                }

                if (empty($sede)) {
                    $sede = 'Cochabamba';
                }

                if (empty($sigla)) {
                    $sigla = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $nombre), 0, 6));
                }

                $carrera = Carrera::where('nombre', $nombre)
                    ->where('sede', $sede)
                    ->first();

                if (!$carrera) {
                    $carrera = Carrera::where('sigla', $sigla)
                        ->where('sede', $sede)
                        ->first();
                }

                if ($carrera) {
                    $carrera->update([
                        'nombre'      => $nombre,
                        'sigla'       => $sigla,
                        'descripcion' => $descripcion ?: $carrera->descripcion,
                    ]);
                    $actualizadas++;
                } else {
                    Carrera::create([
                        'nombre'      => $nombre,
                        'sigla'       => $sigla,
                        'sede'        => $sede,
                        'descripcion' => $descripcion ?: "Carrera de {$nombre} - Sede {$sede}",
                    ]);
                    $creadas++;
                }
            }

            DB::commit();

            return response()->json([
                'message'      => '✅ Importación de carreras por lote completada exitosamente.',
                'creadas'      => $creadas,
                'actualizadas' => $actualizadas,
                'total'        => $creadas + $actualizadas,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error durante la importación de carreras.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera y descarga la plantilla de importación de Carreras en CSV.
     */
    public function descargarPlantilla()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Plantilla_Importacion_Carreras.csv"',
        ];

        $csvData = "\xEF\xBB\xBF"; // UTF-8 BOM para Excel
        $csvData .= "NOMBRE DE CARRERA,SIGLA,SEDE,DESCRIPCION\n";
        $csvData .= "Ingeniería de Sistemas,CARSIS-CB,Cochabamba,Carrera de Ingeniería de Sistemas Sede Cochabamba\n";
        $csvData .= "Medicina,MED-IV,Ivirgarzama,Carrera de Medicina Sede Ivirgarzama\n";
        $csvData .= "Odontología,ODO-SC,Santa Cruz,Carrera de Odontología Sede Santa Cruz\n";
        $csvData .= "Derecho,DER-PQ,Puerto Quijarro,Carrera de Derecho Sede Puerto Quijarro\n";
        $csvData .= "Enfermería,ENF-GY,Guayaramerin,Carrera de Enfermería Sede Guayaramerin\n";
        $csvData .= "Fisioterapia,FIS-CB,Cobija,Carrera de Fisioterapia Sede Cobija\n";
        $csvData .= "Bioquímica y Farmacia,BQ-LP,La Paz,Carrera de Bioquímica y Farmacia Sede La Paz\n";
        $csvData .= "Medicina,MED-EA,El Alto,Carrera de Medicina Sede El Alto\n";

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
