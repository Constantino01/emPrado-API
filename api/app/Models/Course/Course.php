<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// Vamos assumir que os alunos também terão a sua própria pasta "Student" no futuro
use App\Models\Student\Student;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relação: O Curso tem vários Módulos (Plano de Estudos)
     * Como o Module está no mesmo namespace, não precisamos de importar.
     */
    public function modules()
    {
        return $this->belongsToMany(Module::class, 'course_module')
            ->withPivot('order_index') // Trazer a coluna
            ->orderByPivot('order_index', 'asc') // Ordenar do menor para o maior
            ->withTimestamps();
    }

    /**
     * Relação: O Curso tem vários Alunos matriculados
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'course_student')
            ->withPivot('status', 'enrollment_date')
            ->withTimestamps();
    }
}