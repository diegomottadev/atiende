<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'plan', 'stripe_customer_id', 'stripe_subscription_id', 'mp_preapproval_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const FREE_TEMPLATE_LIMIT = 5;

    protected $attributes = [
        'plan' => 'free',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function isPro(): bool
    {
        return $this->plan === 'pro';
    }

    public function templateLimit(): ?int
    {
        return $this->isPro() ? null : self::FREE_TEMPLATE_LIMIT;
    }
}
