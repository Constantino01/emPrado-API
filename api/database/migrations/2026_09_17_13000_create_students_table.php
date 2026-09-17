<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            
            // Dados Pessoais
            $table->string('name');
            $table->date('birthday')->nullable();
            $table->string('address')->nullable();
            
            // Contactos
            $table->string('email_1')->unique();
            $table->string('email_2')->nullable();
            $table->string('phone_1')->nullable();
            $table->string('phone_2')->nullable();
            
            // Encarregados de Educação / Pais
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            
            // Data de Registo Oficial na Escola
            $table->date('registration_date')->useCurrent();
            
            $table->timestamps();
            $table->softDeletes(); // Muito importante para não apagar o histórico financeiro/notas por engano
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};