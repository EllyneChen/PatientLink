<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false; // uses its own 'timestamp' column

    protected $fillable = [
        'actor_id',
        'action',
        'outcome',
        'entity_type',
        'entity_id',
        'metadata',
        'timestamp',
    ];

    protected $casts = [
        'metadata' => 'array',
        'timestamp' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * NFR-07: convenience helper for writing an immutable audit entry
     * from anywhere in the application.
     *
     * Usage:
     *   AuditLog::record($userId, 'LOGIN', 'success');
     *   AuditLog::record($userId, 'CONSENT_REQUEST', 'success', 'ConsentRecord', $consent->id, ['nupi' => $nupi]);
     */
    public static function record(
        string $actorId,
        string $action,
        string $outcome,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'actor_id'    => $actorId,
            'action'      => $action,
            'outcome'     => $outcome,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'metadata'    => $metadata,
            'timestamp'   => now(),
        ]);
    }
}
