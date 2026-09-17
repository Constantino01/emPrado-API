<?php

namespace App\Models\Student;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// Import the Invitation model from the Engagement domain
use App\Models\Event\Invite;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'academic_year',
        'description',
    ];

    /**
     * Relationship: The students that belong to this group
     */
    public function students()
    {
        // The pivot 'group_student' manages the allocation of a student to a group
        return $this->belongsToMany(Student::class, 'group_student')
                    ->withPivot('status') // e.g., active, transferred, dropped
                    ->withTimestamps();
    }

    /**
     * Relationship: Invitations (Campaigns) sent to this entire group
     */
    public function invitations()
    {
        return $this->belongsToMany(Invite::class, 'group_invitation')
                    ->withTimestamps();
    }
}