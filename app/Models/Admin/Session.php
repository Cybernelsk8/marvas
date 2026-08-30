<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
    ];

    protected $appends = ['browser','os'];

    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function getBrowserAttribute(): string {
        $ua = $this->user_agent ?? '';

        if (stripos($ua, 'Chrome') !== false && stripos($ua, 'Brave') === false) {
            return 'Chrome';
        }

        if (stripos($ua, 'Brave') !== false) {
            return 'Brave';
        }

        if (stripos($ua, 'Firefox') !== false) {
            return 'Firefox';
        }

        if (stripos($ua, 'Safari') !== false && stripos($ua, 'Chrome') === false) {
            return 'Safari';
        }

        if (stripos($ua, 'Edg') !== false) { // Edge moderno
            return 'Edge';
        }

        return 'Desconocido';
    }

    public function getOsAttribute(): string {
        $ua = $this->user_agent ?? '';

        if (stripos($ua, 'Windows') !== false) {
            return 'Windows';
        }
        if (stripos($ua, 'Mac OS X') !== false) {
            return 'macOS';
        }
        if (stripos($ua, 'Android') !== false) {
            return 'Android';
        }
        if (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
            return 'iOS';
        }
        if (stripos($ua, 'Linux') !== false) {
            return 'Linux';
        }

        return 'Desconocido';
    }
}
