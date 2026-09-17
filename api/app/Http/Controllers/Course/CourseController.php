<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Models\Course\Course;
use App\Models\Course\Module;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    // ---------------------------------------------------------
    // CRUD BASE DO CURSO
    // ---------------------------------------------------------

    public function index()
    {
        return response()->json(Course::paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $course = Course::create($validated);

        return response()->json([
            'message' => 'Curso criado com sucesso.',
            'data'    => $course
        ], 201);
    }

    public function show(Course $course)
    {
        // Traz o curso com os módulos já ordenados pela pivot order_index
        $course->load(['modules', 'students']);
        
        return response()->json($course);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $course->update($validated);

        return response()->json([
            'message' => 'Curso atualizado com sucesso.',
            'data'    => $course
        ]);
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return response()->json([
            'message' => 'Curso removido com sucesso.'
        ]);
    }

    // ---------------------------------------------------------
    // GESTÃO DE MÓDULOS NO CURSO (O Plano de Estudos)
    // ---------------------------------------------------------

    /**
     * Associa um módulo ao curso, definindo a sua ordem.
     */
    public function addModule(Request $request, Course $course)
    {
        $validated = $request->validate([
            'module_id'   => 'required|exists:modules,id',
            'order_index' => 'nullable|integer',
        ]);

        $moduleId = $validated['module_id'];

        if ($course->modules()->where('module_id', $moduleId)->exists()) {
            return response()->json(['message' => 'Este módulo já pertence a este curso.'], 422);
        }

        $course->modules()->attach($moduleId, [
            'order_index' => $validated['order_index'] ?? 0
        ]);

        return response()->json(['message' => 'Módulo adicionado ao plano de estudos com sucesso.']);
    }

    public function removeModule(Course $course, Module $module)
    {
        $course->modules()->detach($module->id);

        return response()->json(['message' => 'Módulo removido do curso.']);
    }

    // ---------------------------------------------------------
    // GESTÃO DE ALUNOS (Matrículas com Módulos Obrigatórios)
    // ---------------------------------------------------------

    /**
     * Matricula um aluno no curso e nos módulos escolhidos (Mínimo 1 obrigatório).
     */
    public function enroll(Request $request, Course $course)
    {
        $validated = $request->validate([
            'student_id'      => 'required|exists:students,id',
            'status'          => 'nullable|string|in:active,completed,suspended',
            'enrollment_date' => 'nullable|date',
            // Validação rigorosa: tem de enviar um array de módulos e ter pelo menos 1
            'module_ids'      => 'required|array|min:1', 
            'module_ids.*'    => 'exists:modules,id'
        ]);

        $studentId = $validated['student_id'];
        $student = Student::findOrFail($studentId);

        if ($course->students()->where('student_id', $studentId)->exists()) {
            return response()->json(['message' => 'O aluno já se encontra matriculado neste curso.'], 422);
        }

        // Usamos uma transação para garantir que a matrícula fica completa ou não é feita de todo
        DB::transaction(function () use ($course, $student, $validated) {
            
            // 1. Matricula no curso geral (tabela: course_student)
            $course->students()->attach($student->id, [
                'status'          => $validated['status'] ?? 'active',
                'enrollment_date' => $validated['enrollment_date'] ?? now()->toDateString(),
            ]);

            // 2. Prepara os dados para matricular nos módulos específicos (tabela: module_student)
            $modulesToSync = [];
            foreach ($validated['module_ids'] as $moduleId) {
                // Previne conflitos se o aluno já estiver nalgum módulo através de outro curso
                if (!$student->modules()->where('module_id', $moduleId)->exists()) {
                    $modulesToSync[$moduleId] = [
                        'status' => 'active',
                        'enrolled_at' => now()
                    ];
                }
            }

            // Anexa os módulos à ficha do aluno
            if (!empty($modulesToSync)) {
                $student->modules()->attach($modulesToSync);
            }
        });

        return response()->json([
            'message' => 'Aluno matriculado no curso e respetivos módulos com sucesso.'
        ], 201);
    }

    public function unenroll(Course $course, Student $student)
    {
        $course->students()->detach($student->id);

        return response()->json(['message' => 'Matrícula no curso cancelada.']);
    }
}