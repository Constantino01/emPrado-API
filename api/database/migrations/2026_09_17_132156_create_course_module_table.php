<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_module', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
    
            $table->integer('order_index')->default(0); 
    
            $table->unique(['course_id', 'module_id']);
            $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('course_module');
    }
};