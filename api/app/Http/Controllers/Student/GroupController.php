<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Group;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        return response()->json(Group::paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'academic_year' => 'nullable|string|max:20',
            'description'   => 'nullable|string',
        ]);

        $group = Group::create($validated);

        return response()->json([
            'message' => 'Group created successfully.',
            'data'    => $group
        ], 201);
    }

    public function show(Group $group)
    {
        $group->load('students');
        return response()->json($group);
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'academic_year' => 'nullable|string|max:20',
            'description'   => 'nullable|string',
        ]);

        $group->update($validated);

        return response()->json([
            'message' => 'Group updated successfully.',
            'data'    => $group
        ]);
    }

    public function destroy(Group $group)
    {
        $group->delete();
        return response()->json(['message' => 'Group deleted successfully.']);
    }

    // ---------------------------------------------------------
    // STUDENT ALLOCATION
    // ---------------------------------------------------------

    public function addStudent(Request $request, Group $group)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'status'     => 'nullable|string|in:active,transferred,dropped'
        ]);

        $studentId = $request->student_id;

        if ($group->students()->where('student_id', $studentId)->exists()) {
            return response()->json(['message' => 'Student is already in this group.'], 422);
        }

        $group->students()->attach($studentId, [
            'status' => $request->status ?? 'active'
        ]);

        return response()->json(['message' => 'Student added to group successfully.']);
    }

    public function removeStudent(Group $group, Student $student)
    {
        $group->students()->detach($student->id);
        return response()->json(['message' => 'Student removed from group.']);
    }
}