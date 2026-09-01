<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingCourse extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['name', 'description', 'requires_certificate'];

    protected $casts = ['requires_certificate' => 'boolean'];
}
