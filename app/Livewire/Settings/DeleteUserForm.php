<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Gestiona la validación y eliminación de la cuenta autenticada.
 */
class DeleteUserForm extends Component
{
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     * @param Logout $logout Dato recibido por el componente.
     * @return void Valida la contraseña actual antes de eliminar la cuenta autenticada.
     */
    // Valida la contraseña actual antes de eliminar la cuenta autenticada.
    //   Desde la vista: <flux:button wire:click="deleteUser">Eliminar cuenta</flux:button>
    //   Resultado: cuenta eliminada, sesión cerrada y navegación a la página pública.
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        Auth::user()->delete();
        $logout();

        $this->redirect('/', navigate: true);
    }
}
