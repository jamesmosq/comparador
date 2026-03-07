<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = ['group_id', 'resultado_aprendizaje_id', 'subject', 'day_of_week', 'start_time', 'end_time'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function resultadoAprendizaje()
    {
        return $this->belongsTo(ResultadoAprendizaje::class, 'resultado_aprendizaje_id');
    }

    public function getLabelAttribute(): string
    {
        if ($this->resultadoAprendizaje) {
            $ra = $this->resultadoAprendizaje;
            return ($ra->code ? '[' . $ra->code . '] ' : '') . $ra->name;
        }
        return $this->subject ?? '—';
    }
}
