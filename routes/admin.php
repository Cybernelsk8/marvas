<?php

use App\Livewire\Admin\Areas;
use App\Livewire\Admin\Availability;
use App\Livewire\Admin\Consultation\Show as ConsultationShow;
use App\Livewire\Admin\Clinical\BodyRegions;
use App\Livewire\Admin\Clinical\Branches;
use App\Livewire\Admin\Clinical\Professionals;
use App\Livewire\Admin\Clinical\Specialties;
use App\Livewire\Admin\Pages;
use App\Livewire\Admin\Patient\Index as PatientIndex;
use App\Livewire\Admin\Patient\Show as PatientShow;
use App\Livewire\Admin\Permissions;
use App\Livewire\Admin\Roles;
use App\Livewire\Admin\Schedule;
use App\Livewire\Admin\User\Index;
use App\Livewire\Admin\User\Show;
use Illuminate\Support\Facades\Route;

Route::livewire('users', Index::class)
    ->middleware(['can:page.view.users'])
    ->name('admin.users.index');

Route::livewire('users/{user}', Show::class)
    ->middleware(['can:page.view.users'])
    ->name('admin.users.show');

Route::livewire('pages', Pages::class)
    ->middleware(['can:page.view.pages'])
    ->name('admin.pages');

Route::livewire('roles', Roles::class)
    ->middleware(['can:page.view.roles'])
    ->name('admin.roles');

Route::livewire('permissions', Permissions::class)
    ->middleware(['can:page.view.permissions'])
    ->name('admin.permissions');

Route::livewire('areas', Areas::class)
    ->middleware(['can:page.view.areas'])
    ->name('admin.areas');

Route::livewire('availability', Availability::class)
    ->middleware(['can:page.view.schedule'])
    ->name('admin.availability');

Route::livewire('schedule', Schedule::class)
    ->middleware(['can:page.view.schedule'])
    ->name('admin.schedule');

Route::livewire('branches', Branches::class)
    ->middleware(['can:page.view.branches'])
    ->name('admin.branches');

Route::livewire('specialties', Specialties::class)
    ->middleware(['can:page.view.specialties'])
    ->name('admin.specialties');

Route::livewire('body-regions', BodyRegions::class)
    ->middleware(['can:page.view.body-regions'])
    ->name('admin.body-regions');

Route::livewire('professionals', Professionals::class)
    ->middleware(['can:page.view.professionals'])
    ->name('admin.professionals');

Route::livewire('patients', PatientIndex::class)
    ->middleware(['can:page.view.patients'])
    ->name('admin.patients.index');

Route::livewire('patients/{patient}', PatientShow::class)
    ->middleware(['can:page.view.patients'])
    ->name('admin.patients.show');

Route::livewire('consultations/{consultation}', ConsultationShow::class)
    ->name('admin.consultations.show');
