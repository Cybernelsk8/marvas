<?php

use Illuminate\Support\Facades\Route;

Route::prefix('servicios')->group(function () {
    Route::view('homeopatia', 'landing-page.servicios.homeopatia')->name('servicios.homeopatia');
    Route::view('iridiologia', 'landing-page.servicios.iridiologia')->name('servicios.iridiologia');
    Route::view('naturopatia', 'landing-page.servicios.naturopatia')->name('servicios.naturopatia');
    Route::view('quiropraxia', 'landing-page.servicios.quiropraxia')->name('servicios.quiropraxia');
    Route::view('masaje-muscular', 'landing-page.servicios.masaje-muscular')->name('servicios.masaje-muscular');
    Route::view('acupuntura', 'landing-page.servicios.acupuntura')->name('servicios.acupuntura');
});
