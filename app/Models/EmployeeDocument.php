<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use BelongsToOrganization, HasUuids;

    public const CATEGORIES = [
        'personal' => 'Personal records',
        'employment' => 'Employment records',
        'government' => 'Government and statutory',
        'education' => 'Education and training',
        'medical' => 'Medical records',
        'performance' => 'Performance records',
        'disciplinary' => 'Disciplinary records',
        'clearance' => 'Clearances',
        'other' => 'Other',
    ];

    protected $fillable = [
        'employee_id',
        'category',
        'visibility',
        'title',
        'document_number',
        'issued_at',
        'expires_at',
        'notes',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected $hidden = ['disk', 'path'];

    protected $casts = [
        'document_number' => 'encrypted',
        'notes' => 'encrypted',
        'issued_at' => 'date:Y-m-d',
        'expires_at' => 'date:Y-m-d',
        'size' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
