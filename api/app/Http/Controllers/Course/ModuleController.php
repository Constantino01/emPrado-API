<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Models\Course\Module;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * Display a listing of modules.
     * Optionally filter by course_id.
     */
    public function index(Request $request)
    {
        $query = Module::query();

        // Filter modules belonging to a specific course via the pivot table
        if ($request->has('course_id')) {
            $query->whereHas('courses', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Store a newly created module in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'nullable|numeric|min:0',
        ]);

        $module = Module::create($validated);

        return response()->json([
            'message' => 'Module created successfully.',
            'data'    => $module
        ], 201);
    }

    /**
     * Display the specified module.
     */
    public function show(Module $module)
    {
        // Load the courses (with order) and students (with status and enrollment date)
        $module->load([
            'courses' => function ($query) {
                $query->orderByPivot('order_index', 'asc');
            },
            'students'
        ]);
        
        return response()->json($module);
    }

    /**
     * Update the specified module in storage.
     */
    public function update(Request $request, Module $module)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'sometimes|numeric|min:0',
        ]);

        $module->update($validated);

        return response()->json([
            'message' => 'Module updated successfully.',
            'data'    => $module
        ]);
    }

    /**
     * Remove the specified module from storage.
     */
    public function destroy(Module $module)
    {
        $module->delete();

        return response()->json([
            'message' => 'Module deleted successfully.'
        ]);
    }

    // ---------------------------------------------------------
    // GESTÃO DE ALUNOS NO MÓDULO (Inscrições Avulsas)
    // ---------------------------------------------------------

    /**
     * Enroll a student directly into this specific module.
     */
    public function enrollStudent(Request $request, Module $module)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'status'     => 'nullable|string|in:active,completed,dropped',
        ]);

        $studentId = $validated['student_id'];

        if ($module->students()->where('student_id', $studentId)->exists()) {
            return response()->json([
                'message' => 'The student is already enrolled in this module.'
            ], 422);
        }

        $module->students()->attach($studentId, [
            'status'      => $validated['status'] ?? 'active',
            'enrolled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Student enrolled in the module successfully.'
        ], 201);
    }

    /**
     * Remove a student from this module.
     */
    public function unenrollStudent(Module $module, Student $student)
    {
        $module->students()->detach($student->id);

        return response()->json([
            'message' => 'Student removed from the module successfully.'
        ]);
    }
}