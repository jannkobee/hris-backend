<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SsoConfiguration extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['provider', 'issuer_url', 'client_id', 'client_secret', 'scopes', 'is_active'];

    protected $hidden = ['client_secret'];

    protected $casts = ['client_secret' => 'encrypted', 'scopes' => 'array', 'is_active' => 'boolean'];
}
