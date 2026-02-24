<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocentePar extends Model
{
    protected $table = 'docentes_par';

    protected $fillable = [
        'user_id',
        'name',
        'document_number',
        'position',
        'email',
        'institution_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actas()
    {
        return $this->hasMany(Acta::class);
    }

    public function scopeVisibleTo($query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }
}
