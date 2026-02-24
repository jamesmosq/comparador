<?php

use Livewire\Volt\Component;
use App\Models\ProgramaFormacion;
use App\Models\Competencia;
use App\Models\ResultadoAprendizaje;

new class extends Component
{
    public $programas    = [];
    public $competencias = [];
    public $resultados   = [];

    public $selected_programa_id    = null;
    public $selected_competencia_id = null;

    // ── Crear programa ──────────────────────────────────────
    public string $new_prog_name        = '';
    public string $new_prog_code        = '';
    public string $new_prog_description = '';

    // ── Crear competencia ───────────────────────────────────
    public string $new_comp_code  = '';
    public string $new_comp_name  = '';
    public string $new_comp_hours = '';

    // ── Crear resultado de aprendizaje ──────────────────────
    public string $new_ra_code = '';
    public string $new_ra_name = '';

    // ── Editar programa (modal) ─────────────────────────────
    public $editing_prog_id              = null;
    public string $editing_prog_name     = '';
    public string $editing_prog_code     = '';
    public string $editing_prog_description = '';
    public bool $showEditProgModal        = false;

    // ── Editar competencia (modal) ──────────────────────────
    public $editing_comp_id             = null;
    public string $editing_comp_code    = '';
    public string $editing_comp_name    = '';
    public string $editing_comp_hours   = '';
    public bool $showEditCompModal       = false;

    // ── Editar resultado de aprendizaje (modal) ─────────────
    public $editing_ra_id             = null;
    public string $editing_ra_code    = '';
    public string $editing_ra_name    = '';
    public bool $showEditRaModal       = false;

    public function mount(): void
    {
        $this->loadProgramas();
    }

    public function loadProgramas(): void
    {
        $this->programas = ProgramaFormacion::visibleTo(auth()->user())
            ->withCount('competencias')
            ->orderBy('name')
            ->get();
    }

    public function loadCompetencias(): void
    {
        $this->competencias = $this->selected_programa_id
            ? Competencia::where('programa_formacion_id', $this->selected_programa_id)
                ->withCount('resultadosAprendizaje')
                ->orderBy('order')
                ->get()
            : [];
    }

    public function loadResultados(): void
    {
        $this->resultados = $this->selected_competencia_id
            ? ResultadoAprendizaje::where('competencia_id', $this->selected_competencia_id)
                ->orderBy('order')
                ->get()
            : [];
    }

    public function selectPrograma($id): void
    {
        $this->selected_programa_id    = $id;
        $this->selected_competencia_id = null;
        $this->resultados              = [];
        $this->loadCompetencias();
    }

    public function selectCompetencia($id): void
    {
        $this->selected_competencia_id = $id;
        $this->loadResultados();
    }

    // ── PROGRAMAS ────────────────────────────────────────────

    public function addPrograma(): void
    {
        $this->validate([
            'new_prog_name' => 'required|min:3',
        ], ['new_prog_name.required' => 'El nombre del programa es obligatorio.']);

        ProgramaFormacion::create([
            'user_id'     => auth()->id(),
            'name'        => trim($this->new_prog_name),
            'code'        => trim($this->new_prog_code) ?: null,
            'description' => trim($this->new_prog_description) ?: null,
        ]);

        $this->reset(['new_prog_name', 'new_prog_code', 'new_prog_description']);
        $this->loadProgramas();
        session()->flash('success', 'Programa creado correctamente.');
    }

    public function openEditPrograma($id): void
    {
        $prog = ProgramaFormacion::visibleTo(auth()->user())->findOrFail($id);
        $this->editing_prog_id          = $id;
        $this->editing_prog_name        = $prog->name;
        $this->editing_prog_code        = $prog->code ?? '';
        $this->editing_prog_description = $prog->description ?? '';
        $this->showEditProgModal        = true;
    }

    public function savePrograma(): void
    {
        $this->validate(['editing_prog_name' => 'required|min:3']);

        ProgramaFormacion::visibleTo(auth()->user())->findOrFail($this->editing_prog_id)->update([
            'name'        => trim($this->editing_prog_name),
            'code'        => trim($this->editing_prog_code) ?: null,
            'description' => trim($this->editing_prog_description) ?: null,
        ]);

        $this->showEditProgModal = false;
        $this->loadProgramas();
        session()->flash('success', 'Programa actualizado.');
    }

    public function deletePrograma($id): void
    {
        ProgramaFormacion::visibleTo(auth()->user())->findOrFail($id)->delete();
        if ($this->selected_programa_id == $id) {
            $this->selected_programa_id    = null;
            $this->selected_competencia_id = null;
            $this->competencias            = [];
            $this->resultados              = [];
        }
        $this->loadProgramas();
        session()->flash('success', 'Programa eliminado.');
    }

    // ── COMPETENCIAS ─────────────────────────────────────────

    public function addCompetencia(): void
    {
        $this->validate([
            'new_comp_name'  => 'required|min:3',
            'new_comp_hours' => 'nullable|integer|min:1',
        ], [
            'new_comp_name.required' => 'El nombre de la competencia es obligatorio.',
            'new_comp_hours.integer' => 'Las horas deben ser un número entero.',
        ]);

        $maxOrder = Competencia::where('programa_formacion_id', $this->selected_programa_id)->max('order') ?? 0;

        Competencia::create([
            'programa_formacion_id' => $this->selected_programa_id,
            'code'                  => trim($this->new_comp_code) ?: null,
            'name'                  => trim($this->new_comp_name),
            'total_hours'           => (int)$this->new_comp_hours ?: 0,
            'order'                 => $maxOrder + 1,
        ]);

        $this->reset(['new_comp_code', 'new_comp_name', 'new_comp_hours']);
        $this->loadCompetencias();
        session()->flash('success', 'Competencia añadida.');
    }

    public function openEditCompetencia($id): void
    {
        $comp = Competencia::where('programa_formacion_id', $this->selected_programa_id)->findOrFail($id);
        $this->editing_comp_id    = $id;
        $this->editing_comp_code  = $comp->code ?? '';
        $this->editing_comp_name  = $comp->name;
        $this->editing_comp_hours = (string)$comp->total_hours;
        $this->showEditCompModal  = true;
    }

    public function saveCompetencia(): void
    {
        $this->validate([
            'editing_comp_name'  => 'required|min:3',
            'editing_comp_hours' => 'nullable|integer|min:0',
        ]);

        Competencia::where('programa_formacion_id', $this->selected_programa_id)
            ->findOrFail($this->editing_comp_id)
            ->update([
                'code'        => trim($this->editing_comp_code) ?: null,
                'name'        => trim($this->editing_comp_name),
                'total_hours' => (int)$this->editing_comp_hours ?: 0,
            ]);

        $this->showEditCompModal = false;
        $this->loadCompetencias();
        session()->flash('success', 'Competencia actualizada.');
    }

    public function deleteCompetencia($id): void
    {
        Competencia::where('programa_formacion_id', $this->selected_programa_id)
            ->findOrFail($id)
            ->delete();

        if ($this->selected_competencia_id == $id) {
            $this->selected_competencia_id = null;
            $this->resultados              = [];
        }
        $this->loadCompetencias();
        session()->flash('success', 'Competencia eliminada.');
    }

    // ── RESULTADOS DE APRENDIZAJE ─────────────────────────────

    public function addResultado(): void
    {
        $this->validate([
            'new_ra_name' => 'required|min:3',
        ], ['new_ra_name.required' => 'El nombre del resultado de aprendizaje es obligatorio.']);

        $maxOrder = ResultadoAprendizaje::where('competencia_id', $this->selected_competencia_id)->max('order') ?? 0;

        ResultadoAprendizaje::create([
            'competencia_id' => $this->selected_competencia_id,
            'code'           => trim($this->new_ra_code) ?: null,
            'name'           => trim($this->new_ra_name),
            'order'          => $maxOrder + 1,
        ]);

        $this->reset(['new_ra_code', 'new_ra_name']);
        $this->loadResultados();
        $this->loadCompetencias(); // refresh RA count
        session()->flash('success', 'Resultado de Aprendizaje añadido.');
    }

    public function openEditResultado($id): void
    {
        $ra = ResultadoAprendizaje::where('competencia_id', $this->selected_competencia_id)->findOrFail($id);
        $this->editing_ra_id   = $id;
        $this->editing_ra_code = $ra->code ?? '';
        $this->editing_ra_name = $ra->name;
        $this->showEditRaModal = true;
    }

    public function saveResultado(): void
    {
        $this->validate([
            'editing_ra_name' => 'required|min:3',
        ], ['editing_ra_name.required' => 'El nombre es obligatorio.']);

        ResultadoAprendizaje::where('competencia_id', $this->selected_competencia_id)
            ->findOrFail($this->editing_ra_id)
            ->update([
                'code' => trim($this->editing_ra_code) ?: null,
                'name' => trim($this->editing_ra_name),
            ]);

        $this->showEditRaModal = false;
        $this->loadResultados();
        session()->flash('success', 'Resultado de Aprendizaje actualizado.');
    }

    public function deleteResultado($id): void
    {
        ResultadoAprendizaje::where('competencia_id', $this->selected_competencia_id)
            ->findOrFail($id)
            ->delete();
        $this->loadResultados();
        $this->loadCompetencias(); // refresh RA count
        session()->flash('success', 'Resultado de Aprendizaje eliminado.');
    }
};
?>

