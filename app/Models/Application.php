<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'base_url',
        'callback_url',
        'client_id',
        'client_secret',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Application $application) {
            if (empty($application->slug)) {
                $application->slug = Str::slug($application->name);
            }
            if (empty($application->client_id)) {
                $application->client_id = 'app_' . Str::random(24);
            }
            if (empty($application->client_secret)) {
                $application->client_secret = Str::random(48);
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'application_user')
            ->withPivot('role_in_app')
            ->withTimestamps();
    }

    public function ssoTickets(): HasMany
    {
        return $this->hasMany(SsoTicket::class);
    }
}
