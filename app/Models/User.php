<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saved(fn (self $user) => Cache::forget('auth_user_'.$user->getKey()));
        static::deleted(fn (self $user) => Cache::forget('auth_user_'.$user->getKey()));
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'group_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function readingProgress()
    {
        return $this->hasMany(UserItemProgress::class);
    }

    public function problemProgress()
    {
        return $this->hasMany(UserProblemProgress::class);
    }
}
