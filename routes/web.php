<?php

use App\Livewire\Solicitud;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__ . '/settings.php';


// SOLICITUDES DE CITA Y LLENADO DE FORMULARIO DE PACIENTE


Route::livewire('solicitudes', Solicitud::class)->name('solicitudes');
