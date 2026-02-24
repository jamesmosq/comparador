<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramaFormacion extends Model
{
    protected $table = 'programas_formacion';

    protected $fillable = ['user_id', 'name', 'code', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function competencias()
    {
        return $this->hasMany(Competencia::class)->orderBy('order');
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function scopeVisibleTo($query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }
}
