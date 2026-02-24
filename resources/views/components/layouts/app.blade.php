<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ?? config('app.name') }}</title>
        <style>[x-cloak] { display: none !important; }</style>
        <script src="https://cdn.tailwindcss.com"></script>
        @livewireStyles
    </head>
    <body class="bg-gray-100">
        <nav class="bg-white shadow-sm mb-8" x-data="{ mobileOpen: false }">
            <div class="max-w-5xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <!-- Izquierda: marca + enlaces -->
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('dashboard') }}" class="font-bold text-xl text-indigo-600 flex-shrink-0 mr-4">
                            {{ config('app.name') }}
                        </a>
                        <div class="hidden sm:flex sm:space-x-1">
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors
                                      {{ request()->routeIs('dashboard') || request()->routeIs('home')
                                         ? 'bg-indigo-50 text-indigo-700'
                                         : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('horarios') }}"
                               class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors
                                      {{ request()->routeIs('horarios')
                                         ? 'bg-indigo-50 text-indigo-700'
                                         : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                Horarios
                            </a>
                            <a href="{{ route('programas') }}"
                               class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors
                                      {{ request()->routeIs('programas')
                                         ? 'bg-indigo-50 text-indigo-700'
                                         : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                Programas
                            </a>
                            <a href="{{ route('asistencia') }}"
                               class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors
                                      {{ request()->routeIs('asistencia')
                                         ? 'bg-indigo-50 text-indigo-700'
                                         : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                Asistencia
                            </a>
                            <a href="{{ route('reportes') }}"
                               class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors
                                      {{ request()->routeIs('reportes')
                                         ? 'bg-indigo-50 text-indigo-700'
                                         : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                Reportes
                            </a>
                            <a href="{{ route('documentacion') }}"
                               class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors
                                      {{ request()->routeIs('documentacion')
                                         ? 'bg-indigo-50 text-indigo-700'
                                         : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                Documentación
                            </a>
                            @auth
                            @if(auth()->user()->isAdmin())
                            <a href="{{ route('usuarios') }}"
                               class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors
                                      {{ request()->routeIs('usuarios')
                                         ? 'bg-indigo-50 text-indigo-700'
                                         : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                Usuarios
                            </a>
                            @endif
                            @endauth
                        </div>
                    </div>

                    <!-- Derecha: usuario + logout -->
                    <div class="flex items-center gap-3">
                        @auth
                        <span class="text-sm text-gray-600 hidden sm:block font-medium">
                            {{ auth()->user()->name }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="text-sm font-medium text-gray-500 hover:text-red-600 transition-colors px-2 py-1 rounded">
                                Cerrar sesión
                            </button>
                        </form>
                        @endauth

                        <!-- Botón menú móvil -->
                        <button @click="mobileOpen = !mobileOpen"
                                class="sm:hidden p-2 rounded-md text-gray-500 hover:text-gray-900 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Menú móvil -->
            <div x-show="mobileOpen" x-cloak class="sm:hidden border-t border-gray-100">
                <div class="px-4 py-2 space-y-1">
                    <a href="{{ route('dashboard') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium
                              {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('horarios') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium
                              {{ request()->routeIs('horarios') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        Horarios
                    </a>
                    <a href="{{ route('programas') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium
                              {{ request()->routeIs('programas') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        Programas
                    </a>
                    <a href="{{ route('asistencia') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium
                              {{ request()->routeIs('asistencia') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        Asistencia
                    </a>
                    <a href="{{ route('reportes') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium
                              {{ request()->routeIs('reportes') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        Reportes
                    </a>
                    <a href="{{ route('documentacion') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium
                              {{ request()->routeIs('documentacion') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        Documentación
                    </a>
                    @auth
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('usuarios') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium
                              {{ request()->routeIs('usuarios') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        Usuarios
                    </a>
                    @endif
                    @endauth
                </div>
            </div>
        </nav>

        {{ $slot }}

        @livewireScripts
    </body>
</html>