<div class="max-w-7xl mx-auto px-4 pb-12">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Programas de Formación</h1>
        <p class="text-sm text-gray-500 mt-1">
            Define los programas, sus competencias y los resultados de aprendizaje de cada competencia.
        </p>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- PANEL 1: Programas de Formación                               --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="space-y-4">

            {{-- Crear programa --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Nuevo programa</h2>
                <form wire:submit.prevent="addPrograma" class="space-y-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nombre del programa *</label>
                        <input type="text" wire:model="new_prog_name"
                               placeholder="Ej: Análisis y Desarrollo de Software"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        @error('new_prog_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Código</label>
                        <input type="text" wire:model="new_prog_code" placeholder="Ej: 228118"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Descripción</label>
                        <textarea wire:model="new_prog_description" rows="2" placeholder="Opcional..."
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                    </div>
                    <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                        Crear programa
                    </button>
                </form>
            </div>

            {{-- Lista de programas --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">Programas</h2>
                    <span class="text-xs bg-indigo-100 text-indigo-700 font-semibold px-2 py-0.5 rounded-full">
                        {{ count($programas) }}
                    </span>
                </div>

                @if(count($programas) === 0)
                <div class="px-5 py-8 text-center text-gray-400 text-sm">No hay programas registrados aún.</div>
                @else
                <div class="divide-y divide-gray-100">
                    @foreach($programas as $prog)
                    <div x-data="{ confirmDelete: false }"
                         class="px-4 py-3 {{ $selected_programa_id == $prog->id ? 'bg-indigo-50 border-l-4 border-indigo-500' : 'hover:bg-gray-50' }} transition-all">
                        <div class="flex items-start justify-between gap-2">
                            <button type="button" wire:click="selectPrograma({{ $prog->id }})" class="flex-1 text-left min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $prog->name }}</p>
                                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                    @if($prog->code)
                                    <span class="text-xs text-gray-400 font-mono">{{ $prog->code }}</span>
                                    @endif
                                    <span class="text-xs {{ $prog->competencias_count > 0 ? 'text-indigo-600' : 'text-gray-400' }} font-medium">
                                        {{ $prog->competencias_count }} comp.
                                    </span>
                                </div>
                            </button>
                            <div class="flex items-center gap-1 flex-shrink-0" x-show="!confirmDelete">
                                <button wire:click="openEditPrograma({{ $prog->id }})"
                                        class="text-xs text-indigo-600 hover:text-indigo-800 p-1 rounded hover:bg-indigo-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button @click="confirmDelete = true"
                                        class="text-xs text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-1" x-show="confirmDelete" x-cloak>
                                <span class="text-xs text-gray-500">¿Eliminar?</span>
                                <button wire:click="deletePrograma({{ $prog->id }})"
                                        class="text-xs bg-red-600 text-white px-2 py-0.5 rounded hover:bg-red-700">Sí</button>
                                <button @click="confirmDelete = false"
                                        class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded hover:bg-gray-300">No</button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- PANEL 2: Competencias                                         --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div>
            @if($selected_programa_id)
            @php $progActivo = collect($programas)->firstWhere('id', $selected_programa_id); @endphp

            <div class="bg-white rounded-xl shadow-sm border border-indigo-200 overflow-hidden">
                <div class="px-5 py-3 bg-indigo-50 border-b border-indigo-100">
                    <h2 class="text-sm font-semibold text-indigo-800">Competencias</h2>
                    <p class="text-xs text-indigo-500 truncate mt-0.5">{{ $progActivo?->name }}</p>
                </div>

                {{-- Crear competencia --}}
                <div class="p-4 border-b border-gray-100">
                    <form wire:submit.prevent="addCompetencia" class="space-y-2">
                        <div class="grid grid-cols-4 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Código</label>
                                <input type="text" wire:model="new_comp_code" placeholder="240201501"
                                       class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-green-400">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Nombre *</label>
                                <input type="text" wire:model="new_comp_name"
                                       placeholder="Ej: Construir módulos de software"
                                       class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-green-400">
                                @error('new_comp_name') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Horas</label>
                                <input type="number" wire:model="new_comp_hours" placeholder="240" min="1"
                                       class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-green-400">
                            </div>
                        </div>
                        <button type="submit"
                                class="w-full bg-green-600 hover:bg-green-700 text-white text-xs font-medium py-1.5 rounded-lg transition-colors">
                            Añadir competencia
                        </button>
                    </form>
                </div>

                {{-- Lista de competencias --}}
                @if(count($competencias) === 0)
                <div class="px-5 py-8 text-center text-gray-400 text-sm">
                    Este programa aún no tiene competencias.
                </div>
                @else
                <div class="divide-y divide-gray-100">
                    @foreach($competencias as $comp)
                    <div x-data="{ confirmDelete: false }"
                         class="px-4 py-3 {{ $selected_competencia_id == $comp->id ? 'bg-green-50 border-l-4 border-green-500' : 'hover:bg-gray-50' }} transition-all">
                        <div class="flex items-start justify-between gap-2">
                            {{-- Click para seleccionar y ver RAs --}}
                            <button type="button" wire:click="selectCompetencia({{ $comp->id }})" class="flex-1 text-left min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($comp->code)
                                    <span class="text-xs font-mono bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">{{ $comp->code }}</span>
                                    @endif
                                    <span class="text-sm text-gray-800 font-medium leading-tight">{{ $comp->name }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    @if($comp->total_hours > 0)
                                    <span class="text-xs text-gray-400">{{ $comp->total_hours }}h</span>
                                    @endif
                                    <span class="text-xs {{ $comp->resultados_aprendizaje_count > 0 ? 'text-green-600' : 'text-gray-400' }} font-medium">
                                        {{ $comp->resultados_aprendizaje_count }} RA(s)
                                    </span>
                                </div>
                            </button>
                            <div class="flex items-center gap-1 flex-shrink-0" x-show="!confirmDelete">
                                <button wire:click="openEditCompetencia({{ $comp->id }})"
                                        class="text-xs text-indigo-600 hover:text-indigo-800 p-1 rounded hover:bg-indigo-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button @click="confirmDelete = true"
                                        class="text-xs text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-1" x-show="confirmDelete" x-cloak>
                                <span class="text-xs text-gray-500">¿Eliminar?</span>
                                <button wire:click="deleteCompetencia({{ $comp->id }})"
                                        class="text-xs bg-red-600 text-white px-2 py-0.5 rounded">Sí</button>
                                <button @click="confirmDelete = false"
                                        class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded">No</button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex items-center justify-center h-48">
                <div class="text-center text-gray-400 px-6">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm">Selecciona un programa para ver sus competencias</p>
                </div>
            </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- PANEL 3: Resultados de Aprendizaje                            --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div>
            @if($selected_competencia_id)
            @php $compActiva = collect($competencias)->firstWhere('id', $selected_competencia_id); @endphp

            <div class="bg-white rounded-xl shadow-sm border border-green-200 overflow-hidden">
                <div class="px-5 py-3 bg-green-50 border-b border-green-100">
                    <h2 class="text-sm font-semibold text-green-800">Resultados de Aprendizaje</h2>
                    <p class="text-xs text-green-600 truncate mt-0.5">{{ $compActiva?->name }}</p>
                </div>

                {{-- Crear RA --}}
                <div class="p-4 border-b border-gray-100">
                    <form wire:submit.prevent="addResultado" class="space-y-2">
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Código</label>
                                <input type="text" wire:model="new_ra_code" placeholder="Ej: RA1"
                                       class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-teal-400">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Resultado de Aprendizaje *</label>
                                <input type="text" wire:model="new_ra_name"
                                       placeholder="Ej: Identificar los requerimientos del cliente"
                                       class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-teal-400">
                                @error('new_ra_name') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <button type="submit"
                                class="w-full bg-teal-600 hover:bg-teal-700 text-white text-xs font-medium py-1.5 rounded-lg transition-colors">
                            Añadir resultado de aprendizaje
                        </button>
                    </form>
                </div>

                {{-- Lista de RAs --}}
                @if(count($resultados) === 0)
                <div class="px-5 py-8 text-center text-gray-400 text-sm">
                    Esta competencia aún no tiene resultados de aprendizaje.
                </div>
                @else
                <div class="divide-y divide-gray-100">
                    @foreach($resultados as $ra)
                    <div x-data="{ confirmDelete: false }" class="px-4 py-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    @if($ra->code)
                                    <span class="text-xs font-mono bg-teal-100 text-teal-700 px-1.5 py-0.5 rounded flex-shrink-0">{{ $ra->code }}</span>
                                    @endif
                                    <p class="text-sm text-gray-800 leading-snug">{{ $ra->name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0" x-show="!confirmDelete">
                                <button wire:click="openEditResultado({{ $ra->id }})"
                                        class="text-xs text-indigo-600 hover:text-indigo-800 p-1 rounded hover:bg-indigo-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button @click="confirmDelete = true"
                                        class="text-xs text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-1" x-show="confirmDelete" x-cloak>
                                <span class="text-xs text-gray-500">¿Eliminar?</span>
                                <button wire:click="deleteResultado({{ $ra->id }})"
                                        class="text-xs bg-red-600 text-white px-2 py-0.5 rounded">Sí</button>
                                <button @click="confirmDelete = false"
                                        class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded">No</button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex items-center justify-center h-48">
                <div class="text-center text-gray-400 px-6">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-sm">Selecciona una competencia para ver sus resultados de aprendizaje</p>
                </div>
            </div>
            @endif
        </div>

    </div>{{-- /grid --}}

    {{-- ── Modal editar programa ──────────────────────────────────────── --}}
    <div x-show="$wire.showEditProgModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="$wire.showEditProgModal = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 z-10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800">Editar programa</h3>
                <button @click="$wire.showEditProgModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="savePrograma" class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nombre *</label>
                    <input type="text" wire:model="editing_prog_name"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('editing_prog_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Código</label>
                    <input type="text" wire:model="editing_prog_code"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Descripción</label>
                    <textarea wire:model="editing_prog_description" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="submit"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                        Guardar
                    </button>
                    <button type="button" @click="$wire.showEditProgModal = false"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Modal editar competencia ────────────────────────────────────── --}}
    <div x-show="$wire.showEditCompModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="$wire.showEditCompModal = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 z-10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800">Editar competencia</h3>
                <button @click="$wire.showEditCompModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="saveCompetencia" class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Código</label>
                    <input type="text" wire:model="editing_comp_code"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nombre *</label>
                    <input type="text" wire:model="editing_comp_name"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    @error('editing_comp_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Total de horas</label>
                    <input type="number" wire:model="editing_comp_hours" min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    @error('editing_comp_hours') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="submit"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                        Guardar
                    </button>
                    <button type="button" @click="$wire.showEditCompModal = false"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Modal editar resultado de aprendizaje ───────────────────────── --}}
    <div x-show="$wire.showEditRaModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="$wire.showEditRaModal = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 z-10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800">Editar Resultado de Aprendizaje</h3>
                <button @click="$wire.showEditRaModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="saveResultado" class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Código</label>
                    <input type="text" wire:model="editing_ra_code" placeholder="Ej: RA1"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Resultado de Aprendizaje *</label>
                    <textarea wire:model="editing_ra_name" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400 resize-none"></textarea>
                    @error('editing_ra_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="submit"
                            class="flex-1 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                        Guardar
                    </button>
                    <button type="button" @click="$wire.showEditRaModal = false"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
