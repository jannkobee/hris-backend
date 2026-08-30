<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const SUBSCRIPTION_TRIALING = 'trialing';

    public const SUBSCRIPTION_ACTIVE = 'active';

    public const SUBSCRIPTION_PAST_DUE = 'past_due';

    public const SUBSCRIPTION_SUSPENDED = 'suspended';

    public const SUBSCRIPTION_CANCELLED = 'cancelled';

    public const SUBSCRIPTION_STATUSES = [
        self::SUBSCRIPTION_TRIALING,
        self::SUBSCRIPTION_ACTIVE,
        self::SUBSCRIPTION_PAST_DUE,
        self::SUBSCRIPTION_SUSPENDED,
        self::SUBSCRIPTION_CANCELLED,
    ];

    public const PLAN_BASIC = 'basic';

    public const PLAN_ENTERPRISE = 'enterprise';

    protected $fillable = [
        'slug',
        'name',
        'timezone',
        'country_code',
        'plan_code',
        'status',
        'subscription_status',
        'trial_ends_at',
        'current_period_ends_at',
        'employee_limit',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
        'employee_limit' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function appSettings(): HasMany
    {
        return $this->hasMany(AppSetting::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function subscriptionAllowsAccess(): bool
    {
        $subscriptionStatus = $this->subscription_status ?: self::SUBSCRIPTION_ACTIVE;

        if (in_array($subscriptionStatus, [self::SUBSCRIPTION_ACTIVE, self::SUBSCRIPTION_PAST_DUE], true)) {
            return true;
        }

        return $subscriptionStatus === self::SUBSCRIPTION_TRIALING
            && ($this->trial_ends_at === null || $this->trial_ends_at->isFuture());
    }
}
