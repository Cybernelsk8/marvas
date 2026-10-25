<?php

namespace App\Livewire\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Encapsula el cierre de sesión y la invalidación de la sesión web.
 */
class Logout
{

    /**
     * Log the current user out of the application.
     * @return Redirector|RedirectResponse Cierra la sesión web, invalida los datos almacenados y regenera el token CSRF.
     */
    // Cierra la sesión web, invalida los datos almacenados y regenera el token CSRF.
    //   Ejemplo: app(Logout::class)() al elegir “Cerrar sesión”.
    //   Resultado: redirección a / sin conservar la autenticación anterior.
    public function __invoke(): Redirector|RedirectResponse
    {
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        return redirect('/');
    }
}
