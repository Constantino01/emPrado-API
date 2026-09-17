<?php

namespace App\Models\Media;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Event\Event;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'year',
        'original_name',
        'file_name',
        'path',
        'mime_type',
        'size',
        'disk',
        'privacy_status',
        'glacier_status',
        'last_accessed_at',
    ];

    protected $casts = [
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Relação: Evento a que esta media pertence (opcional)
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // ---------------------------------------------------------
    // HELPERS PARA O GLACIER E ACESSO
    // ---------------------------------------------------------

    /**
     * Atualiza a data de último acesso.
     * O controlador deve chamar isto sempre que alguém pede o ficheiro.
     */
    public function recordAccess()
    {
        // Apenas atualiza se tiver passado mais de 1 hora desde o último registo 
        // (para não massacrar a base de dados em vídeos com muito streaming)
        if ($this->last_accessed_at->diffInHours(now()) >= 1) {
            $this->update(['last_accessed_at' => now()]);
        }
    }

    /**
     * Verifica se o ficheiro está atualmente arquivado no Glacier
     */
    public function isArchived()
    {
        return $this->glacier_status === 'archived';
    }
}