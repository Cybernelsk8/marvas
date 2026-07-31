<?php

use Illuminate\Support\Facades\Route;

Route::prefix('servicios')->group(function () {
    Route::view('homeopatia', 'landing-page.servicios.homeopatia')->name('servicios.homeopatia');
});
