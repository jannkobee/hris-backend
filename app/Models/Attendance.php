<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToOrganization, HasUuids, HasFilterScope;

    public $model_name = 'Attendance';

    protected $fillable = [
        'employee_id',
        'date',
        'time_in',
        'time_in_notes',
        'time_in_latitude',
        'time_in_longitude',
        'time_in_accuracy',
        'time_in_photo_disk',
        'time_in_photo_path',
        'time_in_photo_name',
        'time_in_photo_mime',
        'time_in_photo_size',
        'time_out',
        'time_out_notes',
        'time_out_latitude',
        'time_out_longitude',
        'time_out_accuracy',
        'time_out_photo_disk',
        'time_out_photo_path',
        'time_out_photo_name',
        'time_out_photo_mime',
        'time_out_photo_size',
        'ip_address',
        'time_out_ip_address',
    ];

    protected $hidden = [
        'time_in_photo_disk',
        'time_in_photo_path',
        'time_out_photo_disk',
        'time_out_photo_path',
    ];

    protected $appends = [
        'has_time_in_photo',
        'has_time_out_photo',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'time_in_latitude' => 'float',
        'time_in_longitude' => 'float',
        'time_in_accuracy' => 'float',
        'time_out_latitude' => 'float',
        'time_out_longitude' => 'float',
        'time_out_accuracy' => 'float',
    ];

    protected array $filterable = [
        'date',
        'time_in_notes',
        'time_out_notes',
        'employee.first_name',
        'employee.last_name',
        'employee.email',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getHasTimeInPhotoAttribute(): bool
    {
        return filled($this->time_in_photo_path);
    }

    public function getHasTimeOutPhotoAttribute(): bool
    {
        return filled($this->time_out_photo_path);
    }
}
