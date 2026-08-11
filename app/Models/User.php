<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\Searchable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]

class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable, Searchable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1) . Str::substr($initials, -1)
            : $initials;
    }

    public function profesional(): HasOne
    {
        return $this->hasOne(Profesional::class, 'user_id');
    }

    public function citasAgendadas(): HasMany
    {
        return $this->hasMany(Cita::class, 'agendada_por');
    }

    public function movimientosInventarioRegistrados(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'registrado_por');
    }

    public function ordenesCompraSolicitadas(): HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'solicitado_por');
    }

    public function clasificacionesRealizadas(): HasMany
    {
        return $this->hasMany(ClasificacionPaciente::class, 'clasificado_por');
    }

    public function archivosSubidos(): HasMany
    {
        return $this->hasMany(ArchivoClinico::class, 'subido_por');
    }

    public function pagosCobrados(): HasMany
    {
        return $this->hasMany(Pago::class, 'cobrado_por');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'user_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
    }
}
