<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::orderBy('date')->get();

        return view('admin.holidays.index', compact('holidays'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date|unique:holidays,date',
            'name' => 'required|string|max:255',
        ]);

        Holiday::create($data);

        return back()->with('success', 'Feriado agregado.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return back()->with('success', 'Feriado eliminado.');
    }
}
