<?php

namespace App\Models;

use App\Casts\TimeOfDay;
use App\Traits\BelongsToOrganization;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Overtime extends Model
{
    use BelongsToOrganization, HasFactory, HasFilterScope, HasUuids, SoftDeletes;
    // TODO: also add whatever trait your other models use to provide the
    // `filter()` scope — BaseRepository::getList() calls
    // $this->model->filter() and this project doesn't seem to define that
    // on the base Eloquent Model, so it must come from a shared trait
    // (e.g. `use Filterable;`) that I don't have a copy of.

    // Non-incrementing string (uuid) primary key, matching the rest of the app.
    public $incrementing = false;

    protected $keyType = 'string';

    // Used by ResponseService for messages.*_success translations
    // ("Overtime fetched successfully", etc.) via BaseRepository.
    public string $model_name = 'Overtime';

    protected $fillable = [
        'employee_id',
        'date',
        'time_start',
        'time_end',
        'hours',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'time_start' => TimeOfDay::class,
        'time_end' => TimeOfDay::class,
        'hours' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * The employee who rendered the overtime.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * The user (typically an admin/supervisor) who approved or rejected the request.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
