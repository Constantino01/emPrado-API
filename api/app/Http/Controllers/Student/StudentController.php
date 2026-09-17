<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    // ---------------------------------------------------------
    // CRUD BASE DO ALUNO
    // ---------------------------------------------------------

    public function index()
    {
        return response()->json(Student::paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email_1'           => 'required|email|unique:students,email_1',
            'email_2'           => 'nullable|email',
            'phone_1'           => 'nullable|string|max:20',
            'phone_2'           => 'nullable|string|max:20',
            'birthday'          => 'nullable|date',
            'address'           => 'nullable|string',
            'father_name'       => 'nullable|string|max:255',
            'mother_name'       => 'nullable|string|max:255',
            'registration_date' => 'nullable|date',
        ]);

        $student = Student::create($validated);

        return response()->json([
            'message' => 'Student created successfully.',
            'data'    => $student
        ], 201);
    }

    public function show(Student $student)
    {
        // Carrega todo o contexto académico e organizacional
        $student->load(['courses', 'modules', 'groups']);
        
        return response()->json($student);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'name'              => 'sometimes|string|max:255',
            // Ignora o ID do próprio aluno para a validação de email único
            'email_1'           => 'sometimes|email|unique:students,email_1,' . $student->id,
            'email_2'           => 'nullable|email',
            'phone_1'           => 'nullable|string|max:20',
            'phone_2'           => 'nullable|string|max:20',
            'birthday'          => 'nullable|date',
            'address'           => 'nullable|string',
            'father_name'       => 'nullable|string|max:255',
            'mother_name'       => 'nullable|string|max:255',
            'registration_date' => 'nullable|date',
        ]);

        $student->update($validated);

        return response()->json([
            'message' => 'Student updated successfully.',
            'data'    => $student
        ]);
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return response()->json([
            'message' => 'Student deleted successfully.'
        ]);
    }

    // ---------------------------------------------------------
    // RELAÇÕES E HISTÓRICO DO ALUNO (Visualização)
    // ---------------------------------------------------------

    /**
     * Devolve apenas os cursos em que o aluno está inscrito.
     */
    public function courses(Student $student)
    {
        return response()->json($student->courses);
    }

    /**
     * Devolve apenas os módulos (e o seu estado) do aluno.
     */
    public function modules(Student $student)
    {
        return response()->json($student->modules);
    }

    /**
     * Histórico de convites recebidos e as respostas (RSVP) do aluno.
     */
    public function invitations(Student $student)
    {
        return response()->json($student->invitations);
    }

    // ---------------------------------------------------------
    // GESTÃO A PARTIR DO PERFIL DO ALUNO
    // ---------------------------------------------------------

    /**
     * Sincroniza os Grupos/Turmas do aluno de uma só vez (Ideal para formulários multiselect).
     * Se enviares um array vazio [], o aluno é removido de todas as turmas.
     */
    public function syncGroups(Request $request, Student $student)
    {
        $validated = $request->validate([
            'group_ids'   => 'present|array',
            'group_ids.*' => 'exists:groups,id'
        ]);

        // Prepara os dados para a pivot (garantindo que o status fica ativo por defeito)
        $syncData = [];
        foreach ($validated['group_ids'] as $groupId) {
            $syncData[$groupId] = ['status' => 'active'];
        }

        // O método sync() apaga os grupos antigos e insere os novos automaticamente
        $student->groups()->sync($syncData);

        return response()->json([
            'message' => 'Grupos do aluno atualizados com sucesso.',
            'groups'  => $student->groups
        ]);
    }

    /**
     * Alterar o status de um aluno num módulo específico (ex: marcar como concluído)
     */
    public function updateModuleStatus(Request $request, Student $student, $moduleId)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:active,completed,dropped'
        ]);

        if (!$student->modules()->where('module_id', $moduleId)->exists()) {
            return response()->json(['message' => 'O aluno não está inscrito neste módulo.'], 404);
        }

        $student->modules()->updateExistingPivot($moduleId, [
            'status' => $validated['status']
        ]);

        return response()->json(['message' => 'Estado do módulo atualizado.']);
    }
}