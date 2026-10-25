<?php

namespace App\Livewire\Settings\TwoFactor;

use Exception;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Muestra y renueva los códigos de recuperación de dos factores.
 */
class RecoveryCodes extends Component
{
    /** @var list<string> */
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     * @return void Livewire lo ejecuta al mostrar el panel para leer los códigos disponibles.
     */
    // Livewire lo ejecuta al mostrar el panel para leer los códigos disponibles.
    //   Ejemplo: abrir el panel con dos factores habilitados.
    //   Resultado: recoveryCodes contiene la lista descifrada.
    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    /**
     * Generate new recovery codes for the user.
     * @param GenerateNewRecoveryCodes $generateNewRecoveryCodes Dato recibido por el componente.
     * @return void Renueva los códigos con Fortify y recarga la lista para mostrar la nueva tanda.
     */
    // Renueva los códigos con Fortify y recarga la lista para mostrar la nueva tanda.
    //   Desde la vista: <flux:button wire:click="regenerateRecoveryCodes">Generar nuevos códigos</flux:button>
    //   Resultado: se muestran los códigos vigentes recién generados.
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    /**
     * Load the recovery codes for the user.
     * @return void Descifra códigos si dos factores está activo y maneja errores dejando la lista vacía.
     */
    // Descifra códigos si dos factores está activo y maneja errores dejando la lista vacía.
    //   Ejemplo: se invoca al montar el componente y después de regenerarlos.
    //   Resultado: lista disponible o mensaje de error si no se pueden cargar.
    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}
