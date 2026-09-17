<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            
            // Ligações e Contexto
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('year')->default(date('Y')); // Ano do ficheiro/evento
            
            // Dados Físicos do Ficheiro
            $table->string('original_name'); // Nome original (ex: foto_casamento.jpg)
            $table->string('file_name');     // Nome encriptado gerado pelo Laravel
            $table->string('path');          // Caminho no bucket S3
            $table->string('mime_type');     // ex: image/webp, video/mp4
            $table->unsignedBigInteger('size'); // Tamanho em bytes
            
            // Arquitetura AWS e Privacidade
            $table->string('disk'); // 's3_public' ou 's3_private'
            $table->string('privacy_status')->default('private'); // 'public' ou 'private'
            
            // Ciclo de Vida (AWS Glacier)
            $table->string('glacier_status')->default('standard'); // standard, archiving, archived, restoring
            $table->timestamp('last_accessed_at')->useCurrent(); // Atualizado sempre que o ficheiro é lido
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};