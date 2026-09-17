<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_student', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys linking to groups and students
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            
            // Track the student's status within this specific group
            $table->string('status')->default('active'); // e.g., active, transferred, dropped
            
            // Prevent duplicate entries for the same student in the same group
            $table->unique(['group_id', 'student_id']);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_student');
    }
};