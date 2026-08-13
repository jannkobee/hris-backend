<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

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
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, HasFilterScope, Importable;

    public $model_name = 'User';

    protected $fillable = [
        'role_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'gender',
        'birthday',
        'password',
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
        'remember_token',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'password' => 'hashed',
    ];

    protected $appends = ['initials', 'full_name'];

    public function getInitialsAttribute(): string
    {
        $firstInitial = $this->first_name[0] ?? '';
        $lastInitial = $this->last_name[0] ?? '';
        return strtoupper($firstInitial . $lastInitial);
    }

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter()
            ->join(' ');
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
        if ($this->role?->name === 'Admin') {
            return true;
        }

        $permissions = $this->role?->relationLoaded('permissions')
            ? $this->role->permissions
            : $this->role?->permissions()->get();

        return (bool) $permissions?->contains('slug', $permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return collect($permissions)->contains(
            fn (string $permission) => $this->hasPermission($permission)
        );
    }

    public static function importColumns(): array
    {
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
                'rules' => 'required|email|unique:users,email',
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
                'rules' => 'required|exists:roles,name',
                'resolve' => fn($value) => Role::where('name', $value)->value('id'),
            ],
            'password' => [
                'label' => 'Password (optional)',
                'attribute' => 'password',
                'rules' => 'nullable|string|min:8',
                'default' => fn() => Str::random(12),
            ],
        ];
    }
}
