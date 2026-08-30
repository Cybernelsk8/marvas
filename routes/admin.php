<?php

use App\Livewire\Admin\Areas;
use App\Livewire\Admin\Pages;
use App\Livewire\Admin\Permissions;
use App\Livewire\Admin\Roles;
use App\Livewire\Admin\User\Index;
use App\Livewire\Admin\User\Show;
use Illuminate\Support\Facades\Route;

Route::get('users', Index::class)
    ->middleware(['can:page.view.users'])
    ->name('admin.users.index');

Route::get('users/{user}', Show::class)
    ->middleware(['can:page.view.users'])
    ->name('admin.users.show');

Route::get('pages', Pages::class)
    ->middleware(['can:page.view.pages'])
    ->name('admin.pages');

Route::get('roles', Roles::class)
    ->middleware(['can:page.view.roles'])
    ->name('admin.roles');

Route::get('permissions', Permissions::class)
    ->middleware(['can:page.view.permissions'])
    ->name('admin.permissions');

Route::get('areas', Areas::class)
    ->middleware(['can:page.view.areas'])
    ->name('admin.areas');
