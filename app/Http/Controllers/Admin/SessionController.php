<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\TherapeuticSession;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = TherapeuticSession::with('group')->latest()->paginate(15);
        return view('admin.sessions.index', compact('sessions'));
    }

    public function create()
    {
        $groups = Group::where('active', true)->get();
        return view('admin.sessions.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'name' => 'required|string|max:255',
            'session_date' => 'required|date',
        ]);

        $session = TherapeuticSession::create([
            'group_id' => $data['group_id'],
            'name' => $data['name'],
            'session_date' => $data['session_date'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.sessions.show', $session)->with('success', 'Sesión creada con código QR generado.');
    }

    public function show(TherapeuticSession $session)
    {
        $session->load(['group', 'attendances.user', 'weightRecords.user']);

        $joinUrl = route('session.join', $session->qr_token);
        $qrCode = QrCode::size(250)->generate($joinUrl);

        $avgWeight = $session->weightRecords->avg('weight');
        $weightData = $session->weightRecords->map(fn($r) => [
            'name' => $r->user->name,
            'weight' => $r->weight,
        ]);

        return view('admin.sessions.show', compact('session', 'qrCode', 'joinUrl', 'avgWeight', 'weightData'));
    }

    public function toggleStatus(TherapeuticSession $session)
    {
        $session->update([
            'status' => $session->status === 'active' ? 'closed' : 'active',
        ]);
        return back()->with('success', 'Estado de sesión actualizado.');
    }

    public function destroy(TherapeuticSession $session)
    {
        $session->delete();
        return redirect()->route('admin.sessions.index')->with('success', 'Sesión eliminada.');
    }
}
