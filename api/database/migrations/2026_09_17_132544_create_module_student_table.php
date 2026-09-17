<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_student', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            
            // Para sabermos se o aluno está a frequentar, já concluiu ou desistiu do módulo
            $table->string('status')->default('active'); // active, completed, dropped
            $table->timestamp('enrolled_at')->useCurrent();
            
            // Um aluno não pode estar inscrito duas vezes no mesmo módulo
            $table->unique(['module_id', 'student_id']);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_student');
    }
};