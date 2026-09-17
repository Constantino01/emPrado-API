<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Student\Student; // Importamos o modelo do Aluno da sua própria pasta

class Module extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
    ];

    /**
     * Relação: Em que cursos este módulo é lecionado? (course_module)
     */
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_module')
            ->withPivot('order_index')
            ->withTimestamps();
    }

    /**
     * Relação: Que alunos estão a frequentar este módulo especificamente? (module_student)
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'module_student')
            ->withPivot('status', 'enrolled_at')
            ->withTimestamps();
    }
}