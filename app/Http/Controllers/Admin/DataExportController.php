<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupAttendance;
use App\Models\InbodyRecord;
use App\Models\WeightRecord;
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
}
