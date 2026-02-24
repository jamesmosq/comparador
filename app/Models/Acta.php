<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Acta extends Model
{
    protected $fillable = [
        'user_id',
        'group_id',       // legacy nullable — usar groups() para múltiples fichas
        'docente_par_id',
        'competencia_id',
        'tipo',
        'numero_acta',
        'fecha',
        'lugar',
        'agenda',
        'hora_inicio',
        'hora_fin',
        'estado',
        'objetivo',
        'desarrollo',
        'compromisos',    // texto legacy — usar compromisos() para la tabla estructurada
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // ── Relaciones ─────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** @deprecated usar groups() */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /** Fichas / Grupos asociados (muchos a muchos) */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'acta_group');
    }

    public function docentePar()
    {
        return $this->belongsTo(DocentePar::class);
    }

    public function competencia()
    {
        return $this->belongsTo(Competencia::class);
    }

    /** Compromisos estructurados */
    public function compromisoItems()
    {
        return $this->hasMany(ActaCompromiso::class)->orderBy('orden');
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'seguimiento'               => 'Seguimiento al Proceso Formativo',
            'inicio_ficha'              => 'Inicio de Ficha y Distribución de Temario',
            'visita_seguimiento'        => 'Visita de Seguimiento',
            'cierre'                    => 'Cierre del Proceso Formativo',
            'aprobacion_etapa_practica' => 'Aprobación Etapa Práctica',
            default                     => ucfirst($this->tipo),
        };
    }

    public function getTipoCodigoAttribute(): string
    {
        return match ($this->tipo) {
            'seguimiento'               => 'GD-F-007',
            'visita_seguimiento'        => 'GD-F-007',
            'inicio_ficha'              => 'GD-F-001',
            'cierre'                    => 'GD-F-009',
            'aprobacion_etapa_practica' => 'GFPI-F-023',
            default                     => 'GD-F-007',
        };
    }

    public function getTipoShortAttribute(): string
    {
        return match ($this->tipo) {
            'seguimiento'               => 'Seguimiento',
            'inicio_ficha'              => 'Inicio de Ficha',
            'visita_seguimiento'        => 'Visita Seguimiento',
            'cierre'                    => 'Cierre',
            'aprobacion_etapa_practica' => 'Aprobación Etapa Práctica',
            default                     => ucfirst($this->tipo),
        };
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeVisibleTo($query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }
}
