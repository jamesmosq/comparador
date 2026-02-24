<?php
use function Livewire\Volt\{state, mount};
use App\Models\User;
use Illuminate\Support\Facades\Hash;

state([
    'teachers'                    => [],
    // Formulario crear
    'new_name'                    => '',
    'new_email'                   => '',
    'new_password'                => '',
    'new_password_confirmation'   => '',
    // Modal editar
    'editing_id'                  => null,
    'editing_name'                => '',
    'editing_email'               => '',
    'editing_password'            => '',
    'editing_password_confirmation' => '',
    'showEditModal'               => false,
]);

mount(function () {
    $this->loadTeachers();
});

$loadTeachers = function () {
    $this->teachers = User::where('role', 'teacher')->orderBy('name')->get();
};

$addTeacher = function () {
    $this->validate([
        'new_name'     => 'required|string|max:255',
        'new_email'    => 'required|email|unique:users,email',
        'new_password' => 'required|min:6|confirmed',
    ], [
        'new_name.required'      => 'El nombre es obligatorio.',
        'new_email.required'     => 'El correo es obligatorio.',
        'new_email.email'        => 'Ingresa un correo válido.',
        'new_email.unique'       => 'Este correo ya está registrado.',
        'new_password.required'  => 'La contraseña es obligatoria.',
        'new_password.min'       => 'La contraseña debe tener al menos 6 caracteres.',
        'new_password.confirmed' => 'Las contraseñas no coinciden.',
    ]);

    User::create([
        'name'     => trim($this->new_name),
        'email'    => trim($this->new_email),
        'password' => Hash::make($this->new_password),
        'role'     => 'teacher',
    ]);

    $this->reset(['new_name', 'new_email', 'new_password', 'new_password_confirmation']);
    $this->loadTeachers();
    session()->flash('success', 'Profesor creado exitosamente.');
};

$openEdit = function (int $id) {
    $user = User::where('id', $id)->where('role', 'teacher')->firstOrFail();
    $this->editing_id                  = $user->id;
    $this->editing_name                = $user->name;
    $this->editing_email               = $user->email;
    $this->editing_password            = '';
    $this->editing_password_confirmation = '';
    $this->showEditModal               = true;
};

$saveEdit = function () {
    $rules = [
        'editing_name'  => 'required|string|max:255',
        'editing_email' => 'required|email|unique:users,email,' . $this->editing_id,
    ];
    $messages = [
        'editing_name.required'  => 'El nombre es obligatorio.',
        'editing_email.required' => 'El correo es obligatorio.',
        'editing_email.email'    => 'Ingresa un correo válido.',
        'editing_email.unique'   => 'Este correo ya está registrado.',
    ];

    if ($this->editing_password !== '') {
        $rules['editing_password']            = 'min:6|confirmed';
        $messages['editing_password.min']       = 'La contraseña debe tener al menos 6 caracteres.';
        $messages['editing_password.confirmed'] = 'Las contraseñas no coinciden.';
    }

    $this->validate($rules, $messages);

    $data = [
        'name'  => trim($this->editing_name),
        'email' => trim($this->editing_email),
    ];
    if ($this->editing_password !== '') {
        $data['password'] = Hash::make($this->editing_password);
    }

    User::where('id', $this->editing_id)->where('role', 'teacher')->update($data);
    $this->showEditModal = false;
    $this->loadTeachers();
    session()->flash('success', 'Profesor actualizado exitosamente.');
};

$deleteTeacher = function (int $id) {
    User::where('id', $id)->where('role', 'teacher')->delete();
    $this->loadTeachers();
    session()->flash('success', 'Profesor eliminado.');
};
?>

