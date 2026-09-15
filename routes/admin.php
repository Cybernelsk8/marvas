<?php

use App\Livewire\Admin\Areas;
use App\Livewire\Admin\Pages;
use App\Livewire\Admin\Permissions;
use App\Livewire\Admin\Roles;
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
