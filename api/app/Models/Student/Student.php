<?php

namespace App\Models\Student;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// Importações das outras áreas da aplicação
use App\Models\Course\Course;
use App\Models\Course\Module;
use App\Models\Event\Invite;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email_1',
        'email_2',
        'phone_1',
        'phone_2',
        'birthday',
        'address',
        'father_name',
        'mother_name',
        'registration_date',
    ];

    protected $casts = [
        'birthday'          => 'date',
        'registration_date' => 'date',
    ];

    // ---------------------------------------------------------
    // RELAÇÕES ACADÉMICAS (Módulo Course)
    // ---------------------------------------------------------

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_student')
                    ->withPivot('status', 'enrollment_date')
                    ->withTimestamps();
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'module_student')
                    ->withPivot('status', 'enrolled_at')
                    ->withTimestamps();
    }

    // ---------------------------------------------------------
    // RELAÇÕES DE ORGANIZAÇÃO E COMUNICAÇÃO
    // ---------------------------------------------------------

    public function groups()
    {
        // Assume que vamos criar o modelo Group dentro do namespace Student a seguir
        return $this->belongsToMany(Group::class, 'group_student')
                    ->withPivot('status') // ex: active, transferred, dropped
                    ->withTimestamps();
    }

    public function invitations()
    {
        return $this->belongsToMany(Invite::class, 'invitation_student')
                    ->withPivot('token', 'status', 'responded_at')
                    ->withTimestamps();
    }
}