<div class="max-w-5xl mx-auto px-4 pb-12">

    <!-- Cabecera -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Gestión de Usuarios</h1>
        <p class="text-sm text-gray-500 mt-1">Crea y administra las cuentas de los profesores.</p>
    </div>

    <!-- Flash messages -->
    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ── Formulario crear profesor ──────────────────────────── -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-base font-semibold text-gray-700 mb-4">Nuevo profesor</h2>

                <form wire:submit.prevent="addTeacher" class="space-y-3">
                    <!-- Nombre -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nombre completo</label>
                        <input type="text" wire:model="new_name" placeholder="Ej. Juan Pérez"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        @error('new_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Correo -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Correo electrónico</label>
                        <input type="email" wire:model="new_email" placeholder="correo@ejemplo.com"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        @error('new_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Contraseña -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Contraseña</label>
                        <input type="password" wire:model="new_password" placeholder="Mínimo 6 caracteres"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        @error('new_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Confirmar contraseña -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Confirmar contraseña</label>
                        <input type="password" wire:model="new_password_confirmation" placeholder="Repite la contraseña"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>

                    <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors mt-1">
                        Crear profesor
                    </button>
                </form>
            </div>
        </div>

        <!-- ── Lista de profesores ────────────────────────────────── -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-700">Profesores registrados</h2>
                    <span class="text-xs bg-indigo-100 text-indigo-700 font-semibold px-2 py-0.5 rounded-full">
                        {{ count($teachers) }}
                    </span>
                </div>

                @if(count($teachers) === 0)
                <div class="px-5 py-10 text-center text-gray-400 text-sm">
                    No hay profesores registrados aún.
                </div>
                @else
                <div class="divide-y divide-gray-100">
                    @foreach($teachers as $teacher)
                    <div class="px-5 py-4 flex items-center justify-between gap-3"
                         x-data="{ confirmDelete: false }">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $teacher->name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $teacher->email }}</p>
                        </div>

                        <!-- Acciones normales -->
                        <div class="flex items-center gap-2" x-show="!confirmDelete">
                            <span class="text-xs bg-blue-100 text-blue-700 font-medium px-2 py-0.5 rounded-full">
                                Profesor
                            </span>
                            <button type="button"
                                    wire:click="openEdit({{ $teacher->id }})"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium px-2 py-1 rounded hover:bg-indigo-50 transition-colors">
                                Editar
                            </button>
                            <button type="button"
                                    @click="confirmDelete = true"
                                    class="text-xs text-red-500 hover:text-red-700 font-medium px-2 py-1 rounded hover:bg-red-50 transition-colors">
                                Eliminar
                            </button>
                        </div>

                        <!-- Confirmación de eliminar -->
                        <div class="flex items-center gap-2" x-show="confirmDelete" x-cloak>
                            <span class="text-xs text-gray-500">¿Eliminar a <strong>{{ $teacher->name }}</strong>?</span>
                            <button type="button"
                                    wire:click="deleteTeacher({{ $teacher->id }})"
                                    class="text-xs bg-red-600 hover:bg-red-700 text-white font-medium px-3 py-1 rounded transition-colors">
                                Sí, eliminar
                            </button>
                            <button type="button"
                                    @click="confirmDelete = false"
                                    class="text-xs text-gray-500 hover:text-gray-700 font-medium px-2 py-1 rounded hover:bg-gray-100 transition-colors">
                                Cancelar
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ── Modal editar profesor ──────────────────────────────────── -->
    <div x-show="$wire.showEditModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/40" @click="$wire.showEditModal = false"></div>

        <!-- Panel -->
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 z-10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800">Editar profesor</h3>
                <button type="button" @click="$wire.showEditModal = false"
                        class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            <form wire:submit.prevent="saveEdit" class="space-y-3">
                <!-- Nombre -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nombre completo</label>
                    <input type="text" wire:model="editing_name"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('editing_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Correo -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Correo electrónico</label>
                    <input type="email" wire:model="editing_email"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('editing_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Nueva contraseña (opcional) -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Nueva contraseña
                        <span class="text-gray-400 font-normal">(dejar en blanco para no cambiar)</span>
                    </label>
                    <input type="password" wire:model="editing_password" placeholder="Mínimo 6 caracteres"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('editing_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Confirmar contraseña -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Confirmar nueva contraseña</label>
                    <input type="password" wire:model="editing_password_confirmation"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="submit"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                        Guardar cambios
                    </button>
                    <button type="button"
                            @click="$wire.showEditModal = false"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
