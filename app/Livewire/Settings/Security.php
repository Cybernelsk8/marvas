<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use Exception;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Gestiona contraseña, passkeys y autenticación de dos factores.
 */
#[Title('Security settings')]
class Security extends Component
{
    use PasswordValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showModal = false;

    public bool $showVerificationStep = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public bool $canManagePasskeys;

    /**
     * @var array<int, array{id: int, name: string, authenticator: string|null, created_at_diff: string, last_used_at_diff: string|null}>
     */
    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    /**
     * Mount the component.
     * @param DisableTwoFactorAuthentication $disableTwoFactorAuthentication Dato recibido por el componente.
     * @return void Livewire lo ejecuta al montar Seguridad para detectar funciones y cargar passkeys.
     */
    // Livewire lo ejecuta al montar Seguridad para detectar funciones y cargar passkeys.
    //   Ejemplo: abrir la pantalla con passkeys habilitadas carga las credenciales guardadas.
    //   Resultado: indicadores de dos factores y passkeys reflejan la configuración real.
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
    }

    /**
     * Livewire lo invoca para mostrar formularios y modales con el estado de seguridad actual.
     * @return \Illuminate\Contracts\View\View Livewire lo invoca para mostrar formularios y modales con el estado de seguridad actual.
     */
    // Livewire lo invoca para mostrar formularios y modales con el estado de seguridad actual.
    //   Ejemplo: tras guardar una contraseña se vuelve a renderizar esta pantalla.
    //   Resultado: se devuelve la vista de configuración de seguridad.
    public function render()
    {
        return view('livewire.settings.security');
    }

    /**
     * Update the password for the currently authenticated user.
     * @return void Valida la contraseña actual y guarda la nueva, limpiando los campos del formulario.
     */
    // Valida la contraseña actual y guarda la nueva, limpiando los campos del formulario.
    //   Desde la vista: <flux:button wire:click="updatePassword">Actualizar contraseña</flux:button>
    //   Resultado: contraseña actualizada y notificación de éxito.
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }

    /**
     * Load the user's passkeys.
     * @return void Carga las passkeys del usuario con fechas relativas para la lista de pantalla.
     */
    // Carga las passkeys del usuario con fechas relativas para la lista de pantalla.
    //   Ejemplo: se invoca al montar la página y tras eliminar una credencial.
    //   Resultado: lista ordenada de passkeys con datos de presentación.
    public function loadPasskeys(): void
    {
        $this->passkeys = Auth::user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->all();
    }

    /**
     * Show the delete confirmation modal.
     * @param int $passkeyId Dato recibido por el componente.
     * @return void Selecciona la passkey del usuario y abre el modal de confirmación.
     */
    // Selecciona la passkey del usuario y abre el modal de confirmación.
    //   Desde la vista: <flux:button wire:click="confirmDelete({{ $passkey->id }})">Eliminar</flux:button>
    //   Resultado: el modal muestra el nombre de la credencial seleccionada.
    public function confirmDelete(int $passkeyId): void
    {
        $passkey = Auth::user()->passkeys()->findOrFail($passkeyId);

        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the passkey.
     * @param DeletePasskey $deletePasskey Dato recibido por el componente.
     * @return void Elimina la passkey seleccionada mediante la acción de Laravel Passkeys.
     */
    // Elimina la passkey seleccionada mediante la acción de Laravel Passkeys.
    //   Desde la vista: <flux:button wire:click="deletePasskey">Confirmar eliminación</flux:button>
    //   Resultado: modal cerrado y lista recargada sin la credencial borrada.
    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->deletingPasskeyId) {
            return;
        }

        $user = Auth::user();
        $passkey = $user->passkeys()->findOrFail($this->deletingPasskeyId);

        $deletePasskey($user, $passkey);

        $this->closeDeleteModal();
        $this->loadPasskeys();
    }

    /**
     * Close the delete confirmation modal.
     * @return void Cierra el modal y reinicia los datos de la passkey en proceso de eliminación.
     */
    // Cierra el modal y reinicia los datos de la passkey en proceso de eliminación.
    //   Desde la vista: <flux:button wire:click="closeDeleteModal">Volver</flux:button>
    //   Resultado: modal oculto y selección vacía.
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }

    /**
     * Enable two-factor authentication for the user.
     * @param EnableTwoFactorAuthentication $enableTwoFactorAuthentication Dato recibido por el componente.
     * @return void Inicia la activación de dos factores y muestra los datos de configuración.
     */
    // Inicia la activación de dos factores y muestra los datos de configuración.
    //   Desde la vista: <flux:button wire:click="enable">Activar autenticación</flux:button>
    //   Resultado: se prepara el QR y la clave manual para el usuario.
    public function enable(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $enableTwoFactorAuthentication(auth()->user());

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }

        $this->loadSetupData();

        $this->showModal = true;
    }

    /**
     * Load the two-factor authentication setup data for the user.
     * @return void Obtiene el QR y descifra la clave manual de configuración de dos factores.
     */
    // Obtiene el QR y descifra la clave manual de configuración de dos factores.
    //   Ejemplo: se invoca al abrir el proceso de activación.
    //   Resultado: datos listos para mostrar o propiedades vacías con error si falla el descifrado.
    private function loadSetupData(): void
    {
        $user = auth()->user();

        try {
            $this->qrCodeSvg = $user?->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     * @return void Avanza a verificación cuando Fortify la requiere; de lo contrario cierra el modal.
     */
    // Avanza a verificación cuando Fortify la requiere; de lo contrario cierra el modal.
    //   Desde la vista: <flux:button wire:click="showVerificationIfNecessary">Continuar</flux:button>
    //   Resultado: se muestra el paso del código o termina el flujo de configuración.
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
    }

    /**
     * Confirm two-factor authentication for the user.
     * @param ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication Dato recibido por el componente.
     * @return void Valida el código del autenticador y confirma la activación con Fortify.
     */
    // Valida el código del autenticador y confirma la activación con Fortify.
    //   Desde la vista: <flux:button wire:click="confirmTwoFactor">Verificar código</flux:button>
    //   Resultado: modal cerrado y autenticación de dos factores habilitada.
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->closeModal();

        $this->twoFactorEnabled = true;
    }

    /**
     * Reset two-factor verification state.
     * @return void Limpia el código, el paso de verificación y los errores actuales.
     */
    // Limpia el código, el paso de verificación y los errores actuales.
    //   Desde la vista: <flux:button wire:click="resetVerification">Cancelar verificación</flux:button>
    //   Resultado: formulario reiniciado para volver a empezar.
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Disable two-factor authentication for the user.
     * @param DisableTwoFactorAuthentication $disableTwoFactorAuthentication Dato recibido por el componente.
     * @return void Desactiva la autenticación de dos factores para el usuario autenticado.
     */
    // Desactiva la autenticación de dos factores para el usuario autenticado.
    //   Desde la vista: <flux:button wire:click="disable">Desactivar autenticación</flux:button>
    //   Resultado: Fortify la desactiva y el indicador queda en false.
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }

    /**
     * Close the two-factor authentication modal.
     * @return void Cierra el modal y limpia el código, QR, clave y estado temporal.
     */
    // Cierra el modal y limpia el código, QR, clave y estado temporal.
    //   Desde la vista: <flux:button wire:click="closeModal">Cerrar</flux:button>
    //   Resultado: propiedades del flujo quedan reiniciadas.
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showModal',
            'showVerificationStep',
        );

        $this->resetErrorBag();

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }
    }

    /**
     * Get the current modal configuration state.
     * @return array{title: string, description: string, buttonText: string}
     */
    // Devuelve textos del modal acordes con el estado de configuración o verificación.
    //   Desde la vista: {{ $this->modalConfig['title'] }}
    //   Resultado: arreglo con título, descripción y texto de botón para el paso actual.
    #[Computed]
    public function modalConfig(): array
    {
        if ($this->twoFactorEnabled) {
            return [
                'title' => __('Two-factor authentication enabled'),
                'description' => __('Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'),
                'buttonText' => __('Close'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Verify authentication code'),
                'description' => __('Enter the 6-digit code from your authenticator app.'),
                'buttonText' => __('Continue'),
            ];
        }

        return [
            'title' => __('Enable two-factor authentication'),
            'description' => __('To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.'),
            'buttonText' => __('Continue'),
        ];
    }
}
