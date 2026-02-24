<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['institution_id', 'name', 'ficha_number', 'programa_formacion_id', 'day_of_week', 'start_time', 'end_time'];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function programaFormacion()
    {
        return $this->belongsTo(ProgramaFormacion::class, 'programa_formacion_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function actas()
    {
        return $this->belongsToMany(Acta::class, 'acta_group');
    }
}
