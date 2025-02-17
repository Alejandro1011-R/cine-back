<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id_s
 * @property int|null $capacidad
 */
class Sala extends Model
{
    use SoftDeletes;

    protected $table = 'salas';

    protected $primaryKey = 'id_s';

    protected $fillable = [
        'id_c',
        'capacidad',
    ];

    protected $casts = [
        'id_c' => 'integer',
        'capacidad' => 'integer',
    ];

    public function cine(): BelongsTo
    {
        return $this->belongsTo(Cine::class, 'id_c', 'id_c');
    }

    public function butacas(): HasMany
    {
        return $this->hasMany(Butaca::class, 'id_s', 'id_s');
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'id_s', 'id_s');
    }
}
