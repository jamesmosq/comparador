<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ActaController;

// Rutas solo para invitados (redirige autenticados al dashboard)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Rutas protegidas
Route::middleware('auth')->group(function () {
    Route::get('/programas', function () {
        return view('programas');
    })->name('programas');
    Route::get('/', function () {
        return view('dashboard');
    })->name('home');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/horarios', function () {
        return view('horarios');
    })->name('horarios');

    Route::get('/asistencia', function () {
        return view('asistencia');
    })->name('asistencia');

    Route::get('/reportes', function () {
        return view('reportes');
    })->name('reportes');

    Route::get('/documentacion', function () {
        return view('documentacion');
    })->name('documentacion');

    Route::get('/actas/{acta}/preview',       [ActaController::class, 'preview'])->name('actas.preview');
    Route::get('/actas/{acta}/exportar/word', [ActaController::class, 'exportWord'])->name('actas.export.word');
    Route::get('/actas/{acta}/exportar/pdf',  [ActaController::class, 'exportPdf'])->name('actas.export.pdf');
});

// Gestión de usuarios (solo admin)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/usuarios', function () {
        return view('usuarios');
    })->name('usuarios');
});

// Impresión
Route::get('/imprimir/asistencia', [PrintController::class, 'attendance'])
    ->middleware('auth')
    ->name('imprimir.asistencia');

// Exportación
Route::get('/exportar/reporte', [ExportController::class, 'report'])
    ->middleware('auth')
    ->name('export.report');

// Logout
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
