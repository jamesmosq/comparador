<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultadoAprendizaje extends Model
{
    protected $table = 'resultados_aprendizaje';

    protected $fillable = ['competencia_id', 'code', 'name', 'order'];

    public function competencia()
    {
        return $this->belongsTo(Competencia::class);
    }
}
