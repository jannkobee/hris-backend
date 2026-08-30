<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ScimToken extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['name', 'token_hash', 'created_by', 'last_used_at', 'expires_at'];

    protected $hidden = ['token_hash'];

    protected $casts = ['last_used_at' => 'datetime', 'expires_at' => 'datetime'];
}
