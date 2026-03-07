<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{
    protected $table = 'calificaciones'; // Laravel pluraliza "Calificacion" como "calificacions" (incorrecto)

    protected $fillable = [
        'student_id',
        'resultado_aprendizaje_id',
        'group_id',
        'nota',
        'observacion',
        'user_id',
    ];

    protected $casts = [
        'nota' => 'float',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function resultadoAprendizaje()
    {
        return $this->belongsTo(ResultadoAprendizaje::class, 'resultado_aprendizaje_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getEquivalenciaAttribute(): ?string
    {
        if ($this->nota === null) {
            return null;
        }
        return $this->nota >= 3.0 ? 'APROBADO' : 'NO APROBADO';
    }
}
