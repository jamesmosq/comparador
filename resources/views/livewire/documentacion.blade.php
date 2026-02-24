<?php

use Livewire\Volt\Component;
use App\Models\Institution;
use App\Models\Group;
use App\Models\Competencia;
use App\Models\ProgramaFormacion;
use App\Models\DocentePar;
use App\Models\Acta;
use App\Models\ActaCompromiso;

new class extends Component
{
    // ── Datos globales ─────────────────────────────────────────────
    public $institutions     = [];
    public $all_competencias = [];
    public $docentes_par     = [];

    // ── Filtros ────────────────────────────────────────────────────
    public $filter_groups        = [];
    public $filter_institution_id = null;
    public $filter_group_id      = null;
    public string $filter_tipo   = '';
    public string $filter_estado = '';
    public $actas = [];

    // ── Modal CREAR ────────────────────────────────────────────────
    public $new_institution_id = null;
    public $modal_groups       = [];
    public array $new_group_ids         = [];
    public string $new_numero           = '';
    public string $new_tipo             = '';
    public $new_docente_par_id          = null;
    public $new_competencia_id          = null;
    public string $new_fecha            = '';
    public string $new_lugar            = '';
    public string $new_agenda           = '';
    public string $new_hora_inicio      = '';
    public string $new_hora_fin         = '';
    public string $new_objetivo         = '';
    public string $new_desarrollo       = '';
    public string $new_observaciones    = '';
    public array $new_compromisos_rows  = [
        ['descripcion' => '', 'responsable' => 'ambos', 'fecha_limite' => ''],
    ];
    public bool $showCreateModal = false;

    // ── Modal EDITAR ───────────────────────────────────────────────
    public $editing_id                      = null;
    public $editing_institution_id          = null;
    public array $editing_group_ids         = [];
    public string $editing_numero           = '';
    public string $editing_tipo             = '';
    public $editing_docente_par_id          = null;
    public $editing_competencia_id          = null;
    public string $editing_fecha            = '';
    public string $editing_lugar            = '';
    public string $editing_agenda           = '';
    public string $editing_hora_inicio      = '';
    public string $editing_hora_fin         = '';
    public string $editing_objetivo         = '';
    public string $editing_desarrollo       = '';
    public string $editing_observaciones    = '';
    public array $editing_compromisos_rows  = [];
    public bool $showEditModal = false;

    // ── Docentes Par ───────────────────────────────────────────────
    public string $new_dp_name        = '';
    public string $new_dp_document    = '';
    public string $new_dp_position    = '';
    public string $new_dp_email       = '';
    public string $new_dp_institution = '';

    public $editing_dp_id                 = null;
    public string $editing_dp_name        = '';
    public string $editing_dp_document    = '';
    public string $editing_dp_position    = '';
    public string $editing_dp_email       = '';
    public string $editing_dp_institution = '';
    public bool $showEditDpModal = false;

    // ── Ciclo de vida ──────────────────────────────────────────────

    public function mount(): void
    {
        $this->institutions = Institution::visibleTo(auth()->user())->orderBy('name')->get();

        $programaIds = ProgramaFormacion::visibleTo(auth()->user())->pluck('id');
        $this->all_competencias = Competencia::whereIn('programa_formacion_id', $programaIds)
            ->orderBy('name')->get();

        $this->loadActas();
        $this->loadDocentesPar();
    }

    // ── Watchers filtro ────────────────────────────────────────────

    public function updatedFilterInstitutionId(): void
    {
        $this->filter_group_id = null;
        $this->filter_groups = $this->filter_institution_id
            ? Group::where('institution_id', $this->filter_institution_id)->orderBy('name')->get()
            : [];
        $this->loadActas();
    }

    public function updatedFilterGroupId(): void   { $this->loadActas(); }
    public function updatedFilterTipo(): void      { $this->loadActas(); }
    public function updatedFilterEstado(): void    { $this->loadActas(); }

    // ── Watchers modal ─────────────────────────────────────────────

    public function updatedNewInstitutionId(): void
    {
        $this->new_group_ids = [];
        $this->modal_groups  = $this->new_institution_id
            ? Group::where('institution_id', $this->new_institution_id)->orderBy('name')->get()
            : [];
    }

    public function updatedEditingInstitutionId(): void
    {
        $this->editing_group_ids = [];
        $this->modal_groups = $this->editing_institution_id
            ? Group::where('institution_id', $this->editing_institution_id)->orderBy('name')->get()
            : [];
    }

    // ── CRUD Compromisos (filas dinámicas) ─────────────────────────

    public function addNewCompromisoRow(): void
    {
        $this->new_compromisos_rows[] = ['descripcion' => '', 'responsable' => 'ambos', 'fecha_limite' => ''];
    }

    public function removeNewCompromisoRow(int $idx): void
    {
        array_splice($this->new_compromisos_rows, $idx, 1);
        $this->new_compromisos_rows = array_values($this->new_compromisos_rows);
    }

    public function addEditingCompromisoRow(): void
    {
        $this->editing_compromisos_rows[] = ['descripcion' => '', 'responsable' => 'ambos', 'fecha_limite' => ''];
    }

    public function removeEditingCompromisoRow(int $idx): void
    {
        array_splice($this->editing_compromisos_rows, $idx, 1);
        $this->editing_compromisos_rows = array_values($this->editing_compromisos_rows);
    }

    // ── CRUD Actas ─────────────────────────────────────────────────

    public function loadActas(): void
    {
        $query = Acta::with(['groups.institution', 'groups.programaFormacion', 'docentePar', 'compromisoItems'])
            ->visibleTo(auth()->user());

        if ($this->filter_group_id) {
            $query->whereHas('groups', fn ($q) => $q->where('groups.id', $this->filter_group_id));
        } elseif ($this->filter_institution_id) {
            $query->whereHas('groups', fn ($q) => $q->where('institution_id', $this->filter_institution_id));
        }

        if ($this->filter_tipo)   { $query->where('tipo', $this->filter_tipo); }
        if ($this->filter_estado) { $query->where('estado', $this->filter_estado); }

        $this->actas = $query->orderByDesc('fecha')->orderByDesc('id')->get();
    }

    public function addActa(): void
    {
        $this->validate([
            'new_numero'         => 'required',
            'new_tipo'           => 'required|in:seguimiento,inicio_ficha,visita_seguimiento,cierre,aprobacion_etapa_practica',
            'new_group_ids'      => 'required|array|min:1',
            'new_group_ids.*'    => 'exists:groups,id',
            'new_docente_par_id' => 'required|exists:docentes_par,id',
            'new_fecha'          => 'required|date',
            'new_lugar'          => 'required',
        ], [
            'new_numero.required'         => 'El número de acta es obligatorio.',
            'new_tipo.required'           => 'Seleccione el tipo de acta.',
            'new_group_ids.required'      => 'Seleccione al menos una ficha/grupo.',
            'new_group_ids.min'           => 'Seleccione al menos una ficha/grupo.',
            'new_docente_par_id.required' => 'Seleccione el docente par.',
            'new_fecha.required'          => 'La fecha es obligatoria.',
            'new_lugar.required'          => 'El lugar es obligatorio.',
        ]);

        $acta = Acta::create([
            'user_id'        => auth()->id(),
            'numero_acta'    => trim($this->new_numero),
            'tipo'           => $this->new_tipo,
            'docente_par_id' => $this->new_docente_par_id,
            'competencia_id' => $this->new_competencia_id ?: null,
            'fecha'          => $this->new_fecha,
            'lugar'          => trim($this->new_lugar),
            'agenda'         => trim($this->new_agenda) ?: null,
            'hora_inicio'    => $this->new_hora_inicio ?: null,
            'hora_fin'       => $this->new_hora_fin ?: null,
            'objetivo'       => trim($this->new_objetivo) ?: null,
            'desarrollo'     => trim($this->new_desarrollo) ?: null,
            'observaciones'  => trim($this->new_observaciones) ?: null,
            'estado'         => 'borrador',
        ]);

        // Asociar fichas (M:M)
        $acta->groups()->sync($this->new_group_ids);

        // Guardar compromisos estructurados
        foreach ($this->new_compromisos_rows as $i => $row) {
            if (! empty(trim($row['descripcion'] ?? ''))) {
                $acta->compromisoItems()->create([
                    'descripcion'  => trim($row['descripcion']),
                    'responsable'  => $row['responsable'] ?: 'ambos',
                    'fecha_limite' => $row['fecha_limite'] ?: null,
                    'orden'        => $i,
                ]);
            }
        }

        $this->reset([
            'new_numero', 'new_tipo', 'new_institution_id', 'new_group_ids',
            'new_docente_par_id', 'new_competencia_id', 'new_fecha', 'new_lugar',
            'new_agenda', 'new_hora_inicio', 'new_hora_fin', 'new_objetivo',
            'new_desarrollo', 'new_observaciones', 'modal_groups',
        ]);
        $this->new_compromisos_rows = [['descripcion' => '', 'responsable' => 'ambos', 'fecha_limite' => '']];
        $this->showCreateModal = false;
        $this->loadActas();
        session()->flash('success', 'Acta creada correctamente.');
    }

    public function openEditActa($id): void
    {
        $acta = Acta::with(['groups', 'compromisoItems'])->visibleTo(auth()->user())->findOrFail($id);

        $this->editing_id             = $id;
        $this->editing_numero         = $acta->numero_acta;
        $this->editing_tipo           = $acta->tipo;
        $this->editing_docente_par_id = $acta->docente_par_id;
        $this->editing_competencia_id = $acta->competencia_id;
        $this->editing_fecha          = $acta->fecha->format('Y-m-d');
        $this->editing_lugar          = $acta->lugar;
        $this->editing_agenda         = $acta->agenda ?? '';
        $this->editing_hora_inicio    = $acta->hora_inicio ?? '';
        $this->editing_hora_fin       = $acta->hora_fin ?? '';
        $this->editing_objetivo       = $acta->objetivo ?? '';
        $this->editing_desarrollo     = $acta->desarrollo ?? '';
        $this->editing_observaciones  = $acta->observaciones ?? '';

        // Grupos asociados
        $this->editing_group_ids = $acta->groups->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        // Institución del primer grupo para cargar el selector
        $firstGroup = $acta->groups->first();
        if ($firstGroup) {
            $this->editing_institution_id = $firstGroup->institution_id;
            $this->modal_groups = Group::where('institution_id', $firstGroup->institution_id)->orderBy('name')->get();
        }

        // Compromisos estructurados
        $this->editing_compromisos_rows = $acta->compromisoItems->map(fn ($c) => [
            'descripcion'  => $c->descripcion,
            'responsable'  => $c->responsable,
            'fecha_limite' => $c->fecha_limite?->format('Y-m-d') ?? '',
        ])->toArray();

        if (empty($this->editing_compromisos_rows)) {
            $this->editing_compromisos_rows = [['descripcion' => '', 'responsable' => 'ambos', 'fecha_limite' => '']];
        }

        $this->showEditModal = true;
    }

    public function saveActa(): void
    {
        $this->validate([
            'editing_numero'         => 'required',
            'editing_tipo'           => 'required|in:seguimiento,inicio_ficha,visita_seguimiento,cierre,aprobacion_etapa_practica',
            'editing_group_ids'      => 'required|array|min:1',
            'editing_group_ids.*'    => 'exists:groups,id',
            'editing_docente_par_id' => 'required|exists:docentes_par,id',
            'editing_fecha'          => 'required|date',
            'editing_lugar'          => 'required',
        ], [
            'editing_group_ids.required' => 'Seleccione al menos una ficha/grupo.',
            'editing_group_ids.min'      => 'Seleccione al menos una ficha/grupo.',
        ]);

        $acta = Acta::visibleTo(auth()->user())->findOrFail($this->editing_id);

        $acta->update([
            'numero_acta'    => trim($this->editing_numero),
            'tipo'           => $this->editing_tipo,
            'docente_par_id' => $this->editing_docente_par_id,
            'competencia_id' => $this->editing_competencia_id ?: null,
            'fecha'          => $this->editing_fecha,
            'lugar'          => trim($this->editing_lugar),
            'agenda'         => trim($this->editing_agenda) ?: null,
            'hora_inicio'    => $this->editing_hora_inicio ?: null,
            'hora_fin'       => $this->editing_hora_fin ?: null,
            'objetivo'       => trim($this->editing_objetivo) ?: null,
            'desarrollo'     => trim($this->editing_desarrollo) ?: null,
            'observaciones'  => trim($this->editing_observaciones) ?: null,
        ]);

        $acta->groups()->sync($this->editing_group_ids);

        $acta->compromisoItems()->delete();
        foreach ($this->editing_compromisos_rows as $i => $row) {
            if (! empty(trim($row['descripcion'] ?? ''))) {
                $acta->compromisoItems()->create([
                    'descripcion'  => trim($row['descripcion']),
                    'responsable'  => $row['responsable'] ?: 'ambos',
                    'fecha_limite' => $row['fecha_limite'] ?: null,
                    'orden'        => $i,
                ]);
            }
        }

        $this->showEditModal = false;
        $this->loadActas();
        session()->flash('success', 'Acta actualizada.');
    }

    public function deleteActa($id): void
    {
        Acta::visibleTo(auth()->user())->findOrFail($id)->delete();
        $this->loadActas();
        session()->flash('success', 'Acta eliminada.');
    }

    public function finalizarActa($id): void
    {
        Acta::visibleTo(auth()->user())->findOrFail($id)->update(['estado' => 'finalizada']);
        $this->loadActas();
        session()->flash('success', 'Acta finalizada.');
    }

    // ── CRUD Docentes Par ──────────────────────────────────────────

    public function loadDocentesPar(): void
    {
        $this->docentes_par = DocentePar::visibleTo(auth()->user())->orderBy('name')->get();
    }

    public function addDocentePar(): void
    {
        $this->validate(['new_dp_name' => 'required|min:3'],
                        ['new_dp_name.required' => 'El nombre es obligatorio.']);

        DocentePar::create([
            'user_id'          => auth()->id(),
            'name'             => trim($this->new_dp_name),
            'document_number'  => trim($this->new_dp_document) ?: null,
            'position'         => trim($this->new_dp_position) ?: null,
            'email'            => trim($this->new_dp_email) ?: null,
            'institution_name' => trim($this->new_dp_institution) ?: null,
        ]);

        $this->reset(['new_dp_name', 'new_dp_document', 'new_dp_position', 'new_dp_email', 'new_dp_institution']);
        $this->loadDocentesPar();
        session()->flash('success', 'Docente Par creado correctamente.');
    }

    public function openEditDocentePar($id): void
    {
        $dp = DocentePar::visibleTo(auth()->user())->findOrFail($id);
        $this->editing_dp_id          = $id;
        $this->editing_dp_name        = $dp->name;
        $this->editing_dp_document    = $dp->document_number ?? '';
        $this->editing_dp_position    = $dp->position ?? '';
        $this->editing_dp_email       = $dp->email ?? '';
        $this->editing_dp_institution = $dp->institution_name ?? '';
        $this->showEditDpModal        = true;
    }

    public function saveDocentePar(): void
    {
        $this->validate(['editing_dp_name' => 'required|min:3']);

        DocentePar::visibleTo(auth()->user())->findOrFail($this->editing_dp_id)->update([
            'name'             => trim($this->editing_dp_name),
            'document_number'  => trim($this->editing_dp_document) ?: null,
            'position'         => trim($this->editing_dp_position) ?: null,
            'email'            => trim($this->editing_dp_email) ?: null,
            'institution_name' => trim($this->editing_dp_institution) ?: null,
        ]);

        $this->showEditDpModal = false;
        $this->loadDocentesPar();
        session()->flash('success', 'Docente Par actualizado.');
    }

    public function deleteDocentePar($id): void
    {
        DocentePar::visibleTo(auth()->user())->findOrFail($id)->delete();
        $this->loadDocentesPar();
        session()->flash('success', 'Docente Par eliminado.');
    }
};
?>

