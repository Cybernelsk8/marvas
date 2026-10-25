<?php

namespace App\Livewire\Settings;

use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Gestiona la consulta y actualización del perfil de la cuenta autenticada.
 */
#[Title('Profile settings')]
class Profile extends Component
{
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    /**
     * Mount the component.
     * @return void Livewire lo ejecuta al montar la pantalla para precargar los campos del perfil.
     */
    // Livewire lo ejecuta al montar la pantalla para precargar los campos del perfil.
    //   Ejemplo: el usuario abre Configuración de perfil.
    //   Resultado: nombre y correo reflejan los datos actuales de su cuenta.
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     * @return void Valida y guarda el perfil; si cambia el correo, solicita verificar la dirección nueva.
     */
    // Valida y guarda el perfil; si cambia el correo, solicita verificar la dirección nueva.
    //   Desde la vista: <flux:button wire:click="updateProfileInformation">Guardar</flux:button>
    //   Resultado: perfil actualizado y notificación de éxito.
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     * @return void Reenvía el enlace si el correo no está verificado o dirige al dashboard si ya lo está.
     */
    // Reenvía el enlace si el correo no está verificado o dirige al dashboard si ya lo está.
    //   Desde la vista: <flux:button wire:click="resendVerificationNotification">Reenviar enlace</flux:button>
    //   Resultado: se envía una notificación o se redirige al destino previsto.
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(text: __('A new verification link has been sent to your email address.'));
    }

    /**
     * Indica si el usuario requiere completar la verificación de correo.
     * @return bool Indica si el usuario requiere completar la verificación de correo.
     */
    // Indica si el usuario requiere completar la verificación de correo.
    //   Desde la vista: @if ($this->hasUnverifiedEmail) ... @endif
    //   Resultado: true solo para una cuenta verificable aún pendiente.
    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        $user = Auth::user();

        return $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail();
    }

    /**
     * Decide si se puede mostrar la opción de eliminar la cuenta.
     * @return bool Decide si se puede mostrar la opción de eliminar la cuenta.
     */
    // Decide si se puede mostrar la opción de eliminar la cuenta.
    //   Desde la vista: @if ($this->showDeleteUser) ... @endif
    //   Resultado: true si no se exige verificación o el correo ya fue verificado.
    #[Computed]
    public function showDeleteUser(): bool
    {
        $user = Auth::user();

        return ! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail();
    }
}
