<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id_pg
 * @property float|null $cantidad_e
 */
class Efectivo extends Model
{
    use SoftDeletes;

    protected $table = 'efectivos';

    protected $primaryKey = 'id_pg';

    public $incrementing = false;

    protected $fillable = [
        'id_pg',
        'cantidad_e',
    ];

    protected $casts = [
        'cantidad_e' => 'decimal:2',
    ];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'id_pg', 'id_pg');
    }
}
