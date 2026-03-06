<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActaJuicio extends Model
{
    protected $fillable = ['acta_id', 'student_id', 'juicio'];

    public function acta()
    {
        return $this->belongsTo(Acta::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
