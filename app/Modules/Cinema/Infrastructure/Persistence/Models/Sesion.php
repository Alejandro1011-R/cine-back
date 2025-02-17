<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Infrastructure\Persistence\Models;

use App\Modules\Catalog\Infrastructure\Persistence\Models\Pelicula;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Compra;
use App\Shared\Infrastructure\Persistence\Concerns\UsesCompositeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id_p
 * @property int $id_s
 * @property string $fecha
 */
class Sesion extends Model
{
    use UsesCompositeSoftDeletes;

    protected $table = 'sesiones';

    // Composite primary key - Eloquent does not natively support composite PKs
    // for find() etc, but we handle lookups manually.
    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'id_p',
        'id_s',
        'fecha',
    ];

    protected $casts = [
        'id_p' => 'integer',
        'id_s' => 'integer',
        'fecha' => 'datetime',
    ];

    /**
     * Override getKeyName for composite keys.
     */
    public function getKeyName(): string
    {
        return 'id_p';
    }

    protected function compositeKeyNames(): array
    {
        return ['id_p', 'id_s', 'fecha'];
    }

    public function pelicula(): BelongsTo
    {
        return $this->belongsTo(Pelicula::class, 'id_p', 'id_p');
    }

    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'id_s', 'id_s');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'id_p', 'id_p');
    }
}
