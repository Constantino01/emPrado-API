<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Student\Student; // Importamos o domínio dos Estudantes

class Invite extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'subject',
        'message',
    ];

    /**
     * Relação: A que Evento pertence este convite? (N:1)
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relação: Que alunos receberam este convite e qual a resposta? (N:N)
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'invite_student')
                    ->withPivot('status', 'responded_at')
                    ->withTimestamps();
    }
}