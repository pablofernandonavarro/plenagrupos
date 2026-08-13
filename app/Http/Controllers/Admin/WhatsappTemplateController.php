<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;

class WhatsappTemplateController extends Controller
{
    private const KEYS = ['appointment_booked', 'appointment_reminder', 'appointment_cancelled'];

    public function index()
    {
        $templates = WhatsappTemplate::all()->keyBy('key');

        return view('admin.whatsapp.templates.index', compact('templates'));
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'body' => 'required|array',
            'body.*' => 'required|string|max:1000',
            'active' => 'array',
        ]);

        foreach (self::KEYS as $key) {
            if (! isset($data['body'][$key])) {
                continue;
            }

            WhatsappTemplate::updateOrCreate(
                ['key' => $key],
                [
                    'body' => $data['body'][$key],
                    'active' => $request->boolean("active.{$key}"),
                ]
            );
        }

        return back()->with('success', 'Plantillas de WhatsApp guardadas.');
    }
}
