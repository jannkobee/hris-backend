<?php

namespace App\Models\Logs;

use App\Traits\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditLog extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = [
        'user_id',
        'user_full_name',
        'action',
        'module',
        'payload',
        'result',
        'ip_address',
        'http_method',
        'route_name',
    ];

    protected $casts = [
        'payload' => 'array',
        'retention_until' => 'immutable_datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $auditLog): void {
            $organizationId = (string) $auditLog->organization_id;
            $previousHash = static::query()
                ->where('organization_id', $organizationId)
                ->whereNotNull('integrity_hash')
                ->latest('created_at')
                ->latest('id')
                ->value('integrity_hash');

            $auditLog->previous_hash = $previousHash;
            $auditLog->retention_until ??= CarbonImmutable::now()
                ->addYears((int) config('audit.retention_years', 7));
            $auditLog->integrity_hash = hash_hmac(
                'sha256',
                $auditLog->integrityPayload(),
                (string) config('audit.signing_key')
            );
        });

        static::updating(function (): void {
            throw new LogicException('Audit logs are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new LogicException('Audit logs are immutable and cannot be deleted.');
        });
    }

    public function integrityPayload(): string
    {
        return json_encode([
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'user_full_name' => $this->user_full_name,
            'action' => $this->action,
            'module' => $this->module,
            'payload' => $this->payload,
            'result' => $this->result,
            'ip_address' => $this->ip_address,
            'http_method' => $this->http_method,
            'route_name' => $this->route_name,
            'previous_hash' => $this->previous_hash,
            'created_at' => $this->created_at?->toAtomString(),
            'retention_until' => $this->retention_until?->toAtomString(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    public function scopeFilter(Builder $query): Builder
    {
        $search = request('search') ?? false;

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('module', 'LIKE', "%{$search}%")
                    ->orWhere('user_id', 'LIKE', "%{$search}%")
                    ->orWhere('user_full_name', 'LIKE', "%{$search}%")
                    ->orWhere('action', 'LIKE', "%{$search}%")
                    ->orWhere('id', 'LIKE', "%{$search}%");

                $q->orWhere(function ($subQuery) use ($search) {
                    $subQuery->whereRaw('LOWER(payload) LIKE ?', ['%'.strtolower($search).'%']);
                });
            });
        }

        return $query;
    }
}
