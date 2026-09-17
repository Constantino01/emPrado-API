<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invite_student', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('invite_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            
            // Estado da resposta (RSVP)
            $table->string('status')->default('pending'); // pending, accepted, declined
            $table->timestamp('responded_at')->nullable();
            
            $table->unique(['invite_id', 'student_id']);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invite_student');
    }
};