<div class="max-w-7xl mx-auto px-4 pb-12" x-data="{ tab: 'actas' }">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Documentación</h1>
        <p class="text-sm text-gray-500 mt-1">Actas SENA — Articulación con la Media.</p>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- ── Pestañas ──────────────────────────────────────────────── --}}
    <div class="flex border-b border-gray-200 mb-6">
        <button @click="tab='actas'"
                :class="tab==='actas' ? 'border-b-2 border-indigo-600 text-indigo-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-5 py-2.5 text-sm transition-colors">
            Actas
        </button>
        <button @click="tab='docentes'"
                :class="tab==='docentes' ? 'border-b-2 border-indigo-600 text-indigo-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-5 py-2.5 text-sm transition-colors">
            Docentes Par
        </button>
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- PESTAÑA ACTAS                                               --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='actas'">

        {{-- Filtros --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
            <div class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Institución</label>
                    <select wire:model.live="filter_institution_id"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Todas</option>
                        @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Ficha / Grupo</label>
                    <select wire:model.live="filter_group_id"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            @if(!$filter_institution_id) disabled @endif>
                        <option value="">Todos</option>
                        @foreach($filter_groups as $grp)
                        <option value="{{ $grp->id }}">{{ $grp->ficha_number ? $grp->ficha_number . ' — ' : '' }}{{ $grp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                    <select wire:model.live="filter_tipo"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Todos</option>
                        <option value="seguimiento">Seguimiento</option>
                        <option value="inicio_ficha">Inicio de Ficha</option>
                        <option value="visita_seguimiento">Visita de Seguimiento</option>
                        <option value="cierre">Cierre</option>
                        <option value="aprobacion_etapa_practica">Aprobación Etapa Práctica</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
                    <select wire:model.live="filter_estado"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Todos</option>
                        <option value="borrador">Borrador</option>
                        <option value="finalizada">Finalizada</option>
                    </select>
                </div>
                <div class="ml-auto">
                    <button wire:click="$set('showCreateModal', true)"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        + Nueva Acta
                    </button>
                </div>
            </div>
        </div>

        {{-- Tabla de actas --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if(count($actas) === 0)
            <div class="px-6 py-12 text-center text-gray-400 text-sm">No hay actas con los filtros aplicados.</div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tipo</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fichas</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Docente Par</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fecha</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Compromisos</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Estado</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($actas as $i => $acta)
                        @php
                            $tipoBadge = match($acta->tipo) {
                                'seguimiento'               => 'bg-blue-100 text-blue-700',
                                'inicio_ficha'              => 'bg-green-100 text-green-700',
                                'visita_seguimiento'        => 'bg-purple-100 text-purple-700',
                                'cierre'                    => 'bg-red-100 text-red-700',
                                'aprobacion_etapa_practica' => 'bg-orange-100 text-orange-700',
                                default                     => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 py-3 text-gray-500 text-xs">{{ $acta->numero_acta }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $tipoBadge }}">
                                    {{ $acta->tipo_short }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                @foreach($acta->groups as $grp)
                                <div class="text-xs text-gray-700 leading-tight">
                                    {{ $grp->ficha_number ? $grp->ficha_number . ' · ' : '' }}{{ $grp->name }}
                                    <span class="text-gray-400">({{ $grp->institution?->name ?? '' }})</span>
                                </div>
                                @endforeach
                            </td>
                            <td class="px-3 py-3 text-gray-700 text-xs">{{ $acta->docentePar?->name ?? '—' }}</td>
                            <td class="px-3 py-3 text-gray-600 text-xs whitespace-nowrap">{{ $acta->fecha->format('d/m/Y') }}</td>
                            <td class="px-3 py-3">
                                <span class="text-xs text-gray-500">{{ $acta->compromisoItems->count() }} compromisos</span>
                            </td>
                            <td class="px-3 py-3">
                                @if($acta->estado === 'finalizada')
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Finalizada</span>
                                @else
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Borrador</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <a href="{{ route('actas.preview', $acta->id) }}" target="_blank"
                                       class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded">Ver</a>
                                    <a href="{{ route('actas.export.word', $acta->id) }}"
                                       class="text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 px-2 py-1 rounded">Word</a>
                                    <a href="{{ route('actas.export.pdf', $acta->id) }}"
                                       class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-2 py-1 rounded">PDF</a>
                                    <button wire:click="openEditActa({{ $acta->id }})"
                                            class="text-xs bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-2 py-1 rounded">Editar</button>
                                    @if($acta->estado === 'borrador')
                                    <button wire:click="finalizarActa({{ $acta->id }})"
                                            wire:confirm="¿Finalizar esta acta?"
                                            class="text-xs bg-green-100 hover:bg-green-200 text-green-700 px-2 py-1 rounded">Finalizar</button>
                                    @endif
                                    <div x-data="{ c: false }">
                                        <button @click="c=true" x-show="!c"
                                                class="text-xs bg-red-50 hover:bg-red-100 text-red-600 px-2 py-1 rounded">
                                            <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                        <div class="flex items-center gap-1" x-show="c" x-cloak>
                                            <span class="text-xs text-gray-500">¿Eliminar?</span>
                                            <button wire:click="deleteActa({{ $acta->id }})"
                                                    class="text-xs bg-red-600 text-white px-2 py-0.5 rounded">Sí</button>
                                            <button @click="c=false"
                                                    class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded">No</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- PESTAÑA DOCENTES PAR                                        --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='docentes'" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Nuevo Docente Par</h2>
                <form wire:submit.prevent="addDocentePar" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nombre completo *</label>
                        <input type="text" wire:model="new_dp_name" placeholder="Ej: María González"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        @error('new_dp_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Documento</label>
                        <input type="text" wire:model="new_dp_document" placeholder="C.C."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cargo</label>
                        <input type="text" wire:model="new_dp_position" placeholder="Ej: Docente de Aula"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Correo electrónico</label>
                        <input type="email" wire:model="new_dp_email"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Institución articulación</label>
                        <input type="text" wire:model="new_dp_institution" placeholder="Ej: IED La Merced"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                        Crear Docente Par
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">Docentes Par registrados</h2>
                    <span class="text-xs bg-indigo-100 text-indigo-700 font-semibold px-2 py-0.5 rounded-full">{{ count($docentes_par) }}</span>
                </div>
                @if(count($docentes_par) === 0)
                <div class="px-6 py-10 text-center text-gray-400 text-sm">Sin docentes par registrados.</div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Nombre</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Documento</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Cargo</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Institución</th>
                                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($docentes_par as $dp)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800">{{ $dp->name }}</div>
                                    @if($dp->email)<div class="text-xs text-gray-400">{{ $dp->email }}</div>@endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 text-xs">{{ $dp->document_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 text-xs">{{ $dp->position ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 text-xs">{{ $dp->institution_name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1">
                                        <button wire:click="openEditDocentePar({{ $dp->id }})"
                                                class="text-xs text-indigo-600 hover:text-indigo-800 p-1 rounded hover:bg-indigo-50">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <div x-data="{ c: false }">
                                            <button @click="c=true" x-show="!c" class="text-xs text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                            <div class="flex items-center gap-1" x-show="c" x-cloak>
                                                <span class="text-xs text-gray-500">¿Eliminar?</span>
                                                <button wire:click="deleteDocentePar({{ $dp->id }})" class="text-xs bg-red-600 text-white px-2 py-0.5 rounded">Sí</button>
                                                <button @click="c=false" class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded">No</button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>


    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- MACRO: campos de compromiso (reutilizable por slot Alpine) --}}
    {{-- ════════════════════════════════════════════════════════════ --}}

    {{-- ── MODAL CREAR ACTA ──────────────────────────────────────── --}}
    <div x-show="$wire.showCreateModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="$wire.showCreateModal = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 z-10 overflow-y-auto max-h-[92vh]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800">Nueva Acta</h3>
                <button @click="$wire.showCreateModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="addActa" class="space-y-4">

                {{-- Número y tipo --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">N° Acta *</label>
                        <input type="text" wire:model="new_numero" placeholder="Ej: 004-2025"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        @error('new_numero') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo *</label>
                        <select wire:model="new_tipo"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <option value="">Seleccionar...</option>
                            <option value="seguimiento">Seguimiento (GD-F-007)</option>
                            <option value="inicio_ficha">Inicio de Ficha</option>
                            <option value="visita_seguimiento">Visita de Seguimiento</option>
                            <option value="cierre">Cierre</option>
                            <option value="aprobacion_etapa_practica">Aprobación Etapa Práctica</option>
                        </select>
                        @error('new_tipo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Institución + fichas/grupos (checkboxes) --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Institución</label>
                    <select wire:model.live="new_institution_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Seleccionar...</option>
                        @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if(count($modal_groups) > 0 && !$showEditModal)
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Fichas / Grupos * <span class="text-gray-400">(selecciona una o más)</span></label>
                    <div class="border border-gray-300 rounded-lg p-3 grid grid-cols-1 gap-1.5 max-h-40 overflow-y-auto">
                        @foreach($modal_groups as $grp)
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 rounded px-1">
                            <input type="checkbox" wire:model="new_group_ids" value="{{ $grp->id }}"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-400">
                            <span class="text-sm text-gray-700">
                                {{ $grp->ficha_number ? '<span class="font-mono text-xs text-indigo-600">' . $grp->ficha_number . '</span> — ' : '' }}{{ $grp->name }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                    @error('new_group_ids') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                {{-- Docente par y competencia --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Docente Par *</label>
                        <select wire:model="new_docente_par_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <option value="">Seleccionar...</option>
                            @foreach($docentes_par as $dp)
                            <option value="{{ $dp->id }}">{{ $dp->name }}</option>
                            @endforeach
                        </select>
                        @error('new_docente_par_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Competencia</label>
                        <select wire:model="new_competencia_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <option value="">Ninguna</option>
                            @foreach($all_competencias as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->code ? '[' . $comp->code . '] ' : '' }}{{ $comp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Fecha, lugar, horas --}}
                <div class="grid grid-cols-4 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Fecha *</label>
                        <input type="date" wire:model="new_fecha"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        @error('new_fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Hora inicio</label>
                        <input type="time" wire:model="new_hora_inicio"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Hora fin</label>
                        <input type="time" wire:model="new_hora_fin"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Lugar *</label>
                    <input type="text" wire:model="new_lugar" placeholder="Ej: Itagüí — IE Felipe de Restrepo"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('new_lugar') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Agenda --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Agenda / Orden del día</label>
                    <textarea wire:model="new_agenda" rows="2"
                              placeholder="Ej: Saludo, seguimiento académico, seguimiento disciplinario, proyecto formativo..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                </div>

                {{-- Objetivo y Desarrollo --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Objetivo</label>
                    <textarea wire:model="new_objetivo" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Desarrollo</label>
                    <textarea wire:model="new_desarrollo" rows="4"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                </div>

                {{-- Compromisos estructurados --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-medium text-gray-600">Compromisos / Acuerdos</label>
                        <button type="button" wire:click="addNewCompromisoRow"
                                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+ Agregar fila</button>
                    </div>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-2 py-1.5 text-left font-medium text-gray-500 w-1/2">Descripción</th>
                                    <th class="px-2 py-1.5 text-left font-medium text-gray-500 w-1/4">Responsable</th>
                                    <th class="px-2 py-1.5 text-left font-medium text-gray-500">Fecha límite</th>
                                    <th class="w-6"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($new_compromisos_rows as $idx => $row)
                                <tr>
                                    <td class="px-1 py-1">
                                        <input type="text" wire:model="new_compromisos_rows.{{ $idx }}.descripcion"
                                               placeholder="Descripción..."
                                               class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                    </td>
                                    <td class="px-1 py-1">
                                        <select wire:model="new_compromisos_rows.{{ $idx }}.responsable"
                                                class="w-full border border-gray-300 rounded px-1 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                            <option value="instructor_sena">Instructor SENA</option>
                                            <option value="docente_par">Docente Par</option>
                                            <option value="ambos">Ambos</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="date" wire:model="new_compromisos_rows.{{ $idx }}.fecha_limite"
                                               class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                    </td>
                                    <td class="px-1 py-1 text-center">
                                        @if($idx > 0)
                                        <button type="button" wire:click="removeNewCompromisoRow({{ $idx }})"
                                                class="text-red-400 hover:text-red-600 text-base leading-none">&times;</button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Observaciones --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Observaciones</label>
                    <textarea wire:model="new_observaciones" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="submit"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                        Crear Acta
                    </button>
                    <button type="button" @click="$wire.showCreateModal = false"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ── MODAL EDITAR ACTA ─────────────────────────────────────── --}}
    <div x-show="$wire.showEditModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="$wire.showEditModal = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 z-10 overflow-y-auto max-h-[92vh]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800">Editar Acta</h3>
                <button @click="$wire.showEditModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="saveActa" class="space-y-4">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">N° Acta *</label>
                        <input type="text" wire:model="editing_numero"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        @error('editing_numero') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo *</label>
                        <select wire:model="editing_tipo"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <option value="">Seleccionar...</option>
                            <option value="seguimiento">Seguimiento (GD-F-007)</option>
                            <option value="inicio_ficha">Inicio de Ficha</option>
                            <option value="visita_seguimiento">Visita de Seguimiento</option>
                            <option value="cierre">Cierre</option>
                            <option value="aprobacion_etapa_practica">Aprobación Etapa Práctica</option>
                        </select>
                        @error('editing_tipo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Institución</label>
                    <select wire:model.live="editing_institution_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Seleccionar...</option>
                        @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if(count($modal_groups) > 0 && $showEditModal)
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Fichas / Grupos * <span class="text-gray-400">(selecciona una o más)</span></label>
                    <div class="border border-gray-300 rounded-lg p-3 grid grid-cols-1 gap-1.5 max-h-40 overflow-y-auto">
                        @foreach($modal_groups as $grp)
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 rounded px-1">
                            <input type="checkbox" wire:model="editing_group_ids" value="{{ $grp->id }}"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-400">
                            <span class="text-sm text-gray-700">
                                @if($grp->ficha_number)<span class="font-mono text-xs text-indigo-600">{{ $grp->ficha_number }}</span> — @endif{{ $grp->name }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                    @error('editing_group_ids') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Docente Par *</label>
                        <select wire:model="editing_docente_par_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <option value="">Seleccionar...</option>
                            @foreach($docentes_par as $dp)
                            <option value="{{ $dp->id }}">{{ $dp->name }}</option>
                            @endforeach
                        </select>
                        @error('editing_docente_par_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Competencia</label>
                        <select wire:model="editing_competencia_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <option value="">Ninguna</option>
                            @foreach($all_competencias as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->code ? '[' . $comp->code . '] ' : '' }}{{ $comp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Fecha *</label>
                        <input type="date" wire:model="editing_fecha"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        @error('editing_fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Hora inicio</label>
                        <input type="time" wire:model="editing_hora_inicio"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Hora fin</label>
                        <input type="time" wire:model="editing_hora_fin"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Lugar *</label>
                    <input type="text" wire:model="editing_lugar"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('editing_lugar') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Agenda / Orden del día</label>
                    <textarea wire:model="editing_agenda" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Objetivo</label>
                    <textarea wire:model="editing_objetivo" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Desarrollo</label>
                    <textarea wire:model="editing_desarrollo" rows="4"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                </div>

                {{-- Compromisos --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-medium text-gray-600">Compromisos / Acuerdos</label>
                        <button type="button" wire:click="addEditingCompromisoRow"
                                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+ Agregar fila</button>
                    </div>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-2 py-1.5 text-left font-medium text-gray-500 w-1/2">Descripción</th>
                                    <th class="px-2 py-1.5 text-left font-medium text-gray-500 w-1/4">Responsable</th>
                                    <th class="px-2 py-1.5 text-left font-medium text-gray-500">Fecha límite</th>
                                    <th class="w-6"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($editing_compromisos_rows as $idx => $row)
                                <tr>
                                    <td class="px-1 py-1">
                                        <input type="text" wire:model="editing_compromisos_rows.{{ $idx }}.descripcion"
                                               class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                    </td>
                                    <td class="px-1 py-1">
                                        <select wire:model="editing_compromisos_rows.{{ $idx }}.responsable"
                                                class="w-full border border-gray-300 rounded px-1 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                            <option value="instructor_sena">Instructor SENA</option>
                                            <option value="docente_par">Docente Par</option>
                                            <option value="ambos">Ambos</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="date" wire:model="editing_compromisos_rows.{{ $idx }}.fecha_limite"
                                               class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                    </td>
                                    <td class="px-1 py-1 text-center">
                                        @if($idx > 0)
                                        <button type="button" wire:click="removeEditingCompromisoRow({{ $idx }})"
                                                class="text-red-400 hover:text-red-600 text-base leading-none">&times;</button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Observaciones</label>
                    <textarea wire:model="editing_observaciones" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="submit"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                        Guardar
                    </button>
                    <button type="button" @click="$wire.showEditModal = false"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ── MODAL EDITAR DOCENTE PAR ──────────────────────────────── --}}
    <div x-show="$wire.showEditDpModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="$wire.showEditDpModal = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 z-10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800">Editar Docente Par</h3>
                <button @click="$wire.showEditDpModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="saveDocentePar" class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nombre completo *</label>
                    <input type="text" wire:model="editing_dp_name"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('editing_dp_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Documento</label>
                    <input type="text" wire:model="editing_dp_document"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Cargo</label>
                    <input type="text" wire:model="editing_dp_position"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Correo electrónico</label>
                    <input type="email" wire:model="editing_dp_email"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Institución articulación</label>
                    <input type="text" wire:model="editing_dp_institution"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="submit"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                        Guardar
                    </button>
                    <button type="button" @click="$wire.showEditDpModal = false"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
