<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Inquiry;
use App\Models\Reservation;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avg_rating',
        'reviews_count',
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

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewed_user_id');
    }

    public function reservationsAsCustomer(): HasMany
    {
        return $this->hasMany(Reservation::class, 'customer_id');
    }

    public function reservationsAsProvider(): HasMany
    {
        return $this->hasMany(Reservation::class, 'provider_id');
    }

    public function inquiriesAsCustomer(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'customer_id');
    }

    public function inquiriesAsProvider(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'provider_id');
    }
}
