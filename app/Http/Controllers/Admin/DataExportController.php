<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupAttendance;
use App\Models\InbodyRecord;
use App\Models\WeightRecord;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    private function csvResponse(callable $writer, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            $writer($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function attendances(): StreamedResponse
    {
        return $this->csvResponse(function ($handle) {
            fputcsv($handle, [
                'id',
                'group_id',
                'group_name',
                'user_id',
                'patient_name',
                'patient_email',
                'patient_status',
                'attended_at',
                'created_at',
            ]);

            foreach (GroupAttendance::query()
                ->with(['group:id,name', 'user:id,name,email'])
                ->orderBy('attended_at', 'desc')
                ->cursor() as $a) {
                fputcsv($handle, [
                    $a->id,
                    $a->group_id,
                    $a->group?->name,
                    $a->user_id,
                    $a->user?->name,
                    $a->user?->email,
                    $a->user?->patient_status,
                    $a->attended_at?->format('Y-m-d H:i:s'),
                    $a->created_at?->format('Y-m-d H:i:s'),
                ]);
            }
        }, 'asistencias_'.now()->format('Y-m-d_His').'.csv');
    }

    public function weights(): StreamedResponse
    {
        return $this->csvResponse(function ($handle) {
            fputcsv($handle, [
                'id',
                'user_id',
                'patient_name',
                'patient_email',
                'patient_status',
                'group_id',
                'group_name',
                'attendance_id',
                'weight',
                'notes',
                'recorded_at',
                'created_at',
            ]);

            foreach (WeightRecord::query()
                ->with(['group:id,name', 'user:id,name,email'])
                ->orderBy('recorded_at', 'desc')
                ->cursor() as $w) {
                fputcsv($handle, [
                    $w->id,
                    $w->user_id,
                    $w->user?->name,
                    $w->user?->email,
                    $w->user?->patient_status,
                    $w->group_id,
                    $w->group?->name,
                    $w->attendance_id,
                    $w->weight,
                    $w->notes,
                    $w->recorded_at?->format('Y-m-d H:i:s'),
                    $w->created_at?->format('Y-m-d H:i:s'),
                ]);
            }
        }, 'pesos_'.now()->format('Y-m-d_His').'.csv');
    }

    public function inbody(): StreamedResponse
    {
        return $this->csvResponse(function ($handle) {
            fputcsv($handle, [
                'id',
                'user_id',
                'patient_name',
                'patient_email',
                'patient_status',
                'test_date',
                'weight',
                'skeletal_muscle_mass',
                'body_fat_mass',
                'body_fat_percentage',
                'bmi',
                'basal_metabolic_rate',
                'visceral_fat_level',
                'total_body_water',
                'proteins',
                'minerals',
                'inbody_score',
                'obesity_degree',
                'notes',
                'created_at',
            ]);

            foreach (InbodyRecord::query()
                ->with(['patient:id,name,email'])
                ->orderBy('test_date', 'desc')
                ->cursor() as $r) {
                fputcsv($handle, [
                    $r->id,
                    $r->user_id,
                    $r->patient?->name,
                    $r->patient?->email,
                    $r->patient?->patient_status,
                    $r->test_date?->format('Y-m-d'),
                    $r->weight,
                    $r->skeletal_muscle_mass,
                    $r->body_fat_mass,
                    $r->body_fat_percentage,
                    $r->bmi,
                    $r->basal_metabolic_rate,
                    $r->visceral_fat_level,
                    $r->total_body_water,
                    $r->proteins,
                    $r->minerals,
                    $r->inbody_score,
                    $r->obesity_degree,
                    $r->notes,
                    $r->created_at?->format('Y-m-d H:i:s'),
                ]);
            }
        }, 'inbody_'.now()->format('Y-m-d_His').'.csv');
    }

    /** Una fila por cada paciente en cada grupo (canal, UTM, dispositivo al primer QR). */
    public function groupPatients(): StreamedResponse
    {
        return $this->csvResponse(function ($handle) {
            fputcsv($handle, [
                'group_id',
                'group_name',
                'user_id',
                'patient_name',
                'patient_email',
                'patient_status',
                'joined_at',
                'join_source',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_content',
                'first_device_user_agent',
            ]);

            foreach (DB::table('group_patient')
                ->join('groups', 'groups.id', '=', 'group_patient.group_id')
                ->join('users', 'users.id', '=', 'group_patient.user_id')
                ->orderBy('groups.name')
                ->orderBy('users.name')
                ->select([
                    'group_patient.group_id',
                    'groups.name as group_name',
                    'group_patient.user_id',
                    'users.name as patient_name',
                    'users.email as patient_email',
                    'users.patient_status',
                    'group_patient.joined_at',
                    'group_patient.join_source',
                    'group_patient.utm_source',
                    'group_patient.utm_medium',
                    'group_patient.utm_campaign',
                    'group_patient.utm_content',
                    'group_patient.first_device_user_agent',
                ])
                ->cursor() as $row) {
                fputcsv($handle, [
                    $row->group_id,
                    $row->group_name,
                    $row->user_id,
                    $row->patient_name,
                    $row->patient_email,
                    $row->patient_status,
                    $row->joined_at,
                    $row->join_source,
                    $row->utm_source,
                    $row->utm_medium,
                    $row->utm_campaign,
                    $row->utm_content,
                    $row->first_device_user_agent,
                ]);
            }
        }, 'pacientes_por_grupo_'.now()->format('Y-m-d_His').'.csv');
    }
}
