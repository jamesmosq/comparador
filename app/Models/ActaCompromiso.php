<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActaCompromiso extends Model
{
    protected $table = 'acta_compromisos';

    protected $fillable = [
        'acta_id',
        'descripcion',
        'responsable',
        'fecha_limite',
        'orden',
    ];

    protected $casts = [
        'fecha_limite' => 'date',
    ];

    public function acta()
    {
        return $this->belongsTo(Acta::class);
    }

    public function getResponsableLabelAttribute(): string
    {
        return match ($this->responsable) {
            'instructor_sena' => 'Instructor SENA',
            'docente_par'     => 'Docente Par',
            'ambos'           => 'Instructor SENA y Docente Par',
            default           => $this->responsable,
        };
    }
}
