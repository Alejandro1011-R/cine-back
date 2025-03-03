<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Audit;

use Illuminate\Database\Eloquent\Model;

final class AuditLog extends Model
{
    protected $fillable = [
        'actor_ci',
        'action',
        'auditable_type',
        'auditable_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
