<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competencia extends Model
{
    protected $fillable = ['programa_formacion_id', 'code', 'name', 'total_hours', 'order'];

    public function programa()
    {
        return $this->belongsTo(ProgramaFormacion::class, 'programa_formacion_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function resultadosAprendizaje()
    {
        return $this->hasMany(ResultadoAprendizaje::class)->orderBy('order');
    }
}
