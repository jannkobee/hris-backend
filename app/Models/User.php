<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, HasFilterScope;

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

    protected $appends = ['initials'];

    public function getInitialsAttribute(): string
    {
        $firstInitial = $this->first_name[0] ?? '';
        $lastInitial = $this->last_name[0] ?? '';
        return strtoupper($firstInitial . $lastInitial);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
