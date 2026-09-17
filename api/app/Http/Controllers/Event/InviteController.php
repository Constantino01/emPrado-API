<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\Event\Invite;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\EventInviteMail; // Vamos criar este ficheiro a seguir

class InviteController extends Controller
{
    public function index()
    {
        return response()->json(Invite::with('event')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string',
        ]);

        $invite = Invite::create($validated);

        return response()->json([
            'message' => 'Invite created successfully.',
            'data'    => $invite
        ], 201);
    }

    public function show(Invite $invite)
    {
        $invite->load(['event', 'students']);
        return response()->json($invite);
    }

    public function destroy(Invite $invite)
    {
        $invite->delete();
        return response()->json(['message' => 'Invite deleted successfully.']);
    }

    // ---------------------------------------------------------
    // DISPATCH & RSVP MANAGEMENT
    // ---------------------------------------------------------

    public function sendToStudents(Request $request, Invite $invite)
    {
        $validated = $request->validate([
            'student_ids'   => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        // Associa os alunos ao convite na base de dados
        $invite->students()->syncWithoutDetaching($validated['student_ids']);

        // Vai buscar os dados completos dos alunos (precisamos do email e nome)
        $students = Student::whereIn('id', $validated['student_ids'])->get();

        // Envia o email via AWS SES (em background/fila)
        foreach ($students as $student) {
            if (!empty($student->email_1)) {
                Mail::to($student->email_1)->queue(new EventInviteMail($invite, $student));
            }
        }

        return response()->json([
            'message' => 'Invites successfully attached and emails queued for dispatch via SES.'
        ]);
    }

    public function updateRsvp(Request $request, Invite $invite, Student $student)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:accepted,declined,pending',
        ]);

        if (!$invite->students()->where('student_id', $student->id)->exists()) {
            return response()->json(['message' => 'This student was not invited to this event.'], 404);
        }

        $invite->students()->updateExistingPivot($student->id, [
            'status'       => $validated['status'],
            'responded_at' => now(),
        ]);

        return response()->json(['message' => 'RSVP updated successfully.']);
    }
}