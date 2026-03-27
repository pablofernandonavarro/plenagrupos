<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserImportController extends Controller
{
    private const HEADERS = [
        'email',
        'nombre',
        'telefono',
        'plan',
        'fase_actual',
        'fecha inicio del plan',
        'peso_ideal',
        'peso_piso',
        'peso_techo',
        'rol',
    ];

    private const VALID_PLANS = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];

    public function show()
    {
        return view('admin.users.import');
    }

    public function template()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Write headers
        foreach (self::HEADERS as $i => $header) {
            $col = $this->col($i);
            $sheet->getCell($col.'1')->setValue($header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Force telefono column as text (prevent scientific notation)
        $telefonoCol = $this->col(array_search('telefono', self::HEADERS));
        $sheet->getStyle("{$telefonoCol}1:{$telefonoCol}1000")
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        // Style header row
        $lastCol = $this->col(count(self::HEADERS) - 1);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '09CDA6']],
            'alignment' => ['horizontal' => 'center'],
        ]);

        // Fill with existing patients
        $patients = User::where('role', 'patient')->orderBy('name')->get();
        $row = 2;
        foreach ($patients as $p) {
            $phone = $p->phone ? preg_replace('/\D/', '', $p->phone) : '';

            $sheet->getCell('A'.$row)->setValue($p->email);
            $sheet->getCell('B'.$row)->setValue($p->name);

            // Phone as explicit text to prevent scientific notation
            $sheet->getCell('C'.$row)
                ->setDataType(DataType::TYPE_STRING)
                ->setValue($phone);
            $sheet->getStyle('C'.$row)->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_TEXT);

            $sheet->getCell('D'.$row)->setValue($p->plan ?? '');
            $sheet->getCell('E'.$row)->setValue($p->fase_actual ?? '');
            $sheet->getCell('F'.$row)->setValue(
                $p->plan_start_date ? $p->plan_start_date->format('d/m/Y') : ''
            );
            $sheet->getCell('G'.$row)->setValue($p->ideal_weight ?? '');
            $sheet->getCell('H'.$row)->setValue($p->peso_piso ?? '');
            $sheet->getCell('I'.$row)->setValue($p->peso_techo ?? '');
            $sheet->getCell('J'.$row)->setValue('patient');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'usuarios_plena.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $path = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true); // assoc by column letter

        // Map header name (lowercase trimmed) → column letter
        $headers = array_map(fn ($v) => strtolower(trim((string) $v)), $rows[1] ?? []);
        $colMap = array_flip($headers);

        $created = 0;
        $updated = 0;
        $errors = [];

        for ($i = 2; $i <= $sheet->getHighestRow(); $i++) {
            $row = $rows[$i] ?? [];

            $get = function (string ...$keys) use ($row, $colMap): ?string {
                foreach ($keys as $key) {
                    $col = $colMap[$key] ?? null;
                    if ($col) {
                        $val = trim((string) ($row[$col] ?? ''));
                        if ($val !== '') {
                            return $val;
                        }
                    }
                }

                return null;
            };

            $email = $get('email');
            if (! $email) {
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Fila {$i}: email inválido ({$email})";

                continue;
            }

            $name = $get('nombre', 'name') ?? $email;
            $phone = $get('telefono', 'phone');
            $plan = $get('plan');
            $faseActual = $get('fase_actual', 'fase');
            $planStartRaw = $get('fecha inicio del plan', 'fecha_inicio', 'inicio_plan');
            $idealWeight = $get('peso_ideal');
            $pesoPiso = $get('peso_piso');
            $pesoTecho = $get('peso_techo');
            $role = $get('rol', 'role') ?? 'patient';
            $role = in_array($role, ['patient', 'coordinator']) ? $role : 'patient';

            // Validate plan
            if ($plan && ! in_array($plan, self::VALID_PLANS)) {
                $errors[] = "Fila {$i}: plan inválido ({$plan}) — debe ser descenso, mantenimiento o mantenimiento_pleno";
                $plan = null;
            }

            // Validate fase_actual
            if ($faseActual && ! in_array($faseActual, self::VALID_PLANS)) {
                $errors[] = "Fila {$i}: fase_actual inválida ({$faseActual}) — debe ser descenso, mantenimiento o mantenimiento_pleno";
                $faseActual = null;
            }

            // Parse date — try dd/mm/yyyy first, then fallback to Carbon::parse
            $planStart = null;
            if ($planStartRaw) {
                try {
                    $planStart = Carbon::createFromFormat('d/m/Y', $planStartRaw)
                        ?->format('Y-m-d')
                        ?? Carbon::parse($planStartRaw)->format('Y-m-d');
                } catch (\Exception $e) {
                    $errors[] = "Fila {$i}: fecha de inicio inválida ({$planStartRaw}) — usá el formato dd/mm/aaaa";
                }
            }

            $user = User::where('email', $email)->first();

            if ($user) {
                $user->name = $name;
                $user->phone = $phone;
                if ($user->isPatient()) {
                    if ($plan !== null) {
                        $user->plan = $plan;
                    }
                    if ($faseActual !== null) {
                        $user->fase_actual = $faseActual ?: null;
                    }
                    if ($planStart !== null) {
                        $user->plan_start_date = $planStart;
                    }
                    if ($idealWeight !== null) {
                        $user->ideal_weight = (float) $idealWeight;
                    }
                    if ($pesoPiso !== null) {
                        $user->peso_piso = (float) $pesoPiso;
                    }
                    if ($pesoTecho !== null) {
                        $user->peso_techo = (float) $pesoTecho;
                    }
                }
                $user->save();
                $updated++;
            } else {
                User::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'role' => $role,
                    'plan' => $role === 'patient' ? $plan : null,
                    'fase_actual' => $role === 'patient' ? $faseActual : null,
                    'plan_start_date' => $role === 'patient' ? $planStart : null,
                    'ideal_weight' => $idealWeight ? (float) $idealWeight : null,
                    'peso_piso' => $pesoPiso ? (float) $pesoPiso : null,
                    'peso_techo' => $pesoTecho ? (float) $pesoTecho : null,
                    'patient_status' => $role === 'patient' ? 'active' : null,
                    'password' => Hash::make(Str::random(16)),
                ]);
                $created++;
            }
        }

        return back()
            ->with('import_done', true)
            ->with('import_created', $created)
            ->with('import_updated', $updated)
            ->with('import_errors', $errors ?: []);
    }

    /** Convert 0-based index to Excel column letter (A, B, ..., Z, AA, ...) */
    private function col(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
