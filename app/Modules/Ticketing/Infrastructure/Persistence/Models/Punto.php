<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id_pg
 * @property int|null $gastados
 */
class Punto extends Model
{
    use SoftDeletes;

    protected $table = 'puntos_pagos';

    protected $primaryKey = 'id_pg';

    public $incrementing = false;

    protected $fillable = [
        'id_pg',
        'gastados',
    ];

    protected $casts = [
        'gastados' => 'integer',
    ];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'id_pg', 'id_pg');
    }
}
