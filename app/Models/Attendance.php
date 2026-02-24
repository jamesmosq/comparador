<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['student_id', 'schedule_id', 'competencia_id', 'attendance_date', 'is_present', 'is_justified', 'remarks'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function competencia()
    {
        return $this->belongsTo(Competencia::class);
    }
}
