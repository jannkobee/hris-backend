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

    public const PLAN_STARTER = 'starter';

    public const PLAN_GROWTH = 'growth';

    public const PLAN_BUSINESS = 'business';

    public const PLAN_ENTERPRISE = 'enterprise';

    protected $fillable = [
        'slug',
        'name',
        'brand_logo_disk',
        'brand_logo_path',
        'brand_logo_name',
        'brand_logo_mime',
        'brand_logo_size',
        'timezone',
        'country_code',
        'plan_code',
        'status',
        'subscription_status',
        'trial_ends_at',
        'current_period_ends_at',
        'employee_limit',
        'billing_provider', 'billing_customer_id', 'billing_subscription_id', 'billing_interval',
        'offboarding_requested_at', 'offboarding_scheduled_at', 'offboarding_reason',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
        'employee_limit' => 'integer',
        'offboarding_requested_at' => 'datetime',
        'offboarding_scheduled_at' => 'datetime',
    ];

    protected $hidden = [
        'brand_logo_disk',
        'brand_logo_path',
        'brand_logo_name',
        'brand_logo_mime',
        'brand_logo_size',
    ];

    protected $appends = ['brand_logo_url'];

    public function getBrandLogoUrlAttribute(): ?string
    {
        if (! $this->brand_logo_path) {
            return null;
        }

        return '/organization/branding/logo?v='.sha1($this->brand_logo_path);
    }

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

    public function ownerInvitations(): HasMany
    {
        return $this->hasMany(OrganizationOwnerInvitation::class);
    }

    public function dataExports(): HasMany
    {
        return $this->hasMany(OrganizationDataExport::class);
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
