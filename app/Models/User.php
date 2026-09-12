<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $google_id
 * @property bool $is_admin
 * @property Carbon|null $email_verified_at
 * @property string|null $workos_id
 * @property string|null $remember_token
 * @property string|null $avatar
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 * @property-read Collection<int, Application> $applications
 */
#[Fillable(['name', 'email', 'google_id', 'is_admin', 'workos_id', 'avatar', 'current_team_id'])]
#[Hidden(['workos_id', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable;

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
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Applications this user has access to.
     */
    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'application_user')
            ->withPivot('role_in_app')
            ->withTimestamps();
    }

    /**
     * Check if user has access to a given application.
     */
    public function hasAccessTo(Application $application): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->applications()->where('applications.id', $application->id)->where('is_active', true)->exists();
    }
}

