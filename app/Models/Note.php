<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use LogicException;

class Note extends Model
{
    use BelongsToOrganization, HasFilterScope, HasUuids;

    public $model_name = 'Note';

    protected $fillable = [
        'title',
        'content',
        'category',
        'color',
        'is_pinned',
        'is_archived',
    ];

    protected array $filterable = [
        'title',
        'content',
        'category',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_archived' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $builder): void {
            $userId = Auth::id();

            $userId
                ? $builder->where($builder->getModel()->qualifyColumn('created_by'), $userId)
                : $builder->whereRaw('1 = 0');
        });

        static::creating(function (Note $note): void {
            if (! Auth::id()) {
                throw new LogicException('Personal notes cannot be created without an authenticated user.');
            }

            $note->setAttribute('created_by', Auth::id());
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
