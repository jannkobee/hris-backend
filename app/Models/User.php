<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Tenancy\TenantContext;
use App\Traits\BelongsToOrganization;
use App\Traits\HasFilterScope;
use App\Traits\Importable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use BelongsToOrganization, HasApiTokens, HasFactory, HasFilterScope, HasUuids, Importable, Notifiable;

    public $model_name = 'User';

    protected $fillable = [
        'role_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'scim_external_id',
        'is_active',
        'gender',
        'birthday',
        'password',
        'profile_photo_disk',
        'profile_photo_path',
        'profile_photo_name',
        'profile_photo_mime',
        'profile_photo_size',
    ];

    protected array $filterable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'gender',
        'birthday',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'profile_photo_disk',
        'profile_photo_path',
    ];

    protected $casts = [
        'birthday' => 'date:Y-m-d',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'hashed',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
    ];

    protected $appends = ['initials', 'full_name', 'profile_photo_url'];

    public function getInitialsAttribute(): string
    {
        $firstInitial = $this->first_name[0] ?? '';
        $lastInitial = $this->last_name[0] ?? '';

        return strtoupper($firstInitial.$lastInitial);
    }

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter()
            ->join(' ');
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        return "/users/{$this->id}/profile-photo?v=".($this->updated_at?->timestamp ?? time());
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(UserSetting::class);
    }

    public function hasPermission(string $permission): bool
    {
        $role = $this->role;

        if ($role?->name === 'Admin') {
            return true;
        }

        $role?->loadMissing('permissions');

        return (bool) $role?->permissions->contains('slug', $permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return collect($permissions)->contains(
            fn (string $permission) => $this->hasPermission($permission)
        );
    }

    public static function importColumns(): array
    {
        $organizationId = app(TenantContext::class)->organization()->getKey();

        return [
            'first_name' => [
                'label' => 'First Name',
                'attribute' => 'first_name',
                'rules' => 'required|string|max:255',
            ],
            'middle_name' => [
                'label' => 'Middle Name',
                'attribute' => 'middle_name',
                'rules' => 'nullable|string|max:255',
            ],
            'last_name' => [
                'label' => 'Last Name',
                'attribute' => 'last_name',
                'rules' => 'required|string|max:255',
            ],
            'email' => [
                'label' => 'Email',
                'attribute' => 'email',
                'rules' => [
                    'required',
                    'email',
                    Rule::unique('users', 'email')
                        ->where(fn ($query) => $query->where('organization_id', $organizationId)),
                ],
            ],
            'gender' => [
                'label' => 'Gender',
                'attribute' => 'gender',
                'rules' => 'required|in:male,female',
            ],
            'birthday' => [
                'label' => 'Birthday',
                'attribute' => 'birthday',
                'rules' => 'required|date',
            ],
            'role' => [
                'label' => 'Role',
                'attribute' => 'role_id',
                'rules' => [
                    'required',
                    Rule::exists('roles', 'name')
                        ->where(fn ($query) => $query->where('organization_id', $organizationId)),
                ],
                'resolve' => fn ($value) => Role::where('name', $value)->value('id'),
            ],
            'password' => [
                'label' => 'Password (optional)',
                'attribute' => 'password',
                'rules' => 'nullable|string|min:8',
                'default' => fn () => Str::random(12),
            ],
        ];
    }
}
