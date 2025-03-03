<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Audit;

final class AuditLogger
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function record(
        ?string $actorCi,
        string $action,
        ?string $auditableType = null,
        ?string $auditableId = null,
        array $metadata = [],
    ): void {
        AuditLog::create([
            'actor_ci' => $actorCi,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'metadata' => $metadata,
        ]);
    }
}
