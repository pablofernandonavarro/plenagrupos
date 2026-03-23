<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserImportController extends Controller
{
    public function show()
    {
        return view('admin.users.import');
    }

    public function template()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['email', 'nombre', 'telefono', 'plan', 'fecha_inicio', 'peso_ideal', 'peso_piso', 'peso_techo', 'rol'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
            $sheet->getColumnDimensionByColumn($i + 1)->setAutoSize(true);
        }

        // Style header row
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '09CDA6']],
            'alignment' => ['horizontal' => 'center'],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        // Example rows
        $examples = [
            ['juan@ejemplo.com', 'Juan Pérez',   '1122334455', 'descenso',            '18/03/2026', 70.0, 68.0, 73.0, 'patient'],
            ['maria@ejemplo.com','María García', '1155667788', 'mantenimiento',        '01/03/2026', 65.0, 63.0, 67.0, 'patient'],
            ['diego@ejemplo.com','Diego Torres', '',           'mantenimiento_pleno',  '',           '',   '',   '',   'patient'],
        ];

        foreach ($examples as $r => $row) {
            foreach ($row as $c => $value) {
                $sheet->setCellValueByColumnAndRow($c + 1, $r + 2, $value);
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'modelo_importacion_usuarios.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $path        = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true); // assoc by column letter

        // First row = headers, map column letter → header name (lowercase trimmed)
        $headers = array_map(fn($v) => strtolower(trim((string) $v)), $rows[1] ?? []);
        // e.g. ['A'=>'nombre', 'B'=>'email', ...]

        $colMap  = array_flip($headers); // header→column letter
        $created = 0;
        $updated = 0;
        $errors  = [];

        $validPlans = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];

        for ($i = 2; $i <= $sheet->getHighestRow(); $i++) {
            $row = $rows[$i] ?? [];

            $get = function (string $key) use ($row, $colMap): ?string {
                $col = $colMap[$key] ?? null;
                return $col ? trim((string) ($row[$col] ?? '')) ?: null : null;
            };

            $email = $get('email');
            if (!$email) continue;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Fila {$i}: email inválido ({$email})";
                continue;
            }

            $name  = $get('nombre') ?? $get('name') ?? $email;
            $phone = $get('telefono') ?? $get('phone');
            $plan  = $get('plan');
            $planStartRaw = $get('fecha_inicio') ?? $get('inicio_plan');
            $idealWeight  = $get('peso_ideal');
            $pesoPiso     = $get('peso_piso');
            $pesoTecho    = $get('peso_techo');
            $role  = $get('rol') ?? $get('role') ?? 'patient';
            $role  = in_array($role, ['patient', 'coordinator']) ? $role : 'patient';

            if ($plan && !in_array($plan, $validPlans)) {
                $errors[] = "Fila {$i}: plan inválido ({$plan}) — debe ser descenso, mantenimiento o mantenimiento_pleno";
                $plan = null;
            }

            $planStart = null;
            if ($planStartRaw) {
                try {
                    $planStart = \Carbon\Carbon::parse($planStartRaw)->format('Y-m-d');
                } catch (\Exception $e) {
                    $errors[] = "Fila {$i}: fecha de inicio inválida ({$planStartRaw})";
                }
            }

            $user = User::where('email', $email)->first();

            if ($user) {
                $user->name  = $name;
                $user->phone = $phone;
                if ($user->isPatient()) {
                    if ($plan)       $user->plan            = $plan;
                    if ($planStart)  $user->plan_start_date = $planStart;
                    if ($idealWeight !== null) $user->ideal_weight = (float) $idealWeight;
                    if ($pesoPiso    !== null) $user->peso_piso    = (float) $pesoPiso;
                    if ($pesoTecho   !== null) $user->peso_techo   = (float) $pesoTecho;
                }
                $user->save();
                $updated++;
            } else {
                User::create([
                    'name'            => $name,
                    'email'           => $email,
                    'phone'           => $phone,
                    'role'            => $role,
                    'plan'            => $role === 'patient' ? $plan : null,
                    'plan_start_date' => $role === 'patient' ? $planStart : null,
                    'ideal_weight'    => $idealWeight ? (float) $idealWeight : null,
                    'peso_piso'       => $pesoPiso    ? (float) $pesoPiso    : null,
                    'peso_techo'      => $pesoTecho   ? (float) $pesoTecho   : null,
                    'password'        => Hash::make(Str::random(16)),
                ]);
                $created++;
            }
        }

        $message = "Importación completada: {$created} creado(s), {$updated} actualizado(s).";
        if ($errors) {
            return back()->with('import_errors', $errors)->with('success', $message);
        }

        return back()->with('success', $message);
    }
}
