<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invites', function (Blueprint $table) {
            $table->id();
            
            // Ligação direta 1:N ao Evento
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            
            $table->string('subject')->nullable(); // Ex: "Convite para a Cerimónia de Abertura"
            $table->text('message')->nullable();   // Corpo/Mensagem do convite
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invites');
    }
};