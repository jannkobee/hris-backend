<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = [
        'key',
        'value',
    ];
}
