<?php

namespace App\Models\Admin;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\ArchivoClinico;
use App\Models\Admin\Area;
use App\Models\Auditoria;
use App\Models\Cita;
use App\Models\ClasificacionPaciente;
use App\Models\MovimientoInventario;
use App\Models\Notificacion;
use App\Models\OrdenCompra;
use App\Models\Pago;
use App\Models\Profesional;
use App\Traits\Searchable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

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
#[Fillable(['email', 'password', 'area_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
#[Appends(['nombre_corto'])]

class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable, Searchable, HasRoles, SoftDeletes;

    public const DEFAULTPASS = 'password';

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
        $initials = Str::initials($this->information?->nombre_corto, true);

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

    public function getMenuAttribute()
    {
        $allowedPermissions = $this->getAllPermissions()
            ->filter(fn($permission) => Str::startsWith($permission->name, 'page.view'))
            ->pluck('name')
            ->toArray();

        if (empty($allowedPermissions)) {
            return [];
        }

        $pages = Page::with(['parent', 'children'])
            ->whereNull('deleted_at')
            ->orderBy('order')
            ->get();

        $allowedPages = $pages->filter(function ($page) use ($allowedPermissions) {
            return in_array($page->permission_name, $allowedPermissions);
        });

        $menu = collect();

        foreach ($allowedPages as $page) {

            if ($page->parent) {
                $menu->push($page->parent);
            }

            if (!$page->parent) {
                $menu->push($page);
            }
        }

        $menu = $menu->unique('id')->sortBy('order')->values();

        $menu->each(function ($parent) use ($allowedPages) {

            $parent->childrens = $allowedPages
                ->where('page_id', $parent->id)
                ->sortBy('order')
                ->values();
        });

        return $menu->values()->all();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function information(): HasOne
    {
        return $this->hasOne(UserInformation::class);
    }

    public function getNombreCortoAttribute()
    {
        return $this->information?->nombre_corto ?? null;
    }

    public function getUrlPhotoAttribute()
    {
        return $this->information?->url_photo ?? null;
    }

    public function getRoleNameAttribute()
    {
        return $this->roles->pluck('name')->first();
    }
}
