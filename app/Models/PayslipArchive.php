<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipArchive extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['payroll_item_id', 'disk', 'path', 'mime_type', 'checksum', 'archived_by', 'archived_at'];

    protected $hidden = ['disk', 'path', 'checksum'];

    protected $casts = ['archived_at' => 'datetime'];
}
