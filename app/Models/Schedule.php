<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = ['group_id', 'subject', 'day_of_week', 'start_time', 'end_time'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
