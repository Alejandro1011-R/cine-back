<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class RoleChangeAudit extends Model
{
    protected $table = 'role_change_audits';

    protected $fillable = [
        'actor_ci',
        'target_ci',
        'old_role',
        'new_role',
    ];
